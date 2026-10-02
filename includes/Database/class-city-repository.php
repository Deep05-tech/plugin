<?php
/**
 * City Data Repository.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * City Repository Class.
 */
class City_Repository {

	/**
	 * Get city by ID.
	 *
	 * @param int $id City ID.
	 * @return object|null City row or null.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITIES );

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Find city by location hierarchy and normalized name.
	 *
	 * @param int    $country_id Country ID.
	 * @param int    $state_id   State ID.
	 * @param string $name       City name.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return object|null
	 */
	public static function get_by_name( int $country_id, int $state_id, string $name, int $exclude_id = 0 ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITIES );
		$clean_name = trim( strtolower( $name ) );

		$sql = "SELECT * FROM {$table} WHERE country_id = %d AND state_id = %d AND LOWER(TRIM(name)) = %s";
		$params = [ $country_id, $state_id, $clean_name ];

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
	 * Get city by slug within state/country.
	 *
	 * @param int    $state_id State ID.
	 * @param string $slug     Slug.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return object|null
	 */
	public static function get_by_slug( int $state_id, string $slug, int $exclude_id = 0 ): ?object {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITIES );

		$sql = "SELECT * FROM {$table} WHERE state_id = %d AND slug = %s";
		$params = [ $state_id, $slug ];

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
	 * Generate unique slug for city within a state.
	 *
	 * @param int    $state_id State ID.
	 * @param string $name     Base name.
	 * @param int    $exclude_id Optional ID to exclude.
	 * @return string Unique slug.
	 */
	public static function generate_unique_slug( int $state_id, string $name, int $exclude_id = 0 ): string {
		$base_slug = sanitize_title( $name );
		if ( empty( $base_slug ) ) {
			$base_slug = 'city';
		}

		$slug = $base_slug;
		$suffix = 2;

		while ( self::get_by_slug( $state_id, $slug, $exclude_id ) ) {
			$slug = $base_slug . '-' . $suffix;
			$suffix++;
		}

		return $slug;
	}

