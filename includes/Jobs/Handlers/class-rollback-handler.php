<?php
/**
 * Bulk Rollback Handler.
 *
 * @package SeoCityPages\Jobs\Handlers
 */

namespace SeoCityPages\Jobs\Handlers;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Database\Schema;
use SeoCityPages\Logging\Logger;
use SeoCityPages\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rollback Handler Class.
 */
class Rollback_Handler {

	/**
	 * Process a single rollback item.
	 *
	 * @param object $item   Job item object.
	 * @param object $job    Bulk job object.
	 * @param array  $params Job parameters.
	 * @return array Result.
	 */
	public static function process( object $item, object $job, array $params = [] ): array {
		if ( ! current_user_can( Capabilities::BULK_ROLLBACK ) && ! current_user_can( Capabilities::MANAGE ) ) {
			return [
				'success'    => false,
				'error_code' => 'permission_denied',
				'message'    => 'You do not have permission to rollback bulk jobs.',
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
				'message'    => 'Valid City Page ID is required for rollback.',
			];
		}

		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return [
				'success' => true,
				'skipped' => true,
				'reason'  => sprintf( 'Page #%d no longer exists; already rolled back.', $page_id ),
			];
		}

		// SAFETY CHECK 1: Never delete live published pages.
		if ( ! empty( $page->is_published ) || 'published' === $page->status ) {
			return [
				'success' => true,
				'skipped' => true,
				'safety_warning' => 'Rollback Requires Review',
				'reason'  => sprintf( 'Page #%d is live published. Rollback Requires Review.', $page_id ),
				'page_id' => $page_id,
			];
		}

		// SAFETY CHECK 2: Never delete pages with approved or manually edited content.
		$versions = Content_Version_Repository::get_by_page( $page_id );
		foreach ( $versions as $v ) {
			if ( 'approved' === $v->status || ! empty( $v->is_manually_edited ) ) {
				return [
					'success' => true,
					'skipped' => true,
					'safety_warning' => 'Rollback Requires Review',
					'reason'  => sprintf( 'Page #%d contains approved/manual content (v%d). Rollback Requires Review.', $page_id, $v->version_num ),
					'page_id' => $page_id,
				];
			}
		}

		$is_dry_run = ! empty( $job->is_dry_run ) || ! empty( $params['is_dry_run'] );
		if ( $is_dry_run ) {
			return [
				'success' => true,
				'dry_run' => true,
				'message' => sprintf( 'Dry Run: Safe to rollback Page #%d (%s).', $page_id, $page->url_path ),
				'page_id' => $page_id,
			];
		}

		// Safe to delete draft & not_generated page.
		$deleted = City_Page_Repository::delete( $page_id );
		if ( ! $deleted ) {
			return [
				'success'    => false,
				'error_code' => 'delete_failed',
				'message'    => sprintf( 'Failed to delete Page #%d during rollback.', $page_id ),
			];
		}

		return [
			'success'  => true,
			'page_id'  => $page_id,
			'url_path' => $page->url_path,
			'message'  => sprintf( 'Successfully rolled back Page #%d.', $page_id ),
		];
	}
}
