<?php
/**
 * Bulk Validation Handler.
 *
 * @package SeoCityPages\Jobs\Handlers
 */

namespace SeoCityPages\Jobs\Handlers;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Workflow\Quality_Validator;
use SeoCityPages\SEO\SEO_Validator;
use SeoCityPages\SEO\SEO_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validation Handler Class.
 */
class Validation_Handler {

	/**
	 * Process a single validation item.
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

		$errors   = [];
		$warnings = [];

		// 1. Validate relational integrity.
		$rel_check = Quality_Validator::validate_relational_integrity( $page_id );
		if ( ! empty( $rel_check['errors'] ) ) {
			$errors = array_merge( $errors, $rel_check['errors'] );
		}
		if ( ! empty( $rel_check['warnings'] ) ) {
			$warnings = array_merge( $warnings, $rel_check['warnings'] );
		}

		// 2. Validate active content if present.
		$active_version = Content_Version_Repository::get_active_version( $page_id );
		if ( $active_version ) {
			$quality_check = Quality_Validator::validate_content_quality( $active_version->content_json, $page );
			if ( ! empty( $quality_check['errors'] ) ) {
				$errors = array_merge( $errors, $quality_check['errors'] );
			}
			if ( ! empty( $quality_check['warnings'] ) ) {
				$warnings = array_merge( $warnings, $quality_check['warnings'] );
			}
		} else {
			$warnings[] = 'No generated content exists yet for this page.';
		}

		// 3. Validate SEO Metadata.
		$resolved_seo = SEO_Manager::get_instance()->resolve_page_seo( $page );
		$seo_val      = SEO_Validator::validate_metadata( $resolved_seo, $page_id );
		if ( ! empty( $seo_val['errors'] ) ) {
			$errors = array_merge( $errors, $seo_val['errors'] );
		}
		if ( ! empty( $seo_val['warnings'] ) ) {
			$warnings = array_merge( $warnings, $seo_val['warnings'] );
		}

		// 4. Duplicate checks.
		$dupe_titles = City_Page_Repository::find_duplicate_seo_titles( $resolved_seo['seo_title'], $page_id );
		if ( ! empty( $dupe_titles ) ) {
			$warnings[] = sprintf( 'SEO Title is duplicated across %d other published pages.', count( $dupe_titles ) );
		}

		$is_valid = empty( $errors );

		if ( ! $is_valid ) {
			return [
				'success'    => false,
				'error_code' => 'validation_errors',
				'message'    => implode( ' | ', $errors ),
				'warnings'   => $warnings,
				'page_id'    => $page_id,
			];
		}

		return [
			'success'  => true,
			'page_id'  => $page_id,
			'warnings' => $warnings,
			'message'  => empty( $warnings ) ? 'Page is 100% valid.' : sprintf( 'Valid with %d warnings.', count( $warnings ) ),
		];
	}
}
