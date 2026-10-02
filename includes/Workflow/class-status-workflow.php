<?php
/**
 * Workflow State Machine & Lifecycle Controller.
 *
 * Enforces strict transitions, human-in-the-loop approvals, non-destructive
 * regenerations, and publishing gatekeeping.
 *
 * @package SeoCityPages\Workflow
 */

namespace SeoCityPages\Workflow;

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Quality\Quality_Gate_Manager;
use SeoCityPages\Logging\Audit_Logger;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Status_Workflow Class.
 */
class Status_Workflow {

	/**
	 * Lifecycle Status Constants.
	 */
	public const STATUS_NOT_GENERATED   = 'not_generated';
	public const STATUS_QUEUED          = 'queued';
	public const STATUS_GENERATING      = 'generating';
	public const STATUS_NEEDS_REVIEW    = 'needs_review';
	public const STATUS_APPROVED        = 'approved';
	public const STATUS_REJECTED        = 'rejected';
	public const STATUS_MANUALLY_EDITED = 'manually_edited';
	public const STATUS_PUBLISHED       = 'published';
	public const STATUS_ARCHIVED        = 'archived';
	public const STATUS_FAILED          = 'failed';

	/**
	 * Allowed transition map.
	 *
	 * @var array<string, string[]>
	 */
	private const TRANSITIONS = [
		self::STATUS_NOT_GENERATED   => [ self::STATUS_QUEUED, self::STATUS_GENERATING, self::STATUS_FAILED ],
		self::STATUS_QUEUED          => [ self::STATUS_GENERATING, self::STATUS_NOT_GENERATED, self::STATUS_FAILED ],
		self::STATUS_GENERATING      => [ self::STATUS_NEEDS_REVIEW, self::STATUS_FAILED, self::STATUS_NOT_GENERATED ],
		self::STATUS_NEEDS_REVIEW    => [ self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_MANUALLY_EDITED, self::STATUS_QUEUED, self::STATUS_GENERATING ],
		self::STATUS_APPROVED        => [ self::STATUS_PUBLISHED, self::STATUS_REJECTED, self::STATUS_MANUALLY_EDITED, self::STATUS_NEEDS_REVIEW, self::STATUS_QUEUED, self::STATUS_GENERATING ],
		self::STATUS_REJECTED        => [ self::STATUS_QUEUED, self::STATUS_GENERATING, self::STATUS_MANUALLY_EDITED, self::STATUS_NEEDS_REVIEW ],
		self::STATUS_MANUALLY_EDITED => [ self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_NEEDS_REVIEW, self::STATUS_QUEUED, self::STATUS_GENERATING ],
		self::STATUS_PUBLISHED       => [ self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_ARCHIVED, self::STATUS_NEEDS_REVIEW ],
		self::STATUS_FAILED          => [ self::STATUS_QUEUED, self::STATUS_GENERATING, self::STATUS_NOT_GENERATED ],
		self::STATUS_ARCHIVED        => [ self::STATUS_NEEDS_REVIEW, self::STATUS_APPROVED, self::STATUS_QUEUED ],
	];

	/**
	 * Check if a state transition is legally permissible.
	 *
	 * @param string $from_status Current status.
	 * @param string $to_status   Target status.
	 * @return bool
	 */
	public static function can_transition( string $from_status, string $to_status ): bool {
		if ( $from_status === $to_status ) {
			return true;
		}

		$allowed = self::TRANSITIONS[ $from_status ] ?? [];
		return in_array( $to_status, $allowed, true );
	}

	/**
	 * Approve a content version.
	 *
	 * @param int    $version_id Version ID to approve.
	 * @param int    $user_id    User performing the approval.
	 * @param string $notes      Optional review notes.
	 * @return bool
	 */
	public static function approve( int $version_id, int $user_id = 0, string $notes = '' ): bool {
		global $wpdb;
		$version = Content_Version_Repository::get( $version_id );
		if ( ! $version ) {
			return false;
		}

		$versions_table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );
		$pages_table    = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$now     = current_time( 'mysql' );
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();

		$update_version = [
			'status'      => self::STATUS_APPROVED,
			'approved_at' => $now,
			'approved_by' => $user_id,
			'updated_at'  => $now,
		];
		if ( ! empty( $notes ) ) {
			$update_version['review_notes'] = sanitize_textarea_field( $notes );
		}

