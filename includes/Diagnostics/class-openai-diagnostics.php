<?php
/**
 * OpenAI Health Diagnostics & Non-Destructive Connection Tester.
 *
 * @package SeoCityPages\Diagnostics
 */

namespace SeoCityPages\Diagnostics;

use SeoCityPages\AI\OpenAI_Provider;
use SeoCityPages\Security\Security;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI Diagnostics Class.
 */
class OpenAI_Diagnostics {

	public const OPTION_LAST_TEST = 'seo_city_pages_openai_diag_last';

	public const ERR_AUTH        = 'authentication';
	public const ERR_RATE_LIMIT  = 'rate_limit';
	public const ERR_TIMEOUT     = 'timeout';
	public const ERR_NETWORK     = 'network';
	public const ERR_SERVER      = 'server_error';
	public const ERR_INVALID_REQ = 'invalid_request';
	public const ERR_UNKNOWN     = 'unknown';

	/**
	 * Get safe summary of OpenAI configuration without exposing secrets.
	 *
	 * @return array
	 */
	public static function get_status_summary(): array {
		$api_key   = OpenAI_Provider::get_api_key();
		$model     = OpenAI_Provider::get_model();
		$last_test = get_option( self::OPTION_LAST_TEST, [] );

		$has_key = ! empty( $api_key );

		return [
			'provider'               => 'OpenAI',
			'configuration'          => $has_key ? 'Configured' : 'Not Configured',
			'api_key_status'         => $has_key ? 'Configured' : 'Missing',
			'model'                  => $model,
			'connection_status'      => $last_test['status'] ?? 'Not Tested',
			'last_tested_at'         => $last_test['timestamp'] ?? null,
			'last_error_type'        => $last_test['error_type'] ?? null,
			'last_error_message'     => $last_test['error_message'] ?? null,
			'last_remediation'       => $last_test['remediation'] ?? null,
			'last_response_time_ms'  => $last_test['duration_ms'] ?? null,
		];
	}

