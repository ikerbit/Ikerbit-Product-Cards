<?php
/**
 * Plugin Name: Ikerbit Product Cards
 * Description: Tarjetas de producto dinámicas con shortcodes. Gestión via REST API desde n8n.
 * Version: 2.7.0
 * Author: Ikerbit
 */

if (!defined('ABSPATH')) exit;

require plugin_dir_path(__FILE__) . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

YahnisElsts\PluginUpdateChecker\v5p7\PucFactory::buildUpdateChecker(
    'https://github.com/ikerbit/Ikerbit-Product-Cards/',
    __FILE__,
    'ikerbit-product-cards'
);

function ipc_render_markdown($text) {
    if (!class_exists('Parsedown')) return nl2br(esc_html($text));
    $pd = new Parsedown();
    $pd->setMarkupEscaped(true);
    return $pd->text($text);
}

// ─────────────────────────────────────────
// 1. CUSTOM POST TYPE: oferta
// ─────────────────────────────────────────
add_action('init', function() {
    register_post_type('ipc_oferta', [
        'labels' => [
            'name'          => 'Ofertas',
            'singular_name' => 'Oferta',
            'add_new_item'  => 'Añadir Oferta',
            'edit_item'     => 'Editar Oferta',
            'menu_name'     => 'Ofertas',
        ],
        'public'       => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'supports'     => ['title', 'thumbnail', 'custom-fields'],
        'menu_icon'    => 'dashicons-tag',
        'rewrite'      => ['slug' => 'oferta'],
        'has_archive'  => 'ofertas',
    ]);

    // Taxonomía: categoría de producto
    register_taxonomy('ipc_categoria', 'ipc_oferta', [
        'labels' => [
            'name'          => 'Categorías',
            'singular_name' => 'Categoría',
        ],
        'public'       => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite'      => ['slug' => 'ofertas'],
    ]);

    // Taxonomía: marca
    register_taxonomy('ipc_marca', 'ipc_oferta', [
        'labels' => [
            'name'          => 'Marcas',
            'singular_name' => 'Marca',
            'menu_name'     => 'Marcas',
        ],
        'public'       => true,
        'hierarchical' => false,
        'show_in_rest' => true,
        'rewrite'      => ['slug' => 'ofertas/marca'],
    ]);

    // Taxonomía: producto
    register_taxonomy('ipc_producto', 'ipc_oferta', [
        'labels' => [
            'name'          => 'Productos',
            'singular_name' => 'Producto',
            'menu_name'     => 'Productos',
        ],
        'public'       => true,
        'hierarchical' => false,
        'show_in_rest' => true,
        'rewrite'      => ['slug' => 'ofertas/producto'],
    ]);
});

