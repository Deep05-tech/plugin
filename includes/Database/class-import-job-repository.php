<?php
/**
 * Import Job Data Repository.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import Job Repository Class.
 */
class Import_Job_Repository {

	/**
	 * Get job by internal DB ID.
	 *
	 * @param int $id Primary ID.
	 * @return object|null Job row or null.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_IMPORT_JOBS );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Get job by unique job UUID / Token.
	 *
	 * @param string $job_id Unique job string identifier.
	 * @return object|null Job row or null.
	 */
	public static function get_by_job_id( string $job_id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_IMPORT_JOBS );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE job_id = %s LIMIT 1", $job_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Create a new import job tracking record.
	 *
	 * @param array $data Input job attributes.
	 * @return int|\WP_Error Primary ID or WP_Error.
	 */
	public static function create( array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_IMPORT_JOBS );

		$job_id    = sanitize_text_field( $data['job_id'] ?? wp_generate_uuid4() );
		$file_name = sanitize_text_field( $data['file_name'] ?? 'import.csv' );
		$file_path = sanitize_text_field( $data['file_path'] ?? '' );
		$user_id   = (int) ( $data['user_id'] ?? get_current_user_id() );
		$total     = max( 0, (int) ( $data['total_rows'] ?? 0 ) );
		$status    = sanitize_text_field( $data['status'] ?? 'pending' );
		$now       = current_time( 'mysql', true );

		$inserted = $wpdb->insert(
			$table,
			[
				'job_id'                => $job_id,
				'file_name'             => $file_name,
				'file_path'             => $file_path,
				'user_id'               => $user_id,
				'total_rows'            => $total,
				'processed_rows'        => 0,
				'created_countries'     => 0,
				'created_states'        => 0,
				'created_cities'        => 0,
				'created_services'      => 0,
				'created_combinations'  => 0,
				'existing_combinations' => 0,
				'duplicate_rows'        => 0,
				'failed_rows'           => 0,
				'status'                => $status,
				'errors_summary'        => null,
				'started_at'            => $now,
				'completed_at'          => null,
				'created_at'            => $now,
				'updated_at'            => $now,
			],
			[
				'%s', '%s', '%s', '%d', '%d',
				'%d', '%d', '%d', '%d', '%d',
				'%d', '%d', '%d', '%d', '%s',
				'%s', '%s', '%s', '%s', '%s',
			]
		);

		if ( ! $inserted ) {
			return new \WP_Error( 'db_insert_error', __( 'Failed to initialize import job.', 'seo-city-pages' ) );
		}

		$id = (int) $wpdb->insert_id;

		Logger::info(
			sprintf( 'Initialized import job ID %d (Token: %s)', $id, $job_id ),
			[ 'job_id' => $job_id, 'file' => $file_name, 'total_rows' => $total ]
		);

		return $id;
	}

	/**
	 * Update job progress & metric counters.
	 *
	 * @param string|int $identifier Job ID or primary ID.
	 * @param array      $data       Fields to update.
	 * @return bool
	 */
	public static function update_progress( $identifier, array $data ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_IMPORT_JOBS );

		$where = is_numeric( $identifier ) ? [ 'id' => (int) $identifier ] : [ 'job_id' => (string) $identifier ];
		$data['updated_at'] = current_time( 'mysql', true );

		if ( isset( $data['errors_summary'] ) && is_array( $data['errors_summary'] ) ) {
			$data['errors_summary'] = wp_json_encode( $data['errors_summary'] );
		}

		$updated = $wpdb->update( $table, $data, $where );

		return false !== $updated;
	}

	/**
	 * Mark job completed.
	 *
	 * @param string|int $identifier Job ID or primary ID.
	 * @param array      $final_metrics Final statistics.
	 * @return bool
	 */
	public static function complete( $identifier, array $final_metrics = [] ): bool {
		$final_metrics['completed_at'] = current_time( 'mysql', true );
		$failed_count = (int) ( $final_metrics['failed_rows'] ?? 0 );
		if ( empty( $final_metrics['status'] ) ) {
			$final_metrics['status'] = $failed_count > 0 ? 'completed_errors' : 'completed';
		}

		return self::update_progress( $identifier, $final_metrics );
	}

	/**
	 * Get recent import jobs for history log.
	 *
	 * @param int $limit Max rows.
	 * @return object[]
	 */
	public static function get_recent_jobs( int $limit = 20 ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_IMPORT_JOBS );
		$limit = max( 1, $limit );

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return is_array( $results ) ? $results : [];
	}
}
