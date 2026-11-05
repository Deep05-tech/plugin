<?php
/**
 * Dynamic AI Content Context Provider for Elementor & Frontend Templates.
 *
 * Exposes field-level access to approved/draft AI generated content
 * with semantic HTML formatters for repeatable components.
 *
 * @package SeoCityPages\Elementor
 */

namespace SeoCityPages\Elementor;

use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\URL\Request_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content_Context Class.
 */
class Content_Context {

	/**
	 * Cached parsed content array for active request.
	 *
	 * @var array|null
	 */
	private static ?array $cached_content = null;

	/**
	 * Cached version record.
	 *
	 * @var object|null
	 */
	private static ?object $cached_version = null;

	/**
	 * Retrieve full URL for a plugin asset image.
	 *
	 * @param string $filename Asset filename.
	 * @return string
	 */
	public static function get_image_url( string $filename ): string {
		if ( defined( 'SEO_CITY_PAGES_PLUGIN_URL' ) ) {
			return rtrim( SEO_CITY_PAGES_PLUGIN_URL, '/' ) . '/assets/images/' . $filename;
		}
		if ( function_exists( 'plugins_url' ) ) {
			return plugins_url( 'assets/images/' . $filename, dirname( __DIR__ ) . '/seo-city-pages.php' );
		}
		return '/wp-content/plugins/seo-city-pages/assets/images/' . $filename;
	}

	/**
	 * Retrieve active content payload for the current SEO City Page.
	 *
	 * @return array|null
	 */
	public static function get_active_content(): ?array {
		if ( null !== self::$cached_content ) {
			return self::$cached_content;
		}

		$current_page = Request_Resolver::get_current_page();
		if ( ! $current_page || empty( $current_page->id ) ) {
			if ( class_exists( __NAMESPACE__ . '\\Page_Context' ) && Page_Context::has_context() ) {
				$current_page = Page_Context::get_page();
			}
		}

		if ( ! $current_page || empty( $current_page->id ) ) {
			return null;
		}

		$page_id = (int) $current_page->id;

		// In admin preview mode, use specified preview version or latest version; otherwise use published or active approved version.
		$is_preview = Request_Resolver::is_preview() || ( class_exists( __NAMESPACE__ . '\\Page_Context' ) && Page_Context::is_preview() );
		if ( $is_preview ) {
			$preview_vid = isset( $_GET['preview_version_id'] ) ? absint( wp_unslash( $_GET['preview_version_id'] ) ) : 0;
			if ( $preview_vid > 0 ) {
				$version = Content_Version_Repository::get( $preview_vid );
			} else {
				$version = Content_Version_Repository::get_latest_version( $page_id );
			}
		} else {
			$version = City_Page_Repository::get_published_version( $page_id );
			if ( ! $version ) {
				$version = Content_Version_Repository::get_current_approved_version( $page_id );
			}
		}

		if ( ! $version || empty( $version->content_json ) ) {
			return null;
		}

		self::$cached_version = $version;
		$decoded = json_decode( (string) $version->content_json, true );
		if ( is_array( $decoded ) ) {
			$service = class_exists( Page_Context::class ) ? Page_Context::get_service() : '';
			self::$cached_content = self::normalize_content_array( $decoded, $service );
		} else {
			self::$cached_content = null;
		}

		return self::$cached_content;
	}

	/**
	 * Reset cached content (useful for testing and request boundaries).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$cached_content = null;
		self::$cached_version = null;
	}

	/**
	 * Manually set content for testing or custom context overrides.
	 *
	 * @param array $content Content array.
	 * @return void
	 */
	public static function set_content( array $content ): void {
		$service = class_exists( Page_Context::class ) ? Page_Context::get_service() : '';
		self::$cached_content = self::normalize_content_array( $content, $service );
	}

	/**
	 * Recursively normalize entity fumbles across all strings in content array.
	 *
	 * @param array  $data Content array.
	 * @param string $service Service name.
	 * @return array Normalized array.
	 */
	public static function normalize_content_array( array $data, string $service = '' ): array {
		$clean = [];
		foreach ( $data as $k => $v ) {
			if ( is_array( $v ) ) {
				$clean[ $k ] = self::normalize_content_array( $v, $service );
			} elseif ( is_string( $v ) ) {
				$clean[ $k ] = self::normalize_target_entity_fumbles( $v, $service );
			} else {
				$clean[ $k ] = $v;
			}
		}
		return $clean;
	}

	/**
	 * Normalize improper entity-versus-service substitutions.
	 *
	 * Ensures that the target business entity (e.g. Dental Clinic, Law Firm)
	 * is not mistakenly replaced by procedural service terms (e.g. 'dental services')
	 * where the clinic, practice, or firm itself is the subject, while carefully
	 * preserving valid patient queries and procedural references (e.g. 'patients searching for dental services').
	 *
	 * @param string $text Content text or HTML.
	 * @param string $service Target service/niche name.
	 * @return string Normalized text.
	 */
	public static function normalize_target_entity_fumbles( string $text, string $service = '' ): string {
		if ( empty( $text ) ) {
			return $text;
		}

		if ( empty( $service ) && class_exists( Page_Context::class ) ) {
			$service = Page_Context::get_service();
		}

		// Dental Clinic / Practice normalization.
		if ( empty( $service ) || false !== stripos( $service, 'dental' ) || false !== stripos( $service, 'dentist' ) ) {
			$rules = [
				'/(\b(?:landscape|ecosystem|market|competition)\s+of\s+)dental\s+services\b/i' => '${1}dental clinics',
				'/(\b(?:agency|partner|consultant|consulting|solutions?|campaigns?|strategies|marketing|SEO|funnels?|specialists?|experts?)\s+for\s+)dental\s+services\b/i' => '${1}dental clinics',
				'/(\b(?:tailored|engineered|built|designed|crafted|customized)\s+for\s+)dental\s+services\b/i' => '${1}dental clinics',
				'/(\b(?:helping|empowering|enabling|allowing|scale|grow|elevate|serving|connect|connecting)\s+)dental\s+services(?=\s+(?:to|reach|grow|rank|scale|dominate|capture|attract|with|across|in)\b)/i' => '${1}dental clinics',
				'/(\bhelp\s+)dental\s+services(?=\s+(?:to|reach|grow|rank|scale|dominate|capture|attract|acquire)\b)/i' => '${1}dental clinics',
				'/(\b(?:grow|scale|elevate|boost)\s+your\s+)dental\s+services\b/i' => '${1}dental clinic',
				'/(^|[\.\?!;]\s+|\n\s*)For\s+dental\s+services\s+(in|across|throughout)\b/i' => '${1}For dental clinics ${2}',
				'/\bdental\s+services\s+(competing|looking|striving|seeking|operating)\s+in\b/i' => 'dental clinics ${1} in',
				'/(\b(?:local|independent|regional|top)\s+)dental\s+services\s+(?=in\s+[A-Z][a-zA-Z\s]+?\s+(?:face|compete|struggle|require|need|rely|benefit|choose))/i' => '${1}dental clinics ',
				'/\bdental\s+services\s+in\s+([A-Z][a-zA-Z\s]+?)\s+(face|compete|struggle|require|need|rely|benefit|choose)\b/i' => 'dental clinics in ${1} ${2}',
				'/(\branking\s+for\s+)dental\s+services(?=\s+is\s+more\s+than\b)/i' => 'ranking a dental clinic',
				'/(\b(?:partner\s+with|working\s+with|trusted\s+by)\s+)dental\s+services\b/i' => '${1}dental clinics',
				'/(\bpremier\s+(?:choice|provider)\s+for\s+)dental\s+services\b/i' => '${1}dental clinics',
			];
			foreach ( $rules as $pattern => $replacement ) {
				$text = preg_replace( $pattern, $replacement, $text );
			}
		}

		// Law Firm / Legal normalization.
		if ( ! empty( $service ) && ( false !== stripos( $service, 'law' ) || false !== stripos( $service, 'legal' ) || false !== stripos( $service, 'attorney' ) ) ) {
			$legal_rules = [
				'/(\b(?:landscape|ecosystem|market|competition)\s+of\s+)legal\s+services\b/i' => '${1}law firms',
				'/(\b(?:agency|partner|consultant|consulting|solutions?|campaigns?|strategies|marketing|SEO|funnels?|specialists?|experts?)\s+for\s+)legal\s+services\b/i' => '${1}law firms',
				'/(\b(?:tailored|engineered|built|designed|crafted|customized)\s+for\s+)legal\s+services\b/i' => '${1}law firms',
				'/(\b(?:grow|scale|elevate|boost)\s+your\s+)legal\s+services\b/i' => '${1}law firm',
				'/(^|[\.\?!;]\s+|\n\s*)For\s+legal\s+services\s+(in|across|throughout)\b/i' => '${1}For law firms ${2}',
				'/\blegal\s+services\s+(competing|looking|striving|seeking|operating)\s+in\b/i' => 'law firms ${1} in',
				'/(\b(?:partner\s+with|working\s+with|trusted\s+by)\s+)legal\s+services\b/i' => '${1}law firms',
			];
			foreach ( $legal_rules as $pattern => $replacement ) {
				$text = preg_replace( $pattern, $replacement, $text );
			}
		}

		// Home Services & Trade Contractors normalization.
		if ( ! empty( $service ) && ( false !== stripos( $service, 'home service' ) || false !== stripos( $service, 'home cleaning' ) || false !== stripos( $service, 'contractor' ) ) ) {
			$contractor_rules = [
				'/(\b(?:landscape|ecosystem|market|competition)\s+of\s+)home\s+services\b/i' => '${1}home service contractors',
				'/(\b(?:agency|partner|consultant|consulting|solutions?|campaigns?|strategies|marketing|SEO|funnels?|specialists?|experts?)\s+for\s+)home\s+services\b/i' => '${1}home service contractors',
				'/(\b(?:tailored|engineered|built|designed|crafted|customized)\s+for\s+)home\s+services\b/i' => '${1}home service contractors',
				'/(\b(?:grow|scale|elevate|boost)\s+your\s+)home\s+services\b/i' => '${1}home service company',
				'/(^|[\.\?!;]\s+|\n\s*)For\s+home\s+services\s+(in|across|throughout)\b/i' => '${1}For home service contractors ${2}',
				'/\bhome\s+services\s+(competing|looking|striving|seeking|operating)\s+in\b/i' => 'home service contractors ${1} in',
				'/(\b(?:partner\s+with|working\s+with|trusted\s+by)\s+)home\s+services\b/i' => '${1}home service contractors',
			];
			foreach ( $contractor_rules as $pattern => $replacement ) {
				$text = preg_replace( $pattern, $replacement, $text );
			}
		}

		return $text;
	}

