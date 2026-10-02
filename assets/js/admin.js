/**
 * SEO City Pages — Scoped Admin Script & Batch Importer
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// 1. Drag & Drop Visuals on File Dropzone.
		var $dropzone = $('#scp-csv-dropzone');
		var $fileInput = $('#scp-csv-file-input');

		if ($dropzone.length && $fileInput.length) {
			$dropzone.on('dragover dragenter', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$dropzone.addClass('dragover');
			});

			$dropzone.on('dragleave dragend drop', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$dropzone.removeClass('dragover');
			});

			$dropzone.on('drop', function(e) {
				var files = e.originalEvent.dataTransfer.files;
				if (files.length > 0) {
					$fileInput[0].files = files;
				}
			});
		}

		// 2. Batched CSV Importer AJAX Runner.
		var $startBtn = $('#scp-start-import-btn');
		if ($startBtn.length) {
			$startBtn.on('click', function(e) {
				e.preventDefault();

				var jobId = $(this).data('job-id');
				var total = parseInt($(this).data('total'), 10) || 0;
				var nonce = $(this).data('nonce') || (window.seoCityPagesAdmin ? window.seoCityPagesAdmin.nonce : '');
				var ajaxUrl = window.seoCityPagesAdmin ? window.seoCityPagesAdmin.ajaxUrl : ajaxurl;

				if (!jobId || total <= 0) {
					alert('Invalid import configuration.');
					return;
				}

				// Hide preview card and reveal live progress container.
				$('#scp-preview-step-card').slideUp(200);
				$('#scp-import-live-container').slideDown(300);

				$('#scp-progress-total').text(total);
				$('#scp-progress-processed').text(0);
				$('#scp-progress-bar-fill').css('width', '0%');
				$('#scp-progress-percent').text('0%');

				var offset = 0;
				var limit = 50; // Batch chunk size.
				var isImportPaused = false;
				var isImportStopped = false;

				$('#scp-pause-import-btn').on('click', function(e) {
					e.preventDefault();
					isImportPaused = true;
					$(this).hide();
					$('#scp-resume-import-btn').show();
					$('#scp-live-title').html('<span class="dashicons dashicons-controls-pause" style="color: #dba617;"></span> Import Paused');
				});

				$('#scp-resume-import-btn').on('click', function(e) {
					e.preventDefault();
					if (!isImportPaused || isImportStopped) return;
					isImportPaused = false;
					$(this).hide();
					$('#scp-pause-import-btn').show();
					$('#scp-live-title').html('<span class="dashicons dashicons-update spin"></span> Processing Import in Batches...');
					processNextBatch();
				});

				$('#scp-stop-import-btn').on('click', function(e) {
					e.preventDefault();
					if (!confirm('Are you sure you want to stop the import? Any rows already imported will remain saved.')) return;
					isImportStopped = true;
					isImportPaused = true;
					$('#scp-import-controls').hide();
					$('#scp-live-title').html('<span class="dashicons dashicons-no-alt scp-text-danger"></span> Import Stopped by User');
					$('#scp-live-actions').slideDown(200);
				});

				function processNextBatch() {
					if (isImportPaused || isImportStopped) {
						return;
					}

					$.ajax({
						url: ajaxUrl,
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'seo_city_pages_process_batch',
							nonce: nonce,
							job_id: jobId,
							offset: offset,
							limit: limit
						},
						success: function(response) {
							if (isImportStopped) return;

							if (!response || !response.success) {
								var msg = (response && response.data && response.data.message) ? response.data.message : 'Unknown server error during batch processing.';
								$('#scp-live-title').html('<span class="dashicons dashicons-warning scp-text-danger"></span> Import Interrupted: ' + msg);
								$('#scp-import-controls').hide();
								$('#scp-live-actions').show();
								return;
							}

							var data = response.data;
							offset = data.offset;
							var percent = data.percentage;

							// Update Progress UI.
							$('#scp-progress-bar-fill').css('width', percent + '%');
							$('#scp-progress-percent').text(percent + '%');
							$('#scp-progress-processed').text(data.job_stats.processed);

							// Update Live Metric Counters.
							$('#stat-created-combinations').text(data.job_stats.created_combinations);
							$('#stat-existing-combinations').text(data.job_stats.existing_combinations);
							$('#stat-created-countries').text(data.job_stats.created_countries);
							$('#stat-created-states').text(data.job_stats.created_states);
							$('#stat-created-cities').text(data.job_stats.created_cities);
							$('#stat-created-services').text(data.job_stats.created_services);
							$('#stat-failed-rows').text(data.job_stats.failed_rows);

							if (!data.is_finished) {
								if (!isImportPaused && !isImportStopped) {
									processNextBatch();
								}
							} else {
								// Import Complete.
								$('#scp-import-controls').hide();
								$('#scp-progress-bar-fill').removeClass('scp-progress-bar-fill').css({
									'background': '#007017',
									'width': '100%'
								});
								$('#scp-progress-percent').text('100%');

								var failedCount = parseInt(data.job_stats.failed_rows, 10) || 0;
								if (failedCount > 0) {
									$('#scp-live-title').html('<span class="dashicons dashicons-warning" style="color: #dba617;"></span> Import Completed with ' + failedCount + ' Errors.');
									var errUrl = 'admin.php?page=seo-city-pages-import&action=download_errors&job_id=' + encodeURIComponent(jobId);
									$('#btn-download-error-csv').attr('href', errUrl).show();
								} else {
									$('#scp-live-title').html('<span class="dashicons dashicons-yes-alt" style="color: #007017;"></span> Import Completed Successfully!');
								}

								$('#scp-live-actions').slideDown(200);
							}
						},
						error: function(xhr, status, error) {
							if (isImportStopped) return;
							$('#scp-live-title').html('<span class="dashicons dashicons-warning scp-text-danger"></span> Server Communication Error: ' + error);
							$('#scp-import-controls').hide();
							$('#scp-live-actions').show();
						}
					});
				}

				// Launch initial batch chunk.
				processNextBatch();
			});
		}

		var ajaxUrl = window.seoCityPagesAdmin ? window.seoCityPagesAdmin.ajaxUrl : ajaxurl;
		var nonce = window.seoCityPagesAdmin ? window.seoCityPagesAdmin.nonce : '';

		// 3. Test OpenAI Connection AJAX Handler.
		$('#scp-test-conn-btn').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var $result = $('#scp-test-conn-result');

			$btn.prop('disabled', true).text('Testing Connection...');
			$result.html('<div style="padding: 10px 14px; background: #f6f7f7; border-left: 4px solid #72aee6; border-radius: 4px; font-size: 13px;">Contacting OpenAI API...</div>');

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_test_openai_connection',
					nonce: nonce
				},
				success: function(res) {
					$btn.prop('disabled', false).text('Test OpenAI Connection');
					if (res && res.success) {
						$result.html('<div style="padding: 10px 14px; background: #edfaef; border-left: 4px solid #00a32a; color: #007017; border-radius: 4px; font-size: 13px; font-weight: 600;">✓ ' + res.data.message + ' (' + res.data.latency_ms + 'ms)</div>');
					} else {
						var err = (res && res.data && res.data.message) ? res.data.message : 'Unknown connection error.';
						$result.html('<div style="padding: 10px 14px; background: #fcf0f1; border-left: 4px solid #d63638; color: #b32d2e; border-radius: 4px; font-size: 13px;">✕ ' + err + '</div>');
					}
				},
				error: function(xhr, status, error) {
					$btn.prop('disabled', false).text('Test OpenAI Connection');
					$result.html('<div style="padding: 10px 14px; background: #fcf0f1; border-left: 4px solid #d63638; color: #b32d2e; border-radius: 4px; font-size: 13px;">✕ Network error: ' + error + '</div>');
				}
			});
		});

		// 4. Single Page Content Generation AJAX Handler.
		$(document).on('click', '.scp-generate-single-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var pageId = $btn.data('page-id');
			if (!pageId) return;

			var origText = $btn.text();
			$btn.prop('disabled', true).text('Generating...');

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_generate_page_content',
					nonce: nonce,
					page_id: pageId
				},
				success: function(res) {
					if (res && res.success) {
						alert('Content generated successfully! Reloading page to review.');
						window.location.reload();
					} else {
						var err = (res && res.data && res.data.message) ? res.data.message : 'Generation failed.';
						alert('Error: ' + err);
						$btn.prop('disabled', false).text(origText);
					}
				},
				error: function(xhr, status, error) {
					alert('Network error: ' + error);
					$btn.prop('disabled', false).text(origText);
				}
			});
		});

		// Helper: Update Dashboard Metric Counters
		function updateDashboardStats(stats) {
			if (!stats || typeof stats !== 'object') return;
			if (stats.total_pages !== undefined) $('#scp-stat-total-pages').text(Number(stats.total_pages).toLocaleString());
			if (stats.needs_review !== undefined) $('#scp-stat-needs-review').text(Number(stats.needs_review).toLocaleString());
			if (stats.approved !== undefined && stats.manually_edited !== undefined) {
				$('#scp-stat-approved').text(Number(stats.approved + stats.manually_edited).toLocaleString());
			}
			if (stats.total_tokens !== undefined) $('#scp-stat-total-tokens').text(Number(stats.total_tokens).toLocaleString());
			if (stats.not_generated !== undefined) {
				$('#scp-scope-missing-desc').text('(' + stats.not_generated + ' pages without generated content)');
			}
		}

		var isBulkPaused = false;
		var isBulkCancelled = false;
		var currentJobId = 0;

		// Reusable Persistent Bulk Job Runner
		function runBulkJob(jobDbId) {
			currentJobId = jobDbId;
			isBulkPaused = false;
			isBulkCancelled = false;

			var $btn = $('#scp-start-bulk-btn');
			var $progressBox = $('#scp-bulk-progress-box');
			var $pauseBtn = $('#scp-pause-bulk-btn');
			var $resumeBtn = $('#scp-resume-bulk-btn');
			var $cancelBtn = $('#scp-cancel-bulk-btn');
			var retries = 0;
			var maxRetries = 3;

			$btn.prop('disabled', true).text('Generating Content...');
			$progressBox.slideDown(200);
			$pauseBtn.show().prop('disabled', false);
			$resumeBtn.hide();
			$cancelBtn.show().prop('disabled', false);

			function processNextChunk() {
				if (isBulkPaused || isBulkCancelled) {
					return;
				}

				$.ajax({
					url: ajaxUrl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'scp_process_bulk_batch',
						nonce: nonce,
						job_db_id: currentJobId,
						batch_size: 1
					},
					success: function(batchRes) {
						if (isBulkPaused || isBulkCancelled) {
							return;
						}

						retries = 0; // Reset retry counter on success.
						if (!batchRes || !batchRes.success) {
							if (batchRes && batchRes.data && batchRes.data.is_cancelled) {
								$('#scp-bulk-status-heading').html('<span style="color: #d63638;">✕ Job Cancelled</span>');
								$btn.prop('disabled', false).text('Start Bulk Generation →');
								$pauseBtn.hide();
								$resumeBtn.hide();
								return;
							}

							if (batchRes && batchRes.data && batchRes.data.is_paused) {
								$('#scp-bulk-status-heading').html('<span style="color: #dba617;">⏸ Generation Paused</span>');
								$btn.prop('disabled', false).text('Resume Bulk Generation →');
								$pauseBtn.hide();
								$resumeBtn.show();
								return;
							}

							var err = (batchRes && batchRes.data && batchRes.data.error) ? batchRes.data.error : 'Generation Interrupted';
							$('#scp-bulk-status-heading').html('<span style="color: #d63638;">⚠️ ' + err + '</span>');
							$btn.prop('disabled', false).text('Resume Bulk Generation →');
							$pauseBtn.hide();
							$resumeBtn.show();
							return;
						}

						var bData = batchRes.data;
						var total = bData.total || 1;
						var processed = bData.processed || 0;
						var pct = Math.min(100, Math.round((processed / total) * 100));

						$('#scp-bulk-status-heading').text('⚡ Processing Bulk Generation (Job #' + currentJobId + ')...');
						$('#scp-bulk-progress-bar').css('width', pct + '%');
						$('#scp-bulk-progress-text').text(processed + ' / ' + total + ' pages processed (' + pct + '%)');
						$('#scp-bulk-success-text').text((bData.success_cnt || 0) + ' succeeded');
						$('#scp-bulk-failed-text').text((bData.failed_cnt || 0) + ' failed');

						if (bData.stats) {
							updateDashboardStats(bData.stats);
						}

						if (!bData.is_complete) {
							setTimeout(processNextChunk, 200);
						} else {
							$('#scp-bulk-status-heading').html('<span style="color: #00a32a;">✓ Generation Complete!</span>');
							$('#scp-bulk-progress-bar').css('width', '100%');
							$btn.prop('disabled', false).text('Start Bulk Generation →');
							$pauseBtn.hide();
							$resumeBtn.hide();
						}
					},
					error: function() {
						if (isBulkPaused || isBulkCancelled) {
							return;
						}

						if (retries < maxRetries) {
							retries++;
							$('#scp-bulk-status-heading').text('Retrying batch (' + retries + '/' + maxRetries + ')...');
							setTimeout(processNextChunk, 2000);
						} else {
							$('#scp-bulk-status-heading').html('<span style="color: #d63638;">Communication Error during Batch</span>');
							$btn.prop('disabled', false).text('Resume Bulk Generation →');
							$pauseBtn.hide();
							$resumeBtn.show();
						}
					}
				});
			}

			processNextChunk();
		}

		// Pause Button Click Handler
		$('#scp-pause-bulk-btn').on('click', function(e) {
			e.preventDefault();
			if (!currentJobId) return;

			isBulkPaused = true;
			var $btn = $(this);
			$btn.prop('disabled', true).text('Pausing...');

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_pause_bulk_generation',
					nonce: nonce,
					job_db_id: currentJobId
				},
				success: function() {
					$('#scp-bulk-status-heading').html('<span style="color: #dba617;">⏸ Bulk Generation Paused (Job #' + currentJobId + ')</span>');
					$('#scp-pause-bulk-btn').hide();
					$('#scp-resume-bulk-btn').show().prop('disabled', false);
					$('#scp-start-bulk-btn').prop('disabled', false).text('Resume Bulk Generation →');
				}
			});
		});

		// Resume Button Click Handler
		$('#scp-resume-bulk-btn').on('click', function(e) {
			e.preventDefault();
			if (!currentJobId) return;

			isBulkPaused = false;
			isBulkCancelled = false;

			var $btn = $(this);
			$btn.prop('disabled', true).text('Resuming...');

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_resume_bulk_generation',
					nonce: nonce,
					job_db_id: currentJobId
				},
				success: function() {
					$('#scp-resume-bulk-btn').hide();
					$('#scp-pause-bulk-btn').show().prop('disabled', false).text('⏸ Pause');
					$('#scp-start-bulk-btn').prop('disabled', true).text('Generating Content...');
					runBulkJob(currentJobId);
				}
			});
		});

		// Cancel Button Click Handler
		$('#scp-cancel-bulk-btn').on('click', function(e) {
			e.preventDefault();
			if (!currentJobId) return;

			if (!confirm('Are you sure you want to cancel this bulk generation job? Current progress will be saved, and ungenerated pages will stop.')) {
				return;
			}

			isBulkCancelled = true;
			isBulkPaused = false;

			var $btn = $(this);
			$btn.prop('disabled', true).text('Cancelling...');

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_cancel_bulk_generation',
					nonce: nonce,
					job_db_id: currentJobId
				},
				success: function(res) {
					$('#scp-bulk-status-heading').html('<span style="color: #d63638;">✕ Bulk Generation Cancelled (Job #' + currentJobId + ')</span>');
					$('#scp-pause-bulk-btn').hide();
					$('#scp-resume-bulk-btn').hide();
					$('#scp-cancel-bulk-btn').hide();
					$('#scp-start-bulk-btn').prop('disabled', false).text('Start Bulk Generation →');
					if (res && res.data && res.data.stats) {
						updateDashboardStats(res.data.stats);
					}
				}
			});
		});

		// Check for active job on page load and handle initial state.
		var initialActiveJobId = parseInt($('#scp-bulk-progress-box').data('active-job-id'), 10) || 0;
		var initialActiveStatus = $('#scp-bulk-progress-box').data('active-status') || '';

		if (initialActiveJobId > 0) {
			currentJobId = initialActiveJobId;
			if (initialActiveStatus === 'paused') {
				isBulkPaused = true;
				$('#scp-bulk-status-heading').html('<span style="color: #dba617;">⏸ Bulk Generation Paused (Job #' + initialActiveJobId + ')</span>');
				$('#scp-pause-bulk-btn').hide();
				$('#scp-resume-bulk-btn').show();
				$('#scp-start-bulk-btn').prop('disabled', false).text('Resume Bulk Generation →');
			} else {
				runBulkJob(initialActiveJobId);
			}
		}

		// 5. Bulk Generation Start Button.
		$('#scp-start-bulk-btn').on('click', function(e) {
			e.preventDefault();
			if (isBulkPaused && currentJobId > 0) {
				$('#scp-resume-bulk-btn').trigger('click');
				return;
			}

			var mode = $('input[name="scp_bulk_mode"]:checked').val() || 'missing_only';
			var $btn = $(this);

			if (!confirm('Start AI Content Generation for selected pages?')) {
				return;
			}

			$btn.prop('disabled', true).text('Initializing Job...');
			var $progressBox = $('#scp-bulk-progress-box');
			$progressBox.slideDown(200);

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_start_bulk_generation',
					nonce: nonce,
					generation_mode: mode
				},
				success: function(res) {
					if (!res || !res.success) {
						var err = (res && res.data && res.data.message) ? res.data.message : 'Failed to start job.';
						alert('Error: ' + err);
						$btn.prop('disabled', false).text('Start Bulk Generation →');
						return;
					}

					var jobDbId = res.data.job_db_id;
					runBulkJob(jobDbId);
				},
				error: function(xhr, status, error) {
					alert('Network error: ' + error);
					$btn.prop('disabled', false).text('Start Bulk Generation →');
				}
			});
		});

		// 6. Approve & Reject Handlers.
		$(document).on('click', '.scp-approve-btn', function(e) {
			e.preventDefault();
			var versionId = $(this).data('version-id');
			var notes = $('#scp-review-notes-input').val() || '';
			var $btn = $(this);

			$btn.prop('disabled', true).text('Approving...');
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_approve_version',
					nonce: nonce,
					version_id: versionId,
					notes: notes
				},
				success: function(res) {
					if (res && res.success) {
						alert(res.data.message || 'Content version approved!');
						window.location.reload();
					} else {
						alert('Error: ' + (res.data ? res.data.message : 'Approval failed.'));
						$btn.prop('disabled', false).text('✓ Approve Content');
					}
				},
				error: function(xhr, status, error) {
					alert('Network error: ' + error);
					$btn.prop('disabled', false).text('✓ Approve Content');
				}
			});
		});

		$(document).on('click', '.scp-reject-btn', function(e) {
			e.preventDefault();
			var versionId = $(this).data('version-id');
			var $btn = $(this);
			var reason = prompt('Please enter a rejection reason or feedback notes (optional):');
			if (reason === null) {
				return; // Cancelled
			}

			$btn.prop('disabled', true).text('Rejecting...');
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_reject_version',
					nonce: nonce,
					version_id: versionId,
					reason: reason
				},
				success: function(res) {
					if (res && res.success) {
						alert('Version marked as rejected.');
						window.location.reload();
					} else {
						alert('Error: ' + (res.data ? res.data.message : 'Rejection failed.'));
						$btn.prop('disabled', false).text('✕ Reject Content');
					}
				},
				error: function(xhr, status, error) {
					alert('Network error: ' + error);
					$btn.prop('disabled', false).text('✕ Reject Content');
				}
			});
		});

		// 7. Publishing & Unpublishing Handlers.
		$(document).on('click', '.scp-publish-btn', function(e) {
			e.preventDefault();
			var pageId = $(this).data('page-id');
			var versionId = $(this).data('version-id') || 0;
			var $btn = $(this);

			if (!confirm('Are you sure you want to publish this approved version to the live public page?')) {
				return;
			}

			$btn.prop('disabled', true).text('Publishing...');
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_publish_page',
					nonce: nonce,
					page_id: pageId,
					version_id: versionId
				},
				success: function(res) {
					if (res && res.success) {
						alert(res.data.message || 'Page published successfully!');
						window.location.reload();
					} else {
						var msg = (res.data && res.data.message) ? res.data.message : 'Publishing failed.';
						if (res.data && res.data.errors && res.data.errors.length) {
							msg += '\n- ' + res.data.errors.join('\n- ');
						}
						alert('Cannot Publish:\n' + msg);
						$btn.prop('disabled', false).text('🚀 Publish Live');
					}
				},
				error: function(xhr, status, error) {
					alert('Network error: ' + error);
					$btn.prop('disabled', false).text('🚀 Publish Live');
				}
			});
		});

		$(document).on('click', '.scp-unpublish-btn', function(e) {
			e.preventDefault();
			var pageId = $(this).data('page-id');
			var $btn = $(this);

			if (!confirm('Are you sure you want to unpublish this page? It will revert to draft.')) {
				return;
			}

			$btn.prop('disabled', true).text('Unpublishing...');
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_unpublish_page',
					nonce: nonce,
					page_id: pageId
				},
				success: function(res) {
					if (res && res.success) {
						alert(res.data.message || 'Page unpublished.');
						window.location.reload();
					} else {
						alert('Error: ' + (res.data ? res.data.message : 'Unpublishing failed.'));
						$btn.prop('disabled', false).text('↩ Unpublish');
					}
				}
			});
		});

		// 8. Restore Version Handler.
		$(document).on('click', '.scp-restore-btn', function(e) {
			e.preventDefault();
			var versionId = $(this).data('version-id');
			var $btn = $(this);

			if (!confirm('Restoring will create a NEW version (vN+1) cloned from this version. Proceed?')) {
				return;
			}

			$btn.prop('disabled', true).text('Restoring...');
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_restore_version',
					nonce: nonce,
					version_id: versionId,
					auto_approve: 1
				},
				success: function(res) {
					if (res && res.success) {
						alert(res.data.message || 'Version restored successfully!');
						window.location.reload();
					} else {
						alert('Error: ' + (res.data ? res.data.message : 'Restoration failed.'));
						$btn.prop('disabled', false).text('Restore');
					}
				}
			});
		});

		// 9. Save Review Notes Handler.
		$('#scp-save-notes-btn').on('click', function(e) {
			e.preventDefault();
			var versionId = $(this).data('version-id');
			var notes = $('#scp-review-notes-input').val();
			var $btn = $(this);

			$btn.prop('disabled', true).text('Saving...');
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_save_review_notes',
					nonce: nonce,
					version_id: versionId,
					notes: notes
				},
				success: function(res) {
					$btn.prop('disabled', false).text('Save Notes');
					if (res && res.success) {
						$('#scp-save-notes-msg').fadeIn().delay(2000).fadeOut();
					} else {
						alert('Failed to save notes.');
					}
				}
			});
		});

		// 10. Manual Content Editor Save.
		$('#scp-save-edits-btn, #scp-save-approve-btn').on('click', function(e) {
			e.preventDefault();
			var isApprove = $(this).attr('id') === 'scp-save-approve-btn';
			var $form = $('#scp-content-edit-form');
			var formData = $form.serializeArray();

			var dataObj = {
				action: 'scp_save_content_edits',
				nonce: nonce,
				approve_now: isApprove ? 1 : 0
			};

			$.each(formData, function() {
				dataObj[this.name] = this.value;
			});

			var $btn = $(this);
			var orig = $btn.text();
			$btn.prop('disabled', true).text('Saving...');

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: dataObj,
				success: function(res) {
					if (res && res.success) {
						alert(res.data.message);
						window.location.href = 'admin.php?page=seo-city-pages-ai-review';
					} else {
						var msg = (res && res.data && res.data.message) ? res.data.message : 'Failed to save.';
						if (res && res.data && res.data.errors) {
							msg += '\n- ' + res.data.errors.join('\n- ');
						}
						alert('Error: ' + msg);
						$btn.prop('disabled', false).text(orig);
					}
				},
				error: function(xhr, status, error) {
					alert('Network error: ' + error);
					$btn.prop('disabled', false).text(orig);
				}
			});
		});

		// 11. 1-Click Fast Actions (Approve All, Publish All, Approve & Publish All).
		$(document).on('click', '#scp-btn-approve-all', function(e) {
			e.preventDefault();
			var $btn = $(this);
			if (!confirm('Are you sure you want to APPROVE ALL pending AI-generated content versions across the entire site?')) {
				return;
			}

			$btn.prop('disabled', true).text('Approving All...');
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_approve_all',
					nonce: nonce
				},
				success: function(res) {
					if (res && res.success) {
						alert(res.data.message || 'All pending pages approved successfully!');
						window.location.reload();
					} else {
						alert('Error: ' + (res.data ? res.data.message : 'Approve all failed.'));
						$btn.prop('disabled', false).text('✓ Approve All Pending');
					}
				},
				error: function(xhr, status, error) {
					alert('Network error: ' + error);
					$btn.prop('disabled', false).text('✓ Approve All Pending');
				}
			});
		});

		$(document).on('click', '#scp-btn-publish-all', function(e) {
			e.preventDefault();
			var $btn = $(this);
			if (!confirm('Are you sure you want to PUBLISH ALL approved city pages to the live site and WordPress Pages?')) {
				return;
			}

			$btn.prop('disabled', true).text('Publishing All...');
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_publish_all',
					nonce: nonce
				},
				success: function(res) {
					if (res && res.success) {
						alert(res.data.message || 'All approved pages published live successfully!');
						window.location.reload();
					} else {
						var msg = (res.data && res.data.message) ? res.data.message : 'Publish all failed.';
						if (res.data && res.data.errors) {
							var errList = Object.values(res.data.errors);
							if (errList.length) {
								msg += '\n\nErrors encountered:\n- ' + errList.join('\n- ');
							}
						}
						alert(msg);
						window.location.reload();
					}
				},
				error: function(xhr, status, error) {
					alert('Network error: ' + error);
					$btn.prop('disabled', false).text('🚀 Publish All Approved');
				}
			});
		});

		$(document).on('click', '#scp-btn-approve-publish-all', function(e) {
			e.preventDefault();
			var $btn = $(this);
			if (!confirm('⚡ 1-Click Fast Action:\n\nThis will APPROVE ALL pending content and immediately PUBLISH ALL pages live to your website and WordPress Pages.\n\nProceed?')) {
				return;
			}

			$btn.prop('disabled', true).text('Processing Approvals & Publishing...');
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_approve_and_publish_all',
					nonce: nonce
				},
				success: function(res) {
					if (res && res.success) {
						alert(res.data.message || 'All pages approved and published live!');
						window.location.reload();
					} else {
						alert('Error: ' + (res.data ? res.data.message : 'Operation failed.'));
						$btn.prop('disabled', false).text('⚡ 1-Click Approve & Publish All');
					}
				},
				error: function(xhr, status, error) {
					alert('Network error: ' + error);
					$btn.prop('disabled', false).text('⚡ 1-Click Approve & Publish All');
				}
			});
		});

		// 12. Bulk Selection & Confirmation Modal.
		$('.scp-select-all-checkbox').on('change', function() {
			$('.scp-page-checkbox').prop('checked', $(this).prop('checked'));
		});

		$('#scp-apply-bulk-btn').on('click', function(e) {
			e.preventDefault();
			var action = $('#scp-bulk-action-select').val();
			var isSiteWide = ['approve_all', 'publish_all', 'approve_and_publish_all'].indexOf(action) !== -1;
			var selected = [];
			$('.scp-page-checkbox:checked').each(function() {
				selected.push($(this).val());
			});

			if (!action) {
				alert('Please select a bulk action from the dropdown.');
				return;
			}
			if (!isSiteWide && selected.length === 0) {
				alert('Please select one or more pages using the checkboxes, or choose an Entire Site action.');
				return;
			}

			// Configure Modal Text
			var actionLabels = {
				'approve': 'Approve Selected Content',
				'reject': 'Reject Selected Content',
				'publish': 'Publish Live Selected Pages',
				'unpublish': 'Unpublish Selected Pages',
				'approve_all': 'Approve All Pending Content (Entire Site)',
				'publish_all': 'Publish All Approved Pages (Entire Site)',
				'approve_and_publish_all': 'Approve & Publish All Generated Pages (Entire Site)'
			};
			var modalTitle = actionLabels[action] || 'Confirm Bulk Action';
			var modalMsg = '';

			if (isSiteWide) {
				modalMsg = 'You are about to execute <strong>' + modalTitle + '</strong> across all eligible pages on the site.';
			} else {
				modalMsg = 'You are about to <strong>' + action.toUpperCase() + '</strong> ' + selected.length + ' landing page(s).';
			}

			if (action === 'publish' || action === 'publish_all' || action === 'approve_and_publish_all') {
				modalMsg += '<br><br><span style="color: #b26b00;">⚠️ Pages will be published live to public URLs and automatically synchronized into standard WordPress Pages.</span>';
			}

			$('#scp-modal-title').text(modalTitle);
			$('#scp-modal-message').html(modalMsg);

			if (action === 'reject') {
				$('#scp-modal-reject-reason-wrap').show();
			} else {
				$('#scp-modal-reject-reason-wrap').hide();
			}

			$('#scp-bulk-confirm-modal').css('display', 'flex');
		});

		$('#scp-modal-cancel-btn').on('click', function() {
			$('#scp-bulk-confirm-modal').hide();
		});

		$('#scp-modal-proceed-btn').on('click', function() {
			var action = $('#scp-bulk-action-select').val();
			var reason = $('#scp-modal-reject-reason').val() || '';
			var selected = [];
			$('.scp-page-checkbox:checked').each(function() {
				selected.push($(this).val());
			});

			var $btn = $(this);
			$btn.prop('disabled', true).text('Processing...');

			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'scp_bulk_workflow_action',
					nonce: nonce,
					bulk_action: action,
					page_ids: selected,
					reason: reason
				},
				success: function(res) {
					$('#scp-bulk-confirm-modal').hide();
					if (res && res.success) {
						alert(res.data.message || 'Bulk action completed successfully.');
						window.location.reload();
					} else {
						var msg = (res.data && res.data.message) ? res.data.message : 'Bulk action failed.';
						if (res.data && res.data.errors) {
							var errList = Object.values(res.data.errors);
							if (errList.length) {
								msg += '\n\nErrors encountered:\n- ' + errList.join('\n- ');
							}
						}
						alert(msg);
						window.location.reload();
					}
				},
				error: function(xhr, status, error) {
					$('#scp-bulk-confirm-modal').hide();
					alert('Network error during bulk execution: ' + error);
					$btn.prop('disabled', false).text('Proceed');
				}
			});
		});
	});
})(jQuery);
