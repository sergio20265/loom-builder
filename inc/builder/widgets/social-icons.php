<?php
/**
 * Social icons widget. Inline SVG brand icons, no icon font dependency.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inline SVG path data for supported networks.
 *
 * @return array<string,string>
 */
function loom_social_paths() {
	return array(
		'facebook'  => 'M13 22v-8h2.7l.4-3H13V9.2c0-.9.3-1.5 1.6-1.5H16V5.1c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7v3h2.8v8H13z',
		'instagram' => 'M7 2h10a5 5 0 015 5v10a5 5 0 01-5 5H7a5 5 0 01-5-5V7a5 5 0 015-5zm0 2a3 3 0 00-3 3v10a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H7zm5 3a5 5 0 110 10 5 5 0 010-10zm0 2a3 3 0 100 6 3 3 0 000-6zm5.5-.5a1 1 0 110 2 1 1 0 010-2z',
		'twitter'   => 'M18.9 2H22l-7.6 8.7L23 22h-6.9l-5.4-7-6.2 7H1.4l8.1-9.3L1 2h7l4.9 6.5L18.9 2zm-1.2 18h1.9L7.4 4H5.4l12.3 16z',
		'youtube'   => 'M23 7.5a3 3 0 00-2.1-2.1C19 5 12 5 12 5s-7 0-8.9.4A3 3 0 001 7.5 31 31 0 001 12a31 31 0 00.1 4.5 3 3 0 002.1 2.1C5 19 12 19 12 19s7 0 8.9-.4a3 3 0 002.1-2.1A31 31 0 0023 12a31 31 0 00-.1-4.5zM10 15V9l5.2 3L10 15z',
		'telegram'  => 'M21.9 4.3l-3 14.2c-.2 1-.8 1.2-1.7.8l-4.6-3.4-2.2 2.1c-.2.2-.4.4-.9.4l.3-4.7L18.4 6c.2-.2-.1-.3-.4-.1L7.2 12.5l-4.6-1.4c-1-.3-1-1 .2-1.5l18-6.9c.8-.3 1.5.2 1.1 1.7z',
		'vk'        => 'M21.6 7.3c.1-.4 0-.7-.6-.7h-2c-.5 0-.7.3-.9.6 0 0-1 2.5-2.5 4.1-.5.5-.7.6-.9.6-.1 0-.3-.1-.3-.5V7.3c0-.5-.1-.7-.5-.7H11c-.3 0-.5.2-.5.5 0 .5.7.6.8 2v2.8c0 .6-.1.7-.3.7-.6 0-2.3-2.5-3.3-5.4-.2-.5-.4-.7-.9-.7H4.6c-.5 0-.6.3-.6.6 0 .5.6 3.2 3.1 6.7 1.7 2.4 4 3.7 6.1 3.7 1.3 0 1.4-.3 1.4-.8v-1.8c0-.5.1-.6.5-.6.3 0 .7.1 1.8 1.2 1.2 1.2 1.4 1.8 2.1 1.8h2c.5 0 .8-.3.6-.8-.1-.5-.7-1.2-1.5-2-.4-.5-1-1.1-1.2-1.3-.3-.4-.2-.5 0-.8 0 0 2.1-3 2.3-4z',
		'whatsapp'  => 'M20 3.9A10 10 0 003.5 16L2 22l6.2-1.6A10 10 0 1020 3.9zM12 20a8 8 0 01-4-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1112 20zm4.4-5.6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.1-.3.2-.5.1a6.5 6.5 0 01-1.9-1.2 7 7 0 01-1.3-1.6c-.1-.2 0-.4.1-.5l.4-.4.2-.4v-.4l-.8-1.8c-.2-.5-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3a2.7 2.7 0 00-.9 2c0 1.2.9 2.4 1 2.5.1.2 1.7 2.7 4.2 3.7 1.5.6 2.1.7 2.8.6.5-.1 1.4-.6 1.6-1.1.2-.6.2-1 .1-1.1l-.4-.3z',
		'tiktok'    => 'M16 3c.3 2 1.5 3.6 3.5 3.9v2.7c-1.3 0-2.5-.4-3.5-1.1v6.1a5.6 5.6 0 11-5.6-5.6c.3 0 .6 0 .9.1v2.8a2.8 2.8 0 102 2.7V3H16z',
		'linkedin'  => 'M6.5 8.5h-3V21h3V8.5zM5 3.5a1.8 1.8 0 100 3.6 1.8 1.8 0 000-3.6zM21 21h-3v-6.5c0-1.6-.6-2.6-1.9-2.6-1 0-1.6.7-1.9 1.4-.1.2-.1.6-.1.9V21h-3V8.5h3v1.7c.4-.7 1.2-1.7 3-1.7 2.2 0 3.8 1.4 3.8 4.5V21z',
		'email'     => 'M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 2v.4l8 5 8-5V6H4zm16 2.3l-7.4 4.6a1 1 0 01-1.2 0L4 8.3V18h16V8.3z',
	);
}

