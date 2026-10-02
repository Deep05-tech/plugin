<?php
/**
 * Bulk CSV Import Chunk Handler.
 *
 * @package SeoCityPages\Jobs\Handlers
 */

namespace SeoCityPages\Jobs\Handlers;

use SeoCityPages\Import\CSV_Processor;
use SeoCityPages\Import\CSV_Normalizer;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\URL\URL_Structure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV Import Handler Class.
 */
class CSV_Import_Handler {

	/**
	 * Process a single CSV row item.
	 *
	 * @param object $item   Job item object.
	 * @param object $job    Bulk job object.
	 * @param array  $params Job parameters.
	 * @return array Result.
	 */
	public static function process( object $item, object $job, array $params = [] ): array {
		$row = is_string( $item->item_payload ) ? json_decode( $item->item_payload, true ) : $item->item_payload;
		if ( ! is_array( $row ) ) {
			return [
				'success'    => false,
				'error_code' => 'invalid_row_payload',
				'message'    => 'Missing or malformed CSV row payload.',
			];
		}

		$country_name = sanitize_text_field( $row['country'] ?? '' );
		$state_name   = sanitize_text_field( $row['state'] ?? '' );
		$city_name    = sanitize_text_field( $row['city'] ?? '' );
		$service_name = sanitize_text_field( $row['service'] ?? '' );

		if ( empty( $country_name ) || empty( $state_name ) || empty( $city_name ) || empty( $service_name ) ) {
			return [
				'success'    => false,
				'error_code' => 'missing_fields',
				'message'    => 'Missing required location or service columns.',
			];
		}

		$is_dry_run = ! empty( $job->is_dry_run ) || ! empty( $params['is_dry_run'] );

		if ( $is_dry_run ) {
			return [
				'success' => true,
				'dry_run' => true,
				'message' => sprintf( 'Dry Run: Would import %s, %s, %s -> %s', $city_name, $state_name, $country_name, $service_name ),
			];
		}

		// 1. Resolve or Create Country.
		$country = Country_Repository::get_by_name( $country_name );
		if ( ! $country ) {
			$c_code = sanitize_text_field( $row['country_code'] ?? '' );
			$c_id   = Country_Repository::create( [
				'name'   => $country_name,
				'code'   => $c_code,
				'status' => 'active',
			] );
			if ( is_wp_error( $c_id ) ) {
				return [
					'success'    => false,
					'error_code' => 'country_create_failed',
					'message'    => $c_id->get_error_message(),
				];
			}
			$country_id = (int) $c_id;
		} else {
			$country_id = (int) $country->id;
		}

		// 2. Resolve or Create State.
		$state = State_Repository::get_by_name( $country_id, $state_name );
		if ( ! $state ) {
			$s_code = sanitize_text_field( $row['state_code'] ?? '' );
			$s_id   = State_Repository::create( [
				'country_id' => $country_id,
				'name'       => $state_name,
				'code'       => $s_code,
				'status'     => 'active',
			] );
			if ( is_wp_error( $s_id ) ) {
				return [
					'success'    => false,
					'error_code' => 'state_create_failed',
					'message'    => $s_id->get_error_message(),
				];
			}
			$state_id = (int) $s_id;
		} else {
			$state_id = (int) $state->id;
		}

		// 3. Resolve or Create City.
		$city = City_Repository::get_by_name( $country_id, $state_id, $city_name );
		if ( ! $city ) {
			$city_slug = ! empty( $row['city_slug'] ) ? sanitize_title( $row['city_slug'] ) : sanitize_title( $city_name );
			$city_id_created = City_Repository::create( [
				'country_id' => $country_id,
				'state_id'   => $state_id,
				'name'       => $city_name,
				'slug'       => $city_slug,
				'status'     => 'active',
			] );
			if ( is_wp_error( $city_id_created ) ) {
				return [
					'success'    => false,
					'error_code' => 'city_create_failed',
					'message'    => $city_id_created->get_error_message(),
				];
			}
			$city_id = (int) $city_id_created;
			$city_obj = City_Repository::get( $city_id );
		} else {
			$city_id  = (int) $city->id;
			$city_obj = $city;
		}

		// 4. Resolve or Create Service.
		$service = Service_Repository::get_by_name( $service_name );
		if ( ! $service ) {
			$service_slug = ! empty( $row['service_slug'] ) ? sanitize_title( $row['service_slug'] ) : sanitize_title( $service_name );
			$svc_id_created = Service_Repository::create( [
				'name'   => $service_name,
				'slug'   => $service_slug,
				'status' => 'active',
			] );
			if ( is_wp_error( $svc_id_created ) ) {
				return [
					'success'    => false,
					'error_code' => 'service_create_failed',
					'message'    => $svc_id_created->get_error_message(),
				];
			}
			$service_id = (int) $svc_id_created;
			$service_obj = Service_Repository::get( $service_id );
		} else {
			$service_id  = (int) $service->id;
			$service_obj = $service;
		}

		// 5. Check if City Page already exists.
		$existing_page = City_Page_Repository::get_by_combination( $country_id, $state_id, $city_id, $service_id );
		if ( $existing_page ) {
			return [
				'success' => true,
				'skipped' => true,
				'reason'  => sprintf( 'Page combination already exists (ID #%d: %s)', $existing_page->id, $existing_page->url_path ),
				'page_id' => (int) $existing_page->id,
			];
		}

		// 6. Create City Page.
		$url_path = URL_Structure::generate_url_path( $service_obj->slug, $city_obj->slug );
		$page_data = [
			'country_id'        => $country_id,
			'state_id'          => $state_id,
			'city_id'           => $city_id,
			'service_id'        => $service_id,
			'slug'              => $city_obj->slug,
			'url_path'          => $url_path,
			'status'            => sanitize_text_field( $row['page_status'] ?? 'draft' ),
			'generation_status' => 'not_generated',
			'import_job_id'     => $job->job_id,
		];

		$page_id = City_Page_Repository::create( $page_data );
		if ( is_wp_error( $page_id ) || $page_id <= 0 ) {
			return [
				'success'    => false,
				'error_code' => 'page_create_failed',
				'message'    => is_wp_error( $page_id ) ? $page_id->get_error_message() : 'Failed to create city page record.',
			];
		}

		return [
			'success'    => true,
			'page_id'    => $page_id,
			'url_path'   => $url_path,
			'created_id' => $page_id,
		];
	}
}