	/**
	 * Get cities with parent state and country joins.
	 *
	 * @param array $args Query arguments.
	 * @return object[]
	 */
	public static function get_all( array $args = [] ): array {
		global $wpdb;
		$cities_table    = Schema::get_table_name( Schema::TABLE_CITIES );
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		$defaults = [
			'country_id' => 0,
			'state_id'   => 0,
			'search'     => '',
			'status'     => '',
			'orderby'    => 'ci.name',
			'order'      => 'ASC',
			'limit'      => 20,
			'offset'     => 0,
		];
		$args = wp_parse_args( $args, $defaults );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['country_id'] ) ) {
			$where_clauses[] = 'ci.country_id = %d';
			$params[] = (int) $args['country_id'];
		}

		if ( ! empty( $args['state_id'] ) ) {
			$where_clauses[] = 'ci.state_id = %d';
			$params[] = (int) $args['state_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where_clauses[] = '(ci.name LIKE %s OR ci.slug LIKE %s OR st.name LIKE %s OR co.name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], [ 'active', 'inactive' ], true ) ) {
			$where_clauses[] = 'ci.status = %s';
			$params[] = $args['status'];
		}

		$allowed_orderby = [
			'id'           => 'ci.id',
			'name'         => 'ci.name',
			'slug'         => 'ci.slug',
			'status'       => 'ci.status',
			'state_name'   => 'st.name',
			'country_name' => 'co.name',
			'created_at'   => 'ci.created_at',
			'updated_at'   => 'ci.updated_at',
		];
		$order_key = $allowed_orderby[ $args['orderby'] ] ?? 'ci.name';
		$order     = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';

		$limit  = max( 1, (int) $args['limit'] );
		$offset = max( 0, (int) $args['offset'] );

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "
			SELECT ci.*, st.name AS state_name, co.name AS country_name, co.code AS country_code
			FROM {$cities_table} ci
			LEFT JOIN {$states_table} st ON ci.state_id = st.id
			LEFT JOIN {$countries_table} co ON ci.country_id = co.id
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
	 * Count cities matching query.
	 *
	 * @param array $args Query arguments.
	 * @return int Total count.
	 */
	public static function count( array $args = [] ): int {
		global $wpdb;
		$cities_table    = Schema::get_table_name( Schema::TABLE_CITIES );
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['country_id'] ) ) {
			$where_clauses[] = 'ci.country_id = %d';
			$params[] = (int) $args['country_id'];
		}

		if ( ! empty( $args['state_id'] ) ) {
			$where_clauses[] = 'ci.state_id = %d';
			$params[] = (int) $args['state_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where_clauses[] = '(ci.name LIKE %s OR ci.slug LIKE %s OR st.name LIKE %s OR co.name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], [ 'active', 'inactive' ], true ) ) {
			$where_clauses[] = 'ci.status = %s';
			$params[] = $args['status'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "
			SELECT COUNT(*)
			FROM {$cities_table} ci
			LEFT JOIN {$states_table} st ON ci.state_id = st.id
			LEFT JOIN {$countries_table} co ON ci.country_id = co.id
			WHERE {$where_sql}
		";

		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Create a new city record.
	 *
	 * @param array $data Input data.
	 * @return int|\WP_Error Inserted ID or error.
	 */
	public static function create( array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITIES );

		$country_id = (int) ( $data['country_id'] ?? 0 );
		$state_id   = (int) ( $data['state_id'] ?? 0 );

		if ( $country_id <= 0 || ! Country_Repository::get( $country_id ) ) {
			return new \WP_Error( 'invalid_country', __( 'Please select a valid Country.', 'seo-city-pages' ) );
		}

		$state = State_Repository::get( $state_id );
		if ( ! $state || (int) $state->country_id !== $country_id ) {
			return new \WP_Error( 'invalid_state', __( 'Please select a valid State that belongs to the selected Country.', 'seo-city-pages' ) );
		}

		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( empty( $name ) ) {
			return new \WP_Error( 'empty_name', __( 'City name is required.', 'seo-city-pages' ) );
		}

		// Prevent duplicate city in same state & country.
		if ( self::get_by_name( $country_id, $state_id, $name ) ) {
			return new \WP_Error(
				'duplicate_city',
				sprintf(
					/* translators: %s: City name */
					__( 'A city named "%s" already exists in the selected state and country.', 'seo-city-pages' ),
					esc_html( $name )
				)
			);
		}

		$slug = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : self::generate_unique_slug( $state_id, $name );
		$slug = self::generate_unique_slug( $state_id, $slug );
		$status = ( isset( $data['status'] ) && 'inactive' === $data['status'] ) ? 'inactive' : 'active';

		$latitude  = isset( $data['latitude'] ) && is_numeric( $data['latitude'] ) ? (float) $data['latitude'] : null;
		$longitude = isset( $data['longitude'] ) && is_numeric( $data['longitude'] ) ? (float) $data['longitude'] : null;

		$local_industries = isset( $data['local_industries'] ) ? sanitize_textarea_field( $data['local_industries'] ) : null;
		$areas_served     = isset( $data['areas_served'] ) ? sanitize_textarea_field( $data['areas_served'] ) : null;
		$description      = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : null;
		$notes            = isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : null;

		$now = current_time( 'mysql', true );

		$insert_data = [
			'country_id'       => $country_id,
			'state_id'         => $state_id,
			'name'             => $name,
			'slug'             => $slug,
			'status'           => $status,
			'latitude'         => $latitude,
			'longitude'        => $longitude,
			'local_industries' => $local_industries,
			'areas_served'     => $areas_served,
			'description'      => $description,
			'notes'            => $notes,
			'created_at'       => $now,
			'updated_at'       => $now,
		];

		$inserted = $wpdb->insert(
			$table,
			$insert_data,
			[ '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( ! $inserted ) {
			return new \WP_Error( 'db_insert_error', __( 'Failed to save city to database.', 'seo-city-pages' ) );
		}

		$city_id = (int) $wpdb->insert_id;

		Logger::info(
			sprintf( 'City created: %s (ID: %d, State ID: %d, Country ID: %d)', $name, $city_id, $state_id, $country_id ),
			[
				'user_id'    => get_current_user_id(),
				'city_id'    => $city_id,
				'state_id'   => $state_id,
				'country_id' => $country_id,
				'name'       => $name,
			]
		);

		return $city_id;
	}

	/**
	 * Update an existing city record.
	 *
	 * @param int   $id City ID.
	 * @param array $data Input data.
	 * @return bool|\WP_Error True or error.
	 */
	public static function update( int $id, array $data ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITIES );

		$existing = self::get( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'City not found.', 'seo-city-pages' ) );
		}

		$target_country_id = isset( $data['country_id'] ) ? (int) $data['country_id'] : (int) $existing->country_id;
		$target_state_id   = isset( $data['state_id'] ) ? (int) $data['state_id'] : (int) $existing->state_id;

		if ( isset( $data['country_id'] ) && ( $target_country_id <= 0 || ! Country_Repository::get( $target_country_id ) ) ) {
			return new \WP_Error( 'invalid_country', __( 'Please select a valid Country.', 'seo-city-pages' ) );
		}

		$state = State_Repository::get( $target_state_id );
		if ( ! $state || (int) $state->country_id !== $target_country_id ) {
			return new \WP_Error( 'invalid_state', __( 'Please select a valid State that belongs to the selected Country.', 'seo-city-pages' ) );
		}

		$update_data   = [];
		$update_format = [];

		if ( isset( $data['country_id'] ) ) {
			$update_data['country_id'] = $target_country_id;
			$update_format[]           = '%d';
		}

		if ( isset( $data['state_id'] ) ) {
			$update_data['state_id'] = $target_state_id;
			$update_format[]         = '%d';
		}

		if ( isset( $data['name'] ) ) {
			$name = sanitize_text_field( $data['name'] );
			if ( empty( $name ) ) {
				return new \WP_Error( 'empty_name', __( 'City name cannot be empty.', 'seo-city-pages' ) );
			}
			if ( self::get_by_name( $target_country_id, $target_state_id, $name, $id ) ) {
				return new \WP_Error(
					'duplicate_city',
					sprintf(
						/* translators: %s: City name */
						__( 'Another city named "%s" already exists in this state and country.', 'seo-city-pages' ),
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
				$slug = self::generate_unique_slug( $target_state_id, $update_data['name'] ?? $existing->name, $id );
			} else {
				$slug = self::generate_unique_slug( $target_state_id, $slug, $id );
			}
			$update_data['slug'] = $slug;
			$update_format[]     = '%s';
		}

		if ( isset( $data['status'] ) ) {
			$update_data['status'] = 'inactive' === $data['status'] ? 'inactive' : 'active';
			$update_format[]       = '%s';
		}

		if ( array_key_exists( 'latitude', $data ) ) {
			$update_data['latitude'] = ( '' !== $data['latitude'] && is_numeric( $data['latitude'] ) ) ? (float) $data['latitude'] : null;
			$update_format[]         = '%f';
		}

		if ( array_key_exists( 'longitude', $data ) ) {
			$update_data['longitude'] = ( '' !== $data['longitude'] && is_numeric( $data['longitude'] ) ) ? (float) $data['longitude'] : null;
			$update_format[]          = '%f';
		}

		if ( array_key_exists( 'local_industries', $data ) ) {
			$update_data['local_industries'] = sanitize_textarea_field( $data['local_industries'] );
			$update_format[]                 = '%s';
		}

		if ( array_key_exists( 'areas_served', $data ) ) {
			$update_data['areas_served'] = sanitize_textarea_field( $data['areas_served'] );
			$update_format[]             = '%s';
		}

		if ( array_key_exists( 'description', $data ) ) {
			$update_data['description'] = wp_kses_post( $data['description'] );
			$update_format[]            = '%s';
		}

		if ( array_key_exists( 'notes', $data ) ) {
			$update_data['notes'] = sanitize_textarea_field( $data['notes'] );
			$update_format[]      = '%s';
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
			return new \WP_Error( 'db_update_error', __( 'Failed to update city.', 'seo-city-pages' ) );
		}

		Logger::info(
			sprintf( 'City updated: %s (ID: %d)', $update_data['name'] ?? $existing->name, $id ),
			[
				'user_id' => get_current_user_id(),
				'city_id' => $id,
				'changes' => $update_data,
			]
		);

		return true;
	}

	/**
	 * Delete city.
	 *
	 * @param int $id City ID.
	 * @return bool|\WP_Error True or error.
	 */
	public static function delete( int $id ) {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CITIES );

		$existing = self::get( $id );
		if ( ! $existing ) {
			return new \WP_Error( 'not_found', __( 'City not found.', 'seo-city-pages' ) );
		}

		// Dependency validation.
		$page_count = City_Page_Repository::count_by_entity( 'city', $id );
		if ( $page_count > 0 ) {
			return new \WP_Error(
				'has_dependencies',
				sprintf(
					/* translators: %d: Page count */
					__( 'This city cannot be deleted because %d SEO City Page(s) depend on it. Please remove dependent pages first.', 'seo-city-pages' ),
					$page_count
				)
			);
		}

		$deleted = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		if ( ! $deleted ) {
			return new \WP_Error( 'db_delete_error', __( 'Failed to delete city.', 'seo-city-pages' ) );
		}

		Logger::info(
			sprintf( 'City deleted: %s (ID: %d)', $existing->name, $id ),
			[
				'user_id' => get_current_user_id(),
				'city_id' => $id,
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
		$table = Schema::get_table_name( Schema::TABLE_CITIES );

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
	 * Bulk delete cities.
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
