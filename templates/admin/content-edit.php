<?php
/**
 * Manual Content Editor View.
 *
 * @package SeoCityPages\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline">
		<?php printf( esc_html__( 'Edit Content Fields: %s', 'seo-city-pages' ), esc_html( \SeoCityPages\Database\City_Page_Repository::get_derived_title( $page ) ) ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=view&page_id=' . $page->id ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← Back to Review', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<form id="scp-content-edit-form" style="margin-top: 20px;">
		<input type="hidden" name="version_id" value="<?php echo esc_attr( $version->id ?? 0 ); ?>">
		
		<div style="display: flex; flex-direction: column; gap: 20px;">
			<!-- 1. Hero -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( '1. Hero Section', 'seo-city-pages' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Hero Title (H1)', 'seo-city-pages' ); ?></label></th>
						<td><input type="text" name="content[hero][title]" value="<?php echo esc_attr( $content['hero']['title'] ?? '' ); ?>" class="large-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Hero Description', 'seo-city-pages' ); ?></label></th>
						<td><textarea name="content[hero][description]" rows="3" class="large-text" required><?php echo esc_textarea( $content['hero']['description'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Hero CTA Button', 'seo-city-pages' ); ?></label></th>
						<td><input type="text" name="content[hero][cta_text]" value="<?php echo esc_attr( $content['hero']['cta_text'] ?? '' ); ?>" class="regular-text" required></td>
					</tr>
				</table>
			</div>

			<!-- 2. Introduction -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( '2. Introduction', 'seo-city-pages' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Intro Heading', 'seo-city-pages' ); ?></label></th>
						<td><input type="text" name="content[introduction][heading]" value="<?php echo esc_attr( $content['introduction']['heading'] ?? '' ); ?>" class="large-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Intro Body', 'seo-city-pages' ); ?></label></th>
						<td><textarea name="content[introduction][body]" rows="4" class="large-text" required><?php echo esc_textarea( $content['introduction']['body'] ?? '' ); ?></textarea></td>
					</tr>
				</table>
			</div>

			<!-- 3. Service Overview -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( '3. Service Overview', 'seo-city-pages' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Overview Heading', 'seo-city-pages' ); ?></label></th>
						<td><input type="text" name="content[service_overview][heading]" value="<?php echo esc_attr( $content['service_overview']['heading'] ?? '' ); ?>" class="large-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Overview Body', 'seo-city-pages' ); ?></label></th>
						<td><textarea name="content[service_overview][body]" rows="4" class="large-text" required><?php echo esc_textarea( $content['service_overview']['body'] ?? '' ); ?></textarea></td>
					</tr>
				</table>
			</div>

			<!-- 4. Benefits (1 to 3) -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( '4. Benefits & Differentiators', 'seo-city-pages' ); ?></h2>
				<?php for ( $i = 0; $i < 3; $i++ ) : ?>
					<?php $b = $content['benefits'][ $i ] ?? []; ?>
					<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; margin-bottom: 12px;">
						<label style="font-weight: 600; display: block; margin-bottom: 4px;"><?php printf( esc_html__( 'Benefit #%d Title', 'seo-city-pages' ), $i + 1 ); ?></label>
						<input type="text" name="content[benefits][<?php echo esc_attr( $i ); ?>][title]" value="<?php echo esc_attr( $b['title'] ?? '' ); ?>" class="large-text" style="margin-bottom: 8px;" required>
						<label style="font-weight: 600; display: block; margin-bottom: 4px;"><?php printf( esc_html__( 'Benefit #%d Description', 'seo-city-pages' ), $i + 1 ); ?></label>
						<textarea name="content[benefits][<?php echo esc_attr( $i ); ?>][description]" rows="2" class="large-text" required><?php echo esc_textarea( $b['description'] ?? '' ); ?></textarea>
					</div>
				<?php endfor; ?>
			</div>

			<!-- 5. Process (1 to 3) -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( '5. Execution Process', 'seo-city-pages' ); ?></h2>
				<?php for ( $i = 0; $i < 3; $i++ ) : ?>
					<?php $p = $content['process'][ $i ] ?? []; ?>
					<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; margin-bottom: 12px;">
						<input type="hidden" name="content[process][<?php echo esc_attr( $i ); ?>][step_number]" value="<?php echo esc_attr( $i + 1 ); ?>">
						<label style="font-weight: 600; display: block; margin-bottom: 4px;"><?php printf( esc_html__( 'Step #%d Title', 'seo-city-pages' ), $i + 1 ); ?></label>
						<input type="text" name="content[process][<?php echo esc_attr( $i ); ?>][title]" value="<?php echo esc_attr( $p['title'] ?? '' ); ?>" class="large-text" style="margin-bottom: 8px;" required>
						<label style="font-weight: 600; display: block; margin-bottom: 4px;"><?php printf( esc_html__( 'Step #%d Description', 'seo-city-pages' ), $i + 1 ); ?></label>
						<textarea name="content[process][<?php echo esc_attr( $i ); ?>][description]" rows="2" class="large-text" required><?php echo esc_textarea( $p['description'] ?? '' ); ?></textarea>
					</div>
				<?php endfor; ?>
			</div>

			<!-- 6. Local Relevance -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( '6. Local Relevance', 'seo-city-pages' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Heading', 'seo-city-pages' ); ?></label></th>
						<td><input type="text" name="content[local_relevance][heading]" value="<?php echo esc_attr( $content['local_relevance']['heading'] ?? '' ); ?>" class="large-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Body', 'seo-city-pages' ); ?></label></th>
						<td><textarea name="content[local_relevance][body]" rows="4" class="large-text" required><?php echo esc_textarea( $content['local_relevance']['body'] ?? '' ); ?></textarea></td>
					</tr>
				</table>
			</div>

			<!-- 7. FAQs (1 to 3) -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( '7. Frequently Asked Questions', 'seo-city-pages' ); ?></h2>
				<?php for ( $i = 0; $i < 3; $i++ ) : ?>
					<?php $faq = $content['faqs'][ $i ] ?? []; ?>
					<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; margin-bottom: 12px;">
						<label style="font-weight: 600; display: block; margin-bottom: 4px;"><?php printf( esc_html__( 'Question #%d', 'seo-city-pages' ), $i + 1 ); ?></label>
						<input type="text" name="content[faqs][<?php echo esc_attr( $i ); ?>][question]" value="<?php echo esc_attr( $faq['question'] ?? '' ); ?>" class="large-text" style="margin-bottom: 8px;" required>
						<label style="font-weight: 600; display: block; margin-bottom: 4px;"><?php printf( esc_html__( 'Answer #%d', 'seo-city-pages' ), $i + 1 ); ?></label>
						<textarea name="content[faqs][<?php echo esc_attr( $i ); ?>][answer]" rows="2" class="large-text" required><?php echo esc_textarea( $faq['answer'] ?? '' ); ?></textarea>
					</div>
				<?php endfor; ?>
			</div>

			<!-- 8. Final CTA -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin-top: 0; color: #02426A;"><?php esc_html_e( '8. Final Call to Action', 'seo-city-pages' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Heading', 'seo-city-pages' ); ?></label></th>
						<td><input type="text" name="content[final_cta][heading]" value="<?php echo esc_attr( $content['final_cta']['heading'] ?? '' ); ?>" class="large-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Description', 'seo-city-pages' ); ?></label></th>
						<td><textarea name="content[final_cta][description]" rows="3" class="large-text" required><?php echo esc_textarea( $content['final_cta']['description'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'Button Text', 'seo-city-pages' ); ?></label></th>
						<td><input type="text" name="content[final_cta][button_text]" value="<?php echo esc_attr( $content['final_cta']['button_text'] ?? '' ); ?>" class="regular-text" required></td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Submit Actions -->
		<div style="margin-top: 20px; display: flex; gap: 12px; align-items: center;">
			<button type="button" id="scp-save-edits-btn" class="button button-primary button-large" style="background: #02426A; border-color: #02426A;">
				<?php esc_html_e( 'Save Manual Edits', 'seo-city-pages' ); ?>
			</button>
			<button type="button" id="scp-save-approve-btn" class="button button-large" style="background: #00a32a; border-color: #00a32a; color: #fff;">
				<?php esc_html_e( '✓ Save & Approve Immediately', 'seo-city-pages' ); ?>
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=view&page_id=' . $page->id ) ); ?>" class="button button-large">
				<?php esc_html_e( 'Cancel', 'seo-city-pages' ); ?>
			</a>
		</div>
	</form>
</div>
