<?php
/**
 * City Add / Edit Form Template.
 *
 * @package SeoCityPages
 * @var object|null $city
 * @var object[]    $countries
 * @var object[]    $states
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
		<?php echo 'edit' === $action ? esc_html__( 'Edit City', 'seo-city-pages' ) : esc_html__( 'Add New City', 'seo-city-pages' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-cities' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← Back to Cities', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="seo-city-pages-card" style="margin-top: 20px; max-width: 900px;">
		<form method="post">
			<?php echo wp_nonce_field( Security::NONCE_ACTION . '_save_city', '_wpnonce', true, false ); ?>
			<input type="hidden" name="seo_city_pages_save_city" value="1">

			<table class="form-table">
				<tbody>
					<!-- Location Hierarchy -->
					<tr>
						<th scope="row"><label for="city_country_id"><?php esc_html_e( 'Country', 'seo-city-pages' ); ?> <span class="description" style="color:red;">*</span></label></th>
						<td>
							<select name="country_id" id="city_country_id" required style="min-width: 250px;">
								<option value=""><?php esc_html_e( '— Select Country —', 'seo-city-pages' ); ?></option>
								<?php foreach ( $countries as $c ) : ?>
									<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $c->id, $city->country_id ?? $selected_country_id ); ?>>
										<?php echo esc_html( $c->name ); ?> (<?php echo esc_html( $c->code ?: '—' ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="city_state_id"><?php esc_html_e( 'State / Region', 'seo-city-pages' ); ?> <span class="description" style="color:red;">*</span></label></th>
						<td>
							<select name="state_id" id="city_state_id" required style="min-width: 250px;">
								<option value=""><?php esc_html_e( '— Select State / Region —', 'seo-city-pages' ); ?></option>
								<?php foreach ( $states as $st ) : ?>
									<option value="<?php echo esc_attr( $st->id ); ?>" data-country="<?php echo esc_attr( $st->country_id ); ?>" <?php selected( $st->id, $city->state_id ?? '' ); ?>>
										<?php echo esc_html( $st->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Core City Info -->
					<tr>
						<th scope="row"><label for="city_name"><?php esc_html_e( 'City Name', 'seo-city-pages' ); ?> <span class="description" style="color:red;">*</span></label></th>
						<td>
							<input name="name" type="text" id="city_name" value="<?php echo esc_attr( $city->name ?? '' ); ?>" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'e.g. Rajkot, Ahmedabad, Surat, Mumbai, Los Angeles.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="city_slug"><?php esc_html_e( 'Slug', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="slug" type="text" id="city_slug" value="<?php echo esc_attr( $city->slug ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Leave empty to auto-generate from city name.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="city_status"><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></label></th>
						<td>
							<select name="status" id="city_status">
								<option value="active" <?php selected( 'active', $city->status ?? 'active' ); ?>><?php esc_html_e( 'Active', 'seo-city-pages' ); ?></option>
								<option value="inactive" <?php selected( 'inactive', $city->status ?? 'active' ); ?>><?php esc_html_e( 'Inactive', 'seo-city-pages' ); ?></option>
							</select>
						</td>
					</tr>

					<!-- Optional Coordinates -->
					<tr>
						<th scope="row"><label for="city_latitude"><?php esc_html_e( 'Coordinates (Optional)', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="latitude" type="number" step="any" id="city_latitude" value="<?php echo esc_attr( $city->latitude ?? '' ); ?>" placeholder="Latitude (e.g. 22.3039)" class="regular-text" style="width: 180px; margin-right: 10px;">
							<input name="longitude" type="number" step="any" id="city_longitude" value="<?php echo esc_attr( $city->longitude ?? '' ); ?>" placeholder="Longitude (e.g. 70.8022)" class="regular-text" style="width: 180px;">
							<p class="description"><?php esc_html_e( 'Used for local geo-schema and map embedding in future phases.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>

					<!-- Optional Local Data Fields -->
					<tr>
						<th scope="row"><label for="city_local_industries"><?php esc_html_e( 'Local Industries / Economic Hubs', 'seo-city-pages' ); ?></label></th>
						<td>
							<textarea name="local_industries" id="city_local_industries" rows="3" class="large-text"><?php echo esc_textarea( $city->local_industries ?? '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'e.g. Engineering, Casting, Jewelry, Textile, IT software.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="city_areas_served"><?php esc_html_e( 'Areas Served / Neighborhoods', 'seo-city-pages' ); ?></label></th>
						<td>
							<textarea name="areas_served" id="city_areas_served" rows="3" class="large-text"><?php echo esc_textarea( $city->areas_served ?? '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'e.g. Kalawad Road, Yagnik Road, Gondal Road, Mavdi.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="city_description"><?php esc_html_e( 'City Description / Local Context', 'seo-city-pages' ); ?></label></th>
						<td>
							<textarea name="description" id="city_description" rows="4" class="large-text"><?php echo esc_textarea( $city->description ?? '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'General summary of the city used for local dynamic tag insertion.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="city_notes"><?php esc_html_e( 'Internal Admin Notes', 'seo-city-pages' ); ?></label></th>
						<td>
							<textarea name="notes" id="city_notes" rows="2" class="large-text"><?php echo esc_textarea( $city->notes ?? '' ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( 'edit' === $action ? __( 'Update City', 'seo-city-pages' ) : __( 'Create City', 'seo-city-pages' ) ); ?>
		</form>
	</div>
</div>
