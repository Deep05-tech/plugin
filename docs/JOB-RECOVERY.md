# Job Recovery & Rollback Engine

Fault tolerance and safety are foundational to the bulk operations architecture.

---

## 1. Fault Recovery Matrix

| Scenario | Detection Mechanism | Recovery Action |
| :--- | :--- | :--- |
| **Worker Crash / Timeout** | `locked_at` older than 15 minutes in `processing` status | `recover_stale_items()` resets status to `pending` |
| **Transient API Failure (AI/OpenAI)** | Handler catches HTTP/JSON exceptions | Increment `attempts`, mark `pending` for retry until `max_attempts` |
| **Server Restart** | Job in `processing` status with no active worker token | Admin poller or cron detects idle job and resumes batch claiming |
| **Permanent Entity Error** | Validation failure or missing taxonomy | Mark item `failed` with descriptive `error_message`, continue remaining items |

---

## 2. Safe Rollback Strategy

When rolling back a bulk creation or import job:
1. **Target Identification**: Queries all city pages tagged with the job's `import_job_id`.
2. **Safety Checks**:
   - If a page has `is_published = 1`, it is flagged with `Rollback Requires Review` and skipped from automatic deletion.
   - If a page has approved or manually edited versions (`is_manually_edited = 1` or status `approved`), automatic deletion is blocked to prevent data loss.
3. **Draft Cleanup**: Unmodified draft pages without approved content are safely removed along with associated draft content versions.
4. **Audit Logging**: All rollback decisions and skipped entities are logged to the job item's `result_json`.
