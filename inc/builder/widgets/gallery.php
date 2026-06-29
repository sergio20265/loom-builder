<?php
/**
 * Gallery widget. Grid or masonry layout with an optional native lightbox.
 * Enhanced by the "lightbox" frontend module (no external library).
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
				'id'       => 'gallery',
				'title'    => __( 'Gallery', 'loom' ),
				'icon'     => 'format-gallery',
				'category' => 'media',
				'controls' => array(
					'images'   => array(
						'type'    => 'gallery',
						'label'   => __( 'Images', 'loom' ),
						'default' => array(),
						'section' => 'content',
					),
					'layout'   => array(
						'type'    => 'select',
						'label'   => __( 'Layout', 'loom' ),
						'default' => 'grid',
						'options' => array(
							'grid'    => __( 'Grid', 'loom' ),
							'masonry' => __( 'Masonry', 'loom' ),
						),
						'section' => 'content',
					),
					'colsD'    => array( 'type' => 'range', 'label' => __( 'Columns: desktop', 'loom' ), 'default' => 3, 'min' => 1, 'max' => 6, 'section' => 'content' ),
					'colsT'    => array( 'type' => 'range', 'label' => __( 'Columns: tablet', 'loom' ), 'default' => 2, 'min' => 1, 'max' => 5, 'section' => 'content' ),
					'colsM'    => array( 'type' => 'range', 'label' => __( 'Columns: mobile', 'loom' ), 'default' => 1, 'min' => 1, 'max' => 3, 'section' => 'content' ),
					'gap'      => array( 'type' => 'range', 'label' => __( 'Gap (px)', 'loom' ), 'default' => 12, 'min' => 0, 'max' => 48, 'section' => 'content' ),
					'radius'   => array( 'type' => 'range', 'label' => __( 'Radius (px)', 'loom' ), 'default' => 8, 'min' => 0, 'max' => 40, 'section' => 'style' ),
					'lightbox' => array( 'type' => 'toggle', 'label' => __( 'Lightbox', 'loom' ), 'default' => true, 'section' => 'content' ),
					'size'     => array(
						'type'    => 'select',
						'label'   => __( 'Thumbnail size', 'loom' ),
						'default' => 'medium_large',
						'options' => array(
							'medium'       => __( 'Medium', 'loom' ),
							'medium_large' => __( 'Medium Large', 'loom' ),
							'large'        => __( 'Large', 'loom' ),
						),
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_gallery',
			)
		);
	}
);

/**
 * Render the gallery widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_gallery( $s ) {
	$images = isset( $s['images'] ) && is_array( $s['images'] ) ? $s['images'] : array();
	if ( empty( $images ) ) {
		return '<div class="loom-gallery-empty">' . esc_html__( 'Add images to the gallery.', 'loom' ) . '</div>';
	}

	$layout   = 'masonry' === $s['layout'] ? 'masonry' : 'grid';
	$gap      = (int) $s['gap'];
	$radius   = (int) $s['radius'];
	$lightbox = ! empty( $s['lightbox'] );
	$size     = $s['size'] ? $s['size'] : 'medium_large';
	$group    = 'lg' . wp_rand( 1000, 9999 );

	$style = sprintf(
		'--loom-cols-d:%d;--loom-cols-t:%d;--loom-cols-m:%d;--loom-gap:%dpx;--loom-radius:%dpx;',
		max( 1, (int) $s['colsD'] ),
		max( 1, (int) $s['colsT'] ),
		max( 1, (int) $s['colsM'] ),
		$gap,
		$radius
	);

	$out = '<div class="loom-gallery loom-gallery-' . esc_attr( $layout ) . '" style="' . esc_attr( $style ) . '">';

	foreach ( $images as $image ) {
		$id    = isset( $image['id'] ) ? (int) $image['id'] : 0;
		$thumb = '';
		$full  = isset( $image['url'] ) ? esc_url( $image['url'] ) : '';
		$alt   = isset( $image['alt'] ) ? esc_attr( $image['alt'] ) : '';

		if ( $id ) {
			$thumb_src = wp_get_attachment_image_url( $id, $size );
			$full_src  = wp_get_attachment_image_url( $id, 'full' );
			if ( $thumb_src ) {
				$thumb = $thumb_src;
			}
			if ( $full_src ) {
				$full = $full_src;
			}
		}
		if ( ! $thumb ) {
			$thumb = $full;
		}
		if ( ! $thumb ) {
			continue;
		}

		$img_tag = '<img src="' . esc_url( $thumb ) . '" alt="' . $alt . '" loading="lazy">';

		if ( $lightbox && $full ) {
			$out .= '<a class="loom-gallery-item" href="' . esc_url( $full ) . '" data-loom-lightbox="' . esc_attr( $group ) . '"'
				. ( $alt ? ' data-caption="' . $alt . '"' : '' ) . '>' . $img_tag . '</a>';
		} else {
			$out .= '<figure class="loom-gallery-item">' . $img_tag . '</figure>';
		}
	}

	$out .= '</div>';

	return $out;
}
