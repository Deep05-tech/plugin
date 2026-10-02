<?php
/**
 * Environment & Dependency Inspector.
 *
 * @package SeoCityPages
 */

namespace SeoCityPages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Environment Class.
 */
class Environment {

	/**
	 * Minimum PHP version.
	 */
	public const MIN_PHP_VERSION = '7.4';

	/**
	 * Minimum WordPress version.
	 */
	public const MIN_WP_VERSION = '6.0';

	/**
	 * Check if PHP version meets requirements.
	 *
	 * @return bool
	 */
	public static function is_php_compatible(): bool {
		return version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Check if WordPress version meets requirements.
	 *
	 * @return bool
	 */
	public static function is_wp_compatible(): bool {
		global $wp_version;
		return version_compare( $wp_version ?? '0.0', self::MIN_WP_VERSION, '>=' );
	}

	/**
	 * Check if Elementor is installed and active.
	 *
	 * @return bool
	 */
	public static function is_elementor_active(): bool {
		if ( ( function_exists( 'did_action' ) && did_action( 'elementor/loaded' ) ) || class_exists( '\\Elementor\\Plugin' ) || defined( 'ELEMENTOR_VERSION' ) ) {
			return true;
		}

		if ( function_exists( 'is_plugin_active' ) ) {
			return is_plugin_active( 'elementor/elementor.php' );
		}

		$active_plugins = (array) get_option( 'active_plugins', [] );
		if ( in_array( 'elementor/elementor.php', $active_plugins, true ) ) {
			return true;
		}

		if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( 'elementor/elementor.php' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if Elementor plugin files are installed on the server.
	 *
	 * @return bool
	 */
	public static function is_elementor_installed(): bool {
		if ( self::is_elementor_active() ) {
			return true;
		}

		if ( defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/elementor/elementor.php' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get Elementor version.
	 *
	 * @return string|null
	 */
	public static function get_elementor_version(): ?string {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			return ELEMENTOR_VERSION;
		}
		if ( class_exists( '\\Elementor\\Plugin' ) && defined( '\\Elementor\\Plugin::VERSION' ) ) {
			return \Elementor\Plugin::VERSION;
		}
		return null;
	}

	/**
	 * Check if Elementor Pro is active.
	 *
	 * @return bool
	 */
	public static function is_elementor_pro_active(): bool {
		return class_exists( '\\ElementorPro\\Plugin' ) || defined( 'ELEMENTOR_PRO_VERSION' );
	}

	/**
	 * Get Elementor Pro version.
	 *
	 * @return string|null
	 */
	public static function get_elementor_pro_version(): ?string {
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return ELEMENTOR_PRO_VERSION;
		}
		return null;
	}

	/**
	 * Get full Elementor dependency status.
	 *
	 * @return array
	 */
	public static function get_elementor_status(): array {
		$is_active     = self::is_elementor_active();
		$is_installed  = self::is_elementor_installed();
		$is_pro_active = self::is_elementor_pro_active();
		$version       = self::get_elementor_version();
		$pro_version   = self::get_elementor_pro_version();

		if ( $is_active ) {
			$msg = sprintf(
				/* translators: %s: Elementor version */
				__( 'Elementor detected and active (v%s).', 'seo-city-pages' ),
				$version ?: 'Unknown'
			);
			if ( $is_pro_active ) {
				$msg .= ' ' . sprintf(
					/* translators: %s: Elementor Pro version */
					__( 'Elementor Pro active (v%s).', 'seo-city-pages' ),
					$pro_version ?: 'Unknown'
				);
			}
			$status_key = 'active';
		} elseif ( $is_installed ) {
			$msg        = __( 'Elementor is installed but not active. The data layer is fully functional, but template rendering requires activation.', 'seo-city-pages' );
			$status_key = 'installed_inactive';
		} else {
			$msg        = __( 'Elementor is not detected. The data layer is fully functional, but template rendering requires Elementor.', 'seo-city-pages' );
			$status_key = 'not_installed';
		}

		return [
			'status'        => $status_key,
			'message'       => $msg,
			'is_active'     => $is_active,
			'is_installed'  => $is_installed,
			'is_pro_active' => $is_pro_active,
			'version'       => $version,
			'pro_version'   => $pro_version,
		];
	}

	/**
	 * Check if Arolax theme is active (as parent theme, standalone theme, or active child theme).
	 *
	 * @return bool
	 */
	public static function is_arolax_active(): bool {
		if ( defined( 'AROLAX_THEME_VERSION' ) || defined( 'AROLAX_VERSION' ) ) {
			return true;
		}

		if ( function_exists( 'wp_get_theme' ) ) {
			$theme = wp_get_theme();
			if ( $theme->exists() ) {
				$template   = strtolower( (string) $theme->get_template() );
				$stylesheet = strtolower( (string) $theme->get_stylesheet() );
				$name       = strtolower( (string) $theme->get( 'Name' ) );

				if (
					strpos( $template, 'arolax' ) !== false ||
					strpos( $stylesheet, 'arolax' ) !== false ||
					strpos( $name, 'arolax' ) !== false
				) {
					return true;
				}

				$parent = $theme->parent();
				if ( $parent && $parent->exists() ) {
					$parent_template   = strtolower( (string) $parent->get_template() );
					$parent_stylesheet = strtolower( (string) $parent->get_stylesheet() );
					$parent_name       = strtolower( (string) $parent->get( 'Name' ) );

					if (
						strpos( $parent_template, 'arolax' ) !== false ||
						strpos( $parent_stylesheet, 'arolax' ) !== false ||
						strpos( $parent_name, 'arolax' ) !== false
					) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Check if Arolax theme is installed on the server (active or inactive).
	 *
	 * @return bool
	 */
	public static function is_arolax_installed(): bool {
		if ( self::is_arolax_active() ) {
			return true;
		}

		if ( function_exists( 'wp_get_themes' ) ) {
			$themes = wp_get_themes();
			foreach ( $themes as $slug => $theme_obj ) {
				$slug_lower     = strtolower( (string) $slug );
				$name_lower     = strtolower( (string) $theme_obj->get( 'Name' ) );
				$template_lower = strtolower( (string) $theme_obj->get_template() );

				if (
					strpos( $slug_lower, 'arolax' ) !== false ||
					strpos( $name_lower, 'arolax' ) !== false ||
					strpos( $template_lower, 'arolax' ) !== false
				) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get Arolax theme status report.
	 *
	 * @return array
	 */
	public static function get_arolax_status(): array {
		$theme        = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
		$is_active    = self::is_arolax_active();
		$is_installed = self::is_arolax_installed();
		$is_child     = false;
		$parent_name  = '';

		if ( $theme && $theme->exists() && $theme->parent() ) {
			$is_child    = true;
			$parent_name = (string) $theme->parent()->get( 'Name' );
		}

		if ( $is_active ) {
			$message = $is_child
				? sprintf(
					/* translators: 1: Child theme name, 2: Parent theme name */
					__( 'Arolax detected and active (Child theme: %1$s, Parent theme: %2$s).', 'seo-city-pages' ),
					$theme->get( 'Name' ),
					$parent_name
				)
				: __( 'Arolax detected and active.', 'seo-city-pages' );
			$status_key = 'active';
		} elseif ( $is_installed ) {
			$message    = __( 'Arolax is installed but not the active theme. The plugin operates in theme-isolated mode.', 'seo-city-pages' );
			$status_key = 'installed_inactive';
		} else {
			$message    = __( 'Arolax is not installed. The plugin operates universally in theme-isolated mode.', 'seo-city-pages' );
			$status_key = 'not_installed';
		}

		return [
			'status'       => $status_key,
			'message'      => $message,
			'is_active'    => $is_active,
			'is_installed' => $is_installed,
			'is_child'     => $is_child,
			'parent_name'  => $parent_name,
			'theme_name'   => ( $theme && $theme->exists() ) ? $theme->get( 'Name' ) : 'Unknown',
			'version'      => ( $theme && $theme->exists() ) ? $theme->get( 'Version' ) : 'Unknown',
			'stylesheet'   => ( $theme && $theme->exists() ) ? $theme->get_stylesheet() : '',
			'template'     => ( $theme && $theme->exists() ) ? $theme->get_template() : '',
		];
	}

	/**
	 * Get Active Theme details.
	 *
	 * @return array
	 */
	public static function get_theme_info(): array {
		$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
		if ( ! $theme || ! $theme->exists() ) {
			return [
				'name'          => 'Unknown',
				'version'       => 'Unknown',
				'template'      => '',
				'stylesheet'    => '',
				'is_arolax'     => false,
				'arolax_status' => self::get_arolax_status(),
				'is_child'      => false,
				'parent_name'   => '',
			];
		}

		$arolax_status = self::get_arolax_status();

		return [
			'name'          => $theme->get( 'Name' ),
			'version'       => $theme->get( 'Version' ),
			'template'      => $theme->get_template(),
			'stylesheet'    => $theme->get_stylesheet(),
			'is_arolax'     => $arolax_status['is_active'],
			'arolax_status' => $arolax_status,
			'is_child'      => $theme->parent() ? true : false,
			'parent_name'   => $theme->parent() ? $theme->parent()->get( 'Name' ) : '',
		];
	}

	/**
	 * Detect active SEO plugins.
	 *
	 * @return array Array with key 'name', 'version', 'active'.
	 */
	public static function get_active_seo_plugin(): array {
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' ) ) {
			return [
				'name'    => 'Yoast SEO',
				'slug'    => 'wordpress-seo',
				'version' => defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : 'Detected',
				'active'  => true,
			];
		}

		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
			return [
				'name'    => 'Rank Math SEO',
				'slug'    => 'seo-by-rank-math',
				'version' => defined( 'RANK_MATH_VERSION' ) ? RANK_MATH_VERSION : 'Detected',
				'active'  => true,
			];
		}

		if ( defined( 'AIOSEO_VERSION' ) || class_exists( 'AIOSEO\\Plugin\\AIOSEO' ) ) {
			return [
				'name'    => 'All in One SEO',
				'slug'    => 'all-in-one-seo-pack',
				'version' => defined( 'AIOSEO_VERSION' ) ? AIOSEO_VERSION : 'Detected',
				'active'  => true,
			];
		}

		if ( defined( 'SEOPRESS_VERSION' ) ) {
			return [
				'name'    => 'SEOPress',
				'slug'    => 'wp-seopress',
				'version' => SEOPRESS_VERSION,
				'active'  => true,
			];
		}

		return [
			'name'    => 'None Detected',
			'slug'    => '',
			'version' => '',
			'active'  => false,
		];
	}

	/**
	 * Check if Action Scheduler is available.
	 *
	 * @return bool
	 */
	public static function is_action_scheduler_available(): bool {
		return class_exists( 'ActionScheduler' ) || function_exists( 'as_schedule_single_action' );
	}

	/**
	 * Get full system diagnostics report.
	 *
	 * @return array
	 */
	public static function get_diagnostics(): array {
		global $wp_version, $wpdb;

		$php_memory_limit = ini_get( 'memory_limit' );
		$max_exec_time    = ini_get( 'max_execution_time' );
		$upload_max       = ini_get( 'upload_max_filesize' );
		$post_max         = ini_get( 'post_max_size' );

		$db_version = '';
		if ( $wpdb ) {
			$db_version = $wpdb->db_version();
		}

		$uploads_dir = wp_upload_dir();
		$is_writable = wp_is_writable( $uploads_dir['basedir'] );

		return [
			'wordpress'        => [
				'version'    => $wp_version ?? 'Unknown',
				'compatible' => self::is_wp_compatible(),
				'multisite'  => is_multisite(),
				'debug'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'debug_log'  => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
				'cron_status'=> ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON,
			],
			'php'              => [
				'version'      => PHP_VERSION,
				'compatible'   => self::is_php_compatible(),
				'memory_limit' => $php_memory_limit ? $php_memory_limit : 'Unknown',
				'max_exec_time'=> $max_exec_time ? $max_exec_time : '0',
				'upload_max'   => $upload_max ? $upload_max : 'Unknown',
				'post_max'     => $post_max ? $post_max : 'Unknown',
			],
			'database'         => [
				'version' => $db_version ? $db_version : 'Unknown',
				'prefix'  => $wpdb ? $wpdb->prefix : '',
			],
			'elementor'        => [
				'active'  => self::is_elementor_active(),
				'version' => self::get_elementor_version(),
			],
			'elementor_pro'    => [
				'active'  => self::is_elementor_pro_active(),
				'version' => self::get_elementor_pro_version(),
			],
			'theme'            => self::get_theme_info(),
			'seo_plugin'       => self::get_active_seo_plugin(),
			'action_scheduler' => [
				'available' => self::is_action_scheduler_available(),
			],
			'filesystem'       => [
				'uploads_writable' => $is_writable,
			],
		];
	}
}
