<?php
/**
 * XML Sitemap Integration & Scalable Provider.
 *
 * @package SeoCityPages\SEO
 */

namespace SeoCityPages\SEO;

use SeoCityPages\Database\City_Page_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Sitemap_Provider.
 */
class Sitemap_Provider {

	/**
	 * Register sitemap provider into WordPress Core and 3rd-party SEO plugins.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! SEO_Settings::get( 'enable_sitemap', true ) ) {
			return;
		}

		// 1. WordPress Core Sitemaps (WP 5.5+).
		add_action( 'init', [ __CLASS__, 'register_wp_core_sitemap' ] );

		// 2. Yoast SEO Sitemap Integration.
		add_action( 'init', [ __CLASS__, 'register_yoast_sitemap' ] );

		// 3. Rank Math Sitemap Integration.
		add_filter( 'rank_math/sitemap/providers', [ __CLASS__, 'register_rank_math_sitemap' ] );
	}

	/**
	 * Register with WordPress Core WP_Sitemaps.
	 *
	 * @return void
	 */
	public static function register_wp_core_sitemap(): void {
		if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) {
			return;
		}

		$provider = new class extends \WP_Sitemaps_Provider {
			public function __construct() {
				$this->name        = 'seo-city-pages';
				$this->object_type = 'seo_city_page';
			}

			public function get_url_list( $page_num, $object_subtype = '' ) {
				$limit = (int) SEO_Settings::get( 'sitemap_entries_per_page', 1000 );
				$offset = ( $page_num - 1 ) * $limit;

				$pages = City_Page_Repository::get_published_pages_for_sitemap( $limit, $offset );
				$url_list = [];

				$home_url = home_url( '/' );

				foreach ( $pages as $page ) {
					$loc = ! empty( $page->canonical_url )
						? $page->canonical_url
						: ( $home_url . ltrim( $page->url_path, '/' ) );

					$lastmod = ! empty( $page->updated_at )
						? mysql2date( 'Y-m-d\TH:i:s+00:00', $page->updated_at, false )
						: null;

					$entry = [ 'loc' => esc_url( $loc ) ];
					if ( $lastmod ) {
						$entry['lastmod'] = $lastmod;
					}

					$url_list[] = $entry;
				}

				return $url_list;
			}

			public function get_max_num_pages( $object_subtype = '' ) {
				$total = City_Page_Repository::count_published_pages_for_sitemap();
				$limit = (int) SEO_Settings::get( 'sitemap_entries_per_page', 1000 );
				if ( $limit <= 0 ) {
					$limit = 1000;
				}
				return (int) ceil( $total / $limit );
			}
		};

		wp_register_sitemap_provider( 'seo-city-pages', $provider );
	}

	/**
	 * Register custom sitemap with Yoast SEO.
	 *
	 * @return void
	 */
	public static function register_yoast_sitemap(): void {
		if ( ! SEO_Environment_Detector::is_yoast_active() ) {
			return;
		}

		add_action( 'wpseo_do_sitemap_seo_city_pages', [ __CLASS__, 'render_yoast_sitemap' ] );
		add_filter( 'wpseo_sitemap_index', [ __CLASS__, 'add_yoast_sitemap_index' ] );
	}

	/**
	 * Add custom sitemap entry to Yoast index.
	 *
	 * @param string $index XML content.
	 * @return string
	 */
	public static function add_yoast_sitemap_index( string $index ): string {
		$total = City_Page_Repository::count_published_pages_for_sitemap();
		if ( $total <= 0 ) {
			return $index;
		}

		$date = gmdate( 'c' );
		$url  = home_url( '/seo_city_pages-sitemap.xml' );
		$xml  = "\t<sitemap>\n\t\t<loc>" . esc_url( $url ) . "</loc>\n\t\t<lastmod>" . esc_html( $date ) . "</lastmod>\n\t</sitemap>\n";

		return $index . $xml;
	}

	/**
	 * Output Yoast-formatted sitemap XML.
	 *
	 * @return void
	 */
	public static function render_yoast_sitemap(): void {
		$limit = (int) SEO_Settings::get( 'sitemap_entries_per_page', 1000 );
		$pages = City_Page_Repository::get_published_pages_for_sitemap( $limit, 0 );

		$home_url = home_url( '/' );
		$output   = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $pages as $page ) {
			$loc = ! empty( $page->canonical_url )
				? $page->canonical_url
				: ( $home_url . ltrim( $page->url_path, '/' ) );

			$mod = ! empty( $page->updated_at ) ? gmdate( 'c', strtotime( $page->updated_at ) ) : gmdate( 'c' );

			$output .= "\t<url>\n";
			$output .= "\t\t<loc>" . esc_url( $loc ) . "</loc>\n";
			$output .= "\t\t<lastmod>" . esc_html( $mod ) . "</lastmod>\n";
			$output .= "\t\t<changefreq>weekly</changefreq>\n";
			$output .= "\t\t<priority>0.8</priority>\n";
			$output .= "\t</url>\n";
		}

		$output .= '</urlset>';

		if ( class_exists( 'WPSEO_Sitemaps' ) && method_exists( 'WPSEO_Sitemaps', 'set_sitemap' ) ) {
			\WPSEO_Sitemaps::set_sitemap( $output );
		}
	}

	/**
	 * Register provider with Rank Math.
	 *
	 * @param array $providers Registered providers.
	 * @return array
	 */
	public static function register_rank_math_sitemap( array $providers ): array {
		$providers['seo_city_pages'] = [
			'name'  => 'seo_city_pages',
			'title' => __( 'SEO City Pages', 'seo-city-pages' ),
		];
		return $providers;
	}
}
