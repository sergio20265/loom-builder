<?php
/**
 * Site title / tagline widget.
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
				'id'       => 'site_title',
				'title'    => __( 'Site Title', 'loom' ),
				'icon'     => 'admin-site',
				'category' => 'site',
				'controls' => array(
					'tag'         => array(
						'type'    => 'select',
						'label'   => __( 'HTML Tag', 'loom' ),
						'default' => 'span',
						'options' => array(
							'h1'   => 'H1',
							'h2'   => 'H2',
							'h3'   => 'H3',
							'span' => 'span',
							'div'  => 'div',
						),
						'section' => 'content',
					),
					'showTagline' => array(
						'type'    => 'toggle',
						'label'   => __( 'Show tagline', 'loom' ),
						'default' => false,
						'section' => 'content',
					),
					'link'        => array(
						'type'    => 'toggle',
						'label'   => __( 'Link to home', 'loom' ),
						'default' => true,
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_site_title',
			)
		);
	}
);

/**
 * Render the site title widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_site_title( $s ) {
	$allowed = array( 'h1', 'h2', 'h3', 'span', 'div' );
	$tag     = in_array( isset( $s['tag'] ) ? $s['tag'] : '', $allowed, true ) ? $s['tag'] : 'span';

	$name = esc_html( get_bloginfo( 'name' ) );
	if ( ! empty( $s['link'] ) ) {
		$name = '<a href="' . esc_url( home_url( '/' ) ) . '" rel="home">' . $name . '</a>';
	}

	$out = sprintf( '<%1$s class="loom-site-title">%2$s</%1$s>', $tag, $name );

	if ( ! empty( $s['showTagline'] ) ) {
		$tagline = get_bloginfo( 'description' );
		if ( $tagline ) {
			$out .= '<div class="loom-site-tagline">' . esc_html( $tagline ) . '</div>';
		}
	}

	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
