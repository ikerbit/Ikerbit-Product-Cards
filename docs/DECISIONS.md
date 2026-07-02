# Architecture Decisions

This log records significant architectural decisions for the Ikerbit Product Cards plugin.

## Decisions

### AD-001: Procedural Programming Only

**Decision:** The entire codebase uses plain PHP functions. No classes, no OOP patterns, no interfaces.

**Context:** The plugin started as a single file and grew incrementally. The developer prefers procedural style for simplicity and low cognitive overhead.

**Evidence:** Zero class definitions in all `.php` files. All hooks use anonymous functions or named global functions. `ipc-edit.php` was extracted as a separate procedural file but follows the same pattern.

**Consequences:**
- Low barrier to entry for contributors familiar with WordPress procedural code.
- No dependency injection, service containers, or interfaces.
- All functions are in the global namespace (prefixed `ipc_`).

---

### AD-002: Posts as Primary Data Storage

**Decision:** All offer data is stored as WordPress posts (`ipc_oferta` CPT) with post meta. No custom database tables.

**Context:** WordPress provides CPT and post meta APIs that handle CRUD, caching, and queries.

**Evidence:** `wp_insert_post`, `update_post_meta`, `get_post_meta`, `WP_Query` used throughout. No raw SQL queries.

**Consequences:**
- Leverages WordPress object cache and query infrastructure.
- Taxonomy categorization is built-in.
- No migration scripts needed for WordPress upgrades.
- All meta is serialized/deserialized by WordPress (JSON fields use `json_encode`/`json_decode`).

---

### AD-003: REST API for Automation (Not WP-CLI)

**Decision:** External automation (n8n) creates and updates offers via REST API, not WP-CLI commands.

**Context:** n8n has native HTTP request nodes. REST API requires no server-side access beyond HTTP.

**Evidence:** Five registered REST routes under `ipc/v1` namespace. No WP-CLI commands exist in the codebase.

**Consequences:**
- Works with any HTTP-capable automation tool.
- Authentication via shared secret header (`X-IPC-Secret`).
- No need for SSH or WP-CLI installation on n8n server.

---

### AD-004: Shared Secret Authentication

**Decision:** REST API uses a simple shared secret (`X-IPC-Secret` header) instead of OAuth, JWT, or Application Passwords.

**Context:** The API is designed for machine-to-machine communication (n8n → WordPress). A shared secret is the simplest secure option for this use case.

**Evidence:** `ipc_check_secret()` function compares header value against `get_option('ipc_secret')`. Default value: `CAMBIA_ESTE_SECRET`. Only create/update/delete endpoints require auth.

**Consequences:**
- Simple to configure in both WordPress and n8n.
- No token refresh or expiration logic needed.
- Visit/click endpoints are public (no auth) for frontend JS tracking.
- Secret is stored as a WordPress option (plaintext in `wp_options` table).

---

### AD-005: String-Only Meta Field Types

**Decision:** All `register_post_meta` calls use `'type' => 'string'`, even for numeric fields.

**Context:** Simplicity and consistency. WordPress REST API stores meta as strings by default. Numeric casting happens at display time.

**Evidence:** In `rest_api_init`, all 16 fields registered with `'type' => 'string'`. `intval()` and `floatval()` used where numeric values are needed (e.g., `intval(get_post_meta($post_id, 'ipc_visitas', true))`).

**Consequences:**
- No validation errors from REST API type mismatches.
- Must cast at read time for numeric operations.
- Sorting by `meta_value_num` works correctly because `WP_Query` casts internally.

---

### AD-006: Inline Admin CSS/JS

**Decision:** Admin page CSS is written inline within `<style>` tags in PHP functions. No separate admin CSS or JS files.

**Context:** The admin pages are internal tools with moderate complexity. Inline styles avoid additional HTTP requests and file management overhead.

**Evidence:** `ipc_edit_page()` contains `<style>` block. `ipc_stats_page()` contains a `<style>` block. `ipc_ofertas_page()` has no inline styles.

**Consequences:**
- No admin-specific asset files to maintain.
- Styles can use PHP variables inline.
- Harder to reuse styles across admin pages.
- No caching benefit for admin CSS.

---

### AD-007: BEM-Like CSS Naming

**Decision:** CSS classes use a BEM-like convention: `.ipc-card` (block), `.ipc-card__title` (element), `.ipc-card--large` (modifier).

**Context:** The plugin's CSS must coexist with any WordPress theme. Scoped naming reduces collision risk.

**Evidence:** `ipc-styles.css` defines classes following `ipc-{block}`, `ipc-{block}__{element}`, `ipc-{block}--{modifier}`. Marketplace-specific classes use `ipc-mp--{marketplace}`.