// ─────────────────────────────────────────
// 2. REGISTRAR META FIELDS EN REST API
// ─────────────────────────────────────────
add_action('rest_api_init', function() {
    $fields = ['ipc_precio', 'ipc_precio_old', 'ipc_url', 'ipc_img', 'ipc_marketplace', 'ipc_rating', 'ipc_rating_count', 'ipc_stock', 'ipc_descuento', 'ipc_badge', 'ipc_fecha', 'ipc_descripcion', 'ipc_imagenes', 'ipc_video', 'ipc_visitas', 'ipc_clicks', 'ipc_ultimo_click', 'ipc_country', 'ipc_language', 'ipc_currency', 'ipc_custom_description', 'ipc_visitas_paises', 'ipc_clicks_paises', 'ipc_product_code'];
    foreach ($fields as $field) {
        register_post_meta('ipc_oferta', $field, [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }
});

// ─────────────────────────────────────────
// 3. ENDPOINT REST PERSONALIZADO DESDE N8N
// ─────────────────────────────────────────
add_action('rest_api_init', function() {
    register_rest_route('ipc/v1', '/oferta', [
        'methods'             => 'POST',
        'callback'            => 'ipc_crear_oferta',
        'permission_callback' => 'ipc_check_secret',
    ]);
    register_rest_route('ipc/v1', '/oferta/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => 'ipc_actualizar_oferta',
        'permission_callback' => 'ipc_check_secret',
    ]);
    register_rest_route('ipc/v1', '/visita/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => 'ipc_registrar_visita',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('ipc/v1', '/click/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => 'ipc_registrar_click',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('ipc/v1', '/visitor/(?P<country>[A-Z]{2})', [
        'methods'             => 'POST',
        'callback'            => 'ipc_registrar_visitor',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('ipc/v1', '/oferta/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => 'ipc_eliminar_oferta',
        'permission_callback' => 'ipc_check_secret',
    ]);
});

// ─────────────────────────────────────────
// 4b. CONTADORES DE VISITAS Y CLICKS
// ─────────────────────────────────────────
function ipc_registrar_visita($request) {
    $post_id = intval($request['id']);
    if (!get_post($post_id)) return new WP_Error('not_found', 'No encontrado', ['status' => 404]);
    $visitas = intval(get_post_meta($post_id, 'ipc_visitas', true));
    update_post_meta($post_id, 'ipc_visitas', $visitas + 1);

    $country = strtoupper(sanitize_text_field($request->get_param('country') ?: ''));
    if ($country && preg_match('/^[A-Z]{2}$/', $country)) {
        $paises = json_decode(get_post_meta($post_id, 'ipc_visitas_paises', true) ?: '{}', true);
        $paises[$country] = ($paises[$country] ?? 0) + 1;
        update_post_meta($post_id, 'ipc_visitas_paises', json_encode($paises));
    }

    return rest_ensure_response(['success' => true, 'visitas' => $visitas + 1]);
}

function ipc_registrar_click($request) {
    $post_id = intval($request['id']);
    if (!get_post($post_id)) return new WP_Error('not_found', 'No encontrado', ['status' => 404]);
    $clicks = intval(get_post_meta($post_id, 'ipc_clicks', true));
    update_post_meta($post_id, 'ipc_clicks', $clicks + 1);
    update_post_meta($post_id, 'ipc_ultimo_click', current_time('Y-m-d H:i:s'));

    $country = strtoupper(sanitize_text_field($request->get_param('country') ?: ''));
    if ($country && preg_match('/^[A-Z]{2}$/', $country)) {
        $paises = json_decode(get_post_meta($post_id, 'ipc_clicks_paises', true) ?: '{}', true);
        $paises[$country] = ($paises[$country] ?? 0) + 1;
        update_post_meta($post_id, 'ipc_clicks_paises', json_encode($paises));
    }

    return rest_ensure_response(['success' => true, 'clicks' => $clicks + 1]);
}

function ipc_registrar_visitor($request) {
    $country = strtoupper($request['country']);
    $data = json_decode(get_option('ipc_visitor_countries', '{}'), true);
    $data[$country] = ($data[$country] ?? 0) + 1;
    update_option('ipc_visitor_countries', json_encode($data));
    return rest_ensure_response(['success' => true]);
}

function ipc_check_secret($request) {
    $secret = $request->get_header('X-IPC-Secret');
    return $secret === get_option('ipc_secret', 'CAMBIA_ESTE_SECRET');
}

function ipc_sanitize_price($value) {
    $clean = trim(str_replace(['€', '$', '£', ' '], '', $value));
    $clean = str_replace(',', '.', $clean);
    if (preg_match('/^\d+\.?\d*$/', $clean)) return $clean;
    return '0';
}

function ipc_guardar_meta($post_id, $p) {
    $campos = ['ipc_precio', 'ipc_precio_old', 'ipc_url', 'ipc_img', 'ipc_marketplace', 'ipc_rating', 'ipc_rating_count', 'ipc_stock', 'ipc_descuento', 'ipc_badge', 'ipc_fecha', 'ipc_video', 'ipc_product_code'];
    foreach ($campos as $campo) {
        $key = str_replace('ipc_', '', $campo);
        if (isset($p[$key])) {
            $val = sanitize_text_field($p[$key]);
            if ($campo === 'ipc_precio' || $campo === 'ipc_precio_old') {
                $val = ipc_sanitize_price($val);
            }
            update_post_meta($post_id, $campo, $val);
        }
    }
    if (isset($p['descripcion'])) {
        update_post_meta($post_id, 'ipc_descripcion', wp_kses_post($p['descripcion']));
    }
    if (isset($p['custom_description'])) {
        update_post_meta($post_id, 'ipc_custom_description', wp_kses_post($p['custom_description']));
    }
    if (isset($p['country'])) {
        update_post_meta($post_id, 'ipc_country', strtoupper(sanitize_text_field($p['country'])));
    }
    if (isset($p['language'])) {
        update_post_meta($post_id, 'ipc_language', strtolower(sanitize_text_field($p['language'])));
    }
    if (isset($p['currency'])) {
        update_post_meta($post_id, 'ipc_currency', strtoupper(sanitize_text_field($p['currency'])));
    }
    if (isset($p['imagenes'])) {
        $imgs = is_array($p['imagenes']) ? $p['imagenes'] : json_decode($p['imagenes'], true);
        update_post_meta($post_id, 'ipc_imagenes', json_encode(array_values((array)$imgs)));
    }
    if (!empty($p['categoria'])) {
        $term = get_term_by('slug', $p['categoria'], 'ipc_categoria');
        if (!$term) $term = wp_insert_term($p['categoria'], 'ipc_categoria');
        $term_id = is_array($term) ? $term['term_id'] : $term->term_id;
        wp_set_post_terms($post_id, [$term_id], 'ipc_categoria');
    }
    foreach (['marca' => 'ipc_marca', 'producto' => 'ipc_producto'] as $field => $taxonomy) {
        if (!empty($p[$field])) {
            $slug = sanitize_title($p[$field]);
            $term = get_term_by('slug', $slug, $taxonomy);
            if (!$term) $term = wp_insert_term($p[$field], $taxonomy, ['slug' => $slug]);
            $term_id = is_array($term) ? $term['term_id'] : $term->term_id;
            wp_set_post_terms($post_id, [$term_id], $taxonomy);
        }
    }
}

function ipc_crear_oferta($request) {
    $p = $request->get_json_params();
    if (empty($p['titulo'])) return new WP_Error('missing', 'titulo requerido', ['status' => 400]);

    $product_code = sanitize_text_field($p['product_code'] ?? '');
    $marketplace  = sanitize_text_field($p['marketplace'] ?? '');
    $country      = strtoupper(sanitize_text_field($p['country'] ?? ''));

    if ($product_code && $marketplace) {
        $meta_query = [
            ['key' => 'ipc_product_code', 'value' => $product_code],
            ['key' => 'ipc_marketplace', 'value' => $marketplace],
        ];
        if ($country) $meta_query[] = ['key' => 'ipc_country', 'value' => $country];
        $existing = get_posts([
            'post_type'      => 'ipc_oferta',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => $meta_query,
        ]);
        // Fallback sin country (cubre legacy sin ipc_country o envíos sin country)
        if (empty($existing) && $country) {
            array_pop($meta_query);
            $existing = get_posts([
                'post_type'      => 'ipc_oferta',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => $meta_query,
            ]);
        }
        // Segundo fallback: busca por ipc_url (cubre legacy sin product_code)
        if (empty($existing) && !empty($p['url'])) {
            $existing = get_posts([
                'post_type'      => 'ipc_oferta',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => [
                    ['key' => 'ipc_url', 'value' => sanitize_text_field($p['url'])],
                ],
            ]);
        }
        if (!empty($existing)) {
            $post_id = $existing[0];
            wp_update_post(['ID' => $post_id, 'post_title' => sanitize_text_field($p['titulo'])]);
            ipc_guardar_meta($post_id, $p);
            return rest_ensure_response(['success' => true, 'post_id' => $post_id, 'updated' => true, 'url' => get_permalink($post_id)]);
        }
    }

    $post_id = wp_insert_post([
        'post_title'  => sanitize_text_field($p['titulo']),
        'post_type'   => 'ipc_oferta',
        'post_status' => 'publish',
    ]);
    if (is_wp_error($post_id)) return $post_id;

    ipc_guardar_meta($post_id, $p);
    return rest_ensure_response(['success' => true, 'post_id' => $post_id, 'url' => get_permalink($post_id)]);
}

function ipc_eliminar_oferta($request) {
    $post_id = intval($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'ipc_oferta') {
        return new WP_Error('not_found', 'Oferta no encontrada', ['status' => 404]);
    }
    $result = wp_delete_post($post_id, true);
    if (!$result) {
        return new WP_Error('error', 'No se pudo eliminar', ['status' => 500]);
    }
    return rest_ensure_response(['success' => true, 'deleted_id' => $post_id]);
}

function ipc_actualizar_oferta($request) {
    $post_id = intval($request['id']);
    $p = $request->get_json_params();

    if (!empty($p['titulo'])) {
        wp_update_post(['ID' => $post_id, 'post_title' => sanitize_text_field($p['titulo'])]);
    }
    ipc_guardar_meta($post_id, $p);
    return rest_ensure_response(['success' => true, 'post_id' => $post_id, 'updated' => true, 'url' => get_permalink($post_id)]);
}

// ─────────────────────────────────────────
// 4. SHORTCODES
// ─────────────────────────────────────────
add_shortcode('oferta', 'ipc_shortcode_single');
add_shortcode('ofertas', 'ipc_shortcode_grid');

function ipc_currency_symbol($code) {
    $map = ['EUR' => '€', 'USD' => '$', 'MXN' => 'MX$', 'GBP' => '£', 'ARS' => 'AR$', 'CLP' => 'CL$', 'COP' => 'CO$', 'PEN' => 'S/', 'BRL' => 'R$', 'UYU' => '$U', 'CRC' => '₡', 'DOP' => 'RD$', 'GTQ' => 'Q', 'HNL' => 'L', 'NIO' => 'C$', 'PAB' => 'B/', 'PYG' => '₲', 'BOB' => 'Bs', 'VES' => 'Bs.S'];
    return $map[strtoupper($code)] ?? '$';
}

function ipc_detect_country() {
    if (!empty($_COOKIE['ipc_country']) && preg_match('/^[A-Z]{2}$/', $_COOKIE['ipc_country'])) {
        return strtoupper($_COOKIE['ipc_country']);
    }
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $parts = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        if (preg_match('/[_-]([A-Za-z]{2})$/', trim($parts[0]), $m)) {
            return strtoupper($m[1]);
        }
    }
    return strtoupper(get_option('ipc_default_country', 'ES'));
}

function ipc_get_meta($post_id) {
    return [
        'precio'       => get_post_meta($post_id, 'ipc_precio', true),
        'precio_old'   => get_post_meta($post_id, 'ipc_precio_old', true),
        'url'          => get_post_meta($post_id, 'ipc_url', true),
        'img'          => get_post_meta($post_id, 'ipc_img', true),
        'marketplace'  => get_post_meta($post_id, 'ipc_marketplace', true),
        'rating'       => get_post_meta($post_id, 'ipc_rating', true),
        'rating_count' => get_post_meta($post_id, 'ipc_rating_count', true),
        'stock'        => get_post_meta($post_id, 'ipc_stock', true),
        'descuento'    => get_post_meta($post_id, 'ipc_descuento', true),
        'badge'        => get_post_meta($post_id, 'ipc_badge', true),
        'fecha'        => get_post_meta($post_id, 'ipc_fecha', true),
        'descripcion'  => get_post_meta($post_id, 'ipc_descripcion', true),
        'custom_description' => get_post_meta($post_id, 'ipc_custom_description', true),
        'imagenes'     => json_decode(get_post_meta($post_id, 'ipc_imagenes', true) ?: '[]', true),
        'video'        => get_post_meta($post_id, 'ipc_video', true),
        'country'      => get_post_meta($post_id, 'ipc_country', true),
        'language'     => get_post_meta($post_id, 'ipc_language', true),
        'currency'     => get_post_meta($post_id, 'ipc_currency', true),
        'product_code' => get_post_meta($post_id, 'ipc_product_code', true),
    ];
}

function ipc_render_card($post, $size = 'normal') {
    $m = ipc_get_meta($post->ID);
    $titulo_raw = get_the_title($post);
    $titulo = esc_html(mb_strlen($titulo_raw) > 79 ? mb_substr($titulo_raw, 0, 79) . '…' : $titulo_raw);
    $post_url = esc_url(get_permalink($post->ID));
    $img = esc_url($m['img']);
    $url = esc_url($m['url']);
    $precio = esc_html(str_replace('.', ',', $m['precio']));
    $precio_old = esc_html(str_replace('.', ',', $m['precio_old']));
    $marketplace = strtolower(esc_html($m['marketplace'] ?: 'tienda'));
    $marketplace_label = ucfirst($marketplace);
    $rating = floatval($m['rating']);
    $rating_count = esc_html($m['rating_count']);
    $stock = $m['stock'] !== '0' ? true : false;
    $descuento = esc_html($m['descuento']);
    $badge = esc_html($m['badge']);
    $currency_sym = esc_html(ipc_currency_symbol($m['currency'] ?: 'EUR'));
    $custom_desc = $m['custom_description'];
    if ($custom_desc) {
        $custom_desc = strip_tags(ipc_render_markdown($custom_desc));
        $custom_desc = mb_strlen($custom_desc) > 120 ? mb_substr($custom_desc, 0, 117) . '…' : $custom_desc;
        $custom_desc = nl2br(esc_html($custom_desc));
    }

    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= round($rating) ? '★' : '☆';
    }

    $btn_labels = ['amazon' => 'Ver en Amazon', 'ebay' => 'Ver en eBay', 'aliexpress' => 'Ver en AliExpress', 'pccomponentes' => 'Ver en PcComponentes'];
    $btn_label = $btn_labels[$marketplace] ?? 'Ver oferta';

    ob_start(); ?>
    <div class="ipc-card ipc-card--<?php echo esc_attr($size); ?> ipc-mp--<?php echo esc_attr($marketplace); ?>" data-post-id="<?php echo $post->ID; ?>">
        <a href="<?php echo $post_url; ?>" class="ipc-card__link" aria-label="<?php echo $titulo; ?>"></a>
        <div class="ipc-card__img-wrap">
            <?php if ($badge): ?><span class="ipc-badge"><?php echo $badge; ?></span><?php endif; ?>
            <?php if ($descuento): ?><span class="ipc-discount">-<?php echo $descuento; ?>%</span><?php endif; ?>
            <span class="ipc-marketplace-tag"><?php echo $marketplace_label; ?></span>
            <?php if ($img): ?>
                <a href="<?php echo $post_url; ?>" tabindex="-1">
                    <img src="<?php echo $img; ?>" alt="<?php echo $titulo; ?>" loading="lazy">
                </a>
            <?php endif; ?>
        </div>
        <div class="ipc-card__body">
            <h3 class="ipc-card__title"><a href="<?php echo $post_url; ?>"><?php echo $titulo; ?></a></h3>
            <?php if ($custom_desc): ?><p class="ipc-card__desc"><?php echo $custom_desc; ?></p><?php endif; ?>
            <?php if ($rating): ?>
            <div class="ipc-card__rating">
                <span class="ipc-stars"><?php echo $stars; ?></span>
                <?php if ($rating): ?><span class="ipc-rating-num"><?php echo $rating; ?></span><?php endif; ?>
                <?php if ($rating_count): ?><span class="ipc-rating-count">(<?php echo $rating_count; ?>)</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="ipc-card__price-wrap">
                <span class="ipc-price"><?php echo $precio; ?><?php echo $currency_sym; ?></span>
                <?php if ($precio_old): ?><span class="ipc-price-old"><?php echo $precio_old; ?><?php echo $currency_sym; ?></span><?php endif; ?>
            </div>
            <?php if ($stock): ?><div class="ipc-stock">● En stock</div><?php endif; ?>
            <a href="<?php echo $url; ?>" class="ipc-btn ipc-btn--<?php echo esc_attr($marketplace); ?>" target="_blank" rel="sponsored nofollow noopener" style="position:relative;z-index:2" data-post-id="<?php echo $post->ID; ?>">
                <?php echo $btn_label; ?> →
            </a>
            <a href="<?php echo $post_url; ?>" class="ipc-btn-more" style="position:relative;z-index:2">Ver más detalles →</a>
        </div>
    </div>
    <?php return ob_get_clean();
}

function ipc_shortcode_single($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    if (!$atts['id']) return '';
    $post = get_post(intval($atts['id']));
    if (!$post || $post->post_type !== 'ipc_oferta') return '';
    ipc_enqueue_styles();
    return '<div class="ipc-wrap ipc-wrap--single">' . ipc_render_card($post, 'large') . '</div>';
}

function ipc_shortcode_grid($atts) {
    $atts = shortcode_atts([
        'categoria'    => '',
        'marketplace'  => '',
        'marca'        => '',
        'producto'     => '',
        'limite'       => 6,
        'layout'       => 'grid',
        'orderby'      => 'date',
        'order'        => 'DESC',
        'condescuento' => '',
        'country'      => '',
        'product_code' => '',
    ], $atts);

    $args = [
        'post_type'      => 'ipc_oferta',
        'posts_per_page' => intval($atts['limite']),
        'post_status'    => 'publish',
        'order'          => $atts['order'],
    ];

    $meta_query = ['relation' => 'AND'];

    if ($atts['orderby'] === 'descuento') {
        $args['orderby']  = 'meta_value_num';
        $args['meta_key'] = 'ipc_descuento';
        $args['order']    = 'DESC';
        $meta_query[] = [
            'key'     => 'ipc_descuento',
            'value'   => '0',
            'compare' => '>',
            'type'    => 'NUMERIC',
        ];
    } else {
        $args['orderby'] = 'date';
    }

    $tax_query = ['relation' => 'AND'];
    if ($atts['categoria']) {
        $tax_query[] = [
            'taxonomy' => 'ipc_categoria',
            'field'    => 'slug',
            'terms'    => sanitize_title($atts['categoria']),
        ];
    }
    if ($atts['marca']) {
        $marca_raw = trim($atts['marca']);
        if (substr($marca_raw, -1) === '*') {
            $prefix = sanitize_title(rtrim($marca_raw, '*'));
            $all_terms = get_terms(['taxonomy' => 'ipc_marca', 'hide_empty' => false]);
            $matched = array_filter($all_terms, fn($t) => stripos($t->slug, $prefix) === 0);
            $slugs = array_values(array_map(fn($t) => $t->slug, $matched));
            if (empty($slugs)) return '<p class="ipc-empty">No hay ofertas disponibles.</p>';
            $tax_query[] = ['taxonomy' => 'ipc_marca', 'field' => 'slug', 'terms' => $slugs];
        } else {
            $marca_slug = sanitize_title($marca_raw);
            $term_exists = get_term_by('slug', $marca_slug, 'ipc_marca');
            if (!$term_exists) return '<p class="ipc-empty">No hay ofertas disponibles.</p>';
            $tax_query[] = ['taxonomy' => 'ipc_marca', 'field' => 'slug', 'terms' => $marca_slug];
        }
    }
    if ($atts['producto']) {
        $prod_raw = trim($atts['producto']);
        if (substr($prod_raw, -1) === '*') {
            $prefix = sanitize_title(rtrim($prod_raw, '*'));
            $all_terms = get_terms(['taxonomy' => 'ipc_producto', 'hide_empty' => false]);
            $matched = array_filter($all_terms, fn($t) => stripos($t->slug, $prefix) === 0);
            $slugs = array_values(array_map(fn($t) => $t->slug, $matched));
            if (empty($slugs)) return '<p class="ipc-empty">No hay ofertas disponibles.</p>';
            $tax_query[] = ['taxonomy' => 'ipc_producto', 'field' => 'slug', 'terms' => $slugs];
        } else {
            $prod_slug = sanitize_title($prod_raw);
            $term_exists = get_term_by('slug', $prod_slug, 'ipc_producto');
            if (!$term_exists) return '<p class="ipc-empty">No hay ofertas disponibles.</p>';
            $tax_query[] = ['taxonomy' => 'ipc_producto', 'field' => 'slug', 'terms' => $prod_slug];
        }
    }
    if ($atts['marca'] && !$atts['producto']) {
        // verificar término marca
    }
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    if ($atts['marketplace']) {
        $meta_query[] = [
            'key'     => 'ipc_marketplace',
            'value'   => $atts['marketplace'],
            'compare' => 'LIKE',
        ];
    }

    // Filtro por país
    $auto_filter = get_option('ipc_auto_filter_country', 0);
    $country_attr = $atts['country'];
    if ($country_attr === 'auto' || ($auto_filter && empty($country_attr))) {
        $detected = ipc_detect_country();
        $meta_query[] = [
            'key'     => 'ipc_country',
            'value'   => [$detected, 'GLOBAL'],
            'compare' => 'IN',
        ];
    } elseif (!empty($country_attr) && $country_attr !== 'auto') {
        $countries = array_map('trim', explode(',', strtoupper($country_attr)));
        $meta_query[] = [
            'key'     => 'ipc_country',
            'value'   => $countries,
            'compare' => 'IN',
        ];
    }

    // Filtro por código de producto (ASIN, product ID...)
    if ($atts['product_code']) {
        $meta_query[] = [
            'key'     => 'ipc_product_code',
            'value'   => sanitize_text_field($atts['product_code']),
        ];
    }

    if ($atts['condescuento'] === 'si') {
        $meta_query[] = [
            'key'     => 'ipc_descuento',
            'value'   => '0',
            'compare' => '>',
            'type'    => 'NUMERIC',
        ];
    }

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    } elseif ($atts['orderby'] === 'descuento') {
        $args['meta_query'] = $meta_query;
    }

    $query = new WP_Query($args);
    if (!$query->have_posts()) return '<p class="ipc-empty">No hay ofertas disponibles.</p>';

    ipc_enqueue_styles();

    $layout_class = $atts['layout'] === 'horizontal' ? 'ipc-wrap--horizontal' : 'ipc-wrap--grid';
    $html = '<div class="ipc-wrap ' . esc_attr($layout_class) . '">';
    while ($query->have_posts()) {
        $query->the_post();
        $html .= ipc_render_card(get_post());
    }
    wp_reset_postdata();
    $html .= '</div>';
    return $html;
}

