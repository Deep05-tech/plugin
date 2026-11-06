<?php
/**
 * Admin Menu Registrar & Screen Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Environment;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Menu Class.
 */
class Admin_Menu {

	/**
	 * Top-level menu slug.
	 */
	public const MENU_SLUG = 'seo-city-pages';

	/**
	 * Hook suffixes registered for plugin pages.
	 *
	 * @var string[]
	 */
	private $hook_suffixes = [];

	/**
	 * Flag indicating whether menus have already been registered.
	 *
	 * @var bool
	 */
	private static $menus_registered = false;

	/**
	 * Initialize admin menu hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_menu', [ $this, 'deduplicate_submenus' ], 999 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_notices', [ $this, 'render_environment_notices' ] );
		add_filter( 'parent_file', [ $this, 'filter_parent_file' ] );
	}

	/**
	 * Register top-level and submenu pages.
	 *
	 * @return void
	 */
	public function register_menus(): void {
		if ( self::$menus_registered ) {
			return;
		}
		self::$menus_registered = true;

		// Top Level: SEO City Pages (Dashboard)
		$main_hook = add_menu_page(
			__( 'SEO City Pages', 'seo-city-pages' ),
			__( 'SEO City Pages', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG,
			[ $this, 'render_dashboard_page' ],
			'dashicons-location-alt',
			30
		);
		$this->hook_suffixes[] = $main_hook;

		// Submenu: Dashboard
		$dashboard_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard — SEO City Pages', 'seo-city-pages' ),
			__( 'Dashboard', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG,
			[ $this, 'render_dashboard_page' ]
		);
		$this->hook_suffixes[] = $dashboard_hook;

		// Submenu: CSV Import
		$import_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'CSV Import — SEO City Pages', 'seo-city-pages' ),
			__( 'CSV Import', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-import',
			[ $this, 'render_import_page' ]
		);
		$this->hook_suffixes[] = $import_hook;

		// Submenu: Keywords Bank
		$keywords_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Keywords — SEO City Pages', 'seo-city-pages' ),
			__( 'Keywords', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-keywords',
			[ $this, 'render_keywords_page' ]
		);
		$this->hook_suffixes[] = $keywords_hook;

		// Submenu: Pages
		$pages_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Pages — SEO City Pages', 'seo-city-pages' ),
			__( 'Pages', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-pages',
			[ $this, 'render_pages_page' ]
		);
		$this->hook_suffixes[] = $pages_hook;

		// Submenu: Elementor Template
		$template_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Elementor Template — SEO City Pages', 'seo-city-pages' ),
			__( 'Elementor Template', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-template',
			[ $this, 'render_template_page' ]
		);
		$this->hook_suffixes[] = $template_hook;

		// Submenu: Bulk Operations
		$bulk_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Bulk Operations — SEO City Pages', 'seo-city-pages' ),
			__( 'Bulk Operations', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-bulk',
			[ $this, 'render_bulk_page' ]
		);
		$this->hook_suffixes[] = $bulk_hook;

		// Submenu: Settings
		$settings_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings — SEO City Pages', 'seo-city-pages' ),
			__( 'Settings', 'seo-city-pages' ),
			Capabilities::MANAGE_SETTINGS,
			self::MENU_SLUG . '-settings',
			[ $this, 'render_settings_page' ]
		);
		$this->hook_suffixes[] = $settings_hook;

		/*
		|--------------------------------------------------------------------------
		| Options Moved from Sidebar to Dashboard (Accessible via Dashboard Cards)
		|--------------------------------------------------------------------------
		| Registered with parent_slug = null so they do not clutter the sidebar,
		| while keeping their routes, capabilities, and screen renders fully active.
		*/

