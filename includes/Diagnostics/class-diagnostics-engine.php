<?php
/**
 * Master System Diagnostics Engine.
 *
 * @package SeoCityPages\Diagnostics
 */

namespace SeoCityPages\Diagnostics;

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\Migrator;
use SeoCityPages\SEO\Environment_Detector;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Diagnostics Engine Class.
 */
class Diagnostics_Engine {

	public const STATUS_HEALTHY  = 'healthy';
	public const STATUS_WARNING  = 'warning';
	public const STATUS_CRITICAL = 'critical';
	public const STATUS_UNKNOWN  = 'unknown';

	/**
	 * Run full diagnostic evaluation across all subsystems.
	 *
	 * @return array
	 */
	public static function run_all_checks(): array {
		return [
			'system'        => self::check_php_environment(),
			'wordpress'     => self::check_wordpress_environment(),
			'database'      => self::check_database_health(),
			'elementor'     => self::check_elementor_health(),
			'arolax'        => self::check_arolax_health(),
			'seo'           => self::check_seo_provider_health(),
			'cache'         => self::check_cache_environment(),
			'storage'       => self::check_storage_health(),
			'security'      => self::check_security_health(),
			'safe_mode'     => [
				'status'  => Safe_Mode_Manager::is_safe_mode_active() ? self::STATUS_WARNING : self::STATUS_HEALTHY,
				'active'  => Safe_Mode_Manager::is_safe_mode_active(),
				'paused'  => Safe_Mode_Manager::is_processing_paused(),
				'message' => Safe_Mode_Manager::is_safe_mode_active() ? __( 'Safe Mode is ACTIVE. AI generation and background processing are paused.', 'seo-city-pages' ) : __( 'Safe Mode is inactive.', 'seo-city-pages' ),
			],
			'checked_at'    => current_time( 'mysql' ),
		];
	}

	/**
	 * Check PHP environment and resource limits.
	 *
	 * @return array
	 */
	public static function check_php_environment(): array {
		$php_version     = PHP_VERSION;
		$memory_limit    = ini_get( 'memory_limit' );
		$max_exec_time   = (int) ini_get( 'max_execution_time' );
		$upload_max_size = ini_get( 'upload_max_filesize' );
		$post_max_size   = ini_get( 'post_max_size' );

		$issues = [];
		$status = self::STATUS_HEALTHY;

		// PHP version check (Recommend >= 8.0).
		if ( version_compare( $php_version, '7.4', '<' ) ) {
			$status   = self::STATUS_CRITICAL;
			$issues[] = [
				'title'       => __( 'Outdated PHP Version', 'seo-city-pages' ),
				'explanation' => sprintf( __( 'Current PHP is %s. Plugin requires PHP 7.4 or higher.', 'seo-city-pages' ), $php_version ),
				'action'      => __( 'Upgrade your server to PHP 8.1 or PHP 8.2+.', 'seo-city-pages' ),
			];
		}

		// Memory limit check (Recommend >= 256M for large scale batch operations).
		$memory_bytes = self::convert_hr_to_bytes( $memory_limit );
		if ( $memory_bytes > 0 && $memory_bytes < ( 128 * 1024 * 1024 ) ) {
			$status   = self::STATUS_WARNING;
			$issues[] = [
				'title'       => __( 'Low PHP Memory Limit', 'seo-city-pages' ),
				'explanation' => sprintf( __( 'PHP memory limit is %s. Large bulk operations may exceed memory.', 'seo-city-pages' ), $memory_limit ),
				'action'      => __( 'Increase memory_limit to at least 256M in php.ini or wp-config.php.', 'seo-city-pages' ),
			];
		}

		return [
			'status'           => $status,
			'php_version'      => $php_version,
			'memory_limit'     => $memory_limit,
			'max_exec_time'    => $max_exec_time > 0 ? "{$max_exec_time}s" : 'unlimited',
			'upload_max_size'  => $upload_max_size,
			'post_max_size'    => $post_max_size,
			'issues'           => $issues,
		];
	}

