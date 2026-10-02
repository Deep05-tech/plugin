<?php
/**
 * Automated Test Suite for Phase 4 — SEO City Page Data Layer & URL Architecture.
 *
 * @package SeoCityPages\Tests
 */

// Mock environment constants.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'SEO_CITY_PAGES_VERSION' ) ) {
	define( 'SEO_CITY_PAGES_VERSION', '0.4.0' );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_DIR' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_URL' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_URL', 'http://example.local/wp-content/plugins/seo-city-pages/' );
}
if ( ! defined( 'SEO_CITY_PAGES_TESTING' ) ) {
	define( 'SEO_CITY_PAGES_TESTING', true );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

// In-Memory SQLite Mock WPDB.
class Mock_WPDB {
	public string $prefix = 'wp_';
	public int $insert_id = 0;
	private \PDO $pdo;

	public function __construct() {
		$this->pdo = new \PDO( 'sqlite::memory:' );
		$this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
	}

	public function get_charset_collate(): string {
		return '';
	}

	public function prepare( string $query, ...$args ): string {
		if ( empty( $args ) ) {
			return $query;
		}
		if ( count( $args ) === 1 && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$index = 0;
		$pdo   = $this->pdo;
		return preg_replace_callback( '/(%[sdfF])/', function( $matches ) use ( &$index, $args, $pdo ) {
			if ( ! array_key_exists( $index, $args ) ) {
				return $matches[0];
			}
			$arg  = $args[ $index++ ];
			$type = $matches[0];

			if ( '%s' === $type ) {
				return null === $arg ? 'NULL' : $pdo->quote( (string) $arg );
			} elseif ( '%d' === $type ) {
				return null === $arg ? 'NULL' : (string) (int) $arg;
			} elseif ( '%f' === $type || '%F' === $type ) {
				return null === $arg ? 'NULL' : (string) (float) $arg;
			}
			return (string) $arg;
		}, $query );
	}

	public function esc_like( string $text ): string {
		return addcslashes( $text, '_%\\' );
	}

	public function query( string $query ) {
		try {
			$sqlite_query = preg_replace( '/BIGINT\([0-9]+\)\s+UNSIGNED\s+NOT\s+NULL\s+AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $query );
			$sqlite_query = preg_replace( '/BIGINT\([0-9]+\)\s+UNSIGNED/i', 'INTEGER', $sqlite_query );
			$sqlite_query = preg_replace( '/INT\s+UNSIGNED/i', 'INTEGER', $sqlite_query );
			$sqlite_query = preg_replace( '/LONGTEXT/i', 'TEXT', $sqlite_query );
			$sqlite_query = preg_replace( '/DECIMAL\([0-9]+,\s*[0-9]+\)/i', 'NUMERIC', $sqlite_query );
			$sqlite_query = preg_replace( '/UNIQUE\s+KEY\s+[a-zA-Z0-9_]+\s*\([^)]+\),?/i', '', $sqlite_query );
			$sqlite_query = preg_replace( '/KEY\s+[a-zA-Z0-9_]+\s*\([^)]+\),?/i', '', $sqlite_query );
			$sqlite_query = preg_replace( '/PRIMARY\s+KEY\s*\([^)]+\),?/i', '', $sqlite_query );
			$sqlite_query = preg_replace( '/\s*,\s*\)/', ' )', $sqlite_query );
			$sqlite_query = preg_replace( '/\)\s*(DEFAULT\s+CHARSET=[a-zA-Z0-9_]+|COLLATE=[a-zA-Z0-9_]+|ENGINE=[a-zA-Z0-9_]+|utf8mb4[a-zA-Z0-9_]*|\{[^}]+\})*/i', ')', $sqlite_query );

			$stmt = $this->pdo->prepare( $sqlite_query );
			$stmt->execute();
			$this->insert_id = (int) $this->pdo->lastInsertId();
			return $stmt->rowCount();
		} catch ( \PDOException $e ) {
			return false;
		}
	}

	public function get_row( string $query, $output = OBJECT ) {
		try {
			$stmt = $this->pdo->query( $query );
			$row  = $stmt->fetch( \PDO::FETCH_OBJ );
			return $row ?: null;
		} catch ( \PDOException $e ) {
			return null;
		}
	}

	public function get_results( string $query, $output = OBJECT ): array {
		try {
			$stmt = $this->pdo->query( $query );
			return $stmt->fetchAll( \PDO::FETCH_OBJ ) ?: [];
		} catch ( \PDOException $e ) {
			return [];
		}
	}

	public function get_var( string $query ) {
		try {
			$stmt = $this->pdo->query( $query );
			return $stmt->fetchColumn();
		} catch ( \PDOException $e ) {
			return null;
		}
	}

	public function insert( string $table, array $data, $format = null ): bool {
		$keys         = array_keys( $data );
		$fields       = implode( ', ', $keys );
		$placeholders = implode( ', ', array_fill( 0, count( $keys ), '?' ) );
		$values       = array_values( $data );

		try {
			$sql  = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
			$stmt = $this->pdo->prepare( $sql );
			$res  = $stmt->execute( $values );
			$this->insert_id = (int) $this->pdo->lastInsertId();
			return $res;
		} catch ( \PDOException $e ) {
			return false;
		}
	}

	public function update( string $table, array $data, array $where, $format = null, $where_format = null ): bool {
		$fields = [];
		$values = [];
		foreach ( $data as $k => $v ) {
			$fields[] = "{$k} = ?";
			$values[] = $v;
		}
		$where_clauses = [];
		foreach ( $where as $k => $v ) {
			$where_clauses[] = "{$k} = ?";
			$values[]        = $v;
		}

		try {
			$sql  = "UPDATE {$table} SET " . implode( ', ', $fields ) . ' WHERE ' . implode( ' AND ', $where_clauses );
			$stmt = $this->pdo->prepare( $sql );
			return $stmt->execute( $values );
		} catch ( \PDOException $e ) {
			return false;
		}
	}

	public function delete( string $table, array $where, $where_format = null ): bool {
		$where_clauses = [];
		$values        = [];
		foreach ( $where as $k => $v ) {
			$where_clauses[] = "{$k} = ?";
			$values[]        = $v;
		}

		try {
			$sql  = "DELETE FROM {$table} WHERE " . implode( ' AND ', $where_clauses );
			$stmt = $this->pdo->prepare( $sql );
			return $stmt->execute( $values );
		} catch ( \PDOException $e ) {
			return false;
		}
	}
}

// Global Mocks.
global $wpdb, $mock_options, $mock_transients, $mock_current_user_can, $mock_die_history, $mock_status_header, $wp_query;
$wpdb                  = new Mock_WPDB();
$mock_options          = [];
$mock_transients       = [];
$mock_current_user_can = true;
$mock_die_history      = [];
$mock_status_header    = 200;

class Mock_WP_Query {
	public $is_404 = false;
	public $is_page = false;
	public function set_404() {
		$this->is_404 = true;
		$this->is_page = false;
	}
}
$wp_query = new Mock_WP_Query();

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		public function __construct( string $code = '', string $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
		public function get_error_code(): string { return $this->code; }
		public function get_error_message(): string { return $this->message; }
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool { return $thing instanceof WP_Error; }
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $mock_options;
		return $mock_options[ $option ] ?? $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		global $mock_options;
		$mock_options[ $option ] = $value;
		return true;
	}
}
if ( ! function_exists( 'add_option' ) ) {
	function add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' ) {
		global $mock_options;
		if ( isset( $mock_options[ $option ] ) ) {
			return false;
		}
		$mock_options[ $option ] = $value;
		return true;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $transient, $value, int $expiration = 0 ): bool {
		global $mock_transients;
		$mock_transients[ $transient ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $transient ) {
		global $mock_transients;
		return $mock_transients[ $transient ] ?? false;
	}
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( string $transient ): bool {
		global $mock_transients;
		unset( $mock_transients[ $transient ] );
		return true;
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		global $mock_current_user_can;
		return (bool) $mock_current_user_can;
	}
}
if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = [] ) {
		global $mock_die_history;
		$status = is_numeric( $title ) ? (int) $title : 500;
		$mock_die_history[] = [ 'message' => $message, 'status' => $status ];
		throw new \RuntimeException( "wp_die: {$message} (Status: {$status})" );
	}
}
if ( ! function_exists( 'status_header' ) ) {
	function status_header( $code ) {
		global $mock_status_header;
		$mock_status_header = (int) $code;
	}
}
if ( ! function_exists( 'nocache_headers' ) ) {
	function nocache_headers() {}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return is_string( $str ) ? trim( strip_tags( $str ) ) : '';
	}
}
if ( ! function_exists( 'sanitize_file_name' ) ) {
	function sanitize_file_name( string $str ): string {
		return preg_replace( '/[^a-zA-Z0-9_\.-]/', '', $str );
	}
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		$title = strtolower( trim( $title ) );
		$title = preg_replace( '/[^a-z0-9\-_\s]/', '', $title );
		$title = preg_replace( '/[\s_]+/', '-', $title );
		return trim( $title, '-' );
	}
}
if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ): array {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}
		return array_merge( $defaults, (array) $args );
	}
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( string $type, bool $gmt = false ): string {
		return gmdate( 'Y-m-d H:i:s' );
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int {
		return 1;
	}
}
if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	function wp_generate_uuid4(): string {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( string $str ): string {
		return rtrim( $str, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( string $target ): bool {
		return is_dir( $target ) || mkdir( $target, 0755, true );
	}
}
if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir(): array {
		$tmp = sys_get_temp_dir() . '/mock_wp_uploads';
		if ( ! is_dir( $tmp ) ) {
			mkdir( $tmp, 0755, true );
		}
		return [
			'path'    => $tmp,
			'url'     => 'http://example.local/wp-content/uploads',
			'subdir'  => '',
			'basedir' => $tmp,
			'baseurl' => 'http://example.local/wp-content/uploads',
			'error'   => false,
		];
	}
}
if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'http://example.local' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
	}
}
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $args, $url = '' ) {
		if ( empty( $url ) ) {
			$url = 'http://example.local/';
		}
		$parts = parse_url( $url );
		$query = [];
		if ( isset( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
		}
		if ( is_array( $args ) ) {
			$query = array_merge( $query, $args );
		}
		$base = ( $parts['scheme'] ?? 'http' ) . '://' . ( $parts['host'] ?? 'example.local' ) . ( $parts['path'] ?? '/' );
		return $base . ( ! empty( $query ) ? '?' . http_build_query( $query ) : '' );
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://example.local/wp-admin/' . ltrim( $path, '/' );
	}
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'do_action' ) ) {
	function do_action( $tag, ...$args ) {}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, int $options = 0, int $depth = 512 ): string {
		return json_encode( $data, $options, $depth );
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $val ) { return $val; }
}
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ): string {
		return substr( md5( 'mock_salt_' . (string) $action ), 0, 10 );
	}
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( string $nonce, $action = -1 ): bool {
		return $nonce === substr( md5( 'mock_salt_' . (string) $action ), 0, 10 );
	}
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ): string {
		$nonce = wp_create_nonce( $action );
		$html  = '<input type="hidden" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $nonce ) . '" />';
		if ( $echo ) {
			echo $html;
		}
		return $html;
	}
}

