<?php
/**
 * Field-group builder: the meta box on the loom_field_group edit screen and
 * its save handler. The builder UI itself is a small wp.element app
 * (assets/js/acf-admin.js) that serializes to two hidden inputs.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes', 'loom_group_meta_boxes' );

/**
 * Register the builder meta box.
 *
 * @return void
 */
function loom_group_meta_boxes() {
	add_meta_box(
		'loom-group-builder',
		__( 'Fields & Location', 'loom' ),
		'loom_group_meta_box',
		'loom_field_group',
		'normal',
		'high'
	);
}

/**
 * Render the builder root and enqueue its assets.
 *
 * @param WP_Post $post Current group.
 * @return void
 */
function loom_group_meta_box( $post ) {
	wp_nonce_field( 'loom_group_save', 'loom_group_nonce' );

	$fields   = get_post_meta( $post->ID, '_loom_group_fields', true );
	$location = get_post_meta( $post->ID, '_loom_group_location', true );

	$post_types = array();
	foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
		$post_types[ $pt->name ] = $pt->label;
	}

	$templates = get_page_templates(); // [ 'Template Name' => 'file.php' ]

	wp_enqueue_script(
		'loom-acf-admin',
		LOOM_URL . 'assets/js/acf-admin.js',
		array( 'wp-element' ),
		LOOM_VERSION,
		true
	);
	wp_enqueue_style( 'loom-acf-admin', LOOM_URL . 'assets/css/acf-admin.css', array(), LOOM_VERSION );
	wp_localize_script(
		'loom-acf-admin',
		'LoomAcf',
		array(
			'fields'     => $fields ? $fields : '[]',
			'location'   => $location ? $location : '[]',
			'types'      => loom_field_types(),
			'postTypes'  => $post_types,
			'templates'  => $templates,
			'i18n'       => array(
				'addField'   => __( 'Add Field', 'loom' ),
				'addRule'    => __( 'Add Rule', 'loom' ),
				'label'      => __( 'Label', 'loom' ),
				'name'       => __( 'Name', 'loom' ),
				'type'       => __( 'Type', 'loom' ),
				'choices'    => __( 'Choices (one per line, value:Label)', 'loom' ),
				'subFields'  => __( 'Sub fields', 'loom' ),
				'location'   => __( 'Show this group on', 'loom' ),
				'remove'     => __( 'Remove', 'loom' ),
			),
		)
	);

	echo '<div id="loom-acf-root"></div>';
	echo '<input type="hidden" name="loom_group_fields" id="loom_group_fields" value="' . esc_attr( $fields ? $fields : '[]' ) . '">';
	echo '<input type="hidden" name="loom_group_location" id="loom_group_location" value="' . esc_attr( $location ? $location : '[]' ) . '">';
}

/**
 * Persist the field group definition.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function loom_group_save( $post_id ) {
	if ( ! isset( $_POST['loom_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['loom_group_nonce'] ) ), 'loom_group_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields   = isset( $_POST['loom_group_fields'] ) ? wp_unslash( $_POST['loom_group_fields'] ) : '[]';
	$location = isset( $_POST['loom_group_location'] ) ? wp_unslash( $_POST['loom_group_location'] ) : '[]';

	// Validate JSON, re-encode to strip anything unexpected.
	$fields_arr   = json_decode( $fields, true );
	$location_arr = json_decode( $location, true );

	update_post_meta( $post_id, '_loom_group_fields', wp_slash( wp_json_encode( loom_sanitize_group_fields( is_array( $fields_arr ) ? $fields_arr : array() ) ) ) );
	update_post_meta( $post_id, '_loom_group_location', wp_slash( wp_json_encode( loom_sanitize_group_location( is_array( $location_arr ) ? $location_arr : array() ) ) ) );
}
add_action( 'save_post_loom_field_group', 'loom_group_save' );

/**
 * Sanitize an array of field definitions coming from the builder.
 *
 * @param array $fields Raw fields.
 * @return array
 */
function loom_sanitize_group_fields( $fields ) {
	$types = loom_field_types();
	$out   = array();
	foreach ( $fields as $field ) {
		if ( empty( $field['name'] ) || empty( $field['type'] ) || ! isset( $types[ $field['type'] ] ) ) {
			continue;
		}
		$clean = array(
			'name'  => sanitize_key( $field['name'] ),
			'label' => sanitize_text_field( isset( $field['label'] ) ? $field['label'] : $field['name'] ),
			'type'  => $field['type'],
		);
		if ( ! empty( $field['choices'] ) ) {
			$clean['choices'] = sanitize_textarea_field( $field['choices'] );
		}
		if ( 'repeater' === $field['type'] && ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
			$subs = array();
			foreach ( $field['sub_fields'] as $sf ) {
				if ( empty( $sf['name'] ) ) {
					continue;
				}
				$subs[] = array(
					'name'  => sanitize_key( $sf['name'] ),
					'label' => sanitize_text_field( isset( $sf['label'] ) ? $sf['label'] : $sf['name'] ),
					'type'  => isset( $sf['type'] ) && in_array( $sf['type'], array( 'text', 'textarea', 'image' ), true ) ? $sf['type'] : 'text',
				);
			}
			$clean['sub_fields'] = $subs;
		}
		$out[] = $clean;
	}
	return $out;
}

/**
 * Sanitize location rules.
 *
 * @param array $rules Raw rules.
 * @return array
 */
function loom_sanitize_group_location( $rules ) {
	$out    = array();
	$params = array( 'post_type', 'post_template', 'post' );
	foreach ( $rules as $rule ) {
		$param = isset( $rule['param'] ) ? $rule['param'] : '';
		if ( ! in_array( $param, $params, true ) ) {
			continue;
		}
		$out[] = array(
			'param'    => $param,
			'operator' => ( isset( $rule['operator'] ) && '!=' === $rule['operator'] ) ? '!=' : '==',
			'value'    => sanitize_text_field( isset( $rule['value'] ) ? $rule['value'] : '' ),
		);
	}
	return $out;
}
