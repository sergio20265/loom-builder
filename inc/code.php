<?php
/**
 * Global custom code and metrics injection.
 *
 * Stores site-wide head / body-open / footer snippets and custom CSS in the
 * option `loom_code`, and prints them on every front-end request. Intended for
 * analytics (Yandex.Metrica, Google Analytics, GTM), verification tags and
 * global styles. Editing is gated on the `unfiltered_html` capability.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default code blocks.
 *
 * @return array
 */
function loom_code_defaults() {
	return array(
		'head'   => '',
		'body'   => '',
		'footer' => '',
		'css'    => '',
	);
}

/**
 * Read all code blocks (merged with defaults).
 *
 * @return array
 */
function loom_code_all() {
	$saved = get_option( 'loom_code', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), loom_code_defaults() );
}

/**
 * Read one code block.
 *
 * @param string $key head|body|footer|css.
 * @return string
 */
function loom_code_get( $key ) {
	$all = loom_code_all();
	return isset( $all[ $key ] ) ? $all[ $key ] : '';
}

/* ─── Front-end output ──────────────────────────────────────────────────── */

/**
 * Print head snippet + custom CSS in <head>.
 *
 * @return void
 */
function loom_code_print_head() {
	$head = loom_code_get( 'head' );
	if ( '' !== trim( $head ) ) {
		echo "\n" . $head . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	$css = loom_code_get( 'css' );
	if ( '' !== trim( $css ) ) {
		echo '<style id="loom-custom-css">' . str_replace( array( '<', '>' ), array( '', '' ), $css ) . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'loom_code_print_head', 99 );

/**
 * Print body-open snippet (e.g. GTM noscript).
 *
 * @return void
 */
function loom_code_print_body() {
	$body = loom_code_get( 'body' );
	if ( '' !== trim( $body ) ) {
		echo "\n" . $body . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_body_open', 'loom_code_print_body', 1 );

/**
 * Print footer snippet before </body>.
 *
 * @return void
 */
function loom_code_print_footer() {
	$footer = loom_code_get( 'footer' );
	if ( '' !== trim( $footer ) ) {
		echo "\n" . $footer . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_footer', 'loom_code_print_footer', 99 );

/* ─── Settings tab ──────────────────────────────────────────────────────── */

/**
 * Whether the current user may edit global code.
 *
 * @return bool
 */
function loom_code_user_can() {
	return current_user_can( 'unfiltered_html' );
}

/**
 * Render the Code & metrics settings tab.
 *
 * @return void
 */
function loom_code_render_tab() {
	if ( ! loom_code_user_can() ) {
		echo '<p>' . esc_html__( 'You do not have permission to edit global code.', 'loom-builder' ) . '</p>';
		return;
	}

	if ( isset( $_POST['loom_code_save'] ) && check_admin_referer( 'loom_code_save', 'loom_code_nonce' ) ) {
		loom_code_handle_save();
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'loom-builder' ) . '</p></div>';
	}

	$c = loom_code_all();
	?>
	<form method="post">
		<?php wp_nonce_field( 'loom_code_save', 'loom_code_nonce' ); ?>
		<p class="description"><?php esc_html_e( 'Snippets are printed on every page of the site. Use for analytics, verification tags and global styles.', 'loom-builder' ); ?></p>

		<h2><?php esc_html_e( 'Header code (before </head>)', 'loom-builder' ); ?></h2>
		<textarea name="loom_code[head]" rows="6" class="large-text code" spellcheck="false" placeholder="<!-- Yandex.Metrica / Google Analytics / verification -->"><?php echo esc_textarea( $c['head'] ); ?></textarea>

		<h2><?php esc_html_e( 'Body code (after <body>)', 'loom-builder' ); ?></h2>
		<textarea name="loom_code[body]" rows="5" class="large-text code" spellcheck="false" placeholder="<!-- e.g. GTM noscript -->"><?php echo esc_textarea( $c['body'] ); ?></textarea>

		<h2><?php esc_html_e( 'Footer code (before </body>)', 'loom-builder' ); ?></h2>
		<textarea name="loom_code[footer]" rows="5" class="large-text code" spellcheck="false"><?php echo esc_textarea( $c['footer'] ); ?></textarea>

		<h2><?php esc_html_e( 'Custom CSS', 'loom-builder' ); ?></h2>
		<textarea name="loom_code[css]" rows="8" class="large-text code" spellcheck="false" placeholder=".my-class { color: #2563eb; }"><?php echo esc_textarea( $c['css'] ); ?></textarea>

		<p class="submit"><button type="submit" name="loom_code_save" value="1" class="button button-primary"><?php esc_html_e( 'Save settings', 'loom-builder' ); ?></button></p>
	</form>
	<?php
}

/**
 * Persist the code blocks. Raw markup is kept for users with unfiltered_html.
 *
 * @return void
 */
function loom_code_handle_save() {
	if ( ! loom_code_user_can() ) {
		return;
	}
	$in  = isset( $_POST['loom_code'] ) ? wp_unslash( $_POST['loom_code'] ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.MissingUnslash
	$out = array(
		'head'   => isset( $in['head'] ) ? (string) $in['head'] : '',
		'body'   => isset( $in['body'] ) ? (string) $in['body'] : '',
		'footer' => isset( $in['footer'] ) ? (string) $in['footer'] : '',
		'css'    => isset( $in['css'] ) ? str_replace( array( '<', '>' ), array( '', '' ), (string) $in['css'] ) : '',
	);
	update_option( 'loom_code', $out );
}
