<?php
/**
 * Content Version Comparison View.
 *
 * Side-by-side section comparison between two versions.
 *
 * @package SeoCityPages\Admin
 */

use SeoCityPages\Database\City_Page_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline">
		<?php printf( esc_html__( 'Compare Content Versions: %s', 'seo-city-pages' ), esc_html( City_Page_Repository::get_derived_title( $page ) ) ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=view&page_id=' . $page->id ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← Back to Review', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( empty( $version_a ) || empty( $version_b ) ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'Two versions are required for comparison.', 'seo-city-pages' ); ?></p></div>
	<?php else : ?>
		<!-- Comparison Header -->
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
			<!-- Left Column: Version A -->
			<div style="background: #f0f6fa; border: 2px solid #02426A; border-radius: 6px; padding: 16px;">
				<h2 style="margin: 0 0 6px; color: #02426A; font-size: 18px;">
					<?php printf( esc_html__( 'Version #%d (%s)', 'seo-city-pages' ), (int) $version_a->version_num, esc_html( ucfirst( $version_a->source ?? 'ai' ) ) ); ?>
				</h2>
				<p style="margin: 0; font-size: 13px; color: #50575e;">
					<span><strong><?php esc_html_e( 'Status:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( str_replace( '_', ' ', $version_a->status ) ); ?></span> &bull;
					<span><strong><?php esc_html_e( 'Date:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $version_a->created_at ); ?></span> &bull;
					<span><strong><?php esc_html_e( 'Tokens:', 'seo-city-pages' ); ?></strong> <?php echo (int) $version_a->total_tokens; ?></span>
				</p>
			</div>

			<!-- Right Column: Version B -->
			<div style="background: #fdf8e2; border: 2px solid #dba617; border-radius: 6px; padding: 16px;">
				<h2 style="margin: 0 0 6px; color: #8c5b00; font-size: 18px;">
					<?php printf( esc_html__( 'Version #%d (%s)', 'seo-city-pages' ), (int) $version_b->version_num, esc_html( ucfirst( $version_b->source ?? 'ai' ) ) ); ?>
				</h2>
				<p style="margin: 0; font-size: 13px; color: #50575e;">
					<span><strong><?php esc_html_e( 'Status:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( str_replace( '_', ' ', $version_b->status ) ); ?></span> &bull;
					<span><strong><?php esc_html_e( 'Date:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $version_b->created_at ); ?></span> &bull;
					<span><strong><?php esc_html_e( 'Tokens:', 'seo-city-pages' ); ?></strong> <?php echo (int) $version_b->total_tokens; ?></span>
				</p>
			</div>
		</div>

		<!-- Section-by-Section Diff Cards -->
		<div style="display: flex; flex-direction: column; gap: 20px;">
			<!-- 1. Hero Title -->
			<div class="postbox" style="padding: 16px 20px;">
				<h3 style="margin: 0 0 12px; color: #02426A;"><?php esc_html_e( '1. Hero Section', 'seo-city-pages' ); ?></h3>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
					<div>
						<label style="font-weight: 700; color: #02426A;"><?php printf( esc_html__( 'v%d Title:', 'seo-city-pages' ), (int) $version_a->version_num ); ?></label>
						<p style="font-size: 16px; font-weight: 600; margin: 4px 0 10px;"><?php echo esc_html( $content_a['hero']['title'] ?? '' ); ?></p>
						<label style="font-weight: 700; color: #02426A;"><?php printf( esc_html__( 'v%d Description:', 'seo-city-pages' ), (int) $version_a->version_num ); ?></label>
						<p style="margin: 4px 0;"><?php echo esc_html( $content_a['hero']['description'] ?? '' ); ?></p>
					</div>
					<div>
						<label style="font-weight: 700; color: #8c5b00;"><?php printf( esc_html__( 'v%d Title:', 'seo-city-pages' ), (int) $version_b->version_num ); ?></label>
						<p style="font-size: 16px; font-weight: 600; margin: 4px 0 10px;"><?php echo esc_html( $content_b['hero']['title'] ?? '' ); ?></p>
						<label style="font-weight: 700; color: #8c5b00;"><?php printf( esc_html__( 'v%d Description:', 'seo-city-pages' ), (int) $version_b->version_num ); ?></label>
						<p style="margin: 4px 0;"><?php echo esc_html( $content_b['hero']['description'] ?? '' ); ?></p>
					</div>
				</div>
			</div>

			<!-- 2. Introduction -->
			<div class="postbox" style="padding: 16px 20px;">
				<h3 style="margin: 0 0 12px; color: #02426A;"><?php esc_html_e( '2. Introduction', 'seo-city-pages' ); ?></h3>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
					<div>
						<h4 style="margin: 0 0 6px; font-size: 15px;"><?php echo esc_html( $content_a['introduction']['heading'] ?? '' ); ?></h4>
						<p><?php echo wp_kses_post( $content_a['introduction']['body'] ?? '' ); ?></p>
					</div>
					<div>
						<h4 style="margin: 0 0 6px; font-size: 15px;"><?php echo esc_html( $content_b['introduction']['heading'] ?? '' ); ?></h4>
						<p><?php echo wp_kses_post( $content_b['introduction']['body'] ?? '' ); ?></p>
					</div>
				</div>
			</div>

			<!-- 3. Benefits Summary -->
			<div class="postbox" style="padding: 16px 20px;">
				<h3 style="margin: 0 0 12px; color: #02426A;"><?php esc_html_e( '3. Benefits & Differentiators', 'seo-city-pages' ); ?></h3>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
					<div>
						<?php foreach ( ( $content_a['benefits'] ?? [] ) as $b ) : ?>
							<div style="margin-bottom: 8px; padding-left: 10px; border-left: 3px solid #02426A;">
								<strong><?php echo esc_html( $b['title'] ?? '' ); ?></strong>
								<p style="margin: 2px 0 0; font-size: 13px;"><?php echo esc_html( $b['description'] ?? '' ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
					<div>
						<?php foreach ( ( $content_b['benefits'] ?? [] ) as $b ) : ?>
							<div style="margin-bottom: 8px; padding-left: 10px; border-left: 3px solid #dba617;">
								<strong><?php echo esc_html( $b['title'] ?? '' ); ?></strong>
								<p style="margin: 2px 0 0; font-size: 13px;"><?php echo esc_html( $b['description'] ?? '' ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- 4. SEO Metadata -->
			<div class="postbox" style="padding: 16px 20px;">
				<h3 style="margin: 0 0 12px; color: #02426A;"><?php esc_html_e( '4. SEO Metadata', 'seo-city-pages' ); ?></h3>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
					<div>
						<p><strong><?php esc_html_e( 'SEO Title:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $content_a['seo']['title'] ?? '' ); ?></p>
						<p><strong><?php esc_html_e( 'Meta Description:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $content_a['seo']['meta_description'] ?? '' ); ?></p>
					</div>
					<div>
						<p><strong><?php esc_html_e( 'SEO Title:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $content_b['seo']['title'] ?? '' ); ?></p>
						<p><strong><?php esc_html_e( 'Meta Description:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $content_b['seo']['meta_description'] ?? '' ); ?></p>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
