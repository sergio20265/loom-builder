<?php
/**
 * Custom HTML widget. Raw markup is kept as-is for users who may post
 * unfiltered HTML and run through wp_kses_post otherwise (see sanitize.php).
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
				'id'       => 'html',
				'title'    => __( 'HTML', 'loom' ),
				'icon'     => 'editor-code',
				'category' => 'basic',
				'controls' => array(
					'code' => array(
						'type'    => 'code',
						'label'   => __( 'HTML code', 'loom' ),
						'default' => '',
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_html',
			)
		);
	}
);

/**
 * Render the HTML widget. Content is sanitized on save per user capability.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_html( $s ) {
	$code = isset( $s['code'] ) ? (string) $s['code'] : '';
	if ( '' === trim( $code ) ) {
		return '';
	}
	return '<div class="loom-html">' . $code . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
