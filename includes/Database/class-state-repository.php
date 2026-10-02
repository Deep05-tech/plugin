<?php
/**
 * State / Region Data Repository.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * State Repository Class.
 */
class State_Repository {

	/**
	 * Get state by ID.
	 *
	 * @param int $id State ID.
	 * @return object|null State row or null.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_STATES );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Find state by country ID and normalized name.
	 *
	 * @param int    $country_id Parent Country ID.
	 * @param string $name       State name.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return object|null
	 */
	public static function get_by_name( int $country_id, string $name, int $exclude_id = 0 ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_STATES );
		$clean_name = trim( strtolower( $name ) );

		$sql = "SELECT * FROM {$table} WHERE country_id = %d AND LOWER(TRIM(name)) = %s";
		$params = [ $country_id, $clean_name ];

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
	 * Get state by slug (within a country).
	 *
	 * @param int    $country_id Country ID.
	 * @param string $slug       Slug.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return object|null
	 */
	public static function get_by_slug( int $country_id, string $slug, int $exclude_id = 0 ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_STATES );

		$sql = "SELECT * FROM {$table} WHERE country_id = %d AND slug = %s";
		$params = [ $country_id, $slug ];

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
	 * Generate unique slug for state within country.
	 *
	 * @param int    $country_id Country ID.
	 * @param string $name       Base name.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return string Unique slug.
	 */
	public static function generate_unique_slug( int $country_id, string $name, int $exclude_id = 0 ): string {
		$base_slug = sanitize_title( $name );
		if ( empty( $base_slug ) ) {
			$base_slug = 'state';
		}

		$slug = $base_slug;
		$suffix = 2;

		while ( self::get_by_slug( $country_id, $slug, $exclude_id ) ) {
			$slug = $base_slug . '-' . $suffix;
			$suffix++;
		}

		return $slug;
	}

