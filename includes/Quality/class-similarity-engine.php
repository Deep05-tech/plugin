<?php
/**
 * Content Similarity Engine.
 *
 * Provides deterministic content fingerprinting, n-gram shingling, token normalization,
 * and Jaccard similarity computation across city pages.
 *
 * @package SeoCityPages\Quality
 */

namespace SeoCityPages\Quality;

use SeoCityPages\Database\Quality_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Similarity_Engine Class.
 */
class Similarity_Engine {

	/**
	 * Stopwords for normalization.
	 */
	private const STOPWORDS = [
		'a', 'about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and', 'any', 'are',
		'as', 'at', 'be', 'because', 'been', 'before', 'being', 'below', 'between', 'both', 'but',
		'by', 'could', 'did', 'do', 'does', 'doing', 'down', 'during', 'each', 'few', 'for', 'from',
		'further', 'had', 'has', 'have', 'having', 'he', 'her', 'here', 'hers', 'herself', 'him',
		'himself', 'his', 'how', 'i', 'if', 'in', 'into', 'is', 'it', 'its', 'itself', 'just', 'me',
		'more', 'most', 'my', 'myself', 'no', 'nor', 'not', 'now', 'of', 'off', 'on', 'once', 'only',
		'or', 'other', 'our', 'ours', 'ourselves', 'out', 'over', 'own', 'same', 'she', 'should',
		'so', 'some', 'such', 'than', 'that', 'the', 'their', 'theirs', 'them', 'themselves', 'then',
		'there', 'these', 'they', 'this', 'those', 'through', 'to', 'too', 'under', 'until', 'up',
		'very', 'was', 'we', 'were', 'what', 'when', 'where', 'which', 'while', 'who', 'whom',
		'why', 'with', 'would', 'you', 'your', 'yours', 'yourself', 'yourselves',
	];

	/**
	 * Extract flat text representation from content array or string.
	 *
	 * @param array|string $content Structured content or raw string.
	 * @return string
	 */
	public static function extract_text( $content ): string {
		$strip = function( $str ) {
			return function_exists( '\\wp_strip_all_tags' ) ? \wp_strip_all_tags( (string) $str ) : ( function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( (string) $str ) : strip_tags( (string) $str ) );
		};

		if ( is_string( $content ) ) {
			return $strip( $content );
		}

		if ( ! is_array( $content ) ) {
			return '';
		}

		$chunks = [];
		array_walk_recursive(
			$content,
			function( $value ) use ( &$chunks ) {
				if ( is_scalar( $value ) ) {
					$chunks[] = (string) $value;
				}
			}
		);

		return $strip( implode( ' ', $chunks ) );
	}

	/**
	 * Normalize text by lowering case, removing punctuation, stripping stopwords,
	 * and replacing location/service tokens with placeholder tokens.
	 *
	 * @param string $text Raw text.
	 * @param string $city City name to mask.
	 * @param string $state State name to mask.
	 * @param string $service Service name to mask.
	 * @return string
	 */
	public static function normalize_text( string $text, string $city = '', string $state = '', string $service = '' ): string {
		$text = mb_strtolower( $text, 'UTF-8' );

		// Mask entities so template duplicates are detected even if city/service was substituted.
		if ( ! empty( $city ) ) {
			$text = str_replace( mb_strtolower( $city, 'UTF-8' ), '__city__', $text );
		}
		if ( ! empty( $state ) ) {
			$text = str_replace( mb_strtolower( $state, 'UTF-8' ), '__state__', $text );
		}
		if ( ! empty( $service ) ) {
			$text = str_replace( mb_strtolower( $service, 'UTF-8' ), '__service__', $text );
		}

		// Replace non-alphanumeric characters with space.
		$text = preg_replace( '/[^\p{L}\p{N}_]+/u', ' ', $text );

		// Split into words and filter stopwords.
		$words = preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
		$filtered = array_filter(
			$words,
			function( $w ) {
				return ! in_array( $w, self::STOPWORDS, true ) && mb_strlen( $w ) > 1;
			}
		);

		return implode( ' ', $filtered );
	}

	/**
	 * Generate word-based n-grams (shingles).
	 *
	 * @param string $normalized_text Normalized text.
	 * @param int    $n Shingle size (default 3-gram words).
	 * @return array Array of unique shingles.
	 */
	public static function generate_shingles( string $normalized_text, int $n = 3 ): array {
		$words = preg_split( '/\s+/u', trim( $normalized_text ), -1, PREG_SPLIT_NO_EMPTY );
		$count = count( $words );

		if ( $count < $n ) {
			return $words ? [ implode( ' ', $words ) ] : [];
		}

		$shingles = [];
		for ( $i = 0; $i <= $count - $n; $i++ ) {
			$shingle = implode( ' ', array_slice( $words, $i, $n ) );
			$shingles[ md5( $shingle ) ] = true;
		}

		return array_keys( $shingles );
	}

