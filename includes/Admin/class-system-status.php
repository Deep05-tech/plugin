<?php
/**
 * System Status Admin Page Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Environment;
use SeoCityPages\Database\Migrator;
use SeoCityPages\Logging\Logger;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * System Status Controller Class.
 */
class System_Status {

	/**
	 * Render the System Status View.
	 *
	 * @return void
	 */
	public function render(): void {
		$message = '';
		$notice_type = 'success';

		// Handle Clear Logs POST action.
		if ( isset( $_POST['seo_city_pages_clear_logs'] ) ) {
			Security::enforce_capability( Capabilities::VIEW_LOGS );

			$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
			if ( Security::verify_nonce( $nonce, 'clear_logs' ) ) {
				Logger::clear_logs();
				Logger::info( 'Logs cleared by administrator' );
				$message = __( 'Log file cleared successfully.', 'seo-city-pages' );
			} else {
				$message = __( 'Security verification failed. Unable to clear logs.', 'seo-city-pages' );
				$notice_type = 'error';
			}
		}

		$diagnostics = Environment::get_diagnostics();
		$db_version  = Migrator::get_current_db_version();
		$logs        = Logger::get_recent_logs( 50 );
		$log_file    = Logger::get_log_file_path();

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/system-status.php';
	}
}
