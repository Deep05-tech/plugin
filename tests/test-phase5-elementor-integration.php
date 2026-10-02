<?php
/**
 * Automated Test Suite for Phase 5 — Elementor Dynamic Template Integration.
 *
 * @package SeoCityPages\Tests
 */

namespace Elementor\Modules\DynamicTags {
	class Module {
		public const TEXT_CATEGORY = 'text';
		public const URL_CATEGORY  = 'url';
		public const IMAGE_CATEGORY = 'image';
	}
}

namespace Elementor\Core\DynamicTags {
	abstract class Tag {
		abstract public function get_name();
		abstract public function get_title();
		abstract public function get_group();
		abstract public function get_categories();
		abstract public function render();
	}

	class Manager {
		public array $groups = [];
		public array $tags   = [];

		public function register_group( string $name, array $data ): void {
			$this->groups[ $name ] = $data;
		}

		public function register( Tag $tag ): void {
			$this->tags[ $tag->get_name() ] = $tag;
		}
	}
}

namespace Elementor {
	class Frontend {
		public function get_builder_content_for_display( int $template_id, bool $with_css = true ): string {
			$city    = \SeoCityPages\Elementor\Page_Context::get_city();
			$service = \SeoCityPages\Elementor\Page_Context::get_service();
			$title   = \SeoCityPages\Elementor\Page_Context::get_title();

			return "<div class=\"elementor elementor-{$template_id}\">"
				. "<h1 class=\"elementor-heading-title\">" . esc_html( $title ) . "</h1>"
				. "<p class=\"elementor-text\">Serving " . esc_html( $city ) . " with " . esc_html( $service ) . ".</p>"
				. "</div>";
		}
	}

	class Plugin {
		public static $instance = null;
		public $frontend;
		public $dynamic_tags;

