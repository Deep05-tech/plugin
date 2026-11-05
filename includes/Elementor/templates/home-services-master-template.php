<?php
/**
 * Official Vispan Solutions Master Template for Home Services SEO Landing Pages.
 *
 * Fully responsive, high-converting Tailwind layout featuring 10 official Vispan
 * agency services, 6 residential trade pillars, verified reviews, and dual AJAX lead forms.
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
 * @var string $services_html
 * @var string $trades_html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="vispan-home-services-page font-sans text-slate-900 bg-white selection:bg-vispan-ocean selection:text-white">
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

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Inter"', 'sans-serif'],
            display: ['"Plus Jakarta Sans"', 'sans-serif'],
          },
          colors: {
            vispan: {
              dark: '#0A1120',
              navy: '#0F172A',
              ocean: '#0B6599',
              deep: '#1E3A8A',
              royal: '#1D4ED8',
              blue: '#0B6599',
              bright: '#2563EB',
              sky: '#38BDF8',
              light: '#60A5FA',
              ice: '#EFF6FF',
              surface: '#F7F9FC',
              border: '#DCE6F2'
            }
          }
        }
      }
    }
  </script>

  <style>
    body {
      font-family: 'Inter', sans-serif;
      color: #0F172A;
      background-color: #FFFFFF;
      overflow-x: hidden;
    }
    h1, h2, h3, h4, .font-display {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .vispan-hero-bg {
      background: linear-gradient(135deg, #0A1120 0%, #0F172A 45%, #0B6599 100%);
    }
    .vispan-blue-btn {
      background: linear-gradient(135deg, #0B6599 0%, #1D4ED8 100%);
    }
    .vispan-card-hover {
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .vispan-card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px -14px rgba(11, 101, 153, 0.16);
      border-color: #0B6599;
    }
    .official-service-box {
      transition: all 0.25s ease;
    }
    .official-service-box:hover {
      transform: translateY(-4px);
      box-shadow: 0 18px 36px -12px rgba(11, 101, 153, 0.15);
      border-color: #0B6599;
      background-color: #FFFFFF;
    }
    .official-service-box:hover .service-svg {
      stroke: #0B6599;
      transform: scale(1.06);
    }
    .service-svg {
      transition: all 0.25s ease;
    }
  
    /* Theme Isolation & Visibility Overrides */
    .vispan-home-services-page {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      color: #0F172A;
      background-color: #FFFFFF;
      overflow-x: hidden;
      width: 100%;
    }
    .vispan-home-services-page h1,
    .vispan-home-services-page h2,
    .vispan-home-services-page h3,
    .vispan-home-services-page h4,
    .vispan-home-services-page .font-display {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
    }
    .footer__area-8, .jfooter-wrapper {
      display: none !important;
    }
    #preloader, .preloader, .arolax-preloader {
      display: none !important;
    }
  </style>

