<?php
/**
 * Plantilla single para ipc_oferta
 * Ubicación: /wp-content/plugins/ikerbit-product-cards/templates/single-ipc_oferta.php
 */
get_header();

while (have_posts()) : the_post();
    $post_id      = get_the_ID();
    $titulo       = get_the_title();
    $precio       = str_replace('.', ',', get_post_meta($post_id, 'ipc_precio', true));
    $precio_old   = str_replace('.', ',', get_post_meta($post_id, 'ipc_precio_old', true));
    $descuento    = get_post_meta($post_id, 'ipc_descuento', true);
    $url          = get_post_meta($post_id, 'ipc_url', true);
    $img          = get_post_meta($post_id, 'ipc_img', true);
    $marketplace  = strtolower(get_post_meta($post_id, 'ipc_marketplace', true) ?: 'tienda');
    $rating       = floatval(get_post_meta($post_id, 'ipc_rating', true));
    $rating_count = get_post_meta($post_id, 'ipc_rating_count', true);
    $badge        = get_post_meta($post_id, 'ipc_badge', true);
    $fecha        = get_post_meta($post_id, 'ipc_fecha', true);
    $descripcion  = get_post_meta($post_id, 'ipc_descripcion', true);
    $imagenes     = json_decode(get_post_meta($post_id, 'ipc_imagenes', true) ?: '[]', true);
    $video_raw    = get_post_meta($post_id, 'ipc_video', true);
    $videos       = [];
    if ($video_raw) {
        $decoded = json_decode($video_raw, true);
        if (is_array($decoded)) $videos = $decoded;
        else $videos = [$video_raw];
    }
    $terms        = get_the_terms($post_id, 'ipc_categoria');
    $categoria    = $terms ? $terms[0] : null;
    $marca_terms  = get_the_terms($post_id, 'ipc_marca');
    $marca_single = $marca_terms ? $marca_terms[0] : null;
    $prod_terms   = get_the_terms($post_id, 'ipc_producto');
    $prod_single  = $prod_terms ? $prod_terms[0] : null;

    $stars = '';
    for ($i = 1; $i <= 5; $i++) $stars .= $i <= round($rating) ? '★' : '☆';

    $btn_labels = ['amazon' => 'Ver en Amazon', 'ebay' => 'Ver en eBay', 'aliexpress' => 'Ver en AliExpress', 'pccomponentes' => 'Ver en PcComponentes'];
    $btn_label  = $btn_labels[$marketplace] ?? 'Ver oferta';
    $mp_colors  = ['amazon' => '#FF9900', 'ebay' => '#0064d2', 'aliexpress' => '#e43225', 'pccomponentes' => '#ff6b00', 'tienda' => '#111'];
    $btn_color  = $mp_colors[$marketplace] ?? '#111';
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "<?php echo esc_js($titulo); ?>",
  <?php if ($img): ?>"image": "<?php echo esc_url($img); ?>",<?php endif; ?>
  <?php if ($descripcion): ?>"description": "<?php echo esc_js(strip_tags($descripcion)); ?>",<?php endif; ?>
  <?php if ($rating): ?>"aggregateRating": { "@type": "AggregateRating", "ratingValue": "<?php echo $rating; ?>", "reviewCount": "<?php echo intval($rating_count) ?: 1; ?>" },<?php endif; ?>
  "offers": { "@type": "Offer", "url": "<?php echo esc_url($url); ?>", "priceCurrency": "EUR", "price": "<?php echo esc_attr($precio); ?>", "availability": "https://schema.org/InStock" }
}
</script>

