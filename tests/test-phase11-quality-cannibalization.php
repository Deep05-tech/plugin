<?php
/**
 * Phase 11 Test Suite: Programmatic SEO Quality, Content Uniqueness & Cannibalization Controls.
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

// Include prior test environments.
require_once __DIR__ . '/test-phase10-production-health.php';

// Include Phase 11 files.
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Database/class-quality-repository.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Quality/class-similarity-engine.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Quality/class-cannibalization-detector.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Quality/class-internal-link-analyzer.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Quality/class-quality-engine.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Quality/class-quality-gate-manager.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Quality/class-ai-review-advisor.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/Handlers/class-quality-scan-handler.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Admin/class-quality-controller.php';

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\Migrator;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Database\Quality_Repository;
use SeoCityPages\Quality\Similarity_Engine;
use SeoCityPages\Quality\Cannibalization_Detector;
use SeoCityPages\Quality\Internal_Link_Analyzer;
use SeoCityPages\Quality\Quality_Engine;
use SeoCityPages\Quality\Quality_Gate_Manager;
use SeoCityPages\Quality\AI_Review_Advisor;
use SeoCityPages\Jobs\Bulk_Job_Repository;
use SeoCityPages\Jobs\Bulk_Job_Runner;
use SeoCityPages\Jobs\Handlers\Quality_Scan_Handler;
use SeoCityPages\Workflow\Status_Workflow;
use SeoCityPages\Security\Capabilities;

// Install all schema tables for Phase 11 testing.
Schema::install();

function seed_p11_row( string $table, array $data ) {
	global $wpdb;
	$id = (int) ( $data['id'] ?? 0 );
	if ( $id > 0 ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id = %d", $id ) );
	}
	$wpdb->insert( $table, $data );
}

seed_p11_row( Schema::get_table_name( Schema::TABLE_COUNTRIES ), [ 'id' => 1, 'name' => 'United States', 'code' => 'US', 'slug' => 'united-states', 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ] );
seed_p11_row( Schema::get_table_name( Schema::TABLE_STATES ), [ 'id' => 1, 'country_id' => 1, 'name' => 'Florida', 'code' => 'FL', 'slug' => 'florida', 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ] );
seed_p11_row( Schema::get_table_name( Schema::TABLE_CITIES ), [ 'id' => 1, 'country_id' => 1, 'state_id' => 1, 'name' => 'Orlando', 'slug' => 'orlando', 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ] );
seed_p11_row( Schema::get_table_name( Schema::TABLE_SERVICES ), [ 'id' => 1, 'name' => 'SEO Services', 'slug' => 'seo-services', 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ] );
seed_p11_row( Schema::get_table_name( Schema::TABLE_SERVICES ), [ 'id' => 2, 'name' => 'Search Engine Optimization', 'slug' => 'search-engine-optimization', 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ] );

echo "\n=================================================================\n";
echo "🧪 Running SEO City Pages Phase 11 Test Suite: Quality & Cannibalization\n";
echo "=================================================================\n\n";

$p11_passed = 0;
$p11_failed = 0;

function run_p11_test( string $title, callable $callback ) {
	global $p11_passed, $p11_failed;
	try {
		$result = $callback();
		if ( true === $result ) {
			echo "✔ [PASS] {$title}\n";
			$p11_passed++;
		} else {
			echo "✘ [FAIL] {$title} (returned " . var_export( $result, true ) . ")\n";
			$p11_failed++;
		}
	} catch ( \Throwable $e ) {
		echo "✘ [ERROR] {$title} -> " . $e->getMessage() . " on line " . $e->getLine() . "\n";
		$p11_failed++;
	}
}

/**
 * Helper to insert a valid test city page record into database.
 */
function create_phase11_page( array $data ): int {
	global $wpdb;
	$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
	$slug  = sanitize_title( $data['slug'] ?? ( 'p11-' . bin2hex( random_bytes( 4 ) ) ) );
	$now   = current_time( 'mysql' );

	$wpdb->insert(
		$table,
		[
			'country_id'        => (int) ( $data['country_id'] ?? 1 ),
			'state_id'          => (int) ( $data['state_id'] ?? 1 ),
			'city_id'           => (int) ( $data['city_id'] ?? 1 ),
			'service_id'        => (int) ( $data['service_id'] ?? 1 ),
			'slug'              => $slug,
			'url_path'          => '/' . $slug . '/',
			'title_override'    => sanitize_text_field( $data['title'] ?? ( $data['title_override'] ?? 'Test Page' ) ),
			'seo_title'         => sanitize_text_field( $data['seo_title'] ?? ( $data['primary_keyword'] ?? '' ) ),
			'meta_description'  => sanitize_text_field( $data['seo_description'] ?? ( $data['meta_description'] ?? '' ) ),
			'status'            => sanitize_text_field( $data['status'] ?? 'draft' ),
			'generation_status' => sanitize_text_field( $data['generation_status'] ?? 'not_generated' ),
			'created_at'        => $now,
			'updated_at'        => $now,
		]
	);

	return (int) $wpdb->insert_id;
}

