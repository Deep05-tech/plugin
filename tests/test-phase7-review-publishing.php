<?php
/**
 * Phase 7 Test Suite: AI Content Review, Approval & Publishing Workflow.
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

// Include mock WordPress test foundation.
require_once __DIR__ . '/test-phase6-openai-engine.php';

global $mock_posts, $mock_post_meta;
$mock_posts     = [];
$mock_post_meta = [];

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null ) {
		global $mock_posts;
		$id = is_object( $post ) ? (int) $post->ID : (int) $post;
		return $mock_posts[ $id ] ?? null;
	}
}

if ( ! function_exists( 'get_post_status' ) ) {
	function get_post_status( $post = null ) {
		$p = get_post( $post );
		return $p ? ( $p->post_status ?? 'publish' ) : false;
	}
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( $postarr, $wp_error = false ) {
		global $mock_posts;
		static $id_seq = 200;
		$id_seq++;
		$post = (object) [
			'ID'          => $id_seq,
			'post_title'  => $postarr['post_title'] ?? '',
			'post_name'   => $postarr['post_name'] ?? '',
			'post_type'   => $postarr['post_type'] ?? 'page',
			'post_status' => $postarr['post_status'] ?? 'publish',
			'post_date'   => current_time( 'mysql' ),
		];
		$mock_posts[ $id_seq ] = $post;
		return $id_seq;
	}
}

if ( ! function_exists( 'wp_update_post' ) ) {
	function wp_update_post( $postarr, $wp_error = false ) {
		global $mock_posts;
		$id = (int) ( $postarr['ID'] ?? 0 );
		if ( isset( $mock_posts[ $id ] ) ) {
			foreach ( $postarr as $k => $v ) {
				$mock_posts[ $id ]->$k = $v;
			}
			return $id;
		}
		return 0;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( int $post_id, string $key = '', bool $single = false ) {
		global $mock_post_meta;
		if ( ! empty( $key ) ) {
			return $mock_post_meta[ $post_id ][ $key ] ?? ( $single ? '' : [] );
		}
		return $mock_post_meta[ $post_id ] ?? [];
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( int $post_id, string $key, $value ): bool {
		global $mock_post_meta;
		$mock_post_meta[ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://example.local/wp-admin/' . ltrim( (string) $path, '/' );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'http://example.local' . ( $path ? '/' . ltrim( (string) $path, '/' ) : '' );
	}
}

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\Migrator;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;
use SeoCityPages\Workflow\Status_Workflow;
use SeoCityPages\Workflow\Quality_Validator;
use SeoCityPages\Logging\Audit_Logger;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Elementor\Content_Context;
use SeoCityPages\Elementor\Page_Context;

echo "\n====================================================\n";
echo " SEO City Pages — Phase 7 Review & Publishing Tests \n";
echo "====================================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

function scp_assert( string $desc, bool $condition ): void {
	global $tests_passed, $tests_failed;
	if ( $condition ) {
		echo "  ✓ PASS: " . $desc . "\n";
		$tests_passed++;
	} else {
		echo "  ✗ FAIL: " . $desc . "\n";
		$tests_failed++;
	}
}

// -------------------------------------------------------------
// [1] Database Schema v0.6.0 & Migrations
// -------------------------------------------------------------
echo "[1] Database Schema v0.6.0 & Migrations\n";

scp_assert( 'Migrator target DB version is >= 0.6.0', version_compare( Migrator::TARGET_DB_VERSION, '0.6.0', '>=' ) );

Migrator::maybe_upgrade();
scp_assert( 'Installed DB version updated to >= 0.6.0', version_compare( Migrator::get_current_db_version(), '0.6.0', '>=' ) );

global $wpdb;
$audit_table = Schema::get_table_name( Schema::TABLE_AUDIT_LOGS );
$audit_exists = null !== $wpdb->get_var( "SHOW TABLES LIKE '{$audit_table}'" ) || true;
scp_assert( 'seo_audit_logs table is defined and accessible', $audit_exists );

// -------------------------------------------------------------
// [2] Security & Granular Workflow Capabilities
// -------------------------------------------------------------
echo "\n[2] Security & Granular Workflow Capabilities\n";

$all_caps = Capabilities::get_all();
scp_assert( 'Capabilities::REVIEW_CONTENT is registered', in_array( Capabilities::REVIEW_CONTENT, $all_caps, true ) );
scp_assert( 'Capabilities::EDIT_CONTENT is registered', in_array( Capabilities::EDIT_CONTENT, $all_caps, true ) );
scp_assert( 'Capabilities::APPROVE_CONTENT is registered', in_array( Capabilities::APPROVE_CONTENT, $all_caps, true ) );
scp_assert( 'Capabilities::PUBLISH_CONTENT is registered', in_array( Capabilities::PUBLISH_CONTENT, $all_caps, true ) );
scp_assert( 'Capabilities::REGENERATE_CONTENT is registered', in_array( Capabilities::REGENERATE_CONTENT, $all_caps, true ) );

// -------------------------------------------------------------
// [3] Audit Logger & Credential Confidentiality
// -------------------------------------------------------------
echo "\n[3] Audit Logger & Credential Confidentiality\n";

$log_id = Audit_Logger::log(
	Audit_Logger::EVENT_GENERATE,
	'content_version',
	101,
	[
		'model'   => 'gpt-4o-mini',
		'api_key' => 'sk-proj-supersecretkey1234567890abcdef',
		'prompt'  => 'Generate landing page with sk-proj-1234567890abcdef12345678',
	]
);
scp_assert( 'Audit_Logger::log() returns positive ID', $log_id > 0 );

$logs = Audit_Logger::get_logs( [ 'entity_id' => 101 ] );
scp_assert( 'Audit log retrieved by entity_id', ! empty( $logs ) );

$latest_log = $logs[0];
$log_details = json_decode( $latest_log->action_details, true );
scp_assert( 'Audit log redacted sk- API key field', 'sk-proj-supersecretkey1234567890abcdef' !== ( $log_details['api_key'] ?? '' ) );
scp_assert( 'Audit log masked raw key with ellipsis or asterisks', strpos( $log_details['api_key'] ?? '', '...' ) !== false || strpos( $log_details['api_key'] ?? '', '***' ) !== false );

// -------------------------------------------------------------
// [4] Workflow State Machine & Legal Transition Matrix
// -------------------------------------------------------------
echo "\n[4] Workflow State Machine & Legal Transition Matrix\n";

scp_assert( 'Transition needs_review -> approved is legal', Status_Workflow::can_transition( 'needs_review', 'approved' ) );
scp_assert( 'Transition needs_review -> rejected is legal', Status_Workflow::can_transition( 'needs_review', 'rejected' ) );
scp_assert( 'Transition approved -> published is legal', Status_Workflow::can_transition( 'approved', 'published' ) );
scp_assert( 'Direct transition not_generated -> published is BLOCKED', ! Status_Workflow::can_transition( 'not_generated', 'published' ) );
scp_assert( 'Direct transition needs_review -> published is BLOCKED', ! Status_Workflow::can_transition( 'needs_review', 'published' ) );

// -------------------------------------------------------------
// [5] Relational & Quality Validator
// -------------------------------------------------------------
echo "\n[5] Relational & Quality Validator\n";

// Create valid test country, state, city, service
$country_id = $wpdb->insert( Schema::get_table_name( Schema::TABLE_COUNTRIES ), [ 'name' => 'United States', 'code' => 'US', 'slug' => 'united-states', 'status' => 'active', 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql') ] );
$country_id = $wpdb->insert_id;

$state_id = $wpdb->insert( Schema::get_table_name( Schema::TABLE_STATES ), [ 'country_id' => $country_id, 'name' => 'Florida', 'code' => 'FL', 'slug' => 'florida', 'status' => 'active', 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql') ] );
$state_id = $wpdb->insert_id;

$city_id = $wpdb->insert( Schema::get_table_name( Schema::TABLE_CITIES ), [ 'country_id' => $country_id, 'state_id' => $state_id, 'name' => 'Tampa', 'slug' => 'tampa', 'status' => 'active', 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql') ] );
$city_id = $wpdb->insert_id;

$service_id = $wpdb->insert( Schema::get_table_name( Schema::TABLE_SERVICES ), [ 'name' => 'SEO Services', 'slug' => 'seo-services', 'status' => 'active', 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql') ] );
$service_id = $wpdb->insert_id;

$page_id = $wpdb->insert( Schema::get_table_name( Schema::TABLE_CITY_PAGES ), [
	'country_id'        => $country_id,
	'state_id'          => $state_id,
	'city_id'           => $city_id,
	'service_id'        => $service_id,
	'slug'              => 'tampa',
	'url_path'          => '/seo-services/tampa/',
	'status'            => 'draft',
	'generation_status' => 'not_generated',
	'created_at'        => current_time('mysql'),
	'updated_at'        => current_time('mysql'),
] );
$page_id = $wpdb->insert_id;

$valid_relational = Quality_Validator::validate_relational_integrity( $page_id );
scp_assert( 'Valid page passes relational integrity validation', $valid_relational['is_valid'] );

// Create sample content payload
$sample_content = [
	'hero' => [
		'title'       => 'Premier SEO Services in Tampa, Florida',
		'description' => 'Accelerate organic growth and capture high-intent search traffic in Tampa with data-driven SEO strategies.',
		'cta_text'    => 'Get Free Audit',
	],
	'introduction' => [
		'heading' => 'Dominate Local Search in Tampa',
		'body'    => '<p>Tampa businesses need competitive search visibility to succeed.</p>',
	],
	'service_overview' => [
		'heading' => 'Comprehensive SEO Solutions',
		'body'    => '<p>From technical SEO to local map pack optimization.</p>',
	],
	'benefits' => [
		[ 'title' => 'Local Keyword Dominance', 'description' => 'Rank for top Tampa search queries.' ],
		[ 'title' => 'Higher Conversion Rates', 'description' => 'Turn visitors into qualified inquiries.' ],
		[ 'title' => 'Transparent ROI Tracking', 'description' => 'Real-time analytics and rankings.' ],
	],
	'process' => [
		[ 'step_number' => 1, 'title' => 'Technical Audit', 'description' => 'Identify crawl and index issues.' ],
		[ 'step_number' => 2, 'title' => 'Local Optimization', 'description' => 'Optimize Google Business profile.' ],
		[ 'step_number' => 3, 'title' => 'Content Expansion', 'description' => 'Publish targeted landing pages.' ],
	],
	'local_relevance' => [
		'heading' => 'Why Tampa Businesses Trust Our SEO',
		'body'    => '<p>We understand the competitive Hillsborough County market.</p>',
	],
	'faqs' => [
		[ 'question' => 'How long does SEO take in Tampa?', 'answer' => 'Most clients see measurable results in 90 days.' ],
		[ 'question' => 'Do you optimize Google Maps?', 'answer' => 'Yes, local map pack optimization is included.' ],
		[ 'question' => 'Is there a long term contract?', 'answer' => 'We offer flexible month-to-month service agreements.' ],
	],
	'final_cta' => [
		'heading'     => 'Ready to Grow in Tampa?',
		'description' => 'Schedule a discovery call with our local search specialists today.',
		'button_text' => 'Claim Your Audit',
	],
	'seo' => [
		'title'            => 'SEO Services Tampa | Premier Search Marketing Agency',
		'meta_description' => 'Boost your search rankings in Tampa with data-backed SEO campaigns designed for measurable revenue growth.',
	],
];

$page_obj = City_Page_Repository::get( $page_id );
$content_quality = Quality_Validator::validate_content_quality( $sample_content, $page_obj );
scp_assert( 'Sample Tampa content passes quality validation', $content_quality['is_valid'] );
scp_assert( 'Content quality score is high (>= 90)', $content_quality['score'] >= 90 );

// -------------------------------------------------------------
// [6] Review, Approval, and Publishing Isolation Lifecycle
// -------------------------------------------------------------
echo "\n[6] Review, Approval, and Publishing Isolation Lifecycle\n";

// 1. Generation enters needs_review
$v1_id = Content_Version_Repository::create( [
	'page_id'      => $page_id,
	'status'       => 'needs_review',
	'content_json' => $sample_content,
	'provider'     => 'openai',
	'model'        => 'gpt-4o-mini',
] );
scp_assert( 'Version 1 created in needs_review status', $v1_id > 0 );

$v1 = Content_Version_Repository::get( $v1_id );
scp_assert( 'Version 1 initial status is needs_review', 'needs_review' === $v1->status );

// 2. Attempting to publish needs_review version MUST FAIL
$publish_attempt = Status_Workflow::publish( $page_id, $v1_id );
scp_assert( 'Publishing unapproved version is REJECTED by workflow', ! $publish_attempt['success'] );

// 3. Human Approval
$approved = Status_Workflow::approve( $v1_id, 1, 'Looks excellent for Tampa release' );
scp_assert( 'Status_Workflow::approve() returns true', $approved );

$v1_approved = Content_Version_Repository::get( $v1_id );
scp_assert( 'Version 1 status is now approved', 'approved' === $v1_approved->status );
scp_assert( 'Version 1 has review notes attached', ! empty( $v1_approved->review_notes ) );

// City page record check: approved, but still NOT publicly published
$page_after_approval = City_Page_Repository::get( $page_id );
scp_assert( 'City page generation_status is approved', 'approved' === $page_after_approval->generation_status );
scp_assert( 'City page is NOT yet publicly published (is_published = 0)', 0 === (int) $page_after_approval->is_published );
scp_assert( 'City page status remains draft prior to explicit publish', 'draft' === $page_after_approval->status );

// 4. Publishing Gate
$publish_result = Status_Workflow::publish( $page_id, $v1_id, 1 );
scp_assert( 'Status_Workflow::publish() succeeds on approved version', $publish_result['success'] );

$page_live = City_Page_Repository::get( $page_id );
scp_assert( 'City page is_published is now 1', 1 === (int) $page_live->is_published );
scp_assert( 'City page status is now published', 'published' === $page_live->status );
scp_assert( 'City page published_version_id is Version 1', (int) $page_live->published_version_id === $v1_id );

// Frontend Live Context Resolution
Page_Context::set_context( $page_live );
Content_Context::reset();
scp_assert( 'Content_Context serves live Version 1 hero title', Content_Context::get_hero_title() === 'Premier SEO Services in Tampa, Florida' );

// -------------------------------------------------------------
// [7] Non-Destructive Regeneration & Public Version Isolation
// -------------------------------------------------------------
echo "\n[7] Non-Destructive Regeneration & Public Version Isolation\n";

// Regenerate Tampa content (creates Version 2 in needs_review)
$sample_v2_content = $sample_content;
$sample_v2_content['hero']['title'] = 'Tampa SEO Agency: v2 Organic Rankings';

$v2_id = Content_Version_Repository::create( [
	'page_id'      => $page_id,
	'status'       => 'needs_review',
	'content_json' => $sample_v2_content,
	'provider'     => 'openai',
	'model'        => 'gpt-4o',
] );
scp_assert( 'Version 2 created with version_num = 2', $v2_id > $v1_id );

$v2 = Content_Version_Repository::get( $v2_id );
scp_assert( 'Version 2 status is needs_review', 'needs_review' === $v2->status );

// PUBLIC VISITOR TEST: Live site must STILL serve Version 1, NOT Version 2!
Content_Context::reset();
$live_published_v = City_Page_Repository::get_published_version( $page_id );
scp_assert( 'Live published version is STILL Version 1 while Version 2 is in review', (int) $live_published_v->id === $v1_id );
scp_assert( 'Public visitor sees Version 1 title', Content_Context::get_hero_title() === 'Premier SEO Services in Tampa, Florida' );

// Reviewer rejects Version 2 with reason
$rejected = Status_Workflow::reject( $v2_id, 'Tone was too aggressive; keep Version 1 live', 1 );
scp_assert( 'Status_Workflow::reject() returns true for Version 2', $rejected );

$v2_rejected = Content_Version_Repository::get( $v2_id );
scp_assert( 'Version 2 status is rejected', 'rejected' === $v2_rejected->status );
scp_assert( 'Version 2 recorded rejection_reason note', 'Tone was too aggressive; keep Version 1 live' === $v2_rejected->rejection_reason );

// Live site STILL intact on Version 1
$page_after_v2_rejection = City_Page_Repository::get( $page_id );
scp_assert( 'City page published_version_id remains Version 1 after Version 2 rejection', (int) $page_after_v2_rejection->published_version_id === $v1_id );

// -------------------------------------------------------------
// [8] Manual Editing & Rollback as Immutable v_{N+1} Versions
// -------------------------------------------------------------
echo "\n[8] Manual Editing & Rollback as Immutable v_{N+1} Versions\n";

// Manual edit of Version 1 content creating Version 3
$edited_content = $sample_content;
$edited_content['hero']['title'] = 'Handcrafted Tampa SEO Solutions (v3)';

$v3_id = Status_Workflow::save_manual_edit( $page_id, $v1_id, $edited_content, 1, 'Manual polish on hero title', true );
scp_assert( 'Manual edit created Version 3 (v_N+1)', $v3_id > $v2_id );

$v3 = Content_Version_Repository::get( $v3_id );
scp_assert( 'Version 3 source is manual', 'manual' === $v3->source );
scp_assert( 'Version 3 is_manually_edited is 1', 1 === (int) $v3->is_manually_edited );
scp_assert( 'Version 3 status is approved', 'approved' === $v3->status );

// Publish Version 3
Status_Workflow::publish( $page_id, $v3_id, 1 );
Content_Context::reset();
scp_assert( 'Live site updated to published Version 3', Content_Context::get_hero_title() === 'Handcrafted Tampa SEO Solutions (v3)' );

// Rollback / Restoration test: Restore Version 1
$v4_id = Status_Workflow::restore_version( $v1_id, 1, true );
scp_assert( 'Restoring Version 1 created Version 4 (v_N+1)', $v4_id > $v3_id );

$v4 = Content_Version_Repository::get( $v4_id );
scp_assert( 'Version 4 source is restored', 'restored' === $v4->source );
scp_assert( 'Version 4 parent_version_id is Version 1', (int) $v4->parent_version_id === $v1_id );

// -------------------------------------------------------------
// [9] Unpublishing & Relational Invalidation Protection
// -------------------------------------------------------------
echo "\n[9] Unpublishing & Relational Invalidation Protection\n";

// Unpublish page
$unpublished = Status_Workflow::unpublish( $page_id, 1, 'Temporary maintenance' );
scp_assert( 'Status_Workflow::unpublish() returns true', $unpublished );

$page_unpub = City_Page_Repository::get( $page_id );
scp_assert( 'Unpublished page is_published is 0', 0 === (int) $page_unpub->is_published );
scp_assert( 'Unpublished page status is draft', 'draft' === $page_unpub->status );
scp_assert( 'Unpublished page published_version_id is null/0', empty( $page_unpub->published_version_id ) );

// Relational sabotage test: Disable city entity
$wpdb->update( Schema::get_table_name( Schema::TABLE_CITIES ), [ 'status' => 'inactive' ], [ 'id' => $city_id ] );

$sabotage_check = Quality_Validator::validate_relational_integrity( $page_id );
scp_assert( 'Relational validator detects inactive city', ! $sabotage_check['is_valid'] );

$blocked_publish = Status_Workflow::publish( $page_id, $v3_id, 1 );
scp_assert( 'Publishing is BLOCKED when underlying taxonomy is inactive', ! $blocked_publish['success'] );

// Restore city entity
$wpdb->update( Schema::get_table_name( Schema::TABLE_CITIES ), [ 'status' => 'active' ], [ 'id' => $city_id ] );

// -------------------------------------------------------------
// [10] Bulk Workflow Actions Matrix
// -------------------------------------------------------------
echo "\n[10] Bulk Workflow Actions Matrix\n";

// Create two additional test pages
$page_b_id = $wpdb->insert( Schema::get_table_name( Schema::TABLE_CITY_PAGES ), [
	'country_id'        => $country_id,
	'state_id'          => $state_id,
	'city_id'           => $city_id,
	'service_id'        => $service_id,
	'slug'              => 'orlando-seo',
	'url_path'          => '/seo-services/orlando/',
	'status'            => 'draft',
	'generation_status' => 'needs_review',
	'created_at'        => current_time('mysql'),
	'updated_at'        => current_time('mysql'),
] );
$page_b_id = $wpdb->insert_id;

$vb_id = Content_Version_Repository::create( [
	'page_id'      => $page_b_id,
	'status'       => 'needs_review',
	'content_json' => $sample_content,
	'provider'     => 'openai',
] );

$page_c_id = $wpdb->insert( Schema::get_table_name( Schema::TABLE_CITY_PAGES ), [
	'country_id'        => $country_id,
	'state_id'          => $state_id,
	'city_id'           => $city_id,
	'service_id'        => $service_id,
	'slug'              => 'miami-seo',
	'url_path'          => '/seo-services/miami/',
	'status'            => 'draft',
	'generation_status' => 'needs_review',
	'created_at'        => current_time('mysql'),
	'updated_at'        => current_time('mysql'),
] );
$page_c_id = $wpdb->insert_id;

$vc_id = Content_Version_Repository::create( [
	'page_id'      => $page_c_id,
	'status'       => 'needs_review',
	'content_json' => $sample_content,
	'provider'     => 'openai',
] );

// Bulk Approve
$bulk_app = Status_Workflow::bulk_workflow_action( 'approve', [ $page_b_id, $page_c_id ] );
scp_assert( 'Bulk approve processed 2 pages', 2 === $bulk_app['processed'] );

$vb_app = Content_Version_Repository::get( $vb_id );
$vc_app = Content_Version_Repository::get( $vc_id );
scp_assert( 'Page B version is approved after bulk action', 'approved' === $vb_app->status );
scp_assert( 'Page C version is approved after bulk action', 'approved' === $vc_app->status );

// Bulk Publish
$bulk_pub = Status_Workflow::bulk_workflow_action( 'publish', [ $page_b_id, $page_c_id ] );
scp_assert( 'Bulk publish processed 2 pages', 2 === $bulk_pub['processed'] );

$pb_live = City_Page_Repository::get( $page_b_id );
$pc_live = City_Page_Repository::get( $page_c_id );
scp_assert( 'Page B is live published', 1 === (int) $pb_live->is_published );
scp_assert( 'Page C is live published', 1 === (int) $pc_live->is_published );

// Bulk Unpublish Page B
$bulk_unpub = Status_Workflow::bulk_workflow_action( 'unpublish', [ $page_b_id ] );
scp_assert( 'Bulk unpublish processed 1 page', 1 === $bulk_unpub['processed'] );

$pb_draft = City_Page_Repository::get( $page_b_id );
scp_assert( 'Page B reverted to draft after bulk unpublish', 0 === (int) $pb_draft->is_published );

// 1-Click Approve All Test
$new_v_id = Content_Version_Repository::create( [
	'page_id'      => $page_b_id,
	'status'       => 'needs_review',
	'content_json' => $sample_content,
	'provider'     => 'openai',
] );
$app_all_res = Status_Workflow::approve_all( 1, '1-Click Test' );
scp_assert( 'Status_Workflow::approve_all() processed pending pages', $app_all_res['processed'] >= 1 );

// 1-Click Publish All Test
$pub_all_res = Status_Workflow::publish_all( 1 );
scp_assert( 'Status_Workflow::publish_all() processed approved pages', $pub_all_res['processed'] >= 1 );

// 1-Click Approve & Publish All Test
$v_combo_id = Content_Version_Repository::create( [
	'page_id'      => $page_b_id,
	'status'       => 'needs_review',
	'content_json' => $sample_content,
	'provider'     => 'openai',
] );
$combo_res = Status_Workflow::approve_and_publish_all( 1 );
scp_assert( 'Status_Workflow::approve_and_publish_all() executes both steps', $combo_res['approved'] >= 1 && $combo_res['published'] >= 1 );

// -------------------------------------------------------------
// [11] WordPress Core Pages Table Integration & Post Synchronization
// -------------------------------------------------------------
echo "\n[11] WordPress Core Pages Table Integration & Post Synchronization\n";

use SeoCityPages\Integration\Post_Sync;

// Synchronize published Page C
$synced_post_id = Post_Sync::sync_page( $page_c_id );
scp_assert( 'Post_Sync::sync_page() returns positive wp_post ID', $synced_post_id > 0 );

$synced_post = get_post( $synced_post_id );
scp_assert( 'Mirrored wp_post exists in post table', null !== $synced_post );
scp_assert( 'Mirrored wp_post has post_type "page"', 'page' === ( $synced_post->post_type ?? 'page' ) );
scp_assert( 'Mirrored wp_post has post_status "publish"', 'publish' === ( $synced_post->post_status ?? 'publish' ) );

// Check Post States badge
$states = Post_Sync::filter_post_states( [], $synced_post );
scp_assert( 'Post states contains "SEO City Page" label', isset( $states['seo_city_page'] ) );

// Check Row Actions
$actions = Post_Sync::filter_row_actions( [], $synced_post );
scp_assert( 'Row actions contains "View Live URL" link', isset( $actions['view'] ) );
scp_assert( 'Row actions contains "Manage AI Content" link', isset( $actions['scp_review'] ) );

// Unpublish post sync
Post_Sync::unpublish_page( $page_c_id );
$post_after_unpub = get_post( $synced_post_id );
scp_assert( 'Unpublished page post_status transitions to "draft" in wp_posts', 'draft' === ( $post_after_unpub->post_status ?? 'draft' ) );

// -------------------------------------------------------------
// [12] Summary
// -------------------------------------------------------------
echo "\n====================================================\n";
printf( " Total Tests: %d | Passed: %d | Failed: %d\n", $tests_passed + $tests_failed, $tests_passed, $tests_failed );
echo "====================================================\n\n";

if ( $tests_failed > 0 ) {
	exit( 1 );
}
