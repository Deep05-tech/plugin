<?php
/**
 * Main Elementor Integration Orchestrator.
 *
 * Connects the SEO City Page data layer to Elementor Dynamic Tags API
 * and manages template registration and rendering lifecycles.
 *
 * @package SeoCityPages\Elementor
 */

namespace SeoCityPages\Elementor;

use SeoCityPages\Environment;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor_Integration Class.
 */
class Elementor_Integration {

	/**
	 * Dynamic Tag Group Identifier.
	 */
	public const TAG_GROUP = 'seo-city-pages';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize Elementor hooks if Elementor is active.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register shortcodes universally so they work in both Elementor and standard WordPress.
		$this->register_shortcodes();

		if ( ! Environment::is_elementor_active() ) {
			return;
		}

		// Register custom dynamic tags with Elementor Dynamic Tags Manager.
		add_action( 'elementor/dynamic_tags/register', [ $this, 'register_dynamic_tags' ] );
		add_action( 'elementor/dynamic_tags/register_tags', [ $this, 'register_dynamic_tags_legacy' ] );

		// Filter Elementor rendered widgets and content to dynamically hydrate tokens.
		add_filter( 'elementor/widget/render_content', [ self::class, 'filter_rendered_content' ], 10, 2 );
		add_filter( 'elementor/frontend/the_content', [ self::class, 'filter_rendered_content' ], 10, 1 );
	}

	/**
	 * Register universal shortcodes for SEO City Page fields.
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		// Location & Page identity shortcodes.
		add_shortcode( 'seo_city_name', [ Page_Context::class, 'get_city' ] );
		add_shortcode( 'seo_city_state', [ Page_Context::class, 'get_state' ] );
		add_shortcode( 'seo_city_country', [ Page_Context::class, 'get_country' ] );
		add_shortcode( 'seo_city_service', [ Page_Context::class, 'get_service' ] );
		add_shortcode( 'seo_city_title', [ Page_Context::class, 'get_title' ] );
		add_shortcode( 'seo_city_url', [ Page_Context::class, 'get_url' ] );

		// AI-Generated Content field shortcodes.
		add_shortcode( 'seo_hero_title', [ Content_Context::class, 'get_hero_title' ] );
		add_shortcode( 'seo_hero_description', [ Content_Context::class, 'get_hero_description' ] );
		add_shortcode( 'seo_hero_cta', [ Content_Context::class, 'get_hero_cta' ] );
		add_shortcode( 'seo_intro_heading', [ Content_Context::class, 'get_intro_heading' ] );
		add_shortcode( 'seo_intro_body', [ Content_Context::class, 'get_intro_body' ] );
		add_shortcode( 'seo_service_overview_heading', [ Content_Context::class, 'get_service_overview_heading' ] );
		add_shortcode( 'seo_service_overview_body', [ Content_Context::class, 'get_service_overview_body' ] );
		add_shortcode( 'seo_benefits', [ Content_Context::class, 'render_benefits_html' ] );
		add_shortcode( 'seo_process', [ Content_Context::class, 'render_process_html' ] );
		add_shortcode( 'seo_local_relevance_heading', [ Content_Context::class, 'get_local_relevance_heading' ] );
		add_shortcode( 'seo_local_relevance_body', [ Content_Context::class, 'get_local_relevance_body' ] );
		add_shortcode( 'seo_faqs', [ Content_Context::class, 'render_faqs_html' ] );
		add_shortcode( 'seo_final_cta_heading', [ Content_Context::class, 'get_final_cta_heading' ] );
		add_shortcode( 'seo_final_cta_description', [ Content_Context::class, 'get_final_cta_description' ] );
		add_shortcode( 'seo_final_cta_button', [ Content_Context::class, 'get_final_cta_button' ] );

		// Rich Multi-Section Shortcodes (Omega Trove Layout Architecture).
		add_shortcode( 'seo_metro_stats', [ Content_Context::class, 'render_metro_stats_html' ] );
		add_shortcode( 'seo_market_anatomy', [ Content_Context::class, 'render_market_anatomy_html' ] );
		add_shortcode( 'seo_market_accordion', [ Content_Context::class, 'render_market_accordion_html' ] );
		add_shortcode( 'seo_submarkets_grid', [ Content_Context::class, 'render_submarkets_grid_html' ] );
		add_shortcode( 'seo_case_proof', [ Content_Context::class, 'render_case_proof_html' ] );
		add_shortcode( 'seo_signal_stack', [ Content_Context::class, 'render_signal_stack_html' ] );
		add_shortcode( 'seo_long_essay', [ Content_Context::class, 'render_essay_html' ] );
		add_shortcode( 'seo_comparison_table', [ Content_Context::class, 'render_comparison_table_html' ] );
		add_shortcode( 'seo_industry_verticals', [ Content_Context::class, 'render_industry_verticals_html' ] );
		add_shortcode( 'seo_deliverables_checklist', [ Content_Context::class, 'render_deliverables_checklist_html' ] );
		add_shortcode( 'seo_inquiry_form', [ Content_Context::class, 'render_inquiry_form' ] );
		add_shortcode( 'seo_inquiry_form_hero', [ Content_Context::class, 'render_inquiry_form_hero' ] );
		add_shortcode( 'seo_inquiry_form_bottom', [ Content_Context::class, 'render_inquiry_form_bottom' ] );

		// Master City Page shortcode.
		add_shortcode( 'seo_city_page', [ self::class, 'render_city_page_shortcode' ] );
	}

	/**
	 * Filter rendered content to resolve all dynamic placeholders, shortcodes, and tag tokens.
	 *
	 * @param string $content HTML content.
	 * @param mixed  $widget  Optional Elementor widget instance.
	 * @return string
	 */
	public static function filter_rendered_content( string $content, $widget = null ): string {
		static $is_filtering = false;

		if ( empty( $content ) || $is_filtering ) {
			return $content;
		}

		$is_filtering = true;

		$city    = Page_Context::get_city();
		$state   = Page_Context::get_state();
		$country = Page_Context::get_country();
		$service = Page_Context::get_service();
		$title   = Page_Context::get_title();
		$url     = Page_Context::get_url();

		$replacements = [
			// Core identity tokens.
			'{{seo_city_name}}'    => $city,
			'{{seo_city_state}}'   => $state,
			'{{seo_city_country}}' => $country,
			'{{seo_city_service}}' => $service,
			'{{seo_city_title}}'   => $title,
			'{{seo_city_url}}'     => $url,
			'{{city}}'             => $city,
			'{{state}}'            => $state,
			'{{country}}'          => $country,
			'{{service}}'          => $service,
			'{{title}}'            => $title,
			'{{url}}'              => $url,

			// AI Generated Content tokens.
			'{{seo_hero_title}}'              => Content_Context::get_hero_title(),
			'{{seo_hero_description}}'        => Content_Context::get_hero_description(),
			'{{seo_hero_subtitle}}'           => Content_Context::get_hero_subtitle(),
			'{{seo_hero_cta}}'                => Content_Context::get_hero_cta(),
			'{{seo_intro_heading}}'           => Content_Context::get_intro_heading(),
			'{{seo_intro_body}}'              => Content_Context::get_intro_body(),
			'{{seo_service_overview_heading}}' => Content_Context::get_service_overview_heading(),
			'{{seo_service_overview_body}}'   => Content_Context::get_service_overview_body(),
			'{{seo_benefits}}'                => Content_Context::render_benefits_html(),
			'{{seo_process}}'                 => Content_Context::render_process_html(),
			'{{seo_local_relevance_heading}}' => Content_Context::get_local_relevance_heading(),
			'{{seo_local_relevance_body}}'    => Content_Context::get_local_relevance_body(),
			'{{seo_faqs}}'                    => Content_Context::render_faqs_html(),
			'{{seo_final_cta_heading}}'       => Content_Context::get_final_cta_heading(),
			'{{seo_final_cta_description}}'   => Content_Context::get_final_cta_description(),
			'{{seo_final_cta_button}}'        => Content_Context::get_final_cta_button(),

			// High-Converting Proposal Inquiry Forms.
			'{{seo_inquiry_form}}'            => Content_Context::render_inquiry_form(),
			'{{seo_inquiry_form_hero}}'       => Content_Context::render_inquiry_form_hero(),
			'{{seo_inquiry_form_bottom}}'     => Content_Context::render_inquiry_form_bottom(),

			// Rich Section Tokens.
			'{{seo_metro_stats}}'             => Content_Context::render_metro_stats_html(),
			'{{seo_market_anatomy}}'          => Content_Context::render_market_anatomy_html(),
			'{{seo_market_accordion}}'        => Content_Context::render_market_accordion_html(),
			'{{seo_submarkets_grid}}'         => Content_Context::render_submarkets_grid_html(),
			'{{seo_case_proof}}'              => Content_Context::render_case_proof_html(),
			'{{seo_signal_stack}}'            => Content_Context::render_signal_stack_html(),
			'{{seo_long_essay}}'              => Content_Context::render_essay_html(),
			'{{seo_comparison_table}}'        => Content_Context::render_comparison_table_html(),
			'{{seo_industry_verticals}}'      => Content_Context::render_industry_verticals_html(),
			'{{seo_deliverables_checklist}}'  => Content_Context::render_deliverables_checklist_html(),

			// High-Res Photography Asset Tokens.
			'{{seo_image_team}}'              => esc_url( Content_Context::get_image_url( 'seo-strategy-team.jpg' ) ),
			'{{seo_image_city}}'              => esc_url( Content_Context::get_image_url( 'city-market-data.jpg' ) ),
			'{{seo_image_commercial}}'        => esc_url( Content_Context::get_image_url( 'commercial-district.jpg' ) ),
			'{{seo_image_analytics}}'         => esc_url( Content_Context::get_image_url( 'growth-analytics.jpg' ) ),
			'{{seo_image_consultation}}'      => esc_url( Content_Context::get_image_url( 'seo-consultation.jpg' ) ),

			// Elementor Dynamic Tag marker fallbacks.
			'[elementor-tag tag_name="seo_city_name"]'    => $city,
			'[elementor-tag tag_name="seo_city_state"]'   => $state,
			'[elementor-tag tag_name="seo_city_country"]' => $country,
			'[elementor-tag tag_name="seo_city_service"]' => $service,
			'[elementor-tag tag_name="seo_city_title"]'   => $title,
			'[elementor-tag tag_name="seo_city_url"]'     => $url,
			'%5Belementor-tag%20tag_name%3D%22seo_city_url%22%5D' => $url,
		];

		$content = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );

		// Process any WordPress shortcodes present in widget content.
		if ( function_exists( 'do_shortcode' ) && false !== strpos( $content, '[' ) ) {
			$content = do_shortcode( $content );
		}

		// Inject lightweight scroll animation CSS and IntersectionObserver if Vispan reveal classes are present.
		if ( false !== strpos( $content, 'vispan-reveal-' ) && false === strpos( $content, 'vispan-scroll-animations-css' ) ) {
			$animation_assets = self::get_animation_assets();
			$content          = $animation_assets . $content;
		}

		$is_filtering = false;

		return $content;
	}

	/**
	 * Render complete SEO City Page via shortcode.
	 *
	 * @param array $atts Shortcode attributes (e.g. id).
	 * @return string HTML output.
	 */
	public static function render_city_page_shortcode( $atts = [] ): string {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'seo_city_page' );
		$page_id = (int) $atts['id'];

		if ( $page_id <= 0 && function_exists( 'get_the_ID' ) ) {
			$post_id = get_the_ID();
			if ( $post_id > 0 && function_exists( 'get_post_meta' ) ) {
				$page_id = (int) get_post_meta( $post_id, \SeoCityPages\Integration\Post_Sync::META_PAGE_ID, true );
			}
		}

		if ( $page_id <= 0 ) {
			return '';
		}

		$page = \SeoCityPages\Database\City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return '';
		}

		$country = \SeoCityPages\Database\Country_Repository::get( (int) $page->country_id );
		$state   = \SeoCityPages\Database\State_Repository::get( (int) $page->state_id );
		$city    = \SeoCityPages\Database\City_Repository::get( (int) $page->city_id );
		$service = \SeoCityPages\Database\Service_Repository::get( (int) $page->service_id );

		Page_Context::set_context( $page, $country, $state, $city, $service );

		// Check if Elementor template is configured and active.
		if ( Environment::is_elementor_active() ) {
			$settings    = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME, [] );
			$template_id = (int) ( $settings['template_id'] ?? 0 );

			if ( $template_id > 0 && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
				$raw = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );
				if ( ! empty( $raw ) ) {
					return self::filter_rendered_content( $raw );
				}
			}
		}

		// Fallback clean responsive layout.
		$hero_title = Content_Context::get_hero_title();
		$hero_desc  = Content_Context::get_hero_description();
		$hero_cta   = Content_Context::get_hero_cta();
		$intro_h    = Content_Context::get_intro_heading();
		$intro_b    = Content_Context::get_intro_body();
		$form       = Content_Context::render_inquiry_form();

		return '<div class="scp-city-page-wrap" style="max-width:1140px; margin:0 auto; padding:40px 20px; font-family:inherit;">'
			. '<div class="scp-hero" style="background:#0f172a; color:#fff; padding:60px 40px; border-radius:12px; margin-bottom:40px; text-align:center;">'
			. '<h1 style="color:#fff; font-size:36px; margin-bottom:16px;">' . esc_html( $hero_title ) . '</h1>'
			. '<p style="color:#94a3b8; font-size:18px; max-width:800px; margin:0 auto 24px auto;">' . esc_html( $hero_desc ) . '</p>'
			. '<a href="#inquiry-form" class="button button-primary" style="background:#2563eb; color:#fff; padding:12px 28px; border-radius:6px; text-decoration:none; font-weight:600;">' . esc_html( $hero_cta ) . '</a>'
			. '</div>'
			. '<div class="scp-intro" style="margin-bottom:40px;">'
			. '<h2 style="font-size:28px; margin-bottom:12px;">' . esc_html( $intro_h ) . '</h2>'
			. '<div style="font-size:16px; line-height:1.7; color:#334155;">' . wp_kses_post( $intro_b ) . '</div>'
			. '</div>'
			. '<div id="inquiry-form" class="scp-form-section" style="margin-top:40px;">' . $form . '</div>'
			. '</div>';
	}

	/**
	 * Get lightweight CSS and JavaScript for scroll-triggered entrance transitions.
	 *
	 * @return string HTML style and script tags.
	 */
	public static function get_animation_assets(): string {
		static $rendered = false;
		if ( $rendered && ! defined( 'SEO_CITY_PAGES_TESTING' ) ) {
			return '';
		}
		$rendered = true;

		return '<style id="vispan-scroll-animations-css">
			/* 1. Universal Theme Preloader Neutralizer for SEO City Pages */
			#preloader, .preloader, .arolax-preloader, #loading, .page-loader, .loading-screen, .arolax-page-loading, .preloader-wrap {
				display: none !important;
				opacity: 0 !important;
				visibility: hidden !important;
				pointer-events: none !important;
				z-index: -99999 !important;
			}

			/* 2. Initial Offscreen / Fade States */
			.vispan-reveal-left {
				opacity: 0;
				transform: translate3d(-70px, 0, 0);
				transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
				will-change: transform, opacity;
			}
			.vispan-reveal-right {
				opacity: 0;
				transform: translate3d(70px, 0, 0);
				transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
				will-change: transform, opacity;
			}
			.vispan-reveal-up {
				opacity: 0;
				transform: translate3d(0, 50px, 0);
				transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
				will-change: transform, opacity;
			}
			.vispan-reveal-fade {
				opacity: 0;
				transition: opacity 0.85s ease;
				will-change: opacity;
			}

			/* 3. In-View Active State (Smooth Entrance on Load, Refresh & Scroll) */
			.vispan-reveal-left.vispan-in-view,
			.vispan-reveal-right.vispan-in-view,
			.vispan-reveal-up.vispan-in-view,
			.vispan-reveal-fade.vispan-in-view,
			.elementor-editor-active .vispan-reveal-left,
			.elementor-editor-active .vispan-reveal-right,
			.elementor-editor-active .vispan-reveal-up,
			.elementor-editor-active .vispan-reveal-fade,
			body.elementor-editor-active .vispan-reveal-left,
			body.elementor-editor-active .vispan-reveal-right,
			body.elementor-editor-active .vispan-reveal-up,
			body.elementor-editor-active .vispan-reveal-fade {
				opacity: 1 !important;
				transform: translate3d(0, 0, 0) !important;
			}

			.vispan-delay-1 { transition-delay: 0.15s; }
			.vispan-delay-2 { transition-delay: 0.30s; }
			.vispan-delay-3 { transition-delay: 0.45s; }
			.vispan-delay-4 { transition-delay: 0.60s; }

			.vispan-hover-card {
				transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
			}
			.vispan-hover-card:hover {
				transform: translateY(-5px) !important;
				box-shadow: 0 20px 40px -15px rgba(2,66,106,0.18) !important;
			}
			.vispan-btn-glow {
				transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1), background 0.3s ease !important;
			}
			.vispan-btn-glow:hover {
				transform: translateY(-2px) scale(1.02) !important;
				box-shadow: 0 16px 32px -8px rgba(2,66,106,0.45) !important;
			}

			@media (prefers-reduced-motion: reduce) {
				.vispan-reveal-left, .vispan-reveal-right, .vispan-reveal-up, .vispan-reveal-fade {
					opacity: 1 !important;
					transform: none !important;
					transition: none !important;
				}
			}

			/* =========================================================
			   GLOBAL TYPOGRAPHY & LETTER-SPACING CORRECTION
			   ========================================================= */
			.seo-city-page-container,
			.vispan-container,
			.seo-city-page-container *,
			.vispan-hover-card,
			.vispan-hover-card *,
			.vispan-inquiry-card,
			.vispan-inquiry-card * {
				letter-spacing: normal !important;
				word-spacing: normal !important;
				text-rendering: optimizeLegibility !important;
				-webkit-font-smoothing: antialiased !important;
				-moz-osx-font-smoothing: grayscale !important;
			}
			.seo-city-page-container h1,
			.seo-city-page-container h2,
			.seo-city-page-container h3,
			.seo-city-page-container h4,
			.seo-city-page-container h5,
			.seo-city-page-container h6,
			.vispan-hover-card h3,
			.vispan-hover-card h4,
			.vispan-inquiry-card h3 {
				letter-spacing: 0px !important;
				word-spacing: 1.5px !important;
				line-height: 1.35 !important;
				font-feature-settings: "kern" 1, "liga" 1 !important;
			}
			.seo-city-page-container p,
			.seo-city-page-container li,
			.vispan-hover-card p {
				letter-spacing: 0.15px !important;
				word-spacing: 1px !important;
				line-height: 1.65 !important;
			}
			.vispan-badge,
			[style*="text-transform: uppercase"],
			[style*="text-transform:uppercase"] {
				letter-spacing: 1.2px !important;
				word-spacing: 2px !important;
			}

			/* =========================================================
			   GLOBAL RESPONSIVE ENGINE (DESKTOP, LAPTOP, TABLET, MOBILE)
			   ========================================================= */
			.seo-city-page-container, .vispan-container {
				width: 100% !important;
				max-width: 100% !important;
				overflow-x: hidden !important;
				box-sizing: border-box !important;
			}
			.seo-city-page-container *, .vispan-inquiry-card *, .vispan-hover-card * {
				box-sizing: border-box !important;
				max-width: 100% !important;
			}
			.vispan-inquiry-card {
				width: 100% !important;
				box-sizing: border-box !important;
			}
			.vispan-inquiry-input, .vispan-inquiry-select, .vispan-inquiry-textarea {
				box-sizing: border-box !important;
				max-width: 100% !important;
				-webkit-appearance: none;
				appearance: none;
			}
			.vispan-inquiry-select {
				background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23333\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3e%3cpolyline points=\'6 9 12 15 18 9\'%3e%3c/polyline%3e%3c/svg%3e") !important;
				background-repeat: no-repeat !important;
				background-position: right 14px center !important;
				background-size: 14px !important;
				padding-right: 36px !important;
			}

			/* Tablet & Medium Screens (768px - 1024px) */
			@media (max-width: 1024px) {
				.elementor-section {
					padding-left: 20px !important;
					padding-right: 20px !important;
				}
				.vispan-inquiry-card {
					max-width: 100% !important;
				}
				[style*="grid-template-columns"] {
					grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)) !important;
				}
			}

			/* Mobile & Small Tablet Screens (<= 767px) */
			@media (max-width: 767px) {
				/* Layout & Section Spacing */
				.elementor-section, .elementor-top-section {
					padding-top: 36px !important;
					padding-bottom: 36px !important;
					padding-left: 14px !important;
					padding-right: 14px !important;
				}
				.elementor-container, .elementor-row {
					display: flex !important;
					flex-direction: column !important;
					padding-left: 0 !important;
					padding-right: 0 !important;
					width: 100% !important;
				}
				.elementor-column, .elementor-top-column, .elementor-col-10, .elementor-col-20, .elementor-col-30, .elementor-col-33, .elementor-col-40, .elementor-col-50, .elementor-col-60, .elementor-col-70, .elementor-col-80, .elementor-col-90, .elementor-col-100 {
					width: 100% !important;
					max-width: 100% !important;
					flex: 0 0 100% !important;
				}
				.vispan-hero-left, .vispan-hero-right {
					width: 100% !important;
					flex: 0 0 100% !important;
					max-width: 100% !important;
				}
				.vispan-hero-left {
					margin-bottom: 24px !important;
				}

				/* Mobile Grid Auto-Stacking */
				[style*="grid-template-columns"] {
					grid-template-columns: 1fr !important;
					gap: 16px !important;
				}

				/* Typography Scale on Mobile */
				h1, .seo-city-page-container h1 {
					font-size: 28px !important;
					line-height: 1.25 !important;
					letter-spacing: 0 !important;
					word-spacing: 1px !important;
					margin-bottom: 14px !important;
				}
				h2, .seo-city-page-container h2 {
					font-size: 22px !important;
					line-height: 1.3 !important;
					letter-spacing: 0 !important;
					word-spacing: 1px !important;
					margin-bottom: 12px !important;
				}
				h3, .seo-city-page-container h3, .vispan-hover-card h3 {
					font-size: 18px !important;
					line-height: 1.35 !important;
					letter-spacing: 0 !important;
					word-spacing: 1px !important;
				}
				h4, .seo-city-page-container h4, .vispan-hover-card h4 {
					font-size: 16px !important;
					line-height: 1.35 !important;
					letter-spacing: 0 !important;
					word-spacing: 1px !important;
				}
				p, .seo-city-page-container p, .vispan-hover-card p {
					font-size: 14.5px !important;
					line-height: 1.6 !important;
					letter-spacing: 0.1px !important;
					word-spacing: 0.8px !important;
				}

				/* Cards on Mobile */
				.vispan-hover-card {
					padding: 20px 16px !important;
					border-radius: 16px !important;
				}
				.vispan-inquiry-card {
					padding: 22px 16px !important;
					border-radius: 18px !important;
					max-width: 100% !important;
				}
				.vispan-inquiry-card h3 {
					font-size: 19px !important;
					margin-bottom: 14px !important;
				}
				.vispan-inquiry-input, .vispan-inquiry-select, .vispan-inquiry-textarea {
					font-size: 14px !important;
					height: 44px !important;
					padding-left: 14px !important;
					padding-right: 14px !important;
				}
				.vispan-inquiry-textarea {
					height: auto !important;
					min-height: 65px !important;
				}
				.vispan-inquiry-btn {
					height: 48px !important;
					font-size: 14.5px !important;
				}

				/* Mobile Table & Proof Cards */
				.vispan-proof-card {
					padding: 22px 16px !important;
				}
				table, .vispan-table-wrap {
					display: block !important;
					width: 100% !important;
					overflow-x: auto !important;
					-webkit-overflow-scrolling: touch !important;
				}

				/* Buttons */
				.vispan-btn-glow {
					display: block !important;
					text-align: center !important;
					width: 100% !important;
					padding: 14px 20px !important;
					font-size: 13.5px !important;
					margin-bottom: 10px !important;
				}
			}

			/* Small Mobile Screens (<= 480px) */
			@media (max-width: 480px) {
				.elementor-section, .elementor-top-section {
					padding-top: 28px !important;
					padding-bottom: 28px !important;
					padding-left: 10px !important;
					padding-right: 10px !important;
				}
				.vispan-inquiry-card {
					padding: 18px 12px !important;
					border-radius: 14px !important;
				}
				.vispan-hover-card {
					padding: 16px 12px !important;
					border-radius: 14px !important;
				}
				h1, .seo-city-page-container h1 {
					font-size: 24px !important;
				}
				h2, .seo-city-page-container h2 {
					font-size: 20px !important;
				}
			}
		</style>
		<script id="vispan-scroll-animations-js">
		(function() {
			function initVispanAnimations() {
				// Immediate dismissal of theme preloader overlay
				var preloaders = document.querySelectorAll("#preloader, .preloader, .arolax-preloader, #loading, .page-loader, .loading-screen, .arolax-page-loading, .preloader-wrap");
				for (var p = 0; p < preloaders.length; p++) {
					preloaders[p].style.display = "none";
				}

				var targets = document.querySelectorAll(".vispan-reveal-left, .vispan-reveal-right, .vispan-reveal-up, .vispan-reveal-fade");
				if (!targets.length) return;

				function reveal(el) {
					if (!el.classList.contains("vispan-in-view")) {
						el.classList.add("vispan-in-view");
					}
				}

				// Trigger entrance animations for visible hero section after small frame delay
				setTimeout(function() {
					for (var h = 0; h < targets.length; h++) {
						var rect = targets[h].getBoundingClientRect();
						if (rect.top < (window.innerHeight || document.documentElement.clientHeight) && rect.bottom > 0) {
							reveal(targets[h]);
						}
					}
				}, 60);

				if ("IntersectionObserver" in window) {
					var observer = new IntersectionObserver(function(entries, obs) {
						for (var j = 0; j < entries.length; j++) {
							var entry = entries[j];
							if (entry.isIntersecting) {
								reveal(entry.target);
								obs.unobserve(entry.target);
							}
						}
					}, {
						root: null,
						rootMargin: "0px 0px -4% 0px",
						threshold: 0.05
					});

					for (var k = 0; k < targets.length; k++) {
						observer.observe(targets[k]);
					}
				} else {
					for (var i = 0; i < targets.length; i++) {
						reveal(targets[i]);
					}
				}
			}

			if (document.readyState === "loading") {
				document.addEventListener("DOMContentLoaded", initVispanAnimations);
			} else {
				initVispanAnimations();
			}
		})();
		</script>';
	}

	/**
	 * Register Dynamic Tag Group and Tag classes (Elementor v3.5+ API).
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor Dynamic Tags Manager.
	 * @return void
	 */
	public function register_dynamic_tags( $dynamic_tags_manager ): void {
		// 1. Register Tag Group.
		if ( method_exists( $dynamic_tags_manager, 'register_group' ) ) {
			$dynamic_tags_manager->register_group(
				self::TAG_GROUP,
				[
					'title' => __( 'SEO City Pages', 'seo-city-pages' ),
				]
			);
		}

		// 2. Register Tag Classes.
		$tags = [
			DynamicTags\Tag_City::class,
			DynamicTags\Tag_State::class,
			DynamicTags\Tag_Country::class,
			DynamicTags\Tag_Service::class,
			DynamicTags\Tag_Title::class,
			DynamicTags\Tag_URL::class,
		];

		foreach ( $tags as $tag_class ) {
			if ( class_exists( $tag_class ) ) {
				if ( method_exists( $dynamic_tags_manager, 'register' ) ) {
					$dynamic_tags_manager->register( new $tag_class() );
				} elseif ( method_exists( $dynamic_tags_manager, 'register_tag' ) ) {
					$dynamic_tags_manager->register_tag( $tag_class );
				}
			}
		}
	}

	/**
	 * Legacy Dynamic Tag Registration fallback.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Elementor Dynamic Tags Manager.
	 * @return void
	 */
	public function register_dynamic_tags_legacy( $dynamic_tags_manager ): void {
		$this->register_dynamic_tags( $dynamic_tags_manager );
	}

	/**
	 * Retrieve all available Elementor Library templates.
	 *
	 * @return object[] Array of post objects with id, title, type, and status.
	 */
	public static function get_available_templates(): array {
		if ( ! function_exists( 'get_posts' ) ) {
			return [];
		}

		$templates = get_posts( [
			'post_type'        => 'elementor_library',
			'post_status'      => [ 'publish', 'draft', 'private' ],
			'numberposts'      => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'suppress_filters' => false,
		] );

		$result = [];
		foreach ( $templates as $t ) {
			$type = get_post_meta( $t->ID, '_elementor_template_type', true );
			$result[] = (object) [
				'id'     => (int) $t->ID,
				'title'  => $t->post_title ?: sprintf( __( 'Template #%d', 'seo-city-pages' ), (int) $t->ID ),
				'type'   => $type ?: 'page',
				'status' => $t->post_status,
				'date'   => $t->post_date,
			];
		}

		return $result;
	}

	/**
	 * Programmatically generate a modern, high-converting starter SEO City Page Elementor Template
	 * matching Vispan Solutions design language (#02426A Navy Blue, clean typography, agency UI/UX).
	 *
	 * @param string $template_title Title for the template.
	 * @return int|\WP_Error Template Post ID or WP_Error.
	 */
	public static function create_starter_template( string $template_title = '' ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'unauthorized', __( 'Permission denied.', 'seo-city-pages' ) );
		}

		$title = ! empty( $template_title ) ? $template_title : __( 'Vispan Solutions — SEO City Landing Page Master Template', 'seo-city-pages' );

		// 100% Vispan Solutions Brand Palette: #02426A (Primary Navy), #121212 (Headings), #5D5756 (Body), #F0F6FA (Tint), #CCE0ED (Border), #00a32a (Green Accent)
		$elementor_data = [
			// 1. HERO SECTION (Modern Agency Split Grid)
			[
				'id'       => 'vispan_hero_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '70',
						'bottom'   => '60',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#FFFFFF',
					'align_items'           => 'center',
				],
				'elements' => [
					[
						'id'       => 'vispan_hero_left',
						'elType'   => 'column',
						'settings' => [
							'_column_size' => 60,
							'vertical_align' => 'middle',
						],
						'elements' => [
							[
								'id'         => 'vispan_hero_text',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div class="vispan-reveal-left" data-title="{{seo_city_title}}" style="font-family: \'Plus Jakarta Sans\', \'DM Sans\', sans-serif;">
										<div style="display: inline-flex; align-items: center; gap: 8px; background: #F0F6FA; border: 1px solid #CCE0ED; border-radius: 30px; padding: 5px 14px; margin-bottom: 14px;">
											<span style="font-size: 11.5px; font-weight: 800; color: #02426A; letter-spacing: 0.12em; text-transform: uppercase;">⭐ 5.0 ON GOOGLE • {{seo_city_name}} EXPERTISE</span>
										</div>
										<h1 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: clamp(30px, 4vw, 48px); font-weight: 800; line-height: 1.2; letter-spacing: 0px; word-spacing: 1.5px; color: #121212; margin: 0 0 16px;">{{seo_hero_title}}</h1>
										<p style="font-family: \'DM Sans\', sans-serif; font-size: 17px; line-height: 1.6; color: #5D5756; margin: 0 0 20px; max-width: 560px;">{{seo_hero_description}}</p>
										<div style="display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 24px; font-family: \'DM Sans\', sans-serif; font-size: 14px; font-weight: 600; color: #121212;">
											<span style="display: inline-flex; align-items: center; gap: 6px;"><span style="color: #02426A; font-weight: 900;">✓</span> 100% ROI-Focused</span>
											<span style="display: inline-flex; align-items: center; gap: 6px;"><span style="color: #02426A; font-weight: 900;">✓</span> Dedicated Senior Strategist</span>
											<span style="display: inline-flex; align-items: center; gap: 6px;"><span style="color: #02426A; font-weight: 900;">✓</span> Granular Submarket Targeting</span>
										</div>
										<div style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px;">
											<a href="{{seo_city_url}}" class="vispan-btn-glow" style="display: inline-block; background: #02426A; color: #FFFFFF; font-family: \'DM Sans\', sans-serif; font-weight: 700; font-size: 13.5px; letter-spacing: 0.04em; text-transform: uppercase; padding: 14px 32px; border-radius: 100px; text-decoration: none; box-shadow: 0 12px 28px -10px rgba(2,66,106,0.5);">{{seo_hero_cta}}</a>
											<a href="tel:+918485986860" style="font-family: \'DM Sans\', sans-serif; font-size: 14px; font-weight: 700; color: #02426A; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">📞 +91 84859 86860</a>
										</div>
										<div style="margin-top: 22px; padding-top: 16px; border-top: 1px solid #CCE0ED; display: flex; flex-wrap: wrap; gap: 16px; font-family: \'DM Sans\', sans-serif; font-size: 13px; color: #5D5756;">
											<span style="display: inline-flex; align-items: center; gap: 6px;">⚡ <strong style="color: #121212;">Response:</strong> Within 15 Minutes</span>
											<span style="display: inline-flex; align-items: center; gap: 6px;">🔒 <strong style="color: #121212;">Privacy:</strong> 100% Confidential</span>
											<span style="display: inline-flex; align-items: center; gap: 6px;">⭐ <strong style="color: #121212;">Rating:</strong> 5.0 Star Agency</span>
										</div>
										<div style="margin-top: 18px; display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px;">
											<div style="background: #F0F6FA; border: 1.5px solid #CCE0ED; border-radius: 12px; padding: 12px 16px;">
												<div style="font-size: 10.5px; font-weight: 800; color: #02426A; letter-spacing: 0.1em; text-transform: uppercase;">LOCAL GEO-AUTHORITY</div>
												<div style="font-size: 13px; font-weight: 800; color: #121212; margin-top: 2px;">Targeted {{seo_city_name}} Search Radius</div>
											</div>
											<div style="background: #F0F6FA; border: 1.5px solid #CCE0ED; border-radius: 12px; padding: 12px 16px;">
												<div style="font-size: 10.5px; font-weight: 800; color: #02426A; letter-spacing: 0.1em; text-transform: uppercase;">PROVEN ROI HORIZON</div>
												<div style="font-size: 13px; font-weight: 800; color: #121212; margin-top: 2px;">Rankings That Drive Inbound Revenue</div>
											</div>
										</div>
									</div>',
								],
							],
						],
					],
					[
						'id'       => 'vispan_hero_right',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 40 ],
						'elements' => [
							[
								'id'         => 'vispan_hero_inquiry_card',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div class="vispan-reveal-right vispan-delay-1">{{seo_inquiry_form_hero}}</div>',
								],
							],
						],
					],
				],
			],

			// 2. CITYSCAPE / MARKET KICKER BANNER (Full-width Vispan Navy #02426A with City Network Background)
			[
				'id'       => 'vispan_kicker_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '50',
						'bottom'   => '50',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#02426A',
				],
				'elements' => [
					[
						'id'       => 'vispan_kicker_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_kicker_content',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div class="vispan-reveal-up" style="position: relative; text-align: center; max-width: 900px; margin: 0 auto; color: #FFFFFF; font-family: \'Plus Jakarta Sans\', sans-serif; padding: 32px 24px; background: linear-gradient(rgba(2,66,106,0.92), rgba(2,66,106,0.92)), url({{seo_image_city}}) center/cover no-repeat; border-radius: 18px; border: 1.5px solid rgba(255,255,255,0.15); box-shadow: 0 16px 40px -18px rgba(0,0,0,0.5);">
										<div style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #BEE3F8; margin-bottom: 10px;">LOCAL SEARCH MARKET DYNAMICS</div>
										<h2 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: clamp(24px, 3vw, 36px); font-weight: 800; color: #FFFFFF; line-height: 1.25; margin: 0 0 12px;">{{seo_intro_heading}}</h2>
										<p style="font-family: \'DM Sans\', sans-serif; font-size: 15.5px; line-height: 1.6; color: #E2E8F0; max-width: 740px; margin: 0 auto;">{{seo_intro_body}}</p>
									</div>',
								],
							],
						],
					],
				],
			],

			// 3. MARKET ANATOMY & FLOATING METRO IN-NUMBERS PANEL
			[
				'id'       => 'vispan_anatomy_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#FFFFFF',
				],
				'elements' => [
					[
						'id'       => 'vispan_anatomy_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_anatomy_content',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div style="max-width: 1100px; margin: 0 auto;">
										<div style="margin-bottom: 28px;">
											{{seo_market_anatomy}}
										</div>
										<div style="margin-top: 20px;">
											{{seo_metro_stats}}
										</div>
									</div>',
								],
							],
						],
					],
				],
			],

			// 4. STRATEGIC SEARCH TARGETING ACCORDION
			[
				'id'       => 'vispan_accordion_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#F8FAFC',
				],
				'elements' => [
					[
						'id'       => 'vispan_accordion_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_accordion_content',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div style="max-width: 880px; margin: 0 auto; font-family: \'DM Sans\', sans-serif;">
										<div class="vispan-reveal-up" style="text-align: center; margin-bottom: 28px; font-family: \'Plus Jakarta Sans\', sans-serif;">
											<div style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 8px;">SEARCH INTENT ARCHITECTURE</div>
											<h2 style="font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #121212; margin: 0;">How We Capture High-Intent Traffic in {{seo_city_name}}</h2>
										</div>
										{{seo_market_accordion}}
									</div>',
								],
							],
						],
					],
				],
			],

			// 5. PROOF / CASE STUDY HIGHLIGHT BANNER
			[
				'id'       => 'vispan_proof_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#F8FAFC',
				],
				'elements' => [
					[
						'id'       => 'vispan_proof_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_proof_content',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div style="max-width: 900px; margin: 0 auto;">
										{{seo_case_proof}}
									</div>',
								],
							],
						],
					],
				],
			],

			// 6. SIGNAL STACK & RANKING FACTOR FORMULA
			[
				'id'       => 'vispan_signal_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#FFFFFF',
				],
				'elements' => [
					[
						'id'       => 'vispan_signal_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_signal_content',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div style="max-width: 1000px; margin: 0 auto; font-family: \'DM Sans\', sans-serif;">
										<div class="vispan-reveal-up" style="text-align: center; margin-bottom: 32px; font-family: \'Plus Jakarta Sans\', sans-serif;">
											<div style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 8px;">AUTHORITY WEIGHTING</div>
											<h2 style="font-size: clamp(24px, 3vw, 36px); font-weight: 800; color: #121212; line-height: 1.25; margin: 0 0 10px;">The Local Ranking Signal Stack</h2>
											<p style="font-size: 15px; line-height: 1.6; color: #5D5756; max-width: 720px; margin: 0 auto;">Multi-layered search engine authority signals weighted for sustainable top-tier rankings in {{seo_city_name}}.</p>
										</div>
										{{seo_signal_stack}}
									</div>',
								],
							],
						],
					],
				],
			],

			// 7. LONG-FORM AUTHORITY ESSAY & MILESTONE ROADMAP
			[
				'id'       => 'vispan_essay_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#F8FAFC',
				],
				'elements' => [
					[
						'id'       => 'vispan_essay_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_essay_content',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div style="max-width: 1100px; margin: 0 auto;">
										{{seo_long_essay}}
									</div>',
								],
							],
						],
					],
				],
			],

			// 9. INDUSTRY VERTICAL SPECIALIZATION (Cards)
			[
				'id'       => 'vispan_industry_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#FFFFFF',
				],
				'elements' => [
					[
						'id'       => 'vispan_industry_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_industry_header',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 32px; font-family: \'Plus Jakarta Sans\', sans-serif;">
										<div style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 8px;">TARGETED INDUSTRY HUBS</div>
										<h2 style="font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #121212; margin: 0 0 10px;">Industry-Specific Search Strategies in {{seo_city_name}}</h2>
										<p style="font-family: \'DM Sans\', sans-serif; font-size: 15.5px; color: #5D5756; margin: 0 auto;">Every commercial sector in the metro region requires tailored entity schema and buyer conversion funnels.</p>
									</div>',
								],
							],
							[
								'id'         => 'vispan_industry_widget',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '{{seo_industry_verticals}}',
								],
							],
						],
					],
				],
			],

			// 10. NATIONAL DIRECTORY VS VISPAN COMPARISON TABLE
			[
				'id'       => 'vispan_comparison_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#F8FAFC',
				],
				'elements' => [
					[
						'id'       => 'vispan_comparison_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_comparison_header',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 30px; font-family: \'Plus Jakarta Sans\', sans-serif;">
										<div style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 8px;">STRATEGIC DIFFERENTIATION</div>
										<h2 style="font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #121212; margin: 0 0 10px;">Standard National SEO vs. Vispan Architecture</h2>
										<p style="font-family: \'DM Sans\', sans-serif; font-size: 15.5px; color: #5D5756; margin: 0 auto;">Why thin doorway pages fail in competitive metropolitan markets and how our localized entity graph wins.</p>
									</div>',
								],
							],
							[
								'id'         => 'vispan_comparison_widget',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div style="max-width: 1080px; margin: 0 auto;">{{seo_comparison_table}}</div>',
								],
							],
						],
					],
				],
			],

			// 11. 4-STAGE EXECUTION METHODOLOGY
			[
				'id'       => 'vispan_process_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#FFFFFF',
				],
				'elements' => [
					[
						'id'       => 'vispan_process_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_process_header',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 30px; font-family: \'Plus Jakarta Sans\', sans-serif;">
										<div style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 8px;">EXECUTION ROADMAP</div>
										<h2 style="font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #121212; margin: 0 0 10px;">Our 4-Stage Search Growth Engine</h2>
										<p style="font-family: \'DM Sans\', sans-serif; font-size: 15.5px; color: #5D5756; margin: 0 auto;">A systematic, milestone-driven framework designed to establish compound local authority across {{seo_city_name}}.</p>
									</div>',
								],
							],
							[
								'id'         => 'vispan_process_widget',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div style="max-width: 1140px; margin: 0 auto;">{{seo_process}}</div>',
								],
							],
						],
					],
				],
			],

			// 12. CORE CAPABILITIES (Card Grid)
			[
				'id'       => 'vispan_capabilities_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#FFFFFF',
				],
				'elements' => [
					[
						'id'       => 'vispan_cap_header_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_cap_header',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 32px; font-family: \'Plus Jakarta Sans\', sans-serif;">
										<div style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 8px;">CORE EXPERTISE</div>
										<h2 style="font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #121212; margin: 0 0 10px;">{{seo_service_overview_heading}}</h2>
										<p style="font-family: \'DM Sans\', sans-serif; font-size: 15.5px; color: #5D5756; margin: 0 auto;">{{seo_service_overview_body}}</p>
									</div>',
								],
							],
							[
								'id'         => 'vispan_benefits_widget',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '{{seo_benefits}}',
								],
							],
						],
					],
				],
			],

			// 14. FAQ ACCORDION (Clean White Accordion Cards)
			[
				'id'       => 'vispan_faq_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '55',
						'bottom'   => '55',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#F8FAFC',
				],
				'elements' => [
					[
						'id'       => 'vispan_faq_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_faq_content',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div style="max-width: 880px; margin: 0 auto; font-family: \'DM Sans\', sans-serif;">
										<div class="vispan-reveal-up" style="text-align: center; margin-bottom: 28px; font-family: \'Plus Jakarta Sans\', sans-serif;">
											<div style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 8px;">COMMON QUESTIONS</div>
											<h2 style="font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #121212; margin: 0;">Frequently Asked Questions in {{seo_city_name}}</h2>
										</div>
										{{seo_faqs}}
									</div>',
								],
							],
						],
					],
				],
			],

			// 15. BIG CTA BANNER (Full-width High-Impact Vispan Navy #02426A)
			[
				'id'       => 'vispan_cta_section',
				'elType'   => 'section',
				'settings' => [
					'padding' => [
						'unit'     => 'px',
						'top'      => '65',
						'bottom'   => '65',
						'right'    => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#02426A',
				],
				'elements' => [
					[
						'id'       => 'vispan_cta_col',
						'elType'   => 'column',
						'settings' => [ '_column_size' => 100 ],
						'elements' => [
							[
								'id'         => 'vispan_cta_content',
								'elType'     => 'widget',
								'widgetType' => 'text-editor',
								'settings'   => [
									'editor' => '<div class="vispan-reveal-up" style="text-align: center; max-width: 960px; margin: 0 auto; color: #FFFFFF; font-family: \'Plus Jakarta Sans\', sans-serif;">
										<h2 style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: clamp(28px, 3.8vw, 44px); font-weight: 800; color: #FFFFFF; line-height: 1.15; margin: 0 0 14px;">{{seo_final_cta_heading}}</h2>
										<p style="font-family: \'DM Sans\', sans-serif; font-size: 16px; line-height: 1.6; color: #E2E8F0; max-width: 680px; margin: 0 auto 28px;">{{seo_final_cta_description}}</p>
										<div style="text-align: left;">
											{{seo_inquiry_form_bottom}}
										</div>
									</div>',
								],
							],
						],
					],
				],
			],
		];

		$post_id = wp_insert_post( [
			'post_title'   => sanitize_text_field( $title ),
			'post_type'    => 'elementor_library',
			'post_status'  => 'publish',
			'post_content' => '',
		], true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Set Elementor metadata.
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', 'page' );
		update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );

		Logger::info( sprintf( 'Created starter Elementor template ID %d ("%s")', $post_id, $title ) );

		return (int) $post_id;
	}
}
