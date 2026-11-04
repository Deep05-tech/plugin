# SEO City Pages

> **High-Performance Programmatic SEO & AI Content Engine for WordPress & Elementor**

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)
[![Elementor Compatible](https://img.shields.io/badge/Elementor-3.0%2B-red.svg)](https://elementor.com)
[![OpenAI](https://img.shields.io/badge/AI-OpenAI%20GPT--4o-green.svg)](https://openai.com)
[![License](https://img.shields.io/badge/License-GPLv2%2B-orange.svg)](LICENSE)

**SEO City Pages** is an enterprise-grade WordPress plugin engineered for programmatic SEO, automated local client acquisition landing pages, and AI-powered editorial content generation. Built specifically for marketing agencies, multi-location brands, and service businesses, the plugin enables scalable deployment of thousands of location-specific landing pages without bloating the WordPress `wp_posts` table or degrading site speed.

---

## 🌟 Key Features

### 1. Programmatic Location Hierarchy & Fast Routing
* **Hierarchical Taxonomy**: Structured relational database model covering **Countries → States → Cities/Counties → Services**.
* **Zero Rewrite-Rule Bloat**: High-performance $O(1)$ custom route resolver bypassing WordPress's monolithic rewrite rule array.
* **Deterministic URL Patterns**: Full support for hierarchical and flexible URL patterns:
  * `/{country}/{state}/{city}/{service}/` (e.g. `/us/california/los-angeles/home-services/`)
  * `/us/best-digital-marketing-agency-for-{service}-in-{city}/`
  * `/{service}/{city}/`

### 2. AI Content Engine (OpenAI GPT-4o / GPT-4o-mini)
* **Structured Output Generation**: Generates comprehensive, long-form landing page content strictly structured as typed JSON.
* **B2B Marketing Perspective**: Pre-engineered to position Vispan Solutions as the growth agency delivering specialized marketing services to local practitioners and contractors.
* **Anti-Hallucination & Quality Validator**: Enforces strict geographic bounds, detects placeholders (e.g. `[City]`), validates local relevance, and sanitizes dangerous scripts/XSS.
* **Immutable Versioning Lifecycle**: Full content revision management (`needs_review` → `approved` → `published`) with single-click rollbacks ($v_{N+1}$ immutable records).

### 3. Enterprise Elementor Dynamic Hydration
* **Single Master Template Engine**: Power thousands of dynamic city landing pages using a single Elementor Library template.
* **Dynamic Tags & Replacement Tokens**: Inject dynamic context at runtime without cloning Elementor JSON documents:
  * `{{seo_city_name}}`, `{{seo_city_state}}`, `{{seo_city_country}}`
  * `{{seo_hero_title}}`, `{{seo_hero_description}}`, `{{seo_hero_subtitle}}`
  * `{{seo_intro_heading}}`, `{{seo_intro_body}}`
  * `{{seo_service_overview_heading}}`, `{{seo_service_overview_body}}`
  * `{{seo_benefits}}`, `{{seo_process}}`, `{{seo_faqs}}`

### 4. High-Converting Home Services & Multi-Trade Hub
* **Specialized Contractor Landing Page Engine**: Dedicated multi-trade layout optimized for residential and commercial contractors.
* **10 In-House Marketing Services**: 3-column responsive grid showcasing Digital Marketing, Google Ads, Branding, SEO, Web Development, Social Media, Reputation Management, Video Production, VFX, and CGI.
* **22 Contractor Trade Verticals**: Interactive tab filtering across 6 service categories:
  * Core & Emergency (Plumbing, HVAC, Electrical)
  * Exterior & Structural (Roofing, Siding, Gutters, Masonry)
  * Interior & Handyman (Painting, Flooring, Carpentry)
  * Outdoor & Yard Care (Landscaping, Pest Control, Pool, Snow Removal)
  * Cleaning & Sanitation (Housekeeping, Junk Removal, Window Cleaning)
  * Safety & Solar (Security Systems, Locksmith, Solar Power)
* **AJAX Lead & Inquiry Forms**: Double lead-capture forms with CSRF verification, automated honeypot spam protection (`inquiry_hp_check`), and email notifications.

### 5. Technical SEO & Schema Automation
* **Automated Geo-Targeted Schema**: Generates compliant Schema.org JSON-LD (`LocalBusiness`, `Service`, `Organization`) with precise coordinates and geo-targeting.
* **SEO Plugin Compatibility**: Native adapters for **Yoast SEO**, **Rank Math**, and **All in One SEO (AIOSEO)**, with native fallback meta tags.
* **Duplicate Content & Cannibalization Shield**: SHA-256 fingerprinting for titles, descriptions, and content bodies with automated duplicate detection.
* **Native XML Sitemap Provider**: High-scale, chunked XML sitemaps excluding drafts and noindex pages.

### 6. Bulk Operations & Scale Hardening
* **CSV Import / Export**: Streamed processing for large datasets with formula injection protection (`=`, `+`, `-`, `@`).
* **County Batch Generator**: Automated campaign runner to queue and generate hundreds of county-targeted landing pages in background batches.
* **WordPress Pages Synchronization**: Automated mirroring of live city pages into `wp_posts` with custom post state indicators for seamless admin discoverability.

---

## 🏗️ Architecture & Database Model

The plugin installs 16 dedicated, indexed relational tables using WordPress `dbDelta`:

| Table | Purpose |
|---|---|
| `wp_seo_countries` | Country ISO codes, names, and slugs |
| `wp_seo_states` | States / provinces tied to countries |
| `wp_seo_cities` | Cities and counties with coordinates and local industry metadata |
| `wp_seo_services` | Service taxonomy and default copy prompts |
| `wp_seo_city_pages` | Canonical programmatic landing page records and published version pointer |
| `wp_seo_content_versions` | Immutable AI and manual content revisions |
| `wp_seo_keyword_bank` | Global keyword bank and localized search terms |
| `wp_seo_county_campaigns` | Batch generation campaign tracking |
| `wp_seo_bulk_jobs` / `_items`| Atomic queue runner with retry thresholds |
| `wp_seo_quality_evaluations`| Content quality scores, thin content flags, and keyword density audits |
| `wp_seo_audit_logs` | Admin action and publishing audit trails |

---

## 🚀 Installation & Setup

### Requirements
* **WordPress**: 6.0 or higher
* **PHP**: 7.4 or higher (PHP 8.1+ recommended)
* **MySQL**: 5.7+ or MariaDB 10.3+
* **Elementor**: (Optional, for Elementor master template rendering)

### Quick Start
1. **Download & Upload**:
   * Build the plugin package or use `seo-city-pages.zip`.
   * Navigate to **WordPress Admin → Plugins → Add New → Upload Plugin**.
   * Choose `seo-city-pages.zip` and click **Install Now**, then **Activate**.
2. **Configure Settings**:
   * Go to **SEO City Pages → Settings**.
   * Enter your **OpenAI API Key** and select your preferred model (`gpt-4o` or `gpt-4o-mini`).
   * Configure Company Profile, Tone of Voice, and Default SEO Title templates.
3. **Import Locations & Services**:
   * Navigate to **SEO City Pages → Import** to upload locations via CSV or use the sample templates in `/samples/`.
4. **Generate & Publish**:
   * Review pending pages under **SEO City Pages → All Pages**.
   * Run AI generation individually or use **Bulk Operations** to batch-generate content.
   * Approve and publish versions to make them live.

---

## 🧩 Shortcodes & Tokens Reference

### Shortcodes
* `[seo_city_page id="123"]` — Renders the dynamic content for a specific City Page ID.
* `[seo_home_services_trades]` — Renders the interactive 22 contractor trade cards grid.
* `[seo_trade_directory]` — Alias for the multi-trade directory layout.
* `[vispan_inquiry_form]` — Renders the AJAX-enabled contractor lead generation form.

### Elementor Master Template Tokens
* Identity: `{{city}}`, `{{state}}`, `{{country}}`, `{{service}}`, `{{url}}`
* AI Hero: `{{seo_hero_title}}`, `{{seo_hero_description}}`, `{{seo_hero_subtitle}}`
* AI Overview: `{{seo_intro_heading}}`, `{{seo_intro_body}}`, `{{seo_service_overview_heading}}`
* AI Lists: `{{seo_benefits}}`, `{{seo_process}}`, `{{seo_faqs}}`, `{{seo_testimonials}}`

---

## 🧪 Automated Testing

The plugin includes an extensive test suite verifying database migrations, relational integrity, AI prompt injection defenses, Elementor hydration, and bulk job concurrency.

Run all test suites locally:
```bash
for test in tests/*.php; do
    php "$test" || exit 1
done
```

---

## 📁 Repository Structure

```
├── assets/                  # Frontend & admin CSS, JavaScript, and SVGs
├── docs/                    # Architectural specs and engineering documentation
├── includes/
│   ├── AI/                  # Prompt builder, OpenAI provider, and content validators
│   ├── Admin/               # Admin dashboard, settings, and bulk operation controllers
│   ├── Database/            # Schema installer, migrations, and repositories
│   ├── Elementor/           # Elementor tags, tokens, and master templates
│   ├── Frontend/            # AJAX inquiry handler and security checks
│   ├── Integration/         # WordPress core post synchronization
│   ├── Logging/             # Structured audit logging
│   ├── Security/            # Nonce verification, capabilities, and sanitization
│   ├── SEO/                 # JSON-LD Schema, robots meta, and plugin adapters
│   └── URL/                 # Request resolver and O(1) routing engine
├── samples/                 # Sample CSV imports and reference templates
├── templates/               # Admin view templates
├── tests/                   # 16 automated PHP test suites
├── seo-city-pages.php       # Primary WordPress plugin bootstrap file
└── uninstall.php            # Clean database and options uninstaller
```

---

## 📄 License & Attribution

* **Author**: Vispan Solutions & SEO City Pages Team
* **License**: GPLv2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
