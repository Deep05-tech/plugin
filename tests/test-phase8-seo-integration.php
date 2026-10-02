<?php
/**
 * Phase 8 Test Suite: SEO Integration, Metadata & Search Engine Controls.
 *
 * @package SeoCityPages\Tests
 */

// Set up mock WordPress environment for CLI testing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'SEO_CITY_PAGES_PLUGIN_DIR' ) ) {
	define( 'SEO_CITY_PAGES_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

// Include mock WordPress test foundation from Phase 7.
require_once __DIR__ . '/test-phase7-review-publishing.php';

// Include Phase 8 files.
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( (string) $url, $component );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		if ( is_object( $args ) ) {
			$r = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$r = $args;
		} else {
			$r = [];
		}
		if ( is_array( $defaults ) ) {
			return array_merge( $defaults, $r );
		}
		return $r;
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'mysql2date' ) ) {
	function mysql2date( $format, $date, $translate = true ) {
		return date( $format, strtotime( (string) $date ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $string );
		$string = strip_tags( $string );
		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}
		return trim( $string );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		return $value;
	}
}

require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/interface-seo-adapter.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/class-seo-environment-detector.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/class-seo-template-engine.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/class-seo-validator.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/class-seo-settings.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/class-schema-manager.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/class-sitemap-provider.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/Adapters/class-native-adapter.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/Adapters/class-yoast-adapter.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/Adapters/class-rank-math-adapter.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/Adapters/class-aioseo-adapter.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/SEO/class-seo-manager.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Admin/class-seo-controller.php';

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\Migrator;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\SEO\SEO_Settings;
use SeoCityPages\SEO\SEO_Environment_Detector;
use SeoCityPages\SEO\SEO_Template_Engine;
use SeoCityPages\SEO\SEO_Validator;
use SeoCityPages\SEO\Schema_Manager;
use SeoCityPages\SEO\Sitemap_Provider;
use SeoCityPages\SEO\SEO_Manager;
use SeoCityPages\SEO\Adapters\Native_Adapter;
use SeoCityPages\SEO\Adapters\Yoast_Adapter;
use SeoCityPages\SEO\Adapters\Rank_Math_Adapter;
use SeoCityPages\SEO\Adapters\AIOSEO_Adapter;
use SeoCityPages\Security\Capabilities;

$passed = 0;
$failed = 0;

function run_test( string $description, callable $fn ) {
	global $passed, $failed;
	try {
		$result = $fn();
		if ( false !== $result ) {
			echo "\033[32m✔ [PASS]\033[0m {$description}\n";
			$passed++;
		} else {
			echo "\033[31m✘ [FAIL]\033[0m {$description}\n";
			$failed++;
		}
	} catch ( \Throwable $e ) {
		echo "\033[31m✘ [ERROR]\033[0m {$description} -> " . $e->getMessage() . " on line " . $e->getLine() . "\n";
		$failed++;
	}
}

echo "\n=================================================================\n";
echo "🧪 Running SEO City Pages Phase 8 Test Suite: SEO Integration & Metadata\n";
echo "=================================================================\n\n";

// Set up mock tables and data.
Migrator::maybe_upgrade();

$country = Country_Repository::get_by_slug( 'us' );
if ( ! $country ) {
	$c_res = Country_Repository::create( [ 'name' => 'United States', 'code' => 'US', 'slug' => 'us' ] );
	$country_id = is_int( $c_res ) ? $c_res : (int) ( Country_Repository::get_by_slug( 'us' )->id ?? 1 );
} else {
	$country_id = (int) $country->id;
}

$state = State_Repository::get_by_slug( $country_id, 'fl' );
if ( ! $state ) {
	$s_res = State_Repository::create( [ 'name' => 'Florida', 'code' => 'FL', 'slug' => 'fl', 'country_id' => $country_id ] );
	$state_id = is_int( $s_res ) ? $s_res : (int) ( State_Repository::get_by_slug( $country_id, 'fl' )->id ?? 1 );
} else {
	$state_id = (int) $state->id;
}