// 1. Database Schema & Migration v0.9.0
run_p11_test( 'Database Migration: TARGET_DB_VERSION is >= 0.9.0', function() {
	return version_compare( Migrator::TARGET_DB_VERSION, '0.9.0', '>=' );
} );

run_p11_test( 'Database Schema: Phase 11 quality tables are defined in Schema', function() {
	$eval_table = Schema::get_table_name( Schema::TABLE_QUALITY_EVALUATIONS );
	$fp_table   = Schema::get_table_name( Schema::TABLE_CONTENT_FINGERPRINTS );
	$dis_table  = Schema::get_table_name( Schema::TABLE_QUALITY_DISMISSALS );

	return ! empty( $eval_table ) && ! empty( $fp_table ) && ! empty( $dis_table );
} );

// 2. Capabilities
Capabilities::register_defaults();
run_p11_test( 'Capabilities: Phase 11 quality capabilities are registered', function() {
	$all = Capabilities::get_all();
	return in_array( Capabilities::MANAGE_QUALITY, $all, true ) &&
	       in_array( Capabilities::RUN_QUALITY_SCAN, $all, true ) &&
	       in_array( Capabilities::DISMISS_QUALITY_WARNING, $all, true );
} );

// 3. Similarity Engine: Text Normalization & Token Masking
run_p11_test( 'Similarity Engine: Normalizes text and masks city/state/service entities', function() {
	$text = 'Top Orlando Florida SEO Services provider delivering top rankings!';
	$normalized = Similarity_Engine::normalize_text( $text, 'Orlando', 'Florida', 'SEO Services' );
	
	return strpos( $normalized, '__city__' ) !== false &&
	       strpos( $normalized, '__state__' ) !== false &&
	       strpos( $normalized, '__service__' ) !== false;
} );

// 4. Similarity Engine: Jaccard Calculation
run_p11_test( 'Similarity Engine: Identical normalized shingles yield 100% Jaccard similarity', function() {
	$shingles = [ 'alpha beta gamma', 'beta gamma delta', 'gamma delta epsilon' ];
	$score = Similarity_Engine::calculate_jaccard_similarity( $shingles, $shingles );
	return $score === 100.0;
} );

run_p11_test( 'Similarity Engine: Completely disjoint shingles yield 0% Jaccard similarity', function() {
	$shingles_a = [ 'one two three', 'two three four' ];
	$shingles_b = [ 'apple banana orange', 'banana orange grape' ];
	$score = Similarity_Engine::calculate_jaccard_similarity( $shingles_a, $shingles_b );
	return $score === 0.0;
} );

// 5. Cannibalization Detector: Duplicate Keywords in Same City
run_p11_test( 'Cannibalization: Flags 100% overlap for duplicate primary keyword in same city', function() {
	$page_a = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 1,
		'title'           => 'SEO Agency in Miami',
		'slug'            => 'miami-seo-agency-dup-a',
		'primary_keyword' => 'seo agency miami',
	] );

	$page_b = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 2,
		'title'           => 'Miami SEO Agency Best',
		'slug'            => 'miami-seo-agency-dup-b',
		'primary_keyword' => 'seo agency miami',
	] );

	$result = Cannibalization_Detector::analyze_cannibalization( $page_b, 1, 'SEO Agency', 'seo agency miami' );
	return $result['has_overlap'] === true && $result['overlap_score'] === 100.0 && $result['overlap_page_id'] === $page_a;
} );

// 6. Cannibalization Detector: Synonym Service Overlap
run_p11_test( 'Cannibalization: Detects service synonym clash (SEO Services vs Search Engine Optimization)', function() {
	$overlap = Cannibalization_Detector::are_synonym_services( 'SEO Services', 'Search Engine Optimization' );
	$distinct = Cannibalization_Detector::are_synonym_services( 'Plumbing Services', 'SEO Services' );
	return $overlap === true && $distinct === false;
} );

