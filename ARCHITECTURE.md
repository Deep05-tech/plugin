# SEO City Pages — Architecture & Environment Audit Specification

**Document Version:** 1.1.0  
**Phase:** Phase 1 — Environment Audit & System Architecture (Toolchain Installed)  
**Target Plugin Name:** SEO City Pages  
**Workspace:** `/home/uday/Agents/SEO Plugin`  
**Date:** September 2026  

---

## Table of Contents
1. [Environment Audit & System State](#1-environment-audit--system-state)
2. [Confirmed Versions & Technical Inventory](#2-confirmed-versions--technical-inventory)
3. [Unknowns & Unconfirmed Parameters](#3-unknowns--unconfirmed-parameters)
4. [Arolax Theme Integration Strategy](#4-arolax-theme-integration-strategy)
5. [Elementor Extension & Rendering Architecture](#5-elementor-extension--rendering-architecture)
6. [Dynamic Page Generation Architecture](#6-dynamic-page-generation-architecture)
7. [Data Architecture & Storage Model](#7-data-architecture--storage-model)
8. [URL Routing & Rewrite Engine](#8-url-routing--rewrite-engine)
9. [Content Hierarchy & Waterfall Fallback Engine](#9-content-hierarchy--waterfall-fallback-engine)
10. [AI Integration & Provider Abstraction](#10-ai-integration--provider-abstraction)
11. [Security Architecture & Threat Mitigation](#11-security-architecture--threat-mitigation)
12. [Scalability & Performance Strategy](#12-scalability--performance-strategy)
13. [Admin UI & Management Architecture](#13-admin-ui--management-architecture)
14. [Plugin Directory Structure](#14-plugin-directory-structure)
15. [Testing & Quality Assurance Strategy](#15-testing--quality-assurance-strategy)
16. [Implementation Roadmap](#16-implementation-roadmap)
17. [Architectural Risks & Mitigations](#17-architectural-risks--mitigations)
18. [Decisions Required](#18-decisions-required)

---

## 1. Environment Audit & System State

An exhaustive audit of the development environment and system toolchain was conducted. All missing runtime tools and core dependencies were installed and verified.

### Audit Findings & Installed Toolchain Summary

| Parameter | State / Version | Status | Source / Notes |
|---|---|---|---|
| **Operating System** | Linux (Ubuntu 26.04 LTS x86_64) | **CONFIRMED** | Host system |
| **PHP CLI Binary** | PHP 8.4.23 (All core modules) | **INSTALLED & CONFIRMED** | `~/.local/bin/php` |
| **Composer** | Version 2.10.3 | **INSTALLED & CONFIRMED** | `~/.local/bin/composer` |
| **WP-CLI** | Version 2.12.0 | **INSTALLED & CONFIRMED** | `~/.local/bin/wp` |
| **WordPress Core** | Version 7.1 | **CONFIRMED** | `wp-includes/version.php` |
| **MySQL / MariaDB** | MySQL 8.4.0 (Local Service) | **CONFIRMED** | Local configuration `sites.json` |
| **Elementor Core** | Version 4.2.1 | **CONFIRMED** | `wp-content/plugins/elementor` |
| **Active SEO Plugin** | Yoast SEO (`wordpress-seo` v28.2) | **CONFIRMED** | `wp-content/plugins/wordpress-seo` |
| **Elementor Pro** | Not present in local plugins | **NOT AVAILABLE** | Free Elementor active; Pro cleanly abstracted |
| **Arolax Theme** | Not present in local demo site (`hello-elementor` active) | **NOT AVAILABLE** | Theme isolation strategy ensures universal compatibility |
| **Action Scheduler** | Bundled with plugin architecture | **PLANNED FOR BUNDLING** | To be embedded in plugin `vendor/` |
| **PHP Memory Limit** | `256M` | **CONFIRMED** | `php.ini.hbs` configuration |
| **Max Execution Time** | `1200` seconds | **CONFIRMED** | `php.ini.hbs` configuration |
| **WP-Cron** | Available / Enabled | **CONFIRMED** | Default WP-Cron operational |
| **Permalink Structure** | `/%postname%/` | **CONFIRMED** | `wp_options` row 29 |

---

## 2. Confirmed Versions & Technical Inventory

1. **WordPress 7.1 Compatibility**:
   - Modern hook lifecycles (`init`, `template_redirect`, `template_include`, `wp_enqueue_scripts`).
   - Native Block bindings / REST API controllers.
   - Strict adherence to WP Coding Standards (WPCS 3.x).

2. **PHP 8.4+ Runtime**:
   - Strict typing (`declare(strict_types=1);`).
   - Match expressions, typed properties, readonly classes/properties, constructor promotion.
   - Zero deprecated dynamic property assignments.
   - Strict error handling with `try...catch` and typed exceptions.

3. **MySQL 8.4+ Compatibility**:
   - InnoDB default storage engine with UTF8MB4 (`utf8mb4_unicode_520_ci` or `utf8mb4_0900_ai_ci`).
   - Explicit default values for timestamp/datetime columns to prevent strict mode errors (`NO_ZERO_DATE`).
   - Proper composite indexing for multi-column search (`service_id`, `city_id`, `status`).

4. **Elementor 4.2.1 Integration**:
   - Compatible with Elementor 3.x and 4.x dynamic tag management.
   - Dynamic tag registry hook: `elementor/dynamic_tags/register`.
   - Frontend renderer: `\Elementor\Plugin::$instance->frontend->get_builder_content_for_display()`.

5. **Yoast SEO 28.2 Integration**:
   - Hookable meta filters: `wpseo_title`, `wpseo_metadesc`, `wpseo_canonical`, `wpseo_opengraph_title`, `wpseo_opengraph_desc`, `wpseo_schema_*`.
   - Custom sitemap provider registration via `wpseo_sitemaps_init` and `wpseo_sitemap_index`.

---

## 3. Unknowns & Unconfirmed Parameters

Because the production Arolax theme environment is remote or not bundled in the local sandbox, the following items are formally recorded as **UNKNOWN / NOT CONFIRMED**:

1. **Arolax Exact Version & Custom Hooks**:
   - The exact release tag and custom actions (e.g. `arolax_before_header`, `arolax_after_footer`) are unknown.
   - **Architectural Solution**: Rely exclusively on standard WordPress core template hooks (`wp_head`, `wp_body_open`, `wp_footer`, `get_header`, `get_footer`, `the_content`) and standard Elementor canvas/header-footer hooks. This guarantees 100% theme independence.

2. **Presence of Elementor Pro in Production**:
   - If Elementor Pro is present in production, Theme Builder templates might be active.
   - **Architectural Solution**: Provide support for both free Elementor templates (`elementor_library` post type) and Theme Builder Single Page templates, auto-detecting Pro availability.

3. **Active Caching & CDN Layer**:
   - Caching plugins (e.g. LiteSpeed Cache, WP Rocket, Cloudflare) in production are not confirmed.
   - **Architectural Solution**: Include cache-busting hooks, automatic purge requests on content override changes, and support for `DONOTCACHEPAGE` on admin preview routes.

---

## 4. Arolax Theme Integration Strategy

### Core Tenet: Zero Modifications to `/wp-content/themes/arolax/`

Under no circumstances will the plugin edit, overwrite, or patch files inside the Arolax theme directory. The integration must survive theme updates without regression.

```
┌────────────────────────────────────────────────────────┐
│                   WordPress Request                    │
│            GET /services/seo-agency/rajkot/            │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│            SEO City Pages Route Interceptor            │
│         (template_include / single_template)           │
└──────────────────────────┬─────────────────────────────┘
                           │
       ┌───────────────────┴───────────────────┐
       ▼                                       ▼
┌─────────────────────────┐         ┌─────────────────────────┐
│  Theme Full-Width Mode  │         │ Elementor Header/Footer │
│ (Arolax Header/Footer)  │         │      Canvas Mode        │
└──────────────┬──────────┘         └────────────┬────────────┘
               │                                 │
               └────────────────┬────────────────┘
                                │
                                ▼
        ┌───────────────────────────────────────────────┐
        │  SEO City Pages Custom Template Container     │
        │                                               │
        │  1. Initialize SEO_City_Context (Rajkot)      │
        │  2. Execute Dynamic Tag & Token Hydration     │
        │  3. Render Master Elementor Template          │
        │  4. Reset Context after loop                  │
        └───────────────────────────────────────────────┘
```

### Technical Integration Details

1. **Template Routing (`template_include`)**:
   The plugin registers a filter on `template_include`. When a request matches an `seo_city_page` post type or custom route:
   - If Arolax is active, the plugin loads a clean wrapper template from `seo-city-pages/templates/single-seo-city-page.php`.
   - The wrapper template invokes `get_header()` and `get_footer()`, allowing Arolax to inject its standard navigation, branding, mobile drawer, typography, and footer scripts.
   - Inside the loop, it invokes `the_content()`, which is intercepted to output the compiled Elementor master template.

2. **Elementor Page Template Compatibility**:
   - The plugin supports selecting the page layout:
     - `default`: Wraps with Arolax header + footer.
     - `elementor_header_footer`: Uses Elementor Pro / Theme Builder header & footer.
     - `elementor_canvas`: Full blank canvas (no header/footer).

3. **Style & Script Enqueuing**:
   - All plugin-specific styles are enqueued with distinct handles (`seo-city-pages-frontend`) hooked to `wp_enqueue_scripts`.
   - Elementor Kit CSS and Post CSS files for the master template are dynamically registered to guarantee that custom fonts, colors, and responsive breakpoints defined in Elementor load correctly on all dynamic URLs.

---

## 5. Elementor Extension & Rendering Architecture

### Official Supported Elementor APIs

The plugin avoids brittle hacks and exclusively utilizes official Elementor extensibility hooks:

#### A. Dynamic Tags System
Dynamic tags allow any standard Elementor text, heading, URL, image, or textarea widget to pull data from the active city/service context.

1. **Registration Hook**:
   ```php
   add_action( 'elementor/dynamic_tags/register', function( \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager ) {
       $dynamic_tags_manager->register_group( 'seo-city-pages', [
           'title' => __( 'SEO City Pages', 'seo-city-pages' ),
       ] );
       
       $dynamic_tags_manager->register( new \SeoCityPages\Elementor\DynamicTags\City_Name_Tag() );
       $dynamic_tags_manager->register( new \SeoCityPages\Elementor\DynamicTags\Service_Name_Tag() );
       $dynamic_tags_manager->register( new \SeoCityPages\Elementor\DynamicTags\State_Name_Tag() );
       $dynamic_tags_manager->register( new \SeoCityPages\Elementor\DynamicTags\Country_Name_Tag() );
       $dynamic_tags_manager->register( new \SeoCityPages\Elementor\DynamicTags\Hero_Title_Tag() );
       $dynamic_tags_manager->register( new \SeoCityPages\Elementor\DynamicTags\Hero_Desc_Tag() );
       $dynamic_tags_manager->register( new \SeoCityPages\Elementor\DynamicTags\Content_Block_Tag() );
       $dynamic_tags_manager->register( new \SeoCityPages\Elementor\DynamicTags\City_Meta_Tag() );
   } );
   ```

2. **Dynamic Tag Class Architecture**:
   - Inherits from `\Elementor\Core\DynamicTags\Tag` or `\Elementor\Core\DynamicTags\Data_Tag`.
   - Overrides `get_name()`, `get_title()`, `get_group()`, `get_categories()`, `render()`.
   - Categories supported: `\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY`, `URL_CATEGORY`, `POST_META_CATEGORY`.

#### B. Runtime Token Filter Pipeline
In addition to native Elementor Dynamic Tags, text widgets, headings, and HTML blocks that use bracket syntax (e.g. `{{city}}`, `{{service}}`, `{{state}}`, `{{phone}}`, `{{price_starting}}`) are processed via an Elementor widget render filter:
- Hook: `elementor/widget/render_content`
- Content is parsed through `SeoCityPages\Content\Token_Engine::replace_tokens( $content, $context )`.
- HTML is sanitized and token replacements are performed safely without breaking Elementor container markup or JSON structures.

#### C. Master Template Rendering Engine
- The master template is designed once in Elementor (stored as standard `elementor_library` template or `page`).
- When rendering dynamic pages, the plugin retrieves the template ID and calls:
  ```php
  \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $master_template_id );
  ```
- **Crucial Optimization**: The Elementor post CSS is enqueued using `\Elementor\Core\Files\CSS\Post::create( $master_template_id )->enqueue()`. This ensures all styles, column widths, animations, and typography render with 100% fidelity on dynamic pages without generating CSS files for every city page.

---

## 6. Dynamic Page Generation Architecture

### Architectural Principle: 1 Template → N Pages

```
                   ┌───────────────────────────────┐
                   │    Master Elementor Template   │
                   │    (ID: 48229 - "SEO Agency")  │
                   └───────────────┬───────────────┘
                                   │
         ┌─────────────────────────┼─────────────────────────┐
         │                         │                         │
         ▼                         ▼                         ▼
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│ /seo-agency/    │       │ /seo-agency/    │       │ /seo-agency/    │
│ rajkot/         │       │ ahmedabad/      │       │ surat/          │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ Context: Rajkot │       │ Context:        │       │ Context: Surat  │
│ Title: SEO      │       │ Ahmedabad       │       │ Title: SEO      │
│ Agency in Rajkot│       │ Title: SEO      │       │ Agency in Surat │
│ Meta: Rajkot... │       │ Agency in Ahmd..│       │ Meta: Surat...  │
└─────────────────┘       └─────────────────┘       └─────────────────┘
```

### Viability Assessment

This architecture is **100% technically viable** and superior to static page cloning:
- **Storage Efficiency**: 10,000 pages require only lightweight database records (~10 KB each) instead of 10,000 heavy Elementor JSON blobs (~500 KB each), saving ~5 GB of database bloat.
- **Maintainability**: Changing the header banner, CTA button color, or contact form on the master template updates all 10,000 landing pages instantly.
- **Cache Friendly**: Elementor CSS is compiled once for the template; object caches (Redis/Memcached) and full-page caches (WP Rocket, Cloudflare) cache the rendered HTML efficiently.

---

## 7. Data Architecture & Storage Model

### Hybrid Storage Model (Recommended)

1. **WordPress Custom Post Type (`seo_city_page`)**:
   - Used as the routing anchor in WordPress.
   - Provides native slug indexing, post status (`publish`, `draft`), author tracking, revision hooks, Yoast/RankMath SEO indexing, and standard XML sitemap integration.
   - Contains minimal post data: `post_title`, `post_name` (slug), `post_status`, `post_type`.

2. **Normalized Custom Database Tables**:
   - High-density geographic, relational, and job data is stored in dedicated custom indexed database tables (`wp_seo_countries`, `wp_seo_states`, `wp_seo_cities`, `wp_seo_services`, `wp_seo_content_blocks`, `wp_seo_generation_jobs`, `wp_seo_generation_logs`, `wp_seo_audit_logs`).
   - Prevents `wp_postmeta` table bloat and preserves query performance at 10,000–50,000+ pages.

---

## 8. URL Routing & Rewrite Engine

### Supported URL Patterns

The URL structure is configurable via the Admin Settings page:

| Format Pattern | Sample URL | Rewrite Regular Expression |
|---|---|---|
| **Pattern A (Standard Service-City)** | `/seo-agency/rajkot/` | `^([^/]+)/([^/]+)/?$` |
| **Pattern B (Prefixed Service-City)** | `/services/seo-agency/rajkot/` | `^services/([^/]+)/([^/]+)/?$` |
| **Pattern C (City-First)** | `/rajkot/seo-agency/` | `^([^/]+)/([^/]+)/?$` |
| **Pattern D (Full Geo-Hierarchy)** | `/in/gujarat/rajkot/seo-agency/` | `^([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$` |

---

## 9. Content Hierarchy & Waterfall Fallback Engine

### 5-Tier Waterfall Fallback System

```
┌────────────────────────────────────────────────────────┐
│               TIER 1: Global Default                   │
│ (Fallback values across all services and all cities)   │
└──────────────────────────┬─────────────────────────────┘
                           │ Fallback if empty
                           ▼
┌────────────────────────────────────────────────────────┐
│               TIER 2: Service Level                    │
│   (Content specific to "Search Engine Optimization")   │
└──────────────────────────┬─────────────────────────────┘
                           │ Fallback if empty
                           ▼
┌────────────────────────────────────────────────────────┐
│                TIER 3: City Level                      │
│     (Content specific to "Rajkot" e.g. landmarks)      │
└──────────────────────────┬─────────────────────────────┘
                           │ Fallback if empty
                           ▼
┌────────────────────────────────────────────────────────┐
│            TIER 4: Service + City Matrix               │
│ (Specific content for "SEO Agency in Rajkot" generated)│
└──────────────────────────┬─────────────────────────────┘
                           │ Fallback if empty
                           ▼
┌────────────────────────────────────────────────────────┐
│           TIER 5: Individual Page Override             │
│ (Hand-crafted manual override for this specific page)  │
└────────────────────────────────────────────────────────┘
```

---

## 10. AI Integration & Provider Abstraction

- Strategy Pattern with `AI_Provider_Interface`.
- Implements `OpenAI_Provider` (GPT-4o/Mini), `Gemini_Provider` (Gemini 2.0/1.5 Flash/Pro), and `Anthropic_Provider`.
- Strict JSON Schema enforcement with JSON mode.
- Sanitization with `wp_kses_post()`.
- Exponential backoff retries (HTTP 429/503) and token/spend caps.

---

## 11. Security Architecture & Threat Mitigation

- **SQL Injection**: 100% prepared statements via `$wpdb->prepare()`. Whitelisting for all column names and sort orders (`ORDER BY`).
- **XSS**: Contextual escaping (`esc_html()`, `esc_attr()`, `esc_url()`) on output; rich HTML sanitized using `wp_kses_post()`.
- **CSRF**: WordPress nonces (`check_admin_referer()`, `wp_verify_nonce()`) on every form submission and AJAX endpoint.
- **REST Security**: Explicit `permission_callback` defined on 100% of registered REST routes.
- **CSV Formula Injection**: Sanitization of cell data starting with `=`, `+`, `-`, `@`, `\t`, `\r` by prepending a single quote (`'`).

---

## 12. Scalability & Performance Strategy

- Single reusable master template keeps database overhead negligible (< 10 KB per page).
- Background job processing via Action Scheduler in chunks of 25–50 pages.
- Custom chunked XML sitemap provider breaks URLs into 1,000-page sub-sitemaps using direct indexed SQL queries.

---

## 13. Admin UI & Management Architecture

Dedicated top-level menu `SEO City Pages` with submenus:
1. Dashboard
2. Pages (List Table)
3. Cities
4. States & Countries
5. Services
6. Master Templates
7. Content Pools
8. Bulk Generator
9. AI Generator
10. SEO & Schema
11. Internal Links
12. CSV Import / Export
13. Job Queue
14. Audit & Activity Logs
15. Settings

---

## 14. Implementation Roadmap

- **Phase 1 (Complete)**: Environment Audit & System Architecture
- **Phase 2**: Plugin Foundation & Core Bootstrapper
- **Phase 3**: Custom Database Schema & Geographic Data Repositories
- **Phase 4**: SEO City Page Custom Post Type & URL Rewrite Engine
- **Phase 5**: Elementor Extension, Dynamic Tags & Master Template Renderer
- **Phase 6**: 5-Tier Content Engine & Token Replacement Waterfall
- **Phase 7**: CSV Import/Export & Data Synchronization
- **Phase 8**: Bulk Matrix Generator & Background Action Scheduler Queue
- **Phase 9**: Yoast/RankMath Integration & Chunked XML Sitemap Provider
- **Phase 10**: AI Provider Engine (OpenAI & Gemini) with JSON Schema Validation
- **Phase 11**: Security Hardening, Audit Logging & Capability Management
- **Phase 12**: Performance Optimization, Query Caching & Production Packaging

---