$city = City_Repository::get_by_slug( $state_id, 'orlando' );
if ( ! $city ) {
	$ci_res = City_Repository::create( [ 'name' => 'Orlando', 'slug' => 'orlando', 'country_id' => $country_id, 'state_id' => $state_id ] );
	$city_id = is_int( $ci_res ) ? $ci_res : (int) ( City_Repository::get_by_slug( $state_id, 'orlando' )->id ?? 1 );
} else {
	$city_id = (int) $city->id;
}

$service = Service_Repository::get_by_slug( 'seo' );
if ( ! $service ) {
	$sv_res = Service_Repository::create( [ 'name' => 'SEO Agency', 'slug' => 'seo', 'status' => 'active' ] );
	$service_id = is_int( $sv_res ) ? $sv_res : (int) ( Service_Repository::get_by_slug( 'seo' )->id ?? 1 );
} else {
	$service_id = (int) $service->id;
}

$page_res = City_Page_Repository::find_or_create( [
	'country_id' => $country_id,
	'state_id'   => $state_id,
	'city_id'    => $city_id,
	'service_id' => $service_id,
	'status'     => 'draft',
] );
$page_id = (int) $page_res['id'];

// 1. Database Schema & Migration v0.7.0+
run_test( 'Database Migration: TARGET_DB_VERSION is >= 0.7.0', function() {
	return version_compare( Migrator::TARGET_DB_VERSION, '0.7.0', '>=' );
} );

// 2. Capabilities Management
run_test( 'Capabilities: MANAGE_SEO and EDIT_SEO capabilities exist', function() {
	$all_caps = Capabilities::get_all();
	return in_array( Capabilities::MANAGE_SEO, $all_caps, true ) &&
	       in_array( Capabilities::EDIT_SEO, $all_caps, true );
} );

// 3. SHA-256 Hash Duplicate Detection & Indexing
run_test( 'Duplicate Detection: SHA-256 hashes generated on SEO update', function() use ( $page_id ) {
	$title = 'Best SEO Agency in Orlando FL';
	$desc  = 'Professional search engine optimization services for Orlando businesses.';
	$canon = 'https://example.com/service-areas/orlando/seo/';

	City_Page_Repository::update_seo_metadata( $page_id, [
		'seo_title'        => $title,
		'meta_description' => $desc,
		'canonical_url'    => $canon,
	], true );

	$page = City_Page_Repository::get( $page_id );

	$expected_title_hash = hash( 'sha256', $title );
	$expected_desc_hash  = hash( 'sha256', $desc );
	$expected_canon_hash = hash( 'sha256', $canon );

	return $page->seo_title_hash === $expected_title_hash &&
	       $page->seo_desc_hash === $expected_desc_hash &&
	       $page->canonical_hash === $expected_canon_hash &&
	       (int) $page->is_manual_seo_override === 1;
} );

run_test( 'Duplicate Detection: find_duplicate_seo_titles finds matching pages', function() use ( $page_id ) {
	$title = 'Best SEO Agency in Orlando FL';
	$duplicates = City_Page_Repository::find_duplicate_seo_titles( $title, 0 );
	return count( $duplicates ) >= 1 && (int) $duplicates[0]->id === $page_id;
} );

run_test( 'Duplicate Detection: find_duplicate_seo_titles respects exclude_page_id', function() use ( $page_id ) {
	$title = 'Best SEO Agency in Orlando FL';
	$duplicates = City_Page_Repository::find_duplicate_seo_titles( $title, $page_id );
	return count( $duplicates ) === 0;
} );

run_test( 'Duplicate Detection: get_seo_duplicate_stats returns accurate metrics', function() {
	$stats = City_Page_Repository::get_seo_duplicate_stats();
	return isset( $stats['total_pages'], $stats['published_pages'], $stats['manual_overrides'], $stats['duplicate_titles'] );
} );

// 4. Deterministic SEO Template Engine
run_test( 'SEO Template Engine: Replaces tokens accurately', function() use ( $page_id ) {
	$page = City_Page_Repository::get( $page_id );
	$template = '{service} in {city}, {state_code} | {site_name} - {current_year}';
	$rendered = SEO_Template_Engine::render( $template, $page );

	return strpos( $rendered, 'SEO Agency in Orlando, FL' ) !== false &&
	       strpos( $rendered, date( 'Y' ) ) !== false &&
	       strpos( $rendered, '{' ) === false;
} );