	/**
	 * Perform a non-destructive, zero-content connection test.
	 *
	 * @return array
	 */
	public static function test_connection(): array {
		$api_key = OpenAI_Provider::get_api_key();
		if ( empty( $api_key ) ) {
			$result = [
				'success'       => false,
				'error_type'    => self::ERR_AUTH,
				'error_message' => __( 'OpenAI API key is missing. Please configure your API key in Settings.', 'seo-city-pages' ),
				'remediation'   => __( 'Navigate to SEO City Pages -> Settings and enter your OpenAI API key.', 'seo-city-pages' ),
			];
			self::record_test_result( false, $result );
			return $result;
		}

		$start_time = microtime( true );

		// Minimal non-destructive call: GET https://api.openai.com/v1/models with 10s timeout.
		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				],
				'timeout' => 10,
			]
		);

		$duration_ms = (int) round( ( microtime( true ) - $start_time ) * 1000 );

		if ( is_wp_error( $response ) ) {
			$err_msg = $response->get_error_message();
			$err_type = self::ERR_NETWORK;
			if ( strpos( strtolower( $err_msg ), 'timed out' ) !== false || strpos( strtolower( $err_msg ), 'timeout' ) !== false ) {
				$err_type = self::ERR_TIMEOUT;
			}

			$classified = self::classify_error( 0, $err_msg );
			$classified['duration_ms'] = $duration_ms;
			self::record_test_result( false, $classified );
			return $classified;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body_raw    = wp_remote_retrieve_body( $response );
		$body        = json_decode( $body_raw, true );

		if ( $status_code >= 200 && $status_code < 300 ) {
			$result = [
				'success'     => true,
				'status'      => 'Healthy',
				'duration_ms' => $duration_ms,
				'message'     => sprintf( __( 'OpenAI connection verified successfully in %d ms.', 'seo-city-pages' ), $duration_ms ),
			];
			self::record_test_result( true, $result );
			return $result;
		}

		$api_err_msg = $body['error']['message'] ?? ( 'HTTP ' . $status_code );
		$classified  = self::classify_error( $status_code, $api_err_msg );
		$classified['duration_ms'] = $duration_ms;
		self::record_test_result( false, $classified );
		return $classified;
	}

	/**
	 * Classify HTTP and OpenAI API error codes into actionable categories.
	 *
	 * @param int    $status_code HTTP response code.
	 * @param string $raw_message Error message.
	 * @return array
	 */
	public static function classify_error( int $status_code, string $raw_message ): array {
		$sanitized_msg = Security::scrub_sensitive_data( [ 'msg' => $raw_message ] )['msg'] ?? 'Unknown error';

		if ( 401 === $status_code || strpos( strtolower( $raw_message ), 'incorrect api key' ) !== false || strpos( strtolower( $raw_message ), 'invalid api key' ) !== false ) {
			return [
				'success'       => false,
				'error_type'    => self::ERR_AUTH,
				'error_message' => __( 'Authentication failed: Invalid or expired OpenAI API key.', 'seo-city-pages' ),
				'remediation'   => __( 'Verify your OpenAI API key in Settings. Ensure there are no extra spaces or revoked keys.', 'seo-city-pages' ),
			];
		}

		if ( 429 === $status_code || strpos( strtolower( $raw_message ), 'rate limit' ) !== false || strpos( strtolower( $raw_message ), 'quota' ) !== false ) {
			return [
				'success'       => false,
				'error_type'    => self::ERR_RATE_LIMIT,
				'error_message' => __( 'Rate limit or billing quota exceeded.', 'seo-city-pages' ),
				'remediation'   => __( 'Check your OpenAI account billing balance and rate tier limits on platform.openai.com.', 'seo-city-pages' ),
			];
		}

		if ( 400 === $status_code || 404 === $status_code ) {
			return [
				'success'       => false,
				'error_type'    => self::ERR_INVALID_REQ,
				'error_message' => sprintf( __( 'Invalid request or unsupported model: %s', 'seo-city-pages' ), $sanitized_msg ),
				'remediation'   => __( 'Check model configuration in Settings or OpenAI Provider.', 'seo-city-pages' ),
			];
		}

		if ( $status_code >= 500 ) {
			return [
				'success'       => false,
				'error_type'    => self::ERR_SERVER,
				'error_message' => __( 'OpenAI service is temporarily experiencing internal errors (HTTP 5xx).', 'seo-city-pages' ),
				'remediation'   => __( 'Check status.openai.com and retry after a short delay.', 'seo-city-pages' ),
			];
		}

		if ( 0 === $status_code ) {
			return [
				'success'       => false,
				'error_type'    => self::ERR_NETWORK,
				'error_message' => sprintf( __( 'Network/cURL connection failure: %s', 'seo-city-pages' ), $sanitized_msg ),
				'remediation'   => __( 'Ensure your web server has outgoing HTTPS connectivity and firewall access to api.openai.com.', 'seo-city-pages' ),
			];
		}

		return [
			'success'       => false,
			'error_type'    => self::ERR_UNKNOWN,
			'error_message' => sprintf( __( 'OpenAI error (HTTP %d): %s', 'seo-city-pages' ), $status_code, $sanitized_msg ),
			'remediation'   => __( 'Inspect recent error logs for more diagnostic details.', 'seo-city-pages' ),
		];
	}

	/**
	 * Record test results in options without storing any secret values.
	 *
	 * @param bool  $success Success status.
	 * @param array $data    Result data.
	 * @return void
	 */
	private static function record_test_result( bool $success, array $data ): void {
		update_option(
			self::OPTION_LAST_TEST,
			[
				'status'        => $success ? 'Healthy' : 'Failed',
				'timestamp'     => current_time( 'mysql' ),
				'duration_ms'   => $data['duration_ms'] ?? null,
				'error_type'    => $data['error_type'] ?? null,
				'error_message' => $data['error_message'] ?? null,
				'remediation'   => $data['remediation'] ?? null,
			]
		);
	}
}
