<?php
/**
 * Internal Quality Evaluation Engine.
 *
 * Evaluates 12 dimensions of programmatic SEO quality, generates an Internal Content Quality Score
 * (0–100), and classifies pages into Pass, Warning, Needs Review, or Fail.
 *
 * Note: This score is strictly an internal algorithmic heuristic and makes zero claims or guarantees
 * regarding third-party search engine rankings.
 *
 * @package SeoCityPages\Quality
 */

namespace SeoCityPages\Quality;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Database\Quality_Repository;
use SeoCityPages\SEO\SEO_Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quality_Engine Class.
 */
class Quality_Engine {

	/**
	 * Placeholder patterns that must never appear in production content.
	 */
	private const PLACEHOLDER_PATTERNS = [
		'/lorem\s+ipsum/i',
		'/\bTODO\b/i',
		'/\bTBD\b/i',
		'/\{\{[^\}]+\}\}/',
		'/\[(city|state|service|company)\]/i',
		'/_{3,}/',
		'/\[insert\s+[^\]]+\]/i',
	];

	/**
	 * Unsupported or high-risk local ranking/statistic claims.
	 */
	private const UNSUPPORTED_CLAIM_PATTERNS = [
		'/guaranteed\s+(#?1|first\s+page)\s+rank/i',
		'/voted\s+(the\s+)?#?1\s+best/i',
		'/ranked\s+#?1\s+by\s+100%/i',
		'/guarantee\s+top\s+rankings?\s+in\s+\d+\s*(days|hours)/i',
	];

	/**
	 * Overused generic AI cliché phrases.
	 */
	private const CLICHE_PATTERNS = [
		'/in\s+today\'?s\s+fast-?paced\s+(digital\s+)?world/i',
		'/delve\s+(deep(ly)?)?\s+into/i',
		'/\btapestry\b/i',
		'/seamlessly\s+navigate/i',
		'/unlock\s+your\s+(full\s+)?potential/i',
		'/testament\s+to/i',
		'/game-?changer/i',
		'/beacon\s+of\s+excellence/i',
	];

