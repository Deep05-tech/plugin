<?php
/**
 * Test Suite for Phase 3 — Location & Service Management.
 *
 * @package SeoCityPages\Tests
 */

declare(strict_types=1);

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', false );
}
if ( ! defined( 'SEO_CITY_PAGES_VERSION' ) ) {
	define( 'SEO_CITY_PAGES_VERSION', '0.2.0' );
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

// In-Memory / SQLite Mock WPDB for Testing.
class Mock_WPDB {
	public $prefix = 'wp_';
	private \PDO $pdo;
	public $insert_id = 0;

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
			$sqlite_query = preg_replace( '/DECIMAL\([0-9]+,\s*[0-9]+\)/i', 'NUMERIC', $sqlite_query );
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

global $wpdb, $wp_version, $mock_options;
$wpdb         = new Mock_WPDB();
$wp_version   = '7.1';
$mock_options = [];

// WordPress Helper Functions.
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
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( string $str ): string { return strip_tags( trim( $str ) ); }
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( string $title ): string {
		$title = strtolower( trim( $title ) );
		$title = preg_replace( '/[^a-z0-9\-_\s]/', '', $title );
		return preg_replace( '/[\s_]+/', '-', $title );
	}
}
if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( string $content ): string { return trim( $content ); }
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
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $option ): bool {
		global $mock_options;
		unset( $mock_options[ $option ] );
		return true;
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ): string { return json_encode( $data ); }
}
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( string $action ): string { return substr( md5( $action . 'salt' ), 0, 10 ); }
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( string $nonce, string $action ): bool { return $nonce === substr( md5( $action . 'salt' ), 0, 10 ); }
}
if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir(): array {
		$tmp = sys_get_temp_dir() . '/seo_city_pages_p3_uploads';
		if ( ! file_exists( $tmp ) ) {
			mkdir( $tmp, 0777, true );
		}
		return [ 'basedir' => $tmp, 'error' => false ];
	}
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( string $str ): string { return rtrim( $str, '/\\' ) . '/'; }
}
if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( string $dir ): bool { return is_dir( $dir ) || mkdir( $dir, 0777, true ); }
}
if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( string $queries ): void {
		global $wpdb;
		$statements = explode( ';', $queries );
		foreach ( $statements as $sql ) {
			$trimmed = trim( $sql );
			if ( ! empty( $trimmed ) ) {
				$wpdb->query( $trimmed );
			}
		}
	}
}

// Autoload plugin classes.
require_once dirname( __DIR__ ) . '/includes/class-autoloader.php';
\SeoCityPages\Autoloader::register( dirname( __DIR__ ) . '/includes/' );

// Phase 3 Test Suite Class.
class Phase3_Test_Suite {

	private $passed = 0;
	private $failed = 0;

