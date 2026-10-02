<?php
/**
 * Bulk Generation Job Runner & Batch Processor.
 *
 * Coordinates non-blocking chunked batch execution of AI content generation
 * across multiple SEO City Pages with fault tolerance.
 *
 * @package SeoCityPages\AI
 */

namespace SeoCityPages\AI;

use SeoCityPages\Database\Generation_Job_Repository;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generation_Job_Runner Class.
 */
class Generation_Job_Runner {

	/**
	 * Start a new bulk generation job.
	 *
	 * @param int[] $page_ids Array of City Page IDs.
	 * @param array $options  Runtime options (model, provider, etc.).
	 * @return int Created Job DB ID.
	 */
	public static function start_job( array $page_ids, array $options = [] ): int {
		$clean_ids = array_values( array_unique( array_filter( array_map( 'intval', $page_ids ) ) ) );
		if ( empty( $clean_ids ) ) {
			return 0;
		}

		$job_id = 'gen_' . wp_generate_uuid4();

		$db_id = Generation_Job_Repository::create( [
			'job_id'      => $job_id,
			'user_id'     => get_current_user_id(),
			'job_type'    => 'bulk_generate',
			'provider'    => $options['provider'] ?? 'openai',
			'model'       => $options['model'] ?? 'gpt-4o-mini',
			'total_pages' => count( $clean_ids ),
			'page_ids'    => $clean_ids,
		] );

		Logger::info(
			sprintf( 'Initialized bulk AI generation job #%d (%s) for %d pages', $db_id, $job_id, count( $clean_ids ) ),
			[ 'job_db_id' => $db_id, 'job_id' => $job_id, 'total_pages' => count( $clean_ids ) ]
		);

		return $db_id;
	}

	/**
	 * Process a single batch chunk for a job.
	 *
	 * @param int $job_db_id    Generation job database ID.
	 * @param int $batch_size   Number of pages to process in this request.
	 * @return array Batch execution result summary.
	 */
	public static function process_batch( int $job_db_id, int $batch_size = 1 ): array {
		$job = Generation_Job_Repository::get( $job_db_id );
		if ( ! $job ) {
			return [
				'success' => false,
				'error'   => __( 'Generation job not found.', 'seo-city-pages' ),
			];
		}

		if ( Generation_Job_Repository::STATUS_CANCELLED === $job->status ) {
			return [
				'success'      => false,
				'is_cancelled' => true,
				'error'        => __( 'Generation job was cancelled.', 'seo-city-pages' ),
			];
		}

		if ( Generation_Job_Repository::STATUS_PAUSED === $job->status ) {
			return [
				'success'   => false,
				'is_paused' => true,
				'error'     => __( 'Generation job is currently paused.', 'seo-city-pages' ),
			];
		}

		$page_ids = json_decode( (string) $job->page_ids, true );
		if ( ! is_array( $page_ids ) ) {
			$page_ids = [];
		}

		$processed_count = (int) $job->processed_pages;
		$total_count     = (int) $job->total_pages;

		if ( $processed_count >= $total_count || empty( $page_ids ) ) {
			Generation_Job_Repository::complete( $job_db_id );
			return [
				'success'     => true,
				'is_complete' => true,
				'processed'   => $total_count,
				'total'       => $total_count,
				'success_cnt' => (int) $job->success_pages,
				'failed_cnt'  => (int) $job->failed_pages,
			];
		}

		// Slice next chunk.
		$chunk = array_slice( $page_ids, $processed_count, $batch_size );
		$batch_processed = 0;
		$batch_success   = 0;
		$batch_failed    = 0;
		$new_errors      = [];

		$manager = AI_Manager::instance();

		foreach ( $chunk as $page_id ) {
			$batch_processed++;
			$result = $manager->generate_for_page( (int) $page_id, [
				'provider' => $job->provider,
				'model'    => $job->model,
			] );

			if ( $result->is_success() ) {
				$batch_success++;
			} else {
				$batch_failed++;
				$new_errors[] = [
					'page_id' => $page_id,
					'error'   => $result->get_error_message(),
					'code'    => $result->get_error_code(),
					'time'    => current_time( 'mysql' ),
				];
			}
		}

		// Update job progress.
		Generation_Job_Repository::update_progress(
			$job_db_id,
			$batch_processed,
			$batch_success,
			$batch_failed,
			$new_errors
		);

		$refreshed_job = Generation_Job_Repository::get( $job_db_id );
		$new_processed = (int) $refreshed_job->processed_pages;
		$is_complete   = $new_processed >= $total_count;

		return [
			'success'     => true,
			'job_id'      => $job->job_id,
			'is_complete' => $is_complete,
			'processed'   => $new_processed,
			'total'       => $total_count,
			'success_cnt' => (int) $refreshed_job->success_pages,
			'failed_cnt'  => (int) $refreshed_job->failed_pages,
			'errors'      => $new_errors,
		];
	}
}
