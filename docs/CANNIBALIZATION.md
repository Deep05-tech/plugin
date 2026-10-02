# Search Intent & Keyword Cannibalization Detector

## Overview

The **Cannibalization Detector** (`SeoCityPages\Quality\Cannibalization_Detector`) identifies competing landing pages targeting identical or overlapping search queries within the same geographic target market.

---

## Detection Layers

### 1. Duplicate Primary Keyword in Same City
- Queries active and published pages sharing the exact same `city_id`.
- Checks for matching target keywords (`seo_title`, `primary_keyword`, or derived title).
- **Result**: Flags 100.0% overlap conflict and identifies the existing conflicting page ID.

### 2. Service Synonym / Semantic Intent Clashing
In many industries, multiple services share identical search intent (e.g. "Search Engine Optimization" vs "SEO Services", or "Website Design" vs "Web Development"). Creating separate landing pages for both in the same city leads to indexation fragmentation and internal cannibalization.

The detector maintains intent synonym clusters:
- `['seo', 'search engine optimization', 'seo agency', 'seo services', 'seo company']`
- `['web design', 'website design', 'web development', 'website development']`
- `['digital marketing', 'online marketing', 'internet marketing']`
- `['ppc', 'pay per click', 'google ads', 'adwords management']`
- `['social media marketing', 'smm', 'social media management']`
- `['content marketing', 'copywriting', 'content writing']`
- `['link building', 'backlink services', 'off-page seo']`

- **Result**: Flags 85.0% overlap warning (`High intent overlap: Service "X" closely competes with "Y" in same city`).

### 3. Substring & Root Phrase Overlap
- Detects partial keyword nesting (e.g., "Plumber Miami" vs "Emergency Plumber Miami").
- **Result**: Flags 75.0% overlap warning.

---

## Performance & Index Optimization

- Queries are indexed by `city_id` on `wp_seo_city_pages` (`idx_city_id` and `idx_city_service`).
- Execution executes entirely in-memory with sub-millisecond overhead.
