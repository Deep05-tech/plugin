<?php
/**
 * Automated Test Suite for Marketing Proposal Inquiry Forms (Hero Section & Page Bottom).
 *
 * @package SeoCityPages\Tests
 */

namespace Elementor\Modules\DynamicTags {
	class Module {
		public const TEXT_CATEGORY = 'text';
		public const URL_CATEGORY  = 'url';
		public const IMAGE_CATEGORY = 'image';
	}
}

namespace Elementor\Core\DynamicTags {
	abstract class Tag {
		abstract public function get_name();
		abstract public function get_title();
		abstract public function get_group();
		abstract public function get_categories();
		abstract public function render();
	}

	class Manager {
		public array $groups = [];
		public array $tags   = [];

		public function register_group( string $name, array $data ): void {
			$this->groups[ $name ] = $data;
		}

		public function register( Tag $tag ): void {
			$this->tags[ $tag->get_name() ] = $tag;
		}
	}
}

namespace Elementor {
	class Frontend {
		public function get_builder_content_for_display( int $template_id, bool $with_css = true ): string {
			return "<div class=\"elementor elementor-{$template_id}\"></div>";
		}
	}

	class Plugin {
		public static $instance = null;
		public $frontend;
		public $dynamic_tags;

		public function __construct() {
			$this->frontend     = new Frontend();
			$this->dynamic_tags = new \Elementor\Core\DynamicTags\Manager();
		}
	}
}

namespace {

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

	// WordPress Mock Environment
	$GLOBALS['_test_posts']       = [];
	$GLOBALS['_test_post_meta']   = [];
	$GLOBALS['_test_shortcodes']  = [];
	$GLOBALS['_test_actions']     = [];
	$GLOBALS['_test_filters']     = [];
	$GLOBALS['_test_sent_emails'] = [];
	$GLOBALS['_test_json_output'] = null;
	$GLOBALS['_test_json_status'] = null;

	if ( ! function_exists( 'add_shortcode' ) ) {
		function add_shortcode( string $tag, callable $callback ): void {
			$GLOBALS['_test_shortcodes'][ $tag ] = $callback;
		}
	}

	if ( ! function_exists( 'do_shortcode' ) ) {
		function do_shortcode( string $content ): string {
			foreach ( $GLOBALS['_test_shortcodes'] as $tag => $callback ) {
				$pattern = '/' . preg_quote( '[' . $tag . ']', '/' ) . '/';
				if ( preg_match( $pattern, $content ) ) {
					$replacement = call_user_func( $callback, [] );
					$content     = preg_replace( $pattern, (string) $replacement, $content );
				}
			}
			return $content;
		}
	}

