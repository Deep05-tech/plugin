<?php
/**
 * WordPress Post Synchronizer & Core Pages Listing Integration.
 *
 * Automatically mirrors published SEO City Pages into WordPress core wp_posts
 * so they appear seamlessly in WordPress admin "Pages -> All Pages" (edit.php?post_type=page).
 *
 * @package SeoCityPages\Integration
 */

namespace SeoCityPages\Integration;

use SeoCityPages\Database\Schema;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\URL\URL_Engine;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post_Sync Class.
 */
class Post_Sync {

	public const META_PAGE_ID  = '_seo_city_page_id';
	public const META_IS_SCP   = '_is_seo_city_page';
	public const META_URL_PATH = '_seo_city_page_url';

	/**
	 * Initialize WordPress core admin hooks and filters.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'maybe_sync_all_published' ], 30 );

		// Two-way synchronization with WordPress Core Pages (edit.php?post_type=page)
		add_action( 'trashed_post', [ __CLASS__, 'on_trashed_post' ] );
		add_action( 'untrashed_post', [ __CLASS__, 'on_untrashed_post' ] );
		add_action( 'before_delete_post', [ __CLASS__, 'on_deleted_post' ] );

		// Admin column & state enhancements on wp-admin/edit.php?post_type=page
		if ( is_admin() ) {
			add_filter( 'display_post_states', [ __CLASS__, 'filter_post_states' ], 10, 2 );
			add_filter( 'page_row_actions', [ __CLASS__, 'filter_row_actions' ], 10, 2 );
			add_filter( 'post_link', [ __CLASS__, 'filter_permalink' ], 10, 2 );
			add_filter( 'page_link', [ __CLASS__, 'filter_permalink' ], 10, 2 );
		}
	}

	/**
	 * Synchronize a published City Page into WordPress wp_posts table.
	 *
	 * @param int $page_id City Page ID.
	 * @return int WordPress post ID.
	 */
	public static function sync_page( int $page_id ): int {
		global $wpdb;
		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return 0;
		}

		if ( empty( $page->is_published ) && 'published' !== $page->status ) {
			self::unpublish_page( $page_id );
			return 0;
		}

		$title = City_Page_Repository::get_derived_title( $page );
		$slug  = ! empty( $page->slug ) ? $page->slug : sanitize_title( $title );

