# AGENTS.md

Instructions for any AI coding agent (OpenCode, Claude Code, Codex CLI, Cursor, etc.) working on this repository.

## Project Overview

**Ikerbit Product Cards** is a WordPress plugin that manages product affiliate offers via a custom post type (`ipc_oferta`). Offers are created/updated via REST API (designed for n8n automation) and displayed using shortcodes with responsive product cards.

- Entry point: `ikerbit-product-cards.php`
- CPT: `ipc_oferta`
- Taxonomies: `ipc_categoria` (hierarchical), `ipc_marca`, `ipc_producto`
- Prefix for all identifiers: `ipc_`

## Coding Philosophy

- **Procedural only** — no classes, no OOP. The project uses plain functions.
- **WordPress hooks with closures** — all hooks use anonymous functions. Follow this pattern.
- **Single-file core** — keep the main plugin logic in `ikerbit-product-cards.php` unless extracting a clear module (like `ipc-edit.php` for the edit page).
- **Simplicity over abstraction** — do not introduce design patterns, service layers, or dependency injection unless explicitly requested.
- **Spanish for user-facing text** — admin labels, frontend text, and UI messages are in Spanish. Code comments are in Spanish.

## Coding Style

| Aspect               | Convention                                                                 |
|----------------------|----------------------------------------------------------------------------|
| Function prefix      | `ipc_`                                                                     |
| Meta key prefix      | `ipc_` (e.g. `ipc_precio`, `ipc_visitas`)                                  |
| CSS class prefix     | `ipc-` (BEM-like: `ipc-card`, `ipc-card__title`, `ipc-card--large`)       |
| REST namespace       | `ipc/v1`                                                                   |
| Hook callbacks       | Anonymous functions (`add_action('init', function() { ... });`)            |
| Arrow functions      | Use `fn() =>` for simple callbacks where possible                          |
| Escaping             | `esc_html()`, `esc_attr()`, `esc_url()` in templates                       |
| Sanitization         | `sanitize_text_field()` for text, `wp_kses_post()` for description         |
| Permissions callback | Arrow functions: `'permission_callback' => fn() => current_user_can(...)`  |
| Section comments     | `// ─────────────────────────────────────────` with section number         |
| Prices               | Stored with `.` as decimal, displayed with `,` (European format)           |

## Rules for Modifying Code

1. **Preserve existing public behavior** — shortcode attributes, REST API signatures, and admin page URLs must remain backward-compatible unless the CHANGELOG documents a breaking change.
2. **Do not rename files** without explicit request.
3. **Do not refactor from procedural to OOP** — maintain the current paradigm.
4. **Do not extract inline CSS/JS** from admin pages into separate files unless requested.
5. **Do not add comments** unless necessary to explain complex logic.
6. **When adding new meta fields**, register them in `rest_api_init`, add them to `ipc_guardar_meta`, `ipc_get_meta`, `ipc_edit_save`, and the edit page form.
7. **When adding new REST endpoints**, follow the existing pattern: `register_rest_route` with `permission_callback => 'ipc_check_secret'` or `'__return_true'`.

## Backward Compatibility

- All existing shortcode attributes must continue to work.
- REST API endpoint paths (`ipc/v1`) must not change.
- Meta key names must not be renamed without migration logic.
- CSS class names are considered public API (themes may target them).

## WordPress Best Practices

- Always use `ABSPATH` guard at the top of PHP files.
- Use WordPress sanitization/escaping functions, never roll your own.
- Use `wp_enqueue_style`/`wp_enqueue_script` for assets, not hardcoded `<link>`/`<script>` in templates. (Note: the single template loads hls.js via a hardcoded CDN `<script>` — this is a legacy exception.)
- Use nonces for admin forms (`wp_nonce_field` + `check_admin_referer`).
- Use `current_user_can('manage_options')` for admin pages.
- Prefer `WP_Query` over `query_posts` or raw SQL.

## Git Workflow

- Branch: `main`
- Commit messages: concise, in Spanish or English (repo owner's preference: TODO — needs confirmation)
- Before committing: verify no `.env`, `node_modules/`, or log files are staged (`.gitignore` is configured).
- Remote: `git@github.com:ikerbit/Ikerbit-Product-Cards.git`

## Documentation

- **Keep CHANGELOG.md updated** — add entries for every version bump documenting new features, bug fixes, and breaking changes.
- **Update README.md** if the plugin description or installation steps change (currently bare-bones).
- **Architecture changes** (new files, new hooks, new REST routes, template changes) must be reflected in `docs/ARCHITECTURE.md`.
- **New decisions** (framework choices, pattern changes, deprecations) must be logged in `docs/DECISIONS.md`.
- **New technical debt or refactoring ideas** found during development must be noted in `docs/BACKLOG.md`.
- **Completed roadmap items** should be moved from "Planned" to "Implemented" in `docs/ROADMAP.md`.

## Avoid

- Unnecessary refactoring of working code.
- Introducing npm/webpack/composer build steps unless explicitly requested.
- Adding third-party PHP libraries (the plugin has no external PHP dependencies).
- Changing the plugin name or text domain.
- Inventing features not present in the codebase.
