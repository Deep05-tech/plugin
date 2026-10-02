<?php
/**
 * Automated Test Suite for Phase 3 Revision — CSV Importer & Location Data Ingestion.
 *
 * @package SeoCityPages\Tests
 */

// Mock environment constants.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'SEO_CITY_PAGES_VERSION' ) ) {
	define( 'SEO_CITY_PAGES_VERSION', '0.3.0' );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_DIR' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_URL' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_URL', 'http://example.local/wp-content/plugins/seo-city-pages/' );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

// In-Memory SQLite Mock WPDB.
class Mock_WPDB {
	public $prefix = 'wp_';
	public $insert_id = 0;
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
				return (string) (int) $arg;
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

global $wpdb, $wp_version, $mock_options, $mock_transients;
$wpdb            = new Mock_WPDB();
$wp_version      = '7.1';
$mock_options    = [];
$mock_transients = [];

// WordPress Helper Mocks.
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
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string { return strip_tags( trim( $str ) ); }
}
if ( ! function_exists( 'sanitize_file_name' ) ) {
	function sanitize_file_name( string $str ): string { return preg_replace( '/[^a-zA-Z0-9_\.-]/', '', $str ); }
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( string $title ): string {
		$title = strtolower( trim( $title ) );
		$title = preg_replace( '/[^a-z0-9\-_\s]/', '', $title );
		return preg_replace( '/[\s_]+/', '-', $title );
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
	function get_current_user_id(): int { return 1; }
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
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, int $options = 0, int $depth = 512 ): string {
		return json_encode( $data, $options, $depth );
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

/**
 * Phase 3 CSV Importer Test Runner.
 */
class CSV_Importer_Test_Suite {
	private int $passed = 0;
	private int $failed = 0;

	private function assert( bool $condition, string $description ): void {
		if ( $condition ) {
			$this->passed++;
			echo "\033[32m[ PASS ]\033[0m " . $description . PHP_EOL;
		} else {
			$this->failed++;
			echo "\033[31m[ FAIL ]\033[0m " . $description . PHP_EOL;
		}
	}

	public function run(): void {
		echo '====================================================' . PHP_EOL;
		echo '  SEO City Pages — Phase 3 CSV Importer Tests       ' . PHP_EOL;
		echo '====================================================' . PHP_EOL . PHP_EOL;

		// 1. Schema migration to 0.3.0+
		\SeoCityPages\Database\Migrator::maybe_upgrade();
		$this->assert( version_compare( \SeoCityPages\Database\Migrator::get_current_db_version(), '0.3.0', '>=' ), 'Schema migrated to v0.3.0+ with import_jobs & city_pages tables' );

		$this->test_1_first_import();
		$this->test_2_exact_reimport_idempotency();
		$this->test_3_in_csv_duplicate_rows();
		$this->test_4_new_city_under_existing_state();
		$this->test_5_new_service_under_existing_city();
		$this->test_6_new_state_under_existing_country();
		$this->test_7_new_country();
		$this->test_8_invalid_rows_and_empty_fields();
		$this->test_9_extra_columns_handling();
		$this->test_10_whitespace_and_casing_variations();
		$this->test_11_large_batched_chunk_processing();
		$this->test_12_csv_formula_injection_prevention();
		$this->test_13_security_and_capability();
		$this->test_14_error_summary_and_error_csv();

		echo PHP_EOL . '====================================================' . PHP_EOL;
		echo sprintf( 'Results: %d Passed, %d Failed', $this->passed, $this->failed ) . PHP_EOL;
		echo '====================================================' . PHP_EOL;

		if ( $this->failed > 0 ) {
			exit( 1 );
		}
	}

	private function create_temp_csv( string $content ): string {
		$path = tempnam( sys_get_temp_dir(), 'scp_test_' ) . '.csv';
		file_put_contents( $path, $content );
		return $path;
	}

	private function test_1_first_import(): void {
		$csv_content = "country,state,city,service\n" .
			"India,Gujarat,Rajkot,SEO Agency\n" .
			"India,Gujarat,Ahmedabad,SEO Agency\n" .
			"India,Gujarat,Surat,SEO Agency\n" .
			"India,Maharashtra,Mumbai,Digital Marketing Agency\n" .
			"India,Gujarat,Rajkot,Web Development\n";

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );
		$this->assert( $validation['success'] && 5 === $validation['valid_count'], 'Test 1: CSV validated 5 rows successfully' );

		$job_id = wp_generate_uuid4();
		\SeoCityPages\Database\Import_Job_Repository::create( [
			'job_id'     => $job_id,
			'file_name'  => 'sample.csv',
			'total_rows' => 5,
		] );

		\SeoCityPages\Import\CSV_Processor::reset_caches();
		$results = \SeoCityPages\Import\CSV_Processor::process_batch( $validation['valid_rows'], $job_id );

		$this->assert( 5 === $results['processed'], 'Test 1: All 5 rows processed' );
		$this->assert( 1 === $results['created_countries'], 'Test 1: Exactly 1 Country created (India)' );
		$this->assert( 2 === $results['created_states'], 'Test 1: Exactly 2 States created (Gujarat, Maharashtra)' );
		$this->assert( 4 === $results['created_cities'], 'Test 1: Exactly 4 Cities created (Rajkot, Ahmedabad, Surat, Mumbai)' );
		$this->assert( 3 === $results['created_services'], 'Test 1: Exactly 3 Services created (SEO Agency, Digital Marketing Agency, Web Development)' );
		$this->assert( 5 === $results['created_combinations'], 'Test 1: Exactly 5 City + Service combinations created' );
		@unlink( $file );
	}

	private function test_2_exact_reimport_idempotency(): void {
		$csv_content = "country,state,city,service\n" .
			"India,Gujarat,Rajkot,SEO Agency\n" .
			"India,Gujarat,Ahmedabad,SEO Agency\n" .
			"India,Gujarat,Surat,SEO Agency\n" .
			"India,Maharashtra,Mumbai,Digital Marketing Agency\n" .
			"India,Gujarat,Rajkot,Web Development\n";

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );

		$job_id = wp_generate_uuid4();
		\SeoCityPages\Import\CSV_Processor::reset_caches();
		$results = \SeoCityPages\Import\CSV_Processor::process_batch( $validation['valid_rows'], $job_id );

		$this->assert( 0 === $results['created_countries'], 'Test 2 (Re-import): 0 new countries created' );
		$this->assert( 0 === $results['created_states'], 'Test 2 (Re-import): 0 new states created' );
		$this->assert( 0 === $results['created_cities'], 'Test 2 (Re-import): 0 new cities created' );
		$this->assert( 0 === $results['created_services'], 'Test 2 (Re-import): 0 new services created' );
		$this->assert( 0 === $results['created_combinations'], 'Test 2 (Re-import): 0 new combinations created' );
		$this->assert( 5 === $results['existing_combinations'], 'Test 2 (Re-import): All 5 rows identified as existing combinations' );
		@unlink( $file );
	}

	private function test_3_in_csv_duplicate_rows(): void {
		$csv_content = "country,state,city,service\n" .
			"India,Gujarat,Rajkot,SEO Agency\n" .
			"India,Gujarat,Rajkot,SEO Agency\n" . // Duplicate of line 2
			"India,Gujarat,Rajkot,SEO Agency\n";   // Duplicate of line 2

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );

		$this->assert( 1 === $validation['valid_count'], 'Test 3: Only 1 unique row marked valid' );
		$this->assert( 2 === $validation['duplicate_count'], 'Test 3: 2 duplicate rows detected within CSV' );
		@unlink( $file );
	}

	private function test_4_new_city_under_existing_state(): void {
		$csv_content = "country,state,city,service\n" .
			"India,Gujarat,Vadodara,SEO Agency\n";

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );

		\SeoCityPages\Import\CSV_Processor::reset_caches();
		$results = \SeoCityPages\Import\CSV_Processor::process_batch( $validation['valid_rows'] );

		$this->assert( 0 === $results['created_countries'], 'Test 4: Existing Country reused' );
		$this->assert( 0 === $results['created_states'], 'Test 4: Existing State reused' );
		$this->assert( 1 === $results['created_cities'], 'Test 4: New City "Vadodara" created' );
		$this->assert( 0 === $results['created_services'], 'Test 4: Existing Service reused' );
		$this->assert( 1 === $results['created_combinations'], 'Test 4: New combination created for Vadodara' );
		@unlink( $file );
	}

	private function test_5_new_service_under_existing_city(): void {
		$csv_content = "country,state,city,service\n" .
			"India,Gujarat,Rajkot,App Development\n";

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );

		\SeoCityPages\Import\CSV_Processor::reset_caches();
		$results = \SeoCityPages\Import\CSV_Processor::process_batch( $validation['valid_rows'] );

		$this->assert( 0 === $results['created_cities'], 'Test 5: Existing City reused' );
		$this->assert( 1 === $results['created_services'], 'Test 5: New Service "App Development" created' );
		$this->assert( 1 === $results['created_combinations'], 'Test 5: New combination created for Rajkot + App Dev' );
		@unlink( $file );
	}

	private function test_6_new_state_under_existing_country(): void {
		$csv_content = "country,state,city,service\n" .
			"India,Karnataka,Bengaluru,SEO Agency\n";

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );

		\SeoCityPages\Import\CSV_Processor::reset_caches();
		$results = \SeoCityPages\Import\CSV_Processor::process_batch( $validation['valid_rows'] );

		$this->assert( 0 === $results['created_countries'], 'Test 6: Existing Country India reused' );
		$this->assert( 1 === $results['created_states'], 'Test 6: New State Karnataka created' );
		$this->assert( 1 === $results['created_cities'], 'Test 6: New City Bengaluru created' );
		$this->assert( 1 === $results['created_combinations'], 'Test 6: New combination created for Bengaluru' );
		@unlink( $file );
	}

	private function test_7_new_country(): void {
		$csv_content = "country,state,city,service\n" .
			"United Kingdom,England,London,SEO Agency\n";

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );

		\SeoCityPages\Import\CSV_Processor::reset_caches();
		$results = \SeoCityPages\Import\CSV_Processor::process_batch( $validation['valid_rows'] );

		$this->assert( 1 === $results['created_countries'], 'Test 7: New Country United Kingdom created' );
		$this->assert( 1 === $results['created_states'], 'Test 7: New State England created' );
		$this->assert( 1 === $results['created_cities'], 'Test 7: New City London created' );
		$this->assert( 1 === $results['created_combinations'], 'Test 7: New combination created for London' );
		@unlink( $file );
	}

	private function test_8_invalid_rows_and_empty_fields(): void {
		$csv_content = "country,state,city,service\n" .
			",Gujarat,Rajkot,SEO Agency\n" .       // Missing country
			"India,,Rajkot,SEO Agency\n" .          // Missing state
			"India,Gujarat,,SEO Agency\n" .          // Missing city
			"India,Gujarat,Rajkot,\n" .              // Missing service
			"India,Gujarat,Rajkot,SEO Agency,extra\n"; // Inconsistent column count

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );

		$this->assert( 0 === $validation['valid_count'], 'Test 8: 0 valid rows in corrupt file' );
		$this->assert( 5 === $validation['invalid_count'], 'Test 8: All 5 invalid rows flagged with specific errors' );
		@unlink( $file );
	}

	private function test_9_extra_columns_handling(): void {
		$csv_content = "country,state,city,service,notes,postal_code\n" .
			"India,Gujarat,Bhavnagar,SEO Agency,Regional hub,364001\n";

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );

		$this->assert( $validation['success'] && 1 === $validation['valid_count'], 'Test 9: Extra columns mapped cleanly while extracting 4 required fields' );
		$this->assert( 'Bhavnagar' === $validation['valid_rows'][0]['city'], 'Test 9: Correct city extracted despite extra columns' );
		@unlink( $file );
	}

	private function test_10_whitespace_and_casing_variations(): void {
		$csv_content = " Country , STATE , City , SERVICE \n" .
			"  inDIA  ,   gUjArAt  ,   rAjKoT   ,  sEo AgEnCy  \n";

		$file = $this->create_temp_csv( $csv_content );
		$validation = \SeoCityPages\Import\CSV_Validator::validate_file( $file );

		$this->assert( 1 === $validation['valid_count'], 'Test 10: Case/whitespace variations in header and values parsed' );

		\SeoCityPages\Import\CSV_Processor::reset_caches();
		$results = \SeoCityPages\Import\CSV_Processor::process_batch( $validation['valid_rows'] );

		// Should match the existing India / Gujarat / Rajkot / SEO Agency combination
		$this->assert( 0 === $results['created_combinations'], 'Test 10: Whitespace/cased row matched existing record with 0 new combinations created' );
		$this->assert( 1 === $results['existing_combinations'], 'Test 10: Matched as existing combination' );
		@unlink( $file );
	}

	private function test_11_large_batched_chunk_processing(): void {
		$rows = [];
		for ( $i = 1; $i <= 60; $i++ ) {
			$rows[] = [
				'row_index' => $i,
				'country'   => 'India',
				'state'     => 'Gujarat',
				'city'      => 'TestCity_' . $i,
				'service'   => 'SEO Agency',
			];
		}

		$job_id = wp_generate_uuid4();
		\SeoCityPages\Database\Import_Job_Repository::create( [
			'job_id'     => $job_id,
			'file_name'  => 'batch_test.csv',
			'total_rows' => 60,
		] );

		\SeoCityPages\Import\CSV_Processor::reset_caches();
		// Process in 3 batches of 20.
		$b1 = \SeoCityPages\Import\CSV_Processor::process_batch( array_slice( $rows, 0, 20 ), $job_id );
		$b2 = \SeoCityPages\Import\CSV_Processor::process_batch( array_slice( $rows, 20, 20 ), $job_id );
		$b3 = \SeoCityPages\Import\CSV_Processor::process_batch( array_slice( $rows, 40, 20 ), $job_id );

		$job = \SeoCityPages\Database\Import_Job_Repository::get_by_job_id( $job_id );

		$this->assert( 20 === $b1['processed'] && 20 === $b2['processed'] && 20 === $b3['processed'], 'Test 11: All 3 batches of 20 rows processed' );
		$this->assert( 60 === (int) $job->processed_rows, 'Test 11: Job tracking correctly recorded 60 total processed rows across batches' );
		$this->assert( 60 === (int) $job->created_combinations, 'Test 11: Exactly 60 unique test combinations recorded' );
	}

	private function test_12_csv_formula_injection_prevention(): void {
		$cell_eq   = \SeoCityPages\Security\Security::sanitize_csv_cell( '=cmd|/c calc.exe' );
		$cell_plus = \SeoCityPages\Security\Security::sanitize_csv_cell( '+SUM(A1:A10)' );
		$cell_at   = \SeoCityPages\Security\Security::sanitize_csv_cell( '@malicious()' );

		$this->assert( "'=cmd|/c calc.exe" === $cell_eq, 'Test 12: Leading "=" prefixed with single quote' );
		$this->assert( "'+SUM(A1:A10)" === $cell_plus, 'Test 12: Leading "+" prefixed with single quote' );
		$this->assert( "'@malicious()" === $cell_at, 'Test 12: Leading "@" prefixed with single quote' );
	}

	private function test_13_security_and_capability(): void {
		$cap = \SeoCityPages\Security\Capabilities::MANAGE;
		$this->assert( 'manage_seo_city_pages' === $cap, 'Test 13: Capability constant mapped to manage_seo_city_pages' );

		$nonce = \SeoCityPages\Security\Security::create_nonce( 'test_import_action' );
		$this->assert( \SeoCityPages\Security\Security::verify_nonce( $nonce, 'test_import_action' ), 'Test 13: Genuine nonce accepted' );
		$this->assert( ! \SeoCityPages\Security\Security::verify_nonce( 'fake_nonce', 'test_import_action' ), 'Test 13: Forged nonce rejected' );
	}

	private function test_14_error_summary_and_error_csv(): void {
		$job_id = wp_generate_uuid4();
		\SeoCityPages\Database\Import_Job_Repository::create( [
			'job_id'     => $job_id,
			'file_name'  => 'error_test.csv',
			'total_rows' => 2,
		] );

		$errors = [
			[
				'row_index' => 5,
				'country'   => 'India',
				'state'     => 'Unknown',
				'city'      => 'City',
				'service'   => 'Service',
				'reason'    => 'Simulated failure reason',
			],
		];

		\SeoCityPages\Database\Import_Job_Repository::complete( $job_id, [
			'processed_rows' => 2,
			'failed_rows'    => 1,
			'errors_summary' => $errors,
		] );

		$job = \SeoCityPages\Database\Import_Job_Repository::get_by_job_id( $job_id );
		$this->assert( 'completed_errors' === $job->status, 'Test 14: Job with failed rows flagged as completed_errors' );
		$this->assert( strpos( $job->errors_summary, 'Simulated failure reason' ) !== false, 'Test 14: Error summary JSON persisted accurately' );
	}
}

// Execute tests.
$suite = new CSV_Importer_Test_Suite();
$suite->run();
