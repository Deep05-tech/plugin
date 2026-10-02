<?php
/**
 * SEO City Pages Admin Management Screen Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Country_Repository;
use SeoCityPages\Database\State_Repository;
use SeoCityPages\Database\City_Repository;
use SeoCityPages\Database\Service_Repository;
use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;
use SeoCityPages\URL\URL_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pages Controller Class.
 */
class Pages_Controller {

	/**
	 * Main controller entry point.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE );

		$action = sanitize_text_field( $_GET['action'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id     = (int) ( $_GET['id'] ?? 0 );

		if ( 'edit' === $action ) {
			$this->render_edit_screen();
			return;
		}

		if ( 'delete' === $action && $id > 0 ) {
			$nonce = sanitize_text_field( $_GET['_wpnonce'] ?? '' );
			if ( Security::verify_nonce( $nonce, 'delete_page_' . $id ) ) {
				City_Page_Repository::delete( $id, true );
				wp_safe_redirect( remove_query_arg( [ 'action', 'id', '_wpnonce' ] ) );
				exit;
			}
		}

		$this->handle_bulk_actions();
		$this->render_list_screen();
	}

	/**
	 * Render Page Edit screen.
	 *
	 * @return void
	 */
	private function render_edit_screen(): void {
		$page_id = (int) ( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page    = City_Page_Repository::get( $page_id );

		if ( ! $page ) {
			wp_die( esc_html__( 'SEO City Page not found.', 'seo-city-pages' ) );
		}

		$notice = '';
		$error  = '';

		// Handle Form Save.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['seo_city_pages_save_page'] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'save_page_' . $page_id ) ) {
				$error = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
			} else {
				$update_data = [
					'status'                 => sanitize_text_field( $_POST['status'] ?? 'draft' ),
					'title_override'         => sanitize_text_field( $_POST['title_override'] ?? '' ),
					'custom_slug'            => sanitize_text_field( $_POST['custom_slug'] ?? '' ),
					'seo_title'              => sanitize_text_field( $_POST['seo_title'] ?? '' ),
					'meta_description'       => sanitize_textarea_field( $_POST['meta_description'] ?? '' ),
					'canonical_url'          => ! empty( $_POST['canonical_url'] ) ? esc_url_raw( trim( $_POST['canonical_url'] ) ) : null,
					'robots_index'           => sanitize_text_field( $_POST['robots_index'] ?? 'default' ),
					'robots_follow'          => sanitize_text_field( $_POST['robots_follow'] ?? 'default' ),
					'robots_advanced'        => sanitize_text_field( $_POST['robots_advanced'] ?? '' ),
					'og_title'               => sanitize_text_field( $_POST['og_title'] ?? '' ),
					'og_description'         => sanitize_textarea_field( $_POST['og_description'] ?? '' ),
					'og_image'               => ! empty( $_POST['og_image'] ) ? esc_url_raw( trim( $_POST['og_image'] ) ) : null,
					'twitter_title'          => sanitize_text_field( $_POST['twitter_title'] ?? '' ),
					'twitter_description'    => sanitize_textarea_field( $_POST['twitter_description'] ?? '' ),
					'twitter_image'          => ! empty( $_POST['twitter_image'] ) ? esc_url_raw( trim( $_POST['twitter_image'] ) ) : null,
					'schema_type'            => sanitize_text_field( $_POST['schema_type'] ?? 'default' ),
					'is_manual_seo_override' => ! empty( $_POST['is_manual_seo_override'] ) ? 1 : 0,
				];

				$result = City_Page_Repository::update( $page_id, $update_data );
				if ( is_wp_error( $result ) ) {
					$error = $result->get_error_message();
				} else {
					$notice = __( 'SEO City Page updated successfully.', 'seo-city-pages' );
					$page   = City_Page_Repository::get( $page_id ); // Refresh data.
				}
			}
		}

		$preview_url = URL_Engine::build_preview_url( $page_id );
		$public_url  = URL_Engine::build_full_url( $page->url_path );

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/page-form.php';
	}

	/**
	 * Handle List view bulk actions.
	 *
	 * @return void
	 */
	private function handle_bulk_actions(): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['action'] ) && empty( $_POST['action2'] ) ) {
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'bulk-pages' ) ) {
			return;
		}

		$action = sanitize_text_field( $_POST['action'] ?: $_POST['action2'] );
		$ids    = array_map( 'intval', (array) ( $_POST['page_ids'] ?? [] ) );

		if ( empty( $ids ) ) {
			return;
		}

		if ( in_array( $action, City_Page_Repository::ALLOWED_STATUSES, true ) ) {
			City_Page_Repository::bulk_set_status( $ids, $action );
		} elseif ( 'delete' === $action ) {
			City_Page_Repository::bulk_delete( $ids );
		}
	}

	/**
	 * Render Pages List table screen.
	 *
	 * @return void
	 */
	private function render_list_screen(): void {
		$paged   = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$limit   = 20;
		$offset  = ( $paged - 1 ) * $limit;

		$country_id = (int) ( $_GET['country_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$state_id   = (int) ( $_GET['state_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$city_id    = (int) ( $_GET['city_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$service_id = (int) ( $_GET['service_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status     = sanitize_text_field( $_GET['status'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search     = sanitize_text_field( $_GET['s'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby    = sanitize_text_field( $_GET['orderby'] ?? 'id' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order      = sanitize_text_field( $_GET['order'] ?? 'desc' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$query_args = [
			'country_id' => $country_id,
			'state_id'   => $state_id,
			'city_id'    => $city_id,
			'service_id' => $service_id,
			'status'     => $status,
			'search'     => $search,
			'orderby'    => $orderby,
			'order'      => $order,
			'limit'      => $limit,
			'offset'     => $offset,
		];

		$pages       = City_Page_Repository::get_all( $query_args );
		$total_count = City_Page_Repository::count( $query_args );
		$total_pages = ceil( $total_count / $limit );

		$countries = Country_Repository::get_all( [ 'limit' => 500 ] );
		$states    = ! empty( $country_id ) ? State_Repository::get_all( [ 'country_id' => $country_id, 'limit' => 500 ] ) : State_Repository::get_all( [ 'limit' => 500 ] );
		$cities    = ! empty( $state_id ) ? City_Repository::get_all( [ 'state_id' => $state_id, 'limit' => 500 ] ) : City_Repository::get_all( [ 'limit' => 500 ] );
		$services  = Service_Repository::get_all( [ 'limit' => 500 ] );

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/pages-list.php';
	}
}
