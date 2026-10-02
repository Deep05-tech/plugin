<?php
/**
 * Elementor Master Template Configuration View Template.
 *
 * @package SeoCityPages\Admin
 * @var string     $message
 * @var string     $notice_type
 * @var array      $elementor_status
 * @var array      $arolax_status
 * @var object[]   $available_templates
 * @var object|null $selected_template
 * @var bool       $template_deleted
 * @var int        $selected_id
 * @var int        $preview_page_id
 * @var object[]   $sample_pages
 */

use SeoCityPages\Security\Security;
use SeoCityPages\Database\City_Page_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap seo-city-pages-wrap">
	<div class="scp-header">
		<div class="scp-header-left">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Elementor Master Template', 'seo-city-pages' ); ?></h1>
			<p class="scp-subtitle">
				<?php esc_html_e( 'Configure the single, reusable Elementor design that dynamically renders all SEO landing pages across your website.', 'seo-city-pages' ); ?>
			</p>
		</div>
		<div class="scp-header-right">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-pages' ) ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-admin-page"></span> <?php esc_html_e( 'View City Pages', 'seo-city-pages' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages' ) ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'seo-city-pages' ); ?>
			</a>
		</div>
	</div>
	<hr class="wp-header-end">

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Environment & Capability Status Bar -->
	<div class="scp-status-banner" style="display: flex; flex-wrap: wrap; gap: 15px; margin: 15px 0 20px; padding: 12px 16px; background: #fff; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.05); align-items: center;">
		<div>
			<strong><?php esc_html_e( 'Elementor Core:', 'seo-city-pages' ); ?></strong>
			<?php if ( ! empty( $elementor_status['is_active'] ) ) : ?>
				<span class="scp-badge scp-badge-active" style="margin-left: 4px;">
					<?php printf( esc_html__( 'Active (v%s)', 'seo-city-pages' ), esc_html( $elementor_status['version'] ?: '3.x' ) ); ?>
				</span>
			<?php else : ?>
				<span class="scp-badge scp-badge-warning" style="margin-left: 4px;"><?php esc_html_e( 'Not Detected (Fallback Active)', 'seo-city-pages' ); ?></span>
			<?php endif; ?>
		</div>
		<div>
			<strong><?php esc_html_e( 'Elementor Pro:', 'seo-city-pages' ); ?></strong>
			<?php if ( ! empty( $elementor_status['is_pro_active'] ) ) : ?>
				<span class="scp-badge scp-badge-active" style="margin-left: 4px;">
					<?php printf( esc_html__( 'Active (v%s)', 'seo-city-pages' ), esc_html( $elementor_status['pro_version'] ?: '3.x' ) ); ?>
				</span>
			<?php else : ?>
				<span class="scp-badge scp-badge-info" style="margin-left: 4px;"><?php esc_html_e( 'Not Installed (Standard Templates Used)', 'seo-city-pages' ); ?></span>
			<?php endif; ?>
		</div>
		<div>
			<strong><?php esc_html_e( 'Theme Mode:', 'seo-city-pages' ); ?></strong>
			<?php if ( ! empty( $arolax_status['is_active'] ) ) : ?>
				<span class="scp-badge scp-badge-active" style="margin-left: 4px;"><?php esc_html_e( 'Arolax Theme Active', 'seo-city-pages' ); ?></span>
			<?php else : ?>
				<span class="scp-badge scp-badge-info" style="margin-left: 4px;"><?php esc_html_e( 'Theme-Isolated (Universal)', 'seo-city-pages' ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $template_deleted ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Warning:', 'seo-city-pages' ); ?></strong>
				<?php printf( esc_html__( 'The previously configured Elementor template (ID #%d) could not be found or has been moved to trash. Public pages will use the semantic fallback layout until a valid template is selected.', 'seo-city-pages' ), (int) $selected_id ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
		<!-- Main Column -->
		<div>
			<!-- Active Selected Template Card -->
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Active SEO City Page Master Template', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px 20px;">
					<?php if ( $selected_template ) : ?>
						<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
							<div>
								<h3 style="margin: 0 0 5px; font-size: 1.25rem;">
									<?php echo esc_html( $selected_template->title ); ?>
									<span class="scp-badge scp-badge-active" style="font-size: 12px; vertical-align: middle; margin-left: 8px;">
										<?php echo esc_html( ucfirst( $selected_template->status ) ); ?>
									</span>
								</h3>
								<p class="description" style="margin: 0;">
									<?php printf( esc_html__( 'Template ID: #%1$d | Type: %2$s', 'seo-city-pages' ), (int) $selected_template->id, esc_html( strtoupper( $selected_template->type ) ) ); ?>
								</p>
							</div>
							<div style="display: flex; gap: 8px;">
								<a href="<?php echo esc_url( $selected_template->edit_url ); ?>" class="button button-primary button-hero" target="_blank">
									<span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
									<?php esc_html_e( 'Edit Template with Elementor', 'seo-city-pages' ); ?>
								</a>
							</div>
						</div>
						<hr style="margin: 15px 0; border: 0; border-top: 1px solid #ddd;">
						<p class="description">
							<span class="dashicons dashicons-info" style="vertical-align: middle;"></span>
							<?php esc_html_e( 'Any visual, typographic, or layout modifications made in this Elementor template will instantly apply to all 10,000+ SEO City landing pages dynamically.', 'seo-city-pages' ); ?>
						</p>
					<?php else : ?>
						<p style="color: #646970; font-style: italic;">
							<?php esc_html_e( 'No Elementor template is currently selected. SEO City Pages are rendering via the semantic fallback layout. Select an existing template below or create a starter template.', 'seo-city-pages' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Select / Update Template Form -->
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Select Master Template & Editor Preview Context', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px 20px;">
					<form method="post" action="">
						<?php Security::nonce_field( 'save_template' ); ?>

						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="scp_template_id"><strong><?php esc_html_e( 'Elementor Template', 'seo-city-pages' ); ?></strong></label>
									</th>
									<td>
										<select name="template_id" id="scp_template_id" style="width: 100%; max-width: 450px;">
											<option value="0"><?php esc_html_e( '— None (Use Theme Fallback Renderer) —', 'seo-city-pages' ); ?></option>
											<?php foreach ( $available_templates as $tpl ) : ?>
												<option value="<?php echo esc_attr( $tpl->id ); ?>" <?php selected( $selected_id, $tpl->id ); ?>>
													<?php echo esc_html( $tpl->title ); ?> (ID: #<?php echo esc_html( $tpl->id ); ?>, <?php echo esc_html( ucfirst( $tpl->type ) ); ?>)
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description">
											<?php esc_html_e( 'Choose any compatible Elementor page or section template from your Elementor Library.', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_preview_page_id"><strong><?php esc_html_e( 'Editor Preview SEO Page', 'seo-city-pages' ); ?></strong></label>
									</th>
									<td>
										<select name="preview_page_id" id="scp_preview_page_id" style="width: 100%; max-width: 450px;">
											<option value="0"><?php esc_html_e( '— Default Sample Data (Rajkot / SEO Agency) —', 'seo-city-pages' ); ?></option>
											<?php foreach ( $sample_pages as $spage ) : ?>
												<option value="<?php echo esc_attr( $spage->id ); ?>" <?php selected( $preview_page_id, $spage->id ); ?>>
													#<?php echo esc_html( $spage->id ); ?> — <?php echo esc_html( City_Page_Repository::get_derived_title( $spage ) ); ?> (<?php echo esc_html( $spage->url_path ); ?>)
												</option>
											<?php endforeach; ?>
										</select>
										<p class="description">
											<?php esc_html_e( 'Select which real SEO City Page data to simulate when visually editing inside the Elementor editor.', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>
							</tbody>
						</table>

						<div class="scp-form-actions" style="margin-top: 20px; display: flex; gap: 10px; align-items: center;">
							<button type="submit" name="scp_save_template" value="1" class="button button-primary">
								<?php esc_html_e( 'Save Template Configuration', 'seo-city-pages' ); ?>
							</button>

							<?php if ( $selected_id > 0 ) : ?>
								<button type="submit" name="scp_reset_template" value="1" class="button button-secondary" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear the template selection?', 'seo-city-pages' ) ); ?>');">
									<?php esc_html_e( 'Clear Selection', 'seo-city-pages' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</div>

			<!-- Create Starter Template Card -->
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Quick Starter Template Generator', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px 20px;">
					<p>
						<?php esc_html_e( 'Need a clean starting point? Generate a pre-configured minimal Elementor master template containing ready-to-use SEO City dynamic tags, hero container, and call-to-action block.', 'seo-city-pages' ); ?>
					</p>
					<form method="post" action="">
						<?php Security::nonce_field( 'create_starter_template' ); ?>
						<div style="display: flex; gap: 10px; align-items: center; max-width: 600px;">
							<input type="text" name="starter_title" value="<?php esc_attr_e( 'SEO City Landing Page — Master Template', 'seo-city-pages' ); ?>" class="regular-text" style="flex: 1;" required />
							<button type="submit" name="scp_create_starter_template" value="1" class="button button-secondary">
								<span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
								<?php esc_html_e( 'Create & Edit in Elementor', 'seo-city-pages' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Sidebar Column: Dynamic Tags Reference -->
		<div>
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Available Dynamic Tags', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px;">
					<p class="description">
						<?php esc_html_e( 'In the Elementor editor, click the Dynamic Tags icon on any text or URL field to insert these dynamic placeholders:', 'seo-city-pages' ); ?>
					</p>

					<div style="margin-top: 15px;">
						<ul class="scp-features-list" style="margin: 0; padding: 0; list-style: none;">
							<li style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1;">
								<strong><span class="dashicons dashicons-tag" style="color: #2271b1;"></span> <?php esc_html_e( 'City Name', 'seo-city-pages' ); ?></strong>
								<br><code style="font-size: 11px;">seo_city_name</code> — <em><?php esc_html_e( 'e.g. "Rajkot"', 'seo-city-pages' ); ?></em>
							</li>
							<li style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1;">
								<strong><span class="dashicons dashicons-tag" style="color: #2271b1;"></span> <?php esc_html_e( 'State / Region Name', 'seo-city-pages' ); ?></strong>
								<br><code style="font-size: 11px;">seo_city_state</code> — <em><?php esc_html_e( 'e.g. "Gujarat"', 'seo-city-pages' ); ?></em>
							</li>
							<li style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1;">
								<strong><span class="dashicons dashicons-tag" style="color: #2271b1;"></span> <?php esc_html_e( 'Country Name', 'seo-city-pages' ); ?></strong>
								<br><code style="font-size: 11px;">seo_city_country</code> — <em><?php esc_html_e( 'e.g. "India"', 'seo-city-pages' ); ?></em>
							</li>
							<li style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1;">
								<strong><span class="dashicons dashicons-tag" style="color: #2271b1;"></span> <?php esc_html_e( 'Service Name', 'seo-city-pages' ); ?></strong>
								<br><code style="font-size: 11px;">seo_city_service</code> — <em><?php esc_html_e( 'e.g. "SEO Agency"', 'seo-city-pages' ); ?></em>
							</li>
							<li style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1;">
								<strong><span class="dashicons dashicons-tag" style="color: #2271b1;"></span> <?php esc_html_e( 'Page Title', 'seo-city-pages' ); ?></strong>
								<br><code style="font-size: 11px;">seo_city_title</code> — <em><?php esc_html_e( 'e.g. "SEO Agency in Rajkot"', 'seo-city-pages' ); ?></em>
							</li>
							<li style="margin-bottom: 5px;">
								<strong><span class="dashicons dashicons-admin-links" style="color: #2271b1;"></span> <?php esc_html_e( 'Page URL', 'seo-city-pages' ); ?></strong>
								<br><code style="font-size: 11px;">seo_city_url</code> — <em><?php esc_html_e( 'e.g. "/seo-agency/rajkot/"', 'seo-city-pages' ); ?></em>
							</li>
						</ul>
					</div>
				</div>
			</div>

			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Theme & Isolation Rules', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px;">
					<p class="description">
						<span class="dashicons dashicons-yes-alt" style="color: #46b450; vertical-align: middle;"></span>
						<?php esc_html_e( 'Standard theme header and footer wrappers (including Arolax) are preserved automatically.', 'seo-city-pages' ); ?>
					</p>
					<p class="description">
						<span class="dashicons dashicons-yes-alt" style="color: #46b450; vertical-align: middle;"></span>
						<?php esc_html_e( 'Zero theme modifications: theme code is never overridden.', 'seo-city-pages' ); ?>
					</p>
					<p class="description">
						<span class="dashicons dashicons-yes-alt" style="color: #46b450; vertical-align: middle;"></span>
						<?php esc_html_e( 'Zero N+1 database queries: page context is loaded once per request.', 'seo-city-pages' ); ?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