		// Moved to Dashboard: 1. County Generator
		$county_gen_hook = add_submenu_page(
			null,
			__( 'County Generator — SEO City Pages', 'seo-city-pages' ),
			__( 'County Generator', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-county-generator',
			[ $this, 'render_county_generator_page' ]
		);
		$this->hook_suffixes[] = $county_gen_hook;

		// Moved to Dashboard: 2. Countries
		$countries_hook = add_submenu_page(
			null,
			__( 'Countries — SEO City Pages', 'seo-city-pages' ),
			__( 'Countries', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-countries',
			[ $this, 'render_countries_page' ]
		);
		$this->hook_suffixes[] = $countries_hook;

		// Moved to Dashboard: 3. States / Regions
		$states_hook = add_submenu_page(
			null,
			__( 'States / Regions — SEO City Pages', 'seo-city-pages' ),
			__( 'States / Regions', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-states',
			[ $this, 'render_states_page' ]
		);
		$this->hook_suffixes[] = $states_hook;

		// Moved to Dashboard: 4. Cities
		$cities_hook = add_submenu_page(
			null,
			__( 'Cities — SEO City Pages', 'seo-city-pages' ),
			__( 'Cities', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-cities',
			[ $this, 'render_cities_page' ]
		);
		$this->hook_suffixes[] = $cities_hook;

		// Moved to Dashboard: 5. Services
		$services_hook = add_submenu_page(
			null,
			__( 'Services — SEO City Pages', 'seo-city-pages' ),
			__( 'Services', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-services',
			[ $this, 'render_services_page' ]
		);
		$this->hook_suffixes[] = $services_hook;

		// Moved to Dashboard: 6. System Status
		$status_hook = add_submenu_page(
			null,
			__( 'System Status — SEO City Pages', 'seo-city-pages' ),
			__( 'System Status', 'seo-city-pages' ),
			Capabilities::VIEW_STATUS,
			self::MENU_SLUG . '-status',
			[ $this, 'render_status_page' ]
		);
		$this->hook_suffixes[] = $status_hook;

		// Moved to Dashboard: 7. SEO Controls
		$seo_hook = add_submenu_page(
			null,
			__( 'SEO Integration — SEO City Pages', 'seo-city-pages' ),
			__( 'SEO Controls', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-seo',
			[ $this, 'render_seo_page' ]
		);
		$this->hook_suffixes[] = $seo_hook;

		// Moved to Dashboard: 8. System Health
		$health_hook = add_submenu_page(
			null,
			__( 'System Health — SEO City Pages', 'seo-city-pages' ),
			__( 'System Health', 'seo-city-pages' ),
			Capabilities::VIEW_HEALTH,
			self::MENU_SLUG . '-health',
			[ $this, 'render_health_page' ]
		);
		$this->hook_suffixes[] = $health_hook;

		// Moved to Dashboard: 9. Quality Dashboard
		$quality_hook = add_submenu_page(
			null,
			__( 'Quality Dashboard — SEO City Pages', 'seo-city-pages' ),
			__( 'Quality Dashboard', 'seo-city-pages' ),
			Capabilities::MANAGE_QUALITY,
			self::MENU_SLUG . '-quality',
			[ $this, 'render_quality_page' ]
		);
		$this->hook_suffixes[] = $quality_hook;

		// Moved to Dashboard: 10. Audit Log
		$audit_hook = add_submenu_page(
			null,
			__( 'Audit Log — SEO City Pages', 'seo-city-pages' ),
			__( 'Audit Log', 'seo-city-pages' ),
			Capabilities::VIEW_AUDIT_LOGS,
			self::MENU_SLUG . '-audit',
			[ $this, 'render_audit_page' ]
		);
		$this->hook_suffixes[] = $audit_hook;
	}

	/**
	 * Deduplicate any accidentally registered duplicate submenu entries.
	 *
	 * @return void
	 */
	public function deduplicate_submenus(): void {
		global $submenu;
		if ( empty( $submenu[ self::MENU_SLUG ] ) || ! is_array( $submenu[ self::MENU_SLUG ] ) ) {
			return;
		}

		$seen_slugs      = [];
		$unique_submenus = [];

		foreach ( $submenu[ self::MENU_SLUG ] as $item ) {
			$slug = $item[2] ?? '';
			if ( ! empty( $slug ) && ! isset( $seen_slugs[ $slug ] ) ) {
				$seen_slugs[ $slug ] = true;
				$unique_submenus[]   = $item;
			}
		}

		$submenu[ self::MENU_SLUG ] = $unique_submenus;
	}

	/**
	 * Enqueue admin stylesheet and script only on plugin screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$page = sanitize_text_field( $_GET['page'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_plugin_screen = in_array( $hook_suffix, $this->hook_suffixes, true )
			|| ( false !== strpos( $hook_suffix, 'seo-city-pages' ) )
			|| ( false !== strpos( $page, 'seo-city-pages' ) );

		if ( ! $is_plugin_screen ) {
			return;
		}

		$css_url = SEO_CITY_PAGES_PLUGIN_URL . 'assets/css/admin.css';
		$js_url  = SEO_CITY_PAGES_PLUGIN_URL . 'assets/js/admin.js';

		wp_enqueue_style(
			'seo-city-pages-admin',
			$css_url,
			[],
			SEO_CITY_PAGES_VERSION
		);

		wp_enqueue_script(
			'seo-city-pages-admin',
			$js_url,
			[ 'jquery' ],
			SEO_CITY_PAGES_VERSION,
			true
		);

		wp_localize_script(
			'seo-city-pages-admin',
			'seoCityPagesAdmin',
			[
				'nonce'   => Security::create_nonce(),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	/**
	 * Render informational/warning admin notices for dependencies.
	 *
	 * @return void
	 */
	public function render_environment_notices(): void {
		if ( ! current_user_can( Capabilities::MANAGE ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || strpos( $screen->id, self::MENU_SLUG ) === false ) {
			return;
		}

		// Only show notice on plugin screens if Elementor is NOT active.
		if ( ! Environment::is_elementor_active() ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'SEO City Pages Notice:', 'seo-city-pages' ); ?></strong>
					<?php esc_html_e( 'Elementor is not active. The plugin data layer and CSV imports are fully functional, but template rendering requires Elementor.', 'seo-city-pages' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Render Dashboard screen.
	 *
	 * @return void
	 */
	public function render_dashboard_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$dashboard = new Dashboard();
		$dashboard->render();
	}

	/**
	 * Render CSV Import screen.
	 *
	 * @return void
	 */
	public function render_import_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new Import_Controller();
		$controller->render();
	}

	/**
	 * Render Target Keywords Bank screen.
	 *
	 * @return void
	 */
	public function render_keywords_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new Keywords_Controller();
		$controller->render();
	}

	/**
	 * Render Topic-Based County Generator screen.
	 *
	 * @return void
	 */
	public function render_county_generator_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new County_Generator_Controller();
		$controller->render();
	}

	/**
	 * Render SEO City Pages screen.
	 *
	 * @return void
	 */
	public function render_pages_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new Pages_Controller();
		$controller->render();
	}

	/**
	 * Render Elementor Template screen.
	 *
	 * @return void
	 */
	public function render_template_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new Template_Controller();
		$controller->render();
	}

	/**
	 * Render Countries screen.
	 *
	 * @return void
	 */
	public function render_countries_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new Countries_Controller();
		$controller->render();
	}

	/**
	 * Render States screen.
	 *
	 * @return void
	 */
	public function render_states_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new States_Controller();
		$controller->render();
	}

