<?php
/**
 * Server-side renderer: turns the layout JSON tree into final HTML.
 *
 * This is the public output of the builder. The editor never ships HTML to the
 * frontend; it only stores JSON, and this renderer is the single place that
 * produces markup, keeping output SEO-friendly and cache-able.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve dynamic field bindings on a widget's settings.
 *
 * settings._dynamic is a map of { settingKey: fieldName }. When the bound field
 * has a value for the current post, it overrides the static setting. Requires
 * the ACF API; falls back to the static value when unavailable (e.g. previews).
 *
 * @param array $settings Resolved widget settings.
 * @return array
 */
function loom_resolve_dynamic( $settings ) {
	if ( empty( $settings['_dynamic'] ) || ! is_array( $settings['_dynamic'] ) || ! function_exists( 'loom_field' ) ) {
		return $settings;
	}
	foreach ( $settings['_dynamic'] as $key => $field ) {
		if ( ! $field ) {
			continue;
		}
		$value = loom_field( $field );
		if ( null !== $value && '' !== $value ) {
			$settings[ $key ] = $value;
		}
	}
	return $settings;
}

/**
 * Render a whole layout tree to HTML.
 *
 * @param array $tree Layout nodes.
 * @return string
 */
function loom_render_tree( array $tree ) {
	$html = '';
	foreach ( $tree as $node ) {
		$html .= loom_render_node( $node );
	}
	return $html;
}

/**
 * Render a single node (section, column or widget).
 *
 * @param array $node Node data.
 * @return string
 */
function loom_render_node( $node ) {
	if ( empty( $node['type'] ) || empty( $node['id'] ) ) {
		return '';
	}

	$type     = $node['type'];
	$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
	$advanced = isset( $settings['_advanced'] ) ? $settings['_advanced'] : array();

	$classes = array( 'loom-node', 'loom-node-' . sanitize_html_class( $node['id'] ), 'loom-' . sanitize_html_class( $type ) );

	// Animation hooks: entrance (scroll-triggered), loop (continuous) and hover.
	$anim_attr = '';
	if ( ! empty( $advanced['animation'] ) ) {
		$classes[]  = 'loom-anim';
		$anim_attr .= ' data-loom-anim="' . esc_attr( sanitize_html_class( $advanced['animation'] ) ) . '"';
		if ( ! empty( $advanced['animationDelay'] ) ) {
			$anim_attr .= ' data-loom-anim-delay="' . esc_attr( (int) $advanced['animationDelay'] ) . '"';
		}
		if ( ! empty( $advanced['animationDuration'] ) ) {
			$anim_attr .= ' data-loom-anim-duration="' . esc_attr( (int) $advanced['animationDuration'] ) . '"';
		}
		if ( ! empty( $advanced['animationEasing'] ) ) {
			$anim_attr .= ' data-loom-anim-ease="' . esc_attr( sanitize_html_class( $advanced['animationEasing'] ) ) . '"';
		}
	}
	if ( ! empty( $advanced['loopAnimation'] ) ) {
		$classes[] = 'loom-loop-' . sanitize_html_class( $advanced['loopAnimation'] );
	}
	if ( ! empty( $advanced['hoverAnimation'] ) ) {
		$classes[] = 'loom-hover-' . sanitize_html_class( $advanced['hoverAnimation'] );
	}

	// Per-device visibility.
	foreach ( array( 'Desktop', 'Tablet', 'Mobile' ) as $device ) {
		if ( ! empty( $advanced[ 'hide' . $device ] ) ) {
			$classes[] = 'loom-hide-' . strtolower( $device );
		}
	}

	// Custom classes from the user.
	if ( ! empty( $advanced['cssClass'] ) ) {
		foreach ( preg_split( '/\s+/', $advanced['cssClass'] ) as $cls ) {
			$cls = sanitize_html_class( $cls );
			if ( $cls ) {
				$classes[] = $cls;
			}
		}
	}

	$id_attr = '';
	if ( ! empty( $advanced['cssId'] ) ) {
		$id_attr = ' id="' . esc_attr( sanitize_html_class( $advanced['cssId'] ) ) . '"';
	}

	$class_attr = ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
	$children   = isset( $node['children'] ) && is_array( $node['children'] ) ? $node['children'] : array();

	switch ( $type ) {
		case 'section':
			$inner = '<div class="loom-section-inner">' . loom_render_tree( $children ) . '</div>';
			/**
			 * Filters a rendered section's inner markup for Loom add-ons.
			 *
			 * @param string $inner Rendered inner markup.
			 * @param array  $node  Section node data.
			 */
			$inner = (string) apply_filters( 'loom_section_inner', $inner, $node );
			return '<section' . $id_attr . $class_attr . $anim_attr . '>' . $inner . '</section>';

		case 'column':
			return '<div' . $id_attr . $class_attr . $anim_attr . '>' . loom_render_tree( $children ) . '</div>';

		case 'widget':
			$widget_id = isset( $node['widget'] ) ? $node['widget'] : '';
			$registry  = \Loom\Builder\Registry::instance();
			if ( ! $registry->has( $widget_id ) ) {
				return '';
			}
			$resolved = wp_parse_args( $settings, $registry->defaults( $widget_id ) );
			$resolved = loom_resolve_dynamic( $resolved );
			$inner    = $registry->render( $widget_id, $resolved, $node );
			$classes[] = 'loom-widget';
			$classes[] = 'loom-widget-' . sanitize_html_class( $widget_id );
			$class_attr = ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
			return '<div' . $id_attr . $class_attr . $anim_attr . '>' . $inner . '</div>';
	}

	return '';
}

/**
 * Render the layout stored on a given post.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function loom_render_post( $post_id ) {
	$tree = loom_get_layout( $post_id );
	if ( empty( $tree ) ) {
		return '';
	}
	$css  = loom_generate_css( $tree );
	// "alignfull" opts this wrapper out of block themes' own content-width cap
	// (theme.json layout constraints target `.is-layout-constrained > *` and
	// explicitly exclude .alignfull) — otherwise every Loom section, however
	// its own width is configured, is squeezed inside the theme's ~640-840px
	// content column and full-bleed sections/sliders are simply impossible.
	$html = '<div class="loom-doc alignfull loom-doc-' . (int) $post_id . '">' . loom_render_tree( $tree ) . '</div>';

	if ( $css ) {
		// Inline the scoped CSS so a cached page is self-contained.
		$html = '<style id="loom-css-' . (int) $post_id . '">' . $css . '</style>' . $html;
	}
	return $html;
}

/**
 * Replace the_content with the rendered builder layout on enabled entries.
 *
 * @param string $content Original post content.
 * @return string
 */
function loom_filter_the_content( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$post_id = get_the_ID();
	if ( ! $post_id || ! loom_is_enabled( $post_id ) ) {
		return $content;
	}
	$rendered = loom_render_post( $post_id );
	return $rendered ? $rendered : $content;
}
add_filter( 'the_content', 'loom_filter_the_content', 9 );
