<?php
/**
 * States / Regions Admin Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * States Controller Class.
 */
class States_Controller {

	/**
	 * Render the states screen.
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
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['seo_city_pages_save_state'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
			if ( ! Security::verify_nonce( $nonce, 'save_state' ) ) {
				$message = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
				$notice_type = 'error';
			} else {
				$country_id = (int) ( $_POST['country_id'] ?? 0 );
				$name       = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
				$code       = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );
				$slug       = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
				$status     = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );

				$data = [
					'country_id' => $country_id,
					'name'       => $name,
					'code'       => $code,
					'slug'       => $slug,
					'status'     => $status,
				];

				if ( $id > 0 ) {
					$result = State_Repository::update( $id, $data );
					if ( is_wp_error( $result ) ) {
						$message     = $result->get_error_message();
						$notice_type = 'error';
					} else {
						$message = __( 'State/region updated successfully.', 'seo-city-pages' );
					}
				} else {
					$result = State_Repository::create( $data );
					if ( is_wp_error( $result ) ) {
						$message     = $result->get_error_message();
						$notice_type = 'error';
					} else {
						$message = __( 'State/region created successfully.', 'seo-city-pages' );
						$id      = (int) $result;
						$action  = 'edit';
					}
				}
			}
		}

		// Handle Single Delete GET Action.
		if ( 'delete' === $action && $id > 0 && isset( $_GET['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
			if ( Security::verify_nonce( $nonce, 'delete_state_' . $id ) ) {
				$result = State_Repository::delete( $id );
				if ( is_wp_error( $result ) ) {
					$message     = $result->get_error_message();
					$notice_type = 'error';
				} else {
					$message = __( 'State/region deleted successfully.', 'seo-city-pages' );
				}
			} else {
				$message     = __( 'Security check failed. Could not delete state.', 'seo-city-pages' );
				$notice_type = 'error';
			}
			$action = 'list';
		}

		// Handle Bulk Actions POST.
		if ( isset( $_POST['seo_city_pages_bulk_action'] ) && ! empty( $_POST['state_ids'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
			if ( ! Security::verify_nonce( $nonce, 'bulk_states' ) ) {
				$message     = __( 'Security verification failed for bulk action.', 'seo-city-pages' );
				$notice_type = 'error';
			} else {
				$bulk_action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ?? '' ) );
				$ids         = array_map( 'intval', (array) $_POST['state_ids'] );

				if ( 'activate' === $bulk_action ) {
					$count = State_Repository::bulk_update_status( $ids, 'active' );
					/* translators: %d: Number of states activated */
					$message = sprintf( __( '%d state(s) activated.', 'seo-city-pages' ), $count );
				} elseif ( 'deactivate' === $bulk_action ) {
					$count = State_Repository::bulk_update_status( $ids, 'inactive' );
					/* translators: %d: Number of states deactivated */
					$message = sprintf( __( '%d state(s) deactivated.', 'seo-city-pages' ), $count );
				} elseif ( 'delete' === $bulk_action ) {
					$res = State_Repository::bulk_delete( $ids );
					if ( $res['failed'] > 0 ) {
						/* translators: 1: Deleted count, 2: Failed count, 3: Error list */
						$message     = sprintf( __( '%1$d state(s) deleted. %2$d failed due to dependent cities: %3$s', 'seo-city-pages' ), $res['deleted'], $res['failed'], implode( '; ', $res['errors'] ) );
						$notice_type = 'warning';
					} else {
						/* translators: %d: Deleted count */
						$message = sprintf( __( '%d state(s) deleted successfully.', 'seo-city-pages' ), $res['deleted'] );
					}
				}
			}
			$action = 'list';
		}

		// Render Form or List.
		$countries = Country_Repository::get_all( [ 'limit' => 500, 'orderby' => 'name', 'order' => 'ASC' ] );

		if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
			$state = ( 'edit' === $action && $id > 0 ) ? State_Repository::get( $id ) : null;
			if ( 'edit' === $action && ! $state ) {
				$message     = __( 'State not found.', 'seo-city-pages' );
				$notice_type = 'error';
				$action      = 'list';
			} else {
				require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/state-form.php';
				return;
			}
		}

		// List View.
		$paged      = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
		$limit      = 20;
		$offset     = ( $paged - 1 ) * $limit;
		$country_id = isset( $_GET['country_id'] ) ? (int) $_GET['country_id'] : 0;
		$search     = sanitize_text_field( $_GET['s'] ?? '' );
		$status     = sanitize_text_field( $_GET['status'] ?? '' );
		$orderby    = sanitize_text_field( $_GET['orderby'] ?? 'name' );
		$order      = sanitize_text_field( $_GET['order'] ?? 'ASC' );

		$args = [
			'country_id' => $country_id,
			'search'     => $search,
			'status'     => $status,
			'orderby'    => $orderby,
			'order'      => $order,
			'limit'      => $limit,
			'offset'     => $offset,
		];

		$states      = State_Repository::get_all( $args );
		$total_items = State_Repository::count( $args );
		$total_pages = ceil( $total_items / $limit );

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/states-list.php';
	}
}
