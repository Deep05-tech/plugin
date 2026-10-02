<?php
/**
 * Bulk Operations Admin Controller & AJAX Processor.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Jobs\Bulk_Job_Repository;
use SeoCityPages\Jobs\Bulk_Job_Runner;
use SeoCityPages\Import\CSV_Validator;
use SeoCityPages\Export\CSV_Exporter;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Security\Security;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bulk Controller Class.
 */
class Bulk_Controller {

	/**
	 * Nonce action name.
	 */
	public const NONCE_ACTION = 'seo_city_pages_bulk_action';

	/**
	 * Constructor: Register AJAX handlers.
	 */
	public function __construct() {
		add_action( 'wp_ajax_seo_city_pages_csv_preview', [ $this, 'ajax_csv_preview' ] );
		add_action( 'wp_ajax_seo_city_pages_csv_import_queue', [ $this, 'ajax_csv_import_queue' ] );
		add_action( 'wp_ajax_seo_city_pages_bulk_create_queue', [ $this, 'ajax_bulk_create_queue' ] );
		add_action( 'wp_ajax_seo_city_pages_bulk_generate_queue', [ $this, 'ajax_bulk_generate_queue' ] );
		add_action( 'wp_ajax_seo_city_pages_bulk_validate_queue', [ $this, 'ajax_bulk_validate_queue' ] );
		add_action( 'wp_ajax_seo_city_pages_bulk_approve_queue', [ $this, 'ajax_bulk_approve_queue' ] );
		add_action( 'wp_ajax_seo_city_pages_bulk_publish_queue', [ $this, 'ajax_bulk_publish_queue' ] );
		add_action( 'wp_ajax_seo_city_pages_bulk_rollback_queue', [ $this, 'ajax_bulk_rollback_queue' ] );
		add_action( 'wp_ajax_seo_city_pages_bulk_job_poll', [ $this, 'ajax_job_poll' ] );
		add_action( 'wp_ajax_seo_city_pages_bulk_job_control', [ $this, 'ajax_job_control' ] );
		add_action( 'wp_ajax_seo_city_pages_bulk_export_download', [ $this, 'ajax_export_download' ] );
	}

	/**
	 * Render the Bulk Operations Admin View.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE );

		$recent_jobs = Bulk_Job_Repository::get_recent_jobs( 25 );
		$countries   = Country_Repository::get_all( [ 'status' => 'active' ] );
		$services    = Service_Repository::get_all( [ 'status' => 'active' ] );
		$page_stats  = [
			'total'        => City_Page_Repository::count_all(),
			'published'    => City_Page_Repository::count_by_status( 'published' ),
			'draft'        => City_Page_Repository::count_by_status( 'draft' ),
			'needs_review' => City_Page_Repository::count_by_generation_status( 'needs_review' ),
			'approved'     => City_Page_Repository::count_by_generation_status( 'approved' ),
		];

		$template = SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/bulk-operations.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * AJAX Handler: CSV Upload Preview (no database modification).
	 */
	public function ajax_csv_preview(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::BULK_IMPORT );

