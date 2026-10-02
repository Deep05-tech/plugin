# Scale Hardening (10 to 50,000+ Pages)

The plugin is tuned to maintain high throughput and low database resource contention at enterprise scale.

---

## 1. Database Indexing Strategy
Compound indexes are established across all high-cardinality tables:
- `seo_bulk_job_items`: `(job_id, status)`, `(status, locked_at)`, `(worker_token)`
- `seo_city_pages`: `(city_id, service_id)`, `(slug)`, `(status, is_published)`
- `seo_content_versions`: `(page_id, status)`, `(page_id, version_num)`

## 2. Memory & Query Management
1. **Unbuffered Cursors**: Large iteration loops bypass `get_results()` loading everything into memory, utilizing indexed `LIMIT / OFFSET` paging or streamed generators.
2. **Object Cache Clearance**: Explicit calls to `wp_cache_flush()` or selective cache invalidation prevent PHP memory bloat during massive chunk processing.
3. **Transaction Isolation**: Chunk operations commit after each item or small batch to minimize InnoDB row lock hold times.
