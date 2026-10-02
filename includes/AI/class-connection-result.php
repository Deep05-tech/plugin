<?php
/**
 * AI Provider Connection Test Result.
 *
 * @package SeoCityPages\AI
 */

namespace SeoCityPages\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connection_Result Class.
 */
class Connection_Result {

	private bool $success;
	private string $message;
	private string $provider;
	private array $available_models;
	private int $latency_ms;

	public function __construct( bool $success, string $message, string $provider = 'openai', array $available_models = [], int $latency_ms = 0 ) {
		$this->success          = $success;
		$this->message          = $message;
		$this->provider         = $provider;
		$this->available_models = $available_models;
		$this->latency_ms       = $latency_ms;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_message(): string {
		return $this->message;
	}

	public function get_provider(): string {
		return $this->provider;
	}

	public function get_available_models(): array {
		return $this->available_models;
	}

	public function get_latency_ms(): int {
		return $this->latency_ms;
	}

	public function to_array(): array {
		return [
			'success'          => $this->success,
			'message'          => $this->message,
			'provider'         => $this->provider,
			'available_models' => $this->available_models,
			'latency_ms'       => $this->latency_ms,
		];
	}
}
