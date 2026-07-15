<?php
/**
 * Alert / notice box widget. Optional dismiss button handled by the "alert"
 * frontend module (assets/js/frontend.js).
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
				'id'       => 'alert',
				'title'    => __( 'Alert', 'loom-builder' ),
				'icon'     => 'warning',
				'category' => 'basic',
				'controls' => array(
					'type'        => array(
						'type'    => 'select',
						'label'   => __( 'Type', 'loom-builder' ),
						'default' => 'info',
						'options' => array(
							'info'    => __( 'Info', 'loom-builder' ),
							'success' => __( 'Success', 'loom-builder' ),
							'warning' => __( 'Warning', 'loom-builder' ),
							'error'   => __( 'Error', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'text'        => array( 'type' => 'textarea', 'label' => __( 'Text', 'loom-builder' ), 'default' => __( 'This is an alert message.', 'loom-builder' ), 'section' => 'content' ),
					'icon'        => array( 'type' => 'toggle', 'label' => __( 'Show icon', 'loom-builder' ), 'default' => true, 'section' => 'content' ),
					'dismissible' => array( 'type' => 'toggle', 'label' => __( 'Dismissible', 'loom-builder' ), 'default' => false, 'section' => 'content' ),
				),
				'render'   => 'loom_render_alert',
			)
		);
	}
);

/**
 * Dashicon slug per alert type.
 *
 * @param string $type Alert type.
 * @return string
 */
function loom_alert_icon( $type ) {
	$map = array(
		'info'    => 'info',
		'success' => 'yes-alt',
		'warning' => 'warning',
		'error'   => 'dismiss',
	);
	return isset( $map[ $type ] ) ? $map[ $type ] : 'info';
}

/**
 * Render the alert widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_alert( $s ) {
	$type = in_array( isset( $s['type'] ) ? $s['type'] : '', array( 'info', 'success', 'warning', 'error' ), true ) ? $s['type'] : 'info';

	$out  = '<div class="loom-alert loom-alert-' . esc_attr( $type ) . '"' . ( ! empty( $s['dismissible'] ) ? ' data-loom-alert' : '' ) . '>';
	if ( ! empty( $s['icon'] ) ) {
		$out .= '<span class="loom-alert-icon dashicons dashicons-' . esc_attr( loom_alert_icon( $type ) ) . '"></span>';
	}
	$out .= '<div class="loom-alert-text">' . esc_html( $s['text'] ) . '</div>';
	if ( ! empty( $s['dismissible'] ) ) {
		$out .= '<button type="button" class="loom-alert-dismiss" aria-label="' . esc_attr__( 'Dismiss', 'loom-builder' ) . '">&times;</button>';
	}
	$out .= '</div>';

	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
