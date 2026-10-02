<?php
/**
 * Database Migration & Schema Version Manager.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrator Class.
 */
class Migrator {

	/**
	 * Option key for tracking database schema version.
	 */
	public const DB_VERSION_OPTION = 'seo_city_pages_db_version';

	/**
	 * Current target database schema version.
	 */
	public const TARGET_DB_VERSION = '0.11.0';

	/**
	 * Check and execute migrations if current DB version is behind target.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$current_version = get_option( self::DB_VERSION_OPTION, '0.0.0' );

		if ( version_compare( $current_version, self::TARGET_DB_VERSION, '>=' ) ) {
			return;
		}

		self::run_migrations( $current_version );
	}

	/**
	 * Alias for maybe_upgrade().
	 *
	 * @return void
	 */
	public static function migrate(): void {
		self::maybe_upgrade();
	}

	/**
	 * Run sequential migrations.
	 *
	 * @param string $from_version Starting version.
	 * @return void
	 */
	private static function run_migrations( string $from_version ): void {
		Logger::info(
			sprintf( 'Starting database migration from %s to %s', $from_version, self::TARGET_DB_VERSION ),
			[ 'from_version' => $from_version, 'target_version' => self::TARGET_DB_VERSION ]
		);

		// Migration to v0.1.0 (Foundation).
		if ( version_compare( $from_version, '0.1.0', '<' ) ) {
			self::migrate_to_0_1_0();
		}

		// Migration to v0.2.0 (Phase 3: Location & Service Tables).
		if ( version_compare( $from_version, '0.2.0', '<' ) ) {
			self::migrate_to_0_2_0();
		}

		// Migration to v0.3.0 (Phase 3 Revision: Import Jobs & City Pages Tables).
		if ( version_compare( $from_version, '0.3.0', '<' ) ) {
			self::migrate_to_0_3_0();
		}

		// Migration to v0.4.0 (Phase 4: Page Entity, URL Architecture & Indexes).
		if ( version_compare( $from_version, '0.4.0', '<' ) ) {
			self::migrate_to_0_4_0();
		}

		// Migration to v0.5.0 (Phase 6: OpenAI Content Versions & Generation Jobs).
		if ( version_compare( $from_version, '0.5.0', '<' ) ) {
			self::migrate_to_0_5_0();
		}

		// Migration to v0.6.0 (Phase 7: Review, Approval, Publishing & Audit Logs).
		if ( version_compare( $from_version, '0.6.0', '<' ) ) {
			self::migrate_to_0_6_0();
		}

		// Migration to v0.7.0 (Phase 8: SEO Metadata Overrides & Controls).
		if ( version_compare( $from_version, '0.7.0', '<' ) ) {
			self::migrate_to_0_7_0();
		}

		// Migration to v0.8.0 (Phase 9: Bulk Operations & Granular Job Items).
		if ( version_compare( $from_version, '0.8.0', '<' ) ) {
			self::migrate_to_0_8_0();
		}

		// Migration to v0.9.0 (Phase 11: Quality Evaluations, Fingerprints & Dismissals).
		if ( version_compare( $from_version, '0.9.0', '<' ) ) {
			self::migrate_to_0_9_0();
		}

		// Migration to v0.10.0 (Keywords CSV Upload & AI Generation Integration).
		if ( version_compare( $from_version, '0.10.0', '<' ) ) {
			self::migrate_to_0_10_0();
		}

		// Migration to v0.11.0 (Topic-Based County Batch Generator & Campaigns).
		if ( version_compare( $from_version, '0.11.0', '<' ) ) {
			self::migrate_to_0_11_0();
		}

		update_option( self::DB_VERSION_OPTION, self::TARGET_DB_VERSION );

		Logger::info(
			sprintf( 'Database migration completed successfully to %s', self::TARGET_DB_VERSION ),
			[ 'db_version' => self::TARGET_DB_VERSION ]
		);
	}


	/**
	 * Initial migration for v0.1.0 foundation.
	 *
	 * @return void
	 */
	private static function migrate_to_0_1_0(): void {
		// Foundation initialization.
	}

	/**
	 * Migration for v0.2.0: Install Location and Service tables.
	 *
	 * @return void
	 */
	private static function migrate_to_0_2_0(): void {
		Schema::install();
	}

	/**
	 * Migration for v0.3.0: Install Import Jobs and City Pages tables.
	 *
	 * @return void
	 */
	private static function migrate_to_0_3_0(): void {
		Schema::install();
	}

	/**
	 * Migration for v0.4.0: Update City Pages schema with url_path and indexes.
	 *
	 * @return void
	 */
	private static function migrate_to_0_4_0(): void {
		Schema::install();
	}

	/**
	 * Migration for v0.5.0: Install Content Versions & Generation Jobs tables.
	 *
	 * @return void
	 */
	private static function migrate_to_0_5_0(): void {
		Schema::install();
	}

	/**
	 * Migration for v0.6.0: Install Audit Logs, Publishing fields & Content Version review fields.
	 *
	 * @return void
	 */
	private static function migrate_to_0_6_0(): void {
		Schema::install();
	}

	/**
	 * Migration for v0.7.0: Install SEO metadata columns and duplicate detection hash indexes.
	 *
	 * @return void
	 */
	private static function migrate_to_0_7_0(): void {
		Schema::install();
		\SeoCityPages\Security\Capabilities::register_defaults();
	}

	/**
	 * Migration for v0.8.0: Install Bulk Jobs and Granular Job Items tables.
	 *
	 * @return void
	 */
	private static function migrate_to_0_8_0(): void {
		Schema::install();
		\SeoCityPages\Security\Capabilities::register_defaults();
	}

	/**
	 * Migration for v0.9.0: Install Quality Evaluations, Fingerprints & Dismissals tables.
	 *
	 * @return void
	 */
	private static function migrate_to_0_9_0(): void {
		Schema::install();
		\SeoCityPages\Security\Capabilities::register_defaults();
	}

	/**
	 * Migration for v0.10.0: Add Keywords columns (primary_keyword, target_keywords) to City Pages table.
	 *
	 * @return void
	 */
	private static function migrate_to_0_10_0(): void {
		Schema::install();
		\SeoCityPages\Security\Capabilities::register_defaults();
	}

	/**
	 * Migration for v0.11.0: Install County Campaigns table.
	 *
	 * @return void
	 */
	private static function migrate_to_0_11_0(): void {
		Schema::install();
		\SeoCityPages\Security\Capabilities::register_defaults();
	}

	/**
	 * Get current installed DB version.
	 *
	 * @return string
	 */
	public static function get_current_db_version(): string {
		return (string) get_option( self::DB_VERSION_OPTION, '0.0.0' );
	}
}
