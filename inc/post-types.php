<?php
/**
 * Custom post types and meta registration for Loom Builder.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the loom_template CPT (headers, footers, reusable sections,
 * archive/single layouts) and the layout meta used on every builder entry.
 *
 * @return void
 */
function loom_register_post_types() {

	$labels = array(
		'name'               => __( 'Loom Templates', 'loom-builder' ),
		'singular_name'      => __( 'Loom Template', 'loom-builder' ),
		'add_new'            => __( 'Add Template', 'loom-builder' ),
		'add_new_item'       => __( 'Add New Template', 'loom-builder' ),
		'edit_item'          => __( 'Edit Template', 'loom-builder' ),
		'new_item'           => __( 'New Template', 'loom-builder' ),
		'view_item'          => __( 'View Template', 'loom-builder' ),
		'search_items'       => __( 'Search Templates', 'loom-builder' ),
		'not_found'          => __( 'No templates found', 'loom-builder' ),
		'not_found_in_trash' => __( 'No templates found in Trash', 'loom-builder' ),
		'menu_name'          => __( 'Templates', 'loom-builder' ),
	);

	register_post_type(
		'loom_template',
		array(
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => true,
			'show_in_menu'      => 'loom-builder',
			'show_in_rest'      => true,
			'hierarchical'      => false,
			'supports'          => array( 'title', 'author', 'custom-fields' ),
			'capability_type'   => 'page',
			'map_meta_cap'      => true,
			'menu_icon'         => 'dashicons-layout',
			'rewrite'           => false,
			'has_archive'       => false,
		)
	);

	loom_register_meta();
}
add_action( 'init', 'loom_register_post_types' );

/**
 * Register builder meta on every post type that can host a layout.
 * Stored as a JSON string so the renderer and REST share one shape.
 *
 * @return void
 */
function loom_register_meta() {

	$types = loom_builder_post_types();

	foreach ( $types as $type ) {
		// The serialized layout tree (JSON string).
		register_post_meta(
			$type,
			'_loom_layout',
			array(
				'type'          => 'string',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => static function ( $allowed, $meta_key, $object_id ) {
					return $object_id && current_user_can( 'edit_post', (int) $object_id );
				},
			)
		);

		// Whether the Loom renderer should replace the_content for this entry.
		register_post_meta(
			$type,
			'_loom_enabled',
			array(
				'type'          => 'boolean',
				'single'        => true,
				'show_in_rest'  => true,
				'default'       => false,
				'auth_callback' => static function ( $allowed, $meta_key, $object_id ) {
					return $object_id && current_user_can( 'edit_post', (int) $object_id );
				},
			)
		);
	}
}

/**
 * Post types that may use the builder. Filterable so add-ons can extend it.
 *
 * @return string[]
 */
function loom_builder_post_types() {
	$types = array( 'page', 'post', 'loom_template' );

	/**
	 * Filter the list of post types the Loom builder is available on.
	 *
	 * @param string[] $types Post type slugs.
	 */
	return (array) apply_filters( 'loom_builder_post_types', $types );
}

/**
 * Whether a given post is rendered by the Loom builder.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function loom_is_enabled( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id ) {
		return false;
	}
	if ( ! in_array( get_post_type( $post_id ), loom_builder_post_types(), true ) ) {
		return false;
	}
	return (bool) get_post_meta( $post_id, '_loom_enabled', true );
}

/**
 * Read and decode the layout tree for a post.
 *
 * @param int $post_id Post ID.
 * @return array Layout tree (empty array when none).
 */
function loom_get_layout( $post_id ) {
	$raw = get_post_meta( (int) $post_id, '_loom_layout', true );
	if ( empty( $raw ) || ! is_string( $raw ) ) {
		return array();
	}
	$data = json_decode( $raw, true );
	return is_array( $data ) ? $data : array();
}