<style>
.ipc-single { max-width: 1200px; margin: 32px auto; padding: 0 16px; font-family: 'DM Sans', sans-serif; }
.ipc-breadcrumb { font-size: 13px; color: #999; margin-bottom: 24px; }
.ipc-breadcrumb a { color: #666; text-decoration: none; }
.ipc-breadcrumb a:hover { text-decoration: underline; }
.ipc-breadcrumb span { margin: 0 6px; }
.ipc-single__layout { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; }
@media (max-width: 720px) { .ipc-single__layout { grid-template-columns: 1fr; } }
.ipc-single__img-main { background: #fff; border: 1px solid #f0f0f0; border-radius: 16px; display: flex; align-items: center; justify-content: center; padding: 32px; min-height: 320px; position: relative; overflow: hidden; }
.ipc-single__img-main img { max-height: 280px; max-width: 100%; object-fit: contain; }
.ipc-single__badge { position: absolute; top: 14px; left: 14px; background: #111; color: #fff; font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 5px 10px; border-radius: 6px; }
.ipc-single__discount { position: absolute; top: 14px; right: 14px; background: #ff3b30; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 700; line-height: 1.2; padding: 5px 10px; border-radius: 8px; }
.ipc-single__thumbs { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
.ipc-single__thumb { width: 64px; height: 64px; background: #fff; border-radius: 8px; border: 2px solid #f0f0f0; cursor: pointer; overflow: hidden; display: flex; align-items: center; justify-content: center; transition: border-color 0.15s; }
.ipc-single__thumb:hover, .ipc-single__thumb.active { border-color: #111; }
.ipc-single__thumb img { max-width: 56px; max-height: 56px; object-fit: contain; }
.ipc-single__info { display: flex; flex-direction: column; gap: 16px; }
.ipc-single__meta-top { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.ipc-single__mp-tag { background: #f0f0f0; color: #555; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
.ipc-single__fecha { font-size: 12px; color: #aaa; }
.ipc-single__title { font-family: 'DM Sans', sans-serif; font-size: 24px; font-weight: 700; color: #111; line-height: 1.3; margin: 0; }
.ipc-single__rating { display: flex; align-items: center; gap: 8px; }
.ipc-single__stars { color: #f59e0b; font-size: 16px; letter-spacing: 2px; }
.ipc-single__rating-count { font-size: 13px; color: #999; }
.ipc-single__price-wrap { display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap; }
.ipc-single__price { font-family: 'DM Sans', sans-serif; font-size: 36px; font-weight: 800; color: #111; line-height: 1; }
.ipc-single__price-old { font-size: 18px; color: #bbb; text-decoration: line-through; }
.ipc-single__saving { font-size: 13px; font-weight: 600; color: #16a34a; }
.ipc-single__btn { display: block; text-align: center; font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 16px; padding: 16px 24px; border-radius: 12px; text-decoration: none !important; color: #fff !important; background: <?php echo esc_attr($btn_color); ?>; transition: opacity 0.18s; margin-top: 8px; }
.ipc-single__btn:hover { opacity: 0.88; }
.ipc-single__disclaimer { font-size: 11px; color: #bbb; text-align: center; margin-top: -8px; }
.ipc-single__section { margin-top: 40px; padding-top: 32px; border-top: 1px solid #f0f0f0; }
.ipc-single__section h2 { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; color: #111; margin-bottom: 16px; }
.ipc-single__desc-content { font-size: 15px; line-height: 1.7; color: #444; }
.ipc-single__videos { display: flex; flex-direction: column; gap: 16px; }
.ipc-single__video-wrap { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 12px; background: #000; }
.ipc-single__video-wrap iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
.ipc-single__related h2 { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; color: #111; margin-bottom: 20px; }
</style>

<div class="ipc-single" data-post-id="<?php echo $post_id; ?>">

    <div class="ipc-breadcrumb">
        <a href="<?php echo home_url('/ofertas/'); ?>">Ofertas</a><span>›</span>
        <?php if ($categoria): ?>
        <a href="<?php echo get_term_link($categoria); ?>"><?php echo esc_html(ucfirst($categoria->name)); ?></a><span>›</span>
        <?php endif; ?>
        <?php echo esc_html(mb_strlen($titulo) > 60 ? mb_substr($titulo, 0, 60) . '…' : $titulo); ?>
    </div>

    <div class="ipc-single__layout">
        <div class="ipc-single__gallery">
            <div class="ipc-single__img-main">
                <?php if ($badge): ?><span class="ipc-single__badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
                <?php if ($descuento): ?><span class="ipc-single__discount">-<?php echo esc_html($descuento); ?>%</span><?php endif; ?>
                <?php if ($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($titulo); ?>" id="ipc-img-main" loading="eager"><?php endif; ?>
            </div>
            <?php if (!empty($imagenes)): ?>
            <div class="ipc-single__thumbs">
                <?php if ($img): ?>
                <div class="ipc-single__thumb active" onclick="ipcSetImg(this,'<?php echo esc_url($img); ?>')">
                    <img src="<?php echo esc_url($img); ?>" alt="">
                </div>
                <?php endif; ?>
                <?php foreach ($imagenes as $i => $turl): if ($turl === $img) continue; ?>
                <div class="ipc-single__thumb" onclick="ipcSetImg(this,'<?php echo esc_url($turl); ?>')">
                    <img src="<?php echo esc_url($turl); ?>" alt="<?php echo esc_attr($titulo) . ' ' . ($i+2); ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="ipc-single__info" data-post-id="<?php echo $post_id; ?>">
            <div class="ipc-single__meta-top">
                <span class="ipc-single__mp-tag"><?php echo esc_html(ucfirst($marketplace)); ?></span>
                <?php if ($categoria): ?><a href="<?php echo get_term_link($categoria); ?>" style="font-size:12px;color:#999;text-decoration:none"><?php echo esc_html(ucfirst($categoria->name)); ?></a><?php endif; ?>
                <?php if ($fecha): ?><span class="ipc-single__fecha"><?php echo esc_html($fecha); ?></span><?php endif; ?>
            </div>

            <h1 class="ipc-single__title"><?php echo esc_html($titulo); ?></h1>

            <?php if ($rating): ?>
            <div class="ipc-single__rating">
                <span class="ipc-single__stars"><?php echo $stars; ?></span>
                <span style="font-size:14px;font-weight:600"><?php echo $rating; ?>/5</span>
                <?php if ($rating_count): ?><span class="ipc-single__rating-count">(<?php echo esc_html($rating_count); ?> valoraciones)</span><?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="ipc-single__price-wrap">
                <span class="ipc-single__price"><?php echo esc_html($precio); ?>€</span>
                <?php if ($precio_old): ?><span class="ipc-single__price-old"><?php echo esc_html($precio_old); ?>€</span><?php endif; ?>
                <?php if ($precio && $precio_old && floatval($precio_old) > floatval($precio)):
                    $ahorro = number_format(floatval($precio_old) - floatval($precio), 2); ?>
                    <span class="ipc-single__saving">Ahorras <?php echo $ahorro; ?>€</span>
                <?php endif; ?>
            </div>

            <?php if ($url): ?>
            <a href="<?php echo esc_url($url); ?>" class="ipc-single__btn ipc-btn" target="_blank" rel="nofollow noopener sponsored" data-post-id="<?php echo $post_id; ?>"><?php echo $btn_label; ?> →</a>
            <p class="ipc-single__disclaimer">* Enlace de afiliado. El precio puede variar.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($videos)): ?>
    <div class="ipc-single__section">
        <h2>Vídeo del producto</h2>
        <div class="ipc-single__videos" id="ipc-videos-wrap">
        <?php
        $hls_videos = [];
        $mp4_videos = [];
        foreach ($videos as $video_url):
            $video_url = trim($video_url);
            if (!$video_url) continue;
            $embed_url = '';
            $type = '';
            if (strpos($video_url, 'youtube.com/watch') !== false) {
                parse_str(parse_url($video_url, PHP_URL_QUERY), $yt_params);
                if (!empty($yt_params['v'])) $embed_url = 'https://www.youtube.com/embed/' . $yt_params['v'] . '?rel=0';
                $type = 'iframe';
            } elseif (strpos($video_url, 'youtu.be/') !== false) {
                $embed_url = 'https://www.youtube.com/embed/' . basename(parse_url($video_url, PHP_URL_PATH)) . '?rel=0';
                $type = 'iframe';
            } elseif (strpos($video_url, 'vimeo.com/') !== false) {
                $embed_url = 'https://player.vimeo.com/video/' . basename(parse_url($video_url, PHP_URL_PATH));
                $type = 'iframe';
            } elseif (strpos($video_url, '.m3u8') !== false) {
                $type = 'hls';
            } elseif (strpos($video_url, '.mp4') !== false) {
                $type = 'mp4';
            }
            if ($type === 'iframe'): ?>
            <div class="ipc-single__video-wrap">
                <iframe src="<?php echo esc_url($embed_url); ?>" allowfullscreen loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
            <?php elseif ($type === 'mp4'): ?>
            <div class="ipc-single__video-wrap">
                <video controls preload="metadata" style="position:absolute;top:0;left:0;width:100%;height:100%;border-radius:12px;background:#000">
                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                </video>
            </div>
            <?php elseif ($type === 'hls'):
                $hls_videos[] = esc_url($video_url); ?>
            <div class="ipc-single__video-wrap">
                <video id="ipc-hls-<?php echo md5($video_url); ?>" controls preload="metadata" data-hls="<?php echo esc_url($video_url); ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;border-radius:12px;background:#000"></video>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($marca_single || $prod_single): ?>
    <div class="ipc-single__section" style="padding-top:20px;margin-top:20px;border-top:1px solid #f0f0f0">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
            <?php if ($marca_single): ?>
            <span style="font-size:13px;font-weight:600;color:#555;background:#f5f5f5;padding:5px 12px;border-radius:20px">🏷 <?php echo esc_html($marca_single->name); ?></span>
            <?php endif; ?>
            <?php if ($prod_single): ?>
            <span style="font-size:13px;font-weight:600;color:#555;background:#f5f5f5;padding:5px 12px;border-radius:20px">📦 <?php echo esc_html($prod_single->name); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($descripcion): ?>
    <div class="ipc-single__section">
        <h2>Descripción del producto</h2>
        <div class="ipc-single__desc-content"><?php echo wp_kses_post($descripcion); ?></div>
    </div>
    <?php endif; ?>

    <?php if ($categoria): ?>
    <div class="ipc-single__section ipc-single__related">
        <h2>Más ofertas en <?php echo esc_html(ucfirst($categoria->name)); ?></h2>
        <?php echo do_shortcode('[ofertas categoria="' . esc_attr($categoria->slug) . '" limite="4" layout="grid"]'); ?>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/hls.js@1.4.12/dist/hls.min.js"></script>
<script>
function ipcSetImg(thumb, url) {
    document.getElementById('ipc-img-main').src = url;
    document.querySelectorAll('.ipc-single__thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('video[data-hls]').forEach(function(video) {
        var src = video.getAttribute('data-hls');
        if (Hls.isSupported()) {
            var hls = new Hls();
            hls.loadSource(src);
            hls.attachMedia(video);
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = src;
        }
    });
});
</script>

<?php endwhile; ?>
<?php get_footer(); ?>
