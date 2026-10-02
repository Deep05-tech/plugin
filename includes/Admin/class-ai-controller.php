<?php
/**
 * AI Content Administration Controller.
 *
 * Handles AI generation hub, content review screen, manual editor,
 * and secure AJAX endpoints for single/bulk generation and approvals.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\AI\AI_Manager;
use SeoCityPages\AI\Generation_Job_Runner;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Database\Generation_Job_Repository;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI_Controller Class.
 */
class AI_Controller {

	/**
	 * Initialize AI Controller hooks and AJAX actions.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menus' ], 20 );

		// Secure Server-side AJAX Handlers.
		add_action( 'wp_ajax_scp_test_openai_connection', [ $this, 'ajax_test_connection' ] );
		add_action( 'wp_ajax_scp_generate_page_content', [ $this, 'ajax_generate_page_content' ] );
		add_action( 'wp_ajax_scp_start_bulk_generation', [ $this, 'ajax_start_bulk_generation' ] );
		add_action( 'wp_ajax_scp_process_bulk_batch', [ $this, 'ajax_process_bulk_batch' ] );
		add_action( 'wp_ajax_scp_get_job_status', [ $this, 'ajax_get_job_status' ] );
		add_action( 'wp_ajax_scp_pause_bulk_generation', [ $this, 'ajax_pause_bulk_generation' ] );
		add_action( 'wp_ajax_scp_resume_bulk_generation', [ $this, 'ajax_resume_bulk_generation' ] );
		add_action( 'wp_ajax_scp_cancel_bulk_generation', [ $this, 'ajax_cancel_bulk_generation' ] );

		// Phase 7 Review, Approval & Publishing Handlers.
		add_action( 'wp_ajax_scp_approve_version', [ $this, 'ajax_approve_version' ] );
		add_action( 'wp_ajax_scp_reject_version', [ $this, 'ajax_reject_version' ] );
		add_action( 'wp_ajax_scp_publish_page', [ $this, 'ajax_publish_page' ] );
		add_action( 'wp_ajax_scp_unpublish_page', [ $this, 'ajax_unpublish_page' ] );
		add_action( 'wp_ajax_scp_restore_version', [ $this, 'ajax_restore_version' ] );
		add_action( 'wp_ajax_scp_save_review_notes', [ $this, 'ajax_save_review_notes' ] );
		add_action( 'wp_ajax_scp_save_content_edits', [ $this, 'ajax_save_content_edits' ] );
		add_action( 'wp_ajax_scp_bulk_workflow_action', [ $this, 'ajax_bulk_workflow_action' ] );
		add_action( 'wp_ajax_scp_approve_all', [ $this, 'ajax_approve_all' ] );
		add_action( 'wp_ajax_scp_publish_all', [ $this, 'ajax_publish_all' ] );
		add_action( 'wp_ajax_scp_approve_and_publish_all', [ $this, 'ajax_approve_and_publish_all' ] );
	}

	/**
	 * Register AI submenus in WordPress admin.
	 *
	 * @return void
	 */
	public function register_menus(): void {
		add_submenu_page(
			'seo-city-pages',
			__( 'AI Content Engine', 'seo-city-pages' ),
			__( 'AI Content', 'seo-city-pages' ),
			Capabilities::MANAGE,
			'seo-city-pages-ai',
			[ $this, 'render_ai_dashboard' ]
		);

		add_submenu_page(
			'seo-city-pages',
			__( 'Content Review & Approval', 'seo-city-pages' ),
			__( 'Review Content', 'seo-city-pages' ),
			Capabilities::MANAGE,
			'seo-city-pages-ai-review',
			[ $this, 'render_review_screen' ]
		);
	}

	/**
	 * Render AI Generation Dashboard.
	 *
	 * @return void
	 */
	public function render_ai_dashboard(): void {
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_die( esc_html__( 'Permission denied.', 'seo-city-pages' ) );
		}

		$stats = Content_Version_Repository::get_stats();
		$recent_jobs = Generation_Job_Repository::get_recent_jobs( 10 );
		$active_job = Generation_Job_Repository::get_active_job();
		$manager = AI_Manager::instance();
		$openai_provider = $manager->get_provider( 'openai' );
		$supported_models = $openai_provider ? $openai_provider->get_supported_models() : [];