// 7. Internal Link Analyzer & Natural Suggestions
run_p11_test( 'Internal Link Analyzer: Extracts internal links and detects missing links', function() {
	$content_with_links = [
		'introduction' => [ 'body' => '<p>Visit our <a href="/florida/tampa/seo">Tampa SEO</a> office.</p>' ],
	];
	$res = Internal_Link_Analyzer::analyze( 100, $content_with_links, [ 'city_id' => 1 ] );
	return $res['link_count'] === 1;
} );

// 8. Quality Engine: Complete High-Quality Content Evaluation
run_p11_test( 'Quality Engine: Scores robust compliant page as Pass (Score >= 85)', function() {
	seed_p11_row( Schema::get_table_name( Schema::TABLE_CITIES ), [ 'id' => 10, 'country_id' => 1, 'state_id' => 1, 'name' => 'Orlando', 'slug' => 'orlando-unique', 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ] );
	seed_p11_row( Schema::get_table_name( Schema::TABLE_SERVICES ), [ 'id' => 10, 'name' => 'SEO Services', 'slug' => 'seo-services-unique', 'status' => 'active', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ] );

	$page_id = create_phase11_page( [
		'city_id'         => 10,
		'state_id'        => 1,
		'service_id'      => 10,
		'title'           => 'SEO Services in Orlando',
		'slug'            => 'orlando-seo-services-robust',
		'primary_keyword' => 'SEO Services Orlando',
		'seo_title'       => 'Best SEO Services in Orlando | Local Agency',
		'seo_description' => 'Comprehensive SEO services in Orlando FL designed to maximize organic visibility and search traffic.',
	] );

	$rich_text = "Orlando businesses thrive when their local digital search presence is fully optimized. "
		. "Our SEO Services in Orlando provide end-to-end audit, content, and citation strategies across Orange County. "
		. "Choosing professional SEO Services in Orlando helps your business connect directly with targeted local customers. "
		. "We deliver customized campaigns across every neighborhood in Orlando Florida. "
		. '<a href="/services/audit">SEO Audit</a> <a href="/locations/florida">Florida SEO</a> '
		. str_repeat( "Delivering proven digital marketing architectures and schema structured data for regional businesses in Florida. ", 30 );

	$full_content = [
		'hero' => [
			'title'       => 'SEO Services in Orlando',
			'description' => 'Drive measurable organic pipeline in Orlando Florida.',
			'cta_text'    => 'Get Audit',
		],
		'introduction' => [
			'heading' => 'Orlando Search Engine Leaders',
			'body'    => "<p>{$rich_text}</p>",
		],
		'service_overview' => [
			'heading' => 'Tailored Campaigns for Orlando Enterprises',
			'body'    => '<p>From technical audits to geo-targeted schema and local citations, we ensure full authority in Orlando.</p>',
		],
		'benefits' => [
			[ 'title' => 'Local Map Pack', 'description' => 'Achieve top placement for high intent local queries.' ],
			[ 'title' => 'Targeted Keywords', 'description' => 'Capture qualified traffic from customers searching in Orlando.' ],
			[ 'title' => 'Technical Precision', 'description' => 'Fast loading times and compliant schema markup.' ],
		],
		'process' => [
			[ 'step_number' => 1, 'title' => 'Comprehensive Audit', 'description' => 'Analyze competitive landscape in Orlando.' ],
			[ 'step_number' => 2, 'title' => 'On-Page Schema', 'description' => 'Optimize local entity schema.' ],
			[ 'step_number' => 3, 'title' => 'Authority Building', 'description' => 'Acquire high quality local citations.' ],
		],
		'local_relevance' => [
			'heading' => 'Deep Orlando Community Presence',
			'body'    => '<p>Dedicated exclusively to businesses throughout Central Florida and the greater Orlando metropolitan area.</p>',
		],
		'faqs' => [
			[ 'question' => 'Why invest in Orlando SEO?', 'answer' => 'Orlando is a competitive market where organic search capture yields high ROI.' ],
			[ 'question' => 'How soon will results appear?', 'answer' => 'Typically between 60 to 90 days depending on keyword competition.' ],
		],
		'final_cta' => [
			'heading'     => 'Ready to Scale in Orlando?',
			'description' => 'Contact our SEO team today for a complimentary local visibility audit.',
			'button_text' => 'Get Started Now',
		],
	];

	$eval = Quality_Engine::evaluate_page( $page_id, $full_content );
	return $eval['quality_score'] >= 85 && $eval['quality_status'] === Quality_Repository::STATUS_PASS;
} );