	/**
	 * Evaluate a page's content version across all 12 quality dimensions.
	 *
	 * @param int        $page_id City Page ID.
	 * @param array|null $content Optional content array. If null, active version is loaded.
	 * @param array|null $page_data Optional page data.
	 * @return array Evaluation result array.
	 */
	public static function evaluate_page( int $page_id, ?array $content = null, $page_data = null ): array {
		if ( null === $page_data ) {
			$page_data = City_Page_Repository::get( $page_id );
		}

		if ( is_object( $page_data ) ) {
			$page_data = (array) $page_data;
		}

		if ( empty( $page_data ) ) {
			return [
				'quality_score'  => 0,
				'quality_status' => Quality_Repository::STATUS_FAIL,
				'issues'         => [ [ 'type' => 'missing_page', 'severity' => 'critical', 'message' => 'Page record not found.' ] ],
			];
		}

		$version_id = (int) ( $page_data['active_version_id'] ?? $page_data['current_version_id'] ?? $page_data['published_version_id'] ?? 0 );

		if ( null === $content ) {
			if ( $version_id > 0 ) {
				$version = Content_Version_Repository::get( $version_id );
				if ( is_object( $version ) ) {
					$version = (array) $version;
				}
				$content = ! empty( $version['content_json'] ) ? ( is_array( $version['content_json'] ) ? $version['content_json'] : json_decode( $version['content_json'], true ) ) : [];
			} else {
				$version = Content_Version_Repository::get_latest_version( $page_id );
				if ( is_object( $version ) ) {
					$version = (array) $version;
				}
				if ( ! empty( $version ) ) {
					$version_id = (int) ( $version['id'] ?? 0 );
					$content    = ! empty( $version['content_json'] ) ? ( is_array( $version['content_json'] ) ? $version['content_json'] : json_decode( $version['content_json'], true ) ) : [];
				}
			}
		}

		$content = is_array( $content ) ? $content : [];

		$city_name    = (string) ( $page_data['city_name'] ?? '' );
		$state_name   = (string) ( $page_data['state_name'] ?? '' );
		$service_name = (string) ( $page_data['service_name'] ?? '' );
		$service_id   = (int) ( $page_data['service_id'] ?? 0 );
		$state_id     = (int) ( $page_data['state_id'] ?? 0 );
		$city_id      = (int) ( $page_data['city_id'] ?? 0 );

		if ( empty( $city_name ) && $city_id > 0 && class_exists( '\SeoCityPages\Database\City_Repository' ) ) {
			$c = \SeoCityPages\Database\City_Repository::get( $city_id );
			if ( $c && ! empty( $c->name ) ) {
				$city_name = (string) $c->name;
			}
		}
		if ( empty( $service_name ) && $service_id > 0 && class_exists( '\SeoCityPages\Database\Service_Repository' ) ) {
			$s = \SeoCityPages\Database\Service_Repository::get( $service_id );
			if ( $s && ! empty( $s->name ) ) {
				$service_name = (string) $s->name;
			}
		}

		$primary_kw = (string) ( $page_data['primary_keyword'] ?? ( $page_data['seo_title'] ?? ( $service_name . ' ' . $city_name ) ) );

		$raw_text = Similarity_Engine::extract_text( $content );
		$words    = preg_split( '/\s+/u', trim( $raw_text ), -1, PREG_SPLIT_NO_EMPTY );
		$word_cnt = count( $words );

		$issues = [];

		// -------------------------------------------------------------
		// Dimension 1: Completeness (Required Sections)
		// -------------------------------------------------------------
		$required_sections = [ 'hero', 'introduction', 'service_overview', 'benefits', 'process', 'local_relevance', 'faqs', 'final_cta' ];
		$present_sections  = 0;
		foreach ( $required_sections as $sec ) {
			if ( ! empty( $content[ $sec ] ) ) {
				$present_sections++;
			} else {
				$issues[] = [
					'type'     => 'missing_section_' . $sec,
					'severity' => 'major',
					'penalty'  => 6.0,
					'message'  => sprintf( 'Missing required content section "%s".', $sec ),
				];
			}
		}
		$completeness_score = round( ( $present_sections / count( $required_sections ) ) * 100.0, 2 );

		// -------------------------------------------------------------
		// Dimension 2: Thin Content / Length
		// -------------------------------------------------------------
		$thin_content_flag = 0;
		if ( $word_cnt === 0 ) {
			$thin_content_flag = 1;
			$issues[]          = [
				'type'     => 'empty_content',
				'severity' => 'critical',
				'penalty'  => 35.0,
				'message'  => 'Page content is completely empty.',
			];
		} elseif ( $word_cnt < 100 ) {
			$thin_content_flag = 1;
			$issues[]          = [
				'type'     => 'thin_content_warning',
				'severity' => 'warning',
				'penalty'  => 15.0,
				'message'  => sprintf( 'Content is thin (%d words). Recommended 300+ words.', $word_cnt ),
			];
		} elseif ( $word_cnt < 300 ) {
			$thin_content_flag = 1;
			$issues[]          = [
				'type'     => 'thin_content_warning',
				'severity' => 'info',
				'penalty'  => 5.0,
				'message'  => sprintf( 'Content length is modest (%d words). Recommended 600+ words for competitive local coverage.', $word_cnt ),
			];
		}

		// -------------------------------------------------------------
		// Dimension 3: Similarity & Duplication
		// -------------------------------------------------------------
		$sim_result = Similarity_Engine::analyze_similarity(
			$page_id,
			$service_id,
			$state_id,
			$content,
			$city_name,
			$state_name,
			$service_name
		);
		$similarity_score    = (float) $sim_result['max_similarity'];
		$max_similar_page_id = (int) $sim_result['max_similar_page_id'];

		if ( $similarity_score >= 100.0 ) {
			$issues[] = [
				'type'     => 'exact_duplicate_content',
				'severity' => 'critical',
				'penalty'  => 30.0,
				'message'  => sprintf( 'Exact 100%% duplicate content with Page #%d.', $max_similar_page_id ),
			];
		} elseif ( $similarity_score >= 80.0 ) {
			$issues[] = [
				'type'     => 'content_similarity_warning',
				'severity' => 'warning',
				'penalty'  => 15.0,
				'message'  => sprintf( 'High content similarity (%.1f%%) with Page #%d.', $similarity_score, $max_similar_page_id ),
			];
		}

		// -------------------------------------------------------------
		// Dimension 4 & 5: City & Service Relevance
		// -------------------------------------------------------------
		$city_mentions = ! empty( $city_name ) ? substr_count( mb_strtolower( $raw_text, 'UTF-8' ), mb_strtolower( $city_name, 'UTF-8' ) ) : 0;
		$city_relevance_score = ( $city_mentions >= 3 ) ? 100.0 : ( $city_mentions > 0 ? 60.0 : 0.0 );
		if ( $city_mentions === 0 && ! empty( $city_name ) ) {
			$issues[] = [
				'type'     => 'missing_city_context',
				'severity' => 'critical',
				'penalty'  => 25.0,
				'message'  => sprintf( 'Target city "%s" is not mentioned anywhere in page body.', $city_name ),
			];
		} elseif ( $city_mentions < 2 && ! empty( $city_name ) ) {
			$issues[] = [
				'type'     => 'low_city_context',
				'severity' => 'warning',
				'penalty'  => 8.0,
				'message'  => sprintf( 'Target city "%s" is only mentioned %d time(s).', $city_name, $city_mentions ),
			];
		}

		$svc_mentions = ! empty( $service_name ) ? substr_count( mb_strtolower( $raw_text, 'UTF-8' ), mb_strtolower( $service_name, 'UTF-8' ) ) : 0;
		$service_relevance_score = ( $svc_mentions >= 2 ) ? 100.0 : ( $svc_mentions > 0 ? 60.0 : 0.0 );
		if ( $svc_mentions === 0 && ! empty( $service_name ) ) {
			$issues[] = [
				'type'     => 'missing_service_context',
				'severity' => 'critical',
				'penalty'  => 25.0,
				'message'  => sprintf( 'Target service "%s" is not mentioned in page body.', $service_name ),
			];
		}

		// -------------------------------------------------------------
		// Dimension 6: Intent Overlap & Cannibalization
		// -------------------------------------------------------------
		$cannibalization_result = Cannibalization_Detector::analyze_cannibalization(
			$page_id,
			$city_id,
			$service_name,
			$primary_kw
		);
		$intent_overlap_flag = $cannibalization_result['has_overlap'] ? 1 : 0;
		$overlap_page_id     = (int) $cannibalization_result['overlap_page_id'];
		if ( $intent_overlap_flag ) {
			$issues[] = [
				'type'     => 'intent_cannibalization',
				'severity' => 'warning',
				'penalty'  => 15.0,
				'message'  => $cannibalization_result['reason'],
			];
		}

		// -------------------------------------------------------------
		// Dimension 7: Unresolved Placeholders
		// -------------------------------------------------------------
		$placeholder_issues_count = 0;
		$raw_json                 = wp_json_encode( $content );
		foreach ( self::PLACEHOLDER_PATTERNS as $pat ) {
			if ( preg_match_all( $pat, $raw_json, $m ) ) {
				$count                     = count( $m[0] );
				$placeholder_issues_count += $count;
				$issues[]                  = [
					'type'     => 'unresolved_placeholder',
					'severity' => 'critical',
					'penalty'  => ( 15.0 * $count ),
					'message'  => sprintf( 'Found %d unresolved placeholder token(s) matching pattern "%s".', $count, $pat ),
				];
			}
		}

		// -------------------------------------------------------------
		// Dimension 8: Unsupported Local Claims
		// -------------------------------------------------------------
		$unsupported_claims_count = 0;
		foreach ( self::UNSUPPORTED_CLAIM_PATTERNS as $pat ) {
			if ( preg_match_all( $pat, $raw_text, $m ) ) {
				$count                     = count( $m[0] );
				$unsupported_claims_count += $count;
				$issues[]                  = [
					'type'     => 'unsupported_local_claim',
					'severity' => 'warning',
					'penalty'  => ( 8.0 * $count ),
					'message'  => sprintf( 'Found unsupported superlative ranking claim (%s).', implode( ', ', $m[0] ) ),
				];
			}
		}

		// -------------------------------------------------------------
		// Dimension 9: Generic AI Clichés
		// -------------------------------------------------------------
		$cliche_count = 0;
		foreach ( self::CLICHE_PATTERNS as $pat ) {
			if ( preg_match_all( $pat, $raw_text, $m ) ) {
				$count         = count( $m[0] );
				$cliche_count += $count;
				$issues[]      = [
					'type'     => 'generic_ai_cliche',
					'severity' => 'info',
					'penalty'  => ( 3.0 * $count ),
					'message'  => sprintf( 'Repetitive AI cliché detected: "%s".', $m[0][0] ),
				];
			}
		}

		// -------------------------------------------------------------
		// Dimension 10: Keyword Stuffing & Density (1.5% - 3.0%)
		// -------------------------------------------------------------
		$keyword_stuffing_flag = 0;
		if ( ! empty( $primary_kw ) && $word_cnt > 50 && class_exists( SEO_Validator::class ) ) {
			$density_eval = SEO_Validator::calculate_keyword_density( $raw_text, $primary_kw );
			$kw_data      = $density_eval['keywords'][ $primary_kw ] ?? null;
			if ( $kw_data ) {
				$density = (float) ( $kw_data['density'] ?? 0.0 );
				if ( $density > SEO_Validator::KEYWORD_DENSITY_MAX ) {
					$keyword_stuffing_flag = 1;
					$issues[]              = [
						'type'     => 'keyword_stuffing',
						'severity' => 'critical',
						'penalty'  => 20.0,
						'message'  => sprintf( 'Keyword density for "%s" is %.2f%%, exceeding safe maximum of %.1f%%.', $primary_kw, $density, SEO_Validator::KEYWORD_DENSITY_MAX ),
					];
				} elseif ( $density < SEO_Validator::KEYWORD_DENSITY_MIN && $density > 0 ) {
					$issues[] = [
						'type'     => 'low_keyword_density',
						'severity' => 'info',
						'penalty'  => 4.0,
						'message'  => sprintf( 'Keyword density for "%s" is %.2f%% (recommended %.1f%% - %.1f%%).', $primary_kw, $density, SEO_Validator::KEYWORD_DENSITY_MIN, SEO_Validator::KEYWORD_DENSITY_MAX ),
					];
				}
			}
		}

		// -------------------------------------------------------------
		// Dimension 11: Internal Linking & Orphan Detection
		// -------------------------------------------------------------
		$link_analysis       = Internal_Link_Analyzer::analyze( $page_id, $content, $page_data );
		$internal_link_count = (int) $link_analysis['link_count'];
		$is_orphan           = ! empty( $link_analysis['is_orphan'] ) ? 1 : 0;
		$suggestions         = $link_analysis['suggestions'] ?? [];
		if ( ! empty( $link_analysis['issues'] ) ) {
			foreach ( $link_analysis['issues'] as $li ) {
				$li['penalty'] = 5.0;
				$issues[]      = $li;
			}
		}

		// -------------------------------------------------------------
		// Dimension 12: SEO Metadata Quality
		// -------------------------------------------------------------
		$missing_seo_metadata = 0;
		$seo_title            = (string) ( $page_data['seo_title'] ?? '' );
		$meta_desc            = (string) ( $page_data['meta_description'] ?? $page_data['seo_description'] ?? '' );
		if ( empty( $seo_title ) && empty( $meta_desc ) ) {
			$missing_seo_metadata = 1;
			$issues[]             = [
				'type'     => 'missing_seo_metadata',
				'severity' => 'info',
				'penalty'  => 5.0,
				'message'  => 'Using template-derived fallback for SEO title and meta description.',
			];
		}

		// -------------------------------------------------------------
		// Filter Dismissed Issues & Calculate Active Score
		// -------------------------------------------------------------
		$active_issues       = [];
		$active_critical_cnt = 0;
		$calculated_score    = 100.0;
		foreach ( $issues as $iss ) {
			if ( ! Quality_Repository::is_dismissed( $page_id, $iss['type'] ) ) {
				$active_issues[]   = $iss;
				$calculated_score -= (float) ( $iss['penalty'] ?? 5.0 );
				if ( isset( $iss['severity'] ) && 'critical' === $iss['severity'] ) {
					$active_critical_cnt++;
				}
			}
		}

		// Final score bounding.
		$final_score = (int) max( 0, min( 100, round( $calculated_score ) ) );

		// Status determination.
		$active_major_cnt = 0;
		foreach ( $active_issues as $iss ) {
			if ( isset( $iss['severity'] ) && in_array( $iss['severity'], [ 'major', 'critical' ], true ) ) {
				$active_major_cnt++;
			}
		}

		if ( $active_critical_cnt > 0 || $final_score < 40 ) {
			$quality_status = Quality_Repository::STATUS_FAIL;
		} elseif ( $active_major_cnt > 0 || $final_score < 65 ) {
			$quality_status = Quality_Repository::STATUS_NEEDS_REVIEW;
		} elseif ( $final_score < 85 ) {
			$quality_status = Quality_Repository::STATUS_WARNING;
		} else {
			$quality_status = Quality_Repository::STATUS_PASS;
		}

		$record = [
			'page_id'                  => $page_id,
			'version_id'               => $version_id,
			'quality_score'            => $final_score,
			'quality_status'           => $quality_status,
			'completeness_score'       => $completeness_score,
			'thin_content_flag'        => $thin_content_flag,
			'similarity_score'         => $similarity_score,
			'max_similar_page_id'      => $max_similar_page_id,
			'city_relevance_score'     => $city_relevance_score,
			'service_relevance_score'  => $service_relevance_score,
			'intent_overlap_flag'      => $intent_overlap_flag,
			'overlap_page_id'          => $overlap_page_id,
			'placeholder_issues_count' => $placeholder_issues_count,
			'unsupported_claims_count' => $unsupported_claims_count,
			'cliche_count'             => $cliche_count,
			'keyword_stuffing_flag'    => $keyword_stuffing_flag,
			'internal_link_count'      => $internal_link_count,
			'is_orphan'                => $is_orphan,
			'missing_seo_metadata'     => $missing_seo_metadata,
			'issues_json'              => $active_issues,
			'suggestions_json'         => $suggestions,
			'is_stale'                 => 0,
			'evaluated_at'             => current_time( 'mysql' ),
		];

		Quality_Repository::save_evaluation( $record );

		return $record;
	}
}
