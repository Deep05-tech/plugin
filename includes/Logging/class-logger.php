<?php
/**
 * Plugin Logging Subsystem.
 *
 * @package SeoCityPages\Logging
 */

namespace SeoCityPages\Logging;

use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger Class.
 */
class Logger {

	/**
	 * Log levels.
	 */
	public const LEVEL_DEBUG    = 'DEBUG';
	public const LEVEL_INFO     = 'INFO';
	public const LEVEL_WARNING  = 'WARNING';
	public const LEVEL_ERROR    = 'ERROR';
	public const LEVEL_CRITICAL = 'CRITICAL';

	/**
	 * Log categories.
	 */
	public const CAT_GENERAL   = 'general';
	public const CAT_AI        = 'ai';
	public const CAT_SEO       = 'seo';
	public const CAT_QUEUE     = 'queue';
	public const CAT_DB        = 'database';
	public const CAT_SECURITY  = 'security';
	public const CAT_IMPORT    = 'import';
	public const CAT_EXPORT    = 'export';
	public const CAT_INTEGRITY = 'integrity';

	/**
	 * Log file directory name in uploads.
	 */
	private const LOG_DIR = 'seo-city-pages-logs';

	/**
	 * Log file name.
	 */
	private const LOG_FILE = 'plugin.log';

	/**
	 * Max log file size in bytes (5 MB).
	 */
	private const MAX_FILE_SIZE = 5242880;

	/**
	 * Active request correlation ID.
	 *
	 * @var string|null
	 */
	private static ?string $current_correlation_id = null;

	/**
	 * Generate a standardized correlation ID.
	 *
	 * @param string $prefix Optional prefix.
	 * @return string e.g. "SCP-20261001-A1B2C3"
	 */
	public static function generate_correlation_id( string $prefix = 'SCP' ): string {
		$date = gmdate( 'Ymd' );
		$rand = strtoupper( substr( bin2hex( random_bytes( 3 ) ), 0, 6 ) );
		return sprintf( '%s-%s-%s', $prefix, $date, $rand );
	}

	/**
	 * Get or initialize current correlation ID.
	 *
	 * @return string
	 */
	public static function get_correlation_id(): string {
		if ( null === self::$current_correlation_id ) {
			self::$current_correlation_id = self::generate_correlation_id();
		}
		return self::$current_correlation_id;
	}

	/**
	 * Set or override active correlation ID.
	 *
	 * @param string|null $id Correlation ID.
	 * @return void
	 */
	public static function set_correlation_id( ?string $id ): void {
		self::$current_correlation_id = $id ? sanitize_text_field( $id ) : null;
	}

