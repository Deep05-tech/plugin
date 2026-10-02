<?php
/**
 * Content Version Database Repository.
 *
 * Manages version history, approval workflows, similarity fingerprints,
 * and retrieval of AI-generated content versions.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content_Version_Repository Class.
 */
class Content_Version_Repository {

	/**
	 * Status Constants.
	 */
	public const STATUS_NOT_GENERATED   = 'not_generated';
	public const STATUS_QUEUED          = 'queued';
	public const STATUS_GENERATING      = 'generating';
	public const STATUS_NEEDS_REVIEW    = 'needs_review';
	public const STATUS_APPROVED        = 'approved';
	public const STATUS_REJECTED        = 'rejected';
	public const STATUS_MANUALLY_EDITED = 'manually_edited';
	public const STATUS_ARCHIVED        = 'archived';
	public const STATUS_FAILED          = 'failed';

	/**
	 * Create a new content version record.
	 *
	 * @param array $data Version data.
	 * @return int Inserted version ID.
	 */
	public static function create( array $data ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		$page_id = (int) ( $data['page_id'] ?? 0 );
		if ( $page_id <= 0 ) {
			return 0;
		}

		// Determine next version number for this page.
		$latest_version_num = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(version_num) FROM {$table} WHERE page_id = %d",
				$page_id
			)
		);
		$next_version_num = $latest_version_num + 1;

		$content_json = is_string( $data['content_json'] ?? null ) ? $data['content_json'] : wp_json_encode( $data['content_json'] ?? [] );
		$content_hash = ! empty( $data['content_hash'] ) ? sanitize_text_field( $data['content_hash'] ) : self::compute_hash( $content_json );

		$status = sanitize_text_field( $data['status'] ?? self::STATUS_NEEDS_REVIEW );
		$now    = current_time( 'mysql' );

		$insert_data = [
			'page_id'            => $page_id,
			'version_num'        => $next_version_num,
			'status'             => $status,
			'content_json'       => $content_json,
			'content_hash'       => $content_hash,
			'provider'           => sanitize_text_field( $data['provider'] ?? 'openai' ),
			'model'              => sanitize_text_field( $data['model'] ?? 'gpt-4o-mini' ),
			'prompt_version'     => sanitize_text_field( $data['prompt_version'] ?? '1.0' ),
			'schema_version'     => sanitize_text_field( $data['schema_version'] ?? '1.0' ),
			'user_id'            => (int) ( $data['user_id'] ?? get_current_user_id() ),
			'source'             => sanitize_text_field( $data['source'] ?? 'ai' ),
			'is_manually_edited' => ! empty( $data['is_manually_edited'] ) ? 1 : 0,
			'input_tokens'       => (int) ( $data['input_tokens'] ?? 0 ),
			'output_tokens'      => (int) ( $data['output_tokens'] ?? 0 ),
			'total_tokens'       => (int) ( $data['total_tokens'] ?? 0 ),
			'generation_time_ms' => (int) ( $data['generation_time_ms'] ?? 0 ),
			'error_message'      => ! empty( $data['error_message'] ) ? sanitize_text_field( $data['error_message'] ) : null,
			'rejection_reason'   => ! empty( $data['rejection_reason'] ) ? sanitize_textarea_field( $data['rejection_reason'] ) : null,
			'rejected_at'        => ! empty( $data['rejected_at'] ) ? sanitize_text_field( $data['rejected_at'] ) : ( self::STATUS_REJECTED === $status ? $now : null ),
			'rejected_by'        => ! empty( $data['rejected_by'] ) ? (int) $data['rejected_by'] : ( self::STATUS_REJECTED === $status ? get_current_user_id() : null ),
			'approved_at'        => ! empty( $data['approved_at'] ) ? sanitize_text_field( $data['approved_at'] ) : ( self::STATUS_APPROVED === $status ? $now : null ),
			'approved_by'        => ! empty( $data['approved_by'] ) ? (int) $data['approved_by'] : ( self::STATUS_APPROVED === $status ? get_current_user_id() : null ),
			'published_at'       => ! empty( $data['published_at'] ) ? sanitize_text_field( $data['published_at'] ) : null,
			'published_by'       => ! empty( $data['published_by'] ) ? (int) $data['published_by'] : null,
			'review_notes'       => ! empty( $data['review_notes'] ) ? sanitize_textarea_field( $data['review_notes'] ) : null,
			'parent_version_id'  => ! empty( $data['parent_version_id'] ) ? (int) $data['parent_version_id'] : null,
			'created_at'         => $now,
			'updated_at'         => $now,
		];

		$result = $wpdb->insert( $table, $insert_data );

		if ( false === $result ) {
			Logger::error( 'Failed to insert content version', [ 'page_id' => $page_id, 'error' => $wpdb->last_error ] );
			return 0;
		}

		$version_id = (int) $wpdb->insert_id;

		// Update City Page generation status and current_version_id if approved or needs_review.
		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$update_page = [
			'generation_status' => $status,
			'updated_at'        => $now,
		];
		if ( self::STATUS_APPROVED === $status ) {
			$update_page['current_version_id'] = $version_id;
		} elseif ( self::STATUS_NEEDS_REVIEW === $status ) {
			// Set as current draft if page didn't have one.
			$current_vid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT current_version_id FROM {$pages_table} WHERE id = %d", $page_id ) );
			if ( $current_vid <= 0 ) {
				$update_page['current_version_id'] = $version_id;
			}
		}
		$wpdb->update( $pages_table, $update_page, [ 'id' => $page_id ] );

		return $version_id;
	}

	/**
	 * Retrieve a content version by ID.
	 *
	 * @param int $id Version ID.
	 * @return object|null Version object or null.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d LIMIT 1",
				$id
			)
		);

		return $row ?: null;
	}

	/**
	 * Retrieve all content versions for a given page.
	 *
	 * @param int $page_id City Page ID.
	 * @return object[] Array of version objects ordered by version_num DESC.
	 */
	public static function get_by_page_id( int $page_id ): array {
		return self::get_by_page( $page_id );
	}

	/**
	 * Retrieve versions for a given page, optionally filtered by status.
	 *
	 * @param int         $page_id City Page ID.
	 * @param string|null $status  Optional status filter.
	 * @return object[]
	 */
	public static function get_by_page( int $page_id, ?string $status = null ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		if ( $status ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE page_id = %d AND status = %s ORDER BY version_num DESC",
					$page_id,
					$status
				)
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE page_id = %d ORDER BY version_num DESC",
					$page_id
				)
			);
		}

		return $rows ?: [];
	}

	/**
	 * Retrieve current approved/active version for a page (Alias).
	 *
	 * @param int $page_id City Page ID.
	 * @return object|null
	 */
	public static function get_active_version( int $page_id ): ?object {
		return self::get_current_approved_version( $page_id );
	}

	/**
	 * Retrieve current approved version for a page.
	 *
	 * @param int $page_id City Page ID.
	 * @return object|null Approved version object or null.
	 */
	public static function get_current_approved_version( int $page_id ): ?object {
		global $wpdb;
		$versions_table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );
		$pages_table    = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT v.* FROM {$versions_table} v
				 INNER JOIN {$pages_table} p ON p.current_version_id = v.id
				 WHERE p.id = %d LIMIT 1",
				$page_id
			)
		);

		if ( ! $row ) {
			// Fallback: look for latest approved version.
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$versions_table} WHERE page_id = %d AND status = %s ORDER BY version_num DESC LIMIT 1",
					$page_id,
					self::STATUS_APPROVED
				)
			);
		}

		return $row ?: null;
	}

	/**
	 * Retrieve latest version for a page regardless of status.
	 *
	 * @param int $page_id City Page ID.
	 * @return object|null Latest version object or null.
	 */
	public static function get_latest_version( int $page_id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE page_id = %d ORDER BY version_num DESC LIMIT 1",
				$page_id
			)
		);

		return $row ?: null;
	}

	/**
	 * Retrieve the version immediately preceding the given version for comparison diffs.
	 *
	 * @param int $version_id Target version ID.
	 * @return object|null Preceding version object or null.
	 */
	public static function get_previous_version( int $version_id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		$current = self::get( $version_id );
		if ( ! $current ) {
			return null;
		}

		// If parent_version_id is set, prefer that.
		if ( ! empty( $current->parent_version_id ) ) {
			$parent = self::get( (int) $current->parent_version_id );
			if ( $parent ) {
				return $parent;
			}
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE page_id = %d AND version_num < %d ORDER BY version_num DESC LIMIT 1",
				$current->page_id,
				$current->version_num
			)
		);

		return $row ?: null;
	}

	/**
	 * Count total versions for a given page.
	 *
	 * @param int $page_id City Page ID.
	 * @return int Total version count.
	 */
	public static function get_version_count( int $page_id ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE page_id = %d",
				$page_id
			)
		);
	}

	/**
	 * Get counts for review queue tabs.
	 *
	 * @return array
	 */
	public static function get_queue_counts(): array {
		global $wpdb;
		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$rows = $wpdb->get_results(
			"SELECT 
				SUM(CASE WHEN generation_status = 'needs_review' THEN 1 ELSE 0 END) as needs_review,
				SUM(CASE WHEN generation_status = 'approved' THEN 1 ELSE 0 END) as approved,
				SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published,
				SUM(CASE WHEN generation_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
				COUNT(*) as total
			 FROM {$pages_table}"
		);

		$data = $rows[0] ?? null;

		return [
			'needs_review' => (int) ( $data->needs_review ?? 0 ),
			'approved'     => (int) ( $data->approved ?? 0 ),
			'published'    => (int) ( $data->published ?? 0 ),
			'rejected'     => (int) ( $data->rejected ?? 0 ),
			'total'        => (int) ( $data->total ?? 0 ),
		];
	}

	/**
	 * Approve a specific content version.
	 *
	 * @param int $version_id Version ID to approve.
	 * @param int $user_id    User ID performing the approval.
	 * @return bool
	 */
	public static function approve_version( int $version_id, int $user_id = 0 ): bool {
		global $wpdb;
		$versions_table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );
		$pages_table    = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$version = self::get( $version_id );
		if ( ! $version ) {
			return false;
		}

		$now     = current_time( 'mysql' );
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();

		// Mark this version as approved.
		$wpdb->update(
			$versions_table,
			[
				'status'      => self::STATUS_APPROVED,
				'approved_at' => $now,
				'approved_by' => $user_id,
				'updated_at'  => $now,
			],
			[ 'id' => $version_id ]
		);

		// Update City Page's current_version_id and generation_status.
		$wpdb->update(
			$pages_table,
			[
				'current_version_id' => $version_id,
				'generation_status'  => self::STATUS_APPROVED,
				'updated_at'         => $now,
			],
			[ 'id' => $version->page_id ]
		);

		Logger::info(
			sprintf( 'Approved content version #%d for Page #%d', $version_id, $version->page_id ),
			[ 'version_id' => $version_id, 'page_id' => $version->page_id ]
		);

		return true;
	}

	/**
	 * Reject a content version.
	 *
	 * @param int $version_id Version ID.
	 * @return bool
	 */
	public static function reject_version( int $version_id ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		$version = self::get( $version_id );
		if ( ! $version ) {
			return false;
		}

		$now    = current_time( 'mysql' );
		$result = $wpdb->update(
			$table,
			[
				'status'     => self::STATUS_REJECTED,
				'updated_at' => $now,
			],
			[ 'id' => $version_id ]
		);

		// If this was the active version on page, reset generation status on page.
		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$pages_table} SET generation_status = %s WHERE id = %d AND current_version_id = %d",
				self::STATUS_REJECTED,
				$version->page_id,
				$version_id
			)
		);

		return false !== $result;
	}

	/**
	 * Update content JSON manually and mark status as manually_edited.
	 *
	 * @param int    $version_id   Version ID.
	 * @param string $content_json Updated JSON string.
	 * @param string $status       Target status (default 'manually_edited').
	 * @return bool
	 */
	public static function update_content( int $version_id, string $content_json, string $status = self::STATUS_MANUALLY_EDITED ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		$now  = current_time( 'mysql' );
		$hash = self::compute_hash( $content_json );

		$result = $wpdb->update(
			$table,
			[
				'content_json' => $content_json,
				'content_hash' => $hash,
				'status'       => $status,
				'updated_at'   => $now,
			],
			[ 'id' => $version_id ]
		);

		return false !== $result;
	}

	/**
	 * Compute SHA-256 fingerprint hash of content JSON.
	 *
	 * @param string $content_json Raw JSON string.
	 * @return string SHA-256 hex string.
	 */
	public static function compute_hash( string $content_json ): string {
		$decoded = json_decode( $content_json, true );
		if ( is_array( $decoded ) ) {
			// Normalize keys for consistent hashing.
			ksort( $decoded );
			return hash( 'sha256', (string) wp_json_encode( $decoded ) );
		}
		return hash( 'sha256', trim( $content_json ) );
	}

	/**
	 * Check for existing identical content hashes.
	 *
	 * @param string $hash            SHA-256 hash.
	 * @param int    $exclude_page_id Optional Page ID to exclude.
	 * @return object[] Matching content version rows.
	 */
	public static function find_by_hash( string $hash, int $exclude_page_id = 0 ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		if ( $exclude_page_id > 0 ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE content_hash = %s AND page_id != %d LIMIT 10",
					$hash,
					$exclude_page_id
				)
			);
		} else {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE content_hash = %s LIMIT 10",
					$hash
				)
			);
		}

		return $results ?: [];
	}

	/**
	 * Get aggregated AI generation and token usage metrics.
	 *
	 * @return array
	 */
	public static function get_stats(): array {
		global $wpdb;
		$versions_table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );
		$pages_table    = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$total_pages = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$pages_table}" );

		$status_counts = $wpdb->get_results(
			"SELECT generation_status, COUNT(*) as count FROM {$pages_table} GROUP BY generation_status",
			OBJECT_K
		);

		$token_totals = $wpdb->get_row(
			"SELECT 
				COUNT(*) as total_versions,
				SUM(input_tokens) as total_input_tokens,
				SUM(output_tokens) as total_output_tokens,
				SUM(total_tokens) as total_tokens
			 FROM {$versions_table}"
		);

		return [
			'total_pages'          => $total_pages,
			'not_generated'        => (int) ( $status_counts[ self::STATUS_NOT_GENERATED ]->count ?? ( $total_pages - (int) $wpdb->get_var( "SELECT COUNT(DISTINCT page_id) FROM {$versions_table}" ) ) ),
			'needs_review'         => (int) ( $status_counts[ self::STATUS_NEEDS_REVIEW ]->count ?? 0 ),
			'approved'             => (int) ( $status_counts[ self::STATUS_APPROVED ]->count ?? 0 ),
			'manually_edited'      => (int) ( $status_counts[ self::STATUS_MANUALLY_EDITED ]->count ?? 0 ),
			'rejected'             => (int) ( $status_counts[ self::STATUS_REJECTED ]->count ?? 0 ),
			'failed'               => (int) ( $status_counts[ self::STATUS_FAILED ]->count ?? 0 ),
			'total_versions'       => (int) ( $token_totals->total_versions ?? 0 ),
			'total_input_tokens'   => (int) ( $token_totals->total_input_tokens ?? 0 ),
			'total_output_tokens'  => (int) ( $token_totals->total_output_tokens ?? 0 ),
			'total_tokens'         => (int) ( $token_totals->total_tokens ?? 0 ),
		];
	}

	/**
	 * Count total content versions.
	 *
	 * @return int
	 */
	public static function count(): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}
}