	/**
	 * Check WordPress core environment.
	 *
	 * @return array
	 */
	public static function check_wordpress_environment(): array {
		global $wp_version;
		$wp_ver = ! empty( $wp_version ) ? $wp_version : ( $GLOBALS['wp_version'] ?? '6.6.0' );
		$status = self::STATUS_HEALTHY;
		$issues = [];

		$wp_memory_limit = defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : '40M';
		$is_multisite    = is_multisite();
		$cron_disabled   = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;

		// Check WP Version (Requires >= 5.8).
		if ( version_compare( $wp_ver, '5.8', '<' ) ) {
			$status   = self::STATUS_CRITICAL;
			$issues[] = [
				'title'       => __( 'Unsupported WordPress Core', 'seo-city-pages' ),
				'explanation' => sprintf( __( 'WordPress %s is installed. Modern blocks and sitemaps require WP 5.8+.', 'seo-city-pages' ), $wp_ver ),
				'action'      => __( 'Upgrade WordPress to the latest stable release.', 'seo-city-pages' ),
			];
		}

		// Check WP-Cron.
		if ( $cron_disabled ) {
			$issues[] = [
				'title'       => __( 'Alternate / Disabled WP-Cron', 'seo-city-pages' ),
				'explanation' => __( 'DISABLE_WP_CRON is enabled in wp-config.php. Ensure server system cron (crontab) is active.', 'seo-city-pages' ),
				'action'      => __( 'Verify that your server executes wp-cron.php via crontab regularly.', 'seo-city-pages' ),
			];
		}

		return [
			'status'          => $status,
			'wp_version'      => $wp_ver,
			'wp_memory_limit' => $wp_memory_limit,
			'multisite'       => $is_multisite ? 'Yes' : 'No',
			'wp_cron'         => $cron_disabled ? 'Custom Server Cron (DISABLE_WP_CRON)' : 'Standard WP-Cron',
			'issues'          => $issues,
		];
	}

	/**
	 * Check Database schema tables, version, and integrity (read-only).
	 *
	 * @return array
	 */
	public static function check_database_health(): array {
		global $wpdb;
		$status = self::STATUS_HEALTHY;
		$issues = [];

		$installed_db_version = get_option( Migrator::DB_VERSION_OPTION, '0.0.0' );
		$target_db_version    = Migrator::TARGET_DB_VERSION;

		if ( version_compare( $installed_db_version, $target_db_version, '<' ) ) {
			$status   = self::STATUS_WARNING;
			$issues[] = [
				'title'       => __( 'Pending Database Migration', 'seo-city-pages' ),
				'explanation' => sprintf( __( 'Database version is %s while plugin expects %s.', 'seo-city-pages' ), $installed_db_version, $target_db_version ),
				'action'      => __( 'Run database migration via System Status or Settings.', 'seo-city-pages' ),
			];
		}

		// Verify table existence.
		$required_tables = [
			Schema::TABLE_COUNTRIES,
			Schema::TABLE_STATES,
			Schema::TABLE_CITIES,
			Schema::TABLE_SERVICES,
			Schema::TABLE_IMPORT_JOBS,
			Schema::TABLE_CITY_PAGES,
			Schema::TABLE_CONTENT_VERSIONS,
			Schema::TABLE_GENERATION_JOBS,
			Schema::TABLE_AUDIT_LOGS,
			Schema::TABLE_BULK_JOBS,
			Schema::TABLE_BULK_JOB_ITEMS,
		];

		$missing_tables = [];
		foreach ( $required_tables as $table_constant ) {
			$table_name = Schema::get_table_name( $table_constant );
			$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
			if ( ! $exists ) {
				$missing_tables[] = $table_name;
			}
		}

		if ( ! empty( $missing_tables ) ) {
			$status   = self::STATUS_CRITICAL;
			$issues[] = [
				'title'       => __( 'Missing Database Tables', 'seo-city-pages' ),
				'explanation' => sprintf( __( 'The following required plugin tables are missing: %s', 'seo-city-pages' ), implode( ', ', $missing_tables ) ),
				'action'      => __( 'Run the Database Repair tool to restore missing tables.', 'seo-city-pages' ),
			];
		}

		return [
			'status'               => $status,
			'installed_db_version' => $installed_db_version,
			'target_db_version'    => $target_db_version,
			'tables_checked'       => count( $required_tables ),
			'missing_tables'       => $missing_tables,
			'issues'               => $issues,
		];
	}

