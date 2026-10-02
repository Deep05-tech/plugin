<?php
/**
 * Intelligent Keyword Sanitizer, NLP Normalizer & Search Intent Extractor.
 *
 * Cleans messy raw keyword exports (e.g., from Semrush, Ahrefs, GSC, or manual scrapes),
 * strips conversational fluff, biographical noise, personal brands, jumbled tokens,
 * and extracts clean, high-converting commercial search phrases.
 *
 * @package SeoCityPages\Import
 */

namespace SeoCityPages\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keyword_Sanitizer Class.
 */
class Keyword_Sanitizer {

	/**
	 * Common conversational tails & biographical noise patterns to strip.
	 */
	private const NOISE_PATTERNS = [
		'/\b(by\s+(a|an|the)?\s*.*)$/i',                     // e.g. "by a lawyer turned seo pro"
		'/\b(turned\s+.*)$/i',                               // e.g. "turned seo pro"
		'/\b(written\s+by\s+.*)$/i',                         // e.g. "written by expert"
		'/\b(with\s+(a|an|the)?\s*(proven|track|case).*)$/i',// e.g. "with a proven track record"
		'/\b(who\s+.*)$/i',                                  // e.g. "who knows marketing"
		'/\b(how\s+to\s+.*)$/i',                             // e.g. "how to do..."
		'/\b(step\s+by\s+step\s*.*)$/i',                     // e.g. "step by step guide"
		'/\b(guide\s+to\s+.*)$/i',                           // e.g. "guide to..."
		'/\b(tips\s+for\s+.*)$/i',                           // e.g. "tips for..."
		'/\b(near\s+me|in\s+my\s+area|around\s+me|close\s+to\s+me)\b/i', // Geo-noise stripped for dynamic city landing pages
		'/\b(reviews?|reddit|quora|wiki|wikipedia|jobs?|salary|careers?|login|portal|pricing|calculator)\b/i',
	];

	/**
	 * Inverted conversational query patterns to convert to clean commercial phrases.
	 */
	private const CONVERSATIONAL_REWRITES = [
		'/^(market|marketing)\s+(my|your|our|a)\s+(law\s+firm|lawyer|attorney|business|practice)/i' => '$3 marketing',
		'/^(advertising|advertise)\s+(my|your|our|a)\s+(law\s+firm|lawyer|attorney|business|practice)/i' => '$3 advertising',
		'/^for\s+(law\s+firms?|lawyers?|attorneys?)\s+only\s+(marketing|advertising|seo)/i' => 'law firm $2',
		'/^engine\s+(lawyer|attorney|law\s+firm)\s+marketing\s+search/i' => '$1 search engine marketing',
		'/^attorney\s+engine\s+marketing\s+search/i' => 'attorney search engine marketing',
		'/^engine\s+search\s+/i' => 'search engine ',
		'/^firm\s+in\s+marketing$/i' => 'law firm marketing',
		'/^market\s+law\s+firm$/i' => 'law firm marketing',
	];

	/**
	 * Known 3rd party tool/agency brand noise tokens to strip from generic search terms.
	 */
	private const KNOWN_BRAND_TOKENS = [
		'attorneysync',
		'scorpion',
		'juris digital',
		'coalition',
		'johnny chen',
		'johnny',
		'360',
		'inc',
		'llp',
		'ltd',
		'machine',
	];

