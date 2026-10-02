<?php
/**
 * Frontend Marketing Proposal Inquiry Form Handler.
 *
 * Handles AJAX lead submissions, input validation, spam prevention,
 * audit logging, and administrator email dispatch.
 *
 * @package SeoCityPages\Frontend
 */

namespace SeoCityPages\Frontend;

use SeoCityPages\Logging\Logger;
use SeoCityPages\Logging\Audit_Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inquiry_Handler Class.
 */
class Inquiry_Handler {

	/**
	 * Nonce action string.
	 */
	public const NONCE_ACTION = 'seo_city_inquiry_nonce';

	/**
	 * Initialize hooks for AJAX form submission and SMTP mailer.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_ajax_seo_city_submit_inquiry', [ self::class, 'handle_submission' ] );
		add_action( 'wp_ajax_nopriv_seo_city_submit_inquiry', [ self::class, 'handle_submission' ] );
		add_action( 'wp_ajax_seo_city_send_test_email', [ self::class, 'handle_test_email' ] );
		add_action( 'phpmailer_init', [ self::class, 'configure_phpmailer' ] );
	}

	/**
	 * Configure PHPMailer with custom SMTP credentials if enabled in plugin settings.
	 *
	 * @param object $phpmailer PHPMailer instance.
	 * @return void
	 */
	public static function configure_phpmailer( $phpmailer ): void {
		if ( ! class_exists( '\\SeoCityPages\\Admin\\Settings' ) ) {
			return;
		}

		$settings = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME, \SeoCityPages\Admin\Settings::get_defaults() );
		if ( empty( $settings['smtp_enabled'] ) || empty( $settings['smtp_host'] ) ) {
			return;
		}

		if ( method_exists( $phpmailer, 'isSMTP' ) ) {
			$phpmailer->isSMTP();
		}

		$username = trim( (string) ( $settings['smtp_username'] ?? '' ) );
		$password = trim( (string) ( $settings['smtp_password'] ?? '' ) );

		// If Gmail host, strip spaces if user entered App Password with spaces
		if ( false !== stripos( (string) $settings['smtp_host'], 'gmail' ) ) {
			$password = str_replace( ' ', '', $password );
		}

		$phpmailer->Host       = $settings['smtp_host'];
		$phpmailer->SMTPAuth   = ! empty( $username );
		$phpmailer->Port       = ! empty( $settings['smtp_port'] ) ? (int) $settings['smtp_port'] : 587;
		$phpmailer->Username   = $username;
		$phpmailer->Password   = $password;

		$encryption = strtolower( (string) ( $settings['smtp_encryption'] ?? 'tls' ) );
		if ( 'ssl' === $encryption ) {
			$phpmailer->SMTPSecure = 'ssl';
		} elseif ( 'tls' === $encryption ) {
			$phpmailer->SMTPSecure = 'tls';
		} else {
			$phpmailer->SMTPSecure  = '';
			$phpmailer->SMTPAutoTLS = false;
		}

		if ( ! empty( $settings['smtp_from_email'] ) && is_email( $settings['smtp_from_email'] ) ) {
			$phpmailer->From = $settings['smtp_from_email'];
		} elseif ( ! empty( $username ) && is_email( $username ) ) {
			$phpmailer->From = $username;
		}