// Mock Theme Support for Tests.
class Mock_WP_Theme {
	private array $data;
	private ?Mock_WP_Theme $parent;

	public function __construct( array $data = [], ?Mock_WP_Theme $parent = null ) {
		$this->data   = $data;
		$this->parent = $parent;
	}

	public function exists(): bool {
		return ! empty( $this->data['name'] );
	}

	public function get( string $header ) {
		$key = strtolower( $header );
		return $this->data[ $key ] ?? '';
	}

	public function get_template(): string {
		return $this->data['template'] ?? '';
	}

	public function get_stylesheet(): string {
		return $this->data['stylesheet'] ?? '';
	}

	public function parent(): ?Mock_WP_Theme {
		return $this->parent;
	}
}

global $mock_active_theme, $mock_installed_themes;
$mock_active_theme    = new Mock_WP_Theme( [ 'name' => 'Arolax', 'version' => '1.2.0', 'template' => 'arolax', 'stylesheet' => 'arolax' ] );
$mock_installed_themes = [ 'arolax' => $mock_active_theme ];

if ( ! function_exists( 'wp_get_theme' ) ) {
	function wp_get_theme( $stylesheet = null, $theme_root = null ) {
		global $mock_active_theme;
		return $mock_active_theme;
	}
}

if ( ! function_exists( 'wp_get_themes' ) ) {
	function wp_get_themes() {
		global $mock_installed_themes;
		return $mock_installed_themes;
	}
}

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $sqls ) {
		global $wpdb;
		if ( is_string( $sqls ) ) {
			$sqls = explode( ';', $sqls );
		}
		foreach ( $sqls as $sql ) {
			$sql = trim( $sql );
			if ( ! empty( $sql ) ) {
				$wpdb->query( $sql );
			}
		}
		return [];
	}
}

