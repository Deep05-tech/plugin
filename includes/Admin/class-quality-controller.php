<?php
/**
 * Quality Dashboard Admin Controller.
 *
 * Handles admin UI rendering and AJAX actions for programmatic SEO quality scoring,
 * content similarity indexing, intent overlap detection, and pre-publication quality gates.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Quality_Repository;
use SeoCityPages\Jobs\Bulk_Job_Repository;
use SeoCityPages\Quality\AI_Review_Advisor;
use SeoCityPages\Quality\Quality_Engine;
use SeoCityPages\Quality\Quality_Gate_Manager;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quality_Controller Class.
 */
class Quality_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_seo_city_pages_quality_overview', [ $this, 'ajax_get_overview' ] );
		add_action( 'wp_ajax_seo_city_pages_quality_high_risk', [ $this, 'ajax_get_high_risk' ] );
		add_action( 'wp_ajax_seo_city_pages_quality_scan_page', [ $this, 'ajax_scan_page' ] );
		add_action( 'wp_ajax_seo_city_pages_quality_bulk_scan', [ $this, 'ajax_bulk_scan' ] );
		add_action( 'wp_ajax_seo_city_pages_quality_dismiss_issue', [ $this, 'ajax_dismiss_issue' ] );
		add_action( 'wp_ajax_seo_city_pages_quality_revoke_dismissal', [ $this, 'ajax_revoke_dismissal' ] );
		add_action( 'wp_ajax_seo_city_pages_quality_set_gate_mode', [ $this, 'ajax_set_gate_mode' ] );
		add_action( 'wp_ajax_seo_city_pages_quality_ai_review', [ $this, 'ajax_ai_review' ] );
	}

	/**
	 * Render Quality Dashboard.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE_QUALITY );

		$stats     = Quality_Repository::get_overview_stats();
		$gate_mode = Quality_Gate_Manager::get_gate_mode();
		$high_risk = Quality_Repository::get_high_risk_pages( 25, 0 );

		$template_path = SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/quality-dashboard.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="wrap"><h1>Quality Dashboard</h1><p>Quality dashboard template loading...</p></div>';
		}
	}

	/**
	 * AJAX: Get Overview Stats.
	 *
	 * @return void
	 */
	public function ajax_get_overview(): void {
		Security::verify_ajax_request( Capabilities::MANAGE_QUALITY );

		$stats = Quality_Repository::get_overview_stats();
		$stats['gate_mode'] = Quality_Gate_Manager::get_gate_mode();

		wp_send_json_success( $stats );
	}

	/**
	 * AJAX: Get High Risk / Warning Pages.
	 *
	 * @return void
	 */
	public function ajax_get_high_risk(): void {
		Security::verify_ajax_request( Capabilities::MANAGE_QUALITY );

		$limit  = isset( $_POST['limit'] ) ? max( 1, min( 100, (int) $_POST['limit'] ) ) : 50;
		$offset = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;

		$pages = Quality_Repository::get_high_risk_pages( $limit, $offset );
		wp_send_json_success( [ 'pages' => $pages ] );
	}

	/**
	 * AJAX: Scan Single Page.
	 *
	 * @return void
	 */
	public function ajax_scan_page(): void {
		Security::verify_ajax_request( Capabilities::RUN_QUALITY_SCAN );

		$page_id = isset( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0;
		if ( $page_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Invalid page ID.' ] );
		}

		$result = Quality_Engine::evaluate_page( $page_id );
		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Queue Bulk Quality Scan.
	 *
	 * @return void
	 */
	public function ajax_bulk_scan(): void {
		Security::verify_ajax_request( Capabilities::RUN_QUALITY_SCAN );

		$scope = isset( $_POST['scope'] ) ? sanitize_key( $_POST['scope'] ) : 'all'; // 'all' | 'stale' | 'unscanned'

		global $wpdb;
		$pages_table = \SeoCityPages\Database\Schema::get_table_name( \SeoCityPages\Database\Schema::TABLE_CITY_PAGES );
		$eval_table  = \SeoCityPages\Database\Schema::get_table_name( \SeoCityPages\Database\Schema::TABLE_QUALITY_EVALUATIONS );

		if ( 'stale' === $scope ) {
			$page_ids = $wpdb->get_col( "SELECT page_id FROM {$eval_table} WHERE is_stale = 1" );
		} elseif ( 'unscanned' === $scope ) {
			$page_ids = $wpdb->get_col( "SELECT p.id FROM {$pages_table} p LEFT JOIN {$eval_table} e ON p.id = e.page_id WHERE e.id IS NULL" );
		} else {
			$page_ids = $wpdb->get_col( "SELECT id FROM {$pages_table}" );
		}

		if ( empty( $page_ids ) ) {
			wp_send_json_error( [ 'message' => 'No pages found matching scan criteria.' ] );
		}

		$job_id = Bulk_Job_Repository::create_job( [
			'job_type'    => Bulk_Job_Repository::TYPE_BULK_QUALITY_SCAN,
			'title'       => sprintf( 'Bulk Quality Scan (%d pages)', count( $page_ids ) ),
			'total_items' => count( $page_ids ),
			'job_params'  => [ 'scope' => $scope ],
		] );

		if ( empty( $job_id ) ) {
			wp_send_json_error( [ 'message' => 'Failed to initialize bulk quality scan job.' ] );
		}

		$items = [];
		foreach ( $page_ids as $pid ) {
			$items[] = [
				'entity_id'    => (int) $pid,
				'entity_type'  => 'city_page',
				'item_payload' => [ 'page_id' => (int) $pid ],
			];
		}

		Bulk_Job_Repository::add_job_items( $job_id, $items );

		wp_send_json_success( [
			'job_id'      => $job_id,
			'total_items' => count( $page_ids ),
			'message'     => sprintf( 'Queued quality scan for %d pages.', count( $page_ids ) ),
		] );
	}

	/**
	 * AJAX: Dismiss a specific quality issue / warning.
	 *
	 * @return void
	 */
	public function ajax_dismiss_issue(): void {
		Security::verify_ajax_request( Capabilities::DISMISS_QUALITY_WARNING );

		$page_id    = isset( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0;
		$issue_type = isset( $_POST['issue_type'] ) ? sanitize_key( $_POST['issue_type'] ) : '';
		$reason     = isset( $_POST['reason'] ) ? sanitize_text_field( $_POST['reason'] ) : 'Dismissed by administrator';

		if ( $page_id <= 0 || empty( $issue_type ) ) {
			wp_send_json_error( [ 'message' => 'Page ID and issue type are required.' ] );
		}

		$dismissal_id = Quality_Repository::add_dismissal( $page_id, $issue_type, $reason );

		// Re-evaluate page score with dismissal applied.
		$eval = Quality_Engine::evaluate_page( $page_id );

		wp_send_json_success( [
			'dismissal_id'   => $dismissal_id,
			'quality_score'  => $eval['quality_score'],
			'quality_status' => $eval['quality_status'],
			'message'        => 'Issue dismissed successfully.',
		] );
	}

	/**
	 * AJAX: Revoke a previous dismissal.
	 *
	 * @return void
	 */
	public function ajax_revoke_dismissal(): void {
		Security::verify_ajax_request( Capabilities::DISMISS_QUALITY_WARNING );

		$dismissal_id = isset( $_POST['dismissal_id'] ) ? (int) $_POST['dismissal_id'] : 0;
		$page_id      = isset( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0;

		if ( $dismissal_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Invalid dismissal ID.' ] );
		}

		Quality_Repository::revoke_dismissal( $dismissal_id );

		if ( $page_id > 0 ) {
			$eval = Quality_Engine::evaluate_page( $page_id );
		}

		wp_send_json_success( [ 'message' => 'Dismissal revoked.' ] );
	}

	/**
	 * AJAX: Update Pre-Publication Quality Gate Mode.
	 *
	 * @return void
	 */
	public function ajax_set_gate_mode(): void {
		Security::verify_ajax_request( Capabilities::MANAGE_QUALITY );

		$mode = isset( $_POST['gate_mode'] ) ? sanitize_key( $_POST['gate_mode'] ) : '';
		if ( ! Quality_Gate_Manager::set_gate_mode( $mode ) ) {
			wp_send_json_error( [ 'message' => 'Invalid gate mode specified.' ] );
		}

		wp_send_json_success( [
			'gate_mode' => Quality_Gate_Manager::get_gate_mode(),
			'message'   => 'Quality gate mode updated.',
		] );
	}

	/**
	 * AJAX: Single Page AI Editorial Review.
	 *
	 * @return void
	 */
	public function ajax_ai_review(): void {
		Security::verify_ajax_request( Capabilities::REVIEW_CONTENT );

		$page_id = isset( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0;
		if ( $page_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Invalid page ID.' ] );
		}

		$result = AI_Review_Advisor::review_page( $page_id );
		if ( ! $result['success'] ) {
			wp_send_json_error( [ 'message' => $result['error'] ] );
		}

		wp_send_json_success( $result['feedback'] );
	}
}
