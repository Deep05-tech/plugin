<?php
/**
 * Append-Only Audit Log Repository.
 *
 * @package SeoCityPages\Database
 */

namespace SeoCityPages\Database;

use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audit Log Repository Class.
 */
class Audit_Log_Repository {

	/**
	 * Log an audit event.
	 *
	 * @param string $event_type  Event action e.g. 'publish', 'approve', 'repair', 'rollback', 'settings_change'.
	 * @param string $entity_type Target entity e.g. 'city_page', 'content_version', 'bulk_job', 'system'.
	 * @param int    $entity_id   Entity ID.
	 * @param array  $details     Structured event metadata (sanitized).
	 * @param int    $user_id     Optional explicit user ID.
	 * @return int Inserted log ID, or 0 on failure.
	 */
	public static function log_event(
		string $event_type,
		string $entity_type,
		int $entity_id = 0,
		array $details = [],
		int $user_id = 0
	): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_AUDIT_LOGS );

		if ( 0 === $user_id && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}

		$user_ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$raw_ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			$user_ip = filter_var( $raw_ip, FILTER_VALIDATE_IP ) ?: '127.0.0.1';
		}

		// Ensure sensitive fields (passwords, tokens, api keys) are masked before persisting.
		$sanitized_details = self::sanitize_details( $details );

		$now = current_time( 'mysql' );

		$result = $wpdb->insert(
			$table,
			[
				'event_type'     => sanitize_key( $event_type ),
				'entity_type'    => sanitize_key( $entity_type ),
				'entity_id'      => max( 0, $entity_id ),
				'user_id'        => max( 0, $user_id ),
				'user_ip'        => $user_ip,
				'action_details' => wp_json_encode( $sanitized_details ),
				'created_at'     => $now,
			],
			[ '%s', '%s', '%d', '%d', '%s', '%s', '%s' ]
		);

		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Record a security-relevant event.
	 *
	 * @param string $event_type Security event type e.g. 'unauthorized_access', 'invalid_nonce', 'ssrf_blocked'.
	 * @param array  $details    Event details.
	 * @return int
	 */
	public static function record_security_event( string $event_type, array $details = [] ): int {
		return self::log_event(
			'security_' . sanitize_key( $event_type ),
			'security',
			0,
			$details
		);
	}

	/**
	 * Query audit logs with multi-field filtering.
	 *
	 * @param array $filters Filter conditions.
	 * @param int   $limit   Max records.
	 * @param int   $offset  Offset.
	 * @return array
	 */
	public static function get_logs( array $filters = [], int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_AUDIT_LOGS );

		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $filters['event_type'] ) ) {
			$where[]  = 'event_type = %s';
			$params[] = sanitize_key( $filters['event_type'] );
		}
		if ( ! empty( $filters['entity_type'] ) ) {
			$where[]  = 'entity_type = %s';
			$params[] = sanitize_key( $filters['entity_type'] );
		}
		if ( ! empty( $filters['entity_id'] ) ) {
			$where[]  = 'entity_id = %d';
			$params[] = (int) $filters['entity_id'];
		}
		if ( ! empty( $filters['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = (int) $filters['user_id'];
		}
		if ( ! empty( $filters['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = sanitize_text_field( $filters['date_from'] ) . ' 00:00:00';
		}
		if ( ! empty( $filters['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = sanitize_text_field( $filters['date_to'] ) . ' 23:59:59';
		}
		if ( ! empty( $filters['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
			$where[]  = '(action_details LIKE %s OR event_type LIKE %s OR entity_type LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_clause = implode( ' AND ', $where );
		$params[]     = max( 1, min( 500, $limit ) );
		$params[]     = max( 0, $offset );

		$sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY id DESC LIMIT %d OFFSET %d";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $rows ) ) {
			return [];
		}

		foreach ( $rows as $row ) {
			$row->details = ! empty( $row->action_details ) ? json_decode( $row->action_details, true ) : [];
		}

		return $rows;
	}

	/**
	 * Count total audit logs matching filters.
	 *
	 * @param array $filters Filter conditions.
	 * @return int
	 */
	public static function count_logs( array $filters = [] ): int {
		global $wpdb;
		$table = Schema::get_table_name( Schema::TABLE_AUDIT_LOGS );

		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $filters['event_type'] ) ) {
			$where[]  = 'event_type = %s';
			$params[] = sanitize_key( $filters['event_type'] );
		}
		if ( ! empty( $filters['entity_type'] ) ) {
			$where[]  = 'entity_type = %s';
			$params[] = sanitize_key( $filters['entity_type'] );
		}
		if ( ! empty( $filters['entity_id'] ) ) {
			$where[]  = 'entity_id = %d';
			$params[] = (int) $filters['entity_id'];
		}
		if ( ! empty( $filters['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$params[] = (int) $filters['user_id'];
		}
		if ( ! empty( $filters['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
			$where[]  = '(action_details LIKE %s OR event_type LIKE %s OR entity_type LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_clause = implode( ' AND ', $where );
		$sql          = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";

		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Sanitize structured details before persisting.
	 *
	 * @param array $details Raw details.
	 * @return array Sanitized details.
	 */
	private static function sanitize_details( array $details ): array {
		$sanitized = [];
		$sensitive_keys = [ 'api_key', 'token', 'password', 'secret', 'authorization', 'cookie' ];

		foreach ( $details as $k => $v ) {
			$lower_k = strtolower( (string) $k );
			foreach ( $sensitive_keys as $sk ) {
				if ( strpos( $lower_k, $sk ) !== false ) {
					$v = '***REDACTED***';
					break;
				}
			}

			if ( is_array( $v ) ) {
				$sanitized[ $k ] = self::sanitize_details( $v );
			} elseif ( is_scalar( $v ) || null === $v ) {
				$sanitized[ $k ] = is_string( $v ) ? sanitize_text_field( $v ) : $v;
			}
		}

		return $sanitized;
	}
}