<!-- ===================================================================== -->
  <!-- SECTION 1: CLEAN, HIGH-IMPACT HERO + VERIFIED HERO IMAGE SHOWCASE     -->
  <!-- ===================================================================== -->
  <section id="top" class="vispan-hero-bg text-white py-16 lg:py-20 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10">
      <div class="grid lg:grid-cols-12 gap-10 lg:gap-12 items-center">
        
        <!-- Left Column: Official Vispan Taglines, Verified Credentials & CTAs -->
        <div class="lg:col-span-7 space-y-6">
          <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 border border-white/20 text-sky-200 text-xs font-semibold">
            <i class="fa-solid fa-award text-sky-400"></i>
            <span>Award-Winning Digital Marketing Agency Since 2012 · <span class="county-name text-white font-bold"><?php echo esc_html( $city ); ?></span><?php echo ! empty( $state_display ) ? ', <span class="county-state text-white font-bold">' . esc_html( $state_display ) . '</span>' : ''; ?></span>
          </div>

          <h1 class="font-display text-4xl sm:text-5xl lg:text-[48px] font-extrabold tracking-tight leading-[1.14]">
            <?php if ( ! empty( $is_ai_hero_title ) ) : ?>
              <?php echo esc_html( $hero_title ); ?>
            <?php else : ?>
              Your Digital Marketing Partner in <span class="text-sky-300"><span class="county-name"><?php echo esc_html( $city ); ?></span><?php echo ! empty( $state_display ) ? ', <span class="county-state">' . esc_html( $state_display ) . '</span>' : ''; ?></span>—Turning Clicks into Real Conversions
            <?php endif; ?>
          </h1>

          <p class="text-slate-200 text-lg leading-relaxed max-w-2xl font-normal">
            <?php if ( ! empty( $is_ai_hero_desc ) ) : ?>
              <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $hero_description ) : esc_html( $hero_description ); ?>
            <?php else : ?>
              Why follow trends when you can set them? Blend bold ideas with <strong>Vispan Solutions</strong> and watch your home services brand take the spotlight across <span class="county-cities font-semibold text-white"><?php echo esc_html( $cities_str ); ?></span>—from <strong>Plumbing &amp; Hydro Jetting</strong> and <strong>HVAC</strong> to <strong>Home Decor, Blinds, Shades &amp; Shutters</strong>.
            <?php endif; ?>
          </p>

          <!-- Verified Vispan Credentials Pills -->
          <div class="flex flex-wrap gap-2.5 pt-1">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-sky-500/20 border border-sky-300/30 text-sky-100 text-xs font-bold">
              <i class="fa-solid fa-circle-check text-sky-300"></i> IT-ITeS-Excellence Awards 2023 &amp; 2024 Winner
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-sky-500/20 border border-sky-300/30 text-sky-100 text-xs font-bold">
              <i class="fa-solid fa-circle-check text-sky-300"></i> Certified Google Partner
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-sky-500/20 border border-sky-300/30 text-sky-100 text-xs font-bold">
              <i class="fa-solid fa-circle-check text-sky-300"></i> 3X Site of the Day · 4X Site of the Week
            </span>
          </div>

          <!-- Clean Dual CTAs -->
          <div class="flex flex-wrap items-center gap-4 pt-2">
            <a href="#mid-contact" class="inline-flex items-center gap-2.5 px-7 py-4 rounded-xl bg-white text-vispan-ocean font-extrabold text-base shadow-xl hover:bg-sky-50 transition">
              <span><?php echo esc_html( ! empty( $hero_cta ) ? $hero_cta : 'Get A Free Consultation' ); ?></span>
              <i class="fa-solid fa-arrow-right"></i>
            </a>
            <a href="#our-services" class="inline-flex items-center gap-2.5 px-6 py-4 rounded-xl bg-white/10 hover:bg-white/15 border border-white/25 text-white font-semibold text-base transition">
              <span>Explore What We Do</span>
            </a>
          </div>

          <!-- Verified Stats Row (Dynamic AI Metro Stats or Defaults: 2x2 Layout) -->
          <div class="grid grid-cols-2 gap-5 pt-5 border-t border-white/15 max-w-lg">
            <?php if ( ! empty( $ai_metro_stats ) && is_array( $ai_metro_stats ) ) : ?>
              <?php foreach ( array_slice( $ai_metro_stats, 0, 4 ) as $st ) : ?>
                <div>
                  <div class="font-display text-2xl sm:text-3xl font-extrabold text-white"><?php echo esc_html( $st['value'] ?? '' ); ?></div>
                  <div class="text-xs text-sky-200 mt-0.5"><?php echo esc_html( $st['label'] ?? '' ); ?></div>
                </div>
              <?php endforeach; ?>
            <?php else : ?>
              <div>
                <div class="font-display text-2xl sm:text-3xl font-extrabold text-white">Since 2012</div>
                <div class="text-xs text-sky-200 mt-0.5">Empowering Brands</div>
              </div>
              <div>
                <div class="font-display text-2xl sm:text-3xl font-extrabold text-white">150+</div>
                <div class="text-xs text-sky-200 mt-0.5">Projects · 99% Success</div>
              </div>
              <div>
                <div class="font-display text-2xl sm:text-3xl font-extrabold text-white">4.8 ★</div>
                <div class="text-xs text-sky-200 mt-0.5">140+ Verified Reviews</div>
              </div>
              <div>
                <div class="font-display text-2xl sm:text-3xl font-extrabold text-white">2023 &amp; 24</div>
                <div class="text-xs text-sky-200 mt-0.5">Best SEO Award</div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right Column: FRAMED HERO IMAGE SHOWCASE WITH VERIFIED VISPAN CREDENTIAL BADGES -->
        <div class="lg:col-span-5 relative">
          <!-- Main Framed Hero Image -->
          <div class="rounded-3xl border-2 border-blue-400/30 shadow-2xl overflow-hidden bg-slate-900 relative">
            <img
              src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=1000&q=85"
              alt="Professional Home Service Technician with Homeowner"
              onerror="this.src='https://placehold.co/900x700/0B6599/FFFFFF?text=Vispan+Solutions+Home+Services'"
              class="w-full h-[430px] sm:h-[490px] object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-vispan-dark/80 via-transparent to-vispan-dark/25"></div>
          </div>

          <!-- Top-Right Floating Verified Award Badge -->
          <div class="bg-vispan-dark/95 backdrop-blur-md text-white p-4 rounded-2xl shadow-2xl border border-sky-400/40 absolute -top-4 -right-2 sm:right-4 max-w-[275px]">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl vispan-blue-btn flex items-center justify-center text-white shrink-0 shadow-md">
                <i class="fa-solid fa-trophy text-sm"></i>
              </div>
              <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-sky-300">Verified Excellence</div>
                <div class="font-display font-bold text-xs text-white leading-snug mt-0.5">
                  IT-ITeS-Excellence Awards 2023 &amp; 2024 Winner — Best SEO / Digital Marketer
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom-Left Floating White Card: Verified Google Rating -->
          <div class="bg-white text-slate-900 p-4 rounded-2xl shadow-2xl border border-slate-200 absolute -bottom-5 -left-2 sm:left-4 max-w-[285px]">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-xl bg-vispan-ice text-vispan-ocean flex items-center justify-center font-display font-extrabold text-base shrink-0">
                4.8★
              </div>
              <div>
                <div class="flex items-center gap-1 text-amber-400 text-xs">
                  <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <div class="font-display font-extrabold text-xs text-vispan-dark mt-0.5">
                  4.8 ★ Rating (140+ Verified Client Reviews)
                </div>
                <div class="text-[11px] text-slate-500 font-semibold">
                  Empowering Brands Since 2012
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom-Right Floating Verified Project Success Badge -->
          <div class="hidden sm:flex items-center gap-2.5 bg-vispan-ocean text-white px-4 py-3 rounded-2xl shadow-xl border border-sky-300/30 absolute bottom-6 right-4">
            <i class="fa-solid fa-circle-check text-sky-200 text-lg"></i>
            <div>
              <div class="font-display font-extrabold text-sm leading-none">150+ Projects</div>
              <div class="text-[11px] text-sky-100 font-semibold mt-0.5">99% Success Rate</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- VERIFIED GOOGLE PARTNER & PLATFORM TECH STACK STRIP -->
  <div class="bg-vispan-surface border-b border-slate-200 py-5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-wrap items-center justify-between gap-4 text-xs">
      <span class="font-bold uppercase tracking-wider text-slate-500">
        Verified Tools &amp; Certifications Powering Vispan Solutions:
      </span>
      <div class="flex flex-wrap items-center gap-5 sm:gap-7 font-display font-bold text-slate-700">
        <span class="flex items-center gap-1.5"><i class="fa-brands fa-google text-vispan-ocean"></i> Google Partner (Search, Display, Video, Shopping, Analytics)</span>
        <span class="flex items-center gap-1.5"><i class="fa-brands fa-wordpress text-vispan-ocean"></i> WordPress</span>
        <span class="flex items-center gap-1.5"><i class="fa-brands fa-meta text-vispan-ocean"></i> Meta Ads</span>
        <span class="flex items-center gap-1.5"><i class="fa-solid fa-chart-line text-vispan-ocean"></i> Ahrefs &amp; SEMrush</span>
        <span class="flex items-center gap-1.5"><i class="fa-brands fa-hubspot text-vispan-ocean"></i> HubSpot &amp; Mailchimp</span>
      </div>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- SECTION 2: WHY YOU NEED DIGITAL MARKETING FOR HOME SERVICES           -->
  <!-- (100% VERIFIED VISPAN COPY + INCLUDES REQUESTED "CONTACT US" BUTTON)  -->
  <!-- ===================================================================== -->
  <section id="why-marketing" class="py-20 lg:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      
      <!-- Top 2-Column Visual Story -->
      <div class="grid lg:grid-cols-12 gap-12 items-center mb-16">
        <!-- Left Photo with Verified Vispan Award Overlay -->
        <div class="lg:col-span-6 relative">
          <div class="rounded-3xl overflow-hidden shadow-xl border border-slate-200">
            <img src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&w=900&q=80" alt="Home Service Technician on the Job" onerror="this.src='https://placehold.co/800x550/0B6599/FFFFFF?text=Vispan+Solutions'" class="w-full h-[360px] sm:h-[410px] object-cover" />
          </div>
          <div class="bg-vispan-ocean text-white px-6 py-4 rounded-2xl shadow-xl absolute -bottom-5 right-6 flex items-center gap-3.5">
            <span class="font-display text-3xl font-extrabold">99%</span>
            <span class="text-xs font-medium leading-tight text-sky-100">Project Success Rate Across<br><strong class="text-white">150+ Completed Projects</strong></span>
          </div>
        </div>

        <!-- Right Scannable Explanation Using Official Vispan Solutions Copy -->
        <div class="lg:col-span-6 space-y-5">
          <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-vispan-ice text-vispan-ocean text-xs font-extrabold uppercase tracking-wider">
            Why Digital Marketing Matters
          </span>

          <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-vispan-dark tracking-tight leading-tight">
            <?php if ( ! empty( $is_ai_intro_heading ) ) : ?>
              <?php echo esc_html( $intro_heading ); ?>
            <?php else : ?>
              Why Home Service Companies in <span class="county-name text-vispan-ocean"><?php echo esc_html( $city ); ?></span> Need Digital Marketing
            <?php endif; ?>
          </h2>

          <div class="text-slate-600 text-base sm:text-lg leading-relaxed space-y-4">
            <?php if ( ! empty( $is_ai_intro_body ) ) : ?>
              <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $intro_body ) : esc_html( $intro_body ); ?>
            <?php else : ?>
              <p>
                Whether a homeowner needs urgent <strong>plumbing and hydro jetting</strong>, HVAC repair, roofing, or custom <strong>home decor, blinds, shades, and shutters</strong> in <span class="county-cities font-semibold text-slate-900"><?php echo esc_html( $cities_str ); ?></span>, they search online first to find a reputable local company.
              </p>
              <p>
                Since 2012, <strong>Vispan Solutions</strong> has empowered businesses globally with a 360° digital marketing solution—combining expert SEO, targeted Google Ads, high-performing Web Development, Branding, Reputation Management, and Video/CGI production to turn searches into loyal customers.
              </p>
            <?php endif; ?>
          </div>

          <!-- Clean 2-Point Highlight Box (Verified Vispan Differentiators) -->
          <div class="grid sm:grid-cols-2 gap-4 pt-2">
            <div class="p-4 rounded-2xl bg-vispan-surface border border-slate-200 flex items-start gap-3">
              <i class="fa-solid fa-trophy text-vispan-ocean text-lg mt-0.5"></i>
              <div>
                <h3 class="font-display font-bold text-sm text-vispan-dark">Award-Winning SEO &amp; Ads</h3>
                <p class="text-xs text-slate-600 mt-1">Winner of the IT-ITeS-Excellence Awards 2023 &amp; 2024 for Best SEO and Digital Marketer.</p>
              </div>
            </div>
            <div class="p-4 rounded-2xl bg-vispan-surface border border-slate-200 flex items-start gap-3">
              <i class="fa-solid fa-layer-group text-vispan-ocean text-lg mt-0.5"></i>
              <div>
                <h3 class="font-display font-bold text-sm text-vispan-dark">360° In-House Execution</h3>
                <p class="text-xs text-slate-600 mt-1">From SEO and Google Ads to Web Development, Video Production, VFX, and CGI under one roof.</p>
              </div>
            </div>
          </div>

          <!-- Section 2 Contact Us Button (Kept as Requested) -->
          <div class="pt-3">
            <a href="#contact" class="inline-flex items-center gap-2.5 px-7 py-3.5 rounded-xl vispan-blue-btn text-white font-bold text-sm shadow-md shadow-sky-900/15 hover:opacity-95 transition">
              <span>Contact Us</span>
              <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- 2x2 Photo Benefit Grid (2 Boxes Per Line on Tablet & Desktop) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-7">
        <?php if ( ! empty( $ai_benefits ) && is_array( $ai_benefits ) ) : ?>
          <?php
          $benefit_imgs = [
            'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=600&q=80',
          ];
          foreach ( array_slice( $ai_benefits, 0, 4 ) as $b_idx => $b ) :
            $b_img = $benefit_imgs[ $b_idx % count( $benefit_imgs ) ];
          ?>
            <div class="vispan-card-hover bg-vispan-surface rounded-2xl overflow-hidden border border-slate-200 flex flex-col">
              <img src="<?php echo esc_url( $b_img ); ?>" alt="<?php echo esc_attr( $b['title'] ?? '' ); ?>" onerror="this.src='https://placehold.co/600x360/0B6599/FFFFFF?text=Vispan+Solutions'" class="w-full h-40 object-cover" />
              <div class="p-6 flex-1">
                <h3 class="font-display font-bold text-lg text-vispan-ocean"><?php echo esc_html( $b['title'] ?? '' ); ?></h3>
                <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                  <?php echo esc_html( $b['description'] ?? '' ); ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else : ?>
          <!-- Card 1 -->
          <div class="vispan-card-hover bg-vispan-surface rounded-2xl overflow-hidden border border-slate-200 flex flex-col">
            <img src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80" alt="Top Search Engine Rankings" onerror="this.src='https://placehold.co/600x360/0B6599/FFFFFF?text=Organic+Visibility'" class="w-full h-40 object-cover" />
            <div class="p-6 flex-1">
              <h3 class="font-display font-bold text-lg text-vispan-ocean">Top Search Visibility</h3>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Enhance your website's visibility and drive organic traffic with expert SEO strategies tailored to achieve top search engine rankings.
              </p>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="vispan-card-hover bg-vispan-surface rounded-2xl overflow-hidden border border-slate-200 flex flex-col">
            <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80" alt="Targeted Campaigns & Higher ROI" onerror="this.src='https://placehold.co/600x360/0B6599/FFFFFF?text=Higher+ROI'" class="w-full h-40 object-cover" />
            <div class="p-6 flex-1">
              <h3 class="font-display font-bold text-lg text-vispan-ocean">Targeted Campaigns &amp; ROI</h3>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Maximize your online presence with expert Google Ads management, ensuring targeted campaigns, optimized performance, and higher ROI.
              </p>
            </div>
          </div>

          <!-- Card 3 -->
          <div class="vispan-card-hover bg-vispan-surface rounded-2xl overflow-hidden border border-slate-200 flex flex-col">
            <img src="https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?auto=format&fit=crop&w=600&q=80" alt="High-Performing Websites" onerror="this.src='https://placehold.co/600x360/0B6599/FFFFFF?text=Web+Performance'" class="w-full h-40 object-cover" />
            <div class="p-6 flex-1">
              <h3 class="font-display font-bold text-lg text-vispan-ocean">Websites That Convert</h3>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                We build responsive, user-friendly, lightning-fast websites tailored to your business needs that impress visitors and convert clicks into leads.
              </p>
            </div>
          </div>

          <!-- Card 4 -->
          <div class="vispan-card-hover bg-vispan-surface rounded-2xl overflow-hidden border border-slate-200 flex flex-col">
            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=600&q=80" alt="Strong Brand & Reputation" onerror="this.src='https://placehold.co/600x360/0B6599/FFFFFF?text=Brand+Trust'" class="w-full h-40 object-cover" />
            <div class="p-6 flex-1">
              <h3 class="font-display font-bold text-lg text-vispan-ocean">Trust &amp; Brand Credibility</h3>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Protect and improve your brand's online image through strategic monitoring, review management, and creative branding &amp; video services.
              </p>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 3: WHAT WE DO — OFFICIAL VISPAN SOLUTIONS SERVICES (IN BOXES) -->
  <!-- (2 BOXES PER ROW, ZERO NUMBERS, 100% OFFICIAL VISPAN WEBSITE COPY)    -->
  <!-- ===================================================================== -->
  <section id="our-services" class="py-20 lg:py-24 bg-[#F7F9FC] border-y border-slate-200/90">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      
      <!-- Section Heading + Official Vispan Overview Content -->
      <div class="max-w-4xl mx-auto text-center mb-14">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-slate-200 text-vispan-ocean text-xs font-extrabold uppercase tracking-wider mb-3 shadow-sm">
          What We Do · We Span Your Digital Presence
        </span>
        <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-vispan-dark tracking-tight">
          <?php echo ! empty( $service_overview_h ) ? esc_html( $service_overview_h ) : '360° Digital Marketing Solution for Your Business'; ?>
        </h2>
        <p class="text-slate-700 text-base sm:text-lg mt-4 leading-relaxed">
          <?php if ( ! empty( $service_overview_b ) ) : ?>
            <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $service_overview_b ) : esc_html( $service_overview_b ); ?>
          <?php else : ?>
            From <strong>Digital Marketing</strong>, <strong>Google Ads</strong>, <strong>Branding Services</strong>, and <strong>SEO</strong> to <strong>Web Development</strong>, <strong>Social Media Management</strong>, <strong>Online Reputation Management</strong>, <strong>Video Production</strong>, <strong>VFX</strong>, and <strong>CGI Services</strong>—we deliver complete in-house solutions to grow your business in <span class="county-name font-semibold text-vispan-dark"><?php echo esc_html( $city ); ?></span>.
          <?php endif; ?>
        </p>
      </div>

      <!-- 2-Column Boxed Grid (2 Boxes Per Line, Official Vispan Services) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-7" id="official-services-grid"><?php echo $services_html; ?></div>

    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 4: ALL HOME SERVICES WE MARKET (ZERO NUMBERS, NO TABS)        -->
  <!-- ===================================================================== -->
  <section id="home-services-list" class="py-20 lg:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      
      <!-- Section Heading -->
      <div class="max-w-3xl mx-auto text-center mb-14">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-vispan-ice text-vispan-ocean text-xs font-extrabold uppercase tracking-wider mb-3">
          Residential Home Services Directory
        </span>
        <h2 class="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold text-vispan-dark tracking-tight">
          Home Services We Market in <span class="county-name text-vispan-ocean"><?php echo esc_html( $city ); ?></span>
        </h2>
        <p class="text-slate-600 text-base sm:text-lg mt-3">
          If your business offers any—or all—of these residential home services, including <strong>Plumbing &amp; Hydro Jetting</strong> and <strong>Home Decor, Blinds, Shades &amp; Shutters</strong>, you can partner with <strong>Vispan Solutions</strong> because we provide complete digital marketing for every service below.
        </p>
      </div>

      <!-- Dynamic Categories + Home Services Grid (Zero Numbers, No Buttons Inside) -->
      <div id="trades-container" class="space-y-14"><?php echo $trades_html; ?></div>

      <!-- ================================================================= -->
      <!-- FORM #2: DEDICATED MID-PAGE CONTACT SECTION AFTER HOME SERVICES   -->
      <!-- ================================================================= -->
      <div id="mid-contact" class="mt-16 vispan-hero-bg rounded-3xl p-8 sm:p-12 text-white shadow-2xl relative overflow-hidden">
        <div class="grid lg:grid-cols-12 gap-10 items-center relative z-10">
          
          <!-- Left Copy -->
          <div class="lg:col-span-5 space-y-5">
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/15 border border-white/25 text-sky-200 text-xs font-extrabold uppercase tracking-wider">
              One Agency For All Your Home Services
            </span>
            <h3 class="font-display text-3xl sm:text-4xl font-extrabold leading-tight">
              Provide Any of These Home Services? Partner With Vispan Solutions
            </h3>
            <p class="text-slate-200 text-sm sm:text-base leading-relaxed">
              Because we provide SEO, Google Ads, Web Development, Branding, Social Media, Reputation Management, Video, VFX, and CGI for all these home services, you can scale your entire business in <span class="county-name font-bold text-white"><?php echo esc_html( $city ); ?></span> with one trusted partner.
            </p>
            <div class="grid grid-cols-2 gap-3 pt-1 text-xs font-bold text-sky-100">
              <div class="bg-white/10 border border-white/15 rounded-xl p-3 flex items-center gap-2">
                <i class="fa-solid fa-check text-sky-300"></i> Empowering Brands Since 2012
              </div>
              <div class="bg-white/10 border border-white/15 rounded-xl p-3 flex items-center gap-2">
                <i class="fa-solid fa-check text-sky-300"></i> 150+ Projects · 99% Success
              </div>
              <div class="bg-white/10 border border-white/15 rounded-xl p-3 flex items-center gap-2">
                <i class="fa-solid fa-check text-sky-300"></i> 4.8★ (140+ Client Reviews)
              </div>
              <div class="bg-white/10 border border-white/15 rounded-xl p-3 flex items-center gap-2">
                <i class="fa-solid fa-check text-sky-300"></i> Certified Google Partner
              </div>
            </div>
          </div>

          <!-- Right Form #2 -->
          <div class="lg:col-span-7">
            <form onsubmit="submitFormToast(event, 'midFormToast')" class="bg-white text-slate-900 rounded-2xl p-6 sm:p-8 shadow-xl space-y-4">
              <input type="text" name="inquiry_hp_check" value="" style="display:none !important;" tabindex="-1" autocomplete="off" />
              <input type="hidden" name="action" value="seo_city_submit_inquiry" />
              <input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
              <input type="hidden" name="form_layout" value="mid_strategy" />
              <div class="pb-2 border-b border-slate-200">
                <h4 class="font-display font-extrabold text-xl text-vispan-dark">
                  Book a <span class="text-vispan-ocean">Free 1-on-1 Strategy Session</span>
                </h4>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                  Speak directly with our team to align our digital marketing services with your home service offerings.
                </p>
              </div>

              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Full Name*</label>
                  <input type="text" name="full_name" required placeholder="Your Full Name" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm bg-vispan-surface focus:bg-white focus:border-vispan-ocean outline-none" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number*</label>
                  <input type="tel" name="phone" required placeholder="+1 (720) 203-2600" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm bg-vispan-surface focus:bg-white focus:border-vispan-ocean outline-none" />
                </div>
              </div>

              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Work Email*</label>
                  <input type="email" required placeholder="you@company.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm bg-vispan-surface focus:bg-white focus:border-vispan-ocean outline-none" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Your Home Service Trade*</label>
                  <select class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm bg-vispan-surface text-slate-700 focus:bg-white focus:border-vispan-ocean outline-none">
                    <option>Plumbing &amp; Hydro Jetting Services</option>
                    <option>Home Decor, Blinds, Shades &amp; Shutters</option>
                    <option>HVAC / Electrical Services</option>
                    <option>Roofing, Siding, Gutters &amp; Exterior</option>
                    <option>Handyman, Painting, Flooring &amp; Carpentry</option>
                    <option>Lawn Care, Pest Control, Pool &amp; Snow</option>
                    <option>Housekeeping, Junk Removal &amp; Window Cleaning</option>
                    <option>Home Security, Locksmith &amp; Solar Power</option>
                    <option>Multiple / All Home Services</option>
                  </select>
                </div>
              </div>

              <button type="submit" class="w-full py-3.5 rounded-xl vispan-blue-btn text-white font-extrabold text-sm shadow-lg shadow-sky-900/20 hover:opacity-95 transition">
                Schedule My Free 1-on-1 Session →
              </button>

              <div id="midFormToast" class="hidden p-3 rounded-xl bg-vispan-ice border border-vispan-ocean text-vispan-ocean text-xs font-bold text-center">
                ✓ Thank you! Your 1-on-1 strategy session request has been received by Vispan Solutions.
              </div>
            </form>
          </div>

        </div>
      </div>

    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 4B: LOCAL SEARCH ANATOMY, SUBMARKETS & ROADMAP (DYNAMIC AI)   -->
  <!-- ===================================================================== -->
  <?php if ( ! empty( $ai_market_anatomy ) || ! empty( $ai_submarkets ) || ! empty( $ai_market_accordion ) || ! empty( $ai_case_proof ) || ! empty( $ai_essay ) ) : ?>
  <section id="local-market-strategy" class="py-20 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">

      <?php if ( ! empty( $ai_market_anatomy ) ) : ?>
        <div class="max-w-4xl mx-auto text-center mb-14">
          <?php if ( ! empty( $ai_market_anatomy['kicker'] ) ) : ?>
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-vispan-ice text-vispan-ocean text-xs font-extrabold uppercase tracking-wider mb-3">
              <?php echo esc_html( $ai_market_anatomy['kicker'] ); ?>
            </span>
          <?php endif; ?>
          <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-vispan-dark tracking-tight">
            <?php echo esc_html( $ai_market_anatomy['heading'] ?? sprintf( 'The Search Anatomy of %s', $city ) ); ?>
          </h2>
          <?php if ( ! empty( $ai_market_anatomy['lead_paragraph'] ) ) : ?>
            <p class="text-slate-700 text-base sm:text-lg mt-4 leading-relaxed font-medium">
              <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $ai_market_anatomy['lead_paragraph'] ) : esc_html( $ai_market_anatomy['lead_paragraph'] ); ?>
            </p>
          <?php endif; ?>
          <?php if ( ! empty( $ai_market_anatomy['search_dynamics'] ) ) : ?>
            <p class="text-slate-600 text-sm sm:text-base mt-2 leading-relaxed">
              <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $ai_market_anatomy['search_dynamics'] ) : esc_html( $ai_market_anatomy['search_dynamics'] ); ?>
            </p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ( ! empty( $ai_market_accordion ) && is_array( $ai_market_accordion ) ) : ?>
        <div class="max-w-4xl mx-auto mb-16 space-y-4">
          <?php foreach ( $ai_market_accordion as $m_idx => $item ) : ?>
            <details class="group bg-white rounded-2xl border border-slate-200 p-6 shadow-sm"<?php echo 0 === $m_idx ? ' open' : ''; ?>>
              <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base sm:text-lg text-vispan-dark list-none">
                <span class="flex items-center gap-3">
                  <span class="w-8 h-8 rounded-lg bg-vispan-ice text-vispan-ocean flex items-center justify-center text-xs font-black">
                    <?php echo str_pad( (string) ( $m_idx + 1 ), 2, '0', STR_PAD_LEFT ); ?>
                  </span>
                  <span><?php echo esc_html( $item['title'] ?? '' ); ?></span>
                </span>
                <i class="fa-solid fa-chevron-down text-vispan-ocean group-open:rotate-180 transition"></i>
              </summary>
              <div class="text-sm sm:text-base text-slate-600 mt-4 leading-relaxed pl-11">
                <?php if ( ! empty( $item['subtitle'] ) ) : ?>
                  <div class="text-xs font-bold text-vispan-ocean uppercase tracking-wider mb-2"><?php echo esc_html( $item['subtitle'] ); ?></div>
                <?php endif; ?>
                <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $item['body'] ?? '' ) : esc_html( $item['body'] ?? '' ); ?>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ( ! empty( $ai_submarkets ) && is_array( $ai_submarkets ) ) : ?>
        <div class="mb-16">
          <div class="max-w-3xl mx-auto text-center mb-10">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-slate-200 text-vispan-ocean text-xs font-extrabold uppercase tracking-wider mb-2">
              Granular Location Targeting
            </span>
            <h3 class="font-display text-2xl sm:text-3xl font-extrabold text-vispan-dark">
              Local Submarket Hubs in <span class="county-name text-vispan-ocean"><?php echo esc_html( $city ); ?></span>
            </h3>
          </div>
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ( $ai_submarkets as $s_idx => $sm ) : ?>
              <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between shadow-sm hover:shadow-md transition">
                <div>
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-vispan-ocean tracking-wider uppercase"><?php echo str_pad( (string) ( $s_idx + 1 ), 2, '0', STR_PAD_LEFT ); ?> /</span>
                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-vispan-ice text-vispan-ocean uppercase"><?php echo esc_html( $sm['type_tag'] ?? 'Commercial Search' ); ?></span>
                  </div>
                  <h4 class="font-display font-bold text-lg text-vispan-dark mb-2"><?php echo esc_html( $sm['name'] ?? '' ); ?></h4>
                  <p class="text-sm text-slate-600 leading-relaxed mb-4"><?php echo esc_html( $sm['description'] ?? '' ); ?></p>
                </div>
                <?php if ( ! empty( $sm['fact'] ) ) : ?>
                  <div class="pt-3 border-t border-slate-100 text-xs text-slate-500">
                    🎯 <strong class="text-slate-800">Target Intent:</strong> <?php echo esc_html( $sm['fact'] ); ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ( ! empty( $ai_case_proof ) ) : ?>
        <div class="vispan-hero-bg rounded-3xl p-8 sm:p-12 text-white shadow-xl mb-16">
          <div class="grid lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-8 space-y-4">
              <span class="inline-block px-3 py-1 rounded-full bg-white/10 border border-white/20 text-sky-200 text-xs font-bold uppercase tracking-wider">
                <?php echo esc_html( $ai_case_proof['title'] ?? 'Growth Benchmark Case Proof' ); ?>
              </span>
              <h3 class="font-display text-2xl sm:text-3xl font-extrabold text-white">
                <?php echo esc_html( $ai_case_proof['tagline'] ?? sprintf( 'How a local business outranked competitors in %s', $city ) ); ?>
              </h3>
              <blockquote class="text-sky-100 text-base sm:text-lg italic leading-relaxed">
                <?php echo esc_html( $ai_case_proof['client_quote'] ?? '' ); ?>
              </blockquote>
            </div>
            <?php if ( ! empty( $ai_case_proof['highlights'] ) && is_array( $ai_case_proof['highlights'] ) ) : ?>
              <div class="lg:col-span-4 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-1 gap-3">
                <?php foreach ( $ai_case_proof['highlights'] as $hl ) : ?>
                  <div class="bg-white/10 border border-white/20 rounded-xl p-4 text-center">
                    <div class="font-display text-2xl sm:text-3xl font-black text-white"><?php echo esc_html( $hl['metric'] ?? '' ); ?></div>
                    <div class="text-xs text-sky-200 font-semibold mt-1"><?php echo esc_html( $hl['label'] ?? '' ); ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ( ! empty( $ai_essay ) ) : ?>
        <div class="max-w-4xl mx-auto bg-white rounded-3xl border border-slate-200 p-8 sm:p-12 shadow-sm">
          <span class="inline-block px-3.5 py-1 rounded-full bg-vispan-ice text-vispan-ocean text-xs font-extrabold uppercase tracking-wider mb-3">
            Authority Editorial &amp; Roadmap
          </span>
          <h3 class="font-display text-2xl sm:text-3xl font-extrabold text-vispan-dark mb-6">
            <?php echo esc_html( $ai_essay['heading'] ?? sprintf( 'The Strategic Blueprint for %s', $city ) ); ?>
          </h3>
          <div class="text-slate-600 text-base sm:text-lg leading-relaxed space-y-4 mb-8">
            <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $ai_essay['body'] ?? '' ) : esc_html( $ai_essay['body'] ?? '' ); ?>
          </div>
          <?php if ( ! empty( $ai_essay['milestones'] ) && is_array( $ai_essay['milestones'] ) ) : ?>
            <div class="grid sm:grid-cols-2 gap-4 pt-6 border-t border-slate-100">
              <?php foreach ( $ai_essay['milestones'] as $ms ) : ?>
                <div class="p-4 rounded-xl bg-vispan-surface border border-slate-200">
                  <div class="text-xs font-black text-vispan-ocean uppercase tracking-wider"><?php echo esc_html( $ms['step'] ?? '' ); ?></div>
                  <div class="font-display font-bold text-sm text-vispan-dark mt-1"><?php echo esc_html( $ms['title'] ?? '' ); ?></div>
                  <div class="text-xs text-slate-600 mt-1"><?php echo esc_html( $ms['summary'] ?? '' ); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </section>
  <?php endif; ?>

  <!-- ===================================================================== -->
  <!-- SECTION 5: HOW WE WORK (NO NUMBERS & NO BOTTOM BUTTON)                -->
  <!-- ===================================================================== -->
  <section id="process" class="py-20 bg-vispan-surface border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="max-w-3xl mx-auto text-center mb-14">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-slate-200 text-vispan-ocean text-xs font-extrabold uppercase tracking-wider mb-3">
          Transparent Execution
        </span>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-vispan-dark">
          How Vispan Solutions Grows Your Business in <span class="county-name text-vispan-ocean"><?php echo esc_html( $city ); ?></span>
        </h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-7">
        <?php if ( ! empty( $ai_process ) && is_array( $ai_process ) ) : ?>
          <?php
          $icons = [ 'fa-magnifying-glass-chart', 'fa-compass-drafting', 'fa-rocket', 'fa-chart-pie' ];
          foreach ( array_slice( $ai_process, 0, 4 ) as $idx => $step ) :
            $icon = $icons[ $idx % 4 ];
          ?>
            <div class="bg-white p-7 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-11 h-11 rounded-xl vispan-blue-btn text-white flex items-center justify-center mb-5">
                  <i class="fa-solid <?php echo esc_attr( $icon ); ?>"></i>
                </div>
                <h3 class="font-display font-bold text-lg text-vispan-ocean"><?php echo esc_html( $step['title'] ?? '' ); ?></h3>
                <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                  <?php echo esc_html( $step['description'] ?? '' ); ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else : ?>
          <div class="bg-white p-7 rounded-2xl border border-slate-200 shadow-sm">
            <div class="w-11 h-11 rounded-xl vispan-blue-btn text-white flex items-center justify-center mb-5">
              <i class="fa-solid fa-magnifying-glass-chart"></i>
            </div>
            <h3 class="font-display font-bold text-lg text-vispan-ocean">Discovery &amp; Market Audit</h3>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
              We evaluate your current digital footprint, local search visibility, and target home service verticals in <span class="county-name"><?php echo esc_html( $city ); ?></span>.
            </p>
          </div>

          <div class="bg-white p-7 rounded-2xl border border-slate-200 shadow-sm">
            <div class="w-11 h-11 rounded-xl vispan-blue-btn text-white flex items-center justify-center mb-5">
              <i class="fa-solid fa-compass-drafting"></i>
            </div>
            <h3 class="font-display font-bold text-lg text-vispan-ocean">Tailored 360° Strategy</h3>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
              We craft a customized plan combining SEO, Google Ads, Web Development, Branding, and Video/CGI tailored to your goals.
            </p>
          </div>

          <div class="bg-white p-7 rounded-2xl border border-slate-200 shadow-sm">
            <div class="w-11 h-11 rounded-xl vispan-blue-btn text-white flex items-center justify-center mb-5">
              <i class="fa-solid fa-rocket"></i>
            </div>
            <h3 class="font-display font-bold text-lg text-vispan-ocean">In-House Execution &amp; Launch</h3>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
              Our in-house specialists build and launch your high-performing campaigns, service pages, and creative brand assets.
            </p>
          </div>

          <div class="bg-white p-7 rounded-2xl border border-slate-200 shadow-sm">
            <div class="w-11 h-11 rounded-xl vispan-blue-btn text-white flex items-center justify-center mb-5">
              <i class="fa-solid fa-chart-pie"></i>
            </div>
            <h3 class="font-display font-bold text-lg text-vispan-ocean">Optimization &amp; Growth</h3>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
              We continuously monitor performance, optimize campaigns for higher ROI, and scale your visibility across every channel.
            </p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 6: VERIFIED CLIENT TESTIMONIALS FROM VISPANSOLUTIONS.COM      -->
  <!-- ===================================================================== -->
  <section id="testimonials" class="py-20 bg-white border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="max-w-3xl mx-auto text-center mb-14">
        <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-vispan-ice text-vispan-ocean text-xs font-extrabold uppercase tracking-wider mb-3">
          4.8 ★ Rating Across 140+ Verified Client Reviews
        </span>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-vispan-dark">
          What Our Clients Say About Vispan Solutions
        </h2>
      </div>

      <div class="grid md:grid-cols-3 gap-7">
        <?php if ( ! empty( $ai_testimonials ) && is_array( $ai_testimonials ) ) : ?>
          <?php foreach ( array_slice( $ai_testimonials, 0, 3 ) as $t ) : ?>
            <div class="bg-vispan-surface p-7 rounded-2xl border border-slate-200 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-1 text-amber-400 text-sm mb-4">
                  <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="text-sm text-slate-700 leading-relaxed italic">
                  "<?php echo esc_html( $t['quote'] ?? ( $t['content'] ?? '' ) ); ?>"
                </p>
              </div>
              <div class="pt-5 mt-5 border-t border-slate-200 flex items-center justify-between">
                <div>
                  <div class="font-display font-bold text-sm text-vispan-dark"><?php echo esc_html( $t['author'] ?? ( $t['client_name'] ?? 'Verified Client' ) ); ?></div>
                  <div class="text-xs text-vispan-ocean font-semibold"><?php echo esc_html( $t['role'] ?? ( $t['business_type'] ?? 'Home Services Partner' ) ); ?><?php echo ! empty( $city ) ? ' · ' . esc_html( $city ) : ''; ?></div>
                </div>
                <i class="fa-solid fa-quote-right text-slate-300 text-2xl"></i>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else : ?>
          <!-- Verified Review 1: Hristo Gaydarski -->
          <div class="bg-vispan-surface p-7 rounded-2xl border border-slate-200 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-1 text-amber-400 text-sm mb-4">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
              </div>
              <p class="text-sm text-slate-700 leading-relaxed italic">
                "Partnering with Vispan Solutions has been a game-changer for our online presence! They created a sleek, user-friendly website for us and executed a spot-on digital marketing strategy."
              </p>
            </div>
            <div class="pt-5 mt-5 border-t border-slate-200 flex items-center justify-between">
              <div>
                <div class="font-display font-bold text-sm text-vispan-dark">Hristo Gaydarski</div>
                <div class="text-xs text-vispan-ocean font-semibold">Verified Vispan Solutions Client</div>
              </div>
              <i class="fa-solid fa-quote-right text-slate-300 text-2xl"></i>
            </div>
          </div>

          <!-- Verified Review 2: Christina Brauer -->
          <div class="bg-vispan-surface p-7 rounded-2xl border border-slate-200 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-1 text-amber-400 text-sm mb-4">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
              </div>
              <p class="text-sm text-slate-700 leading-relaxed italic">
                "I've been working with Vispan Solutions for several months now, and they have truly transformed our online presence. Their team is knowledgeable, creative, and always ahead of the curve with the latest digital marketing trends."
              </p>
            </div>
            <div class="pt-5 mt-5 border-t border-slate-200 flex items-center justify-between">
              <div>
                <div class="font-display font-bold text-sm text-vispan-dark">Christina Brauer</div>
                <div class="text-xs text-vispan-ocean font-semibold">Verified Vispan Solutions Client</div>
              </div>
              <i class="fa-solid fa-quote-right text-slate-300 text-2xl"></i>
            </div>
          </div>

          <!-- Verified Review 3: William Sage -->
          <div class="bg-vispan-surface p-7 rounded-2xl border border-slate-200 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-1 text-amber-400 text-sm mb-4">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
              </div>
              <p class="text-sm text-slate-700 leading-relaxed italic">
                "We hired Vispan Solutions to revamp our website and manage our digital marketing, and the results have been outstanding! Their web development team built a fast, modern, and responsive site."
              </p>
            </div>
            <div class="pt-5 mt-5 border-t border-slate-200 flex items-center justify-between">
              <div>
                <div class="font-display font-bold text-sm text-vispan-dark">William Sage</div>
                <div class="text-xs text-vispan-ocean font-semibold">Verified Vispan Solutions Client</div>
              </div>
              <i class="fa-solid fa-quote-right text-slate-300 text-2xl"></i>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 7: CLEAN FAQS                                                 -->
  <!-- ===================================================================== -->
  <section id="faq" class="py-20 bg-vispan-surface border-t border-slate-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
      <div class="text-center mb-12">
        <span class="text-xs font-extrabold uppercase tracking-wider text-vispan-ocean">Curious? Here Are Your Burning FAQs</span>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-vispan-dark mt-2">
          Frequently Asked Questions
        </h2>
      </div>

      <div class="space-y-4">
        <?php if ( ! empty( $ai_faqs ) && is_array( $ai_faqs ) ) : ?>
          <?php foreach ( $ai_faqs as $idx => $faq ) : ?>
            <details class="group bg-white rounded-2xl border border-slate-200 p-6"<?php echo 0 === $idx ? ' open' : ''; ?>>
              <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base sm:text-lg text-vispan-dark list-none">
                <span><?php echo esc_html( $faq['question'] ?? '' ); ?></span>
                <i class="fa-solid fa-chevron-down text-vispan-ocean group-open:rotate-180 transition"></i>
              </summary>
              <div class="text-sm sm:text-base text-slate-600 mt-3 leading-relaxed">
                <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $faq['answer'] ?? '' ) : esc_html( $faq['answer'] ?? '' ); ?>
              </div>
            </details>
          <?php endforeach; ?>
        <?php else : ?>
          <details class="group bg-white rounded-2xl border border-slate-200 p-6" open>
            <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base sm:text-lg text-vispan-dark list-none">
              <span>What digital marketing services does Vispan Solutions provide for home service contractors in <span class="county-name"><?php echo esc_html( $city ); ?></span>?</span>
              <i class="fa-solid fa-chevron-down text-vispan-ocean group-open:rotate-180 transition"></i>
            </summary>
            <p class="text-sm sm:text-base text-slate-600 mt-3 leading-relaxed">
              We provide a complete 360° digital solution in-house: <strong>Digital Marketing</strong>, <strong>Google Ads</strong>, <strong>Branding Services</strong>, <strong>SEO</strong>, <strong>Web Development</strong>, <strong>Social Media Management</strong>, <strong>Online Reputation Management</strong>, <strong>Video Production</strong>, <strong>VFX</strong>, and <strong>CGI Services</strong>. Whether your company specializes in emergency trades like <strong>Plumbing &amp; Hydro Jetting</strong>, <strong>HVAC</strong>, and <strong>Electrical</strong>, or scheduled interior upgrades like <strong>Home Decor, Blinds, Shades &amp; Shutters</strong>, Vispan Solutions delivers tailored campaigns for every home service you offer.
            </p>
          </details>

          <details class="group bg-white rounded-2xl border border-slate-200 p-6">
            <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base sm:text-lg text-vispan-dark list-none">
              <span>Why do home service businesses in <span class="county-name"><?php echo esc_html( $city ); ?></span> need professional digital marketing?</span>
              <i class="fa-solid fa-chevron-down text-vispan-ocean group-open:rotate-180 transition"></i>
            </summary>
            <p class="text-sm sm:text-base text-slate-600 mt-3 leading-relaxed">
              Over 85% of homeowners in <span class="county-name"><?php echo esc_html( $city ); ?></span> turn directly to Google Search and Google Maps when hiring contractors for urgent repairs or scheduled renovations. Relying solely on word-of-mouth or paying high fees to shared-lead aggregators leaves your business vulnerable to competitors. Targeted digital marketing, localized SEO, and high-ROI Google Ads ensure your phones ring with exclusive high-ticket customer calls every single week.
            </p>
          </details>

          <details class="group bg-white rounded-2xl border border-slate-200 p-6">
            <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base sm:text-lg text-vispan-dark list-none">
              <span>Why should you contact and partner with Vispan Solutions for these services?</span>
              <i class="fa-solid fa-chevron-down text-vispan-ocean group-open:rotate-180 transition"></i>
            </summary>
            <p class="text-sm sm:text-base text-slate-600 mt-3 leading-relaxed">
              Empowering brands since 2012, Vispan Solutions has delivered 150+ projects with a 99% success rate, earned a 4.8★ rating across 140+ client reviews, and won 12+ industry awards including the IT-ITeS-Excellence Awards 2023 &amp; 2024 for Best SEO and Digital Marketer. We offer strict territory exclusivity (we only partner with one contractor per trade in your market), 100% exclusive inbound leads, and dedicated in-house account management with transparent ROI reporting.
            </p>
          </details>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 8: BOTTOM CONTACT US SECTION (FORM #3 + VERIFIED OFFICES)     -->
  <!-- ===================================================================== -->
  <section id="contact" class="py-20 vispan-hero-bg text-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      <div class="bg-white text-slate-900 rounded-3xl p-8 sm:p-12 shadow-2xl">
        <div class="grid lg:grid-cols-12 gap-10 items-start">
          
          <!-- Left Column: Verified Vispan Global Offices & Contact Information -->
          <div class="lg:col-span-5 space-y-4">
            <span class="inline-block px-3.5 py-1 rounded-full bg-vispan-ice text-vispan-ocean text-xs font-extrabold uppercase tracking-wider">
              We Span Your Digital Presence
            </span>
            <h2 class="font-display text-3xl font-extrabold text-vispan-dark leading-tight">
              <?php if ( ! empty( $final_cta_head ) ) : ?>
                <?php echo esc_html( $final_cta_head ); ?>
              <?php else : ?>
                Ready to Grow in <span class="county-name text-vispan-ocean"><?php echo esc_html( $city ); ?></span>? <span class="text-vispan-ocean">Contact Us</span>
              <?php endif; ?>
            </h2>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
              <?php if ( ! empty( $final_cta_desc ) ) : ?>
                <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $final_cta_desc ) : esc_html( $final_cta_desc ); ?>
              <?php else : ?>
                Partner with Vispan Solutions for tailored SEO, Google Ads, Web Development, Branding, Social Media, Reputation Management, Video Production, VFX, and CGI Services.
              <?php endif; ?>
            </p>

            <!-- Verified Global Office Locations from vispansolutions.com/contact-us/ -->
            <div class="pt-3 border-t border-slate-200 text-xs text-slate-600 space-y-3">
              <div class="p-3 rounded-xl bg-vispan-surface border border-slate-200/80">
                <div class="font-bold text-vispan-dark">USA Office</div>
                <div class="mt-0.5">4261 Rimini Avenue, El Dorado Hills, CA 95762</div>
                <div class="font-bold text-vispan-ocean mt-0.5">+1 (720) 203-2600</div>
              </div>
              <div class="p-3 rounded-xl bg-vispan-surface border border-slate-200/80">
                <div class="font-bold text-vispan-dark">India HQ (Rajkot)</div>
                <div class="mt-0.5">R K Complex, 16/28-Vijay Plot, Gondal Road, RAJKOT – 360002</div>
                <div class="font-bold text-vispan-ocean mt-0.5">+91 84859 86860 · contact@vispansolutions.com</div>
              </div>
              <div class="p-3 rounded-xl bg-vispan-surface border border-slate-200/80">
                <div class="font-bold text-vispan-dark">Canada Office</div>
                <div class="mt-0.5">509 Silverberry Road, NW, Edmonton, AB, T6T 1T2 · <strong class="text-vispan-ocean">+1 (780) 777 0226</strong></div>
              </div>
              <div class="p-3 rounded-xl bg-vispan-surface border border-slate-200/80">
                <div class="font-bold text-vispan-dark">Australia Offices</div>
                <div class="mt-0.5">4 Darnick St, Underwood, QLD, Brisbane, 4119 (<strong class="text-vispan-ocean">+61 (073) 153 3040</strong>)</div>
                <div class="mt-0.5">9/29 Logan River Road, Beenleigh, 4207 (<strong class="text-vispan-ocean">+61 (075) 655 3599</strong>)</div>
              </div>
            </div>
          </div>

          <!-- Right Form Column (Form #3) -->
          <div class="lg:col-span-7">
            <form onsubmit="submitFormToast(event, 'proposalToast')" class="space-y-4 bg-vispan-surface p-6 sm:p-8 rounded-2xl border border-slate-200">
              <input type="text" name="inquiry_hp_check" value="" style="display:none !important;" tabindex="-1" autocomplete="off" />
              <input type="hidden" name="action" value="seo_city_submit_inquiry" />
              <input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
              <input type="hidden" name="form_layout" value="bottom_proposal" />
              <div class="pb-2 border-b border-slate-200">
                <h3 class="font-display font-extrabold text-xl text-vispan-dark">
                  Request a <span class="text-vispan-ocean">Free Live Demo &amp; Custom Proposal</span>
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                  Tell us about your business and the services you need—our team will get back to you promptly.
                </p>
              </div>
              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Full Name*</label>
                  <input type="text" required placeholder="Your Full Name" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm bg-white focus:border-vispan-ocean outline-none" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Company / Organization*</label>
                  <input type="text" name="company_name" required placeholder="Your Company Name" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm bg-white focus:border-vispan-ocean outline-none" />
                </div>
              </div>
              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address*</label>
                  <input type="email" name="email" required placeholder="you@company.com" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm bg-white focus:border-vispan-ocean outline-none" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Phone Number*</label>
                  <input type="tel" required placeholder="+1 (720) 203-2600" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm bg-white focus:border-vispan-ocean outline-none" />
                </div>
              </div>
              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Vispan Service Needed</label>
                  <select name="service" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm bg-white text-slate-700 focus:border-vispan-ocean outline-none">
                    <option>Digital Marketing</option>
                    <option>Google Ads</option>
                    <option>Branding Services</option>
                    <option>SEO</option>
                    <option>Web Development</option>
                    <option>Social Media Management</option>
                    <option>Online Reputation Management</option>
                    <option>Video Production</option>
                    <option>VFX</option>
                    <option>CGI Services</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Your Home Service Category</label>
                  <select name="details" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm bg-white text-slate-700 focus:border-vispan-ocean outline-none">
                    <option>Core Systems &amp; Emergency (HVAC, Plumbing &amp; Hydro Jetting, Electrical)</option>
                    <option>Exterior &amp; Structural (Roofing, Gutters, Garage Door, Pressure Washing)</option>
                    <option>Interior, Home Decor &amp; Handyman (Blinds, Shades, Shutters, Painting, Flooring)</option>
                    <option>Outdoor &amp; Yard Care (Lawn Care, Pest Control, Pool, Snow Removal)</option>
                    <option>Residential Cleaning &amp; Sanitation (Housekeeping, Junk, Window Cleaning)</option>
                    <option>Safety, Security &amp; Energy Automation (Security, Locksmith, Solar)</option>
                    <option>Multiple / All Home Services</option>
                  </select>
                </div>
              </div>
              <button type="submit" class="w-full py-4 rounded-xl vispan-blue-btn text-white font-extrabold text-sm shadow-lg shadow-sky-900/20 hover:opacity-95 transition">
                <?php echo esc_html( ! empty( $final_cta_btn ) ? $final_cta_btn : 'Get My Free Proposal & Demo →' ); ?>
              </button>
              <div id="proposalToast" class="hidden p-3.5 rounded-xl bg-vispan-ice border border-vispan-ocean text-vispan-ocean text-xs font-bold text-center">
                ✓ Thank you! Your request has been received by Vispan Solutions.
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="bg-vispan-dark text-slate-300 py-10 border-t border-slate-800 text-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg vispan-blue-btn text-white font-display font-extrabold flex items-center justify-center">V</div>
        <span class="font-semibold text-white">© 2012–2026 Vispan Solutions Pvt. Ltd. All Rights Reserved.</span>
      </div>
      <div class="flex flex-wrap gap-6 text-slate-300">
        <a href="https://vispansolutions.com/" class="hover:text-white">Home</a>
        <a href="https://vispansolutions.com/about-us/" class="hover:text-white">About Us</a>
        <a href="https://vispansolutions.com/what-we-do/" class="hover:text-white">What We Do</a>
        <a href="https://vispansolutions.com/portfolio/" class="hover:text-white">Portfolio</a>
        <a href="https://vispansolutions.com/career/" class="hover:text-white">Career</a>
        <a href="https://vispansolutions.com/blog/" class="hover:text-white">Blog</a>
        <a href="https://vispansolutions.com/contact-us/" class="hover:text-white">Contact Us</a>
      </div>
    </div>
  </footer>

  <!-- ===================================================================== -->
  <!-- JAVASCRIPT: OFFICIAL VISPAN SERVICES + HOME SERVICES (ZERO NUMBERS)   -->
  <!-- ===================================================================== -->
  <script>
    // Official Vispan Solutions Services (100% Verbatim Titles, Screenshot Lines & Official Website Descriptions — Zero Numbers)
    const officialVispanServices = [
      {
        title: 'Digital Marketing',
        desc: 'Boost your brand with tailored digital marketing services etc.',
        details: 'Boost your brand with tailored digital marketing services, including SEO, social media, and paid ads, driving growth and engagement. We turn scrolling zombies into loyal customers and make algorithms bow to your brand.',
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="8" y="8" width="32" height="32" rx="1" />
          <polyline points="8,34 17,24 23,30 31,19 40,26" />
        </svg>`
      },
      {
        title: 'Google Ads',
        desc: 'Maximize your online presence with expert Google Ads Service',
        details: 'Maximize your online presence with expert Google Ads management, ensuring targeted campaigns, optimized performance, and higher ROI for your business. We craft high-converting ads that attract clicks, leads, and sales.',
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="13" y="6" width="8" height="17" rx="4" />
          <circle cx="29" cy="14" r="4" />
          <path d="M9 24 H39 A15 15 0 0 1 9 24 Z" />
        </svg>`
      },
      {
        title: 'Branding Services',
        desc: 'Enhance your brand identity with our expert branding services',
        details: 'Enhance your brand identity with our expert branding services, delivering creative strategies that captivate audiences and establish a strong market presence.',
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="24" cy="24" r="16" />
          <path d="M9 28 C16 19, 31 19, 40 25" />
          <path d="M18 9 C24 17, 23 30, 11 33" />
          <circle cx="21" cy="19" r="2.5" fill="currentColor" />
        </svg>`
      },
      {
        title: 'SEO',
        desc: "Enhance your website's visibility and organic traffic with expert SEO",
        details: "Enhance your website's visibility and drive organic traffic with our expert SEO strategies, tailored to achieve top search engine rankings. More traffic, better rankings, and zero shady tactics.",
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="24" cy="24" r="12" />
          <circle cx="24" cy="24" r="6" />
          <line x1="24" y1="6" x2="24" y2="42" />
          <line x1="6" y1="24" x2="42" y2="24" />
          <line x1="11" y1="11" x2="37" y2="37" />
          <line x1="37" y1="11" x2="11" y2="37" />
        </svg>`
      },
      {
        title: 'Web Development',
        desc: 'We deliver high performing websites as per your business',
        details: 'Create responsive, user-friendly websites tailored to your business needs, ensuring seamless performance, robust functionality, and engaging designs. We build pixel-perfect, lightning-fast sites that impress and convert.',
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="7" y="7" width="16" height="16" />
          <rect x="23" y="23" width="16" height="16" />
          <rect x="11" y="28" width="6" height="6" />
          <line x1="27" y1="10" x2="35" y2="10" />
          <line x1="27" y1="14" x2="39" y2="14" />
          <line x1="27" y1="18" x2="35" y2="18" />
        </svg>`
      },
      {
        title: 'Social Media Management',
        desc: "Optimize your brand's online presence",
        details: "Optimize your brand's online presence with expert social media management, driving engagement, building community, and enhancing visibility across platforms.",
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <line x1="24" y1="7" x2="24" y2="19" />
          <line x1="24" y1="29" x2="24" y2="41" />
          <line x1="7" y1="24" x2="19" y2="24" />
          <line x1="29" y1="24" x2="41" y2="24" />
          <polyline points="13,18 18,18 18,13" />
          <polyline points="35,18 30,18 30,13" />
          <polyline points="13,30 18,30 18,35" />
          <polyline points="35,30 30,30 30,35" />
        </svg>`
      },
      {
        title: 'Online Reputation Management',
        desc: "We help improve your brand's online image",
        details: "We help protect and improve your brand's online image through strategic monitoring, review management, and positive content creation to build trust and credibility.",
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="26,6 13,26 23,26 20,42 35,21 25,21" />
        </svg>`
      },
      {
        title: 'Video Production',
        desc: 'From concept to final cut, we create engaging videos',
        details: 'From concept to final cut, we create engaging videos that tell your brand story, promote products, or deliver messages through high-quality visuals and professional editing.',
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="24,8 37,15 24,22 11,15" />
          <polygon points="10,18 22,25 22,39 10,32" />
          <polygon points="38,18 26,25 26,39 38,32" />
        </svg>`
      },
      {
        title: 'VFX',
        desc: 'Our VFX services bring imagination to life with stunning visual effects',
        details: 'Our VFX services bring imagination to life with stunning visual effects for films, ads, and digital content—enhancing storytelling and audience experience with creativity and precision.',
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="22,12 34,24 22,36 10,24" />
          <polygon points="31,18 37,24 31,30 25,24" />
          <line x1="6" y1="24" x2="42" y2="24" />
        </svg>`
      },
      {
        title: 'CGI Services',
        desc: 'We deliver high-end CGI visuals, including 3D modeling, animation',
        details: 'We deliver high-end CGI visuals, including 3D modeling, animation, and rendering, to create realistic, impactful imagery for films, advertising, architecture, and product showcases.',
        svg: `<svg class="service-svg w-12 h-12 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="13" cy="21" r="3" />
          <path d="M13 24 V31 L9 35 H15" />
          <circle cx="20" cy="11" r="3" />
          <path d="M20 14 V38" />
          <circle cx="27" cy="18" r="3" />
          <path d="M27 21 V35 L24 38" />
          <circle cx="34" cy="24" r="3" />
          <path d="M34 27 V38" />
        </svg>`
      }
    ];

    function renderOfficialServices() {
      // Render Section 3 Boxed Grid (Strictly 2 Columns per Row, Zero Numbers, Official Vispan Copy)
      const grid = document.getElementById('official-services-grid');
      if (grid) {
        grid.innerHTML = officialVispanServices.map(item => `
          <div class="official-service-box bg-white rounded-2xl border border-slate-200/90 p-7 sm:p-8 flex flex-col justify-between shadow-sm">
            <div>
              <div class="flex items-start gap-4 mb-4">
                <div class="w-14 h-14 rounded-2xl bg-vispan-surface border border-slate-200/80 flex items-center justify-center shrink-0">
                  ${item.svg}
                </div>
                <div>
                  <h3 class="font-display font-extrabold text-xl sm:text-[22px] text-[#0B6599] leading-snug">
                    ${item.title}
                  </h3>
                  <p class="text-sm font-semibold text-slate-700 mt-1">
                    ${item.desc}
                  </p>
                </div>
              </div>

              <p class="text-sm sm:text-[15px] text-slate-600 leading-relaxed mt-3 pt-3 border-t border-slate-100">
                ${item.details}
              </p>
            </div>
          </div>
        `).join('');
      }

      // Render Header Mega Menu (Exact 4-Column Layout from Screenshot)
      const mega = document.getElementById('header-mega-services');
      if (mega) {
        mega.innerHTML = officialVispanServices.map(item => `
          <a href="#our-services" class="flex items-start gap-3.5 group/item p-2 rounded-xl hover:bg-slate-50 transition">
            <div class="shrink-0 pt-0.5 scale-90 origin-top-left">
              ${item.svg}
            </div>
            <div>
              <div class="font-display font-bold text-[15px] text-[#0B6599] group-hover/item:underline leading-snug">
                ${item.title}
              </div>
              <p class="text-xs text-slate-600 mt-1 leading-relaxed font-normal">
                ${item.desc}
              </p>
            </div>
          </a>
        `).join('');
      }
    }

    // Home Services Categories & Services (Zero Numbers, Exact User Content + Home Decor, Blinds, Shades, Shutters & Plumbing and Hydro Jetting)
    const categoriesData = [
      {
        title: 'Core Systems & Emergency Trades',
        subtitle: 'These are high-ticket, high-intent emergency trades that represent the largest spenders in digital marketing:',
        services: [
          {
            name: 'HVAC Services',
            desc: 'Heating, ventilation, air conditioning repair, system replacements, and seasonal duct maintenance.',
            img: 'https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Plumbing & Hydro Jetting Services',
            desc: 'Drain clearing, high-pressure hydro jetting, pipe repair, leak detection, water heater installations, and emergency plumbing.',
            img: 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Electrical Services',
            desc: 'Residential rewiring, breaker panel upgrades, lighting installations, EV charger setups, and smart home hardwiring.',
            img: 'https://images.unsplash.com/photo-1621905252507-b35492cc74b4?auto=format&fit=crop&w=600&q=80'
          }
        ]
      },
      {
        title: 'Exterior & Structural Maintenance',
        subtitle: 'High-stakes exterior services often driven by weather events, age, or aesthetic upgrades:',
        services: [
          {
            name: 'Roofing & Siding',
            desc: 'Roof repairs, total replacements, leak interventions, gutters, and vinyl/wood exterior siding.',
            img: 'https://images.unsplash.com/photo-1632759145351-1d592919f522?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Gutter Cleaning & Installation',
            desc: 'Gutter guard installations, seasonal cleanouts, and drainage alignments.',
            img: 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Garage Door Repair',
            desc: 'Spring replacements, automated motor installations, and structural tracking repairs.',
            img: 'https://images.unsplash.com/photo-1558036117-15d82a90b9b1?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Pressure Washing',
            desc: 'Deep exterior cleaning of siding, driveways, decks, and fences.',
            img: 'https://images.unsplash.com/photo-1527515637462-cff94eecc1ac?auto=format&fit=crop&w=600&q=80'
          }
        ]
      },
      {
        title: 'Interior Improvement, Home Decor & Handyman Services',
        subtitle: 'Scheduled remodel work or general handyman tasks focused on aesthetics and lifestyle enhancements:',
        services: [
          {
            name: 'General Handyman Work',
            desc: 'Drywall patching, furniture assembly, mounting, fixture swaps, and minor odd jobs.',
            img: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Interior & Exterior Painting',
            desc: 'Whole-house painting, trim touch-ups, cabinetry refinishing, and wallpaper hanging.',
            img: 'https://images.unsplash.com/photo-1562259949-e8e7689d7828?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Flooring & Carpeting',
            desc: 'Tile layouts, hardwood installation, carpet laying, and deep carpet cleaning treatments.',
            img: 'https://images.unsplash.com/photo-1581858726788-75bc0f6a952d?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Carpentry',
            desc: 'Custom built-ins, molding, cabinetry installation, deck building, and framing repairs.',
            img: 'https://images.unsplash.com/photo-1504148455328-c376907d081c?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Home Decor, Blinds, Shades & Shutters',
            desc: 'Custom window treatments, blinds, shades, shutters, and interior home decor installations.',
            img: 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=600&q=80'
          }
        ]
      },
      {
        title: 'Outdoor & Yard Care',
        subtitle: 'Recurring, subscription-style recurring services with high seasonal digital marketing demand:',
        services: [
          {
            name: 'Lawn Care & Landscaping',
            desc: 'Mowing, fertilization, sod installation, tree trimming, and landscape architecture.',
            img: 'https://images.unsplash.com/photo-1558904541-efa843a96f01?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Pest Control & Wildlife Removal',
            desc: 'Termite treatments, rodent proofing, seasonal insect sprays, and wildlife relocation.',
            img: 'https://images.unsplash.com/photo-1635048424329-a9bfb146d7aa?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Pool Installation & Maintenance',
            desc: 'Regular chemical balancing, equipment repairs, pool openings/closings, and custom pool builds.',
            img: 'https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Snow & Ice Removal',
            desc: 'Commercial and residential driveway plowing and salting.',
            img: 'https://images.unsplash.com/photo-1517299321609-52687d1bc55a?auto=format&fit=crop&w=600&q=80'
          }
        ]
      },
      {
        title: 'Residential Cleaning & Sanitation',
        subtitle: 'High-frequency recurring service models built strongly around local consumer reviews:',
        services: [
          {
            name: 'Housekeeping & Maid Services',
            desc: 'Weekly/bi-weekly deep cleans, regular dusting, and standard apartment maintenance.',
            img: 'https://images.unsplash.com/photo-1581578017093-cd30fce4eeb7?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Junk Removal & Hauling',
            desc: 'Post-renovation cleanup, estate clear-outs, and large appliance disposal.',
            img: 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Window, Blind & Shutter Cleaning',
            desc: 'Streak-free interior and exterior window, blind, and shutter scrubbing for residential estates.',
            img: 'https://images.unsplash.com/photo-1527515862127-a4fc05baf7a5?auto=format&fit=crop&w=600&q=80'
          }
        ]
      },
      {
        title: 'Safety, Security & Energy Automation',
        subtitle: 'Technological installations designed to protect or optimize a residential living space:',
        services: [
          {
            name: 'Home Security Systems',
            desc: 'Alarm systems, smart lock setups, security camera mapping, and 24/7 monitoring.',
            img: 'https://images.unsplash.com/photo-1558002038-1055907df827?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Locksmith Services',
            desc: 'Emergency lockouts, key duplication, and deadbolt replacements.',
            img: 'https://images.unsplash.com/photo-1582139329536-e7284fece509?auto=format&fit=crop&w=600&q=80'
          },
          {
            name: 'Solar Power & Energy Management',
            desc: 'Solar panel placement, backup home batteries, and energy-efficiency retrofits.',
            img: 'https://images.unsplash.com/photo-1509391366360-2e959784a276?auto=format&fit=crop&w=600&q=80'
          }
        ]
      }
    ];

    function renderTrades() {
      const container = document.getElementById('trades-container');
      let html = '';

      categoriesData.forEach(cat => {
        html += `
          <div class="bg-vispan-surface rounded-3xl p-6 sm:p-9 border border-slate-200">
            <div class="pb-6 mb-7 border-b border-slate-200">
              <h3 class="font-display text-2xl sm:text-3xl font-extrabold text-vispan-dark">
                ${cat.title}
              </h3>
              <p class="text-sm sm:text-base text-slate-600 mt-1">
                ${cat.subtitle}
              </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-7">
              ${cat.services.map(service => `
                <div class="vispan-card-hover scp-trade-card bg-white rounded-2xl overflow-hidden border border-slate-200 flex flex-col shadow-sm h-full">
                  <div class="h-44 relative overflow-hidden">
                    <img src="${service.img}" alt="${service.name}" onerror="this.src='https://placehold.co/600x400/0B6599/FFFFFF?text=${encodeURIComponent(service.name)}'" class="w-full h-full object-cover" />
                  </div>
                  <div class="p-6 flex-1 flex flex-col">
                    <h4 class="font-display font-extrabold text-lg text-[#0B6599]">
                      ${service.name}
                    </h4>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                      ${service.desc}
                    </p>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        `;
      });

      container.innerHTML = html;
    }

    function switchCounty(countyName, stateCode, cities, btnId) {
      document.querySelectorAll('.county-name').forEach(el => el.textContent = countyName);
      document.querySelectorAll('.county-state').forEach(el => el.textContent = stateCode);
      document.querySelectorAll('.county-cities').forEach(el => el.textContent = cities);
      document.title = `Home Services Digital Marketing Agency in ${countyName}, ${stateCode} | Vispan Solutions`;

      document.querySelectorAll('.county-pill').forEach(btn => {
        btn.className = 'county-pill px-3 py-1 rounded-md bg-slate-800/90 hover:bg-slate-700 text-slate-200 font-medium transition';
      });
      const active = document.getElementById(btnId);
      if (active) {
        active.className = 'county-pill px-3 py-1 rounded-md bg-vispan-ocean text-white font-semibold transition';
      }
    }

    function applyCustomCounty() {
      const input = document.getElementById('custom-county-input');
      if (!input || !input.value.trim()) return;
      const parts = input.value.trim().split(',');
      const countyName = parts[0].trim();
      const stateCode = parts[1] ? parts[1].trim().toUpperCase() : 'US';
      switchCounty(countyName, stateCode, `key neighborhoods across ${countyName}`, '');
    }

    function submitFormToast(e, toastId) {
      e.preventDefault();
      const form = e.target;
      const el = document.getElementById(toastId);
      if (el) {
        el.classList.remove('hidden');
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }

      // Submit via AJAX to WordPress admin-ajax.php
      if (typeof fetch !== 'undefined') {
        const formData = new FormData(form);
        const ajaxUrl = (typeof AROLAX_ADDONS_JS !== 'undefined' && AROLAX_ADDONS_JS.ajaxUrl)
          ? AROLAX_ADDONS_JS.ajaxUrl
          : '/wp-admin/admin-ajax.php';
        fetch(ajaxUrl, {
          method: 'POST',
          body: formData
        }).catch(function(err) {
          console.warn('Inquiry dispatch error:', err);
        });
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      renderOfficialServices();
      renderTrades();
    });
  </script>
</div>
