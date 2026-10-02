# XML Sitemap Architecture & Scalability Guide

## 1. High-Scale Architecture (50,000+ Pages)

Generating XML sitemaps for large programmatic directories requires memory efficiency and indexed database lookups:

1. **Chunked Database Pagination**:
   - Never loads the entire dataset into PHP memory.
   - Uses `SELECT id, url_path, updated_at, canonical_url FROM {$table} WHERE status = 'published' AND is_published = 1 AND (robots_index != 'noindex' OR robots_index IS NULL) ORDER BY id ASC LIMIT %d OFFSET %d`.
2. **Deterministic Partitioning**:
   - Splits sitemaps into standard 1,000 URL blocks (e.g. `seo_city_pages-1.xml`, `seo_city_pages-2.xml`).
3. **Strict Inclusion Filter**:
   - Only published (`status = 'published'`, `is_published = 1`) pages.
   - Excludes any page explicitly set to `robots_index = 'noindex'`.

---

## 2. Integration with Core & 3rd-Party Engines

- **WordPress Core Sitemaps**: Implements `WP_Sitemaps_Provider` subclass registered under `seo-city-pages`.
- **Yoast SEO Sitemaps**: Hooks into `wpseo_sitemap_index` and `wpseo_do_sitemap_seo_city_pages`.
- **Rank Math Sitemaps**: Hooks into `rank_math/sitemap/providers`.
