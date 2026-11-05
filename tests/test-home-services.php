<?php
/**
 * Automated Test Suite for Home Services & Multi-Trade (6 Pillars & 21 Trades).
 *
 * @package SeoCityPages\Tests
 */

if ( ! defined( "ABSPATH" ) ) {
	define( "ABSPATH", dirname( __DIR__ ) . "/" );
}
if ( ! defined( "SEO_CITY_PAGES_VERSION" ) ) {
	define( "SEO_CITY_PAGES_VERSION", "1.0.0" );
}
if ( ! defined( "SEO_CITY_PAGES_PLUGIN_DIR" ) ) {
	define( "SEO_CITY_PAGES_PLUGIN_DIR", dirname( __DIR__ ) . "/" );
}
if ( ! defined( "SEO_CITY_PAGES_PLUGIN_URL" ) ) {
	define( "SEO_CITY_PAGES_PLUGIN_URL", "http://example.local/wp-content/plugins/seo-city-pages/" );
}
if ( ! defined( "SEO_CITY_PAGES_TESTING" ) ) {
	define( "SEO_CITY_PAGES_TESTING", true );
}

$GLOBALS["_test_shortcodes"] = [];

if ( ! function_exists( "add_shortcode" ) ) {
	function add_shortcode( string $tag, callable $callback ): void {
		$GLOBALS["_test_shortcodes"][ $tag ] = $callback;
	}
}
if ( ! function_exists( "do_shortcode" ) ) {
	function do_shortcode( string $content ): string {
		foreach ( $GLOBALS["_test_shortcodes"] as $tag => $callback ) {
			$pattern = "/" . preg_quote( "[" . $tag . "]", "/" ) . "/";
			if ( preg_match( $pattern, $content ) ) {
				$replacement = call_user_func( $callback, [] );
				$content     = preg_replace( $pattern, (string) $replacement, $content );
			}
		}
		return $content;
	}
}
if ( ! function_exists( "add_action" ) ) {
	function add_action() {}
}
if ( ! function_exists( "add_filter" ) ) {
	function add_filter() {}
}
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
	function home_url( string $path = "" ): string { return "http://example.local/" . ltrim( $path, "/" ); }
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


if ( ! defined( "OBJECT" ) ) define( "OBJECT", "OBJECT" );
class MockWPDB {
	public string $prefix = "wp_";
	public int $insert_id = 1;
	public array $queries = [];
	public array $tables = [];
	public function get_results( $sql, $output = OBJECT ) { return []; }
	public function get_row( $sql, $output = OBJECT ) { return null; }
	public function prepare( $query, ...$args ) { return vsprintf( str_replace( [ "%s", "%d", "%f" ], [ "'%s'", "%d", "%f" ], $query ), $args ); }
	public function insert( $table, $data, $format = null ) { return 1; }
}
$GLOBALS["wpdb"] = new MockWPDB();

