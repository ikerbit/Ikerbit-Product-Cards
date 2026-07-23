# Roadmap

## Implemented

### v2.7.1 — Search, duplicates & improvements
- Search bar in "Todas las Ofertas" (7 fields)
- Duplicate finder and cleanup (by URL)
- Markdown support for custom_description
- Custom description visible in cards
- Product code + Edit in Top 10 stats
- Dedup fix: works without country field
- Consistent DM Sans font for all section titles

### v2.7.0 — Product code & upsert
- New field `ipc_product_code` for unique product identification (ASIN, etc.)
- Upsert: POST to create endpoint auto-updates if matching product_code + marketplace + country exists
- `product_code` shortcode attribute

### v2.6.0 — Internationalization: per-country offers
- New fields: `ipc_country`, `ipc_language`, `ipc_currency`, `ipc_custom_description`
- Country detection via `navigator.language` + `Accept-Language` + cookie
- Shortcode `country` attribute with `auto` for automatic visitor country detection
- Dynamic currency symbols in cards and single template
- Custom description override per offer
- Settings: default country and auto-filter toggle
- Editor: international fields and custom description section

### v2.5.2 — Update checker validation
- Version bump to validate Plugin Update Checker functionality

### v2.5.1 — Plugin Update Checker
- Integration of Plugin Update Checker (YahnisElsts v5.7) for automatic updates from GitHub releases
- WordPress admin will show update notifications for new GitHub releases

### v2.5.0 — Taxonomies, GA4, Statistics & Documentation
- Taxonomies `ipc_marca` and `ipc_producto` for brand/product classification
- Wildcard support (`*`) in shortcodes for brand and product
- GA4 integration with toggle and Measurement ID
- REST DELETE endpoint for offer removal
- Statistics dashboard with KPIs, top 10, CTR by category/marketplace
- Detection of offers with visits but no clicks
- Project documentation (AGENTS.md + docs/)

### v2.3 — Edit Page, Widget & Counters
- Edit page (`ipc-edit.php`) — full field editing from WordPress admin
- Visit counter (auto-registered on single offer page load)
- Click counter (registered on `mousedown` on buy button)
- `ipc_ultimo_click` — last click timestamp
- Statistics page: CTR by offer
- Visits and Clicks columns in offers list with sortable headers
- `data-post-id` attribute on cards for JS tracking

### v2.2 — Counters & Initial Widget
- REST endpoints for visits and clicks
- Meta fields: `ipc_visitas`, `ipc_clicks`, `ipc_ultimo_click`
- Initial widget (since modified/redesigned in v2.3)
- Statistics submenu in admin
- Shortcode `orderby="clicks"` and `orderby="visitas"`

### v2.1 — Homepage Toggle
- Toggle in settings to use `/ofertas/` as site front page
- Auto-creates WordPress page and assigns as front page
- Warning explanation in settings

### v2.0 — Modular Architecture
- CSS extracted to `ipc-styles.css` loaded via `wp_enqueue_style()`
- Templates moved to `templates/` directory
- `template_include` for single, taxonomy, archive
- `archive-ipc_oferta.php` with category pills and offer grid
- `taxonomy-ipc_categoria.php` with sort, pagination, breadcrumb
- Rewrite slug taxonomy changed to `/ofertas/`
- `has_archive => 'ofertas'` enabled in CPT

### v1.5
- Cards with overlay link to offer page
- Title as `<h3>` with link
- White background on card image area
- `ipc_video` — YouTube, Vimeo, .mp4, .m3u8 (HLS.js)
- Endpoint returns permalink URL on create/update
- `rel="sponsored nofollow noopener"` on buy buttons
- "Ver más detalles" button on cards

### v1.3 – v1.4
- `ipc_fecha` field
- `ipc_descripcion` field
- `ipc_imagenes` — JSON array of additional image URLs
- Single template with gallery, Schema.org, breadcrumb, related offers
- `condescuento="si"` shortcode attribute
- `orderby="descuento"` shortcode attribute
- Title truncation to 79 characters on cards
- Offers list page with sortable columns
- Delete button in list

### v1.2
- Custom sidebar menu with icon
- Settings and Offers List submenus
- List page with image, price, discount, marketplace, category, shortcode

### v1.0 – v1.1
- CPT `ipc_oferta` with taxonomy `ipc_categoria`
- Meta fields: up to `badge`
- REST endpoints: create and update offers
- X-IPC-Secret authentication
- Shortcodes: `[oferta]` and `[ofertas]`
- CSS with DM Sans + Syne fonts, responsive cards

---

## Planned (from CHANGELOG v2.4)

- Country detection by IP for visits and clicks
- `ipc_visitas_paises` and `ipc_clicks_paises` as JSON (`{"ES":45,"MX":12}`)
- Advanced statistics: CTR by category, CTR by marketplace, discount/CTR correlation, visits without clicks
- Time evolution of clicks (individual timestamps per visit and click)
- Statistics by date range: last day, last week, month, quarter, year
- Site-wide aggregated totals with period-over-period comparison
- Preparation for country/currency-based offers

## Possible Future Improvements (from CHANGELOG)

- Statistics widgets in admin dashboard
- `ipc_links` — array of affiliate links for multiple marketplaces
- Automatic country detection for filtered offers
- Multi-currency support
- Bulk CSV import from n8n
- Auto-insertion of offer shortcodes in posts of same category (toggle in settings)
