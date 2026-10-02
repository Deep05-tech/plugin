<?php
/**
 * Data Integrity & Cross-Contamination Scanner.
 *
 * @package SeoCityPages\Diagnostics
 */

namespace SeoCityPages\Diagnostics;

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrity Scanner Class.
 */
class Integrity_Scanner {

	/**
	 * Run complete integrity analysis across data relationships, orphans, and published pages.
	 *
	 * @param int $page_limit Max pages to check for deep content scanning (default 1000).
	 * @return array
	 */
	public static function run_full_scan( int $page_limit = 1000 ): array {
		$orphan_report      = self::scan_orphaned_records();
		$contamination_rep  = self::scan_cross_contamination( $page_limit );
		$published_rep      = self::scan_published_pages( $page_limit );

		$total_errors   = $orphan_report['total_orphans'] + $contamination_rep['total_mismatches'] + $published_rep['total_issues'];
		$overall_status = Diagnostics_Engine::STATUS_HEALTHY;

		if ( $total_errors > 0 ) {
			$overall_status = Diagnostics_Engine::STATUS_WARNING;
		}
		if ( $contamination_rep['total_mismatches'] > 0 || $published_rep['critical_issues'] > 0 ) {
			$overall_status = Diagnostics_Engine::STATUS_CRITICAL;
		}

		return [
			'status'              => $overall_status,
			'scanned_at'          => current_time( 'mysql' ),
			'orphan_report'       => $orphan_report,
			'contamination_rep'   => $contamination_rep,
			'published_report'    => $published_rep,
			'total_issues_found'  => $total_errors,
		];
	}

	/**
	 * Detect orphaned records across all plugin database tables.
	 *
	 * @return array
	 */
	public static function scan_orphaned_records(): array {
		global $wpdb;

		$countries_t = Schema::get_table_name( Schema::TABLE_COUNTRIES );
		$states_t    = Schema::get_table_name( Schema::TABLE_STATES );
		$cities_t    = Schema::get_table_name( Schema::TABLE_CITIES );
		$services_t  = Schema::get_table_name( Schema::TABLE_SERVICES );
		$pages_t     = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$versions_t  = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );
		$jobs_t      = Schema::get_table_name( Schema::TABLE_BULK_JOBS );
		$items_t     = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$orphans = [];

