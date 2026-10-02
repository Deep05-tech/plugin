<?php
/**
 * Bulk Quality Scan Job Handler.
 *
 * Processes asynchronous, chunked background scanning of page quality,
 * similarity indexing, intent overlap detection, and orphan checks.
 *
 * @package SeoCityPages\Jobs\Handlers
 */

namespace SeoCityPages\Jobs\Handlers;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Quality\Quality_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quality_Scan_Handler Class.
 */
class Quality_Scan_Handler {

	/**
	 * Process a single quality scan item.
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
				'message'    => 'Valid City Page ID is required.',
			];
		}

		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return [
				'success'    => false,
				'error_code' => 'page_not_found',
				'message'    => sprintf( 'City Page #%d not found.', $page_id ),
			];
		}

		try {
			$eval = Quality_Engine::evaluate_page( $page_id, null, is_object( $page ) ? (array) $page : $page );

			return [
				'success'        => true,
				'page_id'        => $page_id,
				'quality_score'  => $eval['quality_score'],
				'quality_status' => $eval['quality_status'],
				'issues_count'   => count( $eval['issues_json'] ?? [] ),
			];
		} catch ( \Throwable $e ) {
			return [
				'success'    => false,
				'error_code' => 'evaluation_exception',
				'message'    => $e->getMessage(),
			];
		}
	}
}
