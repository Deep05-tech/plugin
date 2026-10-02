<?php
/**
 * Content Version & Audit History View.
 *
 * @package SeoCityPages\Admin
 */

use SeoCityPages\Database\City_Page_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline">
		<?php printf( esc_html__( 'Version History & Audit Log: %s', 'seo-city-pages' ), esc_html( City_Page_Repository::get_derived_title( $page ) ) ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=view&page_id=' . $page->id ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← Back to Review', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<!-- Version Timeline Table -->
	<div class="postbox" style="margin-top: 20px; padding: 20px;">
		<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( 'Content Version History', 'seo-city-pages' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 90px;"><?php esc_html_e( 'Version', 'seo-city-pages' ); ?></th>
					<th style="width: 100px;"><?php esc_html_e( 'Source', 'seo-city-pages' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Model / Engine', 'seo-city-pages' ); ?></th>
					<th style="width: 100px;"><?php esc_html_e( 'Tokens', 'seo-city-pages' ); ?></th>
					<th style="width: 180px;"><?php esc_html_e( 'Created At', 'seo-city-pages' ); ?></th>
					<th><?php esc_html_e( 'Notes / Rejection Reason', 'seo-city-pages' ); ?></th>
					<th style="width: 160px;"><?php esc_html_e( 'Actions', 'seo-city-pages' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $versions ) ) : ?>
					<tr><td colspan="8"><?php esc_html_e( 'No versions found.', 'seo-city-pages' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $versions as $v ) : ?>
						<?php
						$is_live = (int) $v->id === (int) ( $page->published_version_id ?? 0 );
						?>
						<tr>
							<td><strong>v<?php echo (int) $v->version_num; ?></strong></td>
							<td><code><?php echo esc_html( $v->source ?? 'ai' ); ?></code></td>
							<td>
								<span style="font-weight: 700; text-transform: uppercase; color: <?php echo 'approved' === $v->status ? '#00a32a' : ( 'rejected' === $v->status ? '#d63638' : '#dba617' ); ?>;">
									<?php echo esc_html( str_replace( '_', ' ', $v->status ) ); ?>
								</span>
								<?php if ( $is_live ) : ?>
									<span style="background: #00a32a; color: #fff; font-size: 10px; padding: 2px 5px; border-radius: 3px; font-weight: 700; margin-left: 4px;">LIVE</span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $v->model ); ?></td>
							<td><?php echo (int) $v->total_tokens; ?></td>
							<td><?php echo esc_html( $v->created_at ); ?></td>
							<td>
								<?php if ( ! empty( $v->rejection_reason ) ) : ?>
									<span style="color: #d63638;"><strong><?php esc_html_e( 'Rejected:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $v->rejection_reason ); ?></span><br>
								<?php endif; ?>
								<?php if ( ! empty( $v->review_notes ) ) : ?>
									<span style="color: #50575e;"><?php echo esc_html( $v->review_notes ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=view&page_id=' . $page->id . '&version_id=' . $v->id ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Inspect', 'seo-city-pages' ); ?>
								</a>
								<?php if ( ! $is_live && $v->id !== $versions[0]->id ) : ?>
									<button type="button" class="button button-small scp-restore-btn" data-version-id="<?php echo esc_attr( $v->id ); ?>">
										<?php esc_html_e( 'Restore', 'seo-city-pages' ); ?>
									</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Audit Trail Log Table -->
	<div class="postbox" style="margin-top: 20px; padding: 20px;">
		<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( 'Lifecycle Audit Trail', 'seo-city-pages' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 160px;"><?php esc_html_e( 'Date & Time', 'seo-city-pages' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Event Type', 'seo-city-pages' ); ?></th>
					<th style="width: 140px;"><?php esc_html_e( 'Actor / User', 'seo-city-pages' ); ?></th>
					<th style="width: 120px;"><?php esc_html_e( 'IP Address', 'seo-city-pages' ); ?></th>
					<th><?php esc_html_e( 'Action Details / Context', 'seo-city-pages' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $audit_logs ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No audit entries recorded.', 'seo-city-pages' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $audit_logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( $log->created_at ); ?></td>
							<td><code><?php echo esc_html( $log->event_type ); ?></code></td>
							<td><?php echo esc_html( $log->display_name ?: ( $log->user_login ?: 'System/CLI' ) ); ?></td>
							<td><code><?php echo esc_html( $log->user_ip ); ?></code></td>
							<td>
								<pre style="margin: 0; font-size: 11px; white-space: pre-wrap; background: #f6f7f7; padding: 6px 10px; border-radius: 4px;"><?php echo esc_html( $log->action_details ); ?></pre>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
