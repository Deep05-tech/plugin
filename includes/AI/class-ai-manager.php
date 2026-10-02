<?php
/**
 * AI Generation Subsystem Orchestrator & Provider Registry.
 *
 * Manages provider registration, concurrency locking, generation lifecycles,
 * and version persistence for SEO City Pages.
 *
 * @package SeoCityPages\AI
 */

namespace SeoCityPages\AI;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Admin\Settings;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI_Manager Class.
 */
class AI_Manager {

	private static ?self $instance = null;

	/**
	 * Registered AI providers.
	 *
	 * @var array<string, AI_Provider_Interface>
	 */
	private array $providers = [];

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->register_default_providers();
	}

	/**
	 * Register default AI providers.
	 *
	 * @return void
	 */
	private function register_default_providers(): void {
		$this->register_provider( new OpenAI_Provider() );
	}

	/**
	 * Register an AI provider implementation.
	 *
	 * @param AI_Provider_Interface $provider Provider instance.
	 * @return void
	 */
	public function register_provider( AI_Provider_Interface $provider ): void {
		$this->providers[ $provider->get_identifier() ] = $provider;
	}

	/**
	 * Retrieve a registered AI provider by identifier.
	 *
	 * @param string $identifier Provider slug (default 'openai').
	 * @return AI_Provider_Interface|null
	 */
	public function get_provider( string $identifier = 'openai' ): ?AI_Provider_Interface {
		return $this->providers[ $identifier ] ?? null;
	}

	/**
	 * Check if AI generation is enabled in admin settings and safe mode is not active.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		if ( \SeoCityPages\Diagnostics\Safe_Mode_Manager::is_safe_mode_active() ) {
			return false;
		}
		$settings = get_option( Settings::OPTION_NAME, [] );
		return ! empty( $settings['ai_enabled'] );
	}

	/**
	 * High-level method: Generate content for an individual SEO City Page.
	 *
	 * @param int   $page_id Page ID.
	 * @param array $options Runtime options (force_regenerate, model, etc.).
	 * @return Generation_Result
	 */
	public function generate_for_page( int $page_id, array $options = [] ): Generation_Result {
		if ( \SeoCityPages\Diagnostics\Safe_Mode_Manager::is_safe_mode_active() ) {
			return Generation_Result::failure(
				__( 'Plugin Safe Mode is currently active. AI generation is disabled.', 'seo-city-pages' ),
				'safe_mode_active'
			);
		}

		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return Generation_Result::failure(
				__( 'SEO City Page does not exist.', 'seo-city-pages' ),
				'page_not_found'
			);
		}

		// 1. Acquire generation lock to prevent race conditions.
		$lock_key = 'scp_ai_lock_' . $page_id;
		if ( get_transient( $lock_key ) && empty( $options['force_lock'] ) ) {
			return Generation_Result::failure(
				__( 'This page is currently undergoing generation in another process. Please wait a moment.', 'seo-city-pages' ),
				'locked'
			);
		}
		set_transient( $lock_key, time(), 120 ); // 2 min auto-expiry lock.

		// Update page status to generating.
		$pages_table = \SeoCityPages\Database\Schema::get_table_name( \SeoCityPages\Database\Schema::TABLE_CITY_PAGES );
		global $wpdb;
		$wpdb->update( $pages_table, [ 'generation_status' => 'generating' ], [ 'id' => $page_id ] );

		try {
			// 2. Build Context.
			$context = AI_Context::from_page_id( $page_id, $options );
			if ( ! $context ) {
				delete_transient( $lock_key );
				return Generation_Result::failure( __( 'Unable to construct page context.', 'seo-city-pages' ), 'context_error' );
			}

			// 3. Dispatch to Provider.
			$provider_id = $options['provider'] ?? 'openai';
			$provider    = $this->get_provider( $provider_id );
			if ( ! $provider ) {
				delete_transient( $lock_key );
				return Generation_Result::failure(
					sprintf( __( 'AI Provider "%s" is not registered.', 'seo-city-pages' ), esc_html( $provider_id ) ),
					'invalid_provider'
				);
			}

			$result = $provider->generate( $context, $options );

			if ( ! $result->is_success() ) {
				// Failed generation -> log error and restore status.
				$existing_approved = Content_Version_Repository::get_current_approved_version( $page_id );
				$fallback_status   = $existing_approved ? Content_Version_Repository::STATUS_APPROVED : Content_Version_Repository::STATUS_FAILED;

				$wpdb->update( $pages_table, [ 'generation_status' => $fallback_status ], [ 'id' => $page_id ] );
				delete_transient( $lock_key );

				return $result;
			}

			// 4. Save Content Version record (default status = needs_review).
			$version_id = Content_Version_Repository::create( [
				'page_id'            => $page_id,
				'status'             => Content_Version_Repository::STATUS_NEEDS_REVIEW,
				'content_json'       => $result->get_content_json(),
				'content_hash'       => $result->get_content_hash(),
				'provider'           => $result->get_provider(),
				'model'              => $result->get_model(),
				'input_tokens'       => $result->get_input_tokens(),
				'output_tokens'      => $result->get_output_tokens(),
				'total_tokens'       => $result->get_total_tokens(),
				'generation_time_ms' => $result->get_duration_ms(),
			] );

			delete_transient( $lock_key );

			if ( $version_id <= 0 ) {
				return Generation_Result::failure( __( 'Failed to save generated content version into database.', 'seo-city-pages' ), 'db_version_error' );
			}

			Logger::info(
				sprintf( 'Generated Content Version #%d for Page #%d (status: needs_review)', $version_id, $page_id ),
				[ 'page_id' => $page_id, 'version_id' => $version_id ]
			);

			return $result;

		} catch ( \Throwable $e ) {
			delete_transient( $lock_key );
			Logger::critical( 'Unexpected exception during AI generation', [ 'error' => $e->getMessage(), 'page_id' => $page_id ] );
			return Generation_Result::failure( $e->getMessage(), 'exception' );
		}
	}

	/**
	 * Test connection for active provider.
	 *
	 * @param string $provider_id Provider slug.
	 * @return Connection_Result
	 */
	public function test_connection( string $provider_id = 'openai' ): Connection_Result {
		$provider = $this->get_provider( $provider_id );
		if ( ! $provider ) {
			return new Connection_Result( false, sprintf( __( 'Provider "%s" not found.', 'seo-city-pages' ), $provider_id ) );
		}

		return $provider->test_connection();
	}
}
