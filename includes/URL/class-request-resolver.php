<?php
/**
 * WordPress Public Request & URL Resolver.
 *
 * @package SeoCityPages\URL
 */

namespace SeoCityPages\URL;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Request Resolver Class.
 */
class Request_Resolver {

	/**
	 * Currently resolved SEO City Page for the active request.
	 *
	 * @var object|null
	 */
	private static $current_page = null;

	/**
	 * Flag indicating if the current request is an authorized preview.
	 *
	 * @var bool
	 */
	private static $is_preview = false;

	/**
	 * Initialize request resolution hooks.
	 *
	 * @return void
	 */
	/**
	 * Initialize request resolution hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'parse_request', [ $this, 'parse_request' ], 1 );
		add_action( 'template_redirect', [ $this, 'resolve_request' ], -10 );
		add_filter( 'template_include', [ $this, 'filter_template_include' ], 99 );
		add_action( 'wp_head', [ $this, 'inject_head_optimizations' ], 1 );
	}

	/**
	 * Inject early CSS in <head> to suppress stuck theme preloaders and prepare clean layout for SEO City Pages.
	 *
	 * @return void
	 */
	public function inject_head_optimizations(): void {
		global $post;
		$is_seo_page = self::$current_page !== null
			|| ( $post && '1' === (string) get_post_meta( $post->ID, \SeoCityPages\Integration\Post_Sync::META_IS_SCP, true ) )
			|| isset( $_GET['elementor-preview'] )
			|| isset( $_GET['preview_id'] )
			|| self::$is_preview;

		if ( ! $is_seo_page ) {
			return;
		}

		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		echo '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">' . "\n";
		echo '<style id="vispan-head-theme-isolation">
			#preloader, .preloader, .arolax-preloader, #loading, .page-loader, .loading-screen, .arolax-page-loading, .preloader-wrap {
				display: none !important;
				opacity: 0 !important;
				visibility: hidden !important;
				pointer-events: none !important;
				z-index: -99999 !important;
			}
		</style>' . "\n";
	}

	/**
	 * Get the currently resolved SEO City Page object.
	 *
	 * @return object|null
	 */
	public static function get_current_page(): ?object {
		return self::$current_page;
	}

	/**
	 * Check if the active request is in preview mode.
	 *
	 * @return bool
	 */
	public static function is_preview(): bool {
		return self::$is_preview;
	}

	/**
	 * Filter template inclusion during Elementor preview to prevent 404 template rendering.
	 *
	 * @param string $template Matched template path.
	 * @return string
	 */
	public function filter_template_include( string $template ): string {
		global $post;
		$is_seo_page = self::$current_page !== null
			|| ( $post && '1' === (string) get_post_meta( $post->ID, \SeoCityPages\Integration\Post_Sync::META_IS_SCP, true ) )
			|| isset( $_GET['elementor-preview'] )
			|| isset( $_GET['preview_id'] )
			|| self::$is_preview;

		if ( $is_seo_page ) {
			// 1. Check for Elementor Full Width Template (Header + Full Width Canvas + Footer, NO sidebar/banner)
			if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->modules_manager ) ) {
				$templates_module = \Elementor\Plugin::$instance->modules_manager->get_modules( 'page-templates' );
				if ( $templates_module && method_exists( $templates_module, 'get_template_path' ) ) {
					$el_template = $templates_module->get_template_path( 'elementor_header_footer' );
					if ( ! empty( $el_template ) && file_exists( $el_template ) ) {
						return $el_template;
					}
				}
			}

			// 2. Check for Theme Full Width Templates
			if ( function_exists( 'locate_template' ) ) {
				$theme_fullwidth = locate_template( [ 'page-templates/full-width.php', 'page-templates/full-container-width.php' ] );
				if ( ! empty( $theme_fullwidth ) && file_exists( $theme_fullwidth ) ) {
					return $theme_fullwidth;
				}
			}

			// 3. Prevent 404 template rendering
			if ( false !== strpos( $template, '404' ) ) {
				if ( function_exists( 'get_page_template' ) ) {
					$page_tpl = get_page_template();
					if ( ! empty( $page_tpl ) && file_exists( $page_tpl ) ) {
						return $page_tpl;
					}
				}
			}
		}

		return $template;
	}

	/**
	 * Parse WordPress request early to map SEO City Pages & Elementor previews to WP post queries.
	 *
	 * @param \WP $wp WordPress environment instance.
	 * @return void
	 */
	public function parse_request( $wp ): void {
		if ( ! is_object( $wp ) || ! isset( $wp->query_vars ) ) {
			return;
		}

		// 1. Detect Elementor editor preview requests
		$preview_post_id = 0;
		if ( isset( $_GET['elementor-preview'] ) ) {
			$preview_post_id = (int) $_GET['elementor-preview'];
		} elseif ( isset( $_GET['preview_id'] ) ) {
			$preview_post_id = (int) $_GET['preview_id'];
		}

		if ( $preview_post_id > 0 ) {
			$wp->query_vars['page_id']   = $preview_post_id;
			$wp->query_vars['post_type'] = 'page';
			$this->hydrate_context_for_post( $preview_post_id, true );
			return;
		}

		// 2. Detect direct SEO City Page URL requests
		$request_uri  = $_SERVER['REQUEST_URI'] ?? '/';
		$path_only    = parse_url( $request_uri, PHP_URL_PATH );
		$matched_path = '/' . trim( (string) $path_only, '/' ) . '/';

		if ( '/' === $matched_path ) {
			return;
		}

		$page = City_Page_Repository::get_by_url_path( $matched_path );
		if ( $page ) {
			// Ensure wp_post exists BEFORE core WP_Query runs on the very first try!
			if ( empty( $page->wp_post_id ) || ! get_post( $page->wp_post_id ) ) {
				$post_id = \SeoCityPages\Integration\Post_Sync::sync_page( (int) $page->id );
				if ( $post_id > 0 ) {
					$page->wp_post_id = $post_id;
				}
			}

			$this->hydrate_context_for_page( $page, false );
			if ( ! empty( $page->wp_post_id ) ) {
				$wp->query_vars['page_id']   = (int) $page->wp_post_id;
				$wp->query_vars['post_type'] = 'page';
			}
		}
	}

	/**
	 * Hydrate Page_Context for a given WordPress Post ID.
	 *
	 * @param int  $post_id    WP Post ID.
	 * @param bool $is_preview Preview mode flag.
	 * @return object|null Resolved page object.
	 */
	public function hydrate_context_for_post( int $post_id, bool $is_preview = false ): ?object {
		if ( $post_id <= 0 ) {
			return null;
		}

		$city_page_id = 0;
		if ( function_exists( 'get_post_meta' ) ) {
			$city_page_id = (int) get_post_meta( $post_id, \SeoCityPages\Integration\Post_Sync::META_PAGE_ID, true );
		}

		$page = null;
		if ( $city_page_id > 0 ) {
			$page = City_Page_Repository::get( $city_page_id );
		}

		if ( ! $page ) {
			$post_obj = function_exists( 'get_post' ) ? get_post( $post_id ) : null;
			if ( $post_obj && ! empty( $post_obj->post_name ) ) {
				$page = City_Page_Repository::get_by_slug( $post_obj->post_name );
			}
		}

		if ( $page ) {
			$this->hydrate_context_for_page( $page, $is_preview );
		}

		return $page;
	}

	/**
	 * Hydrate Page_Context for a given SEO City Page record.
	 *
	 * @param object $page       Page record.
	 * @param bool   $is_preview Preview mode flag.
	 * @return void
	 */
	public function hydrate_context_for_page( object $page, bool $is_preview = false ): void {
		$country = Country_Repository::get( (int) $page->country_id );
		$state   = State_Repository::get( (int) $page->state_id );
		$city    = City_Repository::get( (int) $page->city_id );
		$service = Service_Repository::get( (int) $page->service_id );

		self::$current_page = $page;
		self::$is_preview   = $is_preview;

		\SeoCityPages\Elementor\Page_Context::set_context( $page, $country, $state, $city, $service );
		if ( $is_preview ) {
			\SeoCityPages\Elementor\Page_Context::set_preview_mode( true );
		}
	}

	/**
	 * Core request resolution logic.
	 *
	 * @return void
	 */
	public function resolve_request(): void {
		// 0. DO NOT HIJACK OR EXIT IF IN ELEMENTOR PREVIEW, EDITOR, OR AJAX MODE.
		$is_elementor_preview = isset( $_GET['elementor-preview'] )
			|| isset( $_GET['preview_id'] )
			|| ( isset( $_REQUEST['action'] ) && 'elementor' === $_REQUEST['action'] )
			|| ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() )
			|| ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode() )
			|| ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode() );

		if ( $is_elementor_preview ) {
			$preview_post_id = (int) ( $_GET['elementor-preview'] ?? ( $_GET['preview_id'] ?? ( $_GET['post'] ?? ( $_GET['p'] ?? ( $_GET['page_id'] ?? 0 ) ) ) ) );
			if ( $preview_post_id <= 0 && function_exists( 'get_the_ID' ) ) {
				$preview_post_id = (int) get_the_ID();
			}

			if ( $preview_post_id > 0 ) {
				$this->hydrate_context_for_post( $preview_post_id, true );
			}

			// Clear 404 state and set up current queried object.
			global $wp_query, $post;
			if ( isset( $wp_query ) ) {
				$wp_query->is_404      = false;
				$wp_query->is_page     = true;
				$wp_query->is_singular = true;
				$wp_query->is_single   = false;

				if ( $preview_post_id > 0 && function_exists( 'get_post' ) ) {
					$post_obj = get_post( $preview_post_id );
					if ( $post_obj ) {
						$wp_query->queried_object    = $post_obj;
						$wp_query->queried_object_id = $post_obj->ID;
						$wp_query->posts             = [ $post_obj ];
						$wp_query->post_count        = 1;
						$post                        = $post_obj;
						if ( function_exists( 'setup_postdata' ) ) {
							setup_postdata( $post_obj );
						}
					}
				}
			}

			if ( function_exists( 'status_header' ) ) {
				status_header( 200 );
			}

			return; // Hand over control cleanly to Elementor builder!
		}

		// 1. Check for Admin Preview request.
		if ( isset( $_GET['scp_preview_page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->handle_preview_request();
			return;
		}

		// 2. Extract requested relative path.
		$request_uri  = $_SERVER['REQUEST_URI'] ?? '/';
		$path_only    = parse_url( $request_uri, PHP_URL_PATH );
		$matched_path = '/' . trim( (string) $path_only, '/' ) . '/';

		if ( '/' === $matched_path ) {
			return; // Homepage, leave untouched.
		}

		// 3. Fast indexed query against wp_seo_city_pages.
		$page = City_Page_Repository::get_by_url_path( $matched_path );

		if ( ! $page ) {
			// Not an SEO City Page URL. Allow WordPress to handle normally (/about, /contact, etc.).
			return;
		}

		// 4. Validate Page Status & Source Visibility.
		if ( 'published' !== $page->status ) {
			// Draft, private, or trashed page requested publicly -> 404.
			$this->trigger_404();
			return;
		}

		// 5. Validate that all parent source entities are active.
		$country = Country_Repository::get( (int) $page->country_id );
		$state   = State_Repository::get( (int) $page->state_id );
		$city    = City_Repository::get( (int) $page->city_id );
		$service = Service_Repository::get( (int) $page->service_id );

		if (
			! $country || 'active' !== $country->status ||
			! $state   || 'active' !== $state->status ||
			! $city    || 'active' !== $city->status ||
			! $service || 'active' !== $service->status
		) {
			// Inactive source record -> 404 publicly.
			$this->trigger_404();
			return;
		}

		// 6. Ensure WordPress post exists and setup query.
		if ( empty( $page->wp_post_id ) || ! get_post( $page->wp_post_id ) ) {
			$post_id = \SeoCityPages\Integration\Post_Sync::sync_page( (int) $page->id );
			if ( $post_id > 0 ) {
				$page->wp_post_id = $post_id;
			}
		}

		self::$current_page = $page;
		\SeoCityPages\Elementor\Page_Context::set_context( $page, $country, $state, $city, $service );

		global $wp_query, $post;
		if ( isset( $wp_query ) ) {
			$wp_query->is_404      = false;
			$wp_query->is_page     = true;
			$wp_query->is_singular = true;
			$wp_query->is_single   = false;

			if ( ! empty( $page->wp_post_id ) ) {
				$post_obj = get_post( (int) $page->wp_post_id );
				if ( $post_obj ) {
					$wp_query->queried_object    = $post_obj;
					$wp_query->queried_object_id = $post_obj->ID;
					$wp_query->posts             = [ $post_obj ];
					$wp_query->post_count        = 1;
					$post                        = $post_obj;
					if ( function_exists( 'setup_postdata' ) ) {
						setup_postdata( $post_obj );
					}
				}
			}
		}

		if ( function_exists( 'status_header' ) ) {
			status_header( 200 );
		}

		do_action( 'seo_city_pages_before_render', $page, false );

		// If a real synced post exists, pass control cleanly to WordPress template loader (page.php)!
		if ( ! empty( $page->wp_post_id ) && get_post( $page->wp_post_id ) && ! defined( 'SEO_CITY_PAGES_TESTING' ) ) {
			return;
		}

		$this->render_page_response( $page );
	}

	/**
	 * Handle authenticated admin preview request.
	 *
	 * @return void
	 */
	private function handle_preview_request(): void {
		$page_id = (int) ( $_GET['scp_preview_page'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce   = sanitize_text_field( $_GET['_scp_preview_nonce'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_die( esc_html__( 'You do not have permission to preview this page.', 'seo-city-pages' ), 403 );
		}

		if ( ! Security::verify_nonce( $nonce, 'scp_preview_' . $page_id ) ) {
			wp_die( esc_html__( 'Invalid or expired preview token. Please return to the admin panel and click Preview again.', 'seo-city-pages' ), 403 );
		}

		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			wp_die( esc_html__( 'The requested SEO City Page does not exist.', 'seo-city-pages' ), 404 );
		}

		$country = Country_Repository::get( (int) $page->country_id );
		$state   = State_Repository::get( (int) $page->state_id );
		$city    = City_Repository::get( (int) $page->city_id );
		$service = Service_Repository::get( (int) $page->service_id );

		self::$current_page = $page;
		self::$is_preview    = true;
		\SeoCityPages\Elementor\Page_Context::set_context( $page, $country, $state, $city, $service );
		\SeoCityPages\Elementor\Page_Context::set_preview_mode( true );

		$this->render_page_response( $page, true );
	}

	/**
	 * Setup WordPress query state and render the resolved SEO City Page.
	 *
	 * @param object $page SEO City Page record.
	 * @param bool   $is_preview Whether preview mode.
	 * @return void
	 */
	private function render_page_response( object $page, bool $is_preview = false ): void {
		global $wp_query;

		if ( isset( $wp_query ) ) {
			$wp_query->is_404  = false;
			$wp_query->is_page = true;
		}

		status_header( 200 );

		// Fire before-render hook for SEO metadata and integrations.
		do_action( 'seo_city_pages_before_render', $page, $is_preview );

		// Check if Elementor is active and master template is configured.
		if ( \SeoCityPages\Environment::is_elementor_active() ) {
			$settings    = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME, [] );
			$template_id = (int) ( $settings['template_id'] ?? 0 );

			if ( $template_id > 0 ) {
				$tpl_post = get_post( $template_id );
				if ( $tpl_post && 'elementor_library' === $tpl_post->post_type && 'trash' !== $tpl_post->post_status ) {
					$this->render_elementor_template( $template_id );
					return;
				}
			}
		}

		// Fallback rendering if Elementor is inactive or no template selected.
		$this->render_fallback_page( $page, $is_preview );
	}

	/**
	 * Render the page content via Elementor frontend builder.
	 *
	 * @param int $template_id Elementor template post ID.
	 * @return void
	 */
	private function render_elementor_template( int $template_id ): void {
		if ( function_exists( 'get_header' ) ) {
			get_header();
		}

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
			$raw_content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );
			echo \SeoCityPages\Elementor\Elementor_Integration::filter_rendered_content( $raw_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( function_exists( 'get_footer' ) ) {
			get_footer();
		}

		if ( ! defined( 'SEO_CITY_PAGES_TESTING' ) ) {
			exit;
		}
	}

	/**
	 * Render clean semantic HTML fallback when Elementor is not configured.
	 *
	 * @param object $page       SEO City Page record.
	 * @param bool   $is_preview Whether preview mode.
	 * @return void
	 */
	private function render_fallback_page( object $page, bool $is_preview = false ): void {
		if ( function_exists( 'get_header' ) ) {
			get_header();
		}

		$hero_title       = \SeoCityPages\Elementor\Content_Context::get_hero_title();
		$hero_description = \SeoCityPages\Elementor\Content_Context::get_hero_description();
		$hero_cta         = \SeoCityPages\Elementor\Content_Context::get_hero_cta();
		$intro_heading    = \SeoCityPages\Elementor\Content_Context::get_intro_heading();
		$intro_body       = \SeoCityPages\Elementor\Content_Context::get_intro_body();
		$overview_heading = \SeoCityPages\Elementor\Content_Context::get_service_overview_heading();
		$overview_body    = \SeoCityPages\Elementor\Content_Context::get_service_overview_body();
		$final_cta_head   = \SeoCityPages\Elementor\Content_Context::get_final_cta_heading();
		$final_cta_desc   = \SeoCityPages\Elementor\Content_Context::get_final_cta_description();
		$final_cta_btn    = \SeoCityPages\Elementor\Content_Context::get_final_cta_button();

		$city    = \SeoCityPages\Elementor\Page_Context::get_city();
		$state   = \SeoCityPages\Elementor\Page_Context::get_state();
		$service = \SeoCityPages\Elementor\Page_Context::get_service();
		$url     = \SeoCityPages\Elementor\Page_Context::get_url();

		echo \SeoCityPages\Elementor\Elementor_Integration::get_animation_assets(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<main id="primary" class="site-main seo-city-page-container" style="font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1e1e1e; line-height: 1.6; background: #ffffff;">

			<!-- 1. HERO SECTION -->
			<section class="seo-hero-section" style="padding: 80px 24px 70px; background: #FFFFFF; max-width: 1200px; margin: 0 auto;">
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 48px; align-items: center;">
					<div class="vispan-reveal-left">
						<div style="display: inline-flex; align-items: center; gap: 8px; background: #F0F6FA; border: 1px solid #CCE0ED; border-radius: 30px; padding: 6px 16px; margin-bottom: 18px;">
							<span style="font-size: 12px; font-weight: 800; color: #02426A; letter-spacing: 0.12em; text-transform: uppercase;">⭐ 5.0 ON GOOGLE • <?php echo esc_html( $city ); ?> EXPERTISE</span>
						</div>
						<h1 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(30px, 4vw, 50px); font-weight: 800; line-height: 1.2; letter-spacing: 0px; word-spacing: 1.5px; color: #121212; margin: 0 0 18px;"><?php echo esc_html( $hero_title ); ?></h1>
						<p style="font-size: 18px; line-height: 1.65; color: #5D5756; margin: 0 0 24px; max-width: 580px;"><?php echo esc_html( $hero_description ); ?></p>
						<div style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 32px; font-size: 14.5px; font-weight: 600; color: #121212;">
							<span style="display: inline-flex; align-items: center; gap: 6px;"><span style="color: #02426A; font-weight: 900;">✓</span> 100% ROI-Focused</span>
							<span style="display: inline-flex; align-items: center; gap: 6px;"><span style="color: #02426A; font-weight: 900;">✓</span> Dedicated Strategist</span>
							<span style="display: inline-flex; align-items: center; gap: 6px;"><span style="color: #02426A; font-weight: 900;">✓</span> Submarket Targeting</span>
						</div>
						<div style="display: flex; flex-wrap: wrap; align-items: center; gap: 18px;">
							<a href="<?php echo esc_url( $url ); ?>" class="vispan-btn-glow" style="display: inline-block; background: #02426A; color: #FFFFFF; font-weight: 700; font-size: 14px; letter-spacing: 0.04em; text-transform: uppercase; padding: 16px 36px; border-radius: 100px; text-decoration: none; box-shadow: 0 12px 28px -10px rgba(2,66,106,0.5);"><?php echo esc_html( $hero_cta ); ?></a>
							<a href="tel:+918485986860" style="font-size: 14.5px; font-weight: 700; color: #02426A; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">📞 +91 84859 86860</a>
						</div>
					</div>
					<div class="vispan-reveal-right vispan-delay-1 vispan-hover-card" style="background: #F0F6FA; border: 1.5px solid #CCE0ED; border-radius: 24px; padding: 0 0 28px; overflow: hidden; box-shadow: 0 20px 45px -25px rgba(2,66,106,0.2);">
						<div style="height: 170px; position: relative; overflow: hidden;">
							<img src="<?php echo esc_url( \SeoCityPages\Elementor\Content_Context::get_image_url( 'seo-strategy-team.jpg' ) ); ?>" alt="Local SEO Strategy Team" style="width: 100%; height: 100%; object-fit: cover;" />
							<div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(2,66,106,0.15) 0%, rgba(2,66,106,0.85) 100%);"></div>
							<div style="position: absolute; bottom: 12px; left: 18px; right: 18px; display: flex; justify-content: space-between; align-items: flex-end;">
								<span style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 0.14em; text-transform: uppercase; color: #BEE3F8; background: rgba(2,66,106,0.7); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2);">VISPAN LOCAL ADVANTAGE</span>
								<span style="background: #00a32a; color: #FFFFFF; font-size: 10.5px; font-weight: 800; padding: 3px 8px; border-radius: 12px;">ACTIVE STRATEGY</span>
							</div>
						</div>
						<div style="padding: 22px 24px 0;">
							<h3 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 20px; font-weight: 800; color: #121212; margin: 0 0 16px;">Market Growth In <?php echo esc_html( $city ); ?></h3>
							<div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
								<div style="background: #FFFFFF; border: 1px solid #CCE0ED; border-radius: 12px; padding: 12px 14px; display: flex; align-items: flex-start; gap: 10px;">
									<span style="background: #e6f7ec; color: #00a32a; font-size: 15px; font-weight: 900; width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">✓</span>
									<div>
										<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px; font-weight: 800; color: #121212;">Granular Submarket Graph</div>
										<div style="font-size: 12px; color: #5D5756; line-height: 1.4;">Geo-coordinated schema capturing high-CPC commercial searchers.</div>
									</div>
								</div>
								<div style="background: #FFFFFF; border: 1px solid #CCE0ED; border-radius: 12px; padding: 12px 14px; display: flex; align-items: flex-start; gap: 10px;">
									<span style="background: #e6f7ec; color: #00a32a; font-size: 15px; font-weight: 900; width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">✓</span>
									<div>
										<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px; font-weight: 800; color: #121212;">Map Pack & SERP Velocity</div>
										<div style="font-size: 12px; color: #5D5756; line-height: 1.4;">Top 3 Google positioning framework with verified citation audit.</div>
									</div>
								</div>
								<div style="background: #FFFFFF; border: 1px solid #CCE0ED; border-radius: 12px; padding: 12px 14px; display: flex; align-items: flex-start; gap: 10px;">
									<span style="background: #e6f7ec; color: #00a32a; font-size: 15px; font-weight: 900; width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">✓</span>
									<div>
										<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13.5px; font-weight: 800; color: #121212;">Dedicated Senior Strategist</div>
										<div style="font-size: 12px; color: #5D5756; line-height: 1.4;">Direct partner consultation with monthly executive ROI reviews.</div>
									</div>
								</div>
							</div>
							<div style="background: #02426A; border-radius: 12px; padding: 12px 16px; text-align: center; color: #FFFFFF;">
								<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.04em;">📍 Tailored specifically for <?php echo esc_html( $city . ', ' . $state ); ?></div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- 2. CITYSCAPE / MARKET KICKER BANNER -->
			<section style="padding: 75px 24px; background: #02426A; color: #FFFFFF;">
				<div class="vispan-reveal-up" style="position: relative; text-align: center; max-width: 900px; margin: 0 auto; padding: 40px 30px; background: linear-gradient(rgba(2,66,106,0.92), rgba(2,66,106,0.92)), url('<?php echo esc_url( \SeoCityPages\Elementor\Content_Context::get_image_url( 'city-market-data.jpg' ) ); ?>') center/cover no-repeat; border-radius: 20px; border: 1.5px solid rgba(255,255,255,0.15); box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5);">
					<div style="font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #BEE3F8; margin-bottom: 12px;">LOCAL SEARCH MARKET DYNAMICS</div>
					<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #FFFFFF; line-height: 1.25; margin: 0 0 14px;"><?php echo esc_html( $intro_heading ); ?></h2>
					<p style="font-size: 16.5px; line-height: 1.65; color: #E2E8F0; max-width: 740px; margin: 0 auto;"><?php echo function_exists( 'wp_kses_post' ) ? \wp_kses_post( $intro_body ) : esc_html( $intro_body ); ?></p>
				</div>
			</section>

			<!-- 3. MARKET ANATOMY & METRO STATS -->
			<section style="padding: 85px 24px; background: #FFFFFF;">
				<div style="max-width: 1140px; margin: 0 auto;">
					<div style="margin-bottom: 40px;">
						<?php echo \SeoCityPages\Elementor\Content_Context::render_market_anatomy_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div style="margin-top: 30px;">
						<?php echo \SeoCityPages\Elementor\Content_Context::render_metro_stats_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</section>

			<!-- 4. SEARCH INTENT ACCORDION -->
			<section style="padding: 85px 24px; background: #F8FAFC;">
				<div style="max-width: 880px; margin: 0 auto;">
					<div class="vispan-reveal-up" style="text-align: center; margin-bottom: 40px;">
						<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">SEARCH INTENT ARCHITECTURE</div>
						<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; color: #121212; margin: 0;">How We Capture High-Intent Traffic in <?php echo esc_html( $city ); ?></h2>
					</div>
					<?php echo \SeoCityPages\Elementor\Content_Context::render_market_accordion_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 5. THE SUBMARKETS GRID -->
			<section style="padding: 85px 24px; background: #FFFFFF;">
				<div style="max-width: 1140px; margin: 0 auto;">
					<div class="vispan-reveal-up" style="text-align: center; margin-bottom: 48px;">
						<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">HYPER-LOCAL COVERAGE</div>
						<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; color: #121212; margin: 0 0 12px;">The <?php echo esc_html( $city ); ?> Submarket Ecosystem</h2>
						<p style="font-size: 16.5px; color: #5D5756; max-width: 680px; margin: 0 auto;">We target individual commercial nodes across the metropolitan region, establishing local radius dominance.</p>
					</div>
					<?php echo \SeoCityPages\Elementor\Content_Context::render_submarkets_grid_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 6. PROOF / CASE STUDY HIGHLIGHT -->
			<section style="padding: 85px 24px; background: #F8FAFC;">
				<div style="max-width: 900px; margin: 0 auto;">
					<?php echo \SeoCityPages\Elementor\Content_Context::render_case_proof_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 7. SIGNAL STACK & RANKING FACTOR FORMULA -->
			<section style="padding: 85px 24px; background: #FFFFFF;">
				<div style="max-width: 1140px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 48px; align-items: center;">
					<div class="vispan-reveal-left">
						<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">AUTHORITY WEIGHTING</div>
						<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(26px, 3.2vw, 38px); font-weight: 800; color: #121212; line-height: 1.25; margin: 0 0 18px;">The Local Ranking Signal Stack</h2>
						<p style="font-size: 16px; line-height: 1.65; color: #5D5756; margin: 0 0 20px;">Search algorithms evaluate multi-layered authority signals before ranking a business in the top 3 Map Pack positions in <?php echo esc_html( $city ); ?>.</p>
						<p style="font-size: 16px; line-height: 1.65; color: #5D5756; margin: 0 0 24px;">Our proprietary execution engine balances on-page topical completeness with off-page geographic verification to ensure sustained visibility.</p>
						<a href="<?php echo esc_url( $url ); ?>" class="vispan-btn-glow" style="display: inline-block; background: #02426A; color: #FFFFFF; font-weight: 700; font-size: 13.5px; letter-spacing: 0.04em; text-transform: uppercase; padding: 14px 28px; border-radius: 100px; text-decoration: none;">Get Free Strategy Audit →</a>
					</div>
					<div class="vispan-reveal-right vispan-delay-1">
						<?php echo \SeoCityPages\Elementor\Content_Context::render_signal_stack_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</section>

			<!-- 8. LONG-FORM AUTHORITY ESSAY -->
			<section style="padding: 85px 24px; background: #F8FAFC;">
				<div style="max-width: 1140px; margin: 0 auto;">
					<?php echo \SeoCityPages\Elementor\Content_Context::render_essay_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 9. INDUSTRY VERTICAL SPECIALIZATION -->
			<section style="padding: 85px 24px; background: #FFFFFF;">
				<div style="max-width: 1140px; margin: 0 auto;">
					<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 48px;">
						<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">TARGETED INDUSTRY HUBS</div>
						<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; color: #121212; margin: 0 0 12px;">Industry-Specific Search Strategies in <?php echo esc_html( $city ); ?></h2>
						<p style="font-size: 16.5px; color: #5D5756; margin: 0 auto;">Every commercial sector in the metro region requires tailored entity schema and buyer conversion funnels.</p>
					</div>
					<?php echo \SeoCityPages\Elementor\Content_Context::render_industry_verticals_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 10. NATIONAL DIRECTORY VS VISPAN COMPARISON TABLE -->
			<section style="padding: 85px 24px; background: #F8FAFC;">
				<div style="max-width: 1080px; margin: 0 auto;">
					<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 44px;">
						<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">STRATEGIC DIFFERENTIATION</div>
						<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; color: #121212; margin: 0 0 12px;">Standard National SEO vs. Vispan Architecture</h2>
						<p style="font-size: 16.5px; color: #5D5756; margin: 0 auto;">Why thin doorway pages fail in competitive metropolitan markets and how our localized entity graph wins.</p>
					</div>
					<?php echo \SeoCityPages\Elementor\Content_Context::render_comparison_table_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 11. 4-STAGE EXECUTION METHODOLOGY -->
			<section style="padding: 85px 24px; background: #FFFFFF;">
				<div style="max-width: 1140px; margin: 0 auto;">
					<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 44px;">
						<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">EXECUTION ROADMAP</div>
						<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; color: #121212; margin: 0 0 12px;">Our 4-Stage Search Growth Engine</h2>
						<p style="font-size: 16.5px; color: #5D5756; margin: 0 auto;">A systematic, milestone-driven framework designed to establish compound local authority across <?php echo esc_html( $city ); ?>.</p>
					</div>
					<?php echo \SeoCityPages\Elementor\Content_Context::render_process_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 12. 12-POINT TECHNICAL DELIVERABLES CHECKLIST -->
			<section style="padding: 85px 24px; background: #F8FAFC;">
				<div style="max-width: 1140px; margin: 0 auto;">
					<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 44px;">
						<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">TRANSPARENT DELIVERABLES</div>
						<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; color: #121212; margin: 0 0 12px;">Complete Local SEO Deliverables Package</h2>
						<p style="font-size: 16.5px; color: #5D5756; margin: 0 auto;">Everything included in your dedicated <?php echo esc_html( $city ); ?> search growth campaign.</p>
					</div>
					<?php echo \SeoCityPages\Elementor\Content_Context::render_deliverables_checklist_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 13. CORE CAPABILITIES / BENEFITS -->
			<section style="padding: 85px 24px; background: #FFFFFF;">
				<div style="max-width: 1140px; margin: 0 auto;">
					<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 48px;">
						<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">CORE EXPERTISE</div>
						<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; color: #121212; margin: 0 0 12px;"><?php echo esc_html( $overview_heading ); ?></h2>
						<p style="font-size: 16.5px; color: #5D5756; margin: 0 auto;"><?php echo function_exists( 'wp_kses_post' ) ? \wp_kses_post( $overview_body ) : esc_html( $overview_body ); ?></p>
					</div>
					<?php echo \SeoCityPages\Elementor\Content_Context::render_benefits_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 14. FAQ ACCORDION -->
			<section style="padding: 85px 24px; background: #F8FAFC;">
				<div style="max-width: 880px; margin: 0 auto;">
					<div class="vispan-reveal-up" style="text-align: center; margin-bottom: 36px;">
						<div style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">COMMON QUESTIONS</div>
						<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; color: #121212; margin: 0;">Frequently Asked Questions in <?php echo esc_html( $city ); ?></h2>
					</div>
					<?php echo \SeoCityPages\Elementor\Content_Context::render_faqs_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>

			<!-- 15. CLOSING CALL TO ACTION -->
			<section style="padding: 85px 24px; background: #02426A; color: #FFFFFF;">
				<div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto;">
					<h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: clamp(30px, 4vw, 48px); font-weight: 800; color: #FFFFFF; line-height: 1.15; margin: 0 0 16px;"><?php echo esc_html( $final_cta_head ); ?></h2>
					<p style="font-size: 17px; line-height: 1.65; color: #E2E8F0; max-width: 640px; margin: 0 auto 32px;"><?php echo esc_html( $final_cta_desc ); ?></p>
					<div>
						<a href="<?php echo esc_url( $url ); ?>" class="vispan-btn-glow" style="display: inline-block; background: #FFFFFF; color: #02426A; font-family: 'DM Sans', sans-serif; font-weight: 800; font-size: 14.5px; letter-spacing: 0.06em; text-transform: uppercase; padding: 18px 40px; border-radius: 100px; text-decoration: none; box-shadow: 0 12px 30px rgba(0,0,0,0.25);"><?php echo esc_html( $final_cta_btn ); ?></a>
					</div>
				</div>
			</section>
		</main>
		<?php
		if ( function_exists( 'get_footer' ) ) {
			get_footer();
		}

		if ( ! defined( 'SEO_CITY_PAGES_TESTING' ) ) {
			exit;
		}
	}

	/**
	 * Trigger WordPress 404 response cleanly.
	 *
	 * @return void
	 */
	private function trigger_404(): void {
		global $wp_query;

		if ( isset( $wp_query ) ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		nocache_headers();
	}
}
