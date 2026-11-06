<?php
/**
 * Test Suite: Real Estate Vertical Architecture & Exact Sitemap URL Routing.
 */

define( "ABSPATH", __DIR__ . "/../" );
define( "SEO_CITY_PAGES_TESTING", true );
define( "SEO_CITY_PAGES_PLUGIN_DIR", dirname( __DIR__ ) . "/" );
define( "SEO_CITY_PAGES_PLUGIN_URL", "https://maroon-oyster-712637.hostingersite.com/wp-content/plugins/seo-city-pages/" );

if ( ! function_exists( "esc_html" ) ) {
	function esc_html( string $t ): string { return htmlspecialchars( $t, ENT_QUOTES, "UTF-8" ); }
}
if ( ! function_exists( "esc_attr" ) ) {
	function esc_attr( string $t ): string { return htmlspecialchars( $t, ENT_QUOTES, "UTF-8" ); }
}
if ( ! function_exists( "esc_url" ) ) {
	function esc_url( string $u ): string { return filter_var( $u, FILTER_SANITIZE_URL ) ?: ""; }
}
if ( ! function_exists( "wp_create_nonce" ) ) {
	function wp_create_nonce(): string { return "testnonce123"; }
}
if ( ! function_exists( "__" ) ) {
	function __( string $t, string $d = "" ): string { return $t; }
}
if ( ! function_exists( "esc_html__" ) ) {
	function esc_html__( string $t, string $d = "" ): string { return htmlspecialchars( $t, ENT_QUOTES, "UTF-8" ); }
}
if ( ! function_exists( "admin_url" ) ) {
	function admin_url( string $p = "" ): string { return "https://example.com/wp-admin/" . $p; }
}
if ( ! function_exists( "sanitize_text_field" ) ) {
	function sanitize_text_field( string $str ): string { return strip_tags( trim( $str ) ); }
}
if ( ! function_exists( "sanitize_file_name" ) ) {
	function sanitize_file_name( string $str ): string { return preg_replace( '/[^a-zA-Z0-9_\.-]/', '', $str ); }
}
if ( ! function_exists( "sanitize_textarea_field" ) ) {
	function sanitize_textarea_field( string $str ): string { return strip_tags( trim( $str ) ); }
}
if ( ! function_exists( "wp_strip_all_tags" ) ) {
	function wp_strip_all_tags( string $str ): string { return strip_tags( trim( $str ) ); }
}
if ( ! function_exists( "sanitize_title" ) ) {
	function sanitize_title( string $title ): string {
		$title = strtolower( trim( $title ) );
		$title = preg_replace( '/[^a-z0-9\-_\s]/', '', $title );
		return preg_replace( '/[\s_]+/', '-', $title );
	}
}
if ( ! function_exists( "wp_parse_args" ) ) {
	function wp_parse_args( $args, $defaults = [] ): array {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}
		return array_merge( $defaults, (array) $args );
	}
}
if ( ! function_exists( "current_time" ) ) {
	function current_time( string $type, bool $gmt = false ): string {
		return gmdate( 'Y-m-d H:i:s' );
	}
}
if ( ! class_exists( "WP_Error" ) ) {
	class WP_Error {
		private $code;
		private $message;
		public function __construct( string $code = "", string $message = "" ) {
			$this->code = $code;
			$this->message = $message;
		}
		public function get_error_code(): string { return $this->code; }
		public function get_error_message(): string { return $this->message; }
	}
}
if ( ! function_exists( "is_wp_error" ) ) {
	function is_wp_error( $thing ): bool { return $thing instanceof WP_Error; }
}

if ( ! defined( "OBJECT" ) ) define( "OBJECT", "OBJECT" );
class MockWPDB {
	public string $prefix = "wp_";
	public int $insert_id = 1;
	public array $queries = [];
	public array $tables = [];
	public function get_results( $sql, $output = OBJECT ) { return []; }
	public function get_row( $sql, $output = OBJECT ) { return null; }
	public function prepare( $query, ...$args ) { return vsprintf( str_replace( [ "%s", "%d", "%f" ], [ "%s", "%d", "%f" ], $query ), $args ); }
	public function insert( $table, $data, $format = null ) { return 1; }
}
$GLOBALS["wpdb"] = new MockWPDB();

