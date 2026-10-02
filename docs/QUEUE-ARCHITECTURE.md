# Queue & Worker Architecture

The **SEO City Pages Queue Engine** decouples heavy page generation and publishing workloads from user HTTP requests.

---

## 1. Data Schema

### `seo_bulk_jobs`
- `job_id`: Primary auto-increment identifier.
- `job_type`: Type of operation (`create_pages`, `generate_content`, `publish_pages`, etc.).
- `status`: `pending`, `processing`, `paused`, `completed`, `failed`, `cancelled`.
- `total_items`, `processed_items`, `successful_items`, `failed_items`, `skipped_items`.
- `created_by`, `created_at`, `started_at`, `completed_at`, `params_json`.

### `seo_bulk_job_items`
- `item_id`: Primary auto-increment identifier.
- `job_id`: Foreign key reference to parent job.
- `entity_type`: `city_page`, `city`, `service`, `csv_row`.
- `entity_id`: ID of target entity.
- `status`: `pending`, `processing`, `completed`, `failed`, `skipped`.
- `worker_token`: Unique token of the active runner process.
- `locked_at`: Timestamp when the item was claimed.
- `attempts`: Counter of execution attempts.
- `max_attempts`: Threshold before permanent failure (default 3).
- `error_message`, `payload_json`, `result_json`.

---

## 2. Concurrency & Claiming Protocol

```sql
UPDATE wp_seo_bulk_job_items
SET status = 'processing',
    worker_token = %s,
    locked_at = %s,
    attempts = attempts + 1
WHERE job_id = %d AND status = 'pending'
ORDER BY item_id ASC
LIMIT %d;
```

This ensures that multiple concurrent workers or background crons can never process the same item simultaneously.