		if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No CSV file was uploaded.', 'seo-city-pages' ) ] );
		}

		$tmp_file = sanitize_text_field( $_FILES['csv_file']['tmp_name'] );
		$report   = CSV_Validator::validate_file( $tmp_file, true );

		if ( empty( $report['success'] ) ) {
			wp_send_json_error( $report );
		}

		// Cache temp file for confirmed import.
		$upload_dir = wp_upload_dir();
		$target_dir = $upload_dir['basedir'] . '/seo-city-pages-imports/';
		if ( ! file_exists( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
		}

		$preview_token = 'csv_preview_' . wp_generate_password( 16, false ) . '.csv';
		$target_file   = $target_dir . $preview_token;
		move_uploaded_file( $tmp_file, $target_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_move_uploaded_file

		$report['preview_token'] = $preview_token;
		wp_send_json_success( $report );
	}

	/**
	 * AJAX Handler: Confirmed CSV Ingestion into Queue.
	 */
	public function ajax_csv_import_queue(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::BULK_IMPORT );

		$token = sanitize_text_field( $_POST['preview_token'] ?? '' );
		if ( empty( $token ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing import preview token.', 'seo-city-pages' ) ] );
		}

		$upload_dir = wp_upload_dir();
		$file_path  = $upload_dir['basedir'] . '/seo-city-pages-imports/' . $token;

		if ( ! file_exists( $file_path ) ) {
			wp_send_json_error( [ 'message' => __( 'Import file has expired or was not found. Please upload again.', 'seo-city-pages' ) ] );
		}

		$report = CSV_Validator::validate_file( $file_path, false );
		if ( empty( $report['success'] ) || empty( $report['valid_rows'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No valid rows found to import.', 'seo-city-pages' ) ] );
		}

		$is_dry_run = ! empty( $_POST['is_dry_run'] );

		// Create Bulk Job.
		$job_id = Bulk_Job_Repository::create_job( [
			'job_type'    => Bulk_Job_Repository::TYPE_CSV_IMPORT,
			'total_items' => count( $report['valid_rows'] ),
			'is_dry_run'  => $is_dry_run ? 1 : 0,
			'job_params'  => [ 'file_token' => $token, 'is_dry_run' => $is_dry_run ],
		] );

		// Create Items.
		$items = [];
		foreach ( $report['valid_rows'] as $r ) {
			$items[] = [
				'entity_type'  => 'csv_row',
				'action'       => 'import_row',
				'item_payload' => $r,
			];
		}

		Bulk_Job_Repository::create_items_batch( $job_id, $items );

		wp_send_json_success( [
			'job_id'      => $job_id,
			'total_items' => count( $items ),
			'message'     => sprintf( __( 'Queued %d rows for import.', 'seo-city-pages' ), count( $items ) ),
		] );
	}

	/**
	 * AJAX Handler: Queue Bulk Page Creation.
	 */
	public function ajax_bulk_create_queue(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::BULK_CREATE );

		$city_ids    = array_map( 'intval', (array) ( $_POST['city_ids'] ?? [] ) );
		$service_ids = array_map( 'intval', (array) ( $_POST['service_ids'] ?? [] ) );
		$is_dry_run  = ! empty( $_POST['is_dry_run'] );

		if ( empty( $city_ids ) || empty( $service_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'Please select at least one City and one Service.', 'seo-city-pages' ) ] );
		}

		$combinations = [];
		foreach ( $city_ids as $cid ) {
			foreach ( $service_ids as $sid ) {
				$combinations[] = [
					'entity_type'  => 'location_combo',
					'action'       => 'create_page',
					'item_payload' => [ 'city_id' => $cid, 'service_id' => $sid ],
				];
			}
		}

		$job_id = Bulk_Job_Repository::create_job( [
			'job_type'    => Bulk_Job_Repository::TYPE_BULK_CREATE,
			'total_items' => count( $combinations ),
			'is_dry_run'  => $is_dry_run ? 1 : 0,
			'job_params'  => [ 'page_status' => sanitize_text_field( $_POST['page_status'] ?? 'draft' ), 'is_dry_run' => $is_dry_run ],
		] );

		Bulk_Job_Repository::create_items_batch( $job_id, $combinations );

		wp_send_json_success( [
			'job_id'      => $job_id,
			'total_items' => count( $combinations ),
			'message'     => sprintf( __( 'Queued %d city/service combinations.', 'seo-city-pages' ), count( $combinations ) ),
		] );
	}

	/**
	 * AJAX Handler: Queue Bulk AI Content / SEO Generation.
	 */
	public function ajax_bulk_generate_queue(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::BULK_GENERATE );

		$page_ids   = array_map( 'intval', (array) ( $_POST['page_ids'] ?? [] ) );
		$gen_type   = sanitize_text_field( $_POST['gen_type'] ?? 'content' ); // 'content' or 'seo'
		$is_dry_run = ! empty( $_POST['is_dry_run'] );

		if ( empty( $page_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No pages selected for generation.', 'seo-city-pages' ) ] );
		}

		$job_type = ( 'seo' === $gen_type ) ? Bulk_Job_Repository::TYPE_BULK_GENERATE_SEO : Bulk_Job_Repository::TYPE_BULK_GENERATE_CONTENT;

		$job_id = Bulk_Job_Repository::create_job( [
			'job_type'    => $job_type,
			'total_items' => count( $page_ids ),
			'is_dry_run'  => $is_dry_run ? 1 : 0,
			'job_params'  => [
				'model'            => sanitize_text_field( $_POST['model'] ?? 'gpt-4o-mini' ),
				'overwrite_manual' => ! empty( $_POST['overwrite_manual'] ),
				'is_dry_run'       => $is_dry_run,
			],
		] );

		$items = [];
		foreach ( $page_ids as $pid ) {
			$items[] = [
				'entity_id'   => $pid,
				'entity_type' => 'city_page',
				'action'      => ( 'seo' === $gen_type ) ? 'generate_seo' : 'generate_content',
			];
		}

		Bulk_Job_Repository::create_items_batch( $job_id, $items );

		wp_send_json_success( [
			'job_id'      => $job_id,
			'total_items' => count( $items ),
		] );
	}

	/**
	 * AJAX Handler: Queue Bulk Validation.
	 */
	public function ajax_bulk_validate_queue(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::MANAGE );

		$page_ids = array_map( 'intval', (array) ( $_POST['page_ids'] ?? [] ) );
		if ( empty( $page_ids ) ) {
			$page_ids = City_Page_Repository::get_all_ids();
		}

		$job_id = Bulk_Job_Repository::create_job( [
			'job_type'    => Bulk_Job_Repository::TYPE_BULK_VALIDATE,
			'total_items' => count( $page_ids ),
		] );

		$items = [];
		foreach ( $page_ids as $pid ) {
			$items[] = [
				'entity_id'   => $pid,
				'entity_type' => 'city_page',
				'action'      => 'validate_page',
			];
		}

		Bulk_Job_Repository::create_items_batch( $job_id, $items );
		wp_send_json_success( [ 'job_id' => $job_id, 'total_items' => count( $items ) ] );
	}

	/**
	 * AJAX Handler: Queue Bulk Approval.
	 */
	public function ajax_bulk_approve_queue(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::BULK_APPROVE );

		$page_ids   = array_map( 'intval', (array) ( $_POST['page_ids'] ?? [] ) );
		$is_dry_run = ! empty( $_POST['is_dry_run'] );

		if ( empty( $page_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No pages selected for approval.', 'seo-city-pages' ) ] );
		}

		$job_id = Bulk_Job_Repository::create_job( [
			'job_type'    => Bulk_Job_Repository::TYPE_BULK_APPROVE,
			'total_items' => count( $page_ids ),
			'is_dry_run'  => $is_dry_run ? 1 : 0,
		] );

		$items = [];
		foreach ( $page_ids as $pid ) {
			$items[] = [
				'entity_id'   => $pid,
				'entity_type' => 'city_page',
				'action'      => 'approve_content',
			];
		}

		Bulk_Job_Repository::create_items_batch( $job_id, $items );
		wp_send_json_success( [ 'job_id' => $job_id, 'total_items' => count( $items ) ] );
	}

	/**
	 * AJAX Handler: Queue Bulk Publish.
	 */
	public function ajax_bulk_publish_queue(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::BULK_PUBLISH );

		$page_ids   = array_map( 'intval', (array) ( $_POST['page_ids'] ?? [] ) );
		$is_dry_run = ! empty( $_POST['is_dry_run'] );

		if ( empty( $page_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No pages selected for publishing.', 'seo-city-pages' ) ] );
		}

		$job_id = Bulk_Job_Repository::create_job( [
			'job_type'    => Bulk_Job_Repository::TYPE_BULK_PUBLISH,
			'total_items' => count( $page_ids ),
			'is_dry_run'  => $is_dry_run ? 1 : 0,
		] );

		$items = [];
		foreach ( $page_ids as $pid ) {
			$items[] = [
				'entity_id'   => $pid,
				'entity_type' => 'city_page',
				'action'      => 'publish_page',
			];
		}

		Bulk_Job_Repository::create_items_batch( $job_id, $items );
		wp_send_json_success( [ 'job_id' => $job_id, 'total_items' => count( $items ) ] );
	}

	/**
	 * AJAX Handler: Queue Rollback.
	 */
	public function ajax_bulk_rollback_queue(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::BULK_ROLLBACK );

		$target_job_id = sanitize_text_field( $_POST['target_job_id'] ?? '' );
		$is_dry_run    = ! empty( $_POST['is_dry_run'] );

		if ( empty( $target_job_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing target job ID for rollback.', 'seo-city-pages' ) ] );
		}

		$target_job = Bulk_Job_Repository::get_job( $target_job_id );
		if ( ! $target_job ) {
			wp_send_json_error( [ 'message' => __( 'Target job not found.', 'seo-city-pages' ) ] );
		}

		$created_ids = ! empty( $target_job->created_entity_ids ) ? ( is_string( $target_job->created_entity_ids ) ? json_decode( $target_job->created_entity_ids, true ) : $target_job->created_entity_ids ) : [];
		if ( empty( $created_ids ) ) {
			// Fallback: search city pages table by import_job_id.
			$pages = City_Page_Repository::get_by_import_job_id( $target_job_id );
			foreach ( $pages as $p ) {
				$created_ids[] = (int) $p->id;
			}
		}

		if ( empty( $created_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No created pages found for this job.', 'seo-city-pages' ) ] );
		}

		$job_id = Bulk_Job_Repository::create_job( [
			'job_type'    => Bulk_Job_Repository::TYPE_ROLLBACK,
			'total_items' => count( $created_ids ),
			'is_dry_run'  => $is_dry_run ? 1 : 0,
			'job_params'  => [ 'target_job_id' => $target_job_id, 'is_dry_run' => $is_dry_run ],
		] );

		$items = [];
		foreach ( $created_ids as $cid ) {
			$items[] = [
				'entity_id'   => (int) $cid,
				'entity_type' => 'city_page',
				'action'      => 'rollback_page',
			];
		}

		Bulk_Job_Repository::create_items_batch( $job_id, $items );
		wp_send_json_success( [ 'job_id' => $job_id, 'total_items' => count( $items ) ] );
	}

	/**
	 * AJAX Handler: Poll & Progress Execution.
	 */
	public function ajax_job_poll(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::MANAGE );

		$job_id = sanitize_text_field( $_POST['job_id'] ?? '' );
		if ( empty( $job_id ) ) {
			wp_send_json_error( [ 'message' => 'Missing job_id' ] );
		}

		// Process batch chunk (e.g. 25 items).
		$batch_size = isset( $_POST['batch_size'] ) ? max( 5, min( 100, (int) $_POST['batch_size'] ) ) : 25;
		$result     = Bulk_Job_Runner::process_batch( $job_id, $batch_size );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX Handler: Job Controls (pause, resume, cancel, retry).
	 */
	public function ajax_job_control(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::MANAGE );

		$job_id = sanitize_text_field( $_POST['job_id'] ?? '' );
		$action = sanitize_text_field( $_POST['control_action'] ?? '' );

		if ( empty( $job_id ) || empty( $action ) ) {
			wp_send_json_error( [ 'message' => 'Missing parameters.' ] );
		}

		switch ( $action ) {
			case 'pause':
				$ok = Bulk_Job_Runner::pause_job( $job_id );
				break;
			case 'resume':
				$ok = Bulk_Job_Runner::resume_job( $job_id );
				break;
			case 'cancel':
				$ok = Bulk_Job_Runner::cancel_job( $job_id );
				break;
			case 'retry':
				$cnt = Bulk_Job_Runner::retry_job( $job_id );
				$ok  = $cnt >= 0;
				break;
			default:
				$ok = false;
				break;
		}

		if ( ! $ok ) {
			wp_send_json_error( [ 'message' => sprintf( 'Failed to perform "%s" on job.', $action ) ] );
		}

		$progress = Bulk_Job_Repository::recalculate_job_progress( $job_id );
		wp_send_json_success( $progress );
	}

	/**
	 * AJAX Handler: Streamed CSV Export Download.
	 */
	public function ajax_export_download(): void {
		Security::verify_nonce_or_abort( self::NONCE_ACTION );
		Security::enforce_capability( Capabilities::BULK_EXPORT );

		$export_type = sanitize_text_field( $_POST['export_type'] ?? CSV_Exporter::TYPE_CITY_PAGES );
		$filters     = [
			'status'            => sanitize_text_field( $_POST['status'] ?? '' ),
			'generation_status' => sanitize_text_field( $_POST['generation_status'] ?? '' ),
			'job_id'            => sanitize_text_field( $_POST['job_id'] ?? '' ),
		];

		CSV_Exporter::export( $export_type, $filters, 'stream' );
		exit;
	}
}
