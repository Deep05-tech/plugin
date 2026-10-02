<?php
/**
 * Test Runner for SEO City Pages Phase 2 Foundation.
 *
 * @package SeoCityPages\Tests
 */

declare(strict_types=1);

// Mock WordPress constants and environment if running in standalone CLI.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', false );
}
if ( ! defined( 'SEO_CITY_PAGES_VERSION' ) ) {
	define( 'SEO_CITY_PAGES_VERSION', '0.1.0' );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_FILE' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_FILE', dirname( __DIR__ ) . '/seo-city-pages.php' );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_DIR' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_URL' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_URL', 'http://example.local/wp-content/plugins/seo-city-pages/' );
}

// Mock WordPress core global state and functions if not loaded via WP.
global $wp_version, $mock_options, $mock_roles;
$wp_version   = '7.1';
$mock_options = [];
$mock_roles   = [
	'administrator' => new class {
		public $capabilities = [];
		public function add_cap( string $cap ): void {
			$this->capabilities[ $cap ] = true;
		}
		public function remove_cap( string $cap ): void {
			unset( $this->capabilities[ $cap ] );
		}
		public function has_cap( string $cap ): bool {
			return ! empty( $this->capabilities[ $cap ] );
		}
	},
];

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string { return $text; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string { return $text; }
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( string $url ): string { return filter_var( $url, FILTER_SANITIZE_URL ) ?: ''; }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ): string { return json_encode( $data ); }
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, $default = false ) {
		global $mock_options;
		return $mock_options[ $option ] ?? $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, $value ): bool {
		global $mock_options;
		$mock_options[ $option ] = $value;
		return true;
	}
}
if ( ! function_exists( 'add_option' ) ) {
	function add_option( string $option, $value ): bool {
		global $mock_options;
		if ( ! isset( $mock_options[ $option ] ) ) {
			$mock_options[ $option ] = $value;
			return true;
		}
		return false;
	}
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $option ): bool {
		global $mock_options;
		unset( $mock_options[ $option ] );
		return true;
	}
}
if ( ! function_exists( 'get_role' ) ) {
	function get_role( string $role ) {
		global $mock_roles;
		return $mock_roles[ $role ] ?? null;
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $cap ): bool {
		global $mock_roles, $current_user_is_admin;
		if ( false === $current_user_is_admin ) {
			return false;
		}
		return ! empty( $mock_roles['administrator']->capabilities[ $cap ] ) || 'manage_options' === $cap;
	}
}
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( string $action ): string {
		return substr( md5( $action . 'salt_test_123' ), 0, 10 );
	}
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( string $nonce, string $action ): bool {
		return $nonce === substr( md5( $action . 'salt_test_123' ), 0, 10 );
	}
}
if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir(): array {
		$tmp = sys_get_temp_dir() . '/seo_city_pages_test_uploads';
		if ( ! file_exists( $tmp ) ) {
			mkdir( $tmp, 0777, true );
		}
		return [ 'basedir' => $tmp, 'error' => false ];
	}
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( string $string ): string {
		return rtrim( $string, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( string $target ): bool {
		return is_dir( $target ) || mkdir( $target, 0777, true );
	}
}
if ( ! function_exists( 'wp_convert_hr_to_bytes' ) ) {
	function wp_convert_hr_to_bytes( $size ): int {
		$size  = strtolower( trim( (string) $size ) );
		$unit  = substr( $size, -1 );
		$bytes = (int) $size;
		switch ( $unit ) {
			case 'g': $bytes *= 1024 * 1024 * 1024; break;
			case 'm': $bytes *= 1024 * 1024; break;
			case 'k': $bytes *= 1024; break;
		}
		return $bytes;
	}
}
if ( ! function_exists( 'wp_is_writable' ) ) {
	function wp_is_writable( string $path ): bool { return is_writable( $path ); }
}
if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite(): bool { return false; }
}
if ( ! function_exists( 'wp_get_theme' ) ) {
	function wp_get_theme() {
		return new class {
			public function exists(): bool { return true; }
			public function get( string $header ): string { return 'Arolax Theme'; }
			public function get_template(): string { return 'arolax'; }
			public function get_stylesheet(): string { return 'arolax'; }
			public function parent() { return null; }
		};
	}
}
if ( ! function_exists( 'flush_rewrite_rules' ) ) {
	function flush_rewrite_rules( bool $hard = true ): void {}
}
if ( ! function_exists( 'did_action' ) ) {
	function did_action( string $tag ): int { return 0; }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ): string {
		$filtered = wp_check_invalid_utf8( (string) $str );
		return trim( strip_tags( $filtered ) );
	}
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ): string {
		$filtered = wp_check_invalid_utf8( (string) $str );
		return trim( strip_tags( $filtered ) );
	}
}
if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ): string {
		return trim( (string) filter_var( $email, FILTER_SANITIZE_EMAIL ) );
	}
}
if ( ! function_exists( 'is_email' ) ) {
	function is_email( $email ): bool {
		return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
	}
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ): int {
		return abs( (int) $maybeint );
	}
}
if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( string $data ): string {
		return strip_tags( $data, '<p><a><b><strong><i><em><u><ul><ol><li><br><h1><h2><h3><h4><h5><h6><blockquote><hr><span><div>' );
	}
}
if ( ! function_exists( 'wp_check_invalid_utf8' ) ) {
	function wp_check_invalid_utf8( string $string ): string {
		return $string;
	}
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ): int {
		return abs( (int) $maybeint );
	}
}