	public static function get_hero_title(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['hero']['title'] ) ) {
			return esc_html( $content['hero']['title'] );
		}
		return esc_html( Page_Context::get_title() );
	}

	public static function get_hero_description(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['hero']['description'] ) ) {
			return esc_html( $content['hero']['description'] );
		}
		$city    = Page_Context::get_city();
		$service = Page_Context::get_service();
		return esc_html( sprintf( 'Dominate Google search, Google Ads, and Meta Ads for %s in %s. Vispan Solutions delivers full-suite digital marketing, high-converting Local SEO, and PPC lead generation funnels engineered for measurable revenue growth.', $service, $city ) );
	}

	/**
	 * Alias for get_hero_description.
	 *
	 * @return string
	 */
	public static function get_hero_subtitle(): string {
		return self::get_hero_description();
	}

	public static function get_hero_cta(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['hero']['cta_text'] ) ) {
			return esc_html( $content['hero']['cta_text'] );
		}
		return esc_html__( 'Get Free Proposal →', 'seo-city-pages' );
	}

	public static function get_intro_heading(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['introduction']['heading'] ) ) {
			return esc_html( $content['introduction']['heading'] );
		}
		return esc_html( sprintf( 'Dominating Google Search & High-ROI Ads in %s', Page_Context::get_city() ) );
	}

	/**
	 * Safe sanitization and Markdown-to-HTML parser for editorial body content.
	 *
	 * Converts markdown bold (**text**), italics (*text*), and lists (- item or * item)
	 * into clean, beautifully styled semantic HTML tags and runs wp_kses_post.
	 *
	 * @param string $text Raw text or markdown.
	 * @return string Clean, styled HTML.
	 */
	public static function safe_sanitize_body( string $text ): string {
		if ( empty( trim( $text ) ) ) {
			return '';
		}

		// Normalize target business entity fumbles before formatting
		$text = self::normalize_target_entity_fumbles( $text );

		// Normalize line endings
		$text = str_replace( [ "\r\n", "\r" ], "\n", $text );

		// Normalize inline bullets if generated on one line: e.g. "include: - **SEO:**" -> "include:\n- **SEO:**"
		$text = preg_replace( '/(?:\s+|^)[-*•]\s+\*\*/', "\n- **", $text );

		// Convert markdown bold: **text** or __text__
		$text = preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text );
		$text = preg_replace( '/__(.+?)__/s', '<strong>$1</strong>', $text );

		// Convert markdown italics: *text* or _text_
		$text = preg_replace( '/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text );

		// Check if the content has newlines or bullet list items
		$has_newlines = ( false !== strpos( $text, "\n" ) );
		$has_bullets  = (bool) preg_match( '/(?:^|\n)[-*•]\s+/m', $text );

		if ( ! $has_newlines && ! $has_bullets ) {
			if ( function_exists( 'wp_kses_post' ) ) {
				return \wp_kses_post( $text );
			}
			return $text;
		}

		$lines      = explode( "\n", $text );
		$output     = [];
		$list_items = [];
		$in_list    = false;

		foreach ( $lines as $line ) {
			$trimmed = trim( $line );

			// Check for bullet list item: - or * or • followed by space
			if ( preg_match( '/^[-*•]\s+(.+)$/', $trimmed, $m ) ) {
				$in_list      = true;
				$list_items[] = $m[1];
			} elseif ( preg_match( '/^\d+\.\s+(.+)$/', $trimmed, $m ) ) {
				$in_list      = true;
				$list_items[] = $m[1];
			} else {
				if ( $in_list && ! empty( $list_items ) ) {
					$output[]   = self::render_styled_bullet_list( $list_items );
					$list_items = [];
					$in_list    = false;
				}
				if ( '' !== $trimmed ) {
					$output[] = '<p style="margin: 0 0 14px; line-height: 1.65;">' . $trimmed . '</p>';
				}
			}
		}

		if ( $in_list && ! empty( $list_items ) ) {
			$output[] = self::render_styled_bullet_list( $list_items );
		}

		$html = implode( "\n", $output );

		if ( function_exists( 'wp_kses_post' ) ) {
			return \wp_kses_post( $html );
		}

		return $html;
	}

	/**
	 * Render styled checkmark bullet list HTML.
	 *
	 * @param array $items Array of list item strings.
	 * @return string HTML output.
	 */
	public static function render_styled_bullet_list( array $items ): string {
		$html = '<ul style="list-style: none; padding: 0; margin: 16px auto; max-width: 720px; text-align: left; display: flex; flex-direction: column; gap: 10px;">';
		foreach ( $items as $item ) {
			$html .= sprintf(
				'<li style="display: flex; align-items: flex-start; gap: 10px; font-size: 14.5px; line-height: 1.55; color: #4A5568;">' .
				'<span style="color: #02426A; font-weight: 900; font-size: 15px; flex-shrink: 0; margin-top: 1px;">✓</span>' .
				'<div>%s</div>' .
				'</li>',
				$item
			);
		}
		$html .= '</ul>';
		return $html;
	}

	public static function get_intro_body(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['introduction']['body'] ) ) {
			return self::safe_sanitize_body( $content['introduction']['body'] );
		}
		$city  = Page_Context::get_city();
		$state = Page_Context::get_state();
		return esc_html( sprintf( 'Generic marketing fails in competitive markets. Vispan Solutions combines Google Ads, Meta Ads (Facebook & Instagram), Local SEO, and Google Map Pack 3-Pack optimization to capture commercial-intent clients across %s, %s.', $city, $state ) );
	}

	public static function get_service_overview_heading(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['service_overview']['heading'] ) ) {
			return esc_html( $content['service_overview']['heading'] );
		}
		return esc_html( sprintf( 'Full-Funnel Digital Marketing, Google Ads & SEO for %s', Page_Context::get_service() ) );
	}

	public static function get_service_overview_body(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['service_overview']['body'] ) ) {
			return self::safe_sanitize_body( $content['service_overview']['body'] );
		}
		return esc_html( sprintf( 'We engineer complete client acquisition engines for %s in %s — integrating Google Ads PPC, Meta Ads, Technical SEO, Google Map Pack dominance, Web Development, and Online Reputation Management.', Page_Context::get_service(), Page_Context::get_city() ) );
	}

	public static function get_local_relevance_heading(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['local_relevance']['heading'] ) ) {
			return esc_html( $content['local_relevance']['heading'] );
		}
		return esc_html( sprintf( 'Why %s Businesses in %s Choose Vispan Solutions', Page_Context::get_service(), Page_Context::get_city() ) );
	}

	public static function get_local_relevance_body(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['local_relevance']['body'] ) ) {
			return self::safe_sanitize_body( $content['local_relevance']['body'] );
		}
		$city  = Page_Context::get_city();
		$state = Page_Context::get_state();
		return esc_html( sprintf( 'Our data-driven digital marketing infrastructure drives high-converting organic rankings, verified Google Ads ROI, and hyper-targeted Meta advertising across %s, %s.', $city, $state ) );
	}

	public static function get_final_cta_heading(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['final_cta']['heading'] ) ) {
			return esc_html( $content['final_cta']['heading'] );
		}
		return esc_html( sprintf( 'Ready to Scale Your Business in %s?', Page_Context::get_city() ) );
	}

	public static function get_final_cta_description(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['final_cta']['description'] ) ) {
			return esc_html( $content['final_cta']['description'] );
		}
		$city  = Page_Context::get_city();
		$state = Page_Context::get_state();
		return esc_html( sprintf( 'Get a custom local growth roadmap designed to outrank your competition in %s, %s and generate consistent inbound leads.', $city, $state ) );
	}

	public static function get_final_cta_button(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['final_cta']['button_text'] ) ) {
			return esc_html( $content['final_cta']['button_text'] );
		}
		return esc_html__( 'Claim Your Free Strategy Consultation →', 'seo-city-pages' );
	}

	/**
	 * Render semantic HTML card grid for repeatable Benefits[].
	 *
	 * @return string HTML output.
	 */
	public static function render_benefits_html(): string {
		$content  = self::get_active_content();
		$benefits = $content['benefits'] ?? [];

		if ( empty( $benefits ) || ! is_array( $benefits ) ) {
			$city    = Page_Context::get_city();
			$service = Page_Context::get_service();
			$benefits = [
				[ 'title' => 'Google Map Pack 3-Pack Dominance', 'description' => sprintf( 'Dominate top 3 Google Maps positions across %s target zip codes with rigorous Google Business Profile optimization, review velocity systems, and proximity signal alignment.', $city ) ],
				[ 'title' => 'High-CPC Commercial Intent Capture', 'description' => sprintf( 'Target bottom-of-funnel transactional queries for %s, capturing corporate decision makers and high-intent buyers who are ready to make purchasing decisions.', $service ) ],
				[ 'title' => 'Multi-Tiered Geo-Entity Schema', 'description' => 'Engineered nested JSON-LD schema with precise geographic coordinates, service catalogs, and corporate entity graphs for definitive search engine understanding.' ],
				[ 'title' => 'Granular Submarket Node Architecture', 'description' => sprintf( 'Treat %s as an interconnected network of submarkets, engineering dedicated landing nodes that capture regional demand outside saturated downtown cores.', $city ) ],
				[ 'title' => 'Transparent Lead & Revenue Attribution', 'description' => 'Direct call tracking, form submission attribution, and monthly video ROI walkthroughs so you know exactly which keywords generate closed revenue.' ],
				[ 'title' => 'Direct Senior Strategist Access', 'description' => 'Collaborate directly with senior digital marketing strategists. Zero junior account managers, zero automated generic ticket responses.' ],
			];
		}

		$count = count( $benefits );
		$grid_cols = ( 4 === $count || 2 === $count ) 
			? 'grid-template-columns: repeat(auto-fit, minmax(min(100%, 420px), 1fr));'
			: 'grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));';

		$html = '<div style="display: grid; ' . $grid_cols . ' gap: 20px; font-family: \'DM Sans\', sans-serif;">';
		$idx = 0;
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $benefits as $b ) {
			$title = esc_html( $b['title'] ?? '' );
			$desc  = esc_html( $b['description'] ?? '' );
			$delay = $delays[ $idx % count( $delays ) ];
			$reveal_class = ( 0 === $idx % 2 ) ? 'vispan-reveal-left' : 'vispan-reveal-right';
			$idx++;

			$html .= sprintf(
				'<div class="%s %s vispan-hover-card" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 16px; padding: 24px 22px; box-sizing: border-box; display: flex; gap: 16px; align-items: flex-start; box-shadow: 0 4px 18px -6px rgba(2,66,106,0.06);">
					<div style="flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px; background: #02426A; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 900; box-shadow: 0 4px 12px -3px rgba(2,66,106,0.3);">✓</div>
					<div style="flex: 1; min-width: 0;">
						<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 17px; font-weight: 800; color: #121212; margin: 0 0 6px; line-height: 1.35;">%s</h3>
						<p style="font-size: 14px; line-height: 1.6; color: #5D5756; margin: 0;">%s</p>
					</div>
				</div>',
				esc_attr( $reveal_class ),
				esc_attr( $delay ),
				$title,
				$desc
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render semantic HTML card list for repeatable Process[].
	 *
	 * @return string HTML output.
	 */
	public static function render_process_html(): string {
		$content = self::get_active_content();
		$process = $content['process'] ?? [];

		$city    = Page_Context::get_city();
		$state   = Page_Context::get_state();
		$service = Page_Context::get_service();

		if ( empty( $process ) || ! is_array( $process ) ) {
			$process = [
				[ 'step_number' => 1, 'title' => 'Technical Foundation & Geo-Entity Graph Audit', 'description' => sprintf( 'Comprehensive crawl inspection, Core Web Vitals optimization, and nested JSON-LD schema deployment establishing baseline algorithmic trust in %s.', $city ) ],
				[ 'step_number' => 2, 'title' => 'Submarket Commercial Intent Architecture', 'description' => sprintf( 'Deploying dedicated landing nodes targeting high-CPC buyer queries for %s across regional business corridors.', $service ) ],
				[ 'step_number' => 3, 'title' => 'Google Map Pack & Citation Velocity Alignment', 'description' => sprintf( 'Rigorous NAP consistency audit, geo-grid proximity tracking, and automated customer review generation across %s.', $state ) ],
				[ 'step_number' => 4, 'title' => 'Conversion Optimization & Continuous ROI Attribution', 'description' => 'Dynamic phone call tracking, form capture heatmapping, and transparent monthly video ROI reviews demonstrating verified revenue growth.' ],
			];
		} elseif ( count( $process ) < 4 ) {
			// Ensure exactly 4 stages are rendered for complete 4-Stage Search Growth Engine.
			$default_steps = [
				1 => [ 'step_number' => 1, 'title' => 'Technical Baseline & Discovery Audit', 'description' => sprintf( 'Crawl audit, speed tuning, and localized entity schema setup in %s.', $city ) ],
				2 => [ 'step_number' => 2, 'title' => 'Commercial Intent & Demand Mapping', 'description' => sprintf( 'Deploying dedicated landing nodes for high-value %s queries.', $service ) ],
				3 => [ 'step_number' => 3, 'title' => 'Google Map Pack & Citation Alignment', 'description' => sprintf( 'NAP consistency verification and automated review velocity systems across %s.', $city ) ],
				4 => [ 'step_number' => 4, 'title' => 'Conversion Optimization & ROI Attribution', 'description' => 'Call tracking, lead event heatmapping, and transparent monthly ROI reporting.' ],
			];
			for ( $s = count( $process ) + 1; $s <= 4; $s++ ) {
				if ( isset( $default_steps[ $s ] ) ) {
					$process[] = $default_steps[ $s ];
				}
			}
		}

		$html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr)); gap: 18px; font-family: \'DM Sans\', sans-serif;">';
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $process as $idx => $step ) {
			$num   = (int) ( $step['step_number'] ?? ( $idx + 1 ) );
			$title = esc_html( $step['title'] ?? '' );
			$desc  = esc_html( $step['description'] ?? '' );
			$delay = $delays[ $idx % count( $delays ) ];
			$reveal_class = ( 0 === $idx % 2 ) ? 'vispan-reveal-left' : 'vispan-reveal-right';

			$html .= sprintf(
				'<div class="%s %s vispan-hover-card" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 16px; padding: 24px 20px; position: relative;">
					<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11.5px; font-weight: 900; color: #02426A; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 6px;">STAGE 0%d</div>
					<h4 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 16.5px; font-weight: 800; color: #121212; margin: 0 0 8px; line-height: 1.35;">%s</h4>
					<p style="font-size: 13.5px; line-height: 1.6; color: #5D5756; margin: 0;">%s</p>
				</div>',
				esc_attr( $reveal_class ),
				esc_attr( $delay ),
				$num,
				$title,
				$desc
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render semantic HTML accordion for repeatable FAQs[].
	 *
	 * @return string HTML output.
	 */
	public static function render_faqs_html(): string {
		$content = self::get_active_content();
		$faqs    = $content['faqs'] ?? [];

		if ( empty( $faqs ) || ! is_array( $faqs ) ) {
			$city    = Page_Context::get_city();
			$state   = Page_Context::get_state();
			$service = Page_Context::get_service();
			$faqs = [
				[ 'question' => sprintf( 'What digital marketing services does Vispan Solutions provide for %s in %s?', $service, $city ), 'answer' => sprintf( 'Vispan Solutions provides a complete in-house digital marketing engine for %s in %s, including high-ROI Google Ads campaigns, Meta Ads (Facebook & Instagram), Search Engine Optimization (SEO), Google Maps 3-Pack domination, high-converting web funnels, online reputation management, and commercial video/CGI production.', $service, $city ) ],
				[ 'question' => sprintf( 'Why do %s businesses in %s need professional digital marketing?', $service, $city ), 'answer' => sprintf( 'Modern prospective clients in %s search online via mobile devices and Google Maps when seeking professional %s providers. Relying on traditional word-of-mouth leaves your market share open to aggressive competitors and national directory aggregators. Targeted digital marketing ensures your practice captures high-intent local inquiries at the exact moment of decision.', $city, $service ) ],
				[ 'question' => 'Why should you contact and partner with Vispan Solutions for these services?', 'answer' => 'Empowering brands since 2012, Vispan Solutions has delivered 150+ successful projects with a 99% client success rate, a 4.8★ rating across 140+ verified reviews, and 12+ industry awards (including Best SEO and Digital Marketer). We provide strict territory exclusivity, 100% exclusive inbound lead generation, and dedicated in-house execution with transparent weekly ROI tracking.' ],
				[ 'question' => sprintf( 'Can an independent local business in %s outrank established national aggregators?', $city ), 'answer' => sprintf( 'Absolutely. Google’s localized ranking algorithms heavily reward proximity, topical completeness, and verified geographic schema over thin national directory pages. By targeting granular submarket commercial queries, local businesses in %s consistently capture top 3 Map Pack rankings.', $city ) ],
				[ 'question' => sprintf( 'How long does it take to see measurable search rank improvements in %s?', $city ), 'answer' => 'Most localized campaigns begin demonstrating keyword movement and Google Map Pack impression growth within 60 to 90 days. As citation velocity, entity authority, and submarket landing nodes mature, compounding inbound lead volume accelerates over 6 to 12 months.' ],
				[ 'question' => 'How do you measure and report ROI on our local search campaign?', 'answer' => 'We deploy dedicated dynamic call tracking, form submission event tagging, and Google Analytics 4 lead attribution. Every month, you receive a transparent video executive report detailing closed revenue pipeline, lead volume, and keyword growth.' ],
			];
		}

		$html = '<div style="display: flex; flex-direction: column; gap: 14px; font-family: \'DM Sans\', sans-serif;">';
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $faqs as $idx => $faq ) {
			$q = esc_html( $faq['question'] ?? '' );
			$a = esc_html( $faq['answer'] ?? '' );
			$delay = $delays[ $idx % count( $delays ) ];
			$reveal_class = ( 0 === $idx % 2 ) ? 'vispan-reveal-left' : 'vispan-reveal-right';

			$html .= sprintf(
				'<div class="%s %s vispan-hover-card" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 14px; padding: 22px 24px;">
					<h4 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 16.5px; font-weight: 800; color: #02426A; margin: 0 0 8px; line-height: 1.35;">%s</h4>
					<p style="font-size: 14.5px; line-height: 1.6; color: #5D5756; margin: 0;">%s</p>
				</div>',
				esc_attr( $reveal_class ),
				esc_attr( $delay ),
				$q,
				$a
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render Strategic Comparison Table (Standard Agency vs Vispan Architecture).
	 *
	 * @return string HTML output.
	 */
	public static function render_comparison_table_html(): string {
		$city    = Page_Context::get_city();
		$service = Page_Context::get_service();

		$rows = [
			[
				'feature'     => 'Submarket Targeting Depth',
				'traditional' => 'Single broad city tag; misses 85% of regional commercial buyer searches.',
				'vispan'      => sprintf( '6+ Granular landing nodes engineered for specific %s commercial corridors.', $city ),
			],
			[
				'feature'     => 'Schema & Geo-Entity Graph',
				'traditional' => 'Generic Organization markup copied across every site page.',
				'vispan'      => 'Multi-tiered JSON-LD schema with exact GeoCoordinates, Service hierarchies, and local entities.',
			],
			[
				'feature'     => 'Google Map Pack Strategy',
				'traditional' => 'Unmonitored bulk citation blasts leading to NAP conflicts and algorithmic suppression.',
				'vispan'      => sprintf( 'Active geo-grid proximity tracking, verified directory audits, and review velocity across %s.', $city ),
			],
			[
				'feature'     => 'Content Quality & Density',
				'traditional' => 'Thin 300-word template pages vulnerable to Google Helpful Content penalties.',
				'vispan'      => '2,500+ words of rich, data-dense editorial intent answering real local buyer inquiries.',
			],
			[
				'feature'     => 'Lead & Revenue Attribution',
				'traditional' => 'Vanity search impression metrics with zero tracking on phone calls or sales.',
				'vispan'      => 'Dynamic call tracking, recorded lead inquiries, and transparent executive ROI reviews.',
			],
			[
				'feature'     => 'Account Access & Terms',
				'traditional' => '12-month lock-in contracts managed by junior account coordinators.',
				'vispan'      => 'Flexible performance agreements with direct access to senior search strategists.',
			],
		];

		$rows_html = '';
		foreach ( $rows as $idx => $r ) {
			$bg = ( 0 === $idx % 2 ) ? '#FFFFFF' : '#F8FAFC';
			$rows_html .= sprintf(
				'<tr style="background: %s;">
					<td class="vispan-comp-td vispan-comp-factor-cell" style="width: 26%%; padding: 16px 18px; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 14px; font-weight: 800; color: #0F172A; vertical-align: middle; border-top: 1px solid #E2E8F0; border-right: 1px solid #E2E8F0; word-break: break-word; overflow-wrap: break-word; white-space: normal; box-sizing: border-box;">
						%s
					</td>
					<td class="vispan-comp-td vispan-comp-traditional-cell" style="width: 37%%; padding: 16px 18px; font-size: 13.5px; color: #64748B; line-height: 1.55; vertical-align: middle; border-top: 1px solid #E2E8F0; border-right: 1px solid #E2E8F0; word-break: break-word; overflow-wrap: break-word; white-space: normal; box-sizing: border-box;">
						<div style="display: flex; align-items: flex-start; gap: 8px;">
							<span style="color: #94A3B8; font-weight: 700; flex-shrink: 0; font-size: 13px; margin-top: 1px;">✕</span>
							<span>%s</span>
						</div>
					</td>
					<td class="vispan-comp-td vispan-comp-vispan-col vispan-comp-vispan-cell" style="width: 37%%; padding: 16px 18px; font-size: 13.5px; font-weight: 600; color: #003865; line-height: 1.55; vertical-align: middle; border-top: 1px solid #E2E8F0; background: #F2F8FC; word-break: break-word; overflow-wrap: break-word; white-space: normal; box-sizing: border-box;">
						<div style="display: flex; align-items: flex-start; gap: 8px;">
							<span style="background: #e6f7ec; color: #00a32a; font-size: 12px; font-weight: 900; width: 20px; height: 20px; border-radius: 50%%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px;">✓</span>
							<span>%s</span>
						</div>
					</td>
				</tr>',
				$bg,
				esc_html( $r['feature'] ),
				esc_html( $r['traditional'] ),
				esc_html( $r['vispan'] )
			);
		}

		return sprintf(
			'<div class="vispan-comparison-wrap" style="font-family: \'DM Sans\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; width: 100%%; max-width: 100%%; box-sizing: border-box; overflow: hidden; border: 1.5px solid #CCE0ED; border-radius: 20px; box-shadow: 0 12px 36px -12px rgba(2, 66, 106, 0.12); background: #FFFFFF;">
				<style>
					.vispan-comparison-table {
						width: 100%% !important;
						max-width: 100%% !important;
						table-layout: fixed !important;
						border-collapse: collapse !important;
						border-spacing: 0 !important;
						text-align: left !important;
						margin: 0 !important;
						border: none !important;
						box-sizing: border-box !important;
					}
					@media (max-width: 767px) {
						.vispan-comparison-table,
						.vispan-comparison-table thead,
						.vispan-comparison-table tbody,
						.vispan-comparison-table tr,
						.vispan-comparison-table td,
						.vispan-comparison-table th {
							display: block !important;
							width: 100%% !important;
							box-sizing: border-box !important;
						}
						.vispan-comparison-table thead {
							display: none !important;
						}
						.vispan-comparison-table tr {
							border-bottom: 2px solid #D6E4EE !important;
							padding: 16px 14px !important;
						}
						.vispan-comp-td {
							border: none !important;
							padding: 6px 0 !important;
						}
						.vispan-comp-factor-cell {
							font-size: 15.5px !important;
							font-weight: 800 !important;
							color: #003865 !important;
							margin-bottom: 6px !important;
						}
						.vispan-comp-traditional-cell::before {
							content: "Standard Agency: ";
							font-weight: 700;
							color: #64748B;
							display: block;
							font-size: 11.5px;
							text-transform: uppercase;
							margin-bottom: 3px;
						}
						.vispan-comp-vispan-cell {
							background: #F0F7FC !important;
							padding: 12px 14px !important;
							border-radius: 12px !important;
							margin-top: 8px !important;
							border-left: 3px solid #003865 !important;
						}
						.vispan-comp-vispan-cell::before {
							content: "Vispan Advantage: ";
							font-weight: 800;
							color: #003865;
							display: block;
							font-size: 11.5px;
							text-transform: uppercase;
							margin-bottom: 3px;
						}
					}
				</style>
				<table class="vispan-comparison-table" style="width: 100%% !important; max-width: 100%% !important; table-layout: fixed !important; border-collapse: collapse !important; margin: 0 !important; border: none !important;">
					<thead>
						<tr style="background: #003865; color: #FFFFFF;">
							<th style="width: 26%%; padding: 18px 18px; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #FFFFFF; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.12); box-sizing: border-box;">
								Strategic Factor
							</th>
							<th style="width: 37%%; padding: 18px 18px; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #CBD5E1; vertical-align: middle; border-right: 1px solid rgba(255,255,255,0.12); box-sizing: border-box;">
								Standard / National Agency
							</th>
							<th style="width: 37%%; padding: 18px 18px; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #FFFFFF; background: #002D52; vertical-align: middle; box-sizing: border-box;">
								<div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
									<span>Vispan Local Architecture</span>
									<span style="background: #00a32a; color: #FFFFFF; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 20px; letter-spacing: 0.04em;">PROVEN</span>
								</div>
							</th>
						</tr>
					</thead>
					<tbody>
						%s
					</tbody>
				</table>
			</div>',
			$rows_html
		);
	}

	/**
	 * Render Industry Vertical Specialization Cards.
	 *
	 * @return string HTML output.
	 */
	public static function render_industry_verticals_html(): string {
		$city           = Page_Context::get_city();
		$service        = Page_Context::get_service();
		$target_service = ! empty( $service ) ? $service : 'Your Business';

		$is_home_services = (bool) preg_match( '/\b(home\s*services?|home\s*cleaning|contractors?|emergency\s*trades?)\b/i', (string) $service );
		if ( $is_home_services ) {
			return self::render_home_services_trades_html();
		}

		$services = [
			[
				'icon'        => '🚀',
				'niche'       => 'Full-Funnel Growth',
				'btn_title'   => 'Digital Marketing',
				'title'       => sprintf( 'Digital Marketing for %s', $target_service ),
				'description' => sprintf( 'Comprehensive performance marketing funnels engineered to drive consistent, high-intent client acquisition and measurable ROI across %s.', $city ),
			],
			[
				'icon'        => '🎯',
				'niche'       => 'PPC & Search Ads',
				'btn_title'   => 'Google Ads',
				'title'       => 'Google Ads',
				'description' => 'High-converting Google Search, Display, and Performance Max campaigns precision-targeted to capture motivated, high-intent prospects at the exact moment of decision.',
			],
			[
				'icon'        => '🎨',
				'niche'       => 'Identity & Positioning',
				'btn_title'   => 'Branding Services',
				'title'       => 'Branding Services',
				'description' => 'Distinct brand identities, compelling visual guidelines, corporate narrative, and market positioning that build enduring authority, client trust, and long-term loyalty.',
			],
			[
				'icon'        => '🌐',
				'niche'       => 'Organic Search Domination',
				'btn_title'   => 'Search Engine Optimization',
				'title'       => 'Search Engine Optimization',
				'description' => sprintf( 'Dominant Google Map Pack and organic search visibility powered by localized entity schema, deep technical SEO, and authority-building content architecture for %s practices in %s.', $target_service, $city ),
			],
			[
				'icon'        => '💻',
				'niche'       => 'High-Performance UI/UX',
				'btn_title'   => 'Web Development',
				'title'       => sprintf( 'Web Development for %s', $target_service ),
				'description' => sprintf( 'Ultra-fast, mobile-first websites and landing pages custom-engineered with intuitive UX, seamless booking funnels, and frictionless conversion optimization in %s.', $city ),
			],
			[
				'icon'        => '📱',
				'niche'       => 'Engagement & Growth',
				'btn_title'   => 'Social Media Management',
				'title'       => 'Social Media Management',
				'description' => 'Strategic content creation, engaging visual storytelling, active community management, and targeted paid social funnels across Instagram, LinkedIn, and Facebook.',
			],
			[
				'icon'        => '🛡️',
				'niche'       => 'Review Velocity & Trust',
				'btn_title'   => 'Online Reputation Management',
				'title'       => 'Online Reputation Management',
				'description' => sprintf( 'Proactive 5-star review generation, positive sentiment cultivation, and 24/7 brand protection across Google Business Profile and local directories in %s.', $city ),
			],
			[
				'icon'        => '🎬',
				'niche'       => 'Cinematic Storytelling',
				'btn_title'   => 'Video Production',
				'title'       => 'Video Production',
				'description' => sprintf( 'High-impact commercial video production, brand documentaries, client case testimonials, and social video assets crafted to engage prospective %s clients.', $target_service ),
			],
			[
				'icon'        => '✨',
				'niche'       => 'High-End Visual FX',
				'btn_title'   => 'VFX (Visual Effects)',
				'title'       => 'VFX (Visual Effects)',
				'description' => 'Hollywood-grade visual effects, dynamic motion design, and seamless photorealistic compositing for premium commercial advertisements and high-end digital media.',
			],
			[
				'icon'        => '🧊',
				'niche'       => '3D Animation & Renders',
				'btn_title'   => 'CGI Services',
				'title'       => 'CGI Services',
				'description' => 'Photorealistic 3D modeling, detailed visual product and architectural renderings, and state-of-the-art computer-generated animations bringing visionary concepts to life.',
			],
		];

		$html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr)); gap: 20px; font-family: \'DM Sans\', sans-serif;">';
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $services as $idx => $s ) {
			$delay = $delays[ $idx % count( $delays ) ];
			$reveal_class = ( 0 === $idx % 2 ) ? 'vispan-reveal-left' : 'vispan-reveal-right';

			$html .= sprintf(
				'<div class="%s %s vispan-hover-card" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 16px; padding: 24px 20px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 14px rgba(2,66,106,0.04);">
					<div>
						<div style="font-size: 30px; margin-bottom: 12px; line-height: 1;">%s</div>
						<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #02426A; margin-bottom: 6px;">%s</div>
						<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 18px; font-weight: 800; color: #121212; margin: 0 0 10px; line-height: 1.3; letter-spacing: 0px !important; word-spacing: normal !important;">%s</h3>
						<p style="font-size: 13.5px; line-height: 1.6; color: #5D5756; margin: 0; letter-spacing: 0px !important; word-spacing: normal !important;">%s</p>
					</div>
					<div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #CCE0ED;">
						<a href="https://vispansolutions.com/contact-us/" style="text-decoration: none; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 12px; font-weight: 800; color: #02426A; display: inline-flex; align-items: center; gap: 4px; letter-spacing: 0px !important; word-spacing: normal !important;">
							Explore %s →
						</a>
					</div>
				</div>',
				esc_attr( $reveal_class ),
				esc_attr( $delay ),
				$s['icon'],
				esc_html( $s['niche'] ),
				esc_html( $s['title'] ),
				esc_html( $s['description'] ),
				esc_html( $s['btn_title'] ?? $s['title'] )
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render 6 Pillars & 21 Trades Multi-Trade Grid with interactive category filter for Home Services.
	 *
	 * @return string HTML output.
	 */
	public static function render_home_services_trades_html(): string {
		$city  = Page_Context::get_city();
		$state = Page_Context::get_state();

		$trades = [
			// Pillar 1: Core Systems & Emergency Trades
			[
				'pillar'      => 'Core Systems & Emergency',
				'category'    => 'core',
				'badge'       => 'High-Ticket Emergency',
				'title'       => 'HVAC Services',
				'description' => 'Heating, ventilation, air conditioning repair, system replacements, and seasonal duct maintenance.',
				'highlights'  => [ 'Emergency 24/7 Dispatch', 'High-Ticket Replacements' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/><circle cx="12" cy="12" r="10"/></svg>',
			],
			[
				'pillar'      => 'Core Systems & Emergency',
				'category'    => 'core',
				'badge'       => 'High-Ticket Emergency',
				'title'       => 'Plumbing Services',
				'description' => 'Drain clearing, pipe repair, leak detection, water heater installations, and emergency plumbing.',
				'highlights'  => [ 'Urgent Inbound Calls', 'Repiping & Heaters' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2v6a2 2 0 0 0 2 2h6"/><path d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/><path d="M4 12v6a2 2 0 0 0 2 2h6"/><path d="M20 12a8 8 0 0 1-8 8v-4a4 4 0 0 0 4-4h4z"/></svg>',
			],
			[
				'pillar'      => 'Core Systems & Emergency',
				'category'    => 'core',
				'badge'       => 'High-Ticket Emergency',
				'title'       => 'Electrical Services',
				'description' => 'Residential rewiring, breaker panel upgrades, lighting installations, EV charger setups, and smart home hardwiring.',
				'highlights'  => [ 'Panel Upgrades & EV', 'Safety Inspections' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
			],

			// Pillar 2: Exterior & Structural Maintenance
			[
				'pillar'      => 'Exterior & Structural',
				'category'    => 'exterior',
				'badge'       => 'Weather & Storm Events',
				'title'       => 'Roofing & Siding',
				'description' => 'Roof repairs, total replacements, leak interventions, gutters, and vinyl/wood exterior siding.',
				'highlights'  => [ 'Insurance & Storm Claims', 'High-Ticket Installs' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
			],
			[
				'pillar'      => 'Exterior & Structural',
				'category'    => 'exterior',
				'badge'       => 'Seasonal Cleanout',
				'title'       => 'Gutter Cleaning & Installation',
				'description' => 'Gutter guard installations, seasonal cleanouts, and drainage alignments.',
				'highlights'  => [ 'Gutter Guards & Alignments', 'Seasonal Surges' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>',
			],
			[
				'pillar'      => 'Exterior & Structural',
				'category'    => 'exterior',
				'badge'       => '24/7 Emergency Dispatch',
				'title'       => 'Garage Door Repair',
				'description' => 'Spring replacements, opener installations, track realignments, and 24/7 emergency dispatch.',
				'highlights'  => [ 'Torsion Springs & Openers', 'Same-Day Dispatch' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18"/></svg>',
			],
			[
				'pillar'      => 'Exterior & Structural',
				'category'    => 'exterior',
				'badge'       => 'Exterior Revitalization',
				'title'       => 'Pressure Washing',
				'description' => 'Exterior siding, driveway degreasing, deck revitalization, and commercial surface stripping.',
				'highlights'  => [ 'Driveways & Siding', 'Commercial Power Wash' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
			],

			// Pillar 3: Interior & Handyman Trades
			[
				'pillar'      => 'Interior & Handyman',
				'category'    => 'interior',
				'badge'       => 'High-Frequency Inquiries',
				'title'       => 'General Handyman Work',
				'description' => 'Fixture installations, drywall patching, minor carpentry, door repairs, and odd jobs.',
				'highlights'  => [ 'Drywall & Fixture Setup', 'Residential Repairs' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
			],
			[
				'pillar'      => 'Interior & Handyman',
				'category'    => 'interior',
				'badge'       => 'Aesthetic Renovation',
				'title'       => 'Painting',
				'description' => 'Interior and exterior painting, trim restoration, cabinet refinishing, and deck staining.',
				'highlights'  => [ 'Cabinet Refinishing', 'Interior/Exterior Trim' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 11-8-8-8.6 8.6a2 2 0 0 0 0 2.8l5.2 5.2c.8.8 2 .8 2.8 0L19 11Z"/><path d="m5 2 5 5"/><path d="M2 13h15"/><path d="M22 20a2 2 0 1 1-4 0c0-1.6 1.7-2.4 2-4 .3 1.6 2 2.4 2 4Z"/></svg>',
			],
			[
				'pillar'      => 'Interior & Handyman',
				'category'    => 'interior',
				'badge'       => 'Flooring & Hardwood',
				'title'       => 'Flooring & Carpeting',
				'description' => 'Hardwood installation, tile setting, carpet fitting, floor sanding, and luxury vinyl planking.',
				'highlights'  => [ 'Luxury Vinyl Plank & Tile', 'Hardwood Sanding' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
			],
			[
				'pillar'      => 'Interior & Handyman',
				'category'    => 'interior',
				'badge'       => 'Custom Woodworking',
				'title'       => 'Carpentry Services',
				'description' => 'Custom cabinetry, trim and molding, framing, staircases, and built-in shelving.',
				'highlights'  => [ 'Custom Built-Ins & Trim', 'Framing & Staircases' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 8.7 8.7 21.3c-.4.4-1 .4-1.4 0l-4.6-4.6c-.4-.4-.4-1 0-1.4L15.3 2.7c.4-.4 1-.4 1.4 0l4.6 4.6c.4.4.4 1 0 1.4z"/></svg>',
			],

			// Pillar 4: Outdoor Living & Yard Care
			[
				'pillar'      => 'Outdoor Living & Yard',
				'category'    => 'outdoor',
				'badge'       => 'Recurring Landscape Contracts',
				'title'       => 'Lawn Care & Landscaping',
				'description' => 'Landscape architecture, hardscaping, lawn fertilization, aerating, and sod installations.',
				'highlights'  => [ 'Hardscaping & Retaining Walls', 'Recurring Turf Plans' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-9"/><path d="M9 13a4.5 4.5 0 0 0 6 0"/><path d="M12 9a7 7 0 0 0 7-7 7 7 0 0 0-7 7z"/><path d="M12 9A7 7 0 0 0 5 2a7 7 0 0 0 7 7z"/></svg>',
			],
			[
				'pillar'      => 'Outdoor Living & Yard',
				'category'    => 'outdoor',
				'badge'       => 'High-Intent Extermination',
				'title'       => 'Pest Control & Wildlife Removal',
				'description' => 'Termite extermination, rodent exclusion, bed bug eradication, and humane wildlife removal.',
				'highlights'  => [ 'Termite & Rodent Exclusion', 'Emergency Infestation' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8 2 1.88 1.88"/><path d="M14.12 3.88 16 2"/><path d="M9 7.13v-1a3.003 3.003 0 1 1 6 0v1"/><path d="M12 20c-3.3 0-6-2.7-6-6v-3a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v3c0 3.3-2.7 6-6 6"/><path d="M12 20v-9"/><path d="M6.53 9C4.6 8.8 3 7.1 3 5"/><path d="M6 13H2"/><path d="M3 21c0-2.1 1.7-3.9 3.8-4"/><path d="M20.97 5c0 2.1-1.6 3.8-3.5 4"/><path d="M22 13h-4"/><path d="M17.2 17c2.1.1 3.8 1.9 3.8 4"/></svg>',
			],
			[
				'pillar'      => 'Outdoor Living & Yard',
				'category'    => 'outdoor',
				'badge'       => 'Seasonal Maintenance',
				'title'       => 'Pool Maintenance',
				'description' => 'Chemical balancing, pump and filtration repairs, liner replacement, and seasonal winterization.',
				'highlights'  => [ 'Pump & Filter Repairs', 'Winterization & Openings' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/></svg>',
			],
			[
				'pillar'      => 'Outdoor Living & Yard',
				'category'    => 'outdoor',
				'badge'       => 'Winter Surge Demand',
				'title'       => 'Snow & Ice Removal',
				'description' => 'Driveway plowing, commercial lot de-icing, and emergency salting.',
				'highlights'  => [ 'Commercial Plowing Contracts', 'Emergency Salting' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M19.07 4.93 4.93 19.07"/></svg>',
			],

			// Pillar 5: Residential Cleaning & Sanitation
			[
				'pillar'      => 'Cleaning & Sanitation',
				'category'    => 'cleaning',
				'badge'       => 'High-LTV Recurring',
				'title'       => 'Housekeeping & Maid Services',
				'description' => 'Recurring residential cleaning, deep scrubbing, move-in/move-out cleans, and turnover prep.',
				'highlights'  => [ 'Bi-Weekly Recurring LTV', 'Deep Turnover Cleans' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M19 21v-4a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v4"/><path d="M10 9a3 3 0 1 0 6 0 3 3 0 0 0-6 0"/></svg>',
			],
			[
				'pillar'      => 'Cleaning & Sanitation',
				'category'    => 'cleaning',
				'badge'       => 'Fast Turnover Disposal',
				'title'       => 'Junk Removal & Hauling',
				'description' => 'Property cleanouts, construction debris hauling, bulky item disposal, and eco-friendly recycling.',
				'highlights'  => [ 'Estate & Garage Cleanouts', 'Debris Hauling' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
			],
			[
				'pillar'      => 'Cleaning & Sanitation',
				'category'    => 'cleaning',
				'badge'       => 'High-Ticket Panes',
				'title'       => 'Window Cleaning',
				'description' => 'Residential exterior/interior panes, multi-story glass, screen restoration, and track cleaning.',
				'highlights'  => [ 'Pure Water Pole Systems', 'Multi-Story Glass' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="12" y1="3" x2="12" y2="21"/></svg>',
			],

			// Pillar 6: Safety, Security & Energy Management
			[
				'pillar'      => 'Safety, Security & Energy',
				'category'    => 'safety',
				'badge'       => 'Smart Integration',
				'title'       => 'Home Security Systems',
				'description' => 'Smart alarm installations, 24/7 CCTV surveillance, biometric access, and motion sensor grids.',
				'highlights'  => [ 'CCTV & Alarm Systems', 'Smart Access Control' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
			],
			[
				'pillar'      => 'Safety, Security & Energy',
				'category'    => 'safety',
				'badge'       => 'Emergency Lockouts',
				'title'       => 'Locksmith Services',
				'description' => 'Emergency lockouts, rekeying, high-security deadbolts, smart keypads, and access control.',
				'highlights'  => [ 'Emergency 24/7 Rekeying', 'Smart Keypad Locks' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
			],
			[
				'pillar'      => 'Safety, Security & Energy',
				'category'    => 'safety',
				'badge'       => 'Clean Energy & Inverters',
				'title'       => 'Solar Power & Energy Management',
				'description' => 'Photovoltaic panel design, battery storage backups, inverter repairs, and energy efficiency audits.',
				'highlights'  => [ 'Battery Storage Backups', 'Commercial & Home Solar' ],
				'icon_svg'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
			],
		];

		// Header Overview Strip
		$html  = '<div style="font-family: \'DM Sans\', sans-serif; width: 100%; max-width: 1200px; margin: 0 auto;">';
		$html .= '<div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 24px 28px; margin-bottom: 28px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px;">';
		$html .= '<div style="max-width: 650px;">';
		$html .= '<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #0B63F6; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">';
		$html .= '<span style="width: 6px; height: 6px; border-radius: 50%; background: #0B63F6; display: inline-block;"></span> Multi-Trade Lead Generation Ecosystem</span>';
		$html .= sprintf( '<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 8px; line-height: 1.3;">Digital Marketing Engineered for 6 Pillars &amp; 21 Contractor Trades in %s</h3>', esc_html( $city ) );
		$html .= sprintf( '<p style="font-size: 14px; line-height: 1.6; color: #475569; margin: 0;">From urgent emergency dispatches to seasonal maintenance contracts, Vispan Solutions deploys high-intent SEO, Google Local Services Ads, and conversion systems built exclusively for residential service pros.</p>' );
		$html .= '</div>';
		$html .= '<div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">';
		$html .= '<div style="text-align: center; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 18px; min-width: 95px;">';
		$html .= '<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 22px; font-weight: 800; color: #02426A; line-height: 1;">21</div>';
		$html .= '<div style="font-size: 10.5px; color: #64748B; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 4px;">Trades Supported</div>';
		$html .= '</div>';
		$html .= '<div style="text-align: center; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 10px 18px; min-width: 95px;">';
		$html .= '<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 22px; font-weight: 800; color: #02426A; line-height: 1;">6</div>';
		$html .= '<div style="font-size: 10.5px; color: #64748B; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 4px;">Core Pillars</div>';
		$html .= '</div>';
		$html .= '<div style="text-align: center; background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 10px; padding: 10px 18px; min-width: 95px;">';
		$html .= '<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 22px; font-weight: 800; color: #059669; line-height: 1;">100%</div>';
		$html .= '<div style="font-size: 10.5px; color: #047857; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 4px;">Exclusive Leads</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		// Modern Segmented Category Filter Track
		$html .= '<div style="display: flex; justify-content: center; margin-bottom: 28px;">';
		$html .= '<div class="scp-trade-filter-bar" style="display: inline-flex; flex-wrap: wrap; justify-content: center; gap: 6px; background: #FFFFFF; border: 1px solid #E2E8F0; padding: 5px; border-radius: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">';
		$html .= '<button type="button" class="scp-filter-btn" data-category="all" onclick="scpFilterTrades(\'all\', this)" style="padding: 8px 16px; border-radius: 30px; font-size: 12px; font-weight: 800; cursor: pointer; transition: all 0.2s; font-family: \'Plus Jakarta Sans\', sans-serif; border: none; background: #02426A; color: #FFFFFF; box-shadow: 0 2px 8px rgba(2,66,106,0.2);">All Trades (21)</button>';
		$html .= '<button type="button" class="scp-filter-btn" data-category="core" onclick="scpFilterTrades(\'core\', this)" style="padding: 8px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-family: \'Plus Jakarta Sans\', sans-serif; border: none; background: transparent; color: #475569;">Core &amp; Emergency (3)</button>';
		$html .= '<button type="button" class="scp-filter-btn" data-category="exterior" onclick="scpFilterTrades(\'exterior\', this)" style="padding: 8px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-family: \'Plus Jakarta Sans\', sans-serif; border: none; background: transparent; color: #475569;">Exterior &amp; Structural (4)</button>';
		$html .= '<button type="button" class="scp-filter-btn" data-category="interior" onclick="scpFilterTrades(\'interior\', this)" style="padding: 8px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-family: \'Plus Jakarta Sans\', sans-serif; border: none; background: transparent; color: #475569;">Interior &amp; Handyman (4)</button>';
		$html .= '<button type="button" class="scp-filter-btn" data-category="outdoor" onclick="scpFilterTrades(\'outdoor\', this)" style="padding: 8px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-family: \'Plus Jakarta Sans\', sans-serif; border: none; background: transparent; color: #475569;">Outdoor &amp; Yard Care (4)</button>';
		$html .= '<button type="button" class="scp-filter-btn" data-category="cleaning" onclick="scpFilterTrades(\'cleaning\', this)" style="padding: 8px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-family: \'Plus Jakarta Sans\', sans-serif; border: none; background: transparent; color: #475569;">Cleaning &amp; Sanitation (3)</button>';
		$html .= '<button type="button" class="scp-filter-btn" data-category="safety" onclick="scpFilterTrades(\'safety\', this)" style="padding: 8px 16px; border-radius: 30px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-family: \'Plus Jakarta Sans\', sans-serif; border: none; background: transparent; color: #475569;">Safety &amp; Solar (3)</button>';
		$html .= '</div>';
		$html .= '</div>';

		// Single Unified 3-Column Grid for All 21 Trades (3 services per row)
		$html .= '<div class="scp-trade-cards-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 28px;">';

		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $trades as $idx => $t ) {
			$delay        = $delays[ $idx % count( $delays ) ];
			$reveal_class = ( 0 === $idx % 2 ) ? 'vispan-reveal-left' : 'vispan-reveal-right';

			$badge_html = '';
			foreach ( $t['highlights'] as $hl ) {
				$badge_html .= sprintf(
					'<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 12px; font-weight: 600; color: #334155; background: #F1F5F9; border-radius: 6px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 5px;"><span style="color: #059669; font-weight: 900; font-size: 13px;">✓</span> %s</span>',
					esc_html( $hl )
				);
			}

			$html .= sprintf(
				'<div class="scp-trade-card vispan-hover-card %s %s" data-category="%s" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 24px 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;" onmouseover="this.style.borderColor=\'#02426A\';this.style.transform=\'translateY(-3px)\';this.style.boxShadow=\'0 12px 28px -6px rgba(2,66,106,0.12)\';" onmouseout="this.style.borderColor=\'#E2E8F0\';this.style.transform=\'translateY(0)\';this.style.boxShadow=\'0 2px 8px rgba(0,0,0,0.02)\';">
					<div>
						<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
							<div style="width: 40px; height: 40px; border-radius: 8px; background: #F0F6FA; display: flex; align-items: center; justify-content: center; color: #02426A;">
								%s
							</div>
							<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #64748B;">%s</span>
						</div>
						<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #0B63F6; margin-bottom: 5px;">%s</div>
						<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 8px; line-height: 1.3;">%s</h3>
						<p style="font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 16px;">%s</p>
						<div style="display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 8px;">
							%s
						</div>
					</div>
					<div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #F1F5F9; display: flex; align-items: center; justify-content: space-between;">
						<a href="javascript:void(0);" onclick="scpSelectTrade(\'%s\')" style="text-decoration: none; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 13px; font-weight: 800; color: #02426A; display: inline-flex; align-items: center; gap: 6px; transition: color 0.15s ease;" onmouseover="this.style.color=\'#0B63F6\';" onmouseout="this.style.color=\'#02426A\';">
							Grow %s Leads →
						</a>
						<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11px; color: #065F46; font-weight: 700; background: #ECFDF5; border: 1px solid #A7F3D0; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;">
							<span style="width: 6px; height: 6px; border-radius: 50%%; background: #059669; display: inline-block;"></span>%s
						</span>
					</div>
				</div>',
				esc_attr( $reveal_class ),
				esc_attr( $delay ),
				esc_attr( $t['category'] ),
				$t['icon_svg'],
				esc_html( $t['badge'] ),
				esc_html( $t['pillar'] ),
				esc_html( $t['title'] ),
				esc_html( $t['description'] ),
				$badge_html,
				esc_attr( $t['title'] ),
				esc_html( $t['title'] ),
				esc_html( $city )
			);
		}

		$html .= '</div>'; // End scp-trade-cards-grid

		// Inline Script for filtering & trade selection
		$html .= '<script>
		if (typeof window.scpFilterTrades !== "function") {
			window.scpFilterTrades = function(category, btn) {
				var cards = document.querySelectorAll(".scp-trade-card");
				cards.forEach(function(card) {
					if (category === "all" || card.getAttribute("data-category") === category) {
						card.style.display = "flex";
					} else {
						card.style.display = "none";
					}
				});
				var btns = document.querySelectorAll(".scp-filter-btn");
				btns.forEach(function(b) {
					b.style.background = "transparent";
					b.style.color = "#475569";
					b.style.boxShadow = "none";
				});
				if (btn) {
					btn.style.background = "#02426A";
					btn.style.color = "#FFFFFF";
					btn.style.boxShadow = "0 2px 8px rgba(2,66,106,0.2)";
				}
			};
		}
		if (typeof window.scpSelectTrade !== "function") {
			window.scpSelectTrade = function(tradeName) {
				var formCard = document.querySelector(".vispan-inquiry-card");
				if (formCard) {
					formCard.scrollIntoView({ behavior: "smooth", block: "center" });
					var svcHidden = formCard.querySelector("input[name=\'service\']");
					var svcVisible = formCard.querySelector(".vispan-search-input");
					if (svcHidden) svcHidden.value = tradeName;
					if (svcVisible) svcVisible.value = tradeName;
					formCard.style.boxShadow = "0 0 0 3px #0B63F6, 0 20px 40px -15px rgba(2,66,106,0.2)";
					setTimeout(function() {
						formCard.style.boxShadow = "";
					}, 1800);
				} else {
					window.location.href = "https://vispansolutions.com/contact-us/";
				}
			};
		}
		</script>';

		$html .= '</div>'; // End container wrapper

		return $html;
	}

	/**
	 * Render 12-Point Technical & Local Deliverables Checklist.
	 *
	 * @return string HTML output.
	 */
	public static function render_deliverables_checklist_html(): string {
		$city = Page_Context::get_city();

		$deliverables = [
			[ 'title' => 'Technical Crawl & Speed Tuning', 'desc' => 'Core Web Vitals tuning and clean DOM architecture for instant load speeds.' ],
			[ 'title' => 'Nested Geo-Entity JSON-LD Schema', 'desc' => sprintf( 'Coordinates, service catalog, and areaServed markup for %s.', $city ) ],
			[ 'title' => 'Google Business Profile 3-Pack Optimization', 'desc' => 'Category structuring, geotagged media, and service area alignment.' ],
			[ 'title' => 'Submarket Commercial Landing Nodes', 'desc' => sprintf( 'Dedicated landing pages capturing %s regional search demand.', $city ) ],
			[ 'title' => 'NAP Citation Verification & Sync', 'desc' => 'Ensuring 100% address and phone consistency across top business directories.' ],
			[ 'title' => 'Geo-Grid Proximity Rank Tracking', 'desc' => 'Visual radius tracking measuring exact rankings across individual neighborhoods.' ],
			[ 'title' => 'Dynamic Call & Form Lead Attribution', 'desc' => 'Tracking every phone call and form submission directly to the keyword source.' ],
			[ 'title' => 'Competitor Search Gap Exploitation', 'desc' => 'Targeting uncompetitive, high-margin commercial keywords competitors ignore.' ],
			[ 'title' => 'Local Link & Citation Acquisition', 'desc' => 'Securing regional references, industry directories, and local media mentions.' ],
			[ 'title' => 'Conversion Rate & CTA Optimization', 'desc' => 'Heatmapping and mobile funnel enhancements converting visitors into inquiries.' ],
			[ 'title' => 'Review Generation & Reputation Cadence', 'desc' => 'Automated review capture framework building sustained 5-star Google authority.' ],
			[ 'title' => 'Monthly Executive Video ROI Reporting', 'desc' => 'Direct strategic breakdown with your dedicated senior strategist every month.' ],
		];

		$html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 16px; font-family: \'DM Sans\', sans-serif;">';
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $deliverables as $idx => $d ) {
			$delay = $delays[ $idx % count( $delays ) ];
			$reveal_class = ( 0 === $idx % 2 ) ? 'vispan-reveal-left' : 'vispan-reveal-right';

			$html .= sprintf(
				'<div class="%s %s" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 14px; padding: 18px 20px; display: flex; gap: 12px; align-items: flex-start;">
					<span style="background: #e6f7ec; color: #00a32a; font-size: 15px; font-weight: 900; width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">✓</span>
					<div>
						<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 14.5px; font-weight: 800; color: #121212; margin-bottom: 4px;">%s</div>
						<div style="font-size: 13px; line-height: 1.5; color: #5D5756;">%s</div>
					</div>
				</div>',
				esc_attr( $reveal_class ),
				esc_attr( $delay ),
				esc_html( $d['title'] ),
				esc_html( $d['desc'] )
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render Metro Stats In-Numbers card grid.
	 *
	 * @return string HTML output.
	 */
	public static function render_metro_stats_html(): string {
		$content = self::get_active_content();
		$stats   = $content['metro_stats'] ?? [];

		if ( empty( $stats ) || ! is_array( $stats ) ) {
			$city  = Page_Context::get_city();
			$state = Page_Context::get_state();
			$stats = [
				[ 'value' => '2.8M+', 'label' => sprintf( '%s Metro Population', $city ), 'description' => 'Target market size & active digital search audience.' ],
				[ 'value' => '120k+', 'label' => 'Active Commercial Entities', 'description' => sprintf( 'Competing for local and regional visibility across %s.', $state ) ],
				[ 'value' => '84%', 'label' => 'Aggregator SERP Dominance', 'description' => 'Directories outranked through localized submarket schema.' ],
				[ 'value' => '4.8★', 'label' => 'Review Benchmark Target', 'description' => 'Target velocity to trigger Google 3-Pack authority.' ],
				[ 'value' => '90 Days', 'label' => 'Compound Authority Horizon', 'description' => 'Typical timeframe to establish top 3 ranking momentum.' ],
			];
		}

		$html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 160px), 1fr)); gap: 14px;">';
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $stats as $idx => $s ) {
			$val   = esc_html( $s['value'] ?? '' );
			$label = esc_html( $s['label'] ?? '' );
			$desc  = esc_html( $s['description'] ?? '' );
			$delay = $delays[ $idx % count( $delays ) ];

			$html .= sprintf(
				'<div class="vispan-reveal-up %s vispan-hover-card" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 14px; padding: 20px 16px; text-align: center;">
					<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 26px; font-weight: 900; color: #02426A; line-height: 1.1; margin-bottom: 6px;">%s</div>
					<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 13px; font-weight: 800; color: #121212; margin-bottom: 4px;">%s</div>
					<div style="font-family: \'DM Sans\', sans-serif; font-size: 12px; line-height: 1.45; color: #5D5756;">%s</div>
				</div>',
				esc_attr( $delay ),
				$val,
				$label,
				$desc
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render Market Anatomy overview section.
	 *
	 * @return string HTML output.
	 */
	public static function render_market_anatomy_html(): string {
		$content = self::get_active_content();
		$anatomy = $content['market_anatomy'] ?? [];

		$city    = Page_Context::get_city();
		$state   = Page_Context::get_state();
		$service = Page_Context::get_service();

		$kicker   = ! empty( $anatomy['kicker'] ) ? esc_html( $anatomy['kicker'] ) : sprintf( 'Twelve markets in one city. Ranking in %s is an interconnected ecosystem of hyper-local intent.', $city );
		$heading  = ! empty( $anatomy['heading'] ) ? esc_html( $anatomy['heading'] ) : sprintf( 'The Search Anatomy of %s, %s', $city, $state );
		$lead     = ! empty( $anatomy['lead_paragraph'] ) ? self::safe_sanitize_body( $anatomy['lead_paragraph'] ) : sprintf( 'National agencies treat %s as a single geographic checkbox. In reality, commercial buyer behavior in the metro region is fragmented across distinct submarkets, each with its own search density, competitor saturation, and conversion patterns.', $city );
		$dynamics = ! empty( $anatomy['search_dynamics'] ) ? self::safe_sanitize_body( $anatomy['search_dynamics'] ) : sprintf( 'To achieve sustained first-page rankings for %s, your business must establish topical authority across both core city-center searches and high-margin surrounding corridors.', $service );

		return sprintf(
			'<div class="vispan-reveal-up" style="max-width: 900px; margin: 0 auto; text-align: center; font-family: \'DM Sans\', sans-serif;">
				<div style="display: inline-block; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11.5px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #02426A; background: #F0F6FA; border: 1px solid #CCE0ED; border-radius: 30px; padding: 5px 14px; margin-bottom: 12px;">%s</div>
				<h2 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #121212; line-height: 1.2; margin: 0 0 14px;">%s</h2>
				<p style="font-size: 16.5px; line-height: 1.65; color: #121212; font-weight: 500; margin: 0 0 12px;">%s</p>
				<p style="font-size: 14.5px; line-height: 1.6; color: #5D5756; margin: 0;">%s</p>
			</div>',
			$kicker,
			$heading,
			$lead,
			$dynamics
		);
	}

	/**
	 * Render Market Targeting & Search Intent Accordion.
	 *
	 * @return string HTML output.
	 */
	public static function render_market_accordion_html(): string {
		$content   = self::get_active_content();
		$accordion = $content['market_accordion'] ?? [];

		if ( empty( $accordion ) || ! is_array( $accordion ) ) {
			$city    = Page_Context::get_city();
			$service = Page_Context::get_service();
			$accordion = [
				[
					'title'    => 'The searches we target — and why',
					'subtitle' => 'High-CPC commercial and corporate corridors',
					'body'     => sprintf( 'We target bottom-of-funnel commercial keywords with clear purchase intent rather than vanity informational queries. Searches targeting specific commercial districts in %s drive actual high-value inquiries and closed contracts.', $city ),
				],
				[
					'title'    => sprintf( 'Mapping %s\'s search demand', $city ),
					'subtitle' => 'Multi-district intent & competitor analysis',
					'body'     => sprintf( 'We construct localized search demand maps across distinct submarket clusters in %s. By mapping search volume, competitor domain authority, and geographic intent per neighborhood, we deploy targeted landing nodes where competition is winnable.', $city ),
				],
				[
					'title'    => 'A strategy sized to local competition',
					'subtitle' => 'Outmaneuvering national aggregators in SERPs',
					'body'     => sprintf( 'National directories dominate generic %s searches through sheer domain weight. We outmaneuver them by establishing hyper-local topical authority, schema coordinates, and localized review signals that Google favors for local proximity.', $service ),
				],
				[
					'title'    => 'Entity schema & AI search readiness',
					'subtitle' => 'Structured JSON-LD for ChatGPT, Gemini & Google AI',
					'body'     => 'We engineer nested schema and entity relationships so your business is recognized as the verified regional authority in search engines and AI Overviews.',
				],
			];
		}

		$html = '<div style="display: flex; flex-direction: column; gap: 14px;">';
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $accordion as $idx => $item ) {
			$title    = esc_html( $item['title'] ?? '' );
			$subtitle = esc_html( $item['subtitle'] ?? '' );
			$body     = self::safe_sanitize_body( $item['body'] ?? '' );
			$delay    = $delays[ $idx % count( $delays ) ];
			$reveal_class = ( 0 === $idx % 2 ) ? 'vispan-reveal-left' : 'vispan-reveal-right';

			$html .= sprintf(
				'<div class="%s %s vispan-hover-card" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 14px; padding: 22px 24px;">
					<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; flex-wrap: wrap; gap: 8px;">
						<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 17px; font-weight: 800; color: #02426A; margin: 0;">%s</h3>
						<span style="font-family: \'DM Sans\', sans-serif; font-size: 11.5px; font-weight: 700; color: #00a32a; background: #e6f7ec; border-radius: 20px; padding: 3px 10px;">%s</span>
					</div>
					<p style="font-family: \'DM Sans\', sans-serif; font-size: 14.5px; line-height: 1.6; color: #5D5756; margin: 0;">%s</p>
				</div>',
				esc_attr( $reveal_class ),
				esc_attr( $delay ),
				$title,
				$subtitle,
				$body
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render Submarkets Grid cards.
	 *
	 * @return string HTML output.
	 */
	public static function render_submarkets_grid_html(): string {
		$content    = self::get_active_content();
		$submarkets = $content['submarkets'] ?? [];

		if ( empty( $submarkets ) || ! is_array( $submarkets ) ) {
			$city    = Page_Context::get_city();
			$service = Page_Context::get_service();
			$submarkets = [
				[
					'name'        => sprintf( '%s Downtown & Central Business District', $city ),
					'type_tag'    => 'Corporate • Legal • B2B Commercial',
					'fact'        => 'High CPC commercial queries with strong phone call conversion rates.',
					'description' => sprintf( 'Dedicated landing nodes and enterprise entity schema capturing corporate decision makers searching for %s across central business corridors.', $service ),
				],
				[
					'name'        => sprintf( '%s North Metro & Innovation Corridor', $city ),
					'type_tag'    => 'Technology • High-Growth Hub',
					'fact'        => 'Fastest-growing search volume for modern digital and commercial B2B services.',
					'description' => 'Targeted technical search architecture engineered to capture emerging tech corridor growth and regional enterprise procurement.',
				],
				[
					'name'        => sprintf( '%s East Commercial Logistics Zone', $city ),
					'type_tag'    => 'Industrial • Commercial Services',
					'fact'        => 'B2B search terms with large contract values and high buyer retention.',
					'description' => 'Targeted transactional queries and localized schema capturing commercial operators, industrial logistics, and facility owners.',
				],
				[
					'name'        => sprintf( '%s Suburban Growth & Retail Corridor', $city ),
					'type_tag'    => 'Local Services • High Foot Traffic',
					'fact'        => 'Dense local map searches with strong mobile-to-call intent.',
					'description' => 'Google Map Pack optimization and geo-citation networks engineered for dominant local radius coverage across expanding suburban markets.',
				],
				[
					'name'        => sprintf( '%s Affluent West Submarket', $city ),
					'type_tag'    => 'High-Ticket Services • Wealth & Advisory',
					'fact'        => 'High-value customer intent with premium service expectations.',
					'description' => 'Reputation velocity, high-trust content, and hyper-targeted geographic signals designed to capture premium residential and commercial clients.',
				],
				[
					'name'        => sprintf( '%s Regional Trade Hub', $city ),
					'type_tag'    => 'Wholesale • Trade & Contracting',
					'fact'        => 'Commercial trade inquiries with recurring contract lifecycles.',
					'description' => 'Comprehensive category structuring, geo-grid proximity tracking, and directory alignment across commercial trade zones.',
				],
			];
		}

		$commercial_img = esc_url( self::get_image_url( 'commercial-district.jpg' ) );
		$html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 20px; font-family: \'DM Sans\', sans-serif;">';
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $submarkets as $idx => $sm ) {
			$num  = str_pad( (string) ( $idx + 1 ), 2, '0', STR_PAD_LEFT );
			$name = esc_html( $sm['name'] ?? '' );
			$tag  = esc_html( $sm['type_tag'] ?? 'Commercial Search' );
			$fact = esc_html( $sm['fact'] ?? '' );
			$desc = esc_html( $sm['description'] ?? '' );
			$delay = $delays[ $idx % count( $delays ) ];
			$reveal_class = ( 0 === $idx % 2 ) ? 'vispan-reveal-left' : 'vispan-reveal-right';

			// First card features an architectural commercial photo header.
			$card_img_header = ( 0 === $idx ) ? sprintf(
				'<div style="height: 110px; margin: -26px -22px 18px; overflow: hidden; position: relative;">
					<img src="%s" alt="Commercial Submarket" style="width: 100%%; height: 100%%; object-fit: cover;" />
					<div style="position: absolute; inset: 0; background: linear-gradient(180deg, transparent 40%%, rgba(2,66,106,0.7) 100%%);"></div>
				</div>',
				$commercial_img
			) : '';

			$html .= sprintf(
				'<div class="%s %s vispan-hover-card" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 18px; padding: 26px 22px; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; box-shadow: 0 10px 30px -15px rgba(2,66,106,0.08);">
					<div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #02426A 0%%, #00a32a 100%%);"></div>
					<div>
						%s
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
							<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 12.5px; font-weight: 900; color: #02426A; letter-spacing: 0.08em;">%s /</span>
							<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 10.5px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #02426A; background: #F0F6FA; border: 1px solid #CCE0ED; border-radius: 20px; padding: 3px 10px;">%s</span>
						</div>
						<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 17.5px; font-weight: 800; color: #121212; line-height: 1.35; margin: 0 0 10px;">%s</h3>
						<p style="font-family: \'DM Sans\', sans-serif; font-size: 14px; line-height: 1.6; color: #5D5756; margin: 0 0 14px;">%s</p>
					</div>
					<div style="border-top: 1px solid #CCE0ED; font-family: \'DM Sans\', sans-serif; font-size: 12.5px; font-weight: 600; color: #02426A; background: #F0F6FA; margin: 0 -22px -26px; padding: 12px 22px; border-radius: 0 0 16px 16px;">
						🎯 <strong style="color: #121212;">Target Intent:</strong> %s
					</div>
				</div>',
				esc_attr( $reveal_class ),
				esc_attr( $delay ),
				$card_img_header,
				$num,
				$tag,
				$name,
				$desc,
				$fact
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render Proof / Case Study Highlight Banner with verified growth analytics showcase.
	 *
	 * @return string HTML output.
	 */
	public static function render_case_proof_html(): string {
		$content = self::get_active_content();
		$proof   = $content['case_proof'] ?? [];

		$city    = Page_Context::get_city();
		$service = Page_Context::get_service();
		$url     = Page_Context::get_url();

		$title   = ! empty( $proof['title'] ) ? esc_html( $proof['title'] ) : 'Regional Growth Benchmark Case Study';
		$tagline = ! empty( $proof['tagline'] ) ? esc_html( $proof['tagline'] ) : sprintf( 'How a local business outranked national aggregators across %s', $city );
		$quote   = ! empty( $proof['client_quote'] ) ? esc_html( $proof['client_quote'] ) : sprintf( '"In under 5 months, Vispan transformed our organic search footprint. We moved from page 4 to top 3 Map Pack rankings across 14 target zip codes in %s."', $city );
		$analytics_img = esc_url( self::get_image_url( 'growth-analytics.jpg' ) );

		$highlights = $proof['highlights'] ?? [
			[ 'metric' => '+310%', 'label' => 'Organic Inbound Inquiries' ],
			[ 'metric' => 'Top 3', 'label' => sprintf( 'Map Pack in %s', $city ) ],
			[ 'metric' => '#1 Rank', 'label' => sprintf( 'High-Intent %s Keywords', $service ) ],
		];

		$stat_html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 90px), 1fr)); gap: 10px; margin: 18px 0 0; width: 100%; box-sizing: border-box;">';
		foreach ( $highlights as $h ) {
			$stat_html .= sprintf(
				'<div style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; padding: 10px 8px; text-align: center; box-sizing: border-box;">
					<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: clamp(16px, 3.5vw, 22px); font-weight: 900; color: #FFFFFF; line-height: 1.15;">%s</div>
					<div style="font-family: \'DM Sans\', sans-serif; font-size: 10.5px; color: #BEE3F8; font-weight: 600; line-height: 1.35; margin-top: 3px;">%s</div>
				</div>',
				esc_html( $h['metric'] ?? '' ),
				esc_html( $h['label'] ?? '' )
			);
		}
		$stat_html .= '</div>';

		return sprintf(
			'<div class="vispan-reveal-up vispan-hover-card vispan-proof-card" style="background: #02426A; border-radius: 20px; padding: 32px 26px; color: #FFFFFF; box-shadow: 0 25px 60px -20px rgba(2,66,106,0.5); font-family: \'DM Sans\', sans-serif; box-sizing: border-box; max-width: 100%%; overflow: hidden;">
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%%, 280px), 1fr)); gap: 24px; align-items: center; width: 100%%; box-sizing: border-box;">
					<div style="min-width: 0; box-sizing: border-box;">
						<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11.5px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #00a32a; background: #e6f7ec; display: inline-block; padding: 3px 10px; border-radius: 20px; margin-bottom: 12px;">%s</div>
						<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: clamp(20px, 2.6vw, 30px); font-weight: 800; color: #FFFFFF; line-height: 1.25; margin: 0 0 10px;">%s</h3>
						<p style="font-size: 14.5px; line-height: 1.6; color: #E2E8F0; font-style: italic; margin: 0;">%s</p>
						%s
					</div>
					<div style="position: relative; border-radius: 16px; overflow: hidden; box-shadow: 0 16px 36px -10px rgba(0,0,0,0.4); border: 2px solid rgba(255,255,255,0.15); width: 100%%; max-width: 100%%; box-sizing: border-box;">
						<img src="%s" alt="SEO Growth Analytics Proof" style="width: 100%%; height: auto; max-height: 260px; display: block; object-fit: cover;" />
						<div style="position: absolute; bottom: 10px; right: 10px; background: rgba(2,66,106,0.9); border: 1px solid rgba(255,255,255,0.3); border-radius: 30px; padding: 5px 12px; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 10.5px; font-weight: 800; color: #FFFFFF; letter-spacing: 0.08em; text-transform: uppercase;">
							📈 Verified Analytics
						</div>
					</div>
				</div>
			</div>',
			$title,
			$tagline,
			$quote,
			$stat_html,
			$analytics_img
		);
	}

	/**
	 * Render Signal Stack ranking factor breakdown with SVG Donut Chart visualization.
	 *
	 * @return string HTML output.
	 */
	public static function render_signal_stack_html(): string {
		return '';
	}

	/**
	 * Render Long-Form Editorial Essay with drop-cap, consultation visual, and side milestone timeline.
	 *
	 * @return string HTML output.
	 */
	public static function render_essay_html(): string {
		$content = self::get_active_content();
		$essay   = $content['essay'] ?? [];

		$city    = Page_Context::get_city();
		$state   = Page_Context::get_state();
		$service = Page_Context::get_service();

		$heading = ! empty( $essay['heading'] ) ? esc_html( $essay['heading'] ) : sprintf( 'The Strategic Blueprint for %s in %s', $service, $city );
		$body_raw = ! empty( $essay['body'] ) ? $essay['body'] : sprintf( 'Achieving dominant search positioning in %s requires far more than superficial keyword adjustments. Local decision makers scrutinize authority signals, verified competence, and responsive communication before ever filling out a contact form. In competitive metropolitan markets, generic national agencies deploy thin template pages that Google algorithms devalue as doorway content. Our localized search architecture treats %s as an interconnected network of high-value submarkets, engineering content depth, nested schema graphs, and Google 3-Pack authority that outperforms thin directory pages and generic competitors alike.', $city, $city );
		$consult_img = esc_url( self::get_image_url( 'seo-consultation.jpg' ) );

		// Clean body and format with editorial drop-cap.
		$sanitized_body = self::safe_sanitize_body( $body_raw );
		$first_char = mb_substr( strip_tags( $sanitized_body ), 0, 1 );
		$rest_text  = mb_substr( strip_tags( $sanitized_body ), 1 );

		$dropcap_html = sprintf(
			'<span style="float: left; font-family: \'Plus Jakarta Sans\', serif; font-size: 48px; line-height: 40px; font-weight: 900; color: #02426A; margin-right: 10px; padding: 2px 2px 0 0;">%s</span>%s',
			esc_html( $first_char ),
			nl2br( esc_html( $rest_text ) )
		);

		$milestones = $essay['milestones'] ?? [
			[ 'step' => 'Phase 01', 'title' => 'Technical Baseline & Geo-Entity Graph', 'summary' => 'Comprehensive crawl audit, speed tuning, and JSON-LD schema.' ],
			[ 'step' => 'Phase 02', 'title' => 'Submarket Commercial Intent Expansion', 'summary' => 'Dedicated landing nodes targeting high-CPC buyer queries.' ],
			[ 'step' => 'Phase 03', 'title' => 'Citation Velocity & Review Generation', 'summary' => 'Google 3-Pack authority alignment and citation audit.' ],
			[ 'step' => 'Phase 04', 'title' => 'Conversion Optimization & Lead Tracking', 'summary' => 'Continuous heatmapping and transparent monthly ROI reporting.' ],
		];

		$milestone_html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
		foreach ( $milestones as $m ) {
			$milestone_html .= sprintf(
				'<div style="background: #FFFFFF; border: 1px solid #CCE0ED; border-radius: 12px; padding: 14px 18px;">
					<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 10.5px; font-weight: 800; color: #02426A; letter-spacing: 0.1em; text-transform: uppercase;">%s</div>
					<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 14.5px; font-weight: 800; color: #121212; margin: 2px 0 4px; letter-spacing: 0px !important; word-spacing: normal !important;">%s</div>
					<div style="font-family: \'DM Sans\', sans-serif; font-size: 12.5px; color: #5D5756; line-height: 1.5; letter-spacing: 0px !important; word-spacing: normal !important;">%s</div>
				</div>',
				esc_html( $m['step'] ?? '' ),
				esc_html( $m['title'] ?? '' ),
				esc_html( $m['summary'] ?? '' )
			);
		}
		$milestone_html .= '</div>';

		return sprintf(
			'<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%%, 280px), 1fr)); gap: 30px; align-items: start; font-family: \'DM Sans\', sans-serif;">
				<div class="vispan-reveal-left">
					<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11.5px; font-weight: 800; letter-spacing: 0.16em; text-transform: uppercase; color: #02426A; margin-bottom: 8px;">EDITORIAL ANALYSIS</div>
					<h2 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: clamp(24px, 3vw, 36px); font-weight: 800; color: #121212; line-height: 1.25; margin: 0 0 16px; letter-spacing: 0px !important; word-spacing: normal !important;">%s</h2>
					<div style="font-size: 15px; line-height: 1.75; color: #4A5568; margin-bottom: 20px; letter-spacing: 0px !important; word-spacing: normal !important;">%s</div>
				</div>
				<div class="vispan-reveal-right" style="background: #F0F6FA; border: 1.5px solid #CCE0ED; border-radius: 18px; padding: 24px 20px;">
					<div style="height: 130px; margin: -24px -20px 18px; overflow: hidden; border-radius: 16px 16px 0 0; position: relative;">
						<img src="%s" alt="1-on-1 Strategist Consultation" style="width: 100%%; height: 100%%; object-fit: cover;" />
						<div style="position: absolute; bottom: 8px; left: 10px; background: rgba(2,66,106,0.85); padding: 3px 8px; border-radius: 20px; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 10.5px; font-weight: 800; color: #FFFFFF; letter-spacing: 0px !important; word-spacing: normal !important;">
							🤝 Direct Senior Strategist Access
						</div>
					</div>
					<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 16.5px; font-weight: 800; color: #121212; margin: 0 0 14px; letter-spacing: 0px !important; word-spacing: normal !important;">Execution Roadmap</h3>
					%s
				</div>
			</div>',
			$heading,
			$dropcap_html,
			$consult_img,
			$milestone_html
		);
	}

	/**
	 * Render the Hero Section Inquiry Form (Compact vertical card).
	 *
	 * @param array $args Optional arguments.
	 * @return string
	 */
	public static function render_inquiry_form_hero( array $args = [] ): string {
		return self::render_inquiry_form( 'hero', $args );
	}

	/**
	 * Render the Bottom CTA Section Inquiry Form (Spacious multi-column card).
	 *
	 * @param array $args Optional arguments.
	 * @return string
	 */
	public static function render_inquiry_form_bottom( array $args = [] ): string {
		return self::render_inquiry_form( 'bottom', $args );
	}

	/**
	 * Render a Marketing Proposal Inquiry Form matching Vispan Solutions design language.
	 * Includes searchable dropdowns with both dropdown list and instant search capability.
	 *
	 * @param string $layout 'hero' or 'bottom'.
	 * @param array  $args   Optional overrides for defaults.
	 * @return string
	 */
	public static function render_inquiry_form( string $layout = 'hero', array $args = [] ): string {
		$form_id     = uniqid( 'vispan_form_' );
		$page_id     = $args['page_id'] ?? ( class_exists( Page_Context::class ) ? Page_Context::get_page_id() : 0 );
		$current_svc = $args['service'] ?? ( class_exists( Page_Context::class ) ? Page_Context::get_service() : '' );
		$page_url    = $args['url'] ?? ( class_exists( Page_Context::class ) ? Page_Context::get_url() : '' );
		$ajax_url    = function_exists( 'admin_url' ) ? ( function_exists( 'wp_make_link_relative' ) ? wp_make_link_relative( admin_url( 'admin-ajax.php' ) ) : admin_url( 'admin-ajax.php' ) ) : '/wp-admin/admin-ajax.php';
		$nonce       = function_exists( 'wp_create_nonce' ) ? wp_create_nonce( \SeoCityPages\Frontend\Inquiry_Handler::NONCE_ACTION ) : '';

		// Complete global list of all countries with dial codes
		$country_options = [
			[ 'code' => '+93', 'label' => '🇦🇫 +93 (Afghanistan)' ],
			[ 'code' => '+355', 'label' => '🇦🇱 +355 (Albania)' ],
			[ 'code' => '+213', 'label' => '🇩🇿 +213 (Algeria)' ],
			[ 'code' => '+376', 'label' => '🇦🇩 +376 (Andorra)' ],
			[ 'code' => '+244', 'label' => '🇦🇴 +244 (Angola)' ],
			[ 'code' => '+1268', 'label' => '🇦🇬 +1268 (Antigua & Barbuda)' ],
			[ 'code' => '+54', 'label' => '🇦🇷 +54 (Argentina)' ],
			[ 'code' => '+374', 'label' => '🇦🇲 +374 (Armenia)' ],
			[ 'code' => '+61', 'label' => '🇦🇺 +61 (Australia)' ],
			[ 'code' => '+43', 'label' => '🇦🇹 +43 (Austria)' ],
			[ 'code' => '+994', 'label' => '🇦🇿 +994 (Azerbaijan)' ],
			[ 'code' => '+1242', 'label' => '🇧🇸 +1242 (Bahamas)' ],
			[ 'code' => '+973', 'label' => '🇧🇭 +973 (Bahrain)' ],
			[ 'code' => '+880', 'label' => '🇧🇩 +880 (Bangladesh)' ],
			[ 'code' => '+1246', 'label' => '🇧🇧 +1246 (Barbados)' ],
			[ 'code' => '+375', 'label' => '🇧🇾 +375 (Belarus)' ],
			[ 'code' => '+32', 'label' => '🇧🇪 +32 (Belgium)' ],
			[ 'code' => '+501', 'label' => '🇧🇿 +501 (Belize)' ],
			[ 'code' => '+229', 'label' => '🇧🇯 +229 (Benin)' ],
			[ 'code' => '+975', 'label' => '🇧🇹 +975 (Bhutan)' ],
			[ 'code' => '+591', 'label' => '🇧🇴 +591 (Bolivia)' ],
			[ 'code' => '+387', 'label' => '🇧🇦 +387 (Bosnia & Herzegovina)' ],
			[ 'code' => '+267', 'label' => '🇧🇼 +267 (Botswana)' ],
			[ 'code' => '+55', 'label' => '🇧🇷 +55 (Brazil)' ],
			[ 'code' => '+673', 'label' => '🇧🇳 +673 (Brunei)' ],
			[ 'code' => '+359', 'label' => '🇧🇬 +359 (Bulgaria)' ],
			[ 'code' => '+226', 'label' => '🇧🇫 +226 (Burkina Faso)' ],
			[ 'code' => '+257', 'label' => '🇧🇮 +257 (Burundi)' ],
			[ 'code' => '+855', 'label' => '🇰🇭 +855 (Cambodia)' ],
			[ 'code' => '+237', 'label' => '🇨🇲 +237 (Cameroon)' ],
			[ 'code' => '+1', 'label' => '🇨🇦 +1 (Canada)' ],
			[ 'code' => '+238', 'label' => '🇨🇻 +238 (Cape Verde)' ],
			[ 'code' => '+236', 'label' => '🇨🇫 +236 (Central African Rep.)' ],
			[ 'code' => '+235', 'label' => '🇹🇩 +235 (Chad)' ],
			[ 'code' => '+56', 'label' => '🇨🇱 +56 (Chile)' ],
			[ 'code' => '+86', 'label' => '🇨🇳 +86 (China)' ],
			[ 'code' => '+57', 'label' => '🇨🇴 +57 (Colombia)' ],
			[ 'code' => '+269', 'label' => '🇰🇲 +269 (Comoros)' ],
			[ 'code' => '+242', 'label' => '🇨🇬 +242 (Congo)' ],
			[ 'code' => '+506', 'label' => '🇨🇷 +506 (Costa Rica)' ],
			[ 'code' => '+385', 'label' => '🇭🇷 +385 (Croatia)' ],
			[ 'code' => '+53', 'label' => '🇨🇺 +53 (Cuba)' ],
			[ 'code' => '+357', 'label' => '🇨🇾 +357 (Cyprus)' ],
			[ 'code' => '+420', 'label' => '🇨🇿 +420 (Czech Republic)' ],
			[ 'code' => '+45', 'label' => '🇩🇰 +45 (Denmark)' ],
			[ 'code' => '+253', 'label' => '🇩🇯 +253 (Djibouti)' ],
			[ 'code' => '+1767', 'label' => '🇩🇲 +1767 (Dominica)' ],
			[ 'code' => '+1809', 'label' => '🇩🇴 +1809 (Dominican Republic)' ],
			[ 'code' => '+593', 'label' => '🇪🇨 +593 (Ecuador)' ],
			[ 'code' => '+20', 'label' => '🇪🇬 +20 (Egypt)' ],
			[ 'code' => '+503', 'label' => '🇸🇻 +503 (El Salvador)' ],
			[ 'code' => '+240', 'label' => '🇬🇶 +240 (Equatorial Guinea)' ],
			[ 'code' => '+291', 'label' => '🇪🇷 +291 (Eritrea)' ],
			[ 'code' => '+372', 'label' => '🇪🇪 +372 (Estonia)' ],
			[ 'code' => '+268', 'label' => '🇸🇿 +268 (Eswatini)' ],
			[ 'code' => '+251', 'label' => '🇪🇹 +251 (Ethiopia)' ],
			[ 'code' => '+679', 'label' => '🇫🇯 +679 (Fiji)' ],
			[ 'code' => '+358', 'label' => '🇫🇮 +358 (Finland)' ],
			[ 'code' => '+33', 'label' => '🇫🇷 +33 (France)' ],
			[ 'code' => '+241', 'label' => '🇬🇦 +241 (Gabon)' ],
			[ 'code' => '+220', 'label' => '🇬🇲 +220 (Gambia)' ],
			[ 'code' => '+995', 'label' => '🇬🇪 +995 (Georgia)' ],
			[ 'code' => '+49', 'label' => '🇩🇪 +49 (Germany)' ],
			[ 'code' => '+233', 'label' => '🇬🇭 +233 (Ghana)' ],
			[ 'code' => '+30', 'label' => '🇬🇷 +30 (Greece)' ],
			[ 'code' => '+1473', 'label' => '🇬🇩 +1473 (Grenada)' ],
			[ 'code' => '+502', 'label' => '🇬🇹 +502 (Guatemala)' ],
			[ 'code' => '+224', 'label' => '🇬🇳 +224 (Guinea)' ],
			[ 'code' => '+592', 'label' => '🇬🇾 +592 (Guyana)' ],
			[ 'code' => '+509', 'label' => '🇭🇹 +509 (Haiti)' ],
			[ 'code' => '+504', 'label' => '🇭🇳 +504 (Honduras)' ],
			[ 'code' => '+852', 'label' => '🇭🇰 +852 (Hong Kong)' ],
			[ 'code' => '+36', 'label' => '🇭🇺 +36 (Hungary)' ],
			[ 'code' => '+354', 'label' => '🇮🇸 +354 (Iceland)' ],
			[ 'code' => '+91', 'label' => '🇮🇳 +91 (India)' ],
			[ 'code' => '+62', 'label' => '🇮🇩 +62 (Indonesia)' ],
			[ 'code' => '+98', 'label' => '🇮🇷 +98 (Iran)' ],
			[ 'code' => '+964', 'label' => '🇮🇶 +964 (Iraq)' ],
			[ 'code' => '+353', 'label' => '🇮🇪 +353 (Ireland)' ],
			[ 'code' => '+972', 'label' => '🇮🇱 +972 (Israel)' ],
			[ 'code' => '+39', 'label' => '🇮🇹 +39 (Italy)' ],
			[ 'code' => '+225', 'label' => '🇨🇮 +225 (Ivory Coast)' ],
			[ 'code' => '+1876', 'label' => '🇯🇲 +1876 (Jamaica)' ],
			[ 'code' => '+81', 'label' => '🇯🇵 +81 (Japan)' ],
			[ 'code' => '+962', 'label' => '🇯🇴 +962 (Jordan)' ],
			[ 'code' => '+7', 'label' => '🇰🇿 +7 (Kazakhstan)' ],
			[ 'code' => '+254', 'label' => '🇰🇪 +254 (Kenya)' ],
			[ 'code' => '+965', 'label' => '🇰🇼 +965 (Kuwait)' ],
			[ 'code' => '+996', 'label' => '🇰🇬 +996 (Kyrgyzstan)' ],
			[ 'code' => '+856', 'label' => '🇱🇦 +856 (Laos)' ],
			[ 'code' => '+371', 'label' => '🇱🇻 +371 (Latvia)' ],
			[ 'code' => '+961', 'label' => '🇱🇧 +961 (Lebanon)' ],
			[ 'code' => '+266', 'label' => '🇱🇸 +266 (Lesotho)' ],
			[ 'code' => '+231', 'label' => '🇱🇷 +231 (Liberia)' ],
			[ 'code' => '+218', 'label' => '🇱🇾 +218 (Libya)' ],
			[ 'code' => '+423', 'label' => '🇱🇮 +423 (Liechtenstein)' ],
			[ 'code' => '+370', 'label' => '🇱🇹 +370 (Lithuania)' ],
			[ 'code' => '+352', 'label' => '🇱🇺 +352 (Luxembourg)' ],
			[ 'code' => '+261', 'label' => '🇲🇬 +261 (Madagascar)' ],
			[ 'code' => '+265', 'label' => '🇲🇼 +265 (Malawi)' ],
			[ 'code' => '+60', 'label' => '🇲🇾 +60 (Malaysia)' ],
			[ 'code' => '+960', 'label' => '🇲🇻 +960 (Maldives)' ],
			[ 'code' => '+223', 'label' => '🇲🇱 +223 (Mali)' ],
			[ 'code' => '+356', 'label' => '🇲🇹 +356 (Malta)' ],
			[ 'code' => '+222', 'label' => '🇲🇷 +222 (Mauritania)' ],
			[ 'code' => '+230', 'label' => '🇲🇺 +230 (Mauritius)' ],
			[ 'code' => '+52', 'label' => '🇲🇽 +52 (Mexico)' ],
			[ 'code' => '+373', 'label' => '🇲🇩 +373 (Moldova)' ],
			[ 'code' => '+377', 'label' => '🇲🇨 +377 (Monaco)' ],
			[ 'code' => '+976', 'label' => '🇲🇳 +976 (Mongolia)' ],
			[ 'code' => '+382', 'label' => '🇲🇪 +382 (Montenegro)' ],
			[ 'code' => '+212', 'label' => '🇲🇦 +212 (Morocco)' ],
			[ 'code' => '+258', 'label' => '🇲🇿 +258 (Mozambique)' ],
			[ 'code' => '+95', 'label' => '🇲🇲 +95 (Myanmar)' ],
			[ 'code' => '+264', 'label' => '🇳🇦 +264 (Namibia)' ],
			[ 'code' => '+977', 'label' => '🇳🇵 +977 (Nepal)' ],
			[ 'code' => '+31', 'label' => '🇳🇱 +31 (Netherlands)' ],
			[ 'code' => '+64', 'label' => '🇳🇿 +64 (New Zealand)' ],
			[ 'code' => '+505', 'label' => '🇳🇮 +505 (Nicaragua)' ],
			[ 'code' => '+227', 'label' => '🇳🇪 +227 (Niger)' ],
			[ 'code' => '+234', 'label' => '🇳🇬 +234 (Nigeria)' ],
			[ 'code' => '+389', 'label' => '🇲🇰 +389 (North Macedonia)' ],
			[ 'code' => '+47', 'label' => '🇳🇴 +47 (Norway)' ],
			[ 'code' => '+968', 'label' => '🇴🇲 +968 (Oman)' ],
			[ 'code' => '+92', 'label' => '🇵🇰 +92 (Pakistan)' ],
			[ 'code' => '+970', 'label' => '🇵🇸 +970 (Palestine)' ],
			[ 'code' => '+507', 'label' => '🇵🇦 +507 (Panama)' ],
			[ 'code' => '+675', 'label' => '🇵🇬 +675 (Papua New Guinea)' ],
			[ 'code' => '+595', 'label' => '🇵🇾 +595 (Paraguay)' ],
			[ 'code' => '+51', 'label' => '🇵🇪 +51 (Peru)' ],
			[ 'code' => '+63', 'label' => '🇵🇭 +63 (Philippines)' ],
			[ 'code' => '+48', 'label' => '🇵🇱 +48 (Poland)' ],
			[ 'code' => '+351', 'label' => '🇵🇹 +351 (Portugal)' ],
			[ 'code' => '+974', 'label' => '🇶🇦 +974 (Qatar)' ],
			[ 'code' => '+40', 'label' => '🇷🇴 +40 (Romania)' ],
			[ 'code' => '+7', 'label' => '🇷🇺 +7 (Russia)' ],
			[ 'code' => '+250', 'label' => '🇷🇼 +250 (Rwanda)' ],
			[ 'code' => '+966', 'label' => '🇸🇦 +966 (Saudi Arabia)' ],
			[ 'code' => '+221', 'label' => '🇸🇳 +221 (Senegal)' ],
			[ 'code' => '+381', 'label' => '🇷🇸 +381 (Serbia)' ],
			[ 'code' => '+248', 'label' => '🇸🇨 +248 (Seychelles)' ],
			[ 'code' => '+232', 'label' => '🇸🇱 +232 (Sierra Leone)' ],
			[ 'code' => '+65', 'label' => '🇸🇬 +65 (Singapore)' ],
			[ 'code' => '+421', 'label' => '🇸🇰 +421 (Slovakia)' ],
			[ 'code' => '+386', 'label' => '🇸🇮 +386 (Slovenia)' ],
			[ 'code' => '+252', 'label' => '🇸🇴 +252 (Somalia)' ],
			[ 'code' => '+27', 'label' => '🇿🇦 +27 (South Africa)' ],
			[ 'code' => '+82', 'label' => '🇰🇷 +82 (South Korea)' ],
			[ 'code' => '+34', 'label' => '🇪🇸 +34 (Spain)' ],
			[ 'code' => '+94', 'label' => '🇱🇰 +94 (Sri Lanka)' ],
			[ 'code' => '+249', 'label' => '🇸🇩 +249 (Sudan)' ],
			[ 'code' => '+597', 'label' => '🇸🇷 +597 (Suriname)' ],
			[ 'code' => '+46', 'label' => '🇸🇪 +46 (Sweden)' ],
			[ 'code' => '+41', 'label' => '🇨🇭 +41 (Switzerland)' ],
			[ 'code' => '+963', 'label' => '🇸🇾 +963 (Syria)' ],
			[ 'code' => '+886', 'label' => '🇹🇼 +886 (Taiwan)' ],
			[ 'code' => '+992', 'label' => '🇹🇯 +992 (Tajikistan)' ],
			[ 'code' => '+255', 'label' => '🇹🇿 +255 (Tanzania)' ],
			[ 'code' => '+66', 'label' => '🇹🇭 +66 (Thailand)' ],
			[ 'code' => '+228', 'label' => '🇹🇬 +228 (Togo)' ],
			[ 'code' => '+1868', 'label' => '🇹🇹 +1868 (Trinidad & Tobago)' ],
			[ 'code' => '+216', 'label' => '🇹🇳 +216 (Tunisia)' ],
			[ 'code' => '+90', 'label' => '🇹🇷 +90 (Turkey)' ],
			[ 'code' => '+993', 'label' => '🇹🇲 +993 (Turkmenistan)' ],
			[ 'code' => '+256', 'label' => '🇺🇬 +256 (Uganda)' ],
			[ 'code' => '+380', 'label' => '🇺🇦 +380 (Ukraine)' ],
			[ 'code' => '+971', 'label' => '🇦🇪 +971 (United Arab Emirates)' ],
			[ 'code' => '+44', 'label' => '🇬🇧 +44 (United Kingdom)' ],
			[ 'code' => '+1', 'label' => '🇺🇸 +1 (United States)' ],
			[ 'code' => '+598', 'label' => '🇺🇾 +598 (Uruguay)' ],
			[ 'code' => '+998', 'label' => '🇺🇿 +998 (Uzbekistan)' ],
			[ 'code' => '+58', 'label' => '🇻🇪 +58 (Venezuela)' ],
			[ 'code' => '+84', 'label' => '🇻🇳 +84 (Vietnam)' ],
			[ 'code' => '+967', 'label' => '🇾🇪 +967 (Yemen)' ],
			[ 'code' => '+260', 'label' => '🇿🇲 +260 (Zambia)' ],
			[ 'code' => '+263', 'label' => '🇿🇼 +263 (Zimbabwe)' ],
		];

		$country_items_html = '';
		foreach ( $country_options as $c ) {
			$is_sel = ( '+91' === $c['code'] );
			$country_items_html .= sprintf(
				'<div class="vispan-option-item%s" data-value="%s" data-display="%s" style="padding: 9px 14px; font-size: 13px; color: #2D3748; cursor: pointer; transition: background 0.15s; border-bottom: 1px solid #EDF2F7; %s">%s</div>',
				$is_sel ? ' is-selected' : '',
				esc_attr( $c['code'] ),
				esc_attr( $c['code'] ),
				$is_sel ? 'background: #e6f0f7; font-weight: 700;' : '',
				esc_html( $c['label'] )
			);
		}

		// Service Options
		$service_options = [
			'Digital Marketing',
			'Google Ads',
			'Branding Services',
			'SEO',
			'Web Development',
			'Social Media Management',
			'Online Reputation Management',
			'Video Production',
			'VFX',
			'CGI Services',
		];

		$is_home_services = (bool) preg_match( '/\b(home\s*services?|home\s*cleaning|contractors?|emergency\s*trades?)\b/i', (string) $current_svc );
		if ( $is_home_services ) {
			$service_options = array_merge(
				[
					'Home Services Digital Marketing',
					'HVAC Marketing & SEO',
					'Plumbing Services SEO',
					'Electrical Contractor Marketing',
					'Roofing & Siding Lead Gen',
					'Handyman & Remodeling Marketing',
					'Lawn Care & Landscaping SEO',
					'Cleaning & Maid Services SEO',
					'Pest Control Marketing',
				],
				$service_options
			);
		}

		if ( ! empty( $current_svc ) ) {
			// Check case-insensitive match or synonym mapping to one of the options
			$matched_svc = '';
			foreach ( $service_options as $s_opt ) {
				if ( 0 === strcasecmp( $s_opt, (string) $current_svc ) ) {
					$matched_svc = $s_opt;
					break;
				}
			}
			if ( empty( $matched_svc ) ) {
				if ( stripos( $current_svc, 'Home Services' ) !== false || stripos( $current_svc, 'Contractor' ) !== false || stripos( $current_svc, 'Cleaning' ) !== false ) {
					$matched_svc = 'Home Services Digital Marketing';
				} elseif ( stripos( $current_svc, 'HVAC' ) !== false ) {
					$matched_svc = 'HVAC Marketing & SEO';
				} elseif ( stripos( $current_svc, 'Plumb' ) !== false ) {
					$matched_svc = 'Plumbing Services SEO';
				} elseif ( stripos( $current_svc, 'Electric' ) !== false ) {
					$matched_svc = 'Electrical Contractor Marketing';
				} elseif ( stripos( $current_svc, 'Roof' ) !== false || stripos( $current_svc, 'Siding' ) !== false ) {
					$matched_svc = 'Roofing & Siding Lead Gen';
				} elseif ( stripos( $current_svc, 'Handyman' ) !== false ) {
					$matched_svc = 'Handyman & Remodeling Marketing';
				} elseif ( stripos( $current_svc, 'Lawn' ) !== false || stripos( $current_svc, 'Landscap' ) !== false ) {
					$matched_svc = 'Lawn Care & Landscaping SEO';
				} elseif ( stripos( $current_svc, 'Pest' ) !== false ) {
					$matched_svc = 'Pest Control Marketing';
				} elseif ( stripos( $current_svc, 'SEO' ) !== false || stripos( $current_svc, 'Search Engine' ) !== false ) {
					$matched_svc = 'SEO';
				} elseif ( stripos( $current_svc, 'Digital Marketing' ) !== false || stripos( $current_svc, 'DMA' ) !== false ) {
					$matched_svc = 'Digital Marketing';
				} elseif ( stripos( $current_svc, 'Web' ) !== false ) {
					$matched_svc = 'Web Development';
				} elseif ( stripos( $current_svc, 'Google' ) !== false || stripos( $current_svc, 'Ads' ) !== false || stripos( $current_svc, 'PPC' ) !== false ) {
					$matched_svc = 'Google Ads';
				} elseif ( stripos( $current_svc, 'Branding' ) !== false ) {
					$matched_svc = 'Branding Services';
				} elseif ( stripos( $current_svc, 'Social' ) !== false ) {
					$matched_svc = 'Social Media Management';
				} elseif ( stripos( $current_svc, 'Reputation' ) !== false || stripos( $current_svc, 'ORM' ) !== false ) {
					$matched_svc = 'Online Reputation Management';
				} elseif ( stripos( $current_svc, 'Video' ) !== false ) {
					$matched_svc = 'Video Production';
				} elseif ( stripos( $current_svc, 'VFX' ) !== false ) {
					$matched_svc = 'VFX';
				} elseif ( stripos( $current_svc, 'CGI' ) !== false ) {
					$matched_svc = 'CGI Services';
				}
			}
			if ( ! empty( $matched_svc ) ) {
				$current_svc = $matched_svc;
			}
		}

		$svc_items_html = '';
		$svc_initial_val  = ! empty( $current_svc ) && in_array( $current_svc, $service_options, true ) ? $current_svc : '';
		$svc_initial_text = ! empty( $current_svc ) && in_array( $current_svc, $service_options, true ) ? $current_svc : '';

		foreach ( $service_options as $opt ) {
			$is_sel = ( ! empty( $current_svc ) && 0 === strcasecmp( $opt, (string) $current_svc ) );
			$svc_items_html .= sprintf(
				'<div class="vispan-option-item%s" data-value="%s" data-display="%s" value="%s"%s style="padding: 10px 16px; font-size: 13.5px; color: #2D3748; cursor: pointer; transition: background 0.15s; border-bottom: 1px solid #EDF2F7; %s">%s</div>',
				$is_sel ? ' is-selected' : '',
				esc_attr( $opt ),
				esc_attr( $opt ),
				esc_attr( $opt ),
				$is_sel ? ' selected' : '',
				$is_sel ? 'background: #e6f0f7; font-weight: 700;' : '',
				esc_html( $opt )
			);
		}

		$budget_options = [
			'< $1,000 / month',
			'$1,000 - $3,000 / month',
			'$3,000 - $5,000 / month',
			'$5,000 - $10,000 / month',
			'$10,000+ / month',
		];
		$budget_items_html = '';
		foreach ( $budget_options as $b ) {
			$budget_items_html .= sprintf(
				'<div class="vispan-option-item" data-value="%s" data-display="%s" value="%s" style="padding: 10px 16px; font-size: 13.5px; color: #2D3748; cursor: pointer; transition: background 0.15s; border-bottom: 1px solid #EDF2F7;">%s</div>',
				esc_attr( $b ),
				esc_attr( $b ),
				esc_attr( $b ),
				esc_html( $b )
			);
		}

		$is_bottom = ( 'bottom' === $layout );

		$card_max_width = $is_bottom ? '100%' : '520px';
		$card_padding   = $is_bottom ? '40px 36px' : '24px 24px';
		$grid_layout    = $is_bottom ? 'display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px 18px;' : 'display: flex; flex-direction: column; gap: 10px;';
		$full_span      = $is_bottom ? 'grid-column: 1 / -1;' : '';

		$form_title = $args['title'] ?? 'Request A Marketing Proposal';

		return sprintf(
			'<!-- Vispan Inquiry Form: %s -->
			<div class="vispan-inquiry-card vispan-inquiry-layout-%s" style="background: #FFFFFF; border: 1.5px solid #003865; border-radius: 22px; box-shadow: 0 16px 40px -18px rgba(0,56,101,0.22); padding: %s; max-width: %s; margin: 0 auto; box-sizing: border-box; font-family: \'DM Sans\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; position: relative;">
				<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: clamp(19px, 2.2vw, 24px); font-weight: 800; color: #003865; margin: 0 0 14px; text-align: center; line-height: 1.25; letter-spacing: 0px; word-spacing: 1.5px;">%s</h3>
				
				<form id="%s" method="post" action="%s" onsubmit="return window.vispanSubmitInquiry(event, this);" style="%s">
					<!-- Spam Protection Honeypot -->
					<input type="text" name="inquiry_hp_check" value="" style="display:none !important; visibility:hidden !important; position:absolute; left:-9999px;" tabindex="-1" autocomplete="off" />
					<input type="hidden" name="action" value="seo_city_submit_inquiry" />
					<input type="hidden" name="nonce" value="%s" />
					<input type="hidden" name="page_id" value="%s" />
					<input type="hidden" name="page_url" value="%s" />
					<input type="hidden" name="form_layout" value="%s" />

					<!-- Field: Full Name -->
					<div>
						<input type="text" name="full_name" required placeholder="Full Name*" class="vispan-inquiry-input" style="width: 100%%; height: 42px; padding: 8px 16px; background: #F4F6F8; border: 1.5px solid #767676; border-radius: 50px; font-size: 13.5px; color: #1a202c; font-family: inherit; box-sizing: border-box; outline: none; transition: border-color 0.2s, box-shadow 0.2s;" />
					</div>

					<!-- Field: Email Address -->
					<div>
						<input type="email" name="email" required placeholder="Email Address*" class="vispan-inquiry-input" style="width: 100%%; height: 42px; padding: 8px 16px; background: #F4F6F8; border: 1.5px solid #767676; border-radius: 50px; font-size: 13.5px; color: #1a202c; font-family: inherit; box-sizing: border-box; outline: none; transition: border-color 0.2s, box-shadow 0.2s;" />
					</div>

					<!-- Field: Country Code (Searchable Dropdown) + Contact Number -->
					<div>
						<div style="display: flex; gap: 8px; width: 100%%; position: relative;">
							<div class="vispan-searchable-select vispan-country-select" data-name="country_code" style="position: relative; width: 100px; flex-shrink: 0;">
								<input type="hidden" name="country_code" value="+91" required class="vispan-real-input" />
								<div class="vispan-select-trigger" style="position: relative; width: 100%%;">
									<input type="text" class="vispan-inquiry-input vispan-search-input" placeholder="+91" value="" autocomplete="off" style="width: 100%%; height: 42px; padding: 8px 24px 8px 10px; text-align: center; background: #F4F6F8; border: 1.5px solid #767676; border-radius: 50px; font-size: 13.5px; font-weight: 700; color: #1a202c; font-family: inherit; box-sizing: border-box; outline: none; cursor: pointer;" />
									<span class="vispan-select-arrow" style="position: absolute; right: 8px; top: 50%%; transform: translateY(-50%%); pointer-events: none; color: #555; font-size: 9px; transition: transform 0.2s;">▼</span>
								</div>
								<div class="vispan-select-options" style="display: none; position: absolute; top: calc(100%% + 4px); left: 0; min-width: 270px; max-height: 240px; overflow-y: auto; background: #FFFFFF; border: 1.5px solid #003865; border-radius: 14px; box-shadow: 0 14px 35px -5px rgba(0,56,101,0.28); z-index: 99999; box-sizing: border-box; padding: 4px 0;">
									%s
									<div class="vispan-no-match" style="display: none; padding: 12px 14px; font-size: 12.5px; color: #718096; text-align: center;">No matching country found</div>
								</div>
							</div>
							<input type="tel" name="phone" required placeholder="Contact No.*" class="vispan-inquiry-input" style="flex: 1; min-width: 0; height: 42px; padding: 8px 16px; background: #F4F6F8; border: 1.5px solid #767676; border-radius: 50px; font-size: 13.5px; color: #1a202c; font-family: inherit; box-sizing: border-box; outline: none;" />
						</div>
					</div>

					<!-- Field: Select Service (Searchable Dropdown) -->
					<div>
						<div class="vispan-searchable-select" data-name="service" style="position: relative; width: 100%%;">
							<input type="hidden" name="service" value="%s" required class="vispan-real-input" />
							<div class="vispan-select-trigger" style="position: relative; width: 100%%;">
								<input type="text" class="vispan-inquiry-input vispan-search-input" placeholder="Select or Search Service*" value="%s" autocomplete="off" style="width: 100%%; height: 42px; padding: 8px 36px 8px 16px; background: #F4F6F8; border: 1.5px solid #767676; border-radius: 50px; font-size: 13.5px; color: #1a202c; font-family: inherit; box-sizing: border-box; outline: none; cursor: pointer;" />
								<span class="vispan-select-arrow" style="position: absolute; right: 14px; top: 50%%; transform: translateY(-50%%); pointer-events: none; color: #555; font-size: 11px; transition: transform 0.2s;">▼</span>
							</div>
							<div class="vispan-select-options" style="display: none; position: absolute; top: calc(100%% + 4px); left: 0; right: 0; max-height: 220px; overflow-y: auto; background: #FFFFFF; border: 1.5px solid #003865; border-radius: 14px; box-shadow: 0 12px 28px rgba(0,56,101,0.18); z-index: 9999; box-sizing: border-box; padding: 4px 0;">
								%s
								<div class="vispan-no-match" style="display: none; padding: 10px 16px; font-size: 12.5px; color: #A0AEC0; text-align: center;">No matching services</div>
							</div>
						</div>
					</div>

					<!-- Field: Company Name -->
					<div>
						<input type="text" name="company_name" required placeholder="Company Name*" class="vispan-inquiry-input" style="width: 100%%; height: 42px; padding: 8px 16px; background: #F4F6F8; border: 1.5px solid #767676; border-radius: 50px; font-size: 13.5px; color: #1a202c; font-family: inherit; box-sizing: border-box; outline: none;" />
					</div>

					<!-- Field: Website/Landing Page -->
					<div>
						<input type="text" name="website" required placeholder="Website/Landing Page*" class="vispan-inquiry-input" style="width: 100%%; height: 42px; padding: 8px 16px; background: #F4F6F8; border: 1.5px solid #767676; border-radius: 50px; font-size: 13.5px; color: #1a202c; font-family: inherit; box-sizing: border-box; outline: none;" />
					</div>

					<!-- Field: Budget Spent In Last 30 Days? (Searchable Dropdown) -->
					<div style="%s">
						<div class="vispan-searchable-select" data-name="budget" style="position: relative; width: 100%%;">
							<input type="hidden" name="budget" value="" required class="vispan-real-input" />
							<div class="vispan-select-trigger" style="position: relative; width: 100%%;">
								<input type="text" class="vispan-inquiry-input vispan-search-input" placeholder="Budget Spent In Last 30 Days?*" value="" autocomplete="off" style="width: 100%%; height: 42px; padding: 8px 36px 8px 16px; background: #F4F6F8; border: 1.5px solid #767676; border-radius: 50px; font-size: 13.5px; color: #1a202c; font-family: inherit; box-sizing: border-box; outline: none; cursor: pointer;" />
								<span class="vispan-select-arrow" style="position: absolute; right: 14px; top: 50%%; transform: translateY(-50%%); pointer-events: none; color: #555; font-size: 11px; transition: transform 0.2s;">▼</span>
							</div>
							<div class="vispan-select-options" style="display: none; position: absolute; top: calc(100%% + 4px); left: 0; right: 0; max-height: 220px; overflow-y: auto; background: #FFFFFF; border: 1.5px solid #003865; border-radius: 14px; box-shadow: 0 12px 28px rgba(0,56,101,0.18); z-index: 9999; box-sizing: border-box; padding: 4px 0;">
								%s
								<div class="vispan-no-match" style="display: none; padding: 10px 16px; font-size: 12.5px; color: #A0AEC0; text-align: center;">No matching budget range</div>
							</div>
						</div>
					</div>

					<!-- Field: Additional Details/Purpose of Business -->
					<div style="%s">
						<textarea name="details" placeholder="Additional Details/Purpose of Business" class="vispan-inquiry-textarea" rows="2" style="width: 100%%; padding: 10px 16px; background: #F4F6F8; border: 1.5px solid #767676; border-radius: 18px; font-size: 13.5px; color: #1a202c; font-family: inherit; box-sizing: border-box; outline: none; min-height: 60px; resize: vertical; display: block;"></textarea>
					</div>

					<!-- Submit Button -->
					<div style="%s margin-top: 2px;">
						<button type="submit" class="vispan-inquiry-btn" style="width: 100%%; height: 46px; background: #003865; color: #FFFFFF; border: none; border-radius: 50px; font-size: 15px; font-weight: 700; font-family: inherit; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 8px 20px -5px rgba(0,56,101,0.45); transition: background-color 0.2s, transform 0.2s;">
							<span>Request a marketing Proposal</span>
						</button>
					</div>

					<!-- Status / Feedback Container -->
					<div class="vispan-inquiry-status" style="%s display: none; margin-top: 8px; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; text-align: center; line-height: 1.4;"></div>
				</form>
			</div>
			
			<script>
			(function() {
				function initSearchableSelects(root) {
					var container = root || document;
					var selects = container.querySelectorAll(\'.vispan-searchable-select\');
					selects.forEach(function(selectEl) {
						if (selectEl.dataset.initialized === \'true\') return;
						selectEl.dataset.initialized = \'true\';

						var searchInput = selectEl.querySelector(\'.vispan-search-input\');
						var hiddenInput = selectEl.querySelector(\'.vispan-real-input\');
						var optionsMenu = selectEl.querySelector(\'.vispan-select-options\');
						var arrow = selectEl.querySelector(\'.vispan-select-arrow\');
						var optionItems = selectEl.querySelectorAll(\'.vispan-option-item\');
						var noMatch = selectEl.querySelector(\'.vispan-no-match\');

						function openMenu() {
							document.querySelectorAll(\'.vispan-select-options\').forEach(function(m) {
								if (m !== optionsMenu) m.style.display = \'none\';
							});
							document.querySelectorAll(\'.vispan-select-arrow\').forEach(function(a) {
								if (a !== arrow) a.style.transform = \'translateY(-50%%) rotate(0deg)\';
							});
							optionsMenu.style.display = \'block\';
							if (arrow) arrow.style.transform = \'translateY(-50%%) rotate(180deg)\';
							// Always show all options when opening dropdown so user can see every available country / service
							filterOptions(\'\');
						}

						function closeMenu() {
							optionsMenu.style.display = \'none\';
							if (arrow) arrow.style.transform = \'translateY(-50%%) rotate(0deg)\';
						}

						function filterOptions(query) {
							var q = (query || \'\').toLowerCase().trim();
							var visibleCount = 0;
							optionItems.forEach(function(item) {
								var text = (item.textContent || \'\').toLowerCase();
								var val = (item.getAttribute(\'data-value\') || \'\').toLowerCase();
								if (!q || text.indexOf(q) !== -1 || val.indexOf(q) !== -1) {
									item.style.display = \'block\';
									visibleCount++;
								} else {
									item.style.display = \'none\';
								}
							});
							if (noMatch) {
								noMatch.style.display = visibleCount === 0 ? \'block\' : \'none\';
							}
						}

						searchInput.addEventListener(\'focus\', function() {
							openMenu();
							if (searchInput.value) {
								searchInput.select();
							}
						});

						searchInput.addEventListener(\'click\', function(e) {
							e.stopPropagation();
							openMenu();
							if (searchInput.value) {
								searchInput.select();
							}
						});

						selectEl.querySelector(\'.vispan-select-trigger\').addEventListener(\'click\', function(e) {
							e.stopPropagation();
							if (optionsMenu.style.display === \'block\') {
								closeMenu();
							} else {
								searchInput.focus();
								openMenu();
							}
						});

						searchInput.addEventListener(\'input\', function() {
							if (hiddenInput) {
								hiddenInput.value = searchInput.value;
							}
							openMenu();
							filterOptions(searchInput.value.trim());
						});

						optionItems.forEach(function(item) {
							item.addEventListener(\'click\', function(e) {
								e.stopPropagation();
								var val = item.getAttribute(\'data-value\');
								var text = item.getAttribute(\'data-display\') || item.textContent.trim();
								
								if (hiddenInput) {
									hiddenInput.value = val;
								}
								searchInput.value = text;
								
								optionItems.forEach(function(it) {
									it.classList.remove(\'is-selected\');
									it.style.background = \'transparent\';
									it.style.fontWeight = \'normal\';
								});
								item.classList.add(\'is-selected\');
								item.style.background = \'#e6f0f7\';
								item.style.fontWeight = \'700\';

								closeMenu();
								
								var evt = new Event(\'change\', { bubbles: true });
								if (hiddenInput) hiddenInput.dispatchEvent(evt);
							});

							item.addEventListener(\'mouseenter\', function() {
								if (!item.classList.contains(\'is-selected\')) {
									item.style.background = \'#F4F6F8\';
								}
							});
							item.addEventListener(\'mouseleave\', function() {
								if (!item.classList.contains(\'is-selected\')) {
									item.style.background = \'transparent\';
								}
							});
						});
					});
				}

				if (!window.vispanGlobalSelectCloseBound) {
					window.vispanGlobalSelectCloseBound = true;
					document.addEventListener(\'click\', function(e) {
						if (!e.target.closest(\'.vispan-searchable-select\')) {
							document.querySelectorAll(\'.vispan-select-options\').forEach(function(m) {
								m.style.display = \'none\';
							});
							document.querySelectorAll(\'.vispan-select-arrow\').forEach(function(a) {
								a.style.transform = \'translateY(-50%%) rotate(0deg)\';
							});
						}
					});
				}

				if (document.readyState === \'loading\') {
					document.addEventListener(\'DOMContentLoaded\', function() { initSearchableSelects(); });
				} else {
					initSearchableSelects();
				}
			})();

			if (!window.vispanSubmitInquiry) {
				window.vispanSubmitInquiry = function(e, form) {
					e.preventDefault();
					var btn = form.querySelector(\'button[type="submit"]\');
					var statusDiv = form.querySelector(\'.vispan-inquiry-status\');
					var originalBtnText = btn.innerHTML;

					btn.disabled = true;
					btn.style.opacity = \'0.75\';
					btn.innerHTML = \'<span>Submitting Request...</span>\';
					statusDiv.style.display = \'none\';
					statusDiv.className = \'vispan-inquiry-status\';

					var targetUrl = form.getAttribute(\'action\') || \'/wp-admin/admin-ajax.php\';
					try {
						var parsed = new URL(targetUrl, window.location.href);
						targetUrl = window.location.origin + parsed.pathname + parsed.search;
					} catch(ex) {
						targetUrl = window.location.origin + \'/wp-admin/admin-ajax.php\';
					}

					var formData = new FormData(form);

					fetch(targetUrl, {
						method: \'POST\',
						body: formData,
						headers: {
							\'X-Requested-With\': \'XMLHttpRequest\'
						},
						credentials: \'same-origin\'
					})
					.then(function(res) {
						return res.text().then(function(text) {
							var json = null;
							try {
								json = JSON.parse(text);
							} catch (parseErr) {
								console.warn(\'Inquiry form non-JSON response:\', text);
							}
							return { ok: res.ok, status: res.status, json: json, text: text };
						});
					})
					.then(function(result) {
						btn.disabled = false;
						btn.style.opacity = \'1\';
						btn.innerHTML = originalBtnText;

						var data = result.json;
						if (data && data.success) {
							statusDiv.style.display = \'block\';
							statusDiv.style.background = \'#e6f7ec\';
							statusDiv.style.border = \'1.5px solid #00a32a\';
							statusDiv.style.color = \'#0d6824\';
							statusDiv.innerHTML = \'✓ \' + (data.data && data.data.message ? data.data.message : \'Thank you! Your proposal request has been received.\');
							form.reset();
							form.querySelectorAll(\'.vispan-searchable-select\').forEach(function(sel) {
								var si = sel.querySelector(\'.vispan-search-input\');
								var hi = sel.querySelector(\'.vispan-real-input\');
								if (sel.classList.contains(\'vispan-country-select\')) {
									if (si) si.value = \'\';
									if (hi) hi.value = \'+91\';
								}
							});
						} else {
							var errMsg = \'An error occurred. Please check required fields.\';
							if (data && data.data && data.data.message) {
								errMsg = data.data.message;
							} else if (data && data.data && data.data.errors && data.data.errors.length) {
								errMsg = data.data.errors.join(\'<br>\');
							} else if (result.text && result.text.length < 200 && result.text.indexOf(\'<html\') === -1) {
								errMsg = result.text;
							}
							statusDiv.style.display = \'block\';
							statusDiv.style.background = \'#fde8e8\';
							statusDiv.style.border = \'1.5px solid #d32f2f\';
							statusDiv.style.color = \'#9b1c1c\';
							statusDiv.innerHTML = \'⚠ \' + errMsg;
						}
					})
					.catch(function(err) {
						btn.disabled = false;
						btn.style.opacity = \'1\';
						btn.innerHTML = originalBtnText;
						console.error(\'Inquiry submission error:\', err);
						statusDiv.style.display = \'block\';
						statusDiv.style.background = \'#fde8e8\';
						statusDiv.style.border = \'1.5px solid #d32f2f\';
						statusDiv.style.color = \'#9b1c1c\';
						statusDiv.innerHTML = \'⚠ Network connection failed. Please try again.\';
					});

					return false;
				};
			}
			</script>',
			esc_html( $layout ),
			esc_attr( $layout ),
			$card_padding,
			$card_max_width,
			esc_html( $form_title ),
			esc_attr( $form_id ),
			esc_url( $ajax_url ),
			$grid_layout,
			esc_attr( $nonce ),
			esc_attr( (string) $page_id ),
			esc_url( (string) $page_url ),
			esc_attr( $layout ),
			$country_items_html,
			esc_attr( $svc_initial_val ),
			esc_attr( $svc_initial_text ),
			$svc_items_html,
			$full_span,
			$budget_items_html,
			$full_span,
			$full_span,
			$full_span
		);
	}

	/**
	 * Render 6 Agency Growth Capabilities that Vispan Solutions provides for Home Service Contractors.
	 *
	 * @return string HTML output.
	 */
	public static function render_home_services_agency_capabilities_html(): string {
		$city  = Page_Context::get_city();
		$state = Page_Context::get_state();

		$capabilities = [
			[
				'num'         => '01',
				'tag'         => 'ORGANIC MAP PACK DOMINATION',
				'title'       => 'Google Maps 3-Pack & Local SEO',
				'description' => sprintf( 'We optimize your Google Business Profile, localized entity schema, and local neighborhood citations so your service trucks rank #1 across all target zip codes in %s.', esc_html( $city ) ),
				'deliverable' => sprintf( 'Top 3-Pack rankings across %s service radius', esc_html( $city ) ),
				'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>',
			],
			[
				'num'         => '02',
				'tag'         => 'TOP OF GOOGLE • PAY-PER-CALL',
				'title'       => 'Google Guaranteed & Local Services Ads (LSA)',
				'description' => 'Fast-track verification for Google\'s official green checkmark badge. Appear above all traditional search ads and pay strictly for qualified, inbound phone calls from local homeowners.',
				'deliverable' => 'Google Guaranteed badge onboarding & dispute management',
				'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>',
			],
			[
				'num'         => '03',
				'tag'         => 'MOBILE-FIRST DISPATCH ENGINE',
				'title'       => 'High-Converting Contractor Web Funnels',
				'description' => 'Ultra-fast, mobile-first websites designed for homeowners with urgent repair needs. Engineered with instant click-to-call bars, quote estimators, and frictionless booking flows.',
				'deliverable' => 'Sub-second mobile speed & 24/7 call triggers',
				'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>',
			],
			[
				'num'         => '04',
				'tag'         => 'REPUTATION ACCELERATOR',
				'title'       => 'Automated 5-Star Review Generation',
				'description' => 'Automated post-service SMS and WhatsApp review triggers sent to homeowners the moment your technician marks a job complete, building unbeatable local social proof.',
				'deliverable' => 'Compound 5-star review velocity on Google',
				'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
			],
			[
				'num'         => '05',
				'tag'         => 'EMERGENCY CALL CAPTURE',
				'title'       => 'High-Ticket Emergency PPC Campaigns',
				'description' => 'Precision Google Search Ads targeting high-margin emergency jobs (burst pipes, blown AC compressors, roof storm damage, electrical panel failures) with zero wasted budget.',
				'deliverable' => 'Negative keyword filtering & immediate dayparting',
				'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
			],
			[
				'num'         => '06',
				'tag'         => 'DISPATCH CRM INTEGRATION',
				'title'       => 'Call Tracking & Dispatch CRM Sync',
				'description' => 'Every inbound phone call is recorded, transcribed, and tagged with lead source data. We integrate seamlessly with ServiceTitan, Housecall Pro, Jobber, and FieldEdge for closed-loop ROI.',
				'deliverable' => 'Full revenue attribution & CRM automation',
				'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#02426A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
			],
		];

		// Tier 1: Primary Search Dominance (Engines 01 & 02)
		$tier1_html = '';
		for ( $i = 0; $i < 2; $i++ ) {
			$c = $capabilities[ $i ];
			$accent_border = ( 0 === $i ) ? 'border-top: 3.5px solid #02426A;' : 'border-top: 3.5px solid #0B63F6;';
			$badge_bg      = ( 0 === $i ) ? 'background: #EBF3FB; color: #02426A; border: 1px solid #CCE0ED;' : 'background: #F0F6FF; color: #0B63F6; border: 1px solid #DBEAFE;';
			$badge_label   = ( 0 === $i ) ? 'PRIMARY SEARCH FOUNDATION • ENGINE 01' : 'TOP OF GOOGLE • ENGINE 02';

			$tier1_html .= sprintf(
				'<div class="vispan-capability-card vispan-capability-featured" style="background: #FFFFFF; border: 1px solid #E2E8F0; %s border-radius: 16px; padding: 32px 28px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 16px rgba(2,66,106,0.04); transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;" onmouseover="this.style.transform=\'translateY(-3px)\';this.style.boxShadow=\'0 14px 32px -8px rgba(2,66,106,0.12)\';" onmouseout="this.style.transform=\'translateY(0)\';this.style.boxShadow=\'0 4px 16px rgba(2,66,106,0.04)\';">
					<div>
						<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
							<div style="width: 46px; height: 46px; border-radius: 10px; background: #F8FAFC; border: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: center; color: #02426A;">
								%s
							</div>
							<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11.5px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; padding: 4px 10px; border-radius: 6px; %s">%s</span>
						</div>
						<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11.5px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #0B63F6; margin-bottom: 8px;">%s</div>
						<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 12px; line-height: 1.3;">%s</h3>
						<p style="font-family: \'DM Sans\', sans-serif; font-size: 15px; line-height: 1.62; color: #475569; margin: 0 0 22px;">%s</p>
					</div>
					<div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-left: 3px solid #059669; border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; gap: 10px;">
						<span style="color: #059669; font-weight: 900; font-size: 15px; line-height: 1;">✓</span>
						<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 13px; font-weight: 700; color: #02426A; line-height: 1.4;">%s</span>
					</div>
				</div>',
				$accent_border,
				$c['icon'],
				$badge_bg,
				esc_html( $badge_label ),
				esc_html( $c['tag'] ),
				esc_html( $c['title'] ),
				esc_html( $c['description'] ),
				esc_html( $c['deliverable'] )
			);
		}

		// Tier 2: Conversion, Velocity & CRM Operations (Engines 03, 04, 05, 06)
		$tier2_html = '';
		for ( $i = 2; $i < 6; $i++ ) {
			$c = $capabilities[ $i ];
			$tier2_html .= sprintf(
				'<div class="vispan-capability-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 26px 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;" onmouseover="this.style.transform=\'translateY(-3px)\';this.style.boxShadow=\'0 12px 26px -8px rgba(2,66,106,0.1)\';this.style.borderColor=\'#02426A\';" onmouseout="this.style.transform=\'translateY(0)\';this.style.boxShadow=\'0 2px 8px rgba(0,0,0,0.02)\';this.style.borderColor=\'#E2E8F0\';">
					<div>
						<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
							<div style="width: 40px; height: 40px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: center; color: #02426A;">
								%s
							</div>
							<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #02426A; background: #F1F5F9; border: 1px solid #E2E8F0; padding: 3px 9px; border-radius: 5px;">ENGINE %s</span>
						</div>
						<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11.5px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #0B63F6; margin-bottom: 6px;">%s</div>
						<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 17.5px; font-weight: 800; color: #0F172A; margin: 0 0 8px; line-height: 1.3;">%s</h3>
						<p style="font-family: \'DM Sans\', sans-serif; font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 18px;">%s</p>
					</div>
					<div style="padding-top: 14px; border-top: 1px solid #F1F5F9; display: flex; align-items: flex-start; gap: 8px;">
						<span style="color: #059669; font-weight: 900; font-size: 14px; line-height: 1.3;">✓</span>
						<span style="font-family: \'DM Sans\', sans-serif; font-size: 13px; font-weight: 700; color: #02426A; line-height: 1.4;">%s</span>
					</div>
				</div>',
				$c['icon'],
				esc_html( $c['num'] ),
				esc_html( $c['tag'] ),
				esc_html( $c['title'] ),
				esc_html( $c['description'] ),
				esc_html( $c['deliverable'] )
			);
		}

		return sprintf(
			'<div class="scp-agency-services-section" style="max-width: 1200px; margin: 0 auto; padding: 75px 20px;">
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%%, 460px), 1fr)); gap: 24px 48px; align-items: flex-end; margin-bottom: 38px; padding-bottom: 24px; border-bottom: 1.5px solid #F1F5F9; font-family: \'Plus Jakarta Sans\', sans-serif;">
					<div>
						<div style="display: inline-flex; align-items: center; gap: 6px; background: #EBF3FB; border: 1px solid #CCE0ED; border-radius: 30px; padding: 5px 14px; margin-bottom: 14px;">
							<span style="font-size: 11px; font-weight: 800; color: #02426A; letter-spacing: 0.14em; text-transform: uppercase;">WHAT WE DO FOR CONTRACTORS</span>
						</div>
						<h2 style="font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #0F172A; line-height: 1.25; margin: 0;">6 Growth Engines We Build to Keep Your Trucks Booked</h2>
					</div>
					<div>
						<p style="font-family: \'DM Sans\', sans-serif; font-size: 15.5px; line-height: 1.65; color: #475569; margin: 0;">We don\'t do cookie-cutter marketing. Here is the exact performance infrastructure we deploy to scale residential contractors across %s.</p>
					</div>
				</div>

				<!-- Tier 1: Search Dominance Anchors -->
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%%, 460px), 1fr)); gap: 24px; margin-bottom: 28px;">
					%s
				</div>

				<!-- Pipeline Connector Divider -->
				<div style="display: flex; align-items: center; gap: 16px; margin: 32px 0 24px; font-family: \'Plus Jakarta Sans\', sans-serif;">
					<span style="font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #64748B;">CONVERSION, REPUTATION &amp; DISPATCH ATTRIBUTION PIPELINE</span>
					<div style="flex: 1; height: 1px; background: #E2E8F0;"></div>
				</div>

				<!-- Tier 2: Operational Acceleration Grid -->
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%%, 255px), 1fr)); gap: 20px;">
					%s
				</div>
			</div>',
			esc_html( $city ),
			$tier1_html,
			$tier2_html
		);
	}

	/**
	 * Render Vispan vs. Shared Lead Marketplaces (Angi, HomeAdvisor) Comparison Table.
	 *
	 * @return string HTML output.
	 */
	public static function render_home_services_comparison_html(): string {
		$city = Page_Context::get_city();

		$rows = [
			[
				'metric'      => 'Lead Exclusivity',
				'marketplaces'=> 'Shared with 4 to 6 competing contractors simultaneously',
				'vispan'      => '100% Exclusive — only your dispatch receives the call',
			],
			[
				'metric'      => 'Customer Intent',
				'marketplaces'=> 'Tire-kickers price shopping for the absolute cheapest bid',
				'vispan'      => 'Motivated homeowners calling your brand directly with urgent repair needs',
			],
			[
				'metric'      => 'Territory Protection',
				'marketplaces'=> 'None — they sell identical leads to anyone with a credit card',
				'vispan'      => sprintf( 'Guaranteed local territory exclusivity across %s', esc_html( $city ) ),
			],
			[
				'metric'      => 'Asset Ownership',
				'marketplaces'=> 'You rent access to their platform; your marketing builds their domain',
				'vispan'      => 'You own 100% of your Google rankings, customer database, reviews, and digital equity',
			],
			[
				'metric'      => 'Lead Delivery Format',
				'marketplaces'=> 'SMS blast forcing you to race competitors to call back within 60 seconds',
				'vispan'      => 'Direct inbound phone calls connected immediately to your technicians or dispatch team',
			],
		];

		$tbody = '';
		foreach ( $rows as $idx => $r ) {
			$bg = ( $idx % 2 === 0 ) ? '#FFFFFF' : '#F8FAFC';
			$tbody .= sprintf(
				'<tr style="background: %s; border-bottom: 1px solid #E2E8F0;">
					<td style="padding: 18px 22px; font-weight: 800; font-size: 14.5px; color: #0F172A; font-family: \'Plus Jakarta Sans\', sans-serif;">%s</td>
					<td style="padding: 18px 22px; font-size: 14px; color: #64748B; font-family: \'DM Sans\', sans-serif; line-height: 1.5;">
						<span style="color: #94A3B8; font-weight: 800; margin-right: 8px;">✕</span>%s
					</td>
					<td style="padding: 18px 22px; font-size: 14px; color: #02426A; font-weight: 700; font-family: \'DM Sans\', sans-serif; background: #F0F6FA; line-height: 1.5;">
						<span style="color: #059669; font-weight: 900; margin-right: 8px;">✓</span>%s
					</td>
				</tr>',
				esc_attr( $bg ),
				esc_html( $r['metric'] ),
				esc_html( $r['marketplaces'] ),
				esc_html( $r['vispan'] )
			);
		}

		return sprintf(
			'<div class="scp-contractor-comparison-section" style="max-width: 1100px; margin: 0 auto; padding: 70px 20px;">
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%%, 460px), 1fr)); gap: 24px 48px; align-items: flex-end; margin-bottom: 36px; padding-bottom: 24px; border-bottom: 1.5px solid #F1F5F9; font-family: \'Plus Jakarta Sans\', sans-serif;">
					<div>
						<div style="display: inline-flex; align-items: center; gap: 6px; background: #EBF3FB; border: 1px solid #CCE0ED; border-radius: 30px; padding: 5px 14px; margin-bottom: 14px;">
							<span style="font-size: 11px; font-weight: 800; color: #02426A; letter-spacing: 0.14em; text-transform: uppercase;">PRIVATE TERRITORY VS. SHARED DIRECTORIES</span>
						</div>
						<h2 style="font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #0F172A; line-height: 1.25; margin: 0;">Why Contractors Ditch Angi &amp; HomeAdvisor for Vispan</h2>
					</div>
					<div>
						<p style="font-family: \'DM Sans\', sans-serif; font-size: 15.5px; line-height: 1.65; color: #475569; margin: 0;">Shared lead platforms force you into a race-to-the-bottom price war. Here is how our private territory engine changes the game.</p>
					</div>
				</div>
				<div class="vispan-comparison-table-wrapper" style="border: 1.5px solid #E2E8F0; border-radius: 16px; overflow-x: auto; -webkit-overflow-scrolling: touch; box-shadow: 0 10px 30px rgba(2,66,106,0.06); background: #FFFFFF;">
					<table style="width: 100%%; min-width: 680px; border-collapse: collapse; text-align: left;">
						<thead>
							<tr style="background: #02426A; color: #FFFFFF; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 13.5px; text-transform: uppercase; letter-spacing: 0.08em;">
								<th style="padding: 18px 22px; width: 26%%;">Growth Metric</th>
								<th style="padding: 18px 22px; width: 37%%; color: #CBD5E1; background: #002D52;">Shared Directories (Angi / Thumbtack)</th>
								<th style="padding: 18px 22px; width: 37%%; background: #00223F;">
									<div style="display: flex; align-items: center; justify-content: space-between;">
										<span>Vispan Private Engine</span>
										<span style="background: #059669; color: #FFFFFF; font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 12px; letter-spacing: 0.05em;">100%% EXCLUSIVE</span>
									</div>
								</th>
							</tr>
						</thead>
						<tbody>
							%s
						</tbody>
					</table>
				</div>
			</div>',
			$tbody
		);
	}

	/**
	 * Render Contractor-Specific Frequently Asked Questions Accordion.
	 *
	 * @return string HTML output.
	 */
	public static function render_home_services_faqs_html(): string {
		$city  = Page_Context::get_city();
		$state = Page_Context::get_state();

		$faqs = [
			[
				'q' => sprintf( 'What digital marketing services does Vispan Solutions provide for home service contractors in %s?', esc_html( $city ) ),
				'a' => 'We provide a complete 360° digital solution in-house: Digital Marketing, Google Ads, Branding Services, SEO, Web Development, Social Media Management, Online Reputation Management, Video Production, VFX, and CGI Services tailored for home service contractors.',
			],
			[
				'q' => sprintf( 'Why do home service companies in %s need professional digital marketing?', esc_html( $city ) ),
				'a' => sprintf( 'Over 85%% of homeowners in %s turn directly to Google Search and Google Maps when hiring contractors for urgent repairs or renovations. Localized SEO and high-ROI Google Ads protect your business from losing high-ticket jobs to national directory aggregators.', esc_html( $city ) ),
			],
			[
				'q' => 'Why should you contact and partner with Vispan Solutions for these services?',
				'a' => 'Empowering brands since 2012, Vispan Solutions has delivered 150+ projects with a 99% success rate, a 4.8★ rating across 140+ reviews, and 12+ industry awards including Best SEO and Digital Marketer. We offer strict territory exclusivity (one contractor per trade), 100% exclusive inbound calls, and dedicated in-house campaign management with transparent ROI tracking.',
			],
			[
				'q' => sprintf( 'How does territory exclusivity work in %s?', esc_html( $city ) ),
				'a' => sprintf( 'We operate under a strict non-compete territory policy. We partner with only ONE contractor per trade (e.g., 1 HVAC company, 1 Roofing company, 1 Plumber) in %s. All inbound calls and search rankings in your designated market are routed exclusively to your business.', esc_html( $city ) ),
			],
			[
				'q' => 'Are these leads ever shared with or resold to another contractor?',
				'a' => 'Never. Unlike Angi, Thumbtack, or HomeAdvisor, our leads are 100% exclusive direct phone calls from local homeowners in need of repair or installation. You will never compete with 5 other contractors rushing to undercut your price.',
			],
			[
				'q' => 'How quickly can our Google Guaranteed (LSA) and Maps campaigns launch?',
				'a' => 'Google Guaranteed (LSA) campaigns typically launch within 7 to 14 business days once background checks and insurance verifications are submitted. Local SEO and Google Maps optimization begins immediately on Day 1.',
			],
		];

		$items_html = '';
		foreach ( $faqs as $idx => $f ) {
			$items_html .= sprintf(
				'<div class="vispan-faq-item" style="background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 14px; margin-bottom: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: border-color 0.2s ease, box-shadow 0.2s ease;">
					<details style="padding: 20px 24px; cursor: pointer;">
						<summary style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 16.5px; font-weight: 800; color: #0F172A; list-style: none; display: flex; justify-content: space-between; align-items: center; gap: 14px;">
							<span>%s</span>
							<span class="vispan-faq-icon" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%%; background: #F0F6FA; color: #02426A; flex-shrink: 0; transition: transform 0.25s ease, background 0.25s ease;">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="vispan-faq-chevron"><polyline points="6 9 12 15 18 9"/></svg>
							</span>
						</summary>
						<p style="font-family: \'DM Sans\', sans-serif; font-size: 15px; line-height: 1.65; color: #475569; margin: 16px 0 0; padding-top: 14px; border-top: 1px solid #F1F5F9;">%s</p>
					</details>
				</div>',
				esc_html( $f['q'] ),
				esc_html( $f['a'] )
			);
		}

		return sprintf(
			'<div class="scp-contractor-faqs-section" style="max-width: 920px; margin: 0 auto; padding: 70px 20px;">
				<div style="margin-bottom: 36px; font-family: \'Plus Jakarta Sans\', sans-serif;">
					<div style="display: inline-flex; align-items: center; gap: 6px; background: #EBF3FB; border: 1px solid #CCE0ED; border-radius: 30px; padding: 5px 14px; margin-bottom: 14px;">
						<span style="font-size: 11px; font-weight: 800; color: #02426A; letter-spacing: 0.14em; text-transform: uppercase;">CONTRACTOR QUESTIONS ANSWERED</span>
					</div>
					<h2 style="font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #0F172A; margin: 0 0 10px;">Frequently Asked Questions in %s</h2>
					<p style="font-family: \'DM Sans\', sans-serif; font-size: 15.5px; line-height: 1.6; color: #475569; margin: 0; max-width: 720px;">Everything you need to know about our territory exclusivity, lead quality, and onboarding process.</p>
				</div>
				<div>
					%s
				</div>
			</div>',
			esc_html( $city ),
			$items_html
		);
	}

	/**
	 * Render the complete, developer-crafted Home Services Multi-Trade Landing Page.
	 *
	 * Uses the official Vispan Solutions master template with Tailwind CSS styling,
	 * 10 official Vispan agency services, 6 residential trade pillars, verified reviews,
	 * global offices, and AJAX-connected lead forms.
	 *
	 * @return string Complete high-converting HTML layout.
	 */
	public static function render_home_services_full_page_html(): string {
		$city    = Page_Context::get_city();
		if ( empty( $city ) ) {
			$city = 'Rajkot';
		}
		$state   = Page_Context::get_state();
		$country = Page_Context::get_country();
		$service = Page_Context::get_service();
		if ( empty( $service ) ) {
			$service = 'Home Services';
		}

		$state_display = ! empty( $state ) ? $state : '';

		// Dynamic submarket string
		$content_data = self::get_active_content();
		$submarkets   = $content_data['submarkets'] ?? [];
		$cities_list  = [];
		if ( ! empty( $submarkets ) && is_array( $submarkets ) ) {
			foreach ( array_slice( $submarkets, 0, 3 ) as $sm ) {
				if ( ! empty( $sm['name'] ) ) {
					$cities_list[] = $sm['name'];
				}
			}
		}
		if ( ! empty( $cities_list ) ) {
			if ( count( $cities_list ) === 1 ) {
				$cities_str = $cities_list[0];
			} elseif ( count( $cities_list ) === 2 ) {
				$cities_str = $cities_list[0] . ' & ' . $cities_list[1];
			} else {
				$cities_str = $cities_list[0] . ', ' . $cities_list[1] . ' & ' . $cities_list[2];
			}
		} else {
			$cities_str = sprintf( 'key neighborhoods across %s', $city );
		}

		// Dynamic AI Content extraction with fallbacks
		$has_ai_content      = ! empty( $content_data );
		$is_ai_hero_title    = ! empty( $content_data['hero']['title'] );
		$hero_title          = self::get_hero_title();
		$is_ai_hero_desc     = ! empty( $content_data['hero']['description'] );
		$hero_description    = self::get_hero_description();
		$hero_subtitle       = self::get_hero_subtitle();
		$hero_cta            = '';
		if ( ! empty( $content_data['hero']['cta_text'] ) ) {
			$hero_cta = (string) $content_data['hero']['cta_text'];
		} elseif ( ! empty( $content_data['hero']['cta'] ) ) {
			$hero_cta = (string) $content_data['hero']['cta'];
		}
		$is_ai_intro_heading = ! empty( $content_data['introduction']['heading'] );
		$intro_heading       = self::get_intro_heading();
		$is_ai_intro_body    = ! empty( $content_data['introduction']['body'] );
		$intro_body          = self::get_intro_body();
		$service_overview_h  = self::get_service_overview_heading();
		$service_overview_b  = self::get_service_overview_body();
		$final_cta_head      = self::get_final_cta_heading();
		$final_cta_desc      = self::get_final_cta_description();
		$final_cta_btn       = self::get_final_cta_button();
		$ai_process          = ! empty( $content_data['process'] ) && is_array( $content_data['process'] ) ? $content_data['process'] : [];
		$ai_faqs             = ! empty( $content_data['faqs'] ) && is_array( $content_data['faqs'] ) ? $content_data['faqs'] : [];
		$ai_testimonials     = ! empty( $content_data['testimonials'] ) && is_array( $content_data['testimonials'] ) ? $content_data['testimonials'] : [];
		$ai_benefits         = ! empty( $content_data['benefits'] ) && is_array( $content_data['benefits'] ) ? $content_data['benefits'] : [];
		$ai_metro_stats      = ! empty( $content_data['metro_stats'] ) && is_array( $content_data['metro_stats'] ) ? $content_data['metro_stats'] : [];
		$ai_market_anatomy   = ! empty( $content_data['market_anatomy'] ) && is_array( $content_data['market_anatomy'] ) ? $content_data['market_anatomy'] : [];
		$ai_market_accordion = ! empty( $content_data['market_accordion'] ) && is_array( $content_data['market_accordion'] ) ? $content_data['market_accordion'] : [];
		$ai_submarkets       = ! empty( $content_data['submarkets'] ) && is_array( $content_data['submarkets'] ) ? $content_data['submarkets'] : [];
		$ai_case_proof       = ! empty( $content_data['case_proof'] ) && is_array( $content_data['case_proof'] ) ? $content_data['case_proof'] : [];
		$ai_essay            = ! empty( $content_data['essay'] ) && is_array( $content_data['essay'] ) ? $content_data['essay'] : [];
		$ai_local_relevance  = ! empty( $content_data['local_relevance'] ) && is_array( $content_data['local_relevance'] ) ? $content_data['local_relevance'] : [];

		$nonce = function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'vispan_marketing_inquiry' ) : 'vispan_inquiry_nonce';

		// Load pre-rendered services and trades partials
		$tpl_dir       = __DIR__ . '/templates';
		$services_html = file_exists( $tpl_dir . '/services-grid.html' ) ? (string) file_get_contents( $tpl_dir . '/services-grid.html' ) : '';
		$trades_html   = file_exists( $tpl_dir . '/trades-container.html' ) ? (string) file_get_contents( $tpl_dir . '/trades-container.html' ) : '';

		$template_file = $tpl_dir . '/home-services-master-template.php';
		if ( file_exists( $template_file ) ) {
			ob_start();
			include $template_file;
			return (string) ob_get_clean();
		}

		return '<!-- Vispan Home Services template missing -->';
	}
}
