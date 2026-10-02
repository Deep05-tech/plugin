<?php
/**
 * Bulk Job & Item Database Repository.
 *
 * @package SeoCityPages\Jobs
 */

namespace SeoCityPages\Jobs;

use SeoCityPages\Database\Schema;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bulk Job Repository Class.
 */
class Bulk_Job_Repository {

	/**
	 * Job Status Constants.
	 */
	public const STATUS_PENDING          = 'pending';
	public const STATUS_RUNNING          = 'running';
	public const STATUS_PAUSED           = 'paused';
	public const STATUS_COMPLETED        = 'completed';
	public const STATUS_COMPLETED_ERRORS = 'completed_errors';
	public const STATUS_FAILED           = 'failed';
	public const STATUS_CANCELLED        = 'cancelled';

	/**
	 * Item Status Constants.
	 */
	public const ITEM_PENDING    = 'pending';
	public const ITEM_PROCESSING = 'processing';
	public const ITEM_COMPLETED  = 'completed';
	public const ITEM_FAILED     = 'failed';
	public const ITEM_SKIPPED    = 'skipped';

	/**
	 * Job Types.
	 */
	public const TYPE_BULK_CREATE           = 'bulk_create';
	public const TYPE_BULK_GENERATE_CONTENT = 'bulk_generate_content';
	public const TYPE_BULK_GENERATE_SEO     = 'bulk_generate_seo';
	public const TYPE_BULK_VALIDATE         = 'bulk_validate';
	public const TYPE_BULK_APPROVE          = 'bulk_approve';
	public const TYPE_BULK_PUBLISH          = 'bulk_publish';
	public const TYPE_BULK_QUALITY_SCAN     = 'bulk_quality_scan';
	public const TYPE_CSV_IMPORT            = 'csv_import';
	public const TYPE_CSV_EXPORT            = 'csv_export';
	public const TYPE_ROLLBACK              = 'rollback';

	/**
	 * Create a new bulk job.
	 *
	 * @param array $data Job parameters.
	 * @return string Generated job UUID, or empty string on failure.
	 */
	public static function create_job( array $data ): string {
		global $wpdb;

		$job_id = ! empty( $data['job_id'] ) ? sanitize_text_field( $data['job_id'] ) : wp_generate_uuid4();
		$table  = Schema::get_table_name( Schema::TABLE_BULK_JOBS );

		$insert_data = [
			'job_id'             => $job_id,
			'job_type'           => sanitize_text_field( $data['job_type'] ?? self::TYPE_BULK_CREATE ),
			'user_id'            => isset( $data['user_id'] ) ? (int) $data['user_id'] : get_current_user_id(),
			'status'             => sanitize_text_field( $data['status'] ?? self::STATUS_PENDING ),
			'total_items'        => isset( $data['total_items'] ) ? (int) $data['total_items'] : 0,
			'pending_items'      => isset( $data['pending_items'] ) ? (int) $data['pending_items'] : (int) ( $data['total_items'] ?? 0 ),
			'processing_items'   => 0,
			'completed_items'    => 0,
			'failed_items'       => 0,
			'skipped_items'      => 0,
			'current_batch'      => 0,
			'retry_count'        => 0,
			'error_count'        => 0,
			'is_dry_run'         => ! empty( $data['is_dry_run'] ) ? 1 : 0,
			'job_params'         => isset( $data['job_params'] ) ? ( is_array( $data['job_params'] ) ? wp_json_encode( $data['job_params'] ) : $data['job_params'] ) : null,
			'job_summary'        => null,
			'created_entity_ids' => isset( $data['created_entity_ids'] ) ? ( is_array( $data['created_entity_ids'] ) ? wp_json_encode( $data['created_entity_ids'] ) : $data['created_entity_ids'] ) : null,
			'stale_heartbeat'    => current_time( 'mysql' ),
			'started_at'         => null,
			'completed_at'       => null,
			'created_at'         => current_time( 'mysql' ),
			'updated_at'         => current_time( 'mysql' ),
		];

		$result = $wpdb->insert( $table, $insert_data );
		if ( false === $result ) {
			Logger::error( 'Failed to create bulk job in database', [ 'job_id' => $job_id, 'error' => $wpdb->last_error ] );
			return '';
		}

		return $job_id;
	}