run_test( 'SEO Template Engine: Cleans up empty or missing tokens safely', function() {
	$rendered = SEO_Template_Engine::render( '{service} in {city} | ', null );
	return $rendered === 'in' || $rendered === '';
} );

// 5. SEO Validator & Length Rules
run_test( 'SEO Validator: Accepts compliant title and description', function() {
	$report = SEO_Validator::validate( [
		'seo_title'        => 'SEO Agency in Orlando, FL | Growth Specialists',
		'meta_description' => 'Comprehensive search engine optimization for businesses in Orlando Florida. Scalable local rankings and conversion-driven performance.',
		'canonical_url'    => 'https://example.com/orlando/seo/',
	] );

	return $report['valid'] === true && empty( $report['errors'] );
} );

run_test( 'SEO Validator: Warns on excessively long title (>60 chars)', function() {
	$report = SEO_Validator::validate( [
		'seo_title' => 'This is an excessively long SEO title that goes way beyond sixty characters and will surely be truncated by search engines in Google search results',
	] );

	$has_len_warning = false;
	foreach ( $report['warnings'] as $w ) {
		if ( strpos( $w, 'truncated' ) !== false || strpos( $w, 'recommended max' ) !== false ) {
			$has_len_warning = true;
			break;
		}
	}
	return $has_len_warning;
} );

run_test( 'SEO Validator: Errors on invalid canonical URL protocol', function() {
	$report = SEO_Validator::validate( [
		'canonical_url' => 'ftp://invalid-url.com/landing',
	] );

	return $report['valid'] === false && count( $report['errors'] ) > 0;
} );

// 6. Precedence Cascade in SEO_Manager
run_test( 'Precedence Cascade: Tier 1 Manual Override wins over all', function() use ( $page_id ) {
	$page = City_Page_Repository::get( $page_id );
	$page->seo_title = 'Custom Manual Title Override';
	$page->meta_description = 'Custom Manual Description Override';
	$page->is_manual_seo_override = 1;

	$resolved = SEO_Manager::get_instance()->resolve_page_seo( $page );
	return $resolved['seo_title'] === 'Custom Manual Title Override' &&
	       $resolved['meta_description'] === 'Custom Manual Description Override';
} );

run_test( 'Precedence Cascade: Tier 2 Approved AI Metadata wins when no manual override', function() use ( $page_id ) {
	// Create approved content version with seo_metadata
	$v_id = Content_Version_Repository::create( [
		'page_id'      => $page_id,
		'version_num'  => 1,
		'status'       => 'approved',
		'content_json' => [
			'hero' => [ 'title' => 'Hero', 'description' => 'Desc', 'cta_text' => 'CTA' ],
			'seo_metadata' => [
				'seo_title'        => 'AI Generated SEO Title for Orlando',
				'meta_description' => 'AI Generated Meta Description for Orlando SEO services.',
				'og_title'         => 'AI OG Title',
				'og_description'   => 'AI OG Desc',
			],
		],
		'user_id'      => 1,
	] );

	City_Page_Repository::update( $page_id, [
		'published_version_id'   => $v_id,
		'seo_title'              => null,
		'meta_description'       => null,
		'is_manual_seo_override' => 0,
	] );

	$page = City_Page_Repository::get( $page_id );
	$resolved = SEO_Manager::get_instance()->resolve_page_seo( $page );

	return $resolved['seo_title'] === 'AI Generated SEO Title for Orlando' &&
	       $resolved['meta_description'] === 'AI Generated Meta Description for Orlando SEO services.';
} );

run_test( 'Precedence Cascade: Tier 3 Template wins when no AI metadata or manual override', function() use ( $page_id ) {
	City_Page_Repository::update( $page_id, [
		'published_version_id'   => null,
		'current_version_id'     => null,
		'seo_title'              => null,
		'meta_description'       => null,
		'is_manual_seo_override' => 0,
	] );

	SEO_Settings::update( [
		'default_title_template' => 'Custom Template: {service} in {city}, {state_code}',
	] );

	$page = City_Page_Repository::get( $page_id );
	$resolved = SEO_Manager::get_instance()->resolve_page_seo( $page );

	return strpos( $resolved['seo_title'], 'Custom Template: SEO Agency in Orlando, FL' ) !== false;
} );

