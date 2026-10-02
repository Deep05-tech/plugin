<?php
/**
 * Content Review Detail View with Version History, Comparison & Publishing Controls.
 *
 * @package SeoCityPages\Admin
 */

use SeoCityPages\Database\City_Page_Repository;
use SeoCityPages\Database\Content_Version_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_version = $selected_version ?? ( ! empty( $versions ) ? $versions[0] : null );
$content = $current_version ? json_decode( (string) $current_version->content_json, true ) : [];
$is_published = ! empty( $page->is_published );
$published_vid = (int) ( $page->published_version_id ?? 0 );
$is_viewing_published = $current_version && (int) $current_version->id === $published_vid;
$previous_version = $current_version ? Content_Version_Repository::get_previous_version( (int) $current_version->id ) : null;
?>
<div class="wrap seo-city-pages-wrap">
	<h1 class="wp-heading-inline">
		<?php printf( esc_html__( 'Review Content: %s', 'seo-city-pages' ), esc_html( City_Page_Repository::get_derived_title( $page ) ) ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review' ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← Back to Review List', 'seo-city-pages' ); ?>
	</a>
	<hr class="wp-header-end">

	<!-- Relational & Taxonomy Health Notice -->
	<?php if ( ! empty( $integrity ) && ! $integrity['is_valid'] ) : ?>
		<div class="notice notice-error" style="margin: 15px 0; border-left-width: 4px;">
			<p><strong><?php esc_html_e( '⚠️ Source Taxonomy Integrity Warning:', 'seo-city-pages' ); ?></strong></p>
			<ul style="margin: 4px 0 8px 20px; list-style: disc;">
				<?php foreach ( $integrity['errors'] as $err ) : ?>
					<li><?php echo esc_html( $err ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p><?php esc_html_e( 'This page cannot be published until underlying taxonomy errors are resolved.', 'seo-city-pages' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( empty( $current_version ) || empty( $content ) ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'No generated content found for this page.', 'seo-city-pages' ); ?></p></div>
	<?php else : ?>
		<!-- Version Selector & Metadata Bar -->
		<div style="background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #02426A; padding: 16px 20px; border-radius: 4px; margin: 20px 0;">
			<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
				<div style="display: flex; align-items: center; gap: 15px;">
					<div>
						<label for="scp-version-switcher" style="font-weight: 600; font-size: 13px; margin-right: 6px;"><?php esc_html_e( 'Version:', 'seo-city-pages' ); ?></label>
						<select id="scp-version-switcher" onchange="window.location.href=this.value;">
							<?php foreach ( $versions as $v ) : ?>
								<option value="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=view&page_id=' . $page->id . '&version_id=' . $v->id ) ); ?>" <?php selected( $v->id, $current_version->id ); ?>>
									<?php printf( esc_html__( 'v%d (%s) — %s%s', 'seo-city-pages' ), (int) $v->version_num, esc_html( ucfirst( $v->source ?? 'ai' ) ), esc_html( $v->created_at ), ( (int) $v->id === $published_vid ? ' [LIVE]' : '' ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div style="font-size: 13px; color: #50575e;">
						<span><strong><?php esc_html_e( 'Source:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( ucfirst( $current_version->source ?? 'ai' ) ); ?></span> &bull;
						<span><strong><?php esc_html_e( 'Model:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $current_version->model ); ?></span> &bull;
						<span><strong><?php esc_html_e( 'Tokens:', 'seo-city-pages' ); ?></strong> <?php echo (int) $current_version->total_tokens; ?></span> &bull;
						<span>
							<strong><?php esc_html_e( 'Status:', 'seo-city-pages' ); ?></strong>
							<span style="font-weight: 700; text-transform: uppercase; color: <?php echo 'approved' === $current_version->status ? '#00a32a' : ( 'rejected' === $current_version->status ? '#d63638' : '#dba617' ); ?>;">
								<?php echo esc_html( str_replace( '_', ' ', $current_version->status ) ); ?>
							</span>
						</span>
					</div>
				</div>

				<!-- Live Public Badge -->
				<div>
					<?php if ( $is_published && $is_viewing_published ) : ?>
						<span style="background: #edfaef; color: #007017; border: 1px solid #00a32a; padding: 4px 10px; border-radius: 4px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
							✓ <?php esc_html_e( 'ACTIVE PUBLIC VERSION', 'seo-city-pages' ); ?>
						</span>
					<?php elseif ( $is_published ) : ?>
						<span style="background: #fff8e5; color: #b26b00; border: 1px solid #dba617; padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 12px;">
							<?php printf( esc_html__( 'Live site is serving v%d', 'seo-city-pages' ), (int) ( $published_version->version_num ?? 0 ) ); ?>
						</span>
					<?php else : ?>
						<span style="background: #f0f0f1; color: #50575e; border: 1px solid #c3c4c7; padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 12px;">
							<?php esc_html_e( 'DRAFT (Unpublished)', 'seo-city-pages' ); ?>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<!-- Action Toolbar -->
			<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f0f0f1; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
				<div style="display: flex; gap: 8px; flex-wrap: wrap;">
					<?php if ( 'approved' !== $current_version->status ) : ?>
						<button type="button" class="button button-primary scp-approve-btn" data-version-id="<?php echo esc_attr( $current_version->id ); ?>" style="background: #00a32a; border-color: #00a32a;">
							<?php esc_html_e( '✓ Approve Content', 'seo-city-pages' ); ?>
						</button>
					<?php endif; ?>

					<?php if ( 'rejected' !== $current_version->status ) : ?>
						<button type="button" class="button scp-reject-btn" data-version-id="<?php echo esc_attr( $current_version->id ); ?>" style="color: #d63638;">
							<?php esc_html_e( '✕ Reject Content', 'seo-city-pages' ); ?>
						</button>
					<?php endif; ?>

					<!-- Publishing Controls -->
					<?php if ( 'approved' === $current_version->status && ! $is_viewing_published ) : ?>
						<button type="button" class="button scp-publish-btn" data-page-id="<?php echo esc_attr( $page->id ); ?>" data-version-id="<?php echo esc_attr( $current_version->id ); ?>" style="background: #02426A; border-color: #02426A; color: #fff; font-weight: 700;">
							<?php esc_html_e( '🚀 Publish Live to Public Site', 'seo-city-pages' ); ?>
						</button>
					<?php elseif ( $is_published && $is_viewing_published ) : ?>
						<button type="button" class="button scp-unpublish-btn" data-page-id="<?php echo esc_attr( $page->id ); ?>" style="color: #d63638;">
							<?php esc_html_e( '↩ Unpublish (Revert Live Page to Draft)', 'seo-city-pages' ); ?>
						</button>
					<?php endif; ?>

					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=edit&page_id=' . $page->id ) ); ?>" class="button">
						<?php esc_html_e( '✏️ Edit Fields', 'seo-city-pages' ); ?>
					</a>

					<?php if ( $previous_version ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=compare&page_id=' . $page->id . '&v1=' . $current_version->id . '&v2=' . $previous_version->id ) ); ?>" class="button">
							<?php printf( esc_html__( '⚖️ Compare with v%d', 'seo-city-pages' ), (int) $previous_version->version_num ); ?>
						</a>
					<?php endif; ?>

					<a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-city-pages-ai-review&review_action=history&page_id=' . $page->id ) ); ?>" class="button">
						<?php esc_html_e( '📜 Audit History', 'seo-city-pages' ); ?>
					</a>

					<?php
					$public_url = \SeoCityPages\URL\URL_Engine::build_full_url( $page->url_path );
					$preview_url = \SeoCityPages\URL\URL_Engine::build_preview_url( (int) $page->id );
					?>
					<a href="<?php echo esc_url( $is_published ? $public_url : $preview_url ); ?>" target="_blank" class="button" style="font-weight: 600;">
						<?php echo $is_published ? esc_html__( '👁️ View Live URL ↗', 'seo-city-pages' ) : esc_html__( '👁️ Preview Page ↗', 'seo-city-pages' ); ?>
					</a>
				</div>

				<div>
					<?php if ( $current_version && $current_version->id !== ( $versions[0]->id ?? 0 ) ) : ?>
						<button type="button" class="button scp-restore-btn" data-version-id="<?php echo esc_attr( $current_version->id ); ?>">
							<?php printf( esc_html__( '↩ Restore v%d as New Version', 'seo-city-pages' ), (int) $current_version->version_num ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Review Notes Section -->
		<div class="postbox" style="padding: 16px 20px; margin-bottom: 20px;">
			<h3 style="margin: 0 0 10px; font-size: 15px; color: #02426A;"><?php esc_html_e( '📝 Reviewer Notes & Editorial Instructions', 'seo-city-pages' ); ?></h3>
			<textarea id="scp-review-notes-input" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Add internal notes or quality remarks about this version...', 'seo-city-pages' ); ?>"><?php echo esc_textarea( $current_version->review_notes ?? '' ); ?></textarea>
			<div style="margin-top: 8px;">
				<button type="button" id="scp-save-notes-btn" class="button button-small" data-version-id="<?php echo esc_attr( $current_version->id ); ?>">
					<?php esc_html_e( 'Save Notes', 'seo-city-pages' ); ?>
				</button>
				<span id="scp-save-notes-msg" style="margin-left: 10px; font-size: 12px; color: #00a32a; display: none;">✓ Notes saved</span>
			</div>
		</div>

		<!-- Rejection Reason Notice if rejected -->
		<?php if ( 'rejected' === $current_version->status && ! empty( $current_version->rejection_reason ) ) : ?>
			<div class="notice notice-error" style="margin: 15px 0; border-left-width: 4px;">
				<p><strong><?php esc_html_e( 'Rejection Feedback:', 'seo-city-pages' ); ?></strong> <?php echo esc_html( $current_version->rejection_reason ); ?></p>
			</div>
		<?php endif; ?>

		<!-- Content Blocks Preview -->
		<div style="display: flex; flex-direction: column; gap: 20px;">
			<!-- 1. Hero -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin: 0 0 10px; color: #02426A; font-size: 16px;"><?php esc_html_e( '1. Hero Section', 'seo-city-pages' ); ?></h2>
				<h3 style="margin: 0 0 8px; font-size: 22px;"><?php echo esc_html( $content['hero']['title'] ?? '' ); ?></h3>
				<p style="font-size: 15px; color: #5D5756;"><?php echo esc_html( $content['hero']['description'] ?? '' ); ?></p>
				<span class="button button-primary" style="background: #02426A; pointer-events: none;"><?php echo esc_html( $content['hero']['cta_text'] ?? '' ); ?></span>
			</div>

			<!-- 2. Introduction -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin: 0 0 10px; color: #02426A; font-size: 16px;"><?php esc_html_e( '2. Introduction', 'seo-city-pages' ); ?></h2>
				<h3 style="margin: 0 0 8px; font-size: 18px;"><?php echo esc_html( $content['introduction']['heading'] ?? '' ); ?></h3>
				<p style="font-size: 15px;"><?php echo wp_kses_post( $content['introduction']['body'] ?? '' ); ?></p>
			</div>

			<!-- 3. Service Overview -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin: 0 0 10px; color: #02426A; font-size: 16px;"><?php esc_html_e( '3. Service Overview', 'seo-city-pages' ); ?></h2>
				<h3 style="margin: 0 0 8px; font-size: 18px;"><?php echo esc_html( $content['service_overview']['heading'] ?? '' ); ?></h3>
				<p style="font-size: 15px;"><?php echo wp_kses_post( $content['service_overview']['body'] ?? '' ); ?></p>
			</div>

			<!-- 4. Benefits -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin: 0 0 14px; color: #02426A; font-size: 16px;"><?php esc_html_e( '4. Benefits & Differentiators', 'seo-city-pages' ); ?></h2>
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
					<?php foreach ( ( $content['benefits'] ?? [] ) as $b ) : ?>
						<div style="background: #f0f6fa; border: 1px solid #cce0ed; border-radius: 6px; padding: 16px;">
							<h4 style="margin: 0 0 6px; font-size: 15px; color: #02426A;">✓ <?php echo esc_html( $b['title'] ?? '' ); ?></h4>
							<p style="margin: 0; font-size: 13.5px; color: #5D5756;"><?php echo esc_html( $b['description'] ?? '' ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- 5. Metro Stats -->
			<?php if ( ! empty( $content['metro_stats'] ) ) : ?>
				<div class="postbox" style="padding: 20px;">
					<h2 style="margin: 0 0 14px; color: #02426A; font-size: 16px;"><?php esc_html_e( '5. Metro In-Numbers Benchmark Stats', 'seo-city-pages' ); ?></h2>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px;">
						<?php foreach ( $content['metro_stats'] as $ms ) : ?>
							<div style="background: #f0f6fa; border: 1px solid #cce0ed; border-radius: 8px; padding: 14px; text-align: center;">
								<div style="font-size: 22px; font-weight: 800; color: #02426A;"><?php echo esc_html( $ms['value'] ?? '' ); ?></div>
								<div style="font-size: 13px; font-weight: 700; color: #121212;"><?php echo esc_html( $ms['label'] ?? '' ); ?></div>
								<div style="font-size: 12px; color: #5D5756;"><?php echo esc_html( $ms['description'] ?? '' ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- 6. Market Anatomy -->
			<?php if ( ! empty( $content['market_anatomy'] ) ) : ?>
				<div class="postbox" style="padding: 20px;">
					<h2 style="margin: 0 0 10px; color: #02426A; font-size: 16px;"><?php esc_html_e( '6. Market Anatomy & Search Dynamics', 'seo-city-pages' ); ?></h2>
					<div style="background: #f8fafc; border-left: 3px solid #02426A; padding: 12px 16px; margin-bottom: 12px; font-style: italic;">
						<?php echo esc_html( $content['market_anatomy']['kicker'] ?? '' ); ?>
					</div>
					<h3 style="margin: 0 0 8px; font-size: 18px;"><?php echo esc_html( $content['market_anatomy']['heading'] ?? '' ); ?></h3>
					<p style="font-size: 15px; font-weight: 600;"><?php echo wp_kses_post( $content['market_anatomy']['lead_paragraph'] ?? '' ); ?></p>
					<p style="font-size: 14.5px; color: #5D5756;"><?php echo wp_kses_post( $content['market_anatomy']['search_dynamics'] ?? '' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- 7. Submarkets -->
			<?php if ( ! empty( $content['submarkets'] ) ) : ?>
				<div class="postbox" style="padding: 20px;">
					<h2 style="margin: 0 0 14px; color: #02426A; font-size: 16px;"><?php esc_html_e( '7. Target Submarkets & Districts', 'seo-city-pages' ); ?></h2>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
						<?php foreach ( $content['submarkets'] as $sm ) : ?>
							<div style="background: #fff; border: 1px solid #cce0ed; border-radius: 8px; padding: 16px;">
								<span style="font-size: 11px; font-weight: 700; color: #02426A; background: #f0f6fa; padding: 3px 8px; border-radius: 12px; display: inline-block; margin-bottom: 6px;"><?php echo esc_html( $sm['type_tag'] ?? '' ); ?></span>
								<h4 style="margin: 4px 0 8px; font-size: 16px; color: #121212;"><?php echo esc_html( $sm['name'] ?? '' ); ?></h4>
								<p style="margin: 0 0 8px; font-size: 13.5px; color: #5D5756;"><?php echo esc_html( $sm['description'] ?? '' ); ?></p>
								<div style="font-size: 12px; color: #02426A; font-weight: 600;">💡 <?php echo esc_html( $sm['fact'] ?? '' ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- 8. Case Proof -->
			<?php if ( ! empty( $content['case_proof'] ) ) : ?>
				<div class="postbox" style="padding: 20px; background: #02426A; color: #fff;">
					<h2 style="margin: 0 0 8px; color: #BEE3F8; font-size: 16px;"><?php echo esc_html( $content['case_proof']['title'] ?? 'Client Performance Benchmark' ); ?></h2>
					<h3 style="margin: 0 0 12px; font-size: 20px; color: #fff;"><?php echo esc_html( $content['case_proof']['tagline'] ?? '' ); ?></h3>
					<blockquote style="margin: 0 0 16px; font-style: italic; color: #e2e8f0; font-size: 15px; border-left: 3px solid #BEE3F8; padding-left: 14px;">
						<?php echo esc_html( $content['case_proof']['client_quote'] ?? '' ); ?>
					</blockquote>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
						<?php foreach ( ( $content['case_proof']['highlights'] ?? [] ) as $hl ) : ?>
							<div style="background: rgba(255,255,255,0.1); border-radius: 6px; padding: 10px; text-align: center;">
								<div style="font-size: 20px; font-weight: 800; color: #fff;"><?php echo esc_html( $hl['metric'] ?? '' ); ?></div>
								<div style="font-size: 12px; color: #BEE3F8;"><?php echo esc_html( $hl['label'] ?? '' ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- 9. Process -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin: 0 0 14px; color: #02426A; font-size: 16px;"><?php esc_html_e( '9. Execution Methodology', 'seo-city-pages' ); ?></h2>
				<div style="display: flex; flex-direction: column; gap: 10px;">
					<?php foreach ( ( $content['process'] ?? [] ) as $step ) : ?>
						<div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 18px; display: flex; gap: 14px; align-items: flex-start;">
							<span style="background: #02426A; color: #fff; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;"><?php echo esc_html( $step['step_number'] ?? '1' ); ?></span>
							<div>
								<strong style="font-size: 15px;"><?php echo esc_html( $step['title'] ?? '' ); ?></strong>
								<p style="margin: 4px 0 0; font-size: 13.5px; color: #5D5756;"><?php echo esc_html( $step['description'] ?? '' ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- 10. Local Relevance -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin: 0 0 10px; color: #02426A; font-size: 16px;"><?php esc_html_e( '10. Local Relevance', 'seo-city-pages' ); ?></h2>
				<h3 style="margin: 0 0 8px; font-size: 18px;"><?php echo esc_html( $content['local_relevance']['heading'] ?? '' ); ?></h3>
				<p style="font-size: 15px;"><?php echo wp_kses_post( $content['local_relevance']['body'] ?? '' ); ?></p>
			</div>

			<!-- 11. FAQs -->
			<div class="postbox" style="padding: 20px;">
				<h2 style="margin: 0 0 14px; color: #02426A; font-size: 16px;"><?php esc_html_e( '11. Frequently Asked Questions', 'seo-city-pages' ); ?></h2>
				<div style="display: flex; flex-direction: column; gap: 12px;">
					<?php foreach ( ( $content['faqs'] ?? [] ) as $faq ) : ?>
						<div style="background: #f8fafc; border: 1px solid #cce0ed; border-radius: 6px; padding: 16px;">
							<h4 style="margin: 0 0 6px; font-size: 15px; color: #02426A;">❓ <?php echo esc_html( $faq['question'] ?? '' ); ?></h4>
							<p style="margin: 0; font-size: 14px; color: #5D5756;"><?php echo esc_html( $faq['answer'] ?? '' ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- 12. Final CTA -->
			<div class="postbox" style="padding: 20px; background: #02426A; color: #fff;">
				<h2 style="margin: 0 0 10px; color: #fff; font-size: 20px;"><?php echo esc_html( $content['final_cta']['heading'] ?? '' ); ?></h2>
				<p style="color: #e2e8f0; font-size: 15px;"><?php echo esc_html( $content['final_cta']['description'] ?? '' ); ?></p>
				<span class="button button-primary" style="background: #fff; color: #02426A; font-weight: 700; border: none; pointer-events: none; margin-top: 10px;"><?php echo esc_html( $content['final_cta']['button_text'] ?? '' ); ?></span>
			</div>

			<!-- 9. SEO Meta Fields -->
			<?php if ( ! empty( $content['seo'] ) ) : ?>
				<div class="postbox" style="padding: 20px;">
					<h2 style="margin: 0 0 10px; color: #02426A; font-size: 16px;"><?php esc_html_e( '9. SEO Metadata', 'seo-city-pages' ); ?></h2>
					<p><strong><?php esc_html_e( 'SEO Title:', 'seo-city-pages' ); ?></strong> <code><?php echo esc_html( $content['seo']['title'] ?? '' ); ?></code></p>
					<p><strong><?php esc_html_e( 'Meta Description:', 'seo-city-pages' ); ?></strong> <code><?php echo esc_html( $content['seo']['meta_description'] ?? '' ); ?></code></p>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
