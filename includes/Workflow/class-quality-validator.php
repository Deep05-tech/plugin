<?php
/**
 * Quality & Relational Integrity Validator for Content Review & Publishing.
 *
 * @package SeoCityPages\Workflow
 */

namespace SeoCityPages\Workflow;

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\AI\Schema\Content_Validator;
use SeoCityPages\AI\AI_Context;
use SeoCityPages\Logging\Audit_Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quality_Validator Class.
 */
class Quality_Validator {

	/**
	 * Validate relational integrity of a City Page and its source taxonomies.
	 *
	 * @param int $page_id City Page ID.
	 * @return array Validation result ['is_valid' => bool, 'errors' => array, 'warnings' => array, 'page' => object|null]
	 */
	public static function validate_relational_integrity( int $page_id ): array {
		global $wpdb;

		$errors   = [];
		$warnings = [];

		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			$errors[] = sprintf( 'City Page #%d does not exist.', $page_id );
			return [
				'is_valid' => false,
				'errors'   => $errors,
				'warnings' => $warnings,
				'page'     => null,
			];
		}

		// Verify Country entity.
		if ( empty( $page->country_id ) || empty( $page->country_name ) ) {
			$errors[] = 'Source Country record is missing or deleted.';
		} elseif ( 'active' !== ( $page->country_status ?? 'active' ) ) {
			$errors[] = sprintf( 'Source Country "%s" is inactive or disabled.', $page->country_name );
		}

		// Verify State entity.
		if ( empty( $page->state_id ) || empty( $page->state_name ) ) {
			$errors[] = 'Source State record is missing or deleted.';
		} elseif ( 'active' !== ( $page->state_status ?? 'active' ) ) {
			$errors[] = sprintf( 'Source State "%s" is inactive or disabled.', $page->state_name );
		}

		// Verify City entity.
		if ( empty( $page->city_id ) || empty( $page->city_name ) ) {
			$errors[] = 'Source City record is missing or deleted.';
		} elseif ( 'active' !== ( $page->city_status ?? 'active' ) ) {
			$errors[] = sprintf( 'Source City "%s" is inactive or disabled.', $page->city_name );
		}

		// Verify Service entity.
		if ( empty( $page->service_id ) || empty( $page->service_name ) ) {
			$errors[] = 'Source Service record is missing or deleted.';
		} elseif ( 'active' !== ( $page->service_status ?? 'active' ) ) {
			$errors[] = sprintf( 'Source Service "%s" is inactive or disabled.', $page->service_name );
		}

		// Check URL path integrity.
		if ( empty( $page->url_path ) || '/' !== substr( $page->url_path, 0, 1 ) ) {
			$errors[] = 'Invalid or empty URL path structure.';
		}

		$is_valid = empty( $errors );

		// Update is_source_valid cache on city page table.
		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
		$wpdb->update(
			$pages_table,
			[
				'is_source_valid' => $is_valid ? 1 : 0,
				'updated_at'      => current_time( 'mysql' ),
			],
			[ 'id' => $page_id ]
		);

		if ( ! $is_valid ) {
			Audit_Logger::log(
				Audit_Logger::EVENT_VALIDATION_FAILURE,
				'city_page',
				$page_id,
				[
					'stage'    => 'relational_integrity',
					'errors'   => $errors,
					'warnings' => $warnings,
				]
			);
		}

