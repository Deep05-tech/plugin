# Home Services & Multi-Trade Architecture (6 Pillars & 21 Trades)

> **Cross-Conversation Sync Briefing**  
> **Source Session**: [Session 9988a582-c406-4399-ae65-0b692377abbb](conversation://9988a582-c406-4399-ae65-0b692377abbb) (2026-11-03)  
> **Target Session**: [SEO Plugin Chat](conversation://84a64bfa-226f-47f9-9bd7-e5aacfa4f3e5)  
> **Status**: Fully Implemented, Synchronized & Verified (100% Test Passing)

---

## 1. Executive Summary

Previously, the plugin generated city pages focused strictly on single procedural niches (e.g., Dental Clinics, Law Firms, Medical Practices).  
In today's session (2026-11-03), the user requested a **Multi-Trade Home Services Landing Architecture**:
- A single comprehensive landing page that encompasses **all residential contractor services** across a metro market.
- Grouped into **6 Core Pillars** covering **21 High-Demand Trades** (HVAC, Plumbing, Electrical, Roofing, Handyman, Landscaping, Cleaning, Solar, and more).
- Tailored marketing messaging: positioning **Vispan Solutions** as the growth marketing engine delivering 100% exclusive inbound phone calls, territory exclusivity, Google Guaranteed / LSA onboarding, and Google Maps 3-Pack domination for home service contractors.
- **Strict Backward Isolation Constraint**: Zero design, layout, content, or link alterations to earlier generated pages (Law Firms, Dental Clinics, etc.). Existing pages must remain 100% untouched.
- **Brand Palette**: Strictly preserved Vispan brand colors: Deep Navy (`#02426A` / `#003865`), Royal Blue (`#0B63F6`), Light Accent (`#EBF3FB`, `#CCE0ED`), Pure White (`#FFFFFF`).

---

## 2. Complete 6 Pillars & 21 Trades Specification

| # | Pillar | Category Slug | Trades Included & Exact Editorial Scope |
|---|---|---|---|
| **1** | **Core Systems & Emergency Trades** | `core` | • **HVAC Services**: Heating, ventilation, air conditioning repair, system replacements, and seasonal duct maintenance.<br>• **Plumbing Services**: Drain clearing, pipe repair, leak detection, water heater installations, and emergency plumbing.<br>• **Electrical Services**: Residential rewiring, breaker panel upgrades, lighting installations, EV charger setups, and smart home hardwiring. |
| **2** | **Exterior & Structural Maintenance** | `exterior` | • **Roofing & Siding**: Roof repairs, total replacements, leak interventions, gutters, and vinyl/wood exterior siding.<br>• **Gutter Cleaning & Installation**: Gutter guard installations, seasonal cleanouts, and drainage alignments.<br>• **Garage Door Repair**: Spring replacements, opener installations, track realignments, and 24/7 emergency dispatch.<br>• **Pressure Washing**: Exterior siding, driveway degreasing, deck revitalization, and commercial surface stripping. |
| **3** | **Interior & Handyman Trades** | `interior` | • **General Handyman Work**: Fixture installations, drywall patching, minor carpentry, door repairs, and odd jobs.<br>• **Painting**: Interior and exterior painting, trim restoration, cabinet refinishing, and deck staining.<br>• **Flooring & Carpeting**: Hardwood installation, tile setting, carpet fitting, floor sanding, and luxury vinyl planking.<br>• **Carpentry Services**: Custom cabinetry, trim and molding, framing, staircases, and built-in shelving. |
| **4** | **Outdoor Living & Yard Care** | `outdoor` | • **Lawn Care & Landscaping**: Landscape architecture, hardscaping, lawn fertilization, aerating, and sod installations.<br>• **Pest Control & Wildlife Removal**: Termite extermination, rodent exclusion, bed bug eradication, and humane wildlife removal.<br>• **Pool Maintenance**: Chemical balancing, pump and filtration repairs, liner replacement, and seasonal winterization.<br>• **Snow & Ice Removal**: Driveway plowing, commercial lot de-icing, and emergency salting. |
| **5** | **Residential Cleaning & Sanitation** | `cleaning` | • **Housekeeping & Maid Services**: Recurring residential cleaning, deep scrubbing, move-in/move-out cleans, and turnover prep.<br>• **Junk Removal & Hauling**: Property cleanouts, construction debris hauling, bulky item disposal, and eco-friendly recycling.<br>• **Window Cleaning**: Residential exterior/interior panes, multi-story glass, screen restoration, and track cleaning. |
| **6** | **Safety, Security & Energy Management** | `safety` | • **Home Security Systems**: Smart alarm installations, 24/7 CCTV surveillance, biometric access, and motion sensor grids.<br>• **Locksmith Services**: Emergency lockouts, rekeying, high-security deadbolts, smart keypads, and access control.<br>• **Solar Power & Energy Management**: Photovoltaic panel design, battery storage backups, inverter repairs, and energy efficiency audits. |

---

## 3. Files Modified & Technical Implementation

Both plugin installations are kept 100% in sync:
- `/home/uday/Local Sites/demo/app/public/wp-content/plugins/seo-city-pages`
- `/home/uday/Agents/SEO Plugin`

### A. `includes/AI/Prompt/class-prompt-builder.php`
- **Rule 11 Added**: Guides the LLM on the 6 Pillars & 21 Trades framework.
- **Contractor B2B Entity Targeting**:
  - Instructs the AI to address contractor business owners and dispatchers (e.g. 'home service contractors', 'HVAC & plumbing companies', 'field service contractors').
  - Emphasizes Vispan Solutions providing territory exclusivity, Google Guaranteed / LSA badges, and exclusive inbound calls.

### B. `includes/Elementor/class-content-context.php`
- **`render_home_services_trades_html(): string`**:
  - Outputs a dark navy ecosystem header banner with live metrics (21 Trades, 6 Pillars, 100% Exclusive Leads).
  - Renders 7 interactive filter tabs (`All Trades (21)`, `Core & Emergency (3)`, `Exterior & Structural (4)`, `Interior & Handyman (4)`, `Outdoor & Yard Care (4)`, `Cleaning & Sanitation (3)`, `Safety & Solar (3)`).
  - Renders 21 trade cards with custom SVG icons, category tags, scope description, highlight badges, and Vispan hover elevation styling.
  - Embeds lightweight vanilla JavaScript functions `scpFilterTrades(category, btn)` for real-time tab switching and `scpSelectTrade(tradeName)` for one-click smooth-scroll and auto-filling the proposal inquiry form.
- **`render_industry_verticals_html(): string`**:
  - Dynamically inspects `$service`. If it matches Home Services / Home Cleaning / Contractors, it automatically serves `render_home_services_trades_html()`.
  - For Dental Clinics, Law Firms, or any other niche, it continues to serve the original 10 agency services without any deviation.
- **`render_inquiry_form()`**:
  - Dynamically prepends contractor trade options (HVAC Marketing, Plumbing SEO, Roofing Lead Gen, etc.) to the service dropdown when on a Home Services page.
- **`normalize_target_entity_fumbles()`**:
  - Adds normalization rules ensuring 'home services' isn't accidentally misused as a company noun (replaces 'competition of home services' -> 'home service contractors').

### C. `includes/Elementor/class-elementor-integration.php`
- **Shortcodes Registered**:
  - `[seo_home_services_trades]`
  - `[seo_trade_directory]`
- **Tokens Registered in `filter_rendered_content()`**:
  - `{{seo_home_services_trades}}`
  - `{{seo_trade_directory}}`
  - `{{seo_industry_verticals}}` dynamically resolves to the 21 trades for Home Services pages.

---

## 4. Automated Verification & Test Suites

Two automated suites are located in `tests/`:

1. **`tests/test-inquiry-form.php`**:
   - **40/40 assertions passed (100%)**.
   - Verifies Hero and Bottom proposal inquiry forms, honeypot protection, AJAX submissions, and lead email routing.

2. **`tests/test-home-services.php`**:
   - **24/24 assertions passed (100%)**.
   - Test 1: Backward compatibility for Dental Clinic (10 agency services, 0 trade leakage).
   - Test 2: Exactly 21 trade cards rendered across all 6 pillars for Home Services.
   - Test 3: Category filter buttons and vanilla JS functions verified.
   - Test 4: Shortcodes `[seo_home_services_trades]` and tokens `{{seo_home_services_trades}}` verified.
   - Test 5: Dynamic inquiry form adaptation verified.

Run tests anytime with:
```bash
php "/home/uday/Agents/SEO Plugin/tests/test-home-services.php"
php "/home/uday/Agents/SEO Plugin/tests/test-inquiry-form.php"
```

---

## 5. Continuity Guide for Next Steps in this Chat

When continuing work in this chat or executing new features:
1. **No explanation needed**: All context regarding the 6 Pillars, 21 Trades, contractor positioning, and backward isolation is documented here.
2. **Synchronized Directories**: Both `/home/uday/Local Sites/demo/app/public/wp-content/plugins/seo-city-pages` and `/home/uday/Agents/SEO Plugin` are identical.
3. **Elementor Templates**: Any Elementor template with `{{seo_industry_verticals}}` or `{{seo_home_services_trades}}` will automatically display the complete interactive 21-trade directory when a Home Services page is viewed.
