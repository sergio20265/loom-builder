<?php
/**
 * Strict, schema-driven sanitization for the layout tree.
 *
 * Every persisted value is validated against the widget control schema in the
 * registry, so the editor and the renderer share one contract and arbitrary
 * keys never reach the database. Container nodes (section/column) only keep the
 * reserved style/advanced/dynamic buckets; widget nodes additionally keep the
 * settings declared by their controls.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reserved settings buckets shared by every node type.
 *
 * @return string[]
 */
function loom_reserved_setting_keys() {
	return array( '_style', '_advanced', '_dynamic' );
}

/**
 * Layout safety limits used by REST preview/save and template imports.
 *
 * @return array<string,int>
 */
function loom_layout_limits() {
	return (array) apply_filters(
		'loom_layout_limits',
		array(
			'max_nodes'        => 500,
			'max_depth'        => 24,
			'max_repeater_rows' => 100,
			'max_gallery_items' => 100,
			'max_import_bytes' => 1048576,
		)
	);
}

/**
 * Validate a raw layout tree before expensive sanitization/rendering.
 *
 * @param mixed $tree Raw layout tree.
 * @return true|WP_Error
 */
function loom_validate_tree_limits( $tree ) {
	if ( ! is_array( $tree ) ) {
		return new WP_Error( 'loom_invalid_layout', __( 'Layout must be an array.', 'loom' ), array( 'status' => 400 ) );
	}

	$limits = loom_layout_limits();
	$count  = 0;

	$walk = static function ( $nodes, $depth ) use ( &$walk, &$count, $limits ) {
		if ( $depth > (int) $limits['max_depth'] ) {
			return new WP_Error( 'loom_layout_too_deep', __( 'Layout is too deeply nested.', 'loom' ), array( 'status' => 400 ) );
		}

		foreach ( (array) $nodes as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			$count++;
			if ( $count > (int) $limits['max_nodes'] ) {
				return new WP_Error( 'loom_layout_too_large', __( 'Layout contains too many nodes.', 'loom' ), array( 'status' => 400 ) );
			}

			if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
				$result = $walk( $node['children'], $depth + 1 );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		return true;
	};

	return $walk( $tree, 1 );
}

/**
 * Recursively sanitize a layout tree before persisting.
 *
 * Allows only known node types and widget ids; settings are validated against
 * the widget control schema and the reserved style/advanced/dynamic buckets.
 *
 * @param array $tree Raw tree.
 * @return array
 */
function loom_sanitize_tree( $tree ) {
	$clean = array();

	foreach ( (array) $tree as $node ) {
		if ( empty( $node['type'] ) || empty( $node['id'] ) ) {
			continue;
		}

		$type = sanitize_key( $node['type'] );
		if ( ! in_array( $type, array( 'section', 'column', 'widget' ), true ) ) {
			continue;
		}

		$widget_id = '';
		if ( 'widget' === $type ) {
			$widget_id = sanitize_key( isset( $node['widget'] ) ? $node['widget'] : '' );
			// Drop widgets the registry does not know about.
			if ( '' === $widget_id || ! \Loom\Builder\Registry::instance()->has( $widget_id ) ) {
				continue;
			}
		}

		$out = array(
			'id'       => preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $node['id'] ),
			'type'     => $type,
			'settings' => loom_sanitize_node_settings(
				isset( $node['settings'] ) ? $node['settings'] : array(),
				$widget_id
			),
		);

		if ( 'widget' === $type ) {
			$out['widget'] = $widget_id;
		}

		$out['children'] = ( ! empty( $node['children'] ) && is_array( $node['children'] ) )
			? loom_sanitize_tree( $node['children'] )
			: array();

		$clean[] = $out;
	}

	return $clean;
}

/**
 * Sanitize a node's settings against its control schema.
 *
 * @param mixed  $settings  Raw settings.
 * @param string $widget_id Widget id, or '' for section/column containers.
 * @return array
 */
