<?php
/**
 * SEO Template Engine.
 *
 * @package SeoCityPages\SEO
 */

namespace SeoCityPages\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEO_Template_Engine.
 */
class SEO_Template_Engine {

	/**
	 * Render a template string by substituting entity tokens.
	 *
	 * @param string      $template Raw template with placeholders.
	 * @param object|null $page     City page object with joined location/service names.
	 * @return string Rendered text.
	 */
	public static function render( string $template, ?object $page ): string {
		if ( empty( $template ) ) {
			return '';
		}

		$service_name = '';
		$city_name    = '';
		$state_name   = '';
		$state_code   = '';
		$country_name = '';
		$country_code = '';

		if ( $page ) {
			$service_name = $page->service_name ?? '';
			$city_name    = $page->city_name ?? '';
			$state_name   = $page->state_name ?? '';
			$state_code   = $page->state_code ?? '';
			$country_name = $page->country_name ?? '';
			$country_code = $page->country_code ?? '';
		}

		$site_name    = get_bloginfo( 'name' ) ?: 'WordPress';
		$current_year = date( 'Y' );

		$replacements = [
			'{service}'      => $service_name,
			'{service_name}' => $service_name,
			'{city}'         => $city_name,
			'{city_name}'    => $city_name,
			'{state}'        => $state_name,
			'{state_name}'   => $state_name,
			'{state_code}'   => $state_code,
			'{country}'      => $country_name,
			'{country_name}' => $country_name,
			'{country_code}' => $country_code,
			'{site_name}'    => $site_name,
			'{sitename}'     => $site_name,
			'{current_year}' => $current_year,
			'{year}'         => $current_year,
		];

		$rendered = str_ireplace( array_keys( $replacements ), array_values( $replacements ), $template );

		// Clean up any extraneous multi-spaces or trailing/leading punctuation resulting from empty placeholders.
		$rendered = preg_replace( '/\s+/', ' ', $rendered );
		$rendered = preg_replace( '/\s*\|\s*$/', '', $rendered );
		$rendered = preg_replace( '/^\s*\|\s*/', '', $rendered );

		return trim( $rendered );
	}
}
