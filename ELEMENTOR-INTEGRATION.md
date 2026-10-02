# SEO City Pages — Elementor Dynamic Template Integration Reference

**Document Version:** 1.5.0  
**Phase:** Phase 5 — Elementor Dynamic Template Integration  
**Plugin Version:** 0.5.0  
**Text Domain:** `seo-city-pages`  
**Namespace:** `SeoCityPages\Elementor`  

---

## 1. Architectural Concept & Core Mandate

The core design principle of the **SEO City Pages** Elementor integration is **Single-Template Reusable Dynamic Hydration**:

> **CRITICAL RULE**: The plugin **never clones Elementor templates**, **never duplicates Elementor JSON layout trees**, and **never generates thousands of Elementor documents**. 

A single Elementor Library template acts as the visual master design for all SEO landing pages. When a visitor or crawler requests a URL (such as `/seo-agency/rajkot/`), the plugin resolves the canonical database record and injects runtime dynamic context into the template during frontend rendering.

```
[ Request: /seo-agency/rajkot/ ]
             │
             ▼
   [ Request_Resolver ] ──> Fast indexed lookup in wp_seo_city_pages
             │
             ▼
   [ Page_Context Provider ] ──> Hydrates Country, State, City, Service ONCE (Zero N+1)
             │
   ┌─────────┴────────────────────────────────────────┐
   │                                                  │
   ▼                                                  ▼
[ Elementor Active ]                           [ Elementor Inactive / No Template ]
   │                                                  │
   ▼                                                  ▼
[ Reusable Master Template ]                   [ Semantic Fallback Layout ]
(elementor_library ID)                                (Accessible HTML with get_header/footer)
   │
   ▼
[ Dynamic Tags Pipeline ]
├── Tag: City ("Rajkot")
├── Tag: State ("Gujarat")
├── Tag: Country ("India")
├── Tag: Service ("SEO Agency")
├── Tag: Page Title ("SEO Agency in Rajkot")
└── Tag: Page URL ("/seo-agency/rajkot/")
   │
   ▼
[ Rendered SEO Landing Page with Arolax Header & Footer ]
```

---

## 2. Dynamic Tag Identifiers & Reference

All dynamic tags are registered in the Elementor visual editor under the **SEO City Pages** tag group (`seo-city-pages`):

| Dynamic Tag Title | Internal Identifier | Category | Example Value | Description |
| :--- | :--- | :--- | :--- | :--- |
| **City Name** | `seo_city_name` | `text` | `Rajkot` | Resolves the active city name from the database. |
| **State / Region Name** | `seo_city_state` | `text` | `Gujarat` | Resolves the parent state/province/region name. |
| **Country Name** | `seo_city_country` | `text` | `India` | Resolves the parent sovereign country name. |
| **Service Name** | `seo_city_service` | `text` | `SEO Agency` | Resolves the associated service offering name. |
| **Page Title** | `seo_city_title` | `text` | `SEO Agency in Rajkot` | Resolves title override or derived `{Service} in {City}`. |
| **Page URL** | `seo_city_url` | `url`, `text` | `/seo-agency/rajkot/` | Resolves the deterministic public URL. |

---

## 3. High-Performance Request Context Provider (`Page_Context`)

To prevent database query degradation when an Elementor template contains dozens of dynamic tags, [`Page_Context`](file:///home/uday/Agents/SEO%20Plugin/includes/Elementor/class-page-context.php) implements a single-query request caching pattern:

- **Single Query Hydration**: When the request resolver matches a URL, the page record and its related Country, State, City, and Service entities are loaded **once**.
- **Zero N+1 DB Lookups**: 50 dynamic widgets on a single page generate **0 additional SQL queries**.
- **Request Isolation**: Calling `Page_Context::reset_context()` cleans all static memory between consecutive executions, preventing cross-city data contamination.
- **Editor Preview Simulation**: When an administrator edits the template in the Elementor visual editor (`is_edit_mode()`), `Page_Context::get_preview_context()` provides sample data (either from the configured "Editor Preview SEO Page" or default sample values) so designers can visually lay out widgets with realistic text.

---

## 4. Administration & Template Management

The admin screen at **SEO City Pages $\rightarrow$ Elementor Template** provides:

1. **Environment Status Bar**: Live indicators for Elementor Core, Elementor Pro, and Arolax theme mode.
2. **Active Master Template Card**: Displays the current template title, post ID, type (`page`, `section`, `single-page`), status (`publish`, `draft`), and a direct **"Edit Template with Elementor"** action button.
3. **Master Template Selector**: Selects any compatible Elementor Library post (`elementor_library`).
4. **Editor Preview SEO Page**: Configures which live SEO City Page data to simulate during Elementor design sessions.
5. **Quick Starter Template Generator**: Creates a minimal master template with pre-configured dynamic tags, hero section, and CTA block.

---

## 5. Theme Isolation & Arolax Compatibility

In accordance with ADR-003:
- **Zero Theme Files Modified**: No files in `/wp-content/themes/arolax/` are modified or copied.
- **Standard Header / Footer Wrappers**: The template rendering pipeline calls standard WordPress `get_header()` and `get_footer()`, allowing Arolax (or Arolax child themes) to output their headers, navigation menus, and footers seamlessly.
- **Universal Fallback**: If Elementor is disabled or uninstalled, public pages automatically render a clean, accessible semantic HTML layout without throwing PHP fatal errors.

---

## 6. Security & Sanitization

- **Output Escaping**: All dynamic tags pass output through `esc_html()` or `esc_url()` before printing.
- **Template ID Verification**: The template controller validates submitted template IDs (`is_numeric`, `get_post`, post type `elementor_library`, post status not `trash`, capability `manage_seo_city_pages`).
- **CSRF & Nonce Protection**: All admin configuration actions use signed nonces (`save_template`, `create_starter_template`, `reset_template`).
