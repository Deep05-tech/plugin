# Production System Health & Monitoring

The **SEO City Pages Production System Health & Monitoring Architecture** provides automated observability across all subsystems, relational database tables, third-party integrations, and background worker queues.

---

## 1. Subsystem Health Matrix

| Subsystem | Health Indicators Checked | Threshold / Actionable Rule |
| :--- | :--- | :--- |
| **PHP & Server** | PHP Version, Memory Limit, Max Execution Time, Upload/Post limits | Flags Warning if memory < 128M; Critical if PHP < 7.4. |
| **WordPress Core** | WP Version, Multisite status, WP-Cron status | Flags Alternate WP-Cron if `DISABLE_WP_CRON` is active. |
| **Database Schema** | Schema version, 11 required tables, column integrity | Flags Warning on pending migrations, Critical on missing tables. |
| **Elementor Engine** | Elementor active status, Dynamic tag manager availability | Read-only check. Never touches or modifies Elementor files. |
| **Arolax Theme** | Active theme stylesheet, directory location | Read-only check. Never writes into `/wp-content/themes/arolax/`. |
| **SEO Provider** | Active provider (Native, Yoast, Rank Math, AIOSEO) | Flags Warning if multiple competing SEO plugins are active. |
| **OpenAI API** | Secret-masked key status, configured model, connection status | Zero secret exposure. Never executes ping on page loads. |
| **Queue Health** | Pending, running, completed, failed, and stale locks | Flags Warning if worker locks exceed threshold (default 30m). |
| **Storage & Security** | Directory write permissions, HTTPS / SSL status | Verifies write permissions on `wp-content/uploads/`. |

---

## 2. Plugin Safe Mode

When abnormal errors occur (e.g. OpenAI service outages or server resource constraints), administrators can activate **Plugin Safe Mode**:
- **AI Generation**: Suspended immediately.
- **Bulk Publishing**: Blocked to protect live site indexing.
- **Background Worker**: Suspends processing loops.
- **Public City Page Rendering**: **Uninterrupted** — continues serving cached and live pages normally.
- **Diagnostics & Repair Tools**: Remain fully accessible to administrators.
