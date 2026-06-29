<?php
/**
 * Site logo widget. Uses the theme custom logo or a chosen image, linked home.
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
				'id'       => 'site_logo',
				'title'    => __( 'Site Logo', 'loom' ),
				'icon'     => 'admin-home',
				'category' => 'site',
				'controls' => array(
					'source' => array(
						'type'    => 'select',
						'label'   => __( 'Source', 'loom' ),
						'default' => 'site',
						'options' => array(
							'site'   => __( 'Theme logo', 'loom' ),
							'custom' => __( 'Custom image', 'loom' ),
						),
						'section' => 'content',
					),
					'image'  => array(
						'type'    => 'imageobj',
						'label'   => __( 'Custom image', 'loom' ),
						'default' => array(),
						'section' => 'content',
					),
					'width'  => array(
						'type'    => 'range',
						'label'   => __( 'Width (px)', 'loom' ),
						'default' => 120,
						'min'     => 20,
						'max'     => 480,
						'section' => 'content',
					),
					'link'   => array(
						'type'    => 'toggle',
						'label'   => __( 'Link to home', 'loom' ),
						'default' => true,
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_site_logo',
			)
		);
	}
);

/**
 * Render the site logo widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_site_logo( $s ) {
	$source = isset( $s['source'] ) && 'custom' === $s['source'] ? 'custom' : 'site';
	$width  = max( 20, (int) $s['width'] );

	$img = '';
	if ( 'custom' === $source && ! empty( $s['image']['url'] ) ) {
		$img = '<img src="' . esc_url( $s['image']['url'] ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" style="width:' . $width . 'px;height:auto">';
	} elseif ( has_custom_logo() ) {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		$img     = wp_get_attachment_image( $logo_id, 'full', false, array( 'style' => 'width:' . $width . 'px;height:auto' ) );
	}

	if ( ! $img ) {
		$img = '<span class="loom-logo-text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
	}

	if ( ! empty( $s['link'] ) ) {
		$img = '<a href="' . esc_url( home_url( '/' ) ) . '" rel="home">' . $img . '</a>';
	}

	return '<div class="loom-site-logo">' . $img . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
