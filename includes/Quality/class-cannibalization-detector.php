<?php
/**
 * Cannibalization Detector.
 *
 * Identifies search-intent cannibalization, overlapping target keywords,
 * and competing service pages within the same geographical location.
 *
 * @package SeoCityPages\Quality
 */

namespace SeoCityPages\Quality;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cannibalization_Detector Class.
 */
class Cannibalization_Detector {

	/**
	 * Common service synonym groups to detect intent overlaps.
	 */
	private const SYNONYM_GROUPS = [
		[ 'seo', 'search engine optimization', 'seo agency', 'seo services', 'seo company' ],
		[ 'web design', 'website design', 'web development', 'website development' ],
		[ 'digital marketing', 'online marketing', 'internet marketing' ],
		[ 'ppc', 'pay per click', 'google ads', 'adwords management' ],
		[ 'social media marketing', 'smm', 'social media management' ],
		[ 'content marketing', 'copywriting', 'content writing' ],
		[ 'link building', 'backlink services', 'off-page seo' ],
	];

	/**
	 * Analyze a page for potential intent cannibalization against other pages in the same city.
	 *
	 * @param int    $page_id Current page ID.
	 * @param int    $city_id City ID.
	 * @param string $service_name Service name.
	 * @param string $target_keyword Target primary SEO keyword.
	 * @return array [ 'has_overlap' => bool, 'overlap_page_id' => int, 'overlap_score' => float, 'reason' => string ]
	 */
	public static function analyze_cannibalization( int $page_id, int $city_id, string $service_name, string $target_keyword = '' ): array {
		if ( $city_id <= 0 ) {
			return [
				'has_overlap'     => false,
				'overlap_page_id' => 0,
				'overlap_score'   => 0.0,
				'reason'          => '',
			];
		}

		// Find other pages in the exact same city.
		$city_pages = City_Page_Repository::get_all( [ 'city_id' => $city_id, 'limit' => 100 ] );
		if ( empty( $city_pages ) ) {
			return [
				'has_overlap'     => false,
				'overlap_page_id' => 0,
				'overlap_score'   => 0.0,
				'reason'          => '',
			];
		}

		$current_kw_clean      = mb_strtolower( trim( $target_keyword ), 'UTF-8' );
		$current_service_clean = mb_strtolower( trim( $service_name ), 'UTF-8' );

		foreach ( $city_pages as $other ) {
			$other    = is_object( $other ) ? (array) $other : $other;
			$other_id = (int) ( $other['id'] ?? 0 );
			if ( $other_id === $page_id ) {
				continue;
			}

			$other_kw = mb_strtolower( trim( (string) ( $other['primary_keyword'] ?? ( $other['seo_title'] ?? ( $other['title_override'] ?? '' ) ) ) ), 'UTF-8' );
			$other_service = mb_strtolower( trim( (string) ( $other['service_name'] ?? '' ) ), 'UTF-8' );

			if ( empty( $other_service ) && ! empty( $other['service_id'] ) && class_exists( '\SeoCityPages\Database\Service_Repository' ) ) {
				$s = \SeoCityPages\Database\Service_Repository::get( (int) $other['service_id'] );
				if ( $s && ! empty( $s->name ) ) {
					$other_service = mb_strtolower( trim( (string) $s->name ), 'UTF-8' );
				}
			}

			if ( empty( $other_kw ) && ! empty( $other_service ) ) {
				$other_kw = $other_service;
			}

			// Check 1: Identical primary keyword in same city.
			if ( ! empty( $current_kw_clean ) && ! empty( $other_kw ) && $current_kw_clean === $other_kw ) {
				return [
					'has_overlap'     => true,
					'overlap_page_id' => $other_id,
					'overlap_score'   => 100.0,
					'reason'          => sprintf(
						'Duplicate primary keyword "%s" with existing page ID #%d in same city.',
						$target_keyword,
						$other_id
					),
				];
			}

			// Check 2: Service synonym / intent overlap in same city.
			if ( self::are_synonym_services( $current_service_clean, $other_service ) ) {
				return [
					'has_overlap'     => true,
					'overlap_page_id' => $other_id,
					'overlap_score'   => 85.0,
					'reason'          => sprintf(
						'High intent overlap: Service "%s" closely competes with "%s" (Page #%d) in same city.',
						$service_name,
						$other['service_name'] ?? $other_service,
						$other_id
					),
				];
			}

			// Check 3: Substring keyword overlap.
			if ( ! empty( $current_kw_clean ) && ! empty( $other_kw ) ) {
				if ( strpos( $current_kw_clean, $other_kw ) !== false || strpos( $other_kw, $current_kw_clean ) !== false ) {
					return [
						'has_overlap'     => true,
						'overlap_page_id' => $other_id,
						'overlap_score'   => 75.0,
						'reason'          => sprintf(
							'Target keyword "%s" overlaps with keyword "%s" on Page #%d.',
							$target_keyword,
							$other['primary_keyword'],
							$other_id
						),
					];
				}
			}
		}

		return [
			'has_overlap'     => false,
			'overlap_page_id' => 0,
			'overlap_score'   => 0.0,
			'reason'          => '',
		];
	}

	/**
	 * Determine if two service names belong to the same intent synonym cluster.
	 *
	 * @param string $service_a Service name A.
	 * @param string $service_b Service name B.
	 * @return bool
	 */
	public static function are_synonym_services( string $service_a, string $service_b ): bool {
		$service_a = mb_strtolower( trim( $service_a ), 'UTF-8' );
		$service_b = mb_strtolower( trim( $service_b ), 'UTF-8' );

		if ( empty( $service_a ) || empty( $service_b ) ) {
			return false;
		}

		if ( $service_a === $service_b ) {
			return true;
		}

		foreach ( self::SYNONYM_GROUPS as $group ) {
			$match_a = false;
			$match_b = false;

			foreach ( $group as $syn ) {
				$syn = mb_strtolower( trim( $syn ), 'UTF-8' );
				if ( false !== strpos( $service_a, $syn ) ) {
					$match_a = true;
				}
				if ( false !== strpos( $service_b, $syn ) ) {
					$match_b = true;
				}
			}

			if ( $match_a && $match_b ) {
				return true;
			}
		}

		return false;
	}
}
