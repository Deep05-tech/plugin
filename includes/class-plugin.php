<?php
/**
 * Main Plugin Orchestrator Singleton.
 *
 * @package SeoCityPages
 */

namespace SeoCityPages;

use SeoCityPages\Admin\Admin_Menu;
use SeoCityPages\Admin\Settings;
use SeoCityPages\Database\Migrator;
use SeoCityPages\Logging\Logger;
use SeoCityPages\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Admin menu manager instance.
	 *
	 * @var Admin_Menu|null
	 */
	private $admin_menu = null;

	/**
	 * Settings manager instance.
	 *
	 * @var Settings|null
	 */
	private $settings = null;

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
	 * Private constructor.
	 */
	private function __construct() {
		// Private for singleton.
	}

	/**
	 * Initialize plugin lifecycles and subsystems.
	 *
	 * @return void
	 */
	public function init(): void {
		// 1. Check PHP compatibility.
		if ( ! Environment::is_php_compatible() ) {
			add_action( 'admin_notices', [ $this, 'render_php_incompatible_notice' ] );
			return;
		}

		// 2. Check WordPress compatibility.
		if ( ! Environment::is_wp_compatible() ) {
			add_action( 'admin_notices', [ $this, 'render_wp_incompatible_notice' ] );
			return;
		}

		// 3. Check for automatic database schema updates.
		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ], 10 );

		// 4. Initialize Admin layer & AJAX handlers.
		if ( is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
			$this->admin_menu = new Admin_Menu();
			$this->admin_menu->init();

			$this->settings = new Settings();
			$this->settings->init();

			$import_controller = new \SeoCityPages\Admin\Import_Controller();

			$ai_controller = new \SeoCityPages\Admin\AI_Controller();
			$ai_controller->init();

			$seo_controller = new \SeoCityPages\Admin\SEO_Controller();
			$seo_controller->register_hooks();

			$bulk_controller = new \SeoCityPages\Admin\Bulk_Controller();
			$quality_controller = new \SeoCityPages\Admin\Quality_Controller();
			$county_generator_controller = new \SeoCityPages\Admin\County_Generator_Controller();
		}

		// 5. Initialize SEO Manager & Search Engine Controls.
		\SeoCityPages\SEO\SEO_Manager::get_instance()->init();

		// 6. Initialize Public URL & Request Resolution Engine.
		$request_resolver = new \SeoCityPages\URL\Request_Resolver();
		$request_resolver->init();

		// 7. Initialize Elementor Dynamic Template Integration.
		\SeoCityPages\Elementor\Elementor_Integration::instance()->init();

		// 8. Initialize WordPress Core Post Synchronization & Pages Table Integration.
		\SeoCityPages\Integration\Post_Sync::init();

		// 9. Initialize Frontend Lead & Inquiry Form Submission Handlers.
		\SeoCityPages\Frontend\Inquiry_Handler::init();

		// 10. Load text domain for translations.
		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Execute on plugins_loaded hook.
	 *
	 * @return void
	 */
	public function on_plugins_loaded(): void {
		Migrator::maybe_upgrade();
	}

	/**
	 * Load plugin textdomain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'seo-city-pages',
			false,
			dirname( plugin_basename( SEO_CITY_PAGES_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Plugin Activation Handler.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( ! Environment::is_php_compatible() ) {
			deactivate_plugins( plugin_basename( SEO_CITY_PAGES_PLUGIN_FILE ) );
			wp_die(
				sprintf(
					/* translators: 1: Required PHP version, 2: Current PHP version */
					esc_html__( 'SEO City Pages requires PHP version %1$s or higher. Your server is running PHP %2$s.', 'seo-city-pages' ),
					esc_html( Environment::MIN_PHP_VERSION ),
					esc_html( PHP_VERSION )
				),
				esc_html__( 'Plugin Activation Error', 'seo-city-pages' ),
				[ 'back_link' => true ]
			);
		}

		// Register administrator capabilities.
		Capabilities::register_defaults();

		// Initialize default settings if not exists.
		if ( false === get_option( Settings::OPTION_NAME ) ) {
			add_option( Settings::OPTION_NAME, Settings::get_defaults() );
		}

		// Initialize schema version.
		Migrator::maybe_upgrade();

		// Record activation log.
		Logger::info(
			sprintf( 'SEO City Pages activated (v%s)', SEO_CITY_PAGES_VERSION ),
			[
				'wp_version'  => $GLOBALS['wp_version'] ?? 'Unknown',
				'php_version' => PHP_VERSION,
			]
		);
	}

	/**
	 * Plugin Deactivation Handler.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Log deactivation.
		Logger::info( sprintf( 'SEO City Pages deactivated (v%s)', SEO_CITY_PAGES_VERSION ) );

		// Flush rewrite rules safely without touching database content or tables.
		flush_rewrite_rules( false );
	}

	/**
	 * Render notice if PHP version is incompatible.
	 *
	 * @return void
	 */
	public function render_php_incompatible_notice(): void {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'SEO City Pages Activation Blocked:', 'seo-city-pages' ); ?></strong>
				<?php
				printf(
					/* translators: 1: Required PHP version, 2: Current PHP version */
					esc_html__( 'This plugin requires PHP %1$s or higher. Your server is running PHP %2$s.', 'seo-city-pages' ),
					esc_html( Environment::MIN_PHP_VERSION ),
					esc_html( PHP_VERSION )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render notice if WordPress version is incompatible.
	 *
	 * @return void
	 */
	public function render_wp_incompatible_notice(): void {
		global $wp_version;
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'SEO City Pages Compatibility Notice:', 'seo-city-pages' ); ?></strong>
				<?php
				printf(
					/* translators: 1: Required WP version, 2: Current WP version */
					esc_html__( 'This plugin requires WordPress %1$s or higher. Your site is running WordPress %2$s.', 'seo-city-pages' ),
					esc_html( Environment::MIN_WP_VERSION ),
					esc_html( $wp_version ?? '0.0' )
				);
				?>
			</p>
		</div>
		<?php
	}
}
