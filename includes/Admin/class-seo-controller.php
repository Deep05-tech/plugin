<?php
/**
 * SEO Settings & Integration Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\SEO\SEO_Settings;
use SeoCityPages\SEO\SEO_Environment_Detector;
use SeoCityPages\SEO\SEO_Validator;
use SeoCityPages\SEO\SEO_Manager;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEO_Controller.
 */
class SEO_Controller {

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_ajax_seo_city_pages_save_seo_settings', [ $this, 'ajax_save_settings' ] );
		add_action( 'wp_ajax_seo_city_pages_save_page_seo', [ $this, 'ajax_save_page_seo' ] );
		add_action( 'wp_ajax_seo_city_pages_preview_seo', [ $this, 'ajax_preview_seo' ] );
	}

	/**
	 * Render SEO Admin Screen.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE );

		$notice = '';
		$error  = '';

		// Handle POST Form Submission.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['seo_city_pages_save_seo_settings'] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'save_seo_settings' ) ) {
				$error = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
			} else {
				$saved = SEO_Settings::update( [
					'default_title_template'   => wp_unslash( $_POST['default_title_template'] ?? '' ),
					'default_desc_template'    => wp_unslash( $_POST['default_desc_template'] ?? '' ),
					'default_og_image'         => wp_unslash( $_POST['default_og_image'] ?? '' ),
					'default_twitter_card'     => wp_unslash( $_POST['default_twitter_card'] ?? 'summary_large_image' ),
					'default_robots_index'     => wp_unslash( $_POST['default_robots_index'] ?? 'index' ),
					'default_robots_follow'    => wp_unslash( $_POST['default_robots_follow'] ?? 'follow' ),
					'enable_sitemap'           => ! empty( $_POST['enable_sitemap'] ),
					'sitemap_entries_per_page' => (int) ( $_POST['sitemap_entries_per_page'] ?? 1000 ),
					'enable_schema'            => ! empty( $_POST['enable_schema'] ),
					'schema_default_type'      => wp_unslash( $_POST['schema_default_type'] ?? 'Service' ),
					'adapter_override'         => wp_unslash( $_POST['adapter_override'] ?? '' ),
				] );

				if ( $saved ) {
					$notice = __( 'SEO settings saved successfully.', 'seo-city-pages' );
				} else {
					$notice = __( 'Settings updated.', 'seo-city-pages' );
				}
			}
		}

		$settings         = SEO_Settings::get_all();
		$environment_info = SEO_Environment_Detector::get_environment_info();
		$duplicate_stats  = City_Page_Repository::get_seo_duplicate_stats();

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/seo-settings.php';
	}

	/**
	 * AJAX Handler: Save Global SEO Settings.
	 *
	 * @return void
	 */
	public function ajax_save_settings(): void {
		check_ajax_referer( 'seo_city_pages_admin_nonce', 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'seo-city-pages' ) ], 403 );
		}

		$raw_data = $_POST['settings'] ?? [];
		if ( ! is_array( $raw_data ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid payload.', 'seo-city-pages' ) ], 400 );
		}

		SEO_Settings::update( $raw_data );

		wp_send_json_success( [
			'message'  => __( 'SEO Settings saved successfully.', 'seo-city-pages' ),
			'settings' => SEO_Settings::get_all(),
		] );
	}

	/**
	 * AJAX Handler: Save Per-Page SEO Metadata & Manual Overrides.
	 *
	 * @return void
	 */
	public function ajax_save_page_seo(): void {
		check_ajax_referer( 'seo_city_pages_admin_nonce', 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'seo-city-pages' ) ], 403 );
		}

		$page_id = (int) ( $_POST['page_id'] ?? 0 );
		$page    = City_Page_Repository::get( $page_id );

		if ( ! $page ) {
			wp_send_json_error( [ 'message' => __( 'Page not found.', 'seo-city-pages' ) ], 404 );
		}

		$seo_raw   = $_POST['seo'] ?? [];
		$sanitized = SEO_Validator::sanitize( (array) $seo_raw );
		$is_manual = ! empty( $_POST['is_manual'] );

		$result = City_Page_Repository::update_seo_metadata( $page_id, $sanitized, $is_manual );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ], 500 );
		}

		$updated_page = City_Page_Repository::get( $page_id );
		$resolved     = SEO_Manager::get_instance()->resolve_page_seo( $updated_page );

		wp_send_json_success( [
			'message'      => __( 'Page SEO metadata saved.', 'seo-city-pages' ),
			'resolved_seo' => $resolved,
		] );
	}

	/**
	 * AJAX Handler: Dynamic Live Search Snippet Preview.
	 *
	 * @return void
	 */
	public function ajax_preview_seo(): void {
		check_ajax_referer( 'seo_city_pages_admin_nonce', 'nonce' );

		$page_id   = (int) ( $_POST['page_id'] ?? 0 );
		$page      = City_Page_Repository::get( $page_id );
		$title_tpl = sanitize_text_field( $_POST['title'] ?? '' );
		$desc_tpl  = sanitize_textarea_field( $_POST['description'] ?? '' );

		$rendered_title = SEO_Manager::get_instance()->resolve_page_seo( $page ? $page : (object) [] );

		$report = SEO_Validator::validate( [
			'seo_title'        => $title_tpl,
			'meta_description' => $desc_tpl,
		], $page_id );

		wp_send_json_success( [
			'validation' => $report,
			'title'      => $title_tpl,
			'desc'       => $desc_tpl,
		] );
	}
}
