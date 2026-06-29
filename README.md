# Loom Builder

Visual drag-and-drop page builder for WordPress with built-in SEO, native custom fields, reusable templates, animations, and optional WooCommerce widgets.

Loom Builder is designed as a self-contained WordPress plugin: the editor runs on WordPress-native `wp.element`, the frontend is rendered server-side from JSON into HTML and scoped CSS, and the runtime uses vanilla JavaScript with no external libraries.

## Highlights

- Full-screen drag-and-drop editor for pages, posts, templates, headers, footers, and reusable sections
- Server-side rendering from JSON to clean HTML with responsive scoped CSS
- No build step required: editor code uses WordPress-native React through `wp.element`
- Built-in SEO module with title/meta output, canonical URLs, robots directives, Open Graph, Twitter Cards, JSON-LD schema, and XML sitemaps
- Native custom field engine inspired by ACF, without requiring the ACF plugin
- Dynamic field binding for builder widgets
- Optional WooCommerce widgets and native faceted product filtering
- Responsive controls for desktop, tablet, and mobile layouts
- Entrance, hover, and loop animations with `prefers-reduced-motion` support
- Import/export for sections and full page layouts
- Extensible widget registry for custom widgets

## Core Builder

Loom stores page layouts as a JSON tree in post meta and renders them on the server. The editor and renderer share the same widget registry, so inspector controls and frontend output stay aligned.

The builder supports nested sections, columns, and widgets, with mouse-based drag reordering, duplication, deletion, undo/redo history, and responsive preview modes.

Included widget groups cover layout, content, media, templates, navigation, posts, and WooCommerce integrations.

## Included Widgets

Loom Builder ships with widgets such as:

- Heading
- Text / HTML content
- Image
- Button
- Spacer
- Divider
- Gallery
- Slider
- Carousel
- Accordion
- Tabs
- Icon Box
- Video
- Shortcode
- Search
- Navigation Menu
- Site Logo
- Site Title
- Social Icons
- Posts
- Reusable Template
- WooCommerce Products
- WooCommerce Add to Cart
- WooCommerce Product Filter

## Reusable Templates

The `loom_template` custom post type can be used for:

- Headers
- Footers
- Reusable blocks

Templates can be assigned through display conditions, including the entire site, front page, post type, specific post, post type archive, search results, and 404 pages.

Reusable blocks can also be inserted with the Template widget or the `[loom_template id="123"]` shortcode.

## Native Custom Fields

Loom includes a custom field engine with no dependency on ACF. Field groups can be attached to posts using location rules and edited through a visual admin interface.

Supported field types include:

- Text
- Textarea
- Number
- Select
- True / False
- Color
- Image
- Gallery
- Link
- Repeater

Field values can be read in PHP templates with:

```php
$subtitle = loom_field( 'subtitle' );
$hero     = loom_field( 'hero_image', $post_id );
```

Text and image widgets can also bind to custom fields dynamically and resolve those values at render time.

## SEO Module

Loom Builder includes a built-in SEO module so a site does not need a separate SEO plugin for common metadata and structured data needs.

Features include:

- Global SEO settings under the Loom admin menu
- Per-post SEO box for title, description, canonical URL, share image, and noindex
- `<title>` and meta description output
- Canonical URLs
- Robots meta directives
- Open Graph tags
- Twitter Card tags
- JSON-LD graph output for Organization / Person, WebSite, BreadcrumbList, Article, and WooCommerce Product
- Native XML sitemap index and per-post-type sitemaps
- Sitemap and custom rules support in `robots.txt`

Sitemap URLs:

```text
/sitemap.xml
/sitemap-page.xml
/sitemap-post.xml
/sitemap-product.xml
```

## WooCommerce Support

WooCommerce integration is loaded only when WooCommerce is active. Without WooCommerce, Loom Builder continues to work as a standard page builder.

WooCommerce features include:

- Products grid widget with category, count, columns, sorting, featured, and on-sale options
- Add to Cart widget for the current product or a selected product ID
- Product Filter widget for categories, attributes, and price ranges
- Native faceted filtering through WordPress queries
- Product JSON-LD schema output in the SEO module

## Animations

Loom supports CSS-based animations without external animation libraries.

Available animation types include:

- Entrance animations: fade, slide, zoom, rotate, blur, bounce, flip-x, flip-y
- Loop animations: pulse, float, bounce, spin, shake, swing
- Hover animations: grow, shrink, lift, rotate, float, pulse, shadow, bright

Scroll-triggered entrance animations use `IntersectionObserver` and respect the user's reduced-motion preference.

## Architecture

```text
loom-builder.php          Plugin bootstrap, constants, autoloader
inc/
  class-loom-plugin.php   Main plugin orchestrator
  admin-menu.php          Loom admin menu and editor host
  post-types.php          Custom post types and builder meta
  settings.php            Global plugin settings
  hardening.php           Security and hardening helpers
  builder/
    registry.php          Widget registry and editor schema
    render.php            JSON-to-HTML renderer
    rest.php              REST API for layouts and previews
    sanitize.php          Layout sanitization
    templates.php         Reusable template rendering
    templates-io.php      Template import/export
    assets.php            Editor and frontend asset loading
    widgets/              Core widget render callbacks
  css/
    generator.php         Responsive scoped CSS generator
  acf/
    fields.php            Native field types
    api.php               Public field API
    group.php             Field group admin logic
    meta-box.php          Field value meta boxes
  seo/
    settings.php          SEO settings
    meta.php              Head meta output
    schema.php            JSON-LD output
    sitemap.php           XML sitemap routes
  woocommerce/
    woocommerce.php       WooCommerce integration loader
    widgets.php           WooCommerce widgets
    filters.php           Product filtering
assets/
  css/
    editor.css            Editor UI styles
    frontend.css          Frontend base styles
    acf-admin.css         Field admin styles
  js/
    frontend.js           Vanilla JS frontend runtime
    acf-admin.js          Field admin interactions
    acf-fields.js         Field builder interactions
    templates-admin.js    Template import/export UI
    editor/               Modular editor application
languages/                Translation files
```

## Installation

1. Copy the `loom-builder` folder into `wp-content/plugins/`.
2. Activate Loom Builder in the WordPress admin.
3. Open a page or post and click **Edit with Loom**.
4. Optional: use a clean full-width theme such as Loom Canvas for a blank page-building surface.

No npm install, bundling, or build step is required.

## Extending Loom

Custom widgets can be registered through the `loom_register_widgets` action:

```php
add_action( 'loom_register_widgets', function ( $registry ) {
    $registry->register( array(
        'id'       => 'my_widget',
        'title'    => 'My Widget',
        'icon'     => 'star-filled',
        'category' => 'basic',
        'controls' => array(
            'text' => array(
                'type'    => 'text',
                'label'   => 'Text',
                'default' => '',
                'section' => 'content',
            ),
        ),
        'render'   => function ( $settings ) {
            return '<div>' . esc_html( $settings['text'] ) . '</div>';
        },
    ) );
} );
```

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- WooCommerce is optional

## License

GPL-2.0-or-later
