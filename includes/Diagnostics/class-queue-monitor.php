<?php
/**
 * Queue & Background Worker Diagnostics Monitor.
 *
 * @package SeoCityPages\Diagnostics
 */

namespace SeoCityPages\Diagnostics;

use SeoCityPages\Database\Schema;
use SeoCityPages\Jobs\Bulk_Job_Repository;
use SeoCityPages\Database\Audit_Log_Repository;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queue Monitor Class.
 */
class Queue_Monitor {

	public const DEFAULT_STALE_THRESHOLD = 1800; // 30 minutes

	/**
	 * Get comprehensive queue health metrics.
	 *
	 * @return array
	 */
	public static function get_queue_metrics(): array {
		global $wpdb;
		$jobs_table  = Schema::get_table_name( Schema::TABLE_BULK_JOBS );
		$items_table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$settings        = get_option( 'seo_city_pages_settings', [] );
		$stale_threshold = ! empty( $settings['stale_job_threshold'] ) ? (int) $settings['stale_job_threshold'] : self::DEFAULT_STALE_THRESHOLD;

		// 1. Job Status Aggregates.
		$job_counts = [
			'pending'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = %s", Bulk_Job_Repository::STATUS_PENDING ) ),
			'running'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = %s", Bulk_Job_Repository::STATUS_RUNNING ) ),
			'paused'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = %s", Bulk_Job_Repository::STATUS_PAUSED ) ),
			'completed' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = %s", Bulk_Job_Repository::STATUS_COMPLETED ) ),
			'failed'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = %s", Bulk_Job_Repository::STATUS_FAILED ) ),
		];

		// 2. Item Status Aggregates.
		$item_counts = [
			'pending'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$items_table} WHERE status = %s", Bulk_Job_Repository::ITEM_PENDING ) ),
			'processing' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$items_table} WHERE status = %s", Bulk_Job_Repository::ITEM_PROCESSING ) ),
			'completed'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$items_table} WHERE status = %s", Bulk_Job_Repository::ITEM_COMPLETED ) ),
			'failed'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$items_table} WHERE status = %s", Bulk_Job_Repository::ITEM_FAILED ) ),
			'skipped'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$items_table} WHERE status = %s", Bulk_Job_Repository::ITEM_SKIPPED ) ),
		];

		// 3. Stale Item Detection (Locked > threshold).
		$cutoff_time = gmdate( 'Y-m-d H:i:s', time() - $stale_threshold );
		$stale_items = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$items_table} WHERE status = %s AND locked_at < %s",
				Bulk_Job_Repository::ITEM_PROCESSING,
				$cutoff_time
			)
		);

		// 4. Timestamps for Oldest/Latest jobs.
		$oldest_pending = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM {$jobs_table} WHERE status = %s ORDER BY id ASC LIMIT 1", Bulk_Job_Repository::STATUS_PENDING ) );
		$oldest_running = $wpdb->get_var( $wpdb->prepare( "SELECT started_at FROM {$jobs_table} WHERE status = %s ORDER BY id ASC LIMIT 1", Bulk_Job_Repository::STATUS_RUNNING ) );
		$last_completed = $wpdb->get_var( $wpdb->prepare( "SELECT completed_at FROM {$jobs_table} WHERE status = %s ORDER BY id DESC LIMIT 1", Bulk_Job_Repository::STATUS_COMPLETED ) );
		$last_failed    = $wpdb->get_var( $wpdb->prepare( "SELECT updated_at FROM {$jobs_table} WHERE status = %s ORDER BY id DESC LIMIT 1", Bulk_Job_Repository::STATUS_FAILED ) );

		$status = Diagnostics_Engine::STATUS_HEALTHY;
		$issues = [];

		if ( $stale_items > 0 ) {
			$status   = Diagnostics_Engine::STATUS_WARNING;
			$issues[] = [
				'title'       => __( 'Stale Queue Items Detected', 'seo-city-pages' ),
				'explanation' => sprintf( __( '%d queue items have been in "processing" state longer than %d seconds.', 'seo-city-pages' ), $stale_items, $stale_threshold ),
				'action'      => __( 'Run "Recover Stale Jobs" to reset orphaned locks back to pending.', 'seo-city-pages' ),
			];
		}

		if ( $item_counts['pending'] > 5000 ) {
			$issues[] = [
				'title'       => __( 'High Queue Backlog', 'seo-city-pages' ),
				'explanation' => sprintf( __( 'Current queue backlog is %d pending items.', 'seo-city-pages' ), $item_counts['pending'] ),
				'action'      => __( 'Verify that background AJAX runners or crons are executing actively.', 'seo-city-pages' ),
			];
		}

		return [
			'status'          => $status,
			'job_counts'      => $job_counts,
			'item_counts'     => $item_counts,
			'stale_items'     => $stale_items,
			'stale_threshold' => $stale_threshold,
			'oldest_pending'  => $oldest_pending ?: 'None',
			'oldest_running'  => $oldest_running ?: 'None',
			'last_completed'  => $last_completed ?: 'None',
			'last_failed'     => $last_failed ?: 'None',
			'issues'          => $issues,
		];
	}

	/**
	 * Safely recover stale items across all running jobs.
	 *
	 * @param int $threshold_seconds Stale threshold.
	 * @return array Result summary.
	 */
	public static function recover_stale_jobs( int $threshold_seconds = 1800 ): array {
		$recovered_count = Bulk_Job_Repository::recover_stale_items( $threshold_seconds );

		Audit_Log_Repository::log_event(
			'queue_repair_recover_stale',
			'queue',
			0,
			[
				'recovered_items'   => $recovered_count,
				'threshold_seconds' => $threshold_seconds,
			]
		);

		Logger::info( sprintf( 'Queue Repair: Recovered %d stale items.', $recovered_count ), [], Logger::CAT_QUEUE );

		return [
			'success'         => true,
			'recovered_count' => $recovered_count,
			'message'         => sprintf( __( 'Successfully recovered and reset %d stale queue items to pending.', 'seo-city-pages' ), $recovered_count ),
		];
	}

	/**
	 * Re-queue failed items for a specific job.
	 *
	 * @param string $job_id Job UUID.
	 * @return array
	 */
	public static function retry_failed_job_items( string $job_id ): array {
		$reset_count = Bulk_Job_Repository::reset_failed_items_for_retry( $job_id );

		Audit_Log_Repository::log_event(
			'queue_repair_retry_failed',
			'bulk_job',
			0,
			[
				'job_id'      => $job_id,
				'reset_count' => $reset_count,
			]
		);

		Logger::info( sprintf( 'Queue Repair: Reset %d failed items for retry on job %s.', $reset_count, $job_id ), [], Logger::CAT_QUEUE );

		return [
			'success'     => true,
			'reset_count' => $reset_count,
			'message'     => sprintf( __( 'Successfully reset %d failed items for retry.', 'seo-city-pages' ), $reset_count ),
		];
	}

	/**
	 * Rebuild job counter states from ground truth items.
	 *
	 * @param string $job_id Job UUID.
	 * @return array
	 */
	public static function rebuild_job_state( string $job_id ): array {
		$progress = Bulk_Job_Repository::recalculate_job_progress( $job_id );

		Audit_Log_Repository::log_event(
			'queue_repair_rebuild_state',
			'bulk_job',
			0,
			[
				'job_id'   => $job_id,
				'progress' => $progress,
			]
		);

		Logger::info( sprintf( 'Queue Repair: Rebuilt progress counters for job %s.', $job_id ), [], Logger::CAT_QUEUE );

		return [
			'success'  => true,
			'progress' => $progress,
			'message'  => __( 'Job state and progress counters rebuilt successfully.', 'seo-city-pages' ),
		];
	}
}
