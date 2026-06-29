<?php
/**
 * Carousel widget. Multi-item horizontal slider with responsive item counts,
 * loop, autoplay, arrows and dots. Enhanced by the "carousel" frontend module.
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
				'id'       => 'carousel',
				'title'    => __( 'Carousel', 'loom' ),
				'icon'     => 'slides',
				'category' => 'media',
				'controls' => array(
					'items'    => array(
						'type'    => 'repeater',
						'label'   => __( 'Items', 'loom' ),
						'section' => 'content',
						'titleField' => 'caption',
						'fields'  => array(
							'image'   => array( 'type' => 'imageobj', 'label' => __( 'Image', 'loom' ), 'default' => array() ),
							'caption' => array( 'type' => 'text', 'label' => __( 'Caption', 'loom' ), 'default' => '' ),
							'link'    => array( 'type' => 'url', 'label' => __( 'Link', 'loom' ), 'default' => '' ),
						),
						'default' => array(),
					),
					'perD'     => array( 'type' => 'range', 'label' => __( 'Items: desktop', 'loom' ), 'default' => 3, 'min' => 1, 'max' => 8, 'section' => 'content' ),
					'perT'     => array( 'type' => 'range', 'label' => __( 'Items: tablet', 'loom' ), 'default' => 2, 'min' => 1, 'max' => 6, 'section' => 'content' ),
					'perM'     => array( 'type' => 'range', 'label' => __( 'Items: mobile', 'loom' ), 'default' => 1, 'min' => 1, 'max' => 4, 'section' => 'content' ),
					'gap'      => array( 'type' => 'range', 'label' => __( 'Gap (px)', 'loom' ), 'default' => 20, 'min' => 0, 'max' => 60, 'section' => 'content' ),
					'autoplay' => array( 'type' => 'toggle', 'label' => __( 'Autoplay', 'loom' ), 'default' => false, 'section' => 'content' ),
					'interval' => array( 'type' => 'number', 'label' => __( 'Interval (ms)', 'loom' ), 'default' => 4000, 'section' => 'content' ),
					'loop'     => array( 'type' => 'toggle', 'label' => __( 'Loop', 'loom' ), 'default' => true, 'section' => 'content' ),
					'arrows'   => array( 'type' => 'toggle', 'label' => __( 'Arrows', 'loom' ), 'default' => true, 'section' => 'content' ),
					'dots'     => array( 'type' => 'toggle', 'label' => __( 'Dots', 'loom' ), 'default' => false, 'section' => 'content' ),
					'radius'   => array( 'type' => 'range', 'label' => __( 'Image radius (px)', 'loom' ), 'default' => 10, 'min' => 0, 'max' => 40, 'section' => 'style' ),
				),
				'render'   => 'loom_render_carousel',
			)
		);
	}
);

/**
 * Render the carousel widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_carousel( $s ) {
	$items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
	if ( empty( $items ) ) {
		return '<div class="loom-carousel-empty">' . esc_html__( 'Add items to the carousel.', 'loom' ) . '</div>';
	}

	$gap    = (int) $s['gap'];
	$radius = (int) $s['radius'];

	$data = sprintf(
		' data-loom-carousel data-d="%d" data-t="%d" data-m="%d" data-gap="%d" data-autoplay="%s" data-interval="%d" data-loop="%s"',
		max( 1, (int) $s['perD'] ),
		max( 1, (int) $s['perT'] ),
		max( 1, (int) $s['perM'] ),
		$gap,
		! empty( $s['autoplay'] ) ? '1' : '0',
		max( 1000, (int) $s['interval'] ),
		! empty( $s['loop'] ) ? '1' : '0'
	);

	$out  = '<div class="loom-carousel"' . $data . '>';
	$out .= '<div class="loom-carousel-viewport"><div class="loom-carousel-track" style="gap:' . $gap . 'px">';

	foreach ( $items as $item ) {
		$img = isset( $item['image']['url'] ) ? esc_url( $item['image']['url'] ) : '';
		$alt = isset( $item['image']['alt'] ) ? esc_attr( $item['image']['alt'] ) : '';

		$media = $img
			? '<img src="' . $img . '" alt="' . $alt . '" loading="lazy" style="border-radius:' . $radius . 'px">'
			: '<span class="loom-image-placeholder">' . esc_html__( 'No image', 'loom' ) . '</span>';

		if ( ! empty( $item['link'] ) ) {
			$media = '<a href="' . esc_url( $item['link'] ) . '">' . $media . '</a>';
		}

		$caption = ! empty( $item['caption'] ) ? '<div class="loom-carousel-caption">' . esc_html( $item['caption'] ) . '</div>' : '';

		$out .= '<div class="loom-carousel-item">' . $media . $caption . '</div>';
	}

	$out .= '</div></div>'; // track, viewport

	if ( ! empty( $s['arrows'] ) ) {
		$out .= '<button class="loom-carousel-arrow loom-carousel-prev" aria-label="' . esc_attr__( 'Previous', 'loom' ) . '">&#8249;</button>';
		$out .= '<button class="loom-carousel-arrow loom-carousel-next" aria-label="' . esc_attr__( 'Next', 'loom' ) . '">&#8250;</button>';
	}
	if ( ! empty( $s['dots'] ) ) {
		$out .= '<div class="loom-carousel-dots"></div>';
	}

	$out .= '</div>'; // .loom-carousel

	return $out;
}
