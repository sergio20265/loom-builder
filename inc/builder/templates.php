<?php
/**
 * Reusable templates: turn loom_template posts into site headers, footers and
 * insertable blocks, shown according to display conditions.
 *
 * Stored on each loom_template:
 *   _loom_template_type       : header | footer | block
 *   _loom_template_conditions : JSON array of rules (AND-combined)
 *   _loom_layout              : the builder layout (shared with the editor)
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─── Meta box ──────────────────────────────────────────────────────────── */

add_action( 'add_meta_boxes', 'loom_template_meta_boxes' );

/**
 * Register the template settings meta box.
 *
 * @return void
 */
function loom_template_meta_boxes() {
	add_meta_box( 'loom-template-settings', __( 'Template Settings', 'loom-builder' ), 'loom_template_meta_box', 'loom_template', 'side', 'high' );
}

/**
 * Render the type selector and the display-conditions builder.
 *
 * @param WP_Post $post Template post.
 * @return void
 */
function loom_template_meta_box( $post ) {
	wp_nonce_field( 'loom_template_save', 'loom_template_nonce' );

	$type       = get_post_meta( $post->ID, '_loom_template_type', true );
	$conditions = get_post_meta( $post->ID, '_loom_template_conditions', true );

	// Pre-select the type on a brand-new template opened from the editor's
	// "+ New header/footer" quick link (?loom_type=header|footer).
	if ( ! $type && 'auto-draft' === $post->post_status && isset( $_GET['loom_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = sanitize_key( wp_unslash( $_GET['loom_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $requested, array( 'header', 'footer' ), true ) ) {
			$type = $requested;
		}
	}
	$type = $type ? $type : 'block';

	$post_types = array();
	foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
		if ( 'loom_template' === $pt->name ) {
			continue;
		}
		$post_types[ $pt->name ] = $pt->label;
	}

	wp_enqueue_script( 'loom-template-admin', LOOM_URL . 'assets/js/templates-admin.js', array(), LOOM_VERSION, true );
	wp_enqueue_style( 'loom-acf-admin', LOOM_URL . 'assets/css/acf-admin.css', array(), LOOM_VERSION );
	wp_localize_script(
		'loom-template-admin',
		'LoomTpl',
		array(
			'conditions' => $conditions ? $conditions : '[]',
			'postTypes'  => $post_types,
		)
	);
	?>
	<p>
		<label for="loom_template_type"><strong><?php esc_html_e( 'Type', 'loom-builder' ); ?></strong></label><br>
		<select id="loom_template_type" name="loom_template_type" style="width:100%">
			<option value="block"<?php selected( $type, 'block' ); ?>><?php esc_html_e( 'Block / Section', 'loom-builder' ); ?></option>
			<option value="header"<?php selected( $type, 'header' ); ?>><?php esc_html_e( 'Header', 'loom-builder' ); ?></option>
			<option value="footer"<?php selected( $type, 'footer' ); ?>><?php esc_html_e( 'Footer', 'loom-builder' ); ?></option>
		</select>
	</p>
	<p><strong><?php esc_html_e( 'Display conditions', 'loom-builder' ); ?></strong></p>
	<div id="loom-tpl-conditions"></div>
	<input type="hidden" name="loom_template_conditions" id="loom_template_conditions" value="<?php echo esc_attr( $conditions ? $conditions : '[]' ); ?>">
	<p class="description"><?php esc_html_e( 'Header/footer templates render where conditions match. Blocks are inserted via the Template widget or [loom_template id="…"].', 'loom-builder' ); ?></p>
	<?php
}

add_action( 'save_post_loom_template', 'loom_template_save' );