add_action(
	'loom_register_widgets',
	static function ( $registry ) {
		$registry->register(
			array(
				'id'       => 'social',
				'title'    => __( 'Social Icons', 'loom-builder' ),
				'icon'     => 'share',
				'category' => 'site',
				'controls' => array(
					'items' => array(
						'type'       => 'repeater',
						'label'      => __( 'Icons', 'loom-builder' ),
						'section'    => 'content',
						'titleField' => 'network',
						'fields'     => array(
							'network' => array(
								'type'    => 'select',
								'label'   => __( 'Network', 'loom-builder' ),
								'default' => 'facebook',
								'options' => array(
									'facebook'  => 'Facebook',
									'instagram' => 'Instagram',
									'twitter'   => 'X / Twitter',
									'youtube'   => 'YouTube',
									'telegram'  => 'Telegram',
									'vk'        => 'VK',
									'whatsapp'  => 'WhatsApp',
									'tiktok'    => 'TikTok',
									'linkedin'  => 'LinkedIn',
									'email'     => 'Email',
								),
							),
							'url'     => array( 'type' => 'url', 'label' => __( 'Link', 'loom-builder' ), 'default' => '' ),
						),
						'default'    => array(),
					),
					'size'  => array( 'type' => 'range', 'label' => __( 'Icon size (px)', 'loom-builder' ), 'default' => 22, 'min' => 12, 'max' => 48, 'section' => 'content' ),
					'gap'   => array( 'type' => 'range', 'label' => __( 'Gap (px)', 'loom-builder' ), 'default' => 10, 'min' => 0, 'max' => 40, 'section' => 'content' ),
					'shape' => array(
						'type'    => 'select',
						'label'   => __( 'Shape', 'loom-builder' ),
						'default' => 'round',
						'options' => array(
							'bare'   => __( 'Bare', 'loom-builder' ),
							'round'  => __( 'Round', 'loom-builder' ),
							'square' => __( 'Square', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'color' => array( 'type' => 'color', 'label' => __( 'Icon color', 'loom-builder' ), 'default' => '#2563eb', 'section' => 'style' ),
				),
				'render'   => 'loom_render_social',
			)
		);
	}
);

/**
 * Render the social icons widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_social( $s ) {
	$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
	if ( empty( $items ) ) {
		return '<div class="loom-social-empty">' . esc_html__( 'Add social links.', 'loom-builder' ) . '</div>';
	}

	$paths = loom_social_paths();
	$size  = max( 12, (int) $s['size'] );
	$gap   = (int) $s['gap'];
	$shape = in_array( isset( $s['shape'] ) ? $s['shape'] : '', array( 'bare', 'round', 'square' ), true ) ? $s['shape'] : 'round';
	$color = loom_css_color( isset( $s['color'] ) ? $s['color'] : '' );
	$color = $color ? $color : '#2563eb';

	$style = 'gap:' . $gap . 'px;--loom-social-color:' . $color . ';--loom-social-size:' . $size . 'px';
	$out   = '<div class="loom-social loom-social-' . esc_attr( $shape ) . '" style="' . esc_attr( $style ) . '">';

	foreach ( $items as $item ) {
		$net = isset( $item['network'] ) ? $item['network'] : '';
		if ( ! isset( $paths[ $net ] ) ) {
			continue;
		}
		$url = ! empty( $item['url'] ) ? esc_url( $item['url'] ) : '#';
		$svg = '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="currentColor" fill-rule="evenodd" aria-hidden="true"><path d="' . esc_attr( $paths[ $net ] ) . '"/></svg>';
		$out .= '<a class="loom-social-link loom-social-' . esc_attr( $net ) . '" href="' . $url . '" aria-label="' . esc_attr( ucfirst( $net ) ) . '" rel="noopener"' . ( 'email' === $net ? '' : ' target="_blank"' ) . '>' . $svg . '</a>';
	}

	$out .= '</div>';
	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
