<?php
/**
 * REST API for the editor: load a layout, save a layout, render a preview.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'loom_register_rest_routes' );

/**
 * Register all /loom/v1 routes.
 *
 * @return void
 */
function loom_register_rest_routes() {

	$can_edit = static function ( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		return $post_id && current_user_can( 'edit_post', $post_id );
	};

	register_rest_route(
		'loom/v1',
		'/layout/(?P<id>\d+)',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'loom_rest_get_layout',
				'permission_callback' => $can_edit,
				'args'                => array(
					// A bare 'is_numeric' string would crash on PHP 8+: WP core calls
					// validate_callback with 3 args, but the builtin only accepts 1.
					'id' => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value );
						},
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'loom_rest_save_layout',
				'permission_callback' => $can_edit,
				'args'                => array(
					// A bare 'is_numeric' string would crash on PHP 8+: WP core calls
					// validate_callback with 3 args, but the builtin only accepts 1.
					'id' => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value );
						},
					),
				),
			),
		)
	);

	register_rest_route(
		'loom/v1',
		'/render',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'loom_rest_render',
			'permission_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_rest_route(
		'loom/v1',
		'/templates',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'loom_rest_get_templates',
			'permission_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}

/**
 * List header and footer templates for the editor's quick-access menu.
 *
 * @return WP_REST_Response
 */
function loom_rest_get_templates() {
	$out = array(
		'header' => array(),
		'footer' => array(),
	);

	foreach ( array_keys( $out ) as $type ) {
		$posts = get_posts(
			array(
				'post_type'      => 'loom_template',
				'post_status'    => array( 'publish', 'draft' ),
				'numberposts'    => -1,
				'meta_key'       => '_loom_template_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $type, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		foreach ( $posts as $post ) {
			$out[ $type ][] = array(
				'id'     => $post->ID,
				'title'  => $post->post_title ? $post->post_title : ( '#' . $post->ID ),
				'status' => $post->post_status,
			);
		}
	}

	return rest_ensure_response( $out );
}

/**
 * GET a post layout and the editor schema/config.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function loom_rest_get_layout( WP_REST_Request $request ) {
	$post_id = (int) $request['id'];
	return rest_ensure_response(
		array(
			'id'      => $post_id,
			'enabled' => loom_is_enabled( $post_id ),
			'tree'    => loom_get_layout( $post_id ),
		)
	);
}

/**
 * PUT/POST a post layout. Body: { tree: [...], enabled: bool }.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function loom_rest_save_layout( WP_REST_Request $request ) {
	$post_id = (int) $request['id'];
	$params  = $request->get_json_params();

	$tree    = isset( $params['tree'] ) && is_array( $params['tree'] ) ? $params['tree'] : array();
	$enabled = ! empty( $params['enabled'] );

	$valid = loom_validate_tree_limits( $tree );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}

	$tree = loom_sanitize_tree( $tree );

	update_post_meta( $post_id, '_loom_layout', wp_slash( wp_json_encode( $tree ) ) );
	update_post_meta( $post_id, '_loom_enabled', $enabled ? 1 : 0 );

	/**
	 * Fires after a valid Loom layout is stored through the editor REST API.
	 *
	 * Add-ons can retain revision snapshots or evaluate page budgets without
	 * duplicating the editor's security and sanitization path.
	 *
	 * @param int   $post_id Edited post id.
	 * @param array $tree    Sanitized layout tree.
	 * @param bool  $enabled Builder enabled state.
	 */
	do_action( 'loom_layout_saved', $post_id, $tree, $enabled );

	return rest_ensure_response(
		array(
			'saved'   => true,
			'enabled' => $enabled,
		)
	);
}

/**
 * Render an arbitrary tree to HTML+CSS for the live preview iframe.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function loom_rest_render( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	$tree   = isset( $params['tree'] ) && is_array( $params['tree'] ) ? $params['tree'] : array();
	$valid  = loom_validate_tree_limits( $tree );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}
	$tree = loom_sanitize_tree( $tree );

	return rest_ensure_response(
		array(
			'html' => loom_render_tree( $tree ),
			'css'  => loom_generate_css( $tree ),
		)
	);
}
