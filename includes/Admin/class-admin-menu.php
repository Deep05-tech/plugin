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
	 * Initialize admin menu hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_notices', [ $this, 'render_environment_notices' ] );
	}

	/**
	 * Register top-level and submenu pages.
	 *
	 * @return void
	 */
	public function register_menus(): void {
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

		// Submenu: County Generator
		$county_gen_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'County Generator — SEO City Pages', 'seo-city-pages' ),
			__( 'County Generator', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-county-generator',
			[ $this, 'render_county_generator_page' ]
		);
		$this->hook_suffixes[] = $county_gen_hook;

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

		// Submenu: Countries
		$countries_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Countries — SEO City Pages', 'seo-city-pages' ),
			__( 'Countries', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-countries',
			[ $this, 'render_countries_page' ]
		);
		$this->hook_suffixes[] = $countries_hook;

		// Submenu: States / Regions
		$states_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'States / Regions — SEO City Pages', 'seo-city-pages' ),
			__( 'States / Regions', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-states',
			[ $this, 'render_states_page' ]
		);
		$this->hook_suffixes[] = $states_hook;

		// Submenu: Cities
		$cities_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Cities — SEO City Pages', 'seo-city-pages' ),
			__( 'Cities', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-cities',
			[ $this, 'render_cities_page' ]
		);
		$this->hook_suffixes[] = $cities_hook;

		// Submenu: Services
		$services_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Services — SEO City Pages', 'seo-city-pages' ),
			__( 'Services', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-services',
			[ $this, 'render_services_page' ]
		);
		$this->hook_suffixes[] = $services_hook;

		// Submenu: System Status
		$status_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'System Status — SEO City Pages', 'seo-city-pages' ),
			__( 'System Status', 'seo-city-pages' ),
			Capabilities::VIEW_STATUS,
			self::MENU_SLUG . '-status',
			[ $this, 'render_status_page' ]
		);
		$this->hook_suffixes[] = $status_hook;

		// Submenu: SEO Integration & Metadata
		$seo_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'SEO Integration — SEO City Pages', 'seo-city-pages' ),
			__( 'SEO Controls', 'seo-city-pages' ),
			Capabilities::MANAGE,
			self::MENU_SLUG . '-seo',
			[ $this, 'render_seo_page' ]
		);
		$this->hook_suffixes[] = $seo_hook;

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

		// Submenu: Quality Dashboard
		$quality_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Quality Dashboard — SEO City Pages', 'seo-city-pages' ),
			__( 'Quality Dashboard', 'seo-city-pages' ),
			Capabilities::MANAGE_QUALITY,
			self::MENU_SLUG . '-quality',
			[ $this, 'render_quality_page' ]
		);
		$this->hook_suffixes[] = $quality_hook;

		// Submenu: System Health
		$health_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'System Health — SEO City Pages', 'seo-city-pages' ),
			__( 'System Health', 'seo-city-pages' ),
			Capabilities::VIEW_HEALTH,
			self::MENU_SLUG . '-health',
			[ $this, 'render_health_page' ]
		);
		$this->hook_suffixes[] = $health_hook;

		// Submenu: Audit Log
		$audit_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Audit Log — SEO City Pages', 'seo-city-pages' ),
			__( 'Audit Log', 'seo-city-pages' ),
			Capabilities::VIEW_AUDIT_LOGS,
			self::MENU_SLUG . '-audit',
			[ $this, 'render_audit_page' ]
		);
		$this->hook_suffixes[] = $audit_hook;

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
}
