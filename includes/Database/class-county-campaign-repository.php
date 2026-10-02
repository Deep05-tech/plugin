<?php
/**
 * County Campaign Repository & Batch Manager.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Data\County_Dataset;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * County Campaign Repository Class.
 */
class County_Campaign_Repository {

	/**
	 * Create a new County Campaign and divide counties into 100-page batches.
	 *
	 * @param array $args Campaign arguments (topic, service_id, target_states, options).
	 * @return array{campaign_id: string, total_counties: int, total_batches: int, batches: array}|false
	 */
	public static function create_campaign( array $args ) {
		global $wpdb;

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 );
		}
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			@wp_raise_memory_limit( 'admin' );
		}

		$topic         = sanitize_text_field( $args['topic'] ?? '' );
		$service_id    = (int) ( $args['service_id'] ?? 0 );
		$target_states = $args['target_states'] ?? 'ALL';
		$batch_size    = (int) ( $args['batch_size'] ?? 100 );
		if ( $batch_size <= 0 ) {
			$batch_size = 100;
		}

		if ( empty( $topic ) ) {
			return false;
		}

		// Ensure schema tables exist.
		Migrator::maybe_upgrade();

		// Ensure Service exists or create it.
		if ( $service_id <= 0 ) {
			$service_slug     = sanitize_title( $topic );
			$existing_service = Service_Repository::get_by_slug( $service_slug );
			if ( ! $existing_service ) {
				$existing_service = Service_Repository::get_by_name( $topic );
			}

			if ( $existing_service ) {
				$service_id = (int) $existing_service->id;
			} else {
				$created = Service_Repository::create( [
					'name'        => $topic,
					'slug'        => $service_slug,
					'description' => sprintf( __( 'Automated service for %s', 'seo-city-pages' ), $topic ),
					'status'      => 'active',
				] );
				$service_id = is_wp_error( $created ) ? 0 : (int) $created;
			}
		}

		// Get counties based on selected states.
		$states_filter = ( 'ALL' === $target_states || empty( $target_states ) ) ? [] : $target_states;
		$counties = County_Dataset::get_counties( $states_filter );

		if ( empty( $counties ) ) {
			return false;
		}

		$total_counties = count( $counties );
		$campaign_id    = 'camp_' . wp_generate_password( 12, false, false );
		$chunks         = array_chunk( $counties, $batch_size );
		$total_batches  = count( $chunks );

		$states_json = is_array( $target_states ) ? implode( ',', $target_states ) : (string) $target_states;

		// Insert Campaign Record.
		$campaign_table = Schema::get_table_name( Schema::TABLE_COUNTY_CAMPAIGNS );
		$now            = current_time( 'mysql' );
		$user_id        = get_current_user_id();

		$camp_inserted = $wpdb->insert(
			$campaign_table,
			[
				'campaign_id'       => $campaign_id,
				'topic'             => $topic,
				'service_id'        => $service_id,
				'target_states'     => $states_json,
				'total_counties'    => $total_counties,
				'batch_size'        => $batch_size,
				'total_batches'     => $total_batches,
				'completed_batches' => 0,
				'total_pages'       => $total_counties,
				'generated_pages'   => 0,
				'published_pages'   => 0,
				'failed_pages'      => 0,
				'status'            => 'ready',
				'options'           => wp_json_encode( $args['options'] ?? [] ),
				'created_at'        => $now,
				'updated_at'        => $now,
			],
			[ '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $camp_inserted ) {
			// Reinstall schema in case table was missing on older installations and retry.
			Schema::install();
			$wpdb->insert(
				$campaign_table,
				[
					'campaign_id'       => $campaign_id,
					'topic'             => $topic,
					'service_id'        => $service_id,
					'target_states'     => $states_json,
					'total_counties'    => $total_counties,
					'batch_size'        => $batch_size,
					'total_batches'     => $total_batches,
					'completed_batches' => 0,
					'total_pages'       => $total_counties,
					'generated_pages'   => 0,
					'published_pages'   => 0,
					'failed_pages'      => 0,
					'status'            => 'ready',
					'options'           => wp_json_encode( $args['options'] ?? [] ),
					'created_at'        => $now,
					'updated_at'        => $now,
				],
				[ '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' ]
			);
		}

		$bulk_jobs_table  = Schema::get_table_name( Schema::TABLE_BULK_JOBS );
		$bulk_items_table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );
		$batch_details    = [];

		// Create individual batch jobs.
		foreach ( $chunks as $index => $batch_counties ) {
			$batch_num = $index + 1;
			$job_id    = $campaign_id . '_b' . $batch_num;
			$count     = count( $batch_counties );

			// Sample states covered in this batch.
			$states_covered = array_unique( array_column( $batch_counties, 'state_code' ) );
			$states_label   = implode( ', ', array_slice( $states_covered, 0, 3 ) ) . ( count( $states_covered ) > 3 ? '...' : '' );

			$job_params = [
				'campaign_id'    => $campaign_id,
				'batch_number'   => $batch_num,
				'topic'          => $topic,
				'service_id'     => $service_id,
				'states_covered' => $states_covered,
				'states_label'   => $states_label,
				'counties_count' => $count,
			];

			$wpdb->insert(
				$bulk_jobs_table,
				[
					'job_id'           => $job_id,
					'job_type'         => 'county_batch_generate',
					'user_id'          => $user_id,
					'status'           => 'pending',
					'total_items'      => $count,
					'pending_items'    => $count,
					'processing_items' => 0,
					'completed_items'  => 0,
					'failed_items'     => 0,
					'skipped_items'    => 0,
					'current_batch'    => $batch_num,
					'job_params'       => wp_json_encode( $job_params ),
					'created_at'       => $now,
					'updated_at'       => $now,
				],
				[ '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ]
			);

			// High-performance multi-row bulk insert for items in this batch.
			self::insert_batch_items_fast( $bulk_items_table, $job_id, $campaign_id, $service_id, $topic, $batch_counties, $now );

			$batch_details[] = [
				'batch_number' => $batch_num,
				'job_id'       => $job_id,
				'total_items'  => $count,
				'states_label' => $states_label,
				'status'       => 'pending',
			];
		}

		Logger::info(
			sprintf( 'Created County Campaign %s with %d counties across %d batches', $campaign_id, $total_counties, $total_batches ),
			[ 'campaign_id' => $campaign_id, 'topic' => $topic ]
		);

		return [
			'campaign_id'    => $campaign_id,
			'topic'          => $topic,
			'service_id'     => $service_id,
			'total_counties' => $total_counties,
			'total_batches'  => $total_batches,
			'batch_size'     => $batch_size,
			'batches'        => $batch_details,
		];
	}

	/**
	 * Perform high-performance multi-row insert for batch items.
	 *
	 * @param string $table          Bulk items table name.
	 * @param string $job_id         Job ID.
	 * @param string $campaign_id    Campaign ID.
	 * @param int    $service_id     Service ID.
	 * @param string $topic          Topic string.
	 * @param array  $batch_counties Array of county data.
	 * @param string $timestamp      Current timestamp.
	 * @return void
	 */
	public static function insert_batch_items_fast( string $table, string $job_id, string $campaign_id, int $service_id, string $topic, array $batch_counties, string $timestamp ): void {
		global $wpdb;

		if ( empty( $batch_counties ) ) {
			return;
		}

		// Split into chunks of 100 rows for memory safety and MySQL packet compliance.
		$chunks = array_chunk( $batch_counties, 100 );

		foreach ( $chunks as $chunk ) {
			$placeholders = [];
			$values       = [];

			foreach ( $chunk as $county_item ) {
				$payload = wp_json_encode( [
					'campaign_id' => $campaign_id,
					'service_id'  => $service_id,
					'topic'       => $topic,
					'state_code'  => $county_item['state_code'],
					'state_name'  => $county_item['state_name'],
					'county_name' => $county_item['county_name'],
					'county_slug' => $county_item['county_slug'],
				] );

				$placeholders[] = '(%s, %d, %s, %s, %s, %d, %d, %s, %s, %s)';
				$values[]       = $job_id;
				$values[]       = 0;
				$values[]       = 'county_page';
				$values[]       = 'generate';
				$values[]       = 'pending';
				$values[]       = 0;
				$values[]       = 3;
				$values[]       = $payload;
				$values[]       = $timestamp;
				$values[]       = $timestamp;
			}

			$query = "INSERT INTO {$table} (job_id, entity_id, entity_type, action, status, attempts, max_attempts, item_payload, created_at, updated_at) VALUES " . implode( ', ', $placeholders );

			$wpdb->query( $wpdb->prepare( $query, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	/**
	 * Get campaign by ID.
	 *
	 * @param string $campaign_id Unique campaign ID.
	 * @return object|null
	 */
	public static function get_campaign( string $campaign_id ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTY_CAMPAIGNS );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE campaign_id = %s", $campaign_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get recent campaigns.
	 *
	 * @param int $limit Number of campaigns.
	 * @return array<object>
	 */
	public static function get_recent_campaigns( int $limit = 20 ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTY_CAMPAIGNS );
		$limit = max( 1, min( 100, $limit ) );
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $results ) ? $results : [];
	}

	/**
	 * Get all batch jobs for a campaign.
	 *
	 * @param string $campaign_id Unique campaign ID.
	 * @return array<object>
	 */
	public static function get_campaign_batches( string $campaign_id ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOBS );
		$like  = sanitize_key( $campaign_id ) . '_b%';
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE job_id LIKE %s ORDER BY current_batch ASC", $like ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return is_array( $results ) ? $results : [];
	}

	/**
	 * Recalculate campaign metrics from its batches.
	 *
	 * @param string $campaign_id Unique campaign ID.
	 * @return void
	 */
	public static function update_campaign_stats( string $campaign_id ): void {
		global $wpdb;
		$batches = self::get_campaign_batches( $campaign_id );
		if ( empty( $batches ) ) {
			return;
		}

		$total_batches     = count( $batches );
		$completed_batches = 0;
		$generated_pages   = 0;
		$failed_pages      = 0;
		$is_running        = false;
		$all_completed     = true;

		foreach ( $batches as $batch ) {
			$generated_pages += (int) $batch->completed_items;
			$failed_pages    += (int) $batch->failed_items;

			if ( 'completed' === $batch->status ) {
				$completed_batches++;
			} else {
				$all_completed = false;
			}

			if ( 'processing' === $batch->status || 'running' === $batch->status ) {
				$is_running = true;
			}
		}

		$status = 'ready';
		if ( $is_running ) {
			$status = 'running';
		} elseif ( $all_completed && $total_batches > 0 ) {
			$status = 'completed';
		} elseif ( $completed_batches > 0 || $generated_pages > 0 ) {
			$status = 'in_progress';
		}

		$campaign_table = Schema::get_table_name( Schema::TABLE_COUNTY_CAMPAIGNS );
		$wpdb->update(
			$campaign_table,
			[
				'completed_batches' => $completed_batches,
				'generated_pages'   => $generated_pages,
				'failed_pages'      => $failed_pages,
				'status'            => $status,
				'updated_at'        => current_time( 'mysql' ),
			],
			[ 'campaign_id' => $campaign_id ],
			[ '%d', '%d', '%d', '%s', '%s' ],
			[ '%s' ]
		);
	}

	/**
	 * Delete a campaign and its bulk jobs.
	 *
	 * @param string $campaign_id Unique campaign ID.
	 * @return bool
	 */
	public static function delete_campaign( string $campaign_id ): bool {
		global $wpdb;
		$campaign_table = Schema::get_table_name( Schema::TABLE_COUNTY_CAMPAIGNS );
		$bulk_jobs      = Schema::get_table_name( Schema::TABLE_BULK_JOBS );
		$bulk_items     = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		// Delete campaign record.
		$wpdb->delete( $campaign_table, [ 'campaign_id' => $campaign_id ], [ '%s' ] );

		// Delete batch jobs and items.
		$like = sanitize_key( $campaign_id ) . '_b%';
		$jobs = $wpdb->get_col( $wpdb->prepare( "SELECT job_id FROM {$bulk_jobs} WHERE job_id LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $jobs ) ) {
			foreach ( $jobs as $job_id ) {
				$wpdb->delete( $bulk_items, [ 'job_id' => $job_id ], [ '%s' ] );
				$wpdb->delete( $bulk_jobs, [ 'job_id' => $job_id ], [ '%s' ] );
			}
		}

		return true;
	}
}
