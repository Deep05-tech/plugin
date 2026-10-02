<?php
/**
 * CSV File, Structure, and Data Quality Validator.
 *
 * @package SeoCityPages\Import
 */

namespace SeoCityPages\Import;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Validator Class.
 */
class CSV_Validator {

	/**
	 * Required column keys (normalized).
	 */
	public const REQUIRED_COLUMNS = [ 'country', 'state', 'city', 'service' ];

	/**
	 * Recognized optional column keys.
	 */
	public const OPTIONAL_COLUMNS = [
		'country_code',
		'state_code',
		'city_slug',
		'service_slug',
		'page_status',
		'keywords',
		'target_keywords',
		'primary_keyword',
		'secondary_keywords',
		'keyword',
	];

	/**
	 * Max allowed field lengths.
	 */
	public const MAX_LENGTHS = [
		'country'      => 100,
		'state'        => 100,
		'city'         => 100,
		'service'      => 150,
		'country_code' => 10,
		'state_code'   => 20,
		'city_slug'    => 100,
		'service_slug' => 150,
		'page_status'  => 20,
	];

	/**
	 * Suspicious values that trigger Data Quality Warnings (not hard failures).
	 */
	public const SUSPICIOUS_VALUES = [
		'unassigned',
		'unknown',
		'n/a',
		'na',
		'out of gujarat',
		'out of state',
		'other',
		'none',
		'null',
		'test',
		'tbd',
		'placeholder',
	];

	/**
	 * Neutralize potential CSV formula injection (=, +, -, @).
	 *
	 * @param string $value Input string.
	 * @return string Safe string.
	 */
	public static function sanitize_formula_injection( string $value ): string {
		$trimmed = trim( $value );
		if ( empty( $trimmed ) ) {
			return $trimmed;
		}

		$first_char = substr( $trimmed, 0, 1 );
		if ( in_array( $first_char, [ '=', '+', '-', '@' ], true ) ) {
			// Prefix with single quote to deactivate spreadsheet formula execution.
			return "'" . $trimmed;
		}

		return $trimmed;
	}

	/**
	 * Check if a location value is suspicious.
	 *
	 * @param string $val Value.
	 * @return bool
	 */
	public static function is_suspicious_value( string $val ): bool {
		$normalized = strtolower( trim( $val ) );
		return in_array( $normalized, self::SUSPICIOUS_VALUES, true );
	}

