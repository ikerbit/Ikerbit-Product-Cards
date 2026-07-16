<?php
/**
 * Plantilla de archivo de taxonomía ipc_categoria
 * Ubicación: /wp-content/plugins/ikerbit-product-cards/templates/taxonomy-ipc_categoria.php
 */
get_header();

$term        = get_queried_object();
$term_name   = $term ? ucfirst($term->name) : 'Ofertas';
$term_desc   = $term ? term_description($term) : '';
$paged       = get_query_var('paged') ?: 1;

$base_args = [
    'post_type'      => 'ipc_oferta',
    'post_status'    => 'publish',
    'posts_per_page' => 24,
    'paged'          => $paged,
    'tax_query'      => [[
        'taxonomy' => 'ipc_categoria',
        'field'    => 'slug',
        'terms'    => $term ? $term->slug : '',
    ]],
];

if (get_option('ipc_auto_filter_country', 0)) {
    $country = ipc_detect_country();
    $base_args['meta_query'] = [
        ['key' => 'ipc_country', 'value' => [$country, 'GLOBAL'], 'compare' => 'IN'],
    ];
}

$query = new WP_Query($base_args);
?>

<style>
.ipc-archive-outer { width: 100vw; position: relative; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw; padding: 0 24px; box-sizing: border-box; }
.ipc-archive { max-width: 1200px; margin: 32px auto; font-family: 'DM Sans', sans-serif; }
.ipc-archive .ipc-wrap--grid { grid-template-columns: repeat(4, 1fr); }
@media (max-width: 1024px) { .ipc-archive .ipc-wrap--grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 720px) { .ipc-archive .ipc-wrap--grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .ipc-archive .ipc-wrap--grid { grid-template-columns: 1fr; } }
.ipc-archive__header { margin-bottom: 32px; }
.ipc-archive__breadcrumb { font-size: 13px; color: #999; margin-bottom: 12px; }
.ipc-archive__breadcrumb a { color: #666; text-decoration: none; }
.ipc-archive__breadcrumb a:hover { text-decoration: underline; }
.ipc-archive__breadcrumb span { margin: 0 6px; }
.ipc-archive__title { font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 800; color: #111; margin: 0 0 8px; }
.ipc-archive__count { font-size: 14px; color: #999; }
.ipc-archive__desc { font-size: 15px; color: #555; line-height: 1.6; margin-top: 8px; }
.ipc-archive__filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
.ipc-archive__filter-label { font-size: 13px; font-weight: 600; color: #555; }
.ipc-archive__filter-btn { font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 20px; border: 1px solid #e0e0e0; background: #fff; cursor: pointer; text-decoration: none; color: #555; transition: all 0.15s; }
.ipc-archive__filter-btn:hover, .ipc-archive__filter-btn.active { background: #111; color: #fff; border-color: #111; }
.ipc-archive__pagination { display: flex; gap: 8px; justify-content: center; margin-top: 40px; flex-wrap: wrap; }
.ipc-archive__page-btn { font-size: 13px; font-weight: 600; padding: 8px 14px; border-radius: 8px; border: 1px solid #e0e0e0; background: #fff; text-decoration: none; color: #555; transition: all 0.15s; }
.ipc-archive__page-btn:hover, .ipc-archive__page-btn.current { background: #111; color: #fff; border-color: #111; }
.ipc-archive__empty { text-align: center; padding: 60px 20px; color: #999; font-size: 16px; }
</style>

<div class="ipc-archive-outer"><div class="ipc-archive">

    <div class="ipc-archive__header">
        <div class="ipc-archive__breadcrumb">
            <a href="<?php echo home_url('/ofertas/'); ?>">Ofertas</a>
            <span>›</span>
            <?php echo esc_html($term_name); ?>
        </div>
        <h1 class="ipc-archive__title">Ofertas en <?php echo esc_html($term_name); ?></h1>
        <p class="ipc-archive__count"><?php echo $query->found_posts; ?> ofertas encontradas</p>
        <?php if ($term_desc): ?><div class="ipc-archive__desc"><?php echo wp_kses_post($term_desc); ?></div><?php endif; ?>
    </div>

    <div class="ipc-archive__filters">
        <span class="ipc-archive__filter-label">Ordenar por:</span>
        <?php
        $current_url = get_term_link($term);
        $orderby = $_GET['orderby'] ?? 'date';
        $filters = ['date' => 'Más recientes (fecha oferta)', 'descuento' => 'Mayor descuento', 'precio_asc' => 'Precio ↑', 'precio_desc' => 'Precio ↓'];
        foreach ($filters as $key => $label):
            $url = add_query_arg('orderby', $key, $current_url);
            $active = $orderby === $key ? ' active' : '';
        ?>
        <a href="<?php echo esc_url($url); ?>" class="ipc-archive__filter-btn<?php echo $active; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>

    <?php
    // Aplicar ordenación desde filtros
    $orderby = $_GET['orderby'] ?? 'date';
    $order_args = [];
    if ($orderby === 'descuento') {
        $order_args = ['orderby' => 'meta_value_num', 'meta_key' => 'ipc_descuento', 'order' => 'DESC'];
    } elseif ($orderby === 'precio_asc') {
        $order_args = ['orderby' => 'meta_value_num', 'meta_key' => 'ipc_precio', 'order' => 'ASC'];
    } elseif ($orderby === 'precio_desc') {
        $order_args = ['orderby' => 'meta_value_num', 'meta_key' => 'ipc_precio', 'order' => 'DESC'];
    }
    if ($order_args) {
        $query = new WP_Query(array_merge($base_args, $order_args));
    }
    ?>

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
            $page_url = add_query_arg(['paged' => $i, 'orderby' => $orderby], get_term_link($term));
            $current  = $i === $paged ? ' current' : '';
        ?>
        <a href="<?php echo esc_url($page_url); ?>" class="ipc-archive__page-btn<?php echo $current; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="ipc-archive__empty">No hay ofertas en esta categoría todavía.</div>
    <?php endif; ?>

</div>

</div><!-- .ipc-archive-outer -->
<?php get_footer(); ?>
