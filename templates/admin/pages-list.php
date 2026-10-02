<?php
/**
 * SEO City Pages List View Template.
 *
 * @package SeoCityPages\Admin
 * @var object[] $pages
 * @var int      $total_count
 * @var int      $total_pages
 * @var int      $paged
 * @var int      $country_id
 * @var int      $state_id
 * @var int      $city_id
 * @var int      $service_id
 * @var string   $status
 * @var string   $search
 * @var string   $orderby
 * @var string   $order
 * @var object[] $countries
 * @var object[] $states
 * @var object[] $cities
 * @var object[] $services
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bulk_nonce = \SeoCityPages\Security\Security::create_nonce( 'bulk-pages' );
?>

<div class="wrap seo-city-pages-wrap">
	<div class="scp-header">
		<div class="scp-header-left">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'SEO City Pages', 'seo-city-pages' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-import' ) ); ?>" class="page-title-action">
				<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Import More via CSV', 'seo-city-pages' ); ?>
			</a>
			<p class="scp-subtitle"><?php printf( esc_html__( 'Total Generated Landing Pages: %d', 'seo-city-pages' ), (int) $total_count ); ?></p>
		</div>
	</div>

	<!-- Filter & Search Bar -->
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin-bottom: 15px;">
		<input type="hidden" name="page" value="seo-city-pages-pages" />

		<div class="tablenav top" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
			<!-- Country Filter -->
			<select name="country_id">
				<option value=""><?php esc_html_e( '— All Countries —', 'seo-city-pages' ); ?></option>
				<?php foreach ( $countries as $co ) : ?>
					<option value="<?php echo esc_attr( $co->id ); ?>" <?php selected( $country_id, $co->id ); ?>>
						<?php echo esc_html( $co->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<!-- State Filter -->
			<select name="state_id">
				<option value=""><?php esc_html_e( '— All States / Regions —', 'seo-city-pages' ); ?></option>
				<?php foreach ( $states as $st ) : ?>
					<option value="<?php echo esc_attr( $st->id ); ?>" <?php selected( $state_id, $st->id ); ?>>
						<?php echo esc_html( $st->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<!-- City Filter -->
			<select name="city_id">
				<option value=""><?php esc_html_e( '— All Cities —', 'seo-city-pages' ); ?></option>
				<?php foreach ( $cities as $ci ) : ?>
					<option value="<?php echo esc_attr( $ci->id ); ?>" <?php selected( $city_id, $ci->id ); ?>>
						<?php echo esc_html( $ci->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<!-- Service Filter -->
			<select name="service_id">
				<option value=""><?php esc_html_e( '— All Services —', 'seo-city-pages' ); ?></option>
				<?php foreach ( $services as $sv ) : ?>
					<option value="<?php echo esc_attr( $sv->id ); ?>" <?php selected( $service_id, $sv->id ); ?>>
						<?php echo esc_html( $sv->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<!-- Status Filter -->
			<select name="status">
				<option value=""><?php esc_html_e( '— All Statuses —', 'seo-city-pages' ); ?></option>
				<option value="draft" <?php selected( 'draft', $status ); ?>><?php esc_html_e( 'Draft', 'seo-city-pages' ); ?></option>
				<option value="published" <?php selected( 'published', $status ); ?>><?php esc_html_e( 'Published', 'seo-city-pages' ); ?></option>
				<option value="private" <?php selected( 'private', $status ); ?>><?php esc_html_e( 'Private', 'seo-city-pages' ); ?></option>
				<option value="trash" <?php selected( 'trash', $status ); ?>><?php esc_html_e( 'Trash', 'seo-city-pages' ); ?></option>
			</select>

			<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'seo-city-pages' ); ?>" />

			<!-- Search Box -->
			<div style="margin-left: auto; display: flex; gap: 4px;">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search pages or cities...', 'seo-city-pages' ); ?>" />
				<input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'seo-city-pages' ); ?>" />
			</div>
		</div>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-pages' ) ); ?>">
		<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $bulk_nonce ); ?>" />

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<select name="action">
					<option value=""><?php esc_html_e( 'Bulk Actions', 'seo-city-pages' ); ?></option>
					<option value="published"><?php esc_html_e( 'Set Status: Published', 'seo-city-pages' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Set Status: Draft', 'seo-city-pages' ); ?></option>
					<option value="private"><?php esc_html_e( 'Set Status: Private', 'seo-city-pages' ); ?></option>
					<option value="trash"><?php esc_html_e( 'Move to Trash', 'seo-city-pages' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Permanently Delete', 'seo-city-pages' ); ?></option>
				</select>
				<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'seo-city-pages' ); ?>" />
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column" style="width: 30px;">
						<input type="checkbox" id="cb-select-all-1" />
					</td>
					<th scope="col"><?php esc_html_e( 'Page Name', 'seo-city-pages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Country', 'seo-city-pages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'State / Region', 'seo-city-pages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'City', 'seo-city-pages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Service', 'seo-city-pages' ); ?></th>
					<th scope="col"><?php esc_html_e( 'URL Route', 'seo-city-pages' ); ?></th>
					<th scope="col" style="width: 100px;"><?php esc_html_e( 'Status', 'seo-city-pages' ); ?></th>
					<th scope="col" style="width: 130px;"><?php esc_html_e( 'Created', 'seo-city-pages' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $pages ) ) : ?>
					<tr>
						<td colspan="9"><?php esc_html_e( 'No SEO City Pages found matching your criteria.', 'seo-city-pages' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $pages as $p ) : ?>
						<?php
						$edit_url    = admin_url( 'admin.php?page=seo-city-pages-pages&action=edit&id=' . (int) $p->id );
						$preview_url = \SeoCityPages\URL\URL_Engine::build_preview_url( (int) $p->id );
						$public_url  = \SeoCityPages\URL\URL_Engine::build_full_url( $p->url_path );
						$delete_url  = wp_nonce_url(
							admin_url( 'admin.php?page=seo-city-pages-pages&action=delete&id=' . (int) $p->id ),
							'delete_page_' . (int) $p->id
						);
						$derived_title = \SeoCityPages\Database\City_Page_Repository::get_derived_title( $p );
						?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="page_ids[]" value="<?php echo esc_attr( $p->id ); ?>" />
							</th>
							<td>
								<strong>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="row-title">
										<?php echo esc_html( $derived_title ); ?>
									</a>
								</strong>
								<?php if ( ! empty( $p->title_override ) ) : ?>
									<span class="description" style="display: block; font-size: 11px;"><?php printf( esc_html__( 'Override: %s', 'seo-city-pages' ), esc_html( $p->title_override ) ); ?></span>
								<?php endif; ?>
								<div class="row-actions">
									<span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'seo-city-pages' ); ?></a> | </span>
									<span class="view"><a href="<?php echo esc_url( $preview_url ); ?>" target="_blank"><?php esc_html_e( 'Preview', 'seo-city-pages' ); ?></a> | </span>
									<span class="trash"><a href="<?php echo esc_url( $delete_url ); ?>" style="color: #a00;" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this SEO City Page?', 'seo-city-pages' ) ); ?>');"><?php esc_html_e( 'Delete', 'seo-city-pages' ); ?></a></span>
								</div>
							</td>
							<td><?php echo esc_html( $p->country_name ?? '—' ); ?></td>
							<td><?php echo esc_html( $p->state_name ?? '—' ); ?></td>
							<td><strong><?php echo esc_html( $p->city_name ?? '—' ); ?></strong></td>
							<td><?php echo esc_html( $p->service_name ?? '—' ); ?></td>
							<td>
								<code><?php echo esc_html( $p->url_path ); ?></code>
								<?php if ( 'published' === $p->status ) : ?>
									<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" style="text-decoration: none; margin-left: 4px;" title="<?php esc_attr_e( 'Visit Public Page', 'seo-city-pages' ); ?>">
										<span class="dashicons dashicons-external" style="font-size: 14px; vertical-align: middle;"></span>
									</a>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( 'published' === $p->status ) : ?>
									<span class="scp-badge scp-badge-active"><?php esc_html_e( 'Published', 'seo-city-pages' ); ?></span>
								<?php elseif ( 'draft' === $p->status ) : ?>
									<span class="scp-badge scp-badge-warning"><?php esc_html_e( 'Draft', 'seo-city-pages' ); ?></span>
								<?php elseif ( 'private' === $p->status ) : ?>
									<span class="scp-badge scp-badge-info"><?php esc_html_e( 'Private', 'seo-city-pages' ); ?></span>
								<?php else : ?>
									<span class="scp-badge scp-badge-inactive"><?php echo esc_html( ucfirst( $p->status ) ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $p->created_at ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<!-- Pagination Footer -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom" style="margin-top: 15px;">
				<div class="tablenav-pages">
					<span class="displaying-num"><?php printf( esc_html__( '%d items', 'seo-city-pages' ), (int) $total_count ); ?></span>
					<?php
					echo paginate_links( [
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
						'total'     => $total_pages,
						'current'   => $paged,
					] );
					?>
				</div>
			</div>
		<?php endif; ?>
	</form>
</div>
