<?php
/**
 * Core Security Helpers & Protections.
 *
 * @package SeoCityPages\Security
 */

namespace SeoCityPages\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security Class.
 */
class Security {

	/**
	 * Nonce action prefix.
	 */
	public const NONCE_ACTION = 'seo_city_pages_action';

	/**
	 * Create a nonce for a specific action.
	 *
	 * @param string $action Specific action name.
	 * @return string Nonce string.
	 */
	public static function create_nonce( string $action = '' ): string {
		$full_action = self::NONCE_ACTION . ( $action ? '_' . $action : '' );
		return wp_create_nonce( $full_action );
	}

	/**
	 * Verify a nonce for a specific action.
	 *
	 * @param string $nonce  Nonce token.
	 * @param string $action Specific action name.
	 * @return bool
	 */
	public static function verify_nonce( string $nonce, string $action = '' ): bool {
		$full_action = self::NONCE_ACTION . ( $action ? '_' . $action : '' );
		return (bool) wp_verify_nonce( $nonce, $full_action );
	}

	/**
	 * Output or return nonce field HTML.
	 *
	 * @param string $action  Specific action name.
	 * @param string $name    Nonce form field name.
	 * @param bool   $referer Whether to set referer field.
	 * @param bool   $echo    Whether to echo or return.
	 * @return string
	 */
	public static function nonce_field( string $action = '', string $name = '_wpnonce', bool $referer = true, bool $echo = true ): string {
		$full_action = self::NONCE_ACTION . ( $action ? '_' . $action : '' );
		return wp_nonce_field( $full_action, $name, $referer, $echo );
	}

	/**
	 * Check admin referer with die on failure.
	 *
	 * @param string $action    Action name.
	 * @param string $query_arg Nonce key in $_REQUEST.
	 * @return bool
	 */
	public static function check_admin_referer( string $action = '', string $query_arg = '_wpnonce' ): bool {
		$full_action = self::NONCE_ACTION . ( $action ? '_' . $action : '' );
		return (bool) check_admin_referer( $full_action, $query_arg );
	}

	/**
	 * Check AJAX referer with safe error response.
	 *
	 * @param string $action    Action name.
	 * @param string $query_arg Nonce key in $_REQUEST.
	 * @param bool   $die       Whether to terminate on failure.
	 * @return bool
	 */
	public static function check_ajax_referer( string $action = '', string $query_arg = '_wpnonce', bool $die = true ): bool {
		$full_action = self::NONCE_ACTION . ( $action ? '_' . $action : '' );
		return (bool) check_ajax_referer( $full_action, $query_arg, $die );
	}

	/**
	 * Verify nonce from request or abort execution with JSON error or wp_die.
	 *
	 * @param string $action    Action name.
	 * @param string $query_arg Nonce key in $_REQUEST.
	 * @return void
	 */
	public static function verify_nonce_or_abort( string $action = '', string $query_arg = 'nonce' ): void {
		$nonce = '';
		if ( ! empty( $_REQUEST[ $query_arg ] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST[ $query_arg ] ) );
		} elseif ( ! empty( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		}

		$is_valid = false;
		if ( ! empty( $nonce ) ) {
			if ( self::verify_nonce( $nonce, $action ) ) {
				$is_valid = true;
			} elseif ( (bool) wp_verify_nonce( $nonce, $action ) ) {
				$is_valid = true;
			} elseif ( self::verify_nonce( $nonce, '' ) ) {
				$is_valid = true;
			} elseif ( (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				$is_valid = true;
			}
		}

		if ( ! $is_valid ) {
			if ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				wp_send_json_error( [
					'message' => __( 'Security check failed. Invalid or expired token. Please refresh the page and try again.', 'seo-city-pages' ),
				], 403 );
			} else {
				wp_die(
					esc_html__( 'Security check failed. Invalid or expired token. Please refresh the page and try again.', 'seo-city-pages' ),
					esc_html__( 'Security Check Failed', 'seo-city-pages' ),
					[ 'response' => 403 ]
				);
			}
		}
	}

	/**
	 * Enforce capability check or fail gracefully.
	 *
	 * @param string $capability Capability to check.
	 * @return void
	 */
	public static function enforce_capability( string $capability = Capabilities::MANAGE ): void {
		if ( ! Capabilities::user_can( $capability ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'seo-city-pages' ),
				esc_html__( 'Permission Denied', 'seo-city-pages' ),
				[ 'response' => 403 ]
			);
		}
	}

	/**
	 * Sanitize CSV cell against formula injection (CSV Injection).
	 *
	 * @param string $value Cell string.
	 * @return string Sanitized cell string.
	 */
	public static function sanitize_csv_cell( string $value ): string {
		$disallowed_start_chars = [ '=', '+', '-', '@', "\t", "\r" ];

		if ( ! empty( $value ) && in_array( $value[0], $disallowed_start_chars, true ) ) {
			return "'" . $value;
		}

		return $value;
	}

	/**
	 * Mask sensitive secrets (e.g. API keys).
	 *
	 * @param string $secret Unmasked secret.
	 * @return string Masked string.
	 */
	public static function mask_secret( string $secret ): string {
		$length = strlen( $secret );
		if ( $length <= 8 ) {
			return str_repeat( '*', $length );
		}

		$prefix = substr( $secret, 0, 4 );
		$suffix = substr( $secret, -4 );
		$masked = str_repeat( '*', min( 12, $length - 8 ) );

		return $prefix . $masked . $suffix;
	}

	/**
	 * Scrub sensitive parameters from request payloads or debug output.
	 *
	 * @param array $data Input data array.
	 * @return array Scrubbed array.
	 */
	public static function scrub_sensitive_data( array $data ): array {
		$sensitive_keys = [
			'api_key', 'apikey', 'key', 'secret', 'password', 'pass',
			'token', 'authorization', 'bearer', 'auth', 'cookie',
		];

		$cleaned = [];
		foreach ( $data as $k => $v ) {
			$lower_key = strtolower( (string) $k );
			$is_sensitive = false;

			foreach ( $sensitive_keys as $secret_needle ) {
				if ( strpos( $lower_key, $secret_needle ) !== false ) {
					$is_sensitive = true;
					break;
				}
			}

			if ( $is_sensitive ) {
				$cleaned[ $k ] = '[REDACTED]';
			} elseif ( is_array( $v ) ) {
				$cleaned[ $k ] = self::scrub_sensitive_data( $v );
			} else {
				$cleaned[ $k ] = $v;
			}
		}

		return $cleaned;
	}
}
