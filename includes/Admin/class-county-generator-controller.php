<?php
/**
 * Topic-Based County Batch Generator Admin Controller & AJAX Processor.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Database\County_Campaign_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Data\County_Dataset;
use SeoCityPages\Jobs\County_Batch_Runner;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * County Generator Controller Class.
 */
class County_Generator_Controller {

	/**
	 * Nonce action name.
	 */
	public const NONCE_ACTION = 'seo_county_generator_action';

	/**
	 * Constructor: Register AJAX handlers.
	 */
	public function __construct() {
		add_action( 'wp_ajax_seo_county_create_campaign', [ $this, 'ajax_create_campaign' ] );
		add_action( 'wp_ajax_seo_county_process_chunk', [ $this, 'ajax_process_chunk' ] );
		add_action( 'wp_ajax_seo_county_batch_action', [ $this, 'ajax_batch_action' ] );
		add_action( 'wp_ajax_seo_county_get_status', [ $this, 'ajax_get_status' ] );
	}

	/**
	 * Render the County Generator Admin View.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE );

		$selected_campaign_id = sanitize_text_field( $_GET['campaign_id'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$campaigns            = County_Campaign_Repository::get_recent_campaigns( 30 );
		$states               = County_Dataset::get_states();
		$services             = Service_Repository::get_all( [ 'status' => 'active' ] );
		$active_campaign      = null;
		$batches              = [];

		if ( ! empty( $selected_campaign_id ) ) {
			$active_campaign = County_Campaign_Repository::get_campaign( $selected_campaign_id );
			if ( $active_campaign ) {
				$batches = County_Campaign_Repository::get_campaign_batches( $selected_campaign_id );
			}
		} elseif ( ! empty( $campaigns ) ) {
			$active_campaign = $campaigns[0];
			$batches = County_Campaign_Repository::get_campaign_batches( $active_campaign->campaign_id );
		}

		$template = SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/county-generator.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * AJAX Handler: Create a new Topic County Campaign.
	 */
	public function ajax_create_campaign(): void {
		try {
			Security::verify_nonce_or_abort( self::NONCE_ACTION );
			Security::enforce_capability( Capabilities::MANAGE );

			$topic         = sanitize_text_field( $_POST['topic'] ?? '' );
			$service_id    = (int) ( $_POST['service_id'] ?? 0 );
			$target_states = sanitize_text_field( $_POST['target_states'] ?? 'ALL' );
			$batch_size    = (int) ( $_POST['batch_size'] ?? 100 );

			if ( empty( $topic ) ) {
				wp_send_json_error( [ 'message' => __( 'Please enter a Topic or Niche name (e.g. Dental Clinic, Law Firm).', 'seo-city-pages' ) ] );
			}

			$states_array = 'ALL' === $target_states ? 'ALL' : array_filter( array_map( 'trim', explode( ',', $target_states ) ) );

			$result = County_Campaign_Repository::create_campaign( [
				'topic'         => $topic,
				'service_id'    => $service_id,
				'target_states' => $states_array,
				'batch_size'    => $batch_size > 0 ? $batch_size : 100,
			] );

			if ( ! $result ) {
				wp_send_json_error( [ 'message' => __( 'Failed to create campaign. No counties found matching criteria.', 'seo-city-pages' ) ] );
			}

			wp_send_json_success( [
				'message'     => sprintf(
					__( 'Successfully created campaign "%1$s" with %2$d counties in %3$d batches of %4$d pages.', 'seo-city-pages' ),
					esc_html( $result['topic'] ),
					(int) $result['total_counties'],
					(int) $result['total_batches'],
					(int) $result['batch_size']
				),
				'campaign_id' => $result['campaign_id'],
				'campaign'    => $result,
			] );
		} catch ( \Throwable $e ) {
			Logger::error( 'County Campaign Creation Failed: ' . $e->getMessage(), [ 'exception' => $e ] );
			wp_send_json_error( [ 'message' => __( 'Server error creating campaign: ', 'seo-city-pages' ) . $e->getMessage() ] );
		}
	}

	/**
	 * AJAX Handler: Process next chunk for a batch.
	 */
	public function ajax_process_chunk(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::MANAGE );

		$job_id     = sanitize_text_field( $_POST['job_id'] ?? '' );
		$chunk_size = (int) ( $_POST['chunk_size'] ?? 5 );

		if ( empty( $job_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing batch job ID.', 'seo-city-pages' ) ] );
		}

		$result = County_Batch_Runner::process_chunk( $job_id, max( 1, min( 25, $chunk_size ) ) );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX Handler: Batch control actions (retry, publish, delete).
	 */
	public function ajax_batch_action(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::MANAGE );

		$action      = sanitize_text_field( $_POST['batch_action'] ?? '' );
		$job_id      = sanitize_text_field( $_POST['job_id'] ?? '' );
		$campaign_id = sanitize_text_field( $_POST['campaign_id'] ?? '' );

		switch ( $action ) {
			case 'retry_failed':
				if ( empty( $job_id ) ) {
					wp_send_json_error( [ 'message' => __( 'Missing job ID.', 'seo-city-pages' ) ] );
				}
				County_Batch_Runner::retry_failed( $job_id );
				wp_send_json_success( [ 'message' => __( 'Failed items reset to pending. Ready to retry.', 'seo-city-pages' ) ] );
				break;

			case 'publish_batch':
				if ( empty( $job_id ) ) {
					wp_send_json_error( [ 'message' => __( 'Missing job ID.', 'seo-city-pages' ) ] );
				}
				$publish_res = County_Batch_Runner::bulk_publish_batch( $job_id );
				wp_send_json_success( [
					'message' => sprintf( __( 'Published %d generated pages to WordPress live posts.', 'seo-city-pages' ), $publish_res['published'] ),
					'stats'   => $publish_res,
				] );
				break;

			case 'delete_campaign':
				if ( empty( $campaign_id ) ) {
					wp_send_json_error( [ 'message' => __( 'Missing campaign ID.', 'seo-city-pages' ) ] );
				}
				County_Campaign_Repository::delete_campaign( $campaign_id );
				wp_send_json_success( [ 'message' => __( 'Campaign and associated batches deleted successfully.', 'seo-city-pages' ) ] );
				break;

			default:
				wp_send_json_error( [ 'message' => __( 'Invalid action.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX Handler: Get campaign and batches live status.
	 */
	public function ajax_get_status(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::MANAGE );

		$campaign_id = sanitize_text_field( $_POST['campaign_id'] ?? '' );
		if ( empty( $campaign_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing campaign ID.', 'seo-city-pages' ) ] );
		}

		$campaign = County_Campaign_Repository::get_campaign( $campaign_id );
		$batches  = County_Campaign_Repository::get_campaign_batches( $campaign_id );

		wp_send_json_success( [
			'campaign' => $campaign,
			'batches'  => $batches,
		] );
	}
}
