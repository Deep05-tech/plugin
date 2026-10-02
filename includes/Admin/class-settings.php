<?php
/**
 * Settings Admin Page & WordPress Settings API Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Class.
 */
class Settings {

	/**
	 * Settings group name.
	 */
	public const SETTINGS_GROUP = 'seo_city_pages_settings_group';

	/**
	 * Option name.
	 */
	public const OPTION_NAME = 'seo_city_pages_settings';

	/**
	 * Initialize Settings API hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register settings, sections, and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => self::get_defaults(),
			]
		);

		// Section: General Settings.
		add_settings_section(
			'seo_city_pages_general_section',
			__( 'General & Performance', 'seo-city-pages' ),
			[ $this, 'render_general_section_desc' ],
			'seo-city-pages-settings'
		);

		add_settings_field(
			'cache_compatibility_mode',
			__( 'Cache Compatibility Mode', 'seo-city-pages' ),
			[ $this, 'render_cache_mode_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_general_section'
		);

		// Section: URL Architecture.
		add_settings_section(
			'seo_city_pages_url_section',
			__( 'URL Architecture & Structure', 'seo-city-pages' ),
			[ $this, 'render_url_section_desc' ],
			'seo-city-pages-settings'
		);

		add_settings_field(
			'url_pattern',
			__( 'SEO City Page URL Pattern', 'seo-city-pages' ),
			[ $this, 'render_url_pattern_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_url_section'
		);

		// Section: Debugging & Logs.
		add_settings_section(
			'seo_city_pages_logging_section',
			__( 'Logging & Developer Mode', 'seo-city-pages' ),
			[ $this, 'render_logging_section_desc' ],
			'seo-city-pages-settings'
		);

		add_settings_field(
			'debug_mode',
			__( 'Developer / Debug Mode', 'seo-city-pages' ),
			[ $this, 'render_debug_mode_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_logging_section'
		);

		add_settings_field(
			'log_level',
			__( 'Log Level', 'seo-city-pages' ),
			[ $this, 'render_log_level_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_logging_section'
		);

		// Section: Inquiry & Lead Notifications.
		add_settings_section(
			'seo_city_pages_notifications_section',
			__( '📬 Marketing Proposal Lead Notifications & SMTP Delivery', 'seo-city-pages' ),
			[ $this, 'render_notifications_section_desc' ],
			'seo-city-pages-settings'
		);

		add_settings_field(
			'inquiry_notification_emails',
			__( 'Lead Recipient Email(s)', 'seo-city-pages' ),
			[ $this, 'render_inquiry_emails_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_notifications_section'
		);

		add_settings_field(
			'smtp_enabled',
			__( 'Enable Custom SMTP', 'seo-city-pages' ),
			[ $this, 'render_smtp_enabled_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_notifications_section'
		);

		add_settings_field(
			'smtp_host',
			__( 'SMTP Host', 'seo-city-pages' ),
			[ $this, 'render_smtp_host_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_notifications_section'
		);

		add_settings_field(
			'smtp_port',
			__( 'SMTP Port & Encryption', 'seo-city-pages' ),
			[ $this, 'render_smtp_port_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_notifications_section'
		);

		add_settings_field(
			'smtp_username',
			__( 'SMTP Username / Email', 'seo-city-pages' ),
			[ $this, 'render_smtp_username_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_notifications_section'
		);

		add_settings_field(
			'smtp_password',
			__( 'SMTP Password / App Password', 'seo-city-pages' ),
			[ $this, 'render_smtp_password_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_notifications_section'
		);

		add_settings_field(
			'smtp_from_email',
			__( 'From Email & Name', 'seo-city-pages' ),
			[ $this, 'render_smtp_from_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_notifications_section'
		);

		// Section: OpenAI Content Engine.
		add_settings_section(
			'seo_city_pages_ai_section',
			__( '🤖 OpenAI Content Generation Engine', 'seo-city-pages' ),
			[ $this, 'render_ai_section_desc' ],
			'seo-city-pages-settings'
		);

		add_settings_field(
			'ai_enabled',
			__( 'Enable AI Generation', 'seo-city-pages' ),
			[ $this, 'render_ai_enabled_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_ai_section'
		);

		add_settings_field(
			'ai_api_key',
			__( 'OpenAI API Key', 'seo-city-pages' ),
			[ $this, 'render_ai_api_key_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_ai_section'
		);

		add_settings_field(
			'ai_model',
			__( 'OpenAI Model', 'seo-city-pages' ),
			[ $this, 'render_ai_model_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_ai_section'
		);

		add_settings_field(
			'ai_temperature',
			__( 'Temperature / Creativity', 'seo-city-pages' ),
			[ $this, 'render_ai_temperature_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_ai_section'
		);

		add_settings_field(
			'ai_content_length',
			__( 'Content Length', 'seo-city-pages' ),
			[ $this, 'render_ai_length_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_ai_section'
		);

		// Section: Global Business Profile.
		add_settings_section(
			'seo_city_pages_business_section',
			__( '🏢 Global Business Profile (AI Reference)', 'seo-city-pages' ),
			[ $this, 'render_business_section_desc' ],
			'seo-city-pages-settings'
		);

		add_settings_field(
			'ai_company_name',
			__( 'Company Name', 'seo-city-pages' ),
			[ $this, 'render_ai_company_name_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_business_section'
		);

		add_settings_field(
			'ai_company_description',
			__( 'Company Description', 'seo-city-pages' ),
			[ $this, 'render_ai_company_desc_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_business_section'
		);

		add_settings_field(
			'ai_core_services',
			__( 'Core Services Summary', 'seo-city-pages' ),
			[ $this, 'render_ai_core_services_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_business_section'
		);

		add_settings_field(
			'ai_differentiators',
			__( 'Approved Differentiators', 'seo-city-pages' ),
			[ $this, 'render_ai_differentiators_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_business_section'
		);

		// Section: Brand Voice & Guidelines.
		add_settings_section(
			'seo_city_pages_voice_section',
			__( '✍️ Brand Voice & Editorial Guidelines', 'seo-city-pages' ),
			[ $this, 'render_voice_section_desc' ],
			'seo-city-pages-settings'
		);

		add_settings_field(
			'ai_tone',
			__( 'Tone of Voice', 'seo-city-pages' ),
			[ $this, 'render_ai_tone_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_voice_section'
		);

		add_settings_field(
			'ai_prohibited_phrases',
			__( 'Prohibited Phrases (Negative Constraints)', 'seo-city-pages' ),
			[ $this, 'render_ai_prohibited_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_voice_section'
		);

		// Section: Uninstall & Data Retention.
		add_settings_section(
			'seo_city_pages_uninstall_section',
			__( 'Data Retention & Uninstall', 'seo-city-pages' ),
			[ $this, 'render_uninstall_section_desc' ],
			'seo-city-pages-settings'
		);

		add_settings_field(
			'delete_data_on_uninstall',
			__( 'Delete Data on Uninstall', 'seo-city-pages' ),
			[ $this, 'render_delete_on_uninstall_field' ],
			'seo-city-pages-settings',
			'seo_city_pages_uninstall_section'
		);
	}

	/**
	 * Default settings values.
	 *
	 * @return array
	 */
	public static function get_defaults(): array {
		return [
			'cache_compatibility_mode' => 1,
			'url_pattern'              => \SeoCityPages\URL\URL_Engine::DEFAULT_PATTERN,
			'template_id'              => 0,
			'preview_page_id'          => 0,
			'debug_mode'               => 0,
			'log_level'                => 'info',
			'delete_data_on_uninstall' => 0,
			'inquiry_notification_emails' => '',
			'smtp_enabled'             => 0,
			'smtp_host'                => '',
			'smtp_port'                => 587,
			'smtp_encryption'          => 'tls',
			'smtp_auth'                => 1,
			'smtp_username'            => '',
			'smtp_password'            => '',
			'smtp_from_email'          => '',
			'smtp_from_name'           => 'Vispan Solutions',
			'ai_enabled'               => 1,
			'ai_provider'              => 'openai',
			'ai_api_key'               => '',
			'ai_model'                 => 'gpt-4o-mini',
			'ai_temperature'           => 0.7,
			'ai_content_length'        => 'medium',
			'ai_company_name'          => 'Vispan Solutions',
			'ai_company_description'   => 'Leading full-service digital marketing and local SEO agency providing measurable revenue growth.',
			'ai_core_services'         => 'Local SEO, Google Map Pack Optimization, Technical Search Architecture, Conversion Rate Optimization',
			'ai_target_audience'       => 'Business owners, founders, and marketing directors',
			'ai_brand_positioning'     => 'Results-driven, transparent, and authoritative',
			'ai_differentiators'       => 'Dedicated strategist, weekly reporting, ROI-driven keyword research',
			'ai_tone'                  => 'Professional, authoritative, direct, and compelling',
			'ai_style'                 => 'Modern B2B agency, concise paragraphs, high-converting',
			'ai_prohibited_phrases'    => 'cheap SEO, guarantee #1 rankings, best in the world',
			'ai_preferred_terminology' => 'inbound leads, organic search velocity, topical authority',
		];
	}