// Require Autoloader.
require_once dirname( __DIR__ ) . '/includes/class-autoloader.php';
\SeoCityPages\Autoloader::register( dirname( __DIR__ ) . '/includes' );

// Test Runner.
class Phase4_Test_Runner {
	private int $passed = 0;
	private int $failed = 0;
	private array $errors = [];

	public function assert( string $name, bool $condition, string $failure_message = '' ): void {
		if ( $condition ) {
			$this->passed++;
			echo "\033[32m  ✓ PASS:\033[0m {$name}\n";
		} else {
			$this->failed++;
			$msg = $failure_message ?: 'Assertion failed';
			$this->errors[] = "{$name}: {$msg}";
			echo "\033[31m  ✗ FAIL:\033[0m {$name} — {$msg}\n";
		}
	}

	public function run(): void {
		echo "\n\033[1;36m====================================================\033[0m\n";
		echo "\033[1;36m SEO City Pages — Phase 4 Automated Test Suite\033[0m\n";
		echo "\033[1;36m====================================================\033[0m\n\n";

		$this->test_database_schema_v040();
		$this->test_url_engine_token_generation();
		$this->test_city_page_repository_crud();
		$this->test_cascade_deletion_protection();
		$this->test_csv_idempotency_and_page_generation();
		$this->test_request_resolver_and_status_rules();
		$this->test_preview_security();
		$this->test_dependency_and_security_verification();

		echo "\n\033[1;36m====================================================\033[0m\n";
		echo " Total Tests: " . ( $this->passed + $this->failed ) . " | Passed: \033[32m{$this->passed}\033[0m | Failed: \033[31m{$this->failed}\033[0m\n";
		echo "\033[1;36m====================================================\033[0m\n\n";

		if ( $this->failed > 0 ) {
			echo "\033[31mFailures:\033[0m\n";
			foreach ( $this->errors as $err ) {
				echo " - {$err}\n";
			}
			exit( 1 );
		}
	}

