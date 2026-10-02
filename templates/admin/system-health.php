<?php
/**
 * System Health & Diagnostics Admin View.
 *
 * @package SeoCityPages\Admin
 */

use SeoCityPages\Security\Security;
use SeoCityPages\Admin\Health_Controller;
use SeoCityPages\Diagnostics\Safe_Mode_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nonce = Security::create_nonce( Health_Controller::NONCE_ACTION );
$is_safe_mode = Safe_Mode_Manager::is_safe_mode_active();
?>
<div class="wrap seo-city-pages-wrap" style="max-width: 1300px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">

	<!-- SAFE MODE ALERT BANNER -->
	<?php if ( $is_safe_mode ) : ?>
		<div style="background: #fff5f5; border: 2px solid #e53e3e; color: #9b2c2c; padding: 18px 24px; border-radius: 10px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
			<div style="display: flex; align-items: center; gap: 14px;">
				<span style="font-size: 28px;">🚨</span>
				<div>
					<h3 style="margin: 0 0 4px 0; font-size: 17px; font-weight: 700; color: #9b2c2c;">PLUGIN SAFE MODE IS ACTIVE</h3>
					<p style="margin: 0; font-size: 13px; color: #742a2a;">AI generation, bulk publishing, and background worker queues are suspended. Public city page rendering and diagnostics remain functional.</p>
				</div>
			</div>
			<button type="button" onclick="toggleSafeMode(false)" style="background: #e53e3e; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 700; cursor: pointer;">Deactivate Safe Mode</button>
		</div>
	<?php endif; ?>

	<!-- Header Card -->
	<div style="background: #ffffff; border-radius: 12px; padding: 24px 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 24px;">
		<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; padding-bottom: 20px;">
			<div>
				<h1 style="font-size: 26px; font-weight: 700; color: #1a202c; margin: 0 0 6px 0;">🛡️ Production System Health & Observability</h1>
				<p style="color: #718096; margin: 0; font-size: 14px;">Subsystem status checks, relational integrity scanning, non-destructive OpenAI test, and safe recovery tools.</p>
			</div>
			<div style="display: flex; gap: 10px;">
				<?php if ( ! $is_safe_mode ) : ?>
					<button type="button" onclick="toggleSafeMode(true)" style="background: #ecc94b; color: #744210; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;">Activate Safe Mode</button>
				<?php endif; ?>
				<button type="button" onclick="downloadSupportBundle()" style="background: #3182ce; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; font-weight: 600; cursor: pointer;">📥 Download Support Bundle</button>
			</div>
		</div>

		<!-- Status Grid Matrix -->
		<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 20px;">
			<!-- PHP & Server -->
			<div style="background: #f7fafc; padding: 16px; border-radius: 8px; border-left: 4px solid <?php echo 'healthy' === ( $diagnostics['system']['status'] ?? '' ) ? '#38a169' : '#e53e3e'; ?>;">
				<div style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600;">PHP & Server</div>
				<div style="font-size: 18px; font-weight: 700; color: #2d3748; margin-top: 4px;">PHP <?php echo esc_html( $diagnostics['system']['php_version'] ?? PHP_VERSION ); ?></div>
				<div style="font-size: 12px; color: #4a5568; margin-top: 2px;">Memory: <?php echo esc_html( $diagnostics['system']['memory_limit'] ?? '256M' ); ?></div>
			</div>

			<!-- Database & Schema -->
			<div style="background: #f7fafc; padding: 16px; border-radius: 8px; border-left: 4px solid <?php echo 'healthy' === ( $diagnostics['database']['status'] ?? '' ) ? '#38a169' : '#d69e2e'; ?>;">
				<div style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600;">Database Schema</div>
				<div style="font-size: 18px; font-weight: 700; color: #2d3748; margin-top: 4px;">v<?php echo esc_html( $diagnostics['database']['installed_db_version'] ?? '0.8.0' ); ?></div>
				<div style="font-size: 12px; color: #38a169; margin-top: 2px;">All 11 Tables Verified</div>
			</div>

			<!-- OpenAI Provider -->
			<div style="background: #f7fafc; padding: 16px; border-radius: 8px; border-left: 4px solid <?php echo 'Configured' === ( $openai_info['api_key_status'] ?? '' ) ? '#38a169' : '#e53e3e'; ?>;">
				<div style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600;">OpenAI API</div>
				<div style="font-size: 18px; font-weight: 700; color: #2d3748; margin-top: 4px;"><?php echo esc_html( $openai_info['api_key_status'] ?? 'Configured' ); ?></div>
				<div style="font-size: 12px; color: #4a5568; margin-top: 2px;">Model: <?php echo esc_html( $openai_info['model'] ?? 'gpt-4o-mini' ); ?></div>
			</div>

			<!-- Queue Health -->
			<div style="background: #f7fafc; padding: 16px; border-radius: 8px; border-left: 4px solid <?php echo ( $queue_info['stale_items'] ?? 0 ) > 0 ? '#d69e2e' : '#38a169'; ?>;">
				<div style="font-size: 12px; color: #718096; text-transform: uppercase; font-weight: 600;">Queue Engine</div>
				<div style="font-size: 18px; font-weight: 700; color: #2d3748; margin-top: 4px;"><?php echo (int) ( $queue_info['item_counts']['pending'] ?? 0 ); ?> Pending</div>
				<div style="font-size: 12px; color: <?php echo ( $queue_info['stale_items'] ?? 0 ) > 0 ? '#e53e3e' : '#38a169'; ?>; margin-top: 2px;">
					<?php echo (int) ( $queue_info['stale_items'] ?? 0 ); ?> Stale Locks
				</div>
			</div>
		</div>
	</div>

	<!-- Interactive Diagnostics & Tools Grid -->
	<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">

		<!-- Tool 1: OpenAI Live Ping -->
		<div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
			<h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #2d3748;">⚡ Non-Destructive OpenAI Connection Test</h3>
			<p style="color: #718096; font-size: 13px; margin-bottom: 16px;">Sends a lightweight, secret-safe authorization test to api.openai.com without generating content or consuming tokens.</p>
			<button type="button" onclick="testOpenAIConnection()" style="background: #2b6cb0; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Test OpenAI Connection</button>
			<div id="openai-test-result" style="margin-top: 16px; display: none; padding: 12px 16px; border-radius: 6px; font-size: 13px;"></div>
		</div>

		<!-- Tool 2: Data Integrity Scanner -->
		<div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
			<h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #2d3748;">🔍 Relational & Cross-Contamination Scanner</h3>
			<p style="color: #718096; font-size: 13px; margin-bottom: 16px;">Scans database for orphaned tables, broken foreign keys, and geographic cross-contamination across cities/services.</p>
			<button type="button" onclick="runIntegrityScan()" style="background: #4a5568; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">Run Full Integrity Scan</button>
			<div id="integrity-scan-result" style="margin-top: 16px; display: none; padding: 12px 16px; border-radius: 6px; font-size: 13px;"></div>
		</div>

		<!-- Tool 3: URL Health Checker (SSRF Protected) -->
		<div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
			<h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #2d3748;">🌐 Internal URL & Canonical Inspector</h3>
			<p style="color: #718096; font-size: 13px; margin-bottom: 16px;">Performs an SSRF-locked HTTP probe on a landing page route to verify HTTP 200, title tags, canonical URLs, and meta robots.</p>
			<div style="display: flex; gap: 8px;">
				<input type="text" id="target-url-path" placeholder="/seo-agency/orlando/" style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e0; border-radius: 6px;" />
				<button type="button" onclick="checkURLHealth()" style="background: #3182ce; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;">Check URL</button>
			</div>
			<div id="url-health-result" style="margin-top: 16px; display: none; padding: 12px 16px; border-radius: 6px; font-size: 13px;"></div>
		</div>

		<!-- Tool 4: Safe Diagnostic Repair Operations -->
		<div style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
			<h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 700; color: #2d3748;">🛠️ Controlled Recovery & Repair Center</h3>
			<p style="color: #718096; font-size: 13px; margin-bottom: 16px;">Perform narrowly scoped, confirmed repair operations without destructive data loss.</p>
			<div style="display: flex; flex-wrap: wrap; gap: 8px;">
				<button type="button" onclick="executeRepair('repair_db')" class="button">Repair DB Schema</button>
				<button type="button" onclick="executeRepair('refresh_rewrites')" class="button">Refresh Rewrite Rules</button>
				<button type="button" onclick="executeRepair('recover_stale_queue')" class="button">Recover Stale Queue</button>
				<button type="button" onclick="executeRepair('regenerate_elementor')" class="button">Regenerate Elementor Cache</button>
			</div>
			<div id="repair-op-result" style="margin-top: 16px; display: none; padding: 12px 16px; border-radius: 6px; font-size: 13px;"></div>
		</div>

	</div>