		include SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/ai-dashboard.php';
	}

	/**
	 * Render Content Review & Approval Screen.
	 *
	 * @return void
	 */
	public function render_review_screen(): void {
		if ( ! Capabilities::user_can( Capabilities::REVIEW_CONTENT ) ) {
			wp_die( esc_html__( 'Permission denied.', 'seo-city-pages' ) );
		}

		$page_id = (int) ( $_GET['page_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action  = sanitize_text_field( $_GET['review_action'] ?? 'list' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'edit' === $action && $page_id > 0 ) {
			if ( ! Capabilities::user_can( Capabilities::EDIT_CONTENT ) ) {
				wp_die( esc_html__( 'Permission denied.', 'seo-city-pages' ) );
			}
			$page = City_Page_Repository::get( $page_id );
			$version = Content_Version_Repository::get_latest_version( $page_id );
			$content = $version ? json_decode( (string) $version->content_json, true ) : [];
			include SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/content-edit.php';
			return;
		}

		if ( 'compare' === $action && $page_id > 0 ) {
			$page = City_Page_Repository::get( $page_id );
			$v1_id = (int) ( $_GET['v1'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$v2_id = (int) ( $_GET['v2'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$version_a = $v1_id > 0 ? Content_Version_Repository::get( $v1_id ) : Content_Version_Repository::get_latest_version( $page_id );
			$version_b = $v2_id > 0 ? Content_Version_Repository::get( $v2_id ) : ( $version_a ? Content_Version_Repository::get_previous_version( (int) $version_a->id ) : null );

			$content_a = $version_a ? json_decode( (string) $version_a->content_json, true ) : [];
			$content_b = $version_b ? json_decode( (string) $version_b->content_json, true ) : [];

			include SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/content-compare.php';
			return;
		}

		if ( 'history' === $action && $page_id > 0 ) {
			$page = City_Page_Repository::get( $page_id );
			$versions = Content_Version_Repository::get_by_page_id( $page_id );
			$audit_logs = \SeoCityPages\Logging\Audit_Logger::get_logs( [ 'entity_id' => $page_id, 'limit' => 50 ] );
			include SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/content-history.php';
			return;
		}

		if ( 'view' === $action && $page_id > 0 ) {
			$page = City_Page_Repository::get( $page_id );
			$versions = Content_Version_Repository::get_by_page_id( $page_id );
			$view_version_id = (int) ( $_GET['version_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected_version = $view_version_id > 0 ? Content_Version_Repository::get( $view_version_id ) : ( ! empty( $versions ) ? $versions[0] : null );
			$published_version = City_Page_Repository::get_published_version( $page_id );
			$integrity = \SeoCityPages\Workflow\Quality_Validator::validate_relational_integrity( $page_id );

			include SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/content-review-detail.php';
			return;
		}

		// Default: List pages.
		$filter_status = sanitize_text_field( $_GET['filter_gen_status'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged         = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page      = 20;
		$offset        = ( $paged - 1 ) * $per_page;

		$query_args = [
			'limit'   => $per_page,
			'offset'  => $offset,
			'orderby' => 'p.updated_at',
			'order'   => 'DESC',
		];

		if ( 'published' === $filter_status ) {
			$query_args['is_published'] = 1;
		} elseif ( ! empty( $filter_status ) ) {
			$query_args['generation_status'] = $filter_status;
		}

		$pages       = City_Page_Repository::get_all( $query_args );
		$total_pages = City_Page_Repository::count( $query_args );
		$queue_counts = Content_Version_Repository::get_queue_counts();
		$stats        = Content_Version_Repository::get_stats();

		include SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/content-review-list.php';
	}

	/**
	 * AJAX: Test OpenAI Connection server-side.
	 *
	 * @return void
	 */
	public function ajax_test_connection(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$result = AI_Manager::instance()->test_connection( 'openai' );

		if ( $result->is_success() ) {
			wp_send_json_success( [
				'message'    => $result->get_message(),
				'latency_ms' => $result->get_latency_ms(),
			] );
		} else {
			wp_send_json_error( [
				'message'    => $result->get_message(),
				'latency_ms' => $result->get_latency_ms(),
			] );
		}
	}

	/**
	 * AJAX: Generate content for an individual SEO City Page.
	 *
	 * @return void
	 */
	public function ajax_generate_page_content(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$api_key = \SeoCityPages\AI\OpenAI_Provider::get_api_key();
		if ( empty( $api_key ) ) {
			wp_send_json_error( [ 'message' => __( 'OpenAI API key is missing. Please configure your API key in Settings -> AI & Model Settings before generating content.', 'seo-city-pages' ) ] );
		}

		$page_id = (int) ( $_POST['page_id'] ?? 0 );
		if ( $page_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid page ID.', 'seo-city-pages' ) ] );
		}

		$result = AI_Manager::instance()->generate_for_page( $page_id );

		if ( $result->is_success() ) {
			wp_send_json_success( [
				'message'      => __( 'Content generated successfully and queued for review.', 'seo-city-pages' ),
				'page_id'      => $page_id,
				'tokens'       => $result->get_total_tokens(),
				'duration_ms'  => $result->get_duration_ms(),
			] );
		} else {
			wp_send_json_error( [
				'message' => $result->get_error_message(),
				'code'    => $result->get_error_code(),
				'errors'  => $result->get_validation_errors(),
			] );
		}
	}

	/**
	 * AJAX: Initialize a bulk generation job.
	 *
	 * @return void
	 */
	public function ajax_start_bulk_generation(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$total_in_db = City_Page_Repository::count();
		if ( 0 === $total_in_db ) {
			wp_send_json_error( [ 'message' => __( 'No SEO City Pages found in your database. Please create pages first using the County Generator or CSV Import screen before generating AI content.', 'seo-city-pages' ) ] );
		}

		$api_key = \SeoCityPages\AI\OpenAI_Provider::get_api_key();
		if ( empty( $api_key ) ) {
			wp_send_json_error( [ 'message' => __( 'OpenAI API key is missing. Please configure your API key in Settings -> AI & Model Settings before generating content.', 'seo-city-pages' ) ] );
		}

		$page_ids = $_POST['page_ids'] ?? [];
		$mode     = sanitize_text_field( $_POST['generation_mode'] ?? 'missing_only' );

		if ( empty( $page_ids ) ) {
			// If no explicit IDs selected, fetch all pages matching mode.
			$filter_args = [ 'limit' => 5000 ];
			if ( 'missing_only' === $mode ) {
				$filter_args['generation_status'] = 'not_generated';
			}
			$pages = City_Page_Repository::get_all( $filter_args );
			$page_ids = array_map( function( $p ) { return (int) $p->id; }, $pages );
		}

		if ( empty( $page_ids ) ) {
			if ( 'missing_only' === $mode ) {
				wp_send_json_error( [ 'message' => __( 'All existing pages already have generated content! To regenerate existing content, select "Regenerate All Pages".', 'seo-city-pages' ) ] );
			} else {
				wp_send_json_error( [ 'message' => __( 'No eligible pages found for content generation.', 'seo-city-pages' ) ] );
			}
		}

		$job_db_id = Generation_Job_Runner::start_job( $page_ids );
		if ( $job_db_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Failed to create generation job.', 'seo-city-pages' ) ] );
		}

		wp_send_json_success( [
			'job_db_id'   => $job_db_id,
			'total_pages' => count( $page_ids ),
			'message'     => sprintf( __( 'Started bulk generation job for %d pages.', 'seo-city-pages' ), count( $page_ids ) ),
		] );
	}

	/**
	 * AJAX: Process a single batch chunk for bulk generation.
	 *
	 * @return void
	 */
	public function ajax_process_bulk_batch(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$job_db_id  = (int) ( $_POST['job_db_id'] ?? 0 );
		$batch_size = max( 1, min( 5, (int) ( $_POST['batch_size'] ?? 1 ) ) );

		if ( $job_db_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid job ID.', 'seo-city-pages' ) ] );
		}

		$result = Generation_Job_Runner::process_batch( $job_db_id, $batch_size );
		$result['stats'] = Content_Version_Repository::get_stats();

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX: Check active job status and overall metrics.
	 *
	 * @return void
	 */
	public function ajax_get_job_status(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$job_db_id = (int) ( $_REQUEST['job_db_id'] ?? 0 );
		$job = $job_db_id > 0 ? Generation_Job_Repository::get( $job_db_id ) : Generation_Job_Repository::get_active_job();
		$stats = Content_Version_Repository::get_stats();

		if ( ! $job ) {
			wp_send_json_success( [
				'has_active_job' => false,
				'stats'          => $stats,
			] );
		}

		$total       = (int) $job->total_pages;
		$processed   = (int) $job->processed_pages;
		$success     = (int) $job->success_pages;
		$failed      = (int) $job->failed_pages;
		$status      = (string) $job->status;
		$pct         = $total > 0 ? min( 100, (int) round( ( $processed / $total ) * 100 ) ) : 0;
		$is_complete = in_array( $status, [ Generation_Job_Repository::STATUS_COMPLETED, Generation_Job_Repository::STATUS_COMPLETED_ERRORS, Generation_Job_Repository::STATUS_FAILED, Generation_Job_Repository::STATUS_CANCELLED ], true ) || ( $processed >= $total && $total > 0 );

		wp_send_json_success( [
			'has_active_job' => ! $is_complete,
			'job_db_id'      => (int) $job->id,
			'status'         => $status,
			'total'          => $total,
			'processed'      => $processed,
			'success_cnt'    => $success,
			'failed_cnt'     => $failed,
			'percentage'     => $pct,
			'is_complete'    => $is_complete,
			'stats'          => $stats,
		] );
	}

	/**
	 * AJAX: Pause a running bulk generation job.
	 *
	 * @return void
	 */
	public function ajax_pause_bulk_generation(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$job_db_id = (int) ( $_POST['job_db_id'] ?? 0 );
		if ( $job_db_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid job ID.', 'seo-city-pages' ) ] );
		}

		$ok = Generation_Job_Repository::pause( $job_db_id );
		if ( $ok ) {
			wp_send_json_success( [
				'message' => __( 'Bulk generation paused.', 'seo-city-pages' ),
				'job_id'  => $job_db_id,
			] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to pause job.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Resume a paused bulk generation job.
	 *
	 * @return void
	 */
	public function ajax_resume_bulk_generation(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$job_db_id = (int) ( $_POST['job_db_id'] ?? 0 );
		if ( $job_db_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid job ID.', 'seo-city-pages' ) ] );
		}

		$ok = Generation_Job_Repository::resume( $job_db_id );
		if ( $ok ) {
			wp_send_json_success( [
				'message' => __( 'Bulk generation resumed.', 'seo-city-pages' ),
				'job_id'  => $job_db_id,
			] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to resume job.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Cancel an in-progress generation job.
	 *
	 * @return void
	 */
	public function ajax_cancel_bulk_generation(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$job_db_id = (int) ( $_POST['job_db_id'] ?? 0 );
		if ( $job_db_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid job ID.', 'seo-city-pages' ) ] );
		}

		$ok = Generation_Job_Repository::cancel( $job_db_id );
		if ( $ok ) {
			wp_send_json_success( [
				'message' => __( 'Bulk generation cancelled.', 'seo-city-pages' ),
				'job_id'  => $job_db_id,
				'stats'   => Content_Version_Repository::get_stats(),
			] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to cancel job.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Approve a content version.
	 *
	 * @return void
	 */
	public function ajax_approve_version(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::APPROVE_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized: Insufficient permissions to approve content.', 'seo-city-pages' ) ], 403 );
		}

		$version_id = (int) ( $_POST['version_id'] ?? 0 );
		$notes      = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

		if ( $version_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid version ID.', 'seo-city-pages' ) ] );
		}

		$approved = \SeoCityPages\Workflow\Status_Workflow::approve( $version_id, get_current_user_id(), $notes );

		if ( $approved ) {
			wp_send_json_success( [ 'message' => __( 'Content version approved successfully.', 'seo-city-pages' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to approve content version.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Reject a content version.
	 *
	 * @return void
	 */
	public function ajax_reject_version(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::APPROVE_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized: Insufficient permissions to reject content.', 'seo-city-pages' ) ], 403 );
		}

		$version_id = (int) ( $_POST['version_id'] ?? 0 );
		$reason     = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );
		$notes      = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

		if ( $version_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid version ID.', 'seo-city-pages' ) ] );
		}

		$rejected = \SeoCityPages\Workflow\Status_Workflow::reject( $version_id, $reason, get_current_user_id(), $notes );

		if ( $rejected ) {
			wp_send_json_success( [ 'message' => __( 'Content version marked as rejected.', 'seo-city-pages' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to reject content version.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Publish an approved version to the live page.
	 *
	 * @return void
	 */
	public function ajax_publish_page(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::PUBLISH_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized: Insufficient permissions to publish content.', 'seo-city-pages' ) ], 403 );
		}

		$page_id    = (int) ( $_POST['page_id'] ?? 0 );
		$version_id = (int) ( $_POST['version_id'] ?? 0 );

		if ( $page_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid page ID.', 'seo-city-pages' ) ] );
		}

		$result = \SeoCityPages\Workflow\Status_Workflow::publish( $page_id, $version_id, get_current_user_id() );

		if ( $result['success'] ) {
			wp_send_json_success( [ 'message' => $result['message'] ] );
		} else {
			wp_send_json_error( [
				'message' => $result['message'],
				'errors'  => $result['errors'] ?? [],
			] );
		}
	}

	/**
	 * AJAX: Unpublish a live page.
	 *
	 * @return void
	 */
	public function ajax_unpublish_page(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::PUBLISH_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized: Insufficient permissions to unpublish content.', 'seo-city-pages' ) ], 403 );
		}

		$page_id = (int) ( $_POST['page_id'] ?? 0 );
		$reason  = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( $page_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid page ID.', 'seo-city-pages' ) ] );
		}

		$unpublished = \SeoCityPages\Workflow\Status_Workflow::unpublish( $page_id, get_current_user_id(), $reason );

		if ( $unpublished ) {
			wp_send_json_success( [ 'message' => __( 'Page unpublished and reverted to draft.', 'seo-city-pages' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to unpublish page.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Restore an older version.
	 *
	 * @return void
	 */
	public function ajax_restore_version(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::APPROVE_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$version_id   = (int) ( $_POST['version_id'] ?? 0 );
		$auto_approve = ! empty( $_POST['auto_approve'] );

		if ( $version_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid version ID.', 'seo-city-pages' ) ] );
		}

		$new_version_id = \SeoCityPages\Workflow\Status_Workflow::restore_version( $version_id, get_current_user_id(), $auto_approve );

		if ( $new_version_id > 0 ) {
			wp_send_json_success( [
				'message'        => __( 'Version restored successfully as a new version.', 'seo-city-pages' ),
				'new_version_id' => $new_version_id,
			] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to restore version.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Save review notes on a content version.
	 *
	 * @return void
	 */
	public function ajax_save_review_notes(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::REVIEW_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'seo-city-pages' ) ], 403 );
		}

		$version_id = (int) ( $_POST['version_id'] ?? 0 );
		$notes      = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

		if ( $version_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid version ID.', 'seo-city-pages' ) ] );
		}

		global $wpdb;
		$table = \SeoCityPages\Database\Schema::get_table_name( \SeoCityPages\Database\Schema::TABLE_CONTENT_VERSIONS );
		$updated = $wpdb->update(
			$table,
			[
				'review_notes' => $notes,
				'updated_at'   => current_time( 'mysql' ),
			],
			[ 'id' => $version_id ]
		);

		if ( false !== $updated ) {
			wp_send_json_success( [ 'message' => __( 'Review notes saved.', 'seo-city-pages' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to save notes.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Save manual field edits for a content version.
	 *
	 * @return void
	 */
	public function ajax_save_content_edits(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::EDIT_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized: Insufficient permissions to edit content.', 'seo-city-pages' ) ], 403 );
		}

		$version_id = (int) ( $_POST['version_id'] ?? 0 );
		$payload    = $_POST['content'] ?? [];
		$notes      = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? 'Manual edits saved' ) );
		$auto_approve = ! empty( $_POST['approve_now'] );

		if ( $version_id <= 0 || ! is_array( $payload ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid parameters.', 'seo-city-pages' ) ] );
		}

		$version = Content_Version_Repository::get( $version_id );
		if ( ! $version ) {
			wp_send_json_error( [ 'message' => __( 'Content version not found.', 'seo-city-pages' ) ] );
		}

		$page_id = (int) $version->page_id;
		$page    = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			wp_send_json_error( [ 'message' => __( 'City page not found.', 'seo-city-pages' ) ] );
		}

		// Validate & Sanitize manual edits against schema and anti-hallucination rules.
		$validation = \SeoCityPages\AI\Content_Validator::validate( $payload, $page );
		if ( ! $validation['is_valid'] ) {
			wp_send_json_error( [
				'message' => __( 'Validation failed on edited content.', 'seo-city-pages' ),
				'errors'  => $validation['errors'],
			] );
		}

		// Create a new version (v_{N+1}) preserving immutable history.
		$new_version_id = \SeoCityPages\Workflow\Status_Workflow::save_manual_edit(
			$page_id,
			$version_id,
			$validation['sanitized'],
			get_current_user_id(),
			$notes,
			$auto_approve
		);

		if ( $new_version_id > 0 ) {
			wp_send_json_success( [
				'message'        => __( 'Content edits saved as a new version successfully.', 'seo-city-pages' ),
				'new_version_id' => $new_version_id,
			] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to save edits in database.', 'seo-city-pages' ) ] );
		}
	}

	/**
	 * AJAX: Bulk workflow actions (approve, reject, publish, unpublish).
	 *
	 * @return void
	 */
	public function ajax_bulk_workflow_action(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		$bulk_action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ?? '' ) );
		$page_ids    = isset( $_POST['page_ids'] ) ? array_map( 'absint', (array) $_POST['page_ids'] ) : [];
		$reason      = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );

		$is_site_wide = in_array( $bulk_action, [ 'approve_all', 'publish_all', 'approve_and_publish_all' ], true );

		if ( empty( $bulk_action ) || ( empty( $page_ids ) && ! $is_site_wide ) ) {
			wp_send_json_error( [ 'message' => __( 'Please select pages and an action.', 'seo-city-pages' ) ] );
		}

		// Check granular capabilities per bulk action.
		if ( in_array( $bulk_action, [ 'approve', 'reject', 'approve_all' ], true ) ) {
			if ( ! Capabilities::user_can( Capabilities::APPROVE_CONTENT ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized: You do not have permission to approve/reject content.', 'seo-city-pages' ) ], 403 );
			}
		} elseif ( in_array( $bulk_action, [ 'publish', 'unpublish', 'publish_all' ], true ) ) {
			if ( ! Capabilities::user_can( Capabilities::PUBLISH_CONTENT ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized: You do not have permission to publish/unpublish content.', 'seo-city-pages' ) ], 403 );
			}
		} elseif ( 'approve_and_publish_all' === $bulk_action ) {
			if ( ! Capabilities::user_can( Capabilities::APPROVE_CONTENT ) || ! Capabilities::user_can( Capabilities::PUBLISH_CONTENT ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized: You do not have permission to approve and publish content.', 'seo-city-pages' ) ], 403 );
			}
		}

		$options = [
			'reason' => $reason,
			'notes'  => 'Bulk action: ' . $bulk_action,
		];

		$result = \SeoCityPages\Workflow\Status_Workflow::bulk_workflow_action( $bulk_action, $page_ids, $options );

		wp_send_json_success( [
			'message'   => sprintf( __( 'Bulk %s processed for %d pages.', 'seo-city-pages' ), esc_html( str_replace( '_', ' ', $bulk_action ) ), (int) ( $result['processed'] ?? 0 ) ),
			'processed' => $result['processed'] ?? 0,
			'approved'  => $result['approved'] ?? null,
			'published' => $result['published'] ?? null,
			'errors'    => $result['errors'] ?? [],
		] );
	}

	/**
	 * AJAX: 1-Click Approve All Generated Pages.
	 *
	 * @return void
	 */
	public function ajax_approve_all(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::APPROVE_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized: You do not have permission to approve content.', 'seo-city-pages' ) ], 403 );
		}

		$result = \SeoCityPages\Workflow\Status_Workflow::approve_all( get_current_user_id() );

		wp_send_json_success( [
			'message'   => sprintf( __( 'Successfully approved %d pending city pages.', 'seo-city-pages' ), (int) $result['processed'] ),
			'processed' => $result['processed'],
			'total'     => $result['total'],
		] );
	}

	/**
	 * AJAX: 1-Click Publish All Approved Pages.
	 *
	 * @return void
	 */
	public function ajax_publish_all(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::PUBLISH_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized: You do not have permission to publish content.', 'seo-city-pages' ) ], 403 );
		}

		$result = \SeoCityPages\Workflow\Status_Workflow::publish_all( get_current_user_id() );

		wp_send_json_success( [
			'message'   => sprintf( __( 'Successfully published %d approved city pages to live URLs and WordPress Pages.', 'seo-city-pages' ), (int) $result['processed'] ),
			'processed' => $result['processed'],
			'total'     => $result['total'],
			'errors'    => $result['errors'],
		] );
	}

	/**
	 * AJAX: 1-Click Approve & Publish All Generated Pages.
	 *
	 * @return void
	 */
	public function ajax_approve_and_publish_all(): void {
		check_ajax_referer( Security::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::user_can( Capabilities::APPROVE_CONTENT ) || ! Capabilities::user_can( Capabilities::PUBLISH_CONTENT ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized: You do not have permission to approve and publish content.', 'seo-city-pages' ) ], 403 );
		}

		$result = \SeoCityPages\Workflow\Status_Workflow::approve_and_publish_all( get_current_user_id() );

		wp_send_json_success( [
			'message'   => sprintf( __( 'Completed! %d pages approved, %d pages published live and synced to WordPress Pages.', 'seo-city-pages' ), (int) $result['approved'], (int) $result['published'] ),
			'approved'  => $result['approved'],
			'published' => $result['published'],
			'errors'    => $result['errors'],
		] );
	}
}