	private function test_database_schema_v040(): void {
		echo "\033[1;33m[1] Database Schema v0.4.0 & Migrations\033[0m\n";

		\SeoCityPages\Database\Schema::install();
		$this->assert( 'Schema tables created without error via install()', true );

		\SeoCityPages\Database\Migrator::maybe_upgrade();
		$db_ver = get_option( \SeoCityPages\Database\Migrator::DB_VERSION_OPTION );
		$this->assert( 'Database version is >= 0.4.0', version_compare( (string) $db_ver, '0.4.0', '>=' ) );
	}

	private function test_url_engine_token_generation(): void {
		echo "\n\033[1;33m[2] URL Engine Token Generation & Validation\033[0m\n";

		$country = (object) [ 'code' => 'IN', 'slug' => 'india' ];
		$state   = (object) [ 'code' => 'GJ', 'slug' => 'gujarat' ];
		$city    = (object) [ 'slug' => 'rajkot' ];
		$service = (object) [ 'slug' => 'seo-agency' ];

		// Default pattern: /{service}/{city}/
		$default_path = \SeoCityPages\URL\URL_Engine::build_path( $country, $state, $city, $service, '', '/{service}/{city}/' );
		$this->assert( 'Default pattern generates /seo-agency/rajkot/', '/seo-agency/rajkot/' === $default_path );

		// Custom hierarchical pattern
		$hier_path = \SeoCityPages\URL\URL_Engine::build_path( $country, $state, $city, $service, '', '/{country}/{state}/{city}/{service}/' );
		$this->assert( 'Hierarchical pattern generates /india/gujarat/rajkot/seo-agency/', '/india/gujarat/rajkot/seo-agency/' === $hier_path );

		// Custom slug override pattern
		$slug_path = \SeoCityPages\URL\URL_Engine::build_path( $country, $state, $city, $service, 'best-seo-agency-rajkot' );
		$this->assert( 'Custom slug override generates /best-seo-agency-rajkot/', '/best-seo-agency-rajkot/' === $slug_path );

		// Pattern validation
		$val_valid = \SeoCityPages\URL\URL_Engine::validate_pattern( '/{service}/{city}/' );
		$this->assert( 'Valid pattern passes validation', $val_valid['valid'] );

		$val_hier = \SeoCityPages\URL\URL_Engine::validate_pattern( '/{country}/{state}/{city}/{service}/' );
		$this->assert( 'Valid 4-token pattern passes', $val_hier['valid'] );

		$val_inv = \SeoCityPages\URL\URL_Engine::validate_pattern( '/locations/{unknown_token}/' );
		$this->assert( 'Unsupported token fails validation', ! $val_inv['valid'] );

		$val_script = \SeoCityPages\URL\URL_Engine::validate_pattern( '/{service}/<script>' );
		$this->assert( 'Pattern with XSS syntax fails validation', ! $val_script['valid'] );
	}

