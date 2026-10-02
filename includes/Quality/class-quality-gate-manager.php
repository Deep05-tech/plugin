<?php
/**
 * Pre-Publication Quality Gate Manager.
 *
 * Enforces quality validation policies before a page can transition to published status.
 * Supports configurable gate modes: Off, Warnings Only, and Block Critical.
 *
 * @package SeoCityPages\Quality
 */

namespace SeoCityPages\Quality;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Quality_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quality_Gate_Manager Class.
 */
class Quality_Gate_Manager {

	/**
	 * Gate Mode Option Key.
	 */
	public const OPTION_GATE_MODE = 'seo_city_pages_quality_gate_mode';

	/**
	 * Get current quality gate mode.
	 *
	 * @return string 'off' | 'warnings_only' | 'block_critical'
	 */
	public static function get_gate_mode(): string {
		return (string) get_option( self::OPTION_GATE_MODE, Quality_Repository::GATE_MODE_WARNINGS_ONLY );
	}

	/**
	 * Set quality gate mode.
	 *
	 * @param string $mode Gate mode.
	 * @return bool
	 */
	public static function set_gate_mode( string $mode ): bool {
		$allowed = [
			Quality_Repository::GATE_MODE_OFF,
			Quality_Repository::GATE_MODE_WARNINGS_ONLY,
			Quality_Repository::GATE_MODE_BLOCK_CRITICAL,
		];

		if ( ! in_array( $mode, $allowed, true ) ) {
			return false;
		}

		return update_option( self::OPTION_GATE_MODE, $mode );
	}

	/**
	 * Evaluate if a page is allowed to be published under the current gate policy.
	 *
	 * @param int        $page_id City Page ID.
	 * @param array|null $page_data Optional page record.
	 * @return array [ 'allowed' => bool, 'reason' => string, 'evaluation' => array|null ]
	 */
	public static function can_publish( int $page_id, ?array $page_data = null ): array {
		$mode = self::get_gate_mode();

		if ( Quality_Repository::GATE_MODE_OFF === $mode ) {
			return [
				'allowed'    => true,
				'reason'     => 'Quality gate is turned off.',
				'evaluation' => null,
			];
		}

		$eval = Quality_Repository::get_by_page_id( $page_id );

		// If never evaluated or stale, run an evaluation now.
		if ( ! $eval || ! empty( $eval['is_stale'] ) ) {
			$eval = Quality_Engine::evaluate_page( $page_id, null, $page_data );
		}

		if ( Quality_Repository::GATE_MODE_WARNINGS_ONLY === $mode ) {
			return [
				'allowed'    => true,
				'reason'     => 'Quality gate in warnings-only mode.',
				'evaluation' => $eval,
			];
		}

		// Mode: block_critical.
		if ( Quality_Repository::STATUS_FAIL === $eval['quality_status'] ) {
			$reasons = [];
			if ( ! empty( $eval['issues_json'] ) ) {
				$issues = is_array( $eval['issues_json'] ) ? $eval['issues_json'] : json_decode( $eval['issues_json'], true );
				foreach ( $issues as $iss ) {
					if ( isset( $iss['severity'] ) && 'critical' === $iss['severity'] ) {
						$reasons[] = $iss['message'];
					}
				}
			}

			$reason_str = 'Quality gate blocked publication: ' . ( ! empty( $reasons ) ? implode( '; ', $reasons ) : 'Page failed internal quality gate (critical violations).' );

			return [
				'allowed'    => false,
				'reason'     => $reason_str,
				'evaluation' => $eval,
			];
		}

		return [
			'allowed'    => true,
			'reason'     => 'Passed quality gate.',
			'evaluation' => $eval,
		];
	}

	/**
	 * Invalidate quality evaluation when content is edited or regenerated.
	 *
	 * @param int $page_id Page ID.
	 * @return void
	 */
	public static function invalidate( int $page_id ): void {
		Quality_Repository::mark_stale_by_page_id( $page_id );
	}
}
