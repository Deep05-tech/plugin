<?php
/**
 * Bulk SEO Metadata Generation Handler.
 *
 * @package SeoCityPages\Jobs\Handlers
 */

namespace SeoCityPages\Jobs\Handlers;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\SEO\SEO_Manager;
use SeoCityPages\SEO\SEO_Template_Engine;
use SeoCityPages\SEO\SEO_Validator;
use SeoCityPages\SEO\SEO_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO Generation Handler Class.
 */
class SEO_Generation_Handler {

	/**
	 * Process a single SEO generation item.
	 *
	 * @param object $item   Job item object.
	 * @param object $job    Bulk job object.
	 * @param array  $params Job parameters.
	 * @return array Result.
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
				'message'    => 'Valid City Page ID is required for SEO generation.',
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

		// 1. Check for manual override lock.
		if ( ! empty( $page->is_manual_seo_override ) && empty( $params['overwrite_manual'] ) ) {
			return [
				'success' => true,
				'skipped' => true,
				'reason'  => sprintf( 'Page #%d has a locked manual SEO override; skipping.', $page_id ),
				'page_id' => $page_id,
			];
		}

		$is_dry_run = ! empty( $job->is_dry_run ) || ! empty( $params['is_dry_run'] );

		// 2. Resolve or compute template-driven SEO metadata.
		$resolved = SEO_Manager::get_instance()->resolve_page_seo( $page );

		if ( $is_dry_run ) {
			return [
				'success'   => true,
				'dry_run'   => true,
				'page_id'   => $page_id,
				'seo_title' => $resolved['seo_title'],
				'meta_desc' => $resolved['meta_description'],
				'canonical' => $resolved['canonical_url'],
				'message'   => sprintf( 'Dry Run: Generated SEO title "%s" for Page #%d.', $resolved['seo_title'], $page_id ),
			];
		}

		// 3. Save generated SEO fields on city page.
		$update_fields = [
			'seo_title'        => $resolved['seo_title'],
			'meta_description' => $resolved['meta_description'],
			'canonical_url'    => $resolved['canonical_url'],
			'robots_index'     => $resolved['robots_index'],
			'robots_follow'    => $resolved['robots_follow'],
		];

		$saved = City_Page_Repository::update_seo( $page_id, $update_fields );
		if ( ! $saved ) {
			return [
				'success'    => false,
				'error_code' => 'save_failed',
				'message'    => sprintf( 'Failed to save SEO metadata for Page #%d.', $page_id ),
			];
		}

		return [
			'success'   => true,
			'page_id'   => $page_id,
			'seo_title' => $resolved['seo_title'],
		];
	}
}