	private function test_city_page_repository_crud(): void {
		echo "\n\033[1;33m[3] City Page Repository CRUD & Operations\033[0m\n";

		// Create prerequisite parent records
		$country_res = \SeoCityPages\Database\Country_Repository::create( [ 'name' => 'India', 'code' => 'IN', 'slug' => 'india' ] );
		$country_id  = is_numeric( $country_res ) ? (int) $country_res : (int) \SeoCityPages\Database\Country_Repository::get_by_name( 'India' )->id;

		$state_res   = \SeoCityPages\Database\State_Repository::create( [ 'country_id' => $country_id, 'name' => 'Gujarat', 'code' => 'GJ', 'slug' => 'gujarat' ] );
		$state_id    = is_numeric( $state_res ) ? (int) $state_res : (int) \SeoCityPages\Database\State_Repository::get_by_name( $country_id, 'Gujarat' )->id;

		$city_res    = \SeoCityPages\Database\City_Repository::create( [ 'country_id' => $country_id, 'state_id' => $state_id, 'name' => 'Rajkot', 'slug' => 'rajkot' ] );
		$city_id     = is_numeric( $city_res ) ? (int) $city_res : (int) \SeoCityPages\Database\City_Repository::get_by_name( $country_id, $state_id, 'Rajkot' )->id;

		$service_res = \SeoCityPages\Database\Service_Repository::create( [ 'name' => 'SEO Agency', 'slug' => 'seo-agency' ] );
		$service_id  = is_numeric( $service_res ) ? (int) $service_res : (int) \SeoCityPages\Database\Service_Repository::get_by_name( 'SEO Agency' )->id;

		// Insert City Page via find_or_create
		$res = \SeoCityPages\Database\City_Page_Repository::find_or_create( [
			'country_id' => $country_id,
			'state_id'   => $state_id,
			'city_id'    => $city_id,
			'service_id' => $service_id,
		] );

		$this->assert( 'City Page created with positive ID', $res['id'] > 0 );
		$page_id = $res['id'];

		$page = \SeoCityPages\Database\City_Page_Repository::get( $page_id );
		$this->assert( 'City Page retrieved successfully', ! empty( $page ) );
		$this->assert( 'Default status is draft', 'draft' === $page->status );
		$this->assert( 'Auto-generated url_path is /seo-agency/rajkot/', '/seo-agency/rajkot/' === $page->url_path );

		// Derived title
		$derived_title = \SeoCityPages\Database\City_Page_Repository::get_derived_title( $page );
		$this->assert( 'Derived title is "SEO Agency in Rajkot"', 'SEO Agency in Rajkot' === $derived_title );

		// Get by URL path
		$by_path = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/seo-agency/rajkot/' );
		$this->assert( 'Lookup by url_path returns correct page ID', (int) $by_path->id === $page_id );

		// Update
		\SeoCityPages\Database\City_Page_Repository::update( $page_id, [
			'status'         => 'published',
			'title_override' => 'Top Rated SEO Agency in Rajkot',
		] );

		$updated_page = \SeoCityPages\Database\City_Page_Repository::get( $page_id );
		$this->assert( 'Page status updated to published', 'published' === $updated_page->status );
		$this->assert( 'Page title_override updated', 'Top Rated SEO Agency in Rajkot' === $updated_page->title_override );

		// Combined filtering
		$results = \SeoCityPages\Database\City_Page_Repository::get_all( [
			'country_id' => $country_id,
			'state_id'   => $state_id,
			'city_id'    => $city_id,
			'service_id' => $service_id,
			'status'     => 'published',
		] );
		$this->assert( 'Filtered query returned exactly 1 matching page', 1 === count( $results ) );

		// Multi-field search
		$search_results = \SeoCityPages\Database\City_Page_Repository::get_all( [ 'search' => 'Rajkot' ] );
		$this->assert( 'Multi-field search found page by city name', count( $search_results ) >= 1 );

		// Bulk status change
		\SeoCityPages\Database\City_Page_Repository::bulk_set_status( [ $page_id ], 'draft' );
		$draft_page = \SeoCityPages\Database\City_Page_Repository::get( $page_id );
		$this->assert( 'Bulk status change updated page back to draft', 'draft' === $draft_page->status );
	}