// Load autoloader.
require_once dirname( __DIR__ ) . '/includes/class-autoloader.php';
\SeoCityPages\Autoloader::register( dirname( __DIR__ ) . '/includes/' );

// Test Suite Implementation.
class Foundation_Test_Suite {

	private $passed = 0;
	private $failed = 0;

	public function run(): void {
		echo "====================================================\n";
		echo "  SEO City Pages — Phase 2 Foundation Test Suite   \n";
		echo "====================================================\n\n";

		$this->test_autoloader();
		$this->test_singleton_instantiation();
		$this->test_environment_detection();
		$this->test_capabilities_management();
		$this->test_security_helpers();
		$this->test_settings_sanitization();
		$this->test_logging_subsystem();
		$this->test_migrator();
		$this->test_activation_deactivation();

		echo "\n====================================================\n";
		echo sprintf( "Results: %d Passed, %d Failed\n", $this->passed, $this->failed );
		echo "====================================================\n";

		if ( $this->failed > 0 ) {
			exit( 1 );
		}
	}

	private function assert( bool $condition, string $test_name ): void {
		if ( $condition ) {
			echo "[ PASS ] " . $test_name . "\n";
			$this->passed++;
		} else {
			echo "[ FAIL ] " . $test_name . "\n";
			$this->failed++;
		}
	}

	private function test_autoloader(): void {
		$this->assert( class_exists( 'SeoCityPages\\Plugin' ), 'Autoloader loads SeoCityPages\\Plugin' );
		$this->assert( class_exists( 'SeoCityPages\\Environment' ), 'Autoloader loads SeoCityPages\\Environment' );
		$this->assert( class_exists( 'SeoCityPages\\Security\\Capabilities' ), 'Autoloader loads SeoCityPages\\Security\\Capabilities' );
		$this->assert( class_exists( 'SeoCityPages\\Security\\Security' ), 'Autoloader loads SeoCityPages\\Security\\Security' );
		$this->assert( class_exists( 'SeoCityPages\\Logging\\Logger' ), 'Autoloader loads SeoCityPages\\Logging\\Logger' );
		$this->assert( class_exists( 'SeoCityPages\\Database\\Migrator' ), 'Autoloader loads SeoCityPages\\Database\\Migrator' );
		$this->assert( class_exists( 'SeoCityPages\\Admin\\Admin_Menu' ), 'Autoloader loads SeoCityPages\\Admin\\Admin_Menu' );
		$this->assert( class_exists( 'SeoCityPages\\Admin\\Settings' ), 'Autoloader loads SeoCityPages\\Admin\\Settings' );
		$this->assert( class_exists( 'SeoCityPages\\Admin\\Dashboard' ), 'Autoloader loads SeoCityPages\\Admin\\Dashboard' );
		$this->assert( class_exists( 'SeoCityPages\\Admin\\System_Status' ), 'Autoloader loads SeoCityPages\\Admin\\System_Status' );
	}

	private function test_singleton_instantiation(): void {
		$instance1 = \SeoCityPages\Plugin::instance();
		$instance2 = \SeoCityPages\Plugin::instance();
		$this->assert( $instance1 instanceof \SeoCityPages\Plugin, 'Plugin::instance() returns Plugin instance' );
		$this->assert( $instance1 === $instance2, 'Plugin::instance() returns strict same singleton instance' );
	}

