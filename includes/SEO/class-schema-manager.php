<?php
/**
 * Schema JSON-LD Structured Data Manager.
 *
 * @package SeoCityPages\SEO
 */

namespace SeoCityPages\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Schema_Manager.
 */
class Schema_Manager {

	/**
	 * Build structured JSON-LD array for a city page.
	 *
	 * @param object $page         City page database record.
	 * @param array  $resolved_seo Resolved SEO payload (title, desc, canonical, etc.).
	 * @return array JSON-LD schema array or empty array if disabled.
	 */
	public static function generate_schema( object $page, array $resolved_seo ): array {
		if ( ! SEO_Settings::get( 'enable_schema', true ) ) {
			return [];
		}

		$schema_type = $page->schema_type ?? 'default';
		if ( 'default' === $schema_type || empty( $schema_type ) ) {
			$schema_type = SEO_Settings::get( 'schema_default_type', 'Service' );
		}

		if ( 'none' === $schema_type ) {
			return [];
		}

		$site_name = get_bloginfo( 'name' ) ?: 'Website';
		$home_url  = home_url( '/' );
		$page_url  = $resolved_seo['canonical_url'] ?? ( $home_url . ltrim( $page->url_path ?? '', '/' ) );

		$service_name = $page->service_name ?? 'Service';
		$city_name    = $page->city_name ?? 'City';
		$state_name   = $page->state_name ?? '';
		$country_code = $page->country_code ?? '';

		$area_served = [
			'@type' => 'City',
			'name'  => $city_name,
		];

		if ( ! empty( $state_name ) || ! empty( $country_code ) ) {
			$admin_area = [
				'@type' => 'AdministrativeArea',
			];
			if ( ! empty( $state_name ) ) {
				$admin_area['name'] = $state_name;
			}
			if ( ! empty( $country_code ) ) {
				$admin_area['addressCountry'] = $country_code;
			}
			$area_served['containedInPlace'] = $admin_area;
		}

		$provider = [
			'@type' => 'Organization',
			'name'  => $site_name,
			'url'   => $home_url,
		];

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => $schema_type,
			'name'        => sprintf( '%s in %s', $service_name, $city_name ),
			'description' => $resolved_seo['meta_description'] ?? '',
			'url'         => $page_url,
			'areaServed'  => $area_served,
			'provider'    => $provider,
		];

		// If LocalBusiness or ProfessionalService, attach service type.
		if ( in_array( $schema_type, [ 'LocalBusiness', 'ProfessionalService' ], true ) ) {
			$schema['serviceType'] = $service_name;
		}

		return apply_filters( 'seo_city_pages_schema_data', $schema, $page, $resolved_seo );
	}

	/**
	 * Render HTML script tag containing JSON-LD.
	 *
	 * @param object $page         City page database record.
	 * @param array  $resolved_seo Resolved SEO payload.
	 * @return string HTML script tag or empty string.
	 */
	public static function render_json_ld( object $page, array $resolved_seo ): string {
		$schema = self::generate_schema( $page, $resolved_seo );
		if ( empty( $schema ) ) {
			return '';
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		if ( ! $json ) {
			return '';
		}

		return "\n<!-- SEO City Pages Schema JSON-LD -->\n<script type=\"application/ld+json\">\n" . $json . "\n</script>\n";
	}
}
