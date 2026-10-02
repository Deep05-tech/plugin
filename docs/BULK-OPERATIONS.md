# Bulk Operations Engine

The **SEO City Pages Bulk Operations Engine** is designed to orchestrate and execute large-scale actions (from 10 to 50,000+ pages) asynchronously with zero server timeouts, memory exhaustion, or database locks.

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                   Bulk Operations Center                    │
│      (Wizard / CSV Import / Queue Center / Rollbacks)       │
└──────────────────────────────┬──────────────────────────────┘
                               │ AJAX / REST
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                    Bulk_Job_Runner                          │
│   • Worker Token Validation   • Chunk Execution (10-100/req)│
│   • Time Budget Allocation    • Stale Recovery (15m Lock)   │
└──────────────────────────────┬──────────────────────────────┘
                               │ Atomic Batch Claims
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                  Database Queue Tables                      │
│   • seo_bulk_jobs (Parent Job Meta & Aggregated Progress)   │
│   • seo_bulk_job_items (Per-Item State, Worker, Payload)    │
└──────────────────────────────┬──────────────────────────────┘
                               │ Dispatches to
            ┌──────────────────┼──────────────────┐
            ▼                  ▼                  ▼
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│  Page Creation   │ │  AI Generation   │ │ SEO / Publish    │
│     Handler      │ │     Handler      │ │     Handlers     │
└──────────────────┘ └──────────────────┘ └──────────────────┘
```

---

## 2. Granular Operations Supported

| Operation Type | Description | Idempotency / Duplicate Strategy |
| :--- | :--- | :--- |
| `create_pages` | Generates location + service combinations in bulk | Checks existing combinations, skips duplicates gracefully |
| `generate_content` | Asynchronously generates AI content for selected pages | Generates new `needs_review` version, isolates live content |
| `generate_seo` | Computes title tags, meta descriptions, and OG tags | Applies fallback cascade, skips manual overrides |
| `validate_pages` | Evaluates SEO quality and taxonomy integrity | Generates validation warnings/errors per item |
| `approve_content` | Transitions pending AI versions to `approved` | Bypasses unchanged or rejected drafts |
| `publish_pages` | Publishes approved versions & syncs `wp_posts` | Blocks unapproved drafts; handles WP core sync |
| `csv_import` | Ingests CSV rows into cities, services, and pages | Structural preview, sanitizes formulas, flags warnings |
| `rollback` | Reverts batch operations cleanly | Blocks silent deletion of live or edited pages |

---

## 3. Asynchronous Chunk Processing & Worker Safety

1. **Request Time Budgeting**: Each chunk runner execution enforces a strict time budget (default 20 seconds) and memory limit check (e.g. 80% of `memory_limit`) to avoid PHP max execution time timeouts.
2. **Atomic Worker Locking**: Worker instances generate a unique worker token (e.g. `worker_66f..._a1b2c3`) and atomically lock items using SQL `UPDATE ... WHERE status = 'pending' LIMIT %d`.
3. **Stale Recovery**: If a worker crashes or times out, orphaned `processing` items whose locks are older than 15 minutes are automatically reset back to `pending`.
4. **Retry Caps**: Every item tracks `attempts`. If an item encounters an error, it is retried up to `max_attempts` (default 3) before being permanently marked `failed`.