	/**
	 * Check Elementor integration health (read-only).
	 *
	 * @return array
	 */
	public static function check_elementor_health(): array {
		$plugin_dir   = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : '';
		$is_installed = defined( 'ELEMENTOR_VERSION' ) || ( ! empty( $plugin_dir ) && file_exists( $plugin_dir . '/elementor/elementor.php' ) );
		$is_active    = ( function_exists( 'did_action' ) && did_action( 'elementor/loaded' ) ) || class_exists( '\Elementor\Plugin' );
		$version      = defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'Unknown';

		$status = self::STATUS_HEALTHY;
		$issues = [];

		if ( ! $is_active ) {
			$status   = self::STATUS_WARNING;
			$issues[] = [
				'title'       => __( 'Elementor Not Active', 'seo-city-pages' ),
				'explanation' => __( 'Elementor is not loaded. Elementor dynamic tags and page templates will be inactive.', 'seo-city-pages' ),
				'action'      => __( 'Activate the Elementor plugin if using Elementor page templates.', 'seo-city-pages' ),
			];
		}

		return [
			'status'          => $status,
			'is_installed'    => $is_installed ? 'Yes' : 'No',
			'is_active'       => $is_active ? 'Yes' : 'No',
			'version'         => $version,
			'dynamic_tags'    => class_exists( '\Elementor\Core\DynamicTags\Manager' ) ? 'Supported' : 'Not Loaded',
			'issues'          => $issues,
		];
	}

	/**
	 * Check Arolax theme health (read-only, never modifies files).
	 *
	 * @return array
	 */
	public static function check_arolax_health(): array {
		$theme_name    = 'Unknown';
		$theme_slug    = 'Unknown';
		$is_arolax     = false;
		$theme_dir     = '';

		if ( function_exists( 'wp_get_theme' ) ) {
			$theme         = wp_get_theme();
			$theme_name    = $theme->get( 'Name' );
			$theme_slug    = $theme->get_stylesheet();
			$template_slug = $theme->get_template();
			$is_arolax     = ( 'arolax' === strtolower( $theme_slug ) || 'arolax' === strtolower( $template_slug ) || strpos( strtolower( $theme_name ), 'arolax' ) !== false );
		}
		if ( function_exists( 'get_template_directory' ) ) {
			$theme_dir = get_template_directory();
		}

		return [
			'status'        => self::STATUS_HEALTHY,
			'theme_name'    => $theme_name,
			'theme_slug'    => $theme_slug,
			'is_arolax'     => $is_arolax ? 'Yes' : 'No',
			'theme_dir'     => $theme_dir,
			'issues'        => [],
		];
	}

	/**
	 * Check SEO provider status and multi-plugin conflicts.
	 *
	 * @return array
	 */
	public static function check_seo_provider_health(): array {
		$provider  = 'native';
		$conflicts = [];

		if ( class_exists( '\SeoCityPages\SEO\SEO_Environment_Detector' ) ) {
			$active_plugins = \SeoCityPages\SEO\SEO_Environment_Detector::get_active_plugins();
			$provider       = \SeoCityPages\SEO\SEO_Environment_Detector::get_primary_adapter_slug();
			if ( count( $active_plugins ) > 1 ) {
				$conflicts = $active_plugins;
			}
		}

		$status = self::STATUS_HEALTHY;
		$issues = [];

		if ( ! empty( $conflicts ) ) {
			$status   = self::STATUS_WARNING;
			$issues[] = [
				'title'       => __( 'Multiple SEO Plugins Detected', 'seo-city-pages' ),
				'explanation' => sprintf( __( 'Detected active plugins: %s. Using %s as primary adapter.', 'seo-city-pages' ), implode( ', ', $conflicts ), $provider ),
				'action'      => __( 'We recommend having only one active SEO plugin to prevent meta tag duplication.', 'seo-city-pages' ),
			];
		}

		return [
			'status'    => $status,
			'provider'  => ucfirst( $provider ),
			'conflicts' => $conflicts,
			'issues'    => $issues,
		];
	}

