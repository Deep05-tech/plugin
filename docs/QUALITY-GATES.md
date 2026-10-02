# Pre-Publication Quality Gates & Invalidation Lifecycle

## Overview

The **Quality Gate Manager** (`SeoCityPages\Quality\Quality_Gate_Manager`) enforces pre-publication quality standards before any programmatic city page can transition to `published` status in WordPress.

---

## Gate Modes & Policies

The quality gate mode can be configured globally in **SEO City Pages $\rightarrow$ Quality Dashboard**:

| Mode | Key | Behavior | Intended Use Case |
| :--- | :--- | :--- | :--- |
| **Off** | `off` | Allows publication immediately without checking quality scores or warnings. | Local staging tests & debug mode. |
| **Warnings Only** | `warnings_only` *(Default)* | Evaluates quality and records metrics/warnings, but permits publication. | Safe default for migrating existing catalogs. |
| **Block Critical** | `block_critical` | Hard-blocks publication if page has critical violations (unresolved placeholders, empty content, 100% duplicate, keyword stuffing > 3%). | High-volume programmatic production pipelines. |

---

## Automatic Stale Invalidation Lifecycle

Quality evaluations remain valid until underlying page content changes. The system automatically marks evaluations stale (`is_stale = 1`) whenever:
1. **Manual Content Edit**: Administrator updates text or markup via Content Editor (`Status_Workflow::save_manual_edit()`).
2. **AI Content Regeneration**: A new AI content version is generated.
3. **Version Restoration / Rollback**: An earlier content version is restored (`Status_Workflow::restore_version()`).
4. **Taxonomy & Relational Changes**: City or service names are updated.

When an invalidated page is scheduled for publication or viewed in the Quality Dashboard, a fresh background or synchronous quality evaluation is automatically re-run.

---

## On-Demand AI Review Advisor

- **Role**: Supplemental advisory critique powered by OpenAI for complex edge-case page evaluations.
- **Safety**: 100% read-only. The AI Review Advisor **CANNOT** alter database records, modify page copy, or publish content automatically.
- **Cost Efficiency**: Standard quality scans execute 100% deterministically in PHP with 0 external API calls.