// ─────────────────────────────────────────
// 5. ESTILOS
// ─────────────────────────────────────────
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'ipc-styles',
        plugin_dir_url(__FILE__) . 'ipc-styles.css',
        [],
        '2.7.0'
    );
    wp_enqueue_style(
        'ipc-fonts',
        'https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap',
        [],
        null
    );
});

function ipc_enqueue_styles() {
    // Compatibilidad: estilos ya cargados via wp_enqueue_scripts
}

// ─────────────────────────────────────────
// 6. PÁGINA DE AJUSTES (SECRET KEY)
// ─────────────────────────────────────────
add_action('admin_menu', function() {
    add_menu_page('Ikerbit Product Cards', 'Product Cards', 'manage_options', 'ipc-settings', 'ipc_settings_page', 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>'), 30);
    add_submenu_page('ipc-settings', 'Ajustes', 'Ajustes', 'manage_options', 'ipc-settings', 'ipc_settings_page');
    add_submenu_page('ipc-settings', 'Todas las Ofertas', 'Todas las Ofertas', 'manage_options', 'ipc-ofertas', 'ipc_ofertas_page');
    add_submenu_page('ipc-settings', 'Estadísticas', 'Estadísticas', 'manage_options', 'ipc-stats', 'ipc_stats_page');
});

// ─────────────────────────────────────────
// PLANTILLAS DESDE EL PLUGIN
// ─────────────────────────────────────────
add_filter('template_include', function($template) {
    if (is_singular('ipc_oferta')) {
        $plugin_tpl = plugin_dir_path(__FILE__) . 'templates/single-ipc_oferta.php';
        if (file_exists($plugin_tpl)) return $plugin_tpl;
    }
    if (is_tax('ipc_categoria')) {
        $plugin_tpl = plugin_dir_path(__FILE__) . 'templates/taxonomy-ipc_categoria.php';
        if (file_exists($plugin_tpl)) return $plugin_tpl;
    }
    if (is_tax('ipc_marca') || is_tax('ipc_producto')) {
        $plugin_tpl = plugin_dir_path(__FILE__) . 'templates/taxonomy-ipc_categoria.php';
        if (file_exists($plugin_tpl)) return $plugin_tpl;
    }
    if (is_post_type_archive('ipc_oferta')) {
        $plugin_tpl = plugin_dir_path(__FILE__) . 'templates/archive-ipc_oferta.php';
        if (file_exists($plugin_tpl)) return $plugin_tpl;
    }
    return $template;
});

// ─────────────────────────────────────────
// PÁGINA DE INICIO — ARCHIVO CPT
// ─────────────────────────────────────────
add_filter('template_include', function($template) {
    if (is_front_page() && get_option('ipc_home_enabled', 0)) {
        $plugin_tpl = plugin_dir_path(__FILE__) . 'templates/archive-ipc_oferta.php';
        if (file_exists($plugin_tpl)) return $plugin_tpl;
    }
    return $template;
}, 99);

function ipc_settings_page() {
    if (isset($_POST['ipc_secret'])) {
        update_option('ipc_secret', sanitize_text_field($_POST['ipc_secret']));
        update_option('ipc_home_enabled', isset($_POST['ipc_home_enabled']) ? 1 : 0);
        update_option('ipc_ga4_id', sanitize_text_field($_POST['ipc_ga4_id'] ?? ''));
        update_option('ipc_ga4_enabled', isset($_POST['ipc_ga4_enabled']) ? 1 : 0);
        update_option('ipc_default_country', strtoupper(sanitize_text_field($_POST['ipc_default_country'] ?? 'ES')));
        update_option('ipc_auto_filter_country', isset($_POST['ipc_auto_filter_country']) ? 1 : 0);

        // Si se activa como portada, configurar WordPress automáticamente
        if (isset($_POST['ipc_home_enabled'])) {
            // Buscar o crear página "Ofertas"
            $page = get_page_by_path('ofertas');
            if (!$page) {
                $page_id = wp_insert_post(['post_title' => 'Ofertas', 'post_name' => 'ofertas', 'post_status' => 'publish', 'post_type' => 'page']);
            } else {
                $page_id = $page->ID;
            }
            update_option('show_on_front', 'page');
            update_option('page_on_front', $page_id);
        } else {
            update_option('show_on_front', 'posts');
        }

        echo '<div class="updated"><p>✅ Ajustes guardados.</p></div>';
    }
    $secret       = get_option('ipc_secret', '');
    $home_enabled  = get_option('ipc_home_enabled', 0);
    $ga4_id        = get_option('ipc_ga4_id', '');
    $ga4_enabled   = get_option('ipc_ga4_enabled', 0);
    $default_country = get_option('ipc_default_country', 'ES');
    $auto_filter     = get_option('ipc_auto_filter_country', 0);
    ?>
    <div class="wrap">
        <h1>Ikerbit Product Cards v2.7.0</h1>
        <h2>Configuración API</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Secret Key (para n8n)</th>
                    <td>
                        <input type="text" name="ipc_secret" value="<?php echo esc_attr($secret); ?>" class="regular-text">
                        <p class="description">Usa este valor en el header <code>X-IPC-Secret</code> desde n8n.</p>
                    </td>
                </tr>
                <tr>
                    <th>Integración GA4</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ipc_ga4_enabled" value="1" <?php checked($ga4_enabled, 1); ?>>
                            Activar envío de eventos a Google Analytics 4
                        </label>
                        <br><br>
                        <label style="font-weight:600">Measurement ID</label><br>
                        <input type="text" name="ipc_ga4_id" value="<?php echo esc_attr($ga4_id); ?>" placeholder="G-XXXXXXXXXX" class="regular-text">
                        <p class="description">El plugin enviará eventos <code>affiliate_visit</code> y <code>affiliate_click</code> cuando GA4 esté activo en el sitio.</p>
                    </td>
                </tr>
                <tr>
                    <th>País por defecto</th>
                    <td>
                        <input type="text" name="ipc_default_country" value="<?php echo esc_attr($default_country); ?>" maxlength="6" style="width:80px;text-transform:uppercase">
                        <p class="description">Código ISO del país por defecto (ej: ES, MX, AR). Se usa como fallback si no se detecta el país del visitante.</p>
                    </td>
                </tr>
                <tr>
                    <th>Filtro automático por país</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ipc_auto_filter_country" value="1" <?php checked($auto_filter, 1); ?>>
                            Filtrar ofertas automáticamente según el país del visitante
                        </label>
                        <p class="description">Si se activa, los shortcodes sin atributo <code>country</code> filtrarán automáticamente. Usa <code>country="auto"</code> para filtrar en shortcodes individuales.</p>
                    </td>
                </tr>
                    <td>
                        <label>
                            <input type="checkbox" name="ipc_home_enabled" value="1" <?php checked($home_enabled, 1); ?>>
                            Mostrar el archivo de ofertas (<code>/ofertas/</code>) como portada del sitio
                        </label>
                        <p class="description" style="color:#b45309;margin-top:6px">
                            ⚠️ Al activar esta opción el plugin creará automáticamente una página llamada <strong>Ofertas</strong> en WordPress y la asignará como portada. 
                            Si ya existe una portada personalizada será reemplazada. 
                            Al desactivarla WordPress volverá a mostrar las entradas como portada.
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Guardar'); ?>
        </form>
        <h2>Shortcodes disponibles</h2>
        <table class="widefat">
            <thead><tr><th>Shortcode</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><code>[oferta id="123"]</code></td><td>Muestra una oferta individual por ID</td></tr>
                <tr><td><code>[ofertas categoria="ram" limite="6" layout="grid"]</code></td><td>Grid filtrado por categoría</td></tr>
                <tr><td><code>[ofertas marketplace="amazon" limite="4" layout="horizontal"]</code></td><td>Fila horizontal filtrada por marketplace</td></tr>
                <tr><td><code>[ofertas categoria="ram" marketplace="amazon" limite="4" layout="grid"]</code></td><td>Combinando filtros</td></tr>
                <tr><td><code>[ofertas orderby="descuento" limite="6" layout="grid"]</code></td><td>Mayor descuento primero</td></tr>
                <tr><td><code>[ofertas condescuento="si" limite="4" layout="horizontal"]</code></td><td>Solo ofertas con descuento</td></tr>
                <tr><td><code>[ofertas orderby="clicks" limite="6" layout="grid"]</code></td><td>Más clicadas primero</td></tr>
                <tr><td><code>[ofertas orderby="visitas" limite="6" layout="grid"]</code></td><td>Más visitadas primero</td></tr>
                <tr><td><code>[ofertas categoria="ram" orderby="descuento" condescuento="si" limite="4" layout="grid"]</code></td><td>Combinando filtros</td></tr>
                <tr><td><code>[ofertas country="ES" limite="6" layout="grid"]</code></td><td>Filtrado por país (ES, MX, DE...)</td></tr>
                <tr><td><code>[ofertas product_code="B0XXX" limite="6" layout="grid"]</code></td><td>Filtra por código de producto</td></tr>
                <tr><td><code>[ofertas product_code="B0XXX" country="auto"]</code></td><td>Producto concreto + país automático</td></tr>
                <tr><td><code>[ofertas country="auto" limite="6" layout="grid"]</code></td><td>Detecta y filtra por país del visitante (incluye ofertas globales)</td></tr>
                <tr><td colspan="2" style="background:#f9f9f9;font-weight:700;padding:8px 10px">🌍 Filtros internacionales</td></tr>
                <tr><td><code>[ofertas category="ram" country="auto" limite="6" layout="grid"]</code></td><td>Combina categoría con detección automática de país</td></tr>
                <tr><td><code>[ofertas marketplace="amazon" country="auto" limite="4" layout="horizontal"]</code></td><td>Marketplace + país auto</td></tr>
                <tr><td><code>[ofertas producto="samsung-galaxy-s24" country="auto"]</code></td><td>Producto + país (ideal para reviews multi-marketplace)</td></tr>
                <tr><td><code>[ofertas country="auto" condescuento="si" limite="6" layout="grid"]</code></td><td>Ofertas con descuento del país del visitante</td></tr>
                <tr><td colspan="2" style="background:#f9f9f9;font-weight:700;padding:8px 10px">💡 Combinaciones con país</td></tr>
                <tr><td><code>[ofertas marca="samsung" limite="6" layout="grid"]</code></td><td>Todas las ofertas de una marca</td></tr>
                <tr><td><code>[ofertas producto="samsung-galaxy-s24" limite="4" layout="grid"]</code></td><td>Ofertas de un producto concreto</td></tr>
                <tr><td><code>[ofertas marca="apple" categoria="smartphones" limite="6" layout="grid"]</code></td><td>Marca + categoría combinadas</td></tr>
                <tr><td><code>[ofertas marca="logitech" marketplace="amazon" condescuento="si" limite="4" layout="horizontal"]</code></td><td>Marca + marketplace + con descuento</td></tr>
                <tr><td><code>[ofertas producto="iphone*" categoria="smartphones" limite="4" layout="grid"]</code></td><td>Wildcard — todos los productos que empiezan por "iphone"</td></tr>
            </tbody>
        </table>
        <h2>Endpoint n8n</h2>
        <p><strong>Crear oferta:</strong> <code>POST <?php echo get_site_url(); ?>/wp-json/ipc/v1/oferta</code></p>
        <p><strong>Actualizar oferta:</strong> <code>POST <?php echo get_site_url(); ?>/wp-json/ipc/v1/oferta/{id}</code></p>
        <p><strong>Header requerido:</strong> <code>X-IPC-Secret: tu-secret</code></p>
        <h3>Body JSON de ejemplo:</h3>
        <pre style="background:#f4f4f4;padding:12px;border-radius:6px;"><?php echo esc_html(json_encode([
            'product_code' => 'B0F2295TXB',
            'titulo'       => 'Kingston FURY Beast DDR5 32GB',
            'precio'       => '89.99',
            'precio_old'   => '119.99',
            'descuento'    => '25',
            'url'          => 'https://www.amazon.es/dp/XXXXXXXXX?tag=tu-tag',
            'img'          => 'https://url-imagen.jpg',
            'marketplace'  => 'amazon',
            'rating'       => '4.5',
            'rating_count' => '1243',
            'stock'        => '1',
            'badge'        => 'Oferta Flash',
            'categoria'    => 'ram',
            'fecha'        => '2026-03-28',
            'descripcion'  => 'Descripción detallada del producto para SEO.',
            'imagenes'     => ['https://url-imagen-1.jpg', 'https://url-imagen-2.jpg'],
            'video'        => ['https://www.youtube.com/watch?v=XXXXX'],
            'marca'        => 'Samsung',
            'producto'     => 'Samsung Galaxy S24',
            'country'      => 'ES',
            'language'     => 'es',
            'currency'     => 'EUR',
            'custom_description' => 'Descripción personalizada que sustituye a la original en la card.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
    </div>
    <?php
}

// ─────────────────────────────────────────
// 7. PÁGINA DE LISTADO DE OFERTAS
// ─────────────────────────────────────────
function ipc_ofertas_page() {
    if (isset($_GET['delete']) && current_user_can('manage_options')) {
        wp_delete_post(intval($_GET['delete']), true);
        echo '<div class="updated"><p>✅ Oferta eliminada.</p></div>';
    }

    $paged    = isset($_GET['paged'])   ? intval($_GET['paged'])          : 1;
    $orderby  = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
    $order    = isset($_GET['order'])   ? ($_GET['order'] === 'ASC' ? 'ASC' : 'DESC') : 'DESC';
    $search_field = isset($_GET['search_field']) ? sanitize_text_field($_GET['search_field']) : '';
    $search_term  = isset($_GET['search_term'])  ? sanitize_text_field($_GET['search_term'])  : '';

    $query_args = [
        'post_type'      => 'ipc_oferta',
        'posts_per_page' => 20,
        'paged'          => $paged,
        'post_status'    => 'publish',
        'order'          => $order,
    ];

    if ($orderby === 'titulo') {
        $query_args['orderby'] = 'title';
    } elseif ($orderby === 'fecha_oferta') {
        $query_args['orderby']  = 'meta_value';
        $query_args['meta_key'] = 'ipc_fecha';
    } elseif ($orderby === 'precio') {
        $query_args['orderby']  = 'meta_value_num';
        $query_args['meta_key'] = 'ipc_precio';
    } elseif ($orderby === 'visitas') {
        $query_args['orderby']  = 'meta_value_num';
        $query_args['meta_key'] = 'ipc_visitas';
    } elseif ($orderby === 'clicks') {
        $query_args['orderby']  = 'meta_value_num';
        $query_args['meta_key'] = 'ipc_clicks';
    } elseif ($orderby === 'ID') {
        $query_args['orderby'] = 'ID';
    } elseif ($orderby === 'descuento') {
        $query_args['orderby']  = 'meta_value_num';
        $query_args['meta_key'] = 'ipc_descuento';
    } elseif ($orderby === 'marketplace') {
        $query_args['orderby']  = 'meta_value';
        $query_args['meta_key'] = 'ipc_marketplace';
    } elseif ($orderby === 'marca') {
        $query_args['orderby'] = 'title';
        $query_args['order']   = $order;
    } elseif ($orderby === 'categoria') {
        $query_args['orderby'] = 'title';
        $query_args['order']   = $order;
    } elseif ($orderby === 'producto') {
        $query_args['orderby'] = 'title';
        $query_args['order']   = $order;
    } elseif ($orderby === 'country') {
        $query_args['orderby']  = 'meta_value';
        $query_args['meta_key'] = 'ipc_country';
    } elseif ($orderby === 'product_code') {
        $query_args['orderby']  = 'meta_value';
        $query_args['meta_key'] = 'ipc_product_code';
    } else {
        $query_args['orderby'] = 'date';
    }

    if ($search_field && $search_term) {
        if ($search_field === 'titulo') {
            $query_args['s'] = $search_term;
        } elseif ($search_field === 'product_code') {
            if (!isset($query_args['meta_query'])) $query_args['meta_query'] = [];
            $query_args['meta_query'][] = ['key' => 'ipc_product_code', 'value' => $search_term, 'compare' => 'LIKE'];
        } elseif ($search_field === 'marketplace') {
            if (!isset($query_args['meta_query'])) $query_args['meta_query'] = [];
            $query_args['meta_query'][] = ['key' => 'ipc_marketplace', 'value' => $search_term, 'compare' => 'LIKE'];
        } elseif ($search_field === 'country') {
            if (!isset($query_args['meta_query'])) $query_args['meta_query'] = [];
            $query_args['meta_query'][] = ['key' => 'ipc_country', 'value' => strtoupper($search_term)];
        } elseif ($search_field === 'categoria') {
            if (!isset($query_args['tax_query'])) $query_args['tax_query'] = [];
            $query_args['tax_query'][] = ['taxonomy' => 'ipc_categoria', 'field' => 'slug', 'terms' => sanitize_title($search_term)];
        } elseif ($search_field === 'marca') {
            if (!isset($query_args['tax_query'])) $query_args['tax_query'] = [];
            $query_args['tax_query'][] = ['taxonomy' => 'ipc_marca', 'field' => 'slug', 'terms' => sanitize_title($search_term)];
        } elseif ($search_field === 'producto') {
            if (!isset($query_args['tax_query'])) $query_args['tax_query'] = [];
            $query_args['tax_query'][] = ['taxonomy' => 'ipc_producto', 'field' => 'slug', 'terms' => sanitize_title($search_term)];
        }
    }

    $query = new WP_Query($query_args);

    // Helper para links de ordenación
    $sort_url = function($col) use ($orderby, $order, $search_field, $search_term) {
        $new_order = ($orderby === $col && $order === 'ASC') ? 'DESC' : 'ASC';
        $url = admin_url('admin.php?page=ipc-ofertas&orderby=' . $col . '&order=' . $new_order);
        if ($search_field && $search_term) $url .= '&search_field=' . urlencode($search_field) . '&search_term=' . urlencode($search_term);
        return $url;
    };
    $sort_arrow = function($col) use ($orderby, $order) {
        if ($orderby !== $col) return '';
        return $order === 'ASC' ? ' ▲' : ' ▼';
    };
    ?>
    <div class="wrap">
        <h1>Todas las Ofertas <a href="<?php echo admin_url('admin.php?page=ipc-settings'); ?>" class="page-title-action">Ajustes</a></h1>
        <form method="get" action="<?php echo admin_url('admin.php'); ?>" style="margin-bottom:16px;display:flex;gap:8px;align-items:center">
            <input type="hidden" name="page" value="ipc-ofertas">
            <select name="search_field" style="width:150px">
                <option value="">— Buscar por —</option>
                <?php
                $search_fields = ['titulo' => 'Título', 'product_code' => 'Código', 'marketplace' => 'Marketplace', 'country' => 'País', 'categoria' => 'Categoría', 'marca' => 'Marca', 'producto' => 'Producto'];
                foreach ($search_fields as $val => $label):
                ?>
                <option value="<?php echo $val; ?>" <?php selected($search_field, $val); ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search_term" value="<?php echo esc_attr($search_term); ?>" placeholder="Buscar..." style="width:220px">
            <button type="submit" class="button">Buscar</button>
            <a href="<?php echo admin_url('admin.php?page=ipc-ofertas'); ?>" class="button">Limpiar</a>
        </form>
        <p>Total: <strong><?php echo $query->found_posts; ?></strong> ofertas</p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width:60px">Img</th>
                    <th><a href="<?php echo $sort_url('titulo'); ?>">Título<?php echo $sort_arrow('titulo'); ?></a></th>
                    <th><a href="<?php echo $sort_url('precio'); ?>">Precio<?php echo $sort_arrow('precio'); ?></a></th>
                    <th><a href="<?php echo $sort_url('descuento'); ?>">Descuento<?php echo $sort_arrow('descuento'); ?></a></th>
                    <th><a href="<?php echo $sort_url('marketplace'); ?>">Marketplace<?php echo $sort_arrow('marketplace'); ?></a></th>
                    <th><a href="<?php echo $sort_url('product_code'); ?>">Código<?php echo $sort_arrow('product_code'); ?></a></th>
                    <th><a href="<?php echo $sort_url('country'); ?>">País<?php echo $sort_arrow('country'); ?></a></th>
                    <th><a href="<?php echo $sort_url('categoria'); ?>">Categoría<?php echo $sort_arrow('categoria'); ?></a></th>
                    <th><a href="<?php echo $sort_url('marca'); ?>">Marca<?php echo $sort_arrow('marca'); ?></a></th>
                    <th><a href="<?php echo $sort_url('producto'); ?>">Producto<?php echo $sort_arrow('producto'); ?></a></th>
                    <th><a href="<?php echo $sort_url('fecha_oferta'); ?>">Fecha<?php echo $sort_arrow('fecha_oferta'); ?></a></th>
                    <th><a href="<?php echo $sort_url('visitas'); ?>">👁 Visitas<?php echo $sort_arrow('visitas'); ?></a></th>
                    <th><a href="<?php echo $sort_url('clicks'); ?>">🖱 Clicks<?php echo $sort_arrow('clicks'); ?></a></th>
                    <th><a href="<?php echo $sort_url('ID'); ?>">Post ID<?php echo $sort_arrow('ID'); ?></a></th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($query->have_posts()): while ($query->have_posts()): $query->the_post();
                $id         = get_the_ID();
                $img        = get_post_meta($id, 'ipc_img', true);
                $precio     = get_post_meta($id, 'ipc_precio', true);
                $descuento  = get_post_meta($id, 'ipc_descuento', true);
                $marketplace= get_post_meta($id, 'ipc_marketplace', true);
                $prod_code   = get_post_meta($id, 'ipc_product_code', true);
                $country    = get_post_meta($id, 'ipc_country', true);
                $fecha      = get_post_meta($id, 'ipc_fecha', true);
                $visitas    = intval(get_post_meta($id, 'ipc_visitas', true));
                $clicks_cnt = intval(get_post_meta($id, 'ipc_clicks', true));
                $terms        = get_the_terms($id, 'ipc_categoria');
                $cat          = $terms ? $terms[0]->slug : '—';
                $marca_terms  = get_the_terms($id, 'ipc_marca');
                $marca_val    = (!empty($marca_terms) && !is_wp_error($marca_terms)) ? esc_html($marca_terms[0]->name) : '—';
                $prod_terms   = get_the_terms($id, 'ipc_producto');
                $prod_val     = (!empty($prod_terms) && !is_wp_error($prod_terms)) ? esc_html(mb_strimwidth($prod_terms[0]->name, 0, 20, '…')) : '—';
                $delete_url   = admin_url('admin.php?page=ipc-ofertas&delete=' . $id . '&orderby=' . $orderby . '&order=' . $order);
                if ($search_field && $search_term) $delete_url .= '&search_field=' . urlencode($search_field) . '&search_term=' . urlencode($search_term);
                $currency_sym = ipc_currency_symbol(get_post_meta($id, 'ipc_currency', true) ?: 'EUR');
            ?>
                <tr>
                    <td><?php if ($img): ?><img src="<?php echo esc_url($img); ?>" style="width:50px;height:50px;object-fit:contain;background:#f5f5f5;border-radius:4px"><?php endif; ?></td>
                    <td><a href="<?php echo esc_url(get_permalink($id)); ?>" target="_blank"><strong><?php echo esc_html(mb_strimwidth(get_the_title(), 0, 50, '…')); ?></strong></a></td>
                    <td><?php echo $precio ? esc_html($precio) . esc_html($currency_sym) : '—'; ?></td>
                    <td><?php echo $descuento ? '<span style="background:#ff3b30;color:#fff;padding:2px 6px;border-radius:4px;font-size:11px">-' . esc_html($descuento) . '%</span>' : '—'; ?></td>
                    <td><?php echo esc_html(ucfirst($marketplace ?: '—')); ?></td>
                    <td><code style="font-size:11px"><?php echo esc_html($prod_code ?: '—'); ?></code></td>
                    <td><code><?php echo esc_html($country ?: '—'); ?></code></td>
                    <td><code><?php echo esc_html($cat); ?></code></td>
                    <td><?php echo $marca_val; ?></td>
                    <td><?php echo $prod_val; ?></td>
                    <td><?php echo esc_html($fecha ?: '—'); ?></td>
                    <td style="text-align:center"><?php echo $visitas ?: '—'; ?></td>
                    <td style="text-align:center"><?php echo $clicks_cnt ?: '—'; ?></td>
                    <td><code style="font-size:12px"><?php echo $id; ?></code></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=ipc-edit&post_id=' . $id); ?>" class="button button-small">Editar</a>
                        <a href="<?php echo $delete_url; ?>" class="button button-small" style="color:#cc0000;margin-left:4px" onclick="return confirm('¿Eliminar esta oferta?')">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; wp_reset_postdata();
            else: ?>
                <tr><td colspan="15">No hay ofertas todavía.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
        $total_pages = $query->max_num_pages;
        if ($total_pages > 1) {
            echo '<div style="margin-top:16px">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $url = admin_url('admin.php?page=ipc-ofertas&paged=' . $i . '&orderby=' . $orderby . '&order=' . $order);
                if ($search_field && $search_term) $url .= '&search_field=' . urlencode($search_field) . '&search_term=' . urlencode($search_term);
                $style = $i === $paged ? 'font-weight:bold;text-decoration:underline' : '';
                echo '<a href="' . $url . '" style="margin-right:6px;' . $style . '">' . $i . '</a>';
            }
            echo '</div>';
        }
        ?>
    </div>
    <?php
}

// ─────────────────────────────────────────
// 8. DASHBOARD DE ESTADÍSTICAS
// ─────────────────────────────────────────
function ipc_stats_page() {
    // Obtener todas las ofertas para calcular KPIs
    $all = get_posts(['post_type' => 'ipc_oferta', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
    $total_visitas = 0;
    $total_clicks  = 0;
    $data_ofertas  = [];

    foreach ($all as $pid) {
        $v = intval(get_post_meta($pid, 'ipc_visitas', true));
        $c = intval(get_post_meta($pid, 'ipc_clicks', true));
        $total_visitas += $v;
        $total_clicks  += $c;
        $terms = get_the_terms($pid, 'ipc_categoria');
        $mp    = get_post_meta($pid, 'ipc_marketplace', true);
        $pais  = get_post_meta($pid, 'ipc_country', true) ?: '—';
        $data_ofertas[] = [
            'id'       => $pid,
            'titulo'   => get_the_title($pid),
            'visitas'  => $v,
            'clicks'   => $c,
            'ctr'      => $v > 0 ? round(($c / $v) * 100, 1) : 0,
            'cat'      => $terms ? $terms[0]->name : '—',
            'mp'       => ucfirst($mp ?: '—'),
            'pais'     => $pais,
            'ultimo'   => get_post_meta($pid, 'ipc_ultimo_click', true) ?: '—',
        ];
    }

    $ctr_global = $total_visitas > 0 ? round(($total_clicks / $total_visitas) * 100, 1) : 0;

    // Top 10 por clicks
    usort($data_ofertas, fn($a, $b) => $b['clicks'] - $a['clicks']);
    $top10 = array_slice($data_ofertas, 0, 10);

    // CTR por categoría
    $by_cat = [];
    foreach ($data_ofertas as $d) {
        $cat = $d['cat'];
        if (!isset($by_cat[$cat])) $by_cat[$cat] = ['visitas' => 0, 'clicks' => 0];
        $by_cat[$cat]['visitas'] += $d['visitas'];
        $by_cat[$cat]['clicks']  += $d['clicks'];
    }
    arsort($by_cat);

    // CTR por marketplace
    $by_mp = [];
    foreach ($data_ofertas as $d) {
        $mp = $d['mp'];
        if (!isset($by_mp[$mp])) $by_mp[$mp] = ['visitas' => 0, 'clicks' => 0];
        $by_mp[$mp]['visitas'] += $d['visitas'];
        $by_mp[$mp]['clicks']  += $d['clicks'];
    }

    // CTR por país
    $by_country = [];
    foreach ($data_ofertas as $d) {
        $pais = $d['pais'];
        if (!isset($by_country[$pais])) $by_country[$pais] = ['visitas' => 0, 'clicks' => 0];
        $by_country[$pais]['visitas'] += $d['visitas'];
        $by_country[$pais]['clicks']  += $d['clicks'];
    }
    arsort($by_country);

    // Visitantes por país (tracking global + agregado de ofertas)
    $visitor_global = json_decode(get_option('ipc_visitor_countries', '{}'), true);
    $visitor_visitas = [];
    $visitor_clicks = [];
    foreach ($data_ofertas as $d) {
        $pv = json_decode(get_post_meta($d['id'], 'ipc_visitas_paises', true) ?: '{}', true);
        $pc = json_decode(get_post_meta($d['id'], 'ipc_clicks_paises', true) ?: '{}', true);
        foreach ($pv as $c => $n) {
            $visitor_visitas[$c] = ($visitor_visitas[$c] ?? 0) + $n;
        }
        foreach ($pc as $c => $n) {
            $visitor_clicks[$c] = ($visitor_clicks[$c] ?? 0) + $n;
        }
    }
    arsort($visitor_global);
    arsort($visitor_visitas);
    $sin_clicks = array_filter($data_ofertas, fn($d) => $d['visitas'] > 0 && $d['clicks'] === 0);
    usort($sin_clicks, fn($a, $b) => $b['visitas'] - $a['visitas']);
    $sin_clicks = array_slice($sin_clicks, 0, 5);
    ?>
    <div class="wrap" style="max-width:1100px">
        <h1>📊 Dashboard de Estadísticas</h1>

        <style>
        .ipc-kpi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin:20px 0 28px; }
        .ipc-kpi { background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:20px 24px; }
        .ipc-kpi__label { font-size:12px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
        .ipc-kpi__value { font-size:36px; font-weight:800; color:#111; line-height:1; }
        .ipc-kpi__sub { font-size:12px; color:#aaa; margin-top:4px; }
        .ipc-stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .ipc-stat-box { background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:16px 20px; }
        .ipc-stat-box h3 { margin:0 0 12px; font-size:14px; font-weight:700; color:#333; }
        .ipc-bar-wrap { display:flex; align-items:center; gap:8px; margin-bottom:6px; font-size:12px; }
        .ipc-bar { height:8px; border-radius:4px; background:#2271b1; min-width:4px; }
        </style>

        <!-- KPIs -->
        <div class="ipc-kpi-grid">
            <div class="ipc-kpi">
                <div class="ipc-kpi__label">👁 Visitas totales</div>
                <div class="ipc-kpi__value"><?php echo number_format($total_visitas); ?></div>
                <div class="ipc-kpi__sub"><?php echo count($all); ?> ofertas publicadas</div>
            </div>
            <div class="ipc-kpi">
                <div class="ipc-kpi__label">🖱 Clicks afiliados</div>
                <div class="ipc-kpi__value"><?php echo number_format($total_clicks); ?></div>
                <div class="ipc-kpi__sub">Botones de compra pulsados</div>
            </div>
            <div class="ipc-kpi" style="<?php echo $ctr_global >= 20 ? 'border-color:#16a34a' : ($ctr_global >= 10 ? 'border-color:#f59e0b' : ''); ?>">
                <div class="ipc-kpi__label">📈 CTR Global</div>
                <div class="ipc-kpi__value" style="color:<?php echo $ctr_global >= 20 ? '#16a34a' : ($ctr_global >= 10 ? '#f59e0b' : '#111'); ?>"><?php echo $ctr_global; ?>%</div>
                <div class="ipc-kpi__sub">Clicks / Visitas</div>
            </div>
        </div>

        <!-- TOP 10 + Visitas sin clicks -->
        <div style="margin-bottom:20px">
            <div class="ipc-stat-box">
                <h3>🔥 Top 10 ofertas por clicks</h3>
                <table class="widefat striped" style="font-size:13px">
                    <thead><tr><th>#</th><th>Oferta</th><th>Código</th><th>Categoría</th><th>Marketplace</th><th>País</th><th style="text-align:center">Visitas</th><th style="text-align:center">Clicks</th><th style="text-align:center">CTR</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($top10 as $i => $d): 
                        $pc = get_post_meta($d['id'], 'ipc_product_code', true) ?: '—';
                    ?>
                    <tr>
                        <td style="color:#aaa;font-size:11px"><?php echo $i+1; ?></td>
                        <td><a href="<?php echo get_permalink($d['id']); ?>" target="_blank"><?php echo esc_html(mb_strimwidth($d['titulo'], 0, 45, '…')); ?></a></td>
                        <td><code style="font-size:11px"><?php echo esc_html($pc); ?></code></td>
                        <td><?php echo esc_html($d['cat']); ?></td>
                        <td><?php echo esc_html($d['mp']); ?></td>
                        <td><code><?php echo esc_html($d['pais']); ?></code></td>
                        <td style="text-align:center"><?php echo (int)$d['visitas']; ?></td>
                        <td style="text-align:center"><strong><?php echo (int)$d['clicks']; ?></strong></td>
                        <td style="text-align:center">
                            <?php if ($d['visitas'] > 0): ?>
                            <span style="background:<?php echo $d['ctr'] >= 20 ? '#dcfce7' : ($d['ctr'] >= 10 ? '#fef9c3' : '#f3f4f6'); ?>;color:<?php echo $d['ctr'] >= 20 ? '#16a34a' : ($d['ctr'] >= 10 ? '#b45309' : '#555'); ?>;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:600"><?php echo $d['ctr']; ?>%</span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><a href="<?php echo admin_url('admin.php?page=ipc-edit&post_id=' . $d['id']); ?>" class="button button-small">Editar</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($top10)): ?><tr><td colspan="11">No hay datos todavía.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CTR por categoría y marketplace -->
        <div class="ipc-stat-grid">
            <div class="ipc-stat-box">
                <h3>📂 CTR por categoría</h3>
                <?php foreach ($by_cat as $cat => $d):
                    $ctr = $d['visitas'] > 0 ? round(($d['clicks'] / $d['visitas']) * 100, 1) : 0;
                    $pct = $d['visitas'] > 0 ? min(100, round(($d['clicks'] / $d['visitas']) * 100)) : 0;
                ?>
                <div class="ipc-bar-wrap">
                    <span style="width:110px;color:#555"><?php echo esc_html(mb_strimwidth($cat, 0, 16, '…')); ?></span>
                    <div class="ipc-bar" style="width:<?php echo $pct * 2; ?>px"></div>
                    <span style="color:#888"><?php echo $ctr; ?>% (<?php echo $d['clicks']; ?>/<?php echo $d['visitas']; ?>)</span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($by_cat)): ?><p style="color:#aaa;font-size:12px">Sin datos.</p><?php endif; ?>
            </div>
            <div class="ipc-stat-box">
                <h3>🛒 CTR por marketplace</h3>
                <?php foreach ($by_mp as $mp => $d):
                    $ctr = $d['visitas'] > 0 ? round(($d['clicks'] / $d['visitas']) * 100, 1) : 0;
                    $pct = $d['visitas'] > 0 ? min(100, round(($d['clicks'] / $d['visitas']) * 100)) : 0;
                ?>
                <div class="ipc-bar-wrap">
                    <span style="width:110px;color:#555"><?php echo esc_html($mp); ?></span>
                    <div class="ipc-bar" style="width:<?php echo $pct * 2; ?>px;background:#FF9900"></div>
                    <span style="color:#888"><?php echo $ctr; ?>% (<?php echo $d['clicks']; ?>/<?php echo $d['visitas']; ?>)</span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($by_mp)): ?><p style="color:#aaa;font-size:12px">Sin datos.</p><?php endif; ?>
            </div>
        </div>

        <!-- Visitantes por país (tracking de visitantes) -->
        <?php if (!empty($visitor_global) || !empty($visitor_visitas) || !empty($visitor_clicks)): ?>
        <div style="margin-bottom:20px">
            <div class="ipc-stat-box">
                <h3>👥 Visitantes por país</h3>
                <table class="widefat striped" style="font-size:13px">
                    <thead><tr><th>País</th><th style="text-align:center">Visitas al sitio</th><th style="text-align:center">Visitas a ofertas</th><th style="text-align:center">Clicks</th></tr></thead>
                    <tbody>
                    <?php
                    $all_countries = array_unique(array_merge(array_keys($visitor_global), array_keys($visitor_visitas), array_keys($visitor_clicks)));
                    sort($all_countries);
                    foreach ($all_countries as $c):
                        $vg = $visitor_global[$c] ?? 0;
                        $vv = $visitor_visitas[$c] ?? 0;
                        $vc = $visitor_clicks[$c] ?? 0;
                        $has_offers = false;
                        foreach ($data_ofertas as $d) {
                            if ($d['pais'] === $c) { $has_offers = true; break; }
                        }
                    ?>
                    <tr>
                        <td>
                            <code><?php echo esc_html($c); ?></code>
                            <?php if (!$has_offers && $vg > 0): ?>
                            <span style="background:#fef3c7;color:#b45309;font-size:10px;padding:2px 6px;border-radius:8px;margin-left:6px">sin ofertas</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center"><?php echo $vg ?: '—'; ?></td>
                        <td style="text-align:center"><?php echo $vv ?: '—'; ?></td>
                        <td style="text-align:center"><strong><?php echo $vc ?: '—'; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTR por país (ofertas) -->
        <?php if (!empty($by_country)): ?>
        <div class="ipc-stat-box" style="margin-bottom:20px">
            <h3>🌍 CTR por país</h3>
            <?php foreach ($by_country as $pais => $d):
                $ctr = $d['visitas'] > 0 ? round(($d['clicks'] / $d['visitas']) * 100, 1) : 0;
                $pct = $d['visitas'] > 0 ? min(100, round(($d['clicks'] / $d['visitas']) * 100)) : 0;
            ?>
            <div class="ipc-bar-wrap">
                <span style="width:110px;color:#555"><?php echo esc_html($pais); ?></span>
                <div class="ipc-bar" style="width:<?php echo $pct * 2; ?>px;background:#16a34a"></div>
                <span style="color:#888"><?php echo $ctr; ?>% (<?php echo $d['clicks']; ?>/<?php echo $d['visitas']; ?>)</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Visitas sin clicks -->
        <?php if (!empty($sin_clicks)): ?>
        <div class="ipc-stat-box">
            <h3>⚠️ Ofertas con visitas pero sin clicks <span style="font-size:12px;font-weight:400;color:#999">— candidatas a optimizar</span></h3>
            <table class="widefat" style="font-size:13px">
                <thead><tr><th>Oferta</th><th>Categoría</th><th>Marketplace</th><th>País</th><th style="text-align:center">Visitas</th></tr></thead>
                <tbody>
                <?php foreach ($sin_clicks as $d): ?>
                <tr>
                    <td><a href="<?php echo esc_url(admin_url('admin.php?page=ipc-edit&post_id=' . $d['id'])); ?>"><?php echo esc_html(mb_strimwidth($d['titulo'], 0, 55, '…')); ?></a></td>
                    <td><?php echo esc_html($d['cat']); ?></td>
                    <td><?php echo esc_html($d['mp']); ?></td>
                    <td><code><?php echo esc_html($d['pais']); ?></code></td>
                    <td style="text-align:center"><?php echo $d['visitas']; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// ─────────────────────────────────────────
// 9. JS TRACKING — VISITAS Y CLICKS
// ─────────────────────────────────────────
add_action('wp_footer', function() {
    if (!is_singular('ipc_oferta') && !is_tax('ipc_categoria') && !is_post_type_archive('ipc_oferta') && !is_front_page()) return;
    ?>
    <script>
    (function() {
        var restUrl  = '<?php echo esc_url(rest_url('ipc/v1')); ?>';
        var ga4      = <?php echo get_option('ipc_ga4_enabled', 0) ? 'true' : 'false'; ?>;
        var ipcCountry = (document.cookie.match(/ipc_country=([A-Z]{2})/) || [])[1] || '';

        function sendGA4(eventName, params) {
            if (!ga4 || typeof gtag === 'undefined') return;
            gtag('event', eventName, params);
        }

        // Registrar visita en página de oferta individual
        <?php if (is_singular('ipc_oferta')): ?>
        var postId    = <?php echo get_the_ID(); ?>;
        var postTitle = <?php echo json_encode(get_the_title()); ?>;
        var postCat   = <?php
            $t = get_the_terms(get_the_ID(), 'ipc_categoria');
            echo json_encode($t ? $t[0]->slug : '');
        ?>;
        fetch(restUrl + '/visita/' + postId + '?country=' + ipcCountry, { method: 'POST', keepalive: true }).catch(function(){});
        sendGA4('affiliate_visit', { post_id: postId, post_title: postTitle, category: postCat });
        <?php endif; ?>

        // Registrar click en botón de compra
        document.addEventListener('mousedown', function(e) {
            var btn = e.target.closest('a.ipc-btn, a.ipc-widget__btn');
            if (!btn) return;
            var pid = btn.getAttribute('data-post-id');
            if (!pid) {
                var parent = btn.parentElement;
                while (parent) {
                    pid = parent.getAttribute('data-post-id');
                    if (pid) break;
                    parent = parent.parentElement;
                }
            }
            if (!pid) return;
            fetch(restUrl + '/click/' + pid + '?country=' + ipcCountry, { method: 'POST', keepalive: true }).catch(function(){});
            // Evento GA4
            var card = btn.closest('[data-post-id]');
            var title = card ? (card.querySelector('.ipc-card__title, .ipc-widget__name') || {}).innerText || '' : '';
            sendGA4('affiliate_click', { post_id: pid, post_title: title.trim(), value: 1 });
        });
    })();
    </script>
    <?php
});

// ─────────────────────────────────────────
// 10. COOKIE DE PAÍS — JS CLIENTE
// ─────────────────────────────────────────
add_action('wp_footer', function() {
    if (is_admin()) return;
    ?>
    <script>
    (function() {
        var restUrl = '<?php echo esc_url(rest_url('ipc/v1')); ?>';
        if (!document.cookie.match(/ipc_country=/)) {
            var lang = navigator.language || navigator.userLanguage || '';
            var parts = lang.split('-');
            var country = parts.length > 1 ? parts[1].toUpperCase() : '';
            if (country && /^[A-Z]{2}$/.test(country)) {
                document.cookie = 'ipc_country=' + country + ';path=/;max-age=86400;samesite=lax';
            }
        }
        if (!document.cookie.match(/ipc_visited=/)) {
            document.cookie = 'ipc_visited=1;path=/;max-age=3600;samesite=lax';
            var c = (document.cookie.match(/ipc_country=([A-Z]{2})/) || [])[1] || '';
            if (c) fetch(restUrl + '/visitor/' + c, { method: 'POST', keepalive: true }).catch(function(){});
        }
    })();
    </script>
    <?php
}, 1);

// ─────────────────────────────────────────
// 11. WIDGET DE OFERTAS
// ─────────────────────────────────────────
add_action('widgets_init', function() {
    register_widget('IPC_Widget_Ofertas');
});

class IPC_Widget_Ofertas extends WP_Widget {

    public function __construct() {
        parent::__construct('ipc_widget_ofertas', 'IPC — Ofertas destacadas', [
            'description' => 'Muestra tarjetas de oferta en el sidebar con diseño personalizable.',
        ]);
    }

    public function widget($args, $instance) {
        $titulo      = !empty($instance['titulo'])      ? $instance['titulo']      : 'Ofertas destacadas';
        $categoria   = !empty($instance['categoria'])   ? $instance['categoria']   : '';
        $marketplace = !empty($instance['marketplace']) ? $instance['marketplace'] : '';
        $limite      = !empty($instance['limite'])      ? intval($instance['limite']) : 3;
        $orderby     = !empty($instance['orderby'])     ? $instance['orderby']     : 'fecha';
        $color_titulo= !empty($instance['color_titulo'])? $instance['color_titulo']: '#111111';
        $color_btn   = !empty($instance['color_btn'])   ? $instance['color_btn']   : '#FF9900';
        $color_precio= !empty($instance['color_precio'])? $instance['color_precio']: '#111111';

        $query_args = [
            'post_type'      => 'ipc_oferta',
            'post_status'    => 'publish',
            'posts_per_page' => $limite,
        ];
        if ($orderby === 'descuento') {
            $query_args['orderby']  = 'meta_value_num';
            $query_args['meta_key'] = 'ipc_descuento';
            $query_args['order']    = 'DESC';
        } elseif ($orderby === 'clicks') {
            $query_args['orderby']  = 'meta_value_num';
            $query_args['meta_key'] = 'ipc_clicks';
            $query_args['order']    = 'DESC';
        } else {
            $query_args['orderby']  = 'meta_value';
            $query_args['meta_key'] = 'ipc_fecha';
            $query_args['order']    = 'DESC';
        }
        if ($categoria) {
            $query_args['tax_query'] = [[
                'taxonomy' => 'ipc_categoria',
                'field'    => 'slug',
                'terms'    => $categoria,
            ]];
        }
        if ($marketplace) {
            $query_args['meta_query'] = [[
                'key'     => 'ipc_marketplace',
                'value'   => $marketplace,
                'compare' => 'LIKE',
            ]];
    }

    $query = new WP_Query($query_args);
        if (!$query->have_posts()) return;

        echo $args['before_widget'];
        ?>
        <style>
        .ipc-widget { font-family: 'DM Sans', sans-serif; }
        .ipc-widget__title { font-size: 20px; font-weight: 800; color: <?php echo esc_attr($color_titulo); ?>; margin: 0 0 14px; padding-bottom: 8px; border-bottom: 2px solid <?php echo esc_attr($color_titulo); ?>; }
        .ipc-widget__list { display: flex; flex-direction: column; gap: 16px; }
        .ipc-widget__item { display: flex; flex-direction: column; background: #fff; border-radius: 12px; border: 1px solid #f0f0f0; overflow: hidden; transition: box-shadow 0.18s ease; }
        .ipc-widget__item:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.09); }
        .ipc-widget__img-wrap { position: relative; background: #fff; display: flex; align-items: center; justify-content: center; padding: 14px; height: 140px; border-bottom: 1px solid #f5f5f5; }
        .ipc-widget__img-wrap img { max-height: 110px; max-width: 100%; object-fit: contain; }
        .ipc-widget__badge-discount { position: absolute; top: 8px; right: 8px; background: #ff3b30; color: #fff; font-size: 28px; font-weight: 800; line-height: 1.2; padding: 6px 12px; border-radius: 10px; }
        .ipc-widget__body { padding: 10px 12px 12px; display: flex; flex-direction: column; gap: 6px; }
        .ipc-widget__name { font-size: 12px; font-weight: 600; color: #222; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-decoration: none; }
        .ipc-widget__name:hover { text-decoration: underline; }
        .ipc-widget__prices { display: flex; align-items: baseline; gap: 7px; flex-wrap: wrap; }
        .ipc-widget__price { font-size: 26px; font-weight: 800; color: <?php echo esc_attr($color_precio); ?>; line-height: 1; }
        .ipc-widget__price-old { font-size: 12px; color: #bbb; text-decoration: line-through; }
        .ipc-widget__btn { display: block; text-align: center; font-size: 12px; font-weight: 600; padding: 7px 10px; border-radius: 8px; text-decoration: none !important; color: #fff !important; background: <?php echo esc_attr($color_btn); ?>; transition: opacity 0.15s; margin-top: 2px; }
        .ipc-widget__btn:hover { opacity: 0.85; }
        .ipc-widget__more { display: block; text-align: center; font-size: 11px; color: #999; text-decoration: none; margin-top: 2px; }
        .ipc-widget__more:hover { color: #333; }
        </style>

        <div class="ipc-widget">
            <?php if ($titulo): ?><h2 class="ipc-widget__title"><?php echo esc_html($titulo); ?></h2><?php endif; ?>
            <div class="ipc-widget__list">
            <?php while ($query->have_posts()): $query->the_post();
                $pid      = get_the_ID();
                $img      = get_post_meta($pid, 'ipc_img', true);
                $precio   = str_replace('.', ',', get_post_meta($pid, 'ipc_precio', true));
                $p_old    = str_replace('.', ',', get_post_meta($pid, 'ipc_precio_old', true));
                $desc     = get_post_meta($pid, 'ipc_descuento', true);
                $url_af   = get_post_meta($pid, 'ipc_url', true);
                $post_url = get_permalink($pid);
                $mp       = strtolower(get_post_meta($pid, 'ipc_marketplace', true) ?: 'tienda');
                $btn_labels = ['amazon' => 'Ver en Amazon', 'ebay' => 'Ver en eBay', 'aliexpress' => 'Ver en AliExpress', 'pccomponentes' => 'Ver en PcComponentes'];
                $btn_lbl  = $btn_labels[$mp] ?? 'Ver oferta';
            ?>
            <div class="ipc-widget__item" data-post-id="<?php echo $pid; ?>">
                <a href="<?php echo esc_url($post_url); ?>" class="ipc-widget__img-wrap">
                    <?php if ($desc): ?><span class="ipc-widget__badge-discount">-<?php echo esc_html($desc); ?>%</span><?php endif; ?>
                    <?php if ($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy"><?php endif; ?>
                </a>
                <div class="ipc-widget__body">
                    <a href="<?php echo esc_url($post_url); ?>" class="ipc-widget__name"><?php echo esc_html(mb_strimwidth(get_the_title(), 0, 60, '…')); ?></a>
                    <div class="ipc-widget__prices">
                        <span class="ipc-widget__price"><?php echo esc_html($precio); ?>€</span>
                        <?php if ($p_old): ?><span class="ipc-widget__price-old"><?php echo esc_html($p_old); ?>€</span><?php endif; ?>
                    </div>
                    <?php if ($url_af): ?>
                    <a href="<?php echo esc_url($url_af); ?>" class="ipc-widget__btn ipc-btn" target="_blank" rel="sponsored nofollow noopener" data-post-id="<?php echo $pid; ?>"><?php echo $btn_lbl; ?></a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($post_url); ?>" class="ipc-widget__more">Ver más detalles →</a>
                </div>
            </div>
            <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form($instance) {
        $titulo      = $instance['titulo']      ?? 'Ofertas destacadas';
        $categoria   = $instance['categoria']   ?? '';
        $marketplace = $instance['marketplace'] ?? '';
        $limite      = $instance['limite']      ?? 3;
        $orderby     = $instance['orderby']     ?? 'fecha';
        $color_titulo= $instance['color_titulo']?? '#111111';
        $color_btn   = $instance['color_btn']   ?? '#FF9900';
        $color_precio= $instance['color_precio']?? '#111111';
        ?>
        <p><label>Título del widget<br><input class="widefat" name="<?php echo $this->get_field_name('titulo'); ?>" type="text" value="<?php echo esc_attr($titulo); ?>"></label></p>
        <p><label>Categoría (slug)<br><input class="widefat" name="<?php echo $this->get_field_name('categoria'); ?>" type="text" value="<?php echo esc_attr($categoria); ?>" placeholder="ej: tablets"></label></p>
        <p><label>Marketplace<br><input class="widefat" name="<?php echo $this->get_field_name('marketplace'); ?>" type="text" value="<?php echo esc_attr($marketplace); ?>" placeholder="ej: amazon"></label></p>
        <p><label>Número de ofertas<br><input class="widefat" name="<?php echo $this->get_field_name('limite'); ?>" type="number" min="1" max="10" value="<?php echo esc_attr($limite); ?>"></label></p>
        <p><label>Ordenar por<br>
            <select class="widefat" name="<?php echo $this->get_field_name('orderby'); ?>">
                <option value="fecha" <?php selected($orderby, 'fecha'); ?>>Más recientes</option>
                <option value="descuento" <?php selected($orderby, 'descuento'); ?>>Mayor descuento</option>
                <option value="clicks" <?php selected($orderby, 'clicks'); ?>>Más clicadas</option>
            </select>
        </label></p>
        <p><label>Color título <input type="color" name="<?php echo $this->get_field_name('color_titulo'); ?>" value="<?php echo esc_attr($color_titulo); ?>"></label></p>
        <p><label>Color precio <input type="color" name="<?php echo $this->get_field_name('color_precio'); ?>" value="<?php echo esc_attr($color_precio); ?>"></label></p>
        <p><label>Color botón <input type="color" name="<?php echo $this->get_field_name('color_btn'); ?>" value="<?php echo esc_attr($color_btn); ?>"></label></p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        return [
            'titulo'      => sanitize_text_field($new_instance['titulo']),
            'categoria'   => sanitize_text_field($new_instance['categoria']),
            'marketplace' => sanitize_text_field($new_instance['marketplace']),
            'limite'      => intval($new_instance['limite']),
            'orderby'     => sanitize_text_field($new_instance['orderby']),
            'color_titulo'=> sanitize_hex_color($new_instance['color_titulo']),
            'color_btn'   => sanitize_hex_color($new_instance['color_btn']),
            'color_precio'=> sanitize_hex_color($new_instance['color_precio']),
        ];
    }
}

// Cargar página de edición
require_once plugin_dir_path(__FILE__) . 'ipc-edit.php';
