<?php
/**
 * SEO City Page Data Repository.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\URL\URL_Engine;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * City Page Repository Class.
 */
class City_Page_Repository {

	/**
	 * Allowed statuses.
	 */
	public const STATUS_DRAFT     = 'draft';
	public const STATUS_PUBLISHED = 'published';
	public const STATUS_PRIVATE   = 'private';
	public const STATUS_TRASH     = 'trash';

	public const ALLOWED_STATUSES = [
		self::STATUS_DRAFT,
		self::STATUS_PUBLISHED,
		self::STATUS_PRIVATE,
		self::STATUS_TRASH,
	];

	public const ALLOWED_GENERATION_STATUSES = [
		'not_generated',
		'queued',
		'generating',
		'needs_review',
		'approved',
		'rejected',
		'manually_edited',
		'failed',
	];

	/**
	 * Get page record by primary ID with joined location and service details.
	 *
	 * @param int $id Primary ID.
	 * @return object|null
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$pages_table     = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$cities_table    = Schema::get_table_name( Schema::TABLE_CITIES );
		$services_table  = Schema::get_table_name( Schema::TABLE_SERVICES );

		$sql = "
			SELECT p.*,
				co.name AS country_name, co.code AS country_code, co.slug AS country_slug, co.status AS country_status,
				st.name AS state_name, st.code AS state_code, st.slug AS state_slug, st.status AS state_status,
				ci.name AS city_name, ci.slug AS city_slug, ci.status AS city_status, ci.latitude, ci.longitude,
				sv.name AS service_name, sv.slug AS service_slug, sv.status AS service_status,
				sv.default_hero_title, sv.default_hero_desc, sv.default_cta, sv.default_seo_title, sv.default_meta_desc
			FROM {$pages_table} p
			LEFT JOIN {$countries_table} co ON p.country_id = co.id
			LEFT JOIN {$states_table} st ON p.state_id = st.id
			LEFT JOIN {$cities_table} ci ON p.city_id = ci.id
			LEFT JOIN {$services_table} sv ON p.service_id = sv.id
			WHERE p.id = %d
			LIMIT 1
		";

		$row = $wpdb->get_row(
			$wpdb->prepare( $sql, $id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Fast indexed lookup by URL path.
	 *
	 * @param string $url_path Relative path e.g. "/seo-agency/rajkot/".
	 * @return object|null
	 */
	public static function get_by_url_path( string $url_path ): ?object {
		global $wpdb;
		$pages_table     = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$cities_table    = Schema::get_table_name( Schema::TABLE_CITIES );
		$services_table  = Schema::get_table_name( Schema::TABLE_SERVICES );

		$sql = "
			SELECT p.*,
				co.name AS country_name, co.code AS country_code, co.slug AS country_slug, co.status AS country_status,
				st.name AS state_name, st.code AS state_code, st.slug AS state_slug, st.status AS state_status,
				ci.name AS city_name, ci.slug AS city_slug, ci.status AS city_status,
				sv.name AS service_name, sv.slug AS service_slug, sv.status AS service_status
			FROM {$pages_table} p
			LEFT JOIN {$countries_table} co ON p.country_id = co.id
			LEFT JOIN {$states_table} st ON p.state_id = st.id
			LEFT JOIN {$cities_table} ci ON p.city_id = ci.id
			LEFT JOIN {$services_table} sv ON p.service_id = sv.id
			WHERE p.url_path = %s
			ORDER BY (CASE WHEN p.status = 'published' THEN 1 ELSE 0 END) DESC, p.id DESC
			LIMIT 1
		";

		$row = $wpdb->get_row(
			$wpdb->prepare( $sql, $url_path ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Find logical combination record by entity IDs.
	 *
	 * @param int $country_id Country ID.
	 * @param int $state_id   State ID.
	 * @param int $city_id    City ID.
	 * @param int $service_id Service ID.
	 * @return object|null
	 */
	public static function find_combination( int $country_id, int $state_id, int $city_id, int $service_id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE country_id = %d AND state_id = %d AND city_id = %d AND service_id = %d LIMIT 1",
				$country_id,
				$state_id,
				$city_id,
				$service_id
			)
		);

		return $row ?: null;
	}

	/**
	 * Generate derived human-readable page name.
	 *
	 * @param object|array $page Page record with joined service & city names.
	 * @return string Derived page title.
	 */
	public static function get_derived_title( $page ): string {
		if ( is_object( $page ) ) {
			if ( ! empty( $page->title_override ) ) {
				return $page->title_override;
			}
			$service = $page->service_name ?? 'Service';
			$city    = $page->city_name ?? 'City';
			return sprintf( '%s in %s', $service, $city );
		}

		if ( is_array( $page ) ) {
			if ( ! empty( $page['title_override'] ) ) {
				return $page['title_override'];
			}
			$service = $page['service_name'] ?? 'Service';
			$city    = $page['city_name'] ?? 'City';
			return sprintf( '%s in %s', $service, $city );
		}

		return __( 'SEO City Page', 'seo-city-pages' );
	}

	/**
	 * Create or find logical city page record idempotently.
	 *
	 * @param array $data Combination input attributes.
	 * @return array ['id' => int, 'created' => bool]
	 */
	public static function find_or_create( array $data ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$country_id = (int) ( $data['country_id'] ?? 0 );
		$state_id   = (int) ( $data['state_id'] ?? 0 );
		$city_id    = (int) ( $data['city_id'] ?? 0 );
		$service_id = (int) ( $data['service_id'] ?? 0 );

		if ( $country_id <= 0 || $state_id <= 0 || $city_id <= 0 || $service_id <= 0 ) {
			return [ 'id' => 0, 'created' => false, 'error' => 'invalid_entity_ids' ];
		}

		// Check if already exists.
		$existing = self::find_combination( $country_id, $state_id, $city_id, $service_id );
		if ( $existing ) {
			return [ 'id' => (int) $existing->id, 'created' => false ];
		}

		$country = Country_Repository::get( $country_id );
		$state   = State_Repository::get( $state_id );
		$city    = City_Repository::get( $city_id );
		$service = Service_Repository::get( $service_id );

		$slug = sanitize_title( "{$service->slug}-{$city->slug}" );
		$url_path = URL_Engine::build_path( $country, $state, $city, $service );

		$status          = sanitize_text_field( $data['status'] ?? self::STATUS_DRAFT );
		$import_job_id   = ! empty( $data['import_job_id'] ) ? sanitize_text_field( $data['import_job_id'] ) : null;
		$primary_keyword = ! empty( $data['primary_keyword'] ) ? sanitize_text_field( $data['primary_keyword'] ) : null;
		$target_keywords = ! empty( $data['target_keywords'] ) ? sanitize_textarea_field( $data['target_keywords'] ) : ( ! empty( $data['keywords'] ) ? sanitize_textarea_field( $data['keywords'] ) : null );
		$now             = current_time( 'mysql', true );

		$inserted = $wpdb->insert(
			$table,
			[
				'country_id'      => $country_id,
				'state_id'        => $state_id,
				'city_id'         => $city_id,
				'service_id'      => $service_id,
				'slug'            => $slug,
				'custom_slug'     => null,
				'url_path'        => $url_path,
				'title_override'  => null,
				'status'          => $status,
				'import_job_id'   => $import_job_id,
				'primary_keyword' => $primary_keyword,
				'target_keywords' => $target_keywords,
				'created_at'      => $now,
				'updated_at'      => $now,
			],
			[ '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $inserted ) {
			// Handle potential race condition.
			$existing = self::find_combination( $country_id, $state_id, $city_id, $service_id );
			if ( $existing ) {
				return [ 'id' => (int) $existing->id, 'created' => false ];
			}
			return [ 'id' => 0, 'created' => false, 'error' => 'insert_failed' ];
		}

		$new_id = (int) $wpdb->insert_id;

		Logger::info(
			sprintf( 'Created SEO City Page ID %d (%s)', $new_id, $url_path ),
			[ 'page_id' => $new_id, 'url_path' => $url_path, 'job_id' => $import_job_id ]
		);

		return [ 'id' => $new_id, 'created' => true ];
	}

	/**
	 * Update an existing SEO City Page.
	 *
	 * @param int   $id   Page ID.
	 * @param array $data Fields to update.
	 * @return bool|\WP_Error
	 */
	public static function update( int $id, array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$existing = self::get( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'Page record not found.', 'seo-city-pages' ) );
		}

		$update_data = [
			'updated_at' => current_time( 'mysql', true ),
		];
		$formats = [ '%s' ];

		if ( isset( $data['status'] ) && in_array( $data['status'], self::ALLOWED_STATUSES, true ) ) {
			$update_data['status'] = $data['status'];
			$formats[] = '%s';
		}

		if ( isset( $data['generation_status'] ) && in_array( $data['generation_status'], self::ALLOWED_GENERATION_STATUSES, true ) ) {
			$update_data['generation_status'] = $data['generation_status'];
			$formats[] = '%s';
		}

		if ( array_key_exists( 'current_version_id', $data ) ) {
			$update_data['current_version_id'] = $data['current_version_id'] ? (int) $data['current_version_id'] : null;
			$formats[] = '%d';
		}

		if ( array_key_exists( 'published_version_id', $data ) ) {
			$update_data['published_version_id'] = $data['published_version_id'] ? (int) $data['published_version_id'] : null;
			$formats[] = '%d';
		}

		if ( array_key_exists( 'is_published', $data ) ) {
			$update_data['is_published'] = ! empty( $data['is_published'] ) ? 1 : 0;
			$formats[] = '%d';
		}

		if ( array_key_exists( 'published_at', $data ) ) {
			$update_data['published_at'] = $data['published_at'] ?: null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'published_by', $data ) ) {
			$update_data['published_by'] = $data['published_by'] ? (int) $data['published_by'] : null;
			$formats[] = '%d';
		}

