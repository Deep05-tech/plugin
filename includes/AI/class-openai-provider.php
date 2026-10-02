<?php
/**
 * OpenAI AI Provider Implementation.
 *
 * Handles server-side API communication with OpenAI Chat Completions & Responses API
 * using strict Structured Outputs (JSON Schema), exponential backoff retries,
 * token accounting, and credential isolation.
 *
 * @package SeoCityPages\AI
 */

namespace SeoCityPages\AI;

use SeoCityPages\AI\Schema\Content_Schema;
use SeoCityPages\AI\Schema\Content_Validator;
use SeoCityPages\AI\Prompt\Prompt_Builder;
use SeoCityPages\Admin\Settings;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI_Provider Class.
 */
class OpenAI_Provider implements AI_Provider_Interface {

	public const PROVIDER_ID = 'openai';
	public const DEFAULT_MODEL = 'gpt-4o-mini';
	public const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
	public const MODELS_ENDPOINT = 'https://api.openai.com/v1/models';

	public const SUPPORTED_MODELS = [
		'gpt-4o-mini'       => 'GPT-4o Mini (Fast & Cost-Effective — Recommended)',
		'gpt-4o'            => 'GPT-4o (High Intelligence & Nuanced Copywriting)',
		'gpt-4.5-preview'   => 'GPT-4.5 Preview (Next-Gen Flagship Model)',
		'o3-mini'           => 'o3 Mini (High-Reasoning Analytical Model)',
	];

	public function get_identifier(): string {
		return self::PROVIDER_ID;
	}

	public function get_name(): string {
		return 'OpenAI';
	}

	public function get_default_model(): string {
		return self::DEFAULT_MODEL;
	}

	public function get_supported_models(): array {
		return self::SUPPORTED_MODELS;
	}

	/**
	 * Retrieve OpenAI API Key securely from environment or settings.
	 *
	 * @return string API key or empty string.
	 */
	public static function get_api_key(): string {
		if ( defined( 'SEO_CITY_OPENAI_API_KEY' ) && ! empty( constant( 'SEO_CITY_OPENAI_API_KEY' ) ) ) {
			return trim( (string) constant( 'SEO_CITY_OPENAI_API_KEY' ) );
		}

		$env_key = getenv( 'SEO_CITY_OPENAI_API_KEY' );
		if ( ! empty( $env_key ) ) {
			return trim( (string) $env_key );
		}

		$settings = get_option( Settings::OPTION_NAME, [] );
		$key      = $settings['ai_api_key'] ?? $settings['openai_api_key'] ?? '';
		return trim( (string) $key );
	}

	/**
	 * Retrieve configured OpenAI model.
	 *
	 * @return string Model identifier.
	 */
	public static function get_model(): string {
		$settings = get_option( Settings::OPTION_NAME, [] );
		$model    = sanitize_text_field( $settings['ai_model'] ?? self::DEFAULT_MODEL );
		return array_key_exists( $model, self::SUPPORTED_MODELS ) ? $model : self::DEFAULT_MODEL;
	}

