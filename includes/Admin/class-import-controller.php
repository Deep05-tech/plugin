<?php
/**
 * CSV Import Screen Controller & AJAX Batch Processor.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;
use SeoCityPages\Import\CSV_Validator;
use SeoCityPages\Import\CSV_Processor;
use SeoCityPages\Database\Import_Job_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import Controller Class.
 */
class Import_Controller {

	/**
	 * Constructor: Register AJAX handlers.
	 */
	public function __construct() {
		add_action( 'wp_ajax_seo_city_pages_process_batch', [ $this, 'ajax_process_batch' ] );
	}

	/**
	 * Render the CSV Import admin view.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE );

		$action = sanitize_text_field( $_GET['action'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Handle Download Sample CSV.
		if ( 'download_sample' === $action ) {
			$this->handle_download_sample();
			return;
		}

		// Handle Download Error CSV.
		if ( 'download_errors' === $action ) {
			$this->handle_download_errors();
			return;
		}

		$preview_data = null;
		$error_notice = '';

		// Handle Upload & Preview form submission.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['seo_city_pages_upload_csv'] ) ) {
			$preview_data = $this->handle_upload_and_preview( $error_notice );
		}

		$recent_jobs = Import_Job_Repository::get_recent_jobs( 15 );
		$total_pages = City_Page_Repository::count();

		include SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/csv-import.php';
	}

	/**
	 * Handle CSV upload and generate validation preview.
	 *
	 * @param string $error_notice Reference to error message.
	 * @return array|null Preview payload or null.
	 */
	private function handle_upload_and_preview( string &$error_notice ): ?array {
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			$error_notice = __( 'Permission denied. You do not have sufficient permissions to upload imports.', 'seo-city-pages' );
			Logger::warning( 'Unauthorized CSV upload attempt by user ID ' . get_current_user_id() );
			return null;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'import_upload' ) ) {
			$error_notice = __( 'Security verification failed or session expired. Please refresh the page and try again.', 'seo-city-pages' );
			Logger::warning( 'CSV upload failed nonce verification for user ID ' . get_current_user_id() );
			return null;
		}

		if ( ! isset( $_FILES['csv_file'] ) || ! is_array( $_FILES['csv_file'] ) ) {
			$error_notice = __( 'Please select a valid CSV file to upload.', 'seo-city-pages' );
			return null;
		}

		$file = $_FILES['csv_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$upload_error = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );

		if ( UPLOAD_ERR_OK !== $upload_error ) {
			switch ( $upload_error ) {
				case UPLOAD_ERR_NO_FILE:
					$error_notice = __( 'Please select a CSV file before submitting.', 'seo-city-pages' );
					break;
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$error_notice = __( 'The uploaded file exceeds the maximum allowed file size.', 'seo-city-pages' );
					break;
				case UPLOAD_ERR_PARTIAL:
					$error_notice = __( 'The file was only partially uploaded. Please try again.', 'seo-city-pages' );
					break;
				default:
					/* translators: %d: PHP upload error code */
					$error_notice = sprintf( __( 'File upload failed with error code %d. Please try again.', 'seo-city-pages' ), $upload_error );
					break;
			}
			return null;
		}

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$error_notice = __( 'Uploaded file could not be verified on the server.', 'seo-city-pages' );
			return null;
		}

		// Enforce a maximum file size limit (10MB).
		$max_bytes = 10 * 1024 * 1024;
		if ( ! empty( $file['size'] ) && $file['size'] > $max_bytes ) {
			$error_notice = __( 'The uploaded CSV file exceeds the 10MB limit.', 'seo-city-pages' );
			return null;
		}

		$orig_name = sanitize_file_name( $file['name'] ?? '' );
		$file_ext  = strtolower( pathinfo( $orig_name, PATHINFO_EXTENSION ) );

		if ( 'csv' !== $file_ext ) {
			$error_notice = __( 'Invalid file extension. Only standard .csv files are supported.', 'seo-city-pages' );
			return null;
		}

		// Create safe upload directory.
		$upload_dir = wp_upload_dir();
		$target_dir = trailingslashit( $upload_dir['basedir'] ) . 'seo-city-pages-imports';
		if ( ! file_exists( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
			// Write index.php and .htaccess for security.
			file_put_contents( $target_dir . '/index.php', '<?php // Silence is golden.' );
			file_put_contents( $target_dir . '/.htaccess', 'Deny from all' );
		}

		$job_id    = wp_generate_uuid4();
		$safe_name = 'import_' . $job_id . '.csv';
		$target_path = $target_dir . '/' . $safe_name;

		if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
			$error_notice = __( 'Failed to save uploaded file to storage.', 'seo-city-pages' );
			return null;
		}

		// Run validation on the uploaded CSV file.
		$validation = CSV_Validator::validate_file( $target_path );

		if ( empty( $validation['success'] ) ) {
			$error_notice = $validation['error'] ?? __( 'CSV validation failed.', 'seo-city-pages' );
			@unlink( $target_path );
			return null;
		}

		// Create initial Import Job record.
		$job_db_id = Import_Job_Repository::create( [
			'job_id'     => $job_id,
			'file_name'  => $orig_name,
			'file_path'  => $target_path,
			'total_rows' => $validation['total_rows'],
			'status'     => 'ready',
		] );

		// Save parsed valid rows in a temporary transient for batch execution.
		set_transient( 'seo_city_pages_job_rows_' . $job_id, $validation['valid_rows'], DAY_IN_SECONDS );

		return [
			'job_id'          => $job_id,
			'file_name'       => $orig_name,
			'total_rows'      => $validation['total_rows'],
			'valid_count'     => $validation['valid_count'],
			'invalid_count'   => $validation['invalid_count'],
			'duplicate_count' => $validation['duplicate_count'],
			'valid_rows'      => array_slice( $validation['valid_rows'], 0, 10 ), // Preview top 10.
			'invalid_rows'    => $validation['invalid_rows'],
			'duplicate_rows'  => $validation['duplicate_rows'],
		];
	}

	/**
	 * AJAX endpoint for executing a batch chunk.
	 *
	 * @return void
	 */
	public function ajax_process_batch(): void {
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			Logger::warning( 'Unauthorized AJAX batch import attempt by user ID ' . get_current_user_id() );
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'seo-city-pages' ) ], 403 );
		}

		$nonce = sanitize_text_field( $_POST['nonce'] ?? '' );
		if ( ! Security::verify_nonce( $nonce, 'batch_import' ) ) {
			Logger::warning( 'AJAX batch import failed nonce verification for user ID ' . get_current_user_id() );
			wp_send_json_error( [ 'message' => __( 'Security nonce expired. Please refresh the page and try again.', 'seo-city-pages' ) ], 403 );
		}

		$job_id = sanitize_text_field( $_POST['job_id'] ?? '' );
		$offset = max( 0, (int) ( $_POST['offset'] ?? 0 ) );
		$limit  = max( 1, min( 100, (int) ( $_POST['limit'] ?? 50 ) ) );

		if ( empty( $job_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid job ID.', 'seo-city-pages' ) ], 400 );
		}

		$all_rows = get_transient( 'seo_city_pages_job_rows_' . $job_id );
		if ( false === $all_rows || ! is_array( $all_rows ) ) {
			wp_send_json_error( [ 'message' => __( 'Import session expired or rows could not be loaded. Please re-upload.', 'seo-city-pages' ) ], 404 );
		}

		$total_valid = count( $all_rows );
		$batch_slice = array_slice( $all_rows, $offset, $limit );

		$batch_result = CSV_Processor::process_batch( $batch_slice, $job_id );

		$new_offset = $offset + count( $batch_slice );
		$is_finished = $new_offset >= $total_valid;

		if ( $is_finished ) {
			Import_Job_Repository::complete( $job_id );
			delete_transient( 'seo_city_pages_job_rows_' . $job_id );
		}

		$job = Import_Job_Repository::get_by_job_id( $job_id );

		wp_send_json_success( [
			'job_id'       => $job_id,
			'offset'       => $new_offset,
			'total'        => $total_valid,
			'is_finished'  => $is_finished,
			'percentage'   => $total_valid > 0 ? min( 100, round( ( $new_offset / $total_valid ) * 100 ) ) : 100,
			'batch_stats'  => $batch_result,
			'job_stats'    => [
				'processed'             => $job ? (int) $job->processed_rows : $new_offset,
				'created_countries'     => $job ? (int) $job->created_countries : 0,
				'created_states'        => $job ? (int) $job->created_states : 0,
				'created_cities'        => $job ? (int) $job->created_cities : 0,
				'created_services'      => $job ? (int) $job->created_services : 0,
				'created_combinations'  => $job ? (int) $job->created_combinations : 0,
				'existing_combinations' => $job ? (int) $job->existing_combinations : 0,
				'failed_rows'           => $job ? (int) $job->failed_rows : 0,
				'status'                => $job ? $job->status : 'processing',
			],
		] );
	}

	/**
	 * Stream sample CSV download to the browser.
	 *
	 * @return void
	 */
	private function handle_download_sample(): void {
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'seo-city-pages' ) );
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=sample-city-pages-import.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $output, [ 'country', 'state', 'city', 'service' ] );
		fputcsv( $output, [ 'India', 'Gujarat', 'Rajkot', 'SEO Agency' ] );
		fputcsv( $output, [ 'India', 'Gujarat', 'Ahmedabad', 'SEO Agency' ] );
		fputcsv( $output, [ 'India', 'Gujarat', 'Surat', 'SEO Agency' ] );
		fputcsv( $output, [ 'India', 'Maharashtra', 'Mumbai', 'Digital Marketing Agency' ] );
		fputcsv( $output, [ 'India', 'Gujarat', 'Rajkot', 'Web Development' ] );
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Stream failed rows error CSV download to the browser.
	 *
	 * @return void
	 */
	private function handle_download_errors(): void {
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'seo-city-pages' ) );
		}

		$job_id = sanitize_text_field( $_GET['job_id'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job    = Import_Job_Repository::get_by_job_id( $job_id );

		if ( ! $job ) {
			wp_die( esc_html__( 'Import job not found.', 'seo-city-pages' ) );
		}

		$errors = ! empty( $job->errors_summary ) ? json_decode( $job->errors_summary, true ) : [];
		if ( ! is_array( $errors ) ) {
			$errors = [];
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=import-errors-' . sanitize_file_name( $job_id ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $output, [ 'Row', 'Country', 'State', 'City', 'Service', 'Error Reason' ] );

		foreach ( $errors as $err ) {
			fputcsv( $output, [
				Security::sanitize_csv_cell( $err['row_index'] ?? '' ),
				Security::sanitize_csv_cell( $err['country'] ?? '' ),
				Security::sanitize_csv_cell( $err['state'] ?? '' ),
				Security::sanitize_csv_cell( $err['city'] ?? '' ),
				Security::sanitize_csv_cell( $err['service'] ?? '' ),
				Security::sanitize_csv_cell( $err['reason'] ?? '' ),
			] );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