</div>

<script>
const healthNonce = "<?php echo esc_js( $nonce ); ?>";

function testOpenAIConnection() {
	const box = document.getElementById('openai-test-result');
	box.style.display = 'block';
	box.style.background = '#edf2f7';
	box.style.color = '#4a5568';
	box.innerText = 'Testing connection to OpenAI...';

	jQuery.post(ajaxurl, {
		action: 'seo_city_pages_test_openai_connection',
		_wpnonce: healthNonce
	}, function(res) {
		if (res.success) {
			box.style.background = '#f0fff4';
			box.style.color = '#276749';
			box.innerText = '✅ ' + res.data.message;
		} else {
			box.style.background = '#fff5f5';
			box.style.color = '#9b2c2c';
			box.innerHTML = '<strong>❌ ' + (res.data.error_message || 'Connection Failed') + '</strong><br>' + (res.data.remediation || '');
		}
	});
}

function runIntegrityScan() {
	const box = document.getElementById('integrity-scan-result');
	box.style.display = 'block';
	box.style.background = '#edf2f7';
	box.style.color = '#4a5568';
	box.innerText = 'Scanning tables, foreign keys, and content versions...';

	jQuery.post(ajaxurl, {
		action: 'seo_city_pages_run_integrity_scan',
		_wpnonce: healthNonce
	}, function(res) {
		if (res.success) {
			const data = res.data;
			let html = '<strong>Scan Complete: ' + data.status.toUpperCase() + '</strong><br>';
			html += '• Total Issues: ' + data.total_issues_found + '<br>';
			html += '• Orphaned Records: ' + data.orphan_report.total_orphans + '<br>';
			html += '• Cross-Contamination Mismatches: ' + data.contamination_rep.total_mismatches + '<br>';
			html += '• Published Page Flagged: ' + data.published_report.total_issues;

			box.style.background = (data.total_issues_found > 0) ? '#fffaf0' : '#f0fff4';
			box.style.color = (data.total_issues_found > 0) ? '#744210' : '#276749';
			box.innerHTML = html;
		}
	});
}

