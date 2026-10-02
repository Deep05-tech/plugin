<?php
/**
 * Bulk Content Approval Handler.
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
 * Approval Handler Class.
 */
class Approval_Handler {

	/**
	 * Process a single content approval item.
	 *
	 * @param object $item   Job item object.
	 * @param object $job    Bulk job object.
	 * @param array  $params Job parameters.
	 * @return array Result.
	 */
	public static function process( object $item, object $job, array $params = [] ): array {
		if ( ! current_user_can( Capabilities::APPROVE_CONTENT ) && ! current_user_can( Capabilities::BULK_APPROVE ) && ! current_user_can( Capabilities::MANAGE ) ) {
			return [
				'success'    => false,
				'error_code' => 'permission_denied',
				'message'    => 'You do not have permission to approve content.',
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

		$is_dry_run = ! empty( $job->is_dry_run ) || ! empty( $params['is_dry_run'] );

		// Check for latest reviewable version.
		$versions = Content_Version_Repository::get_by_page( $page_id, 'needs_review' );
		if ( empty( $versions ) ) {
			// Check if already approved.
			if ( 'approved' === $page->generation_status ) {
				return [
					'success' => true,
					'skipped' => true,
					'reason'  => 'Page is already in approved state.',
					'page_id' => $page_id,
				];
			}

			return [
				'success'    => false,
				'error_code' => 'no_reviewable_version',
				'message'    => sprintf( 'Page #%d has no content versions pending review.', $page_id ),
			];
		}

		$version_to_approve = $versions[0];

		if ( $is_dry_run ) {
			return [
				'success'    => true,
				'dry_run'    => true,
				'page_id'    => $page_id,
				'version_id' => $version_to_approve->id,
				'message'    => sprintf( 'Dry Run: Would approve Version #%d for Page #%d.', $version_to_approve->id, $page_id ),
			];
		}

		$user_id = (int) ( $job->user_id ?? get_current_user_id() );
		$approved = Status_Workflow::approve( (int) $version_to_approve->id, $user_id, 'Bulk approved via Bulk Operations Center' );

		if ( ! $approved ) {
			return [
				'success'    => false,
				'error_code' => 'approval_failed',
				'message'    => sprintf( 'Failed to approve Version #%d for Page #%d.', $version_to_approve->id, $page_id ),
			];
		}

		return [
			'success'    => true,
			'page_id'    => $page_id,
			'version_id' => (int) $version_to_approve->id,
		];
	}
}
