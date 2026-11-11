<?php
/**
 * Official Vispan Solutions Master Template for General Agency & Professional Services SEO Landing Pages.
 *
 * Sourced directly from https://vispansolutions.com/best-digital-marketing-agency-for-dental-clinic-in-rajkot/
 * Designed for Dental Clinics, Law Firms, Healthcare, Accounting, Tech, and all professional B2B/B2C services.
 * Features 12 comprehensive Vispan architecture sections:
 *  1. Hero Split Grid (Google 5.0 Badge, Value Props, Call CTA, and Interactive Inquiry Proposal Card)
 *  2. Cityscape / Market Kicker Section (#02426A Navy Dynamics Banner)
 *  3. Market Anatomy & Metro Stats Strip (4 Pillars + Animated Stat Counters)
 *  4. Search Intent Architecture Accordion (High-Intent Traffic Capturing)
 *  5. Proof / Case Study Success Story (Verified Growth Metrics & Advantage Checklist)
 *  6. Editorial Long-Form Authority Essay (The Anatomy of Local Search Authority)
 *  7. Full Suite of Agency Services (10 Core Digital Marketing & Creative Capabilities)
 *  8. Strategic Differentiation Comparison Table (Standard National SEO vs. Vispan Architecture)
 *  9. 4-Stage Execution Roadmap (Audit -> Architecture -> Authority -> Scale)
 * 10. Complete Deliverables Package & Core Capabilities (12-Point Checklist + Benefits Grid)
 * 11. Local FAQs Accordion (Hyper-Local Questions & Practice Answers)
 * 12. Final Navy CTA Banner & Dual Bottom Intake Form (#02426A Conversion Engine)
 *
 * @package SeoCityPages\Elementor
 *
 * @var string $city
 * @var string $state
 * @var string $country
 * @var string $service
 * @var string $url
 * @var string $nonce
 * @var string $hero_title
 * @var string $hero_description
 * @var string $hero_cta
 * @var string $intro_heading
 * @var string $intro_body
 * @var string $service_overview_h
 * @var string $service_overview_b
 * @var string $final_cta_head
 * @var string $final_cta_desc
 * @var string $final_cta_btn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$display_city    = ! empty( $city ) ? $city : 'Your City';
$display_state   = ! empty( $state ) ? $state : '';
$display_service = ! empty( $service ) ? $service : 'Digital Marketing & Growth';
$display_url     = ! empty( $url ) ? $url : '#proposal-form';

$location_label = $display_city;
if ( ! empty( $display_state ) ) {
	$location_label .= ', ' . $display_state;
}

$nonce_val = ! empty( $nonce ) ? $nonce : ( function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'vispan_marketing_inquiry' ) : 'vispan_nonce' );
?>
<div class="vispan-general-agency-page seo-city-page-container elementor elementor-67818" style="font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #121212; background: #FFFFFF; line-height: 1.6; letter-spacing: normal; word-spacing: normal;">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Google Fonts: Plus Jakarta Sans & DM Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <!-- Lenis Modern Smooth Scroll Engine -->
  <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.18/dist/lenis.min.js"></script>

  <style>
    /* =========================================================================
       1. VISPAN SOLUTIONS OFFICIAL BRAND TYPOGRAPHY & RESETS
       ========================================================================= */
    .vispan-general-agency-page {
      font-family: 'DM Sans', sans-serif;
      color: #121212;
      background-color: #FFFFFF;
      overflow-x: clip !important;
      overflow-y: visible !important;
      box-sizing: border-box;
    }
    .vispan-general-agency-page *,
    .vispan-general-agency-page *::before,
    .vispan-general-agency-page *::after {
      box-sizing: border-box;
    }
    .vispan-general-agency-page h1,
    .vispan-general-agency-page h2,
    .vispan-general-agency-page h3,
    .vispan-general-agency-page h4,
    .vispan-general-agency-page h5,
    .vispan-general-agency-page h6 {
      font-family: 'Plus Jakarta Sans', sans-serif;
      letter-spacing: 0px !important;
      word-spacing: normal !important;
      color: #121212;
    }
    .vispan-general-agency-page p {
      letter-spacing: normal !important;
      word-spacing: normal !important;
      color: #5D5756;
      font-size: 16px;
      line-height: 1.65;
    }

    /* Base Lenis Smooth Scroll Configuration */
    html.lenis, html.lenis body {
      height: auto;
    }
    .lenis.lenis-smooth {
      scroll-behavior: auto !important;
    }
    .lenis.lenis-smooth [data-lenis-prevent] {
      overscroll-behavior: contain;
    }
    .lenis.lenis-stopped {
      overflow: hidden;
    }
    .lenis.lenis-smooth iframe {
      pointer-events: none;
    }

    /* Prevent sticky / scroll overflow locks */
    html, body, #page, .wp-site-blocks, .vispan-general-agency-page {
      overflow-x: clip !important;
      overflow-y: visible !important;
    }

    /* =========================================================================
       2. HARDWARE-ACCELERATED ENTRANCE ANIMATIONS (MATCHING HOME SERVICES)
       ========================================================================= */
    @media (prefers-reduced-motion: no-preference) {
      .vispan-general-agency-page .vispan-reveal-left,
      html.js-animations .vispan-general-agency-page [data-animate="fade-left"] {
        opacity: 0;
        transform: translate3d(-50px, 0, 0);
        transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform;
      }
      .vispan-general-agency-page .vispan-reveal-right,
      html.js-animations .vispan-general-agency-page [data-animate="fade-right"] {
        opacity: 0;
        transform: translate3d(50px, 0, 0);
        transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform;
      }
      .vispan-general-agency-page .vispan-reveal-up,
      html.js-animations .vispan-general-agency-page [data-animate="fade-up"] {
        opacity: 0;
        transform: translate3d(0, 40px, 0);
        transition: opacity 0.85s cubic-bezier(0.16, 1, 0.3, 1), transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform;
      }
      .vispan-general-agency-page .vispan-reveal-fade,
      html.js-animations .vispan-general-agency-page [data-animate="zoom-in"] {
        opacity: 0;
        transform: scale3d(0.95, 0.95, 1);
        transition: opacity 0.85s ease, transform 0.85s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform;
      }

      /* In-View Active State - Release transforms & will-change to eliminate compositor GPU layer thrashing */
      .vispan-general-agency-page .vispan-reveal-left.vispan-in-view,
      .vispan-general-agency-page .vispan-reveal-right.vispan-in-view,
      .vispan-general-agency-page .vispan-reveal-up.vispan-in-view,
      .vispan-general-agency-page .vispan-reveal-fade.vispan-in-view,
      html.js-animations .vispan-general-agency-page [data-animate].is-animated {
        opacity: 1 !important;
        transform: none !important;
        will-change: auto !important;
      }

      .vispan-general-agency-page .vispan-delay-1 { transition-delay: 150ms; }
      .vispan-general-agency-page .vispan-delay-2 { transition-delay: 300ms; }
      .vispan-general-agency-page .vispan-delay-3 { transition-delay: 450ms; }
      .vispan-general-agency-page .vispan-delay-4 { transition-delay: 600ms; }
    }

    /* =========================================================================
       3. INTERACTIVE CARDS & BUTTON GLOW STYLES
       ========================================================================= */
    .vispan-hover-card {
      transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s ease, border-color 0.3s ease !important;
      will-change: transform, box-shadow;
    }
    .vispan-hover-card:hover {
      transform: translateY(-5px) !important;
      box-shadow: 0 18px 36px -12px rgba(2, 66, 106, 0.16) !important;
      border-color: #02426A !important;
    }
    .vispan-btn-glow {
      transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.28s ease, background 0.25s ease !important;
      will-change: transform, box-shadow;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .vispan-btn-glow:hover {
      transform: translateY(-2px) scale(1.02) !important;
      box-shadow: 0 16px 32px -8px rgba(2, 66, 106, 0.45) !important;
    }

    /* Floating Back to Top Button */
    #back-to-top-btn {
      position: fixed;
      bottom: 24px;
      right: 24px;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: #02426A;
      color: #FFFFFF;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease, transform 0.3s ease;
      z-index: 9999;
      box-shadow: 0 4px 14px rgba(2, 66, 106, 0.35);
    }
    #back-to-top-btn.visible {
      opacity: 1;
      pointer-events: auto;
      transform: translateY(0);
    }
    #back-to-top-btn:hover {
      transform: translateY(-3px);
      background: #0B6599;
    }

    /* Section Container Helper */
    .vispan-section-wrap {
      max-width: 1140px;
      margin-left: auto;
      margin-right: auto;
      padding-left: 20px;
      padding-right: 20px;
    }

    /* Responsive Grid Auto-Stacking */
    @media (max-width: 900px) {
      .vispan-hero-grid {
        grid-template-columns: 1fr !important;
        gap: 36px !important;
      }
    }
    @media (max-width: 767px) {
      .vispan-section-wrap {
        padding-left: 16px;
        padding-right: 16px;
      }
      .vispan-hero-grid {
        grid-template-columns: 1fr !important;
        gap: 28px !important;
      }
    }
  </style>

  <!-- =======================================================================
       1. HERO SECTION (Modern Agency Split Grid)
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_hero_section elementor-section-boxed" data-id="vispan_hero_section" style="padding: 70px 20px 60px; background: #FFFFFF;">
    <div class="vispan-section-wrap">
      <div class="vispan-hero-grid" style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 48px; align-items: center;">

        <!-- Hero Left Column -->
        <div class="vispan-reveal-left" data-title="<?php echo esc_attr( $hero_title ); ?>">
          <div style="display: inline-flex; align-items: center; gap: 8px; background: #F0F6FA; border: 1px solid #CCE0ED; border-radius: 30px; padding: 5px 14px; margin-bottom: 14px;">
            <span style="font-size: 11.5px; font-weight: 800; color: #02426A; letter-spacing: 0.12em; text-transform: uppercase;">⭐ 5.0 ON GOOGLE • <?php echo esc_html( $display_city ); ?> EXPERTISE</span>
          </div>

          <h1 style="font-size: clamp(28px, 3.8vw, 46px); font-weight: 800; line-height: 1.2; color: #121212; margin: 0 0 16px; letter-spacing: 0px !important;">
            <?php echo esc_html( $hero_title ); ?>
          </h1>

          <p style="font-size: 17px; line-height: 1.6; color: #5D5756; margin: 0 0 20px; max-width: 580px;">
            <?php echo esc_html( $hero_description ); ?>
          </p>

          <div style="display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 24px; font-size: 14px; font-weight: 600; color: #121212;">
            <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="color: #00a32a; font-weight: 900;">✓</span> 100% ROI-Focused</span>
            <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="color: #00a32a; font-weight: 900;">✓</span> Dedicated Senior Strategist</span>
            <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="color: #00a32a; font-weight: 900;">✓</span> Granular Submarket Targeting</span>
          </div>

          <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px;">
            <a href="#proposal-form" class="vispan-btn-glow" style="background: #02426A; color: #FFFFFF; font-weight: 700; font-size: 13.5px; letter-spacing: 0.04em; text-transform: uppercase; padding: 14px 32px; border-radius: 100px; text-decoration: none; box-shadow: 0 12px 28px -10px rgba(2,66,106,0.5);">
              <?php echo esc_html( $hero_cta ); ?>
            </a>
            <a href="tel:+918485986860" style="font-size: 14px; font-weight: 700; color: #02426A; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
              📞 +91 84859 86860
            </a>
          </div>

          <div style="margin-top: 22px; padding-top: 16px; border-top: 1px solid #CCE0ED; display: flex; flex-wrap: wrap; gap: 16px; font-size: 13px; color: #5D5756;">
            <span style="display: inline-flex; align-items: center; gap: 6px;">⚡ <strong style="color: #121212;">Response:</strong> Within 15 Minutes</span>
            <span style="display: inline-flex; align-items: center; gap: 6px;">🔒 <strong style="color: #121212;">Privacy:</strong> 100% Confidential</span>
            <span style="display: inline-flex; align-items: center; gap: 6px;">⭐ <strong style="color: #121212;">Rating:</strong> 5.0 Star Agency</span>
          </div>

          <div style="margin-top: 18px; display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px;">
            <div class="vispan-hover-card" style="background: #F0F6FA; border: 1.5px solid #CCE0ED; border-radius: 12px; padding: 12px 16px;">
              <div style="font-size: 10.5px; font-weight: 800; color: #02426A; letter-spacing: 0.1em; text-transform: uppercase;">LOCAL GEO-AUTHORITY</div>
              <div style="font-size: 13px; font-weight: 800; color: #121212; margin-top: 2px;">Targeted <?php echo esc_html( $display_city ); ?> Search Radius</div>
            </div>
            <div class="vispan-hover-card" style="background: #F0F6FA; border: 1.5px solid #CCE0ED; border-radius: 12px; padding: 12px 16px;">
              <div style="font-size: 10.5px; font-weight: 800; color: #02426A; letter-spacing: 0.1em; text-transform: uppercase;">PROVEN ROI HORIZON</div>
              <div style="font-size: 13px; font-weight: 800; color: #121212; margin-top: 2px;">Rankings That Drive Inbound Revenue</div>
            </div>
          </div>
        </div>

        <!-- Hero Right Column (Interactive Proposal Card) -->
        <div id="proposal-form" class="vispan-reveal-right vispan-delay-1">
          <?php echo \SeoCityPages\Elementor\Content_Context::render_inquiry_form_hero(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>

      </div>
    </div>
  </section>

  <!-- =======================================================================
       2. CITYSCAPE / MARKET KICKER SECTION
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_kicker_section elementor-section-boxed" data-id="vispan_kicker_section" style="padding: 70px 20px; background: #02426A; color: #FFFFFF;">
    <div class="vispan-section-wrap">
      <div class="vispan-reveal-up" style="position: relative; text-align: center; max-width: 900px; margin: 0 auto; padding: 42px 32px; background: linear-gradient(rgba(2,66,106,0.92), rgba(2,66,106,0.92)), url('<?php echo esc_url( \SeoCityPages\Elementor\Content_Context::get_image_url( 'city-market-data.jpg' ) ); ?>') center/cover no-repeat; border-radius: 20px; border: 1.5px solid rgba(255,255,255,0.18); box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5);">
        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #BEE3F8; margin-bottom: 12px;">LOCAL SEARCH MARKET DYNAMICS</div>
        <h2 style="font-size: clamp(24px, 3.2vw, 36px); font-weight: 800; color: #FFFFFF; line-height: 1.25; margin: 0 0 14px;">
          <?php echo esc_html( $intro_heading ); ?>
        </h2>
        <div style="font-size: 16.5px; line-height: 1.65; color: #E2E8F0; max-width: 740px; margin: 0 auto;">
          <?php echo function_exists( 'wp_kses_post' ) ? \wp_kses_post( $intro_body ) : esc_html( $intro_body ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
      </div>
    </div>
  </section>

  <!-- =======================================================================
       3. MARKET ANATOMY & METRO STATS SECTION
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_anatomy_section elementor-section-boxed" data-id="vispan_anatomy_section" style="padding: 85px 20px; background: #FFFFFF;">
    <div class="vispan-section-wrap">
      <div class="vispan-reveal-up" style="margin-bottom: 40px;">
        <?php echo \SeoCityPages\Elementor\Content_Context::render_market_anatomy_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
      <div class="vispan-reveal-up vispan-delay-1" style="margin-top: 30px;">
        <?php echo \SeoCityPages\Elementor\Content_Context::render_metro_stats_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
    </div>
  </section>

  <!-- =======================================================================
       4. SEARCH INTENT ARCHITECTURE ACCORDION SECTION
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_accordion_section elementor-section-boxed" data-id="vispan_accordion_section" style="padding: 85px 20px; background: #F8FAFC;">
    <div class="vispan-section-wrap" style="max-width: 880px;">
      <div class="vispan-reveal-up" style="text-align: center; margin-bottom: 40px;">
        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">SEARCH INTENT ARCHITECTURE</div>
        <h2 style="font-size: clamp(26px, 3.4vw, 40px); font-weight: 800; color: #121212; margin: 0;">How We Capture High-Intent Traffic in <?php echo esc_html( $display_city ); ?></h2>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_market_accordion_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  </section>

  <!-- =======================================================================
       5. PROOF / CASE STUDY HIGHLIGHT SECTION
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_proof_section elementor-section-boxed" data-id="vispan_proof_section" style="padding: 85px 20px; background: #FFFFFF;">
    <div class="vispan-section-wrap" style="max-width: 920px;">
      <?php echo \SeoCityPages\Elementor\Content_Context::render_case_proof_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  </section>

  <!-- =======================================================================
       6. LONG-FORM AUTHORITY EDITORIAL ESSAY SECTION
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_essay_section elementor-section-boxed" data-id="vispan_essay_section" style="padding: 85px 20px; background: #F8FAFC;">
    <div class="vispan-section-wrap">
      <?php echo \SeoCityPages\Elementor\Content_Context::render_essay_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  </section>

  <!-- =======================================================================
       7. ALL AGENCY SERVICES (10 Core Capabilities Cards)
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_industry_section elementor-section-boxed" data-id="vispan_industry_section" style="padding: 85px 20px; background: #FFFFFF;">
    <div class="vispan-section-wrap">
      <div class="vispan-reveal-up" style="text-align: center; max-width: 820px; margin: 0 auto 48px;">
        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">OUR COMPREHENSIVE SUITE</div>
        <h2 style="font-size: clamp(26px, 3.4vw, 40px); font-weight: 800; color: #121212; margin: 0 0 12px;">
          Our Full Suite of Digital Marketing & Creative Services for <?php echo esc_html( ! empty( $service ) ? $service : 'Your Practice' ); ?>
        </h2>
        <p style="font-size: 16.5px; color: #5D5756; margin: 0 auto;">
          From high-intent search campaigns to world-class 3D visuals, explore all 10 specialized services engineered to scale your <?php echo esc_html( ! empty( $service ) ? $service : 'brand' ); ?> practice in <?php echo esc_html( $display_city ); ?>.
        </p>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_industry_verticals_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  </section>

  <!-- =======================================================================
       8. NATIONAL DIRECTORY VS VISPAN COMPARISON TABLE
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_comparison_section elementor-section-boxed" data-id="vispan_comparison_section" style="padding: 85px 20px; background: #F8FAFC;">
    <div class="vispan-section-wrap" style="max-width: 1080px;">
      <div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 44px;">
        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">STRATEGIC DIFFERENTIATION</div>
        <h2 style="font-size: clamp(26px, 3.4vw, 40px); font-weight: 800; color: #121212; margin: 0 0 12px;">
          Standard National SEO vs. Vispan Architecture
        </h2>
        <p style="font-size: 16.5px; color: #5D5756; margin: 0 auto;">
          Why thin doorway pages fail in competitive metropolitan markets and how our localized entity graph wins.
        </p>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_comparison_table_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  </section>

  <!-- =======================================================================
       9. 4-STAGE EXECUTION METHODOLOGY ROADMAP
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_process_section elementor-section-boxed" data-id="vispan_process_section" style="padding: 85px 20px; background: #FFFFFF;">
    <div class="vispan-section-wrap">
      <div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 44px;">
        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">EXECUTION ROADMAP</div>
        <h2 style="font-size: clamp(26px, 3.4vw, 40px); font-weight: 800; color: #121212; margin: 0 0 12px;">
          Our 4-Stage Search Growth Engine
        </h2>
        <p style="font-size: 16.5px; color: #5D5756; margin: 0 auto;">
          A systematic, milestone-driven framework designed to establish compound local authority across <?php echo esc_html( $display_city ); ?>.
        </p>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_process_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  </section>

  <!-- =======================================================================
       10. COMPLETE DELIVERABLES PACKAGE & CORE CAPABILITIES
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_capabilities_section elementor-section-boxed" data-id="vispan_capabilities_section" style="padding: 85px 20px; background: #F8FAFC;">
    <div class="vispan-section-wrap">
      <div class="vispan-reveal-up" style="text-align: center; max-width: 760px; margin: 0 auto 44px;">
        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">TRANSPARENT DELIVERABLES</div>
        <h2 style="font-size: clamp(26px, 3.4vw, 40px); font-weight: 800; color: #121212; margin: 0 0 12px;">
          Complete Local SEO Deliverables Package
        </h2>
        <p style="font-size: 16.5px; color: #5D5756; margin: 0 auto;">
          Everything included in your dedicated <?php echo esc_html( $display_city ); ?> search growth campaign.
        </p>
      </div>
      <div style="margin-bottom: 40px;">
        <?php echo \SeoCityPages\Elementor\Content_Context::render_deliverables_checklist_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
      <div style="margin-top: 30px;">
        <?php echo \SeoCityPages\Elementor\Content_Context::render_benefits_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
    </div>
  </section>

  <!-- =======================================================================
       11. LOCAL FAQS ACCORDION SECTION
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_faq_section elementor-section-boxed" data-id="vispan_faq_section" style="padding: 85px 20px; background: #FFFFFF;">
    <div class="vispan-section-wrap" style="max-width: 880px;">
      <div class="vispan-reveal-up" style="text-align: center; margin-bottom: 36px;">
        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #02426A; margin-bottom: 10px;">COMMON QUESTIONS</div>
        <h2 style="font-size: clamp(26px, 3.4vw, 40px); font-weight: 800; color: #121212; margin: 0;">
          Frequently Asked Questions in <?php echo esc_html( $display_city ); ?>
        </h2>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_faqs_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  </section>

  <!-- =======================================================================
       12. FINAL CLOSING CALL-TO-ACTION BANNER & BOTTOM FORM
       ======================================================================= -->
  <section class="elementor-section elementor-top-section elementor-element elementor-element-vispan_cta_section elementor-section-boxed" data-id="vispan_cta_section" style="padding: 85px 20px; background: #02426A; color: #FFFFFF;">
    <div class="vispan-section-wrap" style="text-align: center;">
      <div class="vispan-reveal-up" style="max-width: 760px; margin: 0 auto;">
        <h2 style="font-size: clamp(28px, 4vw, 46px); font-weight: 800; color: #FFFFFF; line-height: 1.2; margin: 0 0 16px;">
          <?php echo esc_html( $final_cta_head ); ?>
        </h2>
        <p style="font-size: 17px; line-height: 1.65; color: #E2E8F0; max-width: 640px; margin: 0 auto 32px;">
          <?php echo esc_html( $final_cta_desc ); ?>
        </p>
        <div>
          <a href="#proposal-form" class="vispan-btn-glow" style="background: #FFFFFF; color: #02426A; font-weight: 800; font-size: 14.5px; letter-spacing: 0.06em; text-transform: uppercase; padding: 18px 42px; border-radius: 100px; text-decoration: none; box-shadow: 0 12px 30px rgba(0,0,0,0.25);">
            <?php echo esc_html( $final_cta_btn ); ?>
          </a>
        </div>
      </div>

      <div style="margin-top: 50px; max-width: 720px; margin-left: auto; margin-right: auto;">
        <?php echo \SeoCityPages\Elementor\Content_Context::render_inquiry_form_bottom(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </div>
    </div>
  </section>

  <!-- Floating Back to Top Button -->
  <button id="back-to-top-btn" aria-label="Scroll back to top">
    <i class="fa-solid fa-arrow-up text-sm"></i>
  </button>

  <!-- =======================================================================
       UNIFIED LENIS SMOOTH SCROLLING & INTERSECTION ANIMATIONS SCRIPT
       ======================================================================= -->
  <script>
    (function() {
      // 0. Neutralize conflicting external GSAP ScrollSmoother so mouse scrolling never glitches
      function neutralizeScrollSmoother() {
        if (typeof ScrollSmoother !== 'undefined' && typeof ScrollSmoother.get === 'function') {
          try {
            var s = ScrollSmoother.get();
            if (s) s.kill();
          } catch (e) {}
        }
        var sw = document.getElementById('smooth-wrapper');
        var sc = document.getElementById('smooth-content');
        if (sw) {
          sw.style.cssText = 'position: static !important; overflow: visible !important; height: auto !important; width: 100% !important; transform: none !important; inset: auto !important;';
        }
        if (sc) {
          sc.style.cssText = 'position: static !important; overflow: visible !important; height: auto !important; width: 100% !important; transform: none !important;';
        }
      }
      neutralizeScrollSmoother();
      window.addEventListener('load', neutralizeScrollSmoother);

      // 1. Mark progressive enhancement
      document.documentElement.classList.add('js-animations');

      // 2. Initialize Lenis Smooth Scroll Engine (smoothWheel: false for 100% natural, glitch-free mouse scrolling)
      function initLenis() {
        if (typeof Lenis !== 'undefined' && !window.lenis) {
          try {
            var lenis = new Lenis({
              duration: 1.0,
              smoothWheel: false,
              smoothTouch: false
            });
            window.lenis = lenis;

            function raf(time) {
              lenis.raf(time);
              requestAnimationFrame(raf);
            }
            requestAnimationFrame(raf);

            // Internal anchor smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
              anchor.addEventListener('click', function(e) {
                var targetId = this.getAttribute('href');
                if (!targetId || targetId === '#') return;
                var target = document.querySelector(targetId);
                if (target) {
                  e.preventDefault();
                  lenis.scrollTo(target, { offset: -70, duration: 1.15 });
                }
              });
            });
          } catch (err) {
            console.warn('Lenis fallback', err);
          }
        }
      }
      initLenis();
      window.addEventListener('DOMContentLoaded', initLenis);
      window.addEventListener('load', initLenis);

      // 3. Hardware-Accelerated Intersection Observer
      function initAgencyAnimations() {
        var targets = document.querySelectorAll(
          '.vispan-general-agency-page .vispan-reveal-left, ' +
          '.vispan-general-agency-page .vispan-reveal-right, ' +
          '.vispan-general-agency-page .vispan-reveal-up, ' +
          '.vispan-general-agency-page .vispan-reveal-fade, ' +
          '.vispan-general-agency-page [data-animate]'
        );

        if (!targets.length) return;

        function activate(el) {
          el.classList.add('vispan-in-view');
          el.classList.add('is-animated');
        }

        // Above the fold trigger
        setTimeout(function() {
          for (var i = 0; i < targets.length; i++) {
            var rect = targets[i].getBoundingClientRect();
            if (rect.top < (window.innerHeight || document.documentElement.clientHeight) && rect.bottom > 0) {
              activate(targets[i]);
            }
          }
        }, 50);

        if ('IntersectionObserver' in window) {
          var observer = new IntersectionObserver(function(entries, obs) {
            for (var j = 0; j < entries.length; j++) {
              if (entries[j].isIntersecting) {
                activate(entries[j].target);
                obs.unobserve(entries[j].target);
              }
            }
          }, {
            threshold: 0.08,
            rootMargin: '0px 0px -40px 0px'
          });

          for (var k = 0; k < targets.length; k++) {
            observer.observe(targets[k]);
          }

          // Numeric stat counter observer
          var counterObserver = new IntersectionObserver(function(entries, obs) {
            for (var c = 0; c < entries.length; c++) {
              if (entries[c].isIntersecting) {
                animateCounter(entries[c].target);
                obs.unobserve(entries[c].target);
              }
            }
          }, { threshold: 0.25 });

          document.querySelectorAll('.stat-counter').forEach(function(el) {
            counterObserver.observe(el);
          });
        } else {
          for (var m = 0; m < targets.length; m++) {
            activate(targets[m]);
          }
        }

        // Floating Back to Top Button
        var btt = document.getElementById('back-to-top-btn');
        if (btt) {
          window.addEventListener('scroll', function() {
            if (window.scrollY > 400) {
              btt.classList.add('visible');
            } else {
              btt.classList.remove('visible');
            }
          }, { passive: true });

          btt.addEventListener('click', function() {
            if (window.lenis) {
              window.lenis.scrollTo(0, { duration: 1.2 });
            } else {
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
          });
        }
      }

      function animateCounter(el) {
        var rawTarget = el.getAttribute('data-target') || el.textContent.trim();
        var match = rawTarget.match(/([\d\.]+)/);
        if (!match) return;
        var targetNum = parseFloat(match[1]);
        var prefix = rawTarget.slice(0, match.index);
        var suffix = rawTarget.slice(match.index + match[1].length);
        var isDecimal = match[1].includes('.');
        var duration = 1600;
        var start = performance.now();

        function update(now) {
          var elapsed = now - start;
          var progress = Math.min(elapsed / duration, 1);
          var ease = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
          var current = targetNum * ease;
          el.textContent = prefix + (isDecimal ? current.toFixed(1) : Math.floor(current)) + suffix;
          if (progress < 1) {
            requestAnimationFrame(update);
          } else {
            el.textContent = rawTarget;
          }
        }
        requestAnimationFrame(update);
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAgencyAnimations);
      } else {
        initAgencyAnimations();
      }
    })();
  </script>
</div>