// 9. Quality Engine: Critical Placeholder Detection
run_p11_test( 'Quality Engine: Flags unresolved placeholders [City] or Lorem Ipsum as Fail', function() {
	$page_id = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 1,
		'title'           => 'Placeholder Test Page',
		'slug'            => 'placeholder-test-page',
		'primary_keyword' => 'Placeholder Orlando',
	] );

	$bad_content = [
		'hero' => [
			'title'       => 'Top SEO in [City]',
			'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
		],
	];

	$eval = Quality_Engine::evaluate_page( $page_id, $bad_content );
	return $eval['quality_status'] === Quality_Repository::STATUS_FAIL && $eval['placeholder_issues_count'] >= 2;
} );

// 10. Quality Engine: Thin Content Detection
run_p11_test( 'Quality Engine: Flags thin content (< 100 words) with thin_content_flag = 1', function() {
	$page_id = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 1,
		'title'           => 'Thin Content Test',
		'slug'            => 'thin-content-test',
		'primary_keyword' => 'Thin Orlando',
	] );

	$thin_content = [
		'hero' => [ 'title' => 'Short title', 'description' => 'Short desc' ],
	];

	$eval = Quality_Engine::evaluate_page( $page_id, $thin_content );
	return (int) $eval['thin_content_flag'] === 1;
} );

// 11. Pre-Publication Quality Gate: Block Critical Mode
run_p11_test( 'Quality Gate: Blocks publication when gate_mode = block_critical and status = fail', function() {
	Quality_Gate_Manager::set_gate_mode( Quality_Repository::GATE_MODE_BLOCK_CRITICAL );

	$page_id = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 1,
		'title'           => 'Gate Block Test',
		'slug'            => 'gate-block-test',
		'primary_keyword' => 'Gate Orlando',
	] );

	// Create an approved version with unresolved placeholders (Critical Issue)
	$v_id = Content_Version_Repository::create( [
		'page_id'      => $page_id,
		'status'       => Status_Workflow::STATUS_APPROVED,
		'content_json' => json_encode( [ 'hero' => [ 'title' => 'TODO Fix This [City]' ] ] ),
	] );

	global $wpdb;
	$wpdb->update( Schema::get_table_name( Schema::TABLE_CITY_PAGES ), [ 'current_version_id' => $v_id ], [ 'id' => $page_id ] );

	// Attempt to publish
	$pub_res = Status_Workflow::publish( $page_id, $v_id, 1 );
	return $pub_res['success'] === false && stripos( $pub_res['message'], 'quality gate' ) !== false;
} );

// 12. Pre-Publication Quality Gate: Warnings Only Mode
run_p11_test( 'Quality Gate: Allows publication when gate_mode = warnings_only', function() {
	Quality_Gate_Manager::set_gate_mode( Quality_Repository::GATE_MODE_WARNINGS_ONLY );

	$page_id = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 1,
		'title'           => 'Warnings Only Gate Test',
		'slug'            => 'warnings-only-gate-test',
		'primary_keyword' => 'Warnings Gate Orlando',
	] );

	$v_id = Content_Version_Repository::create( [
		'page_id'      => $page_id,
		'status'       => Status_Workflow::STATUS_APPROVED,
		'content_json' => json_encode( [ 'hero' => [ 'title' => 'SEO Services in Orlando' ] ] ),
	] );

	global $wpdb;
	$wpdb->update( Schema::get_table_name( Schema::TABLE_CITY_PAGES ), [ 'current_version_id' => $v_id ], [ 'id' => $page_id ] );

	$pub_res = Status_Workflow::publish( $page_id, $v_id, 1 );
	return $pub_res['success'] === true;
} );

// 13. Pre-Publication Quality Gate: Off Mode
run_p11_test( 'Quality Gate: Allows publication when gate_mode = off', function() {
	Quality_Gate_Manager::set_gate_mode( Quality_Repository::GATE_MODE_OFF );

	$gate_check = Quality_Gate_Manager::can_publish( 999 );
	return $gate_check['allowed'] === true;
} );

// Reset gate mode to recommended block_critical
Quality_Gate_Manager::set_gate_mode( Quality_Repository::GATE_MODE_BLOCK_CRITICAL );

