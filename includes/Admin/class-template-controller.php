<?php
/**
 * Elementor Template Admin Controller.
 *
 * Handles template selection, validation, starter template generation,
 * and preview context configuration.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Environment;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;
use SeoCityPages\Elementor\Elementor_Integration;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template_Controller Class.
 */
class Template_Controller {

	/**
	 * Render and handle the Elementor Template settings screen.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE );

		$message     = '';
		$notice_type = 'success';

		// Handle Form Submissions.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$this->handle_post_actions( $message, $notice_type );
		}

		$settings           = get_option( Settings::OPTION_NAME, [] );
		$selected_id        = (int) ( $settings['template_id'] ?? 0 );
		$preview_page_id    = (int) ( $settings['preview_page_id'] ?? 0 );
		$elementor_status   = Environment::get_elementor_status();
		$arolax_status      = Environment::get_arolax_status();
		$available_templates = Elementor_Integration::get_available_templates();

		// Fetch details of selected template if set.
		$selected_template = null;
		$template_deleted  = false;
		if ( $selected_id > 0 ) {
			$tpl_post = get_post( $selected_id );
			if ( $tpl_post && 'elementor_library' === $tpl_post->post_type && 'trash' !== $tpl_post->post_status ) {
				$type = get_post_meta( $selected_id, '_elementor_template_type', true );
				$selected_template = (object) [
					'id'        => $selected_id,
					'title'     => $tpl_post->post_title,
					'status'    => $tpl_post->post_status,
					'type'      => $type ?: 'page',
					'edit_url'  => admin_url( 'post.php?post=' . $selected_id . '&action=elementor' ),
					'view_url'  => get_permalink( $selected_id ),
				];
			} else {
				$template_deleted = true;
			}
		}

		// Fetch sample SEO City Pages for preview dropdown (first 25).
		$sample_pages = City_Page_Repository::get_all( [ 'limit' => 25 ] );

		include SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/template-settings.php';
	}

	/**
	 * Handle POST actions.
	 *
	 * @param string $message     Reference to status message.
	 * @param string $notice_type Reference to notice type.
	 * @return void
	 */
	private function handle_post_actions( string &$message, string &$notice_type ): void {
		// 1. Save Selected Template.
		if ( isset( $_POST['scp_save_template'] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'save_template' ) ) {
				$message     = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
				$notice_type = 'error';
				return;
			}

			$template_id = (int) ( $_POST['template_id'] ?? 0 );
			$preview_id  = (int) ( $_POST['preview_page_id'] ?? 0 );

			if ( $template_id > 0 ) {
				$tpl_post = get_post( $template_id );
				if ( ! $tpl_post || 'elementor_library' !== $tpl_post->post_type || 'trash' === $tpl_post->post_status ) {
					$message     = __( 'Selected template is invalid, trashed, or not an Elementor Library template.', 'seo-city-pages' );
					$notice_type = 'error';
					return;
				}
			}

			$settings = get_option( Settings::OPTION_NAME, [] );
			$settings['template_id']     = $template_id;
			$settings['preview_page_id'] = $preview_id;
			update_option( Settings::OPTION_NAME, $settings );

			$message = __( 'Elementor template configuration saved successfully.', 'seo-city-pages' );
			Logger::info( sprintf( 'Updated SEO City Page master template ID to %d', $template_id ) );
		}

		// 2. Create Starter Template.
		if ( isset( $_POST['scp_create_starter_template'] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'create_starter_template' ) ) {
				$message     = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
				$notice_type = 'error';
				return;
			}

			$starter_title = sanitize_text_field( $_POST['starter_title'] ?? '' );
			$created_id    = Elementor_Integration::create_starter_template( $starter_title );

			if ( is_wp_error( $created_id ) ) {
				$message     = $created_id->get_error_message();
				$notice_type = 'error';
				return;
			}

			// Automatically select the new starter template.
			$settings = get_option( Settings::OPTION_NAME, [] );
			$settings['template_id'] = (int) $created_id;
			update_option( Settings::OPTION_NAME, $settings );

			$edit_url = admin_url( 'post.php?post=' . (int) $created_id . '&action=elementor' );
			wp_safe_redirect( $edit_url );
			exit;
		}

		// 3. Reset Template.
		if ( isset( $_POST['scp_reset_template'] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'reset_template' ) ) {
				$message     = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
				$notice_type = 'error';
				return;
			}

			$settings = get_option( Settings::OPTION_NAME, [] );
			$settings['template_id'] = 0;
			update_option( Settings::OPTION_NAME, $settings );

			$message = __( 'Template selection has been cleared. SEO City Pages will use the fallback renderer until a new template is configured.', 'seo-city-pages' );
			Logger::info( 'Cleared SEO City Page master template selection' );
		}
	}
}