	/**
	 * Log a message with structured attributes.
	 *
	 * @param string $level    Log level constant.
	 * @param string $message  Log message.
	 * @param array  $context  Extra context details.
	 * @param string $category Category constant.
	 * @return void
	 */
	public static function log(
		string $level,
		string $message,
		array $context = [],
		string $category = self::CAT_GENERAL
	): void {
		$settings   = get_option( 'seo_city_pages_settings', [] );
		$debug_mode = ! empty( $settings['debug_mode'] );
		$log_level  = $settings['log_level'] ?? 'info';

		// If debug message but debug mode is off, skip.
		if ( self::LEVEL_DEBUG === $level && ! $debug_mode ) {
			return;
		}

		// Filter out info logs if log level is set to warning/error only.
		if ( 'error' === $log_level && in_array( $level, [ self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_DEBUG ], true ) ) {
			return;
		}
		if ( 'warning' === $log_level && in_array( $level, [ self::LEVEL_INFO, self::LEVEL_DEBUG ], true ) ) {
			return;
		}

		$cleaned_context = Security::scrub_sensitive_data( $context );
		$timestamp       = gmdate( 'Y-m-d H:i:s' );
		$correlation_id  = self::get_correlation_id();

		$payload = [
			'ts'       => $timestamp,
			'level'    => $level,
			'cat'      => $category,
			'cid'      => $correlation_id,
			'msg'      => sanitize_text_field( $message ),
			'ctx'      => $cleaned_context,
		];

		$formatted_entry = sprintf(
			"[%s] [%s] [%s] [%s] %s%s\n",
			$timestamp,
			$level,
			$category,
			$correlation_id,
			$message,
			! empty( $cleaned_context ) ? ' | ' . wp_json_encode( $cleaned_context ) : ''
		);

		// Write to custom secure log file.
		self::write_to_file( $formatted_entry );

		// If WP_DEBUG_LOG is enabled, also forward error/critical to WP debug log.
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG && in_array( $level, [ self::LEVEL_ERROR, self::LEVEL_CRITICAL ], true ) ) {
			error_log( '[SEO City Pages] ' . $formatted_entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Helper for INFO logs.
	 *
	 * @param string $message  Log message.
	 * @param array  $context  Context.
	 * @param string $category Category.
	 * @return void
	 */
	public static function info( string $message, array $context = [], string $category = self::CAT_GENERAL ): void {
		self::log( self::LEVEL_INFO, $message, $context, $category );
	}

	/**
	 * Helper for WARNING logs.
	 *
	 * @param string $message  Log message.
	 * @param array  $context  Context.
	 * @param string $category Category.
	 * @return void
	 */
	public static function warning( string $message, array $context = [], string $category = self::CAT_GENERAL ): void {
		self::log( self::LEVEL_WARNING, $message, $context, $category );
	}

	/**
	 * Helper for ERROR logs.
	 *
	 * @param string $message  Log message.
	 * @param array  $context  Context.
	 * @param string $category Category.
	 * @return void
	 */
	public static function error( string $message, array $context = [], string $category = self::CAT_GENERAL ): void {
		self::log( self::LEVEL_ERROR, $message, $context, $category );
	}

	/**
	 * Helper for CRITICAL logs.
	 *
	 * @param string $message  Log message.
	 * @param array  $context  Context.
	 * @param string $category Category.
	 * @return void
	 */
	public static function critical( string $message, array $context = [], string $category = self::CAT_GENERAL ): void {
		self::log( self::LEVEL_CRITICAL, $message, $context, $category );
	}

	/**
	 * Helper for DEBUG logs.
	 *
	 * @param string $message  Log message.
	 * @param array  $context  Context.
	 * @param string $category Category.
	 * @return void
	 */
	public static function debug( string $message, array $context = [], string $category = self::CAT_GENERAL ): void {
		self::log( self::LEVEL_DEBUG, $message, $context, $category );
	}

	/**
	 * Get log file path and ensure directory security.
	 *
	 * @return string|null File path or null on failure.
	 */
	public static function get_log_file_path(): ?string {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return null;
		}

		$log_dir = trailingslashit( $uploads['basedir'] ) . self::LOG_DIR;

		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );

			// Protect log directory from direct web access.
			$htaccess_file = trailingslashit( $log_dir ) . '.htaccess';
			if ( ! file_exists( $htaccess_file ) ) {
				file_put_contents( $htaccess_file, "Order Deny,Allow\nDeny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>" );
			}

			$index_file = trailingslashit( $log_dir ) . 'index.php';
			if ( ! file_exists( $index_file ) ) {
				file_put_contents( $index_file, '<?php // Silence is golden.' );
			}
		}

