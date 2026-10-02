<?php
/**
 * Central SEO Manager & Precedence Cascade Orchestrator.
 *
 * @package SeoCityPages\SEO
 */

namespace SeoCityPages\SEO;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\SEO\Adapters\Native_Adapter;
use SeoCityPages\SEO\Adapters\Yoast_Adapter;
use SeoCityPages\SEO\Adapters\Rank_Math_Adapter;
use SeoCityPages\SEO\Adapters\AIOSEO_Adapter;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEO_Manager.
 */
class SEO_Manager {

	/**
	 * Active SEO Adapter.
	 *
	 * @var SEO_Adapter_Interface
	 */
	protected $adapter;

	/**
	 * Singleton instance.
	 *
	 * @var SEO_Manager|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return SEO_Manager
	 */
	public static function get_instance(): SEO_Manager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->setup_adapter();
	}

	/**
	 * Initialize and register SEO hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->adapter ) {
			$this->adapter->register_hooks();
		}

		Sitemap_Provider::register();
	}

	/**
	 * Resolve active adapter based on environment detector & settings override.
	 *
	 * @return void
	 */
	protected function setup_adapter(): void {
		$override = SEO_Settings::get( 'adapter_override', '' );
		$slug     = SEO_Environment_Detector::get_primary_adapter_slug( (string) $override );

		switch ( $slug ) {
			case SEO_Environment_Detector::PLUGIN_YOAST:
				$this->adapter = new Yoast_Adapter( $this );
				break;
			case SEO_Environment_Detector::PLUGIN_RANK_MATH:
				$this->adapter = new Rank_Math_Adapter( $this );
				break;
			case SEO_Environment_Detector::PLUGIN_AIOSEO:
				$this->adapter = new AIOSEO_Adapter( $this );
				break;
			case SEO_Environment_Detector::PLUGIN_NATIVE:
			default:
				$this->adapter = new Native_Adapter( $this );
				break;
		}

		Logger::info(
			sprintf( 'SEO Manager initialized with adapter: %s (%s)', $this->adapter->get_name(), $this->adapter->get_slug() ),
			[ 'adapter' => $this->adapter->get_slug() ]
		);
	}

	/**
	 * Get currently active SEO adapter.
	 *
	 * @return SEO_Adapter_Interface
	 */
	public function get_adapter(): SEO_Adapter_Interface {
		return $this->adapter;
	}

	/**
	 * Apply 4-tier Precedence Cascade to resolve final SEO metadata for a page.
	 *
	 * Precedence:
	 * 1. Manual page override
	 * 2. Approved AI generated metadata
	 * 3. Configured template
	 * 4. Safe fallback
	 *
	 * @param object $page City page record.
	 * @return array Normalized SEO metadata array.
	 */
	public function resolve_page_seo( object $page ): array {
		$site_name = get_bloginfo( 'name' ) ?: 'WordPress';
		$home_url  = home_url( '/' );

		$service_name = $page->service_name ?? 'Service';
		$city_name    = $page->city_name ?? 'City';
		$state_name   = $page->state_name ?? '';
		$state_code   = $page->state_code ?? '';

		// Tier 4: Safe Fallbacks.
		$tier4_title = sprintf( '%s in %s | %s', $service_name, $city_name, $site_name );
		$tier4_desc  = sprintf(
			'Professional %s services in %s%s. Trusted local experts delivering quality results.',
			$service_name,
			$city_name,
			! empty( $state_code ) ? ', ' . $state_code : ''
		);

		// Tier 3: Configured Global Templates.
		$tpl_title = SEO_Settings::get( 'default_title_template', '{service} in {city}, {state_code} | {site_name}' );
		$tpl_desc  = SEO_Settings::get( 'default_desc_template', 'Professional {service} services in {city}, {state}. Trusted local experts delivering quality solutions. Contact us today.' );

		$tier3_title = SEO_Template_Engine::render( (string) $tpl_title, $page );
		$tier3_desc  = SEO_Template_Engine::render( (string) $tpl_desc, $page );

		// Tier 2: Approved AI Metadata from published or current approved version.
		$tier2_title       = '';
		$tier2_desc        = '';
		$tier2_og_title    = '';
		$tier2_og_desc     = '';

		$version_row = null;
		if ( ! empty( $page->published_version_id ) && class_exists( Content_Version_Repository::class ) ) {
			$version_row = Content_Version_Repository::get( (int) $page->published_version_id );
		} elseif ( ! empty( $page->current_version_id ) && class_exists( Content_Version_Repository::class ) ) {
			$version_row = Content_Version_Repository::get( (int) $page->current_version_id );
		}

		if ( $version_row && ! empty( $version_row->content_json ) ) {
			$content = is_string( $version_row->content_json ) ? json_decode( $version_row->content_json, true ) : $version_row->content_json;
			if ( is_array( $content ) && ! empty( $content['seo_metadata'] ) && is_array( $content['seo_metadata'] ) ) {
				$ai_seo = $content['seo_metadata'];
				$tier2_title    = trim( $ai_seo['seo_title'] ?? '' );
				$tier2_desc     = trim( $ai_seo['meta_description'] ?? '' );
				$tier2_og_title = trim( $ai_seo['og_title'] ?? '' );
				$tier2_og_desc  = trim( $ai_seo['og_description'] ?? '' );
			}
		}

		// Tier 1: Manual Page Overrides.
		$manual_title       = trim( $page->seo_title ?? '' );
		$manual_desc        = trim( $page->meta_description ?? '' );
		$manual_canonical   = trim( $page->canonical_url ?? '' );
		$manual_og_title    = trim( $page->og_title ?? '' );
		$manual_og_desc     = trim( $page->og_description ?? '' );
		$manual_og_image    = trim( $page->og_image ?? '' );
		$manual_tw_title    = trim( $page->twitter_title ?? '' );
		$manual_tw_desc     = trim( $page->twitter_description ?? '' );
		$manual_tw_image    = trim( $page->twitter_image ?? '' );

		// Cascading Title:
		if ( ! empty( $manual_title ) ) {
			$final_title = $manual_title;
		} elseif ( ! empty( $tier2_title ) ) {
			$final_title = $tier2_title;
		} elseif ( ! empty( $tier3_title ) ) {
			$final_title = $tier3_title;
		} else {
			$final_title = $tier4_title;
		}

		// Cascading Description:
		if ( ! empty( $manual_desc ) ) {
			$final_desc = $manual_desc;
		} elseif ( ! empty( $tier2_desc ) ) {
			$final_desc = $tier2_desc;
		} elseif ( ! empty( $tier3_desc ) ) {
			$final_desc = $tier3_desc;
		} else {
			$final_desc = $tier4_desc;
		}

		// Canonical URL:
		$default_canonical = $home_url . ltrim( $page->url_path ?? '', '/' );
		$final_canonical   = ! empty( $manual_canonical ) ? $manual_canonical : $default_canonical;

		// Strict Status-Based Indexing Controls:
		$is_published = ( ( $page->status ?? '' ) === 'published' && ! empty( $page->is_published ) );

		if ( ! $is_published ) {
			// Draft, unapproved, private, trash pages MUST always be noindex, nofollow.
			$final_index  = 'noindex';
			$final_follow = 'nofollow';
		} else {
			$page_index = $page->robots_index ?? 'default';
			if ( 'default' === $page_index || empty( $page_index ) ) {
				$final_index = SEO_Settings::get( 'default_robots_index', 'index' );
			} else {
				$final_index = $page_index;
			}

			$page_follow = $page->robots_follow ?? 'default';
			if ( 'default' === $page_follow || empty( $page_follow ) ) {
				$final_follow = SEO_Settings::get( 'default_robots_follow', 'follow' );
			} else {
				$final_follow = $page_follow;
			}
		}

		// OpenGraph data:
		$global_og_image = (string) SEO_Settings::get( 'default_og_image', '' );
		$final_og_title  = ! empty( $manual_og_title ) ? $manual_og_title : ( ! empty( $tier2_og_title ) ? $tier2_og_title : $final_title );
		$final_og_desc   = ! empty( $manual_og_desc ) ? $manual_og_desc : ( ! empty( $tier2_og_desc ) ? $tier2_og_desc : $final_desc );
		$final_og_image  = ! empty( $manual_og_image ) ? $manual_og_image : $global_og_image;

		// Twitter data:
		$final_tw_title  = ! empty( $manual_tw_title ) ? $manual_tw_title : $final_og_title;
		$final_tw_desc   = ! empty( $manual_tw_desc ) ? $manual_tw_desc : $final_og_desc;
		$final_tw_image  = ! empty( $manual_tw_image ) ? $manual_tw_image : $final_og_image;

		$payload = [
			'page_id'             => (int) ( $page->id ?? 0 ),
			'seo_title'           => $final_title,
			'meta_description'    => $final_desc,
			'canonical_url'       => $final_canonical,
			'robots_index'        => $final_index,
			'robots_follow'       => $final_follow,
			'robots_advanced'     => $page->robots_advanced ?? '',
			'og_title'            => $final_og_title,
			'og_description'      => $final_og_desc,
			'og_image'            => $final_og_image,
			'twitter_title'       => $final_tw_title,
			'twitter_description' => $final_tw_desc,
			'twitter_image'       => $final_tw_image,
			'schema_type'         => $page->schema_type ?? 'default',
			'is_published'        => $is_published,
			'is_manual_override'  => ! empty( $page->is_manual_seo_override ),
		];

		return apply_filters( 'seo_city_pages_resolved_seo_payload', $payload, $page );
	}
}