function loom_sanitize_node_settings( $settings, $widget_id ) {
	if ( ! is_array( $settings ) ) {
		return array();
	}

	$out      = array();
	$controls = array();

	if ( '' !== $widget_id ) {
		$widget   = \Loom\Builder\Registry::instance()->get( $widget_id );
		$controls = ( $widget && ! empty( $widget['controls'] ) ) ? $widget['controls'] : array();
	}

	// Schema-declared controls.
	foreach ( $controls as $key => $control ) {
		if ( array_key_exists( $key, $settings ) ) {
			$out[ $key ] = loom_sanitize_control_value( $control, $settings[ $key ] );
		}

		// The "image" control stores its companion url/preview alongside the id.
		if ( isset( $control['type'] ) && 'image' === $control['type'] ) {
			if ( isset( $settings['url'] ) ) {
				$out['url'] = esc_url_raw( (string) $settings['url'] );
			}
			if ( isset( $settings['preview'] ) ) {
				$out['preview'] = esc_url_raw( (string) $settings['preview'] );
			}
		}
	}

	// Reserved buckets, shared by every node type.
	if ( isset( $settings['_style'] ) ) {
		$out['_style'] = loom_sanitize_style( $settings['_style'] );
	}
	if ( isset( $settings['_advanced'] ) ) {
		$out['_advanced'] = loom_sanitize_advanced( $settings['_advanced'] );
	}
	if ( isset( $settings['_dynamic'] ) ) {
		$out['_dynamic'] = loom_sanitize_dynamic( $settings['_dynamic'] );
	}

	return $out;
}

/**
 * Sanitize a single value according to its control definition.
 *
 * @param array $control Control schema entry.
 * @param mixed $value   Raw value.
 * @return mixed
 */
