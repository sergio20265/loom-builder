<?php
/**
 * Icon box widget: a Dashicon with a heading and text. Dashicons are enqueued
 * on the front end by the builder asset loader.
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
				'id'       => 'icon_box',
				'title'    => __( 'Icon Box', 'loom' ),
				'icon'     => 'star-filled',
				'category' => 'basic',
				'controls' => array(
					'icon'      => array(
						'type'    => 'text',
						'label'   => __( 'Dashicon name', 'loom' ),
						'default' => 'star-filled',
						'section' => 'content',
					),
					'title'     => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'loom' ),
						'default' => __( 'Icon box title', 'loom' ),
						'section' => 'content',
					),
					'text'      => array(
						'type'    => 'textarea',
						'label'   => __( 'Text', 'loom' ),
						'default' => __( 'Describe this feature in a sentence or two.', 'loom' ),
						'section' => 'content',
					),
					'link'      => array(
						'type'    => 'url',
						'label'   => __( 'Link', 'loom' ),
						'default' => '',
						'section' => 'content',
					),
					'iconSize'  => array( 'type' => 'range', 'label' => __( 'Icon size (px)', 'loom' ), 'default' => 40, 'min' => 16, 'max' => 120, 'section' => 'content' ),
					'iconColor' => array( 'type' => 'color', 'label' => __( 'Icon color', 'loom' ), 'default' => '#2563eb', 'section' => 'style' ),
					'align'     => array(
						'type'    => 'select',
						'label'   => __( 'Alignment', 'loom' ),
						'default' => 'center',
						'options' => array(
							'left'   => __( 'Left', 'loom' ),
							'center' => __( 'Center', 'loom' ),
							'right'  => __( 'Right', 'loom' ),
						),
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_icon_box',
			)
		);
	}
);

/**
 * Render the icon box widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_icon_box( $s ) {
	$icon  = sanitize_html_class( isset( $s['icon'] ) ? $s['icon'] : 'star-filled' );
	$icon  = $icon ? $icon : 'star-filled';
	$size  = max( 16, (int) $s['iconSize'] );
	$color = loom_css_color( isset( $s['iconColor'] ) ? $s['iconColor'] : '' );
	$color = $color ? $color : '#2563eb';
	$align = in_array( isset( $s['align'] ) ? $s['align'] : '', array( 'left', 'center', 'right' ), true ) ? $s['align'] : 'center';

	$icon_html  = '<span class="loom-icon-box-icon dashicons dashicons-' . esc_attr( $icon ) . '" style="font-size:' . $size . 'px;width:' . $size . 'px;height:' . $size . 'px;color:' . esc_attr( $color ) . '"></span>';
	$title_html = ! empty( $s['title'] ) ? '<h3 class="loom-icon-box-title">' . esc_html( $s['title'] ) . '</h3>' : '';
	$text_html  = ! empty( $s['text'] ) ? '<p class="loom-icon-box-text">' . esc_html( $s['text'] ) . '</p>' : '';

	$inner = $icon_html . $title_html . $text_html;
	if ( ! empty( $s['link'] ) ) {
		$inner = '<a class="loom-icon-box-link" href="' . esc_url( $s['link'] ) . '">' . $inner . '</a>';
	}

	return '<div class="loom-icon-box loom-icon-box-' . esc_attr( $align ) . '">' . $inner . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
