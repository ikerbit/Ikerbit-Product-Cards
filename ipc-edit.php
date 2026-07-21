<?php
/**
 * Página de edición de oferta para Ikerbit Product Cards
 * Ubicación: /wp-content/plugins/ikerbit-product-cards/ipc-edit.php
 */

if (!defined('ABSPATH')) exit;

// ─────────────────────────────────────────
// REGISTRAR SUBMENÚ Y PÁGINA
// ─────────────────────────────────────────
add_action('admin_menu', function() {
    add_submenu_page(
        null, // oculto del menú
        'Editar Oferta',
        'Editar Oferta',
        'manage_options',
        'ipc-edit',
        'ipc_edit_page'
    );
});

// ─────────────────────────────────────────
// PROCESAR GUARDADO
// ─────────────────────────────────────────
function ipc_edit_save($post_id) {
    $campos_texto = [
        'ipc_precio', 'ipc_precio_old', 'ipc_descuento', 'ipc_url', 'ipc_img',
        'ipc_marketplace', 'ipc_rating', 'ipc_rating_count', 'ipc_stock',
        'ipc_badge', 'ipc_fecha', 'ipc_descripcion', 'ipc_custom_description', 'ipc_visitas', 'ipc_clicks', 'ipc_ultimo_click',
        'ipc_country', 'ipc_language', 'ipc_currency', 'ipc_product_code'
    ];

    // Título
    if (isset($_POST['post_title'])) {
        wp_update_post(['ID' => $post_id, 'post_title' => sanitize_text_field($_POST['post_title'])]);
    }

    // Campos de texto simples
    foreach ($campos_texto as $campo) {
        if (isset($_POST[$campo])) {
            $key = $campo;
            $val = ($campo === 'ipc_descripcion' || $campo === 'ipc_custom_description')
                ? wp_kses_post($_POST[$campo])
                : sanitize_text_field($_POST[$campo]);
            update_post_meta($post_id, $key, $val);
        }
    }

    // Imágenes adicionales (array JSON)
    if (isset($_POST['ipc_imagenes_raw'])) {
        $lines = array_filter(array_map('trim', explode("\n", $_POST['ipc_imagenes_raw'])));
        update_post_meta($post_id, 'ipc_imagenes', json_encode(array_values($lines)));
    }

    // Vídeos (array JSON)
    if (isset($_POST['ipc_video_raw'])) {
        $lines = array_filter(array_map('trim', explode("\n", $_POST['ipc_video_raw'])));
        update_post_meta($post_id, 'ipc_video', json_encode(array_values($lines)));
    }

    // Marca
    if (isset($_POST['ipc_marca'])) {
        $nombre = sanitize_text_field($_POST['ipc_marca']);
        if ($nombre) {
            $slug = sanitize_title($nombre);
            $term = get_term_by('slug', $slug, 'ipc_marca');
            if (!$term) $term = wp_insert_term($nombre, 'ipc_marca', ['slug' => $slug]);
            $term_id = is_array($term) ? $term['term_id'] : $term->term_id;
            wp_set_post_terms($post_id, [$term_id], 'ipc_marca');
        } else {
            wp_set_post_terms($post_id, [], 'ipc_marca');
        }
    }

    // Producto
    if (isset($_POST['ipc_producto'])) {
        $nombre = sanitize_text_field($_POST['ipc_producto']);
        if ($nombre) {
            $slug = sanitize_title($nombre);
            $term = get_term_by('slug', $slug, 'ipc_producto');
            if (!$term) $term = wp_insert_term($nombre, 'ipc_producto', ['slug' => $slug]);
            $term_id = is_array($term) ? $term['term_id'] : $term->term_id;
            wp_set_post_terms($post_id, [$term_id], 'ipc_producto');
        } else {
            wp_set_post_terms($post_id, [], 'ipc_producto');
        }
    }

    // Categoría
    if (isset($_POST['ipc_categoria'])) {
        $slug = sanitize_title($_POST['ipc_categoria']);
        if ($slug) {
            $term = get_term_by('slug', $slug, 'ipc_categoria');
            if (!$term) {
                $res = wp_insert_term($slug, 'ipc_categoria');
                $term_id = is_array($res) ? $res['term_id'] : 0;
            } else {
                $term_id = $term->term_id;
            }
            if ($term_id) wp_set_post_terms($post_id, [$term_id], 'ipc_categoria');
        } else {
            wp_set_post_terms($post_id, [], 'ipc_categoria');
        }
    }
}

