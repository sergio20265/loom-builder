<?php
/**
 * Image widget.
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
				'id'       => 'image',
				'title'    => __( 'Image', 'loom-builder' ),
				'icon'     => 'format-image',
				'category' => 'media',
				'controls' => array(
					'id'      => array(
						'type'    => 'image',
						'label'   => __( 'Choose Image', 'loom-builder' ),
						'default' => 0,
						'section' => 'content',
					),
					'size'    => array(
						'type'    => 'select',
						'label'   => __( 'Image Size', 'loom-builder' ),
						'default' => 'large',
						'options' => array(
							'thumbnail' => __( 'Thumbnail', 'loom-builder' ),
							'medium'    => __( 'Medium', 'loom-builder' ),
							'large'     => __( 'Large', 'loom-builder' ),
							'full'      => __( 'Full', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'alt'     => array(
						'type'    => 'text',
						'label'   => __( 'Alt Text (overrides media)', 'loom-builder' ),
						'default' => '',
						'section' => 'content',
					),
					'link'    => array(
						'type'    => 'url',
						'label'   => __( 'Link', 'loom-builder' ),
						'default' => '',
						'section' => 'content',
					),
					'caption' => array(
						'type'    => 'text',
						'label'   => __( 'Caption', 'loom-builder' ),
						'default' => '',
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_image',
			)
		);
	}
);

/**
 * Render the image widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_image( $s ) {
	$attachment_id = (int) $s['id'];
	$size          = $s['size'] ? $s['size'] : 'large';

	if ( $attachment_id ) {
		$attrs = array( 'class' => 'loom-image-el' );
		if ( ! empty( $s['alt'] ) ) {
			$attrs['alt'] = esc_attr( $s['alt'] );
		}
		$img = wp_get_attachment_image( $attachment_id, $size, false, $attrs );
	} elseif ( ! empty( $s['url'] ) ) {
		// Dynamic binding (or external URL) resolves to a plain src.
		$img = '<img class="loom-image-el" src="' . esc_url( $s['url'] ) . '" alt="' . esc_attr( $s['alt'] ) . '" loading="lazy">';
	} else {
		// Placeholder so the widget is visible before a media item is set.
		$img = '<span class="loom-image-placeholder">' . esc_html__( 'No image selected', 'loom-builder' ) . '</span>';
	}

	if ( ! empty( $s['link'] ) && $img ) {
		$img = '<a href="' . esc_url( $s['link'] ) . '">' . $img . '</a>';
	}

	$caption = '';
	if ( ! empty( $s['caption'] ) ) {
		$caption = '<figcaption class="loom-image-caption">' . esc_html( $s['caption'] ) . '</figcaption>';
	}

	return '<figure class="loom-image">' . $img . $caption . '</figure>';
}
