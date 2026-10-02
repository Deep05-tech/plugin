# SEO Architecture & Precedence Cascade — Phase 8

## 1. Overview

Phase 8 integrates SEO City Pages with WordPress SEO infrastructure while maintaining absolute control over titles, meta descriptions, canonical URLs, search engine indexing directives, OpenGraph/Twitter social cards, JSON-LD Schema, and XML sitemaps.

---

## 2. Core Architecture Components

```
                               ┌─────────────────────────────┐
                               │     HTTP Request Resolved   │
                               └──────────────┬──────────────┘
                                              │
                               ▼──────────────▼──────────────┐
                               │       SEO_Manager           │
                               │  Precedence Cascade (1..4)  │
                               └──────────────┬──────────────┘
                                              │
                    ┌─────────────────────────┼─────────────────────────┐
                    ▼                         ▼                         ▼
         ┌────────────────────┐    ┌────────────────────┐    ┌────────────────────┐
         │   Native_Adapter   │    │   Yoast_Adapter    │    │ Rank_Math_Adapter  │
         │ (wp_head/wp_robots)│    │ (wpseo_* hooks)    │    │ (rank_math/* hooks)│
         └────────────────────┘    └────────────────────┘    └────────────────────┘
```

### 2.1 Four-Tier Precedence Cascade

When any crawler or visitor requests an SEO City Page, `SEO_Manager::resolve_page_seo()` determines metadata using strict precedence:

1. **Tier 1 — Manual Override**:
   - Explicit per-page overrides configured by an administrator (`is_manual_seo_override = 1`).
   - Completely immune to automated AI batch overwrites.
2. **Tier 2 — Approved AI Metadata**:
   - `seo_metadata` object generated in the published `content_json` version.
3. **Tier 3 — Configured Template**:
   - Deterministic template replacement using global tokens (`{service}`, `{city}`, `{state_code}`, `{site_name}`, etc.).
4. **Tier 4 — Safe Fallback**:
   - Deterministic standard fallback: `{service} in {city} | {site_name}`.

---

## 3. Strict Status-Based Indexing Controls

| Page Status | `is_published` | Robots Directives Output | Search Engine Behavior |
| :--- | :--- | :--- | :--- |
| `draft` | `0` | `noindex, nofollow` | Excluded from index & sitemaps |
| `needs_review` | `0` | `noindex, nofollow` | Blocked from crawling & indexing |
| `private` | `0` | `noindex, nofollow` | Admins only, no crawl indexation |
| `trash` | `0` | `noindex, nofollow` | 404 response to crawlers |
| `published` | `1` | `index, follow` (or custom) | Live, indexed, included in XML sitemap |

---

## 4. O(1) Fast SHA-256 Duplicate Detection

To prevent duplicate titles, meta descriptions, and canonical loops across 50,000+ pages, all strings are hashed with SHA-256 upon save:
- `seo_title_hash`: `hash('sha256', trim($seo_title))`
- `seo_desc_hash`: `hash('sha256', trim($meta_description))`
- `canonical_hash`: `hash('sha256', trim($canonical_url))`

Indexes on these hash columns guarantee instant collision lookups without full-text database table scans.
