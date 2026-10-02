<?php
/**
 * Quality Dashboard Template.
 *
 * @package SeoCityPages\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats     = $stats ?? \SeoCityPages\Database\Quality_Repository::get_overview_stats();
$gate_mode = $gate_mode ?? \SeoCityPages\Quality\Quality_Gate_Manager::get_gate_mode();
$high_risk = $high_risk ?? \SeoCityPages\Database\Quality_Repository::get_high_risk_pages( 25, 0 );
?>
<div class="wrap seo-city-pages-wrap quality-dashboard-wrap">
	<div class="scp-header">
		<div class="scp-header-left">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-shield-alt" style="font-size:28px; width:28px; height:28px; vertical-align:middle; margin-right:8px; color:#2271b1;"></span>
				<?php esc_html_e( 'Quality Dashboard & Cannibalization Controls', 'seo-city-pages' ); ?>
			</h1>
			<p class="scp-subtitle"><?php esc_html_e( 'Evaluate content uniqueness, prevent search-intent cannibalization, and enforce pre-publication quality gates.', 'seo-city-pages' ); ?></p>
		</div>
		<div class="scp-header-actions">
			<button type="button" class="button button-primary" id="scp-btn-trigger-bulk-scan">
				<span class="dashicons dashicons-update" style="vertical-align:middle; margin-top:-2px;"></span>
				<?php esc_html_e( 'Run Quality Scan', 'seo-city-pages' ); ?>
			</button>
		</div>
	</div>

	<!-- Top Notice / Status Bar -->
	<div class="scp-notice scp-notice-info" style="margin-top: 15px; margin-bottom: 20px; background:#f0f6fc; border-left:4px solid #72aee6; padding:12px 16px;">
		<strong><?php esc_html_e( 'Internal Heuristic Notice:', 'seo-city-pages' ); ?></strong>
		<?php esc_html_e( 'All scores represent internal content completeness and uniqueness heuristics. The system does not predict or guarantee search engine rankings.', 'seo-city-pages' ); ?>
	</div>

	<!-- KPI Overview Cards -->
	<div class="scp-kpi-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom: 25px;">
		<div class="scp-card scp-kpi-card" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:15px; text-align:center;">
			<div style="font-size:12px; font-weight:600; text-transform:uppercase; color:#646970;"><?php esc_html_e( 'Average Quality Score', 'seo-city-pages' ); ?></div>
			<div style="font-size:32px; font-weight:700; color:<?php echo $stats['avg_score'] >= 75 ? '#008a20' : ( $stats['avg_score'] >= 50 ? '#dba617' : '#d63638' ); ?>; margin:8px 0;">
				<?php echo esc_html( $stats['avg_score'] ); ?><span style="font-size:16px;">/100</span>
			</div>
			<div style="font-size:12px; color:#50575e;"><?php echo esc_html( $stats['total_evaluated'] ); ?> of <?php echo esc_html( $stats['total_pages'] ); ?> pages evaluated</div>
		</div>

		<div class="scp-card scp-kpi-card" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:15px; text-align:center;">
			<div style="font-size:12px; font-weight:600; text-transform:uppercase; color:#646970;"><?php esc_html_e( 'Passing Pages', 'seo-city-pages' ); ?></div>
			<div style="font-size:32px; font-weight:700; color:#008a20; margin:8px 0;"><?php echo esc_html( $stats['pass_count'] ); ?></div>
			<div style="font-size:12px; color:#50575e;"><?php esc_html_e( 'Score >= 85 & 0 Critical Issues', 'seo-city-pages' ); ?></div>
		</div>

		<div class="scp-card scp-kpi-card" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:15px; text-align:center;">
			<div style="font-size:12px; font-weight:600; text-transform:uppercase; color:#646970;"><?php esc_html_e( 'Warnings / Review', 'seo-city-pages' ); ?></div>
			<div style="font-size:32px; font-weight:700; color:#dba617; margin:8px 0;">
				<?php echo esc_html( $stats['warning_count'] + $stats['needs_review_count'] ); ?>
			</div>
			<div style="font-size:12px; color:#50575e;"><?php echo esc_html( $stats['warning_count'] ); ?> warnings, <?php echo esc_html( $stats['needs_review_count'] ); ?> in review</div>
		</div>

		<div class="scp-card scp-kpi-card" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:15px; text-align:center;">
			<div style="font-size:12px; font-weight:600; text-transform:uppercase; color:#646970;"><?php esc_html_e( 'Critical Gate Failures', 'seo-city-pages' ); ?></div>
			<div style="font-size:32px; font-weight:700; color:#d63638; margin:8px 0;"><?php echo esc_html( $stats['fail_count'] ); ?></div>
			<div style="font-size:12px; color:#50575e;"><?php esc_html_e( 'Publication Blocked', 'seo-city-pages' ); ?></div>
		</div>

		<div class="scp-card scp-kpi-card" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:15px; text-align:center;">
			<div style="font-size:12px; font-weight:600; text-transform:uppercase; color:#646970;"><?php esc_html_e( 'Overlap & Similarity', 'seo-city-pages' ); ?></div>
			<div style="font-size:32px; font-weight:700; color:#2271b1; margin:8px 0;">
				<?php echo esc_html( $stats['high_similarity_count'] + $stats['cannibalization_count'] ); ?>
			</div>
			<div style="font-size:12px; color:#50575e;"><?php echo esc_html( $stats['high_similarity_count'] ); ?> similar, <?php echo esc_html( $stats['cannibalization_count'] ); ?> cannibalizing</div>
		</div>
	</div>

	<!-- Controls & Settings Grid -->
	<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:25px;">
		<!-- Quality Gate Policy -->
		<div class="scp-card" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:20px;">
			<h2 style="margin-top:0; font-size:16px; border-bottom:1px solid #f0f0f1; padding-bottom:10px;">
				<span class="dashicons dashicons-lock" style="color:#2271b1;"></span>
				<?php esc_html_e( 'Pre-Publication Quality Gate Policy', 'seo-city-pages' ); ?>
			</h2>
			<p style="color:#50575e; font-size:13px; line-height:1.5;">
				<?php esc_html_e( 'Control what happens when an administrator or automated queue attempts to publish pages that contain critical quality issues or fail uniqueness standards.', 'seo-city-pages' ); ?>
			</p>
			<form id="scp-quality-gate-form" style="margin-top:15px;">
				<div style="margin-bottom:10px;">
					<label style="font-weight:600; display:block; margin-bottom:5px;">
						<input type="radio" name="gate_mode" value="block_critical" <?php checked( $gate_mode, 'block_critical' ); ?>>
						<?php esc_html_e( 'Block Critical (Recommended)', 'seo-city-pages' ); ?>
					</label>
					<span style="font-size:12px; color:#646970; margin-left:24px; display:block;">
						<?php esc_html_e( 'Prevents publishing if page has unresolved placeholders, thin content (<300 words), or score < 50.', 'seo-city-pages' ); ?>
					</span>
				</div>
				<div style="margin-bottom:10px;">
					<label style="font-weight:600; display:block; margin-bottom:5px;">
						<input type="radio" name="gate_mode" value="warnings_only" <?php checked( $gate_mode, 'warnings_only' ); ?>>
						<?php esc_html_e( 'Warnings Only', 'seo-city-pages' ); ?>
					</label>
					<span style="font-size:12px; color:#646970; margin-left:24px; display:block;">
						<?php esc_html_e( 'Displays warnings and suggestions but allows publishing to proceed.', 'seo-city-pages' ); ?>
					</span>
				</div>
				<div style="margin-bottom:15px;">
					<label style="font-weight:600; display:block; margin-bottom:5px;">
						<input type="radio" name="gate_mode" value="off" <?php checked( $gate_mode, 'off' ); ?>>
						<?php esc_html_e( 'Off (Advisory only)', 'seo-city-pages' ); ?>
					</label>
					<span style="font-size:12px; color:#646970; margin-left:24px; display:block;">
						<?php esc_html_e( 'Disables pre-publication quality checks entirely.', 'seo-city-pages' ); ?>
					</span>
				</div>
				<button type="button" class="button button-secondary" id="scp-btn-save-gate-mode">
					<?php esc_html_e( 'Update Policy', 'seo-city-pages' ); ?>
				</button>
				<span id="scp-gate-save-status" style="margin-left:10px; font-weight:600;"></span>
			</form>
		</div>

		<!-- Scan Queue Trigger -->
		<div class="scp-card" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:20px;">
			<h2 style="margin-top:0; font-size:16px; border-bottom:1px solid #f0f0f1; padding-bottom:10px;">
				<span class="dashicons dashicons-search" style="color:#2271b1;"></span>
				<?php esc_html_e( 'Bulk Scan & Similarity Indexing', 'seo-city-pages' ); ?>
			</h2>
			<p style="color:#50575e; font-size:13px; line-height:1.5;">
				<?php esc_html_e( 'Run deterministic 12-dimension quality scans across your catalog. Scans run in partitioned background batches without blocking PHP requests.', 'seo-city-pages' ); ?>
			</p>
			<div style="margin-top:15px; display:flex; gap:10px; flex-wrap:wrap;">
				<button type="button" class="button button-secondary scp-btn-bulk-scan-scope" data-scope="unscanned">
					<?php esc_html_e( 'Scan Unscanned Pages', 'seo-city-pages' ); ?> (<?php echo esc_html( $stats['unscanned_pages'] ); ?>)
				</button>
				<button type="button" class="button button-secondary scp-btn-bulk-scan-scope" data-scope="stale">
					<?php esc_html_e( 'Scan Stale Pages', 'seo-city-pages' ); ?> (<?php echo esc_html( $stats['stale_count'] ); ?>)
				</button>
				<button type="button" class="button button-secondary scp-btn-bulk-scan-scope" data-scope="all">
					<?php esc_html_e( 'Full Catalog Scan', 'seo-city-pages' ); ?> (<?php echo esc_html( $stats['total_pages'] ); ?>)
				</button>
			</div>
			<div id="scp-scan-trigger-feedback" style="margin-top:15px; font-size:13px;"></div>
		</div>
	</div>

	<!-- High Risk / Attention Required Table -->
	<div class="scp-card" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:20px;">
		<h2 style="margin-top:0; font-size:16px; border-bottom:1px solid #f0f0f1; padding-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
			<span>
				<span class="dashicons dashicons-warning" style="color:#dba617;"></span>
				<?php esc_html_e( 'High Risk & Attention Required Pages', 'seo-city-pages' ); ?>
			</span>
			<span style="font-size:12px; font-weight:normal; color:#646970;">
				<?php printf( esc_html__( 'Showing top %d pages requiring review', 'seo-city-pages' ), count( $high_risk ) ); ?>
			</span>
		</h2>

		<?php if ( empty( $high_risk ) ) : ?>
			<p style="color:#008a20; padding:20px 0; text-align:center;">
				<span class="dashicons dashicons-yes-alt" style="font-size:24px; vertical-align:middle;"></span>
				<?php esc_html_e( 'No critical quality issues detected. All evaluated pages meet current quality criteria.', 'seo-city-pages' ); ?>
			</p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:60px;"><?php esc_html_e( 'Score', 'seo-city-pages' ); ?></th>
						<th style="width:110px;"><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></th>
						<th><?php esc_html_e( 'City Page', 'seo-city-pages' ); ?></th>
						<th><?php esc_html_e( 'Detected Issues', 'seo-city-pages' ); ?></th>
						<th style="width:130px;"><?php esc_html_e( 'Similarity / Overlap', 'seo-city-pages' ); ?></th>
						<th style="width:180px; text-align:right;"><?php esc_html_e( 'Actions', 'seo-city-pages' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $high_risk as $row ) : 
						$score_color = $row['quality_score'] >= 85 ? '#008a20' : ( $row['quality_score'] >= 50 ? '#dba617' : '#d63638' );
						$badge_bg    = 'pass' === $row['quality_status'] ? '#e7f7ed' : ( 'fail' === $row['quality_status'] ? '#fcf0f1' : '#fcf9e8' );
						$badge_color = 'pass' === $row['quality_status'] ? '#008a20' : ( 'fail' === $row['quality_status'] ? '#d63638' : '#996800' );
					?>
						<tr id="scp-row-page-<?php echo esc_attr( $row['page_id'] ); ?>">
							<td>
								<span style="display:inline-block; padding:4px 8px; font-weight:700; border-radius:3px; background:#f6f7f7; color:<?php echo esc_attr( $score_color ); ?>;">
									<?php echo esc_html( $row['quality_score'] ); ?>
								</span>
							</td>
							<td>
								<span style="display:inline-block; padding:3px 8px; border-radius:12px; font-size:11px; font-weight:600; text-transform:uppercase; background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>;">
									<?php echo esc_html( $row['quality_status'] ); ?>
								</span>
								<?php if ( ! empty( $row['is_stale'] ) ) : ?>
									<span style="font-size:10px; color:#d63638; display:block; margin-top:2px;">[stale]</span>
								<?php endif; ?>
							</td>
							<td>
								<strong><?php echo esc_html( $row['page_title'] ); ?></strong>
								<div style="font-size:11px; color:#646970;">
									ID: #<?php echo esc_html( $row['page_id'] ); ?> | Slug: <code><?php echo esc_html( $row['page_slug'] ); ?></code>
								</div>
							</td>
							<td>
								<?php if ( ! empty( $row['issues'] ) && is_array( $row['issues'] ) ) : ?>
									<ul style="margin:0; padding-left:15px; font-size:12px;">
										<?php foreach ( array_slice( $row['issues'], 0, 3 ) as $iss ) : ?>
											<li style="color:<?php echo 'critical' === ( $iss['severity'] ?? '' ) ? '#d63638' : '#50575e'; ?>;">
												<?php echo esc_html( $iss['message'] ?? '' ); ?>
												<button type="button" class="button-link scp-btn-dismiss-issue" data-page-id="<?php echo esc_attr( $row['page_id'] ); ?>" data-issue-type="<?php echo esc_attr( $iss['type'] ?? '' ); ?>" style="font-size:11px; color:#2271b1; margin-left:5px;">
													[<?php esc_html_e( 'Dismiss', 'seo-city-pages' ); ?>]
												</button>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<span style="font-size:12px; color:#646970;"><?php esc_html_e( 'No active issue records.', 'seo-city-pages' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $row['similarity_score'] > 0 ) : ?>
									<div style="font-size:12px;">
										<strong><?php echo esc_html( number_format( (float) $row['similarity_score'], 1 ) ); ?>%</strong>
										<?php if ( $row['max_similar_page_id'] > 0 ) : ?>
											<span style="color:#646970;">(vs #<?php echo esc_html( $row['max_similar_page_id'] ); ?>)</span>
										<?php endif; ?>
									</div>
								<?php endif; ?>
								<?php if ( ! empty( $row['intent_overlap_flag'] ) ) : ?>
									<span style="display:inline-block; font-size:11px; color:#d63638; background:#fcf0f1; padding:2px 5px; border-radius:3px; margin-top:3px;">
										<?php esc_html_e( 'Intent Overlap', 'seo-city-pages' ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td style="text-align:right;">
								<button type="button" class="button button-small scp-btn-instant-rescan" data-page-id="<?php echo esc_attr( $row['page_id'] ); ?>" title="<?php esc_attr_e( 'Instant Rescan', 'seo-city-pages' ); ?>">
									<span class="dashicons dashicons-update" style="font-size:14px; vertical-align:middle;"></span>
								</button>
								<button type="button" class="button button-small scp-btn-ai-review" data-page-id="<?php echo esc_attr( $row['page_id'] ); ?>" title="<?php esc_attr_e( 'AI Editorial Advisor', 'seo-city-pages' ); ?>">
									<span class="dashicons dashicons-superhero" style="font-size:14px; vertical-align:middle;"></span>
								</button>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-pages&action=edit&id=' . $row['page_id'] ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'seo-city-pages' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>

<!-- Modal for AI Review Feedback -->
<div id="scp-ai-review-modal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6);">
	<div style="background:#fff; width:600px; max-width:90%; margin:80px auto; border-radius:6px; padding:25px; position:relative; box-shadow:0 5px 15px rgba(0,0,0,0.3);">
		<button type="button" id="scp-btn-close-modal" style="position:absolute; right:15px; top:15px; background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
		<h2 style="margin-top:0; font-size:18px; border-bottom:1px solid #ddd; padding-bottom:10px;">
			<span class="dashicons dashicons-superhero" style="color:#2271b1; vertical-align:middle;"></span>
			<?php esc_html_e( 'AI Editorial Quality Advisor', 'seo-city-pages' ); ?>
		</h2>
		<div id="scp-ai-modal-content" style="max-height:400px; overflow-y:auto; font-size:13px; line-height:1.6; margin-top:15px;">
			<p><?php esc_html_e( 'Analyzing page content...', 'seo-city-pages' ); ?></p>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce = '<?php echo esc_js( \SeoCityPages\Security\Security::create_nonce() ); ?>';

	// Save Gate Mode Policy
	$('#scp-btn-save-gate-mode').on('click', function() {
		var mode = $('input[name="gate_mode"]:checked').val();
		var $btn = $(this);
		var $status = $('#scp-gate-save-status');

		$btn.prop('disabled', true);
		$status.text('Updating...').css('color', '#50575e');

		$.post(ajaxUrl, {
			action: 'seo_city_pages_quality_set_gate_mode',
			gate_mode: mode,
			_ajax_nonce: nonce
		}, function(res) {
			$btn.prop('disabled', false);
			if (res.success) {
				$status.text('Saved!').css('color', '#008a20');
				setTimeout(function() { $status.text(''); }, 3000);
			} else {
				$status.text(res.data.message || 'Error').css('color', '#d63638');
			}
		});
	});

	// Trigger Bulk Quality Scan
	$('.scp-btn-bulk-scan-scope, #scp-btn-trigger-bulk-scan').on('click', function() {
		var scope = $(this).data('scope') || 'all';
		var $feedback = $('#scp-scan-trigger-feedback');

		$feedback.html('<span style="color:#2271b1;">Initializing quality scan job...</span>');

		$.post(ajaxUrl, {
			action: 'seo_city_pages_quality_bulk_scan',
			scope: scope,
			_ajax_nonce: nonce
		}, function(res) {
			if (res.success) {
				$feedback.html('<span style="color:#008a20; font-weight:600;">' + res.data.message + ' (Job ID: ' + res.data.job_id + ')</span>');
			} else {
				$feedback.html('<span style="color:#d63638;">' + (res.data.message || 'Scan initialization failed') + '</span>');
			}
		});
	});

	// Instant Rescan Single Page
	$('.scp-btn-instant-rescan').on('click', function() {
		var pageId = $(this).data('page-id');
		var $btn = $(this);
		$btn.prop('disabled', true).addClass('updating-message');

		$.post(ajaxUrl, {
			action: 'seo_city_pages_quality_scan_page',
			page_id: pageId,
			_ajax_nonce: nonce
		}, function(res) {
			$btn.prop('disabled', false).removeClass('updating-message');
			if (res.success) {
				location.reload();
			} else {
				alert(res.data.message || 'Scan error');
			}
		});
	});

	// Dismiss Quality Issue
	$('.scp-btn-dismiss-issue').on('click', function() {
		var pageId = $(this).data('page-id');
		var issueType = $(this).data('issue-type');
		var reason = prompt('Enter justification for dismissing this warning (optional):', 'Marked as false-positive by editor');

		if (null === reason) return;

		var $item = $(this).closest('li');

		$.post(ajaxUrl, {
			action: 'seo_city_pages_quality_dismiss_issue',
			page_id: pageId,
			issue_type: issueType,
			reason: reason,
			_ajax_nonce: nonce
		}, function(res) {
			if (res.success) {
				$item.fadeOut();
			} else {
				alert(res.data.message || 'Dismissal failed');
			}
		});
	});

	// AI Review Modal
	$('.scp-btn-ai-review').on('click', function() {
		var pageId = $(this).data('page-id');
		$('#scp-ai-modal-content').html('<p style="color:#2271b1;"><span class="dashicons dashicons-update" style="animation:spin 2s linear infinite;"></span> Analyzing content with AI advisor...</p>');
		$('#scp-ai-review-modal').fadeIn();

		$.post(ajaxUrl, {
			action: 'seo_city_pages_quality_ai_review',
			page_id: pageId,
			_ajax_nonce: nonce
		}, function(res) {
			if (res.success) {
				var fb = res.data;
				var html = '<div style="background:#f6f7f7; padding:12px; border-radius:4px; margin-bottom:12px;">'
					+ '<strong>Authenticity:</strong> ' + (fb.local_authenticity_rating || 'N/A') + ' | '
					+ '<strong>Tone:</strong> ' + (fb.tone_assessment || 'N/A') + '</div>';

				if (fb.critique && fb.critique.length) {
					html += '<h4 style="margin:10px 0 5px 0;">Critique & Distinctiveness:</h4><ul>';
					$.each(fb.critique, function(i, item) { html += '<li>' + item + '</li>'; });
					html += '</ul>';
				}

				if (fb.suggested_improvements && fb.suggested_improvements.length) {
					html += '<h4 style="margin:10px 0 5px 0;">Suggested Editorial Improvements:</h4><ul>';
					$.each(fb.suggested_improvements, function(i, item) { html += '<li>' + item + '</li>'; });
					html += '</ul>';
				}

				$('#scp-ai-modal-content').html(html);
			} else {
				$('#scp-ai-modal-content').html('<p style="color:#d63638;">' + (res.data.message || 'AI Review unavailable') + '</p>');
			}
		});
	});

	$('#scp-btn-close-modal, #scp-ai-review-modal').on('click', function(e) {
		if (e.target === this || this.id === 'scp-btn-close-modal') {
			$('#scp-ai-review-modal').fadeOut();
		}
	});
});
</script>
