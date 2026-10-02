<?php
/**
 * Phase 9 Test Suite: Bulk Operations, CSV Import/Export & Scale Hardening.
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

// Include mock foundation from Phase 8.
require_once __DIR__ . '/test-phase8-seo-integration.php';

// Include Phase 9 files.
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/class-bulk-job-repository.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/Handlers/class-page-creation-handler.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/Handlers/class-content-generation-handler.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/Handlers/class-seo-generation-handler.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/Handlers/class-validation-handler.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/Handlers/class-approval-handler.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/Handlers/class-publish-handler.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/Handlers/class-csv-import-handler.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/Handlers/class-rollback-handler.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Jobs/class-bulk-job-runner.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Export/class-csv-exporter.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Admin/class-bulk-controller.php';

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\Migrator;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Jobs\Bulk_Job_Repository;
use SeoCityPages\Jobs\Bulk_Job_Runner;
use SeoCityPages\Import\CSV_Validator;
use SeoCityPages\Export\CSV_Exporter;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;
use SeoCityPages\Workflow\Status_Workflow;

echo "\n=================================================================\n";
echo "🧪 Running SEO City Pages Phase 9 Test Suite: Bulk Operations & Scale\n";
echo "=================================================================\n\n";

$p9_passed = 0;
$p9_failed = 0;

function run_p9_test( string $title, callable $callback ) {
	global $p9_passed, $p9_failed;
	try {
		$result = $callback();
		if ( true === $result ) {
			echo "✔ [PASS] {$title}\n";
			$p9_passed++;
		} else {
			echo "✘ [FAIL] {$title} (returned " . var_export( $result, true ) . ")\n";
			$p9_failed++;
		}
	} catch ( \Throwable $e ) {
		echo "✘ [ERROR] {$title} -> " . $e->getMessage() . " on line " . $e->getLine() . "\n";
		$p9_failed++;
	}
}

// 1. Database Schema v0.8.0+
run_p9_test( 'Database Migration: TARGET_DB_VERSION is >= 0.8.0', function() {
	return version_compare( Migrator::TARGET_DB_VERSION, '0.8.0', '>=' );
} );

// 2. Capabilities
Capabilities::register_defaults();
run_p9_test( 'Capabilities: Granular Phase 9 bulk capabilities are registered', function() {
	$all = Capabilities::get_all();
	return in_array( Capabilities::BULK_IMPORT, $all, true ) &&
	       in_array( Capabilities::BULK_EXPORT, $all, true ) &&
	       in_array( Capabilities::BULK_CREATE, $all, true ) &&
	       in_array( Capabilities::BULK_GENERATE, $all, true ) &&
	       in_array( Capabilities::BULK_APPROVE, $all, true ) &&
	       in_array( Capabilities::BULK_PUBLISH, $all, true ) &&
	       in_array( Capabilities::BULK_ROLLBACK, $all, true );
} );

// 3. CSV Preview Mode (Does NOT modify DB)
run_p9_test( 'CSV Preview: Analyzes structure and returns stats without modifying database', function() {
	$temp_file = tempnam( sys_get_temp_dir(), 'csv_prev_' );
	file_put_contents( $temp_file, "country,state,city,service\nUSA,Florida,JacksonvillePreview99,Web Design\n" );

	$preview = CSV_Validator::validate_file( $temp_file, true );
	unlink( $temp_file );

	$page = City_Page_Repository::get_by_slug( 'jacksonvillepreview99' );

	return $preview['success'] === true &&
	       $preview['total_rows'] === 1 &&
	       $preview['valid_count'] === 1 &&
	       $page === null; // Confirms zero DB modification
} );

// 4. CSV Formula Injection Neutralization
run_p9_test( 'CSV Security: Neutralizes formula injection on =, +, -, @', function() {
	$val1 = CSV_Validator::sanitize_formula_injection( '=SUM(A1:A10)' );
	$val2 = CSV_Validator::sanitize_formula_injection( '+cmd|/C calc' );
	$val3 = CSV_Validator::sanitize_formula_injection( '-2+3' );
	$val4 = CSV_Validator::sanitize_formula_injection( '@echo off' );
	$val5 = CSV_Validator::sanitize_formula_injection( 'Normal City Name' );

	return strpos( $val1, "'" ) === 0 &&
	       strpos( $val2, "'" ) === 0 &&
	       strpos( $val3, "'" ) === 0 &&
	       strpos( $val4, "'" ) === 0 &&
	       strpos( $val5, "'" ) === false;
} );

// 5. Data Quality Warnings on Suspicious Values
run_p9_test( 'CSV Validation: Flags Data Quality Warnings for suspicious location values without failing', function() {
	$temp_file = tempnam( sys_get_temp_dir(), 'csv_warn_' );
	file_put_contents( $temp_file, "country,state,city,service\nUSA,Out of State,Unknown,SEO Service\n" );

	$report = CSV_Validator::validate_file( $temp_file, false );
	unlink( $temp_file );

	return $report['success'] === true &&
	       $report['valid_count'] === 1 &&
	       $report['warnings_count'] === 1 &&
	       count( $report['data_warnings'][0]['warnings'] ) >= 2;
} );

// 6. Unknown Column Detection
run_p9_test( 'CSV Headers: Detects recognized vs unknown vs missing columns', function() {
	$header = [ 'country', 'state', 'city', 'service', 'custom_weird_column', 'another_random_col' ];
	$analysis = CSV_Validator::analyze_headers( $header );

	return count( $analysis['recognized'] ) === 4 &&
	       count( $analysis['unknown'] ) === 2 &&
	       empty( $analysis['missing_required'] );
} );

// 7. Duplicate Row Isolation
run_p9_test( 'CSV Validation: Isolates duplicate rows within CSV safely', function() {
	$temp_file = tempnam( sys_get_temp_dir(), 'csv_dupes_' );
	file_put_contents( $temp_file, "country,state,city,service\nUSA,California,San Francisco,SEO\nUSA,California,San Francisco,SEO\n" );

	$report = CSV_Validator::validate_file( $temp_file, false );
	unlink( $temp_file );

	return $report['total_rows'] === 2 &&
	       $report['valid_count'] === 1 &&
	       $report['duplicate_count'] === 1;
} );

// 8. Bulk Job Model & Granular Items Tracking
$bulk_job_id = '';
run_p9_test( 'Bulk Job Model: Creates job and granular items with atomic progress tracking', function() use ( &$bulk_job_id ) {
	$bulk_job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_BULK_CREATE,
		'total_items' => 5,
	] );

	$items = [];
	for ( $i = 1; $i <= 5; $i++ ) {
		$items[] = [
			'entity_type'  => 'location_combo',
			'action'       => 'create_page',
			'item_payload' => [ 'city_id' => $i, 'service_id' => 1 ],
		];
	}
	$inserted = Bulk_Job_Repository::create_items_batch( $bulk_job_id, $items );

	$job = Bulk_Job_Repository::get_job( $bulk_job_id );

	return ! empty( $bulk_job_id ) &&
	       $inserted === 5 &&
	       $job->status === 'pending' &&
	       (int) $job->total_items === 5;
} );

// 9. Atomic Concurrency Lock
run_p9_test( 'Concurrency Protection: Atomic claims prevent two workers from processing the same item', function() use ( $bulk_job_id ) {
	$worker_a = 'worker_alpha_' . uniqid();
	$worker_b = 'worker_beta_' . uniqid();

	$items_a = Bulk_Job_Repository::claim_pending_items( $bulk_job_id, 3, $worker_a );
	$items_b = Bulk_Job_Repository::claim_pending_items( $bulk_job_id, 3, $worker_b );

	// Worker A should get 3, Worker B should get remaining 2 (zero overlap)
	$ids_a = array_map( fn($item) => $item->id, $items_a );
	$ids_b = array_map( fn($item) => $item->id, $items_b );
	$intersection = array_intersect( $ids_a, $ids_b );

	return count( $items_a ) === 3 &&
	       count( $items_b ) === 2 &&
	       empty( $intersection );
} );

// 10. Pause / Resume / Cancel Controls
run_p9_test( 'Job Lifecycle: Pause, Resume, and Cancel status transitions operate cleanly', function() use ( $bulk_job_id ) {
	$paused = Bulk_Job_Runner::pause_job( $bulk_job_id );
	$job_p  = Bulk_Job_Repository::get_job( $bulk_job_id );

	$resumed = Bulk_Job_Runner::resume_job( $bulk_job_id );
	$job_r   = Bulk_Job_Repository::get_job( $bulk_job_id );

	$cancelled = Bulk_Job_Runner::cancel_job( $bulk_job_id );
	$job_c     = Bulk_Job_Repository::get_job( $bulk_job_id );

	return $paused && $job_p->status === 'paused' &&
	       $resumed && $job_r->status === 'running' &&
	       $cancelled && $job_c->status === 'cancelled';
} );

// 11. Stale Job Auto-Recovery
run_p9_test( 'Stale Recovery: Resets timed-out locked items back to pending', function() {
	global $wpdb;
	$stale_job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_BULK_CREATE,
		'total_items' => 1,
	] );

	Bulk_Job_Repository::create_items_batch( $stale_job_id, [
		[ 'entity_type' => 'city_page', 'action' => 'test_action' ]
	] );

	// Simulate worker crash by artificially setting locked_at to 1 hour ago
	$items_table = Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS );
	$past_time   = date( 'Y-m-d H:i:s', time() - 3600 );
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$items_table} SET status = 'processing', locked_at = %s, locked_by = 'crashed_worker', attempts = 1 WHERE job_id = %s",
			$past_time,
			$stale_job_id
		)
	);

	$recovered_count = Bulk_Job_Runner::run_stale_recovery( 600 ); // 10 min window

	$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$items_table} WHERE job_id = %s LIMIT 1", $stale_job_id ) );

	return $recovered_count >= 1 &&
	       $item->status === 'pending' &&
	       $item->locked_by === null;
} );

// 12. Retry Limits
run_p9_test( 'Retry Limits: Marks permanently failed when attempts reach max_attempts', function() {
	global $wpdb;
	$retry_job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_BULK_CREATE,
		'total_items' => 1,
	] );

	$item_id = Bulk_Job_Repository::create_item( [
		'job_id'       => $retry_job_id,
		'action'       => 'test_fail',
		'max_attempts' => 2,
	] );

	// Attempt 1 fail (should reset to pending for retry)
	Bulk_Job_Repository::fail_item( $item_id, 'err1', 'First error', true );
	$item1 = $wpdb->get_row( $wpdb->prepare( "SELECT status, attempts FROM " . Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS ) . " WHERE id = %d", $item_id ) );

	// Simulate attempt 2 claim & fail (reaches max_attempts 2 -> status = failed)
	$wpdb->query( "UPDATE " . Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS ) . " SET attempts = 2 WHERE id = {$item_id}" );
	Bulk_Job_Repository::fail_item( $item_id, 'err2', 'Second error', true );
	$item2 = $wpdb->get_row( $wpdb->prepare( "SELECT status, attempts FROM " . Schema::get_table_name( Schema::TABLE_BULK_JOB_ITEMS ) . " WHERE id = %d", $item_id ) );

	return $item1->status === 'pending' &&
	       $item2->status === 'failed';
} );

// 13. Bulk Page Creation with Existing Duplication Protection
$test_city_id = 0;
$test_service_id = 0;
$p9_created_page_id = 0;
run_p9_test( 'Bulk Page Creation: Creates new combinations and gracefully skips existing', function() use ( &$test_city_id, &$test_service_id, &$p9_created_page_id ) {
	$c = Country_Repository::get_by_name( 'United States' );
	$c_id = $c ? (int) $c->id : (int) Country_Repository::create( [ 'name' => 'United States', 'code' => 'US', 'status' => 'active' ] );

	$s = State_Repository::get_by_name( $c_id, 'Georgia' );
	$s_id = $s ? (int) $s->id : (int) State_Repository::create( [ 'country_id' => $c_id, 'name' => 'Georgia', 'code' => 'GA', 'status' => 'active' ] );

	$ci = City_Repository::get_by_name( $c_id, $s_id, 'Atlanta' );
	$ci_id = $ci ? (int) $ci->id : (int) City_Repository::create( [ 'country_id' => $c_id, 'state_id' => $s_id, 'name' => 'Atlanta', 'slug' => 'atlanta', 'status' => 'active' ] );

	$sv = Service_Repository::get_by_name( 'SEO Consultation' );
	$sv_id = $sv ? (int) $sv->id : (int) Service_Repository::create( [ 'name' => 'SEO Consultation', 'slug' => 'seo-consultation', 'status' => 'active' ] );

	global $wpdb;
	$table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
	$wpdb->delete( $table, [ 'country_id' => $c_id, 'state_id' => $s_id, 'city_id' => $ci_id, 'service_id' => $sv_id ] );

	$test_city_id    = (int) $ci_id;
	$test_service_id = (int) $sv_id;

	$job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_BULK_CREATE,
		'total_items' => 2,
	] );

	Bulk_Job_Repository::create_items_batch( $job_id, [
		[ 'item_payload' => [ 'city_id' => $test_city_id, 'service_id' => $test_service_id ] ],
		[ 'item_payload' => [ 'city_id' => $test_city_id, 'service_id' => $test_service_id ] ], // Duplicate combination
	] );

	$result = Bulk_Job_Runner::process_batch( $job_id, 10 );
	$created = City_Page_Repository::find_combination( $c_id, $s_id, $test_city_id, $test_service_id );
	if ( $created ) {
		$p9_created_page_id = (int) $created->id;
	}

	return $result['completed'] === 1 &&
	       $result['skipped'] === 1 &&
	       $p9_created_page_id > 0;
} );

// 14. Bulk AI Content Generation & Version History ($v_{N+1}$)
run_p9_test( 'Bulk AI Content Generation: Creates version in review and preserves history', function() use ( &$p9_created_page_id ) {
	global $mock_http_response;
	$mock_http_response = [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [
			'id'      => 'chatcmpl-mock-p9-gen',
			'choices' => [
				[
					'message'       => [
						'role'    => 'assistant',
						'content' => json_encode( [
							'hero'             => [
								'title'       => 'Premier Atlanta SEO Consultation Services',
								'subtitle'    => 'Dominate Search in Atlanta',
								'description' => 'Targeted Atlanta optimization with our SEO Consultation expertise.',
								'primary_cta' => 'Contact Us',
							],
							'introduction'     => [
								'heading' => 'Scale Inbound Organic Leads in Atlanta',
								'body'    => '<p>We provide full-spectrum local SEO Consultation services designed specifically for Atlanta enterprises.</p>',
							],
							'service_overview' => [
								'heading' => 'Full-Funnel Local SEO Consultation',
								'body'    => '<p>Targeted SEO Consultation keyword optimization and local citations.</p>',
							],
							'benefits'         => [
								[ 'title' => 'Google Maps 3-Pack', 'description' => 'Capture qualified local searches in Atlanta with our SEO Consultation.' ],
								[ 'title' => 'Granular Local Keywords', 'description' => 'Target high-converting queries across Georgia.' ],
								[ 'title' => 'Technical Optimization', 'description' => 'Schema and speed optimization for Atlanta businesses.' ],
							],
							'process'          => [
								[ 'step_number' => 1, 'title' => 'Audit & Roadmap', 'description' => 'Comprehensive Atlanta search audit and SEO Consultation.' ],
								[ 'step_number' => 2, 'title' => 'On-Page Optimization', 'description' => 'Targeted service keywords.' ],
								[ 'step_number' => 3, 'title' => 'Authority Building', 'description' => 'Local citation alignment.' ],
							],
							'local_relevance'  => [
								'heading' => 'Local Georgia Expertise',
								'body'    => '<p>Serving Atlanta and the wider Fulton County market with premier SEO Consultation.</p>',
							],
							'faqs'             => [
								[ 'question' => 'Why invest in Atlanta SEO Consultation?', 'answer' => 'Local SEO Consultation yields sustainable inbound customer acquisition in Atlanta.' ],
								[ 'question' => 'How are results tracked?', 'answer' => 'Transparent ranking, traffic, and conversion metrics.' ],
							],
							'final_cta'        => [
								'heading'     => 'Supercharge Your Atlanta Market Presence',
								'description' => 'Get a custom SEO Consultation performance analysis tailored to your market.',
								'button_text' => 'Request Free Analysis →',
							],
						] ),
					],
					'finish_reason' => 'stop',
				],
			],
			'usage'   => [ 'prompt_tokens' => 200, 'completion_tokens' => 300, 'total_tokens' => 500 ],
		] ),
	];

	$job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_BULK_GENERATE_CONTENT,
		'total_items' => 1,
	] );

	Bulk_Job_Repository::create_items_batch( $job_id, [
		[ 'entity_id' => $p9_created_page_id, 'action' => 'generate_content' ]
	] );

	$res = Bulk_Job_Runner::process_batch( $job_id, 10 );
	$v = Content_Version_Repository::get_latest_version( $p9_created_page_id );

	return $res['completed'] === 1 &&
	       $v !== null &&
	       $v->status === 'needs_review';
} );

// 15. Bulk Approval Execution
run_p9_test( 'Bulk Approval: Transitions needs_review content to approved status', function() use ( &$p9_created_page_id ) {
	$job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_BULK_APPROVE,
		'total_items' => 1,
	] );

	Bulk_Job_Repository::create_items_batch( $job_id, [
		[ 'entity_id' => $p9_created_page_id, 'action' => 'approve_content' ]
	] );

	$res  = Bulk_Job_Runner::process_batch( $job_id, 10 );
	$page = City_Page_Repository::get( $p9_created_page_id );

	return $res['completed'] === 1 &&
	       $page->generation_status === 'approved';
} );

// 16. Bulk Publishing Execution (STRICT: Only Approved Content)
run_p9_test( 'Bulk Publishing: Publishes approved page and syncs wp_post', function() use ( &$p9_created_page_id ) {
	$job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_BULK_PUBLISH,
		'total_items' => 1,
	] );

	Bulk_Job_Repository::create_items_batch( $job_id, [
		[ 'entity_id' => $p9_created_page_id, 'action' => 'publish_page' ]
	] );

	$res  = Bulk_Job_Runner::process_batch( $job_id, 10 );
	$page = City_Page_Repository::get( $p9_created_page_id );

	return $res['completed'] === 1 &&
	       $page->status === 'published' &&
	       $page->is_published == 1;
} );

// 17. Safe Rollback Strategy & Protection of Published Pages
run_p9_test( 'Rollback Protection: Prevents deletion of live published page (Requires Review)', function() use ( &$p9_created_page_id ) {
	$rb_job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_ROLLBACK,
		'total_items' => 1,
	] );

	Bulk_Job_Repository::create_items_batch( $rb_job_id, [
		[ 'entity_id' => $p9_created_page_id, 'action' => 'rollback_page' ]
	] );

	$res = Bulk_Job_Runner::process_batch( $rb_job_id, 10 );
	$page = City_Page_Repository::get( $p9_created_page_id );

	return $res['skipped'] === 1 &&
	       $page !== null; // Verified page was NOT deleted
} );

// 18. Safe Rollback of Unmodified Draft Pages
run_p9_test( 'Rollback Execution: Safely removes unmodified draft page', function() {
	$c = Country_Repository::get_by_name( 'United States' );
	$c_id = $c ? (int) $c->id : (int) Country_Repository::create( [ 'name' => 'United States', 'code' => 'US', 'status' => 'active' ] );

	$s = State_Repository::get_by_name( $c_id, 'Texas' );
	$s_id = $s ? (int) $s->id : (int) State_Repository::create( [ 'country_id' => $c_id, 'name' => 'Texas', 'code' => 'TX', 'status' => 'active' ] );

	$ci = City_Repository::get_by_name( $c_id, $s_id, 'Austin' );
	$ci_id = $ci ? (int) $ci->id : (int) City_Repository::create( [ 'country_id' => $c_id, 'state_id' => $s_id, 'name' => 'Austin', 'slug' => 'austin', 'status' => 'active' ] );

	$sv = Service_Repository::get_by_name( 'Digital Marketing' );
	$sv_id = $sv ? (int) $sv->id : (int) Service_Repository::create( [ 'name' => 'Digital Marketing', 'slug' => 'digital-marketing', 'status' => 'active' ] );

	$draft_page_id = City_Page_Repository::create( [
		'country_id'        => $c_id,
		'state_id'          => $s_id,
		'city_id'           => $ci_id,
		'service_id'        => $sv_id,
		'slug'              => 'austin',
		'url_path'          => '/digital-marketing/austin/',
		'status'            => 'draft',
		'generation_status' => 'not_generated',
	] );

	$rb_job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_ROLLBACK,
		'total_items' => 1,
	] );

	Bulk_Job_Repository::create_items_batch( $rb_job_id, [
		[ 'entity_id' => $draft_page_id, 'action' => 'rollback_page' ]
	] );

	$res = Bulk_Job_Runner::process_batch( $rb_job_id, 10 );
	$page = City_Page_Repository::get( $draft_page_id );

	return $res['completed'] === 1 &&
	       $page === null; // Verified page was deleted cleanly
} );

// 19. Streamed CSV Export (Memory-Safe)
run_p9_test( 'CSV Export: Streamed chunk export handles datasets without memory overflow', function() {
	$temp_file = CSV_Exporter::export( CSV_Exporter::TYPE_CITY_PAGES, [], 'temp' );

	$file_exists = file_exists( $temp_file );
	$content = file_get_contents( $temp_file );
	unlink( $temp_file );

	return $file_exists &&
	       strpos( $content, 'Page ID' ) !== false &&
	       strpos( $content, 'Country' ) !== false;
} );

// 20. Large Scale Synthetic Benchmark (1,000 items processing)
run_p9_test( 'Scale Hardening: 1,000 items queued and processed in batches through runner', function() {
	$scale_job_id = Bulk_Job_Repository::create_job( [
		'job_type'    => Bulk_Job_Repository::TYPE_BULK_CREATE,
		'total_items' => 1000,
		'is_dry_run'  => 1,
	] );

	$items = [];
	for ( $i = 1; $i <= 1000; $i++ ) {
		$items[] = [
			'entity_type'  => 'location_combo',
			'action'       => 'create_page',
			'item_payload' => [ 'city_id' => 1, 'service_id' => 1 ],
		];
	}

	Bulk_Job_Repository::create_items_batch( $scale_job_id, $items );

	// Process 20 chunks of 50 = 1000 items
	$total_processed = 0;
	for ( $chunk = 0; $chunk < 20; $chunk++ ) {
		$res = Bulk_Job_Runner::process_batch( $scale_job_id, 50 );
		$total_processed += $res['processed_in_batch'];
	}

	$job = Bulk_Job_Repository::get_job( $scale_job_id );

	return $total_processed === 1000 &&
	       in_array( $job->status, [ 'completed', 'completed_errors' ], true ) &&
	       (int) $job->completed_items + (int) $job->skipped_items === 1000;
} );

echo "\n=================================================================\n";
echo "📊 PHASE 9 TEST SUMMARY: {$p9_passed} Passed, {$p9_failed} Failed\n";
echo "=================================================================\n\n";

if ( $p9_failed > 0 ) {
	exit( 1 );
}
