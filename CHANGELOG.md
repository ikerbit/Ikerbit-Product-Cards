# Ikerbit Product Cards — CHANGELOG

## Estructura del plugin
```
/wp-content/plugins/ikerbit-product-cards/
    ikerbit-product-cards.php   ← Plugin principal
    ipc-edit.php                ← Página de edición de ofertas en admin
    ipc-styles.css              ← CSS compartido (cards, grids, layouts)
    AGENTS.md                   ← Instrucciones para agentes AI
    CHANGELOG.md                ← Este archivo
    templates/
        single-ipc_oferta.php       ← Página individual de oferta
        taxonomy-ipc_categoria.php  ← Listado de ofertas por categoría
        archive-ipc_oferta.php      ← Página general /ofertas/
    docs/
        ARCHITECTURE.md             ← Arquitectura del plugin
        DEVELOPMENT.md              ← Guía de desarrollo
        DECISIONS.md                ← Decisiones de arquitectura
        ROADMAP.md                  ← Hoja de ruta
        BACKLOG.md                  ← Deuda técnica y mejoras
```

---

---

---

---

---

---

---

---

## v2.7.1 — Buscador, duplicados, markdown y mejoras
- Buscador desplegable en "Todas las Ofertas" por título, código, marketplace, país, categoría, marca y producto
- Página de detección y limpieza de duplicados por URL (individual y masiva)
- Corregido: deduplicación en `ipc_crear_oferta` ahora funciona también sin country
- Soporte markdown en `custom_description` usando Parsedown (incluido en el plugin)
- `custom_description` visible en cards + página individual
- Columna Código y botón Editar en Top 10 de Estadísticas
- Cambio de tipografía en títulos de sección (Syne → DM Sans) para consistencia
- Paginación corregida: parámetro `orden` en vez de `orderby` para evitar colisión con WP_Query

## v2.7.0 — Upsert por código de producto
- Nuevo campo `ipc_product_code` — identificador único del producto en su marketplace (ASIN, etc.)
- Upsert automático: si n8n envía `product_code` + `marketplace` + `country` y ya existe, actualiza en vez de duplicar
- Precios, títulos y demás campos se actualizan; visitas y clicks se conservan
- Nuevo atributo `product_code` en shortcode `[ofertas]`
- Campo visible en el editor de ofertas (Información principal)
- `product_code` como primer campo en el Body JSON de ejemplo en Ajustes

## v2.6.2 — Tracking de país del visitante
- Nuevo endpoint `POST /ipc/v1/visitor/{country}` para tracking global de visitantes por país
- Nuevos meta fields `ipc_visitas_paises` y `ipc_clicks_paises` (JSON) — desglose por país del visitante
- El JS de tracking envía el país del visitante (desde la cookie) con cada visita y click
- Nueva sección "Visitantes por país" en el dashboard de estadísticas
- Países sin ofertas marcados en amarillo — para identificar mercados sin cobertura
- Throttle de 1 hora para el ping global (evita duplicados por refresco)

## v2.6.1 — Saneamiento de precios y país en estadísticas
- `ipc_sanitize_price()`: normaliza precios eliminando símbolos (€, $) y unificando comas a puntos al recibir desde n8n
- Columna "País" en "Todas las Ofertas" con ordenación
- CTR por país en el dashboard de estadísticas
- Columna país en Top 10 y ofertas sin clicks
- Símbolo de moneda dinámico en la columna Precio del listado de ofertas

## v2.6.0 — Internacionalización: ofertas por país
- Campos nuevos `ipc_country`, `ipc_language`, `ipc_currency`, `ipc_custom_description`
- Detección de país del visitante vía `navigator.language` + `Accept-Language` + cookie (sin dependencias externas)
- Nuevo atributo `country` en shortcode `[ofertas]` — filtra ofertas por código de país (ES, MX, DE...)
- `country="auto"` detecta automáticamente el país del visitante y filtra
- `country="GLOBAL"` para ofertas válidas en todos los países (AliExpress)
- Moneda dinámica: el símbolo de moneda (€, $, MX$...) se muestra según `ipc_currency`
- Campo `ipc_custom_description`: si está relleno, sustituye a `ipc_descripcion` en cards
- Nuevas opciones en Ajustes: país por defecto y toggle de filtro automático
- JS de cookie de país en todas las páginas públicas (priority 1 en `wp_footer`)
- Editor: nueva sección "Internacionalización" con campos país, idioma y moneda
- Editor: nueva sección "Descripción personalizada"
- Shortcodes de ejemplo y JSON body actualizados en Ajustes

## v2.5.2 — Prueba de update checker
- Bump de versión para validar el funcionamiento del Plugin Update Checker

## v2.5.1 — Plugin Update Checker
- Integración de Plugin Update Checker (YahnisElsts) para actualizaciones automáticas desde GitHub releases
- Actualizaciones desde WordPress admin cuando se publiquen nuevas releases en GitHub

## v2.5.0 — Taxonomías, GA4, estadísticas y documentación
- Taxonomías `ipc_marca` y `ipc_producto` para clasificar ofertas por marca y producto
- Soporte de wildcard (`*`) en shortcodes para filtrar por prefijo de marca o producto
- Integración GA4: toggle y Measurement ID en ajustes para enviar eventos `affiliate_visit` y `affiliate_click`
- Endpoint REST `DELETE /ipc/v1/oferta/{id}` para eliminar ofertas
- Dashboard de estadísticas con KPIs (visitas totales, clicks totales, CTR global)
- Top 10 ofertas por clicks, CTR por categoría, CTR por marketplace
- Detección de ofertas con visitas pero sin clicks (candidatas a optimizar)
- Campo `ipc_ultimo_click` en editor
- Documentación completa del proyecto (AGENTS.md + docs/)

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
