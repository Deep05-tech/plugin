# Internal Quality Evaluation Engine

## Overview

The **Internal Quality Engine** evaluates programmatic landing pages across 12 distinct quality dimensions to generate an **Internal Content Quality Score** (0–100) and assign an actionable operational status (**Pass**, **Warning**, **Needs Review**, or **Fail**).

> [!IMPORTANT]
> **Internal Heuristic Disclaimer**: The Internal Content Quality Score is strictly an internal algorithmic heuristic designed to help site administrators prevent thin, repetitive, or low-quality programmatic page production. It **NEVER** claims, promises, or predicts search engine rankings or indexation by Google or other search engines.

---

## 12 Quality Dimensions & Scoring Matrix

| Dimension | Description | Penalty / Logic | Severity |
| :--- | :--- | :--- | :--- |
| **1. Completeness** | Presence of all 8 core page sections (`hero`, `introduction`, `service_overview`, `benefits`, `process`, `local_relevance`, `faqs`, `final_cta`). | -6.0 per missing section | Major |
| **2. Content Length / Thin Content** | Word count validation (0 words = empty, <100 words = thin, <300 words = modest). | 0 words: -35.0<br><100 words: -15.0<br><300 words: -5.0 | Critical / Warning / Info |
| **3. Content Similarity** | 3-gram word shingling & Jaccard index against partitioned candidate pages in the same state/service. | 100% duplicate: -30.0<br>≥80% similarity: -15.0 | Critical / Warning |
| **4. City Relevance** | Target city mention count in page body. | 0 mentions: -25.0<br><2 mentions: -8.0 | Critical / Warning |
| **5. Service Relevance** | Target service name mention count in page body. | 0 mentions: -25.0 | Critical |
| **6. Intent Cannibalization** | Duplicate primary keyword or synonym service competition in the same city. | 100% keyword duplicate: -100.0 / fail<br>Synonym clash: -15.0 | Critical / Warning |
| **7. Unresolved Placeholders** | Unreplaced tokens (`[City]`, `{{...}}`, `___`, `TODO`, `TBD`, `Lorem Ipsum`). | -15.0 per occurrence | Critical |
| **8. Unsupported Local Claims** | Superlative/unverifiable claims (`guaranteed #1 rank`, `voted #1 best`). | -8.0 per occurrence | Warning |
| **9. Generic AI Clichés** | Overused repetitive AI phrases (`in today's fast-paced world`, `delve deep into`, `tapestry`). | -3.0 per occurrence | Info |
| **10. Keyword Density** | Primary keyword density bounded by optimal range (1.5% – 3.0%). | >3.0% (stuffing): -20.0<br><1.5% (low): -4.0 | Critical / Info |
| **11. Internal Linking & Orphan Check** | Presence of internal links (≥2 recommended) and parent city taxonomy active status. | 0 links: -5.0<br>Inactive city orphan: -5.0 | Warning / Warning |
| **12. SEO Metadata Quality** | Custom SEO title and meta description override presence. | Missing title & description: -5.0 | Info |

---

## Status Tiers & Thresholds

1. **Pass (Score ≥ 85)**:
   - High content completeness, high local relevance, proper internal links, optimal keyword density (1.5%–3.0%), zero critical or major violations.
2. **Warning (Score 65–84)**:
   - Modest content length, minor keyword density deviation, or high similarity warning (80%–99%).
3. **Needs Review (Score 40–64 or Major Issues)**:
   - Multiple missing sections, thin content warning (<100 words), or moderate intent overlap.
4. **Fail (Score < 40 or Critical Violations)**:
   - Unresolved placeholders (`[City]`, `Lorem Ipsum`), 0 words empty content, >3.0% keyword stuffing, 100% duplicate content, or missing city/service context.

---

## False-Positive Dismissal Workflow

Administrators can review flagged issues and dismiss false positives with an audit-logged justification:
- When an issue is dismissed, its penalty deduction is automatically removed from active score calculations.
- Dismissals are stored per page/version in `wp_seo_quality_dismissals`.
- Any subsequent content update or version rollback automatically invalidates the evaluation (`is_stale = 1`), prompting a fresh scan.