	/**
	 * Sanitize and validate settings input.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized array.
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return self::get_defaults();
		}

		$output = self::get_defaults();

		// Cache compatibility mode.
		$output['cache_compatibility_mode'] = ! empty( $input['cache_compatibility_mode'] ) ? 1 : 0;

		// Template IDs.
		$output['template_id']     = isset( $input['template_id'] ) ? (int) $input['template_id'] : 0;
		$output['preview_page_id'] = isset( $input['preview_page_id'] ) ? (int) $input['preview_page_id'] : 0;

		// URL pattern validation.
		if ( ! empty( $input['url_pattern'] ) ) {
			$raw_pattern = sanitize_text_field( $input['url_pattern'] );
			$val_check   = \SeoCityPages\URL\URL_Engine::validate_pattern( $raw_pattern );

			if ( $val_check['valid'] ) {
				$output['url_pattern'] = '/' . trim( preg_replace( '#/+#', '/', $raw_pattern ), '/' ) . '/';
			} else {
				$existing = get_option( self::OPTION_NAME, self::get_defaults() );
				$output['url_pattern'] = $existing['url_pattern'] ?? \SeoCityPages\URL\URL_Engine::DEFAULT_PATTERN;
				if ( function_exists( 'add_settings_error' ) ) {
					add_settings_error(
						self::OPTION_NAME,
						'invalid_url_pattern',
						$val_check['error'] ?? __( 'Invalid URL pattern provided. Setting was not changed.', 'seo-city-pages' )
					);
				}
			}
		}

		// Debug mode.
		$output['debug_mode'] = ! empty( $input['debug_mode'] ) ? 1 : 0;

		// Log level.
		$valid_levels = [ 'info', 'warning', 'error' ];
		if ( isset( $input['log_level'] ) && in_array( $input['log_level'], $valid_levels, true ) ) {
			$output['log_level'] = $input['log_level'];
		} else {
			$output['log_level'] = 'info';
		}

		// Delete data on uninstall.
		$output['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] ) ? 1 : 0;

		// Inquiry lead notification recipient emails.
		if ( isset( $input['inquiry_notification_emails'] ) ) {
			$raw_emails = is_array( $input['inquiry_notification_emails'] )
				? implode( ',', $input['inquiry_notification_emails'] )
				: (string) $input['inquiry_notification_emails'];

			$email_list   = preg_split( '/[\r\n,]+/', $raw_emails );
			$valid_emails = [];
			foreach ( $email_list as $email ) {
				$trimmed   = trim( $email );
				$sanitized = $this->clean_email( $trimmed );
				if ( ! empty( $sanitized ) && $this->validate_email( $sanitized ) ) {
					$valid_emails[] = $sanitized;
				}
			}
			$output['inquiry_notification_emails'] = implode( ', ', array_unique( $valid_emails ) );
		}

		// SMTP Settings.
		$output['smtp_enabled'] = ! empty( $input['smtp_enabled'] ) ? 1 : 0;
		$output['smtp_host']    = sanitize_text_field( $input['smtp_host'] ?? '' );
		$output['smtp_port']    = ! empty( $input['smtp_port'] ) ? ( function_exists( 'absint' ) ? absint( $input['smtp_port'] ) : abs( (int) $input['smtp_port'] ) ) : 587;
		$encryption             = strtolower( sanitize_text_field( $input['smtp_encryption'] ?? 'tls' ) );
		$output['smtp_encryption'] = in_array( $encryption, [ 'tls', 'ssl', 'none' ], true ) ? $encryption : 'tls';
		$output['smtp_auth']    = ! empty( $input['smtp_auth'] ) ? 1 : 0;
		$output['smtp_username'] = sanitize_text_field( $input['smtp_username'] ?? '' );

		// SMTP Password: keep existing if empty or masked bullets.
		$existing_settings = get_option( self::OPTION_NAME, self::get_defaults() );
		$new_smtp_pass     = trim( (string) ( $input['smtp_password'] ?? '' ) );
		if ( empty( $new_smtp_pass ) || false !== strpos( $new_smtp_pass, '••••' ) ) {
			$output['smtp_password'] = $existing_settings['smtp_password'] ?? '';
		} else {
			$output['smtp_password'] = sanitize_text_field( $new_smtp_pass );
		}

		$output['smtp_from_email'] = $this->clean_email( $input['smtp_from_email'] ?? '' );
		$output['smtp_from_name']  = sanitize_text_field( $input['smtp_from_name'] ?? 'Vispan Solutions' );

		// AI Settings.
		$output['ai_enabled'] = ! empty( $input['ai_enabled'] ) ? 1 : 0;
		$output['ai_provider'] = sanitize_text_field( $input['ai_provider'] ?? 'openai' );

		// API Key: Keep existing key if input is empty or masked bullets.
		$existing_settings = get_option( self::OPTION_NAME, self::get_defaults() );
		$new_key = trim( (string) ( $input['ai_api_key'] ?? '' ) );
		if ( empty( $new_key ) || false !== strpos( $new_key, '••••' ) || 'sk-...' === $new_key ) {
			$output['ai_api_key'] = $existing_settings['ai_api_key'] ?? '';
		} else {
			$output['ai_api_key'] = sanitize_text_field( $new_key );
		}

		// AI Model.
		$model = sanitize_text_field( $input['ai_model'] ?? 'gpt-4o-mini' );
		$supported_models = \SeoCityPages\AI\OpenAI_Provider::SUPPORTED_MODELS;
		$output['ai_model'] = array_key_exists( $model, $supported_models ) ? $model : 'gpt-4o-mini';

		// AI Temperature.
		$temp = isset( $input['ai_temperature'] ) ? (float) $input['ai_temperature'] : 0.7;
		$output['ai_temperature'] = max( 0.0, min( 1.5, $temp ) );

		// Content Length.
		$length = sanitize_text_field( $input['ai_content_length'] ?? 'medium' );
		$output['ai_content_length'] = in_array( $length, [ 'short', 'medium', 'comprehensive' ], true ) ? $length : 'medium';

		// Business Profile fields.
		$output['ai_company_name']        = sanitize_text_field( $input['ai_company_name'] ?? 'Vispan Solutions' );
		$output['ai_company_description'] = sanitize_textarea_field( $input['ai_company_description'] ?? '' );
		$output['ai_core_services']       = sanitize_text_field( $input['ai_core_services'] ?? '' );
		$output['ai_target_audience']     = sanitize_text_field( $input['ai_target_audience'] ?? '' );
		$output['ai_brand_positioning']   = sanitize_text_field( $input['ai_brand_positioning'] ?? '' );
		$output['ai_differentiators']     = sanitize_textarea_field( $input['ai_differentiators'] ?? '' );

		// Brand Voice fields.
		$output['ai_tone']                  = sanitize_text_field( $input['ai_tone'] ?? 'Professional, authoritative, direct, and compelling' );
		$output['ai_style']                 = sanitize_text_field( $input['ai_style'] ?? 'Modern B2B agency, concise paragraphs, high-converting' );
		$output['ai_prohibited_phrases']    = sanitize_textarea_field( $input['ai_prohibited_phrases'] ?? '' );
		$output['ai_preferred_terminology'] = sanitize_textarea_field( $input['ai_preferred_terminology'] ?? '' );

		return $output;
	}

	/**
	 * Section callback: URL Architecture.
	 *
	 * @return void
	 */
	public function render_url_section_desc(): void {
		echo '<p class="description">' . esc_html__( 'Configure the deterministic public URL path structure for generated SEO City Pages.', 'seo-city-pages' ) . '</p>';
	}

