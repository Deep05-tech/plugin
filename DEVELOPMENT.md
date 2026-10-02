# SEO City Pages — Developer Guide & Reference

**Document Version:** 1.4.0  
**Phase:** Phase 4 — SEO City Page Data Layer + URL Architecture  
**Plugin Version:** 0.4.0  
**Database Schema Version:** 0.4.0  
**Text Domain:** `seo-city-pages`  
**Namespace:** `SeoCityPages\`  

---

## 1. Architectural Overview & Ingestion Flow

The **SEO City Pages** plugin provides a primary CSV-driven data-ingestion pipeline alongside fine-grained administrative management screens and deterministic URL routing:

```
[ CSV File Upload ]
        │
        ▼
[ CSV_Validator & Normalizer ] ──> Structure, Encoding & Formula Injection Checks
        │
        ▼
[ Admin Interactive Preview ] ──> Validation Summary & Duplicate Detection
        │
        ▼
[ Batched AJAX / Action Scheduler ] ──> 50-row Chunks with In-Memory Caching
        │
 ┌──────┴───────────────────────────────────────────────────────┐
 ▼                                                              ▼
[ Location & Service Layer ]                   [ Logical SEO City Page Layer ]
(Countries, States, Cities, Services)          (wp_seo_city_pages Records Schema v0.4.0)
        │                                                       │
        ▼                                                       ▼
