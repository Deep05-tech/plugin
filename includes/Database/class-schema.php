<?php
/**
 * Database Schema Manager & Table Installer.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema Class.
 */
class Schema {

	/**
	 * Table names without prefix.
	 */
	public const TABLE_COUNTRIES         = 'seo_countries';
	public const TABLE_STATES            = 'seo_states';
	public const TABLE_CITIES            = 'seo_cities';
	public const TABLE_SERVICES          = 'seo_services';
	public const TABLE_IMPORT_JOBS       = 'seo_import_jobs';
	public const TABLE_CITY_PAGES        = 'seo_city_pages';
	public const TABLE_CONTENT_VERSIONS  = 'seo_content_versions';
	public const TABLE_GENERATION_JOBS   = 'seo_generation_jobs';
	public const TABLE_AUDIT_LOGS            = 'seo_audit_logs';
	public const TABLE_BULK_JOBS             = 'seo_bulk_jobs';
	public const TABLE_BULK_JOB_ITEMS        = 'seo_bulk_job_items';
	public const TABLE_QUALITY_EVALUATIONS   = 'seo_quality_evaluations';
	public const TABLE_CONTENT_FINGERPRINTS  = 'seo_content_fingerprints';
	public const TABLE_QUALITY_DISMISSALS    = 'seo_quality_dismissals';
	public const TABLE_KEYWORD_BANK          = 'seo_keyword_bank';
	public const TABLE_COUNTY_CAMPAIGNS      = 'seo_county_campaigns';

	/**
	 * Get full table name with WordPress prefix.
	 *
	 * @param string $table Table constant.
	 * @return string Full table name.
	 */
	public static function get_table_name( string $table ): string {
		global $wpdb;
		$prefix = isset( $wpdb->prefix ) ? $wpdb->prefix : 'wp_';
		return $prefix . $table;
	}

