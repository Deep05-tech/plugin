# Diagnostics & Integrity Scanner

---

## 1. Relational & Orphan Scanner
The Integrity Scanner analyzes foreign key chains and parent-child relationships across all 11 database tables:
- **Orphaned States/Cities**: Detects geographical entities missing parent Countries or States.
- **Orphaned City Pages**: Detects landing pages referencing deleted cities or services.
- **Orphaned Content Versions**: Detects version records referencing nonexistent city pages.
- **Orphaned Job Items**: Detects task items referencing nonexistent parent jobs.

> **Safety Rule**: The scanner is strictly read-only. It identifies and reports orphan counts and entity IDs with zero destructive auto-deletions.

---

## 2. Cross-Contamination Scanner
Detects logical misalignment between page taxonomies and generated AI content:
- Evaluates whether a landing page (e.g. *Rajkot + SEO Agency*) contains alien location references (e.g. *Ahmedabad*) or alien service terms.
- Scans `content_json` payloads against target city and service entities.
- Flags mismatches for administrative review.

---

## 3. Safe Internal URL Health Checker (SSRF Defense)
- Probes landing page endpoints to verify HTTP response codes (200 OK), HTML `<title>` tags, canonical tags, and meta robots tags.
- **SSRF Defense Protocol**:
  - Enforces strict hostname matching against `home_url()`.
  - Rejects foreign hostnames, private metadata IP ranges (e.g. `169.254.169.254`), and unsupported URI schemes.
  - Caps redirects and enforces 8-second request timeouts.
