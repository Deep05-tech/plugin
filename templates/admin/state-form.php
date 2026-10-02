<?php
/**
 * State Add / Edit Form Template.
 *
 * @package SeoCityPages
 * @var object|null $state
 * @var object[]    $countries
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
		<?php echo 'edit' === $action ? esc_html__( 'Edit State/Region', 'seo-city-pages' ) : esc_html__( 'Add New State/Region', 'seo-city-pages' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-states' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← Back to States', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="seo-city-pages-card" style="margin-top: 20px; max-width: 800px;">
		<form method="post">
			<?php echo wp_nonce_field( Security::NONCE_ACTION . '_save_state', '_wpnonce', true, false ); ?>
			<input type="hidden" name="seo_city_pages_save_state" value="1">

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="state_country_id"><?php esc_html_e( 'Parent Country', 'seo-city-pages' ); ?> <span class="description" style="color:red;">*</span></label></th>
						<td>
							<select name="country_id" id="state_country_id" required style="min-width: 250px;">
								<option value=""><?php esc_html_e( '— Select Country —', 'seo-city-pages' ); ?></option>
								<?php foreach ( $countries as $c ) : ?>
									<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $c->id, $state->country_id ?? '' ); ?>>
										<?php echo esc_html( $c->name ); ?> (<?php echo esc_html( $c->code ?: '—' ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select the country to which this state or region belongs.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="state_name"><?php esc_html_e( 'State / Region Name', 'seo-city-pages' ); ?> <span class="description" style="color:red;">*</span></label></th>
						<td>
							<input name="name" type="text" id="state_name" value="<?php echo esc_attr( $state->name ?? '' ); ?>" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'e.g. Gujarat, Maharashtra, California, Texas.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="state_code"><?php esc_html_e( 'State Code / Abbreviation', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="code" type="text" id="state_code" value="<?php echo esc_attr( $state->code ?? '' ); ?>" class="small-text" maxlength="20">
							<p class="description"><?php esc_html_e( 'e.g. GJ, MH, CA, TX.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="state_slug"><?php esc_html_e( 'Slug', 'seo-city-pages' ); ?></label></th>
						<td>
							<input name="slug" type="text" id="state_slug" value="<?php echo esc_attr( $state->slug ?? '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Leave empty to auto-generate from state name.', 'seo-city-pages' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="state_status"><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></label></th>
						<td>
							<select name="status" id="state_status">
								<option value="active" <?php selected( 'active', $state->status ?? 'active' ); ?>><?php esc_html_e( 'Active', 'seo-city-pages' ); ?></option>
								<option value="inactive" <?php selected( 'inactive', $state->status ?? 'active' ); ?>><?php esc_html_e( 'Inactive', 'seo-city-pages' ); ?></option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( 'edit' === $action ? __( 'Update State/Region', 'seo-city-pages' ) : __( 'Create State/Region', 'seo-city-pages' ) ); ?>
		</form>
	</div>
</div>
