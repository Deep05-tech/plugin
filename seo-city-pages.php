<?php
/**
 * Plugin Name:       SEO City Pages
 * Plugin URI:        https://github.com/uday/seo-city-pages
 * Description:       Data-driven SEO city landing pages for WordPress and Elementor.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            SEO City Pages Team
 * Text Domain:       seo-city-pages
 * Domain Path:       /languages
 *
 * @package           SeoCityPages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// CRITICAL MULTI-INSTANCE GUARD:
// If this plugin has already been loaded from another directory (e.g. flat vs nested, plugin/ vs seo-city-pages/),
// silently halt execution of this duplicate instance to prevent double menus, double forms, or duplicate database operations.
if ( defined( 'SEO_CITY_PAGES_LOADED' ) ) {
	return;
}
define( 'SEO_CITY_PAGES_LOADED', true );

// Automatically deactivate redundant duplicate plugin entries in active_plugins.
if ( is_admin() ) {
	add_action( 'admin_init', function() {
		$active_plugins = get_option( 'active_plugins', [] );
		if ( ! is_array( $active_plugins ) ) {
			return;
		}

		$current_basename = plugin_basename( __FILE__ );
		$duplicates       = [];

		foreach ( $active_plugins as $plugin_file ) {
			if ( $plugin_file !== $current_basename && (
				basename( $plugin_file ) === 'seo-city-pages.php'
				|| strpos( $plugin_file, 'seo-city-pages' ) !== false
				|| strpos( $plugin_file, 'plugin/seo-city-pages' ) !== false
			) ) {
				$duplicates[] = $plugin_file;
			}
		}

		if ( ! empty( $duplicates ) ) {
			$cleaned = array_values( array_diff( $active_plugins, $duplicates ) );
			update_option( 'active_plugins', $cleaned );
		}
	}, 1 );
}

// Core Plugin Constants.
if ( ! defined( 'SEO_CITY_PAGES_VERSION' ) ) {
	define( 'SEO_CITY_PAGES_VERSION', '0.1.0' );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_FILE' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_DIR' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_URL' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_BASENAME' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// Register PSR-4 Autoloader.
if ( ! class_exists( '\\SeoCityPages\\Autoloader' ) ) {
	require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/class-autoloader.php';
	\SeoCityPages\Autoloader::register( SEO_CITY_PAGES_PLUGIN_DIR . 'includes/' );
}

// Register Activation and Deactivation Hooks.
register_activation_hook( __FILE__, [ '\\SeoCityPages\\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\\SeoCityPages\\Plugin', 'deactivate' ] );

/**
 * Main Plugin Instance Accessor.
 *
 * @return \SeoCityPages\Plugin
 */
if ( ! function_exists( 'seo_city_pages' ) ) {
	function seo_city_pages(): \SeoCityPages\Plugin {
		return \SeoCityPages\Plugin::instance();
	}
}

// Bootstrap Plugin.
seo_city_pages()->init();
