<?php
/**
 * Master Bulk Job Asynchronous Runner & Orchestrator.
 *
 * @package SeoCityPages\Jobs
 */

namespace SeoCityPages\Jobs;

use SeoCityPages\Jobs\Bulk_Job_Repository;
use SeoCityPages\Jobs\Handlers\Page_Creation_Handler;
use SeoCityPages\Jobs\Handlers\Content_Generation_Handler;
use SeoCityPages\Jobs\Handlers\SEO_Generation_Handler;
use SeoCityPages\Jobs\Handlers\Validation_Handler;
use SeoCityPages\Jobs\Handlers\Approval_Handler;
use SeoCityPages\Jobs\Handlers\Publish_Handler;
use SeoCityPages\Jobs\Handlers\CSV_Import_Handler;
use SeoCityPages\Jobs\Handlers\Rollback_Handler;
use SeoCityPages\Logging\Logger;
use SeoCityPages\Logging\Audit_Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bulk Job Runner Class.
 */
class Bulk_Job_Runner {

	/**
	 * Process a batch chunk of items for a specific bulk job.
	 *
	 * @param string $job_id     UUID.
	 * @param int    $batch_size Max items per chunk (default: 25).
	 * @return array Batch progress and status result.
	 */
	public static function process_batch( string $job_id, int $batch_size = 25 ): array {
		// Check if global safe mode or processing pause is active.
		if ( \SeoCityPages\Diagnostics\Safe_Mode_Manager::is_processing_paused() ) {
			return [
				'success'     => true,
				'status'      => 'paused',
				'message'     => 'Background processing is suspended by Safe Mode or Processing Pause.',
				'is_finished' => false,
			];
		}

		$job = Bulk_Job_Repository::get_job( $job_id );
		if ( ! $job ) {
			return [
				'success' => false,
				'error'   => sprintf( 'Bulk job %s not found.', $job_id ),
			];
		}

		// Check if job has been paused, cancelled, or is already finished.
		if ( in_array( $job->status, [ Bulk_Job_Repository::STATUS_PAUSED, Bulk_Job_Repository::STATUS_CANCELLED, Bulk_Job_Repository::STATUS_COMPLETED, Bulk_Job_Repository::STATUS_COMPLETED_ERRORS, Bulk_Job_Repository::STATUS_FAILED ], true ) ) {
			return [
				'success'     => true,
				'status'      => $job->status,
				'message'     => sprintf( 'Job is currently in %s state; skipping execution.', $job->status ),
				'is_finished' => true,
			];
		}

		// Transition pending to running.
		if ( Bulk_Job_Repository::STATUS_PENDING === $job->status ) {
			Bulk_Job_Repository::update_job( $job_id, [
				'status'     => Bulk_Job_Repository::STATUS_RUNNING,
				'started_at' => current_time( 'mysql' ),
			] );
			Audit_Logger::log(
				Audit_Logger::EVENT_BULK_ACTION,
				'bulk_job',
				(int) $job->id,
				[ 'job_id' => $job_id, 'action' => 'job_started', 'job_type' => $job->job_type ]
			);
		}

		// Update heartbeat for stale recovery avoidance.
		Bulk_Job_Repository::update_heartbeat( $job_id );

		// Generate unique worker token for concurrency lock isolation.
		$worker_id = 'worker_' . ( function_exists( 'wp_generate_password' ) ? wp_generate_password( 12, false ) : bin2hex( random_bytes( 6 ) ) );

		// Atomically claim batch of pending items.
		$claimed_items = Bulk_Job_Repository::claim_pending_items( $job_id, $batch_size, $worker_id );

		if ( empty( $claimed_items ) ) {
			// No items left to claim; recalculate final progress.
			$progress = Bulk_Job_Repository::recalculate_job_progress( $job_id );
			return array_merge( [ 'success' => true, 'processed_in_batch' => 0 ], $progress );
		}

		$job_params = ! empty( $job->job_params ) ? ( is_string( $job->job_params ) ? json_decode( $job->job_params, true ) : $job->job_params ) : [];
		$created_ids = ! empty( $job->created_entity_ids ) ? ( is_string( $job->created_entity_ids ) ? json_decode( $job->created_entity_ids, true ) : $job->created_entity_ids ) : [];
		if ( ! is_array( $created_ids ) ) {
			$created_ids = [];
		}

		$processed_count = 0;

		foreach ( $claimed_items as $item ) {
			$processed_count++;
			$result = self::dispatch_item( $item, $job, $job_params );

			if ( ! empty( $result['skipped'] ) ) {
				Bulk_Job_Repository::skip_item( (int) $item->id, $result['reason'] ?? '' );
			} elseif ( ! empty( $result['success'] ) ) {
				Bulk_Job_Repository::complete_item( (int) $item->id, $result );
				if ( ! empty( $result['created_id'] ) ) {
					$created_ids[] = (int) $result['created_id'];
				}
			} else {
				$err_code = $result['error_code'] ?? 'item_error';
				$err_msg  = $result['message'] ?? 'Unknown execution error.';
				Bulk_Job_Repository::fail_item( (int) $item->id, $err_code, $err_msg, true );
			}
		}

		// Update tracked created IDs if new records were created.
		if ( ! empty( $created_ids ) ) {
			Bulk_Job_Repository::update_job( $job_id, [
				'created_entity_ids' => array_values( array_unique( $created_ids ) ),
			] );
		}

		// Recalculate progress.
		$progress = Bulk_Job_Repository::recalculate_job_progress( $job_id );

		if ( ! empty( $progress['is_finished'] ) ) {
			Audit_Logger::log(
				Audit_Logger::EVENT_BULK_ACTION,
				'bulk_job',
				(int) $job->id,
				[
					'job_id'     => $job_id,
					'action'     => 'job_completed',
					'status'     => $progress['status'],
					'total'      => $progress['total'],
					'completed'  => $progress['completed'],
					'failed'     => $progress['failed'],
					'skipped'    => $progress['skipped'],
				]
			);
		}

		return array_merge( [ 'success' => true, 'processed_in_batch' => $processed_count ], $progress );
	}

