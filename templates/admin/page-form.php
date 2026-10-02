<?php
/**
 * SEO City Page Edit Form Template.
 *
 * @package SeoCityPages\Admin
 * @var int    $page_id
 * @var object $page
 * @var string $notice
 * @var string $error
 * @var string $preview_url
 * @var string $public_url
 */

use SeoCityPages\Security\Security;
use SeoCityPages\Database\City_Page_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$derived_title = City_Page_Repository::get_derived_title( $page );
?>

<div class="wrap seo-city-pages-wrap">
	<div class="scp-header">
		<div class="scp-header-left">
			<h1 class="wp-heading-inline">
				<?php printf( esc_html__( 'Edit SEO City Page #%d', 'seo-city-pages' ), (int) $page_id ); ?>
			</h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-pages' ) ); ?>" class="page-title-action">
				<?php esc_html_e( '← Back to Pages', 'seo-city-pages' ); ?>
			</a>
		</div>
		<div class="scp-header-actions" style="margin-top: 10px;">
			<a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" class="button button-secondary">
				<span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span>
				<?php esc_html_e( 'Preview Landing Page', 'seo-city-pages' ); ?>
			</a>
			<?php if ( 'published' === $page->status ) : ?>
				<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" class="button button-secondary" style="margin-left: 5px;">
					<span class="dashicons dashicons-external" style="vertical-align: middle;"></span>
					<?php esc_html_e( 'View Public Page', 'seo-city-pages' ); ?>
				</a>
			<?php endif; ?>
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

	<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px; max-width: 1200px;">
		<!-- Main Form Column -->
		<div>
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Page Configuration & Overrides', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px 20px;">
					<form method="post" action="">
						<?php Security::nonce_field( 'save_page_' . $page_id ); ?>
						<input type="hidden" name="seo_city_pages_save_page" value="1" />

						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<label for="scp_status"><strong><?php esc_html_e( 'Publishing Status', 'seo-city-pages' ); ?></strong></label>
									</th>
									<td>
										<select name="status" id="scp_status" style="min-width: 200px;">
											<option value="draft" <?php selected( $page->status, 'draft' ); ?>><?php esc_html_e( 'Draft (Default / Not Public)', 'seo-city-pages' ); ?></option>
											<option value="published" <?php selected( $page->status, 'published' ); ?>><?php esc_html_e( 'Published (Live & Indexable)', 'seo-city-pages' ); ?></option>
											<option value="private" <?php selected( $page->status, 'private' ); ?>><?php esc_html_e( 'Private (Admins Only)', 'seo-city-pages' ); ?></option>
											<option value="trash" <?php selected( $page->status, 'trash' ); ?>><?php esc_html_e( 'Trash', 'seo-city-pages' ); ?></option>
										</select>
										<p class="description">
											<?php esc_html_e( 'Draft and Private pages return a 404 response to public search crawlers and anonymous visitors.', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_title_override"><strong><?php esc_html_e( 'Title Override', 'seo-city-pages' ); ?></strong></label>
									</th>
									<td>
										<input type="text" name="title_override" id="scp_title_override" class="regular-text" style="width: 100%; max-width: 500px;" value="<?php echo esc_attr( $page->title_override ?? '' ); ?>" placeholder="<?php echo esc_attr( $derived_title ); ?>" />
										<p class="description">
											<?php printf( esc_html__( 'Leave blank to use the default derived title: "%s"', 'seo-city-pages' ), esc_html( $derived_title ) ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_custom_slug"><strong><?php esc_html_e( 'Custom Slug Override', 'seo-city-pages' ); ?></strong></label>
									</th>
									<td>
										<input type="text" name="custom_slug" id="scp_custom_slug" class="regular-text" style="width: 100%; max-width: 500px;" value="<?php echo esc_attr( $page->custom_slug ?? '' ); ?>" placeholder="<?php echo esc_attr( ( $page->service_slug ?? '' ) . '-' . ( $page->city_slug ?? '' ) ); ?>" />
										<p class="description">
											<?php esc_html_e( 'Optional custom slug override for fine-grained URL tuning.', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<strong><?php esc_html_e( 'Resolved URL Path', 'seo-city-pages' ); ?></strong>
									</th>
									<td>
										<code style="font-size: 14px; padding: 6px 10px; background: #f0f0f1; display: inline-block; border-radius: 4px; border: 1px solid #ccd0d4;">
											<?php echo esc_html( $page->url_path ); ?>
										</code>
										<p class="description">
											<?php esc_html_e( 'Generated deterministically by the URL Architecture engine from active location and service tokens.', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>
							</tbody>
						</table>

						<!-- SEO Metadata & Overrides Section -->
						<h3 style="margin-top: 30px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 1px solid #c3c4c7; font-size: 15px;">
							<span class="dashicons dashicons-search" style="vertical-align: middle; margin-right: 4px; color: #2271b1;"></span>
							<?php esc_html_e( 'SEO Metadata & Search Engine Directives', 'seo-city-pages' ); ?>
						</h3>

						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row">
										<?php esc_html_e( 'Manual SEO Override', 'seo-city-pages' ); ?>
									</th>
									<td>
										<label>
											<input type="checkbox" name="is_manual_seo_override" value="1" <?php checked( ! empty( $page->is_manual_seo_override ), true ); ?> />
											<strong><?php esc_html_e( 'Lock Manual Overrides (Prevents AI content regeneration from overwriting custom SEO title & description)', 'seo-city-pages' ); ?></strong>
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_seo_title"><strong><?php esc_html_e( 'SEO Meta Title', 'seo-city-pages' ); ?></strong></label>
									</th>
									<td>
										<input type="text" name="seo_title" id="scp_seo_title" class="large-text" value="<?php echo esc_attr( $page->seo_title ?? '' ); ?>" placeholder="<?php echo esc_attr( $derived_title ); ?>" />
										<div style="font-size: 12px; color: #646970; margin-top: 4px;">
											<span id="page-title-count">0</span> / 60 <?php esc_html_e( 'recommended characters. Leave empty to use global template.', 'seo-city-pages' ); ?>
										</div>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_meta_description"><strong><?php esc_html_e( 'Meta Description', 'seo-city-pages' ); ?></strong></label>
									</th>
									<td>
										<textarea name="meta_description" id="scp_meta_description" rows="3" class="large-text"><?php echo esc_textarea( $page->meta_description ?? '' ); ?></textarea>
										<div style="font-size: 12px; color: #646970; margin-top: 4px;">
											<span id="page-desc-count">0</span> / 160 <?php esc_html_e( 'recommended characters. Leave empty to use global template.', 'seo-city-pages' ); ?>
										</div>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_canonical_url"><?php esc_html_e( 'Canonical URL Override', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<input type="url" name="canonical_url" id="scp_canonical_url" class="large-text" value="<?php echo esc_attr( $page->canonical_url ?? '' ); ?>" placeholder="<?php echo esc_attr( $public_url ); ?>" />
										<p class="description">
											<?php esc_html_e( 'Leave blank to use the standard canonical URL for this page.', 'seo-city-pages' ); ?>
										</p>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_robots_index"><?php esc_html_e( 'Robots Indexing', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<select name="robots_index" id="scp_robots_index">
											<option value="default" <?php selected( $page->robots_index ?? 'default', 'default' ); ?>><?php esc_html_e( 'Use Global Default', 'seo-city-pages' ); ?></option>
											<option value="index" <?php selected( $page->robots_index ?? '', 'index' ); ?>><?php esc_html_e( 'Index (index)', 'seo-city-pages' ); ?></option>
											<option value="noindex" <?php selected( $page->robots_index ?? '', 'noindex' ); ?>><?php esc_html_e( 'Noindex (noindex)', 'seo-city-pages' ); ?></option>
										</select>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_robots_follow"><?php esc_html_e( 'Robots Follow', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<select name="robots_follow" id="scp_robots_follow">
											<option value="default" <?php selected( $page->robots_follow ?? 'default', 'default' ); ?>><?php esc_html_e( 'Use Global Default', 'seo-city-pages' ); ?></option>
											<option value="follow" <?php selected( $page->robots_follow ?? '', 'follow' ); ?>><?php esc_html_e( 'Follow (follow)', 'seo-city-pages' ); ?></option>
											<option value="nofollow" <?php selected( $page->robots_follow ?? '', 'nofollow' ); ?>><?php esc_html_e( 'Nofollow (nofollow)', 'seo-city-pages' ); ?></option>
										</select>
									</td>
								</tr>

								<tr>
									<th scope="row">
										<label for="scp_schema_type"><?php esc_html_e( 'Schema Structured Data', 'seo-city-pages' ); ?></label>
									</th>
									<td>
										<select name="schema_type" id="scp_schema_type">
											<option value="default" <?php selected( $page->schema_type ?? 'default', 'default' ); ?>><?php esc_html_e( 'Use Global Default (Service)', 'seo-city-pages' ); ?></option>
											<option value="Service" <?php selected( $page->schema_type ?? '', 'Service' ); ?>><?php esc_html_e( 'Service', 'seo-city-pages' ); ?></option>
											<option value="LocalBusiness" <?php selected( $page->schema_type ?? '', 'LocalBusiness' ); ?>><?php esc_html_e( 'LocalBusiness', 'seo-city-pages' ); ?></option>
											<option value="ProfessionalService" <?php selected( $page->schema_type ?? '', 'ProfessionalService' ); ?>><?php esc_html_e( 'ProfessionalService', 'seo-city-pages' ); ?></option>
											<option value="none" <?php selected( $page->schema_type ?? '', 'none' ); ?>><?php esc_html_e( 'Disable Schema for this page', 'seo-city-pages' ); ?></option>
										</select>
									</td>
								</tr>
							</tbody>
						</table>

						<div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #dcdcde;">
							<button type="submit" class="button button-primary button-large">
								<?php esc_html_e( 'Save SEO City Page', 'seo-city-pages' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Sidebar / Source Info Column -->
		<div>
			<div class="postbox">
				<div class="postbox-header">
					<h2 class="hndle"><?php esc_html_e( 'Source Location & Service', 'seo-city-pages' ); ?></h2>
				</div>
				<div class="inside" style="padding: 15px;">
					<p style="margin-top: 0; color: #646970; font-size: 13px;">
						<?php esc_html_e( 'This page is bound to the following verified relational entities:', 'seo-city-pages' ); ?>
					</p>

					<div style="margin-bottom: 12px; padding: 10px; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 4px;">
						<strong style="color: #50575e; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;"><?php esc_html_e( 'Service', 'seo-city-pages' ); ?></strong>
						<div style="font-size: 14px; font-weight: 600; margin-top: 2px;">
							<?php echo esc_html( $page->service_name ?? '—' ); ?>
						</div>
						<div style="font-size: 12px; color: #666;">
							Slug: <code><?php echo esc_html( $page->service_slug ?? '—' ); ?></code>
						</div>
					</div>

					<div style="margin-bottom: 12px; padding: 10px; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 4px;">
						<strong style="color: #50575e; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;"><?php esc_html_e( 'City', 'seo-city-pages' ); ?></strong>
						<div style="font-size: 14px; font-weight: 600; margin-top: 2px;">
							<?php echo esc_html( $page->city_name ?? '—' ); ?>
						</div>
						<div style="font-size: 12px; color: #666;">
							Slug: <code><?php echo esc_html( $page->city_slug ?? '—' ); ?></code>
						</div>
					</div>

					<div style="margin-bottom: 12px; padding: 10px; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 4px;">
						<strong style="color: #50575e; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;"><?php esc_html_e( 'State / Region', 'seo-city-pages' ); ?></strong>
						<div style="font-size: 14px; font-weight: 600; margin-top: 2px;">
							<?php echo esc_html( $page->state_name ?? '—' ); ?>
						</div>
						<div style="font-size: 12px; color: #666;">
							Slug: <code><?php echo esc_html( $page->state_slug ?? '—' ); ?></code>
						</div>
					</div>

					<div style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 4px;">
						<strong style="color: #50575e; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;"><?php esc_html_e( 'Country', 'seo-city-pages' ); ?></strong>
						<div style="font-size: 14px; font-weight: 600; margin-top: 2px;">
							<?php echo esc_html( $page->country_name ?? '—' ); ?>
						</div>
						<div style="font-size: 12px; color: #666;">
							Code: <code><?php echo esc_html( $page->country_code ?? '—' ); ?></code>
						</div>
					</div>

					<div style="font-size: 12px; color: #8c8f94; border-top: 1px solid #eee; padding-top: 10px;">
						<div><?php printf( esc_html__( 'Created: %s', 'seo-city-pages' ), esc_html( $page->created_at ?? '—' ) ); ?></div>
						<div><?php printf( esc_html__( 'Last Updated: %s', 'seo-city-pages' ), esc_html( $page->updated_at ?? '—' ) ); ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