	/**
	 * Field callback: URL Pattern.
	 *
	 * @return void
	 */
	public function render_url_pattern_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val     = $options['url_pattern'] ?? \SeoCityPages\URL\URL_Engine::DEFAULT_PATTERN;
		?>
		<input type="text" id="seo_city_pages_url_pattern" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[url_pattern]" value="<?php echo esc_attr( $val ); ?>" class="regular-text code" />
		<p class="description">
			<?php esc_html_e( 'Supported dynamic tokens: ', 'seo-city-pages' ); ?>
			<code>{country}</code>, <code>{state}</code>, <code>{city}</code>, <code>{service}</code>
		</p>
		<p class="description" style="margin-top: 6px;">
			<strong><?php esc_html_e( 'Example Generated URLs:', 'seo-city-pages' ); ?></strong><br />
			<code>/{service}/{city}/</code> &rarr; <code>/seo-agency/rajkot/</code><br />
			<code>/{city}/{service}/</code> &rarr; <code>/rajkot/seo-agency/</code><br />
			<code>/{country}/{state}/{city}/{service}/</code> &rarr; <code>/india/gujarat/rajkot/seo-agency/</code>
		</p>
		<?php
	}

	/**
	 * Section callback: General.
	 *
	 * @return void
	 */
	public function render_general_section_desc(): void {
		echo '<p class="description">' . esc_html__( 'Configure core execution behavior and caching interoperability.', 'seo-city-pages' ) . '</p>';
	}

	/**
	 * Section callback: Logging.
	 *
	 * @return void
	 */
	public function render_logging_section_desc(): void {
		echo '<p class="description">' . esc_html__( 'Configure diagnostic logging and developer tools.', 'seo-city-pages' ) . '</p>';
	}

	/**
	 * Section callback: Uninstall.
	 *
	 * @return void
	 */
	public function render_uninstall_section_desc(): void {
		echo '<p class="description">' . esc_html__( 'Manage data cleanup policies when the plugin is uninstalled.', 'seo-city-pages' ) . '</p>';
	}

	/**
	 * Field callback: Cache Mode.
	 *
	 * @return void
	 */
	public function render_cache_mode_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val     = ! empty( $options['cache_compatibility_mode'] ) ? 1 : 0;
		?>
		<label for="seo_city_pages_cache_mode">
			<input type="checkbox" id="seo_city_pages_cache_mode" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[cache_compatibility_mode]" value="1" <?php checked( 1, $val ); ?> />
			<?php esc_html_e( 'Enable automatic cache-busting headers on dynamic preview routes.', 'seo-city-pages' ); ?>
		</label>
		<?php
	}

	/**
	 * Field callback: Debug Mode.
	 *
	 * @return void
	 */
	public function render_debug_mode_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val     = ! empty( $options['debug_mode'] ) ? 1 : 0;
		?>
		<label for="seo_city_pages_debug_mode">
			<input type="checkbox" id="seo_city_pages_debug_mode" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[debug_mode]" value="1" <?php checked( 1, $val ); ?> />
			<?php esc_html_e( 'Enable verbose logging and advanced diagnostic reporting.', 'seo-city-pages' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Developer mode is strictly internal to authorized administrators and does not expose data to frontend visitors.', 'seo-city-pages' ); ?></p>
		<?php
	}

	/**
	 * Field callback: Log Level.
	 *
	 * @return void
	 */
	public function render_log_level_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val     = $options['log_level'] ?? 'info';
		?>
		<select id="seo_city_pages_log_level" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[log_level]">
			<option value="info" <?php selected( 'info', $val ); ?>><?php esc_html_e( 'Info (Record all operational events)', 'seo-city-pages' ); ?></option>
			<option value="warning" <?php selected( 'warning', $val ); ?>><?php esc_html_e( 'Warning (Record warnings and errors only)', 'seo-city-pages' ); ?></option>
			<option value="error" <?php selected( 'error', $val ); ?>><?php esc_html_e( 'Error (Record errors and critical events only)', 'seo-city-pages' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Field callback: Delete on Uninstall.
	 *
	 * @return void
	 */
	public function render_delete_on_uninstall_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val     = ! empty( $options['delete_data_on_uninstall'] ) ? 1 : 0;
		?>
		<label for="seo_city_pages_delete_uninstall" style="color: #d63638; font-weight: 500;">
			<input type="checkbox" id="seo_city_pages_delete_uninstall" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[delete_data_on_uninstall]" value="1" <?php checked( 1, $val ); ?> />
			<?php esc_html_e( 'Delete all plugin data, settings, and logs when plugin is uninstalled.', 'seo-city-pages' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Default is OFF. When disabled, your pages, data, and settings are preserved even if the plugin is uninstalled and reinstalled.', 'seo-city-pages' ); ?></p>
		<?php
	}

	/**
	 * Section callback: AI Content Engine.
	 *
	 * @return void
	 */
	public function render_ai_section_desc(): void {
		echo '<p class="description">' . esc_html__( 'Configure OpenAI API connection, model choice, temperature, and length preferences.', 'seo-city-pages' ) . '</p>';
	}

	public function render_ai_enabled_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = ! empty( $options['ai_enabled'] ) ? 1 : 0;
		?>
		<label for="scp_ai_enabled">
			<input type="checkbox" id="scp_ai_enabled" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_enabled]" value="1" <?php checked( 1, $val ); ?> />
			<?php esc_html_e( 'Enable OpenAI Content Generation features across the plugin.', 'seo-city-pages' ); ?>
		</label>
		<?php
	}

	public function render_ai_api_key_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$is_env_set = defined( 'SEO_CITY_OPENAI_API_KEY' ) || ! empty( getenv( 'SEO_CITY_OPENAI_API_KEY' ) );
		$has_saved_key = ! empty( $options['ai_api_key'] );
		?>
		<?php if ( $is_env_set ) : ?>
			<input type="text" value="•••••••••••••••••••••••••••••••• (Set via SEO_CITY_OPENAI_API_KEY Environment Variable)" class="regular-text" disabled />
			<p class="description"><?php esc_html_e( 'API key is securely loaded from your environment configuration.', 'seo-city-pages' ); ?></p>
		<?php else : ?>
			<input type="password" id="scp_ai_api_key" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_api_key]" value="<?php echo $has_saved_key ? '••••••••••••••••' : ''; ?>" placeholder="sk-proj-..." class="regular-text" autocomplete="new-password" />
			<p class="description"><?php esc_html_e( 'Your OpenAI secret API key. Stored securely and never exposed in frontend HTML or JavaScript.', 'seo-city-pages' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_ai_model_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['ai_model'] ?? 'gpt-4o-mini';
		$models = \SeoCityPages\AI\OpenAI_Provider::SUPPORTED_MODELS;
		?>
		<select id="scp_ai_model" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_model]">
			<?php foreach ( $models as $id => $label ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $val ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function render_ai_temperature_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = (float) ( $options['ai_temperature'] ?? 0.7 );
		?>
		<input type="number" step="0.1" min="0.0" max="1.5" id="scp_ai_temperature" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_temperature]" value="<?php echo esc_attr( $val ); ?>" class="small-text" />
		<p class="description"><?php esc_html_e( 'Controls creativity (0.0 = deterministic/focused, 0.7 = standard creative, 1.0+ = highly creative). Default: 0.7.', 'seo-city-pages' ); ?></p>
		<?php
	}

	public function render_ai_length_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['ai_content_length'] ?? 'medium';
		?>
		<select id="scp_ai_content_length" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_content_length]">
			<option value="short" <?php selected( 'short', $val ); ?>><?php esc_html_e( 'Short (Concise, punchy copy)', 'seo-city-pages' ); ?></option>
			<option value="medium" <?php selected( 'medium', $val ); ?>><?php esc_html_e( 'Medium (Balanced landing page copy — Recommended)', 'seo-city-pages' ); ?></option>
			<option value="comprehensive" <?php selected( 'comprehensive', $val ); ?>><?php esc_html_e( 'Comprehensive (Detailed in-depth sections)', 'seo-city-pages' ); ?></option>
		</select>
		<?php
	}

	public function render_business_section_desc(): void {
		echo '<p class="description">' . esc_html__( 'Manage your global company details and value proposition. These details are used as reference context during generation without hallucinating unsupported claims.', 'seo-city-pages' ) . '</p>';
	}

	public function render_ai_company_name_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['ai_company_name'] ?? 'Vispan Solutions';
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_company_name]" value="<?php echo esc_attr( $val ); ?>" class="regular-text" />
		<?php
	}

	public function render_ai_company_desc_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['ai_company_description'] ?? '';
		?>
		<textarea name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_company_description]" rows="3" class="large-text"><?php echo esc_textarea( $val ); ?></textarea>
		<?php
	}

	public function render_ai_core_services_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['ai_core_services'] ?? '';
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_core_services]" value="<?php echo esc_attr( $val ); ?>" class="large-text" />
		<?php
	}

	public function render_ai_differentiators_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['ai_differentiators'] ?? '';
		?>
		<textarea name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_differentiators]" rows="2" class="large-text"><?php echo esc_textarea( $val ); ?></textarea>
		<?php
	}

	public function render_notifications_section_desc(): void {
		echo '<p class="description">' . esc_html__( 'Configure recipient email addresses for marketing proposal leads submitted via landing page inquiry forms.', 'seo-city-pages' ) . '</p>';
	}

	public function render_inquiry_emails_field(): void {
		$options     = get_option( self::OPTION_NAME, self::get_defaults() );
		$val         = $options['inquiry_notification_emails'] ?? '';
		$admin_email = get_option( 'admin_email' );
		?>
		<textarea id="scp_inquiry_notification_emails" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[inquiry_notification_emails]" rows="2" class="large-text" placeholder="<?php echo esc_attr( $admin_email ); ?>"><?php echo esc_textarea( $val ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Enter one or more specific email addresses (comma-separated) to receive instant notifications whenever a user submits an inquiry form. If left empty, notifications will be sent to the default administration email: ', 'seo-city-pages' ); ?>
			<code><?php echo esc_html( $admin_email ); ?></code>
		</p>
		<?php
	}

	public function render_smtp_enabled_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = ! empty( $options['smtp_enabled'] ) ? 1 : 0;
		?>
		<label for="scp_smtp_enabled">
			<input type="checkbox" id="scp_smtp_enabled" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[smtp_enabled]" value="1" <?php checked( 1, $val ); ?> />
			<?php esc_html_e( 'Enable Custom SMTP delivery for lead notification emails (e.g. via Gmail, Brevo, SendGrid, Amazon SES).', 'seo-city-pages' ); ?>
		</label>
		<?php
	}

	public function render_smtp_host_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['smtp_host'] ?? '';
		?>
		<input type="text" id="scp_smtp_host" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[smtp_host]" value="<?php echo esc_attr( $val ); ?>" placeholder="smtp.gmail.com" class="regular-text" />
		<p class="description"><?php esc_html_e( 'SMTP server hostname (e.g. smtp.gmail.com, smtp-relay.brevo.com, smtp.mailgun.org).', 'seo-city-pages' ); ?></p>
		<?php
	}

	public function render_smtp_port_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$port = $options['smtp_port'] ?? 587;
		$encryption = $options['smtp_encryption'] ?? 'tls';
		?>
		<div style="display: flex; gap: 12px; align-items: center;">
			<div>
				<label for="scp_smtp_port"><?php esc_html_e( 'Port:', 'seo-city-pages' ); ?></label>
				<input type="number" id="scp_smtp_port" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[smtp_port]" value="<?php echo esc_attr( $port ); ?>" class="small-text" />
			</div>
			<div>
				<label for="scp_smtp_encryption"><?php esc_html_e( 'Encryption:', 'seo-city-pages' ); ?></label>
				<select id="scp_smtp_encryption" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[smtp_encryption]">
					<option value="tls" <?php selected( 'tls', $encryption ); ?>>TLS (Recommended for port 587)</option>
					<option value="ssl" <?php selected( 'ssl', $encryption ); ?>>SSL (Port 465)</option>
					<option value="none" <?php selected( 'none', $encryption ); ?>>None</option>
				</select>
			</div>
		</div>
		<?php
	}

	public function render_smtp_username_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['smtp_username'] ?? '';
		?>
		<input type="text" id="scp_smtp_username" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[smtp_username]" value="<?php echo esc_attr( $val ); ?>" placeholder="your-email@gmail.com" class="regular-text" autocomplete="off" />
		<p class="description"><?php esc_html_e( 'Your SMTP account username or email address.', 'seo-city-pages' ); ?></p>
		<?php
	}

	public function render_smtp_password_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$has_pass = ! empty( $options['smtp_password'] );
		?>
		<div style="display: flex; gap: 8px; align-items: center; max-width: 400px;">
			<input type="password" id="scp_smtp_password" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[smtp_password]" value="<?php echo $has_pass ? '••••••••••••••••' : ''; ?>" placeholder="16-character App Password" class="regular-text" autocomplete="new-password" style="flex: 1;" />
			<button type="button" id="scp_toggle_smtp_pass" class="button button-secondary" style="height: 30px; line-height: 28px; padding: 0 10px;">
				<span class="dashicons dashicons-visibility" style="vertical-align: middle; margin-top: -2px;"></span>
			</button>
		</div>
		<p class="description"><?php esc_html_e( 'Your 16-character Gmail App Password (without spaces). Stored securely in WordPress database.', 'seo-city-pages' ); ?></p>
		<?php
	}

	public function render_smtp_from_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$from_email = $options['smtp_from_email'] ?? '';
		$from_name  = $options['smtp_from_name'] ?? 'Vispan Solutions';
		?>
		<div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
			<div>
				<label for="scp_smtp_from_email"><?php esc_html_e( 'From Email:', 'seo-city-pages' ); ?></label>
				<input type="email" id="scp_smtp_from_email" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[smtp_from_email]" value="<?php echo esc_attr( $from_email ); ?>" placeholder="leads@vispansolutions.com" class="regular-text" />
			</div>
			<div>
				<label for="scp_smtp_from_name"><?php esc_html_e( 'From Name:', 'seo-city-pages' ); ?></label>
				<input type="text" id="scp_smtp_from_name" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[smtp_from_name]" value="<?php echo esc_attr( $from_name ); ?>" placeholder="Vispan Solutions" class="regular-text" />
			</div>
		</div>

		<div style="margin-top: 16px; padding-top: 14px; border-top: 1px dashed #c3c4c7;">
			<button type="button" id="scp_btn_send_test_email" class="button button-secondary" style="height: 32px; line-height: 30px;">
				<span class="dashicons dashicons-email-alt" style="margin-top: 4px;"></span> <?php esc_html_e( 'Send Test Email', 'seo-city-pages' ); ?>
			</button>
			<span id="scp_test_email_status" style="margin-left: 12px; font-weight: 600; font-size: 13px;"></span>
			<p class="description" style="margin-top: 6px;">
				<?php esc_html_e( 'Click to test your SMTP configuration. If using Gmail, generate a 16-character App Password at myaccount.google.com/apppasswords.', 'seo-city-pages' ); ?>
			</p>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var toggleBtn = document.getElementById('scp_toggle_smtp_pass');
			var passInput = document.getElementById('scp_smtp_password');
			if (toggleBtn && passInput) {
				toggleBtn.addEventListener('click', function(e) {
					e.preventDefault();
					if (passInput.type === 'password') {
						passInput.type = 'text';
						toggleBtn.innerHTML = '<span class="dashicons dashicons-hidden" style="vertical-align: middle; margin-top: -2px;"></span>';
					} else {
						passInput.type = 'password';
						toggleBtn.innerHTML = '<span class="dashicons dashicons-visibility" style="vertical-align: middle; margin-top: -2px;"></span>';
					}
				});
			}

			var testBtn = document.getElementById('scp_btn_send_test_email');
			var statusSpan = document.getElementById('scp_test_email_status');
			if (!testBtn || !statusSpan) return;

			testBtn.addEventListener('click', function(e) {
				e.preventDefault();
				testBtn.disabled = true;
				statusSpan.style.color = '#0073aa';
				statusSpan.textContent = 'Sending test email...';

				var data = new FormData();
				data.append('action', 'seo_city_send_test_email');
				data.append('nonce', '<?php echo esc_js( wp_create_nonce( \SeoCityPages\Frontend\Inquiry_Handler::NONCE_ACTION ) ); ?>');

				// Append current form field values
				var host = document.getElementById('scp_smtp_host');
				var port = document.getElementById('scp_smtp_port');
				var enc  = document.getElementById('scp_smtp_encryption');
				var user = document.getElementById('scp_smtp_username');
				var pass = document.getElementById('scp_smtp_password');
				var from = document.getElementById('scp_smtp_from_email');
				var name = document.getElementById('scp_smtp_from_name');
				var rec  = document.getElementById('scp_inquiry_notification_emails');

				if (host) data.append('smtp_host', host.value);
				if (port) data.append('smtp_port', port.value);
				if (enc)  data.append('smtp_encryption', enc.value);
				if (user) data.append('smtp_username', user.value);
				if (pass) data.append('smtp_password', pass.value);
				if (from) data.append('smtp_from_email', from.value);
				if (name) data.append('smtp_from_name', name.value);
				if (rec && rec.value) data.append('test_recipient', rec.value.split(',')[0].trim());

				fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
					method: 'POST',
					body: data
				})
				.then(function(res) { return res.json(); })
				.then(function(resData) {
					testBtn.disabled = false;
					if (resData && resData.success) {
						statusSpan.style.color = '#008a20';
						statusSpan.textContent = '✓ ' + (resData.data && resData.data.message ? resData.data.message : 'Test email sent successfully!');
					} else {
						statusSpan.style.color = '#d63638';
						statusSpan.textContent = '⚠ ' + (resData.data && resData.data.message ? resData.data.message : 'Error sending test email.');
					}
				})
				.catch(function(err) {
					testBtn.disabled = false;
					statusSpan.style.color = '#d63638';
					statusSpan.textContent = '⚠ Network request failed.';
				});
			});
		});
		</script>
		<?php
	}

	public function render_voice_section_desc(): void {
		echo '<p class="description">' . esc_html__( 'Define the voice, tone, and negative constraints for all generated landing page copy.', 'seo-city-pages' ) . '</p>';
	}

	public function render_ai_tone_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['ai_tone'] ?? 'Professional, authoritative, direct, and compelling';
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_tone]" value="<?php echo esc_attr( $val ); ?>" class="regular-text" />
		<?php
	}

	public function render_ai_prohibited_field(): void {
		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$val = $options['ai_prohibited_phrases'] ?? '';
		?>
		<textarea name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ai_prohibited_phrases]" rows="2" class="large-text"><?php echo esc_textarea( $val ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Comma-separated list of phrases or exaggerated promises the model is strictly forbidden from generating.', 'seo-city-pages' ); ?></p>
		<?php
	}

	/**
	 * Get masked representation of the active API key for safe UI display.
	 *
	 * @return string Masked API key string.
	 */
	public static function get_masked_api_key(): string {
		if ( defined( 'SEO_CITY_OPENAI_API_KEY' ) && ! empty( constant( 'SEO_CITY_OPENAI_API_KEY' ) ) ) {
			return '•••••••••••••••••••••••••••••••• (Set via SEO_CITY_OPENAI_API_KEY Environment Variable)';
		}
		$env_key = getenv( 'SEO_CITY_OPENAI_API_KEY' );
		if ( ! empty( $env_key ) ) {
			return '•••••••••••••••••••••••••••••••• (Set via SEO_CITY_OPENAI_API_KEY Environment Variable)';
		}

		$options = get_option( self::OPTION_NAME, self::get_defaults() );
		$key     = trim( (string) ( $options['ai_api_key'] ?? '' ) );

		if ( empty( $key ) ) {
			return '';
		}

		if ( strlen( $key ) <= 8 ) {
			return '••••••••';
		}

		$prefix = substr( $key, 0, 3 );
		$suffix = substr( $key, -4 );
		return $prefix . '••••••••••••' . $suffix;
	}

	/**
	 * Helper to sanitize email safely across environments.
	 *
	 * @param mixed $email
	 * @return string
	 */
	private function clean_email( $email ): string {
		if ( function_exists( 'sanitize_email' ) ) {
			return sanitize_email( $email );
		}
		return trim( (string) filter_var( $email, FILTER_SANITIZE_EMAIL ) );
	}

	/**
	 * Helper to validate email safely across environments.
	 *
	 * @param mixed $email
	 * @return bool
	 */
	private function validate_email( $email ): bool {
		if ( function_exists( 'is_email' ) ) {
			return (bool) is_email( $email );
		}
		return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Render the Settings page view wrapper.
	 *
	 * @return void
	 */
	public function render(): void {
		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/settings.php';
	}
}