		$wpdb->update( $versions_table, $update_version, [ 'id' => $version_id ] );

		// Update City Page generation status and current_version_id.
		// Note: is_published and published_version_id are NOT modified here (strict separation of approval and publishing).
		$wpdb->update(
			$pages_table,
			[
				'current_version_id' => $version_id,
				'generation_status'  => self::STATUS_APPROVED,
				'updated_at'         => $now,
			],
			[ 'id' => $version->page_id ]
		);

		Audit_Logger::log_approval( $version_id, (int) $version->page_id, [ 'review_notes' => $notes ] );

		return true;
	}

	/**
	 * Reject a content version with mandatory or optional feedback reason.
	 *
	 * @param int    $version_id Version ID.
	 * @param string $reason     Rejection reason.
	 * @param int    $user_id    Reviewer user ID.
	 * @param string $notes      Optional notes.
	 * @return bool
	 */
	public static function reject( int $version_id, string $reason = '', int $user_id = 0, string $notes = '' ): bool {
		global $wpdb;
		$version = Content_Version_Repository::get( $version_id );
		if ( ! $version ) {
			return false;
		}

		$versions_table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );
		$pages_table    = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$now     = current_time( 'mysql' );
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();

		$update_data = [
			'status'           => self::STATUS_REJECTED,
			'rejection_reason' => sanitize_textarea_field( $reason ),
			'rejected_at'      => $now,
			'rejected_by'      => $user_id,
			'updated_at'       => $now,
		];
		if ( ! empty( $notes ) ) {
			$update_data['review_notes'] = sanitize_textarea_field( $notes );
		}

		$wpdb->update( $versions_table, $update_data, [ 'id' => $version_id ] );

		// Check if this version was the active draft current_version_id on the page.
		// If so, update page generation_status to rejected.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$pages_table} SET generation_status = %s, updated_at = %s WHERE id = %d AND current_version_id = %d",
				self::STATUS_REJECTED,
				$now,
				$version->page_id,
				$version_id
			)
		);

		Audit_Logger::log_rejection( $version_id, (int) $version->page_id, $reason, [ 'review_notes' => $notes ] );

		return true;
	}

	/**
	 * Publish an approved content version to the live public page.
	 *
	 * @param int $page_id    City Page ID.
	 * @param int $version_id Content version ID to publish (defaults to current_version_id).
	 * @param int $user_id    User performing the action.
	 * @return array ['success' => bool, 'message' => string, 'errors' => array]
	 */
	public static function publish( int $page_id, int $version_id = 0, int $user_id = 0 ): array {
		global $wpdb;

		// 1. Relational taxonomy integrity check.
		$relational = Quality_Validator::validate_relational_integrity( $page_id );
		if ( ! $relational['is_valid'] ) {
			return [
				'success' => false,
				'message' => 'Cannot publish page: source taxonomy relational integrity validation failed.',
				'errors'  => $relational['errors'],
			];
		}

		$page = $relational['page'];

		// 2. Identify target version.
		$target_version_id = $version_id > 0 ? $version_id : (int) $page->current_version_id;
		if ( $target_version_id <= 0 ) {
			return [
				'success' => false,
				'message' => 'Cannot publish page: No content version has been generated or selected.',
				'errors'  => [ 'No content version available.' ],
			];
		}

		$version = Content_Version_Repository::get( $target_version_id );
		if ( ! $version || (int) $version->page_id !== $page_id ) {
			return [
				'success' => false,
				'message' => 'Cannot publish page: Content version does not exist or does not belong to this page.',
				'errors'  => [ 'Invalid content version.' ],
			];
		}

		// 3. Strict state check: content version MUST be 'approved' before publication.
		if ( self::STATUS_APPROVED !== $version->status ) {
			return [
				'success' => false,
				'message' => sprintf( 'Cannot publish: Version #%d status is "%s". Only APPROVED versions can be published.', $target_version_id, $version->status ),
				'errors'  => [ 'Version must be approved prior to publishing.' ],
			];
		}

		// 4. Pre-publication Quality Gate validation.
		if ( class_exists( Quality_Gate_Manager::class ) ) {
			$gate_check = Quality_Gate_Manager::can_publish( $page_id, (array) $page );
			if ( ! $gate_check['allowed'] ) {
				return [
					'success' => false,
					'message' => 'Cannot publish page: ' . $gate_check['reason'],
					'errors'  => [ $gate_check['reason'] ],
				];
			}
		}

		$now     = current_time( 'mysql' );
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();

		$pages_table    = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$versions_table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );

		// Update City Page.
		$wpdb->update(
			$pages_table,
			[
				'status'               => City_Page_Repository::STATUS_PUBLISHED,
				'is_published'         => 1,
				'published_version_id' => $target_version_id,
				'published_at'         => $now,
				'published_by'         => $user_id,
				'updated_at'           => $now,
			],
			[ 'id' => $page_id ]
		);

		// Update Content Version record published metadata.
		$wpdb->update(
			$versions_table,
			[
				'published_at' => $now,
				'published_by' => $user_id,
				'updated_at'   => $now,
			],
			[ 'id' => $target_version_id ]
		);

		Audit_Logger::log_publish( $target_version_id, $page_id, [ 'version_num' => $version->version_num ] );

		// Synchronize with WordPress core wp_posts table to appear in Pages -> All Pages.
		if ( class_exists( '\\SeoCityPages\\Integration\\Post_Sync' ) ) {
			\SeoCityPages\Integration\Post_Sync::sync_page( $page_id );
		}

		return [
			'success' => true,
			'message' => sprintf( 'Page #%d successfully published with Version #%d.', $page_id, $target_version_id ),
			'errors'  => [],
		];
	}

	/**
	 * Unpublish a live page (reverts to draft).
	 *
	 * @param int    $page_id City Page ID.
	 * @param int    $user_id User ID.
	 * @param string $reason  Optional unpublishing reason.
	 * @return bool
	 */
	public static function unpublish( int $page_id, int $user_id = 0, string $reason = '' ): bool {
		global $wpdb;
		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return false;
		}

		$now     = current_time( 'mysql' );
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();

		$wpdb->update(
			$pages_table,
			[
				'status'               => City_Page_Repository::STATUS_DRAFT,
				'is_published'         => 0,
				'published_version_id' => null,
				'updated_at'           => $now,
			],
			[ 'id' => $page_id ]
		);

		Audit_Logger::log_unpublish( $page_id, [ 'reason' => $reason, 'user_id' => $user_id ] );

		// Set status to draft in WordPress core wp_posts table.
		if ( class_exists( '\\SeoCityPages\\Integration\\Post_Sync' ) ) {
			\SeoCityPages\Integration\Post_Sync::unpublish_page( $page_id );
		}

		return true;
	}

	/**
	 * Restore a previous version by creating an immutable new version (v_{N+1}).
	 *
	 * @param int $source_version_id ID of the version being restored.
	 * @param int $user_id           User performing restoration.
	 * @param bool $auto_approve     Whether the restored version should immediately be approved.
	 * @return int Newly created version ID or 0 on failure.
	 */
	public static function restore_version( int $source_version_id, int $user_id = 0, bool $auto_approve = true ): int {
		$source = Content_Version_Repository::get( $source_version_id );
		if ( ! $source ) {
			return 0;
		}

		$user_id = $user_id > 0 ? $user_id : get_current_user_id();

		$new_version_id = Content_Version_Repository::create(
			[
				'page_id'            => $source->page_id,
				'status'             => $auto_approve ? self::STATUS_APPROVED : self::STATUS_NEEDS_REVIEW,
				'content_json'       => $source->content_json,
				'provider'           => $source->provider,
				'model'              => $source->model,
				'prompt_version'     => $source->prompt_version,
				'schema_version'     => $source->schema_version,
				'user_id'            => $user_id,
				'source'             => 'restored',
				'parent_version_id'  => $source_version_id,
				'review_notes'       => sprintf( 'Restored from Version #%d (v%d)', $source_version_id, $source->version_num ),
				'input_tokens'       => 0,
				'output_tokens'      => 0,
				'total_tokens'       => 0,
				'generation_time_ms' => 0,
			]
		);

		if ( $new_version_id > 0 ) {
			Audit_Logger::log_restore( $new_version_id, $source_version_id, (int) $source->page_id );
			Quality_Gate_Manager::invalidate( (int) $source->page_id );
		}

		return $new_version_id;
	}

	/**
	 * Save manual edits to content by creating a new immutable version (v_{N+1}).
	 *
	 * @param int          $page_id         City Page ID.
	 * @param int          $base_version_id Parent / base version ID.
	 * @param array|string $content         Edited content array or JSON string.
	 * @param int          $user_id         Editor user ID.
	 * @param string       $notes           Change summary / notes.
	 * @param bool         $auto_approve    Whether to mark approved immediately.
	 * @return int Newly created version ID or 0 on failure.
	 */
	public static function save_manual_edit( int $page_id, int $base_version_id, $content, int $user_id = 0, string $notes = '', bool $auto_approve = false ): int {
		$base = Content_Version_Repository::get( $base_version_id );

		$content_json = is_string( $content ) ? $content : wp_json_encode( $content );
		$user_id      = $user_id > 0 ? $user_id : get_current_user_id();
		$status       = $auto_approve ? self::STATUS_APPROVED : self::STATUS_NEEDS_REVIEW;

		$new_version_id = Content_Version_Repository::create(
			[
				'page_id'            => $page_id,
				'status'             => $status,
				'content_json'       => $content_json,
				'provider'           => 'manual',
				'model'              => 'editor',
				'prompt_version'     => $base ? $base->prompt_version : '1.0',
				'schema_version'     => $base ? $base->schema_version : '1.0',
				'user_id'            => $user_id,
				'source'             => 'manual',
				'is_manually_edited' => 1,
				'parent_version_id'  => $base_version_id > 0 ? $base_version_id : null,
				'review_notes'       => $notes ?: 'Manual edits saved via Content Editor',
				'input_tokens'       => 0,
				'output_tokens'      => 0,
				'total_tokens'       => 0,
				'generation_time_ms' => 0,
			]
		);

		if ( $new_version_id > 0 ) {
			Audit_Logger::log_edit( $new_version_id, $page_id, [ 'base_version_id' => $base_version_id, 'notes' => $notes ] );
			Quality_Gate_Manager::invalidate( $page_id );
		}

		return $new_version_id;
	}

	/**
	 * Approve all generated content versions currently pending review.
	 *
	 * @param int    $user_id User ID performing the action.
	 * @param string $notes   Review notes.
	 * @return array ['success' => bool, 'processed' => int, 'total' => int]
	 */
	public static function approve_all( int $user_id = 0, string $notes = '' ): array {
		global $wpdb;
		$versions_table = Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS );
		$user_id        = $user_id > 0 ? $user_id : get_current_user_id();
		$notes          = ! empty( $notes ) ? $notes : 'Approved all generated versions via 1-Click Action';

		// Find distinct page IDs where latest version is in needs_review, rejected, or manually_edited.
		$pages_with_pending = $wpdb->get_col(
			"SELECT DISTINCT page_id FROM {$versions_table} WHERE status IN ('needs_review', 'rejected', 'manually_edited')"
		);

		$processed = 0;
		if ( ! empty( $pages_with_pending ) ) {
			foreach ( $pages_with_pending as $page_id ) {
				$latest = Content_Version_Repository::get_latest_version( (int) $page_id );
				if ( $latest && in_array( $latest->status, [ self::STATUS_NEEDS_REVIEW, self::STATUS_REJECTED, self::STATUS_MANUALLY_EDITED ], true ) ) {
					if ( self::approve( (int) $latest->id, $user_id, $notes ) ) {
						$processed++;
					}
				}
			}
		}

		Audit_Logger::log_bulk_action( 'approve_all', $pages_with_pending ?: [], [ 'processed' => $processed ] );

		return [
			'success'   => true,
			'processed' => $processed,
			'total'     => count( $pages_with_pending ?: [] ),
		];
	}

	/**
	 * Publish all approved city pages to the live public site and sync to WordPress Pages.
	 *
	 * @param int $user_id User ID performing the action.
	 * @return array ['success' => bool, 'processed' => int, 'total' => int, 'errors' => array]
	 */
	public static function publish_all( int $user_id = 0 ): array {
		global $wpdb;
		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$user_id     = $user_id > 0 ? $user_id : get_current_user_id();

		// Find all pages that have approved content.
		$approved_pages = $wpdb->get_col(
			"SELECT id FROM {$pages_table} WHERE generation_status = 'approved'"
		);

		$processed = 0;
		$errors    = [];

		if ( ! empty( $approved_pages ) ) {
			foreach ( $approved_pages as $page_id ) {
				$result = self::publish( (int) $page_id, 0, $user_id );
				if ( $result['success'] ) {
					$processed++;
				} else {
					$errors[ (int) $page_id ] = $result['message'];
				}
			}
		}

		// Also trigger WordPress core post synchronization for any newly published pages.
		\SeoCityPages\Integration\Post_Sync::maybe_sync_all_published();

		Audit_Logger::log_bulk_action( 'publish_all', $approved_pages ?: [], [ 'processed' => $processed, 'errors_count' => count( $errors ) ] );

		return [
			'success'   => empty( $errors ),
			'processed' => $processed,
			'total'     => count( $approved_pages ?: [] ),
			'errors'    => $errors,
		];
	}

	/**
	 * 1-Click Approve all pending content and immediately publish all approved pages.
	 *
	 * @param int $user_id User ID performing the action.
	 * @return array ['success' => bool, 'approved' => int, 'published' => int, 'errors' => array]
	 */
	public static function approve_and_publish_all( int $user_id = 0 ): array {
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();

		// 1. Approve all pending
		$app_result = self::approve_all( $user_id, 'Bulk Approved via 1-Click Approve & Publish All' );

		// 2. Publish all approved
		$pub_result = self::publish_all( $user_id );

		return [
			'success'   => $pub_result['success'],
			'approved'  => $app_result['processed'],
			'published' => $pub_result['processed'],
			'processed' => $pub_result['processed'],
			'errors'    => $pub_result['errors'],
		];
	}

	/**
	 * Execute bulk workflow action across multiple pages/versions.
	 *
	 * @param string $action   'approve', 'reject', 'publish', 'unpublish', 'approve_all', 'publish_all', 'approve_and_publish_all'.
	 * @param int[]  $page_ids Array of City Page IDs.
	 * @param array  $options  Action options (e.g. rejection reason).
	 * @return array ['success' => bool, 'processed' => int, 'errors' => array]
	 */
	public static function bulk_workflow_action( string $action, array $page_ids = [], array $options = [] ): array {
		$processed = 0;
		$errors    = [];
		$user_id   = get_current_user_id();

		if ( 'approve_all' === $action ) {
			return self::approve_all( $user_id, $options['notes'] ?? '' );
		} elseif ( 'publish_all' === $action ) {
			return self::publish_all( $user_id );
		} elseif ( 'approve_and_publish_all' === $action ) {
			return self::approve_and_publish_all( $user_id );
		}

		foreach ( $page_ids as $page_id ) {
			$page_id = (int) $page_id;
			if ( $page_id <= 0 ) {
				continue;
			}

			switch ( $action ) {
				case 'approve':
					$latest = Content_Version_Repository::get_latest_version( $page_id );
					if ( $latest && in_array( $latest->status, [ self::STATUS_NEEDS_REVIEW, self::STATUS_REJECTED, self::STATUS_MANUALLY_EDITED ], true ) ) {
						if ( self::approve( (int) $latest->id, $user_id, $options['notes'] ?? 'Bulk Approved' ) ) {
							$processed++;
						}
					}
					break;

				case 'reject':
					$latest = Content_Version_Repository::get_latest_version( $page_id );
					if ( $latest ) {
						$reason = $options['reason'] ?? 'Bulk rejected by administrator';
						if ( self::reject( (int) $latest->id, $reason, $user_id, $options['notes'] ?? '' ) ) {
							$processed++;
						}
					}
					break;

				case 'publish':
					$result = self::publish( $page_id, 0, $user_id );
					if ( $result['success'] ) {
						$processed++;
					} else {
						$errors[ $page_id ] = $result['message'];
					}
					break;

				case 'unpublish':
					if ( self::unpublish( $page_id, $user_id, $options['reason'] ?? 'Bulk unpublished' ) ) {
						$processed++;
					}
					break;
			}
		}

		Audit_Logger::log_bulk_action( $action, $page_ids, [ 'processed' => $processed, 'errors_count' => count( $errors ) ] );

		return [
			'success'   => empty( $errors ),
			'processed' => $processed,
			'errors'    => $errors,
		];
	}
}