	/**
	 * Calculate Jaccard similarity between two sets of shingles.
	 * Returns similarity percentage (0.0 to 100.0).
	 *
	 * @param array $shingles_a Shingle set A.
	 * @param array $shingles_b Shingle set B.
	 * @return float
	 */
	public static function calculate_jaccard_similarity( array $shingles_a, array $shingles_b ): float {
		if ( empty( $shingles_a ) || empty( $shingles_b ) ) {
			return 0.0;
		}

		$flip_a = array_flip( $shingles_a );
		$flip_b = array_flip( $shingles_b );

		$intersection_count = 0;
		foreach ( $flip_a as $k => $_ ) {
			if ( isset( $flip_b[ $k ] ) ) {
				$intersection_count++;
			}
		}

		$union_count = count( $flip_a ) + count( $flip_b ) - $intersection_count;
		if ( $union_count <= 0 ) {
			return 0.0;
		}

		return round( ( $intersection_count / $union_count ) * 100.0, 2 );
	}

	/**
	 * Generate a complete fingerprint for a page's content.
	 *
	 * @param array|string $content Content.
	 * @param string       $city City.
	 * @param string       $state State.
	 * @param string       $service Service.
	 * @return array
	 */
	public static function create_fingerprint( $content, string $city = '', string $state = '', string $service = '' ): array {
		$raw_text        = self::extract_text( $content );
		$normalized_text = self::normalize_text( $raw_text, $city, $state, $service );
		$words           = preg_split( '/\s+/u', trim( $raw_text ), -1, PREG_SPLIT_NO_EMPTY );
		$word_count      = count( $words );
		$shingles        = self::generate_shingles( $normalized_text, 3 );
		$normalized_hash = hash( 'sha256', $normalized_text );

		return [
			'raw_text'        => $raw_text,
			'normalized_text' => $normalized_text,
			'normalized_hash' => $normalized_hash,
			'shingles'        => $shingles,
			'word_count'      => $word_count,
		];
	}

	/**
	 * Analyze similarity of a target page against candidate pages in the database.
	 *
	 * @param int          $page_id Page ID.
	 * @param int          $service_id Service ID.
	 * @param int          $state_id State ID.
	 * @param array|string $content Content.
	 * @param string       $city City name.
	 * @param string       $state State name.
	 * @param string       $service Service name.
	 * @return array [ 'max_similarity' => float, 'max_similar_page_id' => int, 'fingerprint' => array ]
	 */
	public static function analyze_similarity( int $page_id, int $service_id, int $state_id, $content, string $city = '', string $state = '', string $service = '' ): array {
		$fingerprint = self::create_fingerprint( $content, $city, $state, $service );

		// Save fingerprint into database.
		Quality_Repository::save_fingerprint( [
			'page_id'         => $page_id,
			'version_id'      => 0,
			'service_id'      => $service_id,
			'state_id'        => $state_id,
			'normalized_hash' => $fingerprint['normalized_hash'],
			'shingles_json'   => $fingerprint['shingles'],
			'word_count'      => $fingerprint['word_count'],
		] );

		$candidates = Quality_Repository::get_candidate_fingerprints( $service_id, $state_id, $page_id, 200 );

		$max_sim     = 0.0;
		$max_page_id = 0;

		foreach ( $candidates as $cand ) {
			// Fast path: identical normalized hash = 100% similarity.
			if ( ! empty( $cand['normalized_hash'] ) && $cand['normalized_hash'] === $fingerprint['normalized_hash'] ) {
				$max_sim     = 100.0;
				$max_page_id = (int) $cand['page_id'];
				break;
			}

			$cand_shingles = ! empty( $cand['shingles'] ) ? $cand['shingles'] : [];
			$sim = self::calculate_jaccard_similarity( $fingerprint['shingles'], $cand_shingles );

			if ( $sim > $max_sim ) {
				$max_sim     = $sim;
				$max_page_id = (int) $cand['page_id'];
			}
		}

		return [
			'max_similarity'      => $max_sim,
			'max_similar_page_id' => $max_page_id,
			'fingerprint'         => $fingerprint,
		];
	}
}
