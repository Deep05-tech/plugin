<?php
/**
 * Generation Job Database Repository.
 *
 * Tracks single and bulk AI generation jobs, batch queue progress,
 * and error summaries.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generation_Job_Repository Class.
 */
class Generation_Job_Repository {

	public const STATUS_PENDING          = 'pending';
	public const STATUS_PROCESSING       = 'processing';
	public const STATUS_PAUSED           = 'paused';
	public const STATUS_COMPLETED        = 'completed';
	public const STATUS_COMPLETED_ERRORS = 'completed_errors';
	public const STATUS_FAILED           = 'failed';
	public const STATUS_CANCELLED        = 'cancelled';

	/**
	 * Create a new generation job record.
	 *
	 * @param array $data Job data.
	 * @return int Inserted ID.
	 */
	public static function create( array $data ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );

		$now    = current_time( 'mysql' );
		$job_id = ! empty( $data['job_id'] ) ? sanitize_text_field( $data['job_id'] ) : 'gen_' . wp_generate_uuid4();

		$page_ids = $data['page_ids'] ?? [];
		$page_ids_json = is_string( $page_ids ) ? $page_ids : wp_json_encode( array_map( 'intval', $page_ids ) );

		$insert_data = [
			'job_id'            => $job_id,
			'user_id'           => (int) ( $data['user_id'] ?? get_current_user_id() ),
			'job_type'          => sanitize_text_field( $data['job_type'] ?? 'bulk_generate' ),
			'provider'          => sanitize_text_field( $data['provider'] ?? 'openai' ),
			'model'             => sanitize_text_field( $data['model'] ?? 'gpt-4o-mini' ),
			'total_pages'       => (int) ( $data['total_pages'] ?? ( is_array( $page_ids ) ? count( $page_ids ) : 0 ) ),
			'processed_pages'   => 0,
			'success_pages'     => 0,
			'failed_pages'      => 0,
			'status'            => self::STATUS_PENDING,
			'page_ids'          => $page_ids_json,
			'errors_summary'    => wp_json_encode( [] ),
			'external_batch_id' => ! empty( $data['external_batch_id'] ) ? sanitize_text_field( $data['external_batch_id'] ) : null,
			'started_at'        => $now,
			'completed_at'      => null,
			'created_at'        => $now,
			'updated_at'        => $now,
		];

		$result = $wpdb->insert( $table, $insert_data );