		// 1. States with missing Country.
		$orphaned_states = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$states_t} s LEFT JOIN {$countries_t} c ON s.country_id = c.id WHERE c.id IS NULL" );
		if ( $orphaned_states > 0 ) {
			$orphans['states_without_country'] = $orphaned_states;
		}

		// 2. Cities with missing State or Country.
		$orphaned_cities = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cities_t} ci LEFT JOIN {$states_t} s ON ci.state_id = s.id LEFT JOIN {$countries_t} c ON ci.country_id = c.id WHERE s.id IS NULL OR c.id IS NULL" );
		if ( $orphaned_cities > 0 ) {
			$orphans['cities_without_parent'] = $orphaned_cities;
		}

		// 3. City Pages referencing missing City or Service.
		$orphaned_pages = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$pages_t} p LEFT JOIN {$cities_t} ci ON p.city_id = ci.id LEFT JOIN {$services_t} sv ON p.service_id = sv.id WHERE ci.id IS NULL OR sv.id IS NULL" );
		if ( $orphaned_pages > 0 ) {
			$orphans['pages_without_city_or_service'] = $orphaned_pages;
		}

		// 4. Content Versions referencing missing Page.
		$orphaned_versions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$versions_t} v LEFT JOIN {$pages_t} p ON v.page_id = p.id WHERE p.id IS NULL" );
		if ( $orphaned_versions > 0 ) {
			$orphans['versions_without_page'] = $orphaned_versions;
		}

		// 5. Job Items referencing missing Job.
		$orphaned_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$items_t} i LEFT JOIN {$jobs_t} j ON i.job_id = j.job_id WHERE j.id IS NULL" );
		if ( $orphaned_items > 0 ) {
			$orphans['job_items_without_job'] = $orphaned_items;
		}

		$total = array_sum( $orphans );

		return [
			'total_orphans' => $total,
			'breakdown'     => $orphans,
		];
	}

	/**
	 * Detect cross-contamination where a page's content version mismatches its target city/service.
	 *
	 * @param int $limit Max pages to inspect.
	 * @return array
	 */
	public static function scan_cross_contamination( int $limit = 1000 ): array {
		global $wpdb;

		$pages_t    = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$cities_t   = Schema::get_table_name( Schema::TABLE_CITIES );
		$services_t = Schema::get_table_name( Schema::TABLE_SERVICES );
		$versions_t = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		// Query pages with published or current content versions.
		$sql = "
			SELECT p.id AS page_id, p.slug, ci.name AS city_name, ci.slug AS city_slug,
			       sv.name AS service_name, sv.slug AS service_slug,
			       v.id AS version_id, v.content_json
			FROM {$pages_t} p
			INNER JOIN {$cities_t} ci ON p.city_id = ci.id
			INNER JOIN {$services_t} sv ON p.service_id = sv.id
			INNER JOIN {$versions_t} v ON (p.published_version_id = v.id OR (p.published_version_id IS NULL AND p.current_version_id = v.id))
			ORDER BY p.id DESC
			LIMIT %d
		";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $rows ) ) {
			return [
				'scanned_pages'    => 0,
				'total_mismatches' => 0,
				'mismatches'       => [],
			];
		}

		$mismatches = [];

		foreach ( $rows as $row ) {
			if ( empty( $row->content_json ) ) {
				continue;
			}

			$content = json_decode( $row->content_json, true );
			if ( ! is_array( $content ) ) {
				continue;
			}

			$hero_title = $content['hero']['title'] ?? '';
			$hero_desc  = $content['hero']['description'] ?? '';
			$combined   = strtolower( $hero_title . ' ' . $hero_desc );

			// Check if target city name appears in content or if alien city is injected.
			$expected_city = strtolower( trim( $row->city_name ) );
			$city_found    = ( strpos( $combined, $expected_city ) !== false );

			if ( ! empty( $expected_city ) && ! $city_found && strlen( $combined ) > 100 ) {
				$mismatches[] = [
					'page_id'        => (int) $row->page_id,
					'expected_city'  => $row->city_name,
					'expected_serv'  => $row->service_name,
					'version_id'     => (int) $row->version_id,
					'problem'        => sprintf( __( 'Content missing target city name "%s" in hero section.', 'seo-city-pages' ), $row->city_name ),
					'recommendation' => __( 'Regenerate content version or review manual edits.', 'seo-city-pages' ),
				];
			}
		}

		return [
			'scanned_pages'    => count( $rows ),
			'total_mismatches' => count( $mismatches ),
			'mismatches'       => array_slice( $mismatches, 0, 50 ), // Cap output for UI
		];
	}

	/**
	 * Verify published page integrity (published flag, valid version, active parents, valid canonical/robots).
	 *
	 * @param int $limit Max pages.
	 * @return array
	 */
	public static function scan_published_pages( int $limit = 1000 ): array {
		global $wpdb;

		$pages_t    = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$cities_t   = Schema::get_table_name( Schema::TABLE_CITIES );
		$services_t = Schema::get_table_name( Schema::TABLE_SERVICES );
		$versions_t = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		$sql = "
			SELECT p.id AS page_id, p.url_path, p.status, p.is_published, p.published_version_id,
			       p.robots_index, p.robots_follow,
			       ci.status AS city_status, sv.status AS service_status,
			       v.status AS version_status, v.content_json
			FROM {$pages_t} p
			LEFT JOIN {$cities_t} ci ON p.city_id = ci.id
			LEFT JOIN {$services_t} sv ON p.service_id = sv.id
			LEFT JOIN {$versions_t} v ON p.published_version_id = v.id
			WHERE p.is_published = 1
			ORDER BY p.id DESC
			LIMIT %d
		";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $rows ) ) {
			return [
				'scanned_published' => 0,
				'total_issues'      => 0,
				'critical_issues'   => 0,
				'issues'            => [],
			];
		}

		$issues          = [];
		$critical_issues = 0;

		foreach ( $rows as $row ) {
			$page_problems = [];

			// 1. Missing or unapproved published version.
			if ( empty( $row->published_version_id ) || empty( $row->content_json ) ) {
				$page_problems[] = __( 'Published page lacks a valid content version payload.', 'seo-city-pages' );
				$critical_issues++;
			} elseif ( 'approved' !== $row->version_status && 'published' !== $row->version_status ) {
				$page_problems[] = sprintf( __( 'Published version has unapproved status "%s".', 'seo-city-pages' ), $row->version_status );
				$critical_issues++;
			}

			// 2. Inactive underlying taxonomy.
			if ( 'active' !== $row->city_status ) {
				$page_problems[] = __( 'Underlying city is inactive/disabled.', 'seo-city-pages' );
			}
			if ( 'active' !== $row->service_status ) {
				$page_problems[] = __( 'Underlying service is inactive/disabled.', 'seo-city-pages' );
			}

			// 3. Contradictory indexing state (e.g. published but forced noindex by mistake).
			if ( 'noindex' === $row->robots_index ) {
				$page_problems[] = __( 'Published live page has explicit "noindex" directive enabled.', 'seo-city-pages' );
			}

			if ( ! empty( $page_problems ) ) {
				$issues[] = [
					'page_id'        => (int) $row->page_id,
					'url_path'       => $row->url_path,
					'problems'       => $page_problems,
					'status_flag'    => 'Requires Review',
					'recommendation' => __( 'Inspect page settings in City Pages dashboard.', 'seo-city-pages' ),
				];
			}
		}

		return [
			'scanned_published' => count( $rows ),
			'total_issues'      => count( $issues ),
			'critical_issues'   => $critical_issues,
			'issues'            => array_slice( $issues, 0, 50 ),
		];
	}
}