require_once __DIR__ . "/mock-wpdb-prepare.php";
require_once dirname( __DIR__ ) . "/includes/Security/class-capabilities.php";
require_once dirname( __DIR__ ) . "/includes/Security/class-security.php";
require_once dirname( __DIR__ ) . "/includes/Logging/class-logger.php";
require_once dirname( __DIR__ ) . "/includes/Database/class-schema.php";
require_once dirname( __DIR__ ) . "/includes/Database/class-country-repository.php";
require_once dirname( __DIR__ ) . "/includes/Database/class-state-repository.php";
require_once dirname( __DIR__ ) . "/includes/Database/class-city-repository.php";
require_once dirname( __DIR__ ) . "/includes/Database/class-service-repository.php";
require_once dirname( __DIR__ ) . "/includes/Database/class-city-page-repository.php";
require_once dirname( __DIR__ ) . "/includes/Database/class-content-version-repository.php";
require_once dirname( __DIR__ ) . "/includes/URL/class-url-engine.php";
require_once dirname( __DIR__ ) . "/includes/URL/class-request-resolver.php";
require_once dirname( __DIR__ ) . "/includes/Elementor/class-page-context.php";
require_once dirname( __DIR__ ) . "/includes/Elementor/class-content-context.php";
require_once dirname( __DIR__ ) . "/includes/Elementor/class-elementor-integration.php";
require_once dirname( __DIR__ ) . "/includes/Import/class-csv-validator.php";
require_once dirname( __DIR__ ) . "/includes/Import/class-csv-normalizer.php";
require_once dirname( __DIR__ ) . "/includes/Import/class-csv-processor.php";

use SeoCityPages\Elementor\Page_Context;
use SeoCityPages\Elementor\Content_Context;
use SeoCityPages\Import\CSV_Validator;
use SeoCityPages\Import\CSV_Processor;

echo "\n======================================================\n";
echo "  TEST SUITE: Real Estate Vertical & Exact Sitemap URLs\n";
echo "======================================================\n\n";

$passed = 0;
$failed = 0;

function assert_test( $condition, $name ) {
    global $passed, $failed;
    if ( $condition ) {
        echo "  [PASS] " . $name . "\n";
        $passed++;
    } else {
        echo "  [FAIL] " . $name . "\n";
        $failed++;
    }
}

// 1. Real Estate Regex Detection
echo "1. Testing Real Estate Service Detection...\n";
$re_services = [
    "Real Estate Advertising Agency",
    "Real Estate SEO Agency",
    "Real Estate Lead Generation",
    "Real Estate Digital Marketing",
    "Real Estate Growth Partner",
    "Commercial Real Estate Broker",
    "Luxury Realtor Marketing",
    "Housing Market Realty Solutions"
];

$re_pattern = "/\\b(real\\s*estate|realtor|brokerage|housing|realty)\\b/i";

foreach ( $re_services as $s ) {
    assert_test( (bool) preg_match( $re_pattern, $s ), "Matched Real Estate: " . $s );
}

$non_re_services = [
    "Home Services",
    "Plumbing Contractor",
    "Dental Clinic",
    "Criminal Defense Law Firm"
];
foreach ( $non_re_services as $s ) {
    assert_test( ! preg_match( $re_pattern, $s ), "Excluded Non-Real-Estate: " . $s );
}

// 2. Real Estate Master Template Output
echo "\n2. Testing Real Estate Master Template Output...\n";
$page_mock = (object) [
    "id" => 101,
    "slug" => "real-estate-advertising-agency-in-bexar-texas",
    "url_path" => "/us/real-estate-advertising-agency-in-bexar-texas/",
    "title_override" => "Real Estate Advertising Agency in Bexar, Texas"
];
$country_mock = (object) [ "name" => "United States", "code" => "US" ];
$state_mock   = (object) [ "name" => "Texas", "code" => "TX" ];
$city_mock    = (object) [ "name" => "Bexar County", "slug" => "bexar" ];
$service_mock = (object) [ "name" => "Real Estate Advertising Agency", "slug" => "real-estate-advertising-agency" ];

