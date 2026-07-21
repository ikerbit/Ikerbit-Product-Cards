<?php
/**
 * Plantilla de archivo general de ofertas
 * Ubicación: /wp-content/plugins/ikerbit-product-cards/templates/archive-ipc_oferta.php
 */
get_header();

$paged    = get_query_var('paged') ?: 1;
$orderby  = $_GET['orden'] ?? 'date';

$query_args = [
    'post_type'      => 'ipc_oferta',
    'post_status'    => 'publish',
    'posts_per_page' => 24,
    'paged'          => $paged,
];

if (get_option('ipc_auto_filter_country', 0)) {
    $country = ipc_detect_country();
    if (!isset($query_args['meta_query'])) $query_args['meta_query'] = ['relation' => 'AND'];
    $query_args['meta_query'][] = ['key' => 'ipc_country', 'value' => [$country, 'GLOBAL'], 'compare' => 'IN'];
}

if ($orderby === 'descuento') {
    $query_args['orderby']  = 'meta_value_num';
    $query_args['meta_key'] = 'ipc_descuento';
    $query_args['order']    = 'DESC';
    $query_args['meta_query'] = [['key' => 'ipc_descuento', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC']];
} elseif ($orderby === 'precio_asc') {
    $query_args['orderby']  = 'meta_value_num';
    $query_args['meta_key'] = 'ipc_precio';
    $query_args['order']    = 'ASC';
} elseif ($orderby === 'precio_desc') {
    $query_args['orderby']  = 'meta_value_num';
    $query_args['meta_key'] = 'ipc_precio';
    $query_args['order']    = 'DESC';
} else {
    $query_args['orderby']  = 'meta_value';
    $query_args['meta_key'] = 'ipc_fecha';
    $query_args['order']    = 'DESC';
    // Solo mostrar ofertas con descuento en el archivo general
    if (!isset($query_args['meta_query'])) $query_args['meta_query'] = ['relation' => 'AND'];
    $query_args['meta_query'][] = ['key' => 'ipc_descuento', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC'];
}

$query      = new WP_Query($query_args);
$categorias = get_terms(['taxonomy' => 'ipc_categoria', 'hide_empty' => true, 'orderby' => 'name']);
?>

<style>
.ipc-archive-outer { width: 100vw; position: relative; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw; padding: 0 24px; box-sizing: border-box; }
.ipc-archive { max-width: 1200px; margin: 32px auto; font-family: 'DM Sans', sans-serif; }
.ipc-archive .ipc-wrap--grid { grid-template-columns: repeat(4, 1fr); }
@media (max-width: 1024px) { .ipc-archive .ipc-wrap--grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 720px) { .ipc-archive .ipc-wrap--grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .ipc-archive .ipc-wrap--grid { grid-template-columns: 1fr; } }
.ipc-archive__breadcrumb { font-size: 13px; color: #999; margin-bottom: 20px; }
.ipc-archive__breadcrumb a { color: #666; text-decoration: none; }
.ipc-archive__breadcrumb a:hover { text-decoration: underline; }
.ipc-archive__breadcrumb span { margin: 0 6px; }

/* CABECERA */
.ipc-archive__header { margin-bottom: 24px; }
.ipc-archive__title { font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 800; color: #111; margin: 0 0 16px; }

/* CATEGORÍAS — pills discretas */
.ipc-archive__cats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
}
.ipc-archive__cat-pill {
    font-size: 12px;
    font-weight: 600;
    color: #666;
    background: #f5f5f5;
    padding: 5px 12px;
    border-radius: 20px;
    text-decoration: none;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}
.ipc-archive__cat-pill:hover {
    background: #111;
    color: #fff;
}

/* FILTROS */
.ipc-archive__filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
.ipc-archive__filter-label { font-size: 13px; font-weight: 600; color: #555; }
.ipc-archive__filter-btn { font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 20px; border: 1px solid #e0e0e0; background: #fff; cursor: pointer; text-decoration: none; color: #555; transition: all 0.15s; }
.ipc-archive__filter-btn:hover, .ipc-archive__filter-btn.active { background: #111; color: #fff; border-color: #111; }

/* PAGINACIÓN */
.ipc-archive__pagination { display: flex; gap: 8px; justify-content: center; margin-top: 40px; flex-wrap: wrap; }
.ipc-archive__page-btn { font-size: 13px; font-weight: 600; padding: 8px 14px; border-radius: 8px; border: 1px solid #e0e0e0; background: #fff; text-decoration: none; color: #555; transition: all 0.15s; }
.ipc-archive__page-btn:hover, .ipc-archive__page-btn.current { background: #111; color: #fff; border-color: #111; }
.ipc-archive__empty { text-align: center; padding: 60px 20px; color: #999; font-size: 16px; }
</style>

<div class="ipc-archive-outer"><div class="ipc-archive">

    <div class="ipc-archive__breadcrumb">
        <a href="<?php echo home_url(); ?>">Inicio</a>
        <span>›</span>
        Ofertas
    </div>

    <div class="ipc-archive__header">
        <h1 class="ipc-archive__title">Últimas ofertas</h1>

        <?php if (!empty($categorias) && !is_wp_error($categorias)): ?>
        <div class="ipc-archive__cats">
            <?php foreach ($categorias as $cat): ?>
            <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="ipc-archive__cat-pill">
                <?php echo esc_html(ucfirst($cat->name)); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="ipc-archive__filters">
        <span class="ipc-archive__filter-label">Ordenar por:</span>
        <?php
        $base_url = home_url('/ofertas/');
        $filters  = ['date' => 'Más recientes', 'descuento' => 'Mayor descuento', 'precio_asc' => 'Precio ↑', 'precio_desc' => 'Precio ↓'];
        foreach ($filters as $key => $label):
            $url    = add_query_arg('orden', $key, $base_url);
            $active = $orderby === $key ? ' active' : '';
        ?>
        <a href="<?php echo esc_url($url); ?>" class="ipc-archive__filter-btn<?php echo $active; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($query->have_posts()): ?>
    <div class="ipc-wrap ipc-wrap--grid">
        <?php while ($query->have_posts()): $query->the_post(); ?>
            <?php echo ipc_render_card(get_post()); ?>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>

    <?php
    $total_pages = $query->max_num_pages;
    if ($total_pages > 1):
    ?>
    <div class="ipc-archive__pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++):
            $url     = add_query_arg(['paged' => $i, 'orden' => $orderby], home_url('/ofertas/'));
            $current = $i === $paged ? ' current' : '';
        ?>
        <a href="<?php echo esc_url($url); ?>" class="ipc-archive__page-btn<?php echo $current; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="ipc-archive__empty">No hay ofertas todavía.</div>
    <?php endif; ?>

</div>

</div><!-- .ipc-archive-outer -->
<?php get_footer(); ?>
