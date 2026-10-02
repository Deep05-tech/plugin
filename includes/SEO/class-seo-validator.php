<?php
/**
 * SEO Validator & Quality Inspector.
 *
 * @package SeoCityPages\SEO
 */

namespace SeoCityPages\SEO;

use SeoCityPages\Database\City_Page_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEO_Validator.
 */
class SEO_Validator {

	public const TITLE_MIN_RECOMMENDED = 20;
	public const TITLE_MAX_RECOMMENDED = 60;

	public const DESC_MIN_RECOMMENDED = 50;
	public const DESC_MAX_RECOMMENDED = 160;

	/**
	 * Validate and analyze SEO metadata payload for a page.
	 *
	 * @param array $seo_data       SEO fields array.
	 * @param int   $page_id        Optional current page ID.
	 * @param string $page_url_path Optional current page URL path.
	 * @return array Validation report containing 'valid', 'warnings', 'errors', 'metrics'.
	 */
	public static function validate( array $seo_data, int $page_id = 0, string $page_url_path = '' ): array {
		$errors   = [];
		$warnings = [];

		$title       = trim( $seo_data['seo_title'] ?? '' );
		$description = trim( $seo_data['meta_description'] ?? '' );
		$canonical   = trim( $seo_data['canonical_url'] ?? '' );

		// 1. Title validation.
		$title_len = mb_strlen( $title );
		if ( 0 === $title_len ) {
			$warnings[] = __( 'SEO Title is empty. Falling back to template/default.', 'seo-city-pages' );
		} elseif ( $title_len > self::TITLE_MAX_RECOMMENDED ) {
			$warnings[] = sprintf(
				/* translators: %d: current length, %d: max recommended */
				__( 'SEO Title is %1$d characters (recommended max is %2$d). It may be truncated in search results.', 'seo-city-pages' ),
				$title_len,
				self::TITLE_MAX_RECOMMENDED
			);
		} elseif ( $title_len < self::TITLE_MIN_RECOMMENDED ) {
			$warnings[] = sprintf(
				/* translators: %d: current length, %d: min recommended */
				__( 'SEO Title is short (%1$d characters, recommended min is %2$d).', 'seo-city-pages' ),
				$title_len,
				self::TITLE_MIN_RECOMMENDED
			);
		}

		// 2. Meta Description validation.
		$desc_len = mb_strlen( $description );
		if ( 0 === $desc_len ) {
			$warnings[] = __( 'Meta Description is empty. Falling back to template/default.', 'seo-city-pages' );
		} elseif ( $desc_len > self::DESC_MAX_RECOMMENDED ) {
			$warnings[] = sprintf(
				/* translators: %d: current length, %d: max recommended */
				__( 'Meta Description is %1$d characters (recommended max is %2$d). It may be truncated on search snippets.', 'seo-city-pages' ),
				$desc_len,
				self::DESC_MAX_RECOMMENDED
			);
		} elseif ( $desc_len < self::DESC_MIN_RECOMMENDED ) {
			$warnings[] = sprintf(
				/* translators: %d: current length, %d: min recommended */
				__( 'Meta Description is short (%1$d characters, recommended min is %2$d).', 'seo-city-pages' ),
				$desc_len,
				self::DESC_MIN_RECOMMENDED
			);
		}

		// 3. Canonical URL validation.
		if ( ! empty( $canonical ) ) {
			if ( ! filter_var( $canonical, FILTER_VALIDATE_URL ) ) {
				$errors[] = __( 'Canonical URL is not a valid well-formed URL.', 'seo-city-pages' );
			} else {
				$scheme = wp_parse_url( $canonical, PHP_URL_SCHEME );
				if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
					$errors[] = __( 'Canonical URL must use http or https protocol.', 'seo-city-pages' );
				}
			}
		}

		// 4. Duplicate checks if database lookup is available.
		if ( ! empty( $title ) && class_exists( City_Page_Repository::class ) ) {
			$duplicate_titles = City_Page_Repository::find_duplicate_seo_titles( $title, $page_id );
			if ( ! empty( $duplicate_titles ) ) {
				$warnings[] = sprintf(
					/* translators: %d: count of duplicates */
					__( 'Duplicate SEO Title detected: %d other page(s) share this exact title.', 'seo-city-pages' ),
					count( $duplicate_titles )
				);
			}
		}