	private function test_environment_detection(): void {
		$this->assert( \SeoCityPages\Environment::is_php_compatible(), 'Environment::is_php_compatible() returns true on PHP ' . PHP_VERSION );
		$this->assert( \SeoCityPages\Environment::is_wp_compatible(), 'Environment::is_wp_compatible() returns true on WP 7.1' );
		$this->assert( \SeoCityPages\Environment::is_arolax_active(), 'Environment::is_arolax_active() correctly identifies Arolax theme' );

		$diag = \SeoCityPages\Environment::get_diagnostics();
		$this->assert( is_array( $diag ) && isset( $diag['wordpress'], $diag['php'], $diag['database'] ), 'Environment::get_diagnostics() returns complete diagnostics tree' );
	}

	private function test_capabilities_management(): void {
		\SeoCityPages\Security\Capabilities::register_defaults();
		$this->assert( \SeoCityPages\Security\Capabilities::user_can( \SeoCityPages\Security\Capabilities::MANAGE ), 'Administrator role has MANAGE capability' );
		$this->assert( \SeoCityPages\Security\Capabilities::user_can( \SeoCityPages\Security\Capabilities::VIEW_STATUS ), 'Administrator role has VIEW_STATUS capability' );
		$this->assert( \SeoCityPages\Security\Capabilities::user_can( \SeoCityPages\Security\Capabilities::MANAGE_SETTINGS ), 'Administrator role has MANAGE_SETTINGS capability' );
		$this->assert( \SeoCityPages\Security\Capabilities::user_can( \SeoCityPages\Security\Capabilities::VIEW_LOGS ), 'Administrator role has VIEW_LOGS capability' );

		global $current_user_is_admin;
		$current_user_is_admin = false;
		$this->assert( ! \SeoCityPages\Security\Capabilities::user_can( 'random_cap' ), 'Unauthorized user is denied capabilities' );
		$current_user_is_admin = true;
	}

	private function test_security_helpers(): void {
		// Nonce test.
		$nonce = \SeoCityPages\Security\Security::create_nonce( 'test_action' );
		$this->assert( ! empty( $nonce ), 'Security::create_nonce() creates non-empty token' );
		$this->assert( \SeoCityPages\Security\Security::verify_nonce( $nonce, 'test_action' ), 'Security::verify_nonce() validates genuine token' );
		$this->assert( ! \SeoCityPages\Security\Security::verify_nonce( $nonce, 'wrong_action' ), 'Security::verify_nonce() rejects mismatched action' );
		$this->assert( ! \SeoCityPages\Security\Security::verify_nonce( 'fake_nonce', 'test_action' ), 'Security::verify_nonce() rejects forged token' );

		// CSV formula injection test.
		$safe_cell = \SeoCityPages\Security\Security::sanitize_csv_cell( '=cmd|/C calc!A0' );
		$this->assert( $safe_cell[0] === "'", 'CSV formula starting with "=" is sanitized with prepended single quote' );

		$safe_cell2 = \SeoCityPages\Security\Security::sanitize_csv_cell( '+12345' );
		$this->assert( $safe_cell2[0] === "'", 'CSV formula starting with "+" is sanitized with prepended single quote' );

		$safe_cell3 = \SeoCityPages\Security\Security::sanitize_csv_cell( 'Rajkot City' );
		$this->assert( $safe_cell3 === 'Rajkot City', 'Normal string without formula prefix is untouched' );

		// Mask secret test.
		$masked = \SeoCityPages\Security\Security::mask_secret( 'sk-proj-1234567890abcdef' );
		$this->assert( strpos( $masked, 'sk-p' ) === 0 && strpos( $masked, 'cdef' ) !== false && strpos( $masked, '****' ) !== false, 'Security::mask_secret() correctly masks API key' );

		// Sensitive data scrubbing test.
		$payload = [
			'username' => 'admin',
			'api_key'  => 'secret_12345',
			'nested'   => [
				'password' => 'super_secret',
				'city'     => 'Rajkot',
			],
		];
		$scrubbed = \SeoCityPages\Security\Security::scrub_sensitive_data( $payload );
		$this->assert( $scrubbed['api_key'] === '[REDACTED]', 'Sensitive api_key is redacted' );
		$this->assert( $scrubbed['nested']['password'] === '[REDACTED]', 'Nested password is redacted' );
		$this->assert( $scrubbed['nested']['city'] === 'Rajkot', 'Non-sensitive data is preserved' );
	}