	/**
	 * Check caching environment.
	 *
	 * @return array
	 */
	public static function check_cache_environment(): array {
		$object_cache = function_exists( 'wp_using_ext_object_cache' ) && wp_using_ext_object_cache();
		return [
			'status'       => self::STATUS_HEALTHY,
			'object_cache' => $object_cache ? 'External Persistent Cache Active (Redis/Memcached)' : 'Default Internal Object Cache',
			'issues'       => [],
		];
	}

	/**
	 * Check storage and write permissions.
	 *
	 * @return array
	 */
	public static function check_storage_health(): array {
		$uploads  = wp_upload_dir();
		$writable = function_exists( 'wp_is_writable' ) ? wp_is_writable( $uploads['basedir'] ) : is_writable( $uploads['basedir'] );

		$status = $writable ? self::STATUS_HEALTHY : self::STATUS_CRITICAL;
		$issues = [];

		if ( ! $writable ) {
			$issues[] = [
				'title'       => __( 'Uploads Directory Not Writable', 'seo-city-pages' ),
				'explanation' => sprintf( __( 'Directory %s is not writable by PHP.', 'seo-city-pages' ), $uploads['basedir'] ),
				'action'      => __( 'Check directory permissions on wp-content/uploads.', 'seo-city-pages' ),
			];
		}

		return [
			'status'      => $status,
			'uploads_dir' => $uploads['basedir'],
			'is_writable' => $writable ? 'Yes' : 'No',
			'issues'      => $issues,
		];
	}

	/**
	 * Check security settings.
	 *
	 * @return array
	 */
	public static function check_security_health(): array {
		$has_ssl = function_exists( 'is_ssl' ) && is_ssl();
		return [
			'status'  => self::STATUS_HEALTHY,
			'ssl'     => $has_ssl ? 'Active (HTTPS)' : 'Inactive (HTTP)',
			'issues'  => [],
		];
	}

	/**
	 * Generate a sanitized diagnostic support bundle (Zero secrets/API keys).
	 *
	 * @return array
	 */
	public static function generate_support_bundle(): array {
		$diagnostics = self::run_all_checks();
		$settings    = get_option( 'seo_city_pages_settings', [] );

		// Strictly sanitize settings before exporting.
		$sanitized_settings = [];
		foreach ( $settings as $k => $v ) {
			if ( stripos( $k, 'key' ) !== false || stripos( $k, 'token' ) !== false || stripos( $k, 'secret' ) !== false || stripos( $k, 'password' ) !== false ) {
				$sanitized_settings[ $k ] = ! empty( $v ) ? '***CONFIGURED***' : '***MISSING***';
			} else {
				$sanitized_settings[ $k ] = $v;
			}
		}

		return [
			'bundle_generated_at' => gmdate( 'Y-m-d H:i:s UTC' ),
			'plugin_version'      => defined( 'SEO_CITY_PAGES_VERSION' ) ? SEO_CITY_PAGES_VERSION : '0.8.0',
			'diagnostics'         => $diagnostics,
			'settings_sanitized'  => $sanitized_settings,
		];
	}

	/**
	 * Convert human readable size to bytes.
	 *
	 * @param string $val String size e.g. "256M".
	 * @return int
	 */
	private static function convert_hr_to_bytes( string $val ): int {
		$val  = trim( $val );
		$last = strtolower( substr( $val, -1 ) );
		$num  = (int) $val;

		switch ( $last ) {
			case 'g':
				$num *= 1024 * 1024 * 1024;
				break;
			case 'm':
				$num *= 1024 * 1024;
				break;
			case 'k':
				$num *= 1024;
				break;
		}

		return $num;
	}
}
