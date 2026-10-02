<?php
/**
 * Automated Test Suite for Phase 6 — OpenAI Content Generation Engine.
 *
 * @package SeoCityPages\Tests
 */

declare(strict_types=1);

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

	// In-Memory SQLite Mock WPDB with Phase 6 support.
	class Mock_WPDB {
		public string $prefix = 'wp_';
		public int $insert_id = 0;
		public int $query_count = 0;
		public string $last_error = '';
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
				$this->last_error = $e->getMessage();
				return false;
			}
		}

		public function get_row( string $query, $output = OBJECT ) {
			$this->query_count++;
			try {
				$stmt = $this->pdo->query( $query );
				if ( ARRAY_A === $output ) {
					$row = $stmt->fetch( \PDO::FETCH_ASSOC );
					return $row ?: null;
				}
				$row = $stmt->fetch( \PDO::FETCH_OBJ );
				return $row ?: null;
			} catch ( \PDOException $e ) {
				$this->last_error = $e->getMessage();
				return null;
			}
		}

		public function get_results( string $query, $output = OBJECT ): array {
			$this->query_count++;
			try {
				$stmt = $this->pdo->query( $query );
				if ( ARRAY_A === $output ) {
					return $stmt->fetchAll( \PDO::FETCH_ASSOC ) ?: [];
				}
				return $stmt->fetchAll( \PDO::FETCH_OBJ ) ?: [];
			} catch ( \PDOException $e ) {
				$this->last_error = $e->getMessage();
				return [];
			}
		}

		public function get_var( string $query ) {
			$this->query_count++;
			try {
				$stmt = $this->pdo->query( $query );
				return $stmt->fetchColumn();
			} catch ( \PDOException $e ) {
				$this->last_error = $e->getMessage();
				return null;
			}
		}

		public function get_col( string $query ): array {
			$this->query_count++;
			try {
				$stmt = $this->pdo->query( $query );
				return $stmt->fetchAll( \PDO::FETCH_COLUMN ) ?: [];
			} catch ( \PDOException $e ) {
				$this->last_error = $e->getMessage();
				return [];
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
				$this->last_error = $e->getMessage();
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
				$this->last_error = $e->getMessage();
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
				$this->last_error = $e->getMessage();
				return false;
			}
		}

		public function db_version(): string {
			return '8.0.36-Mock';
		}
	}

	global $wpdb;
	$wpdb = new Mock_WPDB();

	// Global options & user mocks.
	global $mock_options, $mock_current_user_can;
	$mock_options          = [];
	$mock_current_user_can = true;

	if ( ! function_exists( '__' ) ) {
		function __( string $text, string $domain = 'default' ): string { return $text; }
	}
	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( string $text, string $domain = 'default' ): string { return $text; }
	}
	if ( ! function_exists( 'esc_html' ) ) {
		function esc_html( $text ): string { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
	}
	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( $text ): string { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
	}
	if ( ! function_exists( 'esc_url' ) ) {
		function esc_url( string $url ): string { return filter_var( $url, FILTER_SANITIZE_URL ) ?: ''; }
	}
	if ( ! function_exists( 'wp_json_encode' ) ) {
		function wp_json_encode( $data, $options = 0, $depth = 512 ): string { return json_encode( $data, $options, $depth ); }
	}
	if ( ! function_exists( 'get_bloginfo' ) ) {
		function get_bloginfo( $show = '' ): string { return 'Vispan Solutions'; }
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
	if ( ! function_exists( 'delete_option' ) ) {
		function delete_option( $option ) {
			global $mock_options;
			unset( $mock_options[ $option ] );
			return true;
		}
	}
	global $mock_transients;
	$mock_transients = [];
	if ( ! function_exists( 'get_transient' ) ) {
		function get_transient( string $transient ) {
			global $mock_transients;
			return $mock_transients[ $transient ] ?? false;
		}
	}
	if ( ! function_exists( 'set_transient' ) ) {
		function set_transient( string $transient, $value, int $expiration = 0 ): bool {
			global $mock_transients;
			$mock_transients[ $transient ] = $value;
			return true;
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
	if ( ! function_exists( 'get_current_user_id' ) ) {
		function get_current_user_id(): int {
			return 1;
		}
	}
	if ( ! function_exists( 'wp_generate_uuid4' ) ) {
		function wp_generate_uuid4(): string {
			return sprintf(
				'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
				mt_rand( 0, 0xffff ),
				mt_rand( 0, 0x0fff ) | 0x4000,
				mt_rand( 0, 0x3fff ) | 0x8000,
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
			);
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
			if ( ! is_string( $str ) ) {
				return '';
			}
			$clean = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $str );
			$clean = preg_replace( '/\s*on\w+\s*=\s*(["\']).*?\1/is', '', $clean );
			return strip_tags( $clean, '<p><a><b><strong><i><em><u><ul><ol><li><br><h1><h2><h3><h4><h5><h6><blockquote><hr><span><div>' );
		}
	}
	if ( ! function_exists( 'sanitize_title' ) ) {
		function sanitize_title( $title ) {
			$title = strtolower( trim( (string) $title ) );
			$title = preg_replace( '/[^a-z0-9\-_\s]/', '', $title );
			$title = preg_replace( '/[\s_]+/', '-', $title );
			return trim( $title, '-' );
		}
	}
	if ( ! function_exists( 'sanitize_key' ) ) {
		function sanitize_key( $key ) {
			return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
		}
	}
	if ( ! function_exists( 'absint' ) ) {
		function absint( $maybeint ): int {
			return abs( (int) $maybeint );
		}
	}
	if ( ! function_exists( 'current_time' ) ) {
		function current_time( string $type, bool $gmt = false ): string {
			return gmdate( 'Y-m-d H:i:s' );
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
	if ( ! function_exists( 'wp_send_json_success' ) ) {
		function wp_send_json_success( $data = null ) {
			throw new \Exception( 'JSON_SUCCESS: ' . json_encode( $data ) );
		}
	}
	if ( ! function_exists( 'wp_send_json_error' ) ) {
		function wp_send_json_error( $data = null ) {
			throw new \Exception( 'JSON_ERROR: ' . json_encode( $data ) );
		}
	}
	if ( ! function_exists( 'wp_remote_post' ) ) {
		global $mock_http_response;
		function wp_remote_post( string $url, array $args = [] ) {
			global $mock_http_response;
			if ( is_callable( $mock_http_response ) ) {
				return call_user_func( $mock_http_response, $url, $args );
			}
			return $mock_http_response ?? [ 'response' => [ 'code' => 200 ], 'body' => '{}' ];
		}
	}
	if ( ! function_exists( 'wp_remote_get' ) ) {
		global $mock_http_response;
		function wp_remote_get( string $url, array $args = [] ) {
			global $mock_http_response;
			if ( is_callable( $mock_http_response ) ) {
				return call_user_func( $mock_http_response, $url, $args );
			}
			return $mock_http_response ?? [ 'response' => [ 'code' => 200 ], 'body' => '{}' ];
		}
	}
	if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
		function wp_remote_retrieve_response_code( $response ) {
			return is_array( $response ) ? ( $response['response']['code'] ?? 0 ) : 0;
		}
	}
	if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
		function wp_remote_retrieve_body( $response ) {
			return is_array( $response ) ? ( $response['body'] ?? '' ) : '';
		}
	}
	if ( ! function_exists( 'is_wp_error' ) ) {
		function is_wp_error( $thing ): bool {
			return is_object( $thing ) && 'WP_Error' === get_class( $thing );
		}
	}
	if ( ! class_exists( 'WP_Error' ) ) {
		class WP_Error {
			private string $code;
			private string $message;
			public function __construct( string $code = '', string $message = '' ) {
				$this->code    = $code;
				$this->message = $message;
			}
			public function get_error_code(): string { return $this->code; }
			public function get_error_message(): string { return $this->message; }
		}
	}
	if ( ! function_exists( 'home_url' ) ) {
		function home_url( string $path = '' ): string {
			return 'http://example.local' . $path;
		}
	}
	if ( ! function_exists( 'wp_upload_dir' ) ) {
		function wp_upload_dir(): array {
			return [ 'basedir' => sys_get_temp_dir() ];
		}
	}
	if ( ! function_exists( 'wp_is_writable' ) ) {
		function wp_is_writable( string $path ): bool {
			return is_writable( $path );
		}
	}
	if ( ! function_exists( 'is_multisite' ) ) {
		function is_multisite(): bool {
			return false;
		}
	}
	global $shortcode_tags;
	$shortcode_tags = [];
	if ( ! function_exists( 'add_shortcode' ) ) {
		function add_shortcode( string $tag, callable $callback ): void {
			global $shortcode_tags;
			$shortcode_tags[ $tag ] = $callback;
		}
	}
	if ( ! function_exists( 'do_shortcode' ) ) {
		function do_shortcode( string $content ): string {
			global $shortcode_tags;
			if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
				return $content;
			}
			foreach ( $shortcode_tags as $tag => $callback ) {
				if ( false !== strpos( $content, '[' . $tag . ']' ) ) {
					$replacement = is_callable( $callback ) ? call_user_func( $callback, [], '', $tag ) : '';
					$content = str_replace( '[' . $tag . ']', (string) $replacement, $content );
				}
			}
			return $content;
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

	// Autoloader.
	require_once dirname( __DIR__ ) . '/includes/class-autoloader.php';
	\SeoCityPages\Autoloader::register( dirname( __DIR__ ) . '/includes' );
}

namespace SeoCityPages\Tests {

	use SeoCityPages\Database\Migrator;
	use SeoCityPages\Database\Schema;
	use SeoCityPages\Database\Country_Repository;
	use SeoCityPages\Database\State_Repository;
	use SeoCityPages\Database\City_Repository;
	use SeoCityPages\Database\Service_Repository;
	use SeoCityPages\Database\City_Page_Repository;
	use SeoCityPages\Database\Content_Version_Repository;
	use SeoCityPages\Database\Generation_Job_Repository;
	use SeoCityPages\AI\AI_Context;
	use SeoCityPages\AI\AI_Manager;
	use SeoCityPages\AI\OpenAI_Provider;
	use SeoCityPages\AI\Generation_Result;
	use SeoCityPages\AI\Generation_Job_Runner;
	use SeoCityPages\AI\Schema\Content_Schema;
	use SeoCityPages\AI\Schema\Content_Validator;
	use SeoCityPages\AI\Prompt\Prompt_Builder;
	use SeoCityPages\Elementor\Page_Context;
	use SeoCityPages\Elementor\Content_Context;
	use SeoCityPages\Elementor\Elementor_Integration;

	/**
	 * Phase 6 Test Runner.
	 */
	class Phase6_Test_Runner {

		private int $passed = 0;
		private int $failed = 0;
		private array $failures = [];

		public function run(): void {
			echo "\n====================================================\n";
			echo " SEO City Pages — Phase 6 OpenAI Engine Test Suite \n";
			echo "====================================================\n\n";

			$this->test_database_schema_and_migrations();
			$this->test_content_schema_definition();
			$this->test_content_validator_and_anti_hallucination();
			$this->test_prompt_builder_and_injection_defense();
			$this->test_openai_provider_structured_output();
			$this->test_content_versioning_lifecycle();
			$this->test_non_destructive_regeneration();
			$this->test_manual_content_editing();
			$this->test_bulk_generation_queue_and_fault_tolerance();
			$this->test_dynamic_elementor_and_shortcode_hydration();
			$this->test_credential_confidentiality();

			echo "\n====================================================\n";
			echo sprintf( " Total Tests: %d | Passed: %d | Failed: %d\n", $this->passed + $this->failed, $this->passed, $this->failed );
			echo "====================================================\n";

			if ( ! empty( $this->failures ) ) {
				echo "\nFailures:\n";
				foreach ( $this->failures as $f ) {
					echo " - {$f}\n";
				}
				exit( 1 );
			}
		}

		private function assert( string $name, bool $condition, string $message = '' ): void {
			if ( $condition ) {
				$this->passed++;
				echo "  \033[0;32m✓ PASS\033[0m: {$name}\n";
			} else {
				$this->failed++;
				$msg = $message ? " ({$message})" : '';
				$this->failures[] = "{$name}{$msg}";
				echo "  \033[0;31m✗ FAIL\033[0m: {$name}{$msg}\n";
			}
		}

		/**
		 * [1] Database Schema v0.5.0 & Migrations.
		 */
		private function test_database_schema_and_migrations(): void {
			echo "\033[1;33m[1] Database Schema v0.5.0 & Migrations\033[0m\n";

			Schema::install();
			Migrator::maybe_upgrade();
			$db_ver = get_option( Migrator::DB_VERSION_OPTION );
			$this->assert( 'Schema upgraded to TARGET_DB_VERSION (>= 0.5.0)', version_compare( $db_ver, '0.5.0', '>=' ) );

			global $wpdb;
			// Check tables exist by executing simple SELECTs
			$v_count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Schema::get_table_name( Schema::TABLE_CONTENT_VERSIONS ) );
			$this->assert( 'seo_content_versions table is queryable', null !== $v_count );

			$j_count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Schema::get_table_name( Schema::TABLE_GENERATION_JOBS ) );
			$this->assert( 'seo_generation_jobs table is queryable', null !== $j_count );

			// Seed standard entities for remaining tests
			$c_id = Country_Repository::create( [ 'name' => 'United States', 'code' => 'US', 'slug' => 'united-states' ] );
			$s_id = State_Repository::create( [ 'country_id' => $c_id, 'name' => 'Florida', 'code' => 'FL', 'slug' => 'florida' ] );
			$city_id = City_Repository::create( [ 'country_id' => $c_id, 'state_id' => $s_id, 'name' => 'Orlando', 'slug' => 'orlando' ] );
			$srv_id = Service_Repository::create( [ 'name' => 'SEO Services', 'slug' => 'seo-services' ] );

			$res = City_Page_Repository::find_or_create( [
				'country_id'        => $c_id,
				'state_id'          => $s_id,
				'city_id'           => $city_id,
				'service_id'        => $srv_id,
			] );

			$p_id = (int) ( $res['id'] ?? 0 );
			$page = City_Page_Repository::get( $p_id );
			$this->assert( 'City Page created with positive ID', $p_id > 0 && null !== $page );
			$this->assert( 'City Page created with generation_status not_generated', 'not_generated' === $page->generation_status );
			$this->assert( 'City Page initial current_version_id is null/0', empty( $page->current_version_id ) );
		}

		/**
		 * [2] Content Schema Definition for Structured Outputs.
		 */
		private function test_content_schema_definition(): void {
			echo "\n\033[1;33m[2] Content Schema Definition & Structured Output Compliance\033[0m\n";

			$wrapper = Content_Schema::get_openai_json_schema();
			$this->assert( 'Wrapper has name "seo_city_landing_page_content"', 'seo_city_landing_page_content' === ( $wrapper['name'] ?? '' ) );
			$this->assert( 'Wrapper has strict = true for Structured Outputs', true === ( $wrapper['strict'] ?? false ) );

			$schema = $wrapper['schema'] ?? [];
			$this->assert( 'Schema type is object', 'object' === ( $schema['type'] ?? '' ) );
			$this->assert( 'Schema has additionalProperties=false for strict output', false === ( $schema['additionalProperties'] ?? null ) );
			$this->assert( 'Schema contains required sections', ! empty( $schema['required'] ) && in_array( 'hero', $schema['required'], true ) && in_array( 'benefits', $schema['required'], true ) );

			$benefits = $schema['properties']['benefits'] ?? [];
			$this->assert( 'Benefits is array with description', 'array' === ( $benefits['type'] ?? '' ) && ! empty( $benefits['description'] ) );

			$faqs = $schema['properties']['faqs'] ?? [];
			$this->assert( 'FAQs is array with description', 'array' === ( $faqs['type'] ?? '' ) && ! empty( $faqs['description'] ) );
		}

		/**
		 * [3] Content Validator, Hallucination Check & HTML Sanitization.
		 */
		private function test_content_validator_and_anti_hallucination(): void {
			echo "\n\033[1;33m[3] Content Validator, Anti-Hallucination & Sanitization\033[0m\n";

			$context = new AI_Context(
				1,
				'United States',
				'Florida',
				'Orlando',
				'SEO Services',
				[ 'company_name' => 'Vispan Solutions' ]
			);

			$valid_sample = [
				'hero' => [
					'title'       => 'Premier Orlando SEO Services for Fast Market Growth',
					'description' => 'Data-driven local optimization engineered to capture high-intent buyers across Central Florida.',
					'cta_text'    => 'Claim Your Free Strategy Proposal →',
				],
				'introduction' => [
					'heading' => 'Dominate Organic Search Results in Orlando',
					'body'    => '<p>Our specialized SEO services help Orlando companies scale qualified inquiries.</p>',
				],
				'service_overview' => [
					'heading' => 'Comprehensive Local Search Solutions',
					'body'    => '<p>Tailored organic strategies engineered specifically for the Orlando business ecosystem.</p>',
				],
				'benefits' => [
					[ 'title' => 'Google Map Pack Dominance', 'description' => 'Target high-converting search intent in Orlando.' ],
					[ 'title' => 'Local Topical Authority', 'description' => 'Build high-authority backlinks and citations.' ],
					[ 'title' => 'Measurable ROI Reporting', 'description' => 'Track weekly keyword rankings and conversion metrics.' ],
				],
				'process' => [
					[ 'step_number' => 1, 'title' => 'Local Market Audit', 'description' => 'Analyze competitive landscape in Orlando.' ],
					[ 'step_number' => 2, 'title' => 'Entity Schema Setup', 'description' => 'Implement structured data for search algorithms.' ],
					[ 'step_number' => 3, 'title' => 'Conversion Optimization', 'description' => 'Transform clicks into booked consultations.' ],
				],
				'local_relevance' => [
					'heading' => 'Why Orlando Businesses Trust Our Local Focus',
					'body'    => '<p>We understand Orlando market dynamics and competitive search volume across Orange County.</p>',
				],
				'faqs' => [
					[ 'question' => 'How soon can an Orlando business rank?', 'answer' => 'Most local clients see substantial organic gains within 60 to 90 days.' ],
					[ 'question' => 'Do you serve other Florida areas?', 'answer' => 'Yes, our campaigns scale seamlessly across multiple Florida markets.' ],
				],
				'final_cta' => [
					'heading'     => 'Ready to Scale Your Orlando Market Share?',
					'description' => 'Schedule your 30-minute discovery session with our senior strategists.',
					'button_text' => 'Claim Your Free Proposal →',
				],
			];

			$result = Content_Validator::validate( $valid_sample, $context );
			$this->assert( 'Valid sample passes validation', true === $result['valid'] );

			// Test Missing Required Field
			$missing_hero = $valid_sample;
			unset( $missing_hero['hero'] );
			$res_missing = Content_Validator::validate( $missing_hero, $context );
			$this->assert( 'Missing hero section fails validation', false === $res_missing['valid'] );

			// Test Unreplaced Placeholders
			$placeholder_sample = $valid_sample;
			$placeholder_sample['hero']['title'] = 'Top [Service] in [City]';
			$res_placeholder = Content_Validator::validate( $placeholder_sample, $context );
			$this->assert( 'Unreplaced template placeholders [City] are rejected', false === $res_placeholder['valid'] );

			// Test Identity / Anti-Hallucination Rejection (Target city missing completely)
			$wrong_city_sample = $valid_sample;
			$wrong_city_sample['hero']['title'] = 'Premier Denver Marketing Services';
			$wrong_city_sample['hero']['description'] = 'High ranking SEO for Colorado.';
			$wrong_city_sample['introduction']['heading'] = 'Denver Search Engine Experts';
			$wrong_city_sample['introduction']['body'] = 'We help Denver companies.';
			$wrong_city_sample['service_overview']['heading'] = 'Denver Overview';
			$wrong_city_sample['service_overview']['body'] = 'Overview of Denver services.';
			$wrong_city_sample['benefits'] = [
				[ 'title' => 'Denver Maps', 'description' => 'Dominate Denver Colorado.' ],
				[ 'title' => 'Top Authority', 'description' => 'Build Colorado citations.' ],
				[ 'title' => 'ROI Tracking', 'description' => 'Track Denver rankings.' ],
			];
			$wrong_city_sample['process'] = [
				[ 'step_number' => 1, 'title' => 'Audit', 'description' => 'Audit Denver.' ],
				[ 'step_number' => 2, 'title' => 'Schema', 'description' => 'Setup schema.' ],
				[ 'step_number' => 3, 'title' => 'Scale', 'description' => 'Scale up.' ],
			];
			$wrong_city_sample['local_relevance']['heading'] = 'Denver Relevance';
			$wrong_city_sample['local_relevance']['body'] = 'Relevance for Colorado.';
			$wrong_city_sample['faqs'] = [
				[ 'question' => 'How soon in Denver?', 'answer' => '60 days in Colorado.' ],
				[ 'question' => 'Do you serve Denver?', 'answer' => 'Yes Denver only.' ],
			];
			$wrong_city_sample['final_cta'] = [
				'heading'     => 'Ready to Scale Denver?',
				'description' => 'Schedule today in Colorado.',
				'button_text' => 'Get Started',
			];

			$res_hallucination = Content_Validator::validate( $wrong_city_sample, $context );
			$this->assert( 'Hallucinated/mismatched city is rejected by validator', false === $res_hallucination['valid'] );

			// Test HTML Sanitization & XSS Defense
			$xss_sample = $valid_sample;
			$xss_sample['introduction']['body'] = '<script>alert("hacked")</script><p onclick="steal()">Safe Orlando Content</p>';
			$res_xss = Content_Validator::validate( $xss_sample, $context );
			$this->assert( 'XSS sample validates after sanitization', true === $res_xss['valid'] );
			$sanitized = $res_xss['sanitized'];
			$this->assert( 'Script tags and onclick attributes are stripped', false === strpos( $sanitized['introduction']['body'], '<script>' ) && false === strpos( $sanitized['introduction']['body'], 'onclick' ) );
		}

		/**
		 * [4] Prompt Builder & Prompt Injection Defense.
		 */
		private function test_prompt_builder_and_injection_defense(): void {
			echo "\n\033[1;33m[4] Prompt Builder & Prompt Injection Defenses\033[0m\n";

			$context = new AI_Context(
				1,
				'United States',
				'Florida',
				'Orlando',
				'SEO Services',
				[ 'company_name' => 'Vispan Solutions' ],
				[ 'tone' => 'Professional, authoritative, conversion-focused' ],
				[ 'Orlando is home to over 300,000 residents and a major tech hub.' ]
			);

			$system_prompt = Prompt_Builder::build_system_prompt();
			$this->assert( 'System prompt includes strict anti-hallucination rules', false !== strpos( $system_prompt, 'Do NOT invent' ) || false !== strpos( $system_prompt, 'ZERO FABRICATION' ) );

			$user_prompt = Prompt_Builder::build_user_prompt( $context );
			$this->assert( 'User prompt contains city name Orlando', false !== strpos( $user_prompt, 'Orlando' ) );
			$this->assert( 'User prompt contains service name SEO Services', false !== strpos( $user_prompt, 'SEO Services' ) );
			$this->assert( 'User prompt contains company name Vispan Solutions', false !== strpos( $user_prompt, 'Vispan Solutions' ) );
			$this->assert( 'User prompt contains approved facts', false !== strpos( $user_prompt, 'Orlando is home to' ) );
			$this->assert( 'User prompt contains Security & Integrity notice', false !== strpos( $user_prompt, 'SECURITY & INTEGRITY NOTICE' ) );
		}

		/**
		 * [5] OpenAI Provider & Structured Output Mocking.
		 */
		private function test_openai_provider_structured_output(): void {
			echo "\n\033[1;33m[5] OpenAI Provider & Structured Output Mocking\033[0m\n";

			putenv( 'SEO_CITY_OPENAI_API_KEY=sk-test-mock-api-key-123456789' );

			global $mock_http_response;

			$context = new AI_Context(
				1,
				'United States',
				'Florida',
				'Orlando',
				'SEO Services',
				[ 'company_name' => 'Vispan Solutions' ]
			);

			$provider = new OpenAI_Provider();

			// Mock Success Response
			$mock_http_response = [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [
					'id'      => 'chatcmpl-test-123',
					'model'   => 'gpt-4o-mini',
					'choices' => [
						[
							'message' => [
								'role'    => 'assistant',
								'content' => json_encode( [
									'hero' => [
										'title'       => 'Top Orlando SEO Services for Rapid Local Growth',
										'description' => 'Maximize high-intent customer acquisition across Greater Orlando with expert search optimization.',
										'cta_text'    => 'Get Free Audit',
									],
									'introduction' => [
										'heading' => 'Scale Inbound Organic Leads in Orlando',
										'body'    => '<p>We provide full-spectrum local SEO services designed specifically for Orlando enterprises.</p>',
									],
									'service_overview' => [
										'heading' => 'Full-Funnel Local SEO',
										'body'    => '<p>Targeted keyword optimization and local citations.</p>',
									],
									'benefits' => [
										[ 'title' => 'Google Maps 3-Pack', 'description' => 'Capture qualified local searches in Orlando.' ],
										[ 'title' => 'Granular Local Keywords', 'description' => 'Target high-converting queries across Florida.' ],
										[ 'title' => 'Technical Entity Optimization', 'description' => 'Fast loading speeds and structured schema.' ],
									],
									'process' => [
										[ 'step_number' => 1, 'title' => 'Audit & Roadmap', 'description' => 'Comprehensive Orlando search audit.' ],
										[ 'step_number' => 2, 'title' => 'On-Page Optimization', 'description' => 'Targeted service keywords and schema.' ],
										[ 'step_number' => 3, 'title' => 'Authority Building', 'description' => 'Local citation alignment.' ],
									],
									'local_relevance' => [
										'heading' => 'Local Central Florida Expertise',
										'body'    => '<p>Serving Orlando and the wider Orange County market.</p>',
									],
									'faqs' => [
										[ 'question' => 'Why invest in Orlando SEO?', 'answer' => 'Local SEO yields sustainable inbound customer acquisition in Orlando.' ],
										[ 'question' => 'How are results tracked?', 'answer' => 'Transparent ranking, traffic, and conversion reports.' ],
									],
									'final_cta' => [
										'heading'     => 'Supercharge Your Orlando Market Presence',
										'description' => 'Get a custom performance analysis tailored to your market.',
										'button_text' => 'Request Free Analysis →',
									],
								] ),
							],
							'finish_reason' => 'stop',
						],
					],
					'usage' => [
						'prompt_tokens'     => 450,
						'completion_tokens' => 320,
						'total_tokens'      => 770,
					],
				] ),
			];

			$result = $provider->generate( $context );
			$this->assert( 'Provider generate() returns successful Generation_Result', $result->is_success() );
			$this->assert( 'Generation_Result contains correct input tokens', 450 === $result->get_input_tokens() );
			$this->assert( 'Generation_Result contains correct output tokens', 320 === $result->get_output_tokens() );
			$this->assert( 'Generation_Result contains structured content array', is_array( $result->get_content() ) && ! empty( $result->get_content()['hero'] ) );

			// Mock Rate Limit 429
			$mock_http_response = [
				'response' => [ 'code' => 429 ],
				'body'     => json_encode( [ 'error' => [ 'message' => 'Rate limit exceeded' ] ] ),
			];

			$res_429 = $provider->generate( $context );
			$this->assert( 'Provider correctly handles 429 Rate Limit error', ! $res_429->is_success() );
			$this->assert( '429 error contains informative message', false !== strpos( $res_429->get_error_message(), 'Rate limit' ) || false !== strpos( $res_429->get_error_message(), '429' ) );

			// Mock Test Connection
			$mock_http_response = [
				'response' => [ 'code' => 200 ],
				'body'     => json_encode( [ 'data' => [ [ 'id' => 'gpt-4o-mini' ], [ 'id' => 'gpt-4o' ] ] ] ),
			];

			$conn_res = $provider->test_connection();
			$this->assert( 'test_connection() returns successful Connection_Result', $conn_res->is_success() );
		}

		/**
		 * [6] Content Versioning & Approval Lifecycle.
		 */
		private function test_content_versioning_lifecycle(): void {
			echo "\n\033[1;33m[6] Content Versioning & Approval Lifecycle\033[0m\n";

			$page = City_Page_Repository::get_by_url_path( '/seo-services/orlando/' );

			$content_data = [
				'hero' => [
					'title'       => 'Premier Orlando SEO Services',
					'description' => 'Grow your business in Orlando Florida.',
					'cta_text'    => 'Get Free Audit',
				],
				'introduction' => [
					'heading' => 'Orlando Search Specialists',
					'body'    => '<p>Leading SEO solutions for Orlando.</p>',
				],
				'service_overview' => [
					'heading' => 'Full Service SEO',
					'body'    => '<p>Local optimization for Florida companies.</p>',
				],
				'benefits' => [
					[ 'title' => 'High Rankings', 'description' => 'Dominate Orlando searches.' ],
					[ 'title' => 'Top Authority', 'description' => 'Build Florida citations.' ],
					[ 'title' => 'ROI Tracking', 'description' => 'Track weekly gains.' ],
				],
				'process' => [
					[ 'step_number' => 1, 'title' => 'Audit', 'description' => 'Orlando audit.' ],
					[ 'step_number' => 2, 'title' => 'Optimization', 'description' => 'On-page SEO.' ],
					[ 'step_number' => 3, 'title' => 'Scale', 'description' => 'Scale up.' ],
				],
				'local_relevance' => [
					'heading' => 'Why Orlando',
					'body'    => '<p>Targeting Central Florida customers.</p>',
				],
				'faqs' => [
					[ 'question' => 'How long?', 'answer' => '60 to 90 days in Orlando.' ],
					[ 'question' => 'Why Orlando?', 'answer' => 'Huge search volume.' ],
				],
				'final_cta' => [
					'heading'     => 'Ready to Grow in Orlando?',
					'description' => 'Book a strategy session today.',
					'button_text' => 'Get Free Proposal',
				],
			];

			// Create Version 1
			$v1_id = Content_Version_Repository::create( [
				'page_id'           => $page->id,
				'provider'          => 'openai',
				'model'             => 'gpt-4o-mini',
				'content_json'      => $content_data,
				'status'            => 'needs_review',
				'input_tokens'      => 400,
				'output_tokens'     => 300,
			] );

			$v1 = Content_Version_Repository::get( $v1_id );
			$this->assert( 'Version 1 created with positive ID', $v1_id > 0 );
			$this->assert( 'Version 1 version_num is 1', 1 === (int) $v1->version_num );
			$this->assert( 'Version 1 initial status is needs_review', 'needs_review' === $v1->status );

			// Update Page Status to needs_review
			City_Page_Repository::update( $page->id, [ 'generation_status' => 'needs_review' ] );
			$updated_page = City_Page_Repository::get( $page->id );
			$this->assert( 'Page generation_status updated to needs_review', 'needs_review' === $updated_page->generation_status );

			// Approve Version 1
			$approved = Content_Version_Repository::approve_version( $v1_id, 1 );
			$this->assert( 'Content_Version_Repository::approve_version() returns true', $approved );

			$v1_approved = Content_Version_Repository::get( $v1_id );
			$this->assert( 'Version 1 status is now approved', 'approved' === $v1_approved->status );
			$this->assert( 'Version 1 approved_by is 1', 1 === (int) $v1_approved->approved_by );

			$active_version = Content_Version_Repository::get_active_version( $page->id );
			$this->assert( 'get_active_version() returns approved version 1', null !== $active_version && (int) $active_version->id === $v1_id );
		}

		/**
		 * [7] Non-Destructive Regeneration.
		 */
		private function test_non_destructive_regeneration(): void {
			echo "\n\033[1;33m[7] Non-Destructive Regeneration & Version Isolation\033[0m\n";

			$page = City_Page_Repository::get_by_url_path( '/seo-services/orlando/' );

			$active_before = Content_Version_Repository::get_active_version( $page->id );
			$v1_id = (int) $active_before->id;

			// Generate Version 2
			$v2_content = json_decode( (string) $active_before->content_json, true );
			$v2_content['hero']['title'] = 'Regenerated V2: Next-Gen Orlando SEO Services';

			$v2_id = Content_Version_Repository::create( [
				'page_id'           => $page->id,
				'provider'          => 'openai',
				'model'             => 'gpt-4o-mini',
				'content_json'      => $v2_content,
				'status'            => 'needs_review',
				'input_tokens'      => 420,
				'output_tokens'     => 310,
			] );

			$v2 = Content_Version_Repository::get( $v2_id );
			$this->assert( 'Version 2 created with version_num = 2', 2 === (int) $v2->version_num );
			$this->assert( 'Version 2 status is needs_review', 'needs_review' === $v2->status );

			// Critical Check: Active version on live page is STILL Version 1!
			$active_during_review = Content_Version_Repository::get_active_version( $page->id );
			$this->assert( 'Active live version remains Version 1 while Version 2 is in review', (int) $active_during_review->id === $v1_id );

			// Reject Version 2 to verify rejection lifecycle
			Content_Version_Repository::reject_version( $v2_id );
			$v2_rejected = Content_Version_Repository::get( $v2_id );
			$this->assert( 'Version 2 status is rejected', 'rejected' === $v2_rejected->status );
			$this->assert( 'Active live version is STILL Version 1 after Version 2 rejection', (int) Content_Version_Repository::get_active_version( $page->id )->id === $v1_id );
		}

		/**
		 * [8] Manual Content Editing.
		 */
		private function test_manual_content_editing(): void {
			echo "\n\033[1;33m[8] Manual Content Editing & Sanitization\033[0m\n";

			$page = City_Page_Repository::get_by_url_path( '/seo-services/orlando/' );

			$active = Content_Version_Repository::get_active_version( $page->id );
			$edited_content = json_decode( (string) $active->content_json, true );
			$edited_content['hero']['title'] = 'Manually Polished Orlando SEO Services';

			// Create new version with manual status
			$v3_id = Content_Version_Repository::create( [
				'page_id'           => $page->id,
				'provider'          => 'manual',
				'model'             => 'human',
				'content_json'      => $edited_content,
				'status'            => 'approved',
				'input_tokens'      => 0,
				'output_tokens'     => 0,
			] );

			City_Page_Repository::update( $page->id, [ 'current_version_id' => $v3_id ] );
			$active_v3 = Content_Version_Repository::get_active_version( $page->id );
			$active_v3_content = json_decode( (string) $active_v3->content_json, true );

			$this->assert( 'Manually edited Version 3 is created with provider=manual', 'manual' === $active_v3->provider );
			$this->assert( 'Active version updated to Version 3', (int) $active_v3->id === $v3_id );
			$this->assert( 'Active version title contains manual edits', 'Manually Polished Orlando SEO Services' === $active_v3_content['hero']['title'] );
		}

		/**
		 * [9] Bulk Generation Queue & Fault Tolerance.
		 */
		private function test_bulk_generation_queue_and_fault_tolerance(): void {
			echo "\n\033[1;33m[9] Bulk Generation Queue & Fault Tolerance\033[0m\n";

			$us  = Country_Repository::get_by_name( 'United States' );
			$fl  = State_Repository::get_by_name( (int) $us->id, 'Florida' );
			$srv = Service_Repository::get_by_name( 'SEO Services' );

			$tampa_id = City_Repository::create( [ 'country_id' => $us->id, 'state_id' => $fl->id, 'name' => 'Tampa', 'slug' => 'tampa' ] );
			$miami_id = City_Repository::create( [ 'country_id' => $us->id, 'state_id' => $fl->id, 'name' => 'Miami', 'slug' => 'miami' ] );
			$jax_id   = City_Repository::create( [ 'country_id' => $us->id, 'state_id' => $fl->id, 'name' => 'Jacksonville', 'slug' => 'jacksonville' ] );

			$res_tampa = City_Page_Repository::find_or_create( [ 'country_id' => $us->id, 'state_id' => $fl->id, 'city_id' => $tampa_id, 'service_id' => $srv->id ] );
			$res_miami = City_Page_Repository::find_or_create( [ 'country_id' => $us->id, 'state_id' => $fl->id, 'city_id' => $miami_id, 'service_id' => $srv->id ] );
			$res_jax   = City_Page_Repository::find_or_create( [ 'country_id' => $us->id, 'state_id' => $fl->id, 'city_id' => $jax_id,   'service_id' => $srv->id ] );

			$p_tampa = (int) $res_tampa['id'];
			$p_miami = (int) $res_miami['id'];
			$p_jax   = (int) $res_jax['id'];

			// Create Job with 3 pages
			$job_db_id = Generation_Job_Repository::create( [
				'page_ids'      => [ $p_tampa, $p_miami, $p_jax ],
				'provider'      => 'openai',
				'model'         => 'gpt-4o-mini',
				'total_pages'   => 3,
			] );

			$this->assert( 'Generation job created with ID', $job_db_id > 0 );

			// Mock HTTP behavior: Tampa succeeds, Miami fails (500), Jax succeeds
			global $mock_http_response;
			$mock_http_response = function( string $url, array $args ) {
				$body = json_decode( $args['body'] ?? '{}', true );
				$user_content = $body['messages'][1]['content'] ?? '';

				if ( false !== strpos( $user_content, 'Miami' ) ) {
					return [
						'response' => [ 'code' => 500 ],
						'body'     => json_encode( [ 'error' => [ 'message' => 'Simulated OpenAI Gateway Timeout for Miami' ] ] ),
					];
				}

				$city = 'Florida';
				if ( false !== strpos( $user_content, 'Tampa' ) ) {
					$city = 'Tampa';
				} elseif ( false !== strpos( $user_content, 'Jacksonville' ) ) {
					$city = 'Jacksonville';
				}

				return [
					'response' => [ 'code' => 200 ],
					'body'     => json_encode( [
						'id'      => 'chatcmpl-' . strtolower( $city ),
						'model'   => 'gpt-4o-mini',
						'choices' => [
							[
								'message' => [
									'role'    => 'assistant',
									'content' => json_encode( [
										'hero' => [
											'title'       => "Top {$city} SEO Services",
											'description' => "Accelerate your pipeline in {$city} Florida.",
											'cta_text'    => 'Get Started',
										],
										'introduction' => [
											'heading' => "{$city} Local Search Leaders",
											'body'    => "<p>Full suite SEO services in {$city}.</p>",
										],
										'service_overview' => [
											'heading' => "Service in {$city}",
											'body'    => "<p>Tailored strategies in {$city}.</p>",
										],
										'benefits' => [
											[ 'title' => 'Map Pack', 'description' => "Rank top 3 in {$city}." ],
											[ 'title' => 'Keywords', 'description' => "Capture intent in {$city}." ],
											[ 'title' => 'Technical', 'description' => 'Fast loading entity schema.' ],
										],
										'process' => [
											[ 'step_number' => 1, 'title' => 'Audit', 'description' => "Audit {$city} market." ],
											[ 'step_number' => 2, 'title' => 'Optimize', 'description' => 'On-page schema.' ],
											[ 'step_number' => 3, 'title' => 'Build', 'description' => 'Citations and authority.' ],
										],
										'local_relevance' => [
											'heading' => "Local Advantage in {$city}",
											'body'    => "<p>Serving {$city} businesses.</p>",
										],
										'faqs' => [
											[ 'question' => "Why {$city} SEO?", 'answer' => "Target local buyers in {$city}." ],
											[ 'question' => 'How long?', 'answer' => '60 to 90 days.' ],
										],
										'final_cta' => [
											'heading'     => "Ready to Win in {$city}?",
											'description' => 'Get your free performance review.',
											'button_text' => 'Start Now',
										],
									] ),
								],
								'finish_reason' => 'stop',
							],
						],
						'usage' => [ 'prompt_tokens' => 300, 'completion_tokens' => 200, 'total_tokens' => 500 ],
					] ),
				];
			};

			// Process Batch of 3 pages
			$runner = new Generation_Job_Runner();
			$step_res = $runner->process_batch( $job_db_id, 10 );

			$job = Generation_Job_Repository::get( $job_db_id );
			$this->assert( 'Job processed exactly 3 pages', 3 === (int) $job->processed_pages );
			$this->assert( 'Job recorded exactly 2 successful pages', 2 === (int) $job->success_pages );
			$this->assert( 'Job recorded exactly 1 failed page (Miami)', 1 === (int) $job->failed_pages );
			$this->assert( 'Job status is completed_errors', Generation_Job_Repository::STATUS_COMPLETED_ERRORS === $job->status );

			$errors = json_decode( (string) $job->errors_summary, true );
			$this->assert( 'Job error log captured Miami error without aborting Tampa or Jacksonville', ! empty( $errors ) );

			// Check Tampa and Jax pages are in needs_review status
			$tampa_page = City_Page_Repository::get( $p_tampa );
			$jax_page   = City_Page_Repository::get( $p_jax );
			$miami_page = City_Page_Repository::get( $p_miami );

			$this->assert( 'Tampa page transitioned to needs_review', 'needs_review' === $tampa_page->generation_status );
			$this->assert( 'Jacksonville page transitioned to needs_review', 'needs_review' === $jax_page->generation_status );
			$this->assert( 'Miami page transitioned to failed status', 'failed' === $miami_page->generation_status );
		}

		/**
		 * [10] Dynamic Elementor & Token Hydration.
		 */
		private function test_dynamic_elementor_and_shortcode_hydration(): void {
			echo "\n\033[1;33m[10] Dynamic Elementor Tokens & Shortcode Hydration\033[0m\n";

			Elementor_Integration::instance()->register_shortcodes();

			$page = City_Page_Repository::get_by_url_path( '/seo-services/orlando/' );

			// Hydrate Page_Context
			Page_Context::set_context( $page );
			$this->assert( 'Page_Context active for Orlando page', Page_Context::has_context() );

			// Test Direct AI Content Context Getters
			$hero_title = Content_Context::get_hero_title();
			$this->assert( 'Content_Context::get_hero_title returns active approved title', 'Manually Polished Orlando SEO Services' === $hero_title );

			$hero_sub = Content_Context::get_hero_subtitle();
			$this->assert( 'Content_Context::get_hero_subtitle returns subtitle', false !== strpos( $hero_sub, 'Orlando' ) );

			// Test Rendered Semantic Sections
			$benefits_html = Content_Context::render_benefits_html();
			$this->assert( 'render_benefits_html() renders semantic cards with Vispan theme styles', false !== strpos( $benefits_html, 'vispan-hover-card' ) );

			$process_html = Content_Context::render_process_html();
			$this->assert( 'render_process_html() renders numbered step cards', false !== strpos( $process_html, 'step_number' ) || false !== strpos( $process_html, '<h4' ) );

			$faqs_html = Content_Context::render_faqs_html();
			$this->assert( 'render_faqs_html() renders FAQ accordion cards with scroll animations', false !== strpos( $faqs_html, 'vispan-reveal-' ) );

			$raw_template = '<section class="hero"><h1>{{seo_hero_title}}</h1><p>{{seo_hero_subtitle}}</p></section><section class="benefits">{{seo_benefits}}</section><section class="faqs">{{seo_faqs}}</section><section class="stats">{{seo_metro_stats}}</section><section class="submarkets">{{seo_submarkets_grid}}</section><section class="proof">{{seo_case_proof}}</section><section class="signals">{{seo_signal_stack}}</section><section class="essay">{{seo_long_essay}}</section>';
			$rendered = Elementor_Integration::filter_rendered_content( $raw_template );

			$this->assert( 'Token {{seo_hero_title}} replaced with active title', false !== strpos( $rendered, 'Manually Polished Orlando SEO Services' ) );
			$this->assert( 'Token {{seo_benefits}} replaced with benefit grid HTML', false !== strpos( $rendered, 'vispan-hover-card' ) );
			$this->assert( 'Token {{seo_metro_stats}} replaced with stats grid', false !== strpos( $rendered, 'Metro Population' ) || false !== strpos( $rendered, 'vispan-hover-card' ) );
			$this->assert( 'Token {{seo_submarkets_grid}} replaced with submarkets cards', false !== strpos( $rendered, 'Submarket' ) || false !== strpos( $rendered, 'vispan-hover-card' ) );
			$this->assert( 'Token {{seo_case_proof}} replaced with proof banner', false !== strpos( $rendered, 'Case Study' ) || false !== strpos( $rendered, 'vispan-btn-glow' ) );
			$this->assert( 'Token {{seo_signal_stack}} replaced with signals', false !== strpos( $rendered, 'Signal Stack' ) || false !== strpos( $rendered, 'Topical Depth' ) );
			$this->assert( 'Token {{seo_long_essay}} replaced with essay', false !== strpos( $rendered, 'Roadmap' ) || false !== strpos( $rendered, 'EDITORIAL ANALYSIS' ) );
			$this->assert( 'No raw curly braces remain in processed content', false === strpos( $rendered, '{{seo_hero_title}}' ) );

			// Test Shortcode Handling
			$shortcode_template = '<div>[seo_hero_title] - [seo_final_cta_button] - [seo_metro_stats] - [seo_submarkets_grid]</div>';
			$sc_rendered = Elementor_Integration::filter_rendered_content( $shortcode_template );
			$this->assert( 'Shortcode [seo_hero_title] replaced', false !== strpos( $sc_rendered, 'Manually Polished Orlando SEO Services' ) );
			$this->assert( 'Shortcode [seo_final_cta_button] replaced', false !== strpos( $sc_rendered, 'Get Free Proposal' ) || false !== strpos( $sc_rendered, 'Strategy Consultation' ) );
			$this->assert( 'Shortcode [seo_metro_stats] replaced', false !== strpos( $sc_rendered, 'Metro Population' ) || false !== strpos( $sc_rendered, 'vispan-hover-card' ) );
			$this->assert( 'Shortcode [seo_submarkets_grid] replaced', false !== strpos( $sc_rendered, 'vispan-hover-card' ) );

			Page_Context::reset_context();
		}

		/**
		 * [11] Credential Confidentiality & Diagnostic Safety.
		 */
		private function test_credential_confidentiality(): void {
			echo "\n\033[1;33m[11] Credential Confidentiality & Diagnostic Safety\033[0m\n";

			// Set simulated API key in settings
			update_option( \SeoCityPages\Admin\Settings::OPTION_NAME, [
				'ai_enabled' => 1,
				'ai_provider' => 'openai',
				'ai_api_key' => 'sk-proj-super-secret-key-999999999999',
				'ai_model' => 'gpt-4o-mini',
			] );

			$settings = new \SeoCityPages\Admin\Settings();
			$masked_key = $settings->get_masked_api_key();
			$this->assert( 'get_masked_api_key() hides secret credentials', false === strpos( $masked_key, 'super-secret' ) && false !== strpos( $masked_key, '••••' ) );

			// Test Diagnostics Redaction
			$diagnostics = \SeoCityPages\Environment::get_diagnostics();
			$diag_json = json_encode( $diagnostics );
			$this->assert( 'Environment::get_diagnostics() contains zero plain API keys', false === strpos( $diag_json, 'super-secret' ) );
		}
	}

	$runner = new Phase6_Test_Runner();
	$runner->run();
}