	private function test_settings_sanitization(): void {
		$settings = new \SeoCityPages\Admin\Settings();

		// Valid input test.
		$input = [
			'cache_compatibility_mode' => '1',
			'debug_mode'               => '1',
			'log_level'                => 'warning',
			'delete_data_on_uninstall' => '0',
		];
		$clean = $settings->sanitize_settings( $input );
		$this->assert( 1 === $clean['cache_compatibility_mode'], 'Settings: cache_compatibility_mode sanitized to 1' );
		$this->assert( 1 === $clean['debug_mode'], 'Settings: debug_mode sanitized to 1' );
		$this->assert( 'warning' === $clean['log_level'], 'Settings: log_level accepted as "warning"' );
		$this->assert( 0 === $clean['delete_data_on_uninstall'], 'Settings: delete_data_on_uninstall sanitized to 0' );

		// Invalid input test.
		$bad_input = [
			'cache_compatibility_mode' => 'invalid_string',
			'log_level'                => 'malicious_eval()',
			'delete_data_on_uninstall' => 'attack',
		];
		$clean_bad = $settings->sanitize_settings( $bad_input );
		$this->assert( 'info' === $clean_bad['log_level'], 'Invalid log_level safely falls back to default "info"' );
		$this->assert( 1 === $clean_bad['delete_data_on_uninstall'], 'Non-empty truthy value casts safely to 1' );

		// Empty/corrupt input test.
		$empty_clean = $settings->sanitize_settings( null );
		$this->assert( is_array( $empty_clean ) && isset( $empty_clean['log_level'] ), 'Corrupt settings input returns safe defaults' );
	}

	private function test_logging_subsystem(): void {
		\SeoCityPages\Logging\Logger::clear_logs();
		\SeoCityPages\Logging\Logger::info( 'Test info message', [ 'foo' => 'bar', 'api_key' => 'secret_token_123' ] );
		\SeoCityPages\Logging\Logger::error( 'Test error message' );

		$logs = \SeoCityPages\Logging\Logger::get_recent_logs( 10 );
		$this->assert( count( $logs ) >= 2, 'Logger recorded recent log lines' );

		$log_text = implode( "\n", $logs );
		$this->assert( strpos( $log_text, 'Test info message' ) !== false, 'Logger recorded info message' );
		$this->assert( strpos( $log_text, 'Test error message' ) !== false, 'Logger recorded error message' );
		$this->assert( strpos( $log_text, 'secret_token_123' ) === false, 'Logger auto-redacted sensitive API key from context' );
		$this->assert( strpos( $log_text, '[REDACTED]' ) !== false, 'Logger replaced sensitive key with [REDACTED]' );

		\SeoCityPages\Logging\Logger::clear_logs();
		$cleared_logs = \SeoCityPages\Logging\Logger::get_recent_logs( 10 );
		$this->assert( empty( $cleared_logs ), 'Logger::clear_logs() clears log storage' );
	}

	private function test_migrator(): void {
		delete_option( \SeoCityPages\Database\Migrator::DB_VERSION_OPTION );
		$this->assert( '0.0.0' === \SeoCityPages\Database\Migrator::get_current_db_version(), 'Migrator initial version defaults to 0.0.0' );

		\SeoCityPages\Database\Migrator::maybe_upgrade();
		$this->assert( \SeoCityPages\Database\Migrator::TARGET_DB_VERSION === \SeoCityPages\Database\Migrator::get_current_db_version(), 'Migrator successfully updates schema version to ' . \SeoCityPages\Database\Migrator::TARGET_DB_VERSION );
	}

	private function test_activation_deactivation(): void {
		\SeoCityPages\Plugin::activate();
		$settings = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME );
		$this->assert( is_array( $settings ) && ! empty( $settings ), 'Plugin::activate() initializes default settings' );
		$this->assert( \SeoCityPages\Database\Migrator::TARGET_DB_VERSION === get_option( \SeoCityPages\Database\Migrator::DB_VERSION_OPTION ), 'Plugin::activate() ensures DB version is set' );

		\SeoCityPages\Plugin::deactivate();
		$preserved_settings = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME );
		$this->assert( ! empty( $preserved_settings ), 'Plugin::deactivate() preserves all settings and data' );
	}
}

// Execute tests.
$suite = new Foundation_Test_Suite();
$suite->run();