	if ( ! function_exists( 'add_action' ) ) {
		function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
			$GLOBALS['_test_actions'][ $hook ][] = $callback;
		}
	}

	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
			$GLOBALS['_test_filters'][ $hook ][] = $callback;
		}
	}

	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( string $hook, $value, ...$args ) {
			if ( ! empty( $GLOBALS['_test_filters'][ $hook ] ) ) {
				foreach ( $GLOBALS['_test_filters'][ $hook ] as $callback ) {
					$value = call_user_func( $callback, $value, ...$args );
				}
			}
			return $value;
		}
	}

	if ( ! function_exists( 'wp_create_nonce' ) ) {
		function wp_create_nonce( string $action = '-1' ): string {
			return substr( md5( 'nonce_' . $action ), 0, 10 );
		}
	}

	if ( ! function_exists( 'wp_verify_nonce' ) ) {
		function wp_verify_nonce( string $nonce, string $action = '-1' ): bool {
			return $nonce === substr( md5( 'nonce_' . $action ), 0, 10 );
		}
	}

	if ( ! function_exists( 'sanitize_text_field' ) ) {
		function sanitize_text_field( string $str ): string {
			return trim( strip_tags( $str ) );
		}
	}

	if ( ! function_exists( 'sanitize_email' ) ) {
		function sanitize_email( string $email ): string {
			return filter_var( trim( $email ), FILTER_SANITIZE_EMAIL ) ?: '';
		}
	}

	if ( ! function_exists( 'sanitize_textarea_field' ) ) {
		function sanitize_textarea_field( string $str ): string {
			return trim( strip_tags( $str ) );
		}
	}

	if ( ! function_exists( 'sanitize_key' ) ) {
		function sanitize_key( string $key ): string {
			return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $key ) );
		}
	}

	if ( ! function_exists( 'is_email' ) ) {
	if ( ! function_exists( "get_current_user_id" ) ) {
		function get_current_user_id(): int {
			return 1;
		}
	}
		function is_email( string $email ): bool {
			return false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
		}
	}

	if ( ! function_exists( 'esc_html' ) ) {
		function esc_html( string $text ): string {
			return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
		}
	}

	if ( ! function_exists( 'esc_attr' ) ) {
		function esc_attr( string $text ): string {
			return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
		}
	}

	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( string $text, string $domain = 'default' ): string {
			return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
		}
	}

	if ( ! function_exists( 'esc_attr__' ) ) {
		function esc_attr__( string $text, string $domain = 'default' ): string {
			return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
		}
	}

	if ( ! function_exists( 'esc_url' ) ) {
		function esc_url( string $url ): string {
			return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
		}
	}

	if ( ! function_exists( 'esc_url_raw' ) ) {
		function esc_url_raw( string $url ): string {
			return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
		}
	}

	if ( ! function_exists( 'wp_unslash' ) ) {
		function wp_unslash( $value ) {
			return is_string( $value ) ? stripslashes( $value ) : $value;
		}
	}

	if ( ! function_exists( 'absint' ) ) {
		function absint( $val ): int {
			return abs( (int) $val );
		}
	}

	if ( ! function_exists( '__' ) ) {
		function __( string $text, string $domain = 'default' ): string {
			return $text;
		}
	}

	if ( ! function_exists( 'admin_url' ) ) {
		function admin_url( string $path = '' ): string {
			return 'http://example.local/wp-admin/' . ltrim( $path, '/' );
		}
	}

	if ( ! function_exists( 'home_url' ) ) {
		function home_url( string $path = '' ): string {
			return 'http://example.local/' . ltrim( $path, '/' );
		}
	}

	if ( ! function_exists( 'trailingslashit' ) ) {
		function trailingslashit( string $path ): string {
			return rtrim( $path, '/\\' ) . '/';
		}
	}

	if ( ! function_exists( 'untrailingslashit' ) ) {
		function untrailingslashit( string $path ): string {
			return rtrim( $path, '/\\' );
		}
	}

	if ( ! function_exists( 'current_time' ) ) {
		function current_time( string $type, $gmt = 0 ): string {
			return 'mysql' === $type ? date( 'Y-m-d H:i:s' ) : (string) time();
		}
	}

	if ( ! function_exists( 'wp_upload_dir' ) ) {
		function wp_upload_dir() {
			$temp = sys_get_temp_dir() . '/seo_city_test_uploads';
			if ( ! is_dir( $temp ) ) {
				@mkdir( $temp, 0777, true );
			}
			return [
				'basedir' => $temp,
				'baseurl' => 'http://example.local/wp-content/uploads',
				'error'   => false,
			];
		}
	}

	if ( ! function_exists( 'wp_mkdir_p' ) ) {
		function wp_mkdir_p( $target ) {
			return @mkdir( $target, 0777, true ) || is_dir( $target );
		}
	}

	$GLOBALS['_test_options'] = [];

	if ( ! function_exists( 'get_option' ) ) {
		function get_option( string $option, $default = false ) {
			if ( isset( $GLOBALS['_test_options'][ $option ] ) ) {
				return $GLOBALS['_test_options'][ $option ];
			}
			if ( 'admin_email' === $option ) {
				return 'admin@example.com';
			}
			return $default;
		}
	}

	if ( ! function_exists( 'wp_mail' ) ) {
		function wp_mail( $to, $subject, $message, $headers = '', $attachments = [] ): bool {
			$GLOBALS['_test_sent_emails'][] = [
				'to'          => $to,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => $headers,
				'attachments' => $attachments,
			];
			return true;
		}
	}

	if ( ! function_exists( 'wp_send_json_success' ) ) {
		function wp_send_json_success( $data = null, $status_code = null ): void {
			$GLOBALS['_test_json_output'] = [ 'success' => true, 'data' => $data ];
			$GLOBALS['_test_json_status'] = $status_code ?: 200;
		}
	}

	if ( ! function_exists( 'wp_send_json_error' ) ) {
		function wp_send_json_error( $data = null, $status_code = null ): void {
			$GLOBALS['_test_json_output'] = [ 'success' => false, 'data' => $data ];
			$GLOBALS['_test_json_status'] = $status_code ?: 400;
		}
	}

	if ( ! function_exists( 'current_user_can' ) ) {
		function current_user_can( string $cap ): bool {
			return true;
		}
	}

	if ( ! function_exists( 'wp_insert_post' ) ) {
		function wp_insert_post( array $postarr, bool $wp_error = false ) {
			static $id = 500;
			$id++;
			$GLOBALS['_test_posts'][ $id ] = (object) $postarr;
			return $id;
		}
	}

	if ( ! function_exists( 'update_post_meta' ) ) {
		function update_post_meta( int $post_id, string $meta_key, $meta_value ): bool {
			$GLOBALS['_test_post_meta'][ $post_id ][ $meta_key ] = $meta_value;
			return true;
		}
	}

	if ( ! function_exists( 'wp_slash' ) ) {
		function wp_slash( $value ) {
			return $value;
		}
	}

	if ( ! function_exists( 'wp_json_encode' ) ) {
		function wp_json_encode( $data ) {
			return json_encode( $data );
		}
	}

	if ( ! function_exists( 'is_wp_error' ) ) {
		function is_wp_error( $thing ): bool {
			return $thing instanceof \WP_Error;
		}
	}

	class WP_Error {
		private string $code;
		private string $message;
		public function __construct( string $code = '', string $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
		public function get_error_message(): string {
			return $this->message;
		}
	}

	// Setup Mock wpdb
	class MockWPDB {
		public string $prefix = 'wp_';
		public int $insert_id = 1;
		public array $queries = [];
		public array $tables = [];

		public function get_results( $sql, $output = OBJECT ) {
			$this->queries[] = $sql;
			return [];
		}

		public function get_row( $sql, $output = OBJECT ) {
			$this->queries[] = $sql;
			return null;
		}

		public function prepare( $query, ...$args ) {
			return vsprintf( str_replace( [ '%s', '%d', '%f' ], [ "'%s'", '%d', '%f' ], $query ), $args );
		}

		public function insert( $table, $data, $format = null ) {
			return 1;
		}
	}
	$GLOBALS['wpdb'] = new MockWPDB();

	// Autoloader
	spl_autoload_register( function ( $class ) {
		$prefix = 'SeoCityPages\\';
		$base_dir = dirname( __DIR__ ) . '/includes/';
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}
		$relative_class = substr( $class, $len );
		$parts = explode( '\\', $relative_class );
		$file_name = 'class-' . strtolower( str_replace( '_', '-', array_pop( $parts ) ) ) . '.php';
		$sub_dir = ! empty( $parts ) ? implode( '/', $parts ) . '/' : '';
		$file = $base_dir . $sub_dir . $file_name;
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	} );

	// Initialize Environment
	\Elementor\Plugin::$instance = new \Elementor\Plugin();
	\SeoCityPages\Elementor\Elementor_Integration::instance()->init();
	\SeoCityPages\Frontend\Inquiry_Handler::init();

	// Test Runner Assertions
	$test_count = 0;
	$pass_count = 0;

	function assert_true( $condition, string $description ): void {
		global $test_count, $pass_count;
		$test_count++;
		if ( $condition ) {
			$pass_count++;
			echo "  [PASS] {$description}\n";
		} else {
			echo "  [FAIL] {$description}\n";
		}
	}

	echo "\n======================================================\n";
	echo "  TEST SUITE: Marketing Proposal Inquiry Forms\n";
	echo "======================================================\n\n";

	// ------------------------------------------------------------------------
	// Test 1: Content_Context Form Rendering (Hero Layout)
	// ------------------------------------------------------------------------
	echo "1. Testing Hero Inquiry Form HTML Generation...\n";
	$hero_form_html = \SeoCityPages\Elementor\Content_Context::render_inquiry_form_hero( [
		'service' => 'Search Engine Optimization',
		'page_id' => 101,
		'url'     => 'http://example.local/us/tx/austin/search-engine-optimization',
	] );

	assert_true( false !== strpos( $hero_form_html, 'Request A Marketing Proposal' ), 'Hero form contains title "Request A Marketing Proposal"' );
	assert_true( false !== strpos( $hero_form_html, 'name="full_name"' ), 'Hero form contains full_name input' );
	assert_true( false !== strpos( $hero_form_html, 'name="email"' ), 'Hero form contains email input' );
	assert_true( false !== strpos( $hero_form_html, 'name="country_code"' ), 'Hero form contains country_code select' );
	assert_true( false !== strpos( $hero_form_html, 'name="phone"' ), 'Hero form contains phone input' );
	assert_true( false !== strpos( $hero_form_html, 'name="service"' ), 'Hero form contains service select' );
	assert_true( false !== strpos( $hero_form_html, 'name="company_name"' ), 'Hero form contains company_name input' );
	assert_true( false !== strpos( $hero_form_html, 'name="website"' ), 'Hero form contains website input' );
	assert_true( false !== strpos( $hero_form_html, 'name="budget"' ), 'Hero form contains budget select' );
	assert_true( false !== strpos( $hero_form_html, 'name="details"' ), 'Hero form contains details textarea' );
	assert_true( false !== strpos( $hero_form_html, 'name="inquiry_hp_check"' ), 'Hero form contains honeypot field' );
	assert_true( false !== strpos( $hero_form_html, 'name="action" value="seo_city_submit_inquiry"' ), 'Hero form contains hidden action field' );
	assert_true( false !== strpos( $hero_form_html, 'Request a marketing Proposal' ), 'Hero form contains CTA submit button' );
	assert_true( false !== strpos( $hero_form_html, 'vispan-inquiry-layout-hero' ), 'Hero form has hero layout class' );

	// ------------------------------------------------------------------------
	// Test 2: Content_Context Form Rendering (Bottom Layout)
	// ------------------------------------------------------------------------
	echo "\n2. Testing Bottom Inquiry Form HTML Generation...\n";
	$bottom_form_html = \SeoCityPages\Elementor\Content_Context::render_inquiry_form_bottom( [
		'service' => 'SEO',
		'page_id' => 102,
	] );

	assert_true( false !== strpos( $bottom_form_html, 'vispan-inquiry-layout-bottom' ), 'Bottom form has bottom layout class' );
	assert_true( false !== strpos( $bottom_form_html, 'grid-template-columns' ), 'Bottom form has responsive CSS grid layout' );
	assert_true( false !== strpos( $bottom_form_html, 'value="SEO" selected' ), 'Bottom form has pre-selected service option' );

	// ------------------------------------------------------------------------
	// Test 3: Universal Shortcodes Registration
	// ------------------------------------------------------------------------
	echo "\n3. Testing Inquiry Form Shortcodes...\n";
	assert_true( isset( $GLOBALS['_test_shortcodes']['seo_inquiry_form'] ), 'Shortcode [seo_inquiry_form] is registered' );
	assert_true( isset( $GLOBALS['_test_shortcodes']['seo_inquiry_form_hero'] ), 'Shortcode [seo_inquiry_form_hero] is registered' );
	assert_true( isset( $GLOBALS['_test_shortcodes']['seo_inquiry_form_bottom'] ), 'Shortcode [seo_inquiry_form_bottom] is registered' );

	$shortcode_out = do_shortcode( '<div>[seo_inquiry_form_hero]</div>' );
	assert_true( false !== strpos( $shortcode_out, 'vispan-inquiry-card' ), 'Shortcode [seo_inquiry_form_hero] properly renders form HTML' );

	// ------------------------------------------------------------------------
	// Test 4: Elementor Dynamic Token Replacement
	// ------------------------------------------------------------------------
	echo "\n4. Testing Elementor Token Replacement...\n";
	$rendered = \SeoCityPages\Elementor\Elementor_Integration::filter_rendered_content( '<div class="section-1">{{seo_inquiry_form_hero}}</div><div class="section-15">{{seo_inquiry_form_bottom}}</div>' );
	assert_true( false !== strpos( $rendered, 'vispan-inquiry-layout-hero' ), 'Token {{seo_inquiry_form_hero}} hydrates hero form' );
	assert_true( false !== strpos( $rendered, 'vispan-inquiry-layout-bottom' ), 'Token {{seo_inquiry_form_bottom}} hydrates bottom form' );

	// ------------------------------------------------------------------------
	// Test 5: Starter Template Generation with Inquiry Forms
	// ------------------------------------------------------------------------
	echo "\n5. Testing Starter Template with Section 1 & Section 15 Inquiry Forms...\n";
	$template_id = \SeoCityPages\Elementor\Elementor_Integration::create_starter_template( 'Master Inquiry Test Template' );
	assert_true( is_int( $template_id ) && $template_id > 0, 'Starter Elementor template created successfully' );

	$meta_data_json = $GLOBALS['_test_post_meta'][ $template_id ]['_elementor_data'] ?? '';
	assert_true( false !== strpos( $meta_data_json, '{{seo_inquiry_form_hero}}' ), 'Starter template Section 1 embeds {{seo_inquiry_form_hero}}' );
	assert_true( false !== strpos( $meta_data_json, '{{seo_inquiry_form_bottom}}' ), 'Starter template Section 15 embeds {{seo_inquiry_form_bottom}}' );

	// ------------------------------------------------------------------------
	// Test 6: Inquiry Form AJAX Handler (Validation, Honeypot, Email & Audit)
	// ------------------------------------------------------------------------
	echo "\n6. Testing Inquiry Handler AJAX Submission Flow...\n";

	// 6a: Spam Honeypot check
	$_POST = [
		'nonce'             => wp_create_nonce( \SeoCityPages\Frontend\Inquiry_Handler::NONCE_ACTION ),
		'inquiry_hp_check'  => 'I am a spambot',
		'full_name'         => 'Bot User',
		'email'             => 'bot@example.com',
	];
	\SeoCityPages\Frontend\Inquiry_Handler::handle_submission();
	assert_true( $GLOBALS['_test_json_output']['success'] === true, 'Honeypot caught submission silently returns success' );
	assert_true( empty( $GLOBALS['_test_sent_emails'] ), 'No admin email sent for honeypot spam submission' );

	// 6b: Missing Required Fields
	$_POST = [
		'nonce'            => wp_create_nonce( \SeoCityPages\Frontend\Inquiry_Handler::NONCE_ACTION ),
		'inquiry_hp_check' => '',
		'full_name'        => 'John Doe',
		'email'            => '', // Missing email
	];
	\SeoCityPages\Frontend\Inquiry_Handler::handle_submission();
	assert_true( $GLOBALS['_test_json_output']['success'] === false, 'Missing required fields returns JSON error' );
	assert_true( $GLOBALS['_test_json_status'] === 400, 'HTTP 400 status returned for missing fields' );

	// 6c: Invalid Email Address
	$_POST = [
		'nonce'            => wp_create_nonce( \SeoCityPages\Frontend\Inquiry_Handler::NONCE_ACTION ),
		'inquiry_hp_check' => '',
		'full_name'        => 'John Doe',
		'email'            => 'invalid-email-string',
		'phone'            => '9876543210',
		'service'          => 'Search Engine Optimization',
		'company_name'     => 'Acme Corp',
		'website'          => 'https://acme.com',
		'budget'           => '$3,000 - $5,000 / month',
	];
	\SeoCityPages\Frontend\Inquiry_Handler::handle_submission();
	assert_true( $GLOBALS['_test_json_output']['success'] === false, 'Invalid email returns JSON error' );

	// 6d: Valid Complete Submission
	$_POST = [
		'nonce'            => wp_create_nonce( \SeoCityPages\Frontend\Inquiry_Handler::NONCE_ACTION ),
		'inquiry_hp_check' => '',
		'full_name'        => 'Alice Johnson',
		'email'            => 'alice@acme.com',
		'country_code'     => '+1 USA',
		'phone'            => '512-555-0199',
		'service'          => 'Search Engine Optimization',
		'company_name'     => 'Acme Corp',
		'website'          => 'https://acme.com',
		'budget'           => '$5,000 - $10,000 / month',
		'details'          => 'Looking to scale organic traffic across 15 Texas metro cities.',
		'page_id'          => '101',
		'page_url'         => 'http://example.local/us/tx/austin/search-engine-optimization',
		'form_layout'      => 'hero',
	];
	\SeoCityPages\Frontend\Inquiry_Handler::handle_submission();
	assert_true( $GLOBALS['_test_json_output']['success'] === true, 'Valid proposal request returns JSON success' );
	assert_true( ! empty( $GLOBALS['_test_sent_emails'] ), 'Admin email successfully triggered via wp_mail' );
	$sent_email = end( $GLOBALS['_test_sent_emails'] );
	assert_true( false !== strpos( $sent_email['subject'], 'Alice Johnson' ), 'Admin email subject contains lead name' );
	assert_true( false !== strpos( $sent_email['message'], '$5,000 - $10,000 / month' ), 'Admin email contains budget selection' );
	assert_true( false !== strpos( $sent_email['message'], 'Acme Corp' ), 'Admin email contains company name' );
	assert_true( in_array( 'Reply-To: Alice Johnson <alice@acme.com>', $sent_email['headers'], true ), 'Email includes proper Reply-To header pointing to the lead' );

	// 6e: Custom Specific Recipient Emails from Settings
	$GLOBALS['_test_options'][\SeoCityPages\Admin\Settings::OPTION_NAME] = [
		'inquiry_notification_emails' => 'sales@vispansolutions.com, leads@vispansolutions.com',
	];

	$_POST = [
		'nonce'            => wp_create_nonce( \SeoCityPages\Frontend\Inquiry_Handler::NONCE_ACTION ),
		'inquiry_hp_check' => '',
		'full_name'        => 'Bob Smith',
		'email'            => 'bob@example.com',
		'country_code'     => '+1',
		'phone'            => '555-123-4567',
		'service'          => 'Google Ads',
		'company_name'     => 'Bob Co',
		'website'          => 'https://bob.com',
		'budget'           => '$10,000+ / month',
		'details'          => 'Need enterprise PPC setup.',
	];
	\SeoCityPages\Frontend\Inquiry_Handler::handle_submission();
	$sent_custom = end( $GLOBALS['_test_sent_emails'] );
	assert_true( is_array( $sent_custom['to'] ) && count( $sent_custom['to'] ) === 2, 'Multiple specific recipient emails dispatched' );
	assert_true( in_array( 'sales@vispansolutions.com', $sent_custom['to'], true ), 'Includes sales@vispansolutions.com' );
	assert_true( in_array( 'leads@vispansolutions.com', $sent_custom['to'], true ), 'Includes leads@vispansolutions.com' );

	echo "\n======================================================\n";
	echo "  TEST SUMMARY: {$pass_count}/{$test_count} assertions passed.\n";
	echo "======================================================\n\n";

	if ( $pass_count !== $test_count ) {
		exit( 1 );
	}
}
