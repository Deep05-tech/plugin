<?php
/**
 * Controlled Diagnostic Repair Operations Manager.
 *
 * @package SeoCityPages\Diagnostics
 */

namespace SeoCityPages\Diagnostics;

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\Migrator;
use SeoCityPages\Database\Audit_Log_Repository;
use SeoCityPages\Jobs\Bulk_Job_Repository;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repair Manager Class.
 */
class Repair_Manager {

	/**
	 * Re-run schema migration to verify or create missing tables/indexes safely.
	 *
	 * @return array
	 */
	public static function repair_database_schema(): array {
		Schema::install();
		update_option( Migrator::DB_VERSION_OPTION, Migrator::TARGET_DB_VERSION );

		Audit_Log_Repository::log_event( 'repair_database_schema', 'database', 0 );
		Logger::info( 'Repaired database schema and re-verified tables.', [], Logger::CAT_DB );

		return [
			'success' => true,
			'message' => __( 'Database schema tables and indexes successfully verified and updated.', 'seo-city-pages' ),
		];
	}

	/**
	 * Refresh WordPress rewrite rules cleanly.
	 *
	 * @return array
	 */
	public static function refresh_rewrite_rules(): array {
		if ( function_exists( 'flush_rewrite_rules' ) ) {
			flush_rewrite_rules( false );
		}

		Audit_Log_Repository::log_event( 'repair_refresh_rewrite_rules', 'system', 0 );
		Logger::info( 'Flushed and regenerated WordPress rewrite rules.', [], Logger::CAT_GENERAL );

		return [
			'success' => true,
			'message' => __( 'WordPress rewrite rules flushed and refreshed successfully.', 'seo-city-pages' ),
		];
	}

	/**
	 * Recover stale queue items across all bulk jobs.
	 *
	 * @param int $threshold_seconds Stale timeout.
	 * @return array
	 */
	public static function recover_stale_queue_locks( int $threshold_seconds = 1800 ): array {
		return Queue_Monitor::recover_stale_jobs( $threshold_seconds );
	}

	/**
	 * Safely regenerate Elementor CSS and meta cache if Elementor is active.
	 *
	 * @return array
	 */
	public static function regenerate_elementor_cache(): array {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return [
				'success' => false,
				'message' => __( 'Elementor is not currently active.', 'seo-city-pages' ),
			];
		}

		if ( isset( \Elementor\Plugin::$instance->files_manager ) && method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
			Audit_Log_Repository::log_event( 'repair_elementor_cache', 'elementor', 0 );
			Logger::info( 'Regenerated Elementor CSS & Data Cache.', [], Logger::CAT_GENERAL );

			return [
				'success' => true,
				'message' => __( 'Elementor CSS and data cache regenerated successfully.', 'seo-city-pages' ),
			];
		}

		return [
			'success' => false,
			'message' => __( 'Elementor files manager is not available.', 'seo-city-pages' ),
		];
	}
}