		if ( ! empty( $description ) && class_exists( City_Page_Repository::class ) ) {
			$duplicate_descs = City_Page_Repository::find_duplicate_meta_descriptions( $description, $page_id );
			if ( ! empty( $duplicate_descs ) ) {
				$warnings[] = sprintf(
					/* translators: %d: count of duplicates */
					__( 'Duplicate Meta Description detected: %d other page(s) share this exact description.', 'seo-city-pages' ),
					count( $duplicate_descs )
				);
			}
		}

		return [
			'valid'    => empty( $errors ),
			'errors'   => $errors,
			'warnings' => $warnings,
			'metrics'  => [
				'title_length'       => $title_len,
				'description_length' => $desc_len,
			],
		];
	}

	/**
	 * Sanitize raw SEO inputs for saving.
	 *
	 * @param array $raw_data Raw input.
	 * @return array Sanitized fields.
	 */
	public static function sanitize( array $raw_data ): array {
		$sanitized = [];

		if ( isset( $raw_data['seo_title'] ) ) {
			$sanitized['seo_title'] = sanitize_text_field( $raw_data['seo_title'] );
		}

		if ( isset( $raw_data['meta_description'] ) ) {
			$sanitized['meta_description'] = sanitize_textarea_field( $raw_data['meta_description'] );
		}

		if ( isset( $raw_data['canonical_url'] ) ) {
			$sanitized['canonical_url'] = ! empty( $raw_data['canonical_url'] ) ? esc_url_raw( trim( $raw_data['canonical_url'] ) ) : null;
		}

		if ( isset( $raw_data['robots_index'] ) ) {
			$index = strtolower( trim( $raw_data['robots_index'] ) );
			$sanitized['robots_index'] = in_array( $index, [ 'index', 'noindex', 'default' ], true ) ? $index : 'default';
		}

		if ( isset( $raw_data['robots_follow'] ) ) {
			$follow = strtolower( trim( $raw_data['robots_follow'] ) );
			$sanitized['robots_follow'] = in_array( $follow, [ 'follow', 'nofollow', 'default' ], true ) ? $follow : 'default';
		}

		if ( isset( $raw_data['robots_advanced'] ) ) {
			$sanitized['robots_advanced'] = sanitize_text_field( $raw_data['robots_advanced'] );
		}

		if ( isset( $raw_data['og_title'] ) ) {
			$sanitized['og_title'] = sanitize_text_field( $raw_data['og_title'] );
		}

		if ( isset( $raw_data['og_description'] ) ) {
			$sanitized['og_description'] = sanitize_textarea_field( $raw_data['og_description'] );
		}

		if ( isset( $raw_data['og_image'] ) ) {
			$sanitized['og_image'] = ! empty( $raw_data['og_image'] ) ? esc_url_raw( trim( $raw_data['og_image'] ) ) : null;
		}

		if ( isset( $raw_data['twitter_title'] ) ) {
			$sanitized['twitter_title'] = sanitize_text_field( $raw_data['twitter_title'] );
		}

		if ( isset( $raw_data['twitter_description'] ) ) {
			$sanitized['twitter_description'] = sanitize_textarea_field( $raw_data['twitter_description'] );
		}

		if ( isset( $raw_data['twitter_image'] ) ) {
			$sanitized['twitter_image'] = ! empty( $raw_data['twitter_image'] ) ? esc_url_raw( trim( $raw_data['twitter_image'] ) ) : null;
		}

		if ( isset( $raw_data['schema_type'] ) ) {
			$schema = sanitize_text_field( $raw_data['schema_type'] );
			$sanitized['schema_type'] = in_array( $schema, [ 'default', 'Service', 'LocalBusiness', 'ProfessionalService', 'Organization', 'none' ], true ) ? $schema : 'default';
		}

		if ( isset( $raw_data['is_manual_seo_override'] ) ) {
			$sanitized['is_manual_seo_override'] = ! empty( $raw_data['is_manual_seo_override'] ) ? 1 : 0;
		}

		return $sanitized;
	}

	public const KEYWORD_DENSITY_MIN = 1.5;
	public const KEYWORD_DENSITY_MAX = 3.0;

	/**
	 * Calculate keyword density for target phrases across content text or array.
	 *
	 * Density formula: (occurrences * keyword_word_count / total_words) * 100%
	 * Target standard: 1.5% to 3.0% (strict ceiling at 3.0%).
	 *
	 * @param string|array $content Full page text, HTML, or structured JSON content array.
	 * @param string|array $target_keywords Target keyword phrase or list of keywords.
	 * @return array Density breakdown with total words, occurrences, and compliance flags.
	 */
	public static function calculate_keyword_density( $content, $target_keywords ): array {
		if ( is_array( $content ) ) {
			$raw_text = self::flatten_content_to_text( $content );
		} else {
			$raw_text = function_exists( '\\wp_strip_all_tags' ) ? \wp_strip_all_tags( (string) $content ) : ( function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( (string) $content ) : strip_tags( (string) $content ) );
		}

		$normalized_text  = strtolower( trim( $raw_text ) );
		$clean_words_text = preg_replace( '/[^\p{L}\p{N}\s\'-]/u', ' ', $normalized_text );
		$words            = preg_split( '/\s+/', trim( (string) $clean_words_text ), -1, PREG_SPLIT_NO_EMPTY );
		$total_words      = count( $words ?: [] );

		if ( 0 === $total_words ) {
			return [
				'total_words'   => 0,
				'is_compliant'  => false,
				'keywords'      => [],
				'warnings'      => [ __( 'Content is empty or contains no words.', 'seo-city-pages' ) ],
			];
		}

		$keywords     = (array) $target_keywords;
		$results      = [
			'total_words'  => $total_words,
			'is_compliant' => true,
			'keywords'     => [],
			'warnings'     => [],
			'errors'       => [],
		];

		foreach ( $keywords as $kw ) {
			$clean_kw = strtolower( trim( (string) $kw ) );
			if ( empty( $clean_kw ) ) {
				continue;
			}

			$kw_words      = preg_split( '/\s+/', $clean_kw, -1, PREG_SPLIT_NO_EMPTY );
			$kw_word_count = count( $kw_words ?: [] );
			if ( 0 === $kw_word_count ) {
				continue;
			}

			// Count occurrences with regex word-boundary matching.
			$pattern = '/\b' . preg_quote( $clean_kw, '/' ) . '\b/iu';
			$count   = preg_match_all( $pattern, (string) $clean_words_text );

			$density         = ( ( $count * $kw_word_count ) / $total_words ) * 100;
			$density_rounded = round( $density, 2 );

			$is_compliant = ( $density_rounded >= self::KEYWORD_DENSITY_MIN && $density_rounded <= self::KEYWORD_DENSITY_MAX );

			$status = 'compliant';
			if ( $density_rounded > self::KEYWORD_DENSITY_MAX ) {
				$status = 'exceeds_max';
				$results['is_compliant'] = false;
				$results['errors'][] = sprintf(
					/* translators: 1: keyword phrase, 2: calculated density, 3: maximum limit */
					__( 'Keyword density for "%1$s" is %2$s%%, exceeding the strict %3$s%% maximum ceiling.', 'seo-city-pages' ),
					$kw,
					$density_rounded,
					self::KEYWORD_DENSITY_MAX
				);
			} elseif ( $density_rounded < self::KEYWORD_DENSITY_MIN ) {
				$status = 'below_min';
				$results['warnings'][] = sprintf(
					/* translators: 1: keyword phrase, 2: calculated density, 3: minimum recommendation */
					__( 'Keyword density for "%1$s" is %2$s%% (recommended minimum: %3$s%%).', 'seo-city-pages' ),
					$kw,
					$density_rounded,
					self::KEYWORD_DENSITY_MIN
				);
			}

			$results['keywords'][ $kw ] = [
				'occurrences'  => $count,
				'word_count'   => $kw_word_count,
				'density'      => $density_rounded,
				'status'       => $status,
				'is_compliant' => $is_compliant,
			];
		}

		return $results;
	}

	/**
	 * Extract readable text from structured content array.
	 *
	 * @param array $data Structured content.
	 * @return string
	 */
	public static function flatten_content_to_text( array $data ): string {
		$text_parts = [];
		array_walk_recursive( $data, function( $value, $key ) use ( &$text_parts ) {
			if ( is_string( $value ) && ! empty( $value ) && ! in_array( $key, [ 'status', 'provider', 'model', 'schema_version' ], true ) ) {
				$text_parts[] = $value;
			}
		} );
		return implode( ' ', $text_parts );
	}
}
