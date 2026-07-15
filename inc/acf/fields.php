<?php
/**
 * Native field engine. Defines field types and the three operations every
 * type needs: render an admin input, sanitize a submitted value for storage,
 * and format a stored value for output.
 *
 * Storage convention (post meta keyed by field name, prefixed with loom_):
 *   text/textarea/number/select/color/radio : scalar string
 *   true_false                               : '1' or '0'
 *   image                                    : attachment id (int)
 *   gallery                                  : JSON array of attachment ids
 *   link                                     : JSON { url, text, target }
 *   repeater                                 : JSON array of row objects
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta key prefix for stored field values.
 */
const LOOM_FIELD_PREFIX = 'loom_';

/**
 * The supported field types and their labels.
 *
 * @return array<string,string>
 */
function loom_field_types() {
	return array(
		'text'       => __( 'Text', 'loom-builder' ),
		'textarea'   => __( 'Textarea', 'loom-builder' ),
		'number'     => __( 'Number', 'loom-builder' ),
		'select'     => __( 'Select', 'loom-builder' ),
		'true_false' => __( 'True / False', 'loom-builder' ),
		'color'      => __( 'Color', 'loom-builder' ),
		'image'      => __( 'Image', 'loom-builder' ),
		'gallery'    => __( 'Gallery', 'loom-builder' ),
		'link'       => __( 'Link', 'loom-builder' ),
		'repeater'   => __( 'Repeater', 'loom-builder' ),
	);
}

/**
 * Field types that can act as a dynamic source for builder text widgets.
 *
 * @return string[]
 */
function loom_text_field_types() {
	return array( 'text', 'textarea', 'number', 'select', 'color' );
}

/**
 * Render the admin input for one field inside the value meta box.
 *
 * @param array $field Field definition (name, type, label, options, sub_fields).
 * @param mixed $value Stored value (already decoded).
 * @return string HTML.
 */
function loom_render_field_input( $field, $value ) {
	$name = 'loom_field[' . esc_attr( $field['name'] ) . ']';
	$id   = 'loom-field-' . esc_attr( $field['name'] );
	$type = $field['type'];

	switch ( $type ) {
		case 'textarea':
			return '<textarea id="' . $id . '" name="' . $name . '" rows="4" class="widefat">' . esc_textarea( (string) $value ) . '</textarea>';

		case 'number':
			return '<input type="number" id="' . $id . '" name="' . $name . '" value="' . esc_attr( (string) $value ) . '" class="regular-text">';

		case 'select':
			$out  = '<select id="' . $id . '" name="' . $name . '">';
			$out .= '<option value="">' . esc_html__( '— Select —', 'loom-builder' ) . '</option>';
			foreach ( loom_parse_choices( $field ) as $val => $label ) {
				$out .= '<option value="' . esc_attr( $val ) . '"' . selected( (string) $value, (string) $val, false ) . '>' . esc_html( $label ) . '</option>';
			}
			return $out . '</select>';

		case 'true_false':
			return '<label><input type="checkbox" id="' . $id . '" name="' . $name . '" value="1"' . checked( (string) $value, '1', false ) . '> ' . esc_html__( 'Yes', 'loom-builder' ) . '</label>';

		case 'color':
			return '<input type="color" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $value ? $value : '#000000' ) . '"> '
				. '<input type="text" name="' . $name . '_hex" value="' . esc_attr( (string) $value ) . '" class="loom-color-hex" placeholder="#rrggbb">';

		case 'image':
			$img = $value ? wp_get_attachment_image( (int) $value, 'thumbnail' ) : '';
			return '<div class="loom-field-image" data-loom-image>'
				. '<input type="hidden" name="' . $name . '" value="' . esc_attr( (string) $value ) . '" class="loom-image-id">'
				. '<div class="loom-image-thumb">' . $img . '</div>'
				. '<button type="button" class="button loom-image-pick">' . esc_html__( 'Select image', 'loom-builder' ) . '</button> '
				. '<button type="button" class="button-link loom-image-clear">' . esc_html__( 'Remove', 'loom-builder' ) . '</button>'
				. '</div>';

		case 'gallery':
			$ids   = is_array( $value ) ? $value : array();
			$thumb = '';
			foreach ( $ids as $gid ) {
				$thumb .= '<span class="loom-gthumb" data-id="' . (int) $gid . '">' . wp_get_attachment_image( (int) $gid, 'thumbnail' ) . '<button type="button" class="loom-gthumb-rm">×</button></span>';
			}
			return '<div class="loom-field-gallery" data-loom-gallery>'
				. '<input type="hidden" name="' . $name . '" value="' . esc_attr( implode( ',', array_map( 'intval', $ids ) ) ) . '" class="loom-gallery-ids">'
				. '<div class="loom-gallery-thumbs">' . $thumb . '</div>'
				. '<button type="button" class="button loom-gallery-pick">' . esc_html__( 'Add images', 'loom-builder' ) . '</button>'
				. '</div>';

		case 'link':
			$link = is_array( $value ) ? $value : array();
			$url  = isset( $link['url'] ) ? $link['url'] : '';
			$text = isset( $link['text'] ) ? $link['text'] : '';
			$tgt  = ! empty( $link['target'] );
			return '<div class="loom-field-link">'
				. '<input type="url" name="' . $name . '[url]" value="' . esc_attr( $url ) . '" placeholder="https://" class="widefat"> '
				. '<input type="text" name="' . $name . '[text]" value="' . esc_attr( $text ) . '" placeholder="' . esc_attr__( 'Link text', 'loom-builder' ) . '" class="widefat" style="margin-top:4px"> '
				. '<label style="display:block;margin-top:4px"><input type="checkbox" name="' . $name . '[target]" value="1"' . checked( $tgt, true, false ) . '> ' . esc_html__( 'Open in new tab', 'loom-builder' ) . '</label>'
				. '</div>';

		case 'repeater':
			return loom_render_repeater_input( $field, $value );

		case 'text':
		default:
			return '<input type="text" id="' . $id . '" name="' . $name . '" value="' . esc_attr( (string) $value ) . '" class="widefat">';
	}
}

