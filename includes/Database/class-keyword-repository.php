<?php
/**
 * Keyword Bank Repository.
 *
 * Manages storage, retrieval, deduplication, and service-based relevance
 * filtering for uploaded target SEO keywords.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keyword_Repository Class.
 */
class Keyword_Repository {

	/**
	 * Bulk insert a list of keywords into the keyword bank.
	 *
	 * @param array $raw_keywords Array of string keywords.
	 * @param bool  $replace      If true, purges existing keyword bank before importing.
	 * @return array ['imported' => int, 'skipped' => int, 'total' => int]
	 */
	public static function bulk_insert( array $raw_keywords, bool $replace = false ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_KEYWORD_BANK );

		if ( $replace ) {
			self::clear_all();
		}

		$stats = [
			'imported' => 0,
			'skipped'  => 0,
			'total'    => count( $raw_keywords ),
		];

		$now = current_time( 'mysql', true );
		$seen = [];

		foreach ( $raw_keywords as $raw ) {
			// Intelligently sanitize, extract intent, and remove noise.
			$clean = \SeoCityPages\Import\Keyword_Sanitizer::sanitize( (string) $raw );

			if ( empty( $clean ) || mb_strlen( $clean ) < 2 ) {
				$stats['skipped']++;
				continue;
			}

			// Max length safeguard.
			if ( mb_strlen( $clean ) > 250 ) {
				$clean = mb_substr( $clean, 0, 250 );
			}

			$normalized = strtolower( preg_replace( '/\s+/', ' ', $clean ) );

			if ( isset( $seen[ $normalized ] ) ) {
				$stats['skipped']++;
				continue;
			}
			$seen[ $normalized ] = true;

			// Check if already in database.
			$exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table} WHERE normalized_keyword = %s LIMIT 1", $normalized )
			);

			if ( $exists ) {
				$stats['skipped']++;
				continue;
			}

			$inserted = $wpdb->insert(
				$table,
				[
					'keyword'            => $clean,
					'normalized_keyword' => $normalized,
					'created_at'         => $now,
				],
				[ '%s', '%s', '%s' ]
			);

			if ( $inserted ) {
				$stats['imported']++;
			} else {
				$stats['skipped']++;
			}
		}

		Logger::info(
			sprintf( 'Keyword Bank bulk import: %d imported, %d skipped out of %d', $stats['imported'], $stats['skipped'], $stats['total'] ),
			$stats
		);

		return $stats;
	}

	/**
	 * Retrieve paginated keywords with optional search filter.
	 *
	 * @param int    $limit  Limit.
	 * @param int    $offset Offset.
	 * @param string $search Search query.
	 * @return object[] Array of keyword records.
	 */
	public static function get_all( int $limit = 100, int $offset = 0, string $search = '' ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_KEYWORD_BANK );

		if ( ! empty( $search ) ) {
			$like = '%' . $wpdb->esc_like( strtolower( trim( $search ) ) ) . '%';
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE normalized_keyword LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d",
				$like,
				$limit,
				$offset
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			);
		}

		$results = $wpdb->get_results( $sql );
		return is_array( $results ) ? $results : [];
	}

	/**
	 * Total count of keywords in the bank.
	 *
	 * @param string $search Optional search filter.
	 * @return int Total keywords count.
	 */
	public static function count( string $search = '' ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_KEYWORD_BANK );

		if ( ! empty( $search ) ) {
			$like = '%' . $wpdb->esc_like( strtolower( trim( $search ) ) ) . '%';
			$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE normalized_keyword LIKE %s", $like );
		} else {
			$sql = "SELECT COUNT(*) FROM {$table}";
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Clear all keywords from the bank.
	 *
	 * @return bool
	 */
	public static function clear_all(): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_KEYWORD_BANK );
		$wpdb->query( "DELETE FROM {$table}" );
		Logger::info( 'Keyword Bank cleared.' );
		return true;
	}

	/**
	 * Delete a single keyword by primary ID.
	 *
	 * @param int $id Keyword ID.
	 * @return bool
	 */
	public static function delete_by_id( int $id ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_KEYWORD_BANK );
		$deleted = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		return (bool) $deleted;
	}

	/**
	 * Retrieve the most relevant keywords from the bank for a given service and city.
	 *
	 * Extracts keywords that match service tokens (e.g. SEO, Marketing, Agency, Web, PPC, Ads)
	 * or falls back to top keywords from the bank if no exact term match exists.
	 *
	 * @param string $service_name Target Service.
	 * @param string $city_name    Target City.
	 * @param int    $limit        Max keywords to return.
	 * @return string[] Array of string keyword phrases.
	 */
	public static function get_relevant_for_service( string $service_name, string $city_name = '', int $limit = 10 ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_KEYWORD_BANK );

		$total = self::count();
		if ( $total === 0 ) {
			return [];
		}

		$keywords = [];

		// 1. Search for keywords containing individual tokens from the service name.
		$service_words = preg_split( '/[\s\-_,\/]+/', strtolower( trim( $service_name ) ) );
		$service_words = array_filter( $service_words, function( $w ) {
			return strlen( $w ) > 2 && ! in_array( $w, [ 'and', 'the', 'for', 'with', 'in' ], true );
		} );

		if ( ! empty( $service_words ) ) {
			$where_clauses = [];
			$params = [];
			foreach ( $service_words as $word ) {
				$where_clauses[] = 'normalized_keyword LIKE %s';
				$params[] = '%' . $wpdb->esc_like( $word ) . '%';
			}

			$where_sql = implode( ' OR ', $where_clauses );
			$sql = $wpdb->prepare(
				"SELECT keyword FROM {$table} WHERE {$where_sql} ORDER BY id ASC LIMIT %d",
				array_merge( $params, [ $limit ] )
			);

			$results = $wpdb->get_results( $sql );
			if ( ! empty( $results ) ) {
				foreach ( $results as $row ) {
					$keywords[] = $row->keyword;
				}
			}
		}

		// 2. If fewer than desired limit, fill remaining slots with general top keywords from bank.
		if ( count( $keywords ) < $limit ) {
			$remaining = $limit - count( $keywords );
			$not_in_clause = '';
			$not_params = [];
			if ( ! empty( $keywords ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $keywords ), '%s' ) );
				$not_in_clause = "WHERE keyword NOT IN ({$placeholders})";
				$not_params = $keywords;
			}

			$fallback_sql = $wpdb->prepare(
				"SELECT keyword FROM {$table} {$not_in_clause} ORDER BY id ASC LIMIT %d",
				array_merge( $not_params, [ $remaining ] )
			);

			$fallback_results = $wpdb->get_results( $fallback_sql );
			if ( ! empty( $fallback_results ) ) {
				foreach ( $fallback_results as $row ) {
					$keywords[] = $row->keyword;
				}
			}
		}

		return array_values( array_unique( $keywords ) );
	}
}
