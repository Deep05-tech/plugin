# OpenAI Integration Guide — SEO City Pages

## 1. Supported Models

The plugin supports OpenAI's modern JSON Schema Structured Output capable models:

| Model ID | Target Use Case | Recommended Temperature | Structured Output Support |
| :--- | :--- | :--- | :--- |
| `gpt-4o-mini` | High-speed, cost-effective bulk landing page generation (Default) | `0.7` | ✅ Strict `json_schema` |
| `gpt-4o` | Maximum creativity and deep analytical copywriting | `0.7` | ✅ Strict `json_schema` |
| `o3-mini` | Advanced reasoning and high-precision technical niches | `1.0` | ✅ Strict `json_schema` |

---

## 2. API Key Configuration

### Option A: Environment Variable / `wp-config.php` (Recommended for Production)
Add the constant to your `wp-config.php` above the `/* That's all, stop editing! */` line:
```php
define( 'SEO_CITY_OPENAI_API_KEY', 'sk-proj-your-actual-api-key-here' );
```
Or export it in your server environment:
```bash
export SEO_CITY_OPENAI_API_KEY="sk-proj-your-actual-api-key-here"
```

*When set via environment variable, the API key is never written to the WordPress database and cannot be read via standard database exports.*

### Option B: WordPress Admin Settings
1. Navigate to **SEO City Pages &rarr; Settings &rarr; AI Content Engine**.
2. Check **Enable OpenAI Content Generation**.
3. Enter your OpenAI secret key in the **OpenAI API Key** field.
4. Click **Save Changes**.

---

## 3. Review & Approval Workflow

```
[ Generate Content ] ──► [ Status: needs_review ] ──► [ Admin Review UI ]
                                                              │
                    ┌─────────────────────────────────────────┴─────────────────────────────────────────┐
                    ▼                                                                                   ▼
            [ Approve Version ]                                                                 [ Reject / Edit ]
                    │                                                                                   │
                    ▼                                                                                   ▼
   [ Live Page Hydrates New Version ]                                                [ Live Page Retains Prior Version ]
```

1. **Generation**: When bulk generation or single-page generation runs, the output is saved to `wp_seo_content_versions` with status `needs_review`.
2. **Review**: The administrator navigates to **SEO City Pages &rarr; City Pages** or the Content Review screen.
3. **Approval**: Clicking **Approve** sets the status to `approved` and updates the page's `current_version_id`.
4. **Instant Update**: The Elementor template dynamically hydrates the newly approved copy without requiring template regeneration or republishing.
