<?php
/**
 * Cities Admin Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cities Controller Class.
 */
class Cities_Controller {

	/**
	 * Render the cities screen.
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
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['seo_city_pages_save_city'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
			if ( ! Security::verify_nonce( $nonce, 'save_city' ) ) {
				$message = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
				$notice_type = 'error';
			} else {
				$country_id       = (int) ( $_POST['country_id'] ?? 0 );
				$state_id         = (int) ( $_POST['state_id'] ?? 0 );
				$name             = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
				$slug             = sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) );
				$status           = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );
				$latitude         = sanitize_text_field( wp_unslash( $_POST['latitude'] ?? '' ) );
				$longitude        = sanitize_text_field( wp_unslash( $_POST['longitude'] ?? '' ) );
				$local_industries = sanitize_textarea_field( wp_unslash( $_POST['local_industries'] ?? '' ) );
				$areas_served     = sanitize_textarea_field( wp_unslash( $_POST['areas_served'] ?? '' ) );
				$description      = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
				$notes            = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );

				$data = [
					'country_id'       => $country_id,
					'state_id'         => $state_id,
					'name'             => $name,
					'slug'             => $slug,
					'status'           => $status,
					'latitude'         => '' !== $latitude ? $latitude : null,
					'longitude'        => '' !== $longitude ? $longitude : null,
					'local_industries' => $local_industries,
					'areas_served'     => $areas_served,
					'description'      => $description,
					'notes'            => $notes,
				];

				if ( $id > 0 ) {
					$result = City_Repository::update( $id, $data );
					if ( is_wp_error( $result ) ) {
						$message     = $result->get_error_message();
						$notice_type = 'error';
					} else {
						$message = __( 'City updated successfully.', 'seo-city-pages' );
					}
				} else {
					$result = City_Repository::create( $data );
					if ( is_wp_error( $result ) ) {
						$message     = $result->get_error_message();
						$notice_type = 'error';
					} else {
						$message = __( 'City created successfully.', 'seo-city-pages' );
						$id      = (int) $result;
						$action  = 'edit';
					}
				}
			}
		}

		// Handle Single Delete GET Action.
		if ( 'delete' === $action && $id > 0 && isset( $_GET['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
			if ( Security::verify_nonce( $nonce, 'delete_city_' . $id ) ) {
				$result = City_Repository::delete( $id );
				if ( is_wp_error( $result ) ) {
					$message     = $result->get_error_message();
					$notice_type = 'error';
				} else {
					$message = __( 'City deleted successfully.', 'seo-city-pages' );
				}
			} else {
				$message     = __( 'Security check failed. Could not delete city.', 'seo-city-pages' );
				$notice_type = 'error';
			}
			$action = 'list';
		}

		// Handle Bulk Actions POST.
		if ( isset( $_POST['seo_city_pages_bulk_action'] ) && ! empty( $_POST['city_ids'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
			if ( ! Security::verify_nonce( $nonce, 'bulk_cities' ) ) {
				$message     = __( 'Security verification failed for bulk action.', 'seo-city-pages' );
				$notice_type = 'error';
			} else {
				$bulk_action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ?? '' ) );
				$ids         = array_map( 'intval', (array) $_POST['city_ids'] );

				if ( 'activate' === $bulk_action ) {
					$count = City_Repository::bulk_update_status( $ids, 'active' );
					/* translators: %d: Number of cities activated */
					$message = sprintf( __( '%d city/cities activated.', 'seo-city-pages' ), $count );
				} elseif ( 'deactivate' === $bulk_action ) {
					$count = City_Repository::bulk_update_status( $ids, 'inactive' );
					/* translators: %d: Number of cities deactivated */
					$message = sprintf( __( '%d city/cities deactivated.', 'seo-city-pages' ), $count );
				} elseif ( 'delete' === $bulk_action ) {
					$res = City_Repository::bulk_delete( $ids );
					/* translators: %d: Number of cities deleted */
					$message = sprintf( __( '%d city/cities deleted successfully.', 'seo-city-pages' ), $res['deleted'] );
				}
			}
			$action = 'list';
		}

		$countries = Country_Repository::get_all( [ 'limit' => 500, 'orderby' => 'name', 'order' => 'ASC' ] );

		// Form View.
		if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
			$city = ( 'edit' === $action && $id > 0 ) ? City_Repository::get( $id ) : null;
			if ( 'edit' === $action && ! $city ) {
				$message     = __( 'City not found.', 'seo-city-pages' );
				$notice_type = 'error';
				$action      = 'list';
			} else {
				$selected_country_id = $city ? (int) $city->country_id : ( isset( $_GET['country_id'] ) ? (int) $_GET['country_id'] : 0 );
				$states = $selected_country_id > 0
					? State_Repository::get_all( [ 'country_id' => $selected_country_id, 'limit' => 500, 'orderby' => 'name', 'order' => 'ASC' ] )
					: State_Repository::get_all( [ 'limit' => 500, 'orderby' => 'name', 'order' => 'ASC' ] );

				require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/city-form.php';
				return;
			}
		}

		// List View.
		$paged      = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
		$limit      = 20;
		$offset     = ( $paged - 1 ) * $limit;
		$country_id = isset( $_GET['country_id'] ) ? (int) $_GET['country_id'] : 0;
		$state_id   = isset( $_GET['state_id'] ) ? (int) $_GET['state_id'] : 0;
		$search     = sanitize_text_field( $_GET['s'] ?? '' );
		$status     = sanitize_text_field( $_GET['status'] ?? '' );
		$orderby    = sanitize_text_field( $_GET['orderby'] ?? 'name' );
		$order      = sanitize_text_field( $_GET['order'] ?? 'ASC' );

		$states = $country_id > 0
			? State_Repository::get_all( [ 'country_id' => $country_id, 'limit' => 500, 'orderby' => 'name', 'order' => 'ASC' ] )
			: State_Repository::get_all( [ 'limit' => 500, 'orderby' => 'name', 'order' => 'ASC' ] );

		$args = [
			'country_id' => $country_id,
			'state_id'   => $state_id,
			'search'     => $search,
			'status'     => $status,
			'orderby'    => $orderby,
			'order'      => $order,
			'limit'      => $limit,
			'offset'     => $offset,
		];

		$cities      = City_Repository::get_all( $args );
		$total_items = City_Repository::count( $args );
		$total_pages = ceil( $total_items / $limit );

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/cities-list.php';
	}
}
