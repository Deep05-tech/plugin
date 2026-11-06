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
		// Note: maybe_sync_all_published is removed from 'init' to prevent race conditions and performance degradation.
		// Synchronization is triggered on deliberate publish events or manual maintenance.

		// Two-way synchronization with WordPress Core Pages (edit.php?post_type=page)
		add_action( 'trashed_post', [ __CLASS__, 'on_trashed_post' ] );
		add_action( 'untrashed_post', [ __CLASS__, 'on_untrashed_post' ] );
		add_action( 'before_delete_post', [ __CLASS__, 'on_deleted_post' ] );

		// Custom permalink filtering for SEO City Pages across admin and frontend
		add_filter( 'post_link', [ __CLASS__, 'filter_permalink' ], 10, 2 );
		add_filter( 'page_link', [ __CLASS__, 'filter_permalink' ], 10, 2 );

		// Admin column & state enhancements on wp-admin/edit.php?post_type=page
		if ( is_admin() ) {
			add_filter( 'display_post_states', [ __CLASS__, 'filter_post_states' ], 10, 2 );
			add_filter( 'page_row_actions', [ __CLASS__, 'filter_row_actions' ], 10, 2 );
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

		// Concurrency protection: prevent re-entrant or duplicate sync calls for the same page in a single request.
		static $in_flight_syncs = [];
		if ( isset( $in_flight_syncs[ $page_id ] ) ) {
			return (int) $in_flight_syncs[ $page_id ];
		}

		// Distributed cross-process lock: prevents race conditions between multiple PHP-FPM workers / background jobs.
		$lock_name = 'scp_sync_lock_' . $page_id;
		$got_lock  = false;
		if ( isset( $wpdb->dbh ) ) {
			$got_lock = (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name ) );
		}

		try {
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

			// 1. Check if the stored wp_post_id in the city page record points to a valid post.
			$existing_post_id = (int) ( $page->wp_post_id ?? 0 );
			if ( $existing_post_id > 0 && ! get_post( $existing_post_id ) ) {
				$existing_post_id = 0;
			}

			// 2. Strict duplicate check in postmeta: find ANY existing posts for this page ID.
			if ( $existing_post_id <= 0 && isset( $wpdb->postmeta ) ) {
				$found_post_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT pm.post_id FROM {$wpdb->postmeta} pm
						INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
						WHERE pm.meta_key = %s AND pm.meta_value = %s
						ORDER BY (p.post_status = 'publish') DESC, p.ID DESC",
						self::META_PAGE_ID,
						(string) $page_id
					)
				);

				if ( ! empty( $found_post_ids ) ) {
					$existing_post_id = (int) $found_post_ids[0];

					// If redundant duplicate posts exist for this page, clean them up immediately!
					if ( count( $found_post_ids ) > 1 ) {
						for ( $i = 1; $i < count( $found_post_ids ); $i++ ) {
							$dup_id = (int) $found_post_ids[ $i ];
							if ( function_exists( 'wp_delete_post' ) ) {
								wp_delete_post( $dup_id, true );
							}
						}
					}
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
				update_post_meta( $post_id, '_wp_page_template', 'elementor_canvas' );

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

			$in_flight_syncs[ $page_id ] = (int) $post_id;
			return (int) $post_id;
		}
	} finally {
			if ( $got_lock && isset( $wpdb->dbh ) ) {
				$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			}
		}

		$in_flight_syncs[ $page_id ] = 0;
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
			$page = $wpdb->get_row( $wpdb->prepare( "SELECT id, wp_post_id FROM {$pages_table} WHERE id = %d", $page_id ) );

			// Safety check: Only trash the city page if this post was the canonical assigned wp_post_id
			if ( $page && (int) $page->wp_post_id === (int) $post_id ) {
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
			global $wpdb;
			$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );
			$page = $wpdb->get_row( $wpdb->prepare( "SELECT id, wp_post_id FROM {$pages_table} WHERE id = %d", $page_id ) );

			// Safety check: Only delete the city page record if this post was the canonical assigned wp_post_id
			if ( $page && (int) $page->wp_post_id === (int) $post_id ) {
				City_Page_Repository::delete( $page_id, false );
				Logger::info( sprintf( 'SEO City Page #%d deleted permanently following WordPress post #%d deletion', $page_id, $post_id ) );
			}
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
	 * Scan and permanently clean up all duplicate WordPress posts created for SEO City Pages.
	 *
	 * Keeps exactly 1 valid canonical post per city page, and permanently removes all redundant copies.
	 *
	 * @return array Summary of cleanup results.
	 */
	public static function cleanup_duplicate_posts(): array {
		global $wpdb;

		// Prevent execution timeout on large scale 3,700+ page libraries
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 );
		}

		// Temporarily unhook two-way sync hooks during bulk cleanup to prevent cascading triggers
		remove_action( 'trashed_post', [ __CLASS__, 'on_trashed_post' ] );
		remove_action( 'before_delete_post', [ __CLASS__, 'on_deleted_post' ] );

		$pages_table = Schema::get_table_name( Schema::TABLE_CITY_PAGES );

		$deleted_posts    = 0;
		$retained_posts   = 0;
		$relinked_records = 0;
		$processed_pids   = [];
		$total_groups     = 0;

		// Helper function to deduplicate any array of post IDs
		$dedup_post_ids = function( array $post_ids, int $hint_page_id = 0 ) use ( &$deleted_posts, &$retained_posts, &$relinked_records, &$processed_pids, $wpdb, $pages_table ) {
			$post_ids = array_unique( array_map( 'intval', $post_ids ) );
			// Filter only valid, existing posts with _is_seo_city_page = '1'
			$valid_ids = [];
			foreach ( $post_ids as $pid ) {
				if ( $pid <= 0 ) {
					continue;
				}
				$post = get_post( $pid );
				if ( ! $post || 'trash' === $post->post_status ) {
					continue;
				}
				// Strictly ensure this is an SEO city page post
				if ( '1' !== (string) get_post_meta( $pid, self::META_IS_SCP, true ) ) {
					continue;
				}
				$valid_ids[] = $pid;
			}

			if ( count( $valid_ids ) <= 1 ) {
				if ( ! empty( $valid_ids ) ) {
					$processed_pids[ $valid_ids[0] ] = true;
				}
				return;
			}

			// Determine canonical keeper:
			// 1. If any post is already marked in processed_pids, keep that one
			$keeper_id = 0;
			foreach ( $valid_ids as $vid ) {
				if ( isset( $processed_pids[ $vid ] ) ) {
					$keeper_id = $vid;
					break;
				}
			}

			// 2. If hint_page_id provided, check its designated wp_post_id
			if ( $keeper_id <= 0 && $hint_page_id > 0 ) {
				$page_row = $wpdb->get_row( $wpdb->prepare( "SELECT wp_post_id FROM {$pages_table} WHERE id = %d", $hint_page_id ) );
				if ( $page_row && in_array( (int) $page_row->wp_post_id, $valid_ids, true ) ) {
					$keeper_id = (int) $page_row->wp_post_id;
				}
			}

			// 3. Keep newest published post with elementor data
			if ( $keeper_id <= 0 ) {
				foreach ( $valid_ids as $vid ) {
					$post = get_post( $vid );
					$el_data = get_post_meta( $vid, '_elementor_data', true );
					if ( $post && 'publish' === $post->post_status && ! empty( $el_data ) && '[]' !== $el_data ) {
						$keeper_id = $vid;
						break;
					}
				}
			}

			// 4. Keep newest published post
			if ( $keeper_id <= 0 ) {
				foreach ( $valid_ids as $vid ) {
					$post = get_post( $vid );
					if ( $post && 'publish' === $post->post_status ) {
						$keeper_id = $vid;
						break;
					}
				}
			}

			// 5. Fallback to newest ID
			if ( $keeper_id <= 0 ) {
				$keeper_id = max( $valid_ids );
			}

			$processed_pids[ $keeper_id ] = true;
			$retained_posts++;

			// Permanently delete all redundant posts in this group
			foreach ( $valid_ids as $vid ) {
				if ( $vid === $keeper_id ) {
					continue;
				}

				// SAFETY: Verify _is_seo_city_page is '1' before deleting
				if ( '1' === (string) get_post_meta( $vid, self::META_IS_SCP, true ) ) {
					wp_delete_post( $vid, true );
					$deleted_posts++;
				}
			}

			// Link keeper_id to the city page record
			$associated_page_id = (int) get_post_meta( $keeper_id, self::META_PAGE_ID, true );
			if ( $associated_page_id <= 0 && $hint_page_id > 0 ) {
				$associated_page_id = $hint_page_id;
				update_post_meta( $keeper_id, self::META_PAGE_ID, $associated_page_id );
			}

			if ( $associated_page_id > 0 ) {
				$wpdb->update(
					$pages_table,
					[
						'wp_post_id' => $keeper_id,
						'updated_at' => current_time( 'mysql' ),
					],
					[ 'id' => $associated_page_id ]
				);
				$relinked_records++;
			}
		};

		// --- PASS 1: Group by _seo_city_page_id ---
		$groups_by_id = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS page_id, GROUP_CONCAT(pm.post_id ORDER BY pm.post_id DESC) AS post_ids
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s AND p.post_status != 'trash'
				GROUP BY pm.meta_value
				HAVING COUNT(pm.post_id) > 1",
				self::META_PAGE_ID
			)
		);
		if ( ! empty( $groups_by_id ) ) {
			$total_groups += count( $groups_by_id );
			foreach ( $groups_by_id as $g ) {
				$dedup_post_ids( explode( ',', (string) $g->post_ids ), (int) $g->page_id );
			}
		}

		// --- PASS 2: Group by identical post_title among SEO City Pages ---
		$groups_by_title = $wpdb->get_results(
			"SELECT p.post_title, GROUP_CONCAT(p.ID ORDER BY p.post_date DESC, p.ID DESC) AS post_ids
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_is_seo_city_page'
			WHERE p.post_type = 'page' AND p.post_status != 'trash' AND p.post_title != ''
			GROUP BY p.post_title
			HAVING COUNT(p.ID) > 1"
		);
		if ( ! empty( $groups_by_title ) ) {
			$total_groups += count( $groups_by_title );
			foreach ( $groups_by_title as $g ) {
				$dedup_post_ids( explode( ',', (string) $g->post_ids ) );
			}
		}

		// --- PASS 3: Group by _seo_city_page_url / target URL path ---
		$groups_by_url = $wpdb->get_results(
			"SELECT pm.meta_value AS url_path, GROUP_CONCAT(pm.post_id ORDER BY pm.post_id DESC) AS post_ids
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_seo_city_page_url' AND pm.meta_value != '' AND p.post_status != 'trash'
			GROUP BY pm.meta_value
			HAVING COUNT(pm.post_id) > 1"
		);
		if ( ! empty( $groups_by_url ) ) {
			$total_groups += count( $groups_by_url );
			foreach ( $groups_by_url as $g ) {
				$dedup_post_ids( explode( ',', (string) $g->post_ids ) );
			}
		}

		// --- PASS 4: Group by Base Slug (Stripping -2, -3, -4 suffixes) ---
		$groups_by_slug = $wpdb->get_results(
			"SELECT REGEXP_REPLACE(p.post_name, '-[0-9]+$', '') AS base_slug, GROUP_CONCAT(p.ID ORDER BY p.ID DESC) AS post_ids
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_is_seo_city_page'
			WHERE p.post_type = 'page' AND p.post_status != 'trash'
			GROUP BY base_slug
			HAVING COUNT(p.ID) > 1"
		);
		if ( ! empty( $groups_by_slug ) ) {
			$total_groups += count( $groups_by_slug );
			foreach ( $groups_by_slug as $g ) {
				$dedup_post_ids( explode( ',', (string) $g->post_ids ) );
			}
		}

		// Clear Elementor CSS cache if Elementor is active
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		Logger::info(
			sprintf(
				'SEO City Pages comprehensive duplicate cleanup finished. Scanned %d duplicate groups, removed %d duplicate posts, retained %d canonical posts.',
				$total_groups,
				$deleted_posts,
				$retained_posts
			),
			[
				'duplicate_groups' => $total_groups,
				'deleted_posts'    => $deleted_posts,
				'retained_posts'   => $retained_posts,
				'relinked_records' => $relinked_records,
			]
		);

		// Restore two-way sync hooks
		add_action( 'trashed_post', [ __CLASS__, 'on_trashed_post' ] );
		add_action( 'before_delete_post', [ __CLASS__, 'on_deleted_post' ] );

		return [
			'success'          => true,
			'duplicate_groups' => $total_groups,
			'deleted_posts'    => $deleted_posts,
			'retained_posts'   => $retained_posts,
			'relinked_records' => $relinked_records,
			'message'          => sprintf(
				__( 'Cleanup complete! Successfully removed %d duplicate pages. %d unique canonical pages retained.', 'seo-city-pages' ),
				$deleted_posts,
				$retained_posts
			),
		];
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
			$page     = City_Page_Repository::get( (int) $page_id );
			$url_path = $page && ! empty( $page->url_path ) ? $page->url_path : get_post_meta( $post->ID, self::META_URL_PATH, true );
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
			$page_id = (int) get_post_meta( $post_obj->ID, self::META_PAGE_ID, true );
			if ( $page_id > 0 ) {
				$page = City_Page_Repository::get( $page_id );
				if ( $page && ! empty( $page->url_path ) ) {
					return URL_Engine::build_full_url( $page->url_path );
				}
			}

			$url_path = get_post_meta( $post_obj->ID, self::META_URL_PATH, true );
			if ( ! empty( $url_path ) ) {
				return URL_Engine::build_full_url( $url_path );
			}
		}
		return $permalink;
	}
}
