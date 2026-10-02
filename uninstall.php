<?php
/**
 * SEO City Pages Uninstall Handler.
 *
 * @package SeoCityPages
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check explicit administrator setting for data deletion on uninstall.
$settings = get_option( 'seo_city_pages_settings', [] );
$delete_data = ! empty( $settings['delete_data_on_uninstall'] );

if ( ! $delete_data ) {
	// Administrator opted to preserve all plugin data, pages, and settings.
	return;
}

// If explicit deletion is enabled by administrator:
delete_option( 'seo_city_pages_settings' );
delete_option( 'seo_city_pages_db_version' );

// Clean up custom capabilities from roles.
require_once dirname( __FILE__ ) . '/includes/Security/class-capabilities.php';
\SeoCityPages\Security\Capabilities::unregister_all();

// Note: Future custom database tables and CPTs will be dropped here ONLY if delete_data is true.
