=== Loom Builder ===
Contributors: loom
Tags: page builder, builder, landing page, seo, woocommerce
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Visual drag and drop page builder with a built-in SEO module. No third-party dependencies.

== Description ==

Loom Builder — визуальный конструктор страниц для WordPress в духе Elementor со
встроенным SEO-модулем. Создавайте лендинги и полноценные сайты без кода. Всё
работает средствами WordPress и самого плагина, без сторонних плагинов.

Редактор написан на нативном WordPress React (wp.element) и не требует сборки.
Публичная часть рендерится на PHP из JSON — быстро и дружелюбно к SEO.

Дорожная карта:
* Фаза 1 (готово): ядро билдера, базовые виджеты, адаптив, анимации.
* Фаза 2 (готово): слайдер, карусель, галерея, лайтбокс, репитер в инспекторе.
* Фаза 3 (готово): расширенные анимации — появление, loop, hover, easing.
* Фаза 4 (готово): нативные ACF-поля, группы полей, динамическая привязка.
* Фаза 5 (готово): WooCommerce — виджеты товаров/корзины/фильтра, фильтры.
* Фаза 6 (готово): SEO-модуль (title/meta/OG, микроразметка, robots, sitemap).

== Installation ==

1. Скопируйте папку loom-builder в wp-content/plugins/ и активируйте.
2. (Опционально) активируйте тему Loom Canvas для чистого full-width контейнера.
3. Откройте страницу/запись и нажмите «Edit with Loom».

== Frequently Asked Questions ==

= Нужен ли Elementor или другой билдер, чтобы это работало? =

Нет. Loom Builder — самостоятельный визуальный редактор, ничего дополнительно
устанавливать не нужно.

= Заменяет ли это ACF? =

Да. У Loom Builder свой нативный движок полей (группы, правила расположения,
10 типов полей включая repeater) — зависимости от Advanced Custom Fields нет.

= Заменяет ли встроенный SEO-модуль отдельный SEO-плагин? =

Да: title-шаблоны, meta description, canonical, Open Graph, JSON-LD граф и
XML sitemap работают из коробки. Если у вас уже установлен другой SEO-плагин,
проверьте вывод head — рекомендуется активировать только один SEO-модуль сразу,
чтобы избежать дублирующихся тегов и sitemap.

= Работает ли редактор с блочными (FSE) темами вроде Twenty Twenty-Five? =

Публичный рендер Loom заменяет содержимое записи через стандартный хук
the_content внутри классического цикла WordPress (in_the_loop() +
is_main_query()). Некоторые блочные темы рендерят блок Post Content вне этого
цикла, из-за чего макет Loom может не отобразиться. Решение — включить
прилагаемую тему Loom Canvas (чистый full-width контейнер с классическим
циклом) или любую другую классическую тему для страниц, собранных в Loom.

= Работает ли это с WooCommerce? =

Да, при активном WooCommerce автоматически появляются виджеты сетки товаров,
кнопки «В корзину» и фасетного фильтра (категории, атрибуты, диапазон цены).

= Что произойдёт с моими страницами, если я деактивирую плагин? =

Ничего не потеряется: макет хранится как JSON в мета-полях записи. Пока плагин
неактивен, страница покажет обычный контент темы; после повторной активации
макет Loom вернётся как был.

= Есть ли платная версия? =

Да, отдельный аддон Loom Builder Pro добавляет Design System, глобальные
секции, визуальный Query Loop без сырого SQL, ревизии макета и SEO-аудит
всего сайта. Устанавливается отдельным плагином поверх бесплатного ядра.

== Screenshots ==

1. Редактор Loom: панель виджетов, канвас с собранной страницей и инспектор.
2. Стартовая страница из аддона Loom Builder Pro — hero, доверие и блок фич без единой картинки.
3. Настройки SEO-модуля: типы JSON-LD схем и XML sitemap с прямой ссылкой.

== Changelog ==

= 1.7.0 =
* Editor UX: drag-and-drop reordering of sections, columns and widgets (with a
  drag handle) including moving widgets between columns; undo/redo with history
  and Ctrl/Cmd+Z / Ctrl+Shift+Z (Ctrl+Y) shortcuts; export a section or the whole
  page to JSON and import sections from JSON (ids regenerated to avoid clashes).

= 1.6.0 =
* Reusable templates: loom_template can be a Header, Footer or Block, shown by
  display conditions (entire site / front page / post type / specific post /
  post type archive / 404 / search). Headers/footers render in theme positions
  via loom_header() / loom_footer() (with wp_body_open / wp_footer fallback).
  Blocks insert via the Template widget or the [loom_template id="…"] shortcode.
  Self-recursion guarded.

= 1.5.0 =
* Phase 6: SEO module (no third-party SEO plugin). Per-post SEO box (title,
  description, canonical, share image, noindex). Head output: title templates,
  meta description, canonical, robots, Open Graph and Twitter Card tags. JSON-LD
  graph: Organization/Person, WebSite (+ SearchAction), BreadcrumbList, Article,
  WooCommerce Product (offers + aggregateRating). Native XML sitemap index +
  per-post-type sitemaps at /sitemap.xml, robots.txt Sitemap line + custom rules.
  Settings page under the Loom menu.

= 1.4.0 =
* Phase 5: WooCommerce integration (guarded by class_exists). Widgets: Products
  grid (category/order/columns/featured/on-sale, respects active filters),
  Add to Cart, Product Filter (categories, attributes, price range). Native
  faceted filters via pre_get_posts on filter_cat / filter_tag / filter_pa_* /
  min_price / max_price (no third-party filter plugin). WooCommerce widget
  category in the editor panel.

= 1.3.0 =
* Phase 4: Native field engine (no ACF dependency). Field types: text, textarea,
  number, select, true/false, color, image, gallery, link, repeater. Field groups
  (loom_field_group CPT) with a visual builder and location rules (post type /
  page template / specific post). Value meta box on matching posts. Public API
  loom_field(). Dynamic binding: builder text and image widgets can pull values
  from custom fields at render time.

= 1.2.0 =
* Phase 3: Advanced animations. Entrance presets (fade/slide/zoom/rotate/blur/
  bounce/flip-x/flip-y) with per-element duration, delay and easing (including
  overshoot/spring curves); continuous loop animations (pulse/float/bounce/spin/
  shake/swing); hover animations (grow/shrink/lift/rotate/float/pulse/shadow/
  bright). Scroll-triggered via IntersectionObserver, honors prefers-reduced-motion.

= 1.1.0 =
* Phase 2: Slider widget (slide/fade/cards, autoplay, arrows, dots, swipe),
  Carousel widget (responsive items, loop, autoplay), Gallery widget
  (grid/masonry, responsive columns) with a native lightbox. New Inspector
  controls: repeater, multi-image gallery picker, single image object.
  All interactions are vanilla JS with no external libraries.

= 1.0.0 =
* Phase 1: core builder, REST layout API, server renderer, responsive scoped CSS,
  widgets (heading, text, image, button, spacer), entrance animations,
  companion Loom Canvas theme.