/**
 * Persist template type and conditions.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function loom_template_save( $post_id ) {
	if ( ! isset( $_POST['loom_template_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['loom_template_nonce'] ) ), 'loom_template_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$type = isset( $_POST['loom_template_type'] ) ? sanitize_key( wp_unslash( $_POST['loom_template_type'] ) ) : 'block';
	if ( ! in_array( $type, array( 'block', 'header', 'footer' ), true ) ) {
		$type = 'block';
	}
	update_post_meta( $post_id, '_loom_template_type', $type );

	$raw  = isset( $_POST['loom_template_conditions'] ) ? wp_unslash( $_POST['loom_template_conditions'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidationSanitization.MissingUnslash
	$rows = json_decode( $raw, true );
	update_post_meta( $post_id, '_loom_template_conditions', wp_slash( wp_json_encode( loom_template_sanitize_conditions( is_array( $rows ) ? $rows : array() ) ) ) );
}

/**
 * Sanitize condition rules.
 *
 * @param array $rows Raw rows.
 * @return array
 */
function loom_template_sanitize_conditions( $rows ) {
	$params = array( 'entire_site', 'front_page', 'post_type', 'post', 'post_type_archive', 'is_404', 'search' );
	$out    = array();
	foreach ( $rows as $row ) {
		$param = isset( $row['param'] ) ? $row['param'] : '';
		if ( ! in_array( $param, $params, true ) ) {
			continue;
		}
		$out[] = array(
			'param'    => $param,
			'operator' => ( isset( $row['operator'] ) && '!=' === $row['operator'] ) ? '!=' : '==',
			'value'    => sanitize_text_field( isset( $row['value'] ) ? $row['value'] : '' ),
		);
	}
	return $out;
}

/* ─── Matching ──────────────────────────────────────────────────────────── */

/**
 * Evaluate a template's display conditions against the current request.
 *
 * @param array $conditions Rules (AND-combined).
 * @return bool
 */
