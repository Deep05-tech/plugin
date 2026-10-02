<?php
/**
 * Plugin Safe Mode & Processing Control Manager.
 *
 * @package SeoCityPages\Diagnostics
 */

namespace SeoCityPages\Diagnostics;

use SeoCityPages\Database\Audit_Log_Repository;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Safe Mode Manager Class.
 */
class Safe_Mode_Manager {

	public const OPTION_SAFE_MODE   = 'seo_city_pages_safe_mode';
	public const OPTION_PAUSE_QUEUE = 'seo_city_pages_pause_processing';

	/**
	 * Check if Plugin Safe Mode is currently active.
	 *
	 * When Safe Mode is active:
	 * - AI content generation is blocked.
	 * - Bulk publishing is blocked.
	 * - Background queue execution is suspended.
	 * - Public frontend rendering and diagnostics continue working normally.
	 *
	 * @return bool
	 */
	public static function is_safe_mode_active(): bool {
		return (bool) get_option( self::OPTION_SAFE_MODE, false );
	}

	/**
	 * Enable Safe Mode.
	 *
	 * @param string $reason Reason for enabling.
	 * @return bool
	 */
	public static function enable_safe_mode( string $reason = '' ): bool {
		$updated = update_option( self::OPTION_SAFE_MODE, 1 );
		Logger::warning( 'Plugin Safe Mode has been ACTIVATED.', [ 'reason' => $reason ], Logger::CAT_GENERAL );
		Audit_Log_Repository::log_event( 'enable_safe_mode', 'system', 0, [ 'reason' => $reason ] );
		return $updated;
	}

	/**
	 * Disable Safe Mode.
	 *
	 * @return bool
	 */
	public static function disable_safe_mode(): bool {
		$updated = update_option( self::OPTION_SAFE_MODE, 0 );
		Logger::info( 'Plugin Safe Mode has been DEACTIVATED.', [], Logger::CAT_GENERAL );
		Audit_Log_Repository::log_event( 'disable_safe_mode', 'system', 0 );
		return $updated;
	}

	/**
	 * Check if background processing / queue runner is paused.
	 *
	 * @return bool
	 */
	public static function is_processing_paused(): bool {
		if ( self::is_safe_mode_active() ) {
			return true;
		}
		return (bool) get_option( self::OPTION_PAUSE_QUEUE, false );
	}

	/**
	 * Pause background processing without full Safe Mode.
	 *
	 * @param string $reason Pause reason.
	 * @return bool
	 */
	public static function pause_processing( string $reason = '' ): bool {
		$updated = update_option( self::OPTION_PAUSE_QUEUE, 1 );
		Logger::warning( 'Background queue processing PAUSED.', [ 'reason' => $reason ], Logger::CAT_QUEUE );
		Audit_Log_Repository::log_event( 'pause_processing', 'queue', 0, [ 'reason' => $reason ] );
		return $updated;
	}

	/**
	 * Resume background processing.
	 *
	 * @return bool
	 */
	public static function resume_processing(): bool {
		$updated = update_option( self::OPTION_PAUSE_QUEUE, 0 );
		Logger::info( 'Background queue processing RESUMED.', [], Logger::CAT_QUEUE );
		Audit_Log_Repository::log_event( 'resume_processing', 'queue', 0 );
		return $updated;
	}
}
