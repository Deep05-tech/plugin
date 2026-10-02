<?php
/**
 * Country Add / Edit Form Template.
 *
 * @package SeoCityPages
 * @var object|null $country
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
		<?php echo 'edit' === $action ? esc_html__( 'Edit Country', 'seo-city-pages' ) : esc_html__( 'Add New Country', 'seo-city-pages' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-countries' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← Back to Countries', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="seo-city-pages-card" style="margin-top: 20px; max-width: 800px;">
		<form method="post">
			<?php echo wp_nonce_field( Security::NONCE_ACTION . '_save_country', '_wpnonce', true, false ); ?>
			<input type="hidden" name="seo_city_pages_save_country" value="1">

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="country_name"><?php esc_html_e( 'Country Name', 'seo-city-pages' ); ?> <span class="description" style="color:red;">*</span></label></th>
						<td>
							<input name="name" type="text" id="country_name" value="<?php echo esc_attr( $country->name ?? '' ); ?>" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'e.g. India, United States, United Kingdom.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="country_code"><?php esc_html_e( 'ISO Code (2-3 chars)', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="code" type="text" id="country_code" value="<?php echo esc_attr( $country->code ?? '' ); ?>" class="small-text" maxlength="10">
							<p class="description"><?php esc_html_e( 'e.g. IN, US, GB.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="country_slug"><?php esc_html_e( 'Slug', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="slug" type="text" id="country_slug" value="<?php echo esc_attr( $country->slug ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Leave empty to auto-generate from country name.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="country_status"><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></label></th>
						<td>
							<select name="status" id="country_status">
								<option value="active" <?php selected( 'active', $country->status ?? 'active' ); ?>><?php esc_html_e( 'Active', 'seo-city-pages' ); ?></option>
								<option value="inactive" <?php selected( 'inactive', $country->status ?? 'active' ); ?>><?php esc_html_e( 'Inactive', 'seo-city-pages' ); ?></option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( 'edit' === $action ? __( 'Update Country', 'seo-city-pages' ) : __( 'Create Country', 'seo-city-pages' ) ); ?>
		</form>
	</div>
</div>
