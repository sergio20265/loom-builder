<?php
/**
 * Tabs widget. Enhanced by the "tabs" frontend module.
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
				'id'       => 'tabs',
				'title'    => __( 'Tabs', 'loom' ),
				'icon'     => 'index-card',
				'category' => 'basic',
				'controls' => array(
					'items' => array(
						'type'       => 'repeater',
						'label'      => __( 'Tabs', 'loom' ),
						'section'    => 'content',
						'titleField' => 'title',
						'fields'     => array(
							'title'   => array( 'type' => 'text', 'label' => __( 'Title', 'loom' ), 'default' => __( 'Tab', 'loom' ) ),
							'content' => array( 'type' => 'textarea', 'label' => __( 'Content', 'loom' ), 'default' => '' ),
						),
						'default'    => array(),
					),
				),
				'render'   => 'loom_render_tabs',
			)
		);
	}
);

/**
 * Render the tabs widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_tabs( $s ) {
	$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
	if ( empty( $items ) ) {
		return '<div class="loom-tabs-empty">' . esc_html__( 'Add tabs.', 'loom' ) . '</div>';
	}

	$nav    = '<div class="loom-tabs-nav" role="tablist">';
	$panels = '<div class="loom-tabs-panels">';

	$i = 0;
	foreach ( $items as $item ) {
		$active = ( 0 === $i );
		$title  = isset( $item['title'] ) ? esc_html( $item['title'] ) : '';
		$body   = isset( $item['content'] ) ? wpautop( wp_kses_post( $item['content'] ) ) : '';

		$nav    .= '<button type="button" class="loom-tab-btn' . ( $active ? ' is-active' : '' ) . '" role="tab" aria-selected="' . ( $active ? 'true' : 'false' ) . '" data-loom-tab="' . $i . '">' . $title . '</button>';
		$panels .= '<div class="loom-tab-panel' . ( $active ? ' is-active' : '' ) . '" role="tabpanel" data-loom-tab="' . $i . '"' . ( $active ? '' : ' hidden' ) . '>' . $body . '</div>';
		$i++;
	}

	$nav    .= '</div>';
	$panels .= '</div>';

	return '<div class="loom-tabs" data-loom-tabs>' . $nav . $panels . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
