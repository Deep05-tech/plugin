<?php
/**
 * Automated Test Suite for Dedicated Keywords CSV Upload & Global Keyword Bank.
 *
 * @package SeoCityPages\Tests
 */

// Mock environment constants.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'SEO_CITY_PAGES_VERSION' ) ) {
	define( 'SEO_CITY_PAGES_VERSION', '1.0.0' );
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
			if ( ! $row ) {
				return null;
			}
			return $row;
		} catch ( \PDOException $e ) {
			return null;
		}
	}

	public function get_results( string $query, $output = OBJECT ) {
		try {
			$stmt = $this->pdo->query( $query );
			return $stmt->fetchAll( \PDO::FETCH_OBJ );
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
		$fields       = implode( ', ', array_keys( $data ) );
		$placeholders = implode( ', ', array_fill( 0, count( $data ), '?' ) );
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
	function get_option( string $option, $default = false ) {
		global $mock_options;
		return $mock_options[ $option ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $option, $value, $autoload = null ): bool {
		global $mock_options;
		$mock_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $str ): string { return trim( strip_tags( $str ) ); }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( string $str ): string { return trim( strip_tags( $str ) ); }
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( string $title ): string {
		$raw = strtolower( trim( $title ) );
		$raw = preg_replace( '/[^a-z0-9_\-\s]/', '', $raw );
		return preg_replace( '/[\s_]+/', '-', $raw );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string { return filter_var( trim( $url ), FILTER_SANITIZE_URL ) ?: ''; }
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( string $type, bool $gmt = false ): string { return gmdate( 'Y-m-d H:i:s' ); }
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string { return $text; }
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( string $show = '' ): string { return 'Vispan Solutions'; }
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int { return 1; }
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ): bool { return true; }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, int $options = 0, int $depth = 512 ): string { return json_encode( $data, $options, $depth ); }
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( string $string ): string { return rtrim( $string, '/\\' ) . '/'; }
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( string $string ): string { return rtrim( $string, '/\\' ); }
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( string $target ): bool {
		return is_dir( $target ) || @mkdir( $target, 0755, true );
	}
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir(): array {
		$tmp = sys_get_temp_dir() . '/mock_wp_uploads_kw_' . uniqid();
		if ( ! is_dir( $tmp ) ) {
			@mkdir( $tmp, 0755, true );
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

if ( ! function_exists( 'get_role' ) ) {
	function get_role( string $role ) {
		return new class {
			public function add_cap( string $cap, bool $grant = true ): void {}
			public function remove_cap( string $cap ): void {}
		};
	}
}

// Load Autoloader & Core Classes
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/class-autoloader.php';
\SeoCityPages\Autoloader::register( SEO_CITY_PAGES_PLUGIN_DIR . 'includes/' );

// Install DB Schema into Mock WPDB
\SeoCityPages\Database\Schema::install();

// Run Test Assertions
echo "\n======================================================\n";
echo "  TEST SUITE: Dedicated Keywords CSV & Keyword Bank\n";
echo "======================================================\n\n";

$test_count = 0;
$pass_count = 0;

function assert_true( bool $condition, string $message ): void {
	global $test_count, $pass_count;
	$test_count++;
	if ( $condition ) {
		$pass_count++;
		echo "  [PASS] {$message}\n";
	} else {
		echo "  [FAIL] {$message}\n";
	}
}

// 1. Schema & Migration Verification
echo "1. Testing Schema & Migrator Versioning...\n";
\SeoCityPages\Database\Migrator::maybe_upgrade();
$db_version = \SeoCityPages\Database\Migrator::get_current_db_version();
assert_true( version_compare( $db_version, '0.10.0', '>=' ), "Database target version updated to at least v0.10.0 (Current: {$db_version})" );

// 2. Keyword Repository Bulk Ingestion & Deduplication
echo "\n2. Testing Keyword Repository Bulk Ingestion & Deduplication...\n";
$raw_sample_keywords = [
	'SEO Agency',
	'Local Search Engine Optimization',
	'Best SEO Company',
	'Google Map Pack Ranking',
	'Organic Traffic Growth',
	'B2B Digital Marketing',
	'E-commerce Search Optimization',
	'SEO Agency', // Duplicate
	'  seo agency  ', // Case/whitespace duplicate
	'PPC Management Services',
	'Google Ads Expert',
];

$import_stats = \SeoCityPages\Database\Keyword_Repository::bulk_insert( $raw_sample_keywords, true );
assert_true( $import_stats['imported'] === 9, "Imported 9 unique keywords (Expected 9, got {$import_stats['imported']})" );
assert_true( $import_stats['skipped'] === 2, "Skipped 2 duplicates (Expected 2, got {$import_stats['skipped']})" );
assert_true( \SeoCityPages\Database\Keyword_Repository::count() === 9, 'Keyword Bank total count is 9' );

// 3. Keyword Bank Search and Retrieval
echo "\n3. Testing Keyword Search and Pagination...\n";
$searched = \SeoCityPages\Database\Keyword_Repository::get_all( 10, 0, 'Google' );
assert_true( count( $searched ) === 2, 'Search for "Google" matches 2 keywords (Map Pack Ranking, Google Ads Expert)' );

// 4. Service-Relevant Keyword Extraction
echo "\n4. Testing Service-Relevant Keyword Extraction...\n";
$seo_keywords = \SeoCityPages\Database\Keyword_Repository::get_relevant_for_service( 'SEO Agency', 'Rajkot', 5 );
assert_true( count( $seo_keywords ) === 5, 'Retrieved 5 relevant keywords for SEO Agency service' );
assert_true( in_array( 'seo agency', $seo_keywords, true ), 'Includes "seo agency"' );
assert_true( in_array( 'best seo company', $seo_keywords, true ), 'Includes "best seo company"' );

$ppc_keywords = \SeoCityPages\Database\Keyword_Repository::get_relevant_for_service( 'Google Ads Management', 'Austin', 5 );
assert_true( in_array( 'google ads expert', $ppc_keywords, true ), 'PPC service matched "google ads expert"' );

// 5. Automatic AI Context & Prompt Builder Integration from Keyword Bank
echo "\n5. Testing AI Context & Prompt Builder Global Keyword Bank Integration...\n";
// Create location taxonomies & a city page with NO explicit keywords set
$co_id = \SeoCityPages\Database\Country_Repository::create( [ 'name' => 'India', 'status' => 'active' ] );
$st_id = \SeoCityPages\Database\State_Repository::create( [ 'country_id' => $co_id, 'name' => 'Gujarat', 'status' => 'active' ] );
$ci_id = \SeoCityPages\Database\City_Repository::create( [ 'country_id' => $co_id, 'state_id' => $st_id, 'name' => 'Rajkot', 'status' => 'active' ] );
$sv_id = \SeoCityPages\Database\Service_Repository::create( [ 'name' => 'SEO Agency', 'status' => 'active' ] );

$page_res = \SeoCityPages\Database\City_Page_Repository::find_or_create( [
	'country_id' => $co_id,
	'state_id'   => $st_id,
	'city_id'    => $ci_id,
	'service_id' => $sv_id,
] );
$page_id = (int) $page_res['id'];

// Build AI Context - should automatically query Keyword Bank
$context = \SeoCityPages\AI\AI_Context::from_page_id( $page_id );
assert_true( $context !== null, 'AI_Context initialized successfully' );
assert_true( ! empty( $context->get_primary_keyword() ), 'AI_Context automatically populated primary keyword from Keyword Bank' );
assert_true( count( $context->get_keywords_list() ) > 1, 'AI_Context automatically populated secondary keywords from Keyword Bank' );

// Build user prompt - should contain target keywords from bank
$prompt = \SeoCityPages\AI\Prompt\Prompt_Builder::build_user_prompt( $context );
assert_true( false !== strpos( $prompt, '=== TARGET SEO KEYWORDS & SEARCH INTENT (MANDATORY INTEGRATION) ===' ), 'Prompt contains TARGET SEO KEYWORDS section' );
assert_true( false !== strpos( $prompt, '1.5% - 3.0% keyword density limits' ), 'Prompt enforces strict keyword density instructions' );

// 6. Intelligent Keyword Sanitization & Noise Stripping (User Sample Cases)
echo "\n6. Testing NLP Keyword Sanitization & Intent Extraction...\n";
$test_cases = [
	'seo for lawyers by a lawyer turned seo pro' => 'seo for lawyers',
	'attorney seo johnny'                        => 'attorney seo',
	'johnny chen attorney seo'                   => 'attorney seo',
	'for law firms only marketing'               => 'law firm marketing',
	'engine lawyer marketing search'             => 'lawyer search engine marketing',
	'market my law firm'                         => 'law firm marketing',
	'market your law firm'                       => 'law firm marketing',
	'advertising my law firm'                    => 'law firm advertising',
	'attorneysync law firm seo services'         => 'law firm seo services',
	'scorpion law firm marketing'                => 'law firm marketing',
	'seo marketing seo lawyer marketing'         => 'seo marketing lawyer',
	'about law firm seo'                         => 'law firm seo',
	'lawyer local seo near me'                   => 'lawyer local seo',
];

foreach ( $test_cases as $noisy => $expected ) {
	$sanitized = \SeoCityPages\Import\Keyword_Sanitizer::sanitize( $noisy );
	assert_true( $sanitized === $expected, sprintf( 'Sanitized "%s" -> "%s" (Expected: "%s")', $noisy, $sanitized, $expected ) );
}

echo "\n======================================================\n";
echo "  TEST SUMMARY: {$pass_count}/{$test_count} assertions passed.\n";
echo "======================================================\n\n";

if ( $pass_count !== $test_count ) {
	exit( 1 );
}
