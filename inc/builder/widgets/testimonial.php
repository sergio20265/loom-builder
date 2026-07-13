<?php
/**
 * Testimonial widget: quote, avatar, name/role and an optional star rating.
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
				'id'       => 'testimonial',
				'title'    => __( 'Testimonial', 'loom' ),
				'icon'     => 'testimonial',
				'category' => 'basic',
				'controls' => array(
					'avatar'     => array( 'type' => 'imageobj', 'label' => __( 'Avatar', 'loom' ), 'default' => array(), 'section' => 'content' ),
					'text'       => array( 'type' => 'textarea', 'label' => __( 'Quote', 'loom' ), 'default' => __( 'This product changed how we work.', 'loom' ), 'section' => 'content' ),
					'name'       => array( 'type' => 'text', 'label' => __( 'Name', 'loom' ), 'default' => __( 'Jane Doe', 'loom' ), 'section' => 'content' ),
					'role'       => array( 'type' => 'text', 'label' => __( 'Role / company', 'loom' ), 'default' => __( 'CEO, Acme Inc.', 'loom' ), 'section' => 'content' ),
					'showRating' => array( 'type' => 'toggle', 'label' => __( 'Show rating', 'loom' ), 'default' => true, 'section' => 'content' ),
					'rating'     => array( 'type' => 'range', 'label' => __( 'Rating', 'loom' ), 'default' => 5, 'min' => 0, 'max' => 5, 'section' => 'content' ),
					'style'      => array(
						'type'    => 'select',
						'label'   => __( 'Style', 'loom' ),
						'default' => 'card',
						'options' => array(
							'card'  => __( 'Card', 'loom' ),
							'plain' => __( 'Plain', 'loom' ),
						),
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_testimonial',
			)
		);
	}
);

/**
 * Render five dashicon stars filled up to a whole-number rating.
 *
 * @param int $rating Rating 0-5.
 * @return string
 */
function loom_testimonial_stars( $rating ) {
	$rating = max( 0, min( 5, (int) $rating ) );
	$out    = '<span class="loom-testimonial-stars" aria-label="' . esc_attr( sprintf( /* translators: %d: rating out of 5. */ __( '%d out of 5', 'loom' ), $rating ) ) . '">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= '<span class="dashicons dashicons-star-' . ( $i <= $rating ? 'filled' : 'empty' ) . '"></span>';
	}
	$out .= '</span>';
	return $out;
}

/**
 * Render the testimonial widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_testimonial( $s ) {
	$style  = ( isset( $s['style'] ) && 'plain' === $s['style'] ) ? 'plain' : 'card';
	$avatar = isset( $s['avatar'] ) && is_array( $s['avatar'] ) ? $s['avatar'] : array();

	$out = '<div class="loom-testimonial loom-testimonial-' . esc_attr( $style ) . '">';

	if ( ! empty( $s['showRating'] ) ) {
		$out .= loom_testimonial_stars( isset( $s['rating'] ) ? $s['rating'] : 5 );
	}

	if ( ! empty( $s['text'] ) ) {
		$out .= '<p class="loom-testimonial-text">' . esc_html( $s['text'] ) . '</p>';
	}

	$out .= '<div class="loom-testimonial-meta">';
	if ( ! empty( $avatar['url'] ) ) {
		$out .= '<img class="loom-testimonial-avatar" src="' . esc_url( $avatar['url'] ) . '" alt="' . esc_attr( isset( $avatar['alt'] ) ? $avatar['alt'] : '' ) . '">';
	}
	$out .= '<div class="loom-testimonial-who">';
	if ( ! empty( $s['name'] ) ) {
		$out .= '<div class="loom-testimonial-name">' . esc_html( $s['name'] ) . '</div>';
	}
	if ( ! empty( $s['role'] ) ) {
		$out .= '<div class="loom-testimonial-role">' . esc_html( $s['role'] ) . '</div>';
	}
	$out .= '</div></div></div>';

	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