		return trailingslashit( $log_dir ) . self::LOG_FILE;
	}

	/**
	 * Write formatted line to log file safely.
	 *
	 * @param string $formatted_entry Log line.
	 * @return void
	 */
	private static function write_to_file( string $formatted_entry ): void {
		$file_path = self::get_log_file_path();
		if ( ! $file_path ) {
			return;
		}

		// Rotate if over size limit.
		if ( file_exists( $file_path ) && filesize( $file_path ) > self::MAX_FILE_SIZE ) {
			$backup_path = $file_path . '.old';
			if ( file_exists( $backup_path ) ) {
				unlink( $backup_path );
			}
			rename( $file_path, $backup_path );
		}

		file_put_contents( $file_path, $formatted_entry, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Retrieve recent log lines for admin viewer.
	 *
	 * @param int $max_lines Maximum lines to return.
	 * @return string[]
	 */
	public static function get_recent_logs( int $max_lines = 100 ): array {
		$file_path = self::get_log_file_path();
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return [];
		}

		$lines = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( ! is_array( $lines ) ) {
			return [];
		}

		return array_slice( $lines, -$max_lines );
	}

	/**
	 * Parse and search log entries with filters.
	 *
	 * @param array $criteria Search and filter options.
	 * @param int   $limit    Max records.
	 * @return array
	 */
	public static function search_logs( array $criteria = [], int $limit = 100 ): array {
		$file_path = self::get_log_file_path();
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return [];
		}

		$lines = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( empty( $lines ) ) {
			return [];
		}

		$results = [];
		// Read in reverse to get newest entries first.
		$reversed = array_reverse( $lines );

		$level_filter    = ! empty( $criteria['level'] ) ? strtoupper( $criteria['level'] ) : null;
		$category_filter = ! empty( $criteria['category'] ) ? strtolower( $criteria['category'] ) : null;
		$search_query    = ! empty( $criteria['search'] ) ? strtolower( $criteria['search'] ) : null;

		foreach ( $reversed as $line ) {
			if ( count( $results ) >= $limit ) {
				break;
			}

			// Format: [2026-10-01 12:00:00] [LEVEL] [category] [CID] message | context
			if ( preg_match( '/^\[([^\]]+)\]\s+\[([^\]]+)\](?:\s+\[([^\]]+)\])?(?:\s+\[([^\]]+)\])?\s+(.*)$/', $line, $matches ) ) {
				$entry_time  = $matches[1] ?? '';
				$entry_level = $matches[2] ?? '';
				$entry_cat   = $matches[3] ?? 'general';
				$entry_cid   = $matches[4] ?? '';
				$entry_body  = $matches[5] ?? '';

				if ( $level_filter && $entry_level !== $level_filter ) {
					continue;
				}
				if ( $category_filter && strtolower( $entry_cat ) !== $category_filter ) {
					continue;
				}
				if ( $search_query && strpos( strtolower( $line ), $search_query ) === false ) {
					continue;
				}

				$results[] = [
					'timestamp'      => $entry_time,
					'level'          => $entry_level,
					'category'       => $entry_cat,
					'correlation_id' => $entry_cid,
					'message'        => $entry_body,
					'raw'            => $line,
				];
			}
		}

		return $results;
	}

	/**
	 * Prune old logs based on retention threshold.
	 *
	 * @param int $retention_days Number of days to retain (e.g. 7, 30, 90, 180).
	 * @return int Number of pruned lines.
	 */
	public static function prune_logs_by_retention( int $retention_days = 30 ): int {
		$file_path = self::get_log_file_path();
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return 0;
		}

		$lines = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( empty( $lines ) ) {
			return 0;
		}

		$cutoff_time = strtotime( "-{$retention_days} days" );
		$kept_lines  = [];
		$pruned      = 0;

		foreach ( $lines as $line ) {
			if ( preg_match( '/^\[([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2})\]/', $line, $matches ) ) {
				$entry_timestamp = strtotime( $matches[1] );
				if ( $entry_timestamp < $cutoff_time ) {
					$pruned++;
					continue;
				}
			}
			$kept_lines[] = $line;
		}

		if ( $pruned > 0 ) {
			file_put_contents( $file_path, implode( "\n", $kept_lines ) . "\n", LOCK_EX );
		}

		return $pruned;
	}

	/**
	 * Clear current log file.
	 *
	 * @return bool
	 */
	public static function clear_logs(): bool {
		$file_path = self::get_log_file_path();
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return true;
		}

		return (bool) file_put_contents( $file_path, '' );
	}
}