		// Check if a post already exists with this page ID meta or stored wp_post_id
		$existing_post_id = (int) ( $page->wp_post_id ?? 0 );
		if ( $existing_post_id <= 0 || ! get_post( $existing_post_id ) ) {
			if ( isset( $wpdb->postmeta ) ) {
				$existing_post_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d LIMIT 1",
						self::META_PAGE_ID,
						$page_id
					)
				);
			}
		}

		$post_data = [
			'post_title'     => $title,
			'post_name'      => $slug,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_content'   => '<!-- seo-city-page-id:' . $page_id . ' -->' . "\n" . '[seo_city_page id="' . $page_id . '"]',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		];

		if ( $existing_post_id > 0 && get_post( $existing_post_id ) ) {
			$post_data['ID'] = $existing_post_id;
			if ( function_exists( 'wp_update_post' ) ) {
				wp_update_post( $post_data );
			}
			$post_id = $existing_post_id;
		} else {
			if ( function_exists( 'wp_insert_post' ) ) {
				$post_id = wp_insert_post( $post_data );
			} else {
				$post_id = 0;
			}
		}

		if ( $post_id > 0 && ! is_wp_error( $post_id ) ) {
			if ( function_exists( 'update_post_meta' ) ) {
				update_post_meta( $post_id, self::META_PAGE_ID, $page_id );
				update_post_meta( $post_id, self::META_IS_SCP, '1' );
				update_post_meta( $post_id, self::META_URL_PATH, $page->url_path );
				update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
				update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
				update_post_meta( $post_id, '_wp_page_template', 'elementor_header_footer' );

				// Arolax & general theme isolation: disable blog sidebar & title banner for full-width landing pages
				update_post_meta( $post_id, 'disable_banner', 1 );
				update_post_meta( $post_id, 'arolax_meta_option', [
					'disable_banner' => 1,
					'blog_sidebar'   => 'no-sidebar',
				] );

				// Seed Elementor template data from master template if post has no data yet.
				$existing_elementor_data = get_post_meta( $post_id, '_elementor_data', true );
				if ( empty( $existing_elementor_data ) ) {
					$settings    = get_option( \SeoCityPages\Admin\Settings::OPTION_NAME, [] );
					$template_id = (int) ( $settings['template_id'] ?? 0 );

					if ( $template_id > 0 ) {
						$tpl_data          = get_post_meta( $template_id, '_elementor_data', true );
						$tpl_page_settings = get_post_meta( $template_id, '_elementor_page_settings', true );

						if ( ! empty( $tpl_data ) ) {
							update_post_meta( $post_id, '_elementor_data', $tpl_data );
						} else {
							update_post_meta( $post_id, '_elementor_data', '[]' );
						}

						if ( ! empty( $tpl_page_settings ) ) {
							update_post_meta( $post_id, '_elementor_page_settings', $tpl_page_settings );
						}
					} else {
						update_post_meta( $post_id, '_elementor_data', '[]' );
					}
				}
			}

			// Update wp_post_id in custom table
			$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
			$wpdb->update(
				$pages_table,
				[
					'wp_post_id' => $post_id,
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'id' => $page_id ]
			);

			return (int) $post_id;
		}

		return 0;
	}

	/**
	 * Unpublish associated WordPress post (sets status to draft).
	 *
	 * @param int $page_id City Page ID.
	 * @return bool
	 */
	public static function unpublish_page( int $page_id ): bool {
		global $wpdb;
		$page = City_Page_Repository::get( $page_id );
		if ( ! $page ) {
			return false;
		}

		$post_id = (int) ( $page->wp_post_id ?? 0 );

		if ( $post_id <= 0 && isset( $wpdb->postmeta ) ) {
			$post_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d LIMIT 1",
					self::META_PAGE_ID,
					$page_id
				)
			);
		}

		if ( $post_id > 0 && function_exists( 'wp_update_post' ) && get_post( $post_id ) ) {
			wp_update_post( [ 'ID' => $post_id, 'post_status' => 'draft' ] );
			return true;
		}

		return false;
	}

	/**
	 * Callback when a WordPress post is moved to trash.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function on_trashed_post( $post_id ): void {
		$page_id = 0;
		if ( function_exists( 'get_post_meta' ) ) {
			$page_id = (int) get_post_meta( (int) $post_id, self::META_PAGE_ID, true );
		}
		if ( $page_id > 0 ) {
			global $wpdb;
			$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
			$wpdb->update(
				$pages_table,
				[
					'status'       => 'trash',
					'is_published' => 0,
					'updated_at'   => current_time( 'mysql' ),
				],
				[ 'id' => $page_id ]
			);
			Logger::info( sprintf( 'SEO City Page #%d moved to trash following WordPress post #%d deletion', $page_id, $post_id ) );
		}
	}

	/**
	 * Callback when a WordPress post is restored from trash.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function on_untrashed_post( $post_id ): void {
		$page_id = 0;
		if ( function_exists( 'get_post_meta' ) ) {
			$page_id = (int) get_post_meta( (int) $post_id, self::META_PAGE_ID, true );
		}
		if ( $page_id > 0 ) {
			global $wpdb;
			$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
			$wpdb->update(
				$pages_table,
				[
					'status'       => 'published',
					'is_published' => 1,
					'wp_post_id'   => (int) $post_id,
					'updated_at'   => current_time( 'mysql' ),
				],
				[ 'id' => $page_id ]
			);
			Logger::info( sprintf( 'SEO City Page #%d restored following WordPress post #%d untrash', $page_id, $post_id ) );
		}
	}

	/**
	 * Callback when a WordPress post is permanently deleted.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function on_deleted_post( $post_id ): void {
		$page_id = 0;
		if ( function_exists( 'get_post_meta' ) ) {
			$page_id = (int) get_post_meta( (int) $post_id, self::META_PAGE_ID, true );
		}
		if ( $page_id > 0 ) {
			// Delete from SEO City Pages repository without recursive post deletion.
			City_Page_Repository::delete( $page_id, false );
			Logger::info( sprintf( 'SEO City Page #%d deleted permanently following WordPress post #%d deletion', $page_id, $post_id ) );
		}
	}

	/**
	 * Synchronize all currently published City Pages to wp_posts.
	 *
	 * @return int Number of synchronized pages.
	 */
	public static function maybe_sync_all_published(): int {
		global $wpdb;
		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$published_pages = $wpdb->get_results(
			"SELECT id, wp_post_id FROM {$pages_table} WHERE is_published = 1 AND status = 'published'"
		);

		$synced = 0;
		if ( ! empty( $published_pages ) ) {
			foreach ( $published_pages as $p ) {
				$post_id = (int) ( $p->wp_post_id ?? 0 );
				// Only sync if post does not exist or has no ID
				$needs_sync = $post_id <= 0 || ! get_post( $post_id );

				if ( $needs_sync ) {
					self::sync_page( (int) $p->id );
					$synced++;
				}
			}
		}

		return $synced;
	}

	/**
	 * Display state badge in wp-admin/edit.php?post_type=page.
	 *
	 * @param array    $post_states Existing post state labels.
	 * @param \WP_Post $post        Post object.
	 * @return array
	 */
	public static function filter_post_states( array $post_states, $post ): array {
		if ( ! $post || ! is_object( $post ) || ! isset( $post->ID ) ) {
			return $post_states;
		}
		if ( function_exists( 'get_post_meta' ) && '1' === (string) get_post_meta( (int) $post->ID, self::META_IS_SCP, true ) ) {
			$post_states['seo_city_page'] = __( 'SEO City Page', 'seo-city-pages' );
		}
		return $post_states;
	}

	/**
	 * Modify row actions for SEO City Pages in Pages table.
	 *
	 * @param array    $actions Action links.
	 * @param \WP_Post $post    Post object.
	 * @return array
	 */
	public static function filter_row_actions( array $actions, $post ): array {
		if ( ! $post || ! is_object( $post ) || ! isset( $post->ID ) ) {
			return $actions;
		}
		if ( ! function_exists( 'get_post_meta' ) ) {
			return $actions;
		}

		$page_id = get_post_meta( (int) $post->ID, self::META_PAGE_ID, true );
		if ( ! empty( $page_id ) ) {
			$url_path = get_post_meta( $post->ID, self::META_URL_PATH, true );
			if ( ! empty( $url_path ) ) {
				$full_url = URL_Engine::build_full_url( $url_path );
				$actions['view'] = sprintf(
					'<a href="%s" target="_blank" aria-label="%s">%s</a>',
					esc_url( $full_url ),
					esc_attr( __( 'View live SEO City Page', 'seo-city-pages' ) ),
					esc_html( __( 'View Live URL ↗', 'seo-city-pages' ) )
				);
			}

			// Add direct link to SEO review editor
			$review_url = admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=view&page_id=' . (int) $page_id );
			$actions['scp_review'] = sprintf(
				'<a href="%s" style="color: #02426A; font-weight: 600;">%s</a>',
				esc_url( $review_url ),
				esc_html( __( 'Manage AI Content', 'seo-city-pages' ) )
			);
		}

		return $actions;
	}

	/**
	 * Filter permalink to return the custom hierarchical SEO route.
	 *
	 * @param string   $permalink Standard page link.
	 * @param \WP_Post $post      Post object.
	 * @return string
	 */
	public static function filter_permalink( string $permalink, $post ): string {
		if ( ! function_exists( 'get_post_meta' ) ) {
			return $permalink;
		}

		$post_obj = is_numeric( $post ) ? get_post( $post ) : $post;
		if ( $post_obj && '1' === get_post_meta( $post_obj->ID, self::META_IS_SCP, true ) ) {
			$url_path = get_post_meta( $post_obj->ID, self::META_URL_PATH, true );
			if ( ! empty( $url_path ) ) {
				return URL_Engine::build_full_url( $url_path );
			}
		}
		return $permalink;
	}
}
