<?php
/**
 * Table widget. Each row's cells are entered as a single "|"-separated field
 * to keep the schema flat (no nested repeaters in the editor).
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
				'id'       => 'table',
				'title'    => __( 'Table', 'loom' ),
				'icon'     => 'editor-table',
				'category' => 'basic',
				'controls' => array(
					'rows'      => array(
						'type'       => 'repeater',
						'label'      => __( 'Rows', 'loom' ),
						'section'    => 'content',
						'titleField' => 'cells',
						'fields'     => array(
							'cells' => array( 'type' => 'text', 'label' => __( 'Cells (separate with |)', 'loom' ), 'default' => '' ),
						),
						'default'    => array(
							array( 'cells' => __( 'Column A', 'loom' ) . ' | ' . __( 'Column B', 'loom' ) ),
							array( 'cells' => __( 'Value 1', 'loom' ) . ' | ' . __( 'Value 2', 'loom' ) ),
						),
					),
					'headerRow' => array( 'type' => 'toggle', 'label' => __( 'First row is header', 'loom' ), 'default' => true, 'section' => 'content' ),
					'striped'   => array( 'type' => 'toggle', 'label' => __( 'Striped rows', 'loom' ), 'default' => true, 'section' => 'content' ),
					'bordered'  => array( 'type' => 'toggle', 'label' => __( 'Bordered', 'loom' ), 'default' => true, 'section' => 'content' ),
				),
				'render'   => 'loom_render_table',
			)
		);
	}
);

/**
 * Render the table widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_table( $s ) {
	$rows = isset( $s['rows'] ) && is_array( $s['rows'] ) ? $s['rows'] : array();
	if ( empty( $rows ) ) {
		return '<div class="loom-table-empty">' . esc_html__( 'Add table rows.', 'loom' ) . '</div>';
	}

	$header = ! empty( $s['headerRow'] );
	$class  = 'loom-table';
	$class .= ! empty( $s['striped'] ) ? ' is-striped' : '';
	$class .= ! empty( $s['bordered'] ) ? ' is-bordered' : '';

	$out    = '<table class="' . esc_attr( $class ) . '">';
	$body_open = false;

	foreach ( $rows as $i => $row ) {
		$cells    = isset( $row['cells'] ) ? array_map( 'trim', explode( '|', (string) $row['cells'] ) ) : array();
		$is_head  = ( 0 === $i && $header );
		$tag      = $is_head ? 'th' : 'td';

		if ( $is_head ) {
			$out .= '<thead><tr>';
		} else {
			if ( ! $body_open ) {
				$out .= '<tbody>';
				$body_open = true;
			}
			$out .= '<tr>';
		}

		foreach ( $cells as $cell ) {
			$out .= '<' . $tag . '>' . esc_html( $cell ) . '</' . $tag . '>';
		}

		$out .= $is_head ? '</tr></thead>' : '</tr>';
	}

	if ( $body_open ) {
		$out .= '</tbody>';
	}
	$out .= '</table>';

	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
