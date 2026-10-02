# SEO Metadata, Template Tokens & Schema Guide

## 1. Automated Template Engine

The SEO template engine deterministically parses dynamic placeholder tokens without regex ambiguity or syntax residues:

| Placeholder Token | Replacement Value | Example Output |
| :--- | :--- | :--- |
| `{service}` | Service Name | `SEO Agency` |
| `{city}` | Target City Name | `Orlando` |
| `{state}` | Full State Name | `Florida` |
| `{state_code}` | State ISO/Postal Code | `FL` |
| `{country}` | Country Name | `United States` |
| `{country_code}` | ISO 2-letter Country Code | `US` |
| `{site_name}` | WordPress Site Title | `Vispan Solutions` |
| `{current_year}` | Active 4-digit Calendar Year | `2026` |

---

## 2. Character Length Validation Rules

`SEO_Validator` continuously checks length metrics:
- **SEO Title**:
  - Recommended length: 50 – 60 characters.
  - Warning triggered if length > 60 (risk of SERP truncation) or < 20 (thin title).
- **Meta Description**:
  - Recommended length: 140 – 160 characters.
  - Warning triggered if length > 160 or < 50.

---

## 3. JSON-LD Schema Structured Data Specification

Schema structured data is generated with zero fabricated ratings or false review counts.

### 3.1 Service / LocalBusiness JSON-LD Structure
```json
{
  "@context": "https://schema.org",
  "@type": "Service",
  "name": "SEO Agency in Orlando",
  "description": "Professional search engine optimization services in Orlando, FL.",
  "url": "https://example.com/service-areas/orlando/seo/",
  "areaServed": {
    "@type": "City",
    "name": "Orlando",
    "containedInPlace": {
      "@type": "AdministrativeArea",
      "name": "Florida",
      "addressCountry": "US"
    }
  },
  "provider": {
    "@type": "Organization",
    "name": "Vispan Solutions",
    "url": "https://example.com/"
  }
}
```
