<?php
/**
 * Bulk AI Content Generation Handler.
 *
 * @package SeoCityPages\Jobs\Handlers
 */

namespace SeoCityPages\Jobs\Handlers;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\AI\AI_Manager;
use SeoCityPages\AI\AI_Context;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content Generation Handler Class.
 */
class Content_Generation_Handler {

	/**
	 * Process a single AI content generation item.
	 *
	 * @param object $item   Job item object.
	 * @param object $job    Bulk job object.
	 * @param array  $params Job parameters.
	 * @return array Result array.
	 */
	public static function process( object $item, object $job, array $params = [] ): array {
		$page_id = (int) $item->entity_id;
		if ( $page_id <= 0 ) {
			$payload = is_string( $item->item_payload ) ? json_decode( $item->item_payload, true ) : $item->item_payload;
			$page_id = (int) ( $payload['page_id'] ?? 0 );
		}

		if ( $page_id <= 0 ) {
			return [
				'success'    => false,
				'error_code' => 'missing_page_id',
				'message'    => 'Valid City Page ID is required for AI generation.',
			];
		}

		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return [
				'success'    => false,
				'error_code' => 'page_not_found',
				'message'    => sprintf( 'City Page #%d does not exist.', $page_id ),
			];
		}

		$is_dry_run = ! empty( $job->is_dry_run ) || ! empty( $params['is_dry_run'] );
		if ( $is_dry_run ) {
			return [
				'success'          => true,
				'dry_run'          => true,
				'page_id'          => $page_id,
				'city'             => $page->city_name ?? '',
				'service'          => $page->service_name ?? '',
				'estimated_tokens' => 2800,
				'message'          => sprintf( 'Dry Run: Page #%d (%s - %s) is eligible for AI generation.', $page_id, $page->city_name ?? '', $page->service_name ?? '' ),
			];
		}

		// Execute AI generation via Phase 6 AI_Manager (preserves version history and non-destructive isolation).
		$options = [
			'model'       => sanitize_text_field( $params['model'] ?? 'gpt-4o-mini' ),
			'temperature' => isset( $params['temperature'] ) ? (float) $params['temperature'] : 0.7,
		];

		$gen_result = AI_Manager::instance()->generate_for_page( $page_id, $options );

		if ( ! $gen_result->is_success() ) {
			return [
				'success'    => false,
				'error_code' => $gen_result->get_error_code() ?: 'generation_error',
				'message'    => $gen_result->get_error_message() ?: 'Unknown AI generation failure.',
			];
		}

		$latest_version = Content_Version_Repository::get_latest_version( $page_id );

		return [
			'success'            => true,
			'page_id'            => $page_id,
			'version_id'         => $latest_version ? (int) $latest_version->id : 0,
			'tokens_used'        => $gen_result->get_total_tokens(),
			'generation_time_ms' => $gen_result->get_duration_ms(),
		];
	}
}
