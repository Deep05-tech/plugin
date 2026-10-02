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
		self::$cached_content = is_array( $decoded ) ? $decoded : null;

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
		self::$cached_content = $content;
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
		return esc_html( sprintf( 'Turn local search traffic in %s into high-paying clients with custom %s strategies engineered for measurable revenue growth.', $city, $service ) );
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
		return esc_html( sprintf( 'Dominating Search in %s Starts With Data', Page_Context::get_city() ) );
	}

	/**
	 * Safe sanitization fallback for HTML body content.
	 *
	 * @param string $text Text to sanitize.
	 * @return string
	 */
	public static function safe_sanitize_body( string $text ): string {
		if ( function_exists( 'wp_kses_post' ) ) {
			return \wp_kses_post( $text );
		}
		return $text;
	}

	public static function get_intro_body(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['introduction']['body'] ) ) {
			return self::safe_sanitize_body( $content['introduction']['body'] );
		}
		$city  = Page_Context::get_city();
		$state = Page_Context::get_state();
		return esc_html( sprintf( 'Generic campaigns fail in competitive local markets. Our localized search architecture captures commercial-intent queries from ready-to-buy clients across %s, %s.', $city, $state ) );
	}

	public static function get_service_overview_heading(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['service_overview']['heading'] ) ) {
			return esc_html( $content['service_overview']['heading'] );
		}
		return esc_html( sprintf( 'Comprehensive %s Solutions', Page_Context::get_service() ) );
	}

	public static function get_service_overview_body(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['service_overview']['body'] ) ) {
			return self::safe_sanitize_body( $content['service_overview']['body'] );
		}
		return esc_html( sprintf( 'We combine technical search precision with high-converting landing page experiences to maximize your return on investment in %s.', Page_Context::get_city() ) );
	}

	public static function get_local_relevance_heading(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['local_relevance']['heading'] ) ) {
			return esc_html( $content['local_relevance']['heading'] );
		}
		return esc_html( sprintf( 'Why Growing Brands in %s Partner with Us', Page_Context::get_city() ) );
	}

	public static function get_local_relevance_body(): string {
		$content = self::get_active_content();
		if ( ! empty( $content['local_relevance']['body'] ) ) {
			return self::safe_sanitize_body( $content['local_relevance']['body'] );
		}
		$city  = Page_Context::get_city();
		$state = Page_Context::get_state();
		return esc_html( sprintf( 'Our specialized search infrastructure drives consistent organic rankings and localized authority across %s, %s.', $city, $state ) );
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
				[ 'question' => sprintf( 'Can an independent local business in %s outrank established national aggregators?', $city ), 'answer' => sprintf( 'Absolutely. Google’s localized ranking algorithms heavily reward proximity, topical completeness, and verified geographic schema over thin national directory pages. By targeting granular submarket commercial queries, local businesses in %s consistently capture top 3 Map Pack rankings.', $city ) ],
				[ 'question' => sprintf( 'How long does it take to see measurable search rank improvements in %s?', $city ), 'answer' => 'Most localized campaigns begin demonstrating keyword movement and Google Map Pack impression growth within 60 to 90 days. As citation velocity, entity authority, and submarket landing nodes mature, compounding inbound lead volume accelerates over 6 to 12 months.' ],
				[ 'question' => sprintf( 'How do you engineer submarket targeting without creating doorway pages in %s?', $city ), 'answer' => sprintf( 'We develop unique, value-dense landing nodes tailored to real commercial districts in %s with distinct local intent data, neighborhood case studies, and hyper-local entity schema that fully complies with Google’s Helpful Content Guidelines.', $city ) ],
				[ 'question' => sprintf( 'What makes %s in %s different from generic national SEO campaigns?', $service, $city ), 'answer' => sprintf( 'Generic SEO focuses on broad national keywords with high competition and low commercial conversion. Our %s strategy in %s concentrates strictly on high-intent regional buyer queries, Google 3-Pack proximity signals, and direct inbound phone inquiries.', $service, $city ) ],
				[ 'question' => 'How do you measure and report ROI on our local search campaign?', 'answer' => 'We deploy dedicated dynamic call tracking, form submission event tagging, and Google Analytics 4 lead attribution. Every month, you receive a transparent video executive report detailing closed revenue pipeline, lead volume, and keyword growth.' ],
				[ 'question' => 'Are there long-term contracts or retainer lock-ins?', 'answer' => 'No. We operate on flexible, transparent month-to-month and quarterly performance agreements. We earn your partnership every single month through measurable organic growth and direct partner communication.' ],
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
		$city    = Page_Context::get_city();
		$service = Page_Context::get_service();

		$verticals = [
			[
				'icon'        => '⚖️',
				'title'       => 'Legal & Professional Services',
				'niche'       => 'High-CPC Corporate & Litigation Search',
				'description' => sprintf( 'Capturing corporate decision makers seeking verified legal counsel in %s with high-trust conversion funnels and entity authority schema.', $city ),
			],
			[
				'icon'        => '🏥',
				'title'       => 'Medical & Healthcare Clinics',
				'niche'       => 'Patient Radius & Procedure Search',
				'description' => sprintf( 'Targeting patient searches across %s submarkets with Google Map Pack 3-Pack optimization and condition-specific landing nodes.', $city ),
			],
			[
				'icon'        => '🏗️',
				'title'       => 'Commercial B2B & Industrial',
				'niche'       => 'Enterprise Procurement & High Contract Values',
				'description' => sprintf( 'Engineering search architecture for %s industrial corridors, logistics hubs, and commercial operators with large order values.', $city ),
			],
			[
				'icon'        => '🏡',
				'title'       => 'High-End Home & Field Services',
				'niche'       => 'High-Intent Emergency & Installation Queries',
				'description' => sprintf( 'Dominating local zip-code search radiuses in %s with mobile-to-call conversion paths and Google Local Services synergy.', $city ),
			],
		];

		$html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 20px; font-family: \'DM Sans\', sans-serif;">';
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];

		foreach ( $verticals as $idx => $v ) {
			$delay = $delays[ $idx % count( $delays ) ];
			$reveal_class = ( 0 === $idx % 2 ) ? 'vispan-reveal-left' : 'vispan-reveal-right';

			$html .= sprintf(
				'<div class="%s %s vispan-hover-card" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 16px; padding: 26px 22px; display: flex; flex-direction: column; justify-content: space-between;">
					<div>
						<div style="font-size: 28px; margin-bottom: 12px;">%s</div>
						<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #02426A; margin-bottom: 4px;">%s</div>
						<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 17.5px; font-weight: 800; color: #121212; margin: 0 0 10px; line-height: 1.35;">%s</h3>
						<p style="font-size: 14px; line-height: 1.6; color: #5D5756; margin: 0;">%s</p>
					</div>
					<div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #CCE0ED; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 12px; font-weight: 800; color: #02426A;">
						Tailored %s Strategy →
					</div>
				</div>',
				esc_attr( $reveal_class ),
				esc_attr( $delay ),
				$v['icon'],
				esc_html( $v['niche'] ),
				esc_html( $v['title'] ),
				esc_html( $v['description'] ),
				esc_html( $service )
			);
		}
		$html .= '</div>';

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
		$content = self::get_active_content();
		$signals = $content['signal_stack'] ?? [];

		if ( empty( $signals ) || ! is_array( $signals ) ) {
			$signals = [
				[ 'label' => 'Topical Depth & Submarket Relevance', 'percentage' => 35, 'description' => 'Granular landing content answering real buyer intent.' ],
				[ 'label' => 'Local Citations & Proximity Signals', 'percentage' => 25, 'description' => 'NAP consistency and geo-authority signals across industry directories.' ],
				[ 'label' => 'Technical Architecture & Schema Markup', 'percentage' => 20, 'description' => 'Fast loading, clean DOM hierarchy, and nested JSON-LD schema.' ],
				[ 'label' => 'Google Map Pack & Review Authority', 'percentage' => 20, 'description' => 'Review velocity and Google Business Profile engagement.' ],
			];
		}

		$colors = [ '#02426A', '#00a32a', '#0284c7', '#d97706' ];
		$delays = [ 'vispan-delay-1', 'vispan-delay-2', 'vispan-delay-3', 'vispan-delay-4' ];
		$cards_html = '<div style="display: flex; flex-direction: column; gap: 14px; font-family: \'DM Sans\', sans-serif;">';

		foreach ( $signals as $idx => $s ) {
			$label = esc_html( $s['label'] ?? '' );
			$pct   = (int) ( $s['percentage'] ?? 25 );
			$desc  = esc_html( $s['description'] ?? '' );
			$col   = $colors[ $idx % count( $colors ) ];
			$delay = $delays[ $idx % count( $delays ) ];

			$cards_html .= sprintf(
				'<div class="vispan-reveal-right %s vispan-hover-card" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-left: 4px solid %s; border-radius: 12px; padding: 16px 18px;">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
						<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 14.5px; font-weight: 800; color: #121212;">%s</span>
						<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 15px; font-weight: 900; color: %s;">%d%%</span>
					</div>
					<div style="background: #F0F6FA; border-radius: 10px; height: 6px; width: 100%%; overflow: hidden; margin-bottom: 6px;">
						<div style="background: %s; height: 100%%; width: %d%%; border-radius: 10px;"></div>
					</div>
					<div style="font-size: 12.5px; color: #5D5756; line-height: 1.45;">%s</div>
				</div>',
				esc_attr( $delay ),
				$col,
				$label,
				$col,
				$pct,
				$col,
				$pct,
				$desc
			);
		}
		$cards_html .= '</div>';

		// SVG Donut Visualization
		$donut_svg = '<div class="vispan-reveal-left" style="background: #FFFFFF; border: 1.5px solid #CCE0ED; border-radius: 18px; padding: 28px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 10px 30px -15px rgba(2,66,106,0.08);">
			<div style="position: relative; width: 160px; height: 160px; margin-bottom: 16px;">
				<svg viewBox="0 0 100 100" width="160" height="160" style="transform: rotate(-90deg); border-radius: 50%;">
					<circle cx="50" cy="50" r="38" fill="none" stroke="#F0F6FA" stroke-width="14" />
					<circle cx="50" cy="50" r="38" fill="none" stroke="#02426A" stroke-width="14" stroke-dasharray="83.5 238.7" stroke-dashoffset="0" />
					<circle cx="50" cy="50" r="38" fill="none" stroke="#00a32a" stroke-width="14" stroke-dasharray="59.7 238.7" stroke-dashoffset="-83.5" />
					<circle cx="50" cy="50" r="38" fill="none" stroke="#0284c7" stroke-width="14" stroke-dasharray="47.7 238.7" stroke-dashoffset="-143.2" />
					<circle cx="50" cy="50" r="38" fill="none" stroke="#d97706" stroke-width="14" stroke-dasharray="47.7 238.7" stroke-dashoffset="-190.9" />
				</svg>
				<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
					<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 22px; font-weight: 900; color: #121212; line-height: 1;">100%</span>
					<span style="font-family: \'DM Sans\', sans-serif; font-size: 10.5px; font-weight: 700; color: #02426A; letter-spacing: 0.08em; text-transform: uppercase; margin-top: 2px;">Algorithmic</span>
				</div>
			</div>
			<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 14.5px; font-weight: 800; color: #121212; margin-bottom: 4px;">Search Signal Weighting</div>
			<div style="font-family: \'DM Sans\', sans-serif; font-size: 12px; color: #5D5756;">Tested across Google Local & Map Pack algorithms</div>
		</div>';

		return sprintf(
			'<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%%, 280px), 1fr)); gap: 24px; align-items: center;">
				%s
				%s
			</div>',
			$donut_svg,
			$cards_html
		);
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
					<div style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 14.5px; font-weight: 800; color: #121212; margin: 2px 0 4px;">%s</div>
					<div style="font-family: \'DM Sans\', sans-serif; font-size: 12.5px; color: #5D5756; line-height: 1.5;">%s</div>
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
					<h2 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: clamp(24px, 3vw, 36px); font-weight: 800; color: #121212; line-height: 1.25; margin: 0 0 16px;">%s</h2>
					<div style="font-size: 15px; line-height: 1.75; color: #4A5568; margin-bottom: 20px;">%s</div>
				</div>
				<div class="vispan-reveal-right" style="background: #F0F6FA; border: 1.5px solid #CCE0ED; border-radius: 18px; padding: 24px 20px;">
					<div style="height: 130px; margin: -24px -20px 18px; overflow: hidden; border-radius: 16px 16px 0 0; position: relative;">
						<img src="%s" alt="1-on-1 Strategist Consultation" style="width: 100%%; height: 100%%; object-fit: cover;" />
						<div style="position: absolute; bottom: 8px; left: 10px; background: rgba(2,66,106,0.85); padding: 3px 8px; border-radius: 20px; font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 10.5px; font-weight: 800; color: #FFFFFF;">
							🤝 Direct Senior Strategist Access
						</div>
					</div>
					<h3 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 16.5px; font-weight: 800; color: #121212; margin: 0 0 14px;">Execution Roadmap</h3>
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

		if ( ! empty( $current_svc ) ) {
			// Check case-insensitive match or synonym mapping to one of the 10 standard services
			$matched_svc = '';
			foreach ( $service_options as $s_opt ) {
				if ( 0 === strcasecmp( $s_opt, (string) $current_svc ) ) {
					$matched_svc = $s_opt;
					break;
				}
			}
			if ( empty( $matched_svc ) ) {
				if ( stripos( $current_svc, 'SEO' ) !== false || stripos( $current_svc, 'Search Engine' ) !== false ) {
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
}