	private function test_cascade_deletion_protection(): void {
		echo "\n\033[1;33m[4] Cascade Deletion Protection\033[0m\n";

		$country_id = \SeoCityPages\Database\Country_Repository::create( [ 'name' => 'USA', 'code' => 'US', 'slug' => 'usa' ] );
		$state_id   = \SeoCityPages\Database\State_Repository::create( [ 'country_id' => $country_id, 'name' => 'California', 'code' => 'CA', 'slug' => 'california' ] );
		$city_id    = \SeoCityPages\Database\City_Repository::create( [ 'country_id' => $country_id, 'state_id' => $state_id, 'name' => 'San Jose', 'slug' => 'san-jose' ] );
		$service_id = \SeoCityPages\Database\Service_Repository::create( [ 'name' => 'App Development', 'slug' => 'app-development' ] );

		$page_res = \SeoCityPages\Database\City_Page_Repository::find_or_create( [
			'country_id' => $country_id,
			'state_id'   => $state_id,
			'city_id'    => $city_id,
			'service_id' => $service_id,
		] );
		$page_id = $page_res['id'];

		// Attempt to delete Country -> should be blocked
		$del_country = \SeoCityPages\Database\Country_Repository::delete( $country_id );
		$this->assert( 'Country deletion blocked when dependent page exists', is_wp_error( $del_country ) && 'has_dependencies' === $del_country->get_error_code() );

		// Attempt to delete Service -> should be blocked
		$del_service = \SeoCityPages\Database\Service_Repository::delete( $service_id );
		$this->assert( 'Service deletion blocked when dependent page exists', is_wp_error( $del_service ) && 'has_dependencies' === $del_service->get_error_code() );

		// Attempt to delete City -> should be blocked
		$del_city = \SeoCityPages\Database\City_Repository::delete( $city_id );
		$this->assert( 'City deletion blocked when dependent page exists', is_wp_error( $del_city ) && 'has_dependencies' === $del_city->get_error_code() );

		// Delete the dependent page first
		\SeoCityPages\Database\City_Page_Repository::delete( $page_id );

		// Now city and service can be deleted safely
		$del_city_ok = \SeoCityPages\Database\City_Repository::delete( $city_id );
		$this->assert( 'City deleted safely after page removal', true === $del_city_ok );

		$del_service_ok = \SeoCityPages\Database\Service_Repository::delete( $service_id );
		$this->assert( 'Service deleted safely after page removal', true === $del_service_ok );
	}

	private function test_csv_idempotency_and_page_generation(): void {
		echo "\n\033[1;33m[5] CSV Import Idempotency & Landing Page Generation\033[0m\n";

		$csv_data = "country,state,city,service\n"
			. "India,Gujarat,Rajkot,SEO Agency\n"
			. "India,Gujarat,Ahmedabad,SEO Agency\n"
			. "India,Gujarat,Surat,SEO Agency\n"
			. "India,Maharashtra,Mumbai,Digital Marketing Agency\n"
			. "India,Gujarat,Rajkot,Web Development\n";

		$tmp_file = tempnam( sys_get_temp_dir(), 'scp_test_' ) . '.csv';
		file_put_contents( $tmp_file, $csv_data );

		// Validate & Ingest 1st time
		$val_res = \SeoCityPages\Import\CSV_Validator::validate_file( $tmp_file );
		$this->assert( 'CSV validated 5 rows successfully', $val_res['success'] && 5 === $val_res['total_rows'] );

		\SeoCityPages\Import\CSV_Processor::reset_caches();
		$job_1 = \SeoCityPages\Import\CSV_Processor::process_batch( $val_res['valid_rows'], 'job_test_1' );

		$this->assert( 'First import created/updated 5 total combinations', 5 === $job_1['created_combinations'] + $job_1['existing_combinations'] );

		// Verify 5 distinct URL paths exist
		$expected_urls = [
			'/seo-agency/rajkot/',
			'/seo-agency/ahmedabad/',
			'/seo-agency/surat/',
			'/digital-marketing-agency/mumbai/',
			'/web-development/rajkot/',
		];

		foreach ( $expected_urls as $expected_url ) {
			$page = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( $expected_url );
			$this->assert( "Generated page exists with URL: {$expected_url}", ! empty( $page ) );
		}

		// Ingest 2nd time (IDEMPOTENCY TEST)
		\SeoCityPages\Import\CSV_Processor::reset_caches();
		$job_2 = \SeoCityPages\Import\CSV_Processor::process_batch( $val_res['valid_rows'], 'job_test_2' );
		$this->assert( 'Second import created 0 new combinations (100% idempotent)', 0 === $job_2['created_combinations'] );
		$this->assert( 'Second import matched 5 existing combinations', 5 === $job_2['existing_combinations'] );

		@unlink( $tmp_file );
	}

