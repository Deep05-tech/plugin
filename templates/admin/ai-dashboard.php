<?php
/**
 * AI Content Engine Dashboard Template.
 *
 * @package SeoCityPages\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'OpenAI Content Generation Engine', 'seo-city-pages' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Review & Approve Content', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<!-- Stats Overview Cards -->
	<div class="scp-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 20px 0;">
		<div class="scp-stat-card" style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #02426A; padding: 18px 20px; border-radius: 4px;">
			<div style="font-size: 13px; color: #646970; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Total City Pages', 'seo-city-pages' ); ?></div>
			<div id="scp-stat-total-pages" style="font-size: 28px; font-weight: 700; color: #1d2327; margin-top: 4px;"><?php echo esc_html( number_format_i18n( $stats['total_pages'] ) ); ?></div>
		</div>
		<div class="scp-stat-card" style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #dba617; padding: 18px 20px; border-radius: 4px;">
			<div style="font-size: 13px; color: #646970; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Needs Review', 'seo-city-pages' ); ?></div>
			<div id="scp-stat-needs-review" style="font-size: 28px; font-weight: 700; color: #dba617; margin-top: 4px;"><?php echo esc_html( number_format_i18n( $stats['needs_review'] ) ); ?></div>
		</div>
		<div class="scp-stat-card" style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #00a32a; padding: 18px 20px; border-radius: 4px;">
			<div style="font-size: 13px; color: #646970; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Approved Content', 'seo-city-pages' ); ?></div>
			<div id="scp-stat-approved" style="font-size: 28px; font-weight: 700; color: #00a32a; margin-top: 4px;"><?php echo esc_html( number_format_i18n( $stats['approved'] + $stats['manually_edited'] ) ); ?></div>
		</div>
		<div class="scp-stat-card" style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #72aee6; padding: 18px 20px; border-radius: 4px;">
			<div style="font-size: 13px; color: #646970; font-weight: 600; text-transform: uppercase;"><?php esc_html_e( 'Total Tokens Used', 'seo-city-pages' ); ?></div>
			<div id="scp-stat-total-tokens" style="font-size: 28px; font-weight: 700; color: #1d2327; margin-top: 4px;"><?php echo esc_html( number_format_i18n( $stats['total_tokens'] ) ); ?></div>
		</div>
	</div>

	<?php
	$has_active       = ! empty( $active_job ) && in_array( $active_job->status, [ 'pending', 'processing' ], true ) && (int) $active_job->processed_pages < (int) $active_job->total_pages;
	$active_total     = $has_active ? (int) $active_job->total_pages : 0;
	$active_processed = $has_active ? (int) $active_job->processed_pages : 0;
	$active_success   = $has_active ? (int) $active_job->success_pages : 0;
	$active_failed    = $has_active ? (int) $active_job->failed_pages : 0;
	$active_pct       = ( $has_active && $active_total > 0 ) ? min( 100, (int) round( ( $active_processed / $active_total ) * 100 ) ) : 0;
	$active_id        = $has_active ? (int) $active_job->id : 0;
	?>

	<!-- 1-Click Workflow Fast Bar -->
	<div style="background: #fff; border: 1.5px solid #dcdcde; border-radius: 6px; padding: 14px 20px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-top: 20px; margin-bottom: 20px;">
		<div>
			<strong style="font-size: 15px; color: #1e1e1e; display: flex; align-items: center; gap: 6px;">
				<span class="dashicons dashicons-yes-alt" style="color: #00a32a; font-size: 20px;"></span>
				<?php esc_html_e( 'Fast Approval & Publishing Actions', 'seo-city-pages' ); ?>
			</strong>
			<span class="description" style="margin-top: 2px; display: block;">
				<?php esc_html_e( 'Approve and publish your generated city landing pages in 1-click without doing it manually.', 'seo-city-pages' ); ?>
			</span>
		</div>
		<div style="display: flex; flex-wrap: wrap; gap: 8px;">
			<button type="button" id="scp-btn-approve-all" class="button button-secondary" style="font-weight: 600; color: #007017; border-color: #00a32a;" <?php echo empty( $stats['needs_review'] ) ? 'disabled' : ''; ?>>
				<?php printf( esc_html__( '✓ Approve All Pending (%d)', 'seo-city-pages' ), (int) $stats['needs_review'] ); ?>
			</button>
			<button type="button" id="scp-btn-publish-all" class="button button-primary" style="font-weight: 600; background: #00a32a; border-color: #00a32a;" <?php echo empty( $stats['approved'] ) ? 'disabled' : ''; ?>>
				<?php printf( esc_html__( '🚀 Publish All Approved (%d)', 'seo-city-pages' ), (int) $stats['approved'] ); ?>
			</button>
			<button type="button" id="scp-btn-approve-publish-all" class="button button-primary" style="font-weight: 700; background: #02426A; border-color: #02426A;">
				<?php esc_html_e( '⚡ 1-Click Approve & Publish All Live', 'seo-city-pages' ); ?>
			</button>
		</div>
	</div>

	<!-- Action Controls -->
	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
		<!-- Left: Bulk Generation Hub -->
		<div class="postbox" style="padding: 20px;">
			<h2 style="margin-top: 0; font-size: 18px;"><?php esc_html_e( '⚡ Bulk Content Generation', 'seo-city-pages' ); ?></h2>
			<p class="description" style="margin-bottom: 20px;">
				<?php esc_html_e( 'Generate structured, anti-hallucinatory content for your SEO City Pages in non-blocking batches using OpenAI Structured Outputs.', 'seo-city-pages' ); ?>
			</p>

			<div style="background: #f0f6fa; border: 1px solid #cce0ed; border-radius: 8px; padding: 18px; margin-bottom: 20px;">
				<label style="font-weight: 600; display: block; margin-bottom: 8px;"><?php esc_html_e( 'Generation Scope:', 'seo-city-pages' ); ?></label>
				<label style="display: block; margin-bottom: 6px;">
					<input type="radio" name="scp_bulk_mode" value="missing_only" checked>
					<strong><?php esc_html_e( 'Generate Only Missing Content', 'seo-city-pages' ); ?></strong>
					<span class="description" id="scp-scope-missing-desc"><?php printf( esc_html__( '(%d pages without generated content)', 'seo-city-pages' ), $stats['not_generated'] ); ?></span>
				</label>
				<label style="display: block;">
					<input type="radio" name="scp_bulk_mode" value="all">
					<strong><?php esc_html_e( 'Regenerate All Pages', 'seo-city-pages' ); ?></strong>
					<span class="description"><?php printf( esc_html__( '(%d total pages; existing approved versions remain safe until new versions are approved)', 'seo-city-pages' ), $stats['total_pages'] ); ?></span>
				</label>
			</div>

			<div style="display: flex; gap: 12px; align-items: center;">
				<button type="button" id="scp-start-bulk-btn" class="button button-primary button-hero" style="background: #02426A; border-color: #02426A;" <?php echo $has_active ? 'disabled' : ''; ?>>
					<?php echo $has_active ? esc_html__( 'Job in Progress...', 'seo-city-pages' ) : esc_html__( 'Start Bulk Generation →', 'seo-city-pages' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-settings#scp-ai-tab' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'AI & Model Settings ⚙️', 'seo-city-pages' ); ?>
				</a>
			</div>

			<!-- Live Progress Box (Persists across page refreshes) -->
			<div id="scp-bulk-progress-box" 
			     style="<?php echo $has_active ? 'display: block;' : 'display: none;'; ?> margin-top: 24px; background: #fff; border: 1.5px solid #02426A; border-radius: 8px; padding: 20px;"
			     data-active-job-id="<?php echo esc_attr( $active_id ); ?>"
			     data-active-status="<?php echo esc_attr( $has_active ? $active_job->status : '' ); ?>"
			     data-active-total="<?php echo esc_attr( $active_total ); ?>"
			     data-active-processed="<?php echo esc_attr( $active_processed ); ?>"
			     data-active-success="<?php echo esc_attr( $active_success ); ?>"
			     data-active-failed="<?php echo esc_attr( $active_failed ); ?>"
			     data-active-pct="<?php echo esc_attr( $active_pct ); ?>">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
					<h3 style="margin: 0; color: #02426A;" id="scp-bulk-status-heading">
						<?php echo $has_active ? sprintf( esc_html__( '⚡ Processing Bulk Generation (Job #%d)...', 'seo-city-pages' ), $active_id ) : esc_html__( 'Generating Content...', 'seo-city-pages' ); ?>
					</h3>
					<div id="scp-bulk-controls-toolbar" style="display: flex; gap: 8px;">
						<button type="button" id="scp-pause-bulk-btn" class="button button-small" style="font-weight: 600;">
							<?php esc_html_e( '⏸ Pause', 'seo-city-pages' ); ?>
						</button>
						<button type="button" id="scp-resume-bulk-btn" class="button button-small button-primary" style="display: none; background: #02426A; border-color: #02426A; font-weight: 600;">
							<?php esc_html_e( '▶ Resume', 'seo-city-pages' ); ?>
						</button>
						<button type="button" id="scp-cancel-bulk-btn" class="button button-small" style="color: #d63638; border-color: #d63638; font-weight: 600;">
							<?php esc_html_e( '✕ Cancel', 'seo-city-pages' ); ?>
						</button>
					</div>
				</div>
				<div style="background: #e2e8f0; border-radius: 100px; height: 16px; overflow: hidden; margin-bottom: 12px;">
					<div id="scp-bulk-progress-bar" style="background: #02426A; height: 100%; width: <?php echo esc_attr( $active_pct ); ?>%; transition: width 0.3s;"></div>
				</div>
				<div style="display: flex; justify-content: space-between; font-size: 13px; color: #5D5756; font-weight: 600;">
					<span id="scp-bulk-progress-text"><?php echo esc_html( "{$active_processed} / {$active_total} pages processed ({$active_pct}%)" ); ?></span>
					<span id="scp-bulk-success-text" style="color: #00a32a;"><?php echo esc_html( "{$active_success} succeeded" ); ?></span>
					<span id="scp-bulk-failed-text" style="color: #d63638;"><?php echo esc_html( "{$active_failed} failed" ); ?></span>
				</div>
			</div>
		</div>

		<!-- Right: OpenAI Connection Status -->
		<div class="postbox" style="padding: 20px;">
			<h2 style="margin-top: 0; font-size: 18px;"><?php esc_html_e( '🔌 OpenAI Health', 'seo-city-pages' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Test API key credentials and check endpoint latency server-side.', 'seo-city-pages' ); ?></p>
			
			<div id="scp-test-conn-result" style="margin: 16px 0; min-height: 40px;">
				<div style="padding: 10px 14px; background: #f6f7f7; border-left: 4px solid #c3c4c7; border-radius: 4px; font-size: 13px;">
					<?php esc_html_e( 'Click Test Connection to check OpenAI status.', 'seo-city-pages' ); ?>
				</div>
			</div>

			<button type="button" id="scp-test-conn-btn" class="button button-secondary" style="width: 100%;">
				<?php esc_html_e( 'Test OpenAI Connection', 'seo-city-pages' ); ?>
			</button>
		</div>
	</div>
</div>
