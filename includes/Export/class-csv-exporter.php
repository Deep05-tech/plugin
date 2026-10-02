<?php
/**
 * Streamed & Memory-Safe CSV Export Engine.
 *
 * @package SeoCityPages\Export
 */

namespace SeoCityPages\Export;

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Jobs\Bulk_Job_Repository;
use SeoCityPages\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Exporter Class.
 */
class CSV_Exporter {

	/**
	 * Export types.
	 */
	public const TYPE_LOCATIONS        = 'locations';
	public const TYPE_SERVICES         = 'services';
	public const TYPE_CITY_PAGES       = 'city_pages';
	public const TYPE_CONTENT_VERSIONS = 'content_versions';
	public const TYPE_SEO_METADATA     = 'seo_metadata';
	public const TYPE_JOB_ERRORS       = 'job_errors';

	/**
	 * Sanitize CSV cell against formula injection.
	 *
	 * @param mixed $value Field value.
	 * @return string Neutralized string.
	 */
	public static function neutralize_formula( $value ): string {
		$str = (string) ( $value ?? '' );
		$trimmed = trim( $str );
		if ( '' === $trimmed ) {
			return '';
		}

		$first_char = substr( $trimmed, 0, 1 );
		if ( in_array( $first_char, [ '=', '+', '-', '@' ], true ) ) {
			return "'" . $str;
		}

		return $str;
	}

	/**
	 * Write a single row to CSV stream with formula protection.
	 *
	 * @param resource $handle Open file/stream handle.
	 * @param array    $row    Row cells.
	 * @return int|false
	 */
	public static function write_row( $handle, array $row ) {
		$sanitized = array_map( [ self::class, 'neutralize_formula' ], $row );
		return fputcsv( $handle, $sanitized, ',', '"', '\\' );
	}

