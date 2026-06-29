<?php
/**
 * Accordion / toggle widget (FAQ). Enhanced by the "accordion" frontend module.
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
				'id'       => 'accordion',
				'title'    => __( 'Accordion', 'loom' ),
				'icon'     => 'editor-justify',
				'category' => 'basic',
				'controls' => array(
					'items'     => array(
						'type'       => 'repeater',
						'label'      => __( 'Items', 'loom' ),
						'section'    => 'content',
						'titleField' => 'title',
						'fields'     => array(
							'title'   => array( 'type' => 'text', 'label' => __( 'Title', 'loom' ), 'default' => __( 'Question', 'loom' ) ),
							'content' => array( 'type' => 'textarea', 'label' => __( 'Content', 'loom' ), 'default' => '' ),
						),
						'default'    => array(),
					),
					'firstOpen' => array( 'type' => 'toggle', 'label' => __( 'Open first item', 'loom' ), 'default' => true, 'section' => 'content' ),
					'single'    => array( 'type' => 'toggle', 'label' => __( 'One open at a time', 'loom' ), 'default' => true, 'section' => 'content' ),
				),
				'render'   => 'loom_render_accordion',
			)
		);
	}
);

/**
 * Render the accordion widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_accordion( $s ) {
	$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
	if ( empty( $items ) ) {
		return '<div class="loom-accordion-empty">' . esc_html__( 'Add accordion items.', 'loom' ) . '</div>';
	}

	$single = ! empty( $s['single'] ) ? '1' : '0';
	$out    = '<div class="loom-accordion" data-loom-accordion data-single="' . esc_attr( $single ) . '">';

	$i = 0;
	foreach ( $items as $item ) {
		$open  = ( 0 === $i && ! empty( $s['firstOpen'] ) );
		$title = isset( $item['title'] ) ? esc_html( $item['title'] ) : '';
		$body  = isset( $item['content'] ) ? wpautop( wp_kses_post( $item['content'] ) ) : '';

		$out .= '<div class="loom-acc-item' . ( $open ? ' is-open' : '' ) . '">';
		$out .= '<button type="button" class="loom-acc-head" aria-expanded="' . ( $open ? 'true' : 'false' ) . '">';
		$out .= '<span class="loom-acc-title">' . $title . '</span><span class="loom-acc-mark" aria-hidden="true"></span>';
		$out .= '</button>';
		$out .= '<div class="loom-acc-panel"' . ( $open ? '' : ' hidden' ) . '><div class="loom-acc-body">' . $body . '</div></div>';
		$out .= '</div>';
		$i++;
	}

	$out .= '</div>';
	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
