# Architectural Decisions Record (ADR) — SEO City Pages

**Plugin:** SEO City Pages  
**Workspace:** `/home/uday/Agents/SEO Plugin`  
**Phase:** Phase 1 — Environment Audit & Architecture  
**Status:** Approved for Implementation Planning  

---

## Index of Decisions

1. [ADR-001: Hybrid Data Model (Custom Post Type + Custom Database Tables)](#adr-001-hybrid-data-model-custom-post-type--custom-database-tables)
2. [ADR-002: Single-Template Dynamic Hydration vs Static Page Duplication](#adr-002-single-template-dynamic-hydration-vs-static-page-duplication)
3. [ADR-003: Zero-Theme Modification Isolation Strategy for Arolax](#adr-003-zero-theme-modification-isolation-strategy-for-arolax)
4. [ADR-004: Official Elementor Dynamic Tags API + Runtime Token Filter Pipeline](#adr-004-official-elementor-dynamic-tags-api--runtime-token-filter-pipeline)
5. [ADR-005: 5-Tier Waterfall Content Inheritance Model](#adr-005-5-tier-waterfall-content-inheritance-model)
6. [ADR-006: Bundled Action Scheduler for Bulk Queue Processing](#adr-006-bundled-action-scheduler-for-bulk-queue-processing)
7. [ADR-007: Provider-Agnostic Strategy Pattern for AI Engine](#adr-007-provider-agnostic-strategy-pattern-for-ai-engine)
8. [ADR-008: Chunked Custom XML Sitemap Provider](#adr-008-chunked-custom-xml-sitemap-provider)
9. [ADR-009: Strict REST API & Nonce Security Hardening with Formula Injection Protection](#adr-009-strict-rest-api--nonce-security-hardening-with-formula-injection-protection)
10. [ADR-010: PSR-4 Modular Namespaced Code Architecture](#adr-010-psr-4-modular-namespaced-code-architecture)

---

## ADR-001: Hybrid Data Model (Custom Post Type + Custom Database Tables)

### Decision
Use a **Hybrid Data Model**: A lightweight WordPress Custom Post Type (`seo_city_page`) acts as the URL routing anchor, sitemap indexable, and standard WordPress post entity, while all normalized geographic data (countries, states, cities), service metadata, content blocks, generation jobs, and audit logs are stored in dedicated custom indexed database tables (`wp_seo_cities`, `wp_seo_services`, `wp_seo_content_blocks`, etc.).

### Reason
Pure WordPress post meta (`wp_postmeta`) stores key-value pairs as unindexed longstrings without relational integrity. When generating 10,000+ pages with ~25 meta fields each, `wp_postmeta` balloons by 250,000+ rows, causing query degradation, memory exhaustion, and sluggish joins across WordPress admin.

### Alternatives Considered
1. **Pure CPT + Post Meta**: Storing every city, state, and content block as standard `wp_postmeta`.
2. **Pure Custom Database Tables (Virtual Pages Only)**: Bypassing WordPress posts completely and routing everything via raw rewrite rules without inserting rows into `wp_posts`.

### Why the Selected Approach is Preferred
- **Superior to Pure Post Meta**: Normalized tables with composite B-Tree indexes provide sub-millisecond lookups even with 50,000+ cities and services, keeping `wp_postmeta` clean.
- **Superior to Pure Custom Tables (Virtual Pages)**: Custom post type entries allow seamless, out-of-the-box integration with Yoast SEO, Rank Math, XML sitemaps, breadcrumbs, core WordPress query caching, and the WordPress trash/revision lifecycle without inventing fragile query-interception hacks.

---

## ADR-002: Single-Template Dynamic Hydration vs Static Page Duplication

### Decision
Render all city landing pages for a given service dynamically from **One Reusable Elementor Master Template** (`elementor_library` ID) by injecting runtime context (`Rajkot`, `Ahmedabad`, `Surat`) during request execution, rather than cloning or compiling separate Elementor JSON blobs for every page.

### Reason
Elementor pages store serialized JSON layout trees with unique widget IDs. Duplicating an Elementor layout 10,000 times would consume 5+ GB of database storage, require 10,000 individual CSS files on disk, and make global design updates impossible without running a mass batch rebuild.

### Alternatives Considered
1. **Static JSON Cloning**: Duplicating the entire Elementor JSON layout tree for each city page and replacing tokens directly in the serialized JSON.
2. **Elementor Kit / Loop Builder**: Relying solely on standard Elementor archive loop templates.

### Why the Selected Approach is Preferred
- **Instant Design Updates**: Modifying a master template in Elementor immediately updates all 1,000–50,000 associated city pages across the entire website with zero re-rendering delay.
- **Minimal Storage Overhead**: Storage per page drops from ~500 KB to ~5 KB.
- **Asset Efficiency**: Only one compiled Elementor CSS stylesheet is loaded for the master template, maximizing browser caching and HTTP/2 multiplexing.

---

## ADR-003: Zero-Theme Modification Isolation Strategy for Arolax

### Decision
The plugin interacts with the Arolax theme exclusively through standard WordPress template hooks (`template_include`, `wp_head`, `wp_body_open`, `wp_footer`, `get_header`, `get_footer`, `the_content`) and Elementor canvas filters. **Zero files inside `/wp-content/themes/arolax/` will ever be touched, modified, or required.**

### Reason
Modifying parent theme files causes fatal site breaks whenever the theme is updated by the author or via WordPress auto-updates.

### Alternatives Considered
1. **Editing Arolax Template Files**: Adding custom city page template files directly into `/wp-content/themes/arolax/`.
2. **Creating an Arolax Child Theme**: Forcing the user to switch to a custom child theme.

### Why the Selected Approach is Preferred
- **100% Theme Update Immunity**: The plugin survives all past and future Arolax theme updates.
- **Universal Compatibility**: If the user switches themes from Arolax to Astra, GeneratePress, or Hello Elementor in the future, the SEO City Pages plugin will continue functioning without code breakage.

---

## ADR-004: Official Elementor Dynamic Tags API + Runtime Token Filter Pipeline

### Decision
Implement data injection through official Elementor Dynamic Tag classes (`\Elementor\Core\DynamicTags\Tag`) registered via `elementor/dynamic_tags/register`, complemented by an `elementor/widget/render_content` runtime token parser for bracket tokens (`{{city}}`, `{{service}}`, etc.).

### Reason
Using official Elementor dynamic tag APIs ensures native compatibility with Elementor's visual controls, theme style engine, preview frames, and future Elementor 4.x / 5.x releases.

### Alternatives Considered
1. **Direct Regex Replacement on Rendered HTML**: Running a raw `preg_replace` on the entire HTML buffer before output.
2. **Shortcodes Only**: Forcing the administrator to write `[seo_city field="city"]` everywhere instead of Elementor tags.

### Why the Selected Approach is Preferred
- **Native Editor UX**: Designers can select dynamic tags directly from Elementor's native widget UI input fields.
- **HTML Safety**: Token processing is scoped to widget render output, preventing corruption of JSON-LD scripts, inline JavaScript, or CSS class names.

---

## ADR-005: 5-Tier Waterfall Content Inheritance Model

### Decision
Implement a 5-tier content fallback hierarchy:  
`Global Default` → `Service Level` → `City Level` → `Service + City Matrix` → `Individual Page Override`.

### Reason
Different landing page elements have different requirements. For example, company trust badges are Global, service descriptions are Service-specific, landmark mentions are City-specific, and specific local case studies are Service+City specific.

### Alternatives Considered
1. **Flat Content Matrix**: Requiring every city-service combination to define all 15 content blocks independently.
2. **Two-Tier System (Global + Override only)**: Only having global text with manual overrides.

### Why the Selected Approach is Preferred
- Eliminates 90% of redundant data entry.
- Enables instant broad updates (e.g. updating pricing across an entire service category in one click) while preserving granular local customization.

---

## ADR-006: Bundled Action Scheduler for Bulk Queue Processing

### Decision
Bundle `woocommerce/action-scheduler` as an isolated, self-contained background task runner for all bulk generation, CSV imports, and AI batch operations, with an automatic fallback to standard WP-Cron.

### Reason
Bulk generating 10,000 pages or calling AI APIs in a single HTTP request causes PHP execution timeouts (504 Gateway Timeout), memory exhaustion, and server crashes. Standard WP-Cron lacks transaction safety, progress tracking, and robust retry mechanisms under high volume.

### Alternatives Considered
1. **Synchronous Browser AJAX Loops**: Keeping the browser tab open and firing AJAX requests one by one.
2. **Raw WP-Cron**: Using standard `wp_schedule_single_event()`.

### Why the Selected Approach is Preferred
- **Enterprise Reliability**: Action Scheduler processes hundreds of thousands of tasks concurrently, provides database row locking, batch chunking, failure logging, and retry queues.
- **Headless Execution**: Administrators can initiate a 20,000-page generation job, close their browser, and return later to view real-time completion status in the Job Queue UI.

---

## ADR-007: Provider-Agnostic Strategy Pattern for AI Engine

### Decision
Implement an `AI_Provider_Interface` and separate concrete adapter classes (`OpenAI_Provider`, `Gemini_Provider`, `Anthropic_Provider`) managed by a central `AI_Manager`.

### Reason
AI models and APIs evolve rapidly. Hardcoding OpenAI or Gemini directly into the core generation logic would create vendor lock-in and require massive rewrites if pricing, models, or rate limits change.

### Alternatives Considered
1. **Direct OpenAI cURL Calls**: Hardcoding OpenAI API requests directly inside the generator controller.
2. **External Cloud Proxy Service**: Routing all AI requests through a proprietary third-party SaaS proxy.

### Why the Selected Approach is Preferred
- **Flexibility**: Administrators can switch between OpenAI (GPT-4o), Google Gemini (Gemini 2.0 Flash), or Anthropic with a single dropdown setting.
- **Privacy & Ownership**: All API calls are executed directly from the user's WordPress server to the provider, keeping API keys private and eliminating third-party intermediaries.

---

## ADR-008: Chunked Custom XML Sitemap Provider

### Decision
Implement a custom chunked XML sitemap provider that integrates directly with Yoast SEO (`wpseo_sitemaps_init`), Rank Math (`rank_math/sitemap/`), and WordPress Core Sitemaps, splitting sitemaps into sub-indices of 1,000 URLs each using direct indexed SQL queries.

### Reason
Default XML sitemap generators attempt to load full `WP_Post` objects into memory. On sites with 10,000–50,000 city pages, this immediately causes out-of-memory fatal errors (`Allowed memory size exhausted`).

### Alternatives Considered
1. **Default Yoast Sitemap Integration**: Letting Yoast crawl all `seo_city_page` posts without pagination limits.
2. **Static XML File Writing**: Writing static XML files to the filesystem on disk.

### Why the Selected Approach is Preferred
- **Ultra-Fast Generation**: Direct lightweight indexed SQL queries (`SELECT post_name, post_modified FROM wp_posts ... LIMIT 1000 OFFSET x`) execute in under 15ms.
- **Zero File Locking**: Dynamic XML generation avoids filesystem permission issues and multi-server synchronization problems.

---

## ADR-009: Strict REST API & Nonce Security Hardening with Formula Injection Protection

### Decision
Enforce 100% `permission_callback` authorization on all REST routes, nonce verification on all AJAX/admin requests, `$wpdb->prepare()` on all SQL queries, `wp_kses_post()` on rich content, and formula sanitization (`'`, `=`, `+`, `-`, `@`) on all CSV imports and exports.

### Reason
WordPress plugins managing bulk data and external APIs are high-value targets for SQLi, XSS, CSRF, CSV formula injection (CVE-grade vulnerability), and privilege escalation.

### Alternatives Considered
1. **Basic Sanitization**: Using standard `sanitize_text_field` only.
2. **Unprotected REST endpoints**: Allowing public read/write without authentication.

### Why the Selected Approach is Preferred
- Passes all WordPress.org and enterprise security audits.
- Eliminates CSV spreadsheet command execution risks when administrators open exported client data in Microsoft Excel or Apple Numbers.

---

## ADR-010: PSR-4 Modular Namespaced Code Architecture

### Decision
Structure the plugin using PSR-4 autoloading with the namespace `SeoCityPages\`, strict type declarations (`declare(strict_types=1);`), single-responsibility classes, and isolated directory layers (`Admin`, `AI`, `Content`, `Database`, `Elementor`, `Queue`, `Routing`, `Security`, `SEO`, `Utils`).

### Reason
Procedural WordPress plugins with giant 3,000-line functions files become unmaintainable, prone to namespace collisions, and impossible to unit test.

### Alternatives Considered
1. **Procedural Functions File (`functions.php` style)**: Global functions with prefixes.
2. **Monolithic Single Class (`class-seo-city-pages.php`)**: Putting all features into one god-class.

---

## ADR-011: Indexed Route Resolution & URL Pattern Engine

### Decision
Implement programmatic landing page routing via indexed database paths (`wp_seo_city_pages.url_path`) and a lightweight request interceptor on `template_redirect` (priority `5`), bypassing traditional regex-heavy WordPress rewrite rules.

### Reason
Adding thousands of dynamic rewrite rules to WordPress's `$wp_rewrite` array severely degrades URL resolution across all pages and requires frequent flushing of rewrite rules. Storing normalized relative paths (e.g., `/seo-agency/rajkot/`) in an indexed column allows `O(1)` route resolution with zero core overhead.

### Alternatives Considered
1. **Dynamic WordPress Rewrite Rules**: Injecting thousands of custom rewrite regex patterns into WordPress core rewrite array.
2. **Standard Virtual Pages with wp_posts**: Inserting full post entities for every combination.

### Why the Selected Approach is Preferred
- **Instant Resolution (`O(1)`)**: Indexed lookup takes < 1ms even across 100,000+ generated pages.
- **Zero WordPress Bloat**: Leaves standard pages (`/about/`, `/contact/`) and blog posts completely untouched.
- **Dynamic URL Pattern Customization**: Configurable token patterns (`/{service}/{city}/`, `/{country}/{state}/{city}/{service}/`) can be changed and recomputed without touching core rewrite tables.
- **Secure Preview Engine**: Signed nonces allow draft inspection without making unreviewed pages publicly visible to search engines.

---

## ADR-012: Unified Security Nonce Token Standard & Resilient Theme/Plugin Dependency Detection

### Decision
1. Standardize all internal security nonce creation and verification on a single helper architecture (`Security::nonce_field($action)`, `Security::create_nonce($action)`, and `Security::verify_nonce($nonce, $action)`), avoiding manual double-prefix concatenation across admin forms and AJAX endpoints.
2. Implement resilient, un-cached environment and theme dependency detection that checks parent themes, child themes (via `wp_get_theme()->parent()`), active theme templates/stylesheets, and active plugin hooks without producing alarmist administrator banners during data-only operations.

### Reason
- Nonce action mismatch between template form rendering and controller verification caused CSRF protection to falsely reject legitimate CSV uploads and AJAX batch chunks.
- When administrators activate custom child themes of Arolax (or activate Elementor via varied directory names), standard theme name string matches failed to recognize the theme inheritance hierarchy.

### Alternatives Considered
1. **Raw wp_nonce_field() throughout templates**: Incurred high risk of mismatched action name typos.
2. **Blocking plugin loading when Elementor/Arolax is missing**: Unnecessarily restricted CSV data management and preparation workflows.

### Why the Selected Approach is Preferred
- Eliminates security verification false-positives across all upload, batch import, and edit operations.
- Correctly recognizes parent themes, child themes, and active Elementor installations without stale cache anomalies.
- Replaces disruptive global admin notice banners with clean, context-specific status bars on the CSV import screen.

---

## ADR-013: Single-Template Dynamic Hydration Pipeline & Zero N+1 Page Context Provider

### Decision
1. Implement a single reusable Elementor master template architecture where 1 template renders all generated SEO landing pages dynamically.
2. Prohibit template cloning, JSON layout duplication, and per-city post generation.
3. Expose dynamic data via official Elementor Dynamic Tags (`seo_city_name`, `seo_city_state`, `seo_city_country`, `seo_city_service`, `seo_city_title`, `seo_city_url`).
4. Manage request lifecycle state via a static `Page_Context` provider that loads related entities once per request, providing $O(1)$ dynamic data access with 0 additional database queries per widget.
5. Provide a semantic HTML fallback rendering mode when Elementor is inactive or unconfigured, preserving standard theme headers and footers (including Arolax) with 0 theme file modifications.

### Reason
- Generating individual Elementor post entities for 10,000+ pages balloons database storage by 5+ GB and makes global design edits impossible without running mass rebuild jobs.
- Dynamic tag widgets querying the database individually create an $N+1$ query bottleneck (e.g. 10 widgets per page $\times 10,000$ pages).

### Alternatives Considered
1. **Per-City Elementor Template Duplication**: Creating separate `elementor_library` posts for each city/service combination. (Rejected: extreme bloat, maintenance nightmare).
2. **Shortcode Replacement in Content**: Injecting shortcodes like `[seo_city]` into post content. (Rejected: breaks visual editing experience and native Elementor styling controls).

### Why the Selected Approach is Preferred
- **Instant Design Propagation**: Editing the master Elementor template immediately updates thousands of landing pages with 0 rebuild delay.
- **Maximum Performance**: Zero N+1 database queries; sub-millisecond dynamic tag evaluation.
- **Theme-Isolated**: Seamlessly wraps with Arolax header/footer without touching theme code.



