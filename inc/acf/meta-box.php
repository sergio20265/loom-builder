<?php
/**
 * Value meta box: renders the fields that apply to the post being edited and
 * saves their values to post meta.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes', 'loom_value_meta_box', 10, 2 );

/**
 * Register the value meta box when the current post matches a field group.
 *
 * @param string  $post_type Current post type.
 * @param WP_Post $post      Current post.
 * @return void
 */
function loom_value_meta_box( $post_type, $post ) {
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	$fields = loom_get_fields_for_post( $post->ID );
	if ( empty( $fields ) ) {
		return;
	}
	add_meta_box(
		'loom-field-values',
		__( 'Loom Fields', 'loom-builder' ),
		'loom_render_value_meta_box',
		$post_type,
		'normal',
		'default',
		array( 'fields' => $fields )
	);
}

/**
 * Render the value meta box.
 *
 * @param WP_Post $post Current post.
 * @param array   $meta Box args (fields).
 * @return void
 */
function loom_render_value_meta_box( $post, $meta ) {
	$fields = isset( $meta['args']['fields'] ) ? $meta['args']['fields'] : array();
	wp_nonce_field( 'loom_values_save', 'loom_values_nonce' );

	wp_enqueue_media();
	wp_enqueue_script( 'loom-acf-fields', LOOM_URL . 'assets/js/acf-fields.js', array(), LOOM_VERSION, true );
	wp_enqueue_style( 'loom-acf-admin', LOOM_URL . 'assets/css/acf-admin.css', array(), LOOM_VERSION );

	echo '<div class="loom-values">';
	foreach ( $fields as $field ) {
		$stored  = get_post_meta( $post->ID, LOOM_FIELD_PREFIX . $field['name'], true );
		$decoded = loom_decode_field_value( $field, $stored );

		echo '<div class="loom-value-row">';
		echo '<label class="loom-value-label">' . esc_html( $field['label'] ) . ' <code>' . esc_html( $field['name'] ) . '</code></label>';
		echo '<div class="loom-value-input">' . loom_render_field_input( $field, $decoded ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
	echo '</div>';
}

add_action( 'save_post', 'loom_values_save' );

/**
 * Persist submitted field values for any post type.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function loom_values_save( $post_id ) {
	if ( ! isset( $_POST['loom_values_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['loom_values_nonce'] ) ), 'loom_values_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = loom_get_fields_for_post( $post_id );
	if ( empty( $fields ) ) {
		return;
	}

	$submitted = isset( $_POST['loom_field'] ) ? wp_unslash( $_POST['loom_field'] ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.MissingUnslash

	foreach ( $fields as $field ) {
		$name = $field['name'];
		$raw  = isset( $submitted[ $name ] ) ? $submitted[ $name ] : '';

		// Checkbox companion: unchecked true_false sends nothing.
		if ( 'true_false' === $field['type'] ) {
			$raw = isset( $submitted[ $name ] ) ? $submitted[ $name ] : '';
		}
		// Color text companion overrides the native picker when present.
		if ( 'color' === $field['type'] && isset( $_POST['loom_field'][ $name . '_hex' ] ) ) {
			$hex = sanitize_text_field( wp_unslash( $_POST['loom_field'][ $name . '_hex' ] ) );
			if ( $hex ) {
				$raw = $hex;
			}
		}

		$value = loom_sanitize_field_value( $field, $raw, $submitted );
		update_post_meta( $post_id, LOOM_FIELD_PREFIX . $name, $value );
	}
}