	/**
	 * Dispatch a single item to its corresponding handler.
	 *
	 * @param object $item       Item object.
	 * @param object $job        Job object.
	 * @param array  $job_params Job parameters.
	 * @return array
	 */
	private static function dispatch_item( object $item, object $job, array $job_params ): array {
		try {
			switch ( $job->job_type ) {
				case Bulk_Job_Repository::TYPE_BULK_CREATE:
					return Page_Creation_Handler::process( $item, $job, $job_params );

				case Bulk_Job_Repository::TYPE_BULK_GENERATE_CONTENT:
					return Content_Generation_Handler::process( $item, $job, $job_params );

				case Bulk_Job_Repository::TYPE_BULK_GENERATE_SEO:
					return SEO_Generation_Handler::process( $item, $job, $job_params );

				case Bulk_Job_Repository::TYPE_BULK_VALIDATE:
					return Validation_Handler::process( $item, $job, $job_params );

				case Bulk_Job_Repository::TYPE_BULK_APPROVE:
					return Approval_Handler::process( $item, $job, $job_params );

				case Bulk_Job_Repository::TYPE_BULK_PUBLISH:
					return Publish_Handler::process( $item, $job, $job_params );

				case Bulk_Job_Repository::TYPE_BULK_QUALITY_SCAN:
					return Quality_Scan_Handler::process( $item, $job, $job_params );

				case Bulk_Job_Repository::TYPE_CSV_IMPORT:
					return CSV_Import_Handler::process( $item, $job, $job_params );

				case Bulk_Job_Repository::TYPE_ROLLBACK:
					return Rollback_Handler::process( $item, $job, $job_params );

				default:
					return [
						'success'    => false,
						'error_code' => 'unknown_job_type',
						'message'    => sprintf( 'Unsupported job type: %s', $job->job_type ),
					];
			}
		} catch ( \Throwable $e ) {
			Logger::error( 'Exception in bulk item processing', [ 'item_id' => $item->id, 'error' => $e->getMessage() ] );
			return [
				'success'    => false,
				'error_code' => 'exception',
				'message'    => $e->getMessage(),
			];
		}
	}

