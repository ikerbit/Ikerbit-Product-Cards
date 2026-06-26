# Ikerbit Product Cards — CHANGELOG

## Estructura del plugin
```
/wp-content/plugins/ikerbit-product-cards/
    ikerbit-product-cards.php   ← Plugin principal
    ipc-edit.php                ← Página de edición de ofertas en admin
    ipc-styles.css              ← CSS compartido (cards, grids, layouts)
    CHANGELOG.md                ← Este archivo
    templates/
        single-ipc_oferta.php       ← Página individual de oferta
        taxonomy-ipc_categoria.php  ← Listado de ofertas por categoría
        archive-ipc_oferta.php      ← Página general /ofertas/
```

---

## v2.3 — Edición, widget y contadores
- `ipc-edit.php` — nueva página de edición de todos los campos de una oferta desde el panel WordPress (título, precio, descuento, marketplace, categoría, imágenes con previsualización, vídeos, descripción, estadísticas)
- Widget rediseñado — imagen grande, precio y descuento prominentes, botón compacto, "Ver más detalles"
- Título del widget cambiado a H2
- Contador de visitas — se registra automáticamente al cargar una página de oferta individual
- Contador de clicks — se registra con `mousedown` al pulsar botón de compra en cards, single y widget (compatible con botón derecho y Ctrl+Click)
- Campo `ipc_ultimo_click` — guarda fecha y hora del último click
- Página "Estadísticas" en el panel admin con CTR por oferta
- Columnas Visitas y Clicks en "Todas las ofertas" con ordenación clicable
- `data-post-id` añadido a cards, single y widget para tracking JS

## v2.2 — Contadores y widget inicial
- Endpoints REST `/ipc/v1/visita/{id}` y `/ipc/v1/click/{id}`
- Meta fields `ipc_visitas`, `ipc_clicks`, `ipc_ultimo_click`
- Widget "IPC — Ofertas destacadas" con configuración de colores, categoría, marketplace y ordenación
- Submenú Estadísticas en panel admin
- Shortcodes `orderby="clicks"` y `orderby="visitas"`

## v2.1 — Toggle página de inicio
- Toggle en ajustes para usar `/ofertas/` como portada del sitio
- El plugin crea automáticamente la página WordPress y la asigna como portada
- Advertencia explicativa en los ajustes

## v2.0 — Arquitectura modular
- CSS extraído a `ipc-styles.css` cargado via `wp_enqueue_style()`
- Plantillas movidas a `templates/` — independientes del tema activo
- `template_include` para `single`, `taxonomy` y `archive`
- `archive-ipc_oferta.php` — página `/ofertas/` con pills de categorías y grid de últimas ofertas
- `taxonomy-ipc_categoria.php` — filtros de ordenación, paginación, breadcrumb desde "Ofertas"
- Rewrite slug taxonomía cambiado a `/ofertas/`
- `has_archive => 'ofertas'` activado en CPT

## v1.5
- Cards clicables — overlay invisible enlaza a página de oferta
- Título como `<h3>` con enlace para SEO long-tail
- Fondo blanco en zona de imagen de cards
- Campo `ipc_video` — soporte YouTube, Vimeo, `.mp4` y `.m3u8` (HLS.js)
- Endpoint devuelve `url` del permalink al crear/actualizar
- `rel="sponsored nofollow noopener"` en botones de compra
- Botón "Ver más detalles →" en cards

## v1.3 — v1.4
- Campo `ipc_fecha` añadido
- Campo `ipc_descripcion` añadido
- Campo `ipc_imagenes` — array JSON de URLs de imágenes adicionales
- `single-ipc_oferta.php` — galería con thumbnails clicables, Schema.org Product, breadcrumb, ofertas relacionadas
- Shortcode `condescuento="si"` para filtrar solo ofertas con descuento
- Shortcode `orderby="descuento"` para ordenar por mayor descuento
- Título limitado a 79 caracteres en cards
- Página "Todas las ofertas" con ordenación clicable por título, precio y fecha
- Botón Eliminar en listado

## v1.2
- Menú propio en sidebar WordPress con icono
- Submenús: Ajustes y Todas las Ofertas
- Página de listado con imagen, precio, descuento, marketplace, categoría, shortcode

## v1.0 — v1.1
- Custom Post Type `ipc_oferta`
- Taxonomía `ipc_categoria`
- Meta fields: titulo, precio, precio_old, descuento, url, img, marketplace, rating, rating_count, stock, badge
- Endpoint REST `POST /ipc/v1/oferta` — crear oferta
- Endpoint REST `POST /ipc/v1/oferta/{id}` — actualizar oferta
- Endpoint REST `DELETE /ipc/v1/oferta/{id}` — eliminar oferta
- Autenticación via header `X-IPC-Secret`
- Shortcodes: `[oferta id=""]`, `[ofertas categoria="" marketplace="" limite="" layout="" orderby="" condescuento=""]`
- CSS con fuentes DM Sans + Syne, cards responsivas, layouts grid y horizontal
- Página de ajustes con Secret Key y Body JSON de ejemplo

---

## Pendiente — v2.4
- Detección de país por IP en visitas y clicks
- Guardar `ipc_visitas_paises` y `ipc_clicks_paises` como JSON `{"ES":45,"MX":12}`
- Estadísticas avanzadas: CTR por categoría, CTR por marketplace, relación descuento/CTR, visitas sin clicks
- Evolución temporal de clicks (timestamps individuales por visita y click)
- Estadísticas por rango de fechas seleccionable — último día, última semana, mes, trimestre, año — para analizar progreso y tendencias
- Totales agregados del sitio: visitas totales, clicks totales, CTR global, evolución respecto al período anterior
- Preparación para futura implementación de ofertas por país y moneda

## Pendiente — futuro
- Widgets de estadísticas en panel admin
- Campo `ipc_links` — array de enlaces de afiliado a múltiples marketplaces
- Detección automática de país para mostrar ofertas filtradas por país
- Soporte multi-moneda
- Importación masiva CSV desde n8n
- Auto-inserción de shortcode de ofertas en posts de la misma categoría (toggle en ajustes)
