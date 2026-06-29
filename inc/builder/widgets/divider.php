<?php
/**
 * Divider widget (horizontal rule with style options).
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
				'id'       => 'divider',
				'title'    => __( 'Divider', 'loom' ),
				'icon'     => 'minus',
				'category' => 'layout',
				'controls' => array(
					'style'     => array(
						'type'    => 'select',
						'label'   => __( 'Style', 'loom' ),
						'default' => 'solid',
						'options' => array(
							'solid'  => __( 'Solid', 'loom' ),
							'dashed' => __( 'Dashed', 'loom' ),
							'dotted' => __( 'Dotted', 'loom' ),
							'double' => __( 'Double', 'loom' ),
						),
						'section' => 'content',
					),
					'width'     => array( 'type' => 'range', 'label' => __( 'Width (%)', 'loom' ), 'default' => 100, 'min' => 10, 'max' => 100, 'section' => 'content' ),
					'thickness' => array( 'type' => 'range', 'label' => __( 'Thickness (px)', 'loom' ), 'default' => 1, 'min' => 1, 'max' => 12, 'section' => 'content' ),
					'gap'       => array( 'type' => 'range', 'label' => __( 'Spacing (px)', 'loom' ), 'default' => 16, 'min' => 0, 'max' => 80, 'section' => 'content' ),
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
					'color'     => array( 'type' => 'color', 'label' => __( 'Color', 'loom' ), 'default' => '#e5e7eb', 'section' => 'style' ),
				),
				'render'   => 'loom_render_divider',
			)
		);
	}
);

/**
 * Render the divider widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_divider( $s ) {
	$style     = in_array( isset( $s['style'] ) ? $s['style'] : '', array( 'solid', 'dashed', 'dotted', 'double' ), true ) ? $s['style'] : 'solid';
	$width     = max( 10, min( 100, (int) $s['width'] ) );
	$thickness = max( 1, (int) $s['thickness'] );
	$gap       = (int) $s['gap'];
	$align     = in_array( isset( $s['align'] ) ? $s['align'] : '', array( 'left', 'center', 'right' ), true ) ? $s['align'] : 'center';
	$color     = loom_css_color( isset( $s['color'] ) ? $s['color'] : '' );
	$color     = $color ? $color : '#e5e7eb';

	$margin = array(
		'left'   => $gap . 'px auto ' . $gap . 'px 0',
		'right'  => $gap . 'px 0 ' . $gap . 'px auto',
		'center' => $gap . 'px auto',
	);

	$hr = 'width:' . $width . '%;border:none;border-top:' . $thickness . 'px ' . $style . ' ' . $color . ';margin:' . $margin[ $align ] . ';';

	return '<hr class="loom-divider" style="' . esc_attr( $hr ) . '">';
}
