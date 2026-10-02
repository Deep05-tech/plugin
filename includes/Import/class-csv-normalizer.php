<?php
/**
 * CSV Value Normalizer.
 *
 * @package SeoCityPages\Import
 */

namespace SeoCityPages\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Normalizer Class.
 */
class CSV_Normalizer {

	/**
	 * Normalize string for key comparison (trim, collapse internal whitespace, lowercase).
	 *
	 * @param string $value Input value.
	 * @return string Normalized key.
	 */
	public static function normalize_key( string $value ): string {
		// Strip UTF-8 BOM if present.
		$value = preg_replace( '/^\xEF\xBB\xBF/', '', $value );
		// Trim and collapse whitespace.
		$value = trim( $value );
		$value = preg_replace( '/\s+/', ' ', $value );
		return strtolower( $value );
	}

	/**
	 * Normalize display name for clean storage without destroying human formatting.
	 *
	 * @param string $value Raw CSV input.
	 * @return string Canonical human-readable name.
	 */
	public static function canonical_name( string $value ): string {
		$value = preg_replace( '/^\xEF\xBB\xBF/', '', $value );
		$value = trim( $value );
		// Replace multiple tabs/spaces with single space.
		$value = preg_replace( '/\s+/', ' ', $value );
		return sanitize_text_field( $value );
	}

	/**
	 * Build normalized composite row key for duplicate comparison.
	 *
	 * @param string $country Country name.
	 * @param string $state   State name.
	 * @param string $city    City name.
	 * @param string $service Service name.
	 * @return string Composite comparison key.
	 */
	public static function row_signature( string $country, string $state, string $city, string $service ): string {
		return self::normalize_key( $country ) . '|' .
			self::normalize_key( $state ) . '|' .
			self::normalize_key( $city ) . '|' .
			self::normalize_key( $service );
	}
}
