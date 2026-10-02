<?php
/**
 * Admin Dashboard View Template.
 *
 * @package SeoCityPages
 * @var array    $diagnostics
 * @var string   $db_version
 * @var int      $total_countries
 * @var int      $total_states
 * @var int      $total_cities
 * @var int      $total_services
 * @var int      $total_pages
 * @var object[] $recent_jobs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<div class="scp-header" style="background: #fff; padding: 20px 24px; border-radius: 8px; border: 1px solid #c3c4c7; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
		<div>
			<h1 class="wp-heading-inline" style="margin: 0; font-size: 24px; font-weight: 600;">
				<span class="dashicons dashicons-location-alt" style="font-size: 28px; width: 28px; height: 28px; margin-right: 6px; color: #2271b1;"></span>
				<?php esc_html_e( 'SEO City Pages', 'seo-city-pages' ); ?>
			</h1>
			<p style="margin: 6px 0 0 0; color: #646970;">
				<?php esc_html_e( 'High-volume programmatic SEO landing page generation for WordPress & Elementor.', 'seo-city-pages' ); ?>
			</p>
		</div>
		<div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import' ) ); ?>" class="button button-primary button-hero" style="display: flex; align-items: center; gap: 6px;">
				<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import CSV Data', 'seo-city-pages' ); ?>
			</a>
		</div>
	</div>

	<!-- 5 Pillar Summary Metric Cards -->
	<div class="seo-city-pages-stats-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px;">
		<div class="seo-city-pages-card" style="text-align: center; padding: 18px 12px; background: #fff; border: 1px solid #c3c4c7; border-radius: 6px;">
			<h3 style="margin: 0; font-size: 13px; color: #646970; text-transform: uppercase;"><?php esc_html_e( 'City Pages', 'seo-city-pages' ); ?></h3>
			<p style="font-size: 32px; font-weight: 700; margin: 8px 0; color: #2271b1;"><?php echo esc_html( $total_pages ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import' ) ); ?>" class="button button-small"><?php esc_html_e( 'Import More →', 'seo-city-pages' ); ?></a>
		</div>

		<div class="seo-city-pages-card" style="text-align: center; padding: 18px 12px; background: #fff; border: 1px solid #c3c4c7; border-radius: 6px;">
			<h3 style="margin: 0; font-size: 13px; color: #646970; text-transform: uppercase;"><?php esc_html_e( 'Countries', 'seo-city-pages' ); ?></h3>
			<p style="font-size: 32px; font-weight: 700; margin: 8px 0; color: #1d2327;"><?php echo esc_html( $total_countries ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-countries' ) ); ?>" class="button button-small"><?php esc_html_e( 'Manage Countries →', 'seo-city-pages' ); ?></a>
		</div>

		<div class="seo-city-pages-card" style="text-align: center; padding: 18px 12px; background: #fff; border: 1px solid #c3c4c7; border-radius: 6px;">
			<h3 style="margin: 0; font-size: 13px; color: #646970; text-transform: uppercase;"><?php esc_html_e( 'States / Regions', 'seo-city-pages' ); ?></h3>
			<p style="font-size: 32px; font-weight: 700; margin: 8px 0; color: #1d2327;"><?php echo esc_html( $total_states ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-states' ) ); ?>" class="button button-small"><?php esc_html_e( 'Manage States →', 'seo-city-pages' ); ?></a>
		</div>

		<div class="seo-city-pages-card" style="text-align: center; padding: 18px 12px; background: #fff; border: 1px solid #c3c4c7; border-radius: 6px;">
			<h3 style="margin: 0; font-size: 13px; color: #646970; text-transform: uppercase;"><?php esc_html_e( 'Cities', 'seo-city-pages' ); ?></h3>
			<p style="font-size: 32px; font-weight: 700; margin: 8px 0; color: #1d2327;"><?php echo esc_html( $total_cities ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-cities' ) ); ?>" class="button button-small"><?php esc_html_e( 'Manage Cities →', 'seo-city-pages' ); ?></a>
		</div>

		<div class="seo-city-pages-card" style="text-align: center; padding: 18px 12px; background: #fff; border: 1px solid #c3c4c7; border-radius: 6px;">
			<h3 style="margin: 0; font-size: 13px; color: #646970; text-transform: uppercase;"><?php esc_html_e( 'Services', 'seo-city-pages' ); ?></h3>
			<p style="font-size: 32px; font-weight: 700; margin: 8px 0; color: #1d2327;"><?php echo esc_html( $total_services ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-services' ) ); ?>" class="button button-small"><?php esc_html_e( 'Manage Services →', 'seo-city-pages' ); ?></a>
		</div>
	</div>

	<!-- Main Grid: Recommended Workflow & Recent Jobs -->
	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
		<!-- Workflow & Actions -->
		<div class="seo-city-pages-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 20px;">
			<h2 style="margin-top: 0;"><?php esc_html_e( 'Recommended Workflow', 'seo-city-pages' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Follow these four simple steps to populate and generate thousands of SEO landing pages:', 'seo-city-pages' ); ?>
			</p>

			<ol style="margin-top: 15px; line-height: 1.8;">
				<li>
					<strong><?php esc_html_e( 'Upload CSV:', 'seo-city-pages' ); ?></strong>
					<?php esc_html_e( 'Upload your spreadsheet with Country, State, City, and Service columns.', 'seo-city-pages' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Automatic Location Ingestion:', 'seo-city-pages' ); ?></strong>
					<?php esc_html_e( 'The engine automatically creates and validates hierarchical location and service records.', 'seo-city-pages' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Manage & Refine:', 'seo-city-pages' ); ?></strong>
					<?php esc_html_e( 'Review, search, and edit individual cities, coordinates, or service blueprints via the management screens.', 'seo-city-pages' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Page Generation (Next Phase):', 'seo-city-pages' ); ?></strong>
					<?php esc_html_e( 'Apply a single reusable Elementor template dynamically across all logical combinations.', 'seo-city-pages' ); ?>
				</li>
			</ol>

			<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import' ) ); ?>" class="button button-primary">
					<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Go to CSV Importer', 'seo-city-pages' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import&action=download_sample' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download Sample CSV', 'seo-city-pages' ); ?>
				</a>
			</div>
		</div>

		<!-- System Status Quick Summary -->
		<div class="seo-city-pages-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 20px;">
			<h2 style="margin-top: 0;"><?php esc_html_e( 'System Status', 'seo-city-pages' ); ?></h2>
			<table class="widefat striped" style="margin-top: 10px;">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Plugin Version', 'seo-city-pages' ); ?></strong></td>
						<td><span class="badge badge-info"><?php echo esc_html( SEO_CITY_PAGES_VERSION ); ?></span></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'DB Schema', 'seo-city-pages' ); ?></strong></td>
						<td><span class="badge badge-info">v<?php echo esc_html( $db_version ); ?></span></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Version', 'seo-city-pages' ); ?></strong></td>
						<td><?php echo esc_html( $diagnostics['php']['version'] ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WordPress', 'seo-city-pages' ); ?></strong></td>
						<td><?php echo esc_html( $diagnostics['wordpress']['version'] ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Elementor', 'seo-city-pages' ); ?></strong></td>
						<td>
							<?php if ( $diagnostics['elementor']['active'] ) : ?>
								<span class="badge badge-pass"><?php esc_html_e( 'Active', 'seo-city-pages' ); ?></span>
							<?php else : ?>
								<span class="badge badge-fail"><?php esc_html_e( 'Inactive', 'seo-city-pages' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
			<p style="margin-top: 15px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-status' ) ); ?>" class="button button-small">
					<?php esc_html_e( 'View Full Diagnostics →', 'seo-city-pages' ); ?>
				</a>
			</p>
		</div>
	</div>

	<!-- Recent Import Jobs -->
	<?php if ( ! empty( $recent_jobs ) ) : ?>
		<div class="seo-city-pages-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 20px; margin-top: 25px;">
			<h2 style="margin-top: 0;"><?php esc_html_e( 'Recent Import History', 'seo-city-pages' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'File Name', 'seo-city-pages' ); ?></th>
						<th><?php esc_html_e( 'Total Rows', 'seo-city-pages' ); ?></th>
						<th><?php esc_html_e( 'Processed', 'seo-city-pages' ); ?></th>
						<th><?php esc_html_e( 'Combinations Created', 'seo-city-pages' ); ?></th>
						<th><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></th>
						<th><?php esc_html_e( 'Date', 'seo-city-pages' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_jobs as $job ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $job->file_name ); ?></strong></td>
							<td><?php echo esc_html( $job->total_rows ); ?></td>
							<td><?php echo esc_html( $job->processed_rows ); ?></td>
							<td>+<?php echo esc_html( $job->created_combinations ); ?></td>
							<td><span class="badge badge-info"><?php echo esc_html( ucfirst( $job->status ) ); ?></span></td>
							<td><?php echo esc_html( $job->created_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