		if ( ! empty( $settings['smtp_from_name'] ) ) {
			$phpmailer->FromName = $settings['smtp_from_name'];
		}
	}

	/**
	 * AJAX handler for sending an administrative SMTP test email.
	 *
	 * @return void
	 */
	public static function handle_test_email(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'seo-city-pages' ) ], 403 );
			return;
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! empty( $nonce ) && function_exists( 'wp_verify_nonce' ) ) {
			if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				wp_send_json_error( [ 'message' => __( 'Security verification failed. Please refresh and try again.', 'seo-city-pages' ) ], 403 );
				return;
			}
		}

		$recipient = isset( $_POST['test_recipient'] ) ? sanitize_email( wp_unslash( $_POST['test_recipient'] ) ) : '';
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			$settings  = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME, \SeoCityPages\Admin\Settings::get_defaults() );
			$emails    = preg_split( '/[\r\n,]+/', (string) ( $settings['inquiry_notification_emails'] ?? '' ) );
			$recipient = ! empty( $emails[0] ) && is_email( trim( $emails[0] ) ) ? trim( $emails[0] ) : get_option( 'admin_email' );
		}

		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			wp_send_json_error( [ 'message' => __( 'No valid recipient email address specified.', 'seo-city-pages' ) ], 400 );
			return;
		}

		// Check if dynamic SMTP credentials were submitted from the Settings test button
		$override_host = isset( $_POST['smtp_host'] ) ? sanitize_text_field( wp_unslash( $_POST['smtp_host'] ) ) : '';
		$override_user = isset( $_POST['smtp_username'] ) ? sanitize_text_field( wp_unslash( $_POST['smtp_username'] ) ) : '';
		$override_pass = isset( $_POST['smtp_password'] ) ? sanitize_text_field( wp_unslash( $_POST['smtp_password'] ) ) : '';
		$override_port = isset( $_POST['smtp_port'] ) ? absint( wp_unslash( $_POST['smtp_port'] ) ) : 587;
		$override_enc  = isset( $_POST['smtp_encryption'] ) ? sanitize_text_field( wp_unslash( $_POST['smtp_encryption'] ) ) : 'tls';
		$override_from = isset( $_POST['smtp_from_email'] ) ? sanitize_email( wp_unslash( $_POST['smtp_from_email'] ) ) : '';
		$override_name = isset( $_POST['smtp_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['smtp_from_name'] ) ) : '';

		$dynamic_phpmailer_hook = null;
		if ( ! empty( $override_host ) && ! empty( $override_user ) && ! empty( $override_pass ) && false === strpos( $override_pass, '••••' ) ) {
			$dynamic_phpmailer_hook = function( $phpmailer ) use ( $override_host, $override_user, $override_pass, $override_port, $override_enc, $override_from, $override_name ) {
				$clean_pass = trim( $override_pass );
				if ( false !== stripos( $override_host, 'gmail' ) ) {
					$clean_pass = str_replace( ' ', '', $clean_pass );
				}

				$phpmailer->isSMTP();
				$phpmailer->Host     = $override_host;
				$phpmailer->SMTPAuth = true;
				$phpmailer->Port     = $override_port ?: 587;
				$phpmailer->Username = trim( $override_user );
				$phpmailer->Password = $clean_pass;

				$enc = strtolower( $override_enc );
				if ( 'ssl' === $enc ) {
					$phpmailer->SMTPSecure = 'ssl';
				} elseif ( 'tls' === $enc ) {
					$phpmailer->SMTPSecure = 'tls';
				} else {
					$phpmailer->SMTPSecure  = '';
					$phpmailer->SMTPAutoTLS = false;
				}

				if ( ! empty( $override_from ) && is_email( $override_from ) ) {
					$phpmailer->From = $override_from;
				} elseif ( is_email( $override_user ) ) {
					$phpmailer->From = $override_user;
				}

				if ( ! empty( $override_name ) ) {
					$phpmailer->FromName = $override_name;
				}
			};
			add_action( 'phpmailer_init', $dynamic_phpmailer_hook, 999 );
		}

		$last_error = '';
		$failed_hook = function( $wp_error ) use ( &$last_error ) {
			if ( is_wp_error( $wp_error ) ) {
				$last_error = $wp_error->get_error_message();
			}
		};
		add_action( 'wp_mail_failed', $failed_hook );

		$subject = sprintf( '[SEO City Pages] Test Email Delivery (%s)', current_time( 'Y-m-d H:i:s' ) );
		$message = "<h2>SEO City Pages — SMTP Test Email</h2>\n<p>Congratulations! Your SMTP and email delivery configuration in SEO City Pages is active and functioning properly.</p>\n<p>Inquiry proposal submissions from your landing pages will be delivered directly to your inbox.</p>";
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		$sent = wp_mail( $recipient, $subject, $message, $headers );
		remove_action( 'wp_mail_failed', $failed_hook );
		if ( $dynamic_phpmailer_hook ) {
			remove_action( 'phpmailer_init', $dynamic_phpmailer_hook, 999 );
		}

		if ( $sent ) {
			wp_send_json_success( [
				'message'   => sprintf( __( 'Test email successfully dispatched to %s!', 'seo-city-pages' ), $recipient ),
				'recipient' => $recipient,
			] );
		} else {
			$err_detail = ! empty( $last_error ) ? $last_error : __( 'Mail sending failed. Please verify your SMTP hostname, username, and password.', 'seo-city-pages' );
			if ( false !== stripos( $err_detail, 'BadCredentials' ) || false !== stripos( $err_detail, 'Authentication Required' ) || false !== stripos( $err_detail, 'Could not authenticate' ) ) {
				$err_detail = __( 'Authentication failed: Gmail requires a 16-character Google App Password (not your regular Gmail password). Generate one at Google Account > Security > 2-Step Verification > App Passwords.', 'seo-city-pages' );
			}
			wp_send_json_error( [
				'message' => $err_detail,
			], 500 );
		}
	}

	/**
	 * Handle AJAX form submission.
	 *
	 * @return void
	 */
	public static function handle_submission(): void {
		// 0. Enable CORS / Cross-Origin requests.
		if ( function_exists( 'send_origin_headers' ) ) {
			send_origin_headers();
		}
		if ( ! headers_sent() ) {
			$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
			header( 'Access-Control-Allow-Origin: ' . $origin );
			header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Content-Type, X-Requested-With' );
			header( 'Access-Control-Allow-Credentials: true' );
		}
		if ( 'OPTIONS' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			if ( function_exists( 'status_header' ) ) {
				status_header( 200 );
			}
			exit;
		}

		// 1. Verify CSRF Nonce if present.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! empty( $nonce ) && function_exists( 'wp_verify_nonce' ) ) {
			if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				wp_send_json_error( [
					'message' => __( 'Security verification failed. Please refresh and try again.', 'seo-city-pages' ),
				], 403 );
				return;
			}
		}

		// 2. Honeypot Spam Check.
		$honeypot = isset( $_POST['inquiry_hp_check'] ) ? sanitize_text_field( wp_unslash( $_POST['inquiry_hp_check'] ) ) : '';
		if ( ! empty( $honeypot ) ) {
			// Silently reject bots without revealing error.
			wp_send_json_success( [
				'message' => __( 'Thank you! Your proposal request has been submitted successfully.', 'seo-city-pages' ),
			] );
			return;
		}

		// 3. Extract and Sanitize Inputs.
		$full_name    = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$country_code = isset( $_POST['country_code'] ) ? sanitize_text_field( wp_unslash( $_POST['country_code'] ) ) : '+91 IND';
		$phone        = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$service      = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';
		$company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['company_name'] ) ) : '';
		$website      = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : '';
		$budget       = isset( $_POST['budget'] ) ? sanitize_text_field( wp_unslash( $_POST['budget'] ) ) : '';
		$details      = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';
		$page_id      = isset( $_POST['page_id'] ) ? absint( wp_unslash( $_POST['page_id'] ) ) : 0;
		$page_url     = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
		$form_layout  = isset( $_POST['form_layout'] ) ? sanitize_key( wp_unslash( $_POST['form_layout'] ) ) : 'hero';

		// 4. Validate Required Fields.
		$errors = [];
		if ( empty( $full_name ) ) {
			$errors[] = __( 'Full Name is required.', 'seo-city-pages' );
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			$errors[] = __( 'A valid Email Address is required.', 'seo-city-pages' );
		}
		if ( empty( $phone ) ) {
			$errors[] = __( 'Contact Number is required.', 'seo-city-pages' );
		}
		if ( empty( $service ) ) {
			$errors[] = __( 'Please select a service.', 'seo-city-pages' );
		}
		if ( empty( $company_name ) ) {
			$errors[] = __( 'Company Name is required.', 'seo-city-pages' );
		}
		if ( empty( $website ) ) {
			$errors[] = __( 'Website/Landing Page is required.', 'seo-city-pages' );
		}
		if ( empty( $budget ) ) {
			$errors[] = __( 'Budget selection is required.', 'seo-city-pages' );
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( [
				'message' => implode( ' ', $errors ),
				'errors'  => $errors,
			], 400 );
			return;
		}

		// 5. Construct Lead Payload.
		$lead_data = [
			'full_name'    => $full_name,
			'email'        => $email,
			'country_code' => $country_code,
			'phone'        => $phone,
			'full_phone'   => trim( $country_code . ' ' . $phone ),
			'service'      => $service,
			'company_name' => $company_name,
			'website'      => $website,
			'budget'       => $budget,
			'details'      => $details,
			'page_id'      => $page_id,
			'page_url'     => $page_url,
			'form_layout'  => $form_layout,
			'submitted_at' => current_time( 'mysql' ),
			'user_ip'      => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
		];

		// 6. Log Lead in Audit Trail.
		if ( class_exists( Audit_Logger::class ) ) {
			Audit_Logger::log(
				'inquiry_submission',
				'city_page',
				$page_id,
				$lead_data
			);
		}

		Logger::info( sprintf( 'New proposal inquiry received from "%s" <%s> for service "%s" (Layout: %s)', $full_name, $email, $service, $form_layout ) );

		// 7. Dispatch Email Notification to Administrator.
		self::send_admin_notification( $lead_data );

		// 8. Return Success Response.
		wp_send_json_success( [
			'message' => __( 'Thank you! Your marketing proposal request has been submitted successfully. Our team will contact you shortly.', 'seo-city-pages' ),
			'lead'    => [
				'name'    => $full_name,
				'service' => $service,
			],
		] );
	}

	/**
	 * Send formatted HTML email notification to configured recipients and site admin.
	 *
	 * @param array $lead Lead submission details.
	 * @return bool
	 */
	public static function send_admin_notification( array $lead ): bool {
		$recipients = [];

		// 1. Check custom configured notification emails from plugin settings.
		if ( class_exists( '\\SeoCityPages\\Admin\\Settings' ) ) {
			$settings = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME, \SeoCityPages\Admin\Settings::get_defaults() );
			$configured_emails = $settings['inquiry_notification_emails'] ?? '';
			if ( ! empty( $configured_emails ) ) {
				$email_list = preg_split( '/[\r\n,]+/', (string) $configured_emails );
				foreach ( $email_list as $em ) {
					$clean_em = sanitize_email( trim( $em ) );
					if ( ! empty( $clean_em ) && is_email( $clean_em ) ) {
						$recipients[] = $clean_em;
					}
				}
			}
		}

		// 2. Fallback to default WordPress admin email if no custom recipients are configured.
		if ( empty( $recipients ) ) {
			$admin_email = get_option( 'admin_email' );
			if ( ! empty( $admin_email ) && is_email( $admin_email ) ) {
				$recipients[] = $admin_email;
			}
		}

		$recipients = array_values( array_unique( $recipients ) );

		/**
		 * Filters the recipient email addresses for new marketing proposal inquiry submissions.
		 *
		 * @param array $recipients List of valid email addresses.
		 * @param array $lead       Lead submission payload.
		 */
		$recipients = apply_filters( 'seo_city_inquiry_notification_emails', $recipients, $lead );

		if ( empty( $recipients ) ) {
			Logger::warning( 'Inquiry email notification skipped: No valid recipient email addresses configured.' );
			return false;
		}

		$subject = sprintf( '🚀 [New Lead] %s - %s (%s)', $lead['full_name'], $lead['company_name'], $lead['service'] );

		// Clean sanitized values for HTML email rendering
		$full_name    = esc_html( $lead['full_name'] ?? '' );
		$email_raw    = $lead['email'] ?? '';
		$email_esc    = esc_html( $email_raw );
		$email_attr   = esc_attr( $email_raw );
		$phone_raw    = $lead['full_phone'] ?? ( ( $lead['country_code'] ?? '' ) . ' ' . ( $lead['phone'] ?? '' ) );
		$phone_esc    = esc_html( trim( $phone_raw ) );
		$phone_tel    = esc_attr( preg_replace( '/[^0-9+]/', '', $phone_raw ) );
		$service      = esc_html( $lead['service'] ?? 'General Inquiry' );
		$company      = esc_html( $lead['company_name'] ?? 'N/A' );
		$website_raw  = $lead['website'] ?? '';
		$website_url  = ! empty( $website_raw ) ? esc_url( $website_raw ) : '';
		$website_esc  = ! empty( $website_raw ) ? esc_html( $website_raw ) : 'N/A';
		$budget       = esc_html( $lead['budget'] ?? 'Not specified' );
		$details_esc  = ! empty( $lead['details'] ) ? nl2br( esc_html( $lead['details'] ) ) : '';
		$page_url     = ! empty( $lead['page_url'] ) ? esc_url( $lead['page_url'] ) : '';
		$submitted_at = esc_html( $lead['submitted_at'] ?? current_time( 'mysql' ) );
		$user_ip      = esc_html( $lead['user_ip'] ?? '' );
		$site_name    = esc_html( function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : 'SEO City Pages' );

		// Modern, Colorful, High-Conversion HTML Email Template
		$message  = '<!DOCTYPE html>' . "\n";
		$message .= '<html lang="en">' . "\n";
		$message .= '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . esc_html( $subject ) . '</title></head>' . "\n";
		$message .= '<body style="margin: 0; padding: 25px 10px; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1e293b;">' . "\n";

		$message .= '<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 620px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0;">' . "\n";

		// Colorful Gradient Header Banner
		$message .= '  <tr>' . "\n";
		$message .= '    <td style="background: linear-gradient(135deg, #003865 0%, #0284c7 60%, #2563eb 100%); padding: 32px 30px; text-align: left; color: #ffffff;">' . "\n";
		$message .= '      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">' . "\n";
		$message .= '        <tr>' . "\n";
		$message .= '          <td>' . "\n";
		$message .= '            <span style="display: inline-block; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(4px); color: #ffffff; font-size: 11px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.35); margin-bottom: 12px;">⚡ NEW PROPOSAL INQUIRY</span>' . "\n";
		$message .= '            <h1 style="margin: 0 0 6px 0; color: #ffffff; font-size: 24px; font-weight: 800; line-height: 1.3;">New Lead Received!</h1>' . "\n";
		$message .= '            <p style="margin: 0; color: #e0f2fe; font-size: 14px; line-height: 1.5;">A prospective client has requested a custom marketing proposal.</p>' . "\n";
		$message .= '          </td>' . "\n";
		$message .= '        </tr>' . "\n";
		$message .= '      </table>' . "\n";
		$message .= '    </td>' . "\n";
		$message .= '  </tr>' . "\n";

		// Highlight Badges (Service & Budget)
		$message .= '  <tr>' . "\n";
		$message .= '    <td style="padding: 20px 30px 10px 30px; background-color: #f8fafc; border-bottom: 1px solid #edf2f7;">' . "\n";
		$message .= '      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">' . "\n";
		$message .= '        <tr>' . "\n";
		$message .= '          <td style="vertical-align: middle;">' . "\n";
		$message .= '            <div style="display: inline-block; margin-right: 12px; margin-bottom: 8px;">' . "\n";
		$message .= '              <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; display: block; margin-bottom: 3px;">Requested Service</span>' . "\n";
		$message .= '              <span style="display: inline-block; background-color: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 13px; padding: 5px 12px; border-radius: 8px; border: 1px solid #bae6fd;">🛠️ ' . $service . '</span>' . "\n";
		$message .= '            </div>' . "\n";
		$message .= '            <div style="display: inline-block; margin-bottom: 8px;">' . "\n";
		$message .= '              <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; display: block; margin-bottom: 3px;">Monthly Budget</span>' . "\n";
		$message .= '              <span style="display: inline-block; background-color: #ecfdf5; color: #047857; font-weight: 700; font-size: 13px; padding: 5px 12px; border-radius: 8px; border: 1px solid #a7f3d0;">💰 ' . $budget . '</span>' . "\n";
		$message .= '            </div>' . "\n";
		$message .= '          </td>' . "\n";
		$message .= '        </tr>' . "\n";
		$message .= '      </table>' . "\n";
		$message .= '    </td>' . "\n";
		$message .= '  </tr>' . "\n";

		// Main Details Section
		$message .= '  <tr>' . "\n";
		$message .= '    <td style="padding: 24px 30px;">' . "\n";

		// Contact & Company Cards Table
		$message .= '      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; margin-bottom: 20px;">' . "\n";

		// Full Name
		$message .= '        <tr>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 13px; font-weight: 700; color: #475569; width: 34%; border-bottom: 1px solid #f1f5f9;">👤 Full Name:</td>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 14px; font-weight: 600; color: #0f172a; border-bottom: 1px solid #f1f5f9;">' . $full_name . '</td>' . "\n";
		$message .= '        </tr>' . "\n";

		// Email Address
		$message .= '        <tr>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 13px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">✉️ Email Address:</td>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 14px; font-weight: 600; color: #0284c7; border-bottom: 1px solid #f1f5f9;"><a href="mailto:' . $email_attr . '" style="color: #0284c7; text-decoration: none; font-weight: 600;">' . $email_esc . '</a></td>' . "\n";
		$message .= '        </tr>' . "\n";

		// Phone Number
		$message .= '        <tr>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 13px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">📞 Phone Number:</td>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 14px; font-weight: 600; color: #0f172a; border-bottom: 1px solid #f1f5f9;"><a href="tel:' . $phone_tel . '" style="color: #0f172a; text-decoration: none; font-weight: 600;">' . $phone_esc . '</a></td>' . "\n";
		$message .= '        </tr>' . "\n";

		// Company Name
		$message .= '        <tr>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 13px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">🏢 Company Name:</td>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 14px; font-weight: 600; color: #0f172a; border-bottom: 1px solid #f1f5f9;">' . $company . '</td>' . "\n";
		$message .= '        </tr>' . "\n";

		// Website
		$message .= '        <tr>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 13px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">🌐 Website:</td>' . "\n";
		$message .= '          <td style="padding: 12px 14px; font-size: 14px; color: #0f172a; border-bottom: 1px solid #f1f5f9;">' . ( ! empty( $website_url ) ? '<a href="' . $website_url . '" target="_blank" style="color: #2563eb; text-decoration: underline;">' . $website_esc . '</a>' : '<span style="color: #94a3b8;">Not provided</span>' ) . '</td>' . "\n";
		$message .= '        </tr>' . "\n";

		// Landing Page Source
		if ( ! empty( $page_url ) ) {
			$message .= '        <tr>' . "\n";
			$message .= '          <td style="padding: 12px 14px; font-size: 13px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">📍 Landing Page:</td>' . "\n";
			$message .= '          <td style="padding: 12px 14px; font-size: 13px; color: #64748b; border-bottom: 1px solid #f1f5f9;"><a href="' . $page_url . '" target="_blank" style="color: #2563eb; word-break: break-all; text-decoration: none;">' . $page_url . '</a></td>' . "\n";
			$message .= '        </tr>' . "\n";
		}

		$message .= '      </table>' . "\n";

		// Additional Details / Notes Callout Box
		if ( ! empty( $details_esc ) ) {
			$message .= '      <div style="background-color: #f8fafc; border-left: 4px solid #2563eb; border-radius: 0 10px 10px 0; padding: 16px 18px; margin-bottom: 24px;">' . "\n";
			$message .= '        <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; color: #1e40af; margin-bottom: 6px; letter-spacing: 0.5px;">💬 Project Requirements & Notes:</div>' . "\n";
			$message .= '        <div style="font-size: 14px; line-height: 1.6; color: #334155;">' . $details_esc . '</div>' . "\n";
			$message .= '      </div>' . "\n";
		}

		// Action Call-to-Action Buttons
		$message .= '      <div style="text-align: center; padding: 15px 0 10px 0;">' . "\n";
		$message .= '        <a href="mailto:' . $email_attr . '?subject=' . rawurlencode( 'Re: Your ' . ( $lead['service'] ?? 'Marketing' ) . ' Proposal Request' ) . '" style="display: inline-block; background: linear-gradient(135deg, #003865 0%, #2563eb 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 8px; margin: 4px 6px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">✉️ Reply to ' . $full_name . '</a>' . "\n";
		if ( ! empty( $phone_tel ) ) {
			$message .= '        <a href="tel:' . $phone_tel . '" style="display: inline-block; background-color: #f1f5f9; color: #334155; text-decoration: none; font-size: 14px; font-weight: 600; padding: 12px 20px; border-radius: 8px; border: 1px solid #cbd5e1; margin: 4px 6px;">📞 Call Client</a>' . "\n";
		}
		$message .= '      </div>' . "\n";

		$message .= '    </td>' . "\n";
		$message .= '  </tr>' . "\n";

		// Clean Footer
		$message .= '  <tr>' . "\n";
		$message .= '    <td style="padding: 20px 30px; background-color: #f8fafc; border-top: 1px solid #edf2f7; text-align: center; color: #94a3b8; font-size: 12px; line-height: 1.6;">' . "\n";
		$message .= '      <p style="margin: 0 0 4px 0;">Submitted on <strong>' . $submitted_at . '</strong>' . ( ! empty( $user_ip ) ? ' from IP <code>' . $user_ip . '</code>' : '' ) . '</p>' . "\n";
		$message .= '      <p style="margin: 0;">Automated Lead Notification from <strong>' . $site_name . '</strong></p>' . "\n";
		$message .= '    </td>' . "\n";
		$message .= '  </tr>' . "\n";

		$message .= '</table>' . "\n";
		$message .= '</body>' . "\n";
		$message .= '</html>' . "\n";

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
		];

		if ( ! empty( $lead['email'] ) && is_email( $lead['email'] ) ) {
			$clean_name = ! empty( $lead['full_name'] ) ? str_replace( [ "\r", "\n", '"', '<', '>' ], '', $lead['full_name'] ) : 'Lead';
			$headers[]  = sprintf( 'Reply-To: %s <%s>', $clean_name, $lead['email'] );
		}

		if ( function_exists( 'wp_mail' ) ) {
			// If single recipient, pass as string; if multiple, array or comma-separated string
			$to_arg = count( $recipients ) === 1 ? $recipients[0] : $recipients;
			return (bool) wp_mail( $to_arg, $subject, $message, $headers );
		}

		return false;
	}
}