	/**
	 * Generate structured SEO landing page content.
	 *
	 * @param AI_Context $context Normalized page context.
	 * @param array      $options Runtime options.
	 * @return Generation_Result
	 */
	public function generate( AI_Context $context, array $options = [] ): Generation_Result {
		$api_key = self::get_api_key();
		if ( empty( $api_key ) ) {
			return Generation_Result::failure(
				__( 'OpenAI API key is missing. Please configure it in SEO City Pages → Settings → AI.', 'seo-city-pages' ),
				'missing_api_key',
				[],
				self::PROVIDER_ID
			);
		}

		$settings = get_option( Settings::OPTION_NAME, [] );
		$model = sanitize_text_field( $options['model'] ?? $settings['ai_model'] ?? self::DEFAULT_MODEL );
		if ( ! array_key_exists( $model, self::SUPPORTED_MODELS ) ) {
			$model = self::DEFAULT_MODEL;
		}

		$temperature = (float) ( $options['temperature'] ?? $settings['ai_temperature'] ?? 0.7 );
		$temperature = max( 0.0, min( 1.5, $temperature ) );

		$system_prompt = Prompt_Builder::build_system_prompt();
		$user_prompt   = Prompt_Builder::build_user_prompt( $context );
		$json_schema   = Content_Schema::get_openai_json_schema();

		$payload = [
			'model'           => $model,
			'messages'        => [
				[ 'role' => 'system', 'content' => $system_prompt ],
				[ 'role' => 'user', 'content' => $user_prompt ],
			],
			'response_format' => [
				'type'        => 'json_schema',
				'json_schema' => $json_schema,
			],
			'temperature'     => $temperature,
		];

		$start_time = microtime( true );
		$response   = $this->send_request_with_retry( self::API_ENDPOINT, $payload, $api_key );
		$duration_ms = (int) ( ( microtime( true ) - $start_time ) * 1000 );

		if ( is_wp_error( $response ) ) {
			Logger::error( 'OpenAI API request failed', [ 'page_id' => $context->get_page_id(), 'error' => $response->get_error_message() ] );
			return Generation_Result::failure(
				$response->get_error_message(),
				$response->get_error_code(),
				[],
				self::PROVIDER_ID,
				$model
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$raw_body    = wp_remote_retrieve_body( $response );
		$body_data   = json_decode( $raw_body, true );

		if ( 200 !== $status_code ) {
			$error_msg = $body_data['error']['message'] ?? sprintf( __( 'OpenAI HTTP %d error.', 'seo-city-pages' ), $status_code );
			Logger::error( 'OpenAI API returned non-200 status', [ 'status' => $status_code, 'error' => $error_msg ] );
			return Generation_Result::failure(
				$error_msg,
				'http_' . $status_code,
				[],
				self::PROVIDER_ID,
				$model
			);
		}

		$raw_content_string = $body_data['choices'][0]['message']['content'] ?? '';
		if ( empty( $raw_content_string ) ) {
			return Generation_Result::failure(
				__( 'OpenAI returned an empty completion.', 'seo-city-pages' ),
				'empty_response',
				[],
				self::PROVIDER_ID,
				$model
			);
		}

		// Token usage metrics.
		$input_tokens  = (int) ( $body_data['usage']['prompt_tokens'] ?? 0 );
		$output_tokens = (int) ( $body_data['usage']['completion_tokens'] ?? 0 );
		$total_tokens  = (int) ( $body_data['usage']['total_tokens'] ?? 0 );

		// Server-side strict schema & identity validation.
		$validation = Content_Validator::validate( $raw_content_string, $context );

		if ( ! $validation['valid'] ) {
			Logger::warning( 'Content validation failed on OpenAI output', [ 'page_id' => $context->get_page_id(), 'errors' => $validation['errors'] ] );
			return Generation_Result::failure(
				sprintf( __( 'Validation failed: %s', 'seo-city-pages' ), implode( '; ', $validation['errors'] ) ),
				'validation_failed',
				$validation['errors'],
				self::PROVIDER_ID,
				$model
			);
		}

		$sanitized_content = $validation['sanitized'];
		$content_json      = wp_json_encode( $sanitized_content );
		$content_hash      = hash( 'sha256', (string) $content_json );

		Logger::info(
			sprintf( 'Successfully generated content for Page #%d via %s (tokens: %d)', $context->get_page_id(), $model, $total_tokens ),
			[ 'page_id' => $context->get_page_id(), 'tokens' => $total_tokens, 'duration_ms' => $duration_ms ]
		);

		return Generation_Result::success(
			$sanitized_content,
			$content_json,
			$content_hash,
			self::PROVIDER_ID,
			$model,
			$input_tokens,
			$output_tokens,
			$total_tokens,
			$duration_ms
		);
	}

	/**
	 * Test connection to OpenAI.
	 *
	 * @return Connection_Result
	 */
	public function test_connection(): Connection_Result {
		$api_key = self::get_api_key();
		if ( empty( $api_key ) ) {
			return new Connection_Result( false, __( 'OpenAI API key is missing.', 'seo-city-pages' ), self::PROVIDER_ID );
		}

		$start_time = microtime( true );

		$response = wp_remote_get(
			self::MODELS_ENDPOINT,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'User-Agent'    => 'SEO-City-Pages-WordPress-Plugin/1.0',
				],
				'timeout' => 15,
			]
		);

		$latency_ms = (int) ( ( microtime( true ) - $start_time ) * 1000 );

		if ( is_wp_error( $response ) ) {
			return new Connection_Result(
				false,
				sprintf( __( 'Connection failed: %s', 'seo-city-pages' ), $response->get_error_message() ),
				self::PROVIDER_ID,
				[],
				$latency_ms
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			$raw_body = wp_remote_retrieve_body( $response );
			$data     = json_decode( $raw_body, true );
			$message  = $data['error']['message'] ?? sprintf( __( 'Authentication failed (HTTP %d).', 'seo-city-pages' ), $status_code );
			return new Connection_Result( false, $message, self::PROVIDER_ID, [], $latency_ms );
		}

		return new Connection_Result(
			true,
			__( 'OpenAI connection successful. Ready for structured generation.', 'seo-city-pages' ),
			self::PROVIDER_ID,
			array_keys( self::SUPPORTED_MODELS ),
			$latency_ms
		);
	}

	/**
	 * Send HTTP request with exponential backoff for transient rate limits and server errors.
	 *
	 * @param string $url     Endpoint URL.
	 * @param array  $payload JSON payload.
	 * @param string $api_key OpenAI API key.
	 * @param int    $max_retries Max retry attempts.
	 * @return array|\WP_Error
	 */
	private function send_request_with_retry( string $url, array $payload, string $api_key, int $max_retries = 2 ) {
		$attempts = 0;
		$backoff_ms = 1000;

		while ( $attempts <= $max_retries ) {
			$attempts++;

			$response = wp_remote_post(
				$url,
				[
					'headers' => [
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
						'User-Agent'    => 'SEO-City-Pages-WordPress-Plugin/1.0',
					],
					'body'    => wp_json_encode( $payload ),
					'timeout' => 60,
				]
			);

			if ( is_wp_error( $response ) ) {
				if ( $attempts > $max_retries ) {
					return $response;
				}
				usleep( $backoff_ms * 1000 );
				$backoff_ms *= 2;
				continue;
			}

			$status = wp_remote_retrieve_response_code( $response );

			// 429 (Rate Limit) or 500/502/503/504 (Server error) -> Retry with backoff.
			if ( in_array( $status, [ 429, 500, 502, 503, 504 ], true ) && $attempts <= $max_retries ) {
				Logger::warning( sprintf( 'OpenAI returned HTTP %d, retrying attempt %d of %d...', $status, $attempts, $max_retries ) );
				usleep( $backoff_ms * 1000 );
				$backoff_ms *= 2;
				continue;
			}

			return $response;
		}

		return new \WP_Error( 'max_retries_exceeded', __( 'OpenAI request exceeded maximum retry attempts.', 'seo-city-pages' ) );
	}
}
