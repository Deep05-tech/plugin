<?php
/**
 * SEO Environment Detector.
 *
 * @package SeoCityPages\SEO
 */

namespace SeoCityPages\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEO_Environment_Detector.
 */
class SEO_Environment_Detector {

	public const PLUGIN_YOAST     = 'yoast';
	public const PLUGIN_RANK_MATH = 'rank_math';
	public const PLUGIN_AIOSEO    = 'aioseo';
	public const PLUGIN_NATIVE    = 'native';

	/**
	 * Detect all currently active 3rd-party SEO plugins.
	 *
	 * @return string[] Array of active plugin identifiers.
	 */
	public static function get_active_plugins(): array {
		$active = [];

		if ( self::is_yoast_active() ) {
			$active[] = self::PLUGIN_YOAST;
		}

		if ( self::is_rank_math_active() ) {
			$active[] = self::PLUGIN_RANK_MATH;
		}

		if ( self::is_aioseo_active() ) {
			$active[] = self::PLUGIN_AIOSEO;
		}

		return $active;
	}

	/**
	 * Determine the primary SEO adapter slug to use.
	 *
	 * @param string $manual_override Optional manual override setting.
	 * @return string Slug: 'yoast', 'rank_math', 'aioseo', or 'native'.
	 */
	public static function get_primary_adapter_slug( string $manual_override = '' ): string {
		if ( ! empty( $manual_override ) && in_array( $manual_override, [ self::PLUGIN_YOAST, self::PLUGIN_RANK_MATH, self::PLUGIN_AIOSEO, self::PLUGIN_NATIVE ], true ) ) {
			return $manual_override;
		}

		$active = self::get_active_plugins();

		if ( in_array( self::PLUGIN_YOAST, $active, true ) ) {
			return self::PLUGIN_YOAST;
		}

		if ( in_array( self::PLUGIN_RANK_MATH, $active, true ) ) {
			return self::PLUGIN_RANK_MATH;
		}

		if ( in_array( self::PLUGIN_AIOSEO, $active, true ) ) {
			return self::PLUGIN_AIOSEO;
		}

		return self::PLUGIN_NATIVE;
	}

	/**
	 * Check if multiple SEO plugins are active simultaneously (conflict scenario).
	 *
	 * @return bool
	 */
	public static function has_conflict(): bool {
		return count( self::get_active_plugins() ) > 1;
	}

	/**
	 * Check if Yoast SEO is active.
	 *
	 * @return bool
	 */
	public static function is_yoast_active(): bool {
		return defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options', false );
	}

	/**
	 * Check if Rank Math SEO is active.
	 *
	 * @return bool
	 */
	public static function is_rank_math_active(): bool {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath', false );
	}

	/**
	 * Check if All in One SEO is active.
	 *
	 * @return bool
	 */
	public static function is_aioseo_active(): bool {
		return defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' );
	}

	/**
	 * Get human-readable description of current environment.
	 *
	 * @return array
	 */
	public static function get_environment_info(): array {
		$active = self::get_active_plugins();
		$primary = self::get_primary_adapter_slug();

		$names = [
			self::PLUGIN_YOAST     => 'Yoast SEO',
			self::PLUGIN_RANK_MATH => 'Rank Math SEO',
			self::PLUGIN_AIOSEO    => 'All in One SEO',
			self::PLUGIN_NATIVE    => 'WordPress Native SEO Engine',
		];

		$active_names = array_map( static function ( $slug ) use ( $names ) {
			return $names[ $slug ] ?? $slug;
		}, $active );

		return [
			'active_slugs'    => $active,
			'active_names'    => $active_names,
			'primary_slug'    => $primary,
			'primary_name'    => $names[ $primary ] ?? 'Native',
			'has_conflict'    => self::has_conflict(),
			'conflict_notice' => self::has_conflict()
				? sprintf( __( 'Multiple SEO plugins detected (%s). SEO City Pages is managing metadata through %s to avoid tag duplication.', 'seo-city-pages' ), implode( ', ', $active_names ), $names[ $primary ] )
				: null,
		];
	}
}
