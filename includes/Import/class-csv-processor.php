<?php
/**
 * CSV Batch Processor & Data Ingestion Engine.
 *
 * @package SeoCityPages\Import
 */

namespace SeoCityPages\Import;

use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Import_Job_Repository;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Processor Class.
 */
class CSV_Processor {

	/**
	 * In-memory entity lookup caches for the current process/batch.
	 */
	private static array $cache_countries = [];
	private static array $cache_states    = [];
	private static array $cache_cities    = [];
	private static array $cache_services  = [];

	/**
	 * Reset in-memory caches.
	 *
	 * @return void
	 */
	public static function reset_caches(): void {
		self::$cache_countries = [];
		self::$cache_states    = [];
		self::$cache_cities    = [];
		self::$cache_services  = [];
	}

	/**
	 * Process a batch of validated rows.
	 *
	 * @param array  $rows       Array of validated row definitions.
	 * @param string $job_id     Unique import job UUID.
	 * @param array  $job_totals Accumulated statistics.
	 * @return array Batch execution results.
	 */
	public static function process_batch( array $rows, string $job_id = '', array $job_totals = [] ): array {
		$batch_stats = [
			'processed'             => 0,
			'created_countries'     => 0,
			'created_states'        => 0,
			'created_cities'        => 0,
			'created_services'      => 0,
			'created_combinations'  => 0,
			'existing_combinations' => 0,
			'failed'                => 0,
			'failed_rows'           => [],
		];

		foreach ( $rows as $row ) {
			$batch_stats['processed']++;

			$country_name = $row['country'];
			$state_name   = $row['state'];
			$city_name    = $row['city'];
			$service_name = $row['service'];
			$row_index    = $row['row_index'] ?? 0;

			try {
				// 1. Resolve Country.
				$country_key = CSV_Normalizer::normalize_key( $country_name );
				if ( ! isset( self::$cache_countries[ $country_key ] ) ) {
					$country = Country_Repository::get_by_name( $country_name );
					if ( ! $country ) {
						$c_id = Country_Repository::create( [
							'name'   => $country_name,
							'status' => 'active',
						] );
						if ( is_wp_error( $c_id ) ) {
							throw new \Exception( $c_id->get_error_message() );
						}
						self::$cache_countries[ $country_key ] = (int) $c_id;
						$batch_stats['created_countries']++;
					} else {
						self::$cache_countries[ $country_key ] = (int) $country->id;
					}
				}
				$country_id = self::$cache_countries[ $country_key ];

				// 2. Resolve State.
				$state_key = $country_id . '|' . CSV_Normalizer::normalize_key( $state_name );
				if ( ! isset( self::$cache_states[ $state_key ] ) ) {
					$state = State_Repository::get_by_name( $country_id, $state_name );
					if ( ! $state ) {
						$s_id = State_Repository::create( [
							'country_id' => $country_id,
							'name'       => $state_name,
							'status'     => 'active',
						] );
						if ( is_wp_error( $s_id ) ) {
							throw new \Exception( $s_id->get_error_message() );
						}
						self::$cache_states[ $state_key ] = (int) $s_id;
						$batch_stats['created_states']++;
					} else {
						self::$cache_states[ $state_key ] = (int) $state->id;
					}
				}
				$state_id = self::$cache_states[ $state_key ];

				// 3. Resolve City.
				$city_key = $country_id . '|' . $state_id . '|' . CSV_Normalizer::normalize_key( $city_name );
				if ( ! isset( self::$cache_cities[ $city_key ] ) ) {
					$city = City_Repository::get_by_name( $country_id, $state_id, $city_name );
					if ( ! $city ) {
						$ci_id = City_Repository::create( [
							'country_id' => $country_id,
							'state_id'   => $state_id,
							'name'       => $city_name,
							'status'     => 'active',
						] );
						if ( is_wp_error( $ci_id ) ) {
							throw new \Exception( $ci_id->get_error_message() );
						}
						self::$cache_cities[ $city_key ] = (int) $ci_id;
						$batch_stats['created_cities']++;
					} else {
						self::$cache_cities[ $city_key ] = (int) $city->id;
					}
				}
				$city_id = self::$cache_cities[ $city_key ];

				// 4. Resolve Service.
				$service_key = CSV_Normalizer::normalize_key( $service_name );
				if ( ! isset( self::$cache_services[ $service_key ] ) ) {
					$service = Service_Repository::get_by_name( $service_name );
					if ( ! $service ) {
						$sv_id = Service_Repository::create( [
							'name'   => $service_name,
							'status' => 'active',
						] );
						if ( is_wp_error( $sv_id ) ) {
							throw new \Exception( $sv_id->get_error_message() );
						}
						self::$cache_services[ $service_key ] = (int) $sv_id;
						$batch_stats['created_services']++;
					} else {
						self::$cache_services[ $service_key ] = (int) $service->id;
					}
				}
				$service_id = self::$cache_services[ $service_key ];

				// 5. Register Logical City Page Combination with Keywords.
				$primary_kw = ! empty( $row['primary_keyword'] ) ? CSV_Normalizer::canonical_name( (string) $row['primary_keyword'] ) : ( ! empty( $row['keyword'] ) ? CSV_Normalizer::canonical_name( (string) $row['keyword'] ) : '' );
				$target_kws = ! empty( $row['keywords'] ) ? sanitize_textarea_field( (string) $row['keywords'] ) : ( ! empty( $row['target_keywords'] ) ? sanitize_textarea_field( (string) $row['target_keywords'] ) : ( ! empty( $row['secondary_keywords'] ) ? sanitize_textarea_field( (string) $row['secondary_keywords'] ) : '' ) );

				$page_combo = City_Page_Repository::find_or_create( [
					'country_id'      => $country_id,
					'state_id'        => $state_id,
					'city_id'         => $city_id,
					'service_id'      => $service_id,
					'import_job_id'   => $job_id,
					'primary_keyword' => $primary_kw,
					'target_keywords' => $target_kws,
				] );

				if ( ! empty( $page_combo['created'] ) ) {
					$batch_stats['created_combinations']++;
				} else {
					$batch_stats['existing_combinations']++;
					// If page already exists and new keywords were provided in CSV, update the keywords.
					if ( ! empty( $page_combo['id'] ) && ( ! empty( $primary_kw ) || ! empty( $target_kws ) ) ) {
						$update_payload = [];
						if ( ! empty( $primary_kw ) ) {
							$update_payload['primary_keyword'] = $primary_kw;
						}
						if ( ! empty( $target_kws ) ) {
							$update_payload['target_keywords'] = $target_kws;
						}
						City_Page_Repository::update( (int) $page_combo['id'], $update_payload );
					}
				}

			} catch ( \Exception $e ) {
				$batch_stats['failed']++;
				$batch_stats['failed_rows'][] = [
					'row_index' => $row_index,
					'country'   => $country_name,
					'state'     => $state_name,
					'city'      => $city_name,
					'service'   => $service_name,
					'reason'    => $e->getMessage(),
				];
				Logger::error(
					sprintf( 'Import failure on row %d: %s', $row_index, $e->getMessage() ),
					[ 'row' => $row ]
				);
			}
		}

		// Update Job tracking if job_id was provided.
		if ( ! empty( $job_id ) ) {
			$job = Import_Job_Repository::get_by_job_id( $job_id );
			if ( $job ) {
				$new_processed = (int) $job->processed_rows + $batch_stats['processed'];
				$new_c_count   = (int) $job->created_countries + $batch_stats['created_countries'];
				$new_s_count   = (int) $job->created_states + $batch_stats['created_states'];
				$new_ci_count  = (int) $job->created_cities + $batch_stats['created_cities'];
				$new_sv_count  = (int) $job->created_services + $batch_stats['created_services'];
				$new_combo     = (int) $job->created_combinations + $batch_stats['created_combinations'];
				$new_exist     = (int) $job->existing_combinations + $batch_stats['existing_combinations'];
				$new_failed    = (int) $job->failed_rows + $batch_stats['failed'];

				$existing_errors = ! empty( $job->errors_summary ) ? json_decode( $job->errors_summary, true ) : [];
				if ( ! is_array( $existing_errors ) ) {
					$existing_errors = [];
				}
				$merged_errors = array_merge( $existing_errors, $batch_stats['failed_rows'] );

				Import_Job_Repository::update_progress( $job_id, [
					'processed_rows'        => $new_processed,
					'created_countries'     => $new_c_count,
					'created_states'        => $new_s_count,
					'created_cities'        => $new_ci_count,
					'created_services'      => $new_sv_count,
					'created_combinations'  => $new_combo,
					'existing_combinations' => $new_exist,
					'failed_rows'           => $new_failed,
					'errors_summary'        => $merged_errors,
					'status'                => 'processing',
				] );
			}
		}

		return $batch_stats;
	}
}
