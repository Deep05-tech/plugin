<?php
/**
 * SEO City Pages — Request Dynamic Page Context Provider.
 *
 * Provides a single, cached context repository for the currently resolved
 * SEO City Page, eliminating redundant database queries per widget (0 N+1).
 *
 * @package SeoCityPages\Elementor
 */

namespace SeoCityPages\Elementor;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\URL\URL_Engine;
use SeoCityPages\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page_Context Class.
 */
class Page_Context {

	/**
	 * Active SEO City Page record.
	 *
	 * @var object|null
	 */
	private static $page = null;

	/**
	 * Active Country record.
	 *
	 * @var object|null
	 */
	private static $country = null;

	/**
	 * Active State/Region record.
	 *
	 * @var object|null
	 */
	private static $state = null;

	/**
	 * Active City record.
	 *
	 * @var object|null
	 */
	private static $city = null;

	/**
	 * Active Service record.
	 *
	 * @var object|null
	 */
	private static $service = null;

	/**
	 * Flag indicating if request is an authorized preview.
	 *
	 * @var bool
	 */
	private static $is_preview = false;

	/**
	 * Hydrate the request context with resolved database entities.
	 *
	 * @param object      $page    SEO City Page object.
	 * @param object|null $country Country object (optional, auto-resolved if omitted).
	 * @param object|null $state   State object (optional, auto-resolved if omitted).
	 * @param object|null $city    City object (optional, auto-resolved if omitted).
	 * @param object|null $service Service object (optional, auto-resolved if omitted).
	 * @return void
	 */
	public static function set_context( object $page, ?object $country = null, ?object $state = null, ?object $city = null, ?object $service = null ): void {
		self::$page    = $page;
		self::$country = $country ?: Country_Repository::get( (int) $page->country_id );
		self::$state   = $state ?: State_Repository::get( (int) $page->state_id );
		self::$city    = $city ?: City_Repository::get( (int) $page->city_id );
		self::$service = $service ?: Service_Repository::get( (int) $page->service_id );
	}

	/**
	 * Set preview mode flag.
	 *
	 * @param bool $is_preview Preview mode status.
	 * @return void
	 */
	public static function set_preview_mode( bool $is_preview ): void {
		self::$is_preview = $is_preview;
	}

	/**
	 * Check if current context is in preview mode.
	 *
	 * @return bool
	 */
	public static function is_preview(): bool {
		return self::$is_preview;
	}

	/**
	 * Check if an active page context has been set.
	 *
	 * @return bool
	 */
	public static function has_context(): bool {
		return null !== self::$page;
	}

	/**
	 * Get the active SEO City Page object.
	 *
	 * @return object|null
	 */
	public static function get_current_page(): ?object {
		return self::$page;
	}

	/**
	 * Alias for get_current_page().
	 *
	 * @return object|null
	 */
	public static function get_page(): ?object {
		return self::$page;
	}

	/**
	 * Get the active SEO City Page ID.
	 *
	 * @return int
	 */
	public static function get_page_id(): int {
		return ( self::$page && ! empty( self::$page->id ) ) ? (int) self::$page->id : 0;
	}

	/**
	 * Get the Country Name (e.g., "India").
	 *
	 * @return string
	 */
	public static function get_country(): string {
		if ( self::$country && ! empty( self::$country->name ) ) {
			return (string) self::$country->name;
		}
		$preview = self::get_preview_context();
		return $preview['country'];
	}

	/**
	 * Get the Country Code (e.g., "IN").
	 *
	 * @return string
	 */
	public static function get_country_code(): string {
		if ( self::$country && ! empty( self::$country->code ) ) {
			return (string) self::$country->code;
		}
		$preview = self::get_preview_context();
		return $preview['country_code'];
	}

	/**
	 * Get the State / Region Name (e.g., "Gujarat").
	 *
	 * @return string
	 */
	public static function get_state(): string {
		if ( self::$state && ! empty( self::$state->name ) ) {
			return (string) self::$state->name;
		}
		$preview = self::get_preview_context();
		return $preview['state'];
	}

	/**
	 * Get the State / Region Code (e.g., "GJ").
	 *
	 * @return string
	 */
	public static function get_state_code(): string {
		if ( self::$state && ! empty( self::$state->code ) ) {
			return (string) self::$state->code;
		}
		$preview = self::get_preview_context();
		return $preview['state_code'];
	}

	/**
	 * Get the City Name (e.g., "Rajkot").
	 *
	 * @return string
	 */
	public static function get_city(): string {
		if ( self::$city && ! empty( self::$city->name ) ) {
			return (string) self::$city->name;
		}
		$preview = self::get_preview_context();
		return $preview['city'];
	}