// Autoloader
spl_autoload_register( function ( $class ) {
	$prefix   = "SeoCityPages\\";
	$base_dir = dirname( __DIR__ ) . "/includes/";
	$len      = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}
	$relative_class = substr( $class, $len );
	$parts          = explode( "\\", $relative_class );
	$file_name      = "class-" . strtolower( str_replace( "_", "-", array_pop( $parts ) ) ) . ".php";
	$sub_dir        = ! empty( $parts ) ? implode( "/", $parts ) . "/" : "";
	$file           = $base_dir . $sub_dir . $file_name;
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

require_once dirname( __DIR__ ) . "/includes/Elementor/class-page-context.php";
require_once dirname( __DIR__ ) . "/includes/Elementor/class-content-context.php";
require_once dirname( __DIR__ ) . "/includes/Elementor/class-elementor-integration.php";

use SeoCityPages\Elementor\Page_Context;
use SeoCityPages\Elementor\Content_Context;
use SeoCityPages\Elementor\Elementor_Integration;

$passed = 0;
$failed = 0;

$assert = function( bool $cond, string $msg ) use ( &$passed, &$failed ) {
	if ( $cond ) {
		echo "  [PASS] " . $msg . "\n";
		$passed++;
	} else {
		echo "  [FAIL] " . $msg . "\n";
		$failed++;
	}
};

echo "======================================================\n";
echo "  TEST SUITE: Home Services & Multi-Trade Architecture\n";
echo "======================================================\n\n";

// 1. Backward compatibility test
echo "1. Testing Backward Compatibility (Dental Clinic)...\n";
$page = (object) [
	"id"             => 101,
	"country_id"     => 1,
	"state_id"       => 1,
	"city_id"        => 1,
	"service_id"     => 1,
	"title_override" => "Dental Clinic SEO Tampa",
	"url_path"       => "tampa/dental-clinic"
];
$country = (object) [ "name" => "United States", "code" => "US" ];
$state   = (object) [ "name" => "Florida", "code" => "FL" ];
$city    = (object) [ "name" => "Tampa" ];
$svc_dental = (object) [ "name" => "Dental Clinic" ];
Page_Context::set_context( $page, $country, $state, $city, $svc_dental );

$dental_html = Content_Context::render_industry_verticals_html();
$assert( strpos( $dental_html, "Digital Marketing for Dental Clinic" ) !== false, "Dental: renders standard Digital Marketing" );
$assert( strpos( $dental_html, "Google Ads" ) !== false, "Dental: renders Google Ads" );
$assert( strpos( $dental_html, "Branding Services" ) !== false, "Dental: renders Branding Services" );
$assert( strpos( $dental_html, "CGI Services" ) !== false, "Dental: renders CGI Services" );
$assert( strpos( $dental_html, "HVAC Services" ) === false, "Dental: zero contractor trade leakage" );
$assert( strpos( $dental_html, "Plumbing Services" ) === false, "Dental: zero plumbing trade leakage" );

// 2. Home Services 21 Trades
echo "\n2. Testing Home Services (6 Pillars & 21 Trades)...\n";
$svc_home = (object) [ "name" => "Home Services" ];
Page_Context::set_context( $page, $country, $state, $city, $svc_home );

$home_html = Content_Context::render_industry_verticals_html();
$trade_count = substr_count( $home_html, "class=\"scp-trade-card " );
$assert( $trade_count === 21, "Home Services: exactly 21 trade cards rendered (found: " . $trade_count . ")" );

$trades = [
	"HVAC Services",
	"Plumbing Services",
	"Electrical Services",
	"Roofing & Siding",
	"Gutter Cleaning & Installation",
	"Garage Door Repair",
	"Pressure Washing",
	"General Handyman Work",
	"Painting",
	"Flooring & Carpeting",
	"Carpentry Services",
	"Lawn Care & Landscaping",
	"Pest Control & Wildlife Removal",
	"Pool Maintenance",
	"Snow & Ice Removal",
	"Housekeeping & Maid Services",
	"Junk Removal & Hauling",
	"Window Cleaning",
	"Home Security Systems",
	"Locksmith Services",
	"Solar Power & Energy Management"
];

$missing_trades = [];
foreach ( $trades as $t ) {
	if ( strpos( $home_html, esc_html( $t ) ) === false ) {
		$missing_trades[] = $t;
	}
}
$assert( empty( $missing_trades ), "All 21 requested trades present in HTML output" );

// 3. Category tabs
echo "\n3. Testing Category Filter Tabs & Interactive JS...\n";
$assert( strpos( $home_html, "data-category=\"all\"" ) !== false, "Category tab: All Trades" );
$assert( strpos( $home_html, "data-category=\"core\"" ) !== false, "Category tab: Core & Emergency" );
$assert( strpos( $home_html, "data-category=\"exterior\"" ) !== false, "Category tab: Exterior & Structural" );
$assert( strpos( $home_html, "data-category=\"interior\"" ) !== false, "Category tab: Interior & Handyman" );
$assert( strpos( $home_html, "data-category=\"outdoor\"" ) !== false, "Category tab: Outdoor & Yard Care" );
$assert( strpos( $home_html, "data-category=\"cleaning\"" ) !== false, "Category tab: Cleaning & Sanitation" );
$assert( strpos( $home_html, "data-category=\"safety\"" ) !== false, "Category tab: Safety & Solar" );
$assert( strpos( $home_html, "scpFilterTrades" ) !== false, "Vanilla JS scpFilterTrades function embedded" );
$assert( strpos( $home_html, "scpSelectTrade" ) !== false, "Vanilla JS scpSelectTrade function embedded" );

// 4. Shortcodes and Tokens
echo "\n4. Testing Shortcodes & Elementor Token Integration...\n";
$integration = Elementor_Integration::instance();
$integration->register_shortcodes();

$assert( isset( $GLOBALS["_test_shortcodes"]["seo_home_services_trades"] ), "Shortcode [seo_home_services_trades] is registered" );
$assert( isset( $GLOBALS["_test_shortcodes"]["seo_trade_directory"] ), "Shortcode [seo_trade_directory] is registered" );

$token_test = "<div class=\"wrap\">{{seo_home_services_trades}}</div>";
$rendered = Elementor_Integration::filter_rendered_content( $token_test );
$assert( substr_count( $rendered, "class=\"scp-trade-card " ) === 21, "Token {{seo_home_services_trades}} hydrates 21 trades" );

$shortcode_test = "<div class=\"wrap\">[seo_trade_directory]</div>";
$rendered_sc = Elementor_Integration::filter_rendered_content( $shortcode_test );
$assert( substr_count( $rendered_sc, "class=\"scp-trade-card " ) === 21, "Shortcode [seo_trade_directory] hydrates 21 trades" );

// 5. Inquiry Form Adaptation
echo "\n5. Testing Inquiry Form Adaptation...\n";
Page_Context::set_context( $page, $country, $state, $city, $svc_dental );
$dental_form = Content_Context::render_inquiry_form();
$assert( strpos( $dental_form, "HVAC Marketing" ) === false, "Dental form excludes contractor trade options" );

Page_Context::set_context( $page, $country, $state, $city, $svc_home );
$home_form = Content_Context::render_inquiry_form();
$assert( strpos( $home_form, "Home Services Digital Marketing" ) !== false, "Home Services form contains contractor options" );
$assert( strpos( $home_form, "HVAC Marketing &amp; SEO" ) !== false || strpos( $home_form, "HVAC Marketing & SEO" ) !== false, "Home Services form contains HVAC option" );

// 6. Dynamic AI Content Injection & Fallbacks in Master Template
echo "\n6. Testing Dynamic AI Content Injection & Fallbacks...\n";
Content_Context::reset();
Page_Context::set_context( $page, $country, $state, $city, $svc_home );

// 6a. Default fallback rendering when AI content is empty
$default_page_html = Content_Context::render_home_services_full_page_html();
$assert( strpos( $default_page_html, 'grid grid-cols-1 md:grid-cols-2 gap-7" id="official-services-grid"' ) !== false, "Services layout: 2 boxes per line (md:grid-cols-2)" );
$assert( strpos( $default_page_html, "2x2 Photo Benefit Grid" ) !== false, "Benefits layout: 2 boxes per line (2x2)" );
$assert( strpos( $default_page_html, 'grid sm:grid-cols-2 lg:grid-cols-3 gap-7' ) !== false, "Trades layout: uniform 3 columns on desktop for all categories" );
$assert( strpos( $default_page_html, 'scp-trade-card bg-white rounded-2xl overflow-hidden border border-slate-200 flex flex-col shadow-sm h-full' ) !== false, "Trade cards: uniform height with h-full" );
$assert( strpos( $default_page_html, "Get A Free Consultation" ) !== false, "Fallback: default hero CTA rendered" );
$assert( strpos( $default_page_html, "Vispan Solutions has delivered 150+ projects" ) !== false, "Fallback: default FAQ rendered" );

// 6b. Dynamic AI content injection
$mock_ai_content = [
	'hero' => [
		'title'       => 'Custom AI Driven Home Services SEO Tampa',
		'description' => 'Dynamic AI generated hero description tailored specifically for Tampa contractor growth.',
		'cta'         => 'Claim Your Tampa SEO Audit',
	],
	'introduction' => [
		'heading' => 'Why Local SEO Dominates The Tampa Contractor Scene',
		'body'    => '<p>Comprehensive localized introduction generated exclusively by OpenAI for Tampa trades.</p>',
	],
	'service_overview' => [
		'heading' => 'Comprehensive Digital Growth Architecture for Tampa Trades',
		'body'    => '<p>Custom marketing engine engineered exclusively to scale Tampa contractors.</p>',
	],
	'metro_stats' => [
		[ 'value' => '3.2M+', 'label' => 'Tampa Metro Reach' ],
		[ 'value' => '45k+', 'label' => 'Active Trade Queries' ],
		[ 'value' => '94%', 'label' => 'High-Intent Phone Calls' ],
		[ 'value' => '60 Days', 'label' => 'Lead Compound Velocity' ],
	],
	'benefits' => [
		[ 'title' => 'Hyper-Local Search Dominance', 'description' => 'Targeting high-intent homeowners in high-ticket zip codes.' ],
		[ 'title' => 'Exclusive Contractor Leads', 'description' => 'Zero shared leads or aggregator competition.' ],
	],
	'market_anatomy' => [
		'kicker'          => 'Hyper-Local Search Intelligence',
		'heading'         => 'The Search Geography of Tampa Bay Trades',
		'lead_paragraph'  => '<p>Analyzing search competition across Tampa Bay communities.</p>',
		'search_dynamics' => '<p>Why national directories lose against targeted local contractor pages.</p>',
	],
	'market_accordion' => [
		[ 'title' => 'HVAC Search Intent Mapping', 'subtitle' => 'High-CPC Emergency Calls', 'body' => '<p>Mapping immediate-dispatch heating and cooling queries.</p>' ],
	],
	'submarkets' => [
		[ 'name' => 'South Tampa & Hyde Park', 'type_tag' => 'Premium Residential', 'fact' => 'High average invoice values.', 'description' => 'Targeting luxury home remodeling and maintenance.' ],
	],
	'case_proof' => [
		'title'        => 'Tampa Regional Benchmark Case Proof',
		'tagline'      => 'How a local plumber captured 14 target zip codes in Tampa',
		'client_quote' => 'Vispan helped us scale our truck fleet from 2 to 7 within 8 months.',
		'highlights'   => [
			[ 'metric' => '+280%', 'label' => 'Inbound Phone Inquiries' ],
		],
	],
	'essay' => [
		'heading'    => 'The Tampa Contractor Growth Blueprint',
		'body'       => '<p>A deep-dive analysis of search mechanics for residential trade fleets in Tampa.</p>',
		'milestones' => [
			[ 'step' => 'PHASE 01', 'title' => 'Core Audit', 'summary' => 'Technical citation graph build.' ],
		],
	],
	'process' => [
		[
			'step'        => '01',
			'title'       => 'Tampa Keyword Discovery',
			'description' => 'Analyzing search volume for local contractor queries.',
		],
		[
			'step'        => '02',
			'title'       => 'Hyperlocal On-Page Optimization',
			'description' => 'Structuring geo-targeted landing pages.',
		],
	],
	'testimonials' => [
		[
			'quote'        => 'Vispan skyrocketed our plumbing leads in Tampa by 240% in 90 days.',
			'client_name'  => 'Mark Henderson',
			'client_role'  => 'Owner, Henderson Plumbing',
			'rating'       => 5,
		],
	],
	'faqs' => [
		[
			'question' => 'How quickly will my Tampa roofing company see results?',
			'answer'   => 'Most Tampa contractors begin generating qualified inquiries within 60 to 90 days.',
		],
	],
	'final_cta' => [
		'heading'     => 'Ready to Dominate the Tampa Contractor Market?',
		'description' => 'Contact Vispan Solutions today to scale your inbound trade leads.',
		'button_text' => 'Claim Your Free Strategy Session Now →',
	],
];

$ref = new ReflectionClass( Content_Context::class );
$prop = $ref->getProperty( 'cached_content' );
$prop->setAccessible( true );
$prop->setValue( null, $mock_ai_content );

$ai_page_html = Content_Context::render_home_services_full_page_html();

$assert( strpos( $ai_page_html, 'Custom AI Driven Home Services SEO Tampa' ) !== false, "Dynamic AI: Hero title rendered" );
$assert( strpos( $ai_page_html, 'Dynamic AI generated hero description tailored specifically for Tampa' ) !== false, "Dynamic AI: Hero description rendered" );
$assert( strpos( $ai_page_html, 'Claim Your Tampa SEO Audit' ) !== false, "Dynamic AI: Hero CTA button rendered" );
$assert( strpos( $ai_page_html, '3.2M+' ) !== false, "Dynamic AI: Hero metro stats rendered" );
$assert( strpos( $ai_page_html, 'Why Local SEO Dominates The Tampa Contractor Scene' ) !== false, "Dynamic AI: Intro heading rendered" );
$assert( strpos( $ai_page_html, 'Comprehensive localized introduction generated exclusively by OpenAI' ) !== false, "Dynamic AI: Intro body rendered" );
$assert( strpos( $ai_page_html, 'Hyper-Local Search Dominance' ) !== false, "Dynamic AI: Custom benefit rendered" );
$assert( strpos( $ai_page_html, 'Comprehensive Digital Growth Architecture for Tampa Trades' ) !== false, "Dynamic AI: Service overview heading rendered" );
$assert( strpos( $ai_page_html, 'The Search Geography of Tampa Bay Trades' ) !== false, "Dynamic AI: Market anatomy heading rendered" );
$assert( strpos( $ai_page_html, 'HVAC Search Intent Mapping' ) !== false, "Dynamic AI: Market accordion item rendered" );
$assert( strpos( $ai_page_html, esc_html( 'South Tampa & Hyde Park' ) ) !== false, "Dynamic AI: Submarket rendered" );
$assert( strpos( $ai_page_html, 'Tampa Regional Benchmark Case Proof' ) !== false, "Dynamic AI: Case proof rendered" );
$assert( strpos( $ai_page_html, 'The Tampa Contractor Growth Blueprint' ) !== false, "Dynamic AI: Editorial essay rendered" );
$assert( strpos( $ai_page_html, 'Tampa Keyword Discovery' ) !== false, "Dynamic AI: Custom process step rendered" );
$assert( strpos( $ai_page_html, 'Mark Henderson' ) !== false, "Dynamic AI: Custom testimonial client rendered" );
$assert( strpos( $ai_page_html, 'How quickly will my Tampa roofing company see results?' ) !== false, "Dynamic AI: Custom FAQ rendered" );
$assert( strpos( $ai_page_html, 'Ready to Dominate the Tampa Contractor Market?' ) !== false, "Dynamic AI: Final CTA heading rendered" );
$assert( strpos( $ai_page_html, 'Claim Your Free Strategy Session Now →' ) !== false, "Dynamic AI: Final CTA button rendered" );

// Reset after test
Content_Context::reset();

echo "\n======================================================\n";
echo "  TEST SUMMARY: " . $passed . " passed, " . $failed . " failed.\n";
echo "======================================================\n";

if ( $failed > 0 ) {
	exit( 1 );
}