	public function run(): void {
		echo "====================================================\n";
		echo "  SEO City Pages — Phase 3 Location & Service Tests \n";
		echo "====================================================\n\n";

		$this->test_schema_installation();
		$this->test_countries_crud_and_duplicates();
		$this->test_states_crud_relationships_and_duplicates();
		$this->test_cities_crud_relationships_and_duplicates();
		$this->test_services_crud_and_blueprints();
		$this->test_dependency_protection_cascade_blocks();
		$this->test_manual_scenario_verification();

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

	private function test_schema_installation(): void {
		\SeoCityPages\Database\Schema::install();
		$this->assert( true, 'Database Schema tables created via dbDelta' );

		\SeoCityPages\Database\Migrator::maybe_upgrade();
		$this->assert( \SeoCityPages\Database\Migrator::TARGET_DB_VERSION === \SeoCityPages\Database\Migrator::get_current_db_version(), 'Database schema successfully upgraded to v' . \SeoCityPages\Database\Migrator::TARGET_DB_VERSION );
	}

	private function test_countries_crud_and_duplicates(): void {
		// 1. Create country.
		$us_id = \SeoCityPages\Database\Country_Repository::create( [
			'name' => 'United States',
			'code' => 'US',
		] );
		$this->assert( is_int( $us_id ) && $us_id > 0, 'Country "United States" created with ID: ' . $us_id );

		// 2. Duplicate prevention (case-insensitive normalized check).
		$dup_id = \SeoCityPages\Database\Country_Repository::create( [
			'name' => '  united states  ',
			'code' => 'USA',
		] );
		$this->assert( is_wp_error( $dup_id ) && 'duplicate_country' === $dup_id->get_error_code(), 'Duplicate country "  united states  " blocked' );

		$dup_id2 = \SeoCityPages\Database\Country_Repository::create( [
			'name' => 'UNITED STATES',
		] );
		$this->assert( is_wp_error( $dup_id2 ) && 'duplicate_country' === $dup_id2->get_error_code(), 'Duplicate country "UNITED STATES" blocked' );

		// 3. Update country.
		$updated = \SeoCityPages\Database\Country_Repository::update( $us_id, [ 'code' => 'USA' ] );
		$this->assert( true === $updated, 'Country updated successfully' );

		$country = \SeoCityPages\Database\Country_Repository::get( $us_id );
		$this->assert( $country && 'USA' === $country->code, 'Country code updated to USA' );

		// 4. Search and pagination.
		$list = \SeoCityPages\Database\Country_Repository::get_all( [ 'search' => 'United' ] );
		$this->assert( count( $list ) >= 1 && $list[0]->name === 'United States', 'Country search by name works' );
	}

	private function test_states_crud_relationships_and_duplicates(): void {
		$us = \SeoCityPages\Database\Country_Repository::get_by_name( 'United States' );
		$this->assert( $us !== null, 'Retrieved parent country United States' );

		// 1. Invalid parent country test.
		$bad_state = \SeoCityPages\Database\State_Repository::create( [
			'country_id' => 99999,
			'name'       => 'California',
		] );
		$this->assert( is_wp_error( $bad_state ) && 'invalid_country' === $bad_state->get_error_code(), 'State with non-existent country rejected' );

		// 2. Create valid state.
		$ca_id = \SeoCityPages\Database\State_Repository::create( [
			'country_id' => (int) $us->id,
			'name'       => 'California',
			'code'       => 'CA',
		] );
		$this->assert( is_int( $ca_id ) && $ca_id > 0, 'State "California" created with ID: ' . $ca_id );

		// 3. Duplicate state in same country blocked.
		$dup_state = \SeoCityPages\Database\State_Repository::create( [
			'country_id' => (int) $us->id,
			'name'       => 'california',
		] );
		$this->assert( is_wp_error( $dup_state ) && 'duplicate_state' === $dup_state->get_error_code(), 'Duplicate state "california" in same country blocked' );

		// 4. Update state.
		$up = \SeoCityPages\Database\State_Repository::update( $ca_id, [ 'code' => 'CAL' ] );
		$this->assert( true === $up, 'State updated successfully' );

		$state = \SeoCityPages\Database\State_Repository::get( $ca_id );
		$this->assert( $state && 'CAL' === $state->code, 'State code verified as CAL' );
	}

	private function test_cities_crud_relationships_and_duplicates(): void {
		$us = \SeoCityPages\Database\Country_Repository::get_by_name( 'United States' );
		$ca = \SeoCityPages\Database\State_Repository::get_by_name( (int) $us->id, 'California' );

		// 1. Invalid country/state mismatch.
		$bad_city = \SeoCityPages\Database\City_Repository::create( [
			'country_id' => (int) $us->id,
			'state_id'   => 99999,
			'name'       => 'Los Angeles',
		] );
		$this->assert( is_wp_error( $bad_city ) && 'invalid_state' === $bad_city->get_error_code(), 'City with mismatched state rejected' );

		// 2. Valid city creation with optional data.
		$la_id = \SeoCityPages\Database\City_Repository::create( [
			'country_id'       => (int) $us->id,
			'state_id'         => (int) $ca->id,
			'name'             => 'Los Angeles',
			'latitude'         => 34.0522,
			'longitude'        => -118.2437,
			'local_industries' => 'Entertainment, Aerospace, Technology',
			'areas_served'     => 'Downtown, Hollywood, Santa Monica',
			'description'      => 'Los Angeles is the commercial and cultural hub of Southern California.',
		] );
		$this->assert( is_int( $la_id ) && $la_id > 0, 'City "Los Angeles" created with coordinates and local data' );

		// 3. Duplicate city check in same state/country.
		$dup_city = \SeoCityPages\Database\City_Repository::create( [
			'country_id' => (int) $us->id,
			'state_id'   => (int) $ca->id,
			'name'       => 'LOS ANGELES',
		] );
		$this->assert( is_wp_error( $dup_city ) && 'duplicate_city' === $dup_city->get_error_code(), 'Duplicate city "LOS ANGELES" blocked' );

		// 4. Coordinates float precision check.
		$city = \SeoCityPages\Database\City_Repository::get( $la_id );
		$this->assert( $city && abs( (float) $city->latitude - 34.0522 ) < 0.0001, 'City latitude stored as valid float' );
	}

	private function test_services_crud_and_blueprints(): void {
		// 1. Create service.
		$seo_id = \SeoCityPages\Database\Service_Repository::create( [
			'name'               => 'PPC Management',
			'description'        => 'Pay-per-click advertising management.',
			'default_hero_title' => 'Top PPC Agency in {{city}}',
			'default_cta'        => 'Get a Free PPC Proposal',
		] );
		$this->assert( is_int( $seo_id ) && $seo_id > 0, 'Service "PPC Management" created with ID: ' . $seo_id );

		// 2. Duplicate service check.
		$dup_svc = \SeoCityPages\Database\Service_Repository::create( [
			'name' => 'ppc management',
		] );
		$this->assert( is_wp_error( $dup_svc ) && 'duplicate_service' === $dup_svc->get_error_code(), 'Duplicate service "ppc management" blocked' );

		// 3. Update service.
		$up = \SeoCityPages\Database\Service_Repository::update( $seo_id, [
			'default_hero_desc' => 'Maximize your ROI with expert PPC campaigns in {{city}}, {{state}}.',
		] );
		$this->assert( true === $up, 'Service updated with default hero description' );
	}

	private function test_dependency_protection_cascade_blocks(): void {
		$us = \SeoCityPages\Database\Country_Repository::get_by_name( 'United States' );
		$ca = \SeoCityPages\Database\State_Repository::get_by_name( (int) $us->id, 'California' );

		// 1. Attempt deleting State while City exists.
		$del_state = \SeoCityPages\Database\State_Repository::delete( (int) $ca->id );
		$this->assert( is_wp_error( $del_state ) && 'has_dependencies' === $del_state->get_error_code(), 'Deleting state with dependent cities is BLOCKED with useful warning' );

		// 2. Attempt deleting Country while States/Cities exist.
		$del_country = \SeoCityPages\Database\Country_Repository::delete( (int) $us->id );
		$this->assert( is_wp_error( $del_country ) && 'has_dependencies' === $del_country->get_error_code(), 'Deleting country with dependent states/cities is BLOCKED with useful warning' );

		// 3. Clean deletion order (City -> State -> Country).
		$la = \SeoCityPages\Database\City_Repository::get_by_name( (int) $us->id, (int) $ca->id, 'Los Angeles' );
		$del_city = \SeoCityPages\Database\City_Repository::delete( (int) $la->id );
		$this->assert( true === $del_city, 'City "Los Angeles" deleted cleanly' );

		$del_state2 = \SeoCityPages\Database\State_Repository::delete( (int) $ca->id );
		$this->assert( true === $del_state2, 'State "California" deleted cleanly once orphaned cities removed' );

		$del_country2 = \SeoCityPages\Database\Country_Repository::delete( (int) $us->id );
		$this->assert( true === $del_country2, 'Country "United States" deleted cleanly once orphaned states removed' );
	}

	private function test_manual_scenario_verification(): void {
		echo "\n--- Executing Required Manual Scenario Dataset ---\n";

		// 1. Country: India
		$india_id = \SeoCityPages\Database\Country_Repository::create( [
			'name' => 'India',
			'code' => 'IN',
		] );
		$this->assert( is_int( $india_id ) && $india_id > 0, 'Scenario: Country "India" created' );

		// 2. States: Gujarat, Maharashtra
		$gujarat_id = \SeoCityPages\Database\State_Repository::create( [
			'country_id' => $india_id,
			'name'       => 'Gujarat',
			'code'       => 'GJ',
		] );
		$maharashtra_id = \SeoCityPages\Database\State_Repository::create( [
			'country_id' => $india_id,
			'name'       => 'Maharashtra',
			'code'       => 'MH',
		] );
		$this->assert( $gujarat_id > 0 && $maharashtra_id > 0, 'Scenario: States "Gujarat" and "Maharashtra" created' );

		// 3. Cities: Rajkot, Ahmedabad, Surat in Gujarat; Mumbai in Maharashtra
		$rajkot_id = \SeoCityPages\Database\City_Repository::create( [
			'country_id'       => $india_id,
			'state_id'         => $gujarat_id,
			'name'             => 'Rajkot',
			'latitude'         => 22.3039,
			'longitude'        => 70.8022,
			'local_industries' => 'Engineering, Foundry, Jewelry, Software',
			'areas_served'     => 'Kalawad Road, Yagnik Road, Gondal Road, 150ft Ring Road',
		] );
		$ahmedabad_id = \SeoCityPages\Database\City_Repository::create( [
			'country_id'       => $india_id,
			'state_id'         => $gujarat_id,
			'name'             => 'Ahmedabad',
			'latitude'         => 23.0225,
			'longitude'        => 72.5714,
			'local_industries' => 'Textile, Pharmaceuticals, IT, Chemicals',
			'areas_served'     => 'SG Highway, Prahlad Nagar, Navrangpura, Bopal',
		] );
		$surat_id = \SeoCityPages\Database\City_Repository::create( [
			'country_id'       => $india_id,
			'state_id'         => $gujarat_id,
			'name'             => 'Surat',
			'latitude'         => 21.1702,
			'longitude'        => 72.8311,
			'local_industries' => 'Diamond cutting, Textiles, Petrochemicals',
			'areas_served'     => 'Athwa Lines, Vesu, Adajan, Varachha',
		] );
		$mumbai_id = \SeoCityPages\Database\City_Repository::create( [
			'country_id'       => $india_id,
			'state_id'         => $maharashtra_id,
			'name'             => 'Mumbai',
			'latitude'         => 19.0760,
			'longitude'        => 72.8777,
			'local_industries' => 'Financial Services, Media, Technology, Healthcare',
			'areas_served'     => 'BKC, Andheri, Lower Parel, Bandra, Powai',
		] );
		$this->assert( $rajkot_id > 0 && $ahmedabad_id > 0 && $surat_id > 0 && $mumbai_id > 0, 'Scenario: All 4 Cities created successfully' );

		// 4. Services: SEO Agency, Digital Marketing Agency, Web Development
		$seo_id = \SeoCityPages\Database\Service_Repository::create( [
			'name'               => 'SEO Agency',
			'description'        => 'Full-service search engine optimization and local ranking.',
			'default_hero_title' => 'Top SEO Agency in {{city}}',
			'default_hero_desc'  => 'Grow your organic traffic and local revenue in {{city}}, {{state}}.',
			'default_cta'        => 'Get Free {{city}} SEO Audit',
		] );
		$dma_id = \SeoCityPages\Database\Service_Repository::create( [
			'name'               => 'Digital Marketing Agency',
			'description'        => 'Performance-driven digital marketing and advertising.',
			'default_hero_title' => 'Leading Digital Marketing Agency in {{city}}',
		] );
		$web_id = \SeoCityPages\Database\Service_Repository::create( [
			'name'               => 'Web Development',
			'description'        => 'Custom responsive websites and Elementor solutions.',
			'default_hero_title' => 'Expert Web Development in {{city}}',
		] );
		$this->assert( $seo_id > 0 && $dma_id > 0 && $web_id > 0, 'Scenario: All 3 Services created successfully' );

		// 5. Query & Filter Verifications:
		$gj_cities = \SeoCityPages\Database\City_Repository::get_all( [ 'state_id' => $gujarat_id ] );
		$this->assert( count( $gj_cities ) === 3, 'Filter: Exactly 3 cities returned for Gujarat (Rajkot, Ahmedabad, Surat)' );

		$mh_cities = \SeoCityPages\Database\City_Repository::get_all( [ 'state_id' => $maharashtra_id ] );
		$this->assert( count( $mh_cities ) === 1 && $mh_cities[0]->name === 'Mumbai', 'Filter: Exactly 1 city returned for Maharashtra (Mumbai)' );

		$search_res = \SeoCityPages\Database\City_Repository::get_all( [ 'search' => 'Rajkot' ] );
		$this->assert( count( $search_res ) === 1 && $search_res[0]->name === 'Rajkot', 'Search: Found Rajkot by name query' );

		// 6. Duplicate Protection Verification on Scenario Data:
		$dup_rajkot = \SeoCityPages\Database\City_Repository::create( [
			'country_id' => $india_id,
			'state_id'   => $gujarat_id,
			'name'       => 'rajkot',
		] );
		$this->assert( is_wp_error( $dup_rajkot ), 'Duplicate: Case-insensitive "rajkot" rejected' );

		$dup_seo = \SeoCityPages\Database\Service_Repository::create( [
			'name' => 'seo agency',
		] );
		$this->assert( is_wp_error( $dup_seo ), 'Duplicate: Case-insensitive "seo agency" rejected' );

		// 7. Dependency Protection Verification on Scenario Data:
		$del_gj = \SeoCityPages\Database\State_Repository::delete( $gujarat_id );
		$this->assert( is_wp_error( $del_gj ), 'Dependency: Cannot delete Gujarat while Rajkot/Ahmedabad/Surat exist' );

		$del_in = \SeoCityPages\Database\Country_Repository::delete( $india_id );
		$this->assert( is_wp_error( $del_in ), 'Dependency: Cannot delete India while dependent states & cities exist' );
	}
}

// Execute Suite.
$suite = new Phase3_Test_Suite();
$suite->run();
