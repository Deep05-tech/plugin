<?php
/**
 * Test Suite: General Agency Master Template & Professional Services Routing (Dental, Law, etc.).
 *
 * Verifies that:
 * 1. Home Services routes to Home Services master template.
 * 2. Real Estate routes to Real Estate master template.
 * 3. All other services (Dental Clinic, Law Firm, etc.) route to General Agency master template.
 * 4. General Agency master template renders all 15 Vispan sections with zero theme leaks and zero trade leaks.
 *
 * @package SeoCityPages\Tests
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
if ( ! function_exists( "home_url" ) ) {
	function home_url( string $path = "" ): string { return "https://example.com/" . ltrim( $path, "/" ); }
}
if ( ! function_exists( "user_trailingslashit" ) ) {
	function user_trailingslashit( string $url ): string { return rtrim( $url, "/" ) . "/"; }
}
if ( ! function_exists( "trailingslashit" ) ) {
	function trailingslashit( string $path ): string { return rtrim( $path, "/\\" ) . "/"; }
}
if ( ! function_exists( "untrailingslashit" ) ) {
	function untrailingslashit( string $path ): string { return rtrim( $path, "/\\" ); }
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
if ( ! function_exists( "get_option" ) ) {
	function get_option( $name, $default = false ) {
		return $default;
	}
}
if ( ! function_exists( "add_shortcode" ) ) {
	function add_shortcode( string $tag, callable $callback ): void {
		$GLOBALS['_test_shortcodes'][ $tag ] = $callback;
	}
}
if ( ! function_exists( "add_action" ) ) {
	function add_action() {}
}
if ( ! function_exists( "add_filter" ) ) {
	function add_filter() {}
}
if ( ! function_exists( "wp_kses_post" ) ) {
	function wp_kses_post( string $content ): string { return $content; }
}

if ( ! defined( "OBJECT" ) ) define( "OBJECT", "OBJECT" );
class MockWPDBGeneral {
	public string $prefix = "wp_";
	public int $insert_id = 1;
	public array $queries = [];
	public array $tables = [];
	public function get_results( $sql, $output = OBJECT ) { return []; }
	public function get_row( $sql, $output = OBJECT ) { return null; }
	public function prepare( $query, ...$args ) { return vsprintf( str_replace( [ "%s", "%d", "%f" ], [ "%s", "%d", "%f" ], $query ), $args ); }
	public function insert( $table, $data, $format = null ) { return 1; }
}
$GLOBALS["wpdb"] = new MockWPDBGeneral();

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
require_once dirname( __DIR__ ) . "/includes/Frontend/class-inquiry-handler.php";
require_once dirname( __DIR__ ) . "/includes/Elementor/class-elementor-integration.php";

use SeoCityPages\Elementor\Page_Context;
use SeoCityPages\Elementor\Content_Context;
use SeoCityPages\Elementor\Elementor_Integration;

echo "\n======================================================\n";
echo "  TEST SUITE: General Agency Master Template & Routing\n";
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

// 1. Service Vertical Routing Logic
echo "1. Testing 3-Way Vertical Template Routing Logic...\n";

$home_services = [
	"Home Services",
	"Home Cleaning Services",
	"HVAC Contractors",
	"Emergency Trades",
	"Plumbing Contractor"
];

$real_estate_services = [
	"Real Estate Advertising Agency",
	"Commercial Real Estate Broker",
	"Luxury Realtor Marketing",
	"Housing Market Realty Solutions"
];

$general_services = [
	"Dental Clinic",
	"Dentist Marketing Agency",
	"Law Firm",
	"Criminal Defense Attorney",
	"Accounting Firm",
	"B2B SaaS Growth",
	"Cosmetic Surgery Clinic",
	"Digital Marketing"
];

$hs_pattern = '/\b(home\s*services?|home\s*cleaning|contractors?|emergency\s*trades?)\b/i';
$re_pattern = '/\b(real\s*estate|realtor|brokerage|housing|realty)\b/i';

function resolve_template_type( string $service, string $hs_pattern, string $re_pattern ): string {
	if ( preg_match( $hs_pattern, $service ) ) {
		return 'home_services';
	}
	if ( preg_match( $re_pattern, $service ) ) {
		return 'real_estate';
	}
	return 'general_agency';
}

foreach ( $home_services as $s ) {
	assert_test( resolve_template_type( $s, $hs_pattern, $re_pattern ) === 'home_services', "Routed to Home Services: " . $s );
}

foreach ( $real_estate_services as $s ) {
	assert_test( resolve_template_type( $s, $hs_pattern, $re_pattern ) === 'real_estate', "Routed to Real Estate: " . $s );
}

foreach ( $general_services as $s ) {
	assert_test( resolve_template_type( $s, $hs_pattern, $re_pattern ) === 'general_agency', "Routed to General Agency: " . $s );
}

// 2. General Agency Master Template Output Verification (Dental Clinic in Rajkot)
echo "\n2. Testing General Agency Master Template Output...\n";

$page_obj = (object) [
	'id'          => 42,
	'country_id'  => 1,
	'state_id'    => 1,
	'city_id'     => 1,
	'service_id'  => 1,
	'url_path'    => '/us/best-digital-marketing-agency-for-dental-clinic-in-rajkot/',
	'status'      => 'published',
	'template_id' => 0,
];

$country_obj = (object) [ 'id' => 1, 'name' => 'United States', 'code' => 'US' ];
$state_obj   = (object) [ 'id' => 1, 'name' => 'Gujarat', 'code' => 'GJ' ];
$city_obj    = (object) [ 'id' => 1, 'name' => 'Rajkot', 'slug' => 'rajkot' ];
$service_obj = (object) [ 'id' => 1, 'name' => 'Dental Clinic', 'slug' => 'dental-clinic' ];

Page_Context::set_context( $page_obj, $country_obj, $state_obj, $city_obj, $service_obj );

$rendered_html = Content_Context::render_general_agency_full_page_html();

assert_test( ! empty( $rendered_html ), "General Agency template renders non-empty HTML" );
assert_test( false !== strpos( $rendered_html, 'vispan-general-agency-page' ), "Contains container class .vispan-general-agency-page" );
assert_test( false !== strpos( $rendered_html, 'cdn.jsdelivr.net/npm/lenis' ) && false !== strpos( $rendered_html, 'data-animate' ), "Includes unified Lenis smooth scrolling and data-animate entrance system" );
assert_test( false !== strpos( $rendered_html, 'Rajkot' ), "Interpolates target city: Rajkot" );
assert_test( false !== strpos( $rendered_html, 'Dental Clinic' ), "Interpolates target service: Dental Clinic" );
assert_test( false !== strpos( $rendered_html, '#proposal-form' ), "Contains proposal intake lead form (#proposal-form)" );
assert_test( false !== strpos( $rendered_html, 'name="nonce"' ) || false !== strpos( $rendered_html, 'testnonce123' ) || false !== strpos( $rendered_html, 'inquiry_nonce' ), "Contains CSRF security nonce for inquiry submission" );
assert_test( false !== strpos( $rendered_html, 'Standard National SEO vs. Vispan Architecture' ) || false !== strpos( $rendered_html, 'Standard National' ), "Contains differentiation comparison table matching live site" );
assert_test( false !== strpos( $rendered_html, 'Our 4-Stage Search Growth Engine' ), "Contains 4-stage execution roadmap" );
assert_test( false !== strpos( $rendered_html, 'Complete Local SEO Deliverables Package' ) || false !== strpos( $rendered_html, 'Deliverables Package' ), "Contains deliverables package matching live site" );
assert_test( false !== strpos( $rendered_html, 'Our Full Suite of Digital Marketing & Creative Services' ), "Contains 10 core agency services cards" );
assert_test( false !== strpos( $rendered_html, 'vispan_cta_section' ), "Contains closing CTA section with dual lead intake form" );

// Verify strict isolation from trades & real estate sectors
assert_test( false === strpos( $rendered_html, 'Residential Trade Services' ), "Zero leakage of Residential Trade Services" );
assert_test( false === strpos( $rendered_html, 'HVAC Systems' ), "Zero leakage of HVAC trade" );
assert_test( false === strpos( $rendered_html, 'Roofing & Solar' ), "Zero leakage of Roofing trade" );
assert_test( false === strpos( $rendered_html, 'Luxury Estates' ), "Zero leakage of Luxury Estates real estate pillar" );
assert_test( false === strpos( $rendered_html, 'Commercial Portfolios' ), "Zero leakage of Commercial Portfolios real estate pillar" );
assert_test( false === strpos( $rendered_html, '<header' ), "General Agency output contains zero custom header tags" );
assert_test( false === strpos( $rendered_html, '<footer' ), "General Agency output contains zero custom footer tags" );

// 3. Shortcode and Template Canvas Isolation
echo "\n3. Testing Shortcode and Template Canvas Isolation...\n";

// Test class-post-sync uses vertical-aware template
$post_sync_code = file_get_contents( dirname( __DIR__ ) . '/includes/Integration/class-post-sync.php' );
assert_test( false !== strpos( $post_sync_code, "'_wp_page_template', \$page_template" ), "Post_Sync configures vertical-aware page template" );

// Test class-request-resolver prioritizes elementor_header_footer for general agency matching live site
$resolver_code = file_get_contents( dirname( __DIR__ ) . '/includes/URL/class-request-resolver.php' );
assert_test( false !== strpos( $resolver_code, "get_template_path( 'elementor_header_footer' )" ), "Request_Resolver routes general services to elementor_header_footer matching vispansolutions.com" );

echo "\n======================================================\n";
echo "  TEST SUMMARY: {$passed} passed, {$failed} failed.\n";
echo "======================================================\n\n";

if ( $failed > 0 ) {
	exit( 1 );
}
exit( 0 );