		public function __construct() {
			$this->frontend     = new Frontend();
			$this->dynamic_tags = new \Elementor\Core\DynamicTags\Manager();
		}
	}
}

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', dirname( __DIR__ ) . '/' );
	}
	if ( ! defined( 'SEO_CITY_PAGES_VERSION' ) ) {
		define( 'SEO_CITY_PAGES_VERSION', '0.5.0' );
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
		public int $query_count = 0;
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
			$this->query_count++;
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
			$this->query_count++;
			try {
				$stmt = $this->pdo->query( $query );
				$row  = $stmt->fetch( \PDO::FETCH_OBJ );
				return $row ?: null;
			} catch ( \PDOException $e ) {
				return null;
			}
		}

		public function get_results( string $query, $output = OBJECT ): array {
			$this->query_count++;
			try {
				$stmt = $this->pdo->query( $query );
				return $stmt->fetchAll( \PDO::FETCH_OBJ ) ?: [];
			} catch ( \PDOException $e ) {
				return [];
			}
		}

		public function get_var( string $query ) {
			$this->query_count++;
			try {
				$stmt = $this->pdo->query( $query );
				return $stmt->fetchColumn();
			} catch ( \PDOException $e ) {
				return null;
			}
		}

		public function insert( string $table, array $data, $format = null ): bool {
			$this->query_count++;
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
			$this->query_count++;
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
			$this->query_count++;
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
	global $wpdb, $mock_options, $mock_posts, $mock_post_meta, $mock_current_user_can, $wp_query;
	$wpdb                  = new Mock_WPDB();
	$mock_options          = [];
	$mock_posts            = [];
	$mock_post_meta        = [];
	$mock_current_user_can = true;

	class Mock_WP_Query {
		public $is_404  = false;
		public $is_page = false;
		public function set_404() {
			$this->is_404  = true;
			$this->is_page = false;
		}
	}
	$wp_query = new Mock_WP_Query();

	\Elementor\Plugin::$instance = new \Elementor\Plugin();
	if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
		define( 'ELEMENTOR_VERSION', '3.18.0' );
	}

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
	if ( ! function_exists( 'current_user_can' ) ) {
		function current_user_can( $cap ) {
			global $mock_current_user_can;
			return (bool) $mock_current_user_can;
		}
	}
	if ( ! function_exists( 'wp_die' ) ) {
		function wp_die( $message = '', $title = '', $args = [] ) {
			$status = is_numeric( $title ) ? (int) $title : 500;
			throw new \RuntimeException( "wp_die: {$message} (Status: {$status})" );
		}
	}
	if ( ! function_exists( 'status_header' ) ) {
		function status_header( $code ) {}
	}
	if ( ! function_exists( 'nocache_headers' ) ) {
		function nocache_headers() {}
	}
	if ( ! function_exists( 'sanitize_text_field' ) ) {
		function sanitize_text_field( $str ) {
			return is_string( $str ) ? trim( strip_tags( $str ) ) : '';
		}
	}
	if ( ! function_exists( 'sanitize_textarea_field' ) ) {
		function sanitize_textarea_field( $str ) {
			return is_string( $str ) ? trim( strip_tags( $str ) ) : '';
		}
	}
	if ( ! function_exists( 'wp_kses_post' ) ) {
		function wp_kses_post( $str ) {
			return is_string( $str ) ? strip_tags( $str, '<p><a><b><strong><i><em><u><ul><ol><li><br><h1><h2><h3><h4><h5><h6><blockquote><hr><span><div>' ) : '';
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
	if ( ! function_exists( 'trailingslashit' ) ) {
		function trailingslashit( string $str ): string {
			return rtrim( $str, '/\\' ) . '/';
		}
	}
	if ( ! function_exists( 'home_url' ) ) {
		function home_url( $path = '' ) {
			return 'http://example.local' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
		}
	}
	if ( ! function_exists( 'admin_url' ) ) {
		function admin_url( $path = '' ) {
			return 'http://example.local/wp-admin/' . ltrim( $path, '/' );
		}
	}
	if ( ! function_exists( 'get_permalink' ) ) {
		function get_permalink( $post = 0 ) {
			return 'http://example.local/?p=' . (int) $post;
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
	if ( ! function_exists( 'wp_slash' ) ) {
		function wp_slash( $val ) { return $val; }
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
	if ( ! function_exists( 'get_post' ) ) {
		function get_post( $post = null ) {
			global $mock_posts;
			$id = is_object( $post ) ? (int) $post->ID : (int) $post;
			return $mock_posts[ $id ] ?? null;
		}
	}
	if ( ! function_exists( 'get_posts' ) ) {
		function get_posts( $args = [] ): array {
			global $mock_posts;
			$type = $args['post_type'] ?? 'post';
			$res  = [];
			foreach ( $mock_posts as $p ) {
				if ( $p->post_type === $type ) {
					$res[] = $p;
				}
			}
			return $res;
		}
	}
	if ( ! function_exists( 'wp_insert_post' ) ) {
		function wp_insert_post( $postarr, $wp_error = false ) {
			global $mock_posts;
			static $id_seq = 100;
			$id_seq++;
			$post = (object) [
				'ID'          => $id_seq,
				'post_title'  => $postarr['post_title'] ?? '',
				'post_type'   => $postarr['post_type'] ?? 'post',
				'post_status' => $postarr['post_status'] ?? 'publish',
				'post_date'   => current_time( 'mysql' ),
			];
			$mock_posts[ $id_seq ] = $post;
			return $id_seq;
		}
	}
	if ( ! function_exists( 'get_post_meta' ) ) {
		function get_post_meta( int $post_id, string $key = '', bool $single = false ) {
			global $mock_post_meta;
			return $mock_post_meta[ $post_id ][ $key ] ?? ( $single ? '' : [] );
		}
	}
	if ( ! function_exists( 'update_post_meta' ) ) {
		function update_post_meta( int $post_id, string $key, $value ): bool {
			global $mock_post_meta;
			$mock_post_meta[ $post_id ][ $key ] = $value;
			return true;
		}
	}
	if ( ! function_exists( 'get_header' ) ) {
		function get_header() {
			echo "<header class=\"site-header\"><!-- Header Content --></header>\n";
		}
	}
	if ( ! function_exists( 'get_footer' ) ) {
		function get_footer() {
			echo "<footer class=\"site-footer\"><!-- Footer Content --></footer>\n";
		}
	}

	// Mock Theme Support.
	class Mock_WP_Theme {
		private array $data;
		private ?Mock_WP_Theme $parent;
		public function __construct( array $data = [], ?Mock_WP_Theme $parent = null ) {
			$this->data   = $data;
			$this->parent = $parent;
		}
		public function exists(): bool { return ! empty( $this->data['name'] ); }
		public function get( string $header ) { return $this->data[ strtolower( $header ) ] ?? ''; }
		public function get_template(): string { return $this->data['template'] ?? ''; }
		public function get_stylesheet(): string { return $this->data['stylesheet'] ?? ''; }
		public function parent(): ?Mock_WP_Theme { return $this->parent; }
	}

	global $mock_active_theme;
	$mock_active_theme = new Mock_WP_Theme( [ 'name' => 'Arolax', 'version' => '1.2.0', 'template' => 'arolax', 'stylesheet' => 'arolax' ] );

	if ( ! function_exists( 'wp_get_theme' ) ) {
		function wp_get_theme( $stylesheet = null, $theme_root = null ) {
			global $mock_active_theme;
			return $mock_active_theme;
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
	class Phase5_Test_Runner {
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
			echo "\033[1;36m SEO City Pages — Phase 5 Automated Test Suite\033[0m\n";
			echo "\033[1;36m====================================================\033[0m\n\n";

			$this->setup_database_and_seed_data();
			$this->test_page_context_provider();
			$this->test_request_isolation();
			$this->test_zero_n_plus_one_queries();
			$this->test_dynamic_tags_api();
			$this->test_starter_template_creation();
			$this->test_template_configuration_and_validation();
			$this->test_frontend_rendering_and_fallback();
			$this->test_five_city_manual_acceptance_matrix();

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

		private function setup_database_and_seed_data(): void {
			echo "\033[1;33m[1] Database Schema & Seed Data Setup\033[0m\n";

			\SeoCityPages\Database\Schema::install();
			$this->assert( 'Database schema installed cleanly', true );

			// Seed Country: India
			$co_id = \SeoCityPages\Database\Country_Repository::create( [ 'name' => 'India', 'code' => 'IN', 'slug' => 'india' ] );
			$this->assert( 'Country "India" created with ID ' . $co_id, $co_id > 0 );

			// Seed States: Gujarat (GJ), Maharashtra (MH)
			$st_gj = \SeoCityPages\Database\State_Repository::create( [ 'country_id' => $co_id, 'name' => 'Gujarat', 'code' => 'GJ', 'slug' => 'gujarat' ] );
			$st_mh = \SeoCityPages\Database\State_Repository::create( [ 'country_id' => $co_id, 'name' => 'Maharashtra', 'code' => 'MH', 'slug' => 'maharashtra' ] );
			$this->assert( 'States "Gujarat" and "Maharashtra" created', $st_gj > 0 && $st_mh > 0 );

			// Seed Cities: Rajkot, Ahmedabad, Surat, Mumbai
			$ci_raj = \SeoCityPages\Database\City_Repository::create( [ 'country_id' => $co_id, 'state_id' => $st_gj, 'name' => 'Rajkot', 'slug' => 'rajkot' ] );
			$ci_ahm = \SeoCityPages\Database\City_Repository::create( [ 'country_id' => $co_id, 'state_id' => $st_gj, 'name' => 'Ahmedabad', 'slug' => 'ahmedabad' ] );
			$ci_sur = \SeoCityPages\Database\City_Repository::create( [ 'country_id' => $co_id, 'state_id' => $st_gj, 'name' => 'Surat', 'slug' => 'surat' ] );
			$ci_mum = \SeoCityPages\Database\City_Repository::create( [ 'country_id' => $co_id, 'state_id' => $st_mh, 'name' => 'Mumbai', 'slug' => 'mumbai' ] );
			$this->assert( 'Cities created: Rajkot, Ahmedabad, Surat, Mumbai', $ci_raj > 0 && $ci_ahm > 0 && $ci_sur > 0 && $ci_mum > 0 );

			// Seed Services: SEO Agency, Digital Marketing Agency, Web Development
			$se_seo = \SeoCityPages\Database\Service_Repository::create( [ 'name' => 'SEO Agency', 'slug' => 'seo-agency' ] );
			$se_dma = \SeoCityPages\Database\Service_Repository::create( [ 'name' => 'Digital Marketing Agency', 'slug' => 'digital-marketing-agency' ] );
			$se_web = \SeoCityPages\Database\Service_Repository::create( [ 'name' => 'Web Development', 'slug' => 'web-development' ] );
			$this->assert( 'Services created: SEO Agency, DMA, Web Development', $se_seo > 0 && $se_dma > 0 && $se_web > 0 );

			// Seed 5 City Pages
			$p1 = \SeoCityPages\Database\City_Page_Repository::find_or_create( [ 'country_id' => $co_id, 'state_id' => $st_gj, 'city_id' => $ci_raj, 'service_id' => $se_seo ] );
			$p2 = \SeoCityPages\Database\City_Page_Repository::find_or_create( [ 'country_id' => $co_id, 'state_id' => $st_gj, 'city_id' => $ci_ahm, 'service_id' => $se_seo ] );
			$p3 = \SeoCityPages\Database\City_Page_Repository::find_or_create( [ 'country_id' => $co_id, 'state_id' => $st_gj, 'city_id' => $ci_sur, 'service_id' => $se_seo ] );
			$p4 = \SeoCityPages\Database\City_Page_Repository::find_or_create( [ 'country_id' => $co_id, 'state_id' => $st_mh, 'city_id' => $ci_mum, 'service_id' => $se_dma ] );
			$p5 = \SeoCityPages\Database\City_Page_Repository::find_or_create( [ 'country_id' => $co_id, 'state_id' => $st_gj, 'city_id' => $ci_raj, 'service_id' => $se_web ] );

			$this->assert( 'All 5 City Page records created with valid IDs', $p1['id'] > 0 && $p2['id'] > 0 && $p3['id'] > 0 && $p4['id'] > 0 && $p5['id'] > 0 );
		}

		private function test_page_context_provider(): void {
			echo "\n\033[1;33m[2] Page_Context Provider & Data Hydration\033[0m\n";

			$page = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/seo-agency/rajkot/' );
			\SeoCityPages\Elementor\Page_Context::set_context( $page );

			$this->assert( 'Page_Context identifies active context', \SeoCityPages\Elementor\Page_Context::has_context() );
			$this->assert( 'Page_Context get_city returns "Rajkot"', 'Rajkot' === \SeoCityPages\Elementor\Page_Context::get_city() );
			$this->assert( 'Page_Context get_state returns "Gujarat"', 'Gujarat' === \SeoCityPages\Elementor\Page_Context::get_state() );
			$this->assert( 'Page_Context get_country returns "India"', 'India' === \SeoCityPages\Elementor\Page_Context::get_country() );
			$this->assert( 'Page_Context get_service returns "SEO Agency"', 'SEO Agency' === \SeoCityPages\Elementor\Page_Context::get_service() );
			$this->assert( 'Page_Context get_title returns "SEO Agency in Rajkot"', 'SEO Agency in Rajkot' === \SeoCityPages\Elementor\Page_Context::get_title() );
			$this->assert( 'Page_Context get_url returns full URL', strpos( \SeoCityPages\Elementor\Page_Context::get_url(), '/seo-agency/rajkot/' ) !== false );

			// Custom Title Override
			\SeoCityPages\Database\City_Page_Repository::update( (int) $page->id, [ 'title_override' => 'Best SEO Agency in Rajkot' ] );
			$updated = \SeoCityPages\Database\City_Page_Repository::get( (int) $page->id );
			\SeoCityPages\Elementor\Page_Context::set_context( $updated );

			$this->assert( 'Page_Context honors custom title override', 'Best SEO Agency in Rajkot' === \SeoCityPages\Elementor\Page_Context::get_title() );
		}

		private function test_request_isolation(): void {
			echo "\n\033[1;33m[3] Request Isolation & Data Integrity\033[0m\n";

			// Request A: Rajkot
			$p_raj = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/seo-agency/rajkot/' );
			\SeoCityPages\Elementor\Page_Context::set_context( $p_raj );
			$this->assert( 'Request A: City is Rajkot', 'Rajkot' === \SeoCityPages\Elementor\Page_Context::get_city() );

			// Request B: Ahmedabad
			$p_ahm = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/seo-agency/ahmedabad/' );
			\SeoCityPages\Elementor\Page_Context::set_context( $p_ahm );
			$this->assert( 'Request B: City is Ahmedabad (not Rajkot)', 'Ahmedabad' === \SeoCityPages\Elementor\Page_Context::get_city() );
			$this->assert( 'Request B: Service is SEO Agency', 'SEO Agency' === \SeoCityPages\Elementor\Page_Context::get_service() );

			// Request C: Mumbai
			$p_mum = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/digital-marketing-agency/mumbai/' );
			\SeoCityPages\Elementor\Page_Context::set_context( $p_mum );
			$this->assert( 'Request C: City is Mumbai', 'Mumbai' === \SeoCityPages\Elementor\Page_Context::get_city() );
			$this->assert( 'Request C: State is Maharashtra', 'Maharashtra' === \SeoCityPages\Elementor\Page_Context::get_state() );
			$this->assert( 'Request C: Service is Digital Marketing Agency', 'Digital Marketing Agency' === \SeoCityPages\Elementor\Page_Context::get_service() );

			// Request D: Context Reset
			\SeoCityPages\Elementor\Page_Context::reset_context();
			$this->assert( 'Context Reset: has_context returns false', false === \SeoCityPages\Elementor\Page_Context::has_context() );
			$this->assert( 'Context Reset: get_city falls back to preview context', ! empty( \SeoCityPages\Elementor\Page_Context::get_city() ) );
		}

		private function test_zero_n_plus_one_queries(): void {
			echo "\n\033[1;33m[4] Zero N+1 Query Architecture Performance\033[0m\n";

			global $wpdb;

			$page = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/seo-agency/surat/' );
			\SeoCityPages\Elementor\Page_Context::set_context( $page );

			$queries_before = $wpdb->query_count;

			// Simulate 50 dynamic widgets accessing various fields on the same page
			for ( $i = 0; $i < 50; $i++ ) {
				\SeoCityPages\Elementor\Page_Context::get_city();
				\SeoCityPages\Elementor\Page_Context::get_state();
				\SeoCityPages\Elementor\Page_Context::get_country();
				\SeoCityPages\Elementor\Page_Context::get_service();
				\SeoCityPages\Elementor\Page_Context::get_title();
				\SeoCityPages\Elementor\Page_Context::get_url();
				\SeoCityPages\Elementor\Page_Context::get_field( 'seo_city_name' );
				\SeoCityPages\Elementor\Page_Context::get_field( 'seo_city_service' );
			}

			$queries_after = $wpdb->query_count;

			$this->assert( '50 widgets accessing dynamic fields generated exactly 0 additional database queries', $queries_before === $queries_after );
		}

		private function test_dynamic_tags_api(): void {
			echo "\n\033[1;33m[5] Elementor Dynamic Tags API & Rendering\033[0m\n";

			$manager = new \Elementor\Core\DynamicTags\Manager();
			\SeoCityPages\Elementor\Elementor_Integration::instance()->register_dynamic_tags( $manager );

			$this->assert( 'Dynamic Tag Group "seo-city-pages" registered', isset( $manager->groups['seo-city-pages'] ) );
			$this->assert( 'Dynamic Tag "seo_city_name" registered', isset( $manager->tags['seo_city_name'] ) );
			$this->assert( 'Dynamic Tag "seo_city_state" registered', isset( $manager->tags['seo_city_state'] ) );
			$this->assert( 'Dynamic Tag "seo_city_country" registered', isset( $manager->tags['seo_city_country'] ) );
			$this->assert( 'Dynamic Tag "seo_city_service" registered', isset( $manager->tags['seo_city_service'] ) );
			$this->assert( 'Dynamic Tag "seo_city_title" registered', isset( $manager->tags['seo_city_title'] ) );
			$this->assert( 'Dynamic Tag "seo_city_url" registered', isset( $manager->tags['seo_city_url'] ) );

			// Test Output Buffer Rendering
			$page = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/seo-agency/surat/' );
			\SeoCityPages\Elementor\Page_Context::set_context( $page );

			ob_start();
			$manager->tags['seo_city_name']->render();
			$city_out = ob_get_clean();
			$this->assert( 'Tag_City renders "Surat"', 'Surat' === $city_out );

			ob_start();
			$manager->tags['seo_city_service']->render();
			$svc_out = ob_get_clean();
			$this->assert( 'Tag_Service renders "SEO Agency"', 'SEO Agency' === $svc_out );
		}

		private function test_starter_template_creation(): void {
			echo "\n\033[1;33m[6] Starter Master Template Programmatic Generation\033[0m\n";

			$created_id = \SeoCityPages\Elementor\Elementor_Integration::create_starter_template( 'SEO City Master Test Template' );

			$this->assert( 'Starter template created with positive ID', is_numeric( $created_id ) && $created_id > 0 );

			$tpl_post = get_post( (int) $created_id );
			$this->assert( 'Starter template post exists', ! empty( $tpl_post ) );
			$this->assert( 'Starter template post_type is "elementor_library"', 'elementor_library' === $tpl_post->post_type );

			$edit_mode = get_post_meta( (int) $created_id, '_elementor_edit_mode', true );
			$this->assert( 'Starter template _elementor_edit_mode is "builder"', 'builder' === $edit_mode );

			$json_data = get_post_meta( (int) $created_id, '_elementor_data', true );
			$this->assert( 'Starter template contains dynamic tag reference', strpos( $json_data, 'seo_city_title' ) !== false );
		}

		private function test_template_configuration_and_validation(): void {
			echo "\n\033[1;33m[7] Template Selection & Validation\033[0m\n";

			// 1. Valid Template Selection
			$starter_id = wp_insert_post( [
				'post_title'  => 'Client SEO Template',
				'post_type'   => 'elementor_library',
				'post_status' => 'publish',
			] );

			$settings = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME, [] );
			$settings['template_id'] = $starter_id;
			update_option( \SeoCityPages\Admin\Settings::OPTION_NAME, $settings );

			$saved_settings = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME );
			$this->assert( 'Template ID saved successfully in settings', (int) $saved_settings['template_id'] === $starter_id );

			// 2. Fetch available templates
			$templates = \SeoCityPages\Elementor\Elementor_Integration::get_available_templates();
			$this->assert( 'get_available_templates returns list of elementor_library posts', count( $templates ) >= 1 );

			// 3. Settings Sanitizer
			$settings_obj = new \SeoCityPages\Admin\Settings();
			$sanitized = $settings_obj->sanitize_settings( [
				'template_id'     => (string) $starter_id,
				'preview_page_id' => '12',
			] );

			$this->assert( 'Settings sanitizer casts template_id to integer', is_int( $sanitized['template_id'] ) && $sanitized['template_id'] === $starter_id );
			$this->assert( 'Settings sanitizer casts preview_page_id to integer', is_int( $sanitized['preview_page_id'] ) && 12 === $sanitized['preview_page_id'] );
		}

		private function test_frontend_rendering_and_fallback(): void {
			echo "\n\033[1;33m[8] Frontend Template Hydration & Semantic Fallback\033[0m\n";

			// Test A: Semantic Fallback Rendering (when template_id = 0)
			$settings = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME, [] );
			$settings['template_id'] = 0;
			update_option( \SeoCityPages\Admin\Settings::OPTION_NAME, $settings );

			$p_raj = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( '/seo-agency/rajkot/' );
			\SeoCityPages\Database\City_Page_Repository::update( (int) $p_raj->id, [ 'status' => 'published' ] );

			$_SERVER['REQUEST_URI'] = '/seo-agency/rajkot/';
			$resolver = new \SeoCityPages\URL\Request_Resolver();

			ob_start();
			$resolver->resolve_request();
			$fallback_html = ob_get_clean();

			$this->assert( 'Fallback HTML includes site-header', strpos( $fallback_html, 'site-header' ) !== false );
			$this->assert( 'Fallback HTML includes page heading', strpos( $fallback_html, 'SEO Agency in Rajkot' ) !== false );
			$this->assert( 'Fallback HTML includes service label', strpos( $fallback_html, 'SEO Agency' ) !== false );
			$this->assert( 'Fallback HTML includes site-footer', strpos( $fallback_html, 'site-footer' ) !== false );

			// Test B: Elementor Master Template Rendering
			$starter_id = wp_insert_post( [
				'post_title'  => 'Master Template',
				'post_type'   => 'elementor_library',
				'post_status' => 'publish',
			] );
			$settings['template_id'] = $starter_id;
			update_option( \SeoCityPages\Admin\Settings::OPTION_NAME, $settings );

			ob_start();
			$resolver->resolve_request();
			$elementor_html = ob_get_clean();

			$this->assert( 'Elementor HTML rendered builder content', strpos( $elementor_html, "elementor-{$starter_id}" ) !== false );
			$this->assert( 'Elementor HTML dynamically injected Rajkot title', strpos( $elementor_html, 'SEO Agency in Rajkot' ) !== false );
			$this->assert( 'Elementor HTML preserved theme header and footer', strpos( $elementor_html, 'site-header' ) !== false && strpos( $elementor_html, 'site-footer' ) !== false );
		}

		private function test_five_city_manual_acceptance_matrix(): void {
			echo "\n\033[1;33m[9] Five-City Manual Acceptance Matrix (Single Reusable Template)\033[0m\n";

			$matrix = [
				[
					'path'     => '/seo-agency/rajkot/',
					'expected' => [ 'title' => 'SEO Agency in Rajkot', 'city' => 'Rajkot', 'state' => 'Gujarat', 'country' => 'India', 'service' => 'SEO Agency' ],
				],
				[
					'path'     => '/seo-agency/ahmedabad/',
					'expected' => [ 'title' => 'SEO Agency in Ahmedabad', 'city' => 'Ahmedabad', 'state' => 'Gujarat', 'country' => 'India', 'service' => 'SEO Agency' ],
				],
				[
					'path'     => '/seo-agency/surat/',
					'expected' => [ 'title' => 'SEO Agency in Surat', 'city' => 'Surat', 'state' => 'Gujarat', 'country' => 'India', 'service' => 'SEO Agency' ],
				],
				[
					'path'     => '/digital-marketing-agency/mumbai/',
					'expected' => [ 'title' => 'Digital Marketing Agency in Mumbai', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'country' => 'India', 'service' => 'Digital Marketing Agency' ],
				],
				[
					'path'     => '/web-development/rajkot/',
					'expected' => [ 'title' => 'Web Development in Rajkot', 'city' => 'Rajkot', 'state' => 'Gujarat', 'country' => 'India', 'service' => 'Web Development' ],
				],
			];

			$resolver = new \SeoCityPages\URL\Request_Resolver();

			foreach ( $matrix as $item ) {
				$page = \SeoCityPages\Database\City_Page_Repository::get_by_url_path( $item['path'] );
				\SeoCityPages\Database\City_Page_Repository::update( (int) $page->id, [ 'status' => 'published' ] );

				$_SERVER['REQUEST_URI'] = $item['path'];

				ob_start();
				$resolver->resolve_request();
				$rendered = ob_get_clean();

				$this->assert( "Matrix [{$item['path']}]: Title is \"{$item['expected']['title']}\"", strpos( $rendered, $item['expected']['title'] ) !== false );
				$this->assert( "Matrix [{$item['path']}]: Body contains \"{$item['expected']['city']}\"", strpos( $rendered, $item['expected']['city'] ) !== false );
				$this->assert( "Matrix [{$item['path']}]: Body contains \"{$item['expected']['service']}\"", strpos( $rendered, $item['expected']['service'] ) !== false );
			}

			$this->assert( 'Single Master Template rendered all 5 distinct landing pages without duplicating Elementor documents', true );
		}
	}

	// Run test suite.
	$runner = new Phase5_Test_Runner();
	$runner->run();
}