function loom_template_matches( $conditions ) {
	if ( empty( $conditions ) ) {
		return false;
	}

	foreach ( $conditions as $rule ) {
		$param = $rule['param'];
		$op    = isset( $rule['operator'] ) ? $rule['operator'] : '==';
		$value = isset( $rule['value'] ) ? $rule['value'] : '';
		$match = true;

		switch ( $param ) {
			case 'entire_site':
				$match = true;
				break;
			case 'front_page':
				$match = is_front_page();
				break;
			case 'post_type':
				$match = is_singular() && get_post_type( get_queried_object_id() ) === $value;
				break;
			case 'post':
				$match = is_singular() && (int) get_queried_object_id() === (int) $value;
				break;
			case 'post_type_archive':
				$match = is_post_type_archive( $value );
				break;
			case 'is_404':
				$match = is_404();
				break;
			case 'search':
				$match = is_search();
				break;
			default:
				$match = false;
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
 * IDs of published templates of a type whose conditions match the request.
 *
 * @param string $type header|footer|block.
 * @return int[]
 */
function loom_get_active_templates( $type ) {
	$posts = get_posts(
		array(
			'post_type'      => 'loom_template',
			'post_status'    => 'publish',
			'numberposts'    => -1,
			'meta_key'       => '_loom_template_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $type, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'fields'         => 'ids',
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
		)
	);

	$active = array();
	foreach ( $posts as $id ) {
		$conditions = json_decode( (string) get_post_meta( $id, '_loom_template_conditions', true ), true );
		if ( loom_template_matches( is_array( $conditions ) ? $conditions : array() ) ) {
			$active[] = (int) $id;
		}
	}
	return $active;
}

/* ─── Rendering ─────────────────────────────────────────────────────────── */

/**
 * Render a single template's layout, guarding against self-recursion.
 *
 * @param int $template_id Template post ID.
 * @return string
 */
function loom_render_template( $template_id ) {
	static $stack = array();

	$template_id = (int) $template_id;
	if ( ! $template_id || in_array( $template_id, $stack, true ) ) {
		return '';
	}
	if ( 'loom_template' !== get_post_type( $template_id ) || 'publish' !== get_post_status( $template_id ) ) {
		return '';
	}

	$stack[] = $template_id;
	$html    = loom_render_post( $template_id );
	array_pop( $stack );

	return $html ? '<div class="loom-template loom-template-' . $template_id . '">' . $html . '</div>' : '';
}

/**
 * Print all active header/footer templates for a position.
 *
 * @param string $type header|footer.
 * @return void
 */
function loom_render_position( $type ) {
	foreach ( loom_get_active_templates( $type ) as $id ) {
		echo loom_render_template( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Theme helper: render Loom header templates (call in header.php).
 * Sets a flag so the wp_body_open fallback does not double-render.
 *
 * @return void
 */
function loom_header() {
	$GLOBALS['loom_header_done'] = true;
	loom_render_position( 'header' );
}

/**
 * Theme helper: render Loom footer templates (call in footer.php).
 *
 * @return void
 */
function loom_footer() {
	$GLOBALS['loom_footer_done'] = true;
	loom_render_position( 'footer' );
}

/**
 * Mark pages that are using Loom site templates.
 *
 * The fallback renderer injects Loom templates independently from the theme.
 * These classes let the frontend stylesheet suppress the corresponding theme
 * chrome for both block and classic themes, preventing duplicate site parts.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function loom_template_body_classes( $classes ) {
	if ( loom_get_active_templates( 'header' ) ) {
		$classes[] = 'loom-has-header-template';
	}
	if ( loom_get_active_templates( 'footer' ) ) {
		$classes[] = 'loom-has-footer-template';
	}
	return $classes;
}
add_filter( 'body_class', 'loom_template_body_classes' );

// Fallback injection for themes that do not call loom_header()/loom_footer().
add_action(
	'wp_body_open',
	static function () {
		if ( empty( $GLOBALS['loom_header_done'] ) ) {
			loom_render_position( 'header' );
		}
	},
	5
);
add_action(
	'wp_footer',
	static function () {
		if ( empty( $GLOBALS['loom_footer_done'] ) ) {
			loom_render_position( 'footer' );
		}
	},
	5
);

/* ─── Shortcode ─────────────────────────────────────────────────────────── */

add_shortcode( 'loom_template', 'loom_template_shortcode' );

/**
 * [loom_template id="123"] — insert a template anywhere.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function loom_template_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'loom_template' );
	return loom_render_template( (int) $atts['id'] );
}

/* ─── Editor support ────────────────────────────────────────────────────── */

/**
 * Block templates as id => title, for the Template widget select.
 *
 * @return array<string,string>
 */
function loom_block_template_choices() {
	$out   = array( '' => __( '— Select template —', 'loom-builder' ) );
	$posts = get_posts(
		array(
			'post_type'      => 'loom_template',
			'post_status'    => 'publish',
			'numberposts'    => -1,
			'meta_key'       => '_loom_template_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => 'block', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	foreach ( $posts as $post ) {
		$out[ (string) $post->ID ] = $post->post_title ? $post->post_title : ( '#' . $post->ID );
	}
	return $out;
}

add_action(
	'loom_register_widgets',
	static function ( $registry ) {
		$registry->register(
			array(
				'id'       => 'template',
				'title'    => __( 'Template', 'loom-builder' ),
				'icon'     => 'layout',
				'category' => 'layout',
				'controls' => array(
					'template_id' => array(
						'type'    => 'select',
						'label'   => __( 'Block template', 'loom-builder' ),
						'default' => '',
						'options' => loom_block_template_choices(),
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_template_widget',
			)
		);
	}
);

/**
 * Render the Template insert widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_template_widget( $s ) {
	$id = isset( $s['template_id'] ) ? (int) $s['template_id'] : 0;
	if ( ! $id ) {
		return '<div class="loom-template-empty">' . esc_html__( 'Select a block template.', 'loom-builder' ) . '</div>';
	}
	return loom_render_template( $id );
}
