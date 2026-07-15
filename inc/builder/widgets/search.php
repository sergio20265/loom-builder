<?php
/**
 * Search form widget.
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
				'id'       => 'search',
				'title'    => __( 'Search', 'loom-builder' ),
				'icon'     => 'search',
				'category' => 'site',
				'controls' => array(
					'placeholder' => array(
						'type'    => 'text',
						'label'   => __( 'Placeholder', 'loom-builder' ),
						'default' => __( 'Search…', 'loom-builder' ),
						'section' => 'content',
					),
					'button'      => array(
						'type'    => 'text',
						'label'   => __( 'Button text', 'loom-builder' ),
						'default' => __( 'Search', 'loom-builder' ),
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_search',
			)
		);
	}
);

/**
 * Render the search widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_search( $s ) {
	$placeholder = isset( $s['placeholder'] ) ? $s['placeholder'] : __( 'Search…', 'loom-builder' );
	$button      = isset( $s['button'] ) ? $s['button'] : __( 'Search', 'loom-builder' );

	$out  = '<form role="search" method="get" class="loom-search-form" action="' . esc_url( home_url( '/' ) ) . '">';
	$out .= '<input type="search" class="loom-search-input" name="s" value="' . esc_attr( get_search_query() ) . '" placeholder="' . esc_attr( $placeholder ) . '" aria-label="' . esc_attr__( 'Search', 'loom-builder' ) . '">';
	$out .= '<button type="submit" class="loom-search-btn">' . esc_html( $button ) . '</button>';
	$out .= '</form>';

	return $out;
}