	/**
	 * Pause a running job.
	 *
	 * @param string $job_id UUID.
	 * @return bool
	 */
	public static function pause_job( string $job_id ): bool {
		$job = Bulk_Job_Repository::get_job( $job_id );
		if ( ! $job || ! in_array( $job->status, [ Bulk_Job_Repository::STATUS_PENDING, Bulk_Job_Repository::STATUS_RUNNING ], true ) ) {
			return false;
		}

		$updated = Bulk_Job_Repository::update_job( $job_id, [ 'status' => Bulk_Job_Repository::STATUS_PAUSED ] );
		if ( $updated ) {
			Audit_Logger::log(
				Audit_Logger::EVENT_BULK_ACTION,
				'bulk_job',
				(int) $job->id,
				[ 'job_id' => $job_id, 'action' => 'job_paused' ]
			);
		}
		return $updated;
	}

	/**
	 * Resume a paused job.
	 *
	 * @param string $job_id UUID.
	 * @return bool
	 */
	public static function resume_job( string $job_id ): bool {
		$job = Bulk_Job_Repository::get_job( $job_id );
		if ( ! $job || Bulk_Job_Repository::STATUS_PAUSED !== $job->status ) {
			return false;
		}

		$updated = Bulk_Job_Repository::update_job( $job_id, [ 'status' => Bulk_Job_Repository::STATUS_RUNNING ] );
		if ( $updated ) {
			Audit_Logger::log(
				Audit_Logger::EVENT_BULK_ACTION,
				'bulk_job',
				(int) $job->id,
				[ 'job_id' => $job_id, 'action' => 'job_resumed' ]
			);
		}
		return $updated;
	}

	/**
	 * Cancel a job.
	 *
	 * @param string $job_id UUID.
	 * @return bool
	 */
	public static function cancel_job( string $job_id ): bool {
		$job = Bulk_Job_Repository::get_job( $job_id );
		if ( ! $job || in_array( $job->status, [ Bulk_Job_Repository::STATUS_COMPLETED, Bulk_Job_Repository::STATUS_COMPLETED_ERRORS, Bulk_Job_Repository::STATUS_CANCELLED ], true ) ) {
			return false;
		}

		$updated = Bulk_Job_Repository::update_job( $job_id, [
			'status'       => Bulk_Job_Repository::STATUS_CANCELLED,
			'completed_at' => current_time( 'mysql' ),
		] );

		if ( $updated ) {
			Audit_Logger::log(
				Audit_Logger::EVENT_BULK_ACTION,
				'bulk_job',
				(int) $job->id,
				[ 'job_id' => $job_id, 'action' => 'job_cancelled' ]
			);
		}
		return $updated;
	}

	/**
	 * Retry failed items for a job.
	 *
	 * @param string $job_id UUID.
	 * @return int Number of items reset for retry.
	 */
	public static function retry_job( string $job_id ): int {
		$count = Bulk_Job_Repository::reset_failed_items_for_retry( $job_id );
		if ( $count > 0 ) {
			$job = Bulk_Job_Repository::get_job( $job_id );
			Audit_Logger::log(
				Audit_Logger::EVENT_BULK_ACTION,
				'bulk_job',
				(int) ( $job ? $job->id : 0 ),
				[ 'job_id' => $job_id, 'action' => 'job_retry_triggered', 'reset_count' => $count ]
			);
		}
		return $count;
	}

	/**
	 * Run periodic stale item & worker crash recovery.
	 *
	 * @param int $stale_threshold_seconds Timeout window in seconds.
	 * @return int
	 */
	public static function run_stale_recovery( int $stale_threshold_seconds = 600 ): int {
		return Bulk_Job_Repository::recover_stale_items( $stale_threshold_seconds );
	}
}
