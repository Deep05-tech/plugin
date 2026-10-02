<?php
/**
 * County Batch Generator Runner & Asynchronous Worker.
 *
 * @package SeoCityPages\Jobs
 */

namespace SeoCityPages\Jobs;

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\County_Campaign_Repository;
use SeoCityPages\AI\AI_Manager;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * County Batch Runner Class.
 */
class County_Batch_Runner {

	/**
	 * Process a chunk of items in a 100-page batch.
	 *
	 * @param string $job_id     Batch Job ID.
	 * @param int    $chunk_size Number of county pages to process per request (default: 5).
	 * @return array
	 */
	public static function process_chunk( string $job_id, int $chunk_size = 5 ): array {
		global $wpdb;

		$bulk_jobs_table  = Schema::get_table_name( Schema::TABLE_BULK_JOBS );
		$bulk_items_table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bulk_jobs_table} WHERE job_id = %s", $job_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $job ) {
			return [
				'success' => false,
				'message' => sprintf( __( 'Batch job %s not found.', 'seo-city-pages' ), $job_id ),
			];
		}

		// Transition job from pending/ready to running.
		if ( 'pending' === $job->status || 'ready' === $job->status || 'paused' === $job->status ) {
			$wpdb->update(
				$bulk_jobs_table,
				[
					'status'     => 'running',
					'started_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'job_id' => $job_id ],
				[ '%s', '%s', '%s' ],
				[ '%s' ]
			);
		}

		// Fetch pending items for this batch.
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$bulk_items_table} WHERE job_id = %s AND status = 'pending' ORDER BY id ASC LIMIT %d",
				$job_id,
				$chunk_size
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$processed_count = 0;
		$error_messages  = [];
		$job_params      = ! empty( $job->job_params ) ? json_decode( $job->job_params, true ) : [];
		$campaign_id     = $job_params['campaign_id'] ?? '';

		if ( ! empty( $items ) ) {
			// Ensure US Country exists.
			$us_country_id = self::get_or_create_country();

			foreach ( $items as $item ) {
				$payload = ! empty( $item->item_payload ) ? json_decode( $item->item_payload, true ) : [];
				$state_code  = sanitize_text_field( $payload['state_code'] ?? 'CA' );
				$state_name  = sanitize_text_field( $payload['state_name'] ?? 'California' );
				$county_name = sanitize_text_field( $payload['county_name'] ?? '' );
				$county_slug = sanitize_title( $payload['county_slug'] ?? $county_name );
				$service_id  = (int) ( $payload['service_id'] ?? 0 );
				$topic       = sanitize_text_field( $payload['topic'] ?? '' );

				if ( empty( $county_name ) || empty( $topic ) ) {
					$wpdb->update(
						$bulk_items_table,
						[
							'status'        => 'failed',
							'error_message' => 'Invalid county or topic payload.',
							'completed_at'  => current_time( 'mysql' ),
						],
						[ 'id' => $item->id ],
						[ '%s', '%s', '%s' ],
						[ '%d' ]
					);
					continue;
				}

				try {
					// 1. Get or create State.
					$state_id = self::get_or_create_state( $us_country_id, $state_name, $state_code );

					// 2. Get or create City/County.
					$city_id = self::get_or_create_city( $us_country_id, $state_id, $county_name, $county_slug );

					// 3. Get or create Service.
					if ( $service_id <= 0 ) {
						$service_id = self::get_or_create_service( $topic );
					}

					// 4. Get or create City Page.
					$page_id = self::get_or_create_page( $us_country_id, $state_id, $city_id, $service_id, $campaign_id );

					if ( ! $page_id ) {
						throw new \Exception( 'Failed to create or retrieve county city page.' );
					}

					$version_id = null;
					$error_msg  = null;

					// 5. Generate AI Content if AI is enabled.
					if ( AI_Manager::instance()->is_enabled() ) {
						$ai_result = AI_Manager::instance()->generate_for_page( $page_id );
						if ( $ai_result->is_success() ) {
							$version_id = $ai_result->get_version_id();
						} else {
							$error_msg = $ai_result->get_error_message();
						}
					}

					$wpdb->update(
						$bulk_items_table,
						[
							'entity_id'     => $page_id,
							'status'        => 'completed',
							'error_message' => $error_msg,
							'item_result'   => wp_json_encode( [
								'page_id'    => $page_id,
								'status'     => 'completed',
								'version_id' => $version_id,
							] ),
							'completed_at'  => current_time( 'mysql' ),
						],
						[ 'id' => $item->id ],
						[ '%d', '%s', '%s', '%s', '%s' ],
						[ '%d' ]
					);

					$processed_count++;

				} catch ( \Throwable $e ) {
					$error_messages[] = $e->getMessage();
					$wpdb->update(
						$bulk_items_table,
						[
							'status'        => 'failed',
							'error_message' => $e->getMessage(),
							'completed_at'  => current_time( 'mysql' ),
						],
						[ 'id' => $item->id ],
						[ '%s', '%s', '%s' ],
						[ '%d' ]
					);
				}
			}
		}

		// Recalculate batch statistics.
		$total_items     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bulk_items_table} WHERE job_id = %s", $job_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$completed_items = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bulk_items_table} WHERE job_id = %s AND status = 'completed'", $job_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$failed_items    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bulk_items_table} WHERE job_id = %s AND status = 'failed'", $job_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pending_items   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bulk_items_table} WHERE job_id = %s AND status = 'pending'", $job_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$is_done = ( 0 === $pending_items );
		$batch_status = $is_done ? ( $failed_items > 0 && 0 === $completed_items ? 'failed' : 'completed' ) : 'running';

		$wpdb->update(
			$bulk_jobs_table,
			[
				'total_items'     => $total_items,
				'completed_items' => $completed_items,
				'failed_items'    => $failed_items,
				'pending_items'   => $pending_items,
				'status'          => $batch_status,
				'completed_at'    => $is_done ? current_time( 'mysql' ) : null,
				'updated_at'      => current_time( 'mysql' ),
			],
			[ 'job_id' => $job_id ],
			[ '%d', '%d', '%d', '%d', '%s', '%s', '%s' ],
			[ '%s' ]
		);

		// Update Campaign aggregates.
		if ( ! empty( $campaign_id ) ) {
			County_Campaign_Repository::update_campaign_stats( $campaign_id );
		}

		$percent = $total_items > 0 ? (int) round( ( ( $completed_items + $failed_items ) / $total_items ) * 100 ) : 100;

		return [
			'success'         => true,
			'job_id'          => $job_id,
			'processed_chunk' => $processed_count,
			'total_items'     => $total_items,
			'completed_items' => $completed_items,
			'failed_items'    => $failed_items,
			'pending_items'   => $pending_items,
			'percent'         => $percent,
			'is_done'         => $is_done,
			'status'          => $batch_status,
			'errors'          => $error_messages,
		];
	}

	/**
	 * Reset failed items to pending for retry.
	 *
	 * @param string $job_id Batch Job ID.
	 * @return bool
	 */
	public static function retry_failed( string $job_id ): bool {
		global $wpdb;
		$bulk_jobs_table  = Schema::get_table_name( Schema::TABLE_BULK_JOBS );
		$bulk_items_table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$bulk_items_table} SET status = 'pending', error_message = NULL, completed_at = NULL WHERE job_id = %s AND status = 'failed'",
				$job_id
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Recalculate.
		$total_items     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bulk_items_table} WHERE job_id = %s", $job_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$completed_items = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bulk_items_table} WHERE job_id = %s AND status = 'completed'", $job_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pending_items   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bulk_items_table} WHERE job_id = %s AND status = 'pending'", $job_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$wpdb->update(
			$bulk_jobs_table,
			[
				'failed_items'  => 0,
				'pending_items' => $pending_items,
				'status'        => 'pending',
				'updated_at'    => current_time( 'mysql' ),
			],
			[ 'job_id' => $job_id ],
			[ '%d', '%d', '%s', '%s' ],
			[ '%s' ]
		);

		return true;
	}

	/**
	 * Bulk publish all generated draft pages in a batch.
	 *
	 * @param string $job_id Batch Job ID.
	 * @return array{published: int, total: int}
	 */
	public static function bulk_publish_batch( string $job_id ): array {
		global $wpdb;
		$bulk_items_table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );
		$pages_table      = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$page_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT entity_id FROM {$bulk_items_table} WHERE job_id = %s AND entity_id > 0 AND status = 'completed'",
				$job_id
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$published_count = 0;
		if ( ! empty( $page_ids ) ) {
			foreach ( $page_ids as $page_id ) {
				$updated = City_Page_Repository::update( (int) $page_id, [
					'status'       => City_Page_Repository::STATUS_PUBLISHED,
					'is_published' => 1,
					'published_at' => current_time( 'mysql' ),
				] );
				if ( ! is_wp_error( $updated ) && $updated ) {
					// Synchronize page to WordPress core wp_posts table so permalinks & theme templates render natively.
					\SeoCityPages\Integration\Post_Sync::sync_page( (int) $page_id );
					$published_count++;
				}
			}
		}

		return [
			'published' => $published_count,
			'total'     => count( $page_ids ),
		];
	}

	/**
	 * Helper: Get or create United States country record.
	 *
	 * @return int Country ID.
	 */
	private static function get_or_create_country(): int {
		$existing = Country_Repository::get_by_slug( 'united-states' );
		if ( ! $existing ) {
			$existing = Country_Repository::get_by_name( 'United States' );
		}
		if ( $existing ) {
			return (int) $existing->id;
		}

		$created = Country_Repository::create( [
			'name'   => 'United States',
			'code'   => 'US',
			'slug'   => 'united-states',
			'status' => 'active',
		] );

		return is_wp_error( $created ) ? 0 : (int) $created;
	}

	/**
	 * Helper: Get or create State record.
	 *
	 * @param int    $country_id Country ID.
	 * @param string $state_name State name.
	 * @param string $state_code 2-letter state code.
	 * @return int State ID.
	 */
	private static function get_or_create_state( int $country_id, string $state_name, string $state_code ): int {
		$slug     = sanitize_title( $state_name );
		$existing = State_Repository::get_by_slug( $country_id, $slug );
		if ( ! $existing ) {
			$existing = State_Repository::get_by_name( $country_id, $state_name );
		}
		if ( $existing ) {
			return (int) $existing->id;
		}

		$created = State_Repository::create( [
			'country_id' => $country_id,
			'name'       => $state_name,
			'code'       => $state_code,
			'slug'       => $slug,
			'status'     => 'active',
		] );

		return is_wp_error( $created ) ? 0 : (int) $created;
	}

	/**
	 * Helper: Get or create City/County record.
	 *
	 * @param int    $country_id  Country ID.
	 * @param int    $state_id    State ID.
	 * @param string $county_name County name.
	 * @param string $county_slug County slug.
	 * @return int City ID.
	 */
	private static function get_or_create_city( int $country_id, int $state_id, string $county_name, string $county_slug ): int {
		$existing = City_Repository::get_by_slug( $state_id, $county_slug );
		if ( ! $existing ) {
			$existing = City_Repository::get_by_name( $country_id, $state_id, $county_name );
		}
		if ( $existing ) {
			return (int) $existing->id;
		}

		$created = City_Repository::create( [
			'country_id' => $country_id,
			'state_id'   => $state_id,
			'name'       => $county_name,
			'slug'       => $county_slug,
			'status'     => 'active',
		] );

		return is_wp_error( $created ) ? 0 : (int) $created;
	}

	/**
	 * Helper: Get or create Service record.
	 *
	 * @param string $topic Service topic name.
	 * @return int Service ID.
	 */
	private static function get_or_create_service( string $topic ): int {
		$slug     = sanitize_title( $topic );
		$existing = Service_Repository::get_by_slug( $slug );
		if ( ! $existing ) {
			$existing = Service_Repository::get_by_name( $topic );
		}
		if ( $existing ) {
			return (int) $existing->id;
		}

		$created = Service_Repository::create( [
			'name'        => $topic,
			'slug'        => $slug,
			'description' => sprintf( __( 'Automated service for %s', 'seo-city-pages' ), $topic ),
			'status'      => 'active',
		] );

		return is_wp_error( $created ) ? 0 : (int) $created;
	}

	/**
	 * Helper: Get or create City Page record.
	 *
	 * @param int    $country_id  Country ID.
	 * @param int    $state_id    State ID.
	 * @param int    $city_id     City ID.
	 * @param int    $service_id  Service ID.
	 * @param string $campaign_id Campaign ID.
	 * @return int Page ID.
	 */
	private static function get_or_create_page( int $country_id, int $state_id, int $city_id, int $service_id, string $campaign_id ): int {
		$existing = City_Page_Repository::get_by_combination( $country_id, $state_id, $city_id, $service_id );
		if ( $existing ) {
			return (int) $existing->id;
		}

		$created = City_Page_Repository::create( [
			'country_id'        => $country_id,
			'state_id'          => $state_id,
			'city_id'           => $city_id,
			'service_id'        => $service_id,
			'status'            => 'draft',
			'generation_status' => 'not_generated',
			'import_job_id'     => $campaign_id,
		] );

		return is_wp_error( $created ) ? 0 : (int) $created;
	}
}