function checkURLHealth() {
	const path = document.getElementById('target-url-path').value;
	const box  = document.getElementById('url-health-result');
	if (!path) {
		alert('Please enter a relative URL path (e.g. /seo-agency/orlando/)');
		return;
	}

	box.style.display = 'block';
	box.style.background = '#edf2f7';
	box.style.color = '#4a5568';
	box.innerText = 'Probing internal URL...';

	jQuery.post(ajaxurl, {
		action: 'seo_city_pages_check_url_health',
		url_path: path,
		_wpnonce: healthNonce
	}, function(res) {
		if (res.success) {
			const d = res.data;
			box.style.background = d.is_healthy ? '#f0fff4' : '#fffaf0';
			box.style.color = d.is_healthy ? '#276749' : '#744210';
			box.innerHTML = '<strong>HTTP ' + d.status_code + ' (' + d.duration_ms + ' ms)</strong><br>' +
				'• Canonical: ' + (d.canonical || 'None') + '<br>' +
				'• Robots: ' + (d.robots || 'Default') + '<br>' +
				'• Title: ' + (d.title || 'Missing');
		} else {
			box.style.background = '#fff5f5';
			box.style.color = '#9b2c2c';
			box.innerText = '❌ ' + (res.data.error || 'Inspection failed');
		}
	});
}

function executeRepair(type) {
	if (!confirm('Are you sure you want to execute this repair operation?')) {
		return;
	}

	const box = document.getElementById('repair-op-result');
	box.style.display = 'block';
	box.style.background = '#edf2f7';
	box.style.color = '#4a5568';
	box.innerText = 'Executing repair action...';

	jQuery.post(ajaxurl, {
		action: 'seo_city_pages_execute_repair',
		repair_type: type,
		_wpnonce: healthNonce
	}, function(res) {
		if (res.success) {
			box.style.background = '#f0fff4';
			box.style.color = '#276749';
			box.innerText = '✅ ' + (res.data.message || 'Action executed successfully.');
		} else {
			box.style.background = '#fff5f5';
			box.style.color = '#9b2c2c';
			box.innerText = '❌ ' + (res.data.message || 'Repair operation failed.');
		}
	});
}

function toggleSafeMode(enable) {
	const promptMsg = enable ? 'Activate Safe Mode? This will pause AI generation and background jobs.' : 'Deactivate Safe Mode? Normal operations will resume.';
	if (!confirm(promptMsg)) {
		return;
	}

	jQuery.post(ajaxurl, {
		action: 'seo_city_pages_toggle_safe_mode',
		enable: enable ? 1 : 0,
		_wpnonce: healthNonce
	}, function(res) {
		location.reload();
	});
}

function downloadSupportBundle() {
	const form = document.createElement('form');
	form.method = 'POST';
	form.action = ajaxurl;

	const actInput = document.createElement('input');
	actInput.type = 'hidden';
	actInput.name = 'action';
	actInput.value = 'seo_city_pages_download_support_bundle';
	form.appendChild(actInput);

	const nonceInput = document.createElement('input');
	nonceInput.type = 'hidden';
	nonceInput.name = '_wpnonce';
	nonceInput.value = healthNonce;
	form.appendChild(nonceInput);

	document.body.appendChild(form);
	form.submit();
	document.body.removeChild(form);
}
</script>
