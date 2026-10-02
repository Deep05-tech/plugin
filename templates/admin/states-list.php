<?php
/**
 * States List View Template.
 *
 * @package SeoCityPages
 */

use SeoCityPages\Security\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'States & Regions', 'seo-city-pages' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-states&action=add' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New State/Region', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="tablenav top">
		<form method="get" style="float: right; margin-bottom: 10px;">
			<input type="hidden" name="page" value="seo-city-pages-states">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search states...', 'seo-city-pages' ); ?>">
			<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'seo-city-pages' ); ?>">
			<?php if ( ! empty( $search ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-states' ) ); ?>" class="button"><?php esc_html_e( 'Clear', 'seo-city-pages' ); ?></a>
			<?php endif; ?>
		</form>

		<form method="get" style="float: left; margin-bottom: 10px;">
			<input type="hidden" name="page" value="seo-city-pages-states">
			<?php if ( ! empty( $search ) ) : ?>
				<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
			<?php endif; ?>
			<select name="country_id">
				<option value="0"><?php esc_html_e( 'All Countries', 'seo-city-pages' ); ?></option>
				<?php foreach ( $countries as $c ) : ?>
					<option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $c->id, $country_id ); ?>>
						<?php echo esc_html( $c->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'seo-city-pages' ); ?></option>
				<option value="active" <?php selected( 'active', $status ); ?>><?php esc_html_e( 'Active', 'seo-city-pages' ); ?></option>
				<option value="inactive" <?php selected( 'inactive', $status ); ?>><?php esc_html_e( 'Inactive', 'seo-city-pages' ); ?></option>
			</select>
			<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'seo-city-pages' ); ?>">
		</form>
	</div>

	<form method="post">
		<?php echo wp_nonce_field( Security::NONCE_ACTION . '_bulk_states', '_wpnonce', true, false ); ?>
		<input type="hidden" name="seo_city_pages_bulk_action" value="1">

		<div class="alignleft actions bulkactions" style="margin-bottom: 10px;">
			<select name="bulk_action">
				<option value=""><?php esc_html_e( 'Bulk Actions', 'seo-city-pages' ); ?></option>
				<option value="activate"><?php esc_html_e( 'Set Active', 'seo-city-pages' ); ?></option>
				<option value="deactivate"><?php esc_html_e( 'Set Inactive', 'seo-city-pages' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'seo-city-pages' ); ?></option>
			</select>
			<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'seo-city-pages' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to perform this bulk action?', 'seo-city-pages' ) ); ?>');">
		</div>

		<table class="widefat fixed striped">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column" style="width: 30px;"><input type="checkbox" id="cb-select-all-1"></td>
					<th><?php esc_html_e( 'State / Region Name', 'seo-city-pages' ); ?></th>
					<th><?php esc_html_e( 'Country', 'seo-city-pages' ); ?></th>
					<th><?php esc_html_e( 'Code', 'seo-city-pages' ); ?></th>
					<th><?php esc_html_e( 'Slug', 'seo-city-pages' ); ?></th>
					<th><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></th>
					<th><?php esc_html_e( 'Updated', 'seo-city-pages' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $states ) ) : ?>
					<tr>
						<td colspan="7"><?php esc_html_e( 'No states/regions found.', 'seo-city-pages' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $states as $st ) : ?>
						<?php
						$edit_url   = admin_url( 'admin.php?page=seo-city-pages-states&action=edit&id=' . (int) $st->id );
						$delete_url = wp_nonce_url(
							admin_url( 'admin.php?page=seo-city-pages-states&action=delete&id=' . (int) $st->id ),
							Security::NONCE_ACTION . '_delete_state_' . $st->id
						);
						?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="state_ids[]" value="<?php echo esc_attr( $st->id ); ?>">
							</th>
							<td>
								<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $st->name ); ?></a></strong>
								<div class="row-actions">
									<span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'seo-city-pages' ); ?></a> | </span>
									<span class="trash"><a href="<?php echo esc_url( $delete_url ); ?>" style="color: #a00;" onclick="return confirm('<?php echo esc_js( __( 'Delete this state? Dependent cities will block deletion.', 'seo-city-pages' ) ); ?>');"><?php esc_html_e( 'Delete', 'seo-city-pages' ); ?></a></span>
								</div>
							</td>
							<td><?php echo esc_html( $st->country_name ?: '—' ); ?></td>
							<td><code><?php echo esc_html( $st->code ?: '—' ); ?></code></td>
							<td><code><?php echo esc_html( $st->slug ); ?></code></td>
							<td>
								<?php if ( 'active' === $st->status ) : ?>
									<span class="badge badge-pass"><?php esc_html_e( 'Active', 'seo-city-pages' ); ?></span>
								<?php else : ?>
									<span class="badge badge-fail"><?php esc_html_e( 'Inactive', 'seo-city-pages' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $st->updated_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</form>

	<!-- Pagination -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php printf( esc_html__( '%d items', 'seo-city-pages' ), $total_items ); ?></span>
				<?php
				echo paginate_links( [
					'base'    => add_query_arg( 'paged', '%#%' ),
					'format'  => '',
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
					'total'   => $total_pages,
					'current' => $paged,
				] );
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
