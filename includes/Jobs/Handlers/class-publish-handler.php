<?php
/**
 * Bulk Publish Handler.
 *
 * @package SeoCityPages\Jobs\Handlers
 */

namespace SeoCityPages\Jobs\Handlers;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Workflow\Status_Workflow;
use SeoCityPages\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Publish Handler Class.
 */
class Publish_Handler {

	/**
	 * Process a single publish item.
	 *
	 * @param object $item   Job item object.
	 * @param object $job    Bulk job object.
	 * @param array  $params Job parameters.
	 * @return array Result.
	 */
	public static function process( object $item, object $job, array $params = [] ): array {
		if ( ! current_user_can( Capabilities::PUBLISH_CONTENT ) && ! current_user_can( Capabilities::BULK_PUBLISH ) && ! current_user_can( Capabilities::MANAGE ) ) {
			return [
				'success'    => false,
				'error_code' => 'permission_denied',
				'message'    => 'You do not have permission to publish city pages.',
			];
		}

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

		// Check if already published.
		if ( ! empty( $page->is_published ) && 'published' === $page->status ) {
			return [
				'success' => true,
				'skipped' => true,
				'reason'  => sprintf( 'Page #%d is already live published.', $page_id ),
				'page_id' => $page_id,
			];
		}

		// Find approved version.
		$active_version = Content_Version_Repository::get_active_version( $page_id );
		if ( ! $active_version || 'approved' !== $active_version->status ) {
			return [
				'success'    => false,
				'error_code' => 'not_approved',
				'message'    => sprintf( 'Page #%d cannot be published because it has no approved content version (Current: %s).', $page_id, $page->generation_status ),
			];
		}

		$is_dry_run = ! empty( $job->is_dry_run ) || ! empty( $params['is_dry_run'] );
		if ( $is_dry_run ) {
			return [
				'success'    => true,
				'dry_run'    => true,
				'page_id'    => $page_id,
				'version_id' => $active_version->id,
				'url_path'   => $page->url_path,
				'message'    => sprintf( 'Dry Run: Eligible to publish Page #%d (%s).', $page_id, $page->url_path ),
			];
		}

		$user_id = (int) ( $job->user_id ?? get_current_user_id() );
		$publish_result = Status_Workflow::publish( $page_id, (int) $active_version->id, $user_id );

		if ( empty( $publish_result['success'] ) ) {
			return [
				'success'    => false,
				'error_code' => 'publish_failed',
				'message'    => $publish_result['error'] ?? 'Unknown publishing failure.',
			];
		}

		return [
			'success'    => true,
			'page_id'    => $page_id,
			'version_id' => (int) $active_version->id,
			'wp_post_id' => $publish_result['wp_post_id'] ?? 0,
			'url_path'   => $page->url_path,
		];
	}
}
