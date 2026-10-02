# Disaster Recovery & Incident Response

This runbook details recovery procedures for production outage scenarios.

---

## Scenario 1: OpenAI API Outage / Rate Limit Exhaustion
- **Detection**: OpenAI Health test returns HTTP 429 / 5xx or `Authentication` / `Rate Limit` error.
- **Impact**: AI content generation for queued items fails. Live published pages are unaffected.
- **Recovery Procedure**:
  1. Activate **Safe Mode** in `SEO City Pages -> System Health` to pause generation attempts.
  2. Verify API key credits and quota on platform.openai.com.
  3. Deactivate Safe Mode and click **Retry Failed Items** in the Job Queue Center.
- **Verification**: Run **Test OpenAI Connection** in System Health to confirm healthy status.

---

## Scenario 2: Worker Queue Crashes or Stale Locks
- **Detection**: Job Queue shows items stuck in `processing` longer than stale threshold.
- **Impact**: New items in the queue are blocked from executing.
- **Recovery Procedure**:
  1. Navigate to `SEO City Pages -> System Health -> Recovery Center`.
  2. Click **Recover Stale Queue**. Stale locks older than the threshold are atomically reset to `pending`.
- **Verification**: Progress meters resume advancing in the Job Queue Center.

---

## Scenario 3: Accidental Database Deletion / Backup Restoration
- **Detection**: Health check reports missing database tables or missing columns.
- **Impact**: Plugin queries fail or return 500 errors.
- **Recovery Procedure**:
  1. Restore the WordPress database backup.
  2. Navigate to `System Health -> Controlled Recovery Center`.
  3. Click **Repair DB Schema** to ensure all 11 tables and compound indexes exist.
  4. Click **Refresh Rewrite Rules** to re-register city page URL endpoints.
- **Verification**: Run **Run Full Integrity Scan** to verify 0 missing tables and 0 orphaned records.

---

## Scenario 4: Multiple SEO Plugin Conflicts
- **Detection**: SEO Health shows "Multiple SEO Plugins Detected (e.g. Yoast and Rank Math)".
- **Impact**: Potential duplication of meta description or title tags in `<head>`.
- **Recovery Procedure**:
  1. Decide on a primary SEO plugin (or Native fallback).
  2. Deactivate the duplicate SEO plugin in WordPress Plugins menu.
- **Verification**: Run **Check URL** on a sample page in System Health and verify only one `<meta name="description">` and `<title>` tag exist.
