<?php
/**
 * Deterministic URL Generation & Validation Engine.
 *
 * @package SeoCityPages\URL
 */

namespace SeoCityPages\URL;

use SeoCityPages\Database\Schema;
use SeoCityPages\Admin\Settings;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL Engine Class.
 */
class URL_Engine {

	/**
	 * Default fallback URL structure pattern.
	 */
	public const DEFAULT_PATTERN = '/{service}/{city}/';

	/**
	 * Supported replacement tokens.
	 */
	public const SUPPORTED_TOKENS = [
		'{country}',
		'{state}',
		'{city}',
		'{service}',
	];

	/**
	 * Retrieve currently configured URL pattern.
	 *
	 * @return string
	 */
	public static function get_pattern(): string {
		$settings = get_option( Settings::OPTION_NAME, [] );
		$pattern  = $settings['url_pattern'] ?? self::DEFAULT_PATTERN;

		return ! empty( $pattern ) ? (string) $pattern : self::DEFAULT_PATTERN;
	}

	/**
	 * Validate a user-submitted URL pattern.
	 *
	 * @param string $pattern Input pattern string.
	 * @return array ['valid' => bool, 'error' => string]
	 */
	public static function validate_pattern( string $pattern ): array {
		$pattern = trim( $pattern );

		if ( empty( $pattern ) ) {
			return [
				'valid' => false,
				'error' => __( 'URL pattern cannot be empty.', 'seo-city-pages' ),
			];
		}

		// Reject protocols, query strings, fragments, path traversal, HTML tags, PHP tags.
		if ( preg_match( '#(https?://|ftp://|\.\.|\?|\#|<|>|"|\'|<\?|\?>|;|javascript:)#i', $pattern ) ) {
			return [
				'valid' => false,
				'error' => __( 'URL pattern contains invalid or forbidden characters (protocols, HTML/PHP tags, query params, fragments, or executable syntax are not allowed).', 'seo-city-pages' ),
			];
		}

		// Extract all {token} occurrences.
		preg_match_all( '/\{[a-zA-Z0-9_-]+\}/', $pattern, $matches );
		$found_tokens = $matches[0] ?? [];

		if ( empty( $found_tokens ) ) {
			return [
				'valid' => false,
				'error' => __( 'URL pattern must contain at least one dynamic token such as {city} and {service}.', 'seo-city-pages' ),
			];
		}

		foreach ( $found_tokens as $tok ) {
			if ( ! in_array( $tok, self::SUPPORTED_TOKENS, true ) ) {
				return [
					'valid' => false,
					'error' => sprintf(
						/* translators: %s: Invalid token */
						__( 'Unsupported token "%s". Only {country}, {state}, {city}, and {service} are supported.', 'seo-city-pages' ),
						esc_html( $tok )
					),
				];
			}
		}

		return [
			'valid' => true,
			'error' => '',
		];
	}

	/**
	 * Build normalized deterministic path for a page combination.
	 *
	 * @param object|array $country  Country record.
	 * @param object|array $state    State record.
	 * @param object|array $city     City record.
	 * @param object|array $service  Service record.
	 * @param string       $custom_slug Optional custom slug override.
	 * @param string       $pattern_override Optional pattern override.
	 * @return string Normalized relative path e.g. "/seo-agency/rajkot/"
	 */
	public static function build_path( $country, $state, $city, $service, string $custom_slug = '', string $pattern_override = '' ): string {
		$country_slug = self::extract_slug( $country, 'country' );
		$state_slug   = self::extract_slug( $state, 'state' );
		$city_slug    = self::extract_slug( $city, 'city' );
		$service_slug = self::extract_slug( $service, 'service' );

		// If custom slug is defined, use custom slug as the route path directly.
		if ( ! empty( $custom_slug ) ) {
			$clean_custom = trim( sanitize_title( $custom_slug ), '/' );
			return '/' . $clean_custom . '/';
		}

		$pattern = ! empty( $pattern_override ) ? $pattern_override : self::get_pattern();

		$path = str_replace(
			[ '{country}', '{state}', '{city}', '{service}' ],
			[ $country_slug, $state_slug, $city_slug, $service_slug ],
			$pattern
		);

		// Clean up multiple slashes and wrap with leading/trailing slashes.
		$path = '/' . trim( preg_replace( '#/+#', '/', $path ), '/' ) . '/';

		return strtolower( $path );
	}

	/**
	 * Helper to extract slug from object or array.
	 *
	 * @param object|array $entity Entity record.
	 * @param string       $default Default slug.
	 * @return string
	 */
	private static function extract_slug( $entity, string $default ): string {
		if ( is_object( $entity ) && ! empty( $entity->slug ) ) {
			return sanitize_title( $entity->slug );
		}
		if ( is_array( $entity ) && ! empty( $entity['slug'] ) ) {
			return sanitize_title( $entity['slug'] );
		}
		if ( is_object( $entity ) && ! empty( $entity->name ) ) {
			return sanitize_title( $entity->name );
		}
		if ( is_array( $entity ) && ! empty( $entity['name'] ) ) {
			return sanitize_title( $entity['name'] );
		}
		return $default;
	}

	/**
	 * Build full absolute public URL from relative path.
	 *
	 * @param string $url_path Relative path.
	 * @return string Absolute URL.
	 */
	public static function build_full_url( string $url_path ): string {
		$clean_path = ltrim( $url_path, '/' );
		return home_url( '/' . $clean_path );
	}

	/**
	 * Build secure admin preview URL with signed nonce.
	 *
	 * @param int $page_id Page record ID.
	 * @return string
	 */
	public static function build_preview_url( int $page_id ): string {
		$nonce = Security::create_nonce( 'scp_preview_' . $page_id );

		return add_query_arg(
			[
				'scp_preview_page'  => $page_id,
				'_scp_preview_nonce' => $nonce,
			],
			home_url( '/' )
		);
	}

	/**
	 * Check if a given URL path collides with an existing SEO City Page.
	 *
	 * @param string $url_path URL path to test.
	 * @param int    $exclude_id Optional page ID to exclude (for updates).
	 * @return bool True if collision exists, false otherwise.
	 */
	public static function check_collision( string $url_path, int $exclude_id = 0 ): bool {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$sql = "SELECT id FROM {$table} WHERE url_path = %s";
		$params = [ $url_path ];

		if ( $exclude_id > 0 ) {
			$sql .= ' AND id != %d';
			$params[] = $exclude_id;
		}

		$sql .= ' LIMIT 1';

		$found_id = $wpdb->get_var(
			$wpdb->prepare( $sql, ...$params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return ! empty( $found_id );
	}
}
