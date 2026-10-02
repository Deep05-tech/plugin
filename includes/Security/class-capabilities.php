<?php
/**
 * Plugin Capabilities Management.
 *
 * @package SeoCityPages\Security
 */

namespace SeoCityPages\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capabilities Class.
 */
class Capabilities {

	/**
	 * Master management capability.
	 */
	public const MANAGE = 'manage_seo_city_pages';

	/**
	 * View system status capability.
	 */
	public const VIEW_STATUS = 'view_seo_city_pages_status';

	/**
	 * Manage settings capability.
	 */
	public const MANAGE_SETTINGS = 'manage_seo_city_pages_settings';

	/**
	 * View activity/error logs capability.
	 */
	public const VIEW_LOGS = 'view_seo_city_pages_logs';

	/**
	 * Review content capability.
	 */
	public const REVIEW_CONTENT = 'seo_city_pages_review_content';

	/**
	 * Edit content capability.
	 */
	public const EDIT_CONTENT = 'seo_city_pages_edit_content';

	/**
	 * Approve content capability.
	 */
	public const APPROVE_CONTENT = 'seo_city_pages_approve_content';

	/**
	 * Publish content capability.
	 */
	public const PUBLISH_CONTENT = 'seo_city_pages_publish_content';

	/**
	 * Regenerate content capability.
	 */
	public const REGENERATE_CONTENT = 'seo_city_pages_regenerate_content';

	/**
	 * Manage SEO settings capability.
	 */
	public const MANAGE_SEO = 'seo_city_pages_manage_seo';

	/**
	 * Edit SEO metadata capability.
	 */
	public const EDIT_SEO = 'seo_city_pages_edit_seo';

	/**
	 * Bulk import capability.
	 */
	public const BULK_IMPORT = 'seo_city_pages_bulk_import';

	/**
	 * Bulk export capability.
	 */
	public const BULK_EXPORT = 'seo_city_pages_bulk_export';

	/**
	 * Bulk create pages capability.
	 */
	public const BULK_CREATE = 'seo_city_pages_bulk_create';

	/**
	 * Bulk generate content & SEO capability.
	 */
	public const BULK_GENERATE = 'seo_city_pages_bulk_generate';

	/**
	 * Bulk approve content capability.
	 */
	public const BULK_APPROVE = 'seo_city_pages_bulk_approve';

	/**
	 * Bulk publish capability.
	 */
	public const BULK_PUBLISH = 'seo_city_pages_bulk_publish';

	/**
	 * Bulk rollback capability.
	 */
	public const BULK_ROLLBACK = 'seo_city_pages_bulk_rollback';

	/**
	 * View system health dashboard capability.
	 */
	public const VIEW_HEALTH = 'view_seo_city_pages_health';

	/**
	 * Run diagnostics & integrity scans capability.
	 */
	public const RUN_DIAGNOSTICS = 'seo_city_pages_run_diagnostics';

	/**
	 * Execute repair tools capability.
	 */
	public const EXECUTE_REPAIR = 'seo_city_pages_execute_repair';

	/**
	 * View audit logs capability.
	 */
	public const VIEW_AUDIT_LOGS = 'view_seo_city_pages_audit_logs';

	/**
	 * Toggle safe mode capability.
	 */
	public const TOGGLE_SAFE_MODE = 'seo_city_pages_toggle_safe_mode';

	/**
	 * Manage quality dashboard & review capability.
	 */
	public const MANAGE_QUALITY = 'manage_seo_city_pages_quality';

	/**
	 * Run quality & similarity scans capability.
	 */
	public const RUN_QUALITY_SCAN = 'seo_city_pages_run_quality_scan';

	/**
	 * Dismiss quality warnings capability.
	 */
	public const DISMISS_QUALITY_WARNING = 'seo_city_pages_dismiss_quality_warning';

	/**
	 * Get list of all plugin custom capabilities.
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return [
			self::MANAGE,
			self::VIEW_STATUS,
			self::MANAGE_SETTINGS,
			self::VIEW_LOGS,
			self::REVIEW_CONTENT,
			self::EDIT_CONTENT,
			self::APPROVE_CONTENT,
			self::PUBLISH_CONTENT,
			self::REGENERATE_CONTENT,
			self::MANAGE_SEO,
			self::EDIT_SEO,
			self::BULK_IMPORT,
			self::BULK_EXPORT,
			self::BULK_CREATE,
			self::BULK_GENERATE,
			self::BULK_APPROVE,
			self::BULK_PUBLISH,
			self::BULK_ROLLBACK,
			self::VIEW_HEALTH,
			self::RUN_DIAGNOSTICS,
			self::EXECUTE_REPAIR,
			self::VIEW_AUDIT_LOGS,
			self::TOGGLE_SAFE_MODE,
			self::MANAGE_QUALITY,
			self::RUN_QUALITY_SCAN,
			self::DISMISS_QUALITY_WARNING,
		];
	}

	/**
	 * Assign capabilities to the administrator role.
	 *
	 * @return void
	 */
	public static function register_defaults(): void {
		if ( ! function_exists( 'get_role' ) ) {
			return;
		}

		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}

		foreach ( self::get_all() as $cap ) {
			$role->add_cap( $cap );
		}
	}

	/**
	 * Alias for register_defaults().
	 *
	 * @return void
	 */
	public static function register(): void {
		self::register_defaults();
	}

	/**
	 * Remove capabilities from roles upon complete removal.
	 *
	 * @return void
	 */
	public static function unregister_all(): void {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		foreach ( $wp_roles->roles as $role_name => $role_info ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( self::get_all() as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	/**
	 * Check if current user has a plugin capability, with fallback to manage_options.
	 *
	 * @param string $cap Capability name.
	 * @return bool
	 */
	public static function user_can( string $cap = self::MANAGE ): bool {
		return current_user_can( $cap ) || current_user_can( 'manage_options' );
	}
}
