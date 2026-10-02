<?php
/**
 * Keywords Bank Admin Screen Controller.
 *
 * @package SeoCityPages\Admin
 */

namespace SeoCityPages\Admin;

use SeoCityPages\Security\Capabilities;
use SeoCityPages\Security\Security;
use SeoCityPages\Database\Keyword_Repository;
use SeoCityPages\Logging\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keywords_Controller Class.
 */
class Keywords_Controller {

	/**
	 * Render the Keywords Bank management admin page.
	 *
	 * @return void
	 */
	public function render(): void {
		Security::enforce_capability( Capabilities::MANAGE );

		$action = sanitize_text_field( $_GET['action'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// 1. Handle Sample CSV Download.
		if ( 'download_sample' === $action ) {
			$this->handle_download_sample();
			return;
		}

		$notice = '';
		$notice_type = 'success';

		// 2. Handle Delete Single Keyword.
		if ( 'delete' === $action && ! empty( $_GET['id'] ) ) {
			if ( check_admin_referer( 'delete_keyword_' . (int) $_GET['id'] ) ) {
				Keyword_Repository::delete_by_id( (int) $_GET['id'] );
				$notice = __( 'Keyword removed from bank successfully.', 'seo-city-pages' );
			}
		}

		// 3. Handle Clear All Keywords.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['scp_clear_all_keywords'] ) ) {
			if ( check_admin_referer( 'scp_clear_keywords_action', 'scp_clear_nonce' ) ) {
				Keyword_Repository::clear_all();
				$notice = __( 'All keywords have been cleared from the Keyword Bank.', 'seo-city-pages' );
			}
		}

		// 4. Handle CSV File Upload.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['scp_upload_keywords_csv'] ) ) {
			$notice = $this->handle_upload( $notice_type );
		}

		$search       = sanitize_text_field( $_GET['s'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged        = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page     = 50;
		$offset       = ( $paged - 1 ) * $per_page;
		$total_items  = Keyword_Repository::count( $search );
		$keywords     = Keyword_Repository::get_all( $per_page, $offset, $search );
		$total_pages  = ceil( $total_items / $per_page );

		include SEO_CITY_PAGES_PLUGIN_DIR . 'templates/admin/keywords.php';
	}

	/**
	 * Handle CSV upload and keyword extraction.
	 *
	 * @param string $notice_type Reference to notice status.
	 * @return string Feedback message.
	 */
	private function handle_upload( string &$notice_type ): string {
		if ( ! check_admin_referer( 'scp_upload_keywords_action', 'scp_upload_nonce' ) ) {
			$notice_type = 'error';
			return __( 'Security check failed. Please refresh and try again.', 'seo-city-pages' );
		}

		if ( empty( $_FILES['keywords_csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['keywords_csv']['tmp_name'] ) ) {
			$notice_type = 'error';
			return __( 'Please select a valid CSV file containing keywords to upload.', 'seo-city-pages' );
		}

		$file_path = $_FILES['keywords_csv']['tmp_name'];
		$replace   = ! empty( $_POST['scp_import_mode'] ) && 'replace' === $_POST['scp_import_mode'];

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			$notice_type = 'error';
			return __( 'Unable to read the uploaded CSV file.', 'seo-city-pages' );
		}

		$extracted_keywords = [];
		$row_index = 0;

		while ( ( $row = fgetcsv( $handle, 4096, ',' ) ) !== false ) {
			$row_index++;
			if ( empty( $row ) ) {
				continue;
			}

			// If header row contains 'keyword' or 'keywords', skip header.
			if ( 1 === $row_index ) {
				$first_cell = strtolower( trim( (string) $row[0] ) );
				if ( in_array( $first_cell, [ 'keyword', 'keywords', 'target_keyword', 'key phrase', 'search term' ], true ) ) {
					continue;
				}
			}

			// Extract all non-empty cells in the row.
			foreach ( $row as $cell ) {
				$clean = trim( (string) $cell );
				if ( ! empty( $clean ) ) {
					// Handle comma-separated lists inside cell.
					$sub_kws = explode( ',', $clean );
					foreach ( $sub_kws as $kw ) {
						$trimmed_kw = trim( $kw );
						if ( ! empty( $trimmed_kw ) ) {
							$extracted_keywords[] = $trimmed_kw;
						}
					}
				}
			}
		}

		fclose( $handle );

		if ( empty( $extracted_keywords ) ) {
			$notice_type = 'error';
			return __( 'No valid keywords found in the uploaded file.', 'seo-city-pages' );
		}

		$results = Keyword_Repository::bulk_insert( $extracted_keywords, $replace );

		$notice_type = 'success';
		return sprintf(
			/* translators: 1: Imported count, 2: Skipped count, 3: Total count */
			__( 'Keywords imported successfully! Added %1$d new keywords to the bank (%2$d duplicates/empty skipped out of %3$d total).', 'seo-city-pages' ),
			$results['imported'],
			$results['skipped'],
			$results['total']
		);
	}

	/**
	 * Stream sample keywords CSV file for download.
	 *
	 * @return void
	 */
	private function handle_download_sample(): void {
		if ( ! current_user_can( Capabilities::MANAGE ) ) {
			wp_die( esc_html__( 'Permission denied.', 'seo-city-pages' ) );
		}

		$filename = 'keywords-sample.csv';
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$sample_file = SEO_CITY_PAGES_PLUGIN_DIR . 'samples/sample-keywords-only.csv';
		if ( file_exists( $sample_file ) ) {
			readfile( $sample_file ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo "keyword\nSEO Agency\nLocal Search Engine Optimization\nBest SEO Company\nGoogle Map Pack Ranking\nOrganic Traffic Growth\n";
		}
		exit;
	}
}
