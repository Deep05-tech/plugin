<?php
/**
 * Settings Admin Page View Template.
 *
 * @package SeoCityPages
 */

use SeoCityPages\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-settings" style="font-size: 28px; width: 28px; height: 28px; margin-right: 8px;"></span>
		<?php esc_html_e( 'SEO City Pages — Settings', 'seo-city-pages' ); ?>
	</h1>
	<hr class="wp-header-end">

	<div class="seo-city-pages-card" style="margin-top: 20px;">
		<form method="post" action="options.php">
			<?php
			settings_fields( Settings::SETTINGS_GROUP );
			do_settings_sections( 'seo-city-pages-settings' );
			submit_button( __( 'Save Settings', 'seo-city-pages' ) );
			?>
		</form>
	</div>
</div>