		return [
			'is_valid' => $is_valid,
			'errors'   => $errors,
			'warnings' => $warnings,
			'page'     => $page,
		];
	}

	/**
	 * Validate content payload quality, anti-hallucination, and placeholder safety.
	 *
	 * @param array|string $content Content array or JSON string.
	 * @param object       $page    City Page object with joined location/service data.
	 * @return array ['is_valid' => bool, 'score' => int, 'errors' => array, 'warnings' => array]
	 */
	public static function validate_content_quality( $content, object $page ): array {
		$content_array = is_string( $content ) ? json_decode( $content, true ) : $content;
		if ( ! is_array( $content_array ) ) {
			return [
				'is_valid' => false,
				'score'    => 0,
				'errors'   => [ 'Invalid content JSON payload format.' ],
				'warnings' => [],
			];
		}

		$context = ( $page instanceof AI_Context ) ? $page : ( is_numeric( $page->id ?? null ) ? AI_Context::from_page_id( (int) $page->id ) : null );
		if ( ! $context && is_object( $page ) ) {
			$context = new AI_Context(
				(int) ( $page->id ?? 0 ),
				$page->country_name ?? '',
				$page->state_name ?? '',
				$page->city_name ?? '',
				$page->service_name ?? ''
			);
		}

		$schema_validation = $context ? Content_Validator::validate( $content_array, $context ) : [ 'valid' => true, 'errors' => [] ];

		$errors   = $schema_validation['errors'] ?? [];
		$warnings = [];

		// Additional deep quality heuristics:
		$score = 100;

		// Check Hero section length.
		$hero_desc = $content_array['hero']['description'] ?? '';
		if ( strlen( $hero_desc ) < 50 ) {
			$warnings[] = 'Hero description is very brief (< 50 chars).';
			$score     -= 10;
		}

		// Check Benefits count.
		$benefits = $content_array['benefits'] ?? [];
		if ( count( $benefits ) < 3 ) {
			$warnings[] = sprintf( 'Only %d benefits generated (recommended minimum: 3).', count( $benefits ) );
			$score     -= 10;
		}

		// Check Process steps count.
		$process = $content_array['process'] ?? [];
		if ( count( $process ) < 3 ) {
			$warnings[] = sprintf( 'Only %d process steps generated (recommended: 3-5).', count( $process ) );
			$score     -= 10;
		}

		// Check FAQs count.
		$faqs = $content_array['faqs'] ?? [];
		if ( count( $faqs ) < 3 ) {
			$warnings[] = sprintf( 'Only %d FAQs generated (recommended minimum: 3).', count( $faqs ) );
			$score     -= 10;
		}

		// Check SEO meta description length (optimal 120-160 chars).
		$meta_desc = $content_array['seo_metadata']['meta_description'] ?? ( $content_array['seo']['meta_description'] ?? '' );
		$meta_len  = strlen( $meta_desc );
		if ( $meta_len > 0 && ( $meta_len < 100 || $meta_len > 175 ) ) {
			$warnings[] = sprintf( 'SEO Meta Description length (%d chars) is outside optimal range (120–160 chars).', $meta_len );
			$score     -= 5;
		}

		// Check Keyword Density (Target 1.5% – 3.0%, Strict Maximum 3.0%).
		$target_keywords = [];
		if ( ! empty( $content_array['seo_metadata']['target_keywords'] ) && is_array( $content_array['seo_metadata']['target_keywords'] ) ) {
			$target_keywords = $content_array['seo_metadata']['target_keywords'];
		} elseif ( ! empty( $content_array['target_keywords'] ) && is_array( $content_array['target_keywords'] ) ) {
			$target_keywords = $content_array['target_keywords'];
		} elseif ( ! empty( $page->service_name ) ) {
			$target_keywords[] = $page->service_name;
		}

		if ( ! empty( $target_keywords ) && class_exists( \SeoCityPages\SEO\SEO_Validator::class ) ) {
			$density_report = \SeoCityPages\SEO\SEO_Validator::calculate_keyword_density( $content_array, $target_keywords );
			if ( ! empty( $density_report['errors'] ) ) {
				foreach ( $density_report['errors'] as $err ) {
					$errors[] = $err;
					$score   -= 25;
				}
			}
			if ( ! empty( $density_report['warnings'] ) ) {
				foreach ( $density_report['warnings'] as $warn ) {
					$warnings[] = $warn;
					$score     -= 5;
				}
			}
		}

		if ( ! empty( $errors ) ) {
			$score = 0;
		}

		return [
			'is_valid' => empty( $errors ),
			'score'    => max( 0, $score ),
			'errors'   => $errors,
			'warnings' => $warnings,
		];
	}
}
