<?php
/**
 * Icon list widget: a repeatable list of Dashicon + text rows.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'loom_register_widgets',
	static function ( $registry ) {
		$registry->register(
			array(
				'id'       => 'icon_list',
				'title'    => __( 'Icon List', 'loom-builder' ),
				'icon'     => 'menu-alt',
				'category' => 'basic',
				'controls' => array(
					'items'     => array(
						'type'       => 'repeater',
						'label'      => __( 'Items', 'loom-builder' ),
						'section'    => 'content',
						'titleField' => 'text',
						'fields'     => array(
							'icon' => array( 'type' => 'text', 'label' => __( 'Dashicon name', 'loom-builder' ), 'default' => 'yes' ),
							'text' => array( 'type' => 'text', 'label' => __( 'Text', 'loom-builder' ), 'default' => __( 'List item', 'loom-builder' ) ),
							'link' => array( 'type' => 'url', 'label' => __( 'Link', 'loom-builder' ), 'default' => '' ),
						),
						'default'    => array(
							array(
								'icon' => 'yes',
								'text' => __( 'List item', 'loom-builder' ),
								'link' => '',
							),
						),
					),
					'layout'    => array(
						'type'    => 'select',
						'label'   => __( 'Layout', 'loom-builder' ),
						'default' => 'vertical',
						'options' => array(
							'vertical'   => __( 'Vertical', 'loom-builder' ),
							'horizontal' => __( 'Horizontal', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'gap'       => array( 'type' => 'range', 'label' => __( 'Gap (px)', 'loom-builder' ), 'default' => 10, 'min' => 0, 'max' => 40, 'section' => 'content' ),
					'iconSize'  => array( 'type' => 'range', 'label' => __( 'Icon size (px)', 'loom-builder' ), 'default' => 18, 'min' => 12, 'max' => 40, 'section' => 'content' ),
					'iconColor' => array( 'type' => 'color', 'label' => __( 'Icon color', 'loom-builder' ), 'default' => '#2563eb', 'section' => 'style' ),
				),
				'render'   => 'loom_render_icon_list',
			)
		);
	}
);

/**
 * Render the icon list widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_icon_list( $s ) {
	$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
	if ( empty( $items ) ) {
		return '<div class="loom-icon-list-empty">' . esc_html__( 'Add list items.', 'loom-builder' ) . '</div>';
	}

	$layout = ( isset( $s['layout'] ) && 'horizontal' === $s['layout'] ) ? 'horizontal' : 'vertical';
	$gap    = (int) $s['gap'];
	$size   = max( 12, (int) $s['iconSize'] );
	$color  = loom_css_color( isset( $s['iconColor'] ) ? $s['iconColor'] : '' );
	$color  = $color ? $color : '#2563eb';

	$style = 'gap:' . $gap . 'px;--loom-icon-list-color:' . $color . ';--loom-icon-list-size:' . $size . 'px';
	$out   = '<ul class="loom-icon-list loom-icon-list-' . esc_attr( $layout ) . '" style="' . esc_attr( $style ) . '">';

	foreach ( $items as $item ) {
		$icon = sanitize_html_class( isset( $item['icon'] ) ? $item['icon'] : 'yes' );
		$icon = $icon ? $icon : 'yes';
		$text = isset( $item['text'] ) ? esc_html( $item['text'] ) : '';

		$inner = '<span class="loom-icon-list-icon dashicons dashicons-' . esc_attr( $icon ) . '"></span><span class="loom-icon-list-text">' . $text . '</span>';
		if ( ! empty( $item['link'] ) ) {
			$inner = '<a class="loom-icon-list-link" href="' . esc_url( $item['link'] ) . '">' . $inner . '</a>';
		}
		$out .= '<li class="loom-icon-list-item">' . $inner . '</li>';
	}

	$out .= '</ul>';
	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
