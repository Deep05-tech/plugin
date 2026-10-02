<?php
/**
 * Services Admin Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Services Controller Class.
 */
class Services_Controller {

	/**
	 * Render the services screen.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE );

		$action = sanitize_text_field( $_GET['action'] ?? 'list' );
		$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		$message = '';
		$notice_type = 'success';

		// Handle Form Submission.
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['seo_city_pages_save_service'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
			if ( ! Security::verify_nonce( $nonce, 'save_service' ) ) {
				$message = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
				$notice_type = 'error';
			} else {
				$name               = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
				$slug               = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
				$status             = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );
				$description        = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
				$default_hero_title = sanitize_text_field( wp_unslash( $_POST['default_hero_title'] ?? '' ) );
				$default_hero_desc  = wp_kses_post( wp_unslash( $_POST['default_hero_desc'] ?? '' ) );
				$default_intro      = wp_kses_post( wp_unslash( $_POST['default_intro'] ?? '' ) );
				$default_cta        = sanitize_text_field( wp_unslash( $_POST['default_cta'] ?? '' ) );
				$default_seo_title  = sanitize_text_field( wp_unslash( $_POST['default_seo_title'] ?? '' ) );
				$default_meta_desc  = sanitize_textarea_field( wp_unslash( $_POST['default_meta_desc'] ?? '' ) );
				$ai_instructions    = sanitize_textarea_field( wp_unslash( $_POST['ai_instructions'] ?? '' ) );

				$data = [
					'name'               => $name,
					'slug'               => $slug,
					'status'             => $status,
					'description'        => $description,
					'default_hero_title' => $default_hero_title,
					'default_hero_desc'  => $default_hero_desc,
					'default_intro'      => $default_intro,
					'default_cta'        => $default_cta,
					'default_seo_title'  => $default_seo_title,
					'default_meta_desc'  => $default_meta_desc,
					'ai_instructions'    => $ai_instructions,
				];

				if ( $id > 0 ) {
					$result = Service_Repository::update( $id, $data );
					if ( is_wp_error( $result ) ) {
						$message     = $result->get_error_message();
						$notice_type = 'error';
					} else {
						$message = __( 'Service updated successfully.', 'seo-city-pages' );
					}
				} else {
					$result = Service_Repository::create( $data );
					if ( is_wp_error( $result ) ) {
						$message     = $result->get_error_message();
						$notice_type = 'error';
					} else {
						$message = __( 'Service created successfully.', 'seo-city-pages' );
						$id      = (int) $result;
						$action  = 'edit';
					}
				}
			}
		}

		// Handle Single Delete GET Action.
		if ( 'delete' === $action && $id > 0 && isset( $_GET['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
			if ( Security::verify_nonce( $nonce, 'delete_service_' . $id ) ) {
				$result = Service_Repository::delete( $id );
				if ( is_wp_error( $result ) ) {
					$message     = $result->get_error_message();
					$notice_type = 'error';
				} else {
					$message = __( 'Service deleted successfully.', 'seo-city-pages' );
				}
			} else {
				$message     = __( 'Security check failed. Could not delete service.', 'seo-city-pages' );
				$notice_type = 'error';
			}
			$action = 'list';
		}

		// Handle Bulk Actions POST.
		if ( isset( $_POST['seo_city_pages_bulk_action'] ) && ! empty( $_POST['service_ids'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
			if ( ! Security::verify_nonce( $nonce, 'bulk_services' ) ) {
				$message     = __( 'Security verification failed for bulk action.', 'seo-city-pages' );
				$notice_type = 'error';
			} else {
				$bulk_action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ?? '' ) );
				$ids         = array_map( 'intval', (array) $_POST['service_ids'] );

				if ( 'activate' === $bulk_action ) {
					$count = Service_Repository::bulk_update_status( $ids, 'active' );
					/* translators: %d: Number of services activated */
					$message = sprintf( __( '%d service(s) activated.', 'seo-city-pages' ), $count );
				} elseif ( 'deactivate' === $bulk_action ) {
					$count = Service_Repository::bulk_update_status( $ids, 'inactive' );
					/* translators: %d: Number of services deactivated */
					$message = sprintf( __( '%d service(s) deactivated.', 'seo-city-pages' ), $count );
				} elseif ( 'delete' === $bulk_action ) {
					$res = Service_Repository::bulk_delete( $ids );
					/* translators: %d: Number of services deleted */
					$message = sprintf( __( '%d service(s) deleted successfully.', 'seo-city-pages' ), $res['deleted'] );
				}
			}
			$action = 'list';
		}

		// Form View.
		if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
			$service = ( 'edit' === $action && $id > 0 ) ? Service_Repository::get( $id ) : null;
			if ( 'edit' === $action && ! $service ) {
				$message     = __( 'Service not found.', 'seo-city-pages' );
				$notice_type = 'error';
				$action      = 'list';
			} else {
				require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/service-form.php';
				return;
			}
		}

		// List View.
		$paged   = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
		$limit   = 20;
		$offset  = ( $paged - 1 ) * $limit;
		$search  = sanitize_text_field( $_GET['s'] ?? '' );
		$status  = sanitize_text_field( $_GET['status'] ?? '' );
		$orderby = sanitize_text_field( $_GET['orderby'] ?? 'name' );
		$order   = sanitize_text_field( $_GET['order'] ?? 'ASC' );

		$args = [
			'search'  => $search,
			'status'  => $status,
			'orderby' => $orderby,
			'order'   => $order,
			'limit'   => $limit,
			'offset'  => $offset,
		];

		$services    = Service_Repository::get_all( $args );
		$total_items = Service_Repository::count( $args );
		$total_pages = ceil( $total_items / $limit );

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/services-list.php';
	}
}
