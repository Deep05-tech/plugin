# Internal Link Analyzer & Natural Linking Suggestions

## Overview

The **Internal Link Analyzer** (`SeoCityPages\Quality\Internal_Link_Analyzer`) inspects internal link connectivity, isolates orphan pages, and generates contextual natural link suggestions without mutating or modifying page content.

---

## Core Capabilities

### 1. Internal Link Extraction & Density
- Parses HTML body content and JSON payload representations.
- Unescapes serialized strings to extract all `<a href="...">` anchors.
- Filters anchors to identify internal links (relative links `/...` or links matching site domain).
- **Standards**:
  - `0 links`: Warning (`no_internal_links`, -5.0 penalty).
  - `< 2 links`: Info notice (`few_internal_links`).
  - `≥ 2 links`: Compliant.

### 2. Orphan Page Detection
A programmatic landing page is classified as an orphan (`is_orphan = 1`) if:
- The parent city or state taxonomy record is marked `inactive` or deleted.
- The page has zero incoming or outgoing relational pathway.

### 3. Non-Destructive Advisory Suggestions
- Suggests natural parent and sibling links based on geographical hierarchy:
  - Parent State Hub (e.g. "Explore all Florida SEO services")
  - Sibling Metro/City Pages (e.g. "Nearby service areas: Tampa, St. Petersburg")
  - Related Complementary Services in the same city (e.g. "Web Design in Orlando")
- **Safety Rule**: Suggestions are strictly advisory in the admin UI and **NEVER** injected into generated HTML automatically.