/**
 * Render a repeater field input (rows of sub-fields).
 *
 * @param array $field Field definition with sub_fields.
 * @param mixed $value Stored rows.
 * @return string
 */
function loom_render_repeater_input( $field, $value ) {
	$rows       = is_array( $value ) ? $value : array();
	$sub_fields = isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ? $field['sub_fields'] : array();
	$base       = 'loom_field[' . esc_attr( $field['name'] ) . ']';

	$render_row = static function ( $index, $row ) use ( $sub_fields, $base ) {
		$out = '<div class="loom-rep-row"><div class="loom-rep-row-body">';
		foreach ( $sub_fields as $sf ) {
			$sname = $base . '[' . $index . '][' . esc_attr( $sf['name'] ) . ']';
			$sval  = isset( $row[ $sf['name'] ] ) ? $row[ $sf['name'] ] : '';
			$out  .= '<p><label>' . esc_html( $sf['label'] ) . '</label><br>';
			if ( 'textarea' === $sf['type'] ) {
				$out .= '<textarea name="' . $sname . '" rows="2" class="widefat">' . esc_textarea( (string) $sval ) . '</textarea>';
			} elseif ( 'image' === $sf['type'] ) {
				$img  = $sval ? wp_get_attachment_image( (int) $sval, 'thumbnail' ) : '';
				$out .= '<span class="loom-field-image" data-loom-image><input type="hidden" name="' . $sname . '" value="' . esc_attr( (string) $sval ) . '" class="loom-image-id"><span class="loom-image-thumb">' . $img . '</span><button type="button" class="button loom-image-pick">' . esc_html__( 'Image', 'loom-builder' ) . '</button></span>';
			} else {
				$out .= '<input type="text" name="' . $sname . '" value="' . esc_attr( (string) $sval ) . '" class="widefat">';
			}
			$out .= '</p>';
		}
		$out .= '</div><button type="button" class="button-link loom-rep-remove">× ' . esc_html__( 'Remove row', 'loom-builder' ) . '</button></div>';
		return $out;
	};

	$out = '<div class="loom-field-repeater" data-loom-repeater><div class="loom-rep-rows">';
	foreach ( $rows as $i => $row ) {
		$out .= $render_row( $i, $row );
	}
	$out .= '</div>';

	// Hidden template for JS-cloned new rows (index __i__).
	$out .= '<script type="text/html" class="loom-rep-tpl">' . $render_row( '__i__', array() ) . '</script>';
	$out .= '<button type="button" class="button loom-rep-add">+ ' . esc_html__( 'Add row', 'loom-builder' ) . '</button>';
	$out .= '</div>';
	return $out;
}

