<?php
/**
 * Topic-Based County Batch Generator Admin Template.
 *
 * @package SeoCityPages\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nonce = wp_create_nonce( \SeoCityPages\Admin\County_Generator_Controller::NONCE_ACTION );
?>

<div class="wrap seo-county-generator-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-location" style="font-size: 28px; line-height: 1; margin-right: 8px; vertical-align: middle;"></span>
		<?php esc_html_e( 'Topic-Based County Page Generator', 'seo-city-pages' ); ?>
	</h1>

	<hr class="wp-header-end">

	<!-- Coexistence Notice Banner -->
	<div class="notice notice-info inline" style="margin: 15px 0 25px 0; padding: 12px 16px; border-left-color: #2271b1; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
		<p style="margin: 0; font-size: 13.5px; line-height: 1.5;">
			<strong><?php esc_html_e( '💡 Flexible Workflows:', 'seo-city-pages' ); ?></strong>
			<?php esc_html_e( 'You can generate pages directly by entering your topic/niche below (automatically split into safe 100-page batches), OR upload custom spreadsheets anytime via the', 'seo-city-pages' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import' ) ); ?>" style="font-weight: 600;">
				<?php esc_html_e( 'CSV Import screen &rarr;', 'seo-city-pages' ); ?>
			</a>
		</p>
	</div>

	<div class="seo-county-grid" style="display: grid; grid-template-columns: 360px 1fr; gap: 24px; align-items: start;">

		<!-- Left Column: Create New Topic Campaign -->
		<div class="postbox" style="padding: 0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
			<div class="postbox-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 16px;">
				<h2 style="font-size: 15px; font-weight: 600; margin: 0; color: #1e293b;">
					<span class="dashicons dashicons-plus-alt2" style="margin-right: 4px; color: #2563eb;"></span>
					<?php esc_html_e( 'New Topic Campaign', 'seo-city-pages' ); ?>
				</h2>
			</div>
			<div class="inside" style="padding: 16px;">
				<form id="seo-county-campaign-form">
					<div style="margin-bottom: 16px;">
						<label for="county_topic" style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">
							<?php esc_html_e( 'Topic / Niche / Service Name', 'seo-city-pages' ); ?> <span style="color: #dc2626;">*</span>
						</label>
						<input type="text" id="county_topic" name="topic" class="large-text" placeholder="<?php esc_attr_e( 'e.g. Dental Clinic, Personal Injury Lawyer, Roofing', 'seo-city-pages' ); ?>" required style="height: 38px; border-radius: 6px; border: 1px solid #cbd5e1;">
						<p class="description" style="margin-top: 4px; font-size: 12px; color: #64748b;">
							<?php esc_html_e( 'The commercial service or niche you want to rank for.', 'seo-city-pages' ); ?>
						</p>
					</div>

					<div style="margin-bottom: 16px;">
						<label style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">
							<?php esc_html_e( 'Geographic Scope (US Counties)', 'seo-city-pages' ); ?>
						</label>
						<label style="display: block; margin-bottom: 8px; cursor: pointer;">
							<input type="radio" name="scope_type" value="all" checked>
							<strong><?php esc_html_e( 'All 50 US States', 'seo-city-pages' ); ?></strong>
							<span style="display: block; font-size: 12px; color: #64748b; margin-left: 22px;">
								<?php esc_html_e( 'All ~3,146 US Counties (~32 batches of 100 pages)', 'seo-city-pages' ); ?>
							</span>
						</label>
						<label style="display: block; margin-bottom: 8px; cursor: pointer;">
							<input type="radio" name="scope_type" value="custom">
							<strong><?php esc_html_e( 'Select Specific States', 'seo-city-pages' ); ?></strong>
						</label>

						<div id="state-select-container" style="display: none; margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; max-height: 180px; overflow-y: auto;">
							<?php foreach ( $states as $code => $name ) : ?>
								<label style="display: block; font-size: 13px; margin-bottom: 4px; cursor: pointer;">
									<input type="checkbox" name="target_states[]" value="<?php echo esc_attr( $code ); ?>">
									<?php echo esc_html( $name . ' (' . $code . ')' ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div style="margin-bottom: 20px;">
						<label for="batch_size" style="display: block; font-weight: 600; margin-bottom: 6px; color: #334155;">
							<?php esc_html_e( 'Batch Size (Pages per Batch)', 'seo-city-pages' ); ?>
						</label>
						<select id="batch_size" name="batch_size" style="width: 100%; height: 38px; border-radius: 6px; border: 1px solid #cbd5e1;">
							<option value="100" selected><?php esc_html_e( '100 Pages per Batch (Recommended)', 'seo-city-pages' ); ?></option>
							<option value="50"><?php esc_html_e( '50 Pages per Batch', 'seo-city-pages' ); ?></option>
							<option value="25"><?php esc_html_e( '25 Pages per Batch', 'seo-city-pages' ); ?></option>
						</select>
					</div>

					<button type="submit" id="btn-create-campaign" class="button button-primary button-large" style="width: 100%; height: 42px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
						<span class="dashicons dashicons-networking"></span>
						<?php esc_html_e( 'Create Batches & Setup', 'seo-city-pages' ); ?>
					</button>
				</form>
			</div>
		</div>

		<!-- Right Column: Active Campaign & 100-Page Batches Manager -->
		<div class="seo-county-main-dashboard">
			<?php if ( ! empty( $campaigns ) && $active_campaign ) : ?>
				<!-- Campaign Header & Switcher -->
				<div class="postbox" style="padding: 16px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
					<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
						<div>
							<div style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #2563eb; letter-spacing: 0.5px;">
								<?php esc_html_e( 'Active Campaign', 'seo-city-pages' ); ?>
							</div>
							<h2 style="margin: 4px 0 0 0; font-size: 20px; font-weight: 700; color: #0f172a;">
								<?php echo esc_html( $active_campaign->topic ); ?>
							</h2>
						</div>

						<div style="display: flex; align-items: center; gap: 10px;">
							<select id="campaign-switcher" onchange="window.location.href='<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-county-generator&campaign_id=' ) ); ?>' + this.value;" style="height: 36px; border-radius: 6px;">
								<?php foreach ( $campaigns as $camp ) : ?>
									<option value="<?php echo esc_attr( $camp->campaign_id ); ?>" <?php selected( $camp->campaign_id, $active_campaign->campaign_id ); ?>>
										<?php echo esc_html( $camp->topic . ' (' . $camp->total_counties . ' counties)' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<button type="button" class="button button-link-delete" onclick="seoDeleteCampaign('<?php echo esc_attr( $active_campaign->campaign_id ); ?>')" style="color: #dc2626; height: 36px;">
								<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
							</button>
						</div>
					</div>

					<!-- Metrics Ribbon -->
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
						<div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
							<div style="font-size: 11px; color: #64748b; font-weight: 600;"><?php esc_html_e( 'TOTAL COUNTIES', 'seo-city-pages' ); ?></div>
							<div style="font-size: 20px; font-weight: 700; color: #1e293b; margin-top: 2px;" id="metric-total-counties">
								<?php echo esc_html( number_format_i18n( $active_campaign->total_counties ) ); ?>
							</div>
						</div>
						<div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
							<div style="font-size: 11px; color: #64748b; font-weight: 600;"><?php esc_html_e( 'TOTAL BATCHES', 'seo-city-pages' ); ?></div>
							<div style="font-size: 20px; font-weight: 700; color: #2563eb; margin-top: 2px;" id="metric-total-batches">
								<?php echo esc_html( $active_campaign->total_batches ); ?>
							</div>
						</div>
						<div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
							<div style="font-size: 11px; color: #64748b; font-weight: 600;"><?php esc_html_e( 'GENERATED PAGES', 'seo-city-pages' ); ?></div>
							<div style="font-size: 20px; font-weight: 700; color: #16a34a; margin-top: 2px;" id="metric-generated-pages">
								<?php echo esc_html( number_format_i18n( $active_campaign->generated_pages ) ); ?>
							</div>
						</div>
						<div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
							<div style="font-size: 11px; color: #64748b; font-weight: 600;"><?php esc_html_e( 'STATUS', 'seo-city-pages' ); ?></div>
							<div style="font-size: 14px; font-weight: 700; text-transform: uppercase; margin-top: 6px;" id="metric-status">
								<span class="seo-badge seo-badge-<?php echo esc_attr( $active_campaign->status ); ?>">
									<?php echo esc_html( $active_campaign->status ); ?>
								</span>
							</div>
						</div>
					</div>
				</div>

				<!-- Batches List -->
				<div class="postbox" style="padding: 0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
					<div class="postbox-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
						<h2 style="font-size: 15px; font-weight: 600; margin: 0; color: #1e293b;">
							<span class="dashicons dashicons-grid-view" style="margin-right: 4px; color: #2563eb;"></span>
							<?php esc_html_e( '100-Page Batches Queue', 'seo-city-pages' ); ?>
						</h2>
						<div style="display: flex; align-items: center; gap: 10px;">
							<label style="font-size: 12px; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px; cursor: pointer;">
								<input type="checkbox" id="auto-run-next-batch" checked>
								<?php esc_html_e( 'Auto-Run Next Batch', 'seo-city-pages' ); ?>
							</label>
							<button type="button" class="button button-primary button-small" id="btn-run-all-batches" onclick="seoRunAllBatches()">
								<span class="dashicons dashicons-controls-play" style="font-size: 14px; line-height: 1.5;"></span> <?php esc_html_e( 'Run All Batches', 'seo-city-pages' ); ?>
							</button>
							<button type="button" class="button button-secondary button-small" id="btn-stop-all-batches" onclick="seoStopAllBatches()" style="display: none; color: #d63638; border-color: #d63638; font-weight: 600;">
								<span class="dashicons dashicons-controls-pause" style="font-size: 14px; line-height: 1.5;"></span> <?php esc_html_e( 'Pause / Stop All', 'seo-city-pages' ); ?>
							</button>
						</div>
					</div>

					<div class="inside" style="padding: 16px;">
						<div class="seo-batches-table-wrap">
							<table class="wp-list-table widefat fixed striped table-view-list" style="border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
								<thead>
									<tr>
										<th style="width: 100px; font-weight: 600;"><?php esc_html_e( 'Batch #', 'seo-city-pages' ); ?></th>
										<th style="width: 180px; font-weight: 600;"><?php esc_html_e( 'Region / States', 'seo-city-pages' ); ?></th>
										<th style="font-weight: 600;"><?php esc_html_e( 'Generation Progress', 'seo-city-pages' ); ?></th>
										<th style="width: 110px; font-weight: 600; text-align: center;"><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></th>
										<th style="width: 240px; font-weight: 600; text-align: right;"><?php esc_html_e( 'Actions', 'seo-city-pages' ); ?></th>
									</tr>
								</thead>
								<tbody id="seo-batches-body">
									<?php foreach ( $batches as $batch ) :
										$params = ! empty( $batch->job_params ) ? json_decode( $batch->job_params, true ) : [];
										$b_num  = $batch->current_batch ?: 1;
										$total  = (int) $batch->total_items;
										$done   = (int) $batch->completed_items;
										$failed = (int) $batch->failed_items;
										$pct    = $total > 0 ? (int) round( ( $done / $total ) * 100 ) : 0;
									?>
										<tr id="batch-row-<?php echo esc_attr( $batch->job_id ); ?>" data-job-id="<?php echo esc_attr( $batch->job_id ); ?>">
											<td style="font-weight: 700; color: #1e293b;">
												Batch #<?php echo esc_html( $b_num ); ?>
											</td>
											<td style="color: #475569; font-size: 13px;">
												<?php echo esc_html( $params['states_label'] ?? 'US Counties' ); ?>
											</td>
											<td>
												<div style="display: flex; align-items: center; gap: 10px;">
													<div style="flex: 1; height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden; position: relative;">
														<div class="progress-bar-fill" style="width: <?php echo esc_attr( $pct ); ?>%; height: 100%; background: #16a34a; transition: width 0.3s ease;"></div>
													</div>
													<span class="progress-text" style="font-size: 12px; font-weight: 600; color: #334155; min-width: 80px;">
														<?php echo esc_html( "{$done} / {$total} ({$pct}%)" ); ?>
													</span>
												</div>
											</td>
											<td style="text-align: center;">
												<span class="seo-status-pill status-<?php echo esc_attr( $batch->status ); ?>">
													<?php echo esc_html( ucfirst( $batch->status ) ); ?>
												</span>
											</td>
											<td style="text-align: right;">
												<div style="display: flex; justify-content: flex-end; gap: 6px; align-items: center;">
													<?php if ( 'completed' === $batch->status ) : ?>
														<button type="button" class="button button-secondary button-small" onclick="seoPublishBatch('<?php echo esc_attr( $batch->job_id ); ?>')" title="<?php esc_attr_e( 'Publish all draft pages live', 'seo-city-pages' ); ?>">
															<span class="dashicons dashicons-upload" style="font-size: 14px; line-height: 1.5;"></span> <?php esc_html_e( 'Publish', 'seo-city-pages' ); ?>
														</button>
													<?php else : ?>
														<button type="button" class="button button-primary button-small btn-run-batch" onclick="seoRunBatch('<?php echo esc_attr( $batch->job_id ); ?>')">
															<span class="dashicons dashicons-controls-play" style="font-size: 14px; line-height: 1.5;"></span> <?php echo $done > 0 ? esc_html__( 'Resume', 'seo-city-pages' ) : esc_html__( 'Generate', 'seo-city-pages' ); ?>
														</button>
														<button type="button" class="button button-secondary button-small btn-stop-batch" onclick="seoStopBatch('<?php echo esc_attr( $batch->job_id ); ?>')" style="display: none; color: #d63638; border-color: #d63638; font-weight: 600;">
															<span class="dashicons dashicons-controls-pause" style="font-size: 14px; line-height: 1.5;"></span> <?php esc_html_e( 'Pause / Stop', 'seo-city-pages' ); ?>
														</button>
													<?php endif; ?>

													<?php if ( $failed > 0 ) : ?>
														<button type="button" class="button button-secondary button-small" onclick="seoRetryBatch('<?php echo esc_attr( $batch->job_id ); ?>')" title="<?php esc_attr_e( 'Retry failed pages', 'seo-city-pages' ); ?>">
															<span class="dashicons dashicons-image-rotate" style="font-size: 14px; line-height: 1.5;"></span> <?php esc_html_e( 'Retry', 'seo-city-pages' ); ?>
														</button>
													<?php endif; ?>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

			<?php else : ?>
				<!-- Empty State Card -->
				<div class="postbox" style="padding: 48px 24px; text-align: center; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
					<span class="dashicons dashicons-admin-site-alt3" style="font-size: 54px; width: 54px; height: 54px; color: #94a3b8; margin-bottom: 12px;"></span>
					<h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0 0 8px 0;">
						<?php esc_html_e( 'No Topic Campaigns Yet', 'seo-city-pages' ); ?>
					</h2>
					<p style="max-width: 480px; margin: 0 auto 20px auto; color: #64748b; font-size: 14px;">
						<?php esc_html_e( 'Enter your topic or niche on the left (e.g. "Dental Clinic", "Criminal Defense Lawyer") and click "Create Batches & Setup" to automatically generate county landing page batches.', 'seo-city-pages' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<style>
.seo-badge {
	display: inline-block;
	padding: 3px 8px;
	border-radius: 4px;
	font-size: 11px;
	letter-spacing: 0.5px;
}
.seo-badge-ready, .seo-badge-pending { background: #e0f2fe; color: #0369a1; }
.seo-badge-running, .seo-badge-in_progress { background: #fef3c7; color: #b45309; }
.seo-badge-completed { background: #dcfce7; color: #15803d; }
.seo-badge-failed { background: #fee2e2; color: #b91c1c; }

.seo-status-pill {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 11px;
	font-weight: 600;
}
.status-pending { background: #f1f5f9; color: #475569; }
.status-running { background: #fef3c7; color: #b45309; animation: pulse 1.5s infinite; }
.status-completed { background: #dcfce7; color: #15803d; }
.status-failed { background: #fee2e2; color: #b91c1c; }

@keyframes pulse {
	0% { opacity: 1; }
	50% { opacity: 0.6; }
	100% { opacity: 1; }
}
</style>

<script>
(function($) {
	'use strict';

	var nonce = '<?php echo esc_js( $nonce ); ?>';
	var activeJobId = null;
	var isRunning = false;

	// Toggle State Checkboxes
	$('input[name="scope_type"]').on('change', function() {
		if ($(this).val() === 'custom') {
			$('#state-select-container').slideDown(150);
		} else {
			$('#state-select-container').slideUp(150);
		}
	});

	// Handle Campaign Creation Form
	$('#seo-county-campaign-form').on('submit', function(e) {
		e.preventDefault();
		var $btn = $('#btn-create-campaign');
		$btn.prop('disabled', true).text('Creating Batches...');

		var scope = $('input[name="scope_type"]:checked').val();
		var targetStates = 'ALL';
		if (scope === 'custom') {
			var selected = [];
			$('input[name="target_states[]"]:checked').each(function() {
				selected.push($(this).val());
			});
			if (selected.length === 0) {
				alert('Please select at least one state.');
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-networking"></span> Create Batches & Setup');
				return;
			}
			targetStates = selected.join(',');
		}

		$.post(ajaxurl, {
			action: 'seo_county_create_campaign',
			_wpnonce: nonce,
			topic: $('#county_topic').val(),
			batch_size: $('#batch_size').val(),
			target_states: targetStates
		}, function(res) {
			if (res.success) {
				window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-county-generator&campaign_id=' ) ); ?>' + res.data.campaign_id;
			} else {
				alert(res.data && res.data.message ? res.data.message : 'Error creating campaign.');
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-networking"></span> Create Batches & Setup');
			}
		}).fail(function(xhr) {
			var errMsg = 'Network request failed. Please try again.';
			if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
				errMsg = xhr.responseJSON.data.message;
			} else if (xhr.responseText) {
				try {
					var parsed = JSON.parse(xhr.responseText);
					if (parsed && parsed.data && parsed.data.message) {
						errMsg = parsed.data.message;
					}
				} catch(e) {
					if (xhr.status === 504 || xhr.status === 502) {
						errMsg = 'Gateway timeout (' + xhr.status + '). The server took too long to respond.';
					} else if (xhr.status === 500) {
						errMsg = 'Server error (500). Please check your WordPress error logs.';
					} else if (xhr.status === 403) {
						errMsg = 'Security nonce expired. Please refresh the page and try again.';
					}
				}
			}
			alert(errMsg);
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-networking"></span> Create Batches & Setup');
		});
	});

	var batchTimeout = null;

	// Run Batch Loop
	window.seoRunBatch = function(jobId) {
		if (isRunning && activeJobId !== jobId) {
			alert('Another batch generation is currently in progress.');
			return;
		}

		activeJobId = jobId;
		isRunning = true;

		var $row = $('#batch-row-' + jobId);
		$row.find('.seo-status-pill').removeClass('status-pending status-completed status-failed').addClass('status-running').text('Running');
		$row.find('.btn-run-batch').hide();
		$row.find('.btn-stop-batch').show();

		$('#btn-run-all-batches').hide();
		$('#btn-stop-all-batches').show();

		processBatchChunk(jobId);
	};

	// Pause / Stop Batch Loop
	window.seoStopBatch = function(jobId) {
		if (batchTimeout) {
			clearTimeout(batchTimeout);
			batchTimeout = null;
		}

		isRunning = false;
		activeJobId = null;

		var $row = $('#batch-row-' + jobId);
		$row.find('.btn-stop-batch').hide();
		$row.find('.btn-run-batch').show().prop('disabled', false).html('<span class="dashicons dashicons-controls-play"></span> Resume');
		$row.find('.seo-status-pill').removeClass('status-running').addClass('status-pending').text('Paused');

		$('#btn-stop-all-batches').hide();
		$('#btn-run-all-batches').show();
	};

	// Run All Batches Sequentially
	window.seoRunAllBatches = function() {
		var $firstUnfinished = $('tbody#seo-batches-body tr').filter(function() {
			return $(this).find('.btn-run-batch').length > 0;
		}).first();

		if (!$firstUnfinished.length) {
			alert('All batches have already been generated!');
			return;
		}

		$('#auto-run-next-batch').prop('checked', true);
		var nextJobId = $firstUnfinished.data('job-id');
		if (nextJobId) {
			seoRunBatch(nextJobId);
		}
	};

	// Stop All Batches
	window.seoStopAllBatches = function() {
		$('#auto-run-next-batch').prop('checked', false);
		if (activeJobId) {
			seoStopBatch(activeJobId);
		} else {
			isRunning = false;
			$('#btn-stop-all-batches').hide();
			$('#btn-run-all-batches').show();
		}
	};

	function processBatchChunk(jobId) {
		if (!isRunning || activeJobId !== jobId) return;

		$.post(ajaxurl, {
			action: 'seo_county_process_chunk',
			_wpnonce: nonce,
			job_id: jobId,
			chunk_size: 5
		}, function(res) {
			if (!res.success) {
				alert(res.data && res.data.message ? res.data.message : 'Batch generation error.');
				seoStopBatch(jobId);
				return;
			}

			var data = res.data;
			var $row = $('#batch-row-' + jobId);
			$row.find('.progress-bar-fill').css('width', data.percent + '%');
			$row.find('.progress-text').text(data.completed_items + ' / ' + data.total_items + ' (' + data.percent + '%)');

			if (data.is_done) {
				$row.find('.seo-status-pill').removeClass('status-running').addClass('status-completed').text('Completed');
				$row.find('.btn-stop-batch').hide();
				$row.find('.btn-run-batch').replaceWith('<button type="button" class="button button-secondary button-small" onclick="seoPublishBatch(\'' + jobId + '\')"><span class="dashicons dashicons-upload"></span> Publish</button>');
				isRunning = false;
				activeJobId = null;

				// Check Auto-run next batch
				if ($('#auto-run-next-batch').is(':checked')) {
					var $nextRow = $row.next('tr[id^="batch-row-"]');
					if ($nextRow.length && $nextRow.find('.btn-run-batch').length) {
						var nextJobId = $nextRow.data('job-id');
						if (nextJobId) {
							batchTimeout = setTimeout(function() {
								seoRunBatch(nextJobId);
							}, 800);
						}
					} else {
						$('#btn-stop-all-batches').hide();
						$('#btn-run-all-batches').show();
					}
				} else {
					$('#btn-stop-all-batches').hide();
					$('#btn-run-all-batches').show();
				}
			} else {
				// Process next chunk
				if (isRunning && activeJobId === jobId) {
					batchTimeout = setTimeout(function() {
						processBatchChunk(jobId);
					}, 500);
				}
			}
		}).fail(function() {
			// Retry once after 3 seconds on network hiccup
			if (isRunning && activeJobId === jobId) {
				batchTimeout = setTimeout(function() {
					if (isRunning && activeJobId === jobId) processBatchChunk(jobId);
				}, 3000);
			}
		});
	}

	// Retry Batch
	window.seoRetryBatch = function(jobId) {
		$.post(ajaxurl, {
			action: 'seo_county_batch_action',
			_wpnonce: nonce,
			batch_action: 'retry_failed',
			job_id: jobId
		}, function(res) {
			if (res.success) {
				location.reload();
			} else {
				alert(res.data && res.data.message ? res.data.message : 'Error resetting items.');
			}
		});
	};

	// Publish Batch
	window.seoPublishBatch = function(jobId) {
		if (!confirm('Publish all generated draft pages in this batch to live WordPress posts?')) return;

		$.post(ajaxurl, {
			action: 'seo_county_batch_action',
			_wpnonce: nonce,
			batch_action: 'publish_batch',
			job_id: jobId
		}, function(res) {
			if (res.success) {
				alert(res.data.message);
				location.reload();
			} else {
				alert(res.data && res.data.message ? res.data.message : 'Error publishing batch.');
			}
		});
	};

	// Delete Campaign
	window.seoDeleteCampaign = function(campaignId) {
		if (!confirm('Are you sure you want to delete this campaign and all its batches?')) return;

		$.post(ajaxurl, {
			action: 'seo_county_batch_action',
			_wpnonce: nonce,
			batch_action: 'delete_campaign',
			campaign_id: campaignId
		}, function(res) {
			if (res.success) {
				window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-county-generator' ) ); ?>';
			} else {
				alert(res.data && res.data.message ? res.data.message : 'Error deleting campaign.');
			}
		});
	};

})(jQuery);
</script>
