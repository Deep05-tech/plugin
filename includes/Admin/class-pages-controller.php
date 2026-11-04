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
use SeoCityPages\Database\Schema;
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

		if ( 'new' === $action ) {
			$this->render_new_screen();
			return;
		}

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
	 * Render Manual Page Generation screen.
	 *
	 * @return void
	 */
	private function render_new_screen(): void {
		$notice         = '';
		$error          = '';
		$generated_page = null;

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['seo_city_pages_manual_generate'] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! Security::verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'manual_generate_page' ) ) {
				$error = __( 'Security verification failed. Please try again.', 'seo-city-pages' );
			} else {
				$country_input = sanitize_text_field( $_POST['country_input'] ?? 'United States' );
				$state_input   = sanitize_text_field( $_POST['state_input'] ?? '' );
				$city_input    = sanitize_text_field( $_POST['city_input'] ?? '' );
				$service_input = sanitize_text_field( $_POST['service_input'] ?? '' );
				$framing       = sanitize_text_field( $_POST['agency_framing'] ?? 'Best Digital Marketing Agency' );
				$primary_kw    = sanitize_text_field( $_POST['primary_keyword'] ?? '' );
				$secondary_kws = sanitize_textarea_field( $_POST['target_keywords'] ?? '' );
				$status        = sanitize_text_field( $_POST['page_status'] ?? 'published' );
				$run_ai        = ! empty( $_POST['run_ai_generation'] );

				if ( empty( $city_input ) || empty( $service_input ) ) {
					$error = __( 'Please enter both City and Service/Niche topic.', 'seo-city-pages' );
				} else {
					global $wpdb;
					$entity_errors = [];

					$t_c  = Schema::get_table_name( Schema::TABLE_COUNTRIES );
					$t_s  = Schema::get_table_name( Schema::TABLE_STATES );
					$t_ci = Schema::get_table_name( Schema::TABLE_CITIES );

					// 1. Resolve Country
					$c_in_clean = trim( $country_input );
					$country = null;

					if ( in_array( strtolower( $c_in_clean ), [ 'us', 'usa', 'united states', 'united states of america' ], true ) ) {
						$country = Country_Repository::get_by_name( 'United States' );
						if ( ! $country ) {
							$country = $wpdb->get_row( "SELECT * FROM {$t_c} WHERE code = 'US' OR slug = 'us' OR slug = 'united-states' LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						}
					}

					if ( ! $country ) {
						$country = Country_Repository::get_by_name( $c_in_clean );
					}

					if ( ! $country ) {
						$country = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT * FROM {$t_c} WHERE LOWER(code) = %s OR LOWER(slug) = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								strtolower( $c_in_clean ),
								sanitize_title( $c_in_clean )
							)
						);
					}

					if ( ! $country ) {
						$c_code = strtoupper( substr( sanitize_title( $c_in_clean ), 0, 2 ) );
						if ( in_array( strtolower( $c_in_clean ), [ 'united states', 'usa', 'us' ], true ) ) {
							$c_code = 'US';
						}
						$c_id = Country_Repository::create( [
							'name'   => $c_in_clean,
							'code'   => $c_code,
							'slug'   => strtolower( $c_code ),
							'status' => 'active',
						] );
						if ( is_wp_error( $c_id ) ) {
							$country = Country_Repository::get_by_name( $c_in_clean );
							$country_id = $country ? (int) $country->id : 0;
							if ( $country_id <= 0 ) {
								$entity_errors[] = sprintf( __( 'Country error: %s', 'seo-city-pages' ), $c_id->get_error_message() );
							}
						} else {
							$country_id = (int) $c_id;
						}
					} else {
						$country_id = (int) $country->id;
					}

					// 2. Resolve State
					$s_in_clean = trim( $state_input );
					$state = null;

					if ( ! empty( $s_in_clean ) && $country_id > 0 ) {
						$state = State_Repository::get_by_name( $country_id, $s_in_clean );
						if ( ! $state ) {
							$state = $wpdb->get_row(
								$wpdb->prepare(
									"SELECT * FROM {$t_s} WHERE country_id = %d AND (LOWER(code) = %s OR LOWER(slug) = %s) LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
									$country_id,
									strtolower( $s_in_clean ),
									sanitize_title( $s_in_clean )
								)
							);
						}

						if ( ! $state ) {
							$s_code = strtoupper( substr( sanitize_title( $s_in_clean ), 0, 2 ) );
							$s_id = State_Repository::create( [
								'country_id' => $country_id,
								'name'       => $s_in_clean,
								'code'       => $s_code,
								'slug'       => sanitize_title( $s_in_clean ),
								'status'     => 'active',
							] );
							if ( is_wp_error( $s_id ) ) {
								$state = State_Repository::get_by_name( $country_id, $s_in_clean );
								$state_id = $state ? (int) $state->id : 0;
								if ( $state_id <= 0 ) {
									$entity_errors[] = sprintf( __( 'State error: %s', 'seo-city-pages' ), $s_id->get_error_message() );
								}
							} else {
								$state_id = (int) $s_id;
							}
						} else {
							$state_id = (int) $state->id;
						}
					} elseif ( $country_id > 0 ) {
						// Fallback state if user left state blank (ensures city can be saved)
						$state = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT * FROM {$t_s} WHERE country_id = %d AND (slug = %s OR slug = %s OR LOWER(name) = %s) LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								$country_id,
								'general',
								'all',
								'general'
							)
						);
						if ( ! $state ) {
							$s_id = State_Repository::create( [
								'country_id' => $country_id,
								'name'       => 'General',
								'code'       => 'GEN',
								'slug'       => 'general',
								'status'     => 'active',
							] );
							if ( is_wp_error( $s_id ) ) {
								$state = State_Repository::get_by_name( $country_id, 'General' );
								$state_id = $state ? (int) $state->id : 0;
							} else {
								$state_id = (int) $s_id;
							}
						} else {
							$state_id = (int) $state->id;
						}
					} else {
						$state_id = 0;
					}

					// 3. Resolve City
					$ci_in_clean = trim( $city_input );
					$city = null;

					if ( ! empty( $ci_in_clean ) && $country_id > 0 && $state_id > 0 ) {
						$city = City_Repository::get_by_name( $country_id, $state_id, $ci_in_clean );
						if ( ! $city ) {
							$city = $wpdb->get_row(
								$wpdb->prepare(
									"SELECT * FROM {$t_ci} WHERE country_id = %d AND state_id = %d AND slug = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
									$country_id,
									$state_id,
									sanitize_title( $ci_in_clean )
								)
							);
						}

						if ( ! $city ) {
							$ci_id = City_Repository::create( [
								'country_id' => $country_id,
								'state_id'   => $state_id,
								'name'       => $ci_in_clean,
								'slug'       => sanitize_title( $ci_in_clean ),
								'status'     => 'active',
							] );
							if ( is_wp_error( $ci_id ) ) {
								$city = City_Repository::get_by_name( $country_id, $state_id, $ci_in_clean );
								$city_id = $city ? (int) $city->id : 0;
								if ( $city_id <= 0 ) {
									$entity_errors[] = sprintf( __( 'City error: %s', 'seo-city-pages' ), $ci_id->get_error_message() );
								}
							} else {
								$city_id = (int) $ci_id;
							}
						} else {
							$city_id = (int) $city->id;
						}
					} else {
						$city_id = 0;
					}

					// 4. Resolve Service
					$sv_in_clean = trim( $service_input );
					$service = null;

					if ( ! empty( $sv_in_clean ) ) {
						$service = Service_Repository::get_by_name( $sv_in_clean );
						if ( ! $service ) {
							$service = Service_Repository::get_by_slug( sanitize_title( $sv_in_clean ) );
						}

						if ( ! $service ) {
							$sv_id = Service_Repository::create( [
								'name'   => $sv_in_clean,
								'slug'   => sanitize_title( $sv_in_clean ),
								'status' => 'active',
							] );
							if ( is_wp_error( $sv_id ) ) {
								$service = Service_Repository::get_by_name( $sv_in_clean );
								$service_id = $service ? (int) $service->id : 0;
								if ( $service_id <= 0 ) {
									$entity_errors[] = sprintf( __( 'Service error: %s', 'seo-city-pages' ), $sv_id->get_error_message() );
								}
							} else {
								$service_id = (int) $sv_id;
							}
						} else {
							$service_id = (int) $service->id;
						}
					} else {
						$service_id = 0;
					}

					if ( $country_id <= 0 || $city_id <= 0 || $service_id <= 0 ) {
						if ( ! empty( $entity_errors ) ) {
							$error = implode( ' ', $entity_errors );
						} else {
							$error = __( 'Failed to save location or service entities to database.', 'seo-city-pages' );
						}
					} else {
						// Auto-compose primary keyword if not provided
						if ( empty( $primary_kw ) ) {
							$primary_kw = sprintf( '%s for %s in %s', $framing, $service_input, $city_input );
							if ( ! empty( $state_input ) ) {
								$primary_kw .= ', ' . $state_input;
							}
						}

						// Auto-compose rich agency secondary keywords if not provided
						if ( empty( $secondary_kws ) ) {
							$secondary_kws = sprintf(
								'Google Ads for %1$s, Meta Ads (Facebook & Instagram) for %1$s, %2$s in %3$s, Local SEO & Google Map Pack 3-Pack, PPC Management, High-Converting Web Funnels, Conversion Rate Optimization, Online Reputation Management',
								$service_input,
								$framing,
								$city_input
							);
						}

						$country_obj = Country_Repository::get( $country_id );
						$state_obj   = State_Repository::get( $state_id );
						$city_obj    = City_Repository::get( $city_id );
						$service_obj = Service_Repository::get( $service_id );
						$calculated_url_path = URL_Engine::build_path( $country_obj, $state_obj, $city_obj, $service_obj );
						$custom_page_slug    = sanitize_title( sprintf( 'best-digital-marketing-agency-for-%s-in-%s', sanitize_title( $service_input ), sanitize_title( $city_input ) ) );

						// 5. Create or Find City Page
						$page_result = City_Page_Repository::find_or_create( [
							'country_id'      => $country_id,
							'state_id'        => $state_id,
							'city_id'         => $city_id,
							'service_id'      => $service_id,
							'slug'            => $custom_page_slug,
							'title_override'  => $primary_kw,
							'status'          => $status,
							'primary_keyword' => $primary_kw,
							'target_keywords' => $secondary_kws,
						] );

						$page_id = (int) ( $page_result['id'] ?? 0 );

						if ( $page_id > 0 ) {
							// Update status, title_override, slug, and keywords
							City_Page_Repository::update( $page_id, [
								'status'          => $status,
								'slug'            => $custom_page_slug,
								'url_path'        => $calculated_url_path,
								'title_override'  => $primary_kw,
								'primary_keyword' => $primary_kw,
								'target_keywords' => $secondary_kws,
								'is_published'    => 'published' === $status ? 1 : 0,
							] );

							// 6. Run AI Generation if requested
							if ( $run_ai && class_exists( '\SeoCityPages\AI\AI_Manager' ) ) {
								$gen_res = \SeoCityPages\AI\AI_Manager::instance()->generate_for_page( $page_id );
								if ( $gen_res && $gen_res->is_success() ) {
									$latest_v = \SeoCityPages\Database\Content_Version_Repository::get_latest_version( $page_id );
									if ( $latest_v ) {
										\SeoCityPages\Database\Content_Version_Repository::approve_version( (int) $latest_v->id );
										if ( 'published' === $status ) {
											$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
											$wpdb->update(
												$pages_table,
												[
													'published_version_id' => (int) $latest_v->id,
													'is_published'          => 1,
													'published_at'          => current_time( 'mysql' ),
												],
												[ 'id' => $page_id ]
											);
										}
									}
								}
							}

							// 7. Sync with WordPress Core Pages
							if ( class_exists( '\SeoCityPages\Integration\Post_Sync' ) ) {
								\SeoCityPages\Integration\Post_Sync::sync_page( $page_id );
							}

							$generated_page = City_Page_Repository::get( $page_id );
							$notice = __( 'Landing page generated successfully!', 'seo-city-pages' );
						} else {
							$error = __( 'Could not create or find City Page record.', 'seo-city-pages' );
						}
					}
				}
			}
		}

		$countries = Country_Repository::get_all( [ 'limit' => 200 ] );
		$states    = State_Repository::get_all( [ 'limit' => 200 ] );
		$services  = Service_Repository::get_all( [ 'limit' => 200 ] );

		require SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/manual-page-form.php';
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