	/**
	 * Stream or export filtered dataset in chunks to prevent memory exhaustion.
	 *
	 * @param string          $type        Export type constant.
	 * @param array           $filters     Filter criteria.
	 * @param resource|string $destination 'stream' (php://output), 'temp' (returns temp file path), or file resource.
	 * @return string|bool Temp file path or boolean success.
	 */
	public static function export( string $type, array $filters = [], $destination = 'stream' ) {
		$is_stream_output = ( 'stream' === $destination );
		$is_temp_file     = ( 'temp' === $destination );

		$handle = null;
		$temp_file_path = null;

		if ( $is_stream_output ) {
			if ( ! headers_sent() ) {
				$filename = sprintf( 'seo-city-pages-%s-%s.csv', $type, date( 'Y-m-d-His' ) );
				header( 'Content-Type: text/csv; charset=UTF-8' );
				header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
				header( 'Pragma: no-cache' );
				header( 'Expires: 0' );
			}
			$handle = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		} elseif ( $is_temp_file ) {
			$temp_file_path = function_exists( 'wp_tempnam' ) ? wp_tempnam( 'scp_export_' . $type ) : tempnam( sys_get_temp_dir(), 'scp_export_' . $type );
			$handle = fopen( $temp_file_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		} elseif ( is_resource( $destination ) ) {
			$handle = $destination;
		}

		if ( ! $handle ) {
			return false;
		}

		// Add UTF-8 BOM for Microsoft Excel compatibility.
		fwrite( $handle, "\xEF\xBB\xBF" );

		switch ( $type ) {
			case self::TYPE_LOCATIONS:
				self::export_locations( $handle, $filters );
				break;

			case self::TYPE_SERVICES:
				self::export_services( $handle, $filters );
				break;

			case self::TYPE_CITY_PAGES:
				self::export_city_pages( $handle, $filters );
				break;

			case self::TYPE_CONTENT_VERSIONS:
				self::export_content_versions( $handle, $filters );
				break;

			case self::TYPE_SEO_METADATA:
				self::export_seo_metadata( $handle, $filters );
				break;

			case self::TYPE_JOB_ERRORS:
				self::export_job_errors( $handle, $filters );
				break;

			default:
				self::write_row( $handle, [ 'Error', 'Unknown export type' ] );
				break;
		}

		if ( $is_temp_file ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $temp_file_path;
		}

		if ( $is_stream_output ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			exit;
		}

		return true;
	}

	/**
	 * Export Locations in chunks.
	 */
	private static function export_locations( $handle, array $filters ): void {
		global $wpdb;
		$cities_table    = Schema::get_table_name( Schema::TABLE_CITIES );
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );

		self::write_row( $handle, [ 'Country', 'Country Code', 'State', 'State Code', 'City', 'City Slug', 'Status', 'Latitude', 'Longitude' ] );

		$chunk_size = 500;
		$offset     = 0;

		while ( true ) {
			$sql = "
				SELECT 
					c.name as country_name, c.code as country_code,
					s.name as state_name, s.code as state_code,
					ci.name as city_name, ci.slug as city_slug, ci.status, ci.latitude, ci.longitude
				FROM {$cities_table} ci
				LEFT JOIN {$states_table} s ON ci.state_id = s.id
				LEFT JOIN {$countries_table} c ON ci.country_id = c.id
				ORDER BY ci.id ASC
				LIMIT %d OFFSET %d
			";
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $chunk_size, $offset ) );
			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $r ) {
				self::write_row( $handle, [
					$r->country_name,
					$r->country_code,
					$r->state_name,
					$r->state_code,
					$r->city_name,
					$r->city_slug,
					$r->status,
					$r->latitude,
					$r->longitude,
				] );
			}

			$offset += count( $rows );
			if ( count( $rows ) < $chunk_size ) {
				break;
			}
		}
	}

	/**
	 * Export Services in chunks.
	 */
	private static function export_services( $handle, array $filters ): void {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_SERVICES );

		self::write_row( $handle, [ 'ID', 'Service Name', 'Slug', 'Status', 'Hero Title', 'SEO Title', 'Created At' ] );

		$chunk_size = 500;
		$offset     = 0;

		while ( true ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d", $chunk_size, $offset ) );
			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $r ) {
				self::write_row( $handle, [
					$r->id,
					$r->name,
					$r->slug,
					$r->status,
					$r->default_hero_title,
					$r->default_seo_title,
					$r->created_at,
				] );
			}

			$offset += count( $rows );
			if ( count( $rows ) < $chunk_size ) {
				break;
			}
		}
	}

	/**
	 * Export City Pages in chunks.
	 */
	private static function export_city_pages( $handle, array $filters ): void {
		global $wpdb;
		$pages_table     = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$cities_table    = Schema::get_table_name( Schema::TABLE_CITIES );
		$states_table    = Schema::get_table_name( Schema::TABLE_STATES );
		$countries_table = Schema::get_table_name( Schema::TABLE_COUNTRIES );
		$services_table  = Schema::get_table_name( Schema::TABLE_SERVICES );

		self::write_row( $handle, [
			'Page ID',
			'Country',
			'State',
			'City',
			'Service',
			'URL Path',
			'Page Status',
			'Generation Status',
			'Is Published',
			'WP Post ID',
			'SEO Title',
			'Meta Description',
			'Canonical URL',
			'Robots Index',
			'Robots Follow',
			'Created At',
		] );

		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 'p.status = %s';
			$params[] = sanitize_text_field( $filters['status'] );
		}
		if ( ! empty( $filters['generation_status'] ) ) {
			$where[]  = 'p.generation_status = %s';
			$params[] = sanitize_text_field( $filters['generation_status'] );
		}
		if ( isset( $filters['is_published'] ) && '' !== $filters['is_published'] ) {
			$where[]  = 'p.is_published = %d';
			$params[] = (int) $filters['is_published'];
		}

		$where_clause = implode( ' AND ', $where );
		$chunk_size   = 500;
		$offset       = 0;

		while ( true ) {
			$query_params = array_merge( $params, [ $chunk_size, $offset ] );
			$sql = "
				SELECT 
					p.*,
					c.name as country_name,
					s.name as state_name,
					ci.name as city_name,
					sv.name as service_name
				FROM {$pages_table} p
				LEFT JOIN {$countries_table} c ON p.country_id = c.id
				LEFT JOIN {$states_table} s ON p.state_id = s.id
				LEFT JOIN {$cities_table} ci ON p.city_id = ci.id
				LEFT JOIN {$services_table} sv ON p.service_id = sv.id
				WHERE {$where_clause}
				ORDER BY p.id ASC
				LIMIT %d OFFSET %d
			";

			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $query_params ) );
			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $r ) {
				self::write_row( $handle, [
					$r->id,
					$r->country_name,
					$r->state_name,
					$r->city_name,
					$r->service_name,
					$r->url_path,
					$r->status,
					$r->generation_status,
					$r->is_published ? 'Yes' : 'No',
					$r->wp_post_id ?: '',
					$r->seo_title ?: '',
					$r->meta_description ?: '',
					$r->canonical_url ?: '',
					$r->robots_index ?: 'default',
					$r->robots_follow ?: 'default',
					$r->created_at,
				] );
			}

			$offset += count( $rows );
			if ( count( $rows ) < $chunk_size ) {
				break;
			}
		}
	}

	/**
	 * Export Content Versions in chunks.
	 */
	private static function export_content_versions( $handle, array $filters ): void {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		self::write_row( $handle, [ 'Version ID', 'Page ID', 'Version Num', 'Status', 'Provider', 'Model', 'Tokens', 'Source', 'Approved By', 'Published At', 'Created At' ] );

		$chunk_size = 500;
		$offset     = 0;

		while ( true ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d", $chunk_size, $offset ) );
			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $r ) {
				self::write_row( $handle, [
					$r->id,
					$r->page_id,
					$r->version_num,
					$r->status,
					$r->provider,
					$r->model,
					$r->total_tokens,
					$r->source,
					$r->approved_by ?: '',
					$r->published_at ?: '',
					$r->created_at,
				] );
			}

			$offset += count( $rows );
			if ( count( $rows ) < $chunk_size ) {
				break;
			}
		}
	}

	/**
	 * Export SEO Metadata in chunks.
	 */
	private static function export_seo_metadata( $handle, array $filters ): void {
		self::export_city_pages( $handle, $filters );
	}

	/**
	 * Export Job Error Report in chunks.
	 */
	private static function export_job_errors( $handle, array $filters ): void {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );

		$job_id = sanitize_text_field( $filters['job_id'] ?? '' );
		self::write_row( $handle, [ 'Item ID', 'Job ID', 'Entity ID', 'Entity Type', 'Action', 'Attempts', 'Error Code', 'Error Message', 'Updated At' ] );

		if ( empty( $job_id ) ) {
			return;
		}

		$chunk_size = 500;
		$offset     = 0;

		while ( true ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE job_id = %s AND status = %s ORDER BY id ASC LIMIT %d OFFSET %d",
					$job_id,
					Bulk_Job_Repository::ITEM_FAILED,
					$chunk_size,
					$offset
				)
			);
			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $r ) {
				self::write_row( $handle, [
					$r->id,
					$r->job_id,
					$r->entity_id,
					$r->entity_type,
					$r->action,
					$r->attempts,
					$r->error_code ?: 'error',
					$r->error_message ?: 'Unknown error',
					$r->updated_at,
				] );
			}

			$offset += count( $rows );
			if ( count( $rows ) < $chunk_size ) {
				break;
			}
		}
	}
}