**Consequences:**
- Low collision risk with theme CSS.
- Self-documenting class names.
- No CSS-in-JS or preprocessor needed.
- CSS classes are public API (BACKLOG C3 notes `!important` can conflict).

---

### AD-008: Anonymous Function Hooks

**Decision:** All WordPress action/filter hooks use closures, not named function callbacks or class methods.

**Context:** Procedural codebase. Closures keep initialization logic close to the hook definition.

**Evidence:** Every `add_action` and `add_filter` in the codebase uses `function() { ... }`. Arrow functions used for simple `permission_callback` values.

**Consequences:**
- Hooks cannot be unhooked by name (no `remove_action` with function name).
- Hook logic is defined where it's registered — good for readability in single files.
- Can create deep nesting if hooks grow complex.

---

### AD-009: European Price Display

**Decision:** Prices are stored with `.` as decimal separator and displayed with `,` as decimal separator (European format, e.g., `89,99€`).

**Context:** The plugin targets Spanish-speaking markets.

**Evidence:** `str_replace('.', ',', $precio)` used in `ipc_render_card()` and single template. Price field accepts `89.99` in API and stores as string.

**Consequences:**
- Consistent display for European users.
- Must convert back to `.` for numeric operations or comparisons.

---

### AD-010: `mousedown` for Click Tracking

**Decision:** Click events on buy buttons are tracked via `mousedown` instead of `click`.

**Context:** `mousedown` fires before the browser navigates away, ensuring the tracking request is sent. `click` may be cancelled by the navigation.

**Evidence:** `document.addEventListener('mousedown', ...)` in the JS tracking code at line 971. CHANGELOG v2.3 documents this as intentional: "compatible con botón derecho y Ctrl+Click".

**Consequences:**
- Higher tracking reliability for affiliate clicks.
- Fires on both left click, right click, and Ctrl+Click.
- Slightly aggressive — fires even if user releases button outside link.

---

### AD-011: Google Fonts via CDN

**Decision:** Fonts (DM Sans, Syne) are loaded from Google Fonts CDN rather than bundled locally.

**Context:** The plugin is distributed without a build step. CDN fonts are cached globally and reduce plugin size.

**Evidence:** `wp_enqueue_style` loads `https://fonts.googleapis.com/css2?family=Syne:...&family=DM+Sans:...`. Additionally, `ipc-styles.css` contains a CSS `@import` for the same URL (BACKLOG B2).

**Consequences:**
- Requires internet connection for fonts to load.
- GDPR/privacy implications (Google Fonts requests user IP).
- No local fallback font stack specified.

---

### AD-012: Single Template Serves Multiple Taxonomies

**Decision:** `ipc_marca` and `ipc_producto` taxonomy archives reuse `taxonomy-ipc_categoria.php` instead of having dedicated templates.

**Context:** The three taxonomies share the same layout (grid of offers). Separate templates would be redundant.

**Evidence:** In `template_include` filter: both `is_tax('ipc_marca')` and `is_tax('ipc_producto')` load `templates/taxonomy-ipc_categoria.php`.

**Consequences:**
- DRY — single template for three taxonomy contexts.
- Template logic must handle any taxonomy generically.
- If taxonomies diverge in design later, this will need refactoring.

---

### AD-018: Plugin Update Checker for Updates

**Decision:** Integrate YahnisElsts Plugin Update Checker library (v5.7) to enable WordPress admin update notifications from GitHub releases.

**Context:** The plugin is hosted on GitHub. Without an update checker, WordPress installations won't know about new versions. The Plugin Update Checker is the de facto standard for self-hosted plugins, polling GitHub releases API.

**Evidence:** `vendor/yahnis-elsts/plugin-update-checker/` bundled in repo. `PucFactory::buildUpdateChecker()` called at plugin initialization with `https://github.com/ikerbit/Ikerbit-Product-Cards/`.

**Consequences:**
- WordPress admin will show update notifications when new GitHub releases are published.
- One-click updates from WordPress admin.
- Library is bundled in `vendor/` (no Composer required at runtime).
- Release tags must follow the library's version detection logic.

---

## Pending Decisions (TODO)

| ID | Topic | Status |
|----|-------|--------|
| AD-013 | Multi-language / i18n strategy | TODO — no i18n infrastructure exists; all strings are Spanish |
| AD-014 | Database migration strategy for future schema changes | TODO — no upgrade/migration mechanism exists |
| AD-015 | Automated testing framework (PHPUnit, e2e) | TODO — no tests exist |
| AD-016 | CI/CD pipeline for releases | TODO — no CI configured |
| AD-017 | Minimum WordPress and PHP version requirements | TODO — not declared in plugin header |
