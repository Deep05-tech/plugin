<?php
/**
 * SEO Settings & Controls Admin View Template.
 *
 * @package SeoCityPages\Admin
 * @var array  $settings
 * @var array  $environment_info
 * @var array  $duplicate_stats
 * @var string $notice
 * @var string $error
 */

use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap seo-city-pages-wrap">
	<div class="scp-header">
		<div class="scp-header-left">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-search" style="font-size: 28px; width: 28px; height: 28px; vertical-align: middle; margin-right: 6px; color: #2271b1;"></span>
				<?php esc_html_e( 'SEO Integration & Metadata Controls', 'seo-city-pages' ); ?>
			</h1>
			<p class="scp-subtitle">
				<?php esc_html_e( 'Configure automated SEO title templates, meta descriptions, XML sitemap generation, OpenGraph metadata, and Schema structured data.', 'seo-city-pages' ); ?>
			</p>
		</div>
	</div>
	<hr class="wp-header-end">

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $environment_info['has_conflict'] ) ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Multiple SEO Plugins Detected:', 'seo-city-pages' ); ?></strong>
				<?php echo esc_html( $environment_info['conflict_notice'] ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px; max-width: 1300px;">
		<!-- Left Column: Settings Form -->
		<div>
			<form method="post" action="">
				<?php Security::nonce_field( 'save_seo_settings' ); ?>
				<input type="hidden" name="seo_city_pages_save_seo_settings" value="1" />

				<!-- 1. SEO Plugin Integration Card -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( '1. Active SEO Environment & Integration Adapter', 'seo-city-pages' ); ?></h2>
					</div>
					<div class="inside" style="padding: 15px 20px;">
						<div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 12px 16px; margin-bottom: 15px;">
							<div style="font-weight: 600; color: #1d2327;">
								<?php esc_html_e( 'Active Detected Engine:', 'seo-city-pages' ); ?>
								<span style="color: #2271b1;"><?php echo esc_html( $environment_info['primary_name'] ); ?></span>
							</div>
							<p style="margin: 4px 0 0 0; font-size: 13px; color: #50575e;">
								<?php esc_html_e( 'SEO City Pages automatically routes meta tags, canonicals, and robots directives through the detected engine.', 'seo-city-pages' ); ?>
							</p>
						</div>

						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="scp_adapter_override"><?php esc_html_e( 'Adapter Engine Override', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<select name="adapter_override" id="scp_adapter_override">
											<option value="" <?php selected( $settings['adapter_override'], '' ); ?>><?php esc_html_e( 'Auto-Detect (Recommended)', 'seo-city-pages' ); ?></option>
											<option value="native" <?php selected( $settings['adapter_override'], 'native' ); ?>><?php esc_html_e( 'Force Native WordPress Engine', 'seo-city-pages' ); ?></option>
											<option value="yoast" <?php selected( $settings['adapter_override'], 'yoast' ); ?>><?php esc_html_e( 'Force Yoast SEO', 'seo-city-pages' ); ?></option>
											<option value="rank_math" <?php selected( $settings['adapter_override'], 'rank_math' ); ?>><?php esc_html_e( 'Force Rank Math SEO', 'seo-city-pages' ); ?></option>
											<option value="aioseo" <?php selected( $settings['adapter_override'], 'aioseo' ); ?>><?php esc_html_e( 'Force All in One SEO', 'seo-city-pages' ); ?></option>
										</select>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- 2. Global SEO Title & Description Templates Card -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( '2. Automated Title & Description Templates', 'seo-city-pages' ); ?></h2>
					</div>
					<div class="inside" style="padding: 15px 20px;">
						<p class="description" style="margin-bottom: 15px;">
							<?php esc_html_e( 'These templates are applied automatically across all generated city pages unless manually overridden or approved via AI metadata.', 'seo-city-pages' ); ?>
						</p>

						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="scp_default_title_template"><strong><?php esc_html_e( 'SEO Title Template', 'seo-city-pages' ); ?></strong></label>
									</th>
									<td>
										<input type="text" name="default_title_template" id="scp_default_title_template" class="large-text" value="<?php echo esc_attr( $settings['default_title_template'] ); ?>" />
										<div style="font-size: 12px; color: #646970; margin-top: 4px;">
											<span id="title-char-count">0</span> / 60 <?php esc_html_e( 'recommended characters', 'seo-city-pages' ); ?>
										</div>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_default_desc_template"><strong><?php esc_html_e( 'Meta Description Template', 'seo-city-pages' ); ?></strong></label>
									</th>
									<td>
										<textarea name="default_desc_template" id="scp_default_desc_template" rows="3" class="large-text"><?php echo esc_textarea( $settings['default_desc_template'] ); ?></textarea>
										<div style="font-size: 12px; color: #646970; margin-top: 4px;">
											<span id="desc-char-count">0</span> / 160 <?php esc_html_e( 'recommended characters', 'seo-city-pages' ); ?>
										</div>
									</td>
								</tr>
							</tbody>
						</table>

						<!-- Dynamic Placeholder Tag Legend -->
						<div style="background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 12px; margin-top: 15px;">
							<strong style="font-size: 12px; text-transform: uppercase; color: #50575e;"><?php esc_html_e( 'Supported Dynamic Tokens:', 'seo-city-pages' ); ?></strong>
							<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
								<code style="cursor: pointer;" onclick="insertToken('{service}')">{service}</code>
								<code style="cursor: pointer;" onclick="insertToken('{city}')">{city}</code>
								<code style="cursor: pointer;" onclick="insertToken('{state_code}')">{state_code}</code>
								<code style="cursor: pointer;" onclick="insertToken('{state}')">{state}</code>
								<code style="cursor: pointer;" onclick="insertToken('{country_code}')">{country_code}</code>
								<code style="cursor: pointer;" onclick="insertToken('{site_name}')">{site_name}</code>
								<code style="cursor: pointer;" onclick="insertToken('{current_year}')">{current_year}</code>
							</div>
						</div>
					</div>
				</div>

				<!-- 3. Social Metadata Card -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( '3. OpenGraph & Social Sharing Defaults', 'seo-city-pages' ); ?></h2>
					</div>
					<div class="inside" style="padding: 15px 20px;">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="scp_default_og_image"><?php esc_html_e( 'Default Share Image URL', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<input type="url" name="default_og_image" id="scp_default_og_image" class="large-text" value="<?php echo esc_attr( $settings['default_og_image'] ); ?>" placeholder="https://example.com/wp-content/uploads/og-banner.jpg" />
										<p class="description">
											<?php esc_html_e( 'Fallback OpenGraph / Twitter social image when no page-specific image is set.', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_default_twitter_card"><?php esc_html_e( 'Twitter / X Card Type', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<select name="default_twitter_card" id="scp_default_twitter_card">
											<option value="summary_large_image" <?php selected( $settings['default_twitter_card'], 'summary_large_image' ); ?>><?php esc_html_e( 'Summary with Large Image (summary_large_image)', 'seo-city-pages' ); ?></option>
											<option value="summary" <?php selected( $settings['default_twitter_card'], 'summary' ); ?>><?php esc_html_e( 'Standard Summary (summary)', 'seo-city-pages' ); ?></option>
										</select>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- 4. Indexing & Search Engine Controls Card -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( '4. Search Engine Indexing & Robots Controls', 'seo-city-pages' ); ?></h2>
					</div>
					<div class="inside" style="padding: 15px 20px;">
						<p class="description" style="margin-bottom: 15px;">
							<?php esc_html_e( 'Draft, unapproved, private, and trashed pages always output noindex, nofollow automatically to protect your site crawl budget.', 'seo-city-pages' ); ?>
						</p>

						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="scp_default_robots_index"><?php esc_html_e( 'Default Published Indexing', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<select name="default_robots_index" id="scp_default_robots_index">
											<option value="index" <?php selected( $settings['default_robots_index'], 'index' ); ?>><?php esc_html_e( 'Allow Indexing (index)', 'seo-city-pages' ); ?></option>
											<option value="noindex" <?php selected( $settings['default_robots_index'], 'noindex' ); ?>><?php esc_html_e( 'Block Indexing (noindex)', 'seo-city-pages' ); ?></option>
										</select>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_default_robots_follow"><?php esc_html_e( 'Default Link Following', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<select name="default_robots_follow" id="scp_default_robots_follow">
											<option value="follow" <?php selected( $settings['default_robots_follow'], 'follow' ); ?>><?php esc_html_e( 'Follow Links (follow)', 'seo-city-pages' ); ?></option>
											<option value="nofollow" <?php selected( $settings['default_robots_follow'], 'nofollow' ); ?>><?php esc_html_e( 'Do Not Follow Links (nofollow)', 'seo-city-pages' ); ?></option>
										</select>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- 5. XML Sitemap & Schema Structured Data Card -->
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle"><?php esc_html_e( '5. XML Sitemap & JSON-LD Schema Settings', 'seo-city-pages' ); ?></h2>
					</div>
					<div class="inside" style="padding: 15px 20px;">
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<?php esc_html_e( 'XML Sitemap Integration', 'seo-city-pages' ); ?>
									</th>
									<td>
										<label>
											<input type="checkbox" name="enable_sitemap" value="1" <?php checked( $settings['enable_sitemap'], true ); ?> />
											<strong><?php esc_html_e( 'Include published City Pages in XML Sitemaps', 'seo-city-pages' ); ?></strong>
										</label>
										<p class="description">
											<?php esc_html_e( 'Generates high-performance paginated sitemaps supporting up to 50,000+ pages without server memory spikes.', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_sitemap_entries_per_page"><?php esc_html_e( 'Sitemap Entries Per Page', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<input type="number" name="sitemap_entries_per_page" id="scp_sitemap_entries_per_page" class="small-text" value="<?php echo esc_attr( $settings['sitemap_entries_per_page'] ); ?>" min="100" max="5000" step="100" />
										<p class="description">
											<?php esc_html_e( 'Number of URL entries per sitemap partition (recommended: 1000).', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<?php esc_html_e( 'Schema.org JSON-LD', 'seo-city-pages' ); ?>
									</th>
									<td>
										<label>
											<input type="checkbox" name="enable_schema" value="1" <?php checked( $settings['enable_schema'], true ); ?> />
											<strong><?php esc_html_e( 'Generate Structured JSON-LD Data', 'seo-city-pages' ); ?></strong>
										</label>
										<p class="description">
											<?php esc_html_e( 'Emits compliant Service and LocalBusiness Schema with accurate areaServed geo-targeting without fake reviews.', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_schema_default_type"><?php esc_html_e( 'Default Schema Type', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<select name="schema_default_type" id="scp_schema_default_type">
											<option value="Service" <?php selected( $settings['schema_default_type'], 'Service' ); ?>><?php esc_html_e( 'Service (Recommended for Agencies / B2B)', 'seo-city-pages' ); ?></option>
											<option value="LocalBusiness" <?php selected( $settings['schema_default_type'], 'LocalBusiness' ); ?>><?php esc_html_e( 'LocalBusiness (For physical service providers)', 'seo-city-pages' ); ?></option>
											<option value="ProfessionalService" <?php selected( $settings['schema_default_type'], 'ProfessionalService' ); ?>><?php esc_html_e( 'ProfessionalService', 'seo-city-pages' ); ?></option>
										</select>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<div style="margin-top: 20px;">
					<button type="submit" class="button button-primary button-large">
						<?php esc_html_e( 'Save All SEO Settings', 'seo-city-pages' ); ?>
					</button>
				</div>
			</form>
		</div>

		<!-- Right Column: Health Stats & Live SERP Preview -->
		<div>
			<!-- SEO Health & Duplicate Stats Card -->
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'SEO Indexing & Health Audit', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px;">
					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
						<div style="background: #f9f9f9; padding: 10px; border-radius: 4px; text-align: center; border: 1px solid #eee;">
							<div style="font-size: 20px; font-weight: 700; color: #1d2327;"><?php echo (int) $duplicate_stats['total_pages']; ?></div>
							<div style="font-size: 11px; text-transform: uppercase; color: #646970;"><?php esc_html_e( 'Total Pages', 'seo-city-pages' ); ?></div>
						</div>
						<div style="background: #f9f9f9; padding: 10px; border-radius: 4px; text-align: center; border: 1px solid #eee;">
							<div style="font-size: 20px; font-weight: 700; color: #008a20;"><?php echo (int) $duplicate_stats['published_pages']; ?></div>
							<div style="font-size: 11px; text-transform: uppercase; color: #646970;"><?php esc_html_e( 'Indexable Pages', 'seo-city-pages' ); ?></div>
						</div>
						<div style="background: #f9f9f9; padding: 10px; border-radius: 4px; text-align: center; border: 1px solid #eee;">
							<div style="font-size: 20px; font-weight: 700; color: #2271b1;"><?php echo (int) $duplicate_stats['manual_overrides']; ?></div>
							<div style="font-size: 11px; text-transform: uppercase; color: #646970;"><?php esc_html_e( 'Manual Overrides', 'seo-city-pages' ); ?></div>
						</div>
						<div style="background: #f9f9f9; padding: 10px; border-radius: 4px; text-align: center; border: 1px solid #eee;">
							<div style="font-size: 20px; font-weight: 700; color: <?php echo $duplicate_stats['duplicate_titles'] > 0 ? '#d63638' : '#008a20'; ?>;">
								<?php echo (int) $duplicate_stats['duplicate_titles']; ?>
							</div>
							<div style="font-size: 11px; text-transform: uppercase; color: #646970;"><?php esc_html_e( 'Duplicate Titles', 'seo-city-pages' ); ?></div>
						</div>
					</div>

					<div style="margin-top: 15px; font-size: 12px; color: #646970; border-top: 1px solid #eee; padding-top: 10px;">
						<div style="margin-bottom: 4px;">
							<strong><?php esc_html_e( 'Duplicate Descriptions:', 'seo-city-pages' ); ?></strong>
							<span style="color: <?php echo $duplicate_stats['duplicate_descriptions'] > 0 ? '#d63638' : '#008a20'; ?>; font-weight: 600;">
								<?php echo (int) $duplicate_stats['duplicate_descriptions']; ?>
							</span>
						</div>
						<div style="margin-bottom: 4px;">
							<strong><?php esc_html_e( 'Missing Titles:', 'seo-city-pages' ); ?></strong>
							<span style="color: <?php echo $duplicate_stats['missing_titles'] > 0 ? '#d63638' : '#008a20'; ?>; font-weight: 600;">
								<?php echo (int) $duplicate_stats['missing_titles']; ?>
							</span>
						</div>
						<div>
							<strong><?php esc_html_e( 'Missing Descriptions:', 'seo-city-pages' ); ?></strong>
							<span style="color: <?php echo $duplicate_stats['missing_descriptions'] > 0 ? '#d63638' : '#008a20'; ?>; font-weight: 600;">
								<?php echo (int) $duplicate_stats['missing_descriptions']; ?>
							</span>
						</div>
					</div>
				</div>
			</div>

			<!-- Live SERP Preview Card -->
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Live Search Snippet Preview', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px;">
					<div style="background: #fff; border: 1px solid #dadce0; border-radius: 8px; padding: 14px; font-family: arial, sans-serif;">
						<div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #202124; margin-bottom: 4px;">
							<span class="dashicons dashicons-admin-site" style="font-size: 16px; width: 16px; height: 16px; color: #5f6368;"></span>
							<span style="font-weight: 500;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
							<span style="color: #5f6368;">› service-areas › orlando</span>
						</div>
						<div id="serp-title-preview" style="color: #1a0dab; font-size: 18px; line-height: 1.3; font-weight: 400; cursor: pointer;">
							SEO Agency in Orlando, FL | <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
						</div>
						<div id="serp-desc-preview" style="color: #4d5156; font-size: 13px; line-height: 1.5; margin-top: 4px;">
							Professional SEO services in Orlando, Florida. Trusted local experts delivering quality solutions. Contact us today.
						</div>
					</div>
				</div>
			</div>

			<!-- 4-Tier Precedence Cascade Guide -->
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Precedence Cascade Rules', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px; font-size: 13px; color: #50575e;">
					<ol style="margin: 0; padding-left: 18px; line-height: 1.6;">
						<li><strong style="color: #2271b1;"><?php esc_html_e( 'Manual Override:', 'seo-city-pages' ); ?></strong> <?php esc_html_e( 'Custom metadata entered on specific page.', 'seo-city-pages' ); ?></li>
						<li><strong style="color: #2271b1;"><?php esc_html_e( 'Approved AI Metadata:', 'seo-city-pages' ); ?></strong> <?php esc_html_e( 'Generated & approved content version metadata.', 'seo-city-pages' ); ?></li>
						<li><strong style="color: #2271b1;"><?php esc_html_e( 'Configured Template:', 'seo-city-pages' ); ?></strong> <?php esc_html_e( 'Global tokens evaluated for service/city.', 'seo-city-pages' ); ?></li>
						<li><strong style="color: #2271b1;"><?php esc_html_e( 'Safe Fallback:', 'seo-city-pages' ); ?></strong> <?php esc_html_e( 'Built-in deterministic standard fallback.', 'seo-city-pages' ); ?></li>
					</ol>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
function updateCharCounts() {
	var title = document.getElementById('scp_default_title_template').value;
	var desc = document.getElementById('scp_default_desc_template').value;

	var titleLen = title.length;
	var descLen = desc.length;

	var titleEl = document.getElementById('title-char-count');
	var descEl = document.getElementById('desc-char-count');

	if (titleEl) {
		titleEl.textContent = titleLen;
		titleEl.style.color = (titleLen > 60) ? '#d63638' : '#008a20';
	}
	if (descEl) {
		descEl.textContent = descLen;
		descEl.style.color = (descLen > 160) ? '#d63638' : '#008a20';
	}

	// Update SERP preview sample
	var sampleTitle = title.replace(/{service}/gi, 'SEO Agency')
		.replace(/{city}/gi, 'Orlando')
		.replace(/{state_code}/gi, 'FL')
		.replace(/{state}/gi, 'Florida')
		.replace(/{site_name}/gi, '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>');

	var sampleDesc = desc.replace(/{service}/gi, 'SEO Agency')
		.replace(/{city}/gi, 'Orlando')
		.replace(/{state_code}/gi, 'FL')
		.replace(/{state}/gi, 'Florida')
		.replace(/{site_name}/gi, '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>');

	var serpTitle = document.getElementById('serp-title-preview');
	var serpDesc = document.getElementById('serp-desc-preview');

	if (serpTitle) serpTitle.textContent = sampleTitle;
	if (serpDesc) serpDesc.textContent = sampleDesc;
}

function insertToken(token) {
	var input = document.getElementById('scp_default_title_template');
	if (!input) return;
	input.value += ' ' + token;
	updateCharCounts();
}

document.addEventListener('DOMContentLoaded', function() {
	var titleInput = document.getElementById('scp_default_title_template');
	var descInput = document.getElementById('scp_default_desc_template');

	if (titleInput) titleInput.addEventListener('input', updateCharCounts);
	if (descInput) descInput.addEventListener('input', updateCharCounts);

	updateCharCounts();
});
</script>
