<?php
/**
 * Internal Link Analyzer.
 *
 * Evaluates internal link structure, detects orphan pages, and generates
 * non-destructive natural linking suggestions.
 *
 * @package SeoCityPages\Quality
 */

namespace SeoCityPages\Quality;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\Service_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Internal_Link_Analyzer Class.
 */
class Internal_Link_Analyzer {

	/**
	 * Minimum recommended internal links per programmatic city page.
	 */
	public const MIN_RECOMMENDED_LINKS = 2;

	/**
	 * Analyze internal linking for a city page.
	 *
	 * @param int          $page_id City Page ID.
	 * @param array|string $content Content.
	 * @param array        $page_data Associated city page record.
	 * @return array [ 'link_count' => int, 'is_orphan' => bool, 'issues' => array, 'suggestions' => array ]
	 */
	public static function analyze( int $page_id, $content, array $page_data = [] ): array {
		if ( is_array( $content ) ) {
			$raw_html = stripslashes( (string) wp_json_encode( $content ) );
		} else {
			$raw_html = (string) $content;
		}

		// Extract href links.
		preg_match_all( '/<a\s+[^>]*href=["\']([^"\']+)["\']/i', $raw_html, $matches );
		$links = ! empty( $matches[1] ) ? $matches[1] : [];

		// Filter internal links (relative or matching site domain).
		$site_url       = function_exists( 'get_site_url' ) ? get_site_url() : '';
		$internal_links = [];
		foreach ( $links as $href ) {
			if ( strpos( $href, '/' ) === 0 || ( ! empty( $site_url ) && strpos( $href, $site_url ) === 0 ) ) {
				$internal_links[] = $href;
			}
		}

		$link_count = count( $internal_links );
		$issues     = [];

		if ( $link_count === 0 ) {
			$issues[] = [
				'type'     => 'no_internal_links',
				'severity' => 'warning',
				'message'  => 'Page contains zero internal links to related city or service pages.',
			];
		} elseif ( $link_count < self::MIN_RECOMMENDED_LINKS ) {
			$issues[] = [
				'type'     => 'few_internal_links',
				'severity' => 'info',
				'message'  => sprintf( 'Page contains only %d internal link(s). Recommended is %d+.', $link_count, self::MIN_RECOMMENDED_LINKS ),
			];
		}

		// Check orphan status: does this page have parent taxonomy and sibling pages linking to it?
		$is_orphan = false;
		$state_id  = (int) ( $page_data['state_id'] ?? 0 );
		$city_id   = (int) ( $page_data['city_id'] ?? 0 );

		if ( $city_id > 0 && class_exists( City_Repository::class ) ) {
			$city = City_Repository::get( $city_id );
			if ( ! $city || ( isset( $city->status ) && 'inactive' === $city->status ) ) {
				$is_orphan = true;
				$issues[]  = [
					'type'     => 'inactive_location_orphan',
					'severity' => 'warning',
					'message'  => 'Parent city location is inactive or missing, making this an orphan page.',
				];
			}
		}

		// Generate natural suggestions (Advisory only — NEVER auto-inject into content).
		$suggestions = self::generate_suggestions( $page_id, $page_data );

		return [
			'link_count'  => $link_count,
			'is_orphan'   => $is_orphan,
			'issues'      => $issues,
			'suggestions' => $suggestions,
		];
	}

	/**
	 * Generate non-destructive internal linking opportunities.
	 *
	 * @param int   $page_id Page ID.
	 * @param array $page_data Page data.
	 * @return array
	 */
	public static function generate_suggestions( int $page_id, array $page_data ): array {
		$suggestions = [];
		$state_id    = (int) ( $page_data['state_id'] ?? 0 );
		$city_id     = (int) ( $page_data['city_id'] ?? 0 );
		$service_id  = (int) ( $page_data['service_id'] ?? 0 );

		// Suggestion 1: Neighboring city pages for the same service.
		if ( $state_id > 0 && $service_id > 0 ) {
			$siblings = City_Page_Repository::get_all( [ 'state_id' => $state_id, 'service_id' => $service_id, 'limit' => 5 ] );
			foreach ( $siblings as $sib ) {
				$sib    = is_object( $sib ) ? (array) $sib : $sib;
				$sib_id = (int) ( $sib['id'] ?? 0 );
				if ( $sib_id > 0 && $sib_id !== $page_id ) {
					$suggestions[] = [
						'type'        => 'neighboring_city',
						'page_id'     => $sib_id,
						'title'       => $sib['title'] ?? 'City Page',
						'anchor_text' => sprintf( '%s in %s', $sib['service_name'] ?? 'Services', $sib['city_name'] ?? 'Nearby' ),
						'url_path'    => $sib['url_path'] ?? '',
					];
					if ( count( $suggestions ) >= 3 ) {
						break;
					}
				}
			}
		}

		// Suggestion 2: Other services available in the same city.
		if ( $city_id > 0 ) {
			$other_services = City_Page_Repository::get_all( [ 'city_id' => $city_id, 'limit' => 10 ] );
			foreach ( $other_services as $svc_page ) {
				$svc_page = is_object( $svc_page ) ? (array) $svc_page : $svc_page;
				$svc_pid  = (int) ( $svc_page['id'] ?? 0 );
				if ( $svc_pid > 0 && $svc_pid !== $page_id && (int) ( $svc_page['service_id'] ?? 0 ) !== $service_id ) {
					$suggestions[] = [
						'type'        => 'related_service_in_city',
						'page_id'     => $svc_pid,
						'title'       => $svc_page['title'] ?? 'Service Page',
						'anchor_text' => sprintf( '%s in %s', $svc_page['service_name'] ?? 'Service', $svc_page['city_name'] ?? 'City' ),
						'url_path'    => $svc_page['url_path'] ?? '',
					];
					if ( count( $suggestions ) >= 6 ) {
						break;
					}
				}
			}
		}

		return $suggestions;
	}
}
