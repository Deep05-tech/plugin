<?php
/**
 * Audit Logger for lifecycle events and administrative actions.
 *
 * @package SeoCityPages\Logging
 */

namespace SeoCityPages\Logging;

use SeoCityPages\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audit_Logger Class.
 */
class Audit_Logger {

	/**
	 * Event type constants.
	 */
	public const EVENT_GENERATE           = 'generate';
	public const EVENT_EDIT               = 'edit';
	public const EVENT_APPROVE            = 'approve';
	public const EVENT_REJECT             = 'reject';
	public const EVENT_PUBLISH            = 'publish';
	public const EVENT_UNPUBLISH          = 'unpublish';
	public const EVENT_RESTORE            = 'restore';
	public const EVENT_BULK_ACTION        = 'bulk_action';
	public const EVENT_VALIDATION_FAILURE = 'validation_failure';
	public const EVENT_SECURITY_WARNING   = 'security_warning';

	/**
	 * Log an audit event.
	 *
	 * @param string $event_type     Lifecycle event constant.
	 * @param string $entity_type    Entity type (city_page, content_version, bulk_job).
	 * @param int    $entity_id      Entity ID.
	 * @param array  $action_details Context details and diffs.
	 * @return int Inserted log ID.
	 */
	public static function log( string $event_type, string $entity_type, int $entity_id, array $action_details = [] ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_AUDIT_LOGS );

		$user_id = get_current_user_id();
		$user_ip = self::get_client_ip();

