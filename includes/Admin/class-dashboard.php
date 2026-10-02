<?php
/**
 * Dashboard Admin Page Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Environment;
use SeoCityPages\Database\Migrator;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Import_Job_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Controller Class.
 */
class Dashboard {

	/**
	 * Render the Dashboard View.
	 *
	 * @return void
	 */
	public function render(): void {
		$diagnostics     = Environment::get_diagnostics();
		$db_version      = Migrator::get_current_db_version();
		$total_countries = Country_Repository::count();
		$total_states    = State_Repository::count();
		$total_cities    = City_Repository::count();
		$total_services  = Service_Repository::count();
		$total_pages     = City_Page_Repository::count();
		$recent_jobs     = Import_Job_Repository::get_recent_jobs( 5 );

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}
}