Page_Context::set_context( $page_mock, $country_mock, $state_mock, $city_mock, $service_mock );

$html = Content_Context::render_real_estate_full_page_html();

assert_test( ! empty( $html ), "Real Estate master template renders non-empty HTML" );
assert_test( false !== strpos( $html, "vispan-real-estate-page" ), "Contains container class .vispan-real-estate-page" );
assert_test( false !== strpos( $html, "Lenis" ), "Includes Lenis smooth scroll engine script" );
assert_test( false !== strpos( $html, "Bexar" ), "Interpolates county name: Bexar" );
assert_test( false !== strpos( $html, "Texas" ), "Interpolates state name: Texas" );
assert_test( false !== strpos( $html, "Residential Homes & Condos" ), "Contains Residential sector pillar" );
assert_test( false !== strpos( $html, "Luxury Waterfront & Custom Homes" ), "Contains Luxury Estates sector pillar" );
assert_test( false !== strpos( $html, "Commercial Real Estate & Leases" ), "Contains Commercial sector pillar" );
assert_test( false !== strpos( $html, "Multi-Family & 1031 Exchange Investors" ), "Contains Multi-Family sector pillar" );
assert_test( false !== strpos( $html, "Land, Acreage & Master Subdivisions" ), "Contains Land & Acreage sector pillar" );
assert_test( false !== strpos( $html, "Brokerage Expansion & Agent Recruiting" ), "Contains Brokerage Teams sector pillar" );
assert_test( false !== strpos( $html, "vispan-ajax-lead-form" ), "Contains dual AJAX lead intake forms" );
assert_test( false !== strpos( $html, "vispan_marketing_inquiry" ), "Forms use vispan_marketing_inquiry AJAX action" );
assert_test( false !== strpos( $html, "Zillow / Realtor.com" ), "Contains comparison table with portals" );

// Zero Home Services Trade Leakage
assert_test( false === strpos( $html, "HVAC & Air Conditioning" ), "Zero leakage of HVAC trade" );
assert_test( false === strpos( $html, "Emergency Plumbing" ), "Zero leakage of Plumbing trade" );
assert_test( false === strpos( $html, "Roof Repair & Replacement" ), "Zero leakage of Roofing trade" );

// 3. Demo CSV Validation
echo "\n3. Testing Demo CSV Dataset (20 Pages)...\n";
$demo_csv_file = dirname( __DIR__ ) . "/data/real-estate-demo-20.csv";
assert_test( file_exists( $demo_csv_file ), "real-estate-demo-20.csv file exists" );

$validation = CSV_Validator::validate_file( $demo_csv_file );
assert_test( 20 === count( $validation["valid_rows"] ), "CSV contains exactly 20 valid rows (found: " . count( $validation["valid_rows"] ) . ")" );
assert_test( 0 === count( $validation["invalid_rows"] ), "CSV has 0 invalid rows" );

$first_row = $validation["valid_rows"][0];
assert_test( ! empty( $first_row["url_path"] ), "First row has url_path defined: " . ( $first_row["url_path"] ?? "" ) );
assert_test( ! empty( $first_row["custom_slug"] ), "First row has custom_slug defined: " . ( $first_row["custom_slug"] ?? "" ) );
assert_test( "published" === $first_row["status"], "First row status is published" );

// 4. Full 3,117 CSV Validation
echo "\n4. Testing Full 3,117 CSV Dataset...\n";
$all_csv_file = dirname( __DIR__ ) . "/data/real-estate-all-3117.csv";
assert_test( file_exists( $all_csv_file ), "real-estate-all-3117.csv file exists" );
$fp = fopen( $all_csv_file, "r" );
$line_count = 0;
while ( fgets( $fp ) !== false ) {
    $line_count++;
}
fclose( $fp );
assert_test( 3118 === $line_count, "All CSV has 3,118 lines (1 header + 3,117 records, found: " . $line_count . ")" );

echo "\n======================================================\n";
echo "  TEST SUMMARY: {$passed} passed, {$failed} failed.\n";
echo "======================================================\n\n";

if ( $failed > 0 ) {
    exit(1);
}
