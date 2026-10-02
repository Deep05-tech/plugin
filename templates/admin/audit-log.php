<?php
/**
 * Append-Only Audit Log Viewer.
 *
 * @package SeoCityPages\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap" style="max-width: 1300px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">
	<div style="background: #ffffff; border-radius: 12px; padding: 24px 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 24px;">
		<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; padding-bottom: 20px;">
			<div>
				<h1 style="font-size: 26px; font-weight: 700; color: #1a202c; margin: 0 0 6px 0;">📜 Immutable System Audit Log</h1>
				<p style="color: #718096; margin: 0; font-size: 14px;">Chronological, append-only record of content state changes, approvals, publishing, rollbacks, and security events.</p>
			</div>
			<div>
				<span style="background: #f7fafc; border: 1px solid #e2e8f0; color: #4a5568; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;">
					Total Records: <?php echo number_format( $total_logs ); ?>
				</span>
			</div>
		</div>

		<!-- Filter Bar -->
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin-top: 20px; display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 12px; align-items: end;">
			<input type="hidden" name="page" value="seo-city-pages-audit" />

			<div>
				<label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Event Type</label>
				<input type="text" name="event_type" value="<?php echo esc_attr( $_GET['event_type'] ?? '' ); ?>" placeholder="e.g. publish, approve" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e0;" />
			</div>

			<div>
				<label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Entity Type</label>
				<input type="text" name="entity_type" value="<?php echo esc_attr( $_GET['entity_type'] ?? '' ); ?>" placeholder="e.g. city_page, queue" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e0;" />
			</div>

			<div>
				<label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Search Keywords</label>
				<input type="text" name="search" value="<?php echo esc_attr( $_GET['search'] ?? '' ); ?>" placeholder="Search details..." style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e0;" />
			</div>

			<div>
				<label style="display: block; font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 4px;">Date From</label>
				<input type="date" name="date_from" value="<?php echo esc_attr( $_GET['date_from'] ?? '' ); ?>" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e0;" />
			</div>

			<div style="display: flex; gap: 8px;">
				<button type="submit" class="button button-primary">Filter Logs</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-audit' ) ); ?>" class="button">Reset</a>
			</div>
		</form>
	</div>

	<!-- Audit Log Table -->
	<div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 70px; font-weight: 700;">ID</th>
					<th style="width: 150px; font-weight: 700;">Event</th>
					<th style="width: 110px; font-weight: 700;">Entity</th>
					<th style="width: 80px; font-weight: 700;">Entity ID</th>
					<th style="width: 100px; font-weight: 700;">User / IP</th>
					<th style="font-weight: 700;">Structured Details</th>
					<th style="width: 160px; font-weight: 700;">Timestamp</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $logs ) ) : ?>
					<tr>
						<td colspan="7" style="text-align: center; color: #a0aec0; padding: 24px;">No audit events recorded for current filters.</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $logs as $entry ) : ?>
						<tr>
							<td><code>#<?php echo (int) $entry->id; ?></code></td>
							<td>
								<span style="font-weight: 700; color: <?php echo strpos( $entry->event_type, 'security' ) !== false ? '#e53e3e' : '#2b6cb0'; ?>;">
									<?php echo esc_html( $entry->event_type ); ?>
								</span>
							</td>
							<td><code><?php echo esc_html( $entry->entity_type ); ?></code></td>
							<td><?php echo (int) $entry->entity_id ?: '-'; ?></td>
							<td>
								<div>User #<?php echo (int) $entry->user_id; ?></div>
								<small style="color: #718096;"><?php echo esc_html( $entry->user_ip ?: '127.0.0.1' ); ?></small>
							</td>
							<td>
								<code style="font-size: 11px; max-height: 80px; overflow-y: auto; display: block; background: #f7fafc; padding: 6px; border-radius: 4px;">
									<?php echo esc_html( wp_json_encode( $entry->details, JSON_UNESCAPED_SLASHES ) ); ?>
								</code>
							</td>
							<td><?php echo esc_html( $entry->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
			<div style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #edf2f7;">
				<div style="color: #718096; font-size: 13px;">
					Showing page <?php echo (int) $page; ?> of <?php echo (int) $total_pages; ?>
				</div>
				<div style="display: flex; gap: 8px;">
					<?php if ( $page > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $page - 1 ) ); ?>" class="button">&larr; Previous</a>
					<?php endif; ?>
					<?php if ( $page < $total_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $page + 1 ) ); ?>" class="button">Next &rarr;</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