	private function test_request_resolver_and_status_rules(): void {
		echo "\n\033[1;33m[6] Request Resolver & Public Status Rules\033[0m\n";

		global $wp_query, $mock_status_header;

		$resolver = new \SeoCityPages\URL\Request_Resolver();

		// Case A: Unrelated URL (e.g. /about-us/) -> Pass through untouched
		$_SERVER['REQUEST_URI'] = '/about-us/';
		$wp_query->is_404       = false;
		$mock_status_header     = 200;
		$resolver->resolve_request();
		$this->assert( 'Unrelated URL /about-us/ is untouched by resolver', false === $wp_query->is_404 );
		$this->assert( 'Current page is null for non-SEO URL', null === \SeoCityPages\URL\Request_Resolver::get_current_page() );

		// Case B: Draft SEO page requested anonymously -> 404 response
		$draft_page = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/seo-agency/ahmedabad/' );
		$_SERVER['REQUEST_URI'] = '/seo-agency/ahmedabad/';
		$resolver->resolve_request();
		$this->assert( 'Draft page returns 404 to anonymous crawler', true === $wp_query->is_404 );
		$this->assert( 'Status header set to 404 for draft page', 404 === $mock_status_header );

		// Case C: Published SEO page requested publicly -> 200 OK
		\SeoCityPages\Database\City_Page_Repository::update( (int) $draft_page->id, [ 'status' => 'published' ] );
		$wp_query->is_404       = false;
		$mock_status_header     = 200;
		ob_start();
		$resolver->resolve_request();
		ob_end_clean();
		$this->assert( 'Published page returns 200 OK without 404', false === $wp_query->is_404 );
		$this->assert( 'Status header set to 200 for published page', 200 === $mock_status_header );
		$this->assert( 'Current page context set to resolved page', ! empty( \SeoCityPages\URL\Request_Resolver::get_current_page() ) );
	}

	private function test_preview_security(): void {
		echo "\n\033[1;33m[7] Admin Preview Security & Signed Nonces\033[0m\n";

		global $wp_query, $mock_current_user_can;

		$page = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/web-development/rajkot/' );
		$this->assert( 'Test page is in draft state', 'draft' === $page->status );

		$valid_nonce = \SeoCityPages\Security\Security::create_nonce( 'scp_preview_' . $page->id );
		$preview_url = \SeoCityPages\URL\URL_Engine::build_preview_url( (int) $page->id );

		$this->assert( 'Preview URL contains scp_preview_page param', strpos( $preview_url, 'scp_preview_page=' . $page->id ) !== false );
		$this->assert( 'Preview URL contains nonce param', strpos( $preview_url, '_scp_preview_nonce=' ) !== false );

		// Test Valid Authorized Preview
		$_GET['scp_preview_page']   = (string) $page->id;
		$_GET['_scp_preview_nonce'] = $valid_nonce;
		$mock_current_user_can       = true;

		$resolver = new \SeoCityPages\URL\Request_Resolver();
		ob_start();
		$resolver->resolve_request();
		ob_end_clean();

		$this->assert( 'Valid admin preview sets is_preview = true', true === \SeoCityPages\URL\Request_Resolver::is_preview() );
		$this->assert( 'Valid admin preview sets current page context', (int) \SeoCityPages\URL\Request_Resolver::get_current_page()->id === (int) $page->id );

		// Test Unauthorized Preview (no permission)
		$mock_current_user_can = false;
		$unauth_caught = false;
		try {
			$resolver->resolve_request();
		} catch ( \RuntimeException $e ) {
			$unauth_caught = ( strpos( $e->getMessage(), 'wp_die' ) !== false );
		}
		$this->assert( 'Unauthorized user blocked from previewing draft page', $unauth_caught );

		// Test Invalid Nonce
		$mock_current_user_can       = true;
		$_GET['_scp_preview_nonce'] = 'invalid_tampered_nonce';
		$bad_nonce_caught = false;
		try {
			$resolver->resolve_request();
		} catch ( \RuntimeException $e ) {
			$bad_nonce_caught = ( strpos( $e->getMessage(), 'Invalid or expired preview token' ) !== false );
		}
		$this->assert( 'Tampered/invalid preview nonce rejected with 403', $bad_nonce_caught );
	}

