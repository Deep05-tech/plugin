# AI Architecture Specification — SEO City Pages (Phase 6)

## 1. Architectural Overview

The OpenAI Content Generation Engine is a high-reliability, zero-hallucination, structured copy pipeline for WordPress. It transforms normalized Location and Service metadata into high-converting, localized landing page copy without hallucinating unsupported claims or altering published URLs.

```
┌─────────────────────────────────────────────────────────────┐
│                       SEO City Page                         │
│ (Country: USA, State: FL, City: Orlando, Service: SEO)     │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                    Prompt Builder                           │
│  - System Prompt: Anti-hallucination, zero-claim boundary   │
│  - User Prompt: City/Service/Company facts & context        │
│  - Security: XML tag boundaries, injection defenses        │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│              OpenAI Structured Outputs Engine               │
│  - Model: gpt-4o-mini / gpt-4o / o3-mini                   │
│  - Response Format: json_schema (strict: true)              │
│  - Schema: Content_Schema v1.0 (additionalProperties: false)│
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 Content Validator & Sanitizer               │
│  - Recursive KSES post sanitization                         │
│  - Strip malicious scripts, event handlers, and iframes     │
│  - Anti-hallucination verification (City & Service checks)   │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                  Content Versioning Layer                   │
│  - Table: wp_seo_content_versions (Version N)               │
│  - Status: needs_review -> approved / rejected / manual     │
│  - Non-destructive: Live pages retain active approved copy  │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 Dynamic Elementor Hydration                 │
│  - Content_Context & Dynamic Tags                           │
│  - Vispan Theme CSS & IntersectionObserver scroll triggers   │
│  - Shortcode fallback & single-template rendering           │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Core Pillars

### 2.1 Zero-Hallucination Policy
- **No Fabricated Facts**: Generation is strictly constrained to the CSV-provided `country`, `state`, `city`, and `service`, combined with global Business Profile settings.
- **Forbidden Knowledge Claims**: The engine is explicitly prohibited from generating invented local street addresses, telephone numbers, local awards, or partner businesses that are not verified in the input context.
- **Server-side Validation**: If the model output fails to reference the exact city or service, or contains placeholder tokens like `[City]`, validation fails and the version is marked `failed` with error diagnostics.

### 2.2 Security & Credential Isolation
- **Secret Key Protection**: OpenAI API keys are never exposed in frontend HTML, DOM attributes, AJAX responses, or diagnostic logs.
- **Environment Priority**: When `SEO_CITY_OPENAI_API_KEY` is set in the PHP environment or `wp-config.php`, it takes strict precedence over database options.
- **Masked UI**: The admin interface only displays masked previews (`••••••••••••••••`) to prevent shoulder surfing or screen capture exposure.

### 2.3 Non-Destructive Versioning
- **Safe Iteration**: Generating or regenerating content never overwrites the live, approved landing page version.
- **Version Isolation**: New generation runs create a new row in `wp_seo_content_versions` with status `needs_review`. The live page continues to serve the approved `current_version_id` until an administrator reviews and approves the new version.
- **Manual Editing**: Administrators can manually polish generated copy; edited versions are saved with `provider = 'manual'` and tracked independently in the version history.

### 2.4 High-Performance Queue & Fault Tolerance
- **Batch Processing**: Generation jobs (`wp_seo_generation_jobs`) process large sets of pages in isolated chunks.
- **Isolated Failure Boundary**: If generation for City A encounters a rate limit or validation failure, City B and City C continue processing unaffected.
- **Automated Backoff**: Built-in exponential backoff handles OpenAI 429 rate limit errors gracefully.