function loom_sanitize_control_value( $control, $value ) {
	$type = isset( $control['type'] ) ? $control['type'] : 'text';

	switch ( $type ) {
		case 'textarea':
			return sanitize_textarea_field( (string) $value );

		case 'richtext':
			return wp_kses_post( (string) $value );

		case 'code':
			// Raw markup for users allowed to post it; filtered for everyone else.
			return current_user_can( 'unfiltered_html' ) ? (string) $value : wp_kses_post( (string) $value );

		case 'url':
			return esc_url_raw( (string) $value );

		case 'number':
			return loom_sanitize_number( $value );

		case 'range':
			$num = loom_sanitize_number( $value );
			if ( '' === $num ) {
				return '';
			}
			if ( isset( $control['min'] ) && $num < $control['min'] ) {
				$num = 0 + $control['min'];
			}
			if ( isset( $control['max'] ) && $num > $control['max'] ) {
				$num = 0 + $control['max'];
			}
			return $num;

		case 'toggle':
			return (bool) $value;

		case 'select':
			$options = isset( $control['options'] ) && is_array( $control['options'] ) ? $control['options'] : array();
			$value   = (string) $value;
			if ( array_key_exists( $value, $options ) ) {
				return $value;
			}
			return isset( $control['default'] ) ? (string) $control['default'] : '';

		case 'color':
			return loom_sanitize_color( $value );

		case 'image':
			return (int) $value;

		case 'imageobj':
			return loom_sanitize_image_object( $value );

		case 'gallery':
			$out = array();
			foreach ( (array) $value as $item ) {
				$img = loom_sanitize_image_object( $item );
				if ( ! empty( $img ) ) {
					$out[] = $img;
				}
			}
			return $out;

		case 'repeater':
			return loom_sanitize_repeater_rows( $control, $value );

		case 'text':
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Sanitize repeater rows against the control's field schema.
 *
 * @param array $control Repeater control (expects a "fields" map).
 * @param mixed $value   Raw rows.
 * @return array
 */
function loom_sanitize_repeater_rows( $control, $value ) {
	$fields = isset( $control['fields'] ) && is_array( $control['fields'] ) ? $control['fields'] : array();
	$out    = array();

	foreach ( (array) $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$clean = array();
		foreach ( $fields as $field_key => $field ) {
			if ( array_key_exists( $field_key, $row ) ) {
				$clean[ $field_key ] = loom_sanitize_control_value( $field, $row[ $field_key ] );
			}
		}
		$out[] = $clean;
	}

	return $out;
}

/**
 * Sanitize an { id, url, alt } image object.
 *
 * @param mixed $value Raw value.
 * @return array Empty array when no usable image is present.
 */
function loom_sanitize_image_object( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	$id  = isset( $value['id'] ) ? (int) $value['id'] : 0;
	$url = isset( $value['url'] ) ? esc_url_raw( (string) $value['url'] ) : '';
	if ( ! $id && '' === $url ) {
		return array();
	}
	return array(
		'id'  => $id,
		'url' => $url,
		'alt' => isset( $value['alt'] ) ? sanitize_text_field( (string) $value['alt'] ) : '',
	);
}

/**
 * Sanitize a numeric value, preserving int vs float, '' when empty/invalid.
 *
 * @param mixed $value Raw value.
 * @return int|float|string
 */
function loom_sanitize_number( $value ) {
	if ( '' === $value || null === $value ) {
		return '';
	}
	if ( ! is_numeric( $value ) ) {
		return '';
	}
	$num = $value + 0;
	return ( (float) (int) $num === (float) $num ) ? (int) $num : (float) $num;
}

/**
 * Sanitize a color string (hex / rgba / keyword), '' when not recognised.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function loom_sanitize_color( $value ) {
	if ( function_exists( 'loom_css_color' ) ) {
		return loom_css_color( $value );
	}
	$value = trim( (string) $value );
	return sanitize_hex_color( $value ) ? sanitize_hex_color( $value ) : '';
}

/**
 * Sanitize the responsive _style bucket: known devices and known CSS props only.
 *
 * @param mixed $style Raw style.
 * @return array
 */
function loom_sanitize_style( $style ) {
	if ( ! is_array( $style ) ) {
		return array();
	}

	$allowed_props = function_exists( 'loom_css_prop_map' ) ? array_keys( loom_css_prop_map() ) : array();
	$box_props     = array( 'padding', 'margin' );
	$out           = array();

	foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
		if ( empty( $style[ $device ] ) || ! is_array( $style[ $device ] ) ) {
			continue;
		}
		$props = array();
		foreach ( $style[ $device ] as $prop => $val ) {
			if ( $allowed_props && ! in_array( $prop, $allowed_props, true ) ) {
				continue;
			}
			if ( in_array( $prop, $box_props, true ) ) {
				$box = loom_sanitize_box( $val );
				if ( ! empty( $box ) ) {
					$props[ $prop ] = $box;
				}
			} elseif ( is_scalar( $val ) && '' !== $val ) {
				// The CSS generator re-validates each value strictly at output.
				$props[ $prop ] = function_exists( 'loom_css_safe' ) ? loom_css_safe( $val ) : sanitize_text_field( (string) $val );
			}
		}
		if ( $props ) {
			$out[ $device ] = $props;
		}
	}

	return $out;
}

/**
 * Sanitize a { t, r, b, l } box object to numeric members.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function loom_sanitize_box( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	$out = array();
	foreach ( array( 't', 'r', 'b', 'l' ) as $side ) {
		if ( isset( $value[ $side ] ) && is_numeric( $value[ $side ] ) ) {
			$out[ $side ] = loom_sanitize_number( $value[ $side ] );
		}
	}
	return $out;
}

/**
 * Sanitize the _advanced bucket against a fixed key whitelist.
 *
 * @param mixed $adv Raw advanced settings.
 * @return array
 */
function loom_sanitize_advanced( $adv ) {
	if ( ! is_array( $adv ) ) {
		return array();
	}

	$out  = array();
	$text = array( 'cssId', 'cssClass', 'animation', 'animationEasing', 'loopAnimation', 'hoverAnimation' );
	$ints = array( 'animationDuration', 'animationDelay' );
	$bool = array( 'hideDesktop', 'hideTablet', 'hideMobile' );

	foreach ( $text as $key ) {
		if ( isset( $adv[ $key ] ) && '' !== $adv[ $key ] ) {
			$out[ $key ] = sanitize_text_field( (string) $adv[ $key ] );
		}
	}
	foreach ( $ints as $key ) {
		if ( isset( $adv[ $key ] ) && '' !== $adv[ $key ] && is_numeric( $adv[ $key ] ) ) {
			$out[ $key ] = (int) $adv[ $key ];
		}
	}
	foreach ( $bool as $key ) {
		if ( ! empty( $adv[ $key ] ) ) {
			$out[ $key ] = true;
		}
	}

	return $out;
}

/**
 * Sanitize the _dynamic bucket: a map of setting key => custom field name.
 *
 * @param mixed $dynamic Raw dynamic map.
 * @return array
 */
function loom_sanitize_dynamic( $dynamic ) {
	if ( ! is_array( $dynamic ) ) {
		return array();
	}
	$out = array();
	foreach ( $dynamic as $key => $field ) {
		$field = sanitize_text_field( (string) $field );
		if ( '' === $field ) {
			continue;
		}
		$out[ sanitize_text_field( (string) $key ) ] = $field;
	}
	return $out;
}
