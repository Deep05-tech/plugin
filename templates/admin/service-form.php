<?php
/**
 * Service Add / Edit Form Template.
 *
 * @package SeoCityPages
 * @var object|null $service
 * @var string      $action
 * @var int         $id
 */

use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline">
		<?php echo 'edit' === $action ? esc_html__( 'Edit Service', 'seo-city-pages' ) : esc_html__( 'Add New Service', 'seo-city-pages' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-services' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← Back to Services', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="seo-city-pages-card" style="margin-top: 20px; max-width: 900px;">
		<form method="post">
			<?php echo wp_nonce_field( Security::NONCE_ACTION . '_save_service', '_wpnonce', true, false ); ?>
			<input type="hidden" name="seo_city_pages_save_service" value="1">

			<table class="form-table">
				<tbody>
					<!-- Core Service Info -->
					<tr>
						<th scope="row"><label for="service_name"><?php esc_html_e( 'Service Name', 'seo-city-pages' ); ?> <span class="description" style="color:red;">*</span></label></th>
						<td>
							<input name="name" type="text" id="service_name" value="<?php echo esc_attr( $service->name ?? '' ); ?>" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'e.g. SEO Agency, Digital Marketing Agency, Web Development, PPC Agency.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service_slug"><?php esc_html_e( 'Slug', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="slug" type="text" id="service_slug" value="<?php echo esc_attr( $service->slug ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'e.g. seo-agency, digital-marketing, web-development. Auto-generated if left empty.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service_status"><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></label></th>
						<td>
							<select name="status" id="service_status">
								<option value="active" <?php selected( 'active', $service->status ?? 'active' ); ?>><?php esc_html_e( 'Active', 'seo-city-pages' ); ?></option>
								<option value="inactive" <?php selected( 'inactive', $service->status ?? 'active' ); ?>><?php esc_html_e( 'Inactive', 'seo-city-pages' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service_description"><?php esc_html_e( 'Service Description', 'seo-city-pages' ); ?></label></th>
						<td>
							<textarea name="description" id="service_description" rows="4" class="large-text"><?php echo esc_textarea( $service->description ?? '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'General description of this service used for content generation and fallback text.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>

					<!-- Future Content & Fallback Blueprints -->
					<tr>
						<th colspan="2">
							<h3 style="margin-bottom: 0; padding-top: 15px; border-top: 1px solid #ddd;"><?php esc_html_e( 'Default Content & SEO Blueprints', 'seo-city-pages' ); ?></h3>
							<p class="description"><?php esc_html_e( 'These default templates serve as Tier 2 fallback content when generating location pages (you can use tokens like {{city}}, {{state}}, {{country}}).', 'seo-city-pages' ); ?></p>
						</th>
					</tr>

					<tr>
						<th scope="row"><label for="service_hero_title"><?php esc_html_e( 'Default Hero Title', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="default_hero_title" type="text" id="service_hero_title" value="<?php echo esc_attr( $service->default_hero_title ?? '' ); ?>" class="large-text" placeholder="e.g. Top Rated {{service}} in {{city}}">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service_hero_desc"><?php esc_html_e( 'Default Hero Description', 'seo-city-pages' ); ?></label></th>
						<td>
							<textarea name="default_hero_desc" id="service_hero_desc" rows="3" class="large-text" placeholder="e.g. Accelerate your business growth with expert {{service}} services in {{city}}, {{state}}."><?php echo esc_textarea( $service->default_hero_desc ?? '' ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service_intro"><?php esc_html_e( 'Default Introduction', 'seo-city-pages' ); ?></label></th>
						<td>
							<textarea name="default_intro" id="service_intro" rows="4" class="large-text"><?php echo esc_textarea( $service->default_intro ?? '' ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service_cta"><?php esc_html_e( 'Default CTA Button Text', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="default_cta" type="text" id="service_cta" value="<?php echo esc_attr( $service->default_cta ?? '' ); ?>" class="regular-text" placeholder="e.g. Request a Free {{city}} Audit">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service_seo_title"><?php esc_html_e( 'Default SEO Title Blueprint', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="default_seo_title" type="text" id="service_seo_title" value="<?php echo esc_attr( $service->default_seo_title ?? '' ); ?>" class="large-text" placeholder="e.g. Best {{service}} in {{city}} | {{state}} Agency">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service_meta_desc"><?php esc_html_e( 'Default Meta Description Blueprint', 'seo-city-pages' ); ?></label></th>
						<td>
							<textarea name="default_meta_desc" id="service_meta_desc" rows="2" class="large-text" placeholder="e.g. Looking for reliable {{service}} in {{city}}? Partner with our award-winning team today."><?php echo esc_textarea( $service->default_meta_desc ?? '' ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service_ai_instructions"><?php esc_html_e( 'AI Prompt Directives', 'seo-city-pages' ); ?></label></th>
						<td>
							<textarea name="ai_instructions" id="service_ai_instructions" rows="3" class="large-text" placeholder="e.g. Focus on ROI, local search ranking, Google Maps, conversion rate optimization..."><?php echo esc_textarea( $service->ai_instructions ?? '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Custom prompt guidance passed to the AI engine when generating city-specific content for this service.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( 'edit' === $action ? __( 'Update Service', 'seo-city-pages' ) : __( 'Create Service', 'seo-city-pages' ) ); ?>
		</form>
	</div>
</div>
