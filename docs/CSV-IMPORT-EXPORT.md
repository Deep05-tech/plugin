# CSV Import & Export System

The **SEO City Pages CSV Engine** facilitates enterprise-grade spreadsheet ingestion and export for tens of thousands of programmatic SEO landing pages.

---

## 1. Security & Protection

### Formula Injection Neutralization
Spreadsheet applications (Microsoft Excel, LibreOffice Calc, Google Sheets) execute formulas if a cell starts with special operators.
The CSV engine sanitizes all incoming and outgoing text fields:
- Characters checked: `=`, `+`, `-`, `@`
- Neutralization: Prepends a single quote `'` to the cell content before parsing or export formatting.

### Input Sanitization & Type Coercion
- Text values stripped of malicious HTML or unbalanced tag injections via `sanitize_text_field()` and `wp_kses_post()`.
- Numeric coordinates (`latitude`, `longitude`) validated against strict WGS84 ranges (-90 to 90, -180 to 180).
- State/City identifiers validated against normalized slugs.

---

## 2. Structural Preview Mode (0 DB Writes)

Before executing any database insertion:
1. `CSV_Validator::preview()` inspects the full CSV stream.
2. Identifies total row counts, valid rows, duplicates, and column mapping.
3. Issues **Data Quality Warnings** (e.g. placeholder locations like "Unknown", "Out of State", or "N/A") without hard-failing the import.
4. Returns an interactive summary modal to the user before queuing the import job.

---

## 3. High-Performance Streamed Export

When exporting datasets of up to 50,000+ pages:
- **Zero In-Memory Array Buffering**: Rows are fetched from the database in cursor batches (e.g. 500 records per query).
- **Direct Output Stream**: Rows are emitted directly to `php://output` via `fputcsv()`.
- **PHP 8.4 Compliance**: Avoids deprecated empty string escape parameters in `fputcsv()`.
- **Customizable Filter Matrix**: Export by status (`all`, `published`, `draft`), city, state, or service.
