# SEO Plugin Integration & Adapter Specification

## 1. Multi-Plugin Environment Detection

`SEO_Environment_Detector` automatically discovers active 3rd-party SEO plugins on boot:
- **Yoast SEO**: `WPSEO_VERSION` / `class_exists('WPSEO_Options')`
- **Rank Math SEO**: `RANK_MATH_VERSION` / `class_exists('RankMath')`
- **All in One SEO**: `AIOSEO_VERSION` / `function_exists('aioseo')`
- **Native WordPress Engine**: Always available as default fallback

### 1.1 Multi-Plugin Conflict Handling
If multiple SEO plugins are simultaneously active:
1. Detects conflict and displays an admin alert.
2. Selects primary adapter according to clean priority (`yoast` > `rank_math` > `aioseo` > `native`).
3. Prevents duplicate `<title>`, `<meta description>`, or canonical tags in output HTML.

---

## 2. Adapter Hook Mapping

| Feature | WordPress Native | Yoast SEO | Rank Math SEO | All in One SEO |
| :--- | :--- | :--- | :--- | :--- |
| **SEO Title** | `pre_get_document_title`, `document_title_parts` | `wpseo_title` | `rank_math/frontend/title` | `aioseo_title` |
| **Meta Description** | `wp_head` (`<meta name="description">`) | `wpseo_metadesc` | `rank_math/frontend/description` | `aioseo_description` |
| **Canonical URL** | `wp_head` (`<link rel="canonical">`) | `wpseo_canonical` | `rank_math/frontend/canonical` | `aioseo_canonical_url` |
| **Robots Meta** | `wp_robots` filter | `wpseo_robots` | `rank_math/frontend/robots` | `aioseo_robots_meta` |
| **OpenGraph Tags** | `wp_head` (`og:title`, `og:description`, `og:image`, `og:url`) | `wpseo_opengraph_*` | `rank_math/opengraph/facebook/*` | `aioseo_facebook_tags` |
| **Twitter Cards** | `wp_head` (`twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`) | `wpseo_twitter_*` | `rank_math/opengraph/twitter/*` | `aioseo_twitter_tags` |
| **Schema JSON-LD** | `wp_head` (`<script type="application/ld+json">`) | `wpseo_schema_graph` | `rank_math/json_ld` | `aioseo_schema_output` |
| **XML Sitemap** | `WP_Sitemaps_Provider` | `wpseo_sitemap_index`, `wpseo_do_sitemap_*` | `rank_math/sitemap/providers` | `aioseo_sitemap_posts` |
