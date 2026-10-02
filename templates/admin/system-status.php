<?php
/**
 * System Status Admin Page View Template.
 *
 * @package SeoCityPages
 * @var array       $diagnostics
 * @var string      $db_version
 * @var string[]    $logs
 * @var string|null $log_file
 * @var string      $message
 * @var string      $notice_type
 */

use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-tools" style="font-size: 28px; width: 28px; height: 28px; margin-right: 8px;"></span>
		<?php esc_html_e( 'SEO City Pages — System Status', 'seo-city-pages' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="seo-city-pages-card" style="margin-top: 20px;">
		<h2><?php esc_html_e( 'Environment & Dependency Diagnostics', 'seo-city-pages' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Detailed operational health checks and dependency status for the plugin ecosystem.', 'seo-city-pages' ); ?>
		</p>

		<table class="widefat striped" style="margin-top: 15px;">
			<thead>
				<tr>
					<th style="width: 20%;"><?php esc_html_e( 'Component', 'seo-city-pages' ); ?></th>
					<th style="width: 25%;"><?php esc_html_e( 'Detected Value', 'seo-city-pages' ); ?></th>
					<th style="width: 15%;"><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></th>
					<th style="width: 40%;"><?php esc_html_e( 'Explanation / Recommendation', 'seo-city-pages' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<!-- WordPress Core -->
				<tr>
					<td><strong>WordPress</strong></td>
					<td>v<?php echo esc_html( $diagnostics['wordpress']['version'] ); ?></td>
					<td>
						<?php if ( $diagnostics['wordpress']['compatible'] ) : ?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-fail"><?php esc_html_e( 'FAIL', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['wordpress']['compatible'] ) : ?>
							<?php esc_html_e( 'WordPress meets minimum requirement (>= 6.0).', 'seo-city-pages' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'WordPress upgrade required for full REST API and Block feature compatibility.', 'seo-city-pages' ); ?>
						<?php endif; ?>
					</td>
				</tr>

				<!-- PHP Runtime -->
				<tr>
					<td><strong>PHP Runtime</strong></td>
					<td>v<?php echo esc_html( $diagnostics['php']['version'] ); ?></td>
					<td>
						<?php if ( $diagnostics['php']['compatible'] ) : ?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-fail"><?php esc_html_e( 'FAIL', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['php']['compatible'] ) : ?>
							<?php esc_html_e( 'PHP runtime meets requirement (>= 7.4).', 'seo-city-pages' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Please upgrade PHP to 7.4 or higher.', 'seo-city-pages' ); ?>
						<?php endif; ?>
					</td>
				</tr>

				<!-- PHP Memory Limit -->
				<tr>
					<td><strong>PHP Memory Limit</strong></td>
					<td><?php echo esc_html( $diagnostics['php']['memory_limit'] ); ?></td>
					<td>
						<?php
						$mem_bytes = wp_convert_hr_to_bytes( $diagnostics['php']['memory_limit'] );
						if ( $mem_bytes >= 134217728 ) : // 128M
							?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-warning"><?php esc_html_e( 'WARNING', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $mem_bytes >= 134217728 ) : ?>
							<?php esc_html_e( 'Memory limit is sufficient for batch operations.', 'seo-city-pages' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Recommend at least 128M (256M preferred) for large batch generations.', 'seo-city-pages' ); ?>
						<?php endif; ?>
					</td>
				</tr>

				<!-- Database -->
				<tr>
					<td><strong>MySQL / MariaDB</strong></td>
					<td>v<?php echo esc_html( $diagnostics['database']['version'] ); ?> (Prefix: <code><?php echo esc_html( $diagnostics['database']['prefix'] ); ?></code>)</td>
					<td><span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span></td>
					<td><?php esc_html_e( 'Database connection verified and operational.', 'seo-city-pages' ); ?></td>
				</tr>

				<!-- Elementor -->
				<tr>
					<td><strong>Elementor Core</strong></td>
					<td>
						<?php if ( $diagnostics['elementor']['active'] ) : ?>
							v<?php echo esc_html( $diagnostics['elementor']['version'] ); ?>
						<?php else : ?>
							<em><?php esc_html_e( 'Not Active', 'seo-city-pages' ); ?></em>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['elementor']['active'] ) : ?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-warning"><?php esc_html_e( 'WARNING', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['elementor']['active'] ) : ?>
							<?php esc_html_e( 'Elementor is active and available for master template rendering.', 'seo-city-pages' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Elementor is not active. Elementor-based page rendering will not be available.', 'seo-city-pages' ); ?>
						<?php endif; ?>
					</td>
				</tr>

				<!-- Elementor Pro -->
				<tr>
					<td><strong>Elementor Pro</strong></td>
					<td>
						<?php if ( $diagnostics['elementor_pro']['active'] ) : ?>
							v<?php echo esc_html( $diagnostics['elementor_pro']['version'] ); ?>
						<?php else : ?>
							<em><?php esc_html_e( 'Not Detected', 'seo-city-pages' ); ?></em>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['elementor_pro']['active'] ) : ?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-info"><?php esc_html_e( 'OPTIONAL', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['elementor_pro']['active'] ) : ?>
							<?php esc_html_e( 'Elementor Pro Theme Builder templates are supported.', 'seo-city-pages' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Standard Elementor templates will be used for master designs.', 'seo-city-pages' ); ?>
						<?php endif; ?>
					</td>
				</tr>

				<!-- Theme / Arolax -->
				<tr>
					<td><strong>Active Theme</strong></td>
					<td>
						<?php echo esc_html( $diagnostics['theme']['name'] ); ?> (v<?php echo esc_html( $diagnostics['theme']['version'] ); ?>)
						<?php if ( ! empty( $diagnostics['theme']['is_child'] ) && ! empty( $diagnostics['theme']['parent_name'] ) ) : ?>
							<br><small class="description"><?php printf( esc_html__( 'Child of: %s', 'seo-city-pages' ), esc_html( $diagnostics['theme']['parent_name'] ) ); ?></small>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['theme']['is_arolax'] ) : ?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-info"><?php esc_html_e( 'INFO', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['theme']['is_arolax'] ) : ?>
							<?php echo esc_html( $diagnostics['theme']['arolax_status']['message'] ?? __( 'Arolax theme detected. Standard theme header/footer wrappers are primed.', 'seo-city-pages' ) ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Arolax was not detected. The plugin operates universally in theme-isolated mode.', 'seo-city-pages' ); ?>
						<?php endif; ?>
					</td>
				</tr>

				<!-- SEO Plugin -->
				<tr>
					<td><strong>SEO Plugin</strong></td>
					<td>
						<?php if ( $diagnostics['seo_plugin']['active'] ) : ?>
							<?php echo esc_html( $diagnostics['seo_plugin']['name'] ); ?> (v<?php echo esc_html( $diagnostics['seo_plugin']['version'] ); ?>)
						<?php else : ?>
							<em><?php esc_html_e( 'None Detected', 'seo-city-pages' ); ?></em>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['seo_plugin']['active'] ) : ?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-info"><?php esc_html_e( 'INFO', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $diagnostics['seo_plugin']['active'] ) : ?>
							<?php esc_html_e( 'Automatic title, meta, canonical, and sitemap hooks will integrate seamlessly.', 'seo-city-pages' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Standard WordPress canonical and meta tags will be managed natively.', 'seo-city-pages' ); ?>
						<?php endif; ?>
					</td>
				</tr>

				<!-- WP-Cron -->
				<tr>
					<td><strong>WP-Cron</strong></td>
					<td><?php echo $diagnostics['wordpress']['cron_status'] ? esc_html__( 'Enabled', 'seo-city-pages' ) : esc_html__( 'Disabled via Constant', 'seo-city-pages' ); ?></td>
					<td>
						<?php if ( $diagnostics['wordpress']['cron_status'] ) : ?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-warning"><?php esc_html_e( 'WARNING', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php esc_html_e( 'Required for automated background tasks and scheduled batch syncs.', 'seo-city-pages' ); ?></td>
				</tr>

				<!-- Action Scheduler -->
				<tr>
					<td><strong>Action Scheduler</strong></td>
					<td><?php echo $diagnostics['action_scheduler']['available'] ? esc_html__( 'Available', 'seo-city-pages' ) : esc_html__( 'Will Use Bundled Fallback', 'seo-city-pages' ); ?></td>
					<td>
						<?php if ( $diagnostics['action_scheduler']['available'] ) : ?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-info"><?php esc_html_e( 'BUNDLED', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php esc_html_e( 'Used for large high-volume asynchronous generation queues.', 'seo-city-pages' ); ?></td>
				</tr>

				<!-- Uploads Write Status -->
				<tr>
					<td><strong>Uploads Directory</strong></td>
					<td><?php echo $diagnostics['filesystem']['uploads_writable'] ? esc_html__( 'Writable', 'seo-city-pages' ) : esc_html__( 'Not Writable', 'seo-city-pages' ); ?></td>
					<td>
						<?php if ( $diagnostics['filesystem']['uploads_writable'] ) : ?>
							<span class="badge badge-pass"><?php esc_html_e( 'PASS', 'seo-city-pages' ); ?></span>
						<?php else : ?>
							<span class="badge badge-fail"><?php esc_html_e( 'FAIL', 'seo-city-pages' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php esc_html_e( 'Required for CSV file imports, exports, and secure diagnostic logs.', 'seo-city-pages' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Diagnostic Logs Card -->
	<div class="seo-city-pages-card" style="margin-top: 25px;">
		<div style="display: flex; justify-content: space-between; align-items: center;">
			<h2><?php esc_html_e( 'Recent Plugin Diagnostic Logs', 'seo-city-pages' ); ?></h2>
			<form method="post" style="margin: 0;">
				<?php echo wp_nonce_field( Security::NONCE_ACTION . '_clear_logs', '_wpnonce', true, false ); ?>
				<input type="hidden" name="seo_city_pages_clear_logs" value="1">
				<button type="submit" class="button button-secondary" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear the diagnostic log file?', 'seo-city-pages' ) ); ?>');">
					<?php esc_html_e( 'Clear Logs', 'seo-city-pages' ); ?>
				</button>
			</form>
		</div>

		<p class="description">
			<?php
			if ( $log_file ) {
				printf(
					/* translators: %s: File path */
					esc_html__( 'Log location: %s (Sensitive data, keys, and passwords are automatically scrubbed).', 'seo-city-pages' ),
					'<code>' . esc_html( basename( dirname( $log_file ) ) . '/' . basename( $log_file ) ) . '</code>'
				);
			}
			?>
		</p>

		<div class="seo-city-pages-log-viewer" style="margin-top: 15px; max-height: 300px; overflow-y: auto; background: #1e1e1e; color: #f1f1f1; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; line-height: 1.5;">
			<?php if ( empty( $logs ) ) : ?>
				<span style="color: #888;"><?php esc_html_e( 'No log entries found. All systems operating normally.', 'seo-city-pages' ); ?></span>
			<?php else : ?>
				<?php foreach ( $logs as $log_line ) : ?>
					<div><?php echo esc_html( $log_line ); ?></div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