		if ( false === $result ) {
			Logger::error( 'Failed to create generation job', [ 'error' => $wpdb->last_error ] );
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Retrieve a job by database ID.
	 *
	 * @param int $id Job ID.
	 * @return object|null
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
				$id
			)
		);

		return $row ?: null;
	}

	/**
	 * Retrieve a job by string job_id.
	 *
	 * @param string $job_id Unique job ID.
	 * @return object|null
	 */
	public static function get_by_job_id( string $job_id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE job_id = %s LIMIT 1",
				$job_id
			)
		);

		return $row ?: null;
	}

	/**
	 * Update incremental progress of a job.
	 *
	 * @param int   $id             Job ID.
	 * @param int   $processed_inc  Count of processed pages to increment.
	 * @param int   $success_inc    Count of successful pages to increment.
	 * @param int   $failed_inc     Count of failed pages to increment.
	 * @param array $new_errors     Array of new error records to append.
	 * @return bool
	 */
	public static function update_progress( int $id, int $processed_inc = 1, int $success_inc = 1, int $failed_inc = 0, array $new_errors = [] ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );

		$job = self::get( $id );
		if ( ! $job ) {
			return false;
		}

		$processed = (int) $job->processed_pages + $processed_inc;
		$success   = (int) $job->success_pages + $success_inc;
		$failed    = (int) $job->failed_pages + $failed_inc;

		$existing_errors = json_decode( (string) $job->errors_summary, true );
		if ( ! is_array( $existing_errors ) ) {
			$existing_errors = [];
		}

		if ( ! empty( $new_errors ) ) {
			$existing_errors = array_merge( $existing_errors, $new_errors );
		}

		$status = $job->status;
		if ( self::STATUS_PENDING === $status ) {
			$status = self::STATUS_PROCESSING;
		}

		if ( $processed >= (int) $job->total_pages && (int) $job->total_pages > 0 ) {
			$status = ( $failed > 0 ) ? self::STATUS_COMPLETED_ERRORS : self::STATUS_COMPLETED;
		}

		$now = current_time( 'mysql' );

		$update_data = [
			'processed_pages' => $processed,
			'success_pages'   => $success,
			'failed_pages'    => $failed,
			'errors_summary'  => wp_json_encode( $existing_errors ),
			'status'          => $status,
			'updated_at'      => $now,
		];

		if ( in_array( $status, [ self::STATUS_COMPLETED, self::STATUS_COMPLETED_ERRORS ], true ) && empty( $job->completed_at ) ) {
			$update_data['completed_at'] = $now;
		}

		$result = $wpdb->update( $table, $update_data, [ 'id' => $id ] );

		return false !== $result;
	}

	/**
	 * Mark a job as completed.
	 *
	 * @param int    $id     Job ID.
	 * @param string $status Completed status.
	 * @return bool
	 */
	public static function complete( int $id, string $status = self::STATUS_COMPLETED ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );

		$now = current_time( 'mysql' );

		return false !== $wpdb->update(
			$table,
			[
				'status'       => $status,
				'completed_at' => $now,
				'updated_at'   => $now,
			],
			[ 'id' => $id ]
		);
	}

	/**
	 * Mark a job as failed with a critical error.
	 *
	 * @param int    $id    Job ID.
	 * @param string $error Error message.
	 * @return bool
	 */
	public static function fail( int $id, string $error ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );

		$job = self::get( $id );
		if ( ! $job ) {
			return false;
		}

		$existing_errors = json_decode( (string) $job->errors_summary, true );
		if ( ! is_array( $existing_errors ) ) {
			$existing_errors = [];
		}
		$existing_errors[] = [ 'error' => $error, 'time' => current_time( 'mysql' ) ];

		$now = current_time( 'mysql' );

		return false !== $wpdb->update(
			$table,
			[
				'status'         => self::STATUS_FAILED,
				'errors_summary' => wp_json_encode( $existing_errors ),
				'completed_at'   => $now,
				'updated_at'     => $now,
			],
			[ 'id' => $id ]
		);
	}

	/**
	 * Get recent generation jobs.
	 *
	 * @param int $limit Max records.
	 * @return object[]
	 */
	public static function get_recent_jobs( int $limit = 10 ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d",
				$limit
			)
		);

		return $rows ?: [];
	}

	/**
	 * Pause a job.
	 *
	 * @param int $id Job ID.
	 * @return bool
	 */
	public static function pause( int $id ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );
		$now   = current_time( 'mysql' );

		return false !== $wpdb->update(
			$table,
			[
				'status'     => self::STATUS_PAUSED,
				'updated_at' => $now,
			],
			[ 'id' => $id ]
		);
	}

	/**
	 * Resume a paused job.
	 *
	 * @param int $id Job ID.
	 * @return bool
	 */
	public static function resume( int $id ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );
		$now   = current_time( 'mysql' );

		return false !== $wpdb->update(
			$table,
			[
				'status'     => self::STATUS_PROCESSING,
				'updated_at' => $now,
			],
			[ 'id' => $id ]
		);
	}

	/**
	 * Cancel a job.
	 *
	 * @param int $id Job ID.
	 * @return bool
	 */
	public static function cancel( int $id ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );
		$now   = current_time( 'mysql' );

		return false !== $wpdb->update(
			$table,
			[
				'status'       => self::STATUS_CANCELLED,
				'completed_at' => $now,
				'updated_at'   => $now,
			],
			[ 'id' => $id ]
		);
	}

	/**
	 * Retrieve the most recent active / in-progress generation job.
	 *
	 * @return object|null
	 */
	public static function get_active_job(): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_GENERATION_JOBS );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status IN (%s, %s, %s) AND processed_pages < total_pages ORDER BY id DESC LIMIT 1",
				self::STATUS_PENDING,
				self::STATUS_PROCESSING,
				self::STATUS_PAUSED
			)
		);

		return $row ?: null;
	}
}
