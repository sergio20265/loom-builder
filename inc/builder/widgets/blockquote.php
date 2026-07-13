<?php
/**
 * Blockquote widget.
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
				'id'       => 'blockquote',
				'title'    => __( 'Blockquote', 'loom' ),
				'icon'     => 'format-quote',
				'category' => 'basic',
				'controls' => array(
					'text'   => array( 'type' => 'textarea', 'label' => __( 'Quote', 'loom' ), 'default' => __( 'The best way to predict the future is to create it.', 'loom' ), 'section' => 'content' ),
					'author' => array( 'type' => 'text', 'label' => __( 'Author', 'loom' ), 'default' => '', 'section' => 'content' ),
					'source' => array( 'type' => 'text', 'label' => __( 'Source', 'loom' ), 'default' => '', 'section' => 'content' ),
					'style'  => array(
						'type'    => 'select',
						'label'   => __( 'Style', 'loom' ),
						'default' => 'default',
						'options' => array(
							'default'  => __( 'Default', 'loom' ),
							'large'    => __( 'Large', 'loom' ),
							'bordered' => __( 'Bordered', 'loom' ),
						),
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_blockquote',
			)
		);
	}
);

/**
 * Render the blockquote widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_blockquote( $s ) {
	$style = in_array( isset( $s['style'] ) ? $s['style'] : '', array( 'default', 'large', 'bordered' ), true ) ? $s['style'] : 'default';

	$out = '<blockquote class="loom-blockquote loom-blockquote-' . esc_attr( $style ) . '">';
	$out .= '<p>' . esc_html( $s['text'] ) . '</p>';
	if ( ! empty( $s['author'] ) ) {
		$out .= '<footer class="loom-blockquote-cite"><cite>' . esc_html( $s['author'] );
		if ( ! empty( $s['source'] ) ) {
			$out .= ', ' . esc_html( $s['source'] );
		}
		$out .= '</cite></footer>';
	}
	$out .= '</blockquote>';

	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