// ─────────────────────────────────────────
// PÁGINA DE EDICIÓN
// ─────────────────────────────────────────
function ipc_edit_page() {
    if (!current_user_can('manage_options')) wp_die('Sin permisos.');

    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    if (!$post_id) wp_die('ID de oferta no válido.');

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'ipc_oferta') wp_die('Oferta no encontrada.');

    $saved = false;
    if (isset($_POST['ipc_save']) && check_admin_referer('ipc_edit_' . $post_id)) {
        ipc_edit_save($post_id);
        $post  = get_post($post_id); // recargar
        $saved = true;
    }

    // Leer campos actuales
    $titulo       = get_the_title($post);
    $precio       = get_post_meta($post_id, 'ipc_precio', true);
    $precio_old   = get_post_meta($post_id, 'ipc_precio_old', true);
    $descuento    = get_post_meta($post_id, 'ipc_descuento', true);
    $url          = get_post_meta($post_id, 'ipc_url', true);
    $img          = get_post_meta($post_id, 'ipc_img', true);
    $marketplace  = get_post_meta($post_id, 'ipc_marketplace', true);
    $rating       = get_post_meta($post_id, 'ipc_rating', true);
    $rating_count = get_post_meta($post_id, 'ipc_rating_count', true);
    $stock        = get_post_meta($post_id, 'ipc_stock', true);
    $badge        = get_post_meta($post_id, 'ipc_badge', true);
    $fecha        = get_post_meta($post_id, 'ipc_fecha', true);
    $descripcion  = get_post_meta($post_id, 'ipc_descripcion', true);
    $visitas      = get_post_meta($post_id, 'ipc_visitas', true);
    $clicks       = get_post_meta($post_id, 'ipc_clicks', true);
    $ultimo_click = get_post_meta($post_id, 'ipc_ultimo_click', true);
    $country      = get_post_meta($post_id, 'ipc_country', true);
    $language     = get_post_meta($post_id, 'ipc_language', true);
    $currency     = get_post_meta($post_id, 'ipc_currency', true);
    $custom_desc  = get_post_meta($post_id, 'ipc_custom_description', true);
    $prod_code    = get_post_meta($post_id, 'ipc_product_code', true);
    $imagenes_raw = get_post_meta($post_id, 'ipc_imagenes', true);
    $imagenes_arr = [];
    if ($imagenes_raw) {
        $dec = json_decode($imagenes_raw, true);
        $imagenes_arr = is_array($dec) ? $dec : (filter_var($imagenes_raw, FILTER_VALIDATE_URL) ? [$imagenes_raw] : []);
    }

    $video_raw_meta = get_post_meta($post_id, 'ipc_video', true);
    $video_arr = [];
    if ($video_raw_meta) {
        $dec = json_decode($video_raw_meta, true);
        $video_arr = is_array($dec) ? $dec : (filter_var($video_raw_meta, FILTER_VALIDATE_URL) ? [$video_raw_meta] : []);
    }
    $terms        = get_the_terms($post_id, 'ipc_categoria');
    $categoria    = $terms ? $terms[0]->slug : '';
    $marca_terms  = get_the_terms($post_id, 'ipc_marca');
    $marca_val    = $marca_terms ? $marca_terms[0]->name : '';
    $prod_terms   = get_the_terms($post_id, 'ipc_producto');
    $prod_val     = $prod_terms ? $prod_terms[0]->name : '';
    $back_url     = admin_url('admin.php?page=ipc-ofertas');
    $post_url     = get_permalink($post_id);
    ?>
    <div class="wrap" style="max-width:900px">
        <h1 style="display:flex;align-items:center;gap:12px">
            <a href="<?php echo $back_url; ?>" style="font-size:13px;font-weight:400;color:#666;text-decoration:none">← Todas las ofertas</a>
            <span style="color:#ccc">|</span>
            Editar oferta #<?php echo $post_id; ?>
            <a href="<?php echo esc_url($post_url); ?>" target="_blank" style="font-size:12px;font-weight:400;color:#2271b1">Ver en el sitio ↗</a>
        </h1>

        <?php if ($saved): ?>
        <div class="notice notice-success is-dismissible"><p>✅ Oferta guardada correctamente.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=ipc-edit&post_id=' . $post_id)); ?>" id="ipc-edit-form">
            <?php wp_nonce_field('ipc_edit_' . $post_id); ?>
            <input type="hidden" name="ipc_save" value="1">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

                <!-- COLUMNA IZQUIERDA -->
                <div style="display:flex;flex-direction:column;gap:20px">

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Información principal</h2></div>
                        <div class="inside" style="display:flex;flex-direction:column;gap:12px">
                            <?php ipc_field('Título', 'post_title', $titulo); ?>
                            <?php ipc_field('Código producto (ASIN, ID...)', 'ipc_product_code', $prod_code); ?>
                            <?php ipc_field('Precio actual (€)', 'ipc_precio', $precio, 'number'); ?>
                            <?php ipc_field('Precio anterior (€)', 'ipc_precio_old', $precio_old, 'number'); ?>
                            <?php ipc_field('Descuento (%)', 'ipc_descuento', $descuento, 'number'); ?>
                            <?php ipc_field('Badge (Oferta, Flash...)', 'ipc_badge', $badge); ?>
                            <?php ipc_field('Fecha oferta', 'ipc_fecha', $fecha); ?>
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Marketplace y categoría</h2></div>
                        <div class="inside" style="display:flex;flex-direction:column;gap:12px">
                            <?php ipc_field('URL de afiliado', 'ipc_url', $url); ?>
                            <div>
                                <label style="font-weight:600;display:block;margin-bottom:4px">Marketplace</label>
                                <select name="ipc_marketplace" style="width:100%">
                                    <?php foreach (['amazon','aliexpress','ebay','pccomponentes','tienda'] as $mp): ?>
                                    <option value="<?php echo $mp; ?>" <?php selected($marketplace, $mp); ?>><?php echo ucfirst($mp); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php ipc_field('Categoría (slug)', 'ipc_categoria', $categoria); ?>
                            <?php ipc_field('Marca (ej: Samsung)', 'ipc_marca', $marca_val); ?>
                            <?php ipc_field('Producto (ej: Samsung Galaxy S24)', 'ipc_producto', $prod_val); ?>
                            <?php ipc_field('Stock (1=sí, 0=no)', 'ipc_stock', $stock); ?>
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Internacionalización</h2></div>
                        <div class="inside" style="display:flex;flex-direction:column;gap:12px">
                            <?php ipc_field('País (ES, MX, DE, GLOBAL...)', 'ipc_country', $country); ?>
                            <?php ipc_field('Idioma (es, de, en...)', 'ipc_language', $language); ?>
                            <?php ipc_field('Moneda (EUR, MXN, USD...)', 'ipc_currency', $currency); ?>
                            <div style="font-size:11px;color:#999;margin-top:-4px">Se guardan en mayúsculas. GLOBAL = oferta para todos los países.</div>
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Valoraciones</h2></div>
                        <div class="inside" style="display:flex;flex-direction:column;gap:12px">
                            <?php ipc_field('Rating (ej: 4.5)', 'ipc_rating', $rating, 'number'); ?>
                            <?php ipc_field('Número de valoraciones', 'ipc_rating_count', $rating_count, 'number'); ?>
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Estadísticas <span style="font-size:12px;font-weight:400;color:#999">(editables manualmente)</span></h2></div>
                        <div class="inside" style="display:flex;flex-direction:column;gap:12px">
                            <?php ipc_field('Visitas', 'ipc_visitas', $visitas, 'number'); ?>
                            <?php ipc_field('Clicks', 'ipc_clicks', $clicks, 'number'); ?>
                            <?php ipc_field('Último click', 'ipc_ultimo_click', $ultimo_click); ?>
                        </div>
                    </div>

                </div>

                <!-- COLUMNA DERECHA -->
                <div style="display:flex;flex-direction:column;gap:20px">

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Imagen principal</h2></div>
                        <div class="inside" style="display:flex;flex-direction:column;gap:10px">
                            <?php if ($img): ?>
                            <div style="background:#f7f8fa;border-radius:8px;padding:16px;display:flex;align-items:center;justify-content:center;min-height:160px">
                                <img id="ipc-preview-main" src="<?php echo esc_url($img); ?>" style="max-height:150px;max-width:100%;object-fit:contain">
                            </div>
                            <?php else: ?>
                            <div id="ipc-preview-main-wrap" style="background:#f7f8fa;border-radius:8px;padding:16px;min-height:80px;display:flex;align-items:center;justify-content:center;color:#ccc">Sin imagen</div>
                            <?php endif; ?>
                            <label style="font-weight:600;display:block;margin-bottom:4px">URL imagen principal</label>
                            <input type="url" name="ipc_img" value="<?php echo esc_attr($img); ?>" class="regular-text" style="width:100%" oninput="ipcPreview(this.value,'ipc-preview-main')">
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Imágenes adicionales</h2></div>
                        <div class="inside" style="display:flex;flex-direction:column;gap:10px">
                            <?php if (!empty($imagenes_arr)): ?>
                            <div style="display:flex;gap:8px;flex-wrap:wrap" id="ipc-thumbs">
                                <?php foreach ($imagenes_arr as $t): ?>
                                <img src="<?php echo esc_url($t); ?>" style="width:56px;height:56px;object-fit:contain;background:#f7f8fa;border-radius:6px;border:1px solid #eee">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <label style="font-weight:600;display:block;margin-bottom:2px">URLs (una por línea)</label>
                            <textarea name="ipc_imagenes_raw" rows="5" style="width:100%;font-size:12px"><?php echo esc_textarea(implode("\n", $imagenes_arr)); ?></textarea>
                            <p class="description">Pega una URL por línea. Se mostrarán como miniaturas en la página de producto.</p>
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Vídeos</h2></div>
                        <div class="inside" style="display:flex;flex-direction:column;gap:10px">
                            <?php if (!empty($video_arr)): ?>
                            <div style="display:flex;flex-direction:column;gap:6px">
                                <?php foreach ($video_arr as $v): ?>
                                <a href="<?php echo esc_url($v); ?>" target="_blank" style="font-size:11px;color:#2271b1;word-break:break-all"><?php echo esc_html($v); ?></a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <label style="font-weight:600;display:block;margin-bottom:2px">URLs de vídeo (una por línea)</label>
                            <textarea name="ipc_video_raw" rows="4" style="width:100%;font-size:12px"><?php echo esc_textarea(implode("\n", $video_arr)); ?></textarea>
                            <p class="description">YouTube, Vimeo, .mp4 o .m3u8 — una URL por línea.</p>
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Descripción</h2></div>
                        <div class="inside">
                            <textarea name="ipc_descripcion" rows="8" style="width:100%"><?php echo esc_textarea($descripcion); ?></textarea>
                            <p class="description">Texto enriquecido para SEO. Se muestra en la página de producto.</p>
                        </div>
                    </div>

                    <div class="postbox">
                        <div class="postbox-header"><h2 class="hndle">Descripción personalizada</h2></div>
                        <div class="inside">
                            <textarea name="ipc_custom_description" rows="6" style="width:100%"><?php echo esc_textarea($custom_desc); ?></textarea>
                            <p class="description">Si se rellena, sustituye a la descripción original en las cards y listados.</p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- BOTÓN GUARDAR -->
            <div style="position:sticky;bottom:0;background:#fff;border-top:1px solid #e0e0e0;padding:16px 0;margin-top:16px;display:flex;align-items:center;gap:16px;z-index:100">
                <button type="submit" id="ipc-save-btn" class="button button-primary button-large" style="font-size:15px;padding:8px 28px">
                    💾 Guardar oferta
                </button>
                <a href="<?php echo $back_url; ?>" class="button button-large">Cancelar</a>
                <a href="<?php echo esc_url($post_url); ?>" target="_blank" class="button button-large">Ver oferta ↗</a>
                <span id="ipc-save-msg" style="color:#2271b1;font-size:13px;display:none">Guardando...</span>
            </div>

        </form>
    </div>

    <script>
    function ipcPreview(url, imgId) {
        var img = document.getElementById(imgId);
        if (img && url) img.src = url;
    }

    // Confirmación antes de guardar
    document.getElementById('ipc-edit-form').addEventListener('submit', function(e) {
        document.getElementById('ipc-save-btn').disabled = true;
        document.getElementById('ipc-save-msg').style.display = 'inline';
    });
    </script>
    <?php
}

// ─────────────────────────────────────────
// HELPER — CAMPO DE TEXTO
// ─────────────────────────────────────────
function ipc_field($label, $name, $value, $type = 'text') {
    echo '<div>';
    echo '<label style="font-weight:600;display:block;margin-bottom:4px">' . esc_html($label) . '</label>';
    echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" style="width:100%">';
    echo '</div>';
}
