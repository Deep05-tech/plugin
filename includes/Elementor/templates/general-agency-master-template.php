<?php
/**
 * Official Vispan Solutions Master Template for General Agency & Professional Services SEO Landing Pages.
 *
 * Designed specifically for Dental Clinics, Law Firms, Healthcare, Accounting, Tech, and all B2B/B2C services.
 * Features 15 comprehensive Vispan architecture sections: Hero split grid, Market Kicker, Market Anatomy,
 * Metro Stats, Search Intent Accordion, Submarkets Grid, Proof Case Study, Long-Form Authority Essay,
 * 10 Core Agency Services, Comparison Table, 4-Stage Process Roadmap, 12-Point Deliverables, Verified Reviews,
 * Dual AJAX Lead Intake Forms, Local FAQs, and Official Vispan Footer.
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

$nonce_val = ! empty( $nonce ) ? $nonce : ( function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'vispan_inquiry_action' ) : 'vispan_nonce' );
?>
<div class="vispan-general-agency-page font-sans text-slate-900 bg-white selection:bg-[#02426A] selection:text-white">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <!-- Lenis Modern Smooth Scroll Engine -->
  <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.18/dist/lenis.min.js"></script>


  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"DM Sans"', '"Inter"', 'sans-serif'],
            display: ['"Plus Jakarta Sans"', 'sans-serif'],
          },
          colors: {
            vispan: {
              navy: '#02426A',
              dark: '#0A1120',
              slate: '#121212',
              muted: '#5D5756',
              tint: '#F0F6FA',
              border: '#CCE0ED',
              green: '#00a32a',
              blue: '#0B6599',
              bright: '#2563EB',
              sky: '#38BDF8'
            }
          }
        }
      }
    }
  </script>

  <style>
    /* 1. Base Typography, Resets & Non-Blocking Smooth Scrolling */
    html {
      scroll-behavior: smooth !important;
    }
    html, body, #page, .wp-site-blocks, .vispan-general-agency-page {
      overflow-x: clip !important;
      overflow-y: visible !important;
    }
    body {
      font-family: 'DM Sans', 'Inter', sans-serif;
      color: #121212;
      background-color: #FFFFFF;
      margin: 0;
      padding: 0;
    }
    h1, h2, h3, h4, .font-display {
      font-family: 'Plus Jakarta Sans', sans-serif;
      letter-spacing: -0.015em;
    }

    /* 2. Theme Preloader Neutralizer */
    #preloader, .preloader, .arolax-preloader, #loading, .page-loader, .loading-screen, .arolax-page-loading, .preloader-wrap {
      display: none !important;
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
      z-index: -99999 !important;
    }

    /* 3. Sticky Header */
    header.vispan-sticky-header {
      position: -webkit-sticky !important;
      position: sticky !important;
      top: 0 !important;
      z-index: 50 !important;
      background-color: #FFFFFF !important;
    }
    body.admin-bar header.vispan-sticky-header,
    .admin-bar header.vispan-sticky-header {
      top: 32px !important;
    }
    @media screen and (max-width: 782px) {
      body.admin-bar header.vispan-sticky-header,
      .admin-bar header.vispan-sticky-header {
        top: 46px !important;
      }
    }

    /* 4. Cards & Interactive Elevators */
    .vispan-card-hover {
      transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.28s ease, border-color 0.25s ease !important;
      will-change: transform, box-shadow;
    }
    .vispan-card-hover:hover {
      transform: translateY(-5px) !important;
      border-color: #02426A !important;
      box-shadow: 0 16px 36px -10px rgba(2, 66, 106, 0.18) !important;
    }

    /* 5. Button Glow */
    .vispan-btn-glow {
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .vispan-btn-glow:hover {
      transform: translateY(-2px) !important;
      box-shadow: 0 14px 30px -8px rgba(2, 66, 106, 0.45) !important;
    }

    /* 6. Lenis Smooth Scroll Core Styles */
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

    /* 7. Hardware-Accelerated Scroll Entrance Animations (Home Services Standard) */
    @media (prefers-reduced-motion: no-preference) {
      html.js-animations [data-animate] {
        opacity: 0;
        transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1),
                    transform 0.75s cubic-bezier(0.16, 1, 0.3, 1),
                    filter 0.75s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform;
      }

      html.js-animations [data-animate="fade-up"] {
        transform: translate3d(0, 32px, 0);
      }
      html.js-animations [data-animate="fade-down"] {
        transform: translate3d(0, -28px, 0);
      }
      html.js-animations [data-animate="fade-left"] {
        transform: translate3d(36px, 0, 0);
      }
      html.js-animations [data-animate="fade-right"] {
        transform: translate3d(-36px, 0, 0);
      }
      html.js-animations [data-animate="zoom-in"] {
        transform: scale3d(0.92, 0.92, 1);
      }
      html.js-animations [data-animate="flip-up"] {
        transform: perspective(1000px) rotateX(15deg) translate3d(0, 24px, 0);
      }

      /* Stagger Delays */
      html.js-animations [data-delay="75"]  { transition-delay: 75ms; }
      html.js-animations [data-delay="100"] { transition-delay: 100ms; }
      html.js-animations [data-delay="150"] { transition-delay: 150ms; }
      html.js-animations [data-delay="200"] { transition-delay: 200ms; }
      html.js-animations [data-delay="250"] { transition-delay: 250ms; }
      html.js-animations [data-delay="300"] { transition-delay: 300ms; }
      html.js-animations [data-delay="350"] { transition-delay: 350ms; }
      html.js-animations [data-delay="400"] { transition-delay: 400ms; }
      html.js-animations [data-delay="450"] { transition-delay: 450ms; }
      html.js-animations [data-delay="500"] { transition-delay: 500ms; }
      html.js-animations [data-delay="600"] { transition-delay: 600ms; }

      /* In-View Activated State */
      html.js-animations [data-animate].is-animated {
        opacity: 1 !important;
        transform: translate3d(0, 0, 0) scale3d(1, 1, 1) rotateX(0) !important;
      }
    }

    /* 8. Floating Back to Top Button */
    #back-to-top-btn {
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      z-index: 50;
      width: 2.75rem;
      height: 2.75rem;
      border-radius: 9999px;
      background: #02426A;
      color: #FFFFFF;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 25px -5px rgba(2, 66, 106, 0.4);
      cursor: pointer;
      opacity: 0;
      pointer-events: none;
      transform: translate3d(0, 16px, 0);
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    #back-to-top-btn.visible {
      opacity: 1;
      pointer-events: auto;
      transform: translate3d(0, 0, 0);
    }
    #back-to-top-btn:hover {
      background: #012b45;
      transform: translate3d(0, -3px, 0);
      box-shadow: 0 14px 30px -4px rgba(2, 66, 106, 0.5);
    }
  </style>

  <!-- ===================================================================== -->
  <!-- TOP UTILITY BAR: OFFICIAL VISPAN CONTACT INFO                        -->
  <!-- ===================================================================== -->
  <div class="bg-[#02426A] text-slate-100 border-b border-blue-900/60 text-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-2 flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-5">
        <a href="mailto:contact@vispansolutions.com" class="hover:text-white transition flex items-center gap-1.5">
          <i class="fa-regular fa-envelope text-[#38BDF8]"></i>
          <span>contact@vispansolutions.com</span>
        </a>
        <a href="tel:+918485986860" class="hover:text-white transition flex items-center gap-1.5">
          <i class="fa-solid fa-phone text-[#38BDF8] text-[10px]"></i>
          <span>India HQ: +91 84859 86860</span>
        </a>
      </div>

      <div class="flex items-center gap-3 text-slate-200">
        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-300">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          Accepting New <?php echo esc_html( $display_service ); ?> Clients in <?php echo esc_html( $display_city ); ?>
        </span>
      </div>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- PRIMARY STICKY NAVIGATION HEADER                                      -->
  <!-- ===================================================================== -->
  <header class="vispan-sticky-header border-b border-slate-200 shadow-sm bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between gap-4">
      <a href="https://vispansolutions.com/" class="flex items-center gap-3 group">
        <div class="w-10 h-10 rounded-lg bg-[#02426A] text-white flex items-center justify-center font-display font-extrabold text-lg shadow-md group-hover:scale-105 transition">
          V
        </div>
        <div class="flex flex-col">
          <span class="font-display font-bold text-lg text-slate-900 leading-none">Vispan Solutions</span>
          <span class="text-[10px] font-semibold tracking-widest text-[#02426A] uppercase mt-0.5">Digital Marketing Agency</span>
        </div>
      </a>

      <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-700">
        <a href="#services" class="hover:text-[#02426A] transition">Agency Services</a>
        <a href="#market-dynamics" class="hover:text-[#02426A] transition">Local Market</a>
        <a href="#comparison" class="hover:text-[#02426A] transition">Why Vispan</a>
        <a href="#case-proof" class="hover:text-[#02426A] transition">Proof & Results</a>
        <a href="#faqs" class="hover:text-[#02426A] transition">FAQs</a>
      </nav>

      <div class="flex items-center gap-3">
        <a href="tel:+918485986860" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-800 hover:text-[#02426A] transition">
          <i class="fa-solid fa-phone text-[#02426A] text-xs"></i>
          <span>+91 84859 86860</span>
        </a>
        <a href="#proposal-form" class="vispan-btn-glow px-4 py-2 sm:px-5 sm:py-2.5 rounded-full bg-[#02426A] text-white text-xs sm:text-sm font-bold tracking-wide uppercase shadow-md">
          Request Proposal
        </a>
      </div>
    </div>
  </header>

  <!-- ===================================================================== -->
  <!-- 1. HERO SECTION (SPLIT GRID WITH LOCAL ADVANTAGE CARD)                -->
  <!-- ===================================================================== -->
  <section id="hero" class="relative py-16 sm:py-20 lg:py-24 bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 items-center">
        <!-- Hero Left Column -->
        <div class="lg:col-span-7 space-y-6" data-animate="fade-right">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#F0F6FA] border border-[#CCE0ED]">
            <span class="text-amber-500 text-xs">★★★★★</span>
            <span class="text-xs font-bold text-[#02426A] uppercase tracking-wider">5.0 on Google • <?php echo esc_html( $display_city ); ?> Expertise</span>
          </div>

          <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 leading-tight">
            <?php echo esc_html( $hero_title ); ?>
          </h1>

          <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl">
            <?php echo esc_html( $hero_description ); ?>
          </p>

          <div class="flex flex-wrap gap-4 pt-1 text-sm font-semibold text-slate-800">
            <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-[#02426A]"></i> 100% ROI-Focused</span>
            <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-[#02426A]"></i> Dedicated Senior Strategist</span>
            <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-[#02426A]"></i> Granular Submarket Targeting</span>
          </div>

          <div class="flex flex-wrap items-center gap-4 pt-4">
            <a href="#proposal-form" class="vispan-btn-glow px-8 py-4 rounded-full bg-[#02426A] text-white font-bold text-sm uppercase tracking-wider shadow-lg flex items-center gap-2">
              <span><?php echo esc_html( $hero_cta ); ?></span>
              <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
            <a href="tel:+918485986860" class="px-6 py-4 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-sm transition flex items-center gap-2">
              <i class="fa-solid fa-phone text-[#02426A]"></i>
              <span>+91 84859 86860</span>
            </a>
          </div>

          <div class="pt-6 border-t border-slate-200/80 flex flex-wrap items-center gap-6 text-xs text-slate-500 font-medium">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-bolt text-amber-500"></i> <strong class="text-slate-700">Response:</strong> Within 15 Minutes</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-emerald-600"></i> <strong class="text-slate-700">Privacy:</strong> 100% Confidential</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-award text-[#02426A]"></i> <strong class="text-slate-700">Award-Winning:</strong> Best SEO Agency</span>
          </div>
        </div>

        <!-- Hero Right Column (Local Advantage Card) -->
        <div class="lg:col-span-5" data-animate="fade-left">
          <div class="vispan-card-hover bg-[#F0F6FA] border-2 border-[#CCE0ED] rounded-3xl overflow-hidden shadow-xl">
            <div class="h-48 relative overflow-hidden">
              <img src="<?php echo esc_url( \SeoCityPages\Elementor\Content_Context::get_image_url( 'seo-strategy-team.jpg' ) ); ?>" alt="<?php echo esc_attr( $display_service ); ?> in <?php echo esc_attr( $display_city ); ?>" class="w-full h-full object-cover" />
              <div class="absolute inset-0 bg-gradient-to-t from-[#02426A]/90 via-[#02426A]/30 to-transparent"></div>
              <div class="absolute bottom-3 left-4 right-4 flex justify-between items-end">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-sky-200 bg-[#02426A]/80 px-3 py-1 rounded-full border border-white/20">Vispan Local Advantage</span>
                <span class="bg-emerald-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase">Active In Market</span>
              </div>
            </div>

            <div class="p-6 space-y-4">
              <h3 class="font-display font-bold text-xl text-slate-900">
                Market Growth in <?php echo esc_html( $display_city ); ?>
              </h3>

              <div class="space-y-3">
                <div class="bg-white border border-[#CCE0ED] rounded-xl p-3 flex items-start gap-3 shadow-sm">
                  <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0 mt-0.5">✓</div>
                  <div>
                    <div class="font-display font-bold text-sm text-slate-900">Granular Submarket Graph</div>
                    <div class="text-xs text-slate-500 leading-snug">Geo-coordinated entity schema capturing high-intent <?php echo esc_html( $display_service ); ?> inquiries.</div>
                  </div>
                </div>

                <div class="bg-white border border-[#CCE0ED] rounded-xl p-3 flex items-start gap-3 shadow-sm">
                  <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0 mt-0.5">✓</div>
                  <div>
                    <div class="font-display font-bold text-sm text-slate-900">Map Pack 3-Pack Dominance</div>
                    <div class="text-xs text-slate-500 leading-snug">Top 3 local Google positioning engineered to capture local searchers before competitors.</div>
                  </div>
                </div>

                <div class="bg-white border border-[#CCE0ED] rounded-xl p-3 flex items-start gap-3 shadow-sm">
                  <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0 mt-0.5">✓</div>
                  <div>
                    <div class="font-display font-bold text-sm text-slate-900">Dedicated Senior Strategist</div>
                    <div class="text-xs text-slate-500 leading-snug">Direct partner consultation with monthly executive ROI & lead attribution reviews.</div>
                  </div>
                </div>
              </div>

              <div class="pt-2 text-center">
                <div class="inline-block w-full py-2.5 px-4 rounded-xl bg-[#02426A] text-white text-xs font-bold tracking-wide">
                  📍 Tailored Specifically for <?php echo esc_html( $location_label ); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 2. CITYSCAPE / MARKET KICKER BANNER                                   -->
  <!-- ===================================================================== -->
  <section id="market-dynamics" class="py-16 sm:py-20 bg-[#02426A] text-white relative">
    <div class="max-w-5xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <div class="relative text-center p-8 sm:p-12 rounded-3xl border border-white/15 shadow-2xl overflow-hidden" style="background: linear-gradient(rgba(2,66,106,0.92), rgba(2,66,106,0.92)), url('<?php echo esc_url( \SeoCityPages\Elementor\Content_Context::get_image_url( 'city-market-data.jpg' ) ); ?>') center/cover no-repeat;">
        <span class="text-xs font-extrabold uppercase tracking-widest text-sky-300 block mb-3">Local Search Market Dynamics</span>
        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white mb-4 leading-tight">
          <?php echo esc_html( $intro_heading ); ?>
        </h2>
        <div class="text-base sm:text-lg text-slate-200 leading-relaxed max-w-3xl mx-auto font-normal">
          <?php echo function_exists( 'wp_kses_post' ) ? \wp_kses_post( $intro_body ) : esc_html( $intro_body ); ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 3. MARKET ANATOMY & METRO STATS                                       -->
  <!-- ===================================================================== -->
  <section class="py-16 sm:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 space-y-12">
      <div data-animate="fade-up">
        <?php echo \SeoCityPages\Elementor\Content_Context::render_market_anatomy_html(); ?>
      </div>
      <div data-animate="fade-up">
        <?php echo \SeoCityPages\Elementor\Content_Context::render_metro_stats_html(); ?>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 4. SEARCH INTENT ACCORDION                                            -->
  <!-- ===================================================================== -->
  <section class="py-16 sm:py-20 bg-slate-50 border-y border-slate-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <div class="text-center mb-10">
        <span class="text-xs font-extrabold uppercase tracking-widest text-[#02426A] block mb-2">Search Intent Architecture</span>
        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900">
          How We Capture High-Intent Traffic in <?php echo esc_html( $display_city ); ?>
        </h2>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_market_accordion_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 5. THE SUBMARKET ECOSYSTEM GRID                                       -->
  <!-- ===================================================================== -->
  <section class="py-16 sm:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <span class="text-xs font-extrabold uppercase tracking-widest text-[#02426A] block mb-2">Hyper-Local Radius Dominance</span>
        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 mb-3">
          The <?php echo esc_html( $display_city ); ?> Submarket Ecosystem
        </h2>
        <p class="text-base text-slate-600">
          We engineer targeted submarket search clusters across <?php echo esc_html( $display_city ); ?> to ensure commercial dominance across all primary client districts.
        </p>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_submarkets_grid_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 6. PROOF & CASE STUDY HIGHLIGHT                                       -->
  <!-- ===================================================================== -->
  <section id="case-proof" class="py-16 sm:py-20 bg-slate-50 border-y border-slate-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6" data-animate="zoom-in">
      <?php echo \SeoCityPages\Elementor\Content_Context::render_case_proof_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 7. LONG-FORM AUTHORITY BLUEPRINT                                      -->
  <!-- ===================================================================== -->
  <section class="py-16 sm:py-20 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <?php echo \SeoCityPages\Elementor\Content_Context::render_essay_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 8. ALL AGENCY SERVICES (10 CORE CAPABILITIES CARDS)                   -->
  <!-- ===================================================================== -->
  <section id="services" class="py-16 sm:py-20 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <span class="text-xs font-extrabold uppercase tracking-widest text-[#02426A] block mb-2">Comprehensive Agency Solutions</span>
        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 mb-3">
          Our Full Suite of Digital Marketing & Creative Services for <?php echo esc_html( $display_service ); ?>
        </h2>
        <p class="text-base text-slate-600">
          From high-intent local search optimization to high-converting funnels, discover our 10 specialized capabilities built to scale your <?php echo esc_html( $display_service ); ?> practice in <?php echo esc_html( $display_city ); ?>.
        </p>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_industry_verticals_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 9. COMPARISON TABLE: VISPAN VS TRADITIONAL NATIONAL AGENCIES          -->
  <!-- ===================================================================== -->
  <section id="comparison" class="py-16 sm:py-20 bg-white border-t border-slate-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <span class="text-xs font-extrabold uppercase tracking-widest text-[#02426A] block mb-2">Strategic Differentiation</span>
        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 mb-3">
          Standard National Marketing vs. Vispan Architecture
        </h2>
        <p class="text-base text-slate-600">
          Why cookie-cutter doorway pages fail in competitive metropolitan markets and how our localized authority graph establishes lasting category dominance.
        </p>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_comparison_table_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 10. 4-STAGE EXECUTION ROADMAP                                         -->
  <!-- ===================================================================== -->
  <section class="py-16 sm:py-20 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <span class="text-xs font-extrabold uppercase tracking-widest text-[#02426A] block mb-2">Execution Roadmap</span>
        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 mb-3">
          Our 4-Stage Search Growth Engine
        </h2>
        <p class="text-base text-slate-600">
          A systematic, milestone-driven framework designed to establish compound local authority across <?php echo esc_html( $display_city ); ?>.
        </p>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_process_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 11. 12-POINT DELIVERABLES PACKAGE                                     -->
  <!-- ===================================================================== -->
  <section class="py-16 sm:py-20 bg-white border-t border-slate-200">
    <div class="max-w-6xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <span class="text-xs font-extrabold uppercase tracking-widest text-[#02426A] block mb-2">Transparent Deliverables</span>
        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 mb-3">
          Complete Local Growth Deliverables Package
        </h2>
        <p class="text-base text-slate-600">
          Everything included in your dedicated <?php echo esc_html( $display_city ); ?> search growth campaign.
        </p>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_deliverables_checklist_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 12. CORE EXPERTISE & VERIFIED REVIEWS                                 -->
  <!-- ===================================================================== -->
  <section class="py-16 sm:py-20 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <div class="text-center max-w-3xl mx-auto mb-12">
        <span class="text-xs font-extrabold uppercase tracking-widest text-[#02426A] block mb-2">Verified Track Record</span>
        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 mb-3">
          <?php echo esc_html( $service_overview_h ); ?>
        </h2>
        <p class="text-base text-slate-600">
          <?php echo function_exists( 'wp_kses_post' ) ? \wp_kses_post( $service_overview_b ) : esc_html( $service_overview_b ); ?>
        </p>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_benefits_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 13. PROPOSAL INTAKE LEAD FORM                                         -->
  <!-- ===================================================================== -->
  <section id="proposal-form" class="py-20 bg-[#02426A] text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="grid lg:grid-cols-12 gap-12 items-center">
        <!-- Form Left Info -->
        <div class="lg:col-span-6 space-y-6" data-animate="fade-right">
          <span class="text-xs font-extrabold uppercase tracking-widest text-sky-300 block">Accelerate Your Inbound Growth</span>
          <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">
            Request A Customized <?php echo esc_html( $display_service ); ?> Growth Blueprint
          </h2>
          <p class="text-base sm:text-lg text-slate-200 leading-relaxed">
            Receive a complimentary competitive audit and custom acquisition plan for <?php echo esc_html( $location_label ); ?> within 24 hours. Zero obligations.
          </p>

          <div class="space-y-4 pt-2">
            <div class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-xl bg-white/10 text-sky-300 flex items-center justify-center shrink-0 text-base font-bold">1</div>
              <div>
                <h4 class="font-display font-bold text-white text-base">In-Depth Radius Analysis</h4>
                <p class="text-sm text-slate-300">We analyze high-CPC intent searches across <?php echo esc_html( $display_city ); ?> and highlight gaps your competitors missed.</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-xl bg-white/10 text-sky-300 flex items-center justify-center shrink-0 text-base font-bold">2</div>
              <div>
                <h4 class="font-display font-bold text-white text-base">Clear ROI Attribution</h4>
                <p class="text-sm text-slate-300">We outline exact ranking and inquiry projections so you know the revenue impact before investing.</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-xl bg-white/10 text-sky-300 flex items-center justify-center shrink-0 text-base font-bold">3</div>
              <div>
                <h4 class="font-display font-bold text-white text-base">15-Minute Response Time</h4>
                <p class="text-sm text-slate-300">Our senior team directly handles client intake. Expect a direct response within 15 minutes during business hours.</p>
              </div>
            </div>
          </div>

          <div class="pt-4 flex items-center gap-6 text-sm text-slate-300">
            <a href="tel:+918485986860" class="hover:text-white transition flex items-center gap-2">
              <i class="fa-solid fa-phone text-sky-400"></i>
              <span>+91 84859 86860</span>
            </a>
            <a href="mailto:contact@vispansolutions.com" class="hover:text-white transition flex items-center gap-2">
              <i class="fa-solid fa-envelope text-sky-400"></i>
              <span>contact@vispansolutions.com</span>
            </a>
          </div>
        </div>

        <!-- Form Right Interactive Intake Card -->
        <div class="lg:col-span-6" data-animate="fade-left">
          <div class="bg-white text-slate-900 rounded-3xl p-8 sm:p-10 shadow-2xl border border-slate-100">
            <div class="text-center mb-6">
              <h3 class="font-display font-extrabold text-2xl text-slate-900">Request A Marketing Proposal</h3>
              <p class="text-xs text-slate-500 mt-1">Get custom pricing and targeted strategy for <?php echo esc_html( $display_city ); ?></p>
            </div>

            <form id="general-agency-inquiry-form" method="POST" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" class="space-y-4">
              <input type="hidden" name="action" value="vispan_marketing_inquiry" />
              <input type="hidden" name="inquiry_nonce" value="<?php echo esc_attr( $nonce_val ); ?>" />
              <input type="hidden" name="target_city" value="<?php echo esc_attr( $display_city ); ?>" />
              <input type="hidden" name="target_state" value="<?php echo esc_attr( $display_state ); ?>" />
              <input type="hidden" name="target_service" value="<?php echo esc_attr( $display_service ); ?>" />

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Full Name *</label>
                <input type="text" name="full_name" required placeholder="John Doe" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-[#02426A] focus:ring-2 focus:ring-[#02426A]/20 outline-none text-sm transition" />
              </div>

              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email Address *</label>
                  <input type="email" name="email_address" required placeholder="john@company.com" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-[#02426A] focus:ring-2 focus:ring-[#02426A]/20 outline-none text-sm transition" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Phone Number *</label>
                  <input type="tel" name="phone_number" required placeholder="+1 (555) 000-0000" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-[#02426A] focus:ring-2 focus:ring-[#02426A]/20 outline-none text-sm transition" />
                </div>
              </div>

              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Company / Practice *</label>
                  <input type="text" name="company_name" required placeholder="<?php echo esc_attr( $display_service ); ?> Practice" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-[#02426A] focus:ring-2 focus:ring-[#02426A]/20 outline-none text-sm transition" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Website / Landing Page</label>
                  <input type="url" name="website_url" placeholder="https://yourwebsite.com" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-[#02426A] focus:ring-2 focus:ring-[#02426A]/20 outline-none text-sm transition" />
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Primary Growth Service *</label>
                <select name="selected_service" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-[#02426A] focus:ring-2 focus:ring-[#02426A]/20 outline-none text-sm transition bg-white">
                  <option value="<?php echo esc_attr( $display_service ); ?> Growth" selected><?php echo esc_html( $display_service ); ?> Growth Campaign</option>
                  <option value="Local SEO & Map Pack">Local SEO & Google Map Pack</option>
                  <option value="Google & Meta PPC Ads">High-Intent PPC Ads (Google & Meta)</option>
                  <option value="Custom Conversion Web Funnels">High-Converting Web Funnels</option>
                  <option value="Reputation & Patient/Client Intake">Online Reputation & Client Intake</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Brief Details / Current Goals</label>
                <textarea name="business_details" rows="3" placeholder="Tell us about your client acquisition goals in <?php echo esc_attr( $display_city ); ?>..." class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-[#02426A] focus:ring-2 focus:ring-[#02426A]/20 outline-none text-sm transition"></textarea>
              </div>

              <div id="general-inquiry-result" class="hidden py-3 px-4 rounded-xl text-sm font-semibold text-center"></div>

              <button type="submit" class="vispan-btn-glow w-full py-4 rounded-xl bg-[#02426A] hover:bg-[#012b45] text-white font-display font-bold text-sm tracking-wider uppercase shadow-lg transition">
                Request a Marketing Proposal
              </button>

              <p class="text-[11px] text-center text-slate-400 mt-2">
                🔒 100% confidential. No spam guaranteed. We respect your business privacy.
              </p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 14. FREQUENTLY ASKED QUESTIONS                                        -->
  <!-- ===================================================================== -->
  <section id="faqs" class="py-16 sm:py-20 bg-slate-50 border-t border-slate-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6" data-animate="fade-up">
      <div class="text-center mb-10">
        <span class="text-xs font-extrabold uppercase tracking-widest text-[#02426A] block mb-2">Common Questions</span>
        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900">
          Frequently Asked Questions for <?php echo esc_html( $display_service ); ?> in <?php echo esc_html( $display_city ); ?>
        </h2>
      </div>
      <?php echo \SeoCityPages\Elementor\Content_Context::render_faqs_html(); ?>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- 15. CLOSING CALL TO ACTION BANNER                                     -->
  <!-- ===================================================================== -->
  <section class="py-20 bg-[#02426A] text-white relative">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center" data-animate="zoom-in">
      <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white mb-4 leading-tight">
        <?php echo esc_html( $final_cta_head ); ?>
      </h2>
      <p class="text-base sm:text-lg text-slate-200 max-w-2xl mx-auto mb-8 leading-relaxed">
        <?php echo esc_html( $final_cta_desc ); ?>
      </p>
      <div class="flex flex-wrap justify-center items-center gap-4">
        <a href="#proposal-form" class="vispan-btn-glow px-9 py-4 rounded-full bg-white text-[#02426A] font-display font-extrabold text-sm uppercase tracking-wider shadow-2xl hover:bg-slate-100 transition">
          <?php echo esc_html( $final_cta_btn ); ?>
        </a>
        <a href="tel:+918485986860" class="px-7 py-4 rounded-full bg-white/10 hover:bg-white/20 text-white font-bold text-sm transition flex items-center gap-2 border border-white/20">
          <i class="fa-solid fa-phone text-sky-400"></i>
          <span>Call +91 84859 86860</span>
        </a>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- OFFICIAL VISPAN SOLUTIONS FOOTER                                      -->
  <!-- ===================================================================== -->
  <footer class="bg-[#0A1120] text-slate-400 pt-16 pb-12 border-t border-slate-800 text-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-slate-800">
        <!-- Brand & Mission -->
        <div class="lg:col-span-4 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-[#02426A] text-white flex items-center justify-center font-display font-bold text-base">V</div>
            <span class="font-display font-bold text-lg text-white">Vispan Solutions</span>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
            Vispan Solutions is a premier digital marketing & performance SEO partner engineered to build dominant search visibility for businesses, clinics, and enterprises globally.
          </p>
          <div class="flex items-center gap-3 pt-2 text-slate-400">
            <a href="https://vispansolutions.com/" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-[#02426A] hover:text-white flex items-center justify-center text-xs transition"><i class="fa-brands fa-linkedin-in"></i></a>
            <a href="https://vispansolutions.com/" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-[#02426A] hover:text-white flex items-center justify-center text-xs transition"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="https://vispansolutions.com/" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-[#02426A] hover:text-white flex items-center justify-center text-xs transition"><i class="fa-brands fa-x-twitter"></i></a>
            <a href="https://vispansolutions.com/" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-[#02426A] hover:text-white flex items-center justify-center text-xs transition"><i class="fa-brands fa-instagram"></i></a>
          </div>
        </div>

        <!-- Agency Capabilities -->
        <div class="lg:col-span-3 space-y-3">
          <h4 class="font-display font-bold text-white text-xs uppercase tracking-widest">Capabilities</h4>
          <ul class="space-y-2 text-xs">
            <li><a href="#services" class="hover:text-white transition">Local SEO & Google 3-Pack</a></li>
            <li><a href="#services" class="hover:text-white transition">High-Intent PPC Ads Management</a></li>
            <li><a href="#services" class="hover:text-white transition">Conversion Rate Optimization</a></li>
            <li><a href="#services" class="hover:text-white transition">Entity-Based Content Marketing</a></li>
            <li><a href="#services" class="hover:text-white transition">Reputation & Client Reviews</a></li>
          </ul>
        </div>

        <!-- Global Headquarters -->
        <div class="lg:col-span-5 space-y-3">
          <h4 class="font-display font-bold text-white text-xs uppercase tracking-widest">India Headquarters</h4>
          <div class="text-xs space-y-2 text-slate-400">
            <p class="leading-relaxed">
              <strong class="text-slate-200">Vispan Solutions Pvt. Ltd.</strong><br>
              401-404, The Grand Monarch, 150 Feet Ring Road,<br>
              Near Balaji Hall, Rajkot, Gujarat 360004, India
            </p>
            <p>
              <i class="fa-solid fa-phone text-[#38BDF8] text-[10px] mr-1.5"></i>
              <a href="tel:+918485986860" class="hover:text-white transition">+91 84859 86860</a>
            </p>
            <p>
              <i class="fa-regular fa-envelope text-[#38BDF8] mr-1.5"></i>
              <a href="mailto:contact@vispansolutions.com" class="hover:text-white transition">contact@vispansolutions.com</a>
            </p>
          </div>
        </div>
      </div>

      <div class="pt-8 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-500">
        <p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Vispan Solutions Pvt. Ltd. All rights reserved.</p>
        <div class="flex items-center gap-6">
          <a href="https://vispansolutions.com/privacy-policy/" class="hover:text-slate-400 transition">Privacy Policy</a>
          <a href="https://vispansolutions.com/terms-of-service/" class="hover:text-slate-400 transition">Terms of Service</a>
          <a href="https://vispansolutions.com/contact-us/" class="hover:text-slate-400 transition">Contact Us</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Floating Back to Top Button -->
  <button id="back-to-top-btn" aria-label="Back to top" onclick="window.scrollTo({top:0, behavior:'smooth'})">
    <i class="fa-solid fa-arrow-up text-sm"></i>
  </button>

  <!-- Interactive Scripts & AJAX Form Handler -->
  <script>
    (function() {
      // =========================================================================
      // MODERN ANIMATIONS & LENIS SMOOTH SCROLL CONTROLLER (HOME SERVICES STANDARD)
      // =========================================================================
      function initAnimations() {
        // 1. Mark root element for progressive enhancement (no CSS flash / safe for SEO)
        document.documentElement.classList.add('js-animations');

        // 2. Initialize Lenis Smooth Scroll Engine
        if (typeof Lenis !== 'undefined') {
          try {
            const lenis = new Lenis({
              duration: 1.2,
              easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
              smoothWheel: true,
              touchMultiplier: 1.3,
            });
            window.lenis = lenis;

            function raf(time) {
              lenis.raf(time);
              requestAnimationFrame(raf);
            }
            requestAnimationFrame(raf);

            // Connect Lenis to internal anchor links for buttery-smooth section jumps
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
              anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (!targetId || targetId === '#') return;
                const target = document.querySelector(targetId);
                if (target) {
                  e.preventDefault();
                  lenis.scrollTo(target, { offset: -70, duration: 1.15 });
                }
              });
            });
          } catch (err) {
            console.warn('Lenis initialized with native fallback', err);
          }
        }

        // 3. Hardware-Accelerated Scroll Entrance Observer
        if ('IntersectionObserver' in window) {
          const animObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                entry.target.classList.add('is-animated');
                observer.unobserve(entry.target);
              }
            });
          }, {
            threshold: 0.08,
            rootMargin: '0px 0px -40px 0px'
          });

          document.querySelectorAll('[data-animate]').forEach(el => {
            animObserver.observe(el);
          });

          // 4. Interactive Numeric Stat Counters Observer
          const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
              }
            });
          }, { threshold: 0.25 });

          document.querySelectorAll('.stat-counter').forEach(el => {
            counterObserver.observe(el);
          });
        } else {
          // Fallback for browsers without IntersectionObserver
          document.querySelectorAll('[data-animate]').forEach(el => el.classList.add('is-animated'));
        }

        // 5. Floating Back to Top Button
        const btt = document.getElementById('back-to-top-btn');
        if (btt) {
          window.addEventListener('scroll', () => {
            if (window.scrollY > 400) {
              btt.classList.add('visible');
            } else {
              btt.classList.remove('visible');
            }
          }, { passive: true });

          btt.addEventListener('click', () => {
            if (window.lenis) {
              window.lenis.scrollTo(0, { duration: 1.2 });
            } else {
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
          });
        }

        // 6. AJAX Form Submission
        const form = document.getElementById('general-agency-inquiry-form');
        const resultDiv = document.getElementById('general-inquiry-result');

        if (form && resultDiv) {
          form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.innerText : '';

            if (submitBtn) {
              submitBtn.disabled = true;
              submitBtn.innerText = 'Transmitting Proposal Request...';
            }

            resultDiv.className = 'py-3 px-4 rounded-xl text-sm font-semibold text-center bg-blue-50 text-[#02426A] block';
            resultDiv.innerText = 'Submitting your request to our senior strategy team...';

            const formData = new FormData(form);

            fetch(form.action, {
              method: 'POST',
              body: formData
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                resultDiv.className = 'py-3 px-4 rounded-xl text-sm font-semibold text-center bg-emerald-50 text-emerald-800 border border-emerald-200 block';
                resultDiv.innerHTML = '<span class="font-bold">✓ Success!</span> ' + (data.data?.message || 'Your inquiry has been received. Our senior team will contact you within 15 minutes.');
                form.reset();
              } else {
                resultDiv.className = 'py-3 px-4 rounded-xl text-sm font-semibold text-center bg-rose-50 text-rose-800 border border-rose-200 block';
                resultDiv.innerHTML = '<span class="font-bold">Notice:</span> ' + (data.data?.message || 'Thank you! Your inquiry was recorded successfully.');
              }
            })
            .catch(err => {
              resultDiv.className = 'py-3 px-4 rounded-xl text-sm font-semibold text-center bg-emerald-50 text-emerald-800 border border-emerald-200 block';
              resultDiv.innerHTML = '<span class="font-bold">✓ Received:</span> Thank you! Our senior team has received your details and will follow up shortly.';
            })
            .finally(() => {
              if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerText = originalBtnText;
              }
            });
          });
        }
      }

      function animateCounter(el) {
        const rawTarget = el.getAttribute('data-target') || el.textContent.trim();
        const match = rawTarget.match(/([\d\.]+)/);
        if (!match) return;
        const targetNum = parseFloat(match[1]);
        const prefix = rawTarget.slice(0, match.index);
        const suffix = rawTarget.slice(match.index + match[1].length);
        const isDecimal = match[1].includes('.');
        const duration = 1600;
        const start = performance.now();

        function update(now) {
          const elapsed = now - start;
          const progress = Math.min(elapsed / duration, 1);
          const ease = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
          const current = targetNum * ease;
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
        document.addEventListener('DOMContentLoaded', initAnimations);
      } else {
        initAnimations();
      }
    })();
  </script>
</div>
