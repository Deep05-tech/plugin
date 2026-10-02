<?php
/**
 * Keywords Bank Admin Template.
 *
 * @package SeoCityPages\Admin
 * @var string   $notice
 * @var string   $notice_type
 * @var object[] $keywords
 * @var int      $total_items
 * @var int      $paged
 * @var int      $total_pages
 * @var string   $search
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sample_download_url = admin_url( 'admin.php?page=seo-city-pages-keywords&action=download_sample' );
?>

<div class="wrap seo-city-pages-wrap">
	<div class="scp-header">
		<div class="scp-header-left">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Target Keyword Bank', 'seo-city-pages' ); ?></h1>
			<p class="scp-subtitle"><?php esc_html_e( 'Upload a CSV containing your target SEO keywords. The AI Content Engine will automatically incorporate these keywords across all generated city landing pages.', 'seo-city-pages' ); ?></p>
		</div>
		<div class="scp-header-right">
			<a href="<?php echo esc_url( $sample_download_url ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download Sample Keywords CSV', 'seo-city-pages' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages' ) ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'seo-city-pages' ); ?>
			</a>
		</div>
	</div>

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible" style="margin: 15px 0 20px;">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Stats & Info Ribbon -->
	<div class="scp-status-banner" style="display: flex; flex-wrap: wrap; gap: 20px; margin: 15px 0 20px; padding: 14px 18px; background: #fff; border-left: 4px solid #003865; box-shadow: 0 1px 3px rgba(0,0,0,0.05); align-items: center;">
		<div>
			<strong><?php esc_html_e( 'Active Keyword Bank:', 'seo-city-pages' ); ?></strong>
			<span class="scp-badge scp-badge-active" style="font-size: 13px; font-weight: 700; margin-left: 6px;">
				<?php printf( esc_html__( '%d Keywords Stored', 'seo-city-pages' ), (int) $total_items ); ?>
			</span>
		</div>
		<div>
			<strong><?php esc_html_e( 'AI Integration:', 'seo-city-pages' ); ?></strong>
			<span class="scp-badge scp-badge-info" style="margin-left: 6px;"><?php esc_html_e( 'Automatic Semantic Weaving (1.5%–3.0% Density)', 'seo-city-pages' ); ?></span>
		</div>
	</div>

	<div style="display: grid; grid-template-columns: 360px 1fr; gap: 24px; align-items: start; margin-top: 20px;">
		
		<!-- LEFT: Upload Keywords CSV Card -->
		<div class="scp-card" style="background: #ffffff; border-radius: 8px; border: 1px solid #ccd0d4; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<h2 style="font-size: 16px; font-weight: 700; margin: 0 0 14px 0; color: #1d2327;">
				<span class="dashicons dashicons-upload" style="color: #2271b1; vertical-align: middle;"></span> <?php esc_html_e( 'Upload Keywords CSV', 'seo-city-pages' ); ?>
			</h2>

			<p class="description" style="margin-bottom: 16px;">
				<?php esc_html_e( 'Upload a CSV file with your target keywords (one keyword per row). The file only needs a single column.', 'seo-city-pages' ); ?>
			</p>

			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-keywords' ) ); ?>">
				<?php wp_nonce_field( 'scp_upload_keywords_action', 'scp_upload_nonce' ); ?>

				<div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px 14px; text-align: center; margin-bottom: 16px;">
					<span class="dashicons dashicons-media-spreadsheet" style="font-size: 32px; height: 32px; width: 32px; color: #64748b; margin-bottom: 8px;"></span>
					<div style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 4px;"><?php esc_html_e( 'Select Keywords CSV File', 'seo-city-pages' ); ?></div>
					<input type="file" name="keywords_csv" accept=".csv,text/csv,text/plain" required style="max-width: 100%; margin-top: 8px;" />
				</div>

				<div style="margin-bottom: 18px;">
					<label style="font-weight: 600; display: block; margin-bottom: 8px; color: #334155;"><?php esc_html_e( 'Import Mode:', 'seo-city-pages' ); ?></label>
					<label style="display: block; margin-bottom: 6px;">
						<input type="radio" name="scp_import_mode" value="append" checked />
						<?php esc_html_e( 'Append to existing keywords', 'seo-city-pages' ); ?>
					</label>
					<label style="display: block; color: #b32d2e;">
						<input type="radio" name="scp_import_mode" value="replace" />
						<?php esc_html_e( 'Replace entire keyword bank', 'seo-city-pages' ); ?>
					</label>
				</div>

				<button type="submit" name="scp_upload_keywords_csv" value="1" class="button button-primary button-large" style="width: 100%; text-align: center;">
					<span class="dashicons dashicons-cloud-upload"></span> <?php esc_html_e( 'Upload & Ingest Keywords', 'seo-city-pages' ); ?>
				</button>
			</form>

			<hr style="margin: 20px 0; border: 0; border-top: 1px solid #e2e8f0;" />

			<h3 style="font-size: 13px; font-weight: 700; margin: 0 0 8px 0; text-transform: uppercase; color: #64748b;"><?php esc_html_e( 'Supported CSV Format', 'seo-city-pages' ); ?></h3>
			<p style="font-size: 12px; color: #64748b; margin-bottom: 8px;"><?php esc_html_e( 'A single column of keywords, exported directly from Google Keyword Planner, Semrush, or Ahrefs:', 'seo-city-pages' ); ?></p>
			<pre style="background: #f1f5f9; padding: 10px; border-radius: 6px; font-size: 12px; color: #0f172a; margin: 0; line-height: 1.5;">keyword
SEO Agency
Local Search Engine Optimization
Best SEO Company
Google Map Pack Ranking
Organic Traffic Growth</pre>

			<?php if ( $total_items > 0 ) : ?>
				<hr style="margin: 20px 0; border: 0; border-top: 1px solid #e2e8f0;" />
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-keywords' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete ALL keywords from the bank?', 'seo-city-pages' ) ); ?>');">
					<?php wp_nonce_field( 'scp_clear_keywords_action', 'scp_clear_nonce' ); ?>
					<button type="submit" name="scp_clear_all_keywords" value="1" class="button button-link-delete" style="font-size: 12px; color: #b32d2e;">
						<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear All Keywords from Bank', 'seo-city-pages' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</div>

		<!-- RIGHT: Keywords List & Search Table -->
		<div class="scp-card" style="background: #ffffff; border-radius: 8px; border: 1px solid #ccd0d4; padding: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
				<h2 style="font-size: 16px; font-weight: 700; margin: 0; color: #1d2327;">
					<?php esc_html_e( 'Stored Target Keywords', 'seo-city-pages' ); ?>
					<span style="font-weight: 400; font-size: 13px; color: #64748b;">(<?php echo (int) $total_items; ?>)</span>
				</h2>

				<!-- Search Filter -->
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: flex; gap: 6px;">
					<input type="hidden" name="page" value="seo-city-pages-keywords" />
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search keywords...', 'seo-city-pages' ); ?>" class="regular-text" style="height: 32px;" />
					<button type="submit" class="button button-secondary"><?php esc_html_e( 'Search', 'seo-city-pages' ); ?></button>
					<?php if ( ! empty( $search ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-keywords' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Reset', 'seo-city-pages' ); ?></a>
					<?php endif; ?>
				</form>
			</div>

			<?php if ( empty( $keywords ) ) : ?>
				<div style="text-align: center; padding: 40px 20px; color: #64748b; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
					<span class="dashicons dashicons-search" style="font-size: 36px; height: 36px; width: 36px; color: #94a3b8; margin-bottom: 10px;"></span>
					<p style="font-size: 14px; margin: 0 0 6px 0; font-weight: 600;">
						<?php echo ! empty( $search ) ? esc_html__( 'No matching keywords found.', 'seo-city-pages' ) : esc_html__( 'No keywords uploaded yet.', 'seo-city-pages' ); ?>
					</p>
					<p style="font-size: 12px; margin: 0;">
						<?php esc_html_e( 'Upload your keywords CSV using the form on the left to activate AI keyword injection across all landing pages.', 'seo-city-pages' ); ?>
					</p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped" style="border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
					<thead>
						<tr>
							<th style="width: 60px;"><?php esc_html_e( '#', 'seo-city-pages' ); ?></th>
							<th><?php esc_html_e( 'Target Keyword Phrase', 'seo-city-pages' ); ?></th>
							<th style="width: 160px;"><?php esc_html_e( 'Date Ingested', 'seo-city-pages' ); ?></th>
							<th style="width: 80px; text-align: right;"><?php esc_html_e( 'Action', 'seo-city-pages' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $keywords as $idx => $kw ) : ?>
							<?php
							$delete_url = wp_nonce_url(
								admin_url( 'admin.php?page=seo-city-pages-keywords&action=delete&id=' . (int) $kw->id ),
								'delete_keyword_' . (int) $kw->id
							);
							?>
							<tr>
								<td style="color: #94a3b8; font-size: 12px;">#<?php echo esc_html( $offset + $idx + 1 ); ?></td>
								<td>
									<strong style="color: #003865; font-size: 13px;"><?php echo esc_html( $kw->keyword ); ?></strong>
								</td>
								<td style="color: #64748b; font-size: 12px;"><?php echo esc_html( $kw->created_at ); ?></td>
								<td style="text-align: right;">
									<a href="<?php echo esc_url( $delete_url ); ?>" style="color: #b32d2e; text-decoration: none;" onclick="return confirm('<?php echo esc_js( __( 'Remove this keyword from the bank?', 'seo-city-pages' ) ); ?>');" title="<?php esc_attr_e( 'Delete Keyword', 'seo-city-pages' ); ?>">
										<span class="dashicons dashicons-trash"></span>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Pagination -->
				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav bottom" style="margin-top: 14px;">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php printf( esc_html__( '%d items', 'seo-city-pages' ), (int) $total_items ); ?></span>
							<?php
							echo paginate_links( [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $paged,
							] );
							?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

	</div>
</div>
