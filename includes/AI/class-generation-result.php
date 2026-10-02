<?php
/**
 * Generation Result Value Object.
 *
 * Encapsulates the output of an AI generation attempt.
 *
 * @package SeoCityPages\AI
 */

namespace SeoCityPages\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generation_Result Class.
 */
class Generation_Result {

	private bool $success;
	private ?array $content;
	private string $content_json;
	private string $content_hash;
	private string $provider;
	private string $model;
	private int $input_tokens;
	private int $output_tokens;
	private int $total_tokens;
	private int $duration_ms;
	private string $error_message;
	private string $error_code;
	private array $validation_errors;

	public function __construct(
		bool $success,
		?array $content = null,
		string $content_json = '',
		string $content_hash = '',
		string $provider = 'openai',
		string $model = 'gpt-4o-mini',
		int $input_tokens = 0,
		int $output_tokens = 0,
		int $total_tokens = 0,
		int $duration_ms = 0,
		string $error_message = '',
		string $error_code = '',
		array $validation_errors = []
	) {
		$this->success           = $success;
		$this->content           = $content;
		$this->content_json      = $content_json;
		$this->content_hash      = $content_hash;
		$this->provider          = $provider;
		$this->model             = $model;
		$this->input_tokens      = $input_tokens;
		$this->output_tokens     = $output_tokens;
		$this->total_tokens      = $total_tokens;
		$this->duration_ms       = $duration_ms;
		$this->error_message     = $error_message;
		$this->error_code        = $error_code;
		$this->validation_errors = $validation_errors;
	}

	public static function success(
		array $content,
		string $content_json,
		string $content_hash,
		string $provider,
		string $model,
		int $input_tokens = 0,
		int $output_tokens = 0,
		int $total_tokens = 0,
		int $duration_ms = 0
	): self {
		return new self(
			true,
			$content,
			$content_json,
			$content_hash,
			$provider,
			$model,
			$input_tokens,
			$output_tokens,
			$total_tokens,
			$duration_ms
		);
	}

	public static function failure( string $error_message, string $error_code = 'generation_failed', array $validation_errors = [], string $provider = 'openai', string $model = '' ): self {
		return new self(
			false,
			null,
			'',
			'',
			$provider,
			$model,
			0,
			0,
			0,
			0,
			$error_message,
			$error_code,
			$validation_errors
		);
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_content(): ?array {
		return $this->content;
	}

	public function get_content_json(): string {
		return $this->content_json;
	}

	public function get_content_hash(): string {
		return $this->content_hash;
	}

	public function get_provider(): string {
		return $this->provider;
	}

	public function get_model(): string {
		return $this->model;
	}

	public function get_input_tokens(): int {
		return $this->input_tokens;
	}

	public function get_output_tokens(): int {
		return $this->output_tokens;
	}

	public function get_total_tokens(): int {
		return $this->total_tokens;
	}

	public function get_duration_ms(): int {
		return $this->duration_ms;
	}

	public function get_error_message(): string {
		return $this->error_message;
	}

	public function get_error_code(): string {
		return $this->error_code;
	}

	public function get_validation_errors(): array {
		return $this->validation_errors;
	}

	public function to_array(): array {
		return [
			'success'           => $this->success,
			'content'           => $this->content,
			'content_hash'      => $this->content_hash,
			'provider'          => $this->provider,
			'model'             => $this->model,
			'input_tokens'      => $this->input_tokens,
			'output_tokens'     => $this->output_tokens,
			'total_tokens'      => $this->total_tokens,
			'duration_ms'       => $this->duration_ms,
			'error_message'     => $this->error_message,
			'error_code'        => $this->error_code,
			'validation_errors' => $this->validation_errors,
		];
	}
}