/**
 * Parse "value : Label" newline choices for select/radio fields.
 *
 * @param array $field Field definition.
 * @return array<string,string>
 */
function loom_parse_choices( $field ) {
	$raw = isset( $field['choices'] ) ? $field['choices'] : '';
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( strpos( $line, ':' ) !== false ) {
			list( $val, $label ) = array_map( 'trim', explode( ':', $line, 2 ) );
		} else {
			$val   = $line;
			$label = $line;
		}
		$out[ $val ] = $label;
	}
	return $out;
}

/**
 * Sanitize a submitted field value for storage.
 *
 * @param array $field Field definition.
 * @param mixed $raw   Raw submitted value.
 * @param array $post  Full $_POST (for color hex companion).
 * @return mixed Storable value (scalars or JSON-encoded strings).
 */
function loom_sanitize_field_value( $field, $raw, $post = array() ) {
	switch ( $field['type'] ) {
		case 'textarea':
			return sanitize_textarea_field( (string) $raw );

		case 'number':
			return is_numeric( $raw ) ? $raw + 0 : '';

		case 'true_false':
			return $raw ? '1' : '0';

		case 'color':
			return sanitize_hex_color( (string) $raw );

		case 'image':
			return (int) $raw;

		case 'gallery':
			$ids = array_filter( array_map( 'intval', explode( ',', (string) $raw ) ) );
			return wp_json_encode( array_values( $ids ) );

		case 'link':
			$link = is_array( $raw ) ? $raw : array();
			return wp_json_encode(
				array(
					'url'    => isset( $link['url'] ) ? esc_url_raw( $link['url'] ) : '',
					'text'   => isset( $link['text'] ) ? sanitize_text_field( $link['text'] ) : '',
					'target' => ! empty( $link['target'] ) ? 1 : 0,
				)
			);

		case 'repeater':
			$rows = is_array( $raw ) ? array_values( $raw ) : array();
			$out  = array();
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$clean = array();
				foreach ( $row as $k => $v ) {
					$clean[ sanitize_key( $k ) ] = is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : sanitize_text_field( $v );
				}
				$out[] = $clean;
			}
			return wp_json_encode( $out );

		case 'select':
		case 'text':
		default:
			return sanitize_text_field( (string) $raw );
	}
}

/**
 * Decode a stored value into its working PHP shape.
 *
 * @param array  $field  Field definition.
 * @param string $stored Stored meta value.
 * @return mixed
 */
function loom_decode_field_value( $field, $stored ) {
	switch ( $field['type'] ) {
		case 'gallery':
		case 'repeater':
			$d = json_decode( (string) $stored, true );
			return is_array( $d ) ? $d : array();
		case 'link':
			$d = json_decode( (string) $stored, true );
			return is_array( $d ) ? $d : array();
		case 'image':
		case 'number':
			return $stored;
		default:
			return $stored;
	}
}

/**
 * Format a stored value for frontend output / dynamic binding.
 *
 * @param array $field  Field definition.
 * @param mixed $value  Decoded value.
 * @return mixed text for text-like fields; url for image; array for gallery/link/repeater.
 */
function loom_format_field_value( $field, $value ) {
	switch ( $field['type'] ) {
		case 'image':
			return $value ? wp_get_attachment_image_url( (int) $value, 'large' ) : '';
		case 'gallery':
			$urls = array();
			foreach ( (array) $value as $gid ) {
				$urls[] = array( 'id' => (int) $gid, 'url' => wp_get_attachment_image_url( (int) $gid, 'large' ) );
			}
			return $urls;
		case 'true_false':
			return ( '1' === (string) $value );
		default:
			return $value;
	}
}