	/**
	 * Get a job record by UUID or integer ID.
	 *
	 * @param string|int $job_identifier UUID or DB ID.
	 * @return object|null
	 */
	public static function get_job( $job_identifier ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOBS );

		if ( is_numeric( $job_identifier ) ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", (int) $job_identifier ) );
		} else {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE job_id = %s LIMIT 1", (string) $job_identifier ) );
		}

		return $row ?: null;
	}

	/**
	 * Update job metadata or status.
	 *
	 * @param string $job_id UUID.
	 * @param array  $data   Key-value pairs to update.
	 * @return bool
	 */
	public static function update_job( string $job_id, array $data ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOBS );

		$update_data = $data;
		$update_data['updated_at'] = current_time( 'mysql' );

		if ( isset( $update_data['job_params'] ) && is_array( $update_data['job_params'] ) ) {
			$update_data['job_params'] = wp_json_encode( $update_data['job_params'] );
		}
		if ( isset( $update_data['job_summary'] ) && is_array( $update_data['job_summary'] ) ) {
			$update_data['job_summary'] = wp_json_encode( $update_data['job_summary'] );
		}
		if ( isset( $update_data['created_entity_ids'] ) && is_array( $update_data['created_entity_ids'] ) ) {
			$update_data['created_entity_ids'] = wp_json_encode( $update_data['created_entity_ids'] );
		}

		$result = $wpdb->update( $table, $update_data, [ 'job_id' => $job_id ] );
		return false !== $result;
	}

	/**
	 * Update job heartbeat timestamp to prevent stale detection.
	 *
	 * @param string $job_id UUID.
	 * @return void
	 */
	public static function update_heartbeat( string $job_id ): void {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOBS );
		$wpdb->update(
			$table,
			[
				'stale_heartbeat' => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			],
			[ 'job_id' => $job_id ]
		);
	}

	/**
	 * Insert a single job item.
	 *
	 * @param array $data Item fields.
	 * @return int Inserted ID or 0 on failure.
	 */
	public static function create_item( array $data ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$insert_data = [
			'job_id'        => sanitize_text_field( $data['job_id'] ?? '' ),
			'entity_id'     => isset( $data['entity_id'] ) ? (int) $data['entity_id'] : 0,
			'entity_type'   => sanitize_text_field( $data['entity_type'] ?? 'city_page' ),
			'action'        => sanitize_text_field( $data['action'] ?? '' ),
			'status'        => sanitize_text_field( $data['status'] ?? self::ITEM_PENDING ),
			'attempts'      => 0,
			'max_attempts'  => isset( $data['max_attempts'] ) ? (int) $data['max_attempts'] : 3,
			'locked_at'     => null,
			'locked_by'     => null,
			'error_code'    => null,
			'error_message' => null,
			'item_payload'  => isset( $data['item_payload'] ) ? ( is_array( $data['item_payload'] ) ? wp_json_encode( $data['item_payload'] ) : $data['item_payload'] ) : null,
			'item_result'   => null,
			'started_at'    => null,
			'completed_at'  => null,
			'created_at'    => current_time( 'mysql' ),
			'updated_at'    => current_time( 'mysql' ),
		];

		$result = $wpdb->insert( $table, $insert_data );
		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Batch insert multiple job items in chunks for high scale.
	 *
	 * @param string $job_id UUID.
	 * @param array  $items  Array of item associative arrays.
	 * @return int Total inserted.
	 */
	public static function create_items_batch( string $job_id, array $items ): int {
		global $wpdb;
		if ( empty( $items ) ) {
			return 0;
		}

		$table        = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );
		$inserted     = 0;
		$now          = current_time( 'mysql' );
		$chunks       = array_chunk( $items, 250 );

		foreach ( $chunks as $chunk ) {
			$value_strings = [];
			$placeholders  = [];
			$params        = [];

			foreach ( $chunk as $item ) {
				$payload = isset( $item['item_payload'] ) ? ( is_array( $item['item_payload'] ) ? wp_json_encode( $item['item_payload'] ) : $item['item_payload'] ) : null;
				$entity_id = isset( $item['entity_id'] ) ? (int) $item['entity_id'] : 0;
				$entity_type = sanitize_text_field( $item['entity_type'] ?? 'city_page' );
				$action = sanitize_text_field( $item['action'] ?? '' );
				$max_attempts = isset( $item['max_attempts'] ) ? (int) $item['max_attempts'] : 3;

				$placeholders[] = '(%s, %d, %s, %s, %s, %d, %d, %s, %s, %s)';
				$params[]       = $job_id;
				$params[]       = $entity_id;
				$params[]       = $entity_type;
				$params[]       = $action;
				$params[]       = self::ITEM_PENDING;
				$params[]       = 0;
				$params[]       = $max_attempts;
				$params[]       = $payload;
				$params[]       = $now;
				$params[]       = $now;
			}

			$query = "INSERT INTO {$table} (job_id, entity_id, entity_type, action, status, attempts, max_attempts, item_payload, created_at, updated_at) VALUES " . implode( ', ', $placeholders );
			$res   = $wpdb->query( $wpdb->prepare( $query, $params ) );
			if ( false !== $res ) {
				$inserted += count( $chunk );
			}
		}

		return $inserted;
	}

	/**
	 * Atomically claim a batch of pending items for a worker.
	 *
	 * @param string $job_id    UUID.
	 * @param int    $limit     Max items to claim.
	 * @param string $worker_id Unique worker lock token.
	 * @return array Claimed item objects.
	 */
	public static function claim_pending_items( string $job_id, int $limit, string $worker_id ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		// 1. Fetch candidate IDs.
		$candidate_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE job_id = %s AND status = %s ORDER BY id ASC LIMIT %d",
				$job_id,
				self::ITEM_PENDING,
				$limit
			)
		);

		if ( empty( $candidate_ids ) ) {
			return [];
		}

		$now     = current_time( 'mysql' );
		$id_list = implode( ',', array_map( 'intval', $candidate_ids ) );

		// 2. Atomically lock and transition candidates.
		$update_query = "
			UPDATE {$table} 
			SET status = %s, locked_at = %s, locked_by = %s, attempts = attempts + 1, started_at = %s, updated_at = %s 
			WHERE id IN ({$id_list}) AND status = %s
		";
		$wpdb->query(
			$wpdb->prepare(
				$update_query,
				self::ITEM_PROCESSING,
				$now,
				$worker_id,
				$now,
				$now,
				self::ITEM_PENDING
			)
		);

		// 3. Return successfully claimed items.
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id IN ({$id_list}) AND locked_by = %s",
				$worker_id
			)
		) ?: [];
	}

	/**
	 * Mark an item as completed.
	 *
	 * @param int   $item_id DB ID.
	 * @param array $result  Result metadata.
	 * @return bool
	 */
	public static function complete_item( int $item_id, array $result = [] ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$res = $wpdb->update(
			$table,
			[
				'status'        => self::ITEM_COMPLETED,
				'locked_at'     => null,
				'locked_by'     => null,
				'item_result'   => ! empty( $result ) ? wp_json_encode( $result ) : null,
				'completed_at'  => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			],
			[ 'id' => $item_id ]
		);

		return false !== $res;
	}

	/**
	 * Mark an item as failed.
	 *
	 * @param int    $item_id       DB ID.
	 * @param string $error_code    Error code.
	 * @param string $error_message Safe error message.
	 * @param bool   $can_retry     Whether to allow retry or mark permanently failed.
	 * @return bool
	 */
	public static function fail_item( int $item_id, string $error_code, string $error_message, bool $can_retry = true ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$item = $wpdb->get_row( $wpdb->prepare( "SELECT attempts, max_attempts FROM {$table} WHERE id = %d LIMIT 1", $item_id ) );
		$attempts     = $item ? (int) $item->attempts : 1;
		$max_attempts = $item ? (int) $item->max_attempts : 3;

		$status = ( $can_retry && $attempts < $max_attempts ) ? self::ITEM_PENDING : self::ITEM_FAILED;

		$res = $wpdb->update(
			$table,
			[
				'status'        => $status,
				'locked_at'     => null,
				'locked_by'     => null,
				'error_code'    => sanitize_text_field( $error_code ),
				'error_message' => sanitize_textarea_field( $error_message ),
				'completed_at'  => ( self::ITEM_FAILED === $status ) ? current_time( 'mysql' ) : null,
				'updated_at'    => current_time( 'mysql' ),
			],
			[ 'id' => $item_id ]
		);

		return false !== $res;
	}

	/**
	 * Mark an item as skipped.
	 *
	 * @param int    $item_id DB ID.
	 * @param string $reason  Reason string.
	 * @return bool
	 */
	public static function skip_item( int $item_id, string $reason = '' ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$res = $wpdb->update(
			$table,
			[
				'status'        => self::ITEM_SKIPPED,
				'locked_at'     => null,
				'locked_by'     => null,
				'error_message' => sanitize_textarea_field( $reason ),
				'completed_at'  => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			],
			[ 'id' => $item_id ]
		);

		return false !== $res;
	}

	/**
	 * Recalculate job counts and progress percentage.
	 *
	 * @param string $job_id UUID.
	 * @return array Progress report.
	 */
	public static function recalculate_job_progress( string $job_id ): array {
		global $wpdb;
		$items_table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );
		$jobs_table  = Schema::get_table_name( Schema::TABLE_BULK_JOBS );

		$counts = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT 
					COUNT(*) as total,
					SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
					SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
					SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
					SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
					SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) as skipped
				FROM {$items_table}
				WHERE job_id = %s
				",
				$job_id
			)
		);

		$total      = (int) ( $counts->total ?? 0 );
		$pending    = (int) ( $counts->pending ?? 0 );
		$processing = (int) ( $counts->processing ?? 0 );
		$completed  = (int) ( $counts->completed ?? 0 );
		$failed     = (int) ( $counts->failed ?? 0 );
		$skipped    = (int) ( $counts->skipped ?? 0 );

		$done_count = $completed + $failed + $skipped;
		$percentage = $total > 0 ? round( ( $done_count / $total ) * 100, 1 ) : 0.0;

		$job = self::get_job( $job_id );
		$current_status = $job ? $job->status : self::STATUS_RUNNING;

		// Automatically evaluate final status if all items finished and job is not paused/cancelled.
		$new_status = $current_status;
		$completed_at = null;

		if ( $total > 0 && 0 === ( $pending + $processing ) && in_array( $current_status, [ self::STATUS_RUNNING, self::STATUS_PENDING ], true ) ) {
			$completed_at = current_time( 'mysql' );
			$new_status = ( $failed > 0 ) ? self::STATUS_COMPLETED_ERRORS : self::STATUS_COMPLETED;
		}

		$wpdb->update(
			$jobs_table,
			[
				'total_items'      => $total,
				'pending_items'    => $pending,
				'processing_items' => $processing,
				'completed_items'  => $completed,
				'failed_items'     => $failed,
				'skipped_items'    => $skipped,
				'error_count'      => $failed,
				'status'           => $new_status,
				'completed_at'     => $completed_at ?: ( $job ? $job->completed_at : null ),
				'updated_at'       => current_time( 'mysql' ),
			],
			[ 'job_id' => $job_id ]
		);

		return [
			'job_id'      => $job_id,
			'status'      => $new_status,
			'total'       => $total,
			'pending'     => $pending,
			'processing'  => $processing,
			'completed'   => $completed,
			'failed'      => $failed,
			'skipped'     => $skipped,
			'percentage'  => $percentage,
			'is_finished' => in_array( $new_status, [ self::STATUS_COMPLETED, self::STATUS_COMPLETED_ERRORS, self::STATUS_FAILED, self::STATUS_CANCELLED ], true ),
		];
	}

	/**
	 * Reset failed items to pending for a retry run.
	 *
	 * @param string $job_id UUID.
	 * @return int Count of items reset.
	 */
	public static function reset_failed_items_for_retry( string $job_id ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$res = $wpdb->query(
			$wpdb->prepare(
				"
				UPDATE {$table}
				SET status = %s, error_code = NULL, error_message = NULL, locked_at = NULL, locked_by = NULL, completed_at = NULL, updated_at = %s
				WHERE job_id = %s AND status = %s
				",
				self::ITEM_PENDING,
				current_time( 'mysql' ),
				$job_id,
				self::ITEM_FAILED
			)
		);

		if ( $res ) {
			$jobs_table = Schema::get_table_name( Schema::TABLE_BULK_JOBS );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$jobs_table} SET status = %s, retry_count = retry_count + 1, completed_at = NULL, updated_at = %s WHERE job_id = %s",
					self::STATUS_PENDING,
					current_time( 'mysql' ),
					$job_id
				)
			);
			self::recalculate_job_progress( $job_id );
		}

		return (int) $res;
	}

	/**
	 * Detect and recover stale job items where worker timed out or crashed.
	 *
	 * @param int $stale_threshold_seconds Timeout window (default 600s = 10 mins).
	 * @return int Count of items recovered.
	 */
	public static function recover_stale_items( int $stale_threshold_seconds = 600 ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$cutoff = date( 'Y-m-d H:i:s', time() - $stale_threshold_seconds );

		// Reset items stuck in processing beyond timeout back to pending if attempts < max_attempts.
		$res = $wpdb->query(
			$wpdb->prepare(
				"
				UPDATE {$table}
				SET status = %s, locked_at = NULL, locked_by = NULL, updated_at = %s
				WHERE status = %s AND locked_at < %s AND attempts < max_attempts
				",
				self::ITEM_PENDING,
				current_time( 'mysql' ),
				self::ITEM_PROCESSING,
				$cutoff
			)
		);

		// Any items exceeding max attempts transition to failed.
		$wpdb->query(
			$wpdb->prepare(
				"
				UPDATE {$table}
				SET status = %s, locked_at = NULL, locked_by = NULL, error_code = 'worker_timeout', error_message = 'Item processing exceeded maximum execution timeout.', updated_at = %s
				WHERE status = %s AND locked_at < %s AND attempts >= max_attempts
				",
				self::ITEM_FAILED,
				current_time( 'mysql' ),
				self::ITEM_PROCESSING,
				$cutoff
			)
		);

		return (int) $res;
	}

	/**
	 * Query recent bulk jobs.
	 *
	 * @param int   $limit   Limit.
	 * @param int   $offset  Offset.
	 * @param array $filters Filters.
	 * @return array
	 */
	public static function get_recent_jobs( int $limit = 50, int $offset = 0, array $filters = [] ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOBS );

		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_text_field( $filters['status'] );
		}
		if ( ! empty( $filters['job_type'] ) ) {
			$where[]  = 'job_type = %s';
			$params[] = sanitize_text_field( $filters['job_type'] );
		}

		$where_clause = implode( ' AND ', $where );
		$params[]     = $limit;
		$params[]     = $offset;

		$sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY id DESC LIMIT %d OFFSET %d";
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) ?: [];
	}

	/**
	 * Get failed items for error reporting.
	 *
	 * @param string $job_id UUID.
	 * @param int    $limit  Limit.
	 * @param int    $offset Offset.
	 * @return array
	 */
	public static function get_failed_items( string $job_id, int $limit = 100, int $offset = 0 ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE job_id = %s AND status = %s ORDER BY id ASC LIMIT %d OFFSET %d",
				$job_id,
				self::ITEM_FAILED,
				$limit,
				$offset
			)
		) ?: [];
	}
}