		if ( array_key_exists( 'wp_post_id', $data ) ) {
			$update_data['wp_post_id'] = $data['wp_post_id'] ? (int) $data['wp_post_id'] : null;
			$formats[] = '%d';
		}

		if ( isset( $data['title_override'] ) ) {
			$update_data['title_override'] = ! empty( $data['title_override'] ) ? sanitize_text_field( $data['title_override'] ) : null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'custom_slug', $data ) ) {
			$custom_slug = ! empty( $data['custom_slug'] ) ? sanitize_title( $data['custom_slug'] ) : null;
			$update_data['custom_slug'] = $custom_slug;
			$formats[] = '%s';

			// Recalculate url_path.
			$country = Country_Repository::get( (int) $existing->country_id );
			$state   = State_Repository::get( (int) $existing->state_id );
			$city    = City_Repository::get( (int) $existing->city_id );
			$service = Service_Repository::get( (int) $existing->service_id );

			$new_url_path = URL_Engine::build_path( $country, $state, $city, $service, (string) $custom_slug );

			// Check collision with another page.
			if ( URL_Engine::check_collision( $new_url_path, $id ) ) {
				return new \WP_Error(
					'url_collision',
					sprintf(
						/* translators: %s: Colliding URL path */
						__( 'URL collision detected: The URL "%s" is already assigned to another page.', 'seo-city-pages' ),
						esc_html( $new_url_path )
					)
				);
			}

			$update_data['url_path'] = $new_url_path;
			$formats[] = '%s';
		}

		// SEO Fields.
		if ( array_key_exists( 'seo_title', $data ) ) {
			$val = ! empty( $data['seo_title'] ) ? sanitize_text_field( $data['seo_title'] ) : null;
			$update_data['seo_title']      = $val;
			$update_data['seo_title_hash'] = $val !== null ? hash( 'sha256', trim( $val ) ) : null;
			$formats[] = '%s';
			$formats[] = '%s';
		}

		if ( array_key_exists( 'meta_description', $data ) ) {
			$val = ! empty( $data['meta_description'] ) ? sanitize_textarea_field( $data['meta_description'] ) : null;
			$update_data['meta_description'] = $val;
			$update_data['seo_desc_hash']    = $val !== null ? hash( 'sha256', trim( $val ) ) : null;
			$formats[] = '%s';
			$formats[] = '%s';
		}

		if ( array_key_exists( 'canonical_url', $data ) ) {
			$val = ! empty( $data['canonical_url'] ) ? esc_url_raw( trim( $data['canonical_url'] ) ) : null;
			$update_data['canonical_url']  = $val;
			$update_data['canonical_hash'] = $val !== null ? hash( 'sha256', trim( $val ) ) : null;
			$formats[] = '%s';
			$formats[] = '%s';
		}

		if ( array_key_exists( 'robots_index', $data ) ) {
			$update_data['robots_index'] = sanitize_text_field( $data['robots_index'] ?: 'default' );
			$formats[] = '%s';
		}

		if ( array_key_exists( 'robots_follow', $data ) ) {
			$update_data['robots_follow'] = sanitize_text_field( $data['robots_follow'] ?: 'default' );
			$formats[] = '%s';
		}

		if ( array_key_exists( 'robots_advanced', $data ) ) {
			$update_data['robots_advanced'] = ! empty( $data['robots_advanced'] ) ? sanitize_text_field( $data['robots_advanced'] ) : null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'og_title', $data ) ) {
			$update_data['og_title'] = ! empty( $data['og_title'] ) ? sanitize_text_field( $data['og_title'] ) : null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'og_description', $data ) ) {
			$update_data['og_description'] = ! empty( $data['og_description'] ) ? sanitize_textarea_field( $data['og_description'] ) : null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'og_image', $data ) ) {
			$update_data['og_image'] = ! empty( $data['og_image'] ) ? esc_url_raw( trim( $data['og_image'] ) ) : null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'twitter_title', $data ) ) {
			$update_data['twitter_title'] = ! empty( $data['twitter_title'] ) ? sanitize_text_field( $data['twitter_title'] ) : null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'twitter_description', $data ) ) {
			$update_data['twitter_description'] = ! empty( $data['twitter_description'] ) ? sanitize_textarea_field( $data['twitter_description'] ) : null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'twitter_image', $data ) ) {
			$update_data['twitter_image'] = ! empty( $data['twitter_image'] ) ? esc_url_raw( trim( $data['twitter_image'] ) ) : null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'schema_type', $data ) ) {
			$update_data['schema_type'] = sanitize_text_field( $data['schema_type'] ?: 'default' );
			$formats[] = '%s';
		}

		if ( array_key_exists( 'is_manual_seo_override', $data ) ) {
			$update_data['is_manual_seo_override'] = ! empty( $data['is_manual_seo_override'] ) ? 1 : 0;
			$formats[] = '%d';
		}

		if ( array_key_exists( 'primary_keyword', $data ) ) {
			$update_data['primary_keyword'] = ! empty( $data['primary_keyword'] ) ? sanitize_text_field( $data['primary_keyword'] ) : null;
			$formats[] = '%s';
		}

		if ( array_key_exists( 'target_keywords', $data ) ) {
			$update_data['target_keywords'] = ! empty( $data['target_keywords'] ) ? sanitize_textarea_field( $data['target_keywords'] ) : null;
			$formats[] = '%s';
		} elseif ( array_key_exists( 'keywords', $data ) ) {
			$update_data['target_keywords'] = ! empty( $data['keywords'] ) ? sanitize_textarea_field( $data['keywords'] ) : null;
			$formats[] = '%s';
		}

		$updated = $wpdb->update( $table, $update_data, [ 'id' => $id ], $formats, [ '%d' ] );

		if ( false === $updated ) {
			return new \WP_Error( 'db_update_error', __( 'Failed to update page in database.', 'seo-city-pages' ) );
		}

		Logger::info(
			sprintf( 'Updated SEO City Page ID %d', $id ),
			[ 'page_id' => $id, 'changes' => $update_data ]
		);

		return true;
	}

	/**
	 * Delete a single page record, its relations, and its mirrored WordPress post.
	 *
	 * @param int  $id             Page ID.
	 * @param bool $delete_wp_post Whether to delete the mirrored WordPress core post.
	 * @return bool
	 */
	public static function delete( int $id, bool $delete_wp_post = true ): bool {
		global $wpdb;
		$pages_table        = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$versions_table     = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );
		$quality_table      = Schema::get_table_name( Schema::TABLE_QUALITY_EVALUATIONS );
		$fingerprints_table = Schema::get_table_name( Schema::TABLE_CONTENT_FINGERPRINTS );
		$dismissals_table   = Schema::get_table_name( Schema::TABLE_QUALITY_DISMISSALS );

		$page = self::get( $id );
		if ( ! $page ) {
			return false;
		}

		// 1. Delete associated WordPress core post if requested.
		if ( $delete_wp_post && ! empty( $page->wp_post_id ) ) {
			$wp_post_id = (int) $page->wp_post_id;
			if ( function_exists( 'delete_post_meta' ) ) {
				delete_post_meta( $wp_post_id, \SeoCityPages\Integration\Post_Sync::META_PAGE_ID );
			}
			if ( function_exists( 'wp_delete_post' ) ) {
				wp_delete_post( $wp_post_id, true );
			}
		}

		// 2. Clean up relational tables.
		$wpdb->delete( $versions_table, [ 'page_id' => $id ], [ '%d' ] );
		$wpdb->delete( $quality_table, [ 'page_id' => $id ], [ '%d' ] );
		$wpdb->delete( $fingerprints_table, [ 'page_id' => $id ], [ '%d' ] );
		$wpdb->delete( $dismissals_table, [ 'page_id' => $id ], [ '%d' ] );

		// 3. Delete page record.
		$deleted = $wpdb->delete( $pages_table, [ 'id' => $id ], [ '%d' ] );

		Logger::info( sprintf( 'Deleted SEO City Page #%d and all associated versions', $id ), [ 'page_id' => $id ] );

		return false !== $deleted;
	}

	/**
	 * Bulk update page statuses.
	 *
	 * @param int[]  $ids    Array of Page IDs.
	 * @param string $status New status.
	 * @return int Number of updated records.
	 */
	public static function bulk_set_status( array $ids, string $status ): int {
		global $wpdb;
		if ( empty( $ids ) || ! in_array( $status, self::ALLOWED_STATUSES, true ) ) {
			return 0;
		}

		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$clean_ids = array_map( 'intval', $ids );
		$placeholders = implode( ', ', array_fill( 0, count( $clean_ids ), '%d' ) );
		$now = current_time( 'mysql', true );

		$sql = "UPDATE {$table} SET status = %s, updated_at = %s WHERE id IN ({$placeholders})";
		$params = array_merge( [ $status, $now ], $clean_ids );

		$updated = $wpdb->query(
			$wpdb->prepare( $sql, ...$params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return is_numeric( $updated ) ? (int) $updated : 0;
	}

	/**
	 * Bulk delete pages and their relations and mirrored WordPress posts.
	 *
	 * @param int[] $ids Array of Page IDs.
	 * @param bool  $delete_wp_post Whether to delete mirrored WP posts.
	 * @return int Number of deleted records.
	 */
	public static function bulk_delete( array $ids, bool $delete_wp_post = true ): int {
		$deleted_count = 0;
		foreach ( $ids as $id ) {
			if ( self::delete( (int) $id, $delete_wp_post ) ) {
				$deleted_count++;
			}
		}
		return $deleted_count;
	}

	/**
	 * Get page records matching search/filters with joined entity details.
	 *
	 * @param array $args Query arguments.
	 * @return object[]
	 */
	public static function get_all( array $args = [] ): array {
		global $wpdb;
		$pages_table     = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$cities_table    = Schema::get_table_name( Schema::TABLE_CITIES );
		$services_table  = Schema::get_table_name( Schema::TABLE_SERVICES );

		$defaults = [
			'country_id' => 0,
			'state_id'   => 0,
			'city_id'           => 0,
			'service_id'        => 0,
			'status'            => '',
			'generation_status' => '',
			'search'            => '',
			'orderby'           => 'p.id',
			'order'             => 'DESC',
			'limit'             => 20,
			'offset'            => 0,
		];
		$args = function_exists( '\\wp_parse_args' ) ? \wp_parse_args( $args, $defaults ) : ( function_exists( 'wp_parse_args' ) ? wp_parse_args( $args, $defaults ) : array_merge( $defaults, (array) $args ) );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['country_id'] ) ) {
			$where_clauses[] = 'p.country_id = %d';
			$params[] = (int) $args['country_id'];
		}
		if ( ! empty( $args['state_id'] ) ) {
			$where_clauses[] = 'p.state_id = %d';
			$params[] = (int) $args['state_id'];
		}
		if ( ! empty( $args['city_id'] ) ) {
			$where_clauses[] = 'p.city_id = %d';
			$params[] = (int) $args['city_id'];
		}
		if ( ! empty( $args['service_id'] ) ) {
			$where_clauses[] = 'p.service_id = %d';
			$params[] = (int) $args['service_id'];
		}
		if ( ! empty( $args['status'] ) && in_array( $args['status'], self::ALLOWED_STATUSES, true ) ) {
			$where_clauses[] = 'p.status = %s';
			$params[] = $args['status'];
		}
		if ( ! empty( $args['generation_status'] ) && in_array( $args['generation_status'], self::ALLOWED_GENERATION_STATUSES, true ) ) {
			$where_clauses[] = 'p.generation_status = %s';
			$params[] = $args['generation_status'];
		}
		if ( isset( $args['is_published'] ) && '' !== $args['is_published'] ) {
			$where_clauses[] = 'p.is_published = %d';
			$params[] = (int) $args['is_published'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where_clauses[] = '(p.url_path LIKE %s OR p.title_override LIKE %s OR ci.name LIKE %s OR sv.name LIKE %s OR st.name LIKE %s OR co.name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$allowed_orderby = [
			'id'           => 'p.id',
			'url_path'     => 'p.url_path',
			'status'       => 'p.status',
			'city_name'    => 'ci.name',
			'service_name' => 'sv.name',
			'state_name'   => 'st.name',
			'country_name' => 'co.name',
			'created_at'   => 'p.created_at',
			'updated_at'   => 'p.updated_at',
		];
		$order_key = $allowed_orderby[ $args['orderby'] ] ?? 'p.id';
		$order     = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$limit  = max( 1, (int) $args['limit'] );
		$offset = max( 0, (int) $args['offset'] );

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "
			SELECT p.*,
				co.name AS country_name, co.code AS country_code,
				st.name AS state_name, st.code AS state_code,
				ci.name AS city_name,
				sv.name AS service_name
			FROM {$pages_table} p
			LEFT JOIN {$countries_table} co ON p.country_id = co.id
			LEFT JOIN {$states_table} st ON p.state_id = st.id
			LEFT JOIN {$cities_table} ci ON p.city_id = ci.id
			LEFT JOIN {$services_table} sv ON p.service_id = sv.id
			WHERE {$where_sql}
			ORDER BY {$order_key} {$order}
			LIMIT %d OFFSET %d
		";
		$params[] = $limit;
		$params[] = $offset;

		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, ...$params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Count total page records matching query.
	 *
	 * @param array $args Filter parameters.
	 * @return int Total matching records.
	 */
	public static function count( array $args = [] ): int {
		global $wpdb;
		$pages_table     = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$cities_table    = Schema::get_table_name( Schema::TABLE_CITIES );
		$services_table  = Schema::get_table_name( Schema::TABLE_SERVICES );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['country_id'] ) ) {
			$where_clauses[] = 'p.country_id = %d';
			$params[] = (int) $args['country_id'];
		}
		if ( ! empty( $args['state_id'] ) ) {
			$where_clauses[] = 'p.state_id = %d';
			$params[] = (int) $args['state_id'];
		}
		if ( ! empty( $args['city_id'] ) ) {
			$where_clauses[] = 'p.city_id = %d';
			$params[] = (int) $args['city_id'];
		}
		if ( ! empty( $args['service_id'] ) ) {
			$where_clauses[] = 'p.service_id = %d';
			$params[] = (int) $args['service_id'];
		}
		if ( ! empty( $args['status'] ) && in_array( $args['status'], self::ALLOWED_STATUSES, true ) ) {
			$where_clauses[] = 'p.status = %s';
			$params[] = $args['status'];
		}
		if ( ! empty( $args['generation_status'] ) && in_array( $args['generation_status'], self::ALLOWED_GENERATION_STATUSES, true ) ) {
			$where_clauses[] = 'p.generation_status = %s';
			$params[] = $args['generation_status'];
		}
		if ( isset( $args['is_published'] ) && '' !== $args['is_published'] ) {
			$where_clauses[] = 'p.is_published = %d';
			$params[] = (int) $args['is_published'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where_clauses[] = '(p.url_path LIKE %s OR p.title_override LIKE %s OR ci.name LIKE %s OR sv.name LIKE %s OR st.name LIKE %s OR co.name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "
			SELECT COUNT(*)
			FROM {$pages_table} p
			LEFT JOIN {$countries_table} co ON p.country_id = co.id
			LEFT JOIN {$states_table} st ON p.state_id = st.id
			LEFT JOIN {$cities_table} ci ON p.city_id = ci.id
			LEFT JOIN {$services_table} sv ON p.service_id = sv.id
			WHERE {$where_sql}
		";

		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Count all city pages.
	 *
	 * @return int
	 */
	public static function count_all(): int {
		return self::count();
	}

	/**
	 * Count city pages by publishing status.
	 *
	 * @param string $status Publishing status ('published', 'draft', etc.).
	 * @return int
	 */
	public static function count_by_status( string $status ): int {
		if ( 'published' === $status ) {
			return self::count( [ 'is_published' => 1 ] );
		}
		return self::count( [ 'status' => $status ] );
	}

	/**
	 * Count city pages by AI generation status.
	 *
	 * @param string $generation_status Generation status ('needs_review', 'approved', etc.).
	 * @return int
	 */
	public static function count_by_generation_status( string $generation_status ): int {
		return self::count( [ 'generation_status' => $generation_status ] );
	}

	/**
	 * Retrieve currently published content version for a page.
	 *
	 * @param int $page_id City Page ID.
	 * @return object|null Content version row or null.
	 */
	public static function get_published_version( int $page_id ): ?object {
		global $wpdb;
		$pages_table    = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$versions_table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT v.* FROM {$versions_table} v
				 INNER JOIN {$pages_table} p ON p.published_version_id = v.id
				 WHERE p.id = %d AND p.is_published = 1 LIMIT 1",
				$page_id
			)
		);

		return $row ?: null;
	}

	/**
	 * Count dependent pages for a specific entity.
	 *
	 * @param string $entity_type 'country', 'state', 'city', or 'service'.
	 * @param int    $entity_id   Entity ID.
	 * @return int
	 */
	public static function count_by_entity( string $entity_type, int $entity_id ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$column_map = [
			'country' => 'country_id',
			'state'   => 'state_id',
			'city'    => 'city_id',
			'service' => 'service_id',
		];

		if ( ! isset( $column_map[ $entity_type ] ) || $entity_id <= 0 ) {
			return 0;
		}

		$col = $column_map[ $entity_type ];
		$sql = "SELECT COUNT(*) FROM {$table} WHERE {$col} = %d";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $entity_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Update SEO metadata for a page.
	 *
	 * @param int   $id        Page ID.
	 * @param array $seo_data  SEO metadata fields.
	 * @param bool  $is_manual Whether this is a manual override.
	 * @return bool|\WP_Error
	 */
	public static function update_seo_metadata( int $id, array $seo_data, bool $is_manual = false ) {
		if ( $is_manual ) {
			$seo_data['is_manual_seo_override'] = 1;
		}
		return self::update( $id, $seo_data );
	}

	/**
	 * Find pages sharing the same SEO title via fast SHA-256 hash lookup.
	 *
	 * @param string $title           SEO title string.
	 * @param int    $exclude_page_id Optional page ID to exclude.
	 * @return array
	 */
	public static function find_duplicate_seo_titles( string $title, int $exclude_page_id = 0 ): array {
		global $wpdb;
		$title = trim( $title );
		if ( empty( $title ) ) {
			return [];
		}

		$hash  = hash( 'sha256', $title );
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$sql = "SELECT id, url_path, seo_title FROM {$table} WHERE seo_title_hash = %s";
		$params = [ $hash ];

		if ( $exclude_page_id > 0 ) {
			$sql .= ' AND id != %d';
			$params[] = $exclude_page_id;
		}

		$sql .= ' LIMIT 10';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $results ?: [];
	}

	/**
	 * Find pages sharing the same meta description via fast SHA-256 hash lookup.
	 *
	 * @param string $desc            Meta description string.
	 * @param int    $exclude_page_id Optional page ID to exclude.
	 * @return array
	 */
	public static function find_duplicate_meta_descriptions( string $desc, int $exclude_page_id = 0 ): array {
		global $wpdb;
		$desc = trim( $desc );
		if ( empty( $desc ) ) {
			return [];
		}

		$hash  = hash( 'sha256', $desc );
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$sql = "SELECT id, url_path, meta_description FROM {$table} WHERE seo_desc_hash = %s";
		$params = [ $hash ];

		if ( $exclude_page_id > 0 ) {
			$sql .= ' AND id != %d';
			$params[] = $exclude_page_id;
		}

		$sql .= ' LIMIT 10';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $results ?: [];
	}

	/**
	 * Find pages sharing the same canonical URL via fast SHA-256 hash lookup.
	 *
	 * @param string $canonical       Canonical URL string.
	 * @param int    $exclude_page_id Optional page ID to exclude.
	 * @return array
	 */
	public static function find_duplicate_canonicals( string $canonical, int $exclude_page_id = 0 ): array {
		global $wpdb;
		$canonical = trim( $canonical );
		if ( empty( $canonical ) ) {
			return [];
		}

		$hash  = hash( 'sha256', $canonical );
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$sql = "SELECT id, url_path, canonical_url FROM {$table} WHERE canonical_hash = %s";
		$params = [ $hash ];

		if ( $exclude_page_id > 0 ) {
			$sql .= ' AND id != %d';
			$params[] = $exclude_page_id;
		}

		$sql .= ' LIMIT 10';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $results ?: [];
	}

	/**
	 * Retrieve published pages eligible for XML sitemap inclusion in chunked batches.
	 *
	 * @param int $limit  Batch size limit.
	 * @param int $offset Batch offset.
	 * @return array
	 */
	public static function get_published_pages_for_sitemap( int $limit = 1000, int $offset = 0 ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$sql = "
			SELECT id, url_path, updated_at, published_at, canonical_url
			FROM {$table}
			WHERE status = 'published'
			  AND is_published = 1
			  AND (robots_index != 'noindex' OR robots_index IS NULL)
			ORDER BY id ASC
			LIMIT %d OFFSET %d
		";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $limit, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $results ?: [];
	}

	/**
	 * Count total published pages eligible for XML sitemap inclusion.
	 *
	 * @return int
	 */
	public static function count_published_pages_for_sitemap(): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$sql = "
			SELECT COUNT(*)
			FROM {$table}
			WHERE status = 'published'
			  AND is_published = 1
			  AND (robots_index != 'noindex' OR robots_index IS NULL)
		";

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Gather comprehensive SEO duplicate & health statistics.
	 *
	 * @return array
	 */
	public static function get_seo_duplicate_stats(): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$total_pages = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$published_pages = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'published' AND is_published = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$manual_overrides = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_manual_seo_override = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$duplicate_titles = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM (
				SELECT seo_title_hash FROM {$table}
				WHERE seo_title_hash IS NOT NULL AND seo_title_hash != ''
				GROUP BY seo_title_hash HAVING COUNT(*) > 1
			) t"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$duplicate_descriptions = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM (
				SELECT seo_desc_hash FROM {$table}
				WHERE seo_desc_hash IS NOT NULL AND seo_desc_hash != ''
				GROUP BY seo_desc_hash HAVING COUNT(*) > 1
			) t"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$missing_titles = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE seo_title IS NULL OR seo_title = ''"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$missing_descriptions = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE meta_description IS NULL OR meta_description = ''"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return [
			'total_pages'            => $total_pages,
			'published_pages'        => $published_pages,
			'manual_overrides'       => $manual_overrides,
			'duplicate_titles'       => $duplicate_titles,
			'duplicate_descriptions' => $duplicate_descriptions,
			'missing_titles'         => $missing_titles,
			'missing_descriptions'   => $missing_descriptions,
		];
	}

	/**
	 * Convenience wrapper to create a city page record.
	 *
	 * @param array $data Input fields.
	 * @return int Inserted page ID or 0.
	 */
	public static function create( array $data ): int {
		$res = self::find_or_create( $data );
		return is_array( $res ) && isset( $res['id'] ) ? (int) $res['id'] : 0;
	}

	/**
	 * Get page record by city slug or page slug.
	 *
	 * @param string $slug Slug.
	 * @return object|null
	 */
	public static function get_by_slug( string $slug ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s OR custom_slug = %s OR slug LIKE %s LIMIT 1", $slug, $slug, '%' . $wpdb->esc_like( $slug ) . '%' ) );
		return $row ? self::get( (int) $row->id ) : null;
	}

	/**
	 * Get page record by combination.
	 *
	 * @param int $country_id Country ID.
	 * @param int $state_id   State ID.
	 * @param int $city_id    City ID.
	 * @param int $service_id Service ID.
	 * @return object|null
	 */
	public static function get_by_combination( int $country_id, int $state_id, int $city_id, int $service_id ): ?object {
		return self::find_combination( $country_id, $state_id, $city_id, $service_id );
	}

	/**
	 * Get pages by import job ID.
	 *
	 * @param string $job_id Job UUID.
	 * @return array
	 */
	public static function get_by_import_job_id( string $job_id ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE import_job_id = %s", $job_id ) ) ?: [];
	}
}