// 7. Strict Status-Based Indexing Controls
run_test( 'Indexing Controls: Draft page forces noindex, nofollow', function() use ( $page_id ) {
	City_Page_Repository::update( $page_id, [
		'status'       => 'draft',
		'is_published' => 0,
	] );

	$page = City_Page_Repository::get( $page_id );
	$resolved = SEO_Manager::get_instance()->resolve_page_seo( $page );

	return $resolved['robots_index'] === 'noindex' &&
	       $resolved['robots_follow'] === 'nofollow';
} );

run_test( 'Indexing Controls: Published page allows configured index, follow', function() use ( $page_id ) {
	City_Page_Repository::update( $page_id, [
		'status'        => 'published',
		'is_published'  => 1,
		'robots_index'  => 'default',
		'robots_follow' => 'default',
	] );

	SEO_Settings::update( [
		'default_robots_index'  => 'index',
		'default_robots_follow' => 'follow',
	] );

	$page = City_Page_Repository::get( $page_id );
	$resolved = SEO_Manager::get_instance()->resolve_page_seo( $page );

	return $resolved['robots_index'] === 'index' &&
	       $resolved['robots_follow'] === 'follow';
} );

// 8. Schema JSON-LD Structured Data
run_test( 'Schema Manager: Generates compliant JSON-LD with geo-targeting', function() use ( $page_id ) {
	$page = City_Page_Repository::get( $page_id );
	$resolved = SEO_Manager::get_instance()->resolve_page_seo( $page );

	$schema = Schema_Manager::generate_schema( $page, $resolved );

	return ! empty( $schema['@context'] ) &&
	       $schema['@context'] === 'https://schema.org' &&
	       $schema['@type'] === 'Service' &&
	       isset( $schema['areaServed'] ) &&
	       $schema['areaServed']['name'] === 'Orlando' &&
	       $schema['areaServed']['containedInPlace']['name'] === 'Florida' &&
	       ! isset( $schema['aggregateRating'] ); // Verify no fake fabricated ratings
} );

run_test( 'Schema Manager: Disables schema when schema_type is none', function() use ( $page_id ) {
	$page = City_Page_Repository::get( $page_id );
	$page->schema_type = 'none';

	$resolved = SEO_Manager::get_instance()->resolve_page_seo( $page );
	$schema = Schema_Manager::generate_schema( $page, $resolved );

	return empty( $schema );
} );

// 9. SEO Environment Detector & Adapters
run_test( 'Environment Detector: Defaults to native when no 3rd party plugins active', function() {
	$primary = SEO_Environment_Detector::get_primary_adapter_slug();
	return $primary === 'native';
} );

run_test( 'Native Adapter: Correctly filters title and robots', function() use ( $page_id ) {
	City_Page_Repository::update( $page_id, [
		'status'       => 'published',
		'is_published' => 1,
	] );
	$page = City_Page_Repository::get( $page_id );
	$adapter = new Native_Adapter( SEO_Manager::get_instance() );

	$filtered_title = $adapter->filter_title( 'Default Title', $page );
	$filtered_robots = $adapter->filter_robots( [], $page );

	return ! empty( $filtered_title ) && isset( $filtered_robots['noindex'] ) === false;
} );

run_test( 'Yoast Adapter: Correctly filters Yoast hooks format', function() use ( $page_id ) {
	$page = City_Page_Repository::get( $page_id );
	$adapter = new Yoast_Adapter( SEO_Manager::get_instance() );

	$filtered_title = $adapter->filter_title( 'Default', $page );
	$filtered_robots = $adapter->filter_robots( 'none', $page );

	return ! empty( $filtered_title ) && strpos( $filtered_robots, 'index' ) !== false;
} );