[ Management & Editing Screens ]               [ Deterministic URL Engine & Resolver ]
(Search, Filter, Pagination, Blueprints)       (O(1) Route Resolution, Preview & 404s)
```

---

## 2. Directory Structure

```
seo-city-pages/
├── seo-city-pages.php              # Main bootstrap file with standard WP plugin headers
├── uninstall.php                   # Safe uninstaller honoring data retention policy
├── ARCHITECTURE.md                 # System architecture & environment specifications
├── DECISIONS.md                    # Architectural Decision Records (ADRs)
├── DEVELOPMENT.md                  # Developer guide & reference documentation
├── DATA-MODEL.md                   # Full database schema and entity specifications
├── CSV-IMPORT.md                   # Comprehensive CSV import specification
├── URL-ARCHITECTURE.md             # URL structure, token engine & request resolver specification
│
├── assets/                         # Static admin and frontend assets
│   ├── css/
│   │   └── admin.css               # Scoped admin styling (cards, tables, progress bar, badges)
│   └── js/
│       └── admin.js                # Batched AJAX processor & drag-and-drop handler
│
├── includes/                       # PSR-4 namespaced PHP classes (SeoCityPages\)
│   ├── class-autoloader.php        # Standalone PSR-4 autoloader
│   ├── class-plugin.php            # Main plugin singleton orchestrator
│   ├── class-environment.php       # Dependency & diagnostic inspector
│   │
│   ├── Admin/                      # Administrative controllers & UI handlers
│   │   ├── class-admin-menu.php           # Top-level menu & submenu registration
│   │   ├── class-dashboard.php            # Primary dashboard view with metrics
│   │   ├── class-pages-controller.php     # SEO City Pages management, search, filters & editing
│   │   ├── class-import-controller.php    # CSV upload, preview, batching, error downloads
│   │   ├── class-countries-controller.php # Country CRUD, bulk actions, search, pagination
│   │   ├── class-states-controller.php    # State CRUD, parent country filtering
│   │   ├── class-cities-controller.php    # City CRUD, coordinate parsing, relationships
│   │   ├── class-services-controller.php  # Service CRUD, content blueprints
│   │   ├── class-settings.php             # Settings API registration, URL pattern & sanitization
│   │   └── class-system-status.php        # Diagnostic report & environment inspector
│   │
│   ├── Database/                   # Data Access Layer & Schema Management
│   │   ├── class-schema.php               # dbDelta table definitions (Schema v0.4.0)
│   │   ├── class-migrator.php             # Schema versioning & sequential upgrades (v0.4.0)
│   │   ├── class-country-repository.php   # Country data access, validation & cascade protection
│   │   ├── class-state-repository.php     # State data access, relationships & cascade protection
│   │   ├── class-city-repository.php      # City data access, coordinates & cascade protection
│   │   ├── class-service-repository.php   # Service data access, blueprints & cascade protection
│   │   ├── class-import-job-repository.php# Import job sessions, progress & error logs
│   │   └── class-city-page-repository.php # SEO City Page CRUD, combination lookup & search
│   │
│   ├── Elementor/                  # Elementor Dynamic Template Integration
│   │   ├── class-elementor-integration.php# Dynamic tags manager, starter template generator
│   │   ├── class-page-context.php         # Request context provider (zero N+1 query cache)
│   │   └── DynamicTags/                   # Native Elementor Dynamic Tag classes
│   │       ├── class-tag-city.php         # Dynamic Tag: seo_city_name
│   │       ├── class-tag-state.php        # Dynamic Tag: seo_city_state
│   │       ├── class-tag-country.php      # Dynamic Tag: seo_city_country
│   │       ├── class-tag-service.php      # Dynamic Tag: seo_city_service
│   │       ├── class-tag-title.php        # Dynamic Tag: seo_city_title
│   │       └── class-tag-url.php          # Dynamic Tag: seo_city_url
│   │
│   ├── Import/                     # CSV Ingestion Engine
│   │   ├── class-csv-normalizer.php       # Trimming, whitespace collapsing, signature hashing
│   │   ├── class-csv-validator.php        # Column header mapping, data type checks, limits
│   │   └── class-csv-processor.php        # Batch processor with in-memory lookup caching
│   │
│   ├── URL/                        # Deterministic Routing & URL Generation
│   │   ├── class-url-engine.php           # Token substitution, pattern validator, signed preview URLs
│   │   └── class-request-resolver.php     # Public request interception, 404s, preview & Elementor rendering
│   │
│   ├── Logging/                    # Logging subsystem
│   │   └── class-logger.php               # File logger with rotation & redaction
│   │
│   └── Security/                   # Security, permissions & token verification
│       ├── class-capabilities.php         # Custom capability constants & role mapping
│       └── class-security.php             # Nonce verification, CSV formula protection, redaction
│
├── samples/
│   └── sample-import.csv           # Standard sample fixture CSV
│
├── templates/                      # Pure PHP template views
│   └── admin/
│       ├── dashboard.php           # Dashboard template
│       ├── pages-list.php          # SEO City Pages table with combined filters & search
│       ├── page-form.php           # SEO City Page edit form, URL preview & overrides
│       ├── csv-import.php          # CSV multi-step import wizard & preview
│       ├── template-settings.php   # Elementor Master Template selector & preview configuration
│       ├── countries-list.php      # Countries list table with search & bulk actions
│       ├── country-form.php        # Add/Edit country form
│       ├── states-list.php         # States list table with country filtering
│       ├── state-form.php          # Add/Edit state form
│       ├── cities-list.php         # Cities list table with state/country filtering
│       ├── city-form.php           # Add/Edit city form with coordinates & fields
│       ├── services-list.php       # Services list table
│       ├── service-form.php        # Add/Edit service form with template blueprints
│       ├── settings.php            # Settings screen with URL Architecture section
│       └── system-status.php       # System status & environment diagnostics
│
└── tests/                          # Automated test suites
    ├── test-foundation.php         # Phase 2 Foundation Test Suite (50 assertions)
    ├── test-phase3-location-service.php # Phase 3 Location & Service Test Suite (37 assertions)
    ├── test-phase3-csv-importer.php# Phase 3 CSV Ingestion Test Suite (50 assertions)
    ├── test-phase4-page-url.php    # Phase 4 SEO City Page & URL Routing Test Suite (65 assertions)
    └── test-phase5-elementor-integration.php # Phase 5 Elementor Dynamic Template Test Suite (64 assertions)
```

---

## 3. Running Automated Tests

Run the complete test suite from the CLI:

```bash
# 1. Foundation Tests (50 assertions)
~/.local/bin/php tests/test-foundation.php

# 2. Manual Location & Service Tests (37 assertions)
~/.local/bin/php tests/test-phase3-location-service.php

# 3. CSV Importer & Ingestion Tests (50 assertions)
~/.local/bin/php tests/test-phase3-csv-importer.php

# 4. Phase 4 Page, URL Routing & Security Tests (65 assertions)
~/.local/bin/php tests/test-phase4-page-url.php

# 5. Phase 5 Elementor Dynamic Template Tests (64 assertions)
~/.local/bin/php tests/test-phase5-elementor-integration.php
```

**Total: 266 assertions passing with 0 failures across 5 test suites.**