	/**
	 * Install or update database tables using dbDelta.
	 *
	 * @return void
	 */
	public static function install(): void {
		global $wpdb;

		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = ( isset( $wpdb ) && method_exists( $wpdb, 'get_charset_collate' ) ) ? $wpdb->get_charset_collate() : '';

		$countries_table        = self::get_table_name( self::TABLE_COUNTRIES );
		$states_table           = self::get_table_name( self::TABLE_STATES );
		$cities_table           = self::get_table_name( self::TABLE_CITIES );
		$services_table         = self::get_table_name( self::TABLE_SERVICES );
		$import_jobs_table      = self::get_table_name( self::TABLE_IMPORT_JOBS );
		$city_pages_table       = self::get_table_name( self::TABLE_CITY_PAGES );
		$content_versions_table = self::get_table_name( self::TABLE_CONTENT_VERSIONS );
		$generation_jobs_table  = self::get_table_name( self::TABLE_GENERATION_JOBS );
		$audit_logs_table       = self::get_table_name( self::TABLE_AUDIT_LOGS );
		$bulk_jobs_table            = self::get_table_name( self::TABLE_BULK_JOBS );
		$bulk_job_items_table       = self::get_table_name( self::TABLE_BULK_JOB_ITEMS );
		$quality_evaluations_table  = self::get_table_name( self::TABLE_QUALITY_EVALUATIONS );
		$content_fingerprints_table = self::get_table_name( self::TABLE_CONTENT_FINGERPRINTS );
		$quality_dismissals_table   = self::get_table_name( self::TABLE_QUALITY_DISMISSALS );
		$keyword_bank_table         = self::get_table_name( self::TABLE_KEYWORD_BANK );
		$county_campaigns_table     = self::get_table_name( self::TABLE_COUNTY_CAMPAIGNS );

		$sql = "
		CREATE TABLE {$countries_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			code VARCHAR(10) NOT NULL DEFAULT '',
			slug VARCHAR(100) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_slug (slug),
			KEY idx_status (status)
		) {$charset_collate};

		CREATE TABLE {$states_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			country_id BIGINT(20) UNSIGNED NOT NULL,
			name VARCHAR(100) NOT NULL,
			code VARCHAR(20) NOT NULL DEFAULT '',
			slug VARCHAR(100) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_country_id (country_id),
			KEY idx_slug (slug),
			KEY idx_status (status)
		) {$charset_collate};

		CREATE TABLE {$cities_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			country_id BIGINT(20) UNSIGNED NOT NULL,
			state_id BIGINT(20) UNSIGNED NOT NULL,
			name VARCHAR(100) NOT NULL,
			slug VARCHAR(100) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			latitude DECIMAL(10, 7) NULL DEFAULT NULL,
			longitude DECIMAL(10, 7) NULL DEFAULT NULL,
			local_industries TEXT NULL DEFAULT NULL,
			areas_served TEXT NULL DEFAULT NULL,
			description TEXT NULL DEFAULT NULL,
			notes TEXT NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_country_id (country_id),
			KEY idx_state_id (state_id),
			KEY idx_slug (slug),
			KEY idx_status (status)
		) {$charset_collate};

		CREATE TABLE {$services_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(150) NOT NULL,
			slug VARCHAR(150) NOT NULL,
			description TEXT NULL DEFAULT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			default_hero_title VARCHAR(255) NULL DEFAULT NULL,
			default_hero_desc TEXT NULL DEFAULT NULL,
			default_intro TEXT NULL DEFAULT NULL,
			default_cta VARCHAR(255) NULL DEFAULT NULL,
			default_seo_title VARCHAR(255) NULL DEFAULT NULL,
			default_meta_desc TEXT NULL DEFAULT NULL,
			ai_instructions TEXT NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_slug (slug),
			KEY idx_status (status)
		) {$charset_collate};

		CREATE TABLE {$import_jobs_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			job_id VARCHAR(64) NOT NULL,
			file_name VARCHAR(255) NOT NULL,
			file_path VARCHAR(255) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			total_rows INT UNSIGNED NOT NULL DEFAULT 0,
			processed_rows INT UNSIGNED NOT NULL DEFAULT 0,
			created_countries INT UNSIGNED NOT NULL DEFAULT 0,
			created_states INT UNSIGNED NOT NULL DEFAULT 0,
			created_cities INT UNSIGNED NOT NULL DEFAULT 0,
			created_services INT UNSIGNED NOT NULL DEFAULT 0,
			created_combinations INT UNSIGNED NOT NULL DEFAULT 0,
			existing_combinations INT UNSIGNED NOT NULL DEFAULT 0,
			duplicate_rows INT UNSIGNED NOT NULL DEFAULT 0,
			failed_rows INT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(30) NOT NULL DEFAULT 'pending',
			errors_summary LONGTEXT NULL DEFAULT NULL,
			started_at DATETIME NULL DEFAULT NULL,
			completed_at DATETIME NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_job_id (job_id),
			KEY idx_status (status)
		) {$charset_collate};

		CREATE TABLE {$city_pages_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			country_id BIGINT(20) UNSIGNED NOT NULL,
			state_id BIGINT(20) UNSIGNED NOT NULL,
			city_id BIGINT(20) UNSIGNED NOT NULL,
			service_id BIGINT(20) UNSIGNED NOT NULL,
			slug VARCHAR(200) NOT NULL,
			custom_slug VARCHAR(200) NULL DEFAULT NULL,
			url_path VARCHAR(255) NOT NULL DEFAULT '',
			title_override VARCHAR(255) NULL DEFAULT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'draft',
			generation_status VARCHAR(30) NOT NULL DEFAULT 'not_generated',
			current_version_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			published_version_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			wp_post_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			is_published TINYINT(1) NOT NULL DEFAULT 0,
			published_at DATETIME NULL DEFAULT NULL,
			published_by BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			is_source_valid TINYINT(1) NOT NULL DEFAULT 1,
			import_job_id VARCHAR(64) NULL DEFAULT NULL,
			seo_title VARCHAR(255) NULL DEFAULT NULL,
			meta_description TEXT NULL DEFAULT NULL,
			canonical_url VARCHAR(255) NULL DEFAULT NULL,
			robots_index VARCHAR(20) NOT NULL DEFAULT 'default',
			robots_follow VARCHAR(20) NOT NULL DEFAULT 'default',
			robots_advanced VARCHAR(100) NULL DEFAULT NULL,
			og_title VARCHAR(255) NULL DEFAULT NULL,
			og_description TEXT NULL DEFAULT NULL,
			og_image VARCHAR(255) NULL DEFAULT NULL,
			twitter_title VARCHAR(255) NULL DEFAULT NULL,
			twitter_description TEXT NULL DEFAULT NULL,
			twitter_image VARCHAR(255) NULL DEFAULT NULL,
			schema_type VARCHAR(50) NOT NULL DEFAULT 'default',
			is_manual_seo_override TINYINT(1) NOT NULL DEFAULT 0,
			seo_title_hash VARCHAR(64) NULL DEFAULT NULL,
			seo_desc_hash VARCHAR(64) NULL DEFAULT NULL,
			canonical_hash VARCHAR(64) NULL DEFAULT NULL,
			primary_keyword VARCHAR(255) NULL DEFAULT NULL,
			target_keywords TEXT NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uk_combo (country_id, state_id, city_id, service_id),
			KEY idx_url_path (url_path),
			KEY idx_country_id (country_id),
			KEY idx_state_id (state_id),
			KEY idx_city_id (city_id),
			KEY idx_service_id (service_id),
			KEY idx_slug (slug),
			KEY idx_status (status),
			KEY idx_generation_status (generation_status),
			KEY idx_current_version_id (current_version_id),
			KEY idx_published_version_id (published_version_id),
			KEY idx_wp_post_id (wp_post_id),
			KEY idx_city_service (city_id, service_id),
			KEY idx_seo_title_hash (seo_title_hash),
			KEY idx_seo_desc_hash (seo_desc_hash),
			KEY idx_canonical_hash (canonical_hash),
			KEY idx_status_published (status, is_published)
		) {$charset_collate};

		CREATE TABLE {$content_versions_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			page_id BIGINT(20) UNSIGNED NOT NULL,
			version_num INT UNSIGNED NOT NULL DEFAULT 1,
			status VARCHAR(30) NOT NULL DEFAULT 'needs_review',
			content_json LONGTEXT NOT NULL,
			content_hash VARCHAR(64) NOT NULL DEFAULT '',
			provider VARCHAR(50) NOT NULL DEFAULT 'openai',
			model VARCHAR(100) NOT NULL DEFAULT 'gpt-4o-mini',
			prompt_version VARCHAR(20) NOT NULL DEFAULT '1.0',
			schema_version VARCHAR(20) NOT NULL DEFAULT '1.0',
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			source VARCHAR(30) NOT NULL DEFAULT 'ai',
			is_manually_edited TINYINT(1) NOT NULL DEFAULT 0,
			input_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			total_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			generation_time_ms INT UNSIGNED NOT NULL DEFAULT 0,
			error_message TEXT NULL DEFAULT NULL,
			rejection_reason TEXT NULL DEFAULT NULL,
			rejected_at DATETIME NULL DEFAULT NULL,
			rejected_by BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			approved_at DATETIME NULL DEFAULT NULL,
			approved_by BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			published_at DATETIME NULL DEFAULT NULL,
			published_by BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			review_notes TEXT NULL DEFAULT NULL,
			parent_version_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_page_id (page_id),
			KEY idx_status (status),
			KEY idx_content_hash (content_hash),
			KEY idx_created_at (created_at)
		) {$charset_collate};

		CREATE TABLE {$generation_jobs_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			job_id VARCHAR(64) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			job_type VARCHAR(30) NOT NULL DEFAULT 'bulk_generate',
			provider VARCHAR(50) NOT NULL DEFAULT 'openai',
			model VARCHAR(100) NOT NULL DEFAULT 'gpt-4o-mini',
			total_pages INT UNSIGNED NOT NULL DEFAULT 0,
			processed_pages INT UNSIGNED NOT NULL DEFAULT 0,
			success_pages INT UNSIGNED NOT NULL DEFAULT 0,
			failed_pages INT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(30) NOT NULL DEFAULT 'pending',
			page_ids LONGTEXT NULL DEFAULT NULL,
			errors_summary LONGTEXT NULL DEFAULT NULL,
			external_batch_id VARCHAR(100) NULL DEFAULT NULL,
			started_at DATETIME NULL DEFAULT NULL,
			completed_at DATETIME NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_job_id (job_id),
			KEY idx_status (status)
		) {$charset_collate};

		CREATE TABLE {$audit_logs_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event_type VARCHAR(50) NOT NULL,
			entity_type VARCHAR(30) NOT NULL,
			entity_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			user_ip VARCHAR(45) NOT NULL DEFAULT '',
			action_details LONGTEXT NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_entity (entity_type, entity_id),
			KEY idx_event_type (event_type),
			KEY idx_created_at (created_at)
		) {$charset_collate};

		CREATE TABLE {$bulk_jobs_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			job_id VARCHAR(64) NOT NULL,
			job_type VARCHAR(50) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(30) NOT NULL DEFAULT 'pending',
			total_items INT UNSIGNED NOT NULL DEFAULT 0,
			pending_items INT UNSIGNED NOT NULL DEFAULT 0,
			processing_items INT UNSIGNED NOT NULL DEFAULT 0,
			completed_items INT UNSIGNED NOT NULL DEFAULT 0,
			failed_items INT UNSIGNED NOT NULL DEFAULT 0,
			skipped_items INT UNSIGNED NOT NULL DEFAULT 0,
			current_batch INT UNSIGNED NOT NULL DEFAULT 0,
			retry_count INT UNSIGNED NOT NULL DEFAULT 0,
			error_count INT UNSIGNED NOT NULL DEFAULT 0,
			is_dry_run TINYINT(1) NOT NULL DEFAULT 0,
			job_params LONGTEXT NULL DEFAULT NULL,
			job_summary LONGTEXT NULL DEFAULT NULL,
			created_entity_ids LONGTEXT NULL DEFAULT NULL,
			stale_heartbeat DATETIME NULL DEFAULT NULL,
			started_at DATETIME NULL DEFAULT NULL,
			completed_at DATETIME NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uk_job_id (job_id),
			KEY idx_job_type (job_type),
			KEY idx_status (status),
			KEY idx_created_at (created_at)
		) {$charset_collate};

		CREATE TABLE {$bulk_job_items_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			job_id VARCHAR(64) NOT NULL,
			entity_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			entity_type VARCHAR(30) NOT NULL DEFAULT 'city_page',
			action VARCHAR(50) NOT NULL DEFAULT '',
			status VARCHAR(30) NOT NULL DEFAULT 'pending',
			attempts INT UNSIGNED NOT NULL DEFAULT 0,
			max_attempts INT UNSIGNED NOT NULL DEFAULT 3,
			locked_at DATETIME NULL DEFAULT NULL,
			locked_by VARCHAR(64) NULL DEFAULT NULL,
			error_code VARCHAR(50) NULL DEFAULT NULL,
			error_message TEXT NULL DEFAULT NULL,
			item_payload LONGTEXT NULL DEFAULT NULL,
			item_result LONGTEXT NULL DEFAULT NULL,
			started_at DATETIME NULL DEFAULT NULL,
			completed_at DATETIME NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_job_item_status (job_id, status),
			KEY idx_locked (status, locked_at),
			KEY idx_entity (entity_type, entity_id)
		) {$charset_collate};

		CREATE TABLE {$quality_evaluations_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			page_id BIGINT(20) UNSIGNED NOT NULL,
			version_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			quality_score INT UNSIGNED NOT NULL DEFAULT 0,
			quality_status VARCHAR(20) NOT NULL DEFAULT 'needs_review',
			completeness_score DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
			thin_content_flag TINYINT(1) NOT NULL DEFAULT 0,
			similarity_score DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
			max_similar_page_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			city_relevance_score DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
			service_relevance_score DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
			intent_overlap_flag TINYINT(1) NOT NULL DEFAULT 0,
			overlap_page_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			placeholder_issues_count INT UNSIGNED NOT NULL DEFAULT 0,
			unsupported_claims_count INT UNSIGNED NOT NULL DEFAULT 0,
			cliche_count INT UNSIGNED NOT NULL DEFAULT 0,
			keyword_stuffing_flag TINYINT(1) NOT NULL DEFAULT 0,
			internal_link_count INT UNSIGNED NOT NULL DEFAULT 0,
			is_orphan TINYINT(1) NOT NULL DEFAULT 0,
			missing_seo_metadata TINYINT(1) NOT NULL DEFAULT 0,
			issues_json LONGTEXT NULL DEFAULT NULL,
			suggestions_json LONGTEXT NULL DEFAULT NULL,
			dimensions_json LONGTEXT NULL DEFAULT NULL,
			is_stale TINYINT(1) NOT NULL DEFAULT 0,
			evaluated_at DATETIME NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uk_page_version (page_id, version_id),
			KEY idx_quality_status (quality_status),
			KEY idx_quality_score (quality_score),
			KEY idx_stale (is_stale),
			KEY idx_similarity (similarity_score),
			KEY idx_thin (thin_content_flag)
		) {$charset_collate};

		CREATE TABLE {$content_fingerprints_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			page_id BIGINT(20) UNSIGNED NOT NULL,
			version_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			service_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			city_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			state_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			word_count INT UNSIGNED NOT NULL DEFAULT 0,
			normalized_hash VARCHAR(64) NOT NULL DEFAULT '',
			section_hashes_json LONGTEXT NULL DEFAULT NULL,
			shingles_json LONGTEXT NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uk_page_version_fp (page_id, version_id),
			KEY idx_service_city (service_id, city_id),
			KEY idx_norm_hash (normalized_hash),
			KEY idx_service_state (service_id, state_id)
		) {$charset_collate};

		CREATE TABLE {$quality_dismissals_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			page_id BIGINT(20) UNSIGNED NOT NULL,
			version_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			issue_type VARCHAR(50) NOT NULL,
			target_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			reason TEXT NULL DEFAULT NULL,
			dismissed_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			dismissed_at DATETIME NOT NULL,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_page_ver_issue (page_id, version_id, issue_type),
			KEY idx_issue_target (issue_type, target_id)
		) {$charset_collate};

		CREATE TABLE {$keyword_bank_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			keyword VARCHAR(255) NOT NULL,
			normalized_keyword VARCHAR(255) NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uk_norm_keyword (normalized_keyword),
			KEY idx_keyword (keyword)
		) {$charset_collate};

		CREATE TABLE {$county_campaigns_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id VARCHAR(64) NOT NULL,
			topic VARCHAR(150) NOT NULL,
			service_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			target_states TEXT NULL DEFAULT NULL,
			total_counties INT UNSIGNED NOT NULL DEFAULT 0,
			batch_size INT UNSIGNED NOT NULL DEFAULT 100,
			total_batches INT UNSIGNED NOT NULL DEFAULT 0,
			completed_batches INT UNSIGNED NOT NULL DEFAULT 0,
			total_pages INT UNSIGNED NOT NULL DEFAULT 0,
			generated_pages INT UNSIGNED NOT NULL DEFAULT 0,
			published_pages INT UNSIGNED NOT NULL DEFAULT 0,
			failed_pages INT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(30) NOT NULL DEFAULT 'ready',
			options LONGTEXT NULL DEFAULT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uk_campaign_id (campaign_id),
			KEY idx_topic (topic),
			KEY idx_status (status),
			KEY idx_created_at (created_at)
		) {$charset_collate};
		";

		if ( function_exists( 'dbDelta' ) ) {
			dbDelta( $sql );
		}
	}
}
