<?php
/**
 * Global SEO Settings Manager.
 *
 * @package SeoCityPages\SEO
 */

namespace SeoCityPages\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEO_Settings.
 */
class SEO_Settings {

	public const OPTION_KEY = 'seo_city_pages_seo_settings';

	/**
	 * Default settings map.
	 *
	 * @return array
	 */
	public static function get_defaults(): array {
		return [
			'default_title_template'    => '{service} in {city}, {state_code} | {site_name}',
			'default_desc_template'     => 'Professional {service} services in {city}, {state}. Trusted local experts delivering quality solutions. Contact us today.',
			'default_og_image'          => '',
			'default_twitter_card'      => 'summary_large_image',
			'default_robots_index'      => 'index',
			'default_robots_follow'     => 'follow',
			'enable_sitemap'            => true,
			'sitemap_entries_per_page'  => 1000,
			'enable_schema'             => true,
			'schema_default_type'       => 'Service',
			'adapter_override'          => '',
		];
	}

	/**
	 * Retrieve all SEO settings.
	 *
	 * @return array
	 */
	public static function get_all(): array {
		$saved = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		return wp_parse_args( $saved, self::get_defaults() );
	}

	/**
	 * Retrieve a specific SEO setting.
	 *
	 * @param string $key     Setting key name.
	 * @param mixed  $default Fallback if not set.
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$all = self::get_all();
		return $all[ $key ] ?? $default;
	}

	/**
	 * Update SEO settings.
	 *
	 * @param array $settings New settings.
	 * @return bool
	 */
	public static function update( array $settings ): bool {
		$current = self::get_all();
		$updated = array_merge( $current, $settings );

		// Sanitization.
		$updated['default_title_template']   = sanitize_text_field( $updated['default_title_template'] ?? '' );
		$updated['default_desc_template']    = sanitize_textarea_field( $updated['default_desc_template'] ?? '' );
		$updated['default_og_image']         = ! empty( $updated['default_og_image'] ) ? esc_url_raw( trim( $updated['default_og_image'] ) ) : '';
		$updated['default_twitter_card']     = in_array( $updated['default_twitter_card'] ?? '', [ 'summary', 'summary_large_image' ], true ) ? $updated['default_twitter_card'] : 'summary_large_image';
		$updated['default_robots_index']     = in_array( $updated['default_robots_index'] ?? '', [ 'index', 'noindex' ], true ) ? $updated['default_robots_index'] : 'index';
		$updated['default_robots_follow']    = in_array( $updated['default_robots_follow'] ?? '', [ 'follow', 'nofollow' ], true ) ? $updated['default_robots_follow'] : 'follow';
		$updated['enable_sitemap']           = ! empty( $updated['enable_sitemap'] );
		$updated['sitemap_entries_per_page'] = max( 100, min( 5000, (int) ( $updated['sitemap_entries_per_page'] ?? 1000 ) ) );
		$updated['enable_schema']            = ! empty( $updated['enable_schema'] );
		$updated['schema_default_type']      = in_array( $updated['schema_default_type'] ?? '', [ 'Service', 'LocalBusiness', 'ProfessionalService' ], true ) ? $updated['schema_default_type'] : 'Service';
		$updated['adapter_override']         = sanitize_text_field( $updated['adapter_override'] ?? '' );

		return update_option( self::OPTION_KEY, $updated );
	}
}
