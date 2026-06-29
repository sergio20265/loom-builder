<?php
/**
 * Field-group storage, location matching and the public value API.
 *
 * Field groups are stored as `loom_field_group` posts with two meta entries:
 *   _loom_group_fields   : JSON array of field definitions
 *   _loom_group_location : JSON array of location rules (AND-combined)
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the field-group CPT under the Loom menu.
 *
 * @return void
 */
function loom_register_field_group_cpt() {
	register_post_type(
		'loom_field_group',
		array(
			'labels'       => array(
				'name'          => __( 'Field Groups', 'loom' ),
				'singular_name' => __( 'Field Group', 'loom' ),
				'add_new_item'  => __( 'Add Field Group', 'loom' ),
				'edit_item'     => __( 'Edit Field Group', 'loom' ),
				'menu_name'     => __( 'Field Groups', 'loom' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'loom-builder',
			'supports'     => array( 'title' ),
			'capability_type' => 'page',
			'map_meta_cap' => true,
			'menu_icon'    => 'dashicons-feedback',
		)
	);
}
add_action( 'init', 'loom_register_field_group_cpt' );

/**
 * Load all published field groups (decoded).
 *
 * @return array<int,array> Each: { id, title, fields[], location[] }.
 */
function loom_get_field_groups() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$posts  = get_posts(
		array(
			'post_type'      => 'loom_field_group',
			'post_status'    => 'publish',
			'numberposts'    => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);
	$cache = array();

	foreach ( $posts as $post ) {
		$fields   = json_decode( (string) get_post_meta( $post->ID, '_loom_group_fields', true ), true );
		$location = json_decode( (string) get_post_meta( $post->ID, '_loom_group_location', true ), true );
		$cache[]  = array(
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'fields'   => is_array( $fields ) ? $fields : array(),
			'location' => is_array( $location ) ? $location : array(),
		);
	}

	return $cache;
}

/**
 * Evaluate whether a group's location rules match a post. Rules are AND-combined.
 *
 * @param array $group   Group definition.
 * @param int   $post_id Post ID.
 * @return bool
 */
function loom_group_matches( $group, $post_id ) {
	$rules = $group['location'];
	if ( empty( $rules ) ) {
		return false;
	}

	$post_type = get_post_type( $post_id );

	foreach ( $rules as $rule ) {
		$param = isset( $rule['param'] ) ? $rule['param'] : '';
		$op    = isset( $rule['operator'] ) ? $rule['operator'] : '==';
		$value = isset( $rule['value'] ) ? $rule['value'] : '';
		$match = true;

		switch ( $param ) {
			case 'post_type':
				$match = ( $post_type === $value );
				break;
			case 'post_template':
				$match = ( get_page_template_slug( $post_id ) === $value );
				break;
			case 'post':
				$match = ( (int) $post_id === (int) $value );
				break;
			default:
				$match = true;
		}

		if ( '!=' === $op ) {
			$match = ! $match;
		}
		if ( ! $match ) {
			return false;
		}
	}

	return true;
}

/**
 * Get the merged field definitions that apply to a post.
 *
 * @param int $post_id Post ID.
 * @return array<int,array>
 */
function loom_get_fields_for_post( $post_id ) {
	$fields = array();
	foreach ( loom_get_field_groups() as $group ) {
		if ( loom_group_matches( $group, $post_id ) ) {
			foreach ( $group['fields'] as $field ) {
				$fields[] = $field;
			}
		}
	}
	return $fields;
}

/**
 * Find a field definition by name across all groups.
 *
 * @param string $name Field name.
 * @return array|null
 */
function loom_find_field( $name ) {
	foreach ( loom_get_field_groups() as $group ) {
		foreach ( $group['fields'] as $field ) {
			if ( isset( $field['name'] ) && $field['name'] === $name ) {
				return $field;
			}
		}
	}
	return null;
}

/**
 * Public accessor: read a formatted field value (ACF-style get_field()).
 *
 * @param string   $name    Field name.
 * @param int|null $post_id Post ID (defaults to current).
 * @return mixed
 */
function loom_field( $name, $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return null;
	}
	$field = loom_find_field( $name );
	if ( ! $field ) {
		return null;
	}
	$stored  = get_post_meta( $post_id, LOOM_FIELD_PREFIX . $name, true );
	$decoded = loom_decode_field_value( $field, $stored );
	return loom_format_field_value( $field, $decoded );
}

/**
 * Flat list of all text-like fields for the builder's dynamic-binding picker.
 *
 * @return array<int,array> Each: { name, label, type }.
 */
function loom_dynamic_field_choices() {
	$out  = array();
	$text = loom_text_field_types();
	$seen = array();
	foreach ( loom_get_field_groups() as $group ) {
		foreach ( $group['fields'] as $field ) {
			if ( ! in_array( $field['type'], $text, true ) ) {
				continue;
			}
			if ( isset( $seen[ $field['name'] ] ) ) {
				continue;
			}
			$seen[ $field['name'] ] = true;
			$out[] = array(
				'name'  => $field['name'],
				'label' => $field['label'] . ' (' . $group['title'] . ')',
				'type'  => $field['type'],
			);
		}
	}
	return $out;
}

/**
 * Image fields available for dynamic binding of the Image widget.
 *
 * @return array<int,array>
 */
function loom_dynamic_image_choices() {
	$out  = array();
	$seen = array();
	foreach ( loom_get_field_groups() as $group ) {
		foreach ( $group['fields'] as $field ) {
			if ( 'image' !== $field['type'] || isset( $seen[ $field['name'] ] ) ) {
				continue;
			}
			$seen[ $field['name'] ] = true;
			$out[] = array( 'name' => $field['name'], 'label' => $field['label'] . ' (' . $group['title'] . ')' );
		}
	}
	return $out;
}
