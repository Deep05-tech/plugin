<?php
/**
 * Bulk Operations Admin View.
 *
 * @package SeoCityPages\Admin
 */

use SeoCityPages\Jobs\Bulk_Job_Repository;
use SeoCityPages\Security\Security;
use SeoCityPages\Admin\Bulk_Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nonce = Security::create_nonce( Bulk_Controller::NONCE_ACTION );
?>
<div class="wrap seo-city-pages-wrap" style="max-width: 1300px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">
	<div style="background: #ffffff; border-radius: 12px; padding: 24px 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 24px;">
		<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; padding-bottom: 20px;">
			<div>
				<h1 style="font-size: 26px; font-weight: 700; color: #1a202c; margin: 0 0 6px 0;">⚡ Bulk Operations Center</h1>
				<p style="color: #718096; margin: 0; font-size: 14px;">High-scale batch creation, AI generation, CSV pipelines, and queue management (10 to 50,000+ pages).</p>
			</div>
			<div style="display: flex; gap: 12px;">
				<span style="background: #ebf8ff; color: #2b6cb0; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;">DB v0.8.0</span>
				<span style="background: #f0fff4; color: #276749; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;">Queue Active</span>
			</div>
		</div>

		<!-- Stats Overview -->
		<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-top: 20px;">
			<div style="background: #f7fafc; padding: 16px; border-radius: 8px; border-left: 4px solid #4a5568;">
				<div style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600;">Total Pages</div>
				<div style="font-size: 22px; font-weight: 700; color: #2d3748; margin-top: 4px;"><?php echo number_format( $page_stats['total'] ?? 0 ); ?></div>
			</div>
			<div style="background: #f7fafc; padding: 16px; border-radius: 8px; border-left: 4px solid #38a169;">
				<div style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600;">Published Live</div>
				<div style="font-size: 22px; font-weight: 700; color: #38a169; margin-top: 4px;"><?php echo number_format( $page_stats['published'] ?? 0 ); ?></div>
			</div>
			<div style="background: #f7fafc; padding: 16px; border-radius: 8px; border-left: 4px solid #d69e2e;">
				<div style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600;">Needs Review</div>
				<div style="font-size: 22px; font-weight: 700; color: #d69e2e; margin-top: 4px;"><?php echo number_format( $page_stats['needs_review'] ?? 0 ); ?></div>
			</div>
			<div style="background: #f7fafc; padding: 16px; border-radius: 8px; border-left: 4px solid #3182ce;">
				<div style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600;">Approved</div>
				<div style="font-size: 22px; font-weight: 700; color: #3182ce; margin-top: 4px;"><?php echo number_format( $page_stats['approved'] ?? 0 ); ?></div>
			</div>
			<div style="background: #f7fafc; padding: 16px; border-radius: 8px; border-left: 4px solid #a0aec0;">
				<div style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600;">Draft Pages</div>
				<div style="font-size: 22px; font-weight: 700; color: #718096; margin-top: 4px;"><?php echo number_format( $page_stats['draft'] ?? 0 ); ?></div>
			</div>
		</div>
	</div>

	<!-- Navigation Tabs -->
	<div style="display: flex; gap: 8px; margin-bottom: 20px;">
		<button type="button" class="scp-tab-btn active" onclick="switchTab('wizard')" style="padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; background: #3182ce; color: #fff;">🪄 Bulk Wizard</button>
		<button type="button" class="scp-tab-btn" onclick="switchTab('csv-import')" style="padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; background: #edf2f7; color: #4a5568;">📥 CSV Import Hub</button>
		<button type="button" class="scp-tab-btn" onclick="switchTab('csv-export')" style="padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; background: #edf2f7; color: #4a5568;">📤 CSV Export Hub</button>
		<button type="button" class="scp-tab-btn" onclick="switchTab('jobs')" style="padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; background: #edf2f7; color: #4a5568;">📊 Job Queue Center</button>
		<button type="button" class="scp-tab-btn" onclick="switchTab('rollback')" style="padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; background: #edf2f7; color: #4a5568;">🔄 Rollback Center</button>
	</div>

	<!-- TAB 1: BULK WIZARD -->
	<div id="tab-wizard" class="scp-tab-content" style="background: #fff; padding: 28px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
		<h2 style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;">Guided Bulk Operations Wizard</h2>
		<p style="color: #718096; margin-bottom: 24px;">Execute bulk page creation, AI generation, validation, approvals, or publishing across selected locations and services.</p>

		<form id="scp-wizard-form" onsubmit="event.preventDefault(); startWizardOperation();">
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
				<div>
					<label style="display: block; font-weight: 600; margin-bottom: 8px;">1. Choose Operation</label>
					<select id="wizard-op-type" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e0;">
						<option value="create_pages">Create SEO City Pages (Combinations)</option>
						<option value="generate_content">Generate AI Content (gpt-4o-mini)</option>
						<option value="generate_seo">Generate SEO Metadata</option>
						<option value="validate">Bulk Validate Data & Quality</option>
						<option value="approve">Bulk Approve Eligible Content</option>
						<option value="publish">Bulk Publish Approved Pages</option>
					</select>
				</div>
				<div>
					<label style="display: block; font-weight: 600; margin-bottom: 8px;">2. Execution Mode</label>
					<div style="padding: 10px; background: #f7fafc; border-radius: 6px; border: 1px solid #e2e8f0;">
						<label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: #2b6cb0;">
							<input type="checkbox" id="wizard-dry-run" checked />
							🧪 DRY RUN SIMULATION (Validate & Preview Impact without changing DB)
						</label>
					</div>
				</div>
			</div>

			<div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end;">
				<button type="submit" style="background: #3182ce; color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 700; cursor: pointer;">Launch Operation &rarr;</button>
			</div>
		</form>
	</div>

	<!-- TAB 2: CSV IMPORT HUB -->
	<div id="tab-csv-import" class="scp-tab-content" style="display: none; background: #fff; padding: 28px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
		<h2 style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;">CSV Ingestion & Validation Pipeline</h2>
		<p style="color: #718096; margin-bottom: 24px;">Upload city/service datasets up to 50,000+ rows. Neutralizes formula injection, performs structural parsing, and previews data quality warnings before committing.</p>

		<div style="border: 2px dashed #cbd5e0; padding: 32px; border-radius: 8px; text-align: center; background: #f7fafc; margin-bottom: 20px;">
			<input type="file" id="csv-upload-input" accept=".csv" style="margin-bottom: 12px;" />
			<div>
				<button type="button" onclick="uploadAndPreviewCSV()" style="background: #2b6cb0; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Parse & Preview CSV</button>
			</div>
		</div>

		<!-- Preview Result Container -->
		<div id="csv-preview-container" style="display: none;"></div>
	</div>

	<!-- TAB 3: CSV EXPORT HUB -->
	<div id="tab-csv-export" class="scp-tab-content" style="display: none; background: #fff; padding: 28px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
		<h2 style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;">Streamed Memory-Safe CSV Export</h2>
		<p style="color: #718096; margin-bottom: 24px;">Export up to 50,000+ records via streamed chunking. Automatically deactivates formula injection for spreadsheet compatibility.</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<input type="hidden" name="action" value="seo_city_pages_bulk_export_download" />
			<input type="hidden" name="seo_city_pages_nonce" value="<?php echo esc_attr( $nonce ); ?>" />

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
				<div>
					<label style="display: block; font-weight: 600; margin-bottom: 8px;">Select Dataset</label>
					<select name="export_type" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e0;">
						<option value="city_pages">SEO City Pages (Full Metadata)</option>
						<option value="locations">Locations (Countries, States, Cities)</option>
						<option value="services">Services Catalog</option>
						<option value="content_versions">Content Version History</option>
					</select>
				</div>
				<div>
					<label style="display: block; font-weight: 600; margin-bottom: 8px;">Status Filter</label>
					<select name="status" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e0;">
						<option value="">All Statuses</option>
						<option value="published">Published Live</option>
						<option value="draft">Draft</option>
					</select>
				</div>
			</div>

			<button type="submit" style="background: #38a169; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer;">📥 Download CSV Export</button>
		</form>
	</div>

	<!-- TAB 4: JOB QUEUE CENTER -->
	<div id="tab-jobs" class="scp-tab-content" style="display: none; background: #fff; padding: 28px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
		<h2 style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;">Asynchronous Job Queue & History</h2>
		<p style="color: #718096; margin-bottom: 24px;">Monitor live progress, active worker locks, pause/resume state, and retry failed operations.</p>

		<div id="active-job-progress-box" style="display: none; background: #ebf8ff; border: 1px solid #bee3f8; padding: 20px; border-radius: 8px; margin-bottom: 24px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
				<h3 id="active-job-title" style="margin: 0; font-size: 16px; color: #2b6cb0;">Processing Job...</h3>
				<div style="display: flex; gap: 8px;">
					<button type="button" id="btn-job-pause" onclick="controlActiveJob('pause')" style="padding: 6px 12px; background: #ecc94b; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">Pause</button>
					<button type="button" id="btn-job-resume" onclick="controlActiveJob('resume')" style="display: none; padding: 6px 12px; background: #48bb78; color: #fff; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">Resume</button>
					<button type="button" id="btn-job-cancel" onclick="controlActiveJob('cancel')" style="padding: 6px 12px; background: #e53e3e; color: #fff; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">Cancel</button>
				</div>
			</div>
			<div style="background: #edf2f7; height: 16px; border-radius: 8px; overflow: hidden; margin-bottom: 8px;">
				<div id="active-job-bar" style="background: #3182ce; width: 0%; height: 100%; transition: width 0.3s ease;"></div>
			</div>
			<div style="display: flex; justify-content: space-between; font-size: 13px; color: #4a5568;">
				<span id="active-job-status-text">Pending...</span>
				<span id="active-job-counts">0 / 0</span>
			</div>
		</div>

		<!-- Recent Jobs Table -->
		<table class="wp-list-table widefat fixed striped" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
			<thead>
				<tr>
					<th style="font-weight: 700;">Job ID</th>
					<th style="font-weight: 700;">Type</th>
					<th style="font-weight: 700;">Status</th>
					<th style="font-weight: 700;">Total</th>
					<th style="font-weight: 700;">Completed</th>
					<th style="font-weight: 700;">Failed</th>
					<th style="font-weight: 700;">Date</th>
					<th style="font-weight: 700;">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $recent_jobs ) ) : ?>
					<tr><td colspan="8" style="text-align: center; color: #a0aec0; padding: 20px;">No bulk jobs recorded yet.</td></tr>
				<?php else : ?>
					<?php foreach ( $recent_jobs as $j ) : ?>
						<tr>
							<td><code><?php echo esc_html( substr( $j->job_id, 0, 13 ) . '...' ); ?></code></td>
							<td><strong><?php echo esc_html( $j->job_type ); ?></strong></td>
							<td>
								<span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; background: <?php echo 'completed' === $j->status ? '#c6f6d5; color: #22543d;' : ( 'failed' === $j->status ? '#fed7d7; color: #742a2a;' : '#edf2f7; color: #4a5568;' ); ?>">
									<?php echo esc_html( $j->status ); ?>
								</span>
							</td>
							<td><?php echo (int) $j->total_items; ?></td>
							<td style="color: #38a169; font-weight: 600;"><?php echo (int) $j->completed_items; ?></td>
							<td style="color: #e53e3e; font-weight: 600;"><?php echo (int) $j->failed_items; ?></td>
							<td><?php echo esc_html( $j->created_at ); ?></td>
							<td>
								<?php if ( (int) $j->failed_items > 0 ) : ?>
									<button type="button" onclick="retryJob('<?php echo esc_js( $j->job_id ); ?>')" class="button button-small">Retry Failed</button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- TAB 5: ROLLBACK CENTER -->
	<div id="tab-rollback" class="scp-tab-content" style="display: none; background: #fff; padding: 28px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
		<h2 style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;">Safe Job Rollback & Invalidation Protection</h2>
		<p style="color: #718096; margin-bottom: 24px;">Revert creation operations safely. Any page that has subsequently been approved, manually edited, or published is automatically protected and flagged for review.</p>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
			<div>
				<label style="display: block; font-weight: 600; margin-bottom: 8px;">Select Target Creation Job</label>
				<select id="rollback-target-job" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e0;">
					<?php foreach ( $recent_jobs as $j ) : ?>
						<?php if ( in_array( $j->job_type, [ Bulk_Job_Repository::TYPE_BULK_CREATE, Bulk_Job_Repository::TYPE_CSV_IMPORT ], true ) ) : ?>
							<option value="<?php echo esc_attr( $j->job_id ); ?>"><?php echo esc_html( "{$j->job_type} ({$j->job_id}) - {$j->created_at}" ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label style="display: block; font-weight: 600; margin-bottom: 8px;">Rollback Safety Mode</label>
				<label style="display: inline-flex; align-items: center; gap: 8px; margin-top: 10px; font-weight: 600; color: #2b6cb0;">
					<input type="checkbox" id="rollback-dry-run" checked />
					🧪 Dry Run Simulation (Inspect safe vs protected pages without deleting)
				</label>
			</div>
		</div>

		<div style="margin-top: 24px;">
			<button type="button" onclick="startRollbackOperation()" style="background: #e53e3e; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer;">Execute Rollback &rarr;</button>
		</div>
	</div>
</div>

<script>
let currentActiveJobId = null;
let pollTimer = null;
const bulkNonce = "<?php echo esc_js( $nonce ); ?>";

function switchTab(tabId) {
	document.querySelectorAll('.scp-tab-content').forEach(el => el.style.display = 'none');
	document.querySelectorAll('.scp-tab-btn').forEach(el => {
		el.style.background = '#edf2f7';
		el.style.color = '#4a5568';
	});
	const target = document.getElementById('tab-' + tabId);
	if (target) target.style.display = 'block';
	event.target.style.background = '#3182ce';
	event.target.style.color = '#fff';
}

function uploadAndPreviewCSV() {
	const fileInput = document.getElementById('csv-upload-input');
	if (!fileInput.files || !fileInput.files[0]) {
		alert('Please select a CSV file first.');
		return;
	}

	const formData = new FormData();
	formData.append('action', 'seo_city_pages_csv_preview');
	formData.append('seo_city_pages_nonce', bulkNonce);
	formData.append('csv_file', fileInput.files[0]);

	const container = document.getElementById('csv-preview-container');
	container.style.display = 'block';
	container.innerHTML = '<div style="padding: 20px; text-align: center; color: #718096;">Parsing & analyzing CSV structure...</div>';

	fetch(ajaxurl, { method: 'POST', body: formData })
		.then(r => r.json())
		.then(data => {
			if (!data.success) {
				container.innerHTML = `<div style="padding: 16px; background: #fed7d7; color: #742a2a; border-radius: 6px;">❌ Error: ${data.data?.error || data.data?.message || 'Failed to parse CSV'}</div>`;
				return;
			}
			const rep = data.data;
			let warningsHtml = '';
			if (rep.data_warnings && rep.data_warnings.length > 0) {
				warningsHtml = `
					<div style="background: #fffaf0; border-left: 4px solid #dd6b20; padding: 12px; margin-bottom: 16px; border-radius: 4px;">
						<strong>⚠️ Data Quality Warnings (${rep.warnings_count}):</strong> Suspicious values flagged for administrator review (e.g. Unassigned, Unknown, Out of State). These will not be rejected automatically.
					</div>
				`;
			}

			container.innerHTML = `
				<div style="background: #f7fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
					<h3 style="margin-top: 0;">CSV Ingestion Preview</h3>
					${warningsHtml}
					<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;">
						<div style="background: #fff; padding: 10px; border-radius: 6px; text-align: center;"><strong>${rep.total_rows}</strong> Total Rows</div>
						<div style="background: #fff; padding: 10px; border-radius: 6px; text-align: center; color: #38a169;"><strong>${rep.valid_count}</strong> Valid Rows</div>
						<div style="background: #fff; padding: 10px; border-radius: 6px; text-align: center; color: #e53e3e;"><strong>${rep.invalid_count}</strong> Invalid</div>
						<div style="background: #fff; padding: 10px; border-radius: 6px; text-align: center; color: #d69e2e;"><strong>${rep.duplicate_count}</strong> Duplicates</div>
					</div>
					<div style="display: flex; justify-content: flex-end; gap: 12px;">
						<button type="button" onclick="confirmCSVImport('${rep.preview_token}', false)" style="background: #38a169; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; cursor: pointer;">Confirm & Ingest ${rep.valid_count} Rows</button>
					</div>
				</div>
			`;
		});
}

function confirmCSVImport(token, dryRun) {
	const formData = new FormData();
	formData.append('action', 'seo_city_pages_csv_import_queue');
	formData.append('seo_city_pages_nonce', bulkNonce);
	formData.append('preview_token', token);
	formData.append('is_dry_run', dryRun ? 1 : 0);

	fetch(ajaxurl, { method: 'POST', body: formData })
		.then(r => r.json())
		.then(data => {
			if (data.success) {
				startPollingJob(data.data.job_id);
			} else {
				alert('Failed: ' + (data.data?.message || 'Error'));
			}
		});
}

function startWizardOperation() {
	const opType = document.getElementById('wizard-op-type').value;
	const isDryRun = document.getElementById('wizard-dry-run').checked;

	let action = 'seo_city_pages_bulk_validate_queue';
	if (opType === 'approve') action = 'seo_city_pages_bulk_approve_queue';
	else if (opType === 'publish') action = 'seo_city_pages_bulk_publish_queue';

	const formData = new FormData();
	formData.append('action', action);
	formData.append('seo_city_pages_nonce', bulkNonce);
	formData.append('is_dry_run', isDryRun ? 1 : 0);

	fetch(ajaxurl, { method: 'POST', body: formData })
		.then(r => r.json())
		.then(data => {
			if (data.success) {
				startPollingJob(data.data.job_id);
			} else {
				alert('Failed: ' + (data.data?.message || 'Error'));
			}
		});
}

function startPollingJob(jobId) {
	currentActiveJobId = jobId;
	switchTab('jobs');
	const box = document.getElementById('active-job-progress-box');
	box.style.display = 'block';

	if (pollTimer) clearInterval(pollTimer);
	pollTimer = setInterval(pollJobTick, 1500);
	pollJobTick();
}

function pollJobTick() {
	if (!currentActiveJobId) return;

	const formData = new FormData();
	formData.append('action', 'seo_city_pages_bulk_job_poll');
	formData.append('seo_city_pages_nonce', bulkNonce);
	formData.append('job_id', currentActiveJobId);
	formData.append('batch_size', 25);

	fetch(ajaxurl, { method: 'POST', body: formData })
		.then(r => r.json())
		.then(data => {
			if (data.success) {
				const p = data.data;
				document.getElementById('active-job-bar').style.width = p.percentage + '%';
				document.getElementById('active-job-counts').innerText = `${p.completed + p.failed + p.skipped} / ${p.total} (${p.percentage}%)`;
				document.getElementById('active-job-status-text').innerText = `Status: ${p.status} | Completed: ${p.completed}, Failed: ${p.failed}, Skipped: ${p.skipped}`;

				if (p.is_finished) {
					clearInterval(pollTimer);
					document.getElementById('active-job-title').innerText = '✅ Job Finished (' + p.status + ')';
				}
			}
		});
}

function controlActiveJob(ctrl) {
	if (!currentActiveJobId) return;
	const formData = new FormData();
	formData.append('action', 'seo_city_pages_bulk_job_control');
	formData.append('seo_city_pages_nonce', bulkNonce);
	formData.append('job_id', currentActiveJobId);
	formData.append('control_action', ctrl);

	fetch(ajaxurl, { method: 'POST', body: formData })
		.then(r => r.json())
		.then(data => {
			if (ctrl === 'pause') {
				document.getElementById('btn-job-pause').style.display = 'none';
				document.getElementById('btn-job-resume').style.display = 'inline-block';
			} else if (ctrl === 'resume') {
				document.getElementById('btn-job-pause').style.display = 'inline-block';
				document.getElementById('btn-job-resume').style.display = 'none';
			}
		});
}
</script>
