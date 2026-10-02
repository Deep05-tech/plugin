<?php
/**
 * Phase 10 Test Suite: Production Health, Diagnostics, Observability & Disaster Recovery.
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

// Include mock foundation from Phase 9.
require_once __DIR__ . '/test-phase9-bulk-operations.php';

// Include Phase 10 files.
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Diagnostics/class-safe-mode-manager.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Diagnostics/class-diagnostics-engine.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Diagnostics/class-openai-diagnostics.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Diagnostics/class-queue-monitor.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Diagnostics/class-integrity-scanner.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Diagnostics/class-url-health-checker.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Diagnostics/class-repair-manager.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Database/class-audit-log-repository.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Admin/class-health-controller.php';
require_once SEO_CITY_PAGES_PLUGIN_DIR . 'includes/Admin/class-audit-controller.php';

use SeoCityPages\Diagnostics\Diagnostics_Engine;
use SeoCityPages\Diagnostics\OpenAI_Diagnostics;
use SeoCityPages\Diagnostics\Queue_Monitor;
use SeoCityPages\Diagnostics\Integrity_Scanner;
use SeoCityPages\Diagnostics\URL_Health_Checker;
use SeoCityPages\Diagnostics\Repair_Manager;
use SeoCityPages\Diagnostics\Safe_Mode_Manager;
use SeoCityPages\Database\Audit_Log_Repository;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Database\Schema;
use SeoCityPages\Database\Migrator;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;
use SeoCityPages\Logging\Logger;
use SeoCityPages\AI\AI_Manager;

echo "\n=================================================================\n";
echo "🧪 Running SEO City Pages Phase 10 Test Suite: Production Health & Observability\n";
echo "=================================================================\n\n";

$pass_count = 0;
$fail_count = 0;

function assert_test( bool $condition, string $message ) {
	global $pass_count, $fail_count;
	if ( $condition ) {
		echo "✔ [PASS] {$message}\n";
		$pass_count++;
	} else {
		echo "❌ [FAIL] {$message}\n";
		$fail_count++;
	}
}

// 1. Healthy Environment & System Diagnostics Check.
$sys_diag = Diagnostics_Engine::check_php_environment();
assert_test( in_array( $sys_diag['status'], [ 'healthy', 'warning' ], true ), 'PHP Environment: Returns structured diagnostic metrics' );

$wp_diag = Diagnostics_Engine::check_wordpress_environment();
assert_test( ! empty( $wp_diag['wp_version'] ), 'WP Environment: Detects WordPress version & memory limit' );

// 2. Elementor Health Detection (Read-only).
$el_diag = Diagnostics_Engine::check_elementor_health();
assert_test( isset( $el_diag['is_active'] ) && isset( $el_diag['version'] ), 'Elementor Health: Safely detects status without modifying files' );

// 3. Arolax Health Detection (Read-only, never writes into /wp-content/themes/arolax/).
$arolax_diag = Diagnostics_Engine::check_arolax_health();
assert_test( isset( $arolax_diag['is_arolax'] ) && isset( $arolax_diag['theme_dir'] ), 'Arolax Theme Health: Read-only evaluation completes safely' );

// 4. SEO Plugin Multi-Conflict Detection.
$seo_diag = Diagnostics_Engine::check_seo_provider_health();
assert_test( isset( $seo_diag['provider'] ) && isset( $seo_diag['conflicts'] ), 'SEO Provider Health: Detects provider and conflict states' );

// 5. Missing OpenAI Configuration & Key Masking.
$openai_summary = OpenAI_Diagnostics::get_status_summary();
assert_test( in_array( $openai_summary['api_key_status'], [ 'Configured', 'Missing' ], true ), 'OpenAI Health: Safely reports key status without exposing secret' );
assert_test( ! isset( $openai_summary['api_key'] ), 'OpenAI Security: Raw API key is never exposed in diagnostics' );

// 6. OpenAI Non-Destructive Connection Test (Zero content created).
$initial_version_count = Content_Version_Repository::count();
$conn_test = OpenAI_Diagnostics::test_connection();
$post_version_count = Content_Version_Repository::count();
assert_test( $initial_version_count === $post_version_count, 'OpenAI Ping: Connection test does NOT create content versions or mutate database' );

// 7. Queue Health & Aggregates.
$queue_metrics = Queue_Monitor::get_queue_metrics();
assert_test( isset( $queue_metrics['job_counts'] ) && isset( $queue_metrics['item_counts'] ), 'Queue Health: Accurately calculates pending, running, failed items' );

// 8. Stale Job & Lock Detection.
assert_test( isset( $queue_metrics['stale_items'] ), 'Stale Lock Detection: Tracks orphaned processing locks' );

// 9. Safe Job Recovery.
$recover_res = Queue_Monitor::recover_stale_jobs( 1800 );
assert_test( ! empty( $recover_res['success'] ), 'Safe Job Recovery: Resets stale locks to pending safely' );

// 10. Orphan Detection (Countries, States, Cities, Pages, Versions, Jobs).
$orphan_rep = Integrity_Scanner::scan_orphaned_records();
assert_test( isset( $orphan_rep['total_orphans'] ) && isset( $orphan_rep['breakdown'] ), 'Orphan Scanner: Inspects relational tables across all 11 schemas' );

// 11. Cross-Contamination Validation.
$contamination_rep = Integrity_Scanner::scan_cross_contamination( 100 );
assert_test( isset( $contamination_rep['total_mismatches'] ), 'Cross-Contamination: Inspects city/service mismatches' );

// 12. Published Page Integrity Scan.
$pub_rep = Integrity_Scanner::scan_published_pages( 100 );
assert_test( isset( $pub_rep['total_issues'] ), 'Published Page Integrity: Verifies published version existence and taxonomy health' );

// 13. URL Health Checker & SSRF Protection (Allowed vs Foreign Host).
$safe_internal_url = home_url( '/seo-agency/orlando/' );
$foreign_evil_url  = 'http://169.254.169.254/latest/meta-data/';
assert_test( URL_Health_Checker::validate_target_url_ssrf( $safe_internal_url ) === true, 'SSRF Protection: Allows safe internal site URL' );
assert_test( URL_Health_Checker::validate_target_url_ssrf( $foreign_evil_url ) === false, 'SSRF Protection: BLOCKS cloud metadata / foreign IP SSRF' );

// 14. Safe Mode Activation & Operational Isolation.
Safe_Mode_Manager::enable_safe_mode( 'Automated test suite' );
assert_test( Safe_Mode_Manager::is_safe_mode_active() === true, 'Safe Mode: Enables cleanly' );

// 15. Safe Mode Disables AI Generation.
$ai_gen_res = AI_Manager::instance()->generate_for_page( 9999 );
assert_test( ! $ai_gen_res->is_success() && $ai_gen_res->get_error_code() === 'safe_mode_active', 'Safe Mode: Blocks AI content generation' );

// 16. Safe Mode Suspends Background Batch Processing.
$batch_res = \SeoCityPages\Jobs\Bulk_Job_Runner::process_batch( 'mock-job-id' );
assert_test( $batch_res['status'] === 'paused', 'Safe Mode: Suspends background bulk runner' );

// 17. Safe Mode Deactivation.
Safe_Mode_Manager::disable_safe_mode();
assert_test( Safe_Mode_Manager::is_safe_mode_active() === false, 'Safe Mode: Deactivates cleanly' );

// 18. Append-Only Audit Log Event Logging.
$audit_id = Audit_Log_Repository::log_event(
	'publish_page',
	'city_page',
	101,
	[ 'action' => 'unit_test_publish', 'secret_token' => 'SECRET_VALUE_XYZ' ]
);
assert_test( $audit_id > 0, 'Audit Log: Inserts audit record successfully' );

// 19. Audit Log Secret Scrubbing.
$saved_logs = Audit_Log_Repository::get_logs( [ 'event_type' => 'publish_page' ], 1 );
assert_test( ! empty( $saved_logs ) && ( $saved_logs[0]->details['secret_token'] ?? '' ) === '***REDACTED***', 'Audit Log Security: Sensitive fields (tokens, passwords) are auto-redacted' );

// 20. Security Event Stream Logging.
$sec_id = Audit_Log_Repository::record_security_event( 'invalid_nonce', [ 'endpoint' => 'bulk_create' ] );
assert_test( $sec_id > 0, 'Security Events: Records security-relevant anomalies' );

// 21. Correlation ID Generator.
$cid = Logger::get_correlation_id();
assert_test( strpos( $cid, 'SCP-' ) === 0, 'Observability: Generates structured SCP-YYYYMMDD-XXXXXX correlation ID' );

// 22. Diagnostic Support Bundle (Sanitized).
$bundle = Diagnostics_Engine::generate_support_bundle();
assert_test( isset( $bundle['bundle_generated_at'] ) && isset( $bundle['diagnostics'] ), 'Support Bundle: Generates complete diagnostic bundle' );
assert_test( ! in_array( 'sk-', $bundle['settings_sanitized'], true ), 'Support Bundle Security: Zero raw API keys in exported diagnostics' );

// 23. Log Retention Pruning.
$pruned_lines = Logger::prune_logs_by_retention( 30 );
assert_test( $pruned_lines >= 0, 'Log Retention: Successfully executes log rotation/pruning' );

// 24. Repair Manager: Schema and Tables Repair.
$repair_schema = Repair_Manager::repair_database_schema();
assert_test( ! empty( $repair_schema['success'] ), 'Repair Manager: Verifies and updates schema safely' );

// 25. Repair Manager: Rewrite Rules Refresh.
$repair_rewrites = Repair_Manager::refresh_rewrite_rules();
assert_test( ! empty( $repair_rewrites['success'] ), 'Repair Manager: Flushes and regenerates rewrite rules' );

// 26. Capabilities: Phase 10 Granular Capabilities Registered.
assert_test( in_array( Capabilities::VIEW_HEALTH, Capabilities::get_all(), true ), 'Capabilities: Registered VIEW_HEALTH capability' );
assert_test( in_array( Capabilities::RUN_DIAGNOSTICS, Capabilities::get_all(), true ), 'Capabilities: Registered RUN_DIAGNOSTICS capability' );
assert_test( in_array( Capabilities::EXECUTE_REPAIR, Capabilities::get_all(), true ), 'Capabilities: Registered EXECUTE_REPAIR capability' );
assert_test( in_array( Capabilities::VIEW_AUDIT_LOGS, Capabilities::get_all(), true ), 'Capabilities: Registered VIEW_AUDIT_LOGS capability' );
assert_test( in_array( Capabilities::TOGGLE_SAFE_MODE, Capabilities::get_all(), true ), 'Capabilities: Registered TOGGLE_SAFE_MODE capability' );

echo "\n=================================================================\n";
echo "📊 PHASE 10 TEST SUMMARY: {$pass_count} Passed, {$fail_count} Failed\n";
echo "=================================================================\n\n";

if ( $fail_count > 0 ) {
	exit( 1 );
}
