# Development

## Local Setup

1. Install a local WordPress instance (e.g., using Local, XAMPP, or Docker).
2. Symlink or copy the plugin folder into `wp-content/plugins/ikerbit-product-cards/`.
3. Activate the plugin from the WordPress admin.
4. Navigate to **Product Cards → Ajustes** and set a secret key for the REST API.

## Testing with n8n

To test the REST API locally:

```bash
# Create an offer
curl -X POST http://localhost/wp-json/ipc/v1/oferta \
  -H "X-IPC-Secret: your-secret" \
  -H "Content-Type: application/json" \
  -d '{"titulo":"Test Product","precio":"49.99","categoria":"test","marketplace":"amazon"}'

# Update an offer
curl -X POST http://localhost/wp-json/ipc/v1/oferta/123 \
  -H "X-IPC-Secret: your-secret" \
  -H "Content-Type: application/json" \
  -d '{"precio":"39.99"}'

# Delete an offer
curl -X DELETE http://localhost/wp-json/ipc/v1/oferta/123 \
  -H "X-IPC-Secret: your-secret"
```

## Project Conventions

### File Organization

- Core logic stays in `ikerbit-product-cards.php`.
- Separate admin pages into standalone PHP files (e.g., `ipc-edit.php`).
- Templates reside in `templates/`.
- CSS is a single file: `ipc-styles.css`.
- Documentation lives in `docs/`.

### Naming

| Scope | Prefix | Example |
|-------|--------|---------|
| Functions | `ipc_` | `ipc_render_card()` |
| Meta keys | `ipc_` | `ipc_precio` |
| CSS classes | `ipc-` | `.ipc-card`, `.ipc-card__title`, `.ipc-card--large` |
| REST namespace | `ipc/v1` | `/wp-json/ipc/v1/oferta` |
| Options | `ipc_` | `get_option('ipc_secret')` |

### PHP Patterns

- No classes — all functions are global.
- Hooks use closures, never named function strings:

```php
add_action('init', function() {
    // register post types
});
```

- Simple callbacks use PHP arrow functions:

```php
'permission_callback' => fn() => current_user_can('manage_options')
```

- Section dividers follow this format:

```php
// ─────────────────────────────────────────
// N. SECTION NAME
// ─────────────────────────────────────────
```

### Escaping & Sanitization

| Context | Function |
|---------|----------|
| HTML text content | `esc_html()` |
| HTML attributes | `esc_attr()` |
| URLs in attributes | `esc_url()` |
| JavaScript strings | `esc_js()` |
| Textarea content | `esc_textarea()` |
| Admin text input | `sanitize_text_field()` |
| Rich HTML (description) | `wp_kses_post()` |
| Titles | `sanitize_text_field()` |

### Prices

Prices are stored as strings with `.` as decimal separator, and displayed with `,` (European format):

```php
$precio = esc_html(str_replace('.', ',', $m['precio']));
```

## Adding a New Meta Field

When adding a new field to offers, update all of these:

1. **`ikerbit-product-cards.php`**:
   - Add to `$fields` array in `rest_api_init` (register_post_meta)
   - Add to `$campos` array in `ipc_guardar_meta()` (save from API)
   - Add to `ipc_get_meta()` return array (read for shortcodes)

2. **`ipc-edit.php`**:
   - If text: add to `$campos_texto` in `ipc_edit_save()`
   - Add form field in `ipc_edit_page()` UI

3. **Templates** (if visible on frontend):
   - `templates/single-ipc_oferta.php` if displayed publicly

4. **`docs/ARCHITECTURE.md`**: update data model

## Testing Strategy

Currently, the project has no automated tests. Manual testing focuses on:

- REST API: test create, update, delete endpoints via curl or n8n
- Shortcodes: render pages with various attribute combinations
- Admin pages: verify sorting, filtering, delete, edit operations
- Frontend: verify card rendering, responsive breakpoints, click tracking
- GA4: verify events fire in browser Network tab

### Recommendations for the Future

- Add PHPUnit tests for `ipc_guardar_meta()`, `ipc_check_secret()`, `ipc_render_card()`.
- Add WP-CLI commands for bulk import/export.
- Use WordPress Playground for quick smoke tests.

## Release Workflow

1. Make changes on the `main` branch.
2. Update the plugin version in `ikerbit-product-cards.php` header comment and `wp_enqueue_style` version.
3. Add an entry to `CHANGELOG.md`.
4. Update affected documentation files in `docs/`.
5. Test manually on a local WordPress instance.
6. Commit, tag, and push to GitHub:

```bash
git add -A
git commit -m "vX.Y: description of changes"
git tag vX.Y
git push origin main
git push origin vX.Y
```

7. Create a GitHub release from the tag — this triggers the Plugin Update Checker for all installed instances.

## GitHub Workflow

- Branch: `main` (single-branch, no PRs currently).
- Remote: `git@github.com:ikerbit/Ikerbit-Product-Cards.git`.
- No CI/CD configured (TODO).
- No linting/formatting pre-commit hooks (TODO).

## Versioning

Semantic versioning is loosely followed:

- **Major** (x.0.0): breaking changes or large architectural shifts
- **Minor** (0.x.0): new features, new fields, new endpoints
- **Patch** (0.0.x): bug fixes

Current version is **2.7.3.2**.

## Environment-Specific Configuration

The plugin stores configuration in WordPress options:

| Option | Purpose |
|--------|---------|
| `ipc_secret` | API secret key for REST auth |
| `ipc_home_enabled` | Toggle to use offers archive as homepage |
| `ipc_ga4_id` | Google Analytics 4 measurement ID |
| `ipc_ga4_enabled` | Toggle GA4 event sending |
