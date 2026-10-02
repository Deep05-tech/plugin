<?php
/**
 * Safe Internal URL Health Checker with SSRF Defense.
 *
 * @package SeoCityPages\Diagnostics
 */

namespace SeoCityPages\Diagnostics;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Security\Security;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL Health Checker Class.
 */
class URL_Health_Checker {

	/**
	 * Inspect the HTTP health and markup of a specific SEO City Page URL safely.
	 *
	 * @param int|string $page_or_url City Page ID or relative URL path.
	 * @return array Diagnostic result.
	 */
	public static function check_page_url( $page_or_url ): array {
		$target_url = '';

		if ( is_numeric( $page_or_url ) ) {
			$page = City_Page_Repository::get( (int) $page_or_url );
			if ( ! $page ) {
				return [
					'success' => false,
					'error'   => __( 'Specified City Page record does not exist.', 'seo-city-pages' ),
				];
			}
			$target_url = home_url( $page->url_path );
		} else {
			$path       = sanitize_text_field( $page_or_url );
			$target_url = home_url( $path );
		}

		// Strict SSRF Validation: Guarantee target URL strictly matches site host.
		$is_safe = self::validate_target_url_ssrf( $target_url );
		if ( ! $is_safe ) {
			Logger::warning( 'SSRF attempt blocked by URL Health Checker.', [ 'url' => $target_url ], Logger::CAT_SECURITY );
			return [
				'success' => false,
				'error'   => __( 'SSRF Protection: URL does not belong to the allowed site domain.', 'seo-city-pages' ),
			];
		}

		$start_time = microtime( true );

		$response = wp_safe_remote_get(
			$target_url,
			[
				'timeout'     => 8,
				'redirection' => 3,
				'headers'     => [
					'User-Agent' => 'SEO-City-Pages-Diagnostics/1.0',
				],
			]
		);

		$duration_ms = (int) round( ( microtime( true ) - $start_time ) * 1000 );

		if ( is_wp_error( $response ) ) {
			return [
				'success'     => false,
				'url'         => esc_url( $target_url ),
				'duration_ms' => $duration_ms,
				'error'       => $response->get_error_message(),
				'status_code' => 0,
			];
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$html_body   = wp_remote_retrieve_body( $response );

		// Analyze basic SEO output tags from response.
		$has_title       = (bool) preg_match( '/<title>(.*?)<\/title>/is', $html_body, $title_match );
		$has_canonical   = (bool) preg_match( '/<link\s+rel=[\'"]canonical[\'"]\s+href=[\'"]([^\'"]+)[\'"]/is', $html_body, $canon_match );
		$has_robots      = (bool) preg_match( '/<meta\s+name=[\'"]robots[\'"]\s+content=[\'"]([^\'"]+)[\'"]/is', $html_body, $robots_match );
		$has_description = (bool) preg_match( '/<meta\s+name=[\'"]description[\'"]\s+content=[\'"]([^\'"]+)[\'"]/is', $html_body, $desc_match );

		$title_text     = $has_title ? trim( $title_match[1] ) : '';
		$canonical_url  = $has_canonical ? trim( $canon_match[1] ) : '';
		$robots_content = $has_robots ? trim( $robots_match[1] ) : '';
		$desc_text      = $has_description ? trim( $desc_match[1] ) : '';

		return [
			'success'          => ( $status_code >= 200 && $status_code < 400 ),
			'url'              => esc_url( $target_url ),
			'status_code'      => $status_code,
			'duration_ms'      => $duration_ms,
			'title'            => esc_html( $title_text ),
			'canonical'        => esc_url( $canonical_url ),
			'robots'           => esc_html( $robots_content ),
			'has_description'  => ! empty( $desc_text ),
			'content_bytes'    => strlen( $html_body ),
			'is_healthy'       => ( 200 === $status_code && $has_title && $has_canonical ),
		];
	}

	/**
	 * Verify that a target URL belongs strictly to the current site domain.
	 *
	 * @param string $target_url Target URL.
	 * @return bool True if safe internal URL.
	 */
	public static function validate_target_url_ssrf( string $target_url ): bool {
		$parsed_target = wp_parse_url( $target_url );
		$parsed_home   = wp_parse_url( home_url() );

		if ( empty( $parsed_target['host'] ) || empty( $parsed_home['host'] ) ) {
			return false;
		}

		// Ensure scheme is http or https.
		if ( ! in_array( strtolower( $parsed_target['scheme'] ?? '' ), [ 'http', 'https' ], true ) ) {
			return false;
		}

		// Strictly compare hostnames.
		if ( strtolower( $parsed_target['host'] ) !== strtolower( $parsed_home['host'] ) ) {
			return false;
		}

		return true;
	}
}
