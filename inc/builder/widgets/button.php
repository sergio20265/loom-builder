<?php
/**
 * Button widget.
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
				'id'       => 'button',
				'title'    => __( 'Button', 'loom-builder' ),
				'icon'     => 'button',
				'category' => 'basic',
				'controls' => array(
					'text'    => array(
						'type'    => 'text',
						'label'   => __( 'Text', 'loom-builder' ),
						'default' => __( 'Click here', 'loom-builder' ),
						'section' => 'content',
					),
					'link'    => array(
						'type'    => 'url',
						'label'   => __( 'Link', 'loom-builder' ),
						'default' => '#',
						'section' => 'content',
					),
					'target'  => array(
						'type'    => 'toggle',
						'label'   => __( 'Open in new tab', 'loom-builder' ),
						'default' => false,
						'section' => 'content',
					),
					'style'   => array(
						'type'    => 'select',
						'label'   => __( 'Style', 'loom-builder' ),
						'default' => 'solid',
						'options' => array(
							'solid'   => __( 'Solid', 'loom-builder' ),
							'outline' => __( 'Outline', 'loom-builder' ),
							'ghost'   => __( 'Ghost', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'bg'      => array(
						'type'    => 'color',
						'label'   => __( 'Background', 'loom-builder' ),
						'default' => '#2563eb',
						'section' => 'style',
					),
					'fg'      => array(
						'type'    => 'color',
						'label'   => __( 'Text color', 'loom-builder' ),
						'default' => '#ffffff',
						'section' => 'style',
					),
					'radius'  => array(
						'type'    => 'range',
						'label'   => __( 'Border radius', 'loom-builder' ),
						'default' => 8,
						'min'     => 0,
						'max'     => 80,
						'section' => 'style',
					),
					'padding' => array(
						'type'    => 'text',
						'label'   => __( 'Padding (CSS)', 'loom-builder' ),
						'default' => '12px 28px',
						'section' => 'style',
					),
				),
				'render'   => 'loom_render_button',
			)
		);
	}
);

/**
 * Render the button widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_button( $s ) {
	$text   = esc_html( $s['text'] );
	$link   = $s['link'] ? esc_url( $s['link'] ) : '#';
	$target = ! empty( $s['target'] ) ? ' target="_blank" rel="noopener noreferrer"' : '';
	$style  = in_array( $s['style'], array( 'solid', 'outline', 'ghost' ), true ) ? $s['style'] : 'solid';

	$bg     = loom_css_color( $s['bg'] );
	$fg     = loom_css_color( $s['fg'] );
	$radius = (int) $s['radius'];
	$pad    = loom_css_length_list( $s['padding'] );

	if ( ! $bg ) {
		$bg = '#2563eb';
	}
	if ( ! $fg ) {
		$fg = '#ffffff';
	}
	if ( ! $pad ) {
		$pad = '12px 28px';
	}

	$css = 'border-radius:' . $radius . 'px;padding:' . $pad . ';';
	if ( 'solid' === $style ) {
		$css .= 'background:' . $bg . ';color:' . $fg . ';border:2px solid ' . $bg . ';';
	} elseif ( 'outline' === $style ) {
		$css .= 'background:transparent;color:' . $bg . ';border:2px solid ' . $bg . ';';
	} else {
		$css .= 'background:transparent;color:' . $bg . ';border:2px solid transparent;';
	}

	return '<a class="loom-button loom-button-' . esc_attr( $style ) . '" href="' . $link . '"' . $target . ' style="' . esc_attr( $css ) . '">' . $text . '</a>';
}
