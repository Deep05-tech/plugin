# Structured Logging & Immutable Audit Trail

---

## 1. Structured Logging Engine
The plugin provides high-performance file-based logging with structured context scrubbing:
- **Log Levels**: `DEBUG`, `INFO`, `WARNING`, `ERROR`, `CRITICAL`.
- **Categories**: `general`, `ai`, `seo`, `queue`, `database`, `security`, `import`, `export`, `integrity`.
- **Correlation IDs**: Every request/batch receives a standardized correlation token (e.g. `SCP-20261001-A1B2C3`) linking API requests, jobs, and errors.
- **Secret Scrubbing**: Automatically scrubs API keys, auth headers, passwords, and tokens before logging.
- **Rotation & Retention**: Automatically rotates files at 5MB and prunes entries older than configured retention limits (7, 30, 90, 180 days).

---

## 2. Immutable Append-Only Audit Log
All business-critical operations are persisted to `wp_seo_audit_logs`:
- Content creation, approvals, rejections, and publishing.
- Rollbacks and manual content overrides.
- Settings changes and repair executions.
- Security-relevant events (failed nonces, unauthorized capability access, SSRF attempts).
- **Zero In-UI Deletions**: The audit trail is strictly append-only from the admin interface.
