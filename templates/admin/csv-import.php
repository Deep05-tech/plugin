<?php
/**
 * CSV Import Admin Template.
 *
 * @package SeoCityPages\Admin
 * @var array|null $preview_data
 * @var string     $error_notice
 * @var object[]   $recent_jobs
 * @var int        $total_pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sample_download_url = admin_url( 'admin.php?page=seo-city-pages-import&action=download_sample' );
$max_upload_size     = size_format( wp_max_upload_size() );
$batch_nonce         = \SeoCityPages\Security\Security::create_nonce( 'batch_import' );
$elementor_active    = \SeoCityPages\Environment::is_elementor_active();
$arolax_active       = \SeoCityPages\Environment::is_arolax_active();
$can_import          = \SeoCityPages\Security\Capabilities::user_can( \SeoCityPages\Security\Capabilities::MANAGE );
?>

<div class="wrap seo-city-pages-wrap">
	<div class="scp-header">
		<div class="scp-header-left">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'CSV Import', 'seo-city-pages' ); ?></h1>
			<p class="scp-subtitle"><?php esc_html_e( 'The primary, high-volume data-ingestion pipeline for Countries, States, Cities, Services, and Logical City Pages.', 'seo-city-pages' ); ?></p>
		</div>
		<div class="scp-header-right">
			<a href="<?php echo esc_url( $sample_download_url ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download Sample CSV', 'seo-city-pages' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages' ) ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'seo-city-pages' ); ?>
			</a>
		</div>
	</div>

	<!-- Environment & Capability Status Bar -->
	<div class="scp-status-banner" style="display: flex; flex-wrap: wrap; gap: 15px; margin: 15px 0 20px; padding: 12px 16px; background: #fff; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.05); align-items: center;">
		<div>
			<strong><?php esc_html_e( 'Plugin Core:', 'seo-city-pages' ); ?></strong>
			<span class="scp-badge scp-badge-active" style="margin-left: 4px;"><?php esc_html_e( 'Ready', 'seo-city-pages' ); ?></span>
		</div>
		<div>
			<strong><?php esc_html_e( 'Elementor:', 'seo-city-pages' ); ?></strong>
			<?php if ( $elementor_active ) : ?>
				<span class="scp-badge scp-badge-active" style="margin-left: 4px;"><?php esc_html_e( 'Active', 'seo-city-pages' ); ?></span>
			<?php else : ?>
				<span class="scp-badge scp-badge-warning" style="margin-left: 4px;"><?php esc_html_e( 'Not Detected (Optional for Data)', 'seo-city-pages' ); ?></span>
			<?php endif; ?>
		</div>
		<div>
			<strong><?php esc_html_e( 'Theme Mode:', 'seo-city-pages' ); ?></strong>
			<?php if ( $arolax_active ) : ?>
				<span class="scp-badge scp-badge-active" style="margin-left: 4px;"><?php esc_html_e( 'Arolax Theme Active', 'seo-city-pages' ); ?></span>
			<?php else : ?>
				<span class="scp-badge scp-badge-info" style="margin-left: 4px;"><?php esc_html_e( 'Theme-Isolated (Universal)', 'seo-city-pages' ); ?></span>
			<?php endif; ?>
		</div>
		<div>
			<strong><?php esc_html_e( 'Import Permission:', 'seo-city-pages' ); ?></strong>
			<?php if ( $can_import ) : ?>
				<span class="scp-badge scp-badge-active" style="margin-left: 4px;"><?php esc_html_e( 'Authorized', 'seo-city-pages' ); ?></span>
			<?php else : ?>
				<span class="scp-badge scp-badge-inactive" style="margin-left: 4px;"><?php esc_html_e( 'Unauthorized', 'seo-city-pages' ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! empty( $error_notice ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><strong><?php esc_html_e( 'Import Validation Error:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $error_notice ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Progress & Live Import State Container (Revealed during/after import) -->
	<div id="scp-import-live-container" class="scp-card" style="display: none;">
		<div class="scp-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
			<h2 id="scp-live-title" style="margin: 0;"><span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Processing Import in Batches...', 'seo-city-pages' ); ?></h2>
			<div id="scp-import-controls" style="display: flex; gap: 8px;">
				<button type="button" id="scp-pause-import-btn" class="button button-secondary button-small" style="font-weight: 600;">
					<span class="dashicons dashicons-controls-pause"></span> <?php esc_html_e( 'Pause', 'seo-city-pages' ); ?>
				</button>
				<button type="button" id="scp-resume-import-btn" class="button button-primary button-small" style="display: none; font-weight: 600;">
					<span class="dashicons dashicons-controls-play"></span> <?php esc_html_e( 'Resume', 'seo-city-pages' ); ?>
				</button>
				<button type="button" id="scp-stop-import-btn" class="button button-secondary button-small" style="color: #d63638; border-color: #d63638; font-weight: 600;">
					<span class="dashicons dashicons-no-alt"></span> <?php esc_html_e( 'Stop Import', 'seo-city-pages' ); ?>
				</button>
			</div>
		</div>
		<div class="scp-card-body">
			<div class="scp-progress-wrapper">
				<div class="scp-progress-bar-bg">
					<div id="scp-progress-bar-fill" class="scp-progress-bar-fill" style="width: 0%;"></div>
				</div>
				<div class="scp-progress-label">
					<span id="scp-progress-percent">0%</span> <?php esc_html_e( 'Completed', 'seo-city-pages' ); ?> 
					(<span id="scp-progress-processed">0</span> / <span id="scp-progress-total">0</span> <?php esc_html_e( 'rows', 'seo-city-pages' ); ?>)
				</div>
			</div>

			<!-- Live Statistics Grid -->
			<div class="scp-metrics-grid" style="margin-top: 20px;">
				<div class="scp-metric-card">
					<span class="scp-metric-value" id="stat-created-combinations">0</span>
					<span class="scp-metric-label"><?php esc_html_e( 'New City Pages', 'seo-city-pages' ); ?></span>
				</div>
				<div class="scp-metric-card">
					<span class="scp-metric-value" id="stat-existing-combinations">0</span>
					<span class="scp-metric-label"><?php esc_html_e( 'Existing Pages', 'seo-city-pages' ); ?></span>
				</div>
				<div class="scp-metric-card">
					<span class="scp-metric-value" id="stat-created-countries">0</span>
					<span class="scp-metric-label"><?php esc_html_e( 'Created Countries', 'seo-city-pages' ); ?></span>
				</div>
				<div class="scp-metric-card">
					<span class="scp-metric-value" id="stat-created-states">0</span>
					<span class="scp-metric-label"><?php esc_html_e( 'Created States', 'seo-city-pages' ); ?></span>
				</div>
				<div class="scp-metric-card">
					<span class="scp-metric-value" id="stat-created-cities">0</span>
					<span class="scp-metric-label"><?php esc_html_e( 'Created Cities', 'seo-city-pages' ); ?></span>
				</div>
				<div class="scp-metric-card">
					<span class="scp-metric-value" id="stat-created-services">0</span>
					<span class="scp-metric-label"><?php esc_html_e( 'Created Services', 'seo-city-pages' ); ?></span>
				</div>
				<div class="scp-metric-card" id="card-failed-rows">
					<span class="scp-metric-value" id="stat-failed-rows">0</span>
					<span class="scp-metric-label"><?php esc_html_e( 'Failed Rows', 'seo-city-pages' ); ?></span>
				</div>
			</div>

			<div id="scp-live-actions" style="margin-top: 25px; display: none;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-cities' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'View Cities', 'seo-city-pages' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-services' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'View Services', 'seo-city-pages' ); ?>
				</a>
				<a id="btn-download-error-csv" href="#" class="button button-secondary" style="display: none;">
					<span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Download Error CSV', 'seo-city-pages' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import' ) ); ?>" class="button button-link">
					<?php esc_html_e( 'Import Another File', 'seo-city-pages' ); ?>
				</a>
			</div>
		</div>
	</div>

	<?php if ( ! empty( $preview_data ) ) : ?>
		<!-- STEP 2: PREVIEW & CONFIRMATION SCREEN -->
		<div id="scp-preview-step-card" class="scp-card">
			<div class="scp-card-header">
				<h2><?php esc_html_e( 'Step 2: Review & Confirm CSV Import', 'seo-city-pages' ); ?></h2>
				<span class="scp-badge scp-badge-info"><?php echo esc_html( $preview_data['file_name'] ); ?></span>
			</div>
			<div class="scp-card-body">
				<p><?php esc_html_e( 'The CSV structure has been validated. Review the summary breakdown below before committing records to the database.', 'seo-city-pages' ); ?></p>

				<div class="scp-metrics-grid">
					<div class="scp-metric-card">
						<span class="scp-metric-value"><?php echo esc_html( $preview_data['total_rows'] ); ?></span>
						<span class="scp-metric-label"><?php esc_html_e( 'Total CSV Rows', 'seo-city-pages' ); ?></span>
					</div>
					<div class="scp-metric-card">
						<span class="scp-metric-value scp-text-success"><?php echo esc_html( $preview_data['valid_count'] ); ?></span>
						<span class="scp-metric-label"><?php esc_html_e( 'Valid Rows to Process', 'seo-city-pages' ); ?></span>
					</div>
					<div class="scp-metric-card">
						<span class="scp-metric-value scp-text-warning"><?php echo esc_html( $preview_data['duplicate_count'] ); ?></span>
						<span class="scp-metric-label"><?php esc_html_e( 'Duplicate Rows in CSV', 'seo-city-pages' ); ?></span>
					</div>
					<div class="scp-metric-card">
						<span class="scp-metric-value scp-text-danger"><?php echo esc_html( $preview_data['invalid_count'] ); ?></span>
						<span class="scp-metric-label"><?php esc_html_e( 'Invalid Rows (Skipped)', 'seo-city-pages' ); ?></span>
					</div>
				</div>

				<?php if ( ! empty( $preview_data['invalid_rows'] ) || ! empty( $preview_data['duplicate_rows'] ) ) : ?>
					<div style="margin-top: 25px;">
						<h3><?php esc_html_e( 'Validation Warnings & Issues', 'seo-city-pages' ); ?></h3>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th style="width: 70px;"><?php esc_html_e( 'Row #', 'seo-city-pages' ); ?></th>
									<th><?php esc_html_e( 'Country', 'seo-city-pages' ); ?></th>
									<th><?php esc_html_e( 'State', 'seo-city-pages' ); ?></th>
									<th><?php esc_html_e( 'City', 'seo-city-pages' ); ?></th>
									<th><?php esc_html_e( 'Service', 'seo-city-pages' ); ?></th>
									<th><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></th>
									<th><?php esc_html_e( 'Reason / Details', 'seo-city-pages' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( array_merge( $preview_data['invalid_rows'], $preview_data['duplicate_rows'] ) as $issue ) : ?>
									<tr>
										<td><strong>#<?php echo esc_html( $issue['row_index'] ); ?></strong></td>
										<td><?php echo esc_html( $issue['country'] ); ?></td>
										<td><?php echo esc_html( $issue['state'] ); ?></td>
										<td><?php echo esc_html( $issue['city'] ); ?></td>
										<td><?php echo esc_html( $issue['service'] ); ?></td>
										<td>
											<?php if ( 'invalid' === $issue['status'] ) : ?>
												<span class="scp-badge scp-badge-inactive"><?php esc_html_e( 'Invalid', 'seo-city-pages' ); ?></span>
											<?php else : ?>
												<span class="scp-badge scp-badge-warning"><?php esc_html_e( 'Duplicate', 'seo-city-pages' ); ?></span>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( $issue['reason'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<!-- Top 10 Preview Table -->
				<div style="margin-top: 25px;">
					<h3><?php esc_html_e( 'Sample Valid Rows (First 10)', 'seo-city-pages' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th style="width: 70px;"><?php esc_html_e( 'Row #', 'seo-city-pages' ); ?></th>
								<th><?php esc_html_e( 'Country', 'seo-city-pages' ); ?></th>
								<th><?php esc_html_e( 'State / Region', 'seo-city-pages' ); ?></th>
								<th><?php esc_html_e( 'City', 'seo-city-pages' ); ?></th>
								<th><?php esc_html_e( 'Service', 'seo-city-pages' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $preview_data['valid_rows'] as $vrow ) : ?>
								<tr>
									<td>#<?php echo esc_html( $vrow['row_index'] ); ?></td>
									<td><strong><?php echo esc_html( $vrow['country'] ); ?></strong></td>
									<td><?php echo esc_html( $vrow['state'] ); ?></td>
									<td><?php echo esc_html( $vrow['city'] ); ?></td>
									<td><?php echo esc_html( $vrow['service'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="scp-form-actions" style="margin-top: 25px;">
					<?php if ( $preview_data['valid_count'] > 0 ) : ?>
						<button type="button" id="scp-start-import-btn" class="button button-primary button-hero"
							data-job-id="<?php echo esc_attr( $preview_data['job_id'] ); ?>"
							data-total="<?php echo esc_attr( $preview_data['valid_count'] ); ?>"
							data-nonce="<?php echo esc_attr( $batch_nonce ); ?>">
							<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Confirm & Start Batched Import', 'seo-city-pages' ); ?>
						</button>
					<?php else : ?>
						<p class="scp-text-danger"><strong><?php esc_html_e( 'No valid rows found to import.', 'seo-city-pages' ); ?></strong></p>
					<?php endif; ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Cancel', 'seo-city-pages' ); ?>
					</a>
				</div>
			</div>
		</div>
	<?php else : ?>
		<!-- STEP 1: UPLOAD CARD -->
		<div class="scp-card">
			<div class="scp-card-header">
				<h2><?php esc_html_e( 'Step 1: Upload CSV File', 'seo-city-pages' ); ?></h2>
			</div>
			<div class="scp-card-body">
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import' ) ); ?>">
					<?php \SeoCityPages\Security\Security::nonce_field( 'import_upload' ); ?>

					<div class="scp-dropzone" id="scp-csv-dropzone">
						<span class="dashicons dashicons-upload scp-dropzone-icon"></span>
						<h3><?php esc_html_e( 'Drag and drop your CSV file here, or click to browse', 'seo-city-pages' ); ?></h3>
						<p class="description"><?php printf( esc_html__( 'Maximum upload file size: %s. Only .csv files are supported.', 'seo-city-pages' ), esc_html( $max_upload_size ) ); ?></p>
						<input type="file" name="csv_file" id="scp-csv-file-input" accept=".csv,text/csv,text/plain" required style="margin-top: 15px;">
					</div>

					<div class="scp-import-instructions" style="margin-top: 25px;">
						<h3><?php esc_html_e( 'Supported CSV Format', 'seo-city-pages' ); ?></h3>
						<p><?php esc_html_e( 'The CSV file must contain a header row with the core location columns, and can optionally include target SEO keywords for AI page generation:', 'seo-city-pages' ); ?></p>
						<code>country,state,city,service,primary_keyword,keywords</code>

						<div style="margin-top: 15px;">
							<p><strong><?php esc_html_e( 'Example CSV Content with Target Keywords:', 'seo-city-pages' ); ?></strong></p>
							<pre class="scp-code-block">country,state,city,service,primary_keyword,keywords
India,Gujarat,Rajkot,SEO Agency,SEO Agency in Rajkot,"seo company rajkot, local seo services rajkot, top search agency"
India,Gujarat,Ahmedabad,SEO Agency,SEO Agency Ahmedabad,"best seo agency ahmedabad, ecommerce seo ahmedabad"
India,Gujarat,Surat,SEO Agency,SEO Agency in Surat,"local seo surat, search engine optimization surat"
India,Maharashtra,Mumbai,Digital Marketing Agency,Digital Marketing Agency Mumbai,"digital marketing services mumbai, online growth agency mumbai"</pre>
						</div>

						<ul class="scp-features-list" style="margin-top: 15px;">
							<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Target SEO Keywords: specify primary_keyword and secondary keywords to inject directly into AI content generation and SEO metadata.', 'seo-city-pages' ); ?></li>
							<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Automatic normalization: handles varying casing and extra spaces without creating duplicates.', 'seo-city-pages' ); ?></li>
							<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Referential integrity: automatically links Countries, States, Cities, and Services.', 'seo-city-pages' ); ?></li>
							<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Idempotent re-import: re-uploading identical rows will safely update keywords without creating duplicate pages.', 'seo-city-pages' ); ?></li>
							<li><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Batch processing: processes thousands of rows safely without timing out.', 'seo-city-pages' ); ?></li>
						</ul>
					</div>

					<div class="scp-form-actions" style="margin-top: 25px;">
						<button type="submit" name="seo_city_pages_upload_csv" value="1" class="button button-primary button-hero">
							<span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Upload & Validate CSV', 'seo-city-pages' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	<?php endif; ?>

	<!-- RECENT IMPORT JOBS HISTORY -->
	<div class="scp-card" style="margin-top: 30px;">
		<div class="scp-card-header">
			<h2><?php esc_html_e( 'Import Job History', 'seo-city-pages' ); ?></h2>
		</div>
		<div class="scp-card-body">
			<?php if ( empty( $recent_jobs ) ) : ?>
				<p class="description"><?php esc_html_e( 'No import jobs have been executed yet.', 'seo-city-pages' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'File Name', 'seo-city-pages' ); ?></th>
							<th><?php esc_html_e( 'Total Rows', 'seo-city-pages' ); ?></th>
							<th><?php esc_html_e( 'Processed', 'seo-city-pages' ); ?></th>
							<th><?php esc_html_e( 'Created Combinations', 'seo-city-pages' ); ?></th>
							<th><?php esc_html_e( 'Existing', 'seo-city-pages' ); ?></th>
							<th><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></th>
							<th><?php esc_html_e( 'Date', 'seo-city-pages' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'seo-city-pages' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_jobs as $job ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $job->file_name ); ?></strong></td>
								<td><?php echo esc_html( $job->total_rows ); ?></td>
								<td><?php echo esc_html( $job->processed_rows ); ?></td>
								<td><span class="scp-badge scp-badge-active">+<?php echo esc_html( $job->created_combinations ); ?></span></td>
								<td><?php echo esc_html( $job->existing_combinations ); ?></td>
								<td>
									<?php if ( 'completed' === $job->status ) : ?>
										<span class="scp-badge scp-badge-active"><?php esc_html_e( 'Completed', 'seo-city-pages' ); ?></span>
									<?php elseif ( 'completed_errors' === $job->status ) : ?>
										<span class="scp-badge scp-badge-warning"><?php esc_html_e( 'Completed with Errors', 'seo-city-pages' ); ?></span>
									<?php elseif ( 'processing' === $job->status ) : ?>
										<span class="scp-badge scp-badge-info"><?php esc_html_e( 'Processing', 'seo-city-pages' ); ?></span>
									<?php else : ?>
										<span class="scp-badge scp-badge-inactive"><?php echo esc_html( ucfirst( $job->status ) ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $job->created_at ); ?></td>
								<td>
									<?php if ( (int) $job->failed_rows > 0 || 'completed_errors' === $job->status ) : ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import&action=download_errors&job_id=' . rawurlencode( $job->job_id ) ) ); ?>" class="button button-small">
											<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Error CSV', 'seo-city-pages' ); ?>
										</a>
									<?php else : ?>
										<span class="description">—</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>
