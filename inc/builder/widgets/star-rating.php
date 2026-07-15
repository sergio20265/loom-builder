<?php
/**
 * Star rating widget.
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
				'id'       => 'star_rating',
				'title'    => __( 'Star Rating', 'loom-builder' ),
				'icon'     => 'star-filled',
				'category' => 'basic',
				'controls' => array(
					'rating' => array( 'type' => 'range', 'label' => __( 'Rating', 'loom-builder' ), 'default' => 4, 'min' => 0, 'max' => 5, 'section' => 'content' ),
					'label'  => array( 'type' => 'text', 'label' => __( 'Label', 'loom-builder' ), 'default' => '', 'section' => 'content' ),
					'color'  => array( 'type' => 'color', 'label' => __( 'Star color', 'loom-builder' ), 'default' => '#f59e0b', 'section' => 'style' ),
					'size'   => array( 'type' => 'range', 'label' => __( 'Star size (px)', 'loom-builder' ), 'default' => 20, 'min' => 14, 'max' => 40, 'section' => 'style' ),
				),
				'render'   => 'loom_render_star_rating',
			)
		);
	}
);

/**
 * Render the star rating widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_star_rating( $s ) {
	$rating = max( 0, min( 5, (int) $s['rating'] ) );
	$color  = loom_css_color( isset( $s['color'] ) ? $s['color'] : '' );
	$color  = $color ? $color : '#f59e0b';
	$size   = max( 14, (int) $s['size'] );

	$out  = '<div class="loom-star-rating" style="--loom-star-color:' . esc_attr( $color ) . ';--loom-star-size:' . $size . 'px">';
	$out .= '<span class="loom-star-rating-stars" aria-label="' . esc_attr( sprintf( /* translators: %d: rating out of 5. */ __( '%d out of 5', 'loom-builder' ), $rating ) ) . '">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= '<span class="dashicons dashicons-star-' . ( $i <= $rating ? 'filled' : 'empty' ) . '"></span>';
	}
	$out .= '</span>';
	if ( ! empty( $s['label'] ) ) {
		$out .= '<span class="loom-star-rating-label">' . esc_html( $s['label'] ) . '</span>';
	}
	$out .= '</div>';

	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
