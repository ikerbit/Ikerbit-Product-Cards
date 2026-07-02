# Roadmap

## Implemented

### v2.5.0 (current)
- Plugin at version 2.5.0 (exact changes: TODO — needs confirmation from developer)

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