		// Redact credentials and sensitive keys recursively.
		$sanitized_details = self::redact_sensitive_data( $action_details );
		$details_json      = wp_json_encode( $sanitized_details );
		$now               = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$table,
			[
				'event_type'     => sanitize_text_field( $event_type ),
				'entity_type'    => sanitize_text_field( $entity_type ),
				'entity_id'      => $entity_id,
				'user_id'        => $user_id,
				'user_ip'        => $user_ip,
				'action_details' => $details_json,
				'created_at'     => $now,
			]
		);

		if ( false === $inserted ) {
			Logger::error( 'Failed to write audit log entry', [ 'event_type' => $event_type, 'error' => $wpdb->last_error ] );
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Log content approval event.
	 *
	 * @param int   $version_id Content version ID.
	 * @param int   $page_id    City page ID.
	 * @param array $details    Optional context.
	 * @return int
	 */
	public static function log_approval( int $version_id, int $page_id, array $details = [] ): int {
		$details['version_id'] = $version_id;
		$details['page_id']    = $page_id;
		return self::log( self::EVENT_APPROVE, 'content_version', $version_id, $details );
	}

	/**
	 * Log content rejection event.
	 *
	 * @param int    $version_id Content version ID.
	 * @param int    $page_id    City page ID.
	 * @param string $reason     Rejection reason note.
	 * @param array  $details    Optional context.
	 * @return int
	 */
	public static function log_rejection( int $version_id, int $page_id, string $reason = '', array $details = [] ): int {
		$details['version_id']       = $version_id;
		$details['page_id']          = $page_id;
		$details['rejection_reason'] = $reason;
		return self::log( self::EVENT_REJECT, 'content_version', $version_id, $details );
	}

	/**
	 * Log page publish event.
	 *
	 * @param int   $version_id Published version ID.
	 * @param int   $page_id    City page ID.
	 * @param array $details    Optional context.
	 * @return int
	 */
	public static function log_publish( int $version_id, int $page_id, array $details = [] ): int {
		$details['published_version_id'] = $version_id;
		$details['page_id']              = $page_id;
		return self::log( self::EVENT_PUBLISH, 'city_page', $page_id, $details );
	}

	/**
	 * Log page unpublish event.
	 *
	 * @param int   $page_id City page ID.
	 * @param array $details Optional context.
	 * @return int
	 */
	public static function log_unpublish( int $page_id, array $details = [] ): int {
		$details['page_id'] = $page_id;
		return self::log( self::EVENT_UNPUBLISH, 'city_page', $page_id, $details );
	}

	/**
	 * Log manual edit event.
	 *
	 * @param int   $version_id New edited version ID.
	 * @param int   $page_id    City page ID.
	 * @param array $details    Diffs / changes.
	 * @return int
	 */
	public static function log_edit( int $version_id, int $page_id, array $details = [] ): int {
		$details['version_id'] = $version_id;
		$details['page_id']    = $page_id;
		return self::log( self::EVENT_EDIT, 'content_version', $version_id, $details );
	}

	/**
	 * Log version restoration / rollback event.
	 *
	 * @param int   $new_version_id Newly created restored version ID.
	 * @param int   $old_version_id Source version ID that was restored.
	 * @param int   $page_id        City page ID.
	 * @param array $details        Optional context.
	 * @return int
	 */
	public static function log_restore( int $new_version_id, int $old_version_id, int $page_id, array $details = [] ): int {
		$details['new_version_id'] = $new_version_id;
		$details['old_version_id'] = $old_version_id;
		$details['page_id']        = $page_id;
		return self::log( self::EVENT_RESTORE, 'content_version', $new_version_id, $details );
	}

	/**
	 * Log bulk workflow action.
	 *
	 * @param string $action   Bulk action name.
	 * @param array  $page_ids Array of affected page IDs.
	 * @param array  $details  Outcome details.
	 * @return int
	 */
	public static function log_bulk_action( string $action, array $page_ids, array $details = [] ): int {
		$details['bulk_action']   = $action;
		$details['page_ids']      = $page_ids;
		$details['total_targets'] = count( $page_ids );
		return self::log( self::EVENT_BULK_ACTION, 'bulk_job', 0, $details );
	}

	/**
	 * Query audit logs with pagination and filters.
	 *
	 * @param array $args Query filter parameters.
	 * @return object[] Array of audit log objects with user display name.
	 */
	public static function get_logs( array $args = [] ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_AUDIT_LOGS );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['event_type'] ) ) {
			$where_clauses[] = 'l.event_type = %s';
			$params[]        = sanitize_text_field( $args['event_type'] );
		}

		if ( ! empty( $args['entity_type'] ) ) {
			$where_clauses[] = 'l.entity_type = %s';
			$params[]        = sanitize_text_field( $args['entity_type'] );
		}

		if ( ! empty( $args['entity_id'] ) ) {
			$where_clauses[] = 'l.entity_id = %d';
			$params[]        = (int) $args['entity_id'];
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where_clauses[] = 'l.user_id = %d';
			$params[]        = (int) $args['user_id'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$limit     = ! empty( $args['limit'] ) ? absint( $args['limit'] ) : 50;
		$offset    = ! empty( $args['offset'] ) ? absint( $args['offset'] ) : 0;

		$sql = "
			SELECT l.*
			FROM {$table} l
			WHERE {$where_sql}
			ORDER BY l.created_at DESC, l.id DESC
			LIMIT %d OFFSET %d
		";
		$params[] = $limit;
		$params[] = $offset;

		$results = $wpdb->get_results(
			$wpdb->prepare( $sql, ...$params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		if ( is_array( $results ) ) {
			foreach ( $results as $r ) {
				if ( ! isset( $r->display_name ) ) {
					$user_data = function_exists( 'get_userdata' ) ? get_userdata( (int) $r->user_id ) : false;
					$r->display_name = ( $user_data && ! empty( $user_data->display_name ) ) ? $user_data->display_name : 'System';
					$r->user_login   = ( $user_data && ! empty( $user_data->user_login ) ) ? $user_data->user_login : 'system';
				}
			}
		}

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Count total audit logs matching filters.
	 *
	 * @param array $args Query filter parameters.
	 * @return int Total count.
	 */
	public static function count_logs( array $args = [] ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_AUDIT_LOGS );

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['event_type'] ) ) {
			$where_clauses[] = 'event_type = %s';
			$params[]        = sanitize_text_field( $args['event_type'] );
		}

		if ( ! empty( $args['entity_type'] ) ) {
			$where_clauses[] = 'entity_type = %s';
			$params[]        = sanitize_text_field( $args['entity_type'] );
		}

		if ( ! empty( $args['entity_id'] ) ) {
			$where_clauses[] = 'entity_id = %d';
			$params[]        = (int) $args['entity_id'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$sql       = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Redact credentials, API keys, and sensitive fields recursively.
	 *
	 * @param array $data Input data structure.
	 * @return array Sanitized array.
	 */
	public static function redact_sensitive_data( array $data ): array {
		$sensitive_keys = [ 'api_key', 'openai_api_key', 'secret', 'password', 'token', 'authorization' ];

		$sanitized = [];
		foreach ( $data as $key => $value ) {
			$lower_key = strtolower( (string) $key );
			$is_sensitive = false;

			foreach ( $sensitive_keys as $secret_key ) {
				if ( false !== strpos( $lower_key, $secret_key ) ) {
					$is_sensitive = true;
					break;
				}
			}

			if ( $is_sensitive && is_scalar( $value ) ) {
				$val_str = (string) $value;
				if ( strlen( $val_str ) > 8 ) {
					$sanitized[ $key ] = substr( $val_str, 0, 4 ) . '...' . substr( $val_str, -4 );
				} else {
					$sanitized[ $key ] = '********';
				}
			} elseif ( is_array( $value ) ) {
				$sanitized[ $key ] = self::redact_sensitive_data( $value );
			} elseif ( is_string( $value ) && preg_match( '/sk-[a-zA-Z0-9_-]{20,}/', $value ) ) {
				$sanitized[ $key ] = preg_replace( '/sk-([a-zA-Z0-9_-]{4})[a-zA-Z0-9_-]+([a-zA-Z0-9_-]{4})/', 'sk-$1...$2', $value );
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Get sanitized client IP address.
	 *
	 * @return string
	 */
	private static function get_client_ip(): string {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip        = trim( $forwarded[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '127.0.0.1';
	}
}
