<?php
/**
 * AI Provider Contract Interface.
 *
 * Defines the public contract that any AI provider (OpenAI, Gemini, etc.)
 * must implement to support SEO City Page generation.
 *
 * @package SeoCityPages\AI
 */

namespace SeoCityPages\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI_Provider_Interface.
 */
interface AI_Provider_Interface {

	/**
	 * Unique provider slug identifier.
	 *
	 * @return string E.g. 'openai'.
	 */
	public function get_identifier(): string;

	/**
	 * Human-readable provider name.
	 *
	 * @return string E.g. 'OpenAI'.
	 */
	public function get_name(): string;

	/**
	 * Generate structured SEO landing page content.
	 *
	 * @param AI_Context $context Page context with location, service, and brand guidelines.
	 * @param array      $options Runtime options (model, temperature, etc.).
	 * @return Generation_Result
	 */
	public function generate( AI_Context $context, array $options = [] ): Generation_Result;

	/**
	 * Test provider connectivity and credential validity server-side.
	 *
	 * @return Connection_Result
	 */
	public function test_connection(): Connection_Result;

	/**
	 * Retrieve list of supported model IDs with labels.
	 *
	 * @return array Array mapping model ID to display name.
	 */
	public function get_supported_models(): array;

	/**
	 * Get default recommended model identifier.
	 *
	 * @return string E.g. 'gpt-4o-mini'.
	 */
	public function get_default_model(): string;
}
