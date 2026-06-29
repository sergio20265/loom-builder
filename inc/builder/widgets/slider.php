<?php
/**
 * Slider widget. Full-width slides with fade / slide / cards effects,
 * autoplay, arrows and dots. Enhanced on the frontend by the "slider" module.
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
				'id'       => 'slider',
				'title'    => __( 'Slider', 'loom' ),
				'icon'     => 'images-alt2',
				'category' => 'media',
				'controls' => array(
					'slides'   => array(
						'type'    => 'repeater',
						'label'   => __( 'Slides', 'loom' ),
						'section' => 'content',
						'titleField' => 'title',
						'fields'  => array(
							'image'   => array( 'type' => 'imageobj', 'label' => __( 'Image', 'loom' ), 'default' => array() ),
							'title'   => array( 'type' => 'text', 'label' => __( 'Title', 'loom' ), 'default' => __( 'Slide title', 'loom' ) ),
							'text'    => array( 'type' => 'textarea', 'label' => __( 'Text', 'loom' ), 'default' => '' ),
							'btnText' => array( 'type' => 'text', 'label' => __( 'Button text', 'loom' ), 'default' => '' ),
							'btnLink' => array( 'type' => 'url', 'label' => __( 'Button link', 'loom' ), 'default' => '' ),
						),
						'default' => array(),
					),
					'effect'   => array(
						'type'    => 'select',
						'label'   => __( 'Effect', 'loom' ),
						'default' => 'slide',
						'options' => array(
							'slide' => __( 'Slide', 'loom' ),
							'fade'  => __( 'Fade', 'loom' ),
							'cards' => __( 'Cards', 'loom' ),
						),
						'section' => 'content',
					),
					'height'   => array(
						'type'    => 'range',
						'label'   => __( 'Height (px)', 'loom' ),
						'default' => 480,
						'min'     => 160,
						'max'     => 900,
						'section' => 'content',
					),
					'autoplay' => array(
						'type'    => 'toggle',
						'label'   => __( 'Autoplay', 'loom' ),
						'default' => true,
						'section' => 'content',
					),
					'interval' => array(
						'type'    => 'number',
						'label'   => __( 'Interval (ms)', 'loom' ),
						'default' => 5000,
						'section' => 'content',
					),
					'arrows'   => array(
						'type'    => 'toggle',
						'label'   => __( 'Arrows', 'loom' ),
						'default' => true,
						'section' => 'content',
					),
					'dots'     => array(
						'type'    => 'toggle',
						'label'   => __( 'Dots', 'loom' ),
						'default' => true,
						'section' => 'content',
					),
					'overlay'  => array(
						'type'    => 'range',
						'label'   => __( 'Overlay darkness (%)', 'loom' ),
						'default' => 35,
						'min'     => 0,
						'max'     => 90,
						'section' => 'style',
					),
				),
				'render'   => 'loom_render_slider',
			)
		);
	}
);

/**
 * Render the slider widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_slider( $s ) {
	$slides = isset( $s['slides'] ) && is_array( $s['slides'] ) ? $s['slides'] : array();
	if ( empty( $slides ) ) {
		return '<div class="loom-slider-empty">' . esc_html__( 'Add slides to the slider.', 'loom' ) . '</div>';
	}

	$effect   = in_array( $s['effect'], array( 'slide', 'fade', 'cards' ), true ) ? $s['effect'] : 'slide';
	$height   = (int) $s['height'];
	$overlay  = max( 0, min( 90, (int) $s['overlay'] ) ) / 100;
	$autoplay = ! empty( $s['autoplay'] ) ? '1' : '0';
	$interval = max( 1000, (int) $s['interval'] );
	$arrows   = ! empty( $s['arrows'] );
	$dots     = ! empty( $s['dots'] );

	$data = sprintf(
		' data-loom-slider data-effect="%s" data-autoplay="%s" data-interval="%d"',
		esc_attr( $effect ),
		esc_attr( $autoplay ),
		$interval
	);

	$out  = '<div class="loom-slider loom-slider-' . esc_attr( $effect ) . '" style="--loom-slider-h:' . $height . 'px"' . $data . '>';
	$out .= '<div class="loom-slides">';

	foreach ( $slides as $slide ) {
		$img   = isset( $slide['image']['url'] ) ? esc_url( $slide['image']['url'] ) : '';
		$style = $img ? ' style="background-image:linear-gradient(rgba(0,0,0,' . $overlay . '),rgba(0,0,0,' . $overlay . ')),url(' . $img . ')"' : '';

		$out .= '<div class="loom-slide"' . $style . '>';
		$out .= '<div class="loom-slide-content">';
		if ( ! empty( $slide['title'] ) ) {
			$out .= '<h2 class="loom-slide-title">' . esc_html( $slide['title'] ) . '</h2>';
		}
		if ( ! empty( $slide['text'] ) ) {
			$out .= '<p class="loom-slide-text">' . esc_html( $slide['text'] ) . '</p>';
		}
		if ( ! empty( $slide['btnText'] ) ) {
			$link = ! empty( $slide['btnLink'] ) ? esc_url( $slide['btnLink'] ) : '#';
			$out .= '<a class="loom-button loom-slide-btn" href="' . $link . '">' . esc_html( $slide['btnText'] ) . '</a>';
		}
		$out .= '</div></div>';
	}

	$out .= '</div>'; // .loom-slides

	if ( $arrows && count( $slides ) > 1 ) {
		$out .= '<button class="loom-slider-arrow loom-slider-prev" aria-label="' . esc_attr__( 'Previous', 'loom' ) . '">&#8249;</button>';
		$out .= '<button class="loom-slider-arrow loom-slider-next" aria-label="' . esc_attr__( 'Next', 'loom' ) . '">&#8250;</button>';
	}
	if ( $dots && count( $slides ) > 1 ) {
		$out .= '<div class="loom-slider-dots"></div>';
	}

	$out .= '</div>'; // .loom-slider

	return $out;
}