	/**
	 * Clean, sanitize, and extract high-intent commercial keywords from a raw input string.
	 *
	 * @param string $raw_keyword Raw messy keyword phrase.
	 * @return string Cleaned keyword phrase, or empty string if invalid/garbage.
	 */
	public static function sanitize( string $raw_keyword ): string {
		// 1. Initial cleanup: remove HTML tags, UTF-8 BOM, tab characters, quotes.
		$str = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $raw_keyword );
		$str = strip_tags( $str );
		$str = html_entity_decode( $str, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$str = trim( $str, "\"'`\t\n\r\0\x0B" );

		// Remove special characters (keep alphanumeric, spaces, hyphens, and ampersands).
		$str = preg_replace( '/[^a-zA-Z0-9\s\-_&]/', ' ', $str );
		$str = preg_replace( '/\s+/', ' ', trim( $str ) );

		if ( empty( $str ) || mb_strlen( $str ) < 3 ) {
			return '';
		}

		// 2. Apply Conversational & Inverted Syntax Rewrites.
		foreach ( self::CONVERSATIONAL_REWRITES as $pattern => $replacement ) {
			if ( preg_match( $pattern, $str ) ) {
				$str = preg_replace( $pattern, $replacement, $str );
				break;
			}
		}

		// 3. Strip Biographical, Conversational & Narrative Noise Tails.
		foreach ( self::NOISE_PATTERNS as $pattern ) {
			$str = preg_replace( $pattern, '', $str );
		}

		// 4. Strip known brand appendages (e.g. "attorneysync", "scorpion", "johnny").
		foreach ( self::KNOWN_BRAND_TOKENS as $brand ) {
			$str = preg_replace( '/\b' . preg_quote( $brand, '/' ) . '\b/i', '', $str );
		}

		// 5. Deduplicate redundant repeated words (e.g. "seo marketing seo lawyer marketing" -> "lawyer seo marketing").
		$str = self::deduplicate_phrase_tokens( $str );

		// 6. Clean leading/trailing prepositions and conjunctions (e.g. "for", "and", "by", "in", "of", "to", "the", "a", "an").
		$str = preg_replace( '/^(for|and|by|in|of|to|the|a|an|with|from|about)\s+/i', '', trim( $str ) );
		$str = preg_replace( '/\s+(for|and|by|in|of|to|the|a|an|with|from|about)$/i', '', trim( $str ) );
		$str = preg_replace( '/\s+/', ' ', trim( $str ) );

		// 7. Validate minimum length and meaningful token count.
		if ( mb_strlen( $str ) < 3 ) {
			return '';
		}

		$words = explode( ' ', $str );
		if ( count( $words ) > 8 ) {
			// Limit to maximum 8 words for a clean search keyword phrase.
			$words = array_slice( $words, 0, 8 );
			$str   = implode( ' ', $words );
		}

		return strtolower( trim( $str ) );
	}

	/**
	 * Deduplicate repeated tokens and collapse redundant adjacent synonyms.
	 *
	 * @param string $phrase Phrase.
	 * @return string Deduplicated phrase.
	 */
	private static function deduplicate_phrase_tokens( string $phrase ): string {
		$words = preg_split( '/\s+/', trim( $phrase ) );
		if ( empty( $words ) ) {
			return '';
		}

		$cleaned_words = [];
		$seen = [];

		foreach ( $words as $w ) {
			$lower = strtolower( trim( $w ) );
			if ( empty( $lower ) ) {
				continue;
			}

			// Stop-words can appear more than once (e.g., "for", "and"), but core keywords should not repeat.
			$is_stopword = in_array( $lower, [ 'for', 'in', 'and', 'of', 'to', 'the', 'a', 'an' ], true );

			if ( ! $is_stopword && isset( $seen[ $lower ] ) ) {
				continue;
			}

			$cleaned_words[] = $lower;
			if ( ! $is_stopword ) {
				$seen[ $lower ] = true;
			}
		}

		return implode( ' ', $cleaned_words );
	}

	/**
	 * Process and sanitize a batch of raw keyword strings.
	 *
	 * @param array $raw_list Raw list of keywords.
	 * @return string[] Array of unique, sanitized keyword phrases.
	 */
	public static function sanitize_batch( array $raw_list ): array {
		$cleaned = [];
		$seen = [];

		foreach ( $raw_list as $raw ) {
			$sanitized = self::sanitize( (string) $raw );
			if ( ! empty( $sanitized ) && ! isset( $seen[ $sanitized ] ) ) {
				$seen[ $sanitized ] = true;
				$cleaned[] = $sanitized;
			}
		}

		return $cleaned;
	}
}
