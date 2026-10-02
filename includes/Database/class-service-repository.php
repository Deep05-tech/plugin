<?php
/**
 * Service Data Repository.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service Repository Class.
 */
class Service_Repository {

	/**
	 * Get service by ID.
	 *
	 * @param int $id Service ID.
	 * @return object|null Service row or null.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Find service by normalized name.
	 *
	 * @param string $name Service name.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return object|null
	 */
	public static function get_by_name( string $name, int $exclude_id = 0 ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );
		$clean_name = trim( strtolower( $name ) );

		$sql = "SELECT * FROM {$table} WHERE LOWER(TRIM(name)) = %s";
		$params = [ $clean_name ];

		if ( $exclude_id > 0 ) {
			$sql .= ' AND id != %d';
			$params[] = $exclude_id;
		}
		$sql .= ' LIMIT 1';

		$row = $wpdb->get_row(
			$wpdb->prepare( $sql, ...$params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Get service by slug.
	 *
	 * @param string $slug Slug.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return object|null
	 */
	public static function get_by_slug( string $slug, int $exclude_id = 0 ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );

		$sql = "SELECT * FROM {$table} WHERE slug = %s";
		$params = [ $slug ];

		if ( $exclude_id > 0 ) {
			$sql .= ' AND id != %d';
			$params[] = $exclude_id;
		}
		$sql .= ' LIMIT 1';

		$row = $wpdb->get_row(
			$wpdb->prepare( $sql, ...$params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Generate unique slug for service.
	 *
	 * @param string $name Base name.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return string Unique slug.
	 */
	public static function generate_unique_slug( string $name, int $exclude_id = 0 ): string {
		$base_slug = sanitize_title( $name );
		if ( empty( $base_slug ) ) {
			$base_slug = 'service';
		}

		$slug = $base_slug;
		$suffix = 2;

		while ( self::get_by_slug( $slug, $exclude_id ) ) {
			$slug = $base_slug . '-' . $suffix;
			$suffix++;
		}

		return $slug;
	}

	/**
	 * Get services matching query.
	 *
	 * @param array $args Query arguments.
	 * @return object[]
	 */
	public static function get_all( array $args = [] ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );

		$defaults = [
			'search'  => '',
			'status'  => '',
			'orderby' => 'name',
			'order'   => 'ASC',
			'limit'   => 20,
			'offset'  => 0,
		];
		$args = wp_parse_args( $args, $defaults );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where_clauses[] = '(name LIKE %s OR slug LIKE %s OR description LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], [ 'active', 'inactive' ], true ) ) {
			$where_clauses[] = 'status = %s';
			$params[] = $args['status'];
		}

		$allowed_orderby = [ 'id', 'name', 'slug', 'status', 'created_at', 'updated_at' ];
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'name';
		$order   = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';

		$limit  = max( 1, (int) $args['limit'] );
		$offset = max( 0, (int) $args['offset'] );

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, ...$params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Count services matching query.
	 *
	 * @param array $args Query arguments.
	 * @return int Total count.
	 */
	public static function count( array $args = [] ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where_clauses[] = '(name LIKE %s OR slug LIKE %s OR description LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], [ 'active', 'inactive' ], true ) ) {
			$where_clauses[] = 'status = %s';
			$params[] = $args['status'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Create a new service record.
	 *
	 * @param array $data Input data.
	 * @return int|\WP_Error Inserted ID or error.
	 */
	public static function create( array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );

		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( empty( $name ) ) {
			return new \WP_Error( 'empty_name', __( 'Service name is required.', 'seo-city-pages' ) );
		}

		// Prevent duplicate service name (normalized case-insensitive).
		if ( self::get_by_name( $name ) ) {
			return new \WP_Error(
				'duplicate_service',
				sprintf(
					/* translators: %s: Service name */
					__( 'A service with the name "%s" already exists.', 'seo-city-pages' ),
					esc_html( $name )
				)
			);
		}

		$slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : self::generate_unique_slug( $name );
		$slug = self::generate_unique_slug( $slug );
		$status = ( isset( $data['status'] ) && 'inactive' === $data['status'] ) ? 'inactive' : 'active';

		$description        = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : null;
		$default_hero_title = isset( $data['default_hero_title'] ) ? sanitize_text_field( $data['default_hero_title'] ) : null;
		$default_hero_desc  = isset( $data['default_hero_desc'] ) ? wp_kses_post( $data['default_hero_desc'] ) : null;
		$default_intro      = isset( $data['default_intro'] ) ? wp_kses_post( $data['default_intro'] ) : null;
		$default_cta        = isset( $data['default_cta'] ) ? sanitize_text_field( $data['default_cta'] ) : null;
		$default_seo_title  = isset( $data['default_seo_title'] ) ? sanitize_text_field( $data['default_seo_title'] ) : null;
		$default_meta_desc  = isset( $data['default_meta_desc'] ) ? sanitize_textarea_field( $data['default_meta_desc'] ) : null;
		$ai_instructions    = isset( $data['ai_instructions'] ) ? sanitize_textarea_field( $data['ai_instructions'] ) : null;

		$now = current_time( 'mysql', true );

		$insert_data = [
			'name'               => $name,
			'slug'               => $slug,
			'description'        => $description,
			'status'             => $status,
			'default_hero_title' => $default_hero_title,
			'default_hero_desc'  => $default_hero_desc,
			'default_intro'      => $default_intro,
			'default_cta'        => $default_cta,
			'default_seo_title'  => $default_seo_title,
			'default_meta_desc'  => $default_meta_desc,
			'ai_instructions'    => $ai_instructions,
			'created_at'         => $now,
			'updated_at'         => $now,
		];

		$inserted = $wpdb->insert(
			$table,
			$insert_data,
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $inserted ) {
			return new \WP_Error( 'db_insert_error', __( 'Failed to save service to database.', 'seo-city-pages' ) );
		}

		$service_id = (int) $wpdb->insert_id;

		Logger::info(
			sprintf( 'Service created: %s (ID: %d)', $name, $service_id ),
			[
				'user_id'    => get_current_user_id(),
				'service_id' => $service_id,
				'name'       => $name,
			]
		);

		return $service_id;
	}

	/**
	 * Update an existing service record.
	 *
	 * @param int   $id Service ID.
	 * @param array $data Input data.
	 * @return bool|\WP_Error True or error.
	 */
	public static function update( int $id, array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );

		$existing = self::get( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'Service not found.', 'seo-city-pages' ) );
		}

		$update_data   = [];
		$update_format = [];

		if ( isset( $data['name'] ) ) {
			$name = sanitize_text_field( $data['name'] );
			if ( empty( $name ) ) {
				return new \WP_Error( 'empty_name', __( 'Service name cannot be empty.', 'seo-city-pages' ) );
			}
			if ( self::get_by_name( $name, $id ) ) {
				return new \WP_Error(
					'duplicate_service',
					sprintf(
						/* translators: %s: Service name */
						__( 'Another service named "%s" already exists.', 'seo-city-pages' ),
						esc_html( $name )
					)
				);
			}
			$update_data['name'] = $name;
			$update_format[]     = '%s';
		}

		if ( isset( $data['slug'] ) ) {
			$slug = sanitize_title( $data['slug'] );
			if ( empty( $slug ) ) {
				$slug = self::generate_unique_slug( $update_data['name'] ?? $existing->name, $id );
			} else {
				$slug = self::generate_unique_slug( $slug, $id );
			}
			$update_data['slug'] = $slug;
			$update_format[]     = '%s';
		}

		if ( isset( $data['status'] ) ) {
			$update_data['status'] = 'inactive' === $data['status'] ? 'inactive' : 'active';
			$update_format[]       = '%s';
		}

		if ( array_key_exists( 'description', $data ) ) {
			$update_data['description'] = wp_kses_post( $data['description'] );
			$update_format[]            = '%s';
		}

		if ( array_key_exists( 'default_hero_title', $data ) ) {
			$update_data['default_hero_title'] = sanitize_text_field( $data['default_hero_title'] );
			$update_format[]                   = '%s';
		}

		if ( array_key_exists( 'default_hero_desc', $data ) ) {
			$update_data['default_hero_desc'] = wp_kses_post( $data['default_hero_desc'] );
			$update_format[]                  = '%s';
		}

		if ( array_key_exists( 'default_intro', $data ) ) {
			$update_data['default_intro'] = wp_kses_post( $data['default_intro'] );
			$update_format[]              = '%s';
		}

		if ( array_key_exists( 'default_cta', $data ) ) {
			$update_data['default_cta'] = sanitize_text_field( $data['default_cta'] );
			$update_format[]            = '%s';
		}

		if ( array_key_exists( 'default_seo_title', $data ) ) {
			$update_data['default_seo_title'] = sanitize_text_field( $data['default_seo_title'] );
			$update_format[]                  = '%s';
		}

		if ( array_key_exists( 'default_meta_desc', $data ) ) {
			$update_data['default_meta_desc'] = sanitize_textarea_field( $data['default_meta_desc'] );
			$update_format[]                  = '%s';
		}

		if ( array_key_exists( 'ai_instructions', $data ) ) {
			$update_data['ai_instructions'] = sanitize_textarea_field( $data['ai_instructions'] );
			$update_format[]                = '%s';
		}

		if ( empty( $update_data ) ) {
			return true;
		}

		$update_data['updated_at'] = current_time( 'mysql', true );
		$update_format[]           = '%s';

		$updated = $wpdb->update(
			$table,
			$update_data,
			[ 'id' => $id ],
			$update_format,
			[ '%d' ]
		);

		if ( false === $updated ) {
			return new \WP_Error( 'db_update_error', __( 'Failed to update service.', 'seo-city-pages' ) );
		}

		Logger::info(
			sprintf( 'Service updated: %s (ID: %d)', $update_data['name'] ?? $existing->name, $id ),
			[
				'user_id'    => get_current_user_id(),
				'service_id' => $id,
				'changes'    => $update_data,
			]
		);

		return true;
	}

	/**
	 * Delete service.
	 *
	 * @param int $id Service ID.
	 * @return bool|\WP_Error True or error.
	 */
	public static function delete( int $id ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );

		$existing = self::get( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'Service not found.', 'seo-city-pages' ) );
		}

		// Dependency validation.
		$page_count = City_Page_Repository::count_by_entity( 'service', $id );
		if ( $page_count > 0 ) {
			return new \WP_Error(
				'has_dependencies',
				sprintf(
					/* translators: %d: Page count */
					__( 'This service cannot be deleted because %d SEO City Page(s) depend on it. Please remove dependent pages first.', 'seo-city-pages' ),
					$page_count
				)
			);
		}

		$deleted = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		if ( ! $deleted ) {
			return new \WP_Error( 'db_delete_error', __( 'Failed to delete service.', 'seo-city-pages' ) );
		}

		Logger::info(
			sprintf( 'Service deleted: %s (ID: %d)', $existing->name, $id ),
			[
				'user_id'    => get_current_user_id(),
				'service_id' => $id,
			]
		);

		return true;
	}

	/**
	 * Bulk update status.
	 *
	 * @param int[]  $ids Array of IDs.
	 * @param string $status New status ('active'|'inactive').
	 * @return int Updated count.
	 */
	public static function bulk_update_status( array $ids, string $status ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );

		$ids = array_filter( array_map( 'intval', $ids ) );
		if ( empty( $ids ) || ! in_array( $status, [ 'active', 'inactive' ], true ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$now          = current_time( 'mysql', true );

		$query = $wpdb->prepare(
			"UPDATE {$table} SET status = %s, updated_at = %s WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$status,
			$now,
			...$ids
		);

		$result = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $result;
	}

	/**
	 * Bulk delete services.
	 *
	 * @param int[] $ids Array of IDs.
	 * @return array Results summary.
	 */
	public static function bulk_delete( array $ids ): array {
		$deleted = 0;
		$failed  = 0;
		$errors  = [];

		foreach ( $ids as $id ) {
			$res = self::delete( (int) $id );
			if ( is_wp_error( $res ) ) {
				$failed++;
				$errors[] = sprintf( 'ID %d: %s', $id, $res->get_error_message() );
			} else {
				$deleted++;
			}
		}

		return [
			'deleted' => $deleted,
			'failed'  => $failed,
			'errors'  => $errors,
		];
	}
}
