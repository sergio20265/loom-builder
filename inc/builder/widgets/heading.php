<?php
/**
 * Heading widget.
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
				'id'       => 'heading',
				'title'    => __( 'Heading', 'loom-builder' ),
				'icon'     => 'heading',
				'category' => 'basic',
				'controls' => array(
					'text' => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'loom-builder' ),
						'default' => __( 'Add Your Heading Text Here', 'loom-builder' ),
						'section' => 'content',
					),
					'tag'  => array(
						'type'    => 'select',
						'label'   => __( 'HTML Tag', 'loom-builder' ),
						'default' => 'h2',
						'options' => array(
							'h1'  => 'H1',
							'h2'  => 'H2',
							'h3'  => 'H3',
							'h4'  => 'H4',
							'h5'  => 'H5',
							'h6'  => 'H6',
							'div' => 'div',
						),
						'section' => 'content',
					),
					'link' => array(
						'type'    => 'url',
						'label'   => __( 'Link', 'loom-builder' ),
						'default' => '',
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_heading',
			)
		);
	}
);

/**
 * Render the heading widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_heading( $s ) {
	$allowed = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div' );
	$tag     = in_array( $s['tag'], $allowed, true ) ? $s['tag'] : 'h2';
	$text    = esc_html( $s['text'] );

	if ( ! empty( $s['link'] ) ) {
		$text = '<a href="' . esc_url( $s['link'] ) . '">' . $text . '</a>';
	}

	return sprintf( '<%1$s class="loom-heading">%2$s</%1$s>', $tag, $text );
}
