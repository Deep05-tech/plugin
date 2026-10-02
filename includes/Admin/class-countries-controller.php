<?php
/**
 * Countries Admin Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Countries Controller Class.
 */
class Countries_Controller {

	/**
	 * Render the countries screen (list or edit form).
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE );

		$action = sanitize_text_field( $_GET['action'] ?? 'list' );
		$id     = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		$message = '';
		$notice_type = 'success';

		// Handle Form Submissions.
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['seo_city_pages_save_country'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
			if ( ! Security::verify_nonce( $nonce, 'save_country' ) ) {
				$message = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
				$notice_type = 'error';
			} else {
				$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
				$code   = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );
				$slug   = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
				$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );

				$data = [
					'name'   => $name,
					'code'   => $code,
					'slug'   => $slug,
					'status' => $status,
				];

				if ( $id > 0 ) {
					$result = Country_Repository::update( $id, $data );
					if ( is_wp_error( $result ) ) {
						$message     = $result->get_error_message();
						$notice_type = 'error';
					} else {
						$message = __( 'Country updated successfully.', 'seo-city-pages' );
					}
				} else {
					$result = Country_Repository::create( $data );
					if ( is_wp_error( $result ) ) {
						$message     = $result->get_error_message();
						$notice_type = 'error';
					} else {
						$message = __( 'Country created successfully.', 'seo-city-pages' );
						$id      = (int) $result;
						$action  = 'edit';
					}
				}
			}
		}

		// Handle Single Delete GET Action.
		if ( 'delete' === $action && $id > 0 && isset( $_GET['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
			if ( Security::verify_nonce( $nonce, 'delete_country_' . $id ) ) {
				$result = Country_Repository::delete( $id );
				if ( is_wp_error( $result ) ) {
					$message     = $result->get_error_message();
					$notice_type = 'error';
				} else {
					$message = __( 'Country deleted successfully.', 'seo-city-pages' );
				}
			} else {
				$message     = __( 'Security check failed. Could not delete country.', 'seo-city-pages' );
				$notice_type = 'error';
			}
			$action = 'list';
		}

		// Handle Bulk Actions POST.
		if ( isset( $_POST['seo_city_pages_bulk_action'] ) && ! empty( $_POST['country_ids'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
			if ( ! Security::verify_nonce( $nonce, 'bulk_countries' ) ) {
				$message     = __( 'Security verification failed for bulk action.', 'seo-city-pages' );
				$notice_type = 'error';
			} else {
				$bulk_action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ?? '' ) );
				$ids         = array_map( 'intval', (array) $_POST['country_ids'] );

				if ( 'activate' === $bulk_action ) {
					$count = Country_Repository::bulk_update_status( $ids, 'active' );
					/* translators: %d: Number of countries activated */
					$message = sprintf( __( '%d country/countries activated.', 'seo-city-pages' ), $count );
				} elseif ( 'deactivate' === $bulk_action ) {
					$count = Country_Repository::bulk_update_status( $ids, 'inactive' );
					/* translators: %d: Number of countries deactivated */
					$message = sprintf( __( '%d country/countries deactivated.', 'seo-city-pages' ), $count );
				} elseif ( 'delete' === $bulk_action ) {
					$res = Country_Repository::bulk_delete( $ids );
					if ( $res['failed'] > 0 ) {
						/* translators: 1: Deleted count, 2: Failed count, 3: Error details */
						$message     = sprintf( __( '%1$d country/countries deleted. %2$d failed due to dependencies: %3$s', 'seo-city-pages' ), $res['deleted'], $res['failed'], implode( '; ', $res['errors'] ) );
						$notice_type = 'warning';
					} else {
						/* translators: %d: Deleted count */
						$message = sprintf( __( '%d country/countries deleted successfully.', 'seo-city-pages' ), $res['deleted'] );
					}
				}
			}
			$action = 'list';
		}

		// Render Form or List.
		if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
			$country = ( 'edit' === $action && $id > 0 ) ? Country_Repository::get( $id ) : null;
			if ( 'edit' === $action && ! $country ) {
				$message     = __( 'Country not found.', 'seo-city-pages' );
				$notice_type = 'error';
				$action      = 'list';
			} else {
				require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/country-form.php';
				return;
			}
		}

		// Default: List View.
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

		$countries   = Country_Repository::get_all( $args );
		$total_items = Country_Repository::count( $args );
		$total_pages = ceil( $total_items / $limit );

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/countries-list.php';
	}
}
