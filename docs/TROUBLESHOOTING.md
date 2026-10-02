# Troubleshooting & Diagnostic Guide

---

## Common Issues & Remediation Matrix

| Symptom / Error | Root Cause | Remediation Steps |
| :--- | :--- | :--- |
| **City page returns 404 Not Found** | WordPress rewrite rules out of sync | Click **Refresh Rewrite Rules** in `System Health -> Repair Center`. |
| **Elementor dynamic tags missing** | Elementor plugin disabled or not loaded | Verify Elementor is active. Check `System Health -> Elementor`. |
| **Queue not processing in background** | Server WP-Cron disabled without system crontab | Ensure system cron is configured to hit `wp-cron.php` regularly. |
| **Orphaned records detected** | Raw taxonomy deletion occurred | Review orphaned IDs in `System Health -> Integrity Scan`. |
| **Stale worker locks** | PHP process timed out during heavy batch | Run **Recover Stale Queue** in `System Health -> Repair Center`. |

---

## Support Bundle Generation
When escalating issues to engineering:
1. Navigate to `SEO City Pages -> System Health`.
2. Click **Download Support Bundle**.
3. A sanitized JSON diagnostic report (with zero API keys, passwords, or secrets) will download for review.
