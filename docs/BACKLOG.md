# Backlog

Technical debt, code smells, and improvement opportunities discovered during code review.

## Bugs

| ID | Severity | File | Description |
|----|----------|------|-------------|
| B1 | Low | `ikerbit-product-cards.php:438` | CSS version `2.0.0` hardcoded in `wp_enqueue_style`; should match plugin version `2.5.0` |
| B2 | Low | `ipc-styles.css:1` + `ikerbit-product-cards.php:441-445` | Google Fonts loaded twice: via CSS `@import` and via `wp_enqueue_style` |
| B3 | Low | `ikerbit-product-cards.php:384-386` | Dead code: empty `if` block checking `$atts['marca'] && !$atts['producto']` in `ipc_shortcode_grid` |
| B4 | Medium | `ikerbit-product-cards.php:139` | Default secret `CAMBIA_ESTE_SECRET` is an insecure fallback — should require explicit configuration or generate a random default |
| B5 | Medium | `ikerbit-product-cards.php:737-739` | Delete action in offers list uses only JavaScript `confirm()` with no nonce verification on the server side |

## Code Quality

| ID | Severity | File | Description |
|----|----------|------|-------------|
| C1 | Low | `ikerbit-product-cards.php:80` | All meta fields registered as type `'string'` in `register_post_meta`. Numeric fields (`precio`, `descuento`, `rating`, `rating_count`, `visitas`, `clicks`) should use `'number'` for validation |
| C2 | Low | `ikerbit-product-cards.php:448-450` | `ipc_enqueue_styles()` is an empty stub called in shortcodes. Either implement it or remove it |
| C3 | Low | `ikerbit-product-cards.php:465-483, 488-494` | Two separate `template_include` filters registered. Should be merged into one filter function |
| C4 | Low | `ipc-edit.php:147` | Triple-nested ternary for `$imagenes_arr` initialization — low readability |
| C5 | Medium | All PHP files | No internationalization (i18n). All user-facing strings are hardcoded in Spanish. No textdomain in plugin header |
| C6 | Medium | `ikerbit-product-cards.php`, `ipc-edit.php` | Inline CSS in admin pages — increases file size and makes style changes harder |
| C7 | Low | `templates/taxonomy-ipc_categoria.php:82-101` | Second `WP_Query` is instantiated when sort filter is applied, instead of modifying the original query via `pre_get_posts` hook or `$query->set()` |

## Performance

| ID | Severity | File | Description |
|----|----------|------|-------------|
| P1 | Medium | `ikerbit-product-cards.php:769` | Statistics page uses `posts_per_page => -1` (unlimited). With many offers, this will cause memory and performance issues. Should paginate or add limits |
| P2 | Low | `ikerbit-product-cards.php:413-414` | `WP_Query` is always executed even when `$atts['orderby'] === 'descuento'` with no other filters, creating an unnecessary meta query |
| P3 | Low | `ipc-styles.css:1` | CSS `@import` for Google Fonts blocks rendering. `wp_enqueue_style` method (also present) is preferred and non-blocking |

## Architecture

| ID | Severity | File | Description |
|----|----------|------|-------------|
| A1 | Low | — | No automated test suite (PHPUnit, WordPress e2e) |
| A2 | Low | — | No WP-CLI commands for bulk operations (import, export, purge) |
| A3 | Low | `ipc-styles.css:159-181` | `!important` on button and link color styles — may conflict with theme styles |
| A4 | Low | — | No WP-Cron cleanup for old or expired offers |
| A5 | Medium | — | No upgrade/migration mechanism. If meta keys or database schema change, existing data may break silently |

## Documentation

| ID | Severity | Description |
|----|----------|-------------|
| D1 | Low | `README.md` is a single sentence; should describe installation, usage, and configuration |
| D2 | Low | No inline docblocks on functions — parameters and return types undocumented |
| D3 | Low | No `@since` tags on functions — makes it hard to track when features were introduced |

## Security

| ID | Severity | File | Description |
|----|----------|------|-------------|
| S1 | Medium | `ikerbit-product-cards.php:737-739` | Delete action lacks nonce verification; vulnerable to CSRF (same as B5) |
| S2 | Low | `ikerbit-product-cards.php:159,167` | `wp_insert_term()` return value is not checked for `WP_Error` in `ipc_guardar_meta()` — silent failure if term creation fails |
| S3 | Low | `ikerbit-product-cards.php:185` | `wp_insert_post()` return value is checked for `is_wp_error()` but `ipc_guardar_meta()` runs regardless — potential orphaned meta if post creation fails halfway |
