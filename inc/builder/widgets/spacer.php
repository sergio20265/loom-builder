<?php
/**
 * Spacer widget (vertical gap).
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
				'id'       => 'spacer',
				'title'    => __( 'Spacer', 'loom-builder' ),
				'icon'     => 'image-flip-vertical',
				'category' => 'layout',
				'controls' => array(
					'height' => array(
						'type'    => 'range',
						'label'   => __( 'Height (px)', 'loom-builder' ),
						'default' => 50,
						'min'     => 0,
						'max'     => 400,
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_spacer',
			)
		);
	}
);

/**
 * Render the spacer widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_spacer( $s ) {
	$height = (int) $s['height'];
	return '<div class="loom-spacer" style="height:' . $height . 'px"></div>';
}
