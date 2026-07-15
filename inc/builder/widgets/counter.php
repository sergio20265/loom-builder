<?php
/**
 * Counter widget: a number that counts up when scrolled into view.
 * Animated by the "counter" frontend module (assets/js/frontend.js).
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
				'id'       => 'counter',
				'title'    => __( 'Counter', 'loom-builder' ),
				'icon'     => 'chart-bar',
				'category' => 'basic',
				'controls' => array(
					'number'   => array( 'type' => 'number', 'label' => __( 'Number', 'loom-builder' ), 'default' => 100, 'section' => 'content' ),
					'prefix'   => array( 'type' => 'text', 'label' => __( 'Prefix', 'loom-builder' ), 'default' => '', 'section' => 'content' ),
					'suffix'   => array( 'type' => 'text', 'label' => __( 'Suffix', 'loom-builder' ), 'default' => '+', 'section' => 'content' ),
					'label'    => array( 'type' => 'text', 'label' => __( 'Label', 'loom-builder' ), 'default' => __( 'Happy clients', 'loom-builder' ), 'section' => 'content' ),
					'duration' => array( 'type' => 'range', 'label' => __( 'Animation duration (ms)', 'loom-builder' ), 'default' => 2000, 'min' => 500, 'max' => 5000, 'section' => 'content' ),
					'align'    => array(
						'type'    => 'select',
						'label'   => __( 'Alignment', 'loom-builder' ),
						'default' => 'center',
						'options' => array(
							'left'   => __( 'Left', 'loom-builder' ),
							'center' => __( 'Center', 'loom-builder' ),
							'right'  => __( 'Right', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'color'    => array( 'type' => 'color', 'label' => __( 'Number color', 'loom-builder' ), 'default' => '#111111', 'section' => 'style' ),
					'size'     => array( 'type' => 'range', 'label' => __( 'Number size (px)', 'loom-builder' ), 'default' => 48, 'min' => 20, 'max' => 96, 'section' => 'style' ),
				),
				'render'   => 'loom_render_counter',
			)
		);
	}
);

/**
 * Render the counter widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_counter( $s ) {
	$number = is_numeric( $s['number'] ) ? $s['number'] + 0 : 0;
	$align  = in_array( isset( $s['align'] ) ? $s['align'] : '', array( 'left', 'center', 'right' ), true ) ? $s['align'] : 'center';
	$color  = loom_css_color( isset( $s['color'] ) ? $s['color'] : '' );
	$color  = $color ? $color : '#111111';
	$size   = max( 20, (int) $s['size'] );
	$dur    = max( 100, (int) $s['duration'] );

	$style = 'color:' . $color . ';font-size:' . $size . 'px';

	$out  = '<div class="loom-counter loom-counter-' . esc_attr( $align ) . '" data-loom-counter data-value="' . esc_attr( $number ) . '" data-duration="' . esc_attr( $dur ) . '">';
	$out .= '<span class="loom-counter-number" style="' . esc_attr( $style ) . '">';
	$out .= '<span class="loom-counter-prefix">' . esc_html( $s['prefix'] ) . '</span>';
	$out .= '<span class="loom-counter-value">0</span>';
	$out .= '<span class="loom-counter-suffix">' . esc_html( $s['suffix'] ) . '</span>';
	$out .= '</span>';
	if ( ! empty( $s['label'] ) ) {
		$out .= '<div class="loom-counter-label">' . esc_html( $s['label'] ) . '</div>';
	}
	$out .= '</div>';

	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
