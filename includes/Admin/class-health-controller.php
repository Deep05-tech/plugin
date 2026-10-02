<?php
/**
 * System Health & Diagnostics Admin Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Diagnostics\Diagnostics_Engine;
use SeoCityPages\Diagnostics\OpenAI_Diagnostics;
use SeoCityPages\Diagnostics\Queue_Monitor;
use SeoCityPages\Diagnostics\Integrity_Scanner;
use SeoCityPages\Diagnostics\URL_Health_Checker;
use SeoCityPages\Diagnostics\Repair_Manager;
use SeoCityPages\Diagnostics\Safe_Mode_Manager;
use SeoCityPages\Security\Security;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Health Controller Class.
 */
class Health_Controller {

	public const NONCE_ACTION = 'seo_city_pages_health_action';

	/**
	 * Constructor: Register AJAX actions.
	 */
	public function __construct() {
		add_action( 'wp_ajax_seo_city_pages_run_diagnostics', [ $this, 'ajax_run_diagnostics' ] );
		add_action( 'wp_ajax_seo_city_pages_run_integrity_scan', [ $this, 'ajax_run_integrity_scan' ] );
		add_action( 'wp_ajax_seo_city_pages_test_openai_connection', [ $this, 'ajax_test_openai' ] );
		add_action( 'wp_ajax_seo_city_pages_check_url_health', [ $this, 'ajax_check_url' ] );
		add_action( 'wp_ajax_seo_city_pages_execute_repair', [ $this, 'ajax_execute_repair' ] );
		add_action( 'wp_ajax_seo_city_pages_toggle_safe_mode', [ $this, 'ajax_toggle_safe_mode' ] );
		add_action( 'wp_ajax_seo_city_pages_download_support_bundle', [ $this, 'ajax_download_support_bundle' ] );
	}

	/**
	 * Render System Health Admin Screen.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::VIEW_HEALTH );

		$diagnostics = Diagnostics_Engine::run_all_checks();
		$openai_info = OpenAI_Diagnostics::get_status_summary();
		$queue_info  = Queue_Monitor::get_queue_metrics();

		$template = SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/system-health.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * AJAX: Run diagnostic checks.
	 */
	public function ajax_run_diagnostics(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::RUN_DIAGNOSTICS );

		$data = [
			'diagnostics' => Diagnostics_Engine::run_all_checks(),
			'openai'      => OpenAI_Diagnostics::get_status_summary(),
			'queue'       => Queue_Monitor::get_queue_metrics(),
		];

		wp_send_json_success( $data );
	}

	/**
	 * AJAX: Run full integrity scan.
	 */
	public function ajax_run_integrity_scan(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::RUN_DIAGNOSTICS );

		$limit  = ! empty( $_POST['limit'] ) ? max( 50, min( 5000, (int) $_POST['limit'] ) ) : 1000;
		$report = Integrity_Scanner::run_full_scan( $limit );

		wp_send_json_success( $report );
	}

	/**
	 * AJAX: Test OpenAI Connection (Minimal, zero content created).
	 */
	public function ajax_test_openai(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::RUN_DIAGNOSTICS );

		$result = OpenAI_Diagnostics::test_connection();
		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX: Check Page URL Health (SSRF Protected).
	 */
	public function ajax_check_url(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::RUN_DIAGNOSTICS );

		$page_id = ! empty( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0;
		$url_path = ! empty( $_POST['url_path'] ) ? sanitize_text_field( wp_unslash( $_POST['url_path'] ) ) : '';

		$target = $page_id > 0 ? $page_id : $url_path;
		if ( empty( $target ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing page ID or URL path.', 'seo-city-pages' ) ] );
		}

		$report = URL_Health_Checker::check_page_url( $target );
		if ( ! empty( $report['success'] ) ) {
			wp_send_json_success( $report );
		} else {
			wp_send_json_error( $report );
		}
	}

	/**
	 * AJAX: Execute Controlled Repair Action.
	 */
	public function ajax_execute_repair(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::EXECUTE_REPAIR );

		$repair_type = ! empty( $_POST['repair_type'] ) ? sanitize_key( $_POST['repair_type'] ) : '';

		switch ( $repair_type ) {
			case 'repair_db':
				$result = Repair_Manager::repair_database_schema();
				wp_send_json_success( $result );
				break;

			case 'refresh_rewrites':
				$result = Repair_Manager::refresh_rewrite_rules();
				wp_send_json_success( $result );
				break;

			case 'recover_stale_queue':
				$result = Repair_Manager::recover_stale_queue_locks();
				wp_send_json_success( $result );
				break;

			case 'regenerate_elementor':
				$result = Repair_Manager::regenerate_elementor_cache();
				if ( ! empty( $result['success'] ) ) {
					wp_send_json_success( $result );
				} else {
					wp_send_json_error( $result );
				}
				break;

			default:
				wp_send_json_error( [ 'message' => __( 'Unknown repair action.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Toggle Safe Mode.
	 */
	public function ajax_toggle_safe_mode(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::TOGGLE_SAFE_MODE );

		$enable = ! empty( $_POST['enable'] );
		$reason = ! empty( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : 'Manual toggle from System Health';

		if ( $enable ) {
			Safe_Mode_Manager::enable_safe_mode( $reason );
		} else {
			Safe_Mode_Manager::disable_safe_mode();
		}

		wp_send_json_success( [
			'safe_mode_active' => Safe_Mode_Manager::is_safe_mode_active(),
			'message'          => $enable ? __( 'Safe Mode activated.', 'seo-city-pages' ) : __( 'Safe Mode deactivated.', 'seo-city-pages' ),
		] );
	}

	/**
	 * AJAX: Download Sanitized Support Bundle.
	 */
	public function ajax_download_support_bundle(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::VIEW_HEALTH );

		$bundle = Diagnostics_Engine::generate_support_bundle();

		$filename = sprintf( 'seo-city-pages-support-bundle-%s.json', gmdate( 'Y-m-d-His' ) );

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo wp_json_encode( $bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}
}
