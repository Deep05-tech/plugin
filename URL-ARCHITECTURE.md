# SEO City Pages — URL Architecture & Deterministic Routing

## 1. Architectural Overview

The **SEO City Pages** plugin generates thousands of programmatic landing pages targeting search queries structured around geographic locations and services (e.g. "Web Development in Rajkot", "SEO Agency in Mumbai").

To achieve zero performance degradation at scale and eliminate WordPress rewrite rule bloat, SEO City Pages implements a **Deterministic High-Performance Routing Layer** backed by indexed custom database tables (`wp_seo_city_pages`).

```
                              Incoming Public HTTP Request
                                           │
                                           ▼
                            ┌─────────────────────────────┐
                            │ Normalizes Request Path URI │
                            └──────────────┬──────────────┘
                                           │
                        ┌──────────────────┴──────────────────┐
                        │                                     │
                 Is Homepage or                         Matches Non-Empty
                Admin/Static File                       Path (e.g. /seo/rajkot/)
                        │                                     │
                        ▼                                     ▼
             [ WordPress Core Handles ]         ┌─────────────────────────────┐
                                                │ Fast Lookup via url_path    │
                                                │ Index (wp_seo_city_pages)   │
                                                └──────────────┬──────────────┘
                                                               │
                                            ┌──────────────────┴──────────────────┐
                                            │                                     │
                                         Found?                                Not Found
                                            │                                     │
                                            ▼                                     ▼
                             ┌─────────────────────────────┐           [ WordPress Core Handles ]
                             │ Status & Entity Validation  │           (Normal Pages/Posts/CPTs)
                             └──────────────┬──────────────┘
                                            │
                         ┌──────────────────┴──────────────────┐
                         │                                     │
               Published & All Active?                  Draft / Private /
                         │                              Inactive Source
                         ▼                                     │
            [ Render Landing Page (200) ]                      ▼
                                                        [ Emit Clean 404 ]
```

---

## 2. Dynamic Token Engine & URL Patterns

### 2.1 Supported Tokens

The URL structure is globally configurable via **SEO City Pages → Settings → URL Architecture**. The engine supports the following substitution tokens:

| Token | Description | Source Field | Example Value |
|---|---|---|---|
| `{service}` | Service slug | `wp_seo_services.slug` | `seo-agency` |
| `{city}` | City slug | `wp_seo_cities.slug` | `rajkot` |
| `{state}` | State / Region slug | `wp_seo_states.slug` | `gujarat` |
| `{country}` | Country code (lowercase) or slug | `wp_seo_countries.code` / `slug` | `in` / `india` |

### 2.2 Default Pattern
* **Default Structure:** `/{service}/{city}/`
* **Example:** `https://example.com/seo-agency/rajkot/`

### 2.3 Alternative Structural Patterns
* `/{country}/{state}/{city}/{service}/` $\rightarrow$ `/india/gujarat/rajkot/seo-agency/`
* `/{city}/{service}/` $\rightarrow$ `/rajkot/seo-agency/`
* `/{service}-in-{city}/` $\rightarrow$ `/seo-agency-in-rajkot/`
* `/locations/{country}/{state}/{city}/{service}/` $\rightarrow$ `/locations/india/gujarat/rajkot/seo-agency/`

### 2.4 Pattern Validation Rules
1. Must contain at least `{service}` and `{city}` tokens to ensure semantic uniqueness.
2. Must start and end with `/`.
3. Allowed characters: lowercase alphanumeric, hyphens, and token braces `{}`.

---

## 3. Request Resolution Pipeline

### 3.1 Non-Intrusive Pass-Through
The request resolver operates on the `template_redirect` hook (priority `5`) prior to template inclusion:
* **Normal Pages & Posts:** Requests to `/about/`, `/contact/`, `/services/`, or blog posts are evaluated against `wp_seo_city_pages` in `O(1)` time using the indexed `url_path` column. If no match is found, WordPress proceeds untouched.
* **Homepage:** Root `/` requests are immediately bypassed.

### 3.2 Status & Visibility Rules
Every resolved SEO City Page undergoes strict visibility checks:

| Page Status | Parent Entity Status | Public Request | Authorized Admin Preview |
|---|---|---|---|
| `published` | All Active (`country`, `state`, `city`, `service`) | **200 OK** (Render) | **200 OK** |
| `draft` (default) | All Active | **404 Not Found** | **200 OK** |
| `private` | All Active | **404 Not Found** (anonymous) | **200 OK** |
| `trash` | Any | **404 Not Found** | **404 Not Found** |
| Any | Any Inactive (`status != 'active'`) | **404 Not Found** | **404 Not Found** |

---

## 4. Secure Admin Preview Architecture

Administrators can safely inspect draft or published landing pages before making them live.

* **Preview URL Scheme:** `/?scp_preview_page={id}&_scp_preview_nonce={nonce}`
* **Security Checks:**
  1. Capability verification: Requires `Capabilities::MANAGE` (`manage_seo_city_pages` / `manage_options`).
  2. Cryptographic Nonce: Generated with action `scp_preview_{page_id}`.
  3. Context Injection: Sets `Request_Resolver::is_preview() = true` and primes `$current_page` without making the page publicly discoverable.

---

## 5. Collision Prevention & Cascade Deletion Protection

### 5.1 Unique Combinations
* Schema v0.4.0 enforces a strict composite database constraint:
  ```sql
  UNIQUE KEY `uk_combo` (`country_id`, `state_id`, `city_id`, `service_id`)
  ```
* Importing or creating an identical Country + State + City + Service record updates the existing record idempotently rather than duplicating entries.

### 5.2 Cascade Deletion Protection
To prevent broken URLs and orphaned SEO landing pages:
* Deleting a `Country`, `State`, `City`, or `Service` is strictly blocked if any dependent records exist in `wp_seo_city_pages`.
* The repository returns a `WP_Error` with actionable feedback:
  ```
  Cannot delete this record because %d SEO City Page(s) depend on it.
  ```

---

## 6. Elementor & Dynamic Rendering Preparation (Phase 5+)

In Phase 5, the rendering pipeline hooks into `seo_city_pages_before_render` action:
1. Loads the selected Master Elementor Landing Page Template.
2. Injects dynamic context variables (`{country_name}`, `{state_name}`, `{city_name}`, `{service_name}`, `{title_override}`).
3. Renders the page through Elementor's CSS/JS asset queue without altering the active theme files.
