<?php
/**
 * Bulk Page Creation Handler.
 *
 * @package SeoCityPages\Jobs\Handlers
 */

namespace SeoCityPages\Jobs\Handlers;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\URL\URL_Engine;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Creation Handler Class.
 */
class Page_Creation_Handler {

	/**
	 * Process a single page creation item.
	 *
	 * @param object $item   Job item object.
	 * @param object $job    Bulk job object.
	 * @param array  $params Job parameters.
	 * @return array Result array with status, message, and metadata.
	 */
	public static function process( object $item, object $job, array $params = [] ): array {
		$payload = is_string( $item->item_payload ) ? json_decode( $item->item_payload, true ) : $item->item_payload;
		if ( ! is_array( $payload ) ) {
			return [
				'success'    => false,
				'error_code' => 'invalid_payload',
				'message'    => 'Missing or invalid item payload format.',
			];
		}

		$city_id    = (int) ( $payload['city_id'] ?? 0 );
		$service_id = (int) ( $payload['service_id'] ?? 0 );
		$is_dry_run = ! empty( $job->is_dry_run ) || ! empty( $params['is_dry_run'] );

		if ( $city_id <= 0 || $service_id <= 0 ) {
			return [
				'success'    => false,
				'error_code' => 'invalid_ids',
				'message'    => 'Valid City ID and Service ID are required.',
			];
		}

		// 1. Verify City and Service records exist.
		$city = City_Repository::get( $city_id );
		if ( ! $city ) {
			return [
				'success'    => false,
				'error_code' => 'city_not_found',
				'message'    => sprintf( 'City ID %d not found.', $city_id ),
			];
		}

		$service = Service_Repository::get( $service_id );
		if ( ! $service ) {
			return [
				'success'    => false,
				'error_code' => 'service_not_found',
				'message'    => sprintf( 'Service ID %d not found.', $service_id ),
			];
		}

		$country_id = (int) $city->country_id;
		$state_id   = (int) $city->state_id;

		// 2. Check for duplicate combination.
		$existing = City_Page_Repository::get_by_combination( $country_id, $state_id, $city_id, $service_id );
		if ( $existing ) {
			return [
				'success' => true,
				'skipped' => true,
				'reason'  => sprintf( 'Page already exists (Page ID #%d: %s).', $existing->id, $existing->url_path ),
				'page_id' => (int) $existing->id,
			];
		}

		if ( $is_dry_run ) {
			return [
				'success'    => true,
				'dry_run'    => true,
				'message'    => sprintf( 'Dry Run: Would create page for %s + %s.', $city->name, $service->name ),
				'city_id'    => $city_id,
				'service_id' => $service_id,
			];
		}

		// 3. Create City Page record.
		$page_data = [
			'country_id'        => $country_id,
			'state_id'          => $state_id,
			'city_id'           => $city_id,
			'service_id'        => $service_id,
			'status'            => sanitize_text_field( $params['page_status'] ?? 'draft' ),
			'generation_status' => 'not_generated',
			'import_job_id'     => $job->job_id,
		];

		$page_id = City_Page_Repository::create( $page_data );
		if ( $page_id <= 0 ) {
			return [
				'success'    => false,
				'error_code' => 'insert_failed',
				'message'    => 'Failed to insert city page.',
			];
		}

		$created_page = City_Page_Repository::get( $page_id );

		return [
			'success'    => true,
			'page_id'    => $page_id,
			'url_path'   => $created_page ? $created_page->url_path : '',
			'city_name'  => $city->name,
			'service'    => $service->name,
			'created_id' => $page_id,
		];
	}
}
