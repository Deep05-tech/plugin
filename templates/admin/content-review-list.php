<?php
/**
 * Content Review List View with Bulk Workflow Controls and Confirmation Modal.
 *
 * @package SeoCityPages\Admin
 */

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'AI Content Review & Publishing Workflow', 'seo-city-pages' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← AI Dashboard', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<!-- Status Filter Tabs -->
	<ul class="subsubsub" style="margin-bottom: 15px;">
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review' ) ); ?>" class="<?php echo empty( $filter_status ) ? 'current' : ''; ?>">
				<?php esc_html_e( 'All Pages', 'seo-city-pages' ); ?> <span class="count">(<?php echo esc_html( $queue_counts['total'] ?? $stats['total_pages'] ); ?>)</span>
			</a> |
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&filter_gen_status=needs_review' ) ); ?>" class="<?php echo 'needs_review' === $filter_status ? 'current' : ''; ?>">
				<?php esc_html_e( 'Needs Review', 'seo-city-pages' ); ?> <span class="count">(<?php echo esc_html( $queue_counts['needs_review'] ?? 0 ); ?>)</span>
			</a> |
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&filter_gen_status=approved' ) ); ?>" class="<?php echo 'approved' === $filter_status ? 'current' : ''; ?>">
				<?php esc_html_e( 'Approved (Unpublished)', 'seo-city-pages' ); ?> <span class="count">(<?php echo esc_html( $queue_counts['approved'] ?? 0 ); ?>)</span>
			</a> |
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&filter_gen_status=published' ) ); ?>" class="<?php echo 'published' === $filter_status ? 'current' : ''; ?>">
				<?php esc_html_e( 'Live Published', 'seo-city-pages' ); ?> <span class="count">(<?php echo esc_html( $queue_counts['published'] ?? 0 ); ?>)</span>
			</a> |
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&filter_gen_status=rejected' ) ); ?>" class="<?php echo 'rejected' === $filter_status ? 'current' : ''; ?>">
				<?php esc_html_e( 'Rejected', 'seo-city-pages' ); ?> <span class="count">(<?php echo esc_html( $queue_counts['rejected'] ?? 0 ); ?>)</span>
			</a>
		</li>
	</ul>

	<!-- 1-Click Fast Actions Bar -->
	<div class="scp-workflow-quick-bar" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; background: #fff; border: 1.5px solid #dcdcde; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px;">
		<span style="font-weight: 700; color: #1e1e1e; display: flex; align-items: center; gap: 6px; margin-right: 6px;">
			<span class="dashicons dashicons-superhero" style="color: #02426A; font-size: 20px;"></span>
			<?php esc_html_e( '1-Click Actions:', 'seo-city-pages' ); ?>
		</span>

		<button type="button" id="scp-btn-approve-all" class="button button-secondary" style="font-weight: 600; color: #007017; border-color: #00a32a;" title="<?php esc_attr_e( 'Approve all pages with content waiting in Needs Review', 'seo-city-pages' ); ?>" <?php echo empty( $queue_counts['needs_review'] ) ? 'disabled' : ''; ?>>
			<?php printf( esc_html__( '✓ Approve All Pending (%d)', 'seo-city-pages' ), (int) ( $queue_counts['needs_review'] ?? 0 ) ); ?>
		</button>

		<button type="button" id="scp-btn-publish-all" class="button button-primary" style="font-weight: 600; background: #00a32a; border-color: #00a32a;" title="<?php esc_attr_e( 'Publish all approved pages to live URLs and WordPress Pages', 'seo-city-pages' ); ?>" <?php echo empty( $queue_counts['approved'] ) ? 'disabled' : ''; ?>>
			<?php printf( esc_html__( '🚀 Publish All Approved (%d)', 'seo-city-pages' ), (int) ( $queue_counts['approved'] ?? 0 ) ); ?>
		</button>

		<button type="button" id="scp-btn-approve-publish-all" class="button button-primary" style="font-weight: 700; background: #02426A; border-color: #02426A;" title="<?php esc_attr_e( 'Approve all pending content and immediately publish all pages live', 'seo-city-pages' ); ?>">
			<?php esc_html_e( '⚡ 1-Click Approve & Publish All', 'seo-city-pages' ); ?>
		</button>
	</div>

	<!-- Bulk Actions & Filter Bar -->
	<form id="scp-review-list-form" method="get">
		<input type="hidden" name="page" value="seo-city-pages-ai-review">
		<?php if ( ! empty( $filter_status ) ) : ?>
			<input type="hidden" name="filter_gen_status" value="<?php echo esc_attr( $filter_status ); ?>">
		<?php endif; ?>

		<div class="tablenav top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
			<div class="alignleft actions bulkactions" style="display: flex; gap: 8px; align-items: center;">
				<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'seo-city-pages' ); ?></label>
				<select name="action" id="scp-bulk-action-select">
					<option value=""><?php esc_html_e( 'Bulk Actions', 'seo-city-pages' ); ?></option>
					<optgroup label="<?php esc_attr_e( 'Selected Pages Only', 'seo-city-pages' ); ?>">
						<option value="approve"><?php esc_html_e( '✓ Approve Selected', 'seo-city-pages' ); ?></option>
						<option value="reject"><?php esc_html_e( '✕ Reject Selected', 'seo-city-pages' ); ?></option>
						<option value="publish"><?php esc_html_e( '🚀 Publish Live Selected', 'seo-city-pages' ); ?></option>
						<option value="unpublish"><?php esc_html_e( '↩ Unpublish Selected (Revert to Draft)', 'seo-city-pages' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Entire Site (All Pages)', 'seo-city-pages' ); ?>">
						<option value="approve_all"><?php esc_html_e( '✓ Approve All Pending (Entire Site)', 'seo-city-pages' ); ?></option>
						<option value="publish_all"><?php esc_html_e( '🚀 Publish All Approved (Entire Site)', 'seo-city-pages' ); ?></option>
						<option value="approve_and_publish_all"><?php esc_html_e( '⚡ Approve & Publish All (Entire Site)', 'seo-city-pages' ); ?></option>
					</optgroup>
				</select>
				<button type="button" id="scp-apply-bulk-btn" class="button action"><?php esc_html_e( 'Apply', 'seo-city-pages' ); ?></button>
			</div>

			<div class="alignright actions" style="display: flex; gap: 10px; align-items: center;">
				<span class="displaying-num"><?php printf( esc_html__( '%s pages', 'seo-city-pages' ), number_format_i18n( $total_pages ) ); ?></span>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column" style="width: 38px;">
						<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'seo-city-pages' ); ?></label>
						<input id="cb-select-all-1" type="checkbox" class="scp-select-all-checkbox">
					</td>
					<th scope="col" style="width: 240px;"><?php esc_html_e( 'Page / Target URL', 'seo-city-pages' ); ?></th>
					<th scope="col" style="width: 140px;"><?php esc_html_e( 'Location', 'seo-city-pages' ); ?></th>
					<th scope="col" style="width: 140px;"><?php esc_html_e( 'Service', 'seo-city-pages' ); ?></th>
					<th scope="col" style="width: 130px;"><?php esc_html_e( 'AI Review Status', 'seo-city-pages' ); ?></th>
					<th scope="col" style="width: 120px;"><?php esc_html_e( 'Live Public State', 'seo-city-pages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Workflow Actions', 'seo-city-pages' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $pages ) ) : ?>
					<tr>
						<td colspan="7" style="text-align: center; padding: 24px;"><?php esc_html_e( 'No city landing pages match the active filter criteria.', 'seo-city-pages' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $pages as $p ) : ?>
						<?php
						$gen_status = $p->generation_status ?: 'not_generated';
						$badge_color = '#646970';
						if ( 'needs_review' === $gen_status ) {
							$badge_color = '#dba617';
						} elseif ( 'approved' === $gen_status ) {
							$badge_color = '#00a32a';
						} elseif ( 'manually_edited' === $gen_status ) {
							$badge_color = '#02426A';
						} elseif ( 'rejected' === $gen_status ) {
							$badge_color = '#d63638';
						} elseif ( 'failed' === $gen_status ) {
							$badge_color = '#d63638';
						}

						$is_published = ! empty( $p->is_published );
						$version_count = Content_Version_Repository::get_version_count( (int) $p->id );
						?>
						<tr id="scp-row-page-<?php echo esc_attr( $p->id ); ?>">
							<th scope="row" class="check-column">
								<input type="checkbox" name="page_ids[]" value="<?php echo esc_attr( $p->id ); ?>" class="scp-page-checkbox">
							</th>
							<td>
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=view&page_id=' . $p->id ) ); ?>">
										<?php echo esc_html( City_Page_Repository::get_derived_title( $p ) ); ?>
									</a>
								</strong>
								<div class="row-actions" style="margin-top: 4px;">
									<code><?php echo esc_html( $p->url_path ); ?></code>
									<?php
									$public_url = \SeoCityPages\URL\URL_Engine::build_full_url( $p->url_path );
									$preview_url = \SeoCityPages\URL\URL_Engine::build_preview_url( (int) $p->id );
									?>
									<a href="<?php echo esc_url( $is_published ? $public_url : $preview_url ); ?>" target="_blank" style="text-decoration: none; margin-left: 6px; font-weight: 600;" title="<?php echo esc_attr( $is_published ? 'Visit Live Page' : 'Preview Draft Page' ); ?>">
										<?php echo $is_published ? '👁️ View Live ↗' : '👁️ Preview ↗'; ?>
									</a>
									<?php if ( $version_count > 0 ) : ?>
										&bull; <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=history&page_id=' . $p->id ) ); ?>"><?php printf( esc_html__( '%d versions', 'seo-city-pages' ), $version_count ); ?></a>
									<?php endif; ?>
								</div>
							</td>
							<td><?php echo esc_html( $p->city_name . ', ' . $p->state_name ); ?></td>
							<td><?php echo esc_html( $p->service_name ); ?></td>
							<td>
								<span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #fff; background: <?php echo esc_attr( $badge_color ); ?>;">
									<?php echo esc_html( str_replace( '_', ' ', $gen_status ) ); ?>
								</span>
							</td>
							<td>
								<?php if ( $is_published ) : ?>
									<span style="display: inline-flex; align-items: center; gap: 4px; color: #00a32a; font-weight: 700; font-size: 12px;">
										<span style="width: 8px; height: 8px; border-radius: 50%; background: #00a32a;"></span>
										<?php esc_html_e( 'PUBLISHED', 'seo-city-pages' ); ?>
									</span>
								<?php else : ?>
									<span style="color: #646970; font-size: 12px; font-weight: 600;">
										<?php esc_html_e( 'Draft (Unpublished)', 'seo-city-pages' ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td>
								<div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
									<?php if ( 'not_generated' === $gen_status || 'failed' === $gen_status ) : ?>
										<button type="button" class="button button-small scp-generate-single-btn" data-page-id="<?php echo esc_attr( $p->id ); ?>">
											<?php esc_html_e( '⚡ Generate', 'seo-city-pages' ); ?>
										</button>
									<?php else : ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=view&page_id=' . $p->id ) ); ?>" class="button button-small button-primary">
											<?php esc_html_e( '🔍 Review', 'seo-city-pages' ); ?>
										</a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=edit&page_id=' . $p->id ) ); ?>" class="button button-small">
											<?php esc_html_e( '✏️ Edit', 'seo-city-pages' ); ?>
										</a>

										<?php if ( 'approved' === $gen_status && ! $is_published ) : ?>
											<button type="button" class="button button-small scp-publish-btn" data-page-id="<?php echo esc_attr( $p->id ); ?>" style="background: #00a32a; border-color: #00a32a; color: #fff;">
												<?php esc_html_e( '🚀 Publish Live', 'seo-city-pages' ); ?>
											</button>
										<?php elseif ( $is_published ) : ?>
											<button type="button" class="button button-small scp-unpublish-btn" data-page-id="<?php echo esc_attr( $p->id ); ?>" style="color: #646970;">
												<?php esc_html_e( '↩ Unpublish', 'seo-city-pages' ); ?>
											</button>
										<?php endif; ?>

										<button type="button" class="button button-small scp-generate-single-btn" data-page-id="<?php echo esc_attr( $p->id ); ?>" title="<?php esc_attr_e( 'Regenerate creates a new draft version without modifying published content.', 'seo-city-pages' ); ?>">
											<?php esc_html_e( '🔄 Regenerate', 'seo-city-pages' ); ?>
										</button>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</form>

	<!-- Bulk Action Confirmation Modal -->
	<div id="scp-bulk-confirm-modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
		<div style="background: #fff; border-radius: 8px; width: 480px; max-width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden;">
			<div style="padding: 16px 20px; background: #02426A; color: #fff; font-weight: 700; font-size: 16px;">
				<span id="scp-modal-title"><?php esc_html_e( 'Confirm Bulk Workflow Action', 'seo-city-pages' ); ?></span>
			</div>
			<div style="padding: 20px;">
				<p id="scp-modal-message" style="font-size: 14px; margin-top: 0; color: #1e1e1e;"></p>
				
				<div id="scp-modal-reject-reason-wrap" style="display: none; margin-top: 14px;">
					<label for="scp-modal-reject-reason" style="display: block; font-weight: 600; margin-bottom: 6px;">
						<?php esc_html_e( 'Rejection Feedback / Notes (optional):', 'seo-city-pages' ); ?>
					</label>
					<textarea id="scp-modal-reject-reason" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'e.g. Needs more local case studies or revised CTA...', 'seo-city-pages' ); ?>"></textarea>
				</div>
			</div>
			<div style="padding: 12px 20px; background: #f6f7f7; border-top: 1px solid #dcdcde; display: flex; justify-content: flex-end; gap: 10px;">
				<button type="button" id="scp-modal-cancel-btn" class="button"><?php esc_html_e( 'Cancel', 'seo-city-pages' ); ?></button>
				<button type="button" id="scp-modal-proceed-btn" class="button button-primary" style="background: #02426A; border-color: #02426A;"><?php esc_html_e( 'Proceed', 'seo-city-pages' ); ?></button>
			</div>
		</div>
	</div>
</div>