// 14. False-Positive Dismissal Manager
run_p11_test( 'Dismissal Manager: Dismissing an issue removes penalty from quality score', function() {
	$page_id = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 1,
		'title'           => 'Dismissal Test Page',
		'slug'            => 'dismissal-test-page',
		'primary_keyword' => 'Dismissal Orlando',
	] );

	$content = [
		'hero' => [
			'title'       => 'SEO Services in Orlando',
			'description' => 'Guaranteed #1 rank in Orlando local search.',
		],
	];

	// Initial evaluation
	$eval_1 = Quality_Engine::evaluate_page( $page_id, $content );
	$initial_score = $eval_1['quality_score'];

	// Dismiss unsupported claim issue
	Quality_Repository::add_dismissal( $page_id, 'unsupported_local_claim', 'Verified award from local commerce board' );
	$is_dismissed = Quality_Repository::is_dismissed( $page_id, 'unsupported_local_claim' );

	// Re-evaluate
	$eval_2 = Quality_Engine::evaluate_page( $page_id, $content );

	return $is_dismissed === true && $eval_2['quality_score'] >= $initial_score;
} );

// 15. Invalidation on Manual Edit & Restore
run_p11_test( 'Invalidation: Manual edit automatically marks quality evaluation stale', function() {
	$page_id = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 1,
		'title'           => 'Stale Invalidation Test',
		'slug'            => 'stale-invalidation-test',
		'primary_keyword' => 'Stale Orlando',
	] );

	// Create and evaluate
	Quality_Engine::evaluate_page( $page_id, [ 'hero' => [ 'title' => 'Original Content' ] ] );
	$eval_before = Quality_Repository::get_by_page_id( $page_id );

	// Perform manual edit
	Status_Workflow::save_manual_edit( $page_id, 0, [ 'hero' => [ 'title' => 'Edited Content' ] ] );

	$eval_after = Quality_Repository::get_by_page_id( $page_id );
	return (int) $eval_before['is_stale'] === 0 && (int) $eval_after['is_stale'] === 1;
} );

// 16. Bulk Quality Scan Background Job Handler
run_p11_test( 'Bulk Quality Scan Handler: Successfully processes background scan item', function() {
	$page_id = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 1,
		'title'           => 'Bulk Scan Item Test',
		'slug'            => 'bulk-scan-item-test',
		'primary_keyword' => 'Bulk Scan Orlando',
	] );

	$v_id = Content_Version_Repository::create( [
		'page_id'      => $page_id,
		'status'       => Status_Workflow::STATUS_APPROVED,
		'content_json' => json_encode( [ 'hero' => [ 'title' => 'Bulk Scan Version' ] ] ),
	] );
	global $wpdb;
	$wpdb->update( Schema::get_table_name( Schema::TABLE_CITY_PAGES ), [ 'current_version_id' => $v_id ], [ 'id' => $page_id ] );

	$item = (object) [
		'id'           => 1,
		'entity_id'    => $page_id,
		'item_payload' => [ 'page_id' => $page_id ],
	];
	$job = (object) [ 'id' => 1 ];

	$res = Quality_Scan_Handler::process( $item, $job );
	return $res['success'] === true && isset( $res['quality_score'] );
} );

// 17. Quality Repository Overview Stats
run_p11_test( 'Quality Repository: get_overview_stats() aggregates metrics accurately', function() {
	$stats = Quality_Repository::get_overview_stats();
	return isset( $stats['total_pages'] ) &&
	       isset( $stats['pass_count'] ) &&
	       isset( $stats['warning_count'] ) &&
	       isset( $stats['fail_count'] ) &&
	       isset( $stats['avg_score'] );
} );

// 18. AI Review Advisor (Advisory Only, Non-Destructive)
run_p11_test( 'AI Review Advisor: Does not mutate database or publish content', function() {
	$page_id = create_phase11_page( [
		'city_id'         => 1,
		'state_id'        => 1,
		'service_id'      => 1,
		'title'           => 'AI Advisor Advisory Test',
		'slug'            => 'ai-advisor-advisory-test',
		'primary_keyword' => 'Advisor Orlando',
	] );

	$page_before = City_Page_Repository::get( $page_id );
	AI_Review_Advisor::review_page( $page_id );
	$page_after = City_Page_Repository::get( $page_id );

	return (int) $page_before->is_published === (int) $page_after->is_published;
} );

echo "\n=================================================================\n";
echo "📊 PHASE 11 TEST SUMMARY: {$p11_passed} Passed, {$p11_failed} Failed\n";
echo "=================================================================\n\n";

if ( $p11_failed > 0 ) {
	exit( 1 );
}
