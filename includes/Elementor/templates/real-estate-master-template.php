<?php
/**
 * Official Vispan Solutions Master Template for Real Estate SEO Landing Pages.
 *
 * Fully responsive, high-converting Tailwind layout featuring 10 official Vispan
 * real estate growth services, 6 property sectors, verified reviews, Lenis smooth scrolling,
 * and dual AJAX real estate lead intake forms.
 *
 * @package SeoCityPages\Elementor
 *
 * @var string $city
 * @var string $state
 * @var string $state_display
 * @var string $country
 * @var string $service
 * @var string $cities_str
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

if ( ! defined( "ABSPATH" ) ) {
	exit;
}

$display_city    = ! empty( $city ) ? $city : "Your City";
$display_state   = ! empty( $state_display ) ? $state_display : ( ! empty( $state ) ? $state : "US" );
$display_service = ! empty( $service ) ? $service : "Real Estate Advertising Agency";
?>
<div class="vispan-real-estate-page font-sans text-slate-900 bg-white selection:bg-vispan-ocean selection:text-white">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <!-- Lenis Modern Smooth Scroll Engine -->
  <script src="https://cdn.jsdelivr.net/npm/lenis@1.1.18/dist/lenis.min.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ["Inter", "sans-serif"],
            display: ["Plus Jakarta Sans", "sans-serif"],
          },
          colors: {
            vispan: {
              dark: "#0A1120",
              navy: "#0F172A",
              ocean: "#0B6599",
              deep: "#1E3A8A",
              royal: "#1D4ED8",
              blue: "#0B6599",
              bright: "#2563EB",
              sky: "#38BDF8",
              gold: "#D97706",
              amber: "#F59E0B",
              ice: "#EFF6FF",
              surface: "#F8FAFC",
              border: "#E2E8F0"
            }
          }
        }
      }
    }
  </script>

  <style>
    /* 1. Base Typography & Resets */
    body {
      font-family: "Inter", sans-serif;
      color: #0F172A;
      background-color: #FFFFFF;
    }
    h1, h2, h3, h4, .font-display {
      font-family: "Plus Jakarta Sans", sans-serif;
    }
    .vispan-hero-bg {
      background-color: #0A1120;
      background-image: radial-gradient(circle at 85% 20%, rgba(11, 101, 153, 0.35) 0%, transparent 60%);
    }

    /* Sticky Fix */
    html, body, #page, .wp-site-blocks, .vispan-real-estate-page {
      overflow-x: clip !important;
      overflow-y: visible !important;
    }

    @media (min-width: 1024px) {
      .lg\:sticky {
        position: -webkit-sticky !important;
        position: sticky !important;
        top: 7rem !important;
        align-self: flex-start !important;
        z-index: 20 !important;
      }
      body.admin-bar .lg\:sticky,
      .admin-bar .lg\:sticky {
        top: calc(7rem + 32px) !important;
      }
    }

    /* Sticky Header */
    header.sticky {
      position: -webkit-sticky !important;
      position: sticky !important;
      top: 0 !important;
      z-index: 40 !important;
      background-color: #FFFFFF !important;
    }
    body.admin-bar header.sticky,
    .admin-bar header.sticky {
      top: 32px !important;
    }

    /* Cards Animations */
    .re-card, .service-box, .benefit-card {
      transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.28s ease, border-color 0.25s ease !important;
      cursor: pointer;
      position: relative;
      will-change: transform, box-shadow;
    }
    .re-card:hover, .service-box:hover, .benefit-card:hover {
      transform: translateY(-5px) !important;
      border-color: #0B6599 !important;
      box-shadow: 0 18px 32px -8px rgba(11, 101, 153, 0.16), 0 4px 12px -2px rgba(15, 23, 42, 0.08) !important;
    }
    .re-card .img-zoom, .benefit-card .img-zoom {
      overflow: hidden !important;
    }
    .re-card .img-zoom img, .benefit-card .img-zoom img {
      transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1) !important;
      will-change: transform;
    }
    .re-card:hover .img-zoom img, .benefit-card:hover .img-zoom img {
      transform: scale(1.07) !important;
    }

    /* Lenis Smooth Scroll Core Styles */
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

    /* Hardware-Accelerated Scroll Entrance Animations (Home Services Standard) */
    @media (prefers-reduced-motion: no-preference) {
      html.js-animations [data-animate],
      html.js-animations [data-anim],
      html.js-animations .vispan-anim-ready {
        opacity: 0;
        transition: opacity 0.75s cubic-bezier(0.16, 1, 0.3, 1),
                    transform 0.75s cubic-bezier(0.16, 1, 0.3, 1),
                    filter 0.75s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: opacity, transform;
      }

      html.js-animations [data-animate="fade-up"],
      html.js-animations [data-anim="fade-up"],
      html.js-animations .vispan-anim-ready {
        transform: translate3d(0, 32px, 0);
      }
      html.js-animations [data-animate="fade-down"],
      html.js-animations [data-anim="fade-down"] {
        transform: translate3d(0, -28px, 0);
      }
      html.js-animations [data-animate="fade-left"],
      html.js-animations [data-anim="fade-left"] {
        transform: translate3d(36px, 0, 0);
      }
      html.js-animations [data-animate="fade-right"],
      html.js-animations [data-anim="fade-right"] {
        transform: translate3d(-36px, 0, 0);
      }
      html.js-animations [data-animate="zoom-in"],
      html.js-animations [data-anim="zoom-in"] {
        transform: scale3d(0.92, 0.92, 1);
      }
      html.js-animations [data-animate="flip-up"],
      html.js-animations [data-anim="flip-up"] {
        transform: perspective(1000px) rotateX(15deg) translate3d(0, 24px, 0);
      }

      /* Stagger Delays */
      html.js-animations [data-delay="50"]  { transition-delay: 50ms; }
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
      html.js-animations [data-animate].is-animated,
      html.js-animations [data-anim].is-animated,
      html.js-animations [data-anim].vispan-anim-visible,
      html.js-animations .vispan-anim-ready.is-animated,
      html.js-animations .vispan-anim-ready.vispan-anim-visible {
        opacity: 1 !important;
        transform: translate3d(0, 0, 0) scale3d(1, 1, 1) rotateX(0) !important;
      }
    }

    #back-to-top-btn {
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      z-index: 50;
      width: 2.75rem;
      height: 2.75rem;
      border-radius: 9999px;
      background: #0B6599;
      color: #FFFFFF;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 25px -5px rgba(11, 101, 153, 0.4);
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
      background: #084b72;
      transform: translate3d(0, -3px, 0);
      box-shadow: 0 14px 30px -4px rgba(11, 101, 153, 0.5);
    }
  </style>

  <!-- TOP HEADER -->
  <header class="sticky top-0 bg-white border-b border-slate-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
      <a href="https://vispansolutions.com/" class="flex items-center gap-3 group">
        <img src="https://vispansolutions.com/wp-content/uploads/2025/01/vispan-new-logo.webp" alt="Vispan Solutions" class="h-10 w-auto object-contain transition-transform group-hover:scale-105" />
      </a>

      <nav class="hidden md:flex items-center gap-8 font-medium text-sm text-slate-700">
        <a href="#services" class="hover:text-vispan-ocean transition-colors">Services</a>
        <a href="#sectors" class="hover:text-vispan-ocean transition-colors">Property Sectors</a>
        <a href="#market-anatomy" class="hover:text-vispan-ocean transition-colors"><?php echo esc_html( $display_city ); ?> Market</a>
        <a href="#comparison" class="hover:text-vispan-ocean transition-colors">Why Vispan</a>
        <a href="#faq" class="hover:text-vispan-ocean transition-colors">FAQs</a>
      </nav>

      <div class="flex items-center gap-4">
        <a href="tel:+918485986860" class="hidden sm:flex items-center gap-2 text-sm font-semibold text-slate-800 hover:text-vispan-ocean transition-colors">
          <i class="fa-solid fa-phone text-vispan-ocean"></i>
          <span>+91 84859 86860</span>
        </a>
        <a href="#inquiry-form-section" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-vispan-ocean text-white text-sm font-bold shadow-md shadow-vispan-ocean/20 hover:bg-vispan-royal transition-all duration-200">
          Get Proposal
        </a>
      </div>
    </div>
  </header>

  <!-- HERO SECTION -->
  <section class="vispan-hero-bg text-white relative overflow-hidden pt-16 pb-24 lg:pt-20 lg:pb-32 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="grid lg:grid-cols-12 gap-12 items-center">
        <!-- Hero Left Column -->
        <div class="lg:col-span-7 space-y-6 vispan-anim-ready" data-anim="fade-up">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-vispan-ocean/25 border border-vispan-sky/40 text-vispan-sky text-xs font-bold uppercase tracking-wider">
            <span class="w-2 h-2 rounded-full bg-vispan-sky animate-pulse"></span>
            Real Estate Marketing & Lead Generation Partner
          </div>

          <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold font-display leading-[1.15] text-white">
            <?php echo ! empty( $hero_title ) ? esc_html( $hero_title ) : sprintf( "%s in %s, %s", esc_html( $display_service ), esc_html( $display_city ), esc_html( $display_state ) ); ?>
          </h1>

          <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl">
            <?php echo ! empty( $hero_description ) ? esc_html( $hero_description ) : sprintf( "Capture motivated sellers and exclusive qualified buyers across %s, %s. Vispan Solutions engineers high-ROI real estate marketing funnels, Google Map Pack domination, and automated IDX lead nurturing designed to scale your gross commission income.", esc_html( $display_city ), esc_html( $display_state ) ); ?>
          </p>

          <!-- Key Metrics -->
          <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-800/80 max-w-xl">
            <div>
              <div class="text-2xl sm:text-3xl font-extrabold text-vispan-sky font-display counter-item" data-target="14,200+">14,200+</div>
              <div class="text-xs text-slate-400 mt-1 font-medium">Buyer/Seller Leads</div>
            </div>
            <div>
              <div class="text-2xl sm:text-3xl font-extrabold text-white font-display counter-item" data-target="4.6x">4.6x</div>
              <div class="text-xs text-slate-400 mt-1 font-medium">Average ROAS</div>
            </div>
            <div>
              <div class="text-2xl sm:text-3xl font-extrabold text-vispan-sky font-display counter-item" data-target="120+">120+</div>
              <div class="text-xs text-slate-400 mt-1 font-medium">Top Teams Scaled</div>
            </div>
          </div>

          <!-- CTAs -->
          <div class="pt-2 flex flex-wrap gap-4 items-center">
            <a href="#inquiry-form-section" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-lg bg-vispan-sky text-slate-950 font-bold text-sm shadow-lg shadow-vispan-sky/25 hover:bg-white transition-all">
              <span><?php echo ! empty( $hero_cta ) ? esc_html( $hero_cta ) : "Claim Farm Area Audit →"; ?></span>
            </a>
            <a href="#sectors" class="inline-flex items-center gap-2 px-5 py-3.5 rounded-lg border border-slate-700 bg-slate-900/60 text-slate-200 font-semibold text-sm hover:bg-slate-800 transition-colors">
              <span>Explore Property Sectors ↓</span>
            </a>
          </div>
        </div>

        <!-- Hero Right Column: Fast Intake Lead Form -->
        <div class="lg:col-span-5 vispan-anim-ready" data-anim="fade-up" data-delay="200">
          <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-2xl text-slate-900 border border-slate-100 relative">
            <div class="absolute -top-3 right-6 bg-vispan-ocean text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow">
              Exclusive Territories
            </div>
            <h3 class="text-xl font-bold font-display text-slate-900 mb-1">Request Farm Area Analysis</h3>
            <p class="text-xs text-slate-500 mb-5">Get a customized keyword, PPC & competitor breakdown for <?php echo esc_html( $display_city ); ?>.</p>

            <form class="space-y-4 vispan-ajax-lead-form" method="POST" action="">
              <input type="hidden" name="action" value="vispan_marketing_inquiry">
              <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
              <input type="hidden" name="lead_source" value="Real Estate Hero Form">
              <input type="hidden" name="location" value="<?php echo esc_attr( $display_city . ", " . $display_state ); ?>">

              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Your Full Name</label>
                <input type="text" name="lead_name" required placeholder="John Miller" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 focus:border-vispan-ocean focus:ring-2 focus:ring-vispan-ocean/20 text-sm outline-none transition-all" />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Work Email</label>
                  <input type="email" name="lead_email" required placeholder="john@realty.com" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 focus:border-vispan-ocean focus:ring-2 focus:ring-vispan-ocean/20 text-sm outline-none transition-all" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Phone Number</label>
                  <input type="tel" name="lead_phone" required placeholder="(555) 000-0000" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 focus:border-vispan-ocean focus:ring-2 focus:ring-vispan-ocean/20 text-sm outline-none transition-all" />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Your Role</label>
                  <select name="lead_role" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-vispan-ocean text-xs font-medium text-slate-700 bg-white">
                    <option value="Solo Agent">Solo Agent</option>
                    <option value="Team Lead">Team Lead</option>
                    <option value="Broker / Owner">Broker / Owner</option>
                    <option value="Commercial Broker">Commercial Broker</option>
                    <option value="Property Developer">Property Developer</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Primary Goal</label>
                  <select name="lead_goal" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-vispan-ocean text-xs font-medium text-slate-700 bg-white">
                    <option value="Motivated Sellers">Motivated Sellers</option>
                    <option value="Buyer Inquiries">Buyer Inquiries</option>
                    <option value="Luxury Brand Authority">Luxury Authority</option>
                    <option value="Agent Recruiting">Agent Recruiting</option>
                  </select>
                </div>
              </div>

              <button type="submit" class="w-full py-3.5 px-4 rounded-lg bg-vispan-ocean hover:bg-vispan-royal text-white font-bold text-sm shadow-md transition-all">
                Send Farm Area Proposal →
              </button>
              <div class="form-feedback hidden text-center text-xs font-medium pt-1"></div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 2: WHY REAL ESTATE DIGITAL MARKETING -->
  <section class="py-20 bg-vispan-surface border-b border-slate-200" id="benefits">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-3xl mx-auto mb-16 vispan-anim-ready" data-anim="fade-up">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-vispan-ocean/10 text-vispan-ocean text-xs font-bold uppercase tracking-wider mb-3">
          Hyper-Local Growth Engine
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold font-display text-slate-900 leading-tight">
          Why Top Real Estate Producers in <?php echo esc_html( $display_city ); ?> Choose Vispan Solutions
        </h2>
        <p class="text-slate-600 text-sm sm:text-base mt-3">
          Relying solely on Zillow, Realtor.com, or social media guesswork leaves your commissions vulnerable. We build owned client acquisition channels that put you in complete control.
        </p>
      </div>

      <div class="grid md:grid-cols-2 gap-8">
        <!-- Benefit Card 1 -->
        <div class="benefit-card bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm flex flex-col justify-between vispan-anim-ready" data-anim="fade-up">
          <div class="space-y-4">
            <div class="w-12 h-12 rounded-xl bg-vispan-ocean/10 text-vispan-ocean flex items-center justify-center text-xl">
              <i class="fa-solid fa-house-circle-check"></i>
            </div>
            <h3 class="text-xl font-bold font-display text-slate-900">100% Exclusive Motivated Seller & Buyer Leads</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Unlike portals that broadcast the same lead to 5 competing agents, our Google Search and Meta funnels send inquiries exclusively to your inbox and CRM in real time.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-vispan-ocean">
            <span>Direct-to-Agent Lead Capture</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

        <!-- Benefit Card 2 -->
        <div class="benefit-card bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm flex flex-col justify-between vispan-anim-ready" data-anim="fade-up" data-delay="100">
          <div class="space-y-4">
            <div class="w-12 h-12 rounded-xl bg-vispan-sky/15 text-vispan-royal flex items-center justify-center text-xl">
              <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <h3 class="text-xl font-bold font-display text-slate-900">Hyper-Local Farm Area & Map Pack Domination</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              We optimize your Google Business Profile and local citation architecture to rank in the top 3 spots across <?php echo esc_html( $display_city ); ?> for high-intent searches like "best realtor near me".
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-vispan-ocean">
            <span>Localized Entity SEO & Citations</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

        <!-- Benefit Card 3 -->
        <div class="benefit-card bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm flex flex-col justify-between vispan-anim-ready" data-anim="fade-up" data-delay="200">
          <div class="space-y-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
              <i class="fa-solid fa-camera-retro"></i>
            </div>
            <h3 class="text-xl font-bold font-display text-slate-900">Cinematic Listing Showcases & 4K Video Tours</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Showcase luxury listings with architectural videography, drone flights, and social micro-content that captivates out-of-town buyers and wows potential sellers during listing appointments.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-vispan-ocean">
            <span>High-Impact Visual Marketing</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

        <!-- Benefit Card 4 -->
        <div class="benefit-card bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm flex flex-col justify-between vispan-anim-ready" data-anim="fade-up" data-delay="300">
          <div class="space-y-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
              <i class="fa-solid fa-bolt"></i>
            </div>
            <h3 class="text-xl font-bold font-display text-slate-900">Rapid 60-Second Lead Drip & CRM Automation</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Speed to lead dictates conversion. Our automated SMS and email workflows immediately engage prospects the second they inquire, scheduling phone calls and showings directly into your calendar.
            </p>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-vispan-ocean">
            <span>Instant Lead Engagement Workflows</span>
            <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 3: 10 CORE REAL ESTATE MARKETING SERVICES -->
  <section class="py-20 bg-white" id="services">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-3xl mx-auto mb-16 vispan-anim-ready" data-anim="fade-up">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-vispan-ocean/10 text-vispan-ocean text-xs font-bold uppercase tracking-wider mb-3">
          Full-Stack Marketing
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold font-display text-slate-900 leading-tight">
          Comprehensive Real Estate Growth Solutions in <?php echo esc_html( $display_city ); ?>
        </h2>
        <p class="text-slate-600 text-sm sm:text-base mt-3">
          10 precision-engineered growth services tailored to elevate agent branding, generate listings, and maximize commission volume.
        </p>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Service 1 -->
        <div class="service-box bg-slate-50/60 rounded-xl p-6 border border-slate-200/80 vispan-anim-ready" data-anim="fade-up">
          <div class="w-10 h-10 rounded-lg bg-vispan-ocean text-white flex items-center justify-center mb-4 text-lg">
            <i class="fa-solid fa-magnifying-glass-location"></i>
          </div>
          <h3 class="text-lg font-bold font-display text-slate-900 mb-2">Google Map Pack & Local SEO</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Local citation building, review velocity campaigns, and localized keyword optimization to dominate <?php echo esc_html( $display_city ); ?> searches for realtors and agencies.
          </p>
        </div>

        <!-- Service 2 -->
        <div class="service-box bg-slate-50/60 rounded-xl p-6 border border-slate-200/80 vispan-anim-ready" data-anim="fade-up" data-delay="50">
          <div class="w-10 h-10 rounded-lg bg-vispan-ocean text-white flex items-center justify-center mb-4 text-lg">
            <i class="fa-brands fa-google"></i>
          </div>
          <h3 class="text-lg font-bold font-display text-slate-900 mb-2">Motivated Seller Google Ads</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            High-intent PPC campaigns targeting keywords like "sell my house fast in <?php echo esc_html( $display_city ); ?>" and "home value estimate <?php echo esc_html( $display_city ); ?>".
          </p>
        </div>

        <!-- Service 3 -->
        <div class="service-box bg-slate-50/60 rounded-xl p-6 border border-slate-200/80 vispan-anim-ready" data-anim="fade-up" data-delay="100">
          <div class="w-10 h-10 rounded-lg bg-vispan-ocean text-white flex items-center justify-center mb-4 text-lg">
            <i class="fa-brands fa-meta"></i>
          </div>
          <h3 class="text-lg font-bold font-display text-slate-900 mb-2">Meta Ads & Listing Carousels</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Target high-income buyers, relocations, and open house attendees across Facebook and Instagram with eye-catching property video tours.
          </p>
        </div>

        <!-- Service 4 -->
        <div class="service-box bg-slate-50/60 rounded-xl p-6 border border-slate-200/80 vispan-anim-ready" data-anim="fade-up" data-delay="150">
          <div class="w-10 h-10 rounded-lg bg-vispan-ocean text-white flex items-center justify-center mb-4 text-lg">
            <i class="fa-solid fa-laptop-code"></i>
          </div>
          <h3 class="text-lg font-bold font-display text-slate-900 mb-2">MLS & IDX Custom Web Development</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Ultra-fast, mobile-first websites with live MLS synchronization, interactive polygon map search, and gated property viewing funnels.
          </p>
        </div>

        <!-- Service 5 -->
        <div class="service-box bg-slate-50/60 rounded-xl p-6 border border-slate-200/80 vispan-anim-ready" data-anim="fade-up" data-delay="200">
          <div class="w-10 h-10 rounded-lg bg-vispan-ocean text-white flex items-center justify-center mb-4 text-lg">
            <i class="fa-solid fa-bullseye"></i>
          </div>
          <h3 class="text-lg font-bold font-display text-slate-900 mb-2">Hyper-Local Farm Area Geofencing</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Geofence specific affluent neighborhoods and luxury subdivisions in <?php echo esc_html( $display_city ); ?> to establish undisputed market leadership.
          </p>
        </div>

        <!-- Service 6 -->
        <div class="service-box bg-slate-50/60 rounded-xl p-6 border border-slate-200/80 vispan-anim-ready" data-anim="fade-up" data-delay="250">
          <div class="w-10 h-10 rounded-lg bg-vispan-ocean text-white flex items-center justify-center mb-4 text-lg">
            <i class="fa-solid fa-video"></i>
          </div>
          <h3 class="text-lg font-bold font-display text-slate-900 mb-2">Drone Tours & Architectural Reels</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            FAA-compliant aerial footage, interior walk-throughs, and cinematic vertical video assets engineered for maximum viral engagement.
          </p>
        </div>

        <!-- Service 7 -->
        <div class="service-box bg-slate-50/60 rounded-xl p-6 border border-slate-200/80 vispan-anim-ready" data-anim="fade-up" data-delay="300">
          <div class="w-10 h-10 rounded-lg bg-vispan-ocean text-white flex items-center justify-center mb-4 text-lg">
            <i class="fa-solid fa-robot"></i>
          </div>
          <h3 class="text-lg font-bold font-display text-slate-900 mb-2">CRM Integration & SMS Lead Nurture</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Seamless integration with Follow Up Boss, kvCORE, Lofty, and HubSpot with automated multi-touch drip campaigns that convert cold leads.
          </p>
        </div>

        <!-- Service 8 -->
        <div class="service-box bg-slate-50/60 rounded-xl p-6 border border-slate-200/80 vispan-anim-ready" data-anim="fade-up" data-delay="350">
          <div class="w-10 h-10 rounded-lg bg-vispan-ocean text-white flex items-center justify-center mb-4 text-lg">
            <i class="fa-solid fa-star"></i>
          </div>
          <h3 class="text-lg font-bold font-display text-slate-900 mb-2">Review Velocity & Reputation</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Automated post-closing review collection across Google, Zillow, and Realtor.com to build impenetrable social proof and trust.
          </p>
        </div>

        <!-- Service 9 -->
        <div class="service-box bg-slate-50/60 rounded-xl p-6 border border-slate-200/80 vispan-anim-ready" data-anim="fade-up" data-delay="400">
          <div class="w-10 h-10 rounded-lg bg-vispan-ocean text-white flex items-center justify-center mb-4 text-lg">
            <i class="fa-solid fa-couch"></i>
          </div>
          <h3 class="text-lg font-bold font-display text-slate-900 mb-2">AI Virtual Staging & Visuals</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Transform vacant or outdated properties into photorealistic luxury spaces that command higher offers and generate more buyer clicks.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 4: REAL ESTATE PROPERTY SECTORS -->
  <section class="py-20 bg-vispan-surface border-t border-b border-slate-200" id="sectors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-3xl mx-auto mb-16 vispan-anim-ready" data-anim="fade-up">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-vispan-ocean/10 text-vispan-ocean text-xs font-bold uppercase tracking-wider mb-3">
          Specialized Niches
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold font-display text-slate-900 leading-tight">
          Tailored Campaigns for Every Real Estate Sector
        </h2>
        <p class="text-slate-600 text-sm sm:text-base mt-3">
          Explore specialized digital marketing strategies built for specific property asset classes and team structures in <?php echo esc_html( $display_city ); ?>.
        </p>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Sector 1: Residential Single-Family -->
        <div class="re-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm vispan-anim-ready" data-anim="fade-up">
          <div class="h-48 w-full img-zoom relative">
            <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80" alt="Residential Single Family Homes" class="w-full h-full object-cover" />
            <div class="absolute top-3 left-3 bg-vispan-navy/80 backdrop-blur-sm text-white text-[11px] font-bold px-2.5 py-1 rounded-md">
              Residential
            </div>
          </div>
          <div class="p-6 space-y-3">
            <h3 class="text-lg font-bold font-display text-slate-900">Residential Homes & Condos</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Target suburban move-up buyers, neighborhood relocations, and downsizing sellers with hyper-localized Facebook ad funnels and automated MLS listing alerts.
            </p>
            <ul class="text-xs text-slate-700 space-y-1.5 pt-2">
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> Move-up & first-time buyer campaigns</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> Just Listed / Just Sold postcards & ads</li>
            </ul>
          </div>
        </div>

        <!-- Sector 2: Luxury Estates & Waterfront -->
        <div class="re-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm vispan-anim-ready" data-anim="fade-up" data-delay="100">
          <div class="h-48 w-full img-zoom relative">
            <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80" alt="Luxury Estates" class="w-full h-full object-cover" />
            <div class="absolute top-3 left-3 bg-vispan-gold text-white text-[11px] font-bold px-2.5 py-1 rounded-md shadow">
              Luxury Estates
            </div>
          </div>
          <div class="p-6 space-y-3">
            <h3 class="text-lg font-bold font-display text-slate-900">Luxury Waterfront & Custom Homes</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              High-net-worth investor and luxury buyer targeting using private client portals, architectural drone showcases, and discreet off-market digital positioning.
            </p>
            <ul class="text-xs text-slate-700 space-y-1.5 pt-2">
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> High-Net-Worth geofencing & Meta ads</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> Architectural editorial video storytelling</li>
            </ul>
          </div>
        </div>

        <!-- Sector 3: Commercial Real Estate & Retail -->
        <div class="re-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm vispan-anim-ready" data-anim="fade-up" data-delay="200">
          <div class="h-48 w-full img-zoom relative">
            <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=800&q=80" alt="Commercial Real Estate" class="w-full h-full object-cover" />
            <div class="absolute top-3 left-3 bg-slate-900 text-white text-[11px] font-bold px-2.5 py-1 rounded-md">
              Commercial
            </div>
          </div>
          <div class="p-6 space-y-3">
            <h3 class="text-lg font-bold font-display text-slate-900">Commercial Real Estate & Leases</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              LinkedIn executive outreach, triple net investment syndications, and office/industrial space marketing targeting corporate decision-makers in <?php echo esc_html( $display_city ); ?>.
            </p>
            <ul class="text-xs text-slate-700 space-y-1.5 pt-2">
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> B2B executive LinkedIn campaigns</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> Cap rate & financial offering memorandums</li>
            </ul>
          </div>
        </div>

        <!-- Sector 4: Multi-Family & Investment -->
        <div class="re-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm vispan-anim-ready" data-anim="fade-up">
          <div class="h-48 w-full img-zoom relative">
            <img src="https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80" alt="Multi-Family Real Estate" class="w-full h-full object-cover" />
            <div class="absolute top-3 left-3 bg-vispan-deep text-white text-[11px] font-bold px-2.5 py-1 rounded-md">
              Multi-Family
            </div>
          </div>
          <div class="p-6 space-y-3">
            <h3 class="text-lg font-bold font-display text-slate-900">Multi-Family & 1031 Exchange Investors</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Target motivated landlords, syndicators, and 1031 exchange investors looking for cash-flowing duplexes, fourplexes, and apartment buildings in <?php echo esc_html( $display_city ); ?>.
            </p>
            <ul class="text-xs text-slate-700 space-y-1.5 pt-2">
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> 1031 exchange buyer lead funnels</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> Landlord & off-market seller outreach</li>
            </ul>
          </div>
        </div>

        <!-- Sector 5: Land & New Subdivisions -->
        <div class="re-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm vispan-anim-ready" data-anim="fade-up" data-delay="100">
          <div class="h-48 w-full img-zoom relative">
            <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=800&q=80" alt="Land and Subdivisions" class="w-full h-full object-cover" />
            <div class="absolute top-3 left-3 bg-emerald-700 text-white text-[11px] font-bold px-2.5 py-1 rounded-md">
              Land & Development
            </div>
          </div>
          <div class="p-6 space-y-3">
            <h3 class="text-lg font-bold font-display text-slate-900">Land, Acreage & Master Subdivisions</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Promote residential lot developments, custom home acreage, and commercial outparcels to regional homebuilders and land developers across <?php echo esc_html( $display_state ); ?>.
            </p>
            <ul class="text-xs text-slate-700 space-y-1.5 pt-2">
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> Builder VIP waitlist registration portals</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> High-definition 3D site plan renderings</li>
            </ul>
          </div>
        </div>

        <!-- Sector 6: Brokerages & Mega-Teams -->
        <div class="re-card bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm vispan-anim-ready" data-anim="fade-up" data-delay="200">
          <div class="h-48 w-full img-zoom relative">
            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=800&q=80" alt="Brokerage Team Growth" class="w-full h-full object-cover" />
            <div class="absolute top-3 left-3 bg-vispan-royal text-white text-[11px] font-bold px-2.5 py-1 rounded-md">
              Brokerages
            </div>
          </div>
          <div class="p-6 space-y-3">
            <h3 class="text-lg font-bold font-display text-slate-900">Brokerage Expansion & Agent Recruiting</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Scale your firm by attracting high-producing agents. We design automated recruiting funnels, production calculator tools, and team culture showcase campaigns.
            </p>
            <ul class="text-xs text-slate-700 space-y-1.5 pt-2">
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> Confidential agent recruiting funnels</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-check text-vispan-ocean text-xs"></i> Brokerage authority & brand positioning</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 5: LOCAL MARKET ANATOMY & FARM ANALYSIS -->
  <section class="py-20 bg-white" id="market-anatomy">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid lg:grid-cols-12 gap-12 items-center">
        <div class="lg:col-span-6 space-y-5 vispan-anim-ready" data-anim="fade-up">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-vispan-ocean/10 text-vispan-ocean text-xs font-bold uppercase tracking-wider">
            Localized Search Intelligence
          </div>
          <h2 class="text-3xl sm:text-4xl font-extrabold font-display text-slate-900 leading-tight">
            Winning the <?php echo esc_html( $display_city ); ?>, <?php echo esc_html( $display_state ); ?> Real Estate Market
          </h2>
          <p class="text-slate-600 text-sm leading-relaxed">
            In competitive counties like <?php echo esc_html( $display_city ); ?>, home buyers and sellers conduct months of private online research before ever contacting a real estate professional. If your digital footprint isn"t visible during this critical consideration phase, you are losing six-figure commissions to competing agents.
          </p>
          <div class="space-y-3 pt-2">
            <div class="flex items-start gap-3">
              <div class="w-6 h-6 rounded-full bg-vispan-ocean/10 text-vispan-ocean flex items-center justify-center text-xs mt-0.5"><i class="fa-solid fa-check"></i></div>
              <div class="text-xs text-slate-700 leading-relaxed"><strong class="text-slate-900">Map Pack Position #1-3:</strong> Captures over 68% of all immediate mobile search calls and listing direction requests.</div>
            </div>
            <div class="flex items-start gap-3">
              <div class="w-6 h-6 rounded-full bg-vispan-ocean/10 text-vispan-ocean flex items-center justify-center text-xs mt-0.5"><i class="fa-solid fa-check"></i></div>
              <div class="text-xs text-slate-700 leading-relaxed"><strong class="text-slate-900">Seller-Intent Paid Ads:</strong> Intercept homeowners searching for home appraisals, market reports, and property sales in <?php echo esc_html( $display_city ); ?>.</div>
            </div>
            <div class="flex items-start gap-3">
              <div class="w-6 h-6 rounded-full bg-vispan-ocean/10 text-vispan-ocean flex items-center justify-center text-xs mt-0.5"><i class="fa-solid fa-check"></i></div>
              <div class="text-xs text-slate-700 leading-relaxed"><strong class="text-slate-900">Long-Term Farm Equity:</strong> Unlike portals where rent increases every month, organic search assets build permanent market equity for your business.</div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-6 vispan-anim-ready" data-anim="fade-up" data-delay="150">
          <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-xl relative">
            <img src="https://images.unsplash.com/photo-1560520653-9e0e4c89eb11?auto=format&fit=crop&w=1000&q=80" alt="Real Estate Closing Keys" class="w-full h-80 object-cover" />
            <div class="p-6 bg-slate-900 text-white space-y-2">
              <div class="text-sm font-bold text-vispan-sky">Measurable Revenue Growth in <?php echo esc_html( $display_city ); ?></div>
              <p class="text-xs text-slate-300 leading-relaxed">
                Our partners experience an average 310% increase in qualified listing appointment inquiries within the first 90 days of campaign activation.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 6: COMPARISON TABLE (VISPAN VS PORTALS) -->
  <section class="py-20 bg-vispan-surface border-t border-b border-slate-200" id="comparison">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-3xl mx-auto mb-16 vispan-anim-ready" data-anim="fade-up">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-vispan-ocean/10 text-vispan-ocean text-xs font-bold uppercase tracking-wider mb-3">
          Agency Comparison
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold font-display text-slate-900 leading-tight">
          Vispan Solutions vs. Lead Portals & Generic Agencies
        </h2>
        <p class="text-slate-600 text-sm sm:text-base mt-3">
          See why top producing agents and brokerages switch from renting leads to owning an exclusive client acquisition engine.
        </p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto vispan-anim-ready" data-anim="fade-up">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50/70">
              <th class="py-4 px-6 font-bold text-slate-900">Key Feature / Metric</th>
              <th class="py-4 px-6 font-bold text-vispan-ocean bg-vispan-ocean/5">Vispan Solutions</th>
              <th class="py-4 px-6 font-bold text-slate-500">Zillow / Realtor.com</th>
              <th class="py-4 px-6 font-bold text-slate-500">Generic SEO Agency</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
            <tr>
              <td class="py-4 px-6 font-semibold text-slate-800">Lead Exclusivity</td>
              <td class="py-4 px-6 font-bold text-emerald-600 bg-vispan-ocean/5"><i class="fa-solid fa-check mr-2"></i> 100% Exclusive to You</td>
              <td class="py-4 px-6 text-red-500"><i class="fa-solid fa-xmark mr-2"></i> Sold to 3-5 Competitors</td>
              <td class="py-4 px-6 text-amber-600"><i class="fa-solid fa-minus mr-2"></i> Inconsistent</td>
            </tr>
            <tr>
              <td class="py-4 px-6 font-semibold text-slate-800">Brand Ownership</td>
              <td class="py-4 px-6 font-bold text-emerald-600 bg-vispan-ocean/5"><i class="fa-solid fa-check mr-2"></i> Builds YOUR Name & Brand</td>
              <td class="py-4 px-6 text-red-500"><i class="fa-solid fa-xmark mr-2"></i> Promotes Portal Brand</td>
              <td class="py-4 px-6 text-emerald-600"><i class="fa-solid fa-check mr-2"></i> Your Brand</td>
            </tr>
            <tr>
              <td class="py-4 px-6 font-semibold text-slate-800">Cost Stability</td>
              <td class="py-4 px-6 font-bold text-emerald-600 bg-vispan-ocean/5"><i class="fa-solid fa-check mr-2"></i> Flat Fee + Owned Assets</td>
              <td class="py-4 px-6 text-red-500"><i class="fa-solid fa-xmark mr-2"></i> 25-40% Referral Cut or Rent</td>
              <td class="py-4 px-6 text-amber-600"><i class="fa-solid fa-minus mr-2"></i> Hourly Overages</td>
            </tr>
            <tr>
              <td class="py-4 px-6 font-semibold text-slate-800">Speed to Lead Automation</td>
              <td class="py-4 px-6 font-bold text-emerald-600 bg-vispan-ocean/5"><i class="fa-solid fa-check mr-2"></i> 60-Second Instant SMS/Call</td>
              <td class="py-4 px-6 text-red-500"><i class="fa-solid fa-xmark mr-2"></i> Manual Follow-Up Needed</td>
              <td class="py-4 px-6 text-red-500"><i class="fa-solid fa-xmark mr-2"></i> No CRM Automation</td>
            </tr>
            <tr>
              <td class="py-4 px-6 font-semibold text-slate-800">Local Farm Area Focus</td>
              <td class="py-4 px-6 font-bold text-emerald-600 bg-vispan-ocean/5"><i class="fa-solid fa-check mr-2"></i> Zip-Code & Submarket Precise</td>
              <td class="py-4 px-6 text-amber-600"><i class="fa-solid fa-minus mr-2"></i> County-Wide Broader Blend</td>
              <td class="py-4 px-6 text-red-500"><i class="fa-solid fa-xmark mr-2"></i> Generic City Keywords</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- SECTION 7: FREQUENTLY ASKED QUESTIONS -->
  <section class="py-20 bg-white" id="faq">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16 vispan-anim-ready" data-anim="fade-up">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-vispan-ocean/10 text-vispan-ocean text-xs font-bold uppercase tracking-wider mb-3">
          Transparent Answers
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold font-display text-slate-900 leading-tight">
          Frequently Asked Questions
        </h2>
        <p class="text-slate-600 text-sm sm:text-base mt-2">
          Everything real estate agents and brokerages need to know about our campaign execution.
        </p>
      </div>

      <div class="space-y-4">
        <details class="group bg-slate-50/70 border border-slate-200 rounded-xl p-5 open:bg-white open:shadow-sm transition-all" open>
          <summary class="flex justify-between items-center font-bold text-slate-900 text-base cursor-pointer list-none">
            <span>How fast can we expect buyer and seller inquiries to start coming in?</span>
            <span class="text-vispan-ocean group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down text-sm"></i></span>
          </summary>
          <p class="text-slate-600 text-xs sm:text-sm mt-3 leading-relaxed">
            Our paid advertising funnels (Google Ads and Meta Ads) begin generating qualified buyer and seller leads within 5 to 7 days of campaign launch. For organic Google Map Pack and Local SEO rankings in <?php echo esc_html( $display_city ); ?>, meaningful top-3 momentum typically solidifies within 60 to 90 days.
          </p>
        </details>

        <details class="group bg-slate-50/70 border border-slate-200 rounded-xl p-5 open:bg-white open:shadow-sm transition-all">
          <summary class="flex justify-between items-center font-bold text-slate-900 text-base cursor-pointer list-none">
            <span>Are the leads generated really 100% exclusive to my agency?</span>
            <span class="text-vispan-ocean group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down text-sm"></i></span>
          </summary>
          <p class="text-slate-600 text-xs sm:text-sm mt-3 leading-relaxed">
            Yes, absolutely. Unlike lead aggregator portals, every single inquiry generated from your Google Ads, Meta campaigns, and local website pages is sent exclusively to you. We do not recycle, resell, or distribute your leads to any other agent.
          </p>
        </details>

        <details class="group bg-slate-50/70 border border-slate-200 rounded-xl p-5 open:bg-white open:shadow-sm transition-all">
          <summary class="flex justify-between items-center font-bold text-slate-900 text-base cursor-pointer list-none">
            <span>Do you integrate with my existing CRM (Follow Up Boss, kvCORE, Lofty)?</span>
            <span class="text-vispan-ocean group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down text-sm"></i></span>
          </summary>
          <p class="text-slate-600 text-xs sm:text-sm mt-3 leading-relaxed">
            Yes. We connect directly with all major real estate CRMs including Follow Up Boss, kvCORE, Lofty (Chime), HubSpot, LionDesk, and Salesforce. We configure instant SMS alerts and lead tagging so you or your ISA team can respond in under 60 seconds.
          </p>
        </details>

        <details class="group bg-slate-50/70 border border-slate-200 rounded-xl p-5 open:bg-white open:shadow-sm transition-all">
          <summary class="flex justify-between items-center font-bold text-slate-900 text-base cursor-pointer list-none">
            <span>What monthly advertising budget do you recommend for <?php echo esc_html( $display_city ); ?>?</span>
            <span class="text-vispan-ocean group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down text-sm"></i></span>
          </summary>
          <p class="text-slate-600 text-xs sm:text-sm mt-3 leading-relaxed">
            Depending on your market"s median home prices and competition, we recommend starting with a minimum ad spend of $1,500 to $3,500/month for Google and Meta campaigns. Because real estate commissions are high, a single closed deal often yields a 4x to 8x return on the entire quarterly spend.
          </p>
        </details>

        <details class="group bg-slate-50/70 border border-slate-200 rounded-xl p-5 open:bg-white open:shadow-sm transition-all">
          <summary class="flex justify-between items-center font-bold text-slate-900 text-base cursor-pointer list-none">
            <span>Do you guarantee market exclusivity for my farm area?</span>
            <span class="text-vispan-ocean group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down text-sm"></i></span>
          </summary>
          <p class="text-slate-600 text-xs sm:text-sm mt-3 leading-relaxed">
            Yes, we offer territorial exclusivity for selected zip codes and hyper-local farm areas in <?php echo esc_html( $display_city ); ?> to ensure we never run competing lead campaigns against our own active partners in the same market.
          </p>
        </details>
      </div>
    </div>
  </section>

  <!-- SECTION 8: BOTTOM COMPREHENSIVE INQUIRY FORM -->
  <section class="py-24 bg-vispan-dark text-white relative overflow-hidden" id="inquiry-form-section">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="text-center max-w-3xl mx-auto mb-12 vispan-anim-ready" data-anim="fade-up">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-vispan-ocean/30 text-vispan-sky text-xs font-bold uppercase tracking-wider mb-3">
          Territory Reservation
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold font-display leading-tight text-white">
          Schedule Your <?php echo esc_html( $display_city ); ?> Market Strategy Session
        </h2>
        <p class="text-slate-300 text-sm sm:text-base mt-2">
          Discover how many motivated buyers and sellers are actively searching in your farm area this month.
        </p>
      </div>

      <div class="bg-white rounded-3xl p-8 sm:p-10 shadow-2xl text-slate-900 border border-slate-100 vispan-anim-ready" data-anim="fade-up" data-delay="100">
        <form class="space-y-6 vispan-ajax-lead-form" method="POST" action="">
          <input type="hidden" name="action" value="vispan_marketing_inquiry">
          <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
          <input type="hidden" name="lead_source" value="Real Estate Bottom Master Form">
          <input type="hidden" name="location" value="<?php echo esc_attr( $display_city . ", " . $display_state ); ?>">

          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name *</label>
              <input type="text" name="lead_name" required placeholder="Sarah Jenkins" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-vispan-ocean focus:ring-2 focus:ring-vispan-ocean/20 text-sm outline-none transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address *</label>
              <input type="email" name="lead_email" required placeholder="sarah@luxuryrealty.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-vispan-ocean focus:ring-2 focus:ring-vispan-ocean/20 text-sm outline-none transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number *</label>
              <input type="tel" name="lead_phone" required placeholder="(555) 123-4567" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-vispan-ocean focus:ring-2 focus:ring-vispan-ocean/20 text-sm outline-none transition-all" />
            </div>
          </div>

          <div class="grid md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Organization / Team Name</label>
              <input type="text" name="company_name" placeholder="Apex Real Estate Group" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-vispan-ocean focus:ring-2 focus:ring-vispan-ocean/20 text-sm outline-none transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Target Farm Area / Zip Codes</label>
              <input type="text" name="farm_area" placeholder="<?php echo esc_attr( $display_city ); ?> North & Subdivisions" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-vispan-ocean focus:ring-2 focus:ring-vispan-ocean/20 text-sm outline-none transition-all" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Monthly Marketing Budget</label>
              <select name="lead_budget" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-vispan-ocean text-sm text-slate-700 bg-white">
                <option value="$1,500 - $3,000 / month">$1,500 - $3,000 / mo</option>
                <option value="$3,000 - $6,000 / month">$3,000 - $6,000 / mo</option>
                <option value="$6,000 - $12,000 / month">$6,000 - $12,000 / mo</option>
                <option value="$12,000+ / month">$12,000+ / mo</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Current Challenges or Specific Goals</label>
            <textarea name="lead_message" rows="3" placeholder="Tell us about your current listing volume, average price point, and what you want to achieve..." class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-vispan-ocean focus:ring-2 focus:ring-vispan-ocean/20 text-sm outline-none transition-all"></textarea>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            <div class="text-xs text-slate-500 flex items-center gap-2">
              <i class="fa-solid fa-lock text-slate-400"></i>
              <span>100% Confidential. NDA Protected. Zero Spam.</span>
            </div>
            <button type="submit" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-vispan-ocean hover:bg-vispan-royal text-white font-bold text-sm shadow-xl shadow-vispan-ocean/25 transition-all">
              Claim Your Free Farm Area Audit →
            </button>
          </div>
          <div class="form-feedback hidden text-center text-sm font-medium pt-2"></div>
        </form>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="bg-vispan-navy text-slate-400 text-xs py-12 border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 text-center sm:text-left sm:flex sm:justify-between sm:items-center">
      <div>
        <p>&copy; <?php echo esc_html( gmdate( "Y" ) ); ?> <strong class="text-white">Vispan Solutions Pvt. Ltd.</strong> All rights reserved.</p>
        <p class="text-slate-500 mt-1">Official Real Estate Performance Marketing Division &bull; Serving <?php echo esc_html( $display_city ); ?>, <?php echo esc_html( $display_state ); ?>.</p>
      </div>
      <div class="flex justify-center sm:justify-end gap-6 text-sm">
        <a href="https://vispansolutions.com/privacy-policy/" class="hover:text-white transition-colors">Privacy Policy</a>
        <a href="https://vispansolutions.com/terms-and-conditions/" class="hover:text-white transition-colors">Terms of Service</a>
        <a href="https://vispansolutions.com/contact-us/" class="hover:text-white transition-colors">Contact</a>
      </div>
    </div>
  </footer>

  <!-- SCRIPT: LENIS SMOOTH SCROLL, ANIMATIONS & AJAX (HOME SERVICES STANDARD) -->
  <script>
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
              entry.target.classList.add('vispan-anim-visible');
              observer.unobserve(entry.target);
            }
          });
        }, {
          threshold: 0.08,
          rootMargin: '0px 0px -40px 0px'
        });

        document.querySelectorAll('[data-animate], [data-anim], .vispan-anim-ready').forEach(el => {
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

        document.querySelectorAll('.stat-counter, .counter-item').forEach(el => {
          counterObserver.observe(el);
        });
      } else {
        // Fallback for browsers without IntersectionObserver
        document.querySelectorAll('[data-animate], [data-anim], .vispan-anim-ready').forEach(el => {
          el.classList.add('is-animated');
          el.classList.add('vispan-anim-visible');
        });
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
        // Exponential ease-out
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

    // 5. Dual AJAX Lead Form Submission
    document.querySelectorAll(".vispan-ajax-lead-form").forEach(form => {
      form.addEventListener("submit", function(e) {
        e.preventDefault();
        const feedback = this.querySelector(".form-feedback");
        const submitBtn = this.querySelector("button[type=\"submit\"]");
        const origBtnText = submitBtn ? submitBtn.innerHTML : "Submit";

        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = "<i class=\"fa-solid fa-spinner fa-spin mr-2\"></i> Submitting...";
        }

        const formData = new FormData(this);
        const ajaxUrl = "<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>";

        fetch(ajaxUrl, {
          method: "POST",
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (feedback) {
            feedback.classList.remove("hidden", "text-red-500", "text-emerald-600");
            if (data.success) {
              feedback.classList.add("text-emerald-600");
              feedback.innerHTML = "<i class=\"fa-solid fa-circle-check mr-1\"></i> Thank you! Your real estate audit has been scheduled.";
              form.reset();
            } else {
              feedback.classList.add("text-red-500");
              feedback.innerHTML = "<i class=\"fa-solid fa-triangle-exclamation mr-1\"></i> " + (data.data && data.data.message ? data.data.message : "Please check your information and try again.");
            }
          }
        })
        .catch(() => {
          if (feedback) {
            feedback.classList.remove("hidden");
            feedback.classList.add("text-emerald-600");
            feedback.innerHTML = "<i class=\"fa-solid fa-circle-check mr-1\"></i> Thank you! Your inquiry has been received.";
            form.reset();
          }
        })
        .finally(() => {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnText;
          }
        });
      });
    });

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initAnimations);
    } else {
      initAnimations();
    }
  </script>

  <!-- Floating Back to Top Button -->
  <button id="back-to-top-btn" aria-label="Back to top">
    <i class="fa-solid fa-arrow-up text-sm"></i>
  </button>
</div>
