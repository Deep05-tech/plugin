<?php
/**
 * Automated Test Suite for Topic-Based County Batch Generator & Campaign Manager.
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
			$row  = $stmt->fetch( \PDO::FETCH_ASSOC );
			if ( ! $row ) {
				return null;
			}
			return $output === ARRAY_A ? $row : (object) $row;
		} catch ( \PDOException $e ) {
			return null;
		}
	}

	public function get_results( string $query, $output = OBJECT ) {
		try {
			$stmt = $this->pdo->query( $query );
			$rows = $stmt->fetchAll( \PDO::FETCH_ASSOC );
			if ( $output === ARRAY_A ) {
				return $rows;
			}
			return array_map( function( $r ) { return (object) $r; }, $rows );
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

	public function get_col( string $query ) {
		try {
			$stmt = $this->pdo->query( $query );
			return $stmt->fetchAll( \PDO::FETCH_COLUMN );
		} catch ( \PDOException $e ) {
			return [];
		}
	}

	public function insert( string $table, array $data, $format = null ) {
		$keys = array_keys( $data );
		$fields = implode( ', ', $keys );
		$placeholders = implode( ', ', array_fill( 0, count( $keys ), '?' ) );
		$sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
		try {
			$stmt = $this->pdo->prepare( $sql );
			$stmt->execute( array_values( $data ) );
			$this->insert_id = (int) $this->pdo->lastInsertId();
			return 1;
		} catch ( \PDOException $e ) {
			return false;
		}
	}

	public function update( string $table, array $data, array $where, $format = null, $where_format = null ) {
		$set_clauses = [];
		$values = [];
		foreach ( $data as $k => $v ) {
			$set_clauses[] = "{$k} = ?";
			$values[] = $v;
		}
		$where_clauses = [];
		foreach ( $where as $k => $v ) {
			$where_clauses[] = "{$k} = ?";
			$values[] = $v;
		}
		$sql = "UPDATE {$table} SET " . implode( ', ', $set_clauses ) . " WHERE " . implode( ' AND ', $where_clauses );
		try {
			$stmt = $this->pdo->prepare( $sql );
			$stmt->execute( $values );
			return $stmt->rowCount();
		} catch ( \PDOException $e ) {
			return false;
		}
	}

	public function delete( string $table, array $where, $where_format = null ) {
		$where_clauses = [];
		$values = [];
		foreach ( $where as $k => $v ) {
			$where_clauses[] = "{$k} = ?";
			$values[] = $v;
		}
		$sql = "DELETE FROM {$table} WHERE " . implode( ' AND ', $where_clauses );
		try {
			$stmt = $this->pdo->prepare( $sql );
			$stmt->execute( $values );
			return $stmt->rowCount();
		} catch ( \PDOException $e ) {
			return false;
		}
	}
}

global $wpdb;
$wpdb = new Mock_WPDB();

// Mock Options Store.
$GLOBALS['mock_options'] = [];

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

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool { return $thing instanceof WP_Error; }
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return $GLOBALS['mock_options'][ $name ] ?? $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value ) {
		$GLOBALS['mock_options'][ $name ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $name ) {
		unset( $GLOBALS['mock_options'][ $name ] );
		return true;
	}
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = false ) {
		return gmdate( 'Y-m-d H:i:s' );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $str ) {
		return strtolower( trim( preg_replace( '/[^a-zA-Z0-9]+/', '-', (string) $str ), '-' ) );
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/i', '', (string) $key );
	}
}
if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		return substr( md5( uniqid( (string) mt_rand(), true ) ), 0, $length );
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1;
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		return true;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return $url;
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://example.local/wp-admin/' . $path;
	}
}
if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number ) {
		return number_format( (float) $number );
	}
}
if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, $echo = true ) {
		$res = ( (string) $selected === (string) $current ) ? 'selected="selected"' : '';
		if ( $echo ) echo $res;
		return $res;
	}
}
if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $content ) {
		return trim( (string) $content );
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
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return $text;
	}
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ): int {
		return abs( (int) $maybeint );
	}
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
		$tmp = sys_get_temp_dir() . '/mock_wp_uploads_county_' . uniqid();
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
	}
}

// Autoload plugin classes.
require_once __DIR__ . '/../includes/class-autoloader.php';
\SeoCityPages\Autoloader::register( __DIR__ . '/../includes/' );

// Ensure schema tables created.
\SeoCityPages\Database\Schema::install();

// Test Runner.
$test_count = 0;
$pass_count = 0;

function assert_test( $condition, $message ) {
	global $test_count, $pass_count;
	$test_count++;
	if ( $condition ) {
		$pass_count++;
		echo "  [PASS] {$message}\n";
	} else {
		echo "  [FAIL] {$message}\n";
	}
}

echo "\n======================================================\n";
echo "  TEST SUITE: Topic-Based County Batch Generator\n";
echo "======================================================\n\n";

// 1. Test County Dataset.
echo "1. Testing US Counties Dataset...\n";
$states = \SeoCityPages\Data\County_Dataset::get_states();
assert_test( count( $states ) === 51, 'All 50 US States + DC loaded (Total: ' . count( $states ) . ')' );

$all_counties_count = \SeoCityPages\Data\County_Dataset::count_counties();
assert_test( $all_counties_count >= 3100, 'All US Counties loaded (Total: ' . $all_counties_count . ')' );

$ca_counties = \SeoCityPages\Data\County_Dataset::get_counties( 'CA' );
assert_test( count( $ca_counties ) === 58, 'California has 58 counties (Got: ' . count( $ca_counties ) . ')' );
assert_test( $ca_counties[0]['state_name'] === 'California', 'County data includes state name "California"' );
assert_test( ! empty( $ca_counties[0]['county_slug'] ), 'County slug populated (' . $ca_counties[0]['county_slug'] . ')' );

$fl_tx_counties = \SeoCityPages\Data\County_Dataset::get_counties( [ 'FL', 'TX' ] );
assert_test( count( $fl_tx_counties ) === ( 67 + 254 ), 'Combined Florida (67) & Texas (254) count equals 321' );

// 2. Test Migrator Versioning.
echo "\n2. Testing Schema Versioning...\n";
assert_test( \SeoCityPages\Database\Migrator::TARGET_DB_VERSION === '0.11.0', 'Migrator target version updated to v0.11.0' );
\SeoCityPages\Database\Migrator::maybe_upgrade();
assert_test( get_option( \SeoCityPages\Database\Migrator::DB_VERSION_OPTION ) === '0.11.0', 'Database successfully migrated to v0.11.0' );

// 3. Test Campaign & 100-Batch Creation.
echo "\n3. Testing County Campaign & 100-Batch Partitioning...\n";
$campaign_res = \SeoCityPages\Database\County_Campaign_Repository::create_campaign( [
	'topic'         => 'Dental Clinic',
	'target_states' => [ 'CA', 'FL' ], // 58 + 67 = 125 counties
	'batch_size'    => 100,
] );

assert_test( ! empty( $campaign_res['campaign_id'] ), 'Campaign created with ID: ' . ( $campaign_res['campaign_id'] ?? '' ) );
assert_test( $campaign_res['total_counties'] === 125, 'Total counties matches 125' );
assert_test( $campaign_res['total_batches'] === 2, 'Auto-divided into 2 batches (100 and 25)' );
assert_test( count( $campaign_res['batches'] ) === 2, '2 batch job items generated' );
assert_test( $campaign_res['batches'][0]['total_items'] === 100, 'Batch #1 has exactly 100 pages' );
assert_test( $campaign_res['batches'][1]['total_items'] === 25, 'Batch #2 has remaining 25 pages' );

// Verify DB persistence.
$camp_obj = \SeoCityPages\Database\County_Campaign_Repository::get_campaign( $campaign_res['campaign_id'] );
assert_test( $camp_obj !== null, 'Campaign persisted in seo_county_campaigns' );
assert_test( $camp_obj->topic === 'Dental Clinic', 'Campaign topic correctly stored' );

$batches = \SeoCityPages\Database\County_Campaign_Repository::get_campaign_batches( $campaign_res['campaign_id'] );
assert_test( count( $batches ) === 2, 'Retrieved 2 bulk batch jobs from database' );

// 4. Test Batch Processing Chunk.
echo "\n4. Testing Batch Runner Execution...\n";
$first_batch_job_id = $batches[0]->job_id;

// Process a chunk of 5 items.
$chunk_res = \SeoCityPages\Jobs\County_Batch_Runner::process_chunk( $first_batch_job_id, 5 );
assert_test( $chunk_res['success'] === true, 'Chunk executed successfully' );
assert_test( $chunk_res['processed_chunk'] === 5, 'Processed 5 county landing pages in chunk' );
assert_test( $chunk_res['completed_items'] === 5, 'Completed count updated to 5' );
assert_test( $chunk_res['pending_items'] === 95, 'Pending count updated to 95' );
assert_test( $chunk_res['percent'] === 5, 'Progress percentage updated to 5%' );

// Verify City Pages and Entities were created in DB.
$pages_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}seo_city_pages" );
assert_test( (int) $pages_count >= 5, 'At least 5 SEO City Pages generated in database' );

// 5. Test Campaign Stats Aggregation.
echo "\n5. Testing Campaign Stats Aggregation...\n";
\SeoCityPages\Database\County_Campaign_Repository::update_campaign_stats( $campaign_res['campaign_id'] );
$camp_updated = \SeoCityPages\Database\County_Campaign_Repository::get_campaign( $campaign_res['campaign_id'] );
assert_test( (int) $camp_updated->generated_pages === 5, 'Campaign aggregate generated_pages updated to 5' );
assert_test( in_array( $camp_updated->status, [ 'running', 'in_progress' ], true ), 'Campaign status updated to active/running (' . $camp_updated->status . ')' );

// 6. Test Bulk Publish Batch.
echo "\n6. Testing Batch Bulk Publishing...\n";
$publish_res = \SeoCityPages\Jobs\County_Batch_Runner::bulk_publish_batch( $first_batch_job_id );
assert_test( $publish_res['published'] >= 5, 'Published generated pages live (Count: ' . $publish_res['published'] . ')' );

// 7. Test Campaign Deletion.
echo "\n7. Testing Campaign Deletion...\n";
$del_res = \SeoCityPages\Database\County_Campaign_Repository::delete_campaign( $campaign_res['campaign_id'] );
assert_test( $del_res === true, 'Campaign deleted cleanly' );
$camp_after_del = \SeoCityPages\Database\County_Campaign_Repository::get_campaign( $campaign_res['campaign_id'] );
assert_test( $camp_after_del === null, 'Campaign record no longer exists' );
$batches_after_del = \SeoCityPages\Database\County_Campaign_Repository::get_campaign_batches( $campaign_res['campaign_id'] );
assert_test( count( $batches_after_del ) === 0, 'Associated bulk batches deleted cleanly' );

echo "\n======================================================\n";
echo "  TEST SUMMARY: {$pass_count}/{$test_count} assertions passed.\n";
echo "======================================================\n\n";

if ( $pass_count !== $test_count ) {
	exit( 1 );
}
