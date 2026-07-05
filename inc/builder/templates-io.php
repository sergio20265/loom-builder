<?php
/**
 * Export and import of Loom templates as portable JSON files.
 *
 * A template export bundles the title, type, display conditions and the layout
 * tree. Imports run the layout through the strict tree sanitizer and create a
 * fresh draft loom_template, so files from other sites are safe to load.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The capability required to create templates on this install.
 *
 * @return string
 */
function loom_templates_io_cap() {
	$pt = get_post_type_object( 'loom_template' );
	return ( $pt && isset( $pt->cap->create_posts ) ) ? $pt->cap->create_posts : 'edit_pages';
}

/* ─── Row action: Export ────────────────────────────────────────────────── */

/**
 * Add an "Export" row action to the loom_template list table.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Current post.
 * @return array
 */
function loom_template_export_row_action( $actions, $post ) {
	if ( 'loom_template' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
		return $actions;
	}
	$url                   = wp_nonce_url(
		admin_url( 'admin-post.php?action=loom_export_template&id=' . $post->ID ),
		'loom_export_template_' . $post->ID
	);
	$actions['loom_export'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Export', 'loom' ) . '</a>';
	return $actions;
}
add_filter( 'post_row_actions', 'loom_template_export_row_action', 20, 2 );

add_action( 'admin_post_loom_export_template', 'loom_handle_export_template' );

/**
 * Stream a single template as a JSON download.
 *
 * @return void
 */
function loom_handle_export_template() {
	$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( esc_html__( 'You cannot export this template.', 'loom' ) );
	}
	check_admin_referer( 'loom_export_template_' . $id );

	if ( 'loom_template' !== get_post_type( $id ) ) {
		wp_die( esc_html__( 'Not a Loom template.', 'loom' ) );
	}

	$post       = get_post( $id );
	$conditions = json_decode( (string) get_post_meta( $id, '_loom_template_conditions', true ), true );

	$payload = array(
		'_loom'      => 'template',
		'version'    => LOOM_VERSION,
		'title'      => $post->post_title,
		'type'       => get_post_meta( $id, '_loom_template_type', true ) ? get_post_meta( $id, '_loom_template_type', true ) : 'block',
		'conditions' => is_array( $conditions ) ? $conditions : array(),
		'layout'     => loom_get_layout( $id ),
	);

	$slug = sanitize_title( $post->post_title );
	$slug = $slug ? $slug : ( 'template-' . $id );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="loom-' . $slug . '.json"' );
	echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}

/* ─── Import UI + handler ───────────────────────────────────────────────── */

/**
 * Render the import form above the template list table.
 *
 * Uses admin_notices so the upload form is not nested inside the list table's
 * own form (which would break the multipart submission).
 *
 * @return void
 */
function loom_template_import_box() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-loom_template' !== $screen->id || ! current_user_can( loom_templates_io_cap() ) ) {
		return;
	}

	if ( isset( $_GET['loom_import'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = sanitize_key( wp_unslash( $_GET['loom_import'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'ok' === $status ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Template imported as a draft.', 'loom' ) . '</p></div>';
		} elseif ( 'error' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Could not import: the file is not a valid Loom template export.', 'loom' ) . '</p></div>';
		}
	}
	?>
	<div class="notice notice-info" style="padding:12px;">
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
			<input type="hidden" name="action" value="loom_import_template">
			<?php wp_nonce_field( 'loom_import_template' ); ?>
			<strong><?php esc_html_e( 'Import template', 'loom' ); ?></strong>
			<input type="file" name="loom_template_file" accept="application/json,.json" required>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'loom' ); ?></button>
		</form>
	</div>
	<?php
}
add_action( 'admin_notices', 'loom_template_import_box' );

add_action( 'admin_post_loom_import_template', 'loom_handle_import_template' );

/**
 * Validate an uploaded export file and create a draft template from it.
 *
 * @return void
 */
function loom_handle_import_template() {
	if ( ! current_user_can( loom_templates_io_cap() ) ) {
		wp_die( esc_html__( 'You cannot import templates.', 'loom' ) );
	}
	check_admin_referer( 'loom_import_template' );

	$redirect = admin_url( 'edit.php?post_type=loom_template' );

	if ( empty( $_FILES['loom_template_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['loom_template_file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		wp_safe_redirect( add_query_arg( 'loom_import', 'error', $redirect ) );
		exit;
	}

	$limits = loom_layout_limits();
	$size   = isset( $_FILES['loom_template_file']['size'] ) ? (int) $_FILES['loom_template_file']['size'] : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( $size > (int) $limits['max_import_bytes'] ) {
		wp_safe_redirect( add_query_arg( 'loom_import', 'error', $redirect ) );
		exit;
	}

	$raw  = file_get_contents( $_FILES['loom_template_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.ValidatedSanitizedInput
	$data = json_decode( (string) $raw, true );

	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) || ! isset( $data['_loom'] ) || 'template' !== $data['_loom'] ) {
		wp_safe_redirect( add_query_arg( 'loom_import', 'error', $redirect ) );
		exit;
	}

	$title = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : __( 'Imported template', 'loom' );
	$type  = isset( $data['type'] ) && in_array( $data['type'], array( 'block', 'header', 'footer' ), true ) ? $data['type'] : 'block';
	$raw_tree = isset( $data['layout'] ) && is_array( $data['layout'] ) ? $data['layout'] : array();
	$valid = loom_validate_tree_limits( $raw_tree );
	if ( is_wp_error( $valid ) ) {
		wp_safe_redirect( add_query_arg( 'loom_import', 'error', $redirect ) );
		exit;
	}
	$tree = loom_sanitize_tree( $raw_tree );
	$cond  = isset( $data['conditions'] ) && is_array( $data['conditions'] ) ? loom_template_sanitize_conditions( $data['conditions'] ) : array();

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'loom_template',
			'post_status' => 'draft',
			'post_title'  => $title,
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		wp_safe_redirect( add_query_arg( 'loom_import', 'error', $redirect ) );
		exit;
	}

	update_post_meta( $post_id, '_loom_layout', wp_slash( wp_json_encode( $tree ) ) );
	update_post_meta( $post_id, '_loom_enabled', 1 );
	update_post_meta( $post_id, '_loom_template_type', $type );
	update_post_meta( $post_id, '_loom_template_conditions', wp_slash( wp_json_encode( $cond ) ) );

	wp_safe_redirect( add_query_arg( 'loom_import', 'ok', $redirect ) );
	exit;
}