	/**
	 * Validate and parse a CSV file.
	 *
	 * @param string $file_path Path to uploaded CSV file.
	 * @param bool   $preview   Whether to run in preview mode (computes DB existing/conflict stats).
	 * @return array Validation report.
	 */
	public static function validate_file( string $file_path, bool $preview = false ): array {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return [
				'success' => false,
				'error'   => __( 'The uploaded CSV file could not be read.', 'seo-city-pages' ),
			];
		}

		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return [
				'success' => false,
				'error'   => __( 'Unable to open CSV file stream.', 'seo-city-pages' ),
			];
		}

		// 1. Parse header row.
		$raw_header = fgetcsv( $handle, 0, ',', '"', '\\' );
		if ( false === $raw_header || empty( $raw_header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return [
				'success' => false,
				'error'   => __( 'The CSV file is empty or missing headers.', 'seo-city-pages' ),
			];
		}

		$header_analysis = self::analyze_headers( $raw_header );
		$column_map      = $header_analysis['column_map'];
		$missing         = $header_analysis['missing_required'];

		if ( ! empty( $missing ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return [
				'success' => false,
				'error'   => sprintf(
					/* translators: %s: Comma-separated list of missing columns */
					__( 'Missing required columns: %s. Expected columns: country, state, city, service.', 'seo-city-pages' ),
					implode( ', ', $missing )
				),
				'recognized_columns' => $header_analysis['recognized'],
				'unknown_columns'    => $header_analysis['unknown'],
				'missing_columns'    => $missing,
			];
		}

		$total_rows     = 0;
		$valid_rows     = [];
		$invalid_rows   = [];
		$duplicate_rows = [];
		$warnings_list  = [];
		$seen_keys      = [];
		$expected_cols  = count( $raw_header );

		$existing_count = 0;
		$new_count      = 0;

		$row_index = 1; // Row 1 was header.

		while ( ( $row = fgetcsv( $handle, 0, ',', '"', '\\' ) ) !== false ) {
			$row_index++;

			// Ignore completely blank lines.
			if ( 1 === count( $row ) && ( null === $row[0] || '' === trim( (string) $row[0] ) ) ) {
				continue;
			}

			$total_rows++;

			// Check column count consistency.
			if ( count( $row ) !== $expected_cols ) {
				$invalid_rows[] = [
					'row_index' => $row_index,
					'country'   => $row[ $column_map['country'] ] ?? '',
					'state'     => $row[ $column_map['state'] ] ?? '',
					'city'      => $row[ $column_map['city'] ] ?? '',
					'service'   => $row[ $column_map['service'] ] ?? '',
					'status'    => 'invalid',
					'reason'    => sprintf(
						/* translators: 1: Row col count, 2: Header col count */
						__( 'Column count mismatch: row has %1$d columns, expected %2$d.', 'seo-city-pages' ),
						count( $row ),
						$expected_cols
					),
				];
				continue;
			}

			// Extract & sanitize formula injection.
			$raw_country = self::sanitize_formula_injection( $row[ $column_map['country'] ] ?? '' );
			$raw_state   = self::sanitize_formula_injection( $row[ $column_map['state'] ] ?? '' );
			$raw_city    = self::sanitize_formula_injection( $row[ $column_map['city'] ] ?? '' );
			$raw_service = self::sanitize_formula_injection( $row[ $column_map['service'] ] ?? '' );

			// UTF-8 Validation.
			if (
				! mb_check_encoding( $raw_country, 'UTF-8' ) ||
				! mb_check_encoding( $raw_state, 'UTF-8' ) ||
				! mb_check_encoding( $raw_city, 'UTF-8' ) ||
				! mb_check_encoding( $raw_service, 'UTF-8' )
			) {
				$invalid_rows[] = [
					'row_index' => $row_index,
					'country'   => $raw_country,
					'state'     => $raw_state,
					'city'      => $raw_city,
					'service'   => $raw_service,
					'status'    => 'invalid',
					'reason'    => __( 'Row contains invalid character encoding (non UTF-8).', 'seo-city-pages' ),
				];
				continue;
			}

			$country = CSV_Normalizer::canonical_name( $raw_country );
			$state   = CSV_Normalizer::canonical_name( $raw_state );
			$city    = CSV_Normalizer::canonical_name( $raw_city );
			$service = CSV_Normalizer::canonical_name( $raw_service );

			// Check for empty required fields.
			$empty_fields = [];
			if ( empty( $country ) ) {
				$empty_fields[] = 'country';
			}
			if ( empty( $state ) ) {
				$empty_fields[] = 'state';
			}
			if ( empty( $city ) ) {
				$empty_fields[] = 'city';
			}
			if ( empty( $service ) ) {
				$empty_fields[] = 'service';
			}

			if ( ! empty( $empty_fields ) ) {
				$invalid_rows[] = [
					'row_index' => $row_index,
					'country'   => $country,
					'state'     => $state,
					'city'      => $city,
					'service'   => $service,
					'status'    => 'invalid',
					'reason'    => sprintf(
						/* translators: %s: Missing field names */
						__( 'Empty required field(s): %s', 'seo-city-pages' ),
						implode( ', ', $empty_fields )
					),
				];
				continue;
			}

			// In-CSV duplicate detection.
			$signature = CSV_Normalizer::row_signature( $country, $state, $city, $service );

			if ( isset( $seen_keys[ $signature ] ) ) {
				$duplicate_rows[] = [
					'row_index'    => $row_index,
					'original_row' => $seen_keys[ $signature ],
					'country'      => $country,
					'state'        => $state,
					'city'         => $city,
					'service'      => $service,
					'status'       => 'duplicate_in_csv',
					'reason'       => sprintf(
						/* translators: %d: Original row number */
						__( 'Duplicate row in CSV: identical to row #%d.', 'seo-city-pages' ),
						$seen_keys[ $signature ]
					),
				];
				continue;
			}

			$seen_keys[ $signature ] = $row_index;

			// Check for Data Quality Warnings (suspicious values).
			$row_warnings = [];
			if ( self::is_suspicious_value( $country ) ) {
				$row_warnings[] = sprintf( 'Suspicious country value: "%s"', $country );
			}
			if ( self::is_suspicious_value( $state ) ) {
				$row_warnings[] = sprintf( 'Suspicious state value: "%s"', $state );
			}
			if ( self::is_suspicious_value( $city ) ) {
				$row_warnings[] = sprintf( 'Suspicious city value: "%s"', $city );
			}
			if ( self::is_suspicious_value( $service ) ) {
				$row_warnings[] = sprintf( 'Suspicious service value: "%s"', $service );
			}

			// Extract optional columns if mapped.
			$country_code = isset( $column_map['country_code'] ) ? self::sanitize_formula_injection( $row[ $column_map['country_code'] ] ?? '' ) : '';
			$state_code   = isset( $column_map['state_code'] ) ? self::sanitize_formula_injection( $row[ $column_map['state_code'] ] ?? '' ) : '';
			$city_slug    = isset( $column_map['city_slug'] ) ? sanitize_title( $row[ $column_map['city_slug'] ] ?? '' ) : '';
			$service_slug = isset( $column_map['service_slug'] ) ? sanitize_title( $row[ $column_map['service_slug'] ] ?? '' ) : '';
			$page_status  = isset( $column_map['page_status'] ) ? sanitize_text_field( $row[ $column_map['page_status'] ] ?? 'draft' ) : 'draft';

			$valid_entry = [
				'row_index'    => $row_index,
				'country'      => $country,
				'state'        => $state,
				'city'         => $city,
				'service'      => $service,
				'country_code' => $country_code,
				'state_code'   => $state_code,
				'city_slug'    => $city_slug,
				'service_slug' => $service_slug,
				'page_status'  => $page_status,
				'signature'    => $signature,
				'warnings'     => $row_warnings,
			];

			if ( ! empty( $row_warnings ) ) {
				$warnings_list[] = [
					'row_index' => $row_index,
					'values'    => "{$city}, {$state}, {$country} - {$service}",
					'warnings'  => $row_warnings,
				];
			}

			// In preview mode, check if page combination exists in DB.
			if ( $preview ) {
				$c_obj = Country_Repository::get_by_name( $country );
				$s_obj = ( $c_obj ) ? State_Repository::get_by_name( (int) $c_obj->id, $state ) : null;
				$ci_obj = ( $c_obj && $s_obj ) ? City_Repository::get_by_name( (int) $c_obj->id, (int) $s_obj->id, $city ) : null;
				$sv_obj = Service_Repository::get_by_name( $service );

				if ( $c_obj && $s_obj && $ci_obj && $sv_obj ) {
					$existing_page = City_Page_Repository::get_by_combination( (int) $c_obj->id, (int) $s_obj->id, (int) $ci_obj->id, (int) $sv_obj->id );
					if ( $existing_page ) {
						$existing_count++;
						$valid_entry['db_status'] = 'existing';
					} else {
						$new_count++;
						$valid_entry['db_status'] = 'new';
					}
				} else {
					$new_count++;
					$valid_entry['db_status'] = 'new';
				}
			}

			$valid_rows[] = $valid_entry;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return [
			'success'             => true,
			'total_rows'          => $total_rows,
			'valid_count'         => count( $valid_rows ),
			'invalid_count'       => count( $invalid_rows ),
			'duplicate_count'     => count( $duplicate_rows ),
			'warnings_count'      => count( $warnings_list ),
			'existing_records'    => $existing_count,
			'new_records'         => $preview ? $new_count : count( $valid_rows ),
			'recognized_columns'  => $header_analysis['recognized'],
			'unknown_columns'     => $header_analysis['unknown'],
			'missing_columns'     => $missing,
			'valid_rows'          => $valid_rows,
			'invalid_rows'        => $invalid_rows,
			'duplicate_rows'      => $duplicate_rows,
			'data_warnings'       => $warnings_list,
		];
	}

	/**
	 * Analyze raw CSV header columns.
	 *
	 * @param array $header Header row array.
	 * @return array Analysis report.
	 */
	public static function analyze_headers( array $header ): array {
		$column_map  = [];
		$recognized  = [];
		$unknown     = [];
		$all_known   = array_merge( self::REQUIRED_COLUMNS, self::OPTIONAL_COLUMNS );

		foreach ( $header as $index => $col_name ) {
			$norm = CSV_Normalizer::normalize_key( (string) $col_name );
			if ( in_array( $norm, $all_known, true ) ) {
				$column_map[ $norm ] = (int) $index;
				$recognized[]        = (string) $col_name;
			} else {
				$unknown[] = (string) $col_name;
			}
		}

		$missing = [];
		foreach ( self::REQUIRED_COLUMNS as $req ) {
			if ( ! isset( $column_map[ $req ] ) ) {
				$missing[] = $req;
			}
		}

		return [
			'column_map'       => $column_map,
			'recognized'       => $recognized,
			'unknown'          => $unknown,
			'missing_required' => $missing,
		];
	}

	/**
	 * Backward compatible map_header_columns.
	 *
	 * @param array $header Raw header row.
	 * @return array
	 */
	public static function map_header_columns( array $header ): array {
		$analysis = self::analyze_headers( $header );
		return $analysis['column_map'];
	}
}
