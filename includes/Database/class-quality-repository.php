<?php
/**
 * Quality Repository.
 *
 * Handles database operations for quality evaluations, content fingerprints,
 * and false-positive dismissals.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quality_Repository Class.
 */
class Quality_Repository {

	/**
	 * Quality Status Constants.
	 */
	public const STATUS_PASS         = 'pass';
	public const STATUS_WARNING      = 'warning';
	public const STATUS_NEEDS_REVIEW = 'needs_review';
	public const STATUS_FAIL         = 'fail';

	/**
	 * Gate Mode Constants.
	 */
	public const GATE_MODE_OFF            = 'off';
	public const GATE_MODE_WARNINGS_ONLY  = 'warnings_only';
	public const GATE_MODE_BLOCK_CRITICAL = 'block_critical';

	/**
	 * Create or update a quality evaluation for a page / version.
	 *
	 * @param array $data Evaluation data.
	 * @return int Evaluation record ID.
	 */
	public static function save_evaluation( array $data ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_QUALITY_EVALUATIONS );

		$page_id    = (int) ( $data['page_id'] ?? 0 );
		$version_id = (int) ( $data['version_id'] ?? 0 );

		if ( $page_id <= 0 ) {
			return 0;
		}

		$existing_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE page_id = %d LIMIT 1",
				$page_id
			)
		);

		$record = [
			'page_id'                  => $page_id,
			'version_id'               => $version_id,
			'quality_score'            => (int) ( $data['quality_score'] ?? 0 ),
			'quality_status'           => sanitize_key( $data['quality_status'] ?? self::STATUS_NEEDS_REVIEW ),
			'completeness_score'       => (float) ( $data['completeness_score'] ?? 0.00 ),
			'thin_content_flag'        => ! empty( $data['thin_content_flag'] ) ? 1 : 0,
			'similarity_score'         => (float) ( $data['similarity_score'] ?? 0.00 ),
			'max_similar_page_id'      => (int) ( $data['max_similar_page_id'] ?? 0 ),
			'city_relevance_score'     => (float) ( $data['city_relevance_score'] ?? 0.00 ),
			'service_relevance_score'  => (float) ( $data['service_relevance_score'] ?? 0.00 ),
			'intent_overlap_flag'      => ! empty( $data['intent_overlap_flag'] ) ? 1 : 0,
			'overlap_page_id'          => (int) ( $data['overlap_page_id'] ?? 0 ),
			'placeholder_issues_count' => (int) ( $data['placeholder_issues_count'] ?? 0 ),
			'unsupported_claims_count' => (int) ( $data['unsupported_claims_count'] ?? 0 ),
			'cliche_count'             => (int) ( $data['cliche_count'] ?? 0 ),
			'keyword_stuffing_flag'    => ! empty( $data['keyword_stuffing_flag'] ) ? 1 : 0,
			'internal_link_count'      => (int) ( $data['internal_link_count'] ?? 0 ),
			'is_orphan'                => ! empty( $data['is_orphan'] ) ? 1 : 0,
			'missing_seo_metadata'     => ! empty( $data['missing_seo_metadata'] ) ? 1 : 0,
			'issues_json'              => is_array( $data['issues_json'] ?? null ) ? wp_json_encode( $data['issues_json'] ) : (string) ( $data['issues_json'] ?? '[]' ),
			'suggestions_json'         => is_array( $data['suggestions_json'] ?? null ) ? wp_json_encode( $data['suggestions_json'] ) : (string) ( $data['suggestions_json'] ?? '[]' ),
			'is_stale'                 => ! empty( $data['is_stale'] ) ? 1 : 0,
			'evaluated_at'             => ! empty( $data['evaluated_at'] ) ? $data['evaluated_at'] : current_time( 'mysql' ),
			'updated_at'               => current_time( 'mysql' ),
		];

		if ( $existing_id > 0 ) {
			$wpdb->update(
				$table,
				$record,
				[ 'id' => $existing_id ]
			);
			return $existing_id;
		}

		$record['created_at'] = current_time( 'mysql' );
		$wpdb->insert( $table, $record );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Get evaluation for a specific page.
	 *
	 * @param int $page_id Page ID.
	 * @return array|null Evaluation record or null.
	 */
	public static function get_by_page_id( int $page_id ): ?array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_QUALITY_EVALUATIONS );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE page_id = %d LIMIT 1", $page_id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$row['issues']      = ! empty( $row['issues_json'] ) ? json_decode( $row['issues_json'], true ) : [];
		$row['suggestions'] = ! empty( $row['suggestions_json'] ) ? json_decode( $row['suggestions_json'], true ) : [];

		return $row;
	}

	/**
	 * Mark evaluation stale for a page (e.g. on edit, regeneration, taxonomy change).
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	public static function mark_stale_by_page_id( int $page_id ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_QUALITY_EVALUATIONS );

		return (bool) $wpdb->update(
			$table,
			[
				'is_stale'   => 1,
				'updated_at' => current_time( 'mysql' ),
			],
			[ 'page_id' => $page_id ]
		);
	}

	/**
	 * Save content fingerprint for fast similarity checks.
	 *
	 * @param array $data Fingerprint data.
	 * @return int Inserted/Updated ID.
	 */
	public static function save_fingerprint( array $data ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_FINGERPRINTS );

		$page_id    = (int) ( $data['page_id'] ?? 0 );
		$version_id = (int) ( $data['version_id'] ?? 0 );

		if ( $page_id <= 0 ) {
			return 0;
		}

		$existing_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE page_id = %d LIMIT 1",
				$page_id
			)
		);

		$record = [
			'page_id'         => $page_id,
			'version_id'      => $version_id,
			'service_id'      => (int) ( $data['service_id'] ?? 0 ),
			'state_id'        => (int) ( $data['state_id'] ?? 0 ),
			'normalized_hash' => sanitize_text_field( $data['normalized_hash'] ?? '' ),
			'shingles_json'   => is_array( $data['shingles_json'] ?? null ) ? wp_json_encode( $data['shingles_json'] ) : (string) ( $data['shingles_json'] ?? '[]' ),
			'word_count'      => (int) ( $data['word_count'] ?? 0 ),
			'created_at'      => current_time( 'mysql' ),
		];

		if ( $existing_id > 0 ) {
			$wpdb->update( $table, $record, [ 'id' => $existing_id ] );
			return $existing_id;
		}

		$wpdb->insert( $table, $record );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Get fingerprint by page ID.
	 *
	 * @param int $page_id Page ID.
	 * @return array|null
	 */
	public static function get_fingerprint_by_page_id( int $page_id ): ?array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_FINGERPRINTS );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE page_id = %d LIMIT 1", $page_id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$row['shingles'] = ! empty( $row['shingles_json'] ) ? json_decode( $row['shingles_json'], true ) : [];
		return $row;
	}

	/**
	 * Get candidate fingerprints sharing the same service or state for partitioned comparison.
	 *
	 * @param int $service_id Service ID.
	 * @param int $state_id State ID.
	 * @param int $exclude_page_id Exclude self.
	 * @param int $limit Maximum candidates.
	 * @return array
	 */
	public static function get_candidate_fingerprints( int $service_id, int $state_id, int $exclude_page_id = 0, int $limit = 200 ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_FINGERPRINTS );

		$where   = [];
		$params  = [];
		$where[] = 'page_id != %d';
		$params[] = $exclude_page_id;

		$or_clauses = [];
		if ( $service_id > 0 ) {
			$or_clauses[] = 'service_id = %d';
			$params[]     = $service_id;
		}
		if ( $state_id > 0 ) {
			$or_clauses[] = 'state_id = %d';
			$params[]     = $state_id;
		}

		if ( ! empty( $or_clauses ) ) {
			$where[] = '(' . implode( ' OR ', $or_clauses ) . ')';
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT %d';
		$params[] = $limit;

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		if ( ! is_array( $results ) ) {
			return [];
		}

		foreach ( $results as &$r ) {
			$r['shingles'] = ! empty( $r['shingles_json'] ) ? json_decode( $r['shingles_json'], true ) : [];
		}

		return $results;
	}

	/**
	 * Add a quality warning / issue dismissal (false-positive marking).
	 *
	 * @param int    $page_id Page ID.
	 * @param string $issue_type Issue type key.
	 * @param string $reason Admin justification.
	 * @param int    $user_id User ID who dismissed.
	 * @return int
	 */
	public static function add_dismissal( int $page_id, string $issue_type, string $reason = '', int $user_id = 0 ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_QUALITY_DISMISSALS );

		$wpdb->insert(
			$table,
			[
				'page_id'      => $page_id,
				'issue_type'   => sanitize_key( $issue_type ),
				'reason'       => sanitize_text_field( $reason ),
				'dismissed_by' => $user_id > 0 ? $user_id : ( function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0 ),
				'dismissed_at' => current_time( 'mysql' ),
				'is_active'    => 1,
				'created_at'   => current_time( 'mysql' ),
			]
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Check if a specific issue type is dismissed for a page.
	 *
	 * @param int    $page_id Page ID.
	 * @param string $issue_type Issue type key.
	 * @return bool
	 */
	public static function is_dismissed( int $page_id, string $issue_type ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_QUALITY_DISMISSALS );

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE page_id = %d AND issue_type = %s AND is_active = 1 LIMIT 1",
				$page_id,
				sanitize_key( $issue_type )
			)
		);

		return ! empty( $id );
	}

	/**
	 * Get active dismissals for a page.
	 *
	 * @param int $page_id Page ID.
	 * @return array
	 */
	public static function get_active_dismissals( int $page_id ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_QUALITY_DISMISSALS );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE page_id = %d AND is_active = 1 ORDER BY dismissed_at DESC",
				$page_id
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Revoke a dismissal (restore warning).
	 *
	 * @param int $dismissal_id Dismissal record ID.
	 * @return bool
	 */
	public static function revoke_dismissal( int $dismissal_id ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_QUALITY_DISMISSALS );

		return (bool) $wpdb->update(
			$table,
			[ 'is_active' => 0 ],
			[ 'id' => $dismissal_id ]
		);
	}

	/**
	 * Get summary stats for the Quality Dashboard.
	 *
	 * @return array
	 */
	public static function get_overview_stats(): array {
		global $wpdb;
		$eval_table  = Schema::get_table_name( Schema::TABLE_QUALITY_EVALUATIONS );
		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$total_pages = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$pages_table}" );
		$total_evals = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$eval_table}" );

		$status_counts = $wpdb->get_results(
			"SELECT quality_status, COUNT(*) as cnt FROM {$eval_table} GROUP BY quality_status",
			ARRAY_A
		) ?: [];

		$counts = [
			self::STATUS_PASS         => 0,
			self::STATUS_WARNING      => 0,
			self::STATUS_NEEDS_REVIEW => 0,
			self::STATUS_FAIL         => 0,
		];

		foreach ( $status_counts as $row ) {
			$st = $row['quality_status'];
			if ( isset( $counts[ $st ] ) ) {
				$counts[ $st ] = (int) $row['cnt'];
			}
		}

		$stale_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$eval_table} WHERE is_stale = 1" );
		$avg_score   = (float) $wpdb->get_var( "SELECT AVG(quality_score) FROM {$eval_table}" );

		$high_similarity_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$eval_table} WHERE similarity_score >= 80.00" );
		$cannibalization_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$eval_table} WHERE intent_overlap_flag = 1" );
		$orphan_count          = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$eval_table} WHERE is_orphan = 1" );

		return [
			'total_pages'           => $total_pages,
			'total_evaluated'       => $total_evals,
			'unscanned_pages'       => max( 0, $total_pages - $total_evals ),
			'pass_count'            => $counts[ self::STATUS_PASS ],
			'warning_count'         => $counts[ self::STATUS_WARNING ],
			'needs_review_count'    => $counts[ self::STATUS_NEEDS_REVIEW ],
			'fail_count'            => $counts[ self::STATUS_FAIL ],
			'stale_count'           => $stale_count,
			'avg_score'             => round( $avg_score, 1 ),
			'high_similarity_count' => $high_similarity_count,
			'cannibalization_count' => $cannibalization_count,
			'orphan_count'          => $orphan_count,
		];
	}

	/**
	 * Get high risk pages needing immediate admin review.
	 *
	 * @param int $limit Max results.
	 * @param int $offset Offset.
	 * @return array
	 */
	public static function get_high_risk_pages( int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$eval_table  = Schema::get_table_name( Schema::TABLE_QUALITY_EVALUATIONS );
		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$sql = "SELECT e.*, p.title as page_title, p.slug as page_slug, p.status as publish_status, p.is_published 
		        FROM {$eval_table} e 
		        INNER JOIN {$pages_table} p ON e.page_id = p.id 
		        WHERE e.quality_status IN ('fail', 'needs_review') OR e.is_stale = 1 OR e.similarity_score >= 80.00 OR e.intent_overlap_flag = 1
		        ORDER BY e.quality_score ASC, e.similarity_score DESC 
		        LIMIT %d OFFSET %d";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $limit, $offset ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		foreach ( $rows as &$r ) {
			$r['issues']      = ! empty( $r['issues_json'] ) ? json_decode( $r['issues_json'], true ) : [];
			$r['suggestions'] = ! empty( $r['suggestions_json'] ) ? json_decode( $r['suggestions_json'], true ) : [];
		}

		return $rows;
	}
}
