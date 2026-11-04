<?php
/**
 * Manual Page Generation Admin Form Template.
 *
 * @package SeoCityPages\Admin
 * @var string       $notice
 * @var string       $error
 * @var object|null  $generated_page
 * @var object[]     $countries
 * @var object[]     $states
 * @var object[]     $services
 */

use SeoCityPages\Security\Security;
use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\URL\URL_Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap seo-city-pages-wrap">
	<div class="scp-header">
		<div class="scp-header-left">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Generate Page Manually', 'seo-city-pages' ); ?>
			</h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-pages' ) ); ?>" class="page-title-action">
				<?php esc_html_e( '← Back to Pages', 'seo-city-pages' ); ?>
			</a>
			<p class="scp-subtitle">
				<?php esc_html_e( 'Directly input location and niche details to generate an SEO landing page instantly without uploading a CSV file.', 'seo-city-pages' ); ?>
			</p>
		</div>
		<div class="scp-header-actions" style="margin-top: 10px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import' ) ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Switch to CSV Upload', 'seo-city-pages' ); ?>
			</a>
		</div>
	</div>
	<hr class="wp-header-end">

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-success is-dismissible" style="padding: 15px 20px; border-left: 4px solid #46b450; background: #f0f9eb; margin: 15px 0 20px; border-radius: 4px;">
			<h3 style="margin: 0 0 8px 0; color: #2e7d32; display: flex; align-items: center; gap: 8px;">
				<span class="dashicons dashicons-yes-alt" style="font-size: 24px; width: 24px; height: 24px;"></span>
				<?php echo esc_html( $notice ); ?>
			</h3>

			<?php if ( $generated_page ) : 
				$page_title   = City_Page_Repository::get_derived_title( $generated_page );
				$public_url   = URL_Engine::build_full_url( $generated_page->url_path );
				$preview_url  = URL_Engine::build_preview_url( (int) $generated_page->id );
				$wp_edit_link = ! empty( $generated_page->wp_post_id ) ? get_edit_post_link( (int) $generated_page->wp_post_id ) : '';
				$edit_link    = admin_url( 'admin.php?page=seo-city-pages-pages&action=edit&id=' . (int) $generated_page->id );
			?>
				<p style="font-size: 15px; margin: 6px 0;">
					<strong><?php esc_html_e( 'Page Title:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $page_title ); ?>
				</p>
				<p style="font-size: 14px; margin: 6px 0;">
					<strong><?php esc_html_e( 'URL Path:', 'seo-city-pages' ); ?></strong> 
					<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" style="font-weight: 600; color: #2271b1;">
						<?php echo esc_html( $public_url ); ?>
					</a>
				</p>

				<div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
					<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" class="button button-primary button-hero" style="display: inline-flex; align-items: center; gap: 6px;">
						<span class="dashicons dashicons-external"></span> <?php esc_html_e( 'View Live Page', 'seo-city-pages' ); ?>
					</a>
					<?php if ( $wp_edit_link ) : ?>
						<a href="<?php echo esc_url( $wp_edit_link ); ?>" target="_blank" class="button button-secondary button-hero" style="display: inline-flex; align-items: center; gap: 6px;">
							<span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit in WordPress', 'seo-city-pages' ); ?>
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( $edit_link ); ?>" class="button button-secondary button-hero" style="display: inline-flex; align-items: center; gap: 6px;">
						<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Edit SEO Metadata', 'seo-city-pages' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<div class="scp-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 6px; padding: 24px; max-width: 900px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-top: 15px;">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-pages&action=new' ) ); ?>" id="scp-manual-generate-form">
			<?php Security::nonce_field( 'manual_generate_page' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<!-- Country -->
					<tr>
						<th scope="row">
							<label for="country_input"><strong><?php esc_html_e( 'Country', 'seo-city-pages' ); ?></strong> <span class="required" style="color:red;">*</span></label>
						</th>
						<td>
							<input type="text" name="country_input" id="country_input" value="United States" class="regular-text" required placeholder="e.g. United States or US" list="country-suggestions">
							<datalist id="country-suggestions">
								<?php foreach ( $countries as $c ) : ?>
									<option value="<?php echo esc_attr( $c->name ); ?>"><?php echo esc_html( $c->code ); ?></option>
								<?php endforeach; ?>
							</datalist>
							<p class="description"><?php esc_html_e( 'Country name or ISO code. URL automatically uses short code (e.g. /us/).', 'seo-city-pages' ); ?></p>
						</td>
					</tr>

					<!-- State / Region -->
					<tr>
						<th scope="row">
							<label for="state_input"><strong><?php esc_html_e( 'State / Region', 'seo-city-pages' ); ?></strong></label>
						</th>
						<td>
							<input type="text" name="state_input" id="state_input" value="" class="regular-text" placeholder="e.g. Indiana, California, Texas" list="state-suggestions">
							<datalist id="state-suggestions">
								<?php foreach ( $states as $s ) : ?>
									<option value="<?php echo esc_attr( $s->name ); ?>"><?php echo esc_html( $s->code ); ?></option>
								<?php endforeach; ?>
							</datalist>
							<p class="description"><?php esc_html_e( 'State or region name.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>

					<!-- City / County -->
					<tr>
						<th scope="row">
							<label for="city_input"><strong><?php esc_html_e( 'City / County', 'seo-city-pages' ); ?></strong> <span class="required" style="color:red;">*</span></label>
						</th>
						<td>
							<input type="text" name="city_input" id="city_input" value="" class="regular-text" required placeholder="e.g. Starke or Los Angeles">
							<p class="description"><?php esc_html_e( 'Target city, town, or county name.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>

					<!-- Service / Niche Topic -->
					<tr>
						<th scope="row">
							<label for="service_input"><strong><?php esc_html_e( 'Service / Niche Topic', 'seo-city-pages' ); ?></strong> <span class="required" style="color:red;">*</span></label>
						</th>
						<td>
							<input type="text" name="service_input" id="service_input" value="" class="regular-text" required placeholder="e.g. Dental Clinic or Law Firm" list="service-suggestions">
							<datalist id="service-suggestions">
								<?php foreach ( $services as $sv ) : ?>
									<option value="<?php echo esc_attr( $sv->name ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
							<p class="description"><?php esc_html_e( 'Business topic or niche (e.g. Dental Clinic, Law Firm, HVAC, Real Estate).', 'seo-city-pages' ); ?></p>
						</td>
					</tr>

					<!-- Agency Service Framing -->
					<tr>
						<th scope="row">
							<label for="agency_framing"><strong><?php esc_html_e( 'Agency Service Framing', 'seo-city-pages' ); ?></strong></label>
						</th>
						<td>
							<select name="agency_framing" id="agency_framing" class="regular-text">
								<option value="Best Digital Marketing Agency" selected="selected"><?php esc_html_e( 'Best Digital Marketing Agency (Recommended & Default)', 'seo-city-pages' ); ?></option>
								<option value="Best Digital Marketing"><?php esc_html_e( 'Best Digital Marketing', 'seo-city-pages' ); ?></option>
								<option value="Top Digital Marketing Agency"><?php esc_html_e( 'Top Digital Marketing Agency', 'seo-city-pages' ); ?></option>
								<option value="Leading Digital Marketing Agency"><?php esc_html_e( 'Leading Digital Marketing Agency', 'seo-city-pages' ); ?></option>
								<option value="Best SEO Agency"><?php esc_html_e( 'Best SEO Agency', 'seo-city-pages' ); ?></option>
								<option value="Digital Marketing & SEO"><?php esc_html_e( 'Digital Marketing & SEO', 'seo-city-pages' ); ?></option>
								<option value="Digital Marketing"><?php esc_html_e( 'Digital Marketing (Standard)', 'seo-city-pages' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Commercial intent modifier positioned in the title, URL, and copy to market your agency services to the niche.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>

					<!-- Primary Target Keyword -->
					<tr>
						<th scope="row">
							<label for="primary_keyword"><strong><?php esc_html_e( 'Primary Target Keyword', 'seo-city-pages' ); ?></strong></label>
						</th>
						<td>
							<input type="text" name="primary_keyword" id="primary_keyword" value="" class="regular-text" placeholder="Leave blank to auto-generate (e.g. Best Digital Marketing Agency for Dental Clinic in Starke)">
							<p class="description"><?php esc_html_e( 'Primary high-intent focus keyword. If left blank, it will be automatically built from your framing and location.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>

					<!-- Secondary Target Keywords -->
					<tr>
						<th scope="row">
							<label for="target_keywords"><strong><?php esc_html_e( 'Target Keywords (Google Ads, Meta, SEO)', 'seo-city-pages' ); ?></strong></label>
						</th>
						<td>
							<textarea name="target_keywords" id="target_keywords" rows="3" class="large-text" placeholder="Leave blank to automatically infuse high-intent agency keywords (Google Ads, Meta Ads, Local SEO, PPC Management, Google Map Pack 3-Pack, Conversion Optimization) or provide your own comma-separated list."></textarea>
							<p class="description"><?php esc_html_e( 'Keywords infused into page headings, AI copywriting, and agency services. Auto-filled with Google Ads, Meta Ads, and SEO if left empty.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>

					<!-- Page Status -->
					<tr>
						<th scope="row">
							<label for="page_status"><strong><?php esc_html_e( 'Page Status', 'seo-city-pages' ); ?></strong></label>
						</th>
						<td>
							<select name="page_status" id="page_status">
								<option value="published" selected="selected"><?php esc_html_e( 'Published (Live immediately)', 'seo-city-pages' ); ?></option>
								<option value="draft"><?php esc_html_e( 'Draft (Review before publishing)', 'seo-city-pages' ); ?></option>
							</select>
						</td>
					</tr>

					<!-- Automation Actions -->
					<tr>
						<th scope="row"><strong><?php esc_html_e( 'Automation Actions', 'seo-city-pages' ); ?></strong></th>
						<td>
							<fieldset>
								<label for="run_ai_generation">
									<input type="checkbox" name="run_ai_generation" id="run_ai_generation" value="1" checked="checked">
									<strong><?php esc_html_e( 'Run AI Copywriting Generation immediately', 'seo-city-pages' ); ?></strong>
								</label>
								<p class="description" style="margin-left: 24px;"><?php esc_html_e( 'Generates high-converting, localized headlines, hero section, and 10 agency services content.', 'seo-city-pages' ); ?></p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit" style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #f0f0f1;">
				<button type="submit" name="seo_city_pages_manual_generate" value="1" class="button button-primary button-hero" style="display: inline-flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-admin-page"></span> <?php esc_html_e( '⚡ Generate Landing Page Now', 'seo-city-pages' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-pages' ) ); ?>" class="button button-secondary button-hero" style="margin-left: 10px;">
					<?php esc_html_e( 'Cancel', 'seo-city-pages' ); ?>
				</a>
			</p>
		</form>
	</div>
</div>