	/**
	 * Render Cities screen.
	 *
	 * @return void
	 */
	public function render_cities_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new Cities_Controller();
		$controller->render();
	}

	/**
	 * Render Services screen.
	 *
	 * @return void
	 */
	public function render_services_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new Services_Controller();
		$controller->render();
	}

	/**
	 * Render System Status screen.
	 *
	 * @return void
	 */
	public function render_status_page(): void {
		Security::enforce_capability( Capabilities::VIEW_STATUS );
		$status = new System_Status();
		$status->render();
	}

	/**
	 * Render SEO Integration & Settings screen.
	 *
	 * @return void
	 */
	public function render_seo_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new SEO_Controller();
		$controller->render();
	}

	/**
	 * Render Bulk Operations screen.
	 *
	 * @return void
	 */
	public function render_bulk_page(): void {
		Security::enforce_capability( Capabilities::MANAGE );
		$controller = new Bulk_Controller();
		$controller->render();
	}

	/**
	 * Render Quality Dashboard screen.
	 *
	 * @return void
	 */
	public function render_quality_page(): void {
		Security::enforce_capability( Capabilities::MANAGE_QUALITY );
		$controller = new Quality_Controller();
		$controller->render();
	}

	/**
	 * Render System Health & Diagnostics screen.
	 *
	 * @return void
	 */
	public function render_health_page(): void {
		Security::enforce_capability( Capabilities::VIEW_HEALTH );
		$controller = new Health_Controller();
		$controller->render();
	}

	/**
	 * Render Immutable Audit Log screen.
	 *
	 * @return void
	 */
	public function render_audit_page(): void {
		Security::enforce_capability( Capabilities::VIEW_AUDIT_LOGS );
		$controller = new Audit_Controller();
		$controller->render();
	}

	/**
	 * Render Settings screen.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		Security::enforce_capability( Capabilities::MANAGE_SETTINGS );
		$settings = new Settings();
		$settings->render();
	}

	/**
	 * Keep top-level SEO City Pages menu highlighted for dashboard-only screens.
	 *
	 * @param string $parent_file Parent menu slug.
	 * @return string
	 */
	public function filter_parent_file( string $parent_file ): string {
		$page = sanitize_text_field( $_GET['page'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $page ) && 0 === strpos( $page, self::MENU_SLUG ) ) {
			return self::MENU_SLUG;
		}
		return $parent_file;
	}
}
