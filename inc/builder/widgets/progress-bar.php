<?php
/**
 * Progress bar widget. Fill animates in on scroll via the "progress" frontend
 * module (assets/js/frontend.js).
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
				'id'       => 'progress_bar',
				'title'    => __( 'Progress Bar', 'loom' ),
				'icon'     => 'chart-line',
				'category' => 'basic',
				'controls' => array(
					'label'       => array( 'type' => 'text', 'label' => __( 'Label', 'loom' ), 'default' => __( 'Skill', 'loom' ), 'section' => 'content' ),
					'percent'     => array( 'type' => 'range', 'label' => __( 'Percent', 'loom' ), 'default' => 75, 'min' => 0, 'max' => 100, 'section' => 'content' ),
					'showPercent' => array( 'type' => 'toggle', 'label' => __( 'Show percent', 'loom' ), 'default' => true, 'section' => 'content' ),
					'animate'     => array( 'type' => 'toggle', 'label' => __( 'Animate on scroll', 'loom' ), 'default' => true, 'section' => 'content' ),
					'barColor'    => array( 'type' => 'color', 'label' => __( 'Bar color', 'loom' ), 'default' => '#2563eb', 'section' => 'style' ),
					'trackColor'  => array( 'type' => 'color', 'label' => __( 'Track color', 'loom' ), 'default' => '#e5e7eb', 'section' => 'style' ),
					'height'      => array( 'type' => 'range', 'label' => __( 'Height (px)', 'loom' ), 'default' => 10, 'min' => 4, 'max' => 40, 'section' => 'style' ),
				),
				'render'   => 'loom_render_progress_bar',
			)
		);
	}
);

/**
 * Render the progress bar widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_progress_bar( $s ) {
	$percent = max( 0, min( 100, (int) $s['percent'] ) );
	$height  = max( 4, (int) $s['height'] );
	$bar     = loom_css_color( isset( $s['barColor'] ) ? $s['barColor'] : '' );
	$bar     = $bar ? $bar : '#2563eb';
	$track   = loom_css_color( isset( $s['trackColor'] ) ? $s['trackColor'] : '' );
	$track   = $track ? $track : '#e5e7eb';
	$animate = ! empty( $s['animate'] );

	$out  = '<div class="loom-progress" data-loom-progress data-percent="' . esc_attr( $percent ) . '" data-animate="' . ( $animate ? '1' : '0' ) . '">';
	if ( ! empty( $s['label'] ) || ! empty( $s['showPercent'] ) ) {
		$out .= '<div class="loom-progress-head">';
		$out .= '<span class="loom-progress-label">' . esc_html( $s['label'] ) . '</span>';
		if ( ! empty( $s['showPercent'] ) ) {
			$out .= '<span class="loom-progress-percent">' . $percent . '%</span>';
		}
		$out .= '</div>';
	}
	$out .= '<div class="loom-progress-track" style="height:' . $height . 'px;background:' . esc_attr( $track ) . '">';
	$out .= '<div class="loom-progress-fill" style="width:' . ( $animate ? '0' : $percent ) . '%;height:' . $height . 'px;background:' . esc_attr( $bar ) . '"></div>';
	$out .= '</div></div>';

	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
