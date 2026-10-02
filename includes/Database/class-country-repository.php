<?php
/**
 * Country Data Repository.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Country Repository Class.
 */
class Country_Repository {

	/**
	 * Get country by ID.
	 *
	 * @param int $id Country ID.
	 * @return object|null Country row or null.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Find country by normalized name.
	 *
	 * @param string $name Country name.
	 * @param int    $exclude_id Optional ID to exclude for updates.
	 * @return object|null
	 */
	public static function get_by_name( string $name, int $exclude_id = 0 ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTRIES );
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
	 * Get country by slug.
	 *
	 * @param string $slug Slug.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return object|null
	 */
	public static function get_by_slug( string $slug, int $exclude_id = 0 ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

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
	 * Generate unique slug for country.
	 *
	 * @param string $name Base name.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return string Unique slug.
	 */
	public static function generate_unique_slug( string $name, int $exclude_id = 0 ): string {
		$base_slug = sanitize_title( $name );
		if ( empty( $base_slug ) ) {
			$base_slug = 'country';
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
	 * Get countries matching query parameters.
	 *
	 * @param array $args Query arguments.
	 * @return object[] Array of country objects.
	 */
	public static function get_all( array $args = [] ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

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
			$where_clauses[] = '(name LIKE %s OR code LIKE %s OR slug LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], [ 'active', 'inactive' ], true ) ) {
			$where_clauses[] = 'status = %s';
			$params[] = $args['status'];
		}

		$allowed_orderby = [ 'id', 'name', 'code', 'slug', 'status', 'created_at', 'updated_at' ];
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
	 * Count countries matching query.
	 *
	 * @param array $args Query arguments.
	 * @return int Total count.
	 */
	public static function count( array $args = [] ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where_clauses[] = '(name LIKE %s OR code LIKE %s OR slug LIKE %s)';
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
	 * Create a new country record.
	 *
	 * @param array $data Input data.
	 * @return int|\WP_Error Inserted ID or error.
	 */
	public static function create( array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( empty( $name ) ) {
			return new \WP_Error( 'empty_name', __( 'Country name is required.', 'seo-city-pages' ) );
		}

		// Prevent duplicate country (case-insensitive normalized check).
		if ( self::get_by_name( $name ) ) {
			return new \WP_Error(
				'duplicate_country',
				sprintf(
					/* translators: %s: Country name */
					__( 'A country with the name "%s" already exists.', 'seo-city-pages' ),
					esc_html( $name )
				)
			);
		}

		$code = strtoupper( sanitize_text_field( $data['code'] ?? '' ) );
		$slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : self::generate_unique_slug( $name );
		$slug = self::generate_unique_slug( $slug );
		$status = ( isset( $data['status'] ) && 'inactive' === $data['status'] ) ? 'inactive' : 'active';
		$now = current_time( 'mysql', true );

		$inserted = $wpdb->insert(
			$table,
			[
				'name'       => $name,
				'code'       => $code,
				'slug'       => $slug,
				'status'     => $status,
				'created_at' => $now,
				'updated_at' => $now,
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $inserted ) {
			return new \WP_Error( 'db_insert_error', __( 'Failed to save country to database.', 'seo-city-pages' ) );
		}

		$country_id = (int) $wpdb->insert_id;

		Logger::info(
			sprintf( 'Country created: %s (ID: %d)', $name, $country_id ),
			[
				'user_id'    => get_current_user_id(),
				'country_id' => $country_id,
				'name'       => $name,
			]
		);

		return $country_id;
	}

	/**
	 * Update an existing country record.
	 *
	 * @param int   $id Country ID.
	 * @param array $data Input data.
	 * @return bool|\WP_Error True or error.
	 */
	public static function update( int $id, array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		$existing = self::get( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'Country not found.', 'seo-city-pages' ) );
		}

		$update_data   = [];
		$update_format = [];

		if ( isset( $data['name'] ) ) {
			$name = sanitize_text_field( $data['name'] );
			if ( empty( $name ) ) {
				return new \WP_Error( 'empty_name', __( 'Country name cannot be empty.', 'seo-city-pages' ) );
			}
			if ( self::get_by_name( $name, $id ) ) {
				return new \WP_Error(
					'duplicate_country',
					sprintf(
						/* translators: %s: Country name */
						__( 'Another country with the name "%s" already exists.', 'seo-city-pages' ),
						esc_html( $name )
					)
				);
			}
			$update_data['name'] = $name;
			$update_format[]     = '%s';
		}

		if ( isset( $data['code'] ) ) {
			$update_data['code'] = strtoupper( sanitize_text_field( $data['code'] ) );
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
			return new \WP_Error( 'db_update_error', __( 'Failed to update country.', 'seo-city-pages' ) );
		}

		Logger::info(
			sprintf( 'Country updated: %s (ID: %d)', $update_data['name'] ?? $existing->name, $id ),
			[
				'user_id'    => get_current_user_id(),
				'country_id' => $id,
				'changes'    => $update_data,
			]
		);

		return true;
	}

	/**
	 * Delete country if it has no dependent states or cities.
	 *
	 * @param int $id Country ID.
	 * @return bool|\WP_Error True or error.
	 */
	public static function delete( int $id ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		$existing = self::get( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'Country not found.', 'seo-city-pages' ) );
		}

		// Dependency validation.
		$state_count = State_Repository::count( [ 'country_id' => $id ] );
		$city_count  = City_Repository::count( [ 'country_id' => $id ] );
		$page_count  = City_Page_Repository::count_by_entity( 'country', $id );

		if ( $state_count > 0 || $city_count > 0 || $page_count > 0 ) {
			return new \WP_Error(
				'has_dependencies',
				sprintf(
					/* translators: 1: State count, 2: City count, 3: Page count */
					__( 'This country cannot be deleted because %1$d state(s), %2$d city/cities, and %3$d SEO City Page(s) depend on it. Please remove dependent records first.', 'seo-city-pages' ),
					$state_count,
					$city_count,
					$page_count
				)
			);
		}

		$deleted = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		if ( ! $deleted ) {
			return new \WP_Error( 'db_delete_error', __( 'Failed to delete country.', 'seo-city-pages' ) );
		}

		Logger::info(
			sprintf( 'Country deleted: %s (ID: %d)', $existing->name, $id ),
			[
				'user_id'    => get_current_user_id(),
				'country_id' => $id,
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
		$table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

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
	 * Bulk delete countries with dependency checks.
	 *
	 * @param int[] $ids Array of IDs.
	 * @return array Results summary ['deleted' => int, 'failed' => int, 'errors' => string[]].
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
