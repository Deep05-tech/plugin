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
			/* Universal Typography Spacing Override */
			.elementor-kit-3 h1, .elementor-kit-3 h2, .elementor-kit-3 h3, .elementor-kit-3 h4, .elementor-kit-3 h5, .elementor-kit-3 h6,
			.elementor-kit-3 p, .elementor-kit-3 a, .elementor-kit-3 span, .elementor-kit-3 div,
			.elementor-widget-text-editor h1, .elementor-widget-text-editor h2, .elementor-widget-text-editor h3, .elementor-widget-text-editor h4,
			.vispan-reveal-left, .vispan-reveal-right, .vispan-reveal-up, .vispan-reveal-fade,
			.vispan-reveal-left *, .vispan-reveal-right *, .vispan-reveal-up *, .vispan-reveal-fade *,
			.vispan-hover-card, .vispan-hover-card * {
				letter-spacing: 0px !important;
				word-spacing: normal !important;
			}

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
			// 1. Check for Elementor Page Templates based on vertical
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
		$rendered = '';
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
			$raw_content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );
			$rendered    = \SeoCityPages\Elementor\Elementor_Integration::filter_rendered_content( $raw_content );
		}

		$is_full_page = false !== strpos( $rendered, 'vispan-home-services-page' )
			|| false !== strpos( $rendered, 'vispan-real-estate-page' )
			|| false !== strpos( $rendered, 'vispan-general-agency-page' );

		if ( ! $is_full_page && function_exists( 'get_header' ) ) {
			get_header();
		}

		echo $rendered; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! $is_full_page && function_exists( 'get_footer' ) ) {
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

		$service = \SeoCityPages\Elementor\Page_Context::get_service();

		$is_home_services = (bool) preg_match( '/\b(home\s*services?|home\s*cleaning|contractors?|emergency\s*trades?)\b/i', (string) $service );
		if ( $is_home_services ) {
			echo \SeoCityPages\Elementor\Elementor_Integration::get_animation_assets(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \SeoCityPages\Elementor\Content_Context::render_home_services_full_page_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( function_exists( 'get_footer' ) ) {
				get_footer();
			}
			if ( ! defined( 'SEO_CITY_PAGES_TESTING' ) ) {
				exit;
			}
			return;
		}

		$is_real_estate = (bool) preg_match( '/\b(real\s*estate|realtor|brokerage|housing|realty)\b/i', (string) $service );
		if ( $is_real_estate ) {
			echo \SeoCityPages\Elementor\Elementor_Integration::get_animation_assets(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \SeoCityPages\Elementor\Content_Context::render_real_estate_full_page_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( function_exists( 'get_footer' ) ) {
				get_footer();
			}
			if ( ! defined( 'SEO_CITY_PAGES_TESTING' ) ) {
				exit;
			}
			return;
		}

		// Master General Agency template for all other services (Dental Clinic, Law Firm, Healthcare, B2B/B2C, etc.)
		echo \SeoCityPages\Elementor\Elementor_Integration::get_animation_assets(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo \SeoCityPages\Elementor\Content_Context::render_general_agency_full_page_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( function_exists( 'get_footer' ) ) {
			get_footer();
		}
		if ( ! defined( 'SEO_CITY_PAGES_TESTING' ) ) {
			exit;
		}
		return;
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
