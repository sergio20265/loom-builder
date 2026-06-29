<?php
/**
 * Text / rich-text widget.
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
				'id'       => 'text',
				'title'    => __( 'Text Editor', 'loom' ),
				'icon'     => 'editor-paragraph',
				'category' => 'basic',
				'controls' => array(
					'content' => array(
						'type'    => 'richtext',
						'label'   => __( 'Content', 'loom' ),
						'default' => __( 'Start writing your text here. You can use paragraphs and basic formatting.', 'loom' ),
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_text',
			)
		);
	}
);

/**
 * Render the text widget. Content is stored as limited HTML and sanitized
 * on save; we sanitize again on output as defense in depth.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_text( $s ) {
	$html = isset( $s['content'] ) ? $s['content'] : '';
	$html = wp_kses_post( $html );
	return '<div class="loom-text">' . $html . '</div>';
}
