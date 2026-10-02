<?php
/**
 * AI Generated Content Validator & Sanitizer.
 *
 * Enforces schema integrity, anti-hallucination checks, placeholder detection,
 * and HTML sanitization on all raw model responses.
 *
 * @package SeoCityPages\AI\Schema
 */

namespace SeoCityPages\AI\Schema;

use SeoCityPages\AI\AI_Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content_Validator Class.
 */
class Content_Validator {

	/**
	 * Forbidden placeholder patterns.
	 */
	private const PLACEHOLDER_PATTERNS = [
		'/\[city\]/i',
		'/\[service\]/i',
		'/\[company\]/i',
		'/\[state\]/i',
		'/\[insert\s+[^\]]+\]/i',
		'/\{city\}/i',
		'/\{service\}/i',
		'/\{company\}/i',
		'/\bYOUR COMPANY\b/i',
		'/\bCOMPANY NAME\b/i',
		'/\bINSERT TEXT\b/i',
		'/\blorem ipsum\b/i',
	];

	/**
	 * Forbidden meta/AI leakage patterns.
	 */
	private const AI_LEAKAGE_PATTERNS = [
		'/as an ai language model/i',
		'/as an ai\b/i',
		'/here is the (generated )?content/i',
		'/certainly! here/i',
		'/sure, here is/i',
		'/i have generated/i',
	];

	/**
	 * Validate and sanitize generated content.
	 *
	 * @param array|string $raw_content Raw content array or JSON string.
	 * @param AI_Context   $context     Page context for identity verification.
	 * @return array ['valid' => bool, 'sanitized' => array|null, 'errors' => array]
	 */
	public static function validate( $raw_content, AI_Context $context ): array {
		$errors = [];

		if ( is_string( $raw_content ) ) {
			$data = json_decode( $raw_content, true );
			if ( ! is_array( $data ) ) {
				return [
					'valid'     => false,
					'sanitized' => null,
					'errors'    => [ 'JSON decoding error: Output is not valid JSON.' ],
				];
			}
		} elseif ( is_array( $raw_content ) ) {
			$data = $raw_content;
		} else {
			return [
				'valid'     => false,
				'sanitized' => null,
				'errors'    => [ 'Invalid payload type.' ],
			];
		}

		// 1. Check Required Top-Level Schema Keys.
		$required_keys = [
			'hero',
			'introduction',
			'service_overview',
			'benefits',
			'process',
			'local_relevance',
			'faqs',
			'final_cta',
		];

		foreach ( $required_keys as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				$errors[] = sprintf( 'Missing required section: "%s"', $key );
			}
		}

		if ( ! empty( $errors ) ) {
			return [ 'valid' => false, 'sanitized' => null, 'errors' => $errors ];
		}

		// 2. Validate & Sanitize Hero Section.
		if ( ! is_array( $data['hero'] ) || empty( $data['hero']['title'] ) || empty( $data['hero']['description'] ) ) {
			$errors[] = 'Hero section must contain non-empty "title" and "description".';
		}

		// 3. Validate & Sanitize Introduction.
		if ( ! is_array( $data['introduction'] ) || empty( $data['introduction']['heading'] ) || empty( $data['introduction']['body'] ) ) {
			$errors[] = 'Introduction section must contain non-empty "heading" and "body".';
		}

		// 4. Validate & Sanitize Service Overview.
		if ( ! is_array( $data['service_overview'] ) || empty( $data['service_overview']['heading'] ) || empty( $data['service_overview']['body'] ) ) {
			$errors[] = 'Service Overview section must contain non-empty "heading" and "body".';
		}

		// 5. Validate Benefits Array (3-6 items).
		if ( ! is_array( $data['benefits'] ) || count( $data['benefits'] ) < 2 || count( $data['benefits'] ) > 8 ) {
			$errors[] = 'Benefits must be an array of between 2 and 8 items.';
		}

		// 6. Validate Process Array (3-6 items).
		if ( ! is_array( $data['process'] ) || count( $data['process'] ) < 2 || count( $data['process'] ) > 8 ) {
			$errors[] = 'Process must be an array of between 2 and 8 steps.';
		}

		// 7. Validate Local Relevance.
		if ( ! is_array( $data['local_relevance'] ) || empty( $data['local_relevance']['heading'] ) || empty( $data['local_relevance']['body'] ) ) {
			$errors[] = 'Local Relevance section must contain non-empty "heading" and "body".';
		}

		// 8. Validate FAQs Array (3-8 items).
		if ( ! is_array( $data['faqs'] ) || count( $data['faqs'] ) < 2 || count( $data['faqs'] ) > 10 ) {
			$errors[] = 'FAQs must be an array of between 2 and 10 items.';
		}

		// 9. Validate Final CTA.
		if ( ! is_array( $data['final_cta'] ) || empty( $data['final_cta']['heading'] ) || empty( $data['final_cta']['description'] ) ) {
			$errors[] = 'Final CTA section must contain non-empty "heading" and "description".';
		}

		if ( ! empty( $errors ) ) {
			return [ 'valid' => false, 'sanitized' => null, 'errors' => $errors ];
		}

		// 10. Deep Content Checks (Placeholders, AI Leakage, Identity Verification).
		$full_text = wp_json_encode( $data );

		foreach ( self::PLACEHOLDER_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, $full_text ) ) {
				$errors[] = sprintf( 'Unreplaced placeholder detected matching pattern %s', $pattern );
				break;
			}
		}

		foreach ( self::AI_LEAKAGE_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, $full_text ) ) {
				$errors[] = 'Detected AI meta-commentary or conversational leakage.';
				break;
			}
		}

		// Verify City name is referenced naturally.
		$city = $context->get_city();
		if ( ! empty( $city ) && false === stripos( $full_text, $city ) ) {
			$errors[] = sprintf( 'Generated content failed identity check: Target city "%s" is missing from content.', $city );
		}

		// Verify Service name is referenced naturally.
		$service = $context->get_service();
		if ( ! empty( $service ) && false === stripos( $full_text, $service ) ) {
			$errors[] = sprintf( 'Generated content failed identity check: Target service "%s" is missing from content.', $service );
		}

		if ( ! empty( $errors ) ) {
			return [ 'valid' => false, 'sanitized' => null, 'errors' => $errors ];
		}

		// 11. Sanitize Data Tree with Safe Allowlist.
		$sanitized = self::sanitize_content_tree( $data );

		return [
			'valid'     => true,
			'sanitized' => $sanitized,
			'errors'    => [],
		];
	}

	/**
	 * Recursively sanitize all fields in content array.
	 *
	 * @param array $data Raw data array.
	 * @return array Sanitized array.
	 */
	private static function sanitize_content_tree( array $data ): array {
		$sanitized = [];

		foreach ( $data as $key => $val ) {
			$clean_key = sanitize_key( $key );
			if ( is_array( $val ) ) {
				$sanitized[ $clean_key ] = self::sanitize_content_tree( $val );
			} elseif ( is_string( $val ) ) {
				// Strip executable scripts while preserving safe formatting tags.
				$sanitized[ $clean_key ] = wp_kses_post( trim( $val ) );
			} elseif ( is_int( $val ) || is_float( $val ) ) {
				$sanitized[ $clean_key ] = $val;
			} elseif ( is_bool( $val ) ) {
				$sanitized[ $clean_key ] = (bool) $val;
			}
		}

		return $sanitized;
	}
}
