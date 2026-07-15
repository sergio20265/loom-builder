<?php
/**
 * Shortcode widget. Runs any registered WordPress shortcode in place.
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
				'id'       => 'shortcode',
				'title'    => __( 'Shortcode', 'loom-builder' ),
				'icon'     => 'shortcode',
				'category' => 'basic',
				'controls' => array(
					'shortcode' => array(
						'type'    => 'textarea',
						'label'   => __( 'Shortcode', 'loom-builder' ),
						'default' => '',
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_shortcode',
			)
		);
	}
);

/**
 * Render the shortcode widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_shortcode( $s ) {
	$code = isset( $s['shortcode'] ) ? trim( (string) $s['shortcode'] ) : '';
	if ( '' === $code ) {
		return '';
	}
	return '<div class="loom-shortcode">' . do_shortcode( $code ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