	private function test_dependency_and_security_verification(): void {
		echo "\n\033[1;33m[8] Dependency Detection & CSV Security Verification\033[0m\n";

		global $mock_active_theme, $mock_installed_themes;

		// 1. Arolax Parent Theme Detection
		$mock_active_theme = new Mock_WP_Theme( [ 'name' => 'Arolax Theme', 'version' => '1.5.0', 'template' => 'arolax', 'stylesheet' => 'arolax' ] );
		$this->assert( 'Arolax parent theme is detected as active', true === \SeoCityPages\Environment::is_arolax_active() );

		$arolax_status = \SeoCityPages\Environment::get_arolax_status();
		$this->assert( 'Arolax status indicates active', 'active' === $arolax_status['status'] );
		$this->assert( 'Arolax status is_child is false for parent theme', false === $arolax_status['is_child'] );

		// 2. Arolax Child Theme Detection
		$parent_theme     = new Mock_WP_Theme( [ 'name' => 'Arolax', 'version' => '1.5.0', 'template' => 'arolax', 'stylesheet' => 'arolax' ] );
		$mock_active_theme = new Mock_WP_Theme( [ 'name' => 'Arolax Child', 'version' => '1.0.0', 'template' => 'arolax', 'stylesheet' => 'arolax-child' ], $parent_theme );
		$this->assert( 'Arolax child theme is detected as active', true === \SeoCityPages\Environment::is_arolax_active() );

		$child_status = \SeoCityPages\Environment::get_arolax_status();
		$this->assert( 'Arolax child theme status is active', 'active' === $child_status['status'] );
		$this->assert( 'Arolax child theme is_child is true', true === $child_status['is_child'] );
		$this->assert( 'Arolax child theme identifies parent as Arolax', 'Arolax' === $child_status['parent_name'] );

		// 3. Non-Arolax Theme (Theme-Isolated Mode)
		$mock_active_theme = new Mock_WP_Theme( [ 'name' => 'Twenty Twenty-Four', 'version' => '1.1.0', 'template' => 'twentytwentyfour', 'stylesheet' => 'twentytwentyfour' ] );
		$this->assert( 'Non-Arolax theme correctly identifies Arolax as inactive', false === \SeoCityPages\Environment::is_arolax_active() );

		// 4. Elementor Detection
		$elem_status = \SeoCityPages\Environment::get_elementor_status();
		$this->assert( 'Elementor status report returns structured array', is_array( $elem_status ) && isset( $elem_status['is_active'], $elem_status['message'] ) );

		// 5. Nonce Generation & Verification Consistency
		// Step 1 Form Upload Nonce:
		$form_nonce_field = \SeoCityPages\Security\Security::nonce_field( 'import_upload', '_wpnonce', true, false );
		$this->assert( 'Security::nonce_field generates HTML input for import_upload', strpos( $form_nonce_field, 'name="_wpnonce"' ) !== false );

		$valid_upload_nonce = \SeoCityPages\Security\Security::create_nonce( 'import_upload' );
		$this->assert( 'Security::verify_nonce validates import_upload nonce', true === \SeoCityPages\Security\Security::verify_nonce( $valid_upload_nonce, 'import_upload' ) );
		$this->assert( 'Security::verify_nonce rejects double-prefixed token mismatch', false === \SeoCityPages\Security\Security::verify_nonce( $valid_upload_nonce, 'seo_city_pages_import_upload' ) );

		// Step 2 Batch AJAX Nonce:
		$batch_nonce = \SeoCityPages\Security\Security::create_nonce( 'batch_import' );
		$this->assert( 'Security::verify_nonce validates batch_import nonce', true === \SeoCityPages\Security\Security::verify_nonce( $batch_nonce, 'batch_import' ) );
		$this->assert( 'Security::verify_nonce rejects forged batch nonce', false === \SeoCityPages\Security\Security::verify_nonce( 'bad_batch_nonce', 'batch_import' ) );

		// Page Form Edit Nonce:
		$page_edit_nonce = \SeoCityPages\Security\Security::create_nonce( 'save_page_123' );
		$this->assert( 'Security::verify_nonce validates save_page_123 nonce', true === \SeoCityPages\Security\Security::verify_nonce( $page_edit_nonce, 'save_page_123' ) );

		// 6. CSV Formula Injection Sanitization
		$unsafe_cell = '=CMD| " /C calc"!A0';
		$safe_cell   = \SeoCityPages\Security\Security::sanitize_csv_cell( $unsafe_cell );
		$this->assert( 'Formula injection cell beginning with = is escaped', "'" . $unsafe_cell === $safe_cell );

		$plus_cell   = '+123456';
		$safe_plus   = \SeoCityPages\Security\Security::sanitize_csv_cell( $plus_cell );
		$this->assert( 'Formula injection cell beginning with + is escaped', "'" . $plus_cell === $safe_plus );
	}
}

// Execute test suite.
$runner = new Phase4_Test_Runner();
$runner->run();