	/**
	 * Get states matching query parameters with parent country join.
	 *
	 * @param array $args Query arguments.
	 * @return object[]
	 */
	public static function get_all( array $args = [] ): array {
		global $wpdb;
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		$defaults = [
			'country_id' => 0,
			'search'     => '',
			'status'     => '',
			'orderby'    => 's.name',
			'order'      => 'ASC',
			'limit'      => 20,
			'offset'     => 0,
		];
		$args = wp_parse_args( $args, $defaults );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['country_id'] ) ) {
			$where_clauses[] = 's.country_id = %d';
			$params[] = (int) $args['country_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where_clauses[] = '(s.name LIKE %s OR s.code LIKE %s OR s.slug LIKE %s OR c.name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], [ 'active', 'inactive' ], true ) ) {
			$where_clauses[] = 's.status = %s';
			$params[] = $args['status'];
		}

		$allowed_orderby = [
			'id'           => 's.id',
			'name'         => 's.name',
			'code'         => 's.code',
			'slug'         => 's.slug',
			'status'       => 's.status',
			'country_name' => 'c.name',
			'created_at'   => 's.created_at',
			'updated_at'   => 's.updated_at',
		];
		$order_key = $allowed_orderby[ $args['orderby'] ] ?? 's.name';
		$order     = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';

		$limit  = max( 1, (int) $args['limit'] );
		$offset = max( 0, (int) $args['offset'] );

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "
			SELECT s.*, c.name AS country_name, c.code AS country_code
			FROM {$states_table} s
			LEFT JOIN {$countries_table} c ON s.country_id = c.id
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
	 * Count states matching query.
	 *
	 * @param array $args Query arguments.
	 * @return int Total count.
	 */
	public static function count( array $args = [] ): int {
		global $wpdb;
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['country_id'] ) ) {
			$where_clauses[] = 's.country_id = %d';
			$params[] = (int) $args['country_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where_clauses[] = '(s.name LIKE %s OR s.code LIKE %s OR s.slug LIKE %s OR c.name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], [ 'active', 'inactive' ], true ) ) {
			$where_clauses[] = 's.status = %s';
			$params[] = $args['status'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "
			SELECT COUNT(*)
			FROM {$states_table} s
			LEFT JOIN {$countries_table} c ON s.country_id = c.id
			WHERE {$where_sql}
		";

		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Create a new state record.
	 *
	 * @param array $data Input data.
	 * @return int|\WP_Error Inserted ID or error.
	 */
	public static function create( array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_STATES );

		$country_id = (int) ( $data['country_id'] ?? 0 );
		if ( $country_id <= 0 || ! Country_Repository::get( $country_id ) ) {
			return new \WP_Error( 'invalid_country', __( 'Please select a valid parent country.', 'seo-city-pages' ) );
		}

		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( empty( $name ) ) {
			return new \WP_Error( 'empty_name', __( 'State/region name is required.', 'seo-city-pages' ) );
		}

		// Prevent duplicate state in same country.
		if ( self::get_by_name( $country_id, $name ) ) {
			return new \WP_Error(
				'duplicate_state',
				sprintf(
					/* translators: %s: State name */
					__( 'A state/region with the name "%s" already exists in the selected country.', 'seo-city-pages' ),
					esc_html( $name )
				)
			);
		}

		$code = strtoupper( sanitize_text_field( $data['code'] ?? '' ) );
		$slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : self::generate_unique_slug( $country_id, $name );
		$slug = self::generate_unique_slug( $country_id, $slug );
		$status = ( isset( $data['status'] ) && 'inactive' === $data['status'] ) ? 'inactive' : 'active';
		$now = current_time( 'mysql', true );

		$inserted = $wpdb->insert(
			$table,
			[
				'country_id' => $country_id,
				'name'       => $name,
				'code'       => $code,
				'slug'       => $slug,
				'status'     => $status,
				'created_at' => $now,
				'updated_at' => $now,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $inserted ) {
			return new \WP_Error( 'db_insert_error', __( 'Failed to save state to database.', 'seo-city-pages' ) );
		}

		$state_id = (int) $wpdb->insert_id;

		Logger::info(
			sprintf( 'State created: %s (ID: %d, Country ID: %d)', $name, $state_id, $country_id ),
			[
				'user_id'    => get_current_user_id(),
				'state_id'   => $state_id,
				'country_id' => $country_id,
				'name'       => $name,
			]
		);

		return $state_id;
	}

	/**
	 * Update an existing state record.
	 *
	 * @param int   $id State ID.
	 * @param array $data Input data.
	 * @return bool|\WP_Error True or error.
	 */
	public static function update( int $id, array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_STATES );

		$existing = self::get( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'State not found.', 'seo-city-pages' ) );
		}

		$update_data   = [];
		$update_format = [];

		$target_country_id = isset( $data['country_id'] ) ? (int) $data['country_id'] : (int) $existing->country_id;
		if ( isset( $data['country_id'] ) ) {
			if ( $target_country_id <= 0 || ! Country_Repository::get( $target_country_id ) ) {
				return new \WP_Error( 'invalid_country', __( 'Please select a valid parent country.', 'seo-city-pages' ) );
			}
			$update_data['country_id'] = $target_country_id;
			$update_format[]           = '%d';
		}

		if ( isset( $data['name'] ) ) {
			$name = sanitize_text_field( $data['name'] );
			if ( empty( $name ) ) {
				return new \WP_Error( 'empty_name', __( 'State name cannot be empty.', 'seo-city-pages' ) );
			}
			if ( self::get_by_name( $target_country_id, $name, $id ) ) {
				return new \WP_Error(
					'duplicate_state',
					sprintf(
						/* translators: %s: State name */
						__( 'Another state with the name "%s" already exists in this country.', 'seo-city-pages' ),
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
				$slug = self::generate_unique_slug( $target_country_id, $update_data['name'] ?? $existing->name, $id );
			} else {
				$slug = self::generate_unique_slug( $target_country_id, $slug, $id );
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
			return new \WP_Error( 'db_update_error', __( 'Failed to update state.', 'seo-city-pages' ) );
		}

		Logger::info(
			sprintf( 'State updated: %s (ID: %d)', $update_data['name'] ?? $existing->name, $id ),
			[
				'user_id'  => get_current_user_id(),
				'state_id' => $id,
				'changes'  => $update_data,
			]
		);

		return true;
	}

	/**
	 * Delete state if it has no dependent cities.
	 *
	 * @param int $id State ID.
	 * @return bool|\WP_Error True or error.
	 */
	public static function delete( int $id ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_STATES );

		$existing = self::get( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'State not found.', 'seo-city-pages' ) );
		}

		// Dependency validation.
		$city_count = City_Repository::count( [ 'state_id' => $id ] );
		$page_count = City_Page_Repository::count_by_entity( 'state', $id );

		if ( $city_count > 0 || $page_count > 0 ) {
			return new \WP_Error(
				'has_dependencies',
				sprintf(
					/* translators: 1: City count, 2: Page count */
					__( 'This state cannot be deleted because %1$d city/cities and %2$d SEO City Page(s) depend on it. Please remove dependent records first.', 'seo-city-pages' ),
					$city_count,
					$page_count
				)
			);
		}

		$deleted = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		if ( ! $deleted ) {
			return new \WP_Error( 'db_delete_error', __( 'Failed to delete state.', 'seo-city-pages' ) );
		}

		Logger::info(
			sprintf( 'State deleted: %s (ID: %d)', $existing->name, $id ),
			[
				'user_id'  => get_current_user_id(),
				'state_id' => $id,
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
		$table = Schema::get_table_name( Schema::TABLE_STATES );

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
	 * Bulk delete states with dependency checks.
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
