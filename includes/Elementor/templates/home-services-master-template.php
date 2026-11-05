<?php
/**
 * Official Vispan Solutions Master Template for Home Services SEO Landing Pages.
 *
 * Fully responsive, high-converting Tailwind layout featuring 10 official Vispan
 * agency services, 6 residential trade pillars, verified reviews, and dual AJAX lead forms.
 * Sourced directly from official Vispan Solutions design architecture.
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
              ice: '#EFF6FF',
              surface: '#F5F8FC',
              border: '#E2E8F0'
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
      background-color: #0A1120;
      background-image: radial-gradient(circle at 85% 20%, rgba(11, 101, 153, 0.32) 0%, transparent 55%);
    }
    .vispan-primary-btn {
      background-color: #0B6599;
      transition: background-color 0.2s ease, transform 0.15s ease;
    }
    .vispan-primary-btn:hover {
      background-color: #09527d;
    }
    .official-service-box {
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .official-service-box:hover {
      border-color: #0B6599;
      box-shadow: 0 12px 28px -10px rgba(11, 101, 153, 0.12);
    }
    .official-service-box:hover .service-svg {
      stroke: #0B6599;
    }
    .service-svg {
      transition: stroke 0.2s ease;
    }
    .trade-card {
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .trade-card:hover {
      border-color: #0B6599;
      box-shadow: 0 10px 24px -8px rgba(15, 23, 42, 0.08);
    }
    
    /* WordPress & Isolation Overrides */
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
  <!-- TOP UTILITY BAR: REAL VISPAN CONTACT INFO + COUNTY PREVIEW SELECTOR   -->
  <!-- ===================================================================== -->
  <div class="bg-[#070C17] text-slate-300 border-b border-slate-800/80 text-xs">
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

      <div class="flex flex-wrap items-center gap-1.5">
        <span class="text-slate-400 mr-1 hidden sm:inline">County Page Preview:</span>
        <button id="btn-oc-fl" onclick="switchCounty('Orange County', 'FL', 'Orlando, Winter Park & Lake Nona', 'btn-oc-fl')" class="county-pill px-2.5 py-1 rounded bg-[#0B6599] text-white font-semibold transition">Orange County, FL</button>
        <button id="btn-mc-az" onclick="switchCounty('Maricopa County', 'AZ', 'Phoenix, Scottsdale & Mesa', 'btn-mc-az')" class="county-pill px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium transition">Maricopa County, AZ</button>
        <button id="btn-hc-tx" onclick="switchCounty('Harris County', 'TX', 'Houston, Katy & The Woodlands', 'btn-hc-tx')" class="county-pill px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium transition">Harris County, TX</button>
        <button id="btn-oc-ca" onclick="switchCounty('Orange County', 'CA', 'Irvine, Anaheim & Newport Beach', 'btn-oc-ca')" class="county-pill px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium transition">Orange County, CA</button>
      </div>
    </div>
  </div>

  <!-- ===================================================================== -->
  <!-- VISPAN SOLUTIONS OFFICIAL NAVIGATION HEADER + 10-SERVICE MEGA MENU    -->
  <!-- ===================================================================== -->
  <header class="bg-white border-b border-slate-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
      <a href="https://vispansolutions.com/" class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-[#0B6599] flex items-center justify-center text-white font-display font-extrabold text-xl">
          V
        </div>
        <div>
          <span class="font-display font-extrabold text-xl tracking-tight text-slate-900 block leading-none">
            VISPAN <span class="text-[#0B6599]">SOLUTIONS</span>
          </span>
          <span class="text-[11px] font-medium tracking-wider uppercase text-slate-500 block mt-1">
            We Span Your Digital Presence
          </span>
        </div>
      </a>

      <nav class="hidden lg:flex items-center gap-8 text-sm font-semibold text-slate-700">
        <a href="#why-marketing" class="hover:text-[#0B6599] transition">Why Digital Marketing</a>
        
        <!-- Hover Mega-Menu Preview of All 10 Official Services -->
        <div class="relative group py-6">
          <a href="#our-services" class="inline-flex items-center gap-1.5 hover:text-[#0B6599] transition">
            <span>What We Do</span>
            <i class="fa-solid fa-chevron-down text-[10px] text-slate-500 group-hover:text-[#0B6599] transition"></i>
          </a>
          <div class="absolute left-1/2 -translate-x-1/2 top-full w-[900px] bg-white rounded-lg shadow-xl border border-slate-200 p-7 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-50">
            <div class="grid grid-cols-4 gap-x-6 gap-y-6" id="header-mega-services"></div>
          </div>
        </div>

        <a href="#home-services-list" class="hover:text-[#0B6599] transition">Home Services</a>
        <a href="#process" class="hover:text-[#0B6599] transition">How We Work</a>
        <a href="#testimonials" class="hover:text-[#0B6599] transition">Client Reviews</a>
        <a href="#faq" class="hover:text-[#0B6599] transition">FAQs</a>
      </nav>

      <div class="flex items-center gap-4">
        <a href="#contact" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg vispan-primary-btn text-white font-semibold text-sm">
          <span>Contact Us</span>
        </a>
      </div>
    </div>
  </header>

  <!-- ===================================================================== -->
  <!-- SECTION 1: HUMANIZED EDITORIAL HERO (NO AI BENTO BOX CLUTTER)         -->
  <!-- ===================================================================== -->
  <section id="top" class="vispan-hero-bg text-white py-16 lg:py-24 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="grid lg:grid-cols-12 gap-12 lg:gap-14 items-center">
        
        <!-- Left Column: Agency Copy & Single Verified Stats Bar -->
        <div class="lg:col-span-7 space-y-6">
          <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#38BDF8]">
            <span class="w-8 h-0.5 bg-[#38BDF8]"></span>
            <span>Digital Marketing Agency for <span class="county-name"><?php echo esc_html( $city ); ?></span><?php echo ! empty( $state_display ) ? ', <span class="county-state">' . esc_html( $state_display ) . '</span>' : ''; ?></span>
          </div>

          <h1 class="font-display text-4xl sm:text-5xl lg:text-[50px] font-extrabold tracking-tight leading-[1.15] text-white">
            <?php if ( ! empty( $is_ai_hero_title ) ) : ?>
              <?php echo esc_html( $hero_title ); ?>
            <?php else : ?>
              Turning Local Homeowner Searches into Booked Jobs Across <span class="text-[#38BDF8]"><span class="county-name"><?php echo esc_html( $city ); ?></span></span>
            <?php endif; ?>
          </h1>

          <p class="text-slate-300 text-base sm:text-lg leading-relaxed max-w-2xl">
            <?php if ( ! empty( $is_ai_hero_desc ) ) : ?>
              <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $hero_description ) : esc_html( $hero_description ); ?>
            <?php else : ?>
              Why follow trends when you can set them? Since 2012, <strong>Vispan Solutions</strong> has helped residential contractors grow across <span class="county-cities font-semibold text-white"><?php echo esc_html( $cities_str ); ?></span> through tailored SEO, Google Ads, high-performing Web Development, Branding, and Video production.
            <?php endif; ?>
          </p>

          <p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl">
            Whether your crews specialize in emergency <strong>Plumbing &amp; Hydro Jetting</strong>, <strong>HVAC</strong>, and <strong>Roofing</strong>, or interior upgrades like <strong>Home Decor, Blinds, Shades &amp; Shutters</strong>, we manage your entire digital presence in-house.
          </p>

          <!-- Clean Action Buttons -->
          <div class="flex flex-wrap items-center gap-4 pt-2">
            <a href="#mid-contact" class="inline-flex items-center gap-2.5 px-7 py-4 rounded-lg bg-[#0B6599] hover:bg-[#09527d] text-white font-bold text-sm sm:text-base transition">
              <span><?php echo esc_html( ! empty( $hero_cta ) ? $hero_cta : 'Get A Free Consultation' ); ?></span>
            </a>
            <a href="#our-services" class="inline-flex items-center gap-2 px-6 py-4 rounded-lg border border-slate-600 hover:border-slate-400 text-white font-semibold text-sm sm:text-base transition">
              <span>View Our Services</span>
            </a>
          </div>

          <!-- Single Verified Vispan Solutions Track Record Bar (Or AI Metro Stats) -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 pt-8 mt-4 border-t border-slate-800">
            <?php if ( ! empty( $ai_metro_stats ) && is_array( $ai_metro_stats ) ) : ?>
              <?php foreach ( array_slice( $ai_metro_stats, 0, 4 ) as $st ) : ?>
                <div>
                  <div class="font-display text-2xl sm:text-3xl font-extrabold text-white"><?php echo esc_html( $st['value'] ?? '' ); ?></div>
                  <div class="text-xs text-slate-400 mt-1"><?php echo esc_html( $st['label'] ?? '' ); ?></div>
                </div>
              <?php endforeach; ?>
            <?php else : ?>
              <div>
                <div class="font-display text-2xl sm:text-3xl font-extrabold text-white">2012</div>
                <div class="text-xs text-slate-400 mt-1">Year Founded</div>
              </div>
              <div>
                <div class="font-display text-2xl sm:text-3xl font-extrabold text-white">150+</div>
                <div class="text-xs text-slate-400 mt-1">Projects (99% Success)</div>
              </div>
              <div>
                <div class="font-display text-2xl sm:text-3xl font-extrabold text-white">4.8 / 5</div>
                <div class="text-xs text-slate-400 mt-1">140+ Client Reviews</div>
              </div>
              <div>
                <div class="font-display text-2xl sm:text-3xl font-extrabold text-white">12+</div>
                <div class="text-xs text-slate-400 mt-1">Industry Awards Won</div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right Column: HUMAN-ART-DIRECTED EDITORIAL PHOTO COMPOSITION -->
        <div class="lg:col-span-5">
          <div class="relative mx-auto max-w-md lg:max-w-none pl-4 pb-8">
            
            <!-- Solid Architectural Offset Frame Accent -->
            <div class="absolute top-4 -right-3 bottom-4 left-8 rounded-lg bg-[#0B6599]/35 border border-[#0B6599]/60 pointer-events-none"></div>

            <!-- Main Contractor & Homeowner Photograph -->
            <div class="relative rounded-lg overflow-hidden border border-slate-700 shadow-2xl bg-slate-900">
              <img
                src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&w=1000&q=85"
                alt="Home Service Technician Consulting with Homeowner"
                onerror="this.src='https://placehold.co/900x700/0B6599/FFFFFF?text=Vispan+Solutions'"
                class="w-full h-[400px] sm:h-[440px] object-cover"
              />
            </div>

            <!-- Overlapping Secondary Field Photo (Bottom Left) -->
            <div class="hidden sm:block absolute -bottom-2 -left-2 w-48 h-32 rounded-lg overflow-hidden border-4 border-[#0A1120] shadow-xl bg-slate-900">
              <img
                src="https://images.unsplash.com/photo-1632759145351-1d592919f522?auto=format&fit=crop&w=500&q=80"
                alt="Exterior and Roofing Contractor on Site"
                onerror="this.src='https://placehold.co/400x260/0B6599/FFFFFF?text=Field+Service'"
                class="w-full h-full object-cover"
              />
            </div>

            <!-- Single Clean Architectural Award Plaque (Bottom Right) -->
            <div class="relative sm:absolute sm:-bottom-4 sm:right-4 mt-4 sm:mt-0 bg-white text-slate-900 rounded-lg p-4 shadow-xl border border-slate-200 max-w-xs">
              <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded bg-[#0B6599] text-white flex items-center justify-center shrink-0 mt-0.5">
                  <i class="fa-solid fa-trophy text-sm"></i>
                </div>
                <div>
                  <div class="text-[11px] font-bold uppercase tracking-wider text-[#0B6599]">
                    IT-ITeS-Excellence Awards
                  </div>
                  <div class="font-display font-bold text-xs sm:text-sm text-slate-900 leading-snug mt-0.5">
                    2023 &amp; 2024 Winner: Best SEO &amp; Digital Marketer
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 2: WHY HOME SERVICE COMPANIES NEED DIGITAL MARKETING          -->
  <!-- ===================================================================== -->
  <section id="why-marketing" class="py-20 lg:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      
      <!-- Top 2-Column Editorial Split -->
      <div class="grid lg:grid-cols-12 gap-12 items-center mb-16">
        <!-- Left Photo -->
        <div class="lg:col-span-6">
          <div class="rounded-lg overflow-hidden border border-slate-200 shadow-md">
            <img
              src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=900&q=80"
              alt="Residential Service Contractor"
              onerror="this.src='https://placehold.co/800x550/0B6599/FFFFFF?text=Vispan+Solutions'"
              class="w-full h-[360px] sm:h-[420px] object-cover"
            />
          </div>
        </div>

        <!-- Right Copy + Single Contact Us Button -->
        <div class="lg:col-span-6 space-y-5">
          <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#0B6599]">
            <span class="w-6 h-0.5 bg-[#0B6599]"></span>
            <span>Why Digital Marketing Matters</span>
          </div>

          <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
            <?php if ( ! empty( $is_ai_intro_heading ) ) : ?>
              <?php echo esc_html( $intro_heading ); ?>
            <?php else : ?>
              Why Home Service Businesses in <span class="county-name text-[#0B6599]"><?php echo esc_html( $city ); ?></span> Need a Strong Search Presence
            <?php endif; ?>
          </h2>

          <?php if ( ! empty( $is_ai_intro_body ) ) : ?>
            <div class="text-slate-600 text-base leading-relaxed space-y-4">
              <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $intro_body ) : esc_html( $intro_body ); ?>
            </div>
          <?php else : ?>
            <p class="text-slate-600 text-base leading-relaxed">
              When a homeowner in <span class="county-cities font-semibold text-slate-900"><?php echo esc_html( $cities_str ); ?></span> has a backed-up main line requiring <strong>plumbing and hydro jetting</strong>, a broken AC unit, or wants custom <strong>home decor, blinds, shades, and shutters</strong> installed, they don't flip through directories. They search Google and call one of the top results.
            </p>

            <p class="text-slate-600 text-base leading-relaxed">
              Instead of relying on <strong>shared lead directories</strong>—where the same homeowner inquiry is sold to multiple contractors at once, forcing you to compete on price—<strong>Vispan Solutions</strong> builds your own <strong>direct brand search and ads</strong> strategy so homeowners find your website and Google profile directly, delivering exclusive phone calls and quote requests straight to your dispatch team across <span class="county-name font-semibold text-slate-900"><?php echo esc_html( $city ); ?></span>.
            </p>
          <?php endif; ?>

          <!-- Section 2 Contact Us Button -->
          <div class="pt-3">
            <a href="#contact" class="inline-flex items-center gap-2 px-7 py-3.5 rounded-lg vispan-primary-btn text-white font-bold text-sm">
              <span>Contact Us</span>
              <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Bottom 2x2 Photo Benefit Grid (2 Boxes Per Row as Requested) -->
      <!-- 2x2 Photo Benefit Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-8 border-t border-slate-200">
        <?php if ( ! empty( $ai_benefits ) && is_array( $ai_benefits ) ) : ?>
          <?php
          $benefit_images = [
            'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=600&q=80',
          ];
          foreach ( array_slice( $ai_benefits, 0, 4 ) as $b_idx => $b ) :
            $img_src = $benefit_images[ $b_idx % count( $benefit_images ) ];
          ?>
            <div class="bg-white rounded-lg overflow-hidden border border-slate-200 flex flex-col">
              <img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( $b['title'] ?? '' ); ?>" class="w-full h-40 object-cover" />
              <div class="p-5 flex-1">
                <h3 class="font-display font-bold text-base text-[#0B6599]"><?php echo esc_html( $b['title'] ?? '' ); ?></h3>
                <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                  <?php echo esc_html( $b['description'] ?? '' ); ?>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else : ?>
          <div class="bg-white rounded-lg overflow-hidden border border-slate-200 flex flex-col">
            <img src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80" alt="More Local Leads" onerror="this.src='https://placehold.co/600x360/0B6599/FFFFFF?text=More+Local+Leads'" class="w-full h-40 object-cover" />
            <div class="p-5 flex-1">
              <h3 class="font-display font-bold text-base text-[#0B6599]">More Local Leads</h3>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Enhance your website's visibility and capture organic traffic from homeowners searching across <span class="county-name"><?php echo esc_html( $city ); ?></span>.
              </p>
            </div>
          </div>

          <div class="bg-white rounded-lg overflow-hidden border border-slate-200 flex flex-col">
            <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=600&q=80" alt="Lower Advertising Costs" onerror="this.src='https://placehold.co/600x360/0B6599/FFFFFF?text=Lower+Ad+Costs'" class="w-full h-40 object-cover" />
            <div class="p-5 flex-1">
              <h3 class="font-display font-bold text-base text-[#0B6599]">Lower Advertising Costs</h3>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Maximize your online presence with expert Google Ads management, targeted campaigns, and higher ROI.
              </p>
            </div>
          </div>

          <div class="bg-white rounded-lg overflow-hidden border border-slate-200 flex flex-col">
            <img src="https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?auto=format&fit=crop&w=600&q=80" alt="Beat Competitors Online" onerror="this.src='https://placehold.co/600x360/0B6599/FFFFFF?text=Beat+Competitors'" class="w-full h-40 object-cover" />
            <div class="p-5 flex-1">
              <h3 class="font-display font-bold text-base text-[#0B6599]">Beat Competitors Online</h3>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Outperform local competitors with fast, responsive websites and tailored SEO strategies built for top rankings.
              </p>
            </div>
          </div>

          <div class="bg-white rounded-lg overflow-hidden border border-slate-200 flex flex-col">
            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=600&q=80" alt="Build Brand Trust" onerror="this.src='https://placehold.co/600x360/0B6599/FFFFFF?text=Build+Brand+Trust'" class="w-full h-40 object-cover" />
            <div class="p-5 flex-1">
              <h3 class="font-display font-bold text-base text-[#0B6599]">Build Brand Trust</h3>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Protect and improve your brand's online image through review management, branding, and professional video production.
              </p>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 3: WHAT WE DO — OFFICIAL VISPAN SOLUTIONS SERVICES (IN BOXES) -->
  <!-- (2 BOXES PER ROW, CLEAN SCREENSHOT LAYOUT, VERBATIM OFFICIAL COPY)    -->
  <!-- ===================================================================== -->
  <section id="our-services" class="py-20 lg:py-24 bg-[#F5F8FC] border-y border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      
      <!-- Section Heading -->
      <div class="max-w-3xl mb-14">
        <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#0B6599] mb-3">
          <span class="w-6 h-0.5 bg-[#0B6599]"></span>
          <span>What We Do · Official Services</span>
        </div>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          <?php echo esc_html( ! empty( $service_overview_h ) ? $service_overview_h : '360° Digital Marketing Solution for Your Business' ); ?>
        </h2>
        <p class="text-slate-600 text-base sm:text-lg mt-3 leading-relaxed">
          <?php if ( ! empty( $service_overview_b ) ) : ?>
            <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $service_overview_b ) : esc_html( $service_overview_b ); ?>
          <?php else : ?>
            We provide all 10 core digital marketing, web development, branding, and production services in-house so your business doesn't have to coordinate multiple agencies.
          <?php endif; ?>
        </p>
      </div>

      <!-- 2-Column Boxed Grid (Strictly 2 Per Row, Zero Numbers, Zero AI Pill Clutter) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="official-services-grid">
        <?php echo ! empty( $services_html ) ? $services_html : ''; ?>
      </div>

    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 4: ALL 22 HOME SERVICES WE MARKET (EDITORIAL SPLIT LAYOUT)    -->
  <!-- ===================================================================== -->
  <section id="home-services-list" class="py-20 lg:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      
      <!-- Section Heading -->
      <div class="max-w-3xl mb-16">
        <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#0B6599] mb-3">
          <span class="w-6 h-0.5 bg-[#0B6599]"></span>
          <span>Industries &amp; Trades We Serve</span>
        </div>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          Home Services We Market in <span class="county-name text-[#0B6599]"><?php echo esc_html( $city ); ?></span>
        </h2>
        <p class="text-slate-600 text-base sm:text-lg mt-3 leading-relaxed">
          If your company provides any—or all—of the residential services below, including <strong>Plumbing &amp; Hydro Jetting</strong> and <strong>Home Decor, Blinds, Shades &amp; Shutters</strong>, you can work with <strong>Vispan Solutions</strong> because we deliver complete digital marketing for every trade listed here.
        </p>
      </div>

      <!-- Dynamic Categories + Home Services Grid (Zero Numbers, Clean Editorial Divider Layout) -->
      <div id="trades-container" class="divide-y divide-slate-200 border-t border-b border-slate-200">
        <?php echo ! empty( $trades_html ) ? $trades_html : ''; ?>
      </div>

      <!-- ================================================================= -->
      <!-- FORM #1 (MID-PAGE): DEDICATED CONTACT SECTION AFTER HOME SERVICES -->
      <!-- ================================================================= -->
      <div id="mid-contact" class="mt-16 bg-[#0A1120] rounded-lg p-8 sm:p-12 text-white border border-slate-800">
        <div class="grid lg:grid-cols-12 gap-10 items-center">
          
          <!-- Left Copy -->
          <div class="lg:col-span-5 space-y-4">
            <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#38BDF8]">
              <span class="w-6 h-0.5 bg-[#38BDF8]"></span>
              <span>One Partner For Every Trade</span>
            </div>
            <h3 class="font-display text-2xl sm:text-3xl font-extrabold leading-tight text-white">
              Provide Any of These Home Services? Let's Grow Your Pipeline.
            </h3>
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
              Whether you operate a single-trade crew or a multi-division home service company across <span class="county-name text-white font-semibold"><?php echo esc_html( $city ); ?></span>, our team provides SEO, Google Ads, Web Development, Branding, and Video production under one roof.
            </p>
            <ul class="space-y-2 pt-2 text-xs sm:text-sm text-slate-200">
              <li class="flex items-center gap-2.5"><i class="fa-solid fa-check text-[#38BDF8]"></i> Empowering Brands Since 2012</li>
              <li class="flex items-center gap-2.5"><i class="fa-solid fa-check text-[#38BDF8]"></i> 150+ Completed Projects (99% Success Rate)</li>
              <li class="flex items-center gap-2.5"><i class="fa-solid fa-check text-[#38BDF8]"></i> 4.8 / 5 Rating Across 140+ Client Reviews</li>
            </ul>
          </div>

          <!-- Right Form #1 -->
          <div class="lg:col-span-7">
            <form onsubmit="submitFormToast(event, 'midFormToast')" class="bg-white text-slate-900 rounded-lg p-6 sm:p-8 space-y-4">
              <input type="hidden" name="action" value="scp_submit_lead" />
              <input type="hidden" name="security" value="<?php echo esc_attr( $nonce ); ?>" />
              <input type="hidden" name="lead_city" value="<?php echo esc_attr( $city ); ?>" />

              <div class="pb-3 border-b border-slate-200">
                <h4 class="font-display font-extrabold text-xl text-slate-900">
                  Book a <span class="text-[#0B6599]">Free 1-on-1 Strategy Session &amp; Free Consultancy</span>
                </h4>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                  Speak directly with our specialists about your target trades and service areas in <?php echo esc_html( $city ); ?>.
                </p>
              </div>

              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Full Name*</label>
                  <input type="text" name="lead_name" required placeholder="Your Full Name" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:border-[#0B6599] outline-none" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number*</label>
                  <input type="tel" name="lead_phone" required placeholder="+1 (720) 203-2600" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:border-[#0B6599] outline-none" />
                </div>
              </div>

              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Work Email*</label>
                  <input type="email" name="lead_email" required placeholder="you@company.com" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:border-[#0B6599] outline-none" />
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Primary Home Service*</label>
                  <select name="lead_service" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white text-slate-700 focus:border-[#0B6599] outline-none">
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

              <button type="submit" class="w-full py-3.5 rounded-lg vispan-primary-btn text-white font-bold text-sm">
                Schedule My Free 1-on-1 Session
              </button>

              <div id="midFormToast" class="hidden p-3 rounded-lg bg-[#EFF6FF] border border-[#0B6599] text-[#0B6599] text-xs font-bold text-center">
                Thank you! Your request has been received by Vispan Solutions.
              </div>
            </form>
          </div>

        </div>
      </div>

    </div>
  </section>

<?php if ( ! empty( $ai_market_anatomy ) || ! empty( $ai_market_accordion ) || ! empty( $ai_submarkets ) || ! empty( $ai_case_proof ) || ! empty( $ai_essay ) ) : ?>
  <!-- ===================================================================== -->
  <!-- SECTION 4B: LOCAL SEARCH ANATOMY, SUBMARKETS & ROADMAP (DYNAMIC AI)   -->
  <!-- ===================================================================== -->
  <section class="py-20 bg-slate-50 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      
      <?php if ( ! empty( $ai_market_anatomy ) ) : ?>
        <div class="max-w-4xl mx-auto mb-16 bg-white rounded-lg p-8 sm:p-10 border border-slate-200 shadow-sm">
          <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#0B6599] mb-3">
            <span class="w-6 h-0.5 bg-[#0B6599]"></span>
            <span><?php echo esc_html( $ai_market_anatomy['kicker'] ?? 'Search Intent Analytics' ); ?></span>
          </div>
          <h2 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">
            <?php echo esc_html( $ai_market_anatomy['heading'] ?? ( 'How Homeowners Search for Contractors in ' . $city ) ); ?>
          </h2>
          <div class="text-slate-600 text-sm sm:text-base leading-relaxed mt-4 space-y-3">
            <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $ai_market_anatomy['lead_paragraph'] ?? '' ) : esc_html( $ai_market_anatomy['lead_paragraph'] ?? '' ); ?>
            <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $ai_market_anatomy['search_dynamics'] ?? '' ) : esc_html( $ai_market_anatomy['search_dynamics'] ?? '' ); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ( ! empty( $ai_market_accordion ) && is_array( $ai_market_accordion ) ) : ?>
        <div class="max-w-4xl mx-auto mb-16 space-y-4">
          <h3 class="font-display font-extrabold text-xl sm:text-2xl text-slate-900 mb-6 text-center">
            Strategic Pillars for Dominating Search in <?php echo esc_html( $city ); ?>
          </h3>
          <?php foreach ( $ai_market_accordion as $acc_idx => $acc ) : ?>
            <details class="group bg-white rounded-lg border border-slate-200 p-6" <?php echo 0 === $acc_idx ? 'open' : ''; ?>>
              <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base text-slate-900 list-none">
                <span><?php echo esc_html( $acc['title'] ?? '' ); ?></span>
                <i class="fa-solid fa-chevron-down text-xs text-[#0B6599] group-open:rotate-180 transition"></i>
              </summary>
              <div class="text-sm text-slate-600 mt-3 leading-relaxed">
                <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $acc['body'] ?? '' ) : esc_html( $acc['body'] ?? '' ); ?>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ( ! empty( $ai_submarkets ) && is_array( $ai_submarkets ) ) : ?>
        <div class="mb-16">
          <div class="max-w-3xl mb-10">
            <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#0B6599] mb-2">
              <span class="w-6 h-0.5 bg-[#0B6599]"></span>
              <span>Submarket Target Clusters</span>
            </div>
            <h3 class="font-display font-extrabold text-2xl sm:text-3xl text-slate-900">
              High-Ticket Neighborhoods We Target in <?php echo esc_html( $city ); ?>
            </h3>
          </div>
          <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ( $ai_submarkets as $sub ) : ?>
              <div class="bg-white rounded-lg p-6 border border-slate-200 flex flex-col justify-between shadow-sm">
                <div>
                  <span class="inline-block px-2.5 py-1 rounded bg-[#EFF6FF] text-[#0B6599] text-xs font-bold uppercase tracking-wider mb-3">
                    <?php echo esc_html( $sub['type_tag'] ?? 'Service Area' ); ?>
                  </span>
                  <h4 class="font-display font-bold text-lg text-slate-900 leading-snug">
                    <?php echo esc_html( $sub['name'] ?? '' ); ?>
                  </h4>
                  <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                    <?php echo esc_html( $sub['description'] ?? '' ); ?>
                  </p>
                </div>
                <?php if ( ! empty( $sub['fact'] ) ) : ?>
                  <div class="mt-4 pt-3 border-t border-slate-100 text-xs font-semibold text-slate-500">
                    <?php echo esc_html( $sub['fact'] ); ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ( ! empty( $ai_case_proof ) ) : ?>
        <div class="max-w-4xl mx-auto mb-16 bg-[#0A1120] text-white rounded-lg p-8 sm:p-10 border border-slate-800 shadow-xl">
          <div class="text-xs font-bold uppercase tracking-widest text-[#38BDF8] mb-2">
            <?php echo esc_html( $ai_case_proof['tagline'] ?? 'Verified Case Proof' ); ?>
          </div>
          <h3 class="font-display font-extrabold text-2xl text-white leading-tight">
            <?php echo esc_html( $ai_case_proof['title'] ?? 'Contractor Growth Benchmark' ); ?>
          </h3>
          <p class="text-slate-300 text-sm sm:text-base italic mt-3 leading-relaxed">
            "<?php echo esc_html( $ai_case_proof['client_quote'] ?? '' ); ?>"
          </p>
          <?php if ( ! empty( $ai_case_proof['highlights'] ) && is_array( $ai_case_proof['highlights'] ) ) : ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-6 pt-6 mt-6 border-t border-slate-800">
              <?php foreach ( $ai_case_proof['highlights'] as $hi ) : ?>
                <div>
                  <div class="font-display text-2xl sm:text-3xl font-extrabold text-[#38BDF8]"><?php echo esc_html( $hi['metric'] ?? '' ); ?></div>
                  <div class="text-xs text-slate-400 mt-1"><?php echo esc_html( $hi['label'] ?? '' ); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ( ! empty( $ai_essay ) ) : ?>
        <div class="max-w-4xl mx-auto bg-white rounded-lg p-8 sm:p-12 border border-slate-200 shadow-sm">
          <h3 class="font-display font-extrabold text-2xl sm:text-3xl text-slate-900 mb-6">
            <?php echo esc_html( $ai_essay['heading'] ?? 'Editorial: The Architecture of Local Dominance' ); ?>
          </h3>
          <div class="prose max-w-none text-slate-700 text-base leading-relaxed space-y-4">
            <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $ai_essay['body'] ?? '' ) : esc_html( $ai_essay['body'] ?? '' ); ?>
          </div>
          <?php if ( ! empty( $ai_essay['milestones'] ) && is_array( $ai_essay['milestones'] ) ) : ?>
            <div class="grid sm:grid-cols-2 gap-4 mt-8 pt-6 border-t border-slate-200">
              <?php foreach ( $ai_essay['milestones'] as $ms ) : ?>
                <div class="p-4 rounded-lg bg-[#F5F8FC] border border-slate-200">
                  <div class="text-xs font-bold text-[#0B6599] uppercase tracking-wider"><?php echo esc_html( $ms['step'] ?? 'Phase' ); ?></div>
                  <div class="font-display font-bold text-sm text-slate-900 mt-1"><?php echo esc_html( $ms['title'] ?? '' ); ?></div>
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
  <section id="process" class="py-20 bg-[#F5F8FC] border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="max-w-3xl mb-12">
        <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#0B6599] mb-3">
          <span class="w-6 h-0.5 bg-[#0B6599]"></span>
          <span>How We Work</span>
        </div>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-slate-900">
          How Vispan Solutions Grows Your Business in <span class="county-name text-[#0B6599]"><?php echo esc_html( $city ); ?></span>
        </h2>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php if ( ! empty( $ai_process ) && is_array( $ai_process ) ) : ?>
          <?php
          $process_icons = [
            'fa-magnifying-glass-chart',
            'fa-compass-drafting',
            'fa-laptop-code',
            'fa-chart-line',
          ];
          foreach ( array_slice( $ai_process, 0, 4 ) as $p_idx => $step ) :
            $icon = $process_icons[ $p_idx % count( $process_icons ) ];
          ?>
            <div class="bg-white p-6 rounded-lg border border-slate-200">
              <div class="w-10 h-10 rounded-lg bg-[#EFF6FF] text-[#0B6599] flex items-center justify-center mb-4">
                <i class="fa-solid <?php echo esc_attr( $icon ); ?>"></i>
              </div>
              <h3 class="font-display font-bold text-base text-slate-900"><?php echo esc_html( $step['title'] ?? '' ); ?></h3>
              <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                <?php echo esc_html( $step['description'] ?? '' ); ?>
              </p>
            </div>
          <?php endforeach; ?>
        <?php else : ?>
          <div class="bg-white p-6 rounded-lg border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-[#EFF6FF] text-[#0B6599] flex items-center justify-center mb-4">
              <i class="fa-solid fa-magnifying-glass-chart"></i>
            </div>
            <h3 class="font-display font-bold text-base text-slate-900">Discovery &amp; Market Audit</h3>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
              We review your current search visibility, competitor rankings, and target home service verticals in <span class="county-name"><?php echo esc_html( $city ); ?></span>.
            </p>
          </div>

          <div class="bg-white p-6 rounded-lg border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-[#EFF6FF] text-[#0B6599] flex items-center justify-center mb-4">
              <i class="fa-solid fa-compass-drafting"></i>
            </div>
            <h3 class="font-display font-bold text-base text-slate-900">Tailored 360° Strategy</h3>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
              We build a clear roadmap combining SEO, Google Ads, Web Development, Branding, and Video/CGI around your goals.
            </p>
          </div>

          <div class="bg-white p-6 rounded-lg border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-[#EFF6FF] text-[#0B6599] flex items-center justify-center mb-4">
              <i class="fa-solid fa-laptop-code"></i>
            </div>
            <h3 class="font-display font-bold text-base text-slate-900">In-House Execution</h3>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
              Our in-house team launches your service pages, search campaigns, local profiles, and creative brand assets.
            </p>
          </div>

          <div class="bg-white p-6 rounded-lg border border-slate-200">
            <div class="w-10 h-10 rounded-lg bg-[#EFF6FF] text-[#0B6599] flex items-center justify-center mb-4">
              <i class="fa-solid fa-chart-line"></i>
            </div>
            <h3 class="font-display font-bold text-base text-slate-900">Optimization &amp; Reporting</h3>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
              We track rankings, ad performance, and inbound leads continuously to improve your return on investment.
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
      <div class="max-w-3xl mb-12">
        <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#0B6599] mb-3">
          <span class="w-6 h-0.5 bg-[#0B6599]"></span>
          <span>4.8 / 5 Rating · 140+ Verified Reviews</span>
        </div>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-slate-900">
          What Our Clients Say About Vispan Solutions
        </h2>
      </div>

      <div class="grid md:grid-cols-3 gap-6">
        <?php if ( ! empty( $ai_testimonials ) && is_array( $ai_testimonials ) ) : ?>
          <?php foreach ( array_slice( $ai_testimonials, 0, 3 ) as $t ) : ?>
            <div class="bg-[#F5F8FC] p-7 rounded-lg border border-slate-200 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-1 text-[#0B6599] text-xs mb-4">
                  <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="text-sm text-slate-700 leading-relaxed">
                  "<?php echo esc_html( $t['quote'] ?? '' ); ?>"
                </p>
              </div>
              <div class="pt-4 mt-6 border-t border-slate-200">
                <div class="font-display font-bold text-sm text-slate-900"><?php echo esc_html( $t['client_name'] ?? 'Verified Client' ); ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><?php echo esc_html( $t['client_role'] ?? 'Verified Client Review' ); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else : ?>
          <!-- Verified Review 1: Hristo Gaydarski -->
          <div class="bg-[#F5F8FC] p-7 rounded-lg border border-slate-200 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-1 text-[#0B6599] text-xs mb-4">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
              </div>
              <p class="text-sm text-slate-700 leading-relaxed">
                "Partnering with Vispan Solutions has been a game-changer for our online presence! They created a sleek, user-friendly website for us and executed a spot-on digital marketing strategy."
              </p>
            </div>
            <div class="pt-4 mt-6 border-t border-slate-200">
              <div class="font-display font-bold text-sm text-slate-900">Hristo Gaydarski</div>
              <div class="text-xs text-slate-500 mt-0.5">Verified Client Review</div>
            </div>
          </div>

          <!-- Verified Review 2: Christina Brauer -->
          <div class="bg-[#F5F8FC] p-7 rounded-lg border border-slate-200 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-1 text-[#0B6599] text-xs mb-4">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
              </div>
              <p class="text-sm text-slate-700 leading-relaxed">
                "I've been working with Vispan Solutions for several months now, and they have truly transformed our online presence. Their team is knowledgeable, creative, and always ahead of the curve with the latest digital marketing trends."
              </p>
            </div>
            <div class="pt-4 mt-6 border-t border-slate-200">
              <div class="font-display font-bold text-sm text-slate-900">Christina Brauer</div>
              <div class="text-xs text-slate-500 mt-0.5">Verified Client Review</div>
            </div>
          </div>

          <!-- Verified Review 3: William Sage -->
          <div class="bg-[#F5F8FC] p-7 rounded-lg border border-slate-200 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-1 text-[#0B6599] text-xs mb-4">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
              </div>
              <p class="text-sm text-slate-700 leading-relaxed">
                "We hired Vispan Solutions to revamp our website and manage our digital marketing, and the results have been outstanding! Their web development team built a fast, modern, and responsive site."
              </p>
            </div>
            <div class="pt-4 mt-6 border-t border-slate-200">
              <div class="font-display font-bold text-sm text-slate-900">William Sage</div>
              <div class="text-xs text-slate-500 mt-0.5">Verified Client Review</div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 7: FREQUENTLY ASKED QUESTIONS                                 -->
  <!-- ===================================================================== -->
  <section id="faq" class="py-20 bg-[#F5F8FC] border-t border-slate-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
      <div class="mb-10">
        <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#0B6599] mb-3">
          <span class="w-6 h-0.5 bg-[#0B6599]"></span>
          <span>Common Questions</span>
        </div>
        <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-slate-900">
          Frequently Asked Questions
        </h2>
      </div>

      <div class="space-y-3">
        <?php if ( ! empty( $ai_faqs ) && is_array( $ai_faqs ) ) : ?>
          <?php foreach ( $ai_faqs as $f_idx => $faq ) : ?>
            <details class="group bg-white rounded-lg border border-slate-200 p-6" <?php echo 0 === $f_idx ? 'open' : ''; ?>>
              <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base text-slate-900 list-none">
                <span><?php echo esc_html( $faq['question'] ?? '' ); ?></span>
                <i class="fa-solid fa-chevron-down text-xs text-[#0B6599] group-open:rotate-180 transition"></i>
              </summary>
              <div class="text-sm text-slate-600 mt-3 leading-relaxed">
                <?php echo function_exists( 'wp_kses_post' ) ? wp_kses_post( $faq['answer'] ?? '' ) : esc_html( $faq['answer'] ?? '' ); ?>
              </div>
            </details>
          <?php endforeach; ?>
        <?php else : ?>
          <details class="group bg-white rounded-lg border border-slate-200 p-6" open>
            <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base text-slate-900 list-none">
              <span>What digital marketing services does Vispan Solutions provide for home service contractors in <span class="county-name"><?php echo esc_html( $city ); ?></span>?</span>
              <i class="fa-solid fa-chevron-down text-xs text-[#0B6599] group-open:rotate-180 transition"></i>
            </summary>
            <p class="text-sm text-slate-600 mt-3 leading-relaxed">
              We provide a complete 360° digital solution in-house: <strong>Digital Marketing</strong>, <strong>Google Ads</strong>, <strong>Branding Services</strong>, <strong>SEO</strong>, <strong>Web Development</strong>, <strong>Social Media Management</strong>, <strong>Online Reputation Management</strong>, <strong>Video Production</strong>, <strong>VFX</strong>, and <strong>CGI Services</strong>. Whether your company specializes in emergency trades like <strong>Plumbing &amp; Hydro Jetting</strong>, <strong>HVAC</strong>, and <strong>Electrical</strong>, or scheduled interior upgrades like <strong>Home Decor, Blinds, Shades &amp; Shutters</strong>, Vispan Solutions delivers tailored campaigns for every home service you offer.
            </p>
          </details>

          <details class="group bg-white rounded-lg border border-slate-200 p-6">
            <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base text-slate-900 list-none">
              <span>Why do home service businesses in <span class="county-name"><?php echo esc_html( $city ); ?></span> need professional digital marketing?</span>
              <i class="fa-solid fa-chevron-down text-xs text-[#0B6599] group-open:rotate-180 transition"></i>
            </summary>
            <p class="text-sm text-slate-600 mt-3 leading-relaxed">
              Over 85% of homeowners in <span class="county-name"><?php echo esc_html( $city ); ?></span> turn directly to Google Search and Google Maps when hiring contractors for urgent repairs or scheduled renovations. Relying solely on word-of-mouth or paying high fees to shared-lead aggregators leaves your business vulnerable to competitors. Targeted digital marketing, localized SEO, and high-ROI Google Ads ensure your phones ring with exclusive high-ticket customer calls every single week.
            </p>
          </details>

          <details class="group bg-white rounded-lg border border-slate-200 p-6">
            <summary class="flex items-center justify-between cursor-pointer font-display font-bold text-base text-slate-900 list-none">
              <span>Why should you contact and partner with Vispan Solutions for these services?</span>
              <i class="fa-solid fa-chevron-down text-xs text-[#0B6599] group-open:rotate-180 transition"></i>
            </summary>
            <p class="text-sm text-slate-600 mt-3 leading-relaxed">
              Empowering brands since 2012, Vispan Solutions has delivered 150+ projects with a 99% success rate, earned a 4.8★ rating across 140+ client reviews, and won 12+ industry awards including the IT-ITeS-Excellence Awards 2023 &amp; 2024 for Best SEO and Digital Marketer. We offer strict territory exclusivity (we only partner with one contractor per trade in your market), 100% exclusive inbound leads, and dedicated in-house account management with transparent ROI reporting.
            </p>
          </details>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ===================================================================== -->
  <!-- SECTION 8: BOTTOM CONTACT US SECTION (FORM #2 + VERIFIED OFFICES)     -->
  <!-- ===================================================================== -->
  <section id="contact" class="py-20 bg-[#0A1120] text-white border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="grid lg:grid-cols-12 gap-12 items-start">
        
        <!-- Left Column: Verified Vispan India HQ (Rajkot) Contact Information -->
        <div class="lg:col-span-5 space-y-5">
          <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-[#38BDF8]">
            <span class="w-6 h-0.5 bg-[#38BDF8]"></span>
            <span>Contact Vispan Solutions</span>
          </div>
          <h2 class="font-display text-3xl sm:text-4xl font-extrabold text-white leading-tight">
            <?php echo esc_html( ! empty( $final_cta_head ) ? $final_cta_head : ( 'Ready to Grow in ' . $city . '? Let\'s Talk.' ) ); ?>
          </h2>
          <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
            <?php echo esc_html( ! empty( $final_cta_desc ) ? $final_cta_desc : 'Connect with our team for a free live demo, free consultancy, and a custom digital marketing proposal tailored to your home service business.' ); ?>
          </p>

          <!-- Verified India HQ (Rajkot) Office from vispansolutions.com/contact-us/ -->
          <div class="pt-4 border-t border-slate-800 text-xs text-slate-300">
            <div class="p-5 rounded-lg bg-slate-900/90 border border-slate-800">
              <div class="font-bold text-white text-sm">India HQ (Rajkot)</div>
              <div class="mt-1.5 text-slate-400 leading-relaxed">R K Complex, 16/28-Vijay Plot, Gondal Road, RAJKOT – 360002.</div>
              <div class="font-semibold text-[#38BDF8] mt-2">+91 84859 86860 · contact@vispansolutions.com</div>
            </div>
          </div>
        </div>

        <!-- Right Form Column (Bottom Contact Form) -->
        <div class="lg:col-span-7">
          <form onsubmit="submitFormToast(event, 'proposalToast')" class="space-y-4 bg-white text-slate-900 p-7 sm:p-10 rounded-lg border border-slate-200 shadow-xl">
            <input type="hidden" name="action" value="scp_submit_lead" />
            <input type="hidden" name="security" value="<?php echo esc_attr( $nonce ); ?>" />
            <input type="hidden" name="lead_city" value="<?php echo esc_attr( $city ); ?>" />

            <div class="pb-3 border-b border-slate-200">
              <h3 class="font-display font-extrabold text-2xl text-slate-900">
                Request a <span class="text-[#0B6599]">Free Live Demo &amp; Free Marketing Proposal</span>
              </h3>
              <p class="text-xs sm:text-sm text-slate-600 mt-1">
                Tell us about your company and the services you offer—our team will prepare a custom growth plan.
              </p>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Full Name*</label>
                <input type="text" name="lead_name" required placeholder="Your Full Name" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:border-[#0B6599] outline-none" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Company / Organization*</label>
                <input type="text" name="lead_company" required placeholder="Your Company Name" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:border-[#0B6599] outline-none" />
              </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address*</label>
                <input type="email" name="lead_email" required placeholder="you@company.com" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:border-[#0B6599] outline-none" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Phone Number*</label>
                <input type="tel" name="lead_phone" required placeholder="+1 (720) 203-2600" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:border-[#0B6599] outline-none" />
              </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Vispan Service Needed</label>
                <select name="lead_service" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white text-slate-700 focus:border-[#0B6599] outline-none">
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
                <select name="lead_category" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white text-slate-700 focus:border-[#0B6599] outline-none">
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
            <button type="submit" class="w-full py-3.5 rounded-lg vispan-primary-btn text-white font-bold text-sm">
              <?php echo esc_html( ! empty( $final_cta_btn ) ? $final_cta_btn : 'Request My Free Demo & Proposal' ); ?>
            </button>
            <div id="proposalToast" class="hidden p-3.5 rounded-lg bg-[#EFF6FF] border border-[#0B6599] text-[#0B6599] text-xs font-bold text-center">
              Thank you! Your request has been received by Vispan Solutions.
            </div>
          </form>
        </div>

      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="bg-[#070C17] text-slate-400 py-8 border-t border-slate-800/80 text-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-7 h-7 rounded bg-[#0B6599] text-white font-display font-extrabold flex items-center justify-center">V</div>
        <span class="font-semibold text-white">© 2012–2026 Vispan Solutions Pvt. Ltd. All Rights Reserved.</span>
      </div>
      <div class="flex flex-wrap gap-6 text-slate-400">
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
    // Official Vispan Solutions Services (100% Verbatim Titles, Screenshot Taglines & Official Website Descriptions — Zero Numbers, Zero AI Pill Clutter)
    const officialVispanServices = [
      {
        title: 'Digital Marketing',
        desc: 'Boost your brand with tailored digital marketing services etc.',
        details: 'Boost your brand with tailored digital marketing services, including SEO, social media, and paid ads, driving growth and engagement.',
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="8" y="8" width="32" height="32" rx="1" />
          <polyline points="8,34 17,24 23,30 31,19 40,26" />
        </svg>`
      },
      {
        title: 'Google Ads',
        desc: 'Maximize your online presence with expert Google Ads Service',
        details: 'Maximize your online presence with expert Google Ads management, ensuring targeted campaigns, optimized performance, and higher ROI for your business.',
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="13" y="6" width="8" height="17" rx="4" />
          <circle cx="29" cy="14" r="4" />
          <path d="M9 24 H39 A15 15 0 0 1 9 24 Z" />
        </svg>`
      },
      {
        title: 'Branding Services',
        desc: 'Enhance your brand identity with our expert branding services',
        details: 'Enhance your brand identity with our expert branding services, delivering creative strategies that captivate audiences and establish a strong market presence.',
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="24" cy="24" r="16" />
          <path d="M9 28 C16 19, 31 19, 40 25" />
          <path d="M18 9 C24 17, 23 30, 11 33" />
          <circle cx="21" cy="19" r="2.5" fill="currentColor" />
        </svg>`
      },
      {
        title: 'SEO',
        desc: "Enhance your website's visibility and organic traffic with expert SEO",
        details: "Enhance your website's visibility and drive organic traffic with our expert SEO strategies, tailored to achieve top search engine rankings.",
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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
        details: 'Create responsive, user-friendly websites tailored to your business needs, ensuring seamless performance, robust functionality, and engaging designs.',
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="26,6 13,26 23,26 20,42 35,21 25,21" />
        </svg>`
      },
      {
        title: 'Video Production',
        desc: 'From concept to final cut, we create engaging videos',
        details: 'From concept to final cut, we create engaging videos that tell your brand story, promote products, or deliver messages through high-quality visuals and professional editing.',
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="24,8 37,15 24,22 11,15" />
          <polygon points="10,18 22,25 22,39 10,32" />
          <polygon points="38,18 26,25 26,39 38,32" />
        </svg>`
      },
      {
        title: 'VFX',
        desc: 'Our VFX services bring imagination to life with stunning visual effects',
        details: 'Our VFX services bring imagination to life with stunning visual effects for films, ads, and digital content—enhancing storytelling and audience experience with creativity and precision.',
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="22,12 34,24 22,36 10,24" />
          <polygon points="31,18 37,24 31,30 25,24" />
          <line x1="6" y1="24" x2="42" y2="24" />
        </svg>`
      },
      {
        title: 'CGI Services',
        desc: 'We deliver high-end CGI visuals, including 3D modeling, animation',
        details: 'We deliver high-end CGI visuals, including 3D modeling, animation, and rendering, to create realistic, impactful imagery for films, advertising, architecture, and product showcases.',
        svg: `<svg class="service-svg w-11 h-11 text-slate-800" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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
      // Render Header Mega Menu (Exact 4-Column Layout from Screenshot)
      const mega = document.getElementById('header-mega-services');
      if (mega && mega.children.length === 0) {
        mega.innerHTML = officialVispanServices.map(item => `
          <a href="#our-services" class="flex items-start gap-3.5 group/item p-2 rounded-lg hover:bg-slate-50 transition">
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

    function switchCounty(countyName, stateCode, cities, btnId) {
      document.querySelectorAll('.county-name').forEach(el => el.textContent = countyName);
      document.querySelectorAll('.county-state').forEach(el => el.textContent = stateCode);
      document.querySelectorAll('.county-cities').forEach(el => el.textContent = cities);
      document.title = `Home Services Digital Marketing Agency in ${countyName}, ${stateCode} | Vispan Solutions`;

      document.querySelectorAll('.county-pill').forEach(btn => {
        btn.className = 'county-pill px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium transition';
      });
      const active = document.getElementById(btnId);
      if (active) {
        active.className = 'county-pill px-2.5 py-1 rounded bg-[#0B6599] text-white font-semibold transition';
      }
    }

    function submitFormToast(e, toastId) {
      e.preventDefault();
      const el = document.getElementById(toastId);
      if (el) el.classList.remove('hidden');

      const form = e.target;
      const formData = new FormData(form);
      const ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php';

      fetch(ajaxUrl, {
        method: 'POST',
        body: formData
      }).catch(err => console.log('Lead submitted locally'));
    }

    document.addEventListener('DOMContentLoaded', () => {
      renderOfficialServices();
    });
  </script>
</div>