run_test( 'Rank Math Adapter: Correctly filters Rank Math hooks format', function() use ( $page_id ) {
	$page = City_Page_Repository::get( $page_id );
	$adapter = new Rank_Math_Adapter( SEO_Manager::get_instance() );

	$filtered_title = $adapter->filter_title( 'Default', $page );
	$filtered_robots = $adapter->filter_robots( [], $page );

	return ! empty( $filtered_title ) && ( $filtered_robots['index'] ?? '' ) === 'index';
} );

run_test( 'AIOSEO Adapter: Correctly filters AIOSEO hooks format', function() use ( $page_id ) {
	$page = City_Page_Repository::get( $page_id );
	$adapter = new AIOSEO_Adapter( SEO_Manager::get_instance() );

	$filtered_title = $adapter->filter_title( 'Default', $page );
	$filtered_robots = $adapter->filter_robots( [], $page );

	return ! empty( $filtered_title ) && ( $filtered_robots['noindex'] ?? false ) === false;
} );

// 10. Scalable Sitemap Integration
run_test( 'Sitemap Provider: Queries published indexable pages in batches', function() {
	$total = City_Page_Repository::count_published_pages_for_sitemap();
	$pages = City_Page_Repository::get_published_pages_for_sitemap( 10, 0 );

	return $total >= 1 && count( $pages ) >= 1;
} );

run_test( 'Sitemap Provider: Excludes draft and noindexed pages', function() use ( $page_id ) {
	$initial_count = City_Page_Repository::count_published_pages_for_sitemap();
	City_Page_Repository::update( $page_id, [
		'status'       => 'draft',
		'is_published' => 0,
	] );

	$new_count = City_Page_Repository::count_published_pages_for_sitemap();
	return $new_count === ( $initial_count - 1 );
} );

// 11. Keyword Density Standards (1.5% - 3.0% with strict 3.0% max ceiling)
run_test( 'Keyword Density: Correctly calculates density within optimal 1.5% - 3.0% range', function() {
	// 100 words total, "SEO Services" (2 words) appears exactly 1 time = (1 * 2 / 100) * 100 = 2.0%
	$words = array_fill( 0, 98, 'marketing' );
	$sample_text = implode( ' ', $words ) . ' SEO Services';

	$report = SEO_Validator::calculate_keyword_density( $sample_text, 'SEO Services' );

	return $report['is_compliant'] === true &&
	       empty( $report['errors'] ) &&
	       empty( $report['warnings'] ) &&
	       $report['keywords']['SEO Services']['density'] == 2.0 &&
	       $report['keywords']['SEO Services']['status'] === 'compliant';
} );

run_test( 'Keyword Density: Flags error when keyword density exceeds 3.0% max ceiling', function() {
	// 100 words total, "SEO Services" (2 words) appears 2 times = (2 * 2 / 100) * 100 = 4.0% > 3.0%
	$words = array_fill( 0, 96, 'growth' );
	$sample_text = 'SEO Services ' . implode( ' ', $words ) . ' SEO Services';

	$report = SEO_Validator::calculate_keyword_density( $sample_text, 'SEO Services' );

	return $report['is_compliant'] === false &&
	       count( $report['errors'] ) === 1 &&
	       $report['keywords']['SEO Services']['density'] == 4.0 &&
	       $report['keywords']['SEO Services']['status'] === 'exceeds_max';
} );

run_test( 'Keyword Density: Flags warning when keyword density is below 1.5% minimum', function() {
	// 200 words total, "SEO Services" (2 words) appears 1 time = (1 * 2 / 200) * 100 = 1.0% < 1.5%
	$words = array_fill( 0, 198, 'strategy' );
	$sample_text = implode( ' ', $words ) . ' SEO Services';

	$report = SEO_Validator::calculate_keyword_density( $sample_text, 'SEO Services' );

	return $report['is_compliant'] === true && // Below min produces warning, not fatal error
	       count( $report['warnings'] ) === 1 &&
	       empty( $report['errors'] ) &&
	       $report['keywords']['SEO Services']['density'] == 1.0 &&
	       $report['keywords']['SEO Services']['status'] === 'below_min';
} );

echo "\n=================================================================\n";
echo "📊 PHASE 8 TEST SUMMARY: {$passed} Passed, {$failed} Failed\n";
echo "=================================================================\n\n";

if ( $failed > 0 ) {
	exit( 1 );
}