	/**
	 * Get the Service Name (e.g., "SEO Agency").
	 *
	 * @return string
	 */
	public static function get_service(): string {
		if ( self::$service && ! empty( self::$service->name ) ) {
			return (string) self::$service->name;
		}
		$preview = self::get_preview_context();
		return $preview['service'];
	}

	/**
	 * Get the Page Title (Title override or derived "{Service} in {City}").
	 *
	 * @return string
	 */
	public static function get_title(): string {
		if ( self::$page ) {
			if ( ! empty( self::$page->title_override ) ) {
				return (string) self::$page->title_override;
			}
			return City_Page_Repository::get_derived_title( self::$page );
		}
		$preview = self::get_preview_context();
		return $preview['title'];
	}

	/**
	 * Get the Public Page URL (e.g., "/seo-agency/rajkot/").
	 *
	 * @return string
	 */
	public static function get_url(): string {
		if ( self::$page && ! empty( self::$page->url_path ) ) {
			return URL_Engine::build_full_url( (string) self::$page->url_path );
		}
		$preview = self::get_preview_context();
		return $preview['url'];
	}

	/**
	 * Get the Page Status (e.g., "published", "draft").
	 *
	 * @return string
	 */
	public static function get_status(): string {
		if ( self::$page && ! empty( self::$page->status ) ) {
			return (string) self::$page->status;
		}
		return 'published';
	}

	/**
	 * Generic field getter by internal identifier.
	 *
	 * @param string $key Field identifier.
	 * @return string
	 */
	public static function get_field( string $key ): string {
		switch ( $key ) {
			case 'seo_city_name':
			case 'city':
				return self::get_city();
			case 'seo_city_state':
			case 'state':
				return self::get_state();
			case 'seo_city_state_code':
			case 'state_code':
				return self::get_state_code();
			case 'seo_city_country':
			case 'country':
				return self::get_country();
			case 'seo_city_country_code':
			case 'country_code':
				return self::get_country_code();
			case 'seo_city_service':
			case 'service':
				return self::get_service();
			case 'seo_city_title':
			case 'title':
				return self::get_title();
			case 'seo_city_url':
			case 'url':
				return self::get_url();
			case 'seo_city_status':
			case 'status':
				return self::get_status();
			default:
				return '';
		}
	}

	/**
	 * Get all active or preview data as an associative array.
	 *
	 * @return array<string, string>
	 */
	public static function get_all_data(): array {
		return [
			'country'      => self::get_country(),
			'country_code' => self::get_country_code(),
			'state'        => self::get_state(),
			'state_code'   => self::get_state_code(),
			'city'         => self::get_city(),
			'service'      => self::get_service(),
			'title'        => self::get_title(),
			'url'          => self::get_url(),
			'status'       => self::get_status(),
		];
	}

	/**
	 * Get safe fallback preview context for Elementor visual editor.
	 *
	 * @return array<string, string>
	 */
	public static function get_preview_context(): array {
		$settings = get_option( Settings::OPTION_NAME, [] );
		$preview_page_id = (int) ( $settings['preview_page_id'] ?? 0 );

		if ( $preview_page_id > 0 ) {
			$preview_page = City_Page_Repository::get( $preview_page_id );
			if ( $preview_page ) {
				$co = Country_Repository::get( (int) $preview_page->country_id );
				$st = State_Repository::get( (int) $preview_page->state_id );
				$ci = City_Repository::get( (int) $preview_page->city_id );
				$se = Service_Repository::get( (int) $preview_page->service_id );

				return [
					'country'      => $co ? $co->name : 'India',
					'country_code' => $co ? $co->code : 'IN',
					'state'        => $st ? $st->name : 'Gujarat',
					'state_code'   => $st ? $st->code : 'GJ',
					'city'         => $ci ? $ci->name : 'Rajkot',
					'service'      => $se ? $se->name : 'SEO Agency',
					'title'        => ! empty( $preview_page->title_override ) ? $preview_page->title_override : City_Page_Repository::get_derived_title( $preview_page ),
					'url'          => URL_Engine::build_full_url( $preview_page->url_path ),
					'status'       => $preview_page->status,
				];
			}
		}

		// Fallback sample data for editor visual preview.
		return [
			'country'      => 'India',
			'country_code' => 'IN',
			'state'        => 'Gujarat',
			'state_code'   => 'GJ',
			'city'         => 'Rajkot',
			'service'      => 'SEO Agency',
			'title'        => 'SEO Agency in Rajkot',
			'url'          => URL_Engine::build_full_url( '/seo-agency/rajkot/' ),
			'status'       => 'published',
		];
	}

	/**
	 * Reset the request context for test isolation and multi-request runs.
	 *
	 * @return void
	 */
	public static function reset_context(): void {
		self::$page       = null;
		self::$country    = null;
		self::$state      = null;
		self::$city       = null;
		self::$service    = null;
		self::$is_preview = false;
	}
}
