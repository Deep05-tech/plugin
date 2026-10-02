<?php
/**
 * AI Quality Review Advisor.
 *
 * Provides optional, strictly manual and on-demand AI-assisted editorial reviews
 * for individual city pages.
 *
 * ADVISORY ONLY: This tool never publishes content automatically or bypasses publication gates.
 *
 * @package SeoCityPages\Quality
 */

namespace SeoCityPages\Quality;

use SeoCityPages\AI\Provider\OpenAI_Provider;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI_Review_Advisor Class.
 */
class AI_Review_Advisor {

	/**
	 * Run an on-demand editorial AI critique for a specific city page.
	 *
	 * @param int $page_id City Page ID.
	 * @return array [ 'success' => bool, 'feedback' => array|string, 'error' => string ]
	 */
	public static function review_page( int $page_id ): array {
		if ( ! Capabilities::user_can( Capabilities::REVIEW_CONTENT ) ) {
			return [
				'success'  => false,
				'feedback' => null,
				'error'    => 'Permission denied. You do not have permission to run AI quality reviews.',
			];
		}

		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return [
				'success'  => false,
				'feedback' => null,
				'error'    => 'Page not found.',
			];
		}

		$page = is_object( $page ) ? (array) $page : $page;

		$version_id = (int) ( $page['active_version_id'] ?? $page['published_version_id'] ?? 0 );
		$version    = $version_id > 0 ? Content_Version_Repository::get( $version_id ) : Content_Version_Repository::get_latest_version( $page_id );

		if ( ! $version || empty( $version['content_json'] ) ) {
			return [
				'success'  => false,
				'feedback' => null,
				'error'    => 'No content version found for this page.',
			];
		}

		$content_array = json_decode( $version['content_json'], true );
		$raw_text      = Similarity_Engine::extract_text( $content_array );

		$system_prompt = "You are an expert editorial reviewer specializing in programmatic SEO quality and local content authenticity. "
			. "Analyze the provided local landing page text. "
			. "Output a JSON object with keys: "
			. "'local_authenticity_rating' (High/Moderate/Low), "
			. "'tone_assessment' (e.g. Professional, Informative, Generic), "
			. "'strengths' (array of strings), "
			. "'critique' (array of specific suggestions to make the page more locally distinctive), "
			. "'suggested_improvements' (array of 2-3 concrete bullet points). "
			. "Do NOT invent false facts or give generic boilerplate. Be concise.";

		$user_prompt = sprintf(
			"Page Title: %s\nCity: %s\nState: %s\nService: %s\n\nPage Content:\n%s",
			$page['title'] ?? '',
			$page['city_name'] ?? '',
			$page['state_name'] ?? '',
			$page['service_name'] ?? '',
			mb_substr( $raw_text, 0, 3000 )
		);

		try {
			$provider = new OpenAI_Provider();
			$result   = $provider->generate( $system_prompt, $user_prompt, [ 'temperature' => 0.3 ] );

			if ( ! $result->is_successful() ) {
				return [
					'success'  => false,
					'feedback' => null,
					'error'    => $result->get_error_message() ?: 'AI Provider returned an error.',
				];
			}

			$data = $result->get_content();

			return [
				'success'  => true,
				'feedback' => $data,
				'error'    => '',
			];
		} catch ( \Throwable $e ) {
			return [
				'success'  => false,
				'feedback' => null,
				'error'    => 'AI Quality Advisor Exception: ' . $e->getMessage(),
			];
		}
	}
}
