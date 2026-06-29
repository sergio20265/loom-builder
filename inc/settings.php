<?php
/**
 * Global builder settings, stored in the single option `loom_settings`.
 *
 * Kept separate from the SEO option (`loom_seo`) so the two modules own their
 * own data. The settings page renders both as tabs (see admin-menu.php), and
 * the uninstall routine reads `delete_data` from here.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default global builder settings.
 *
 * @return array
 */
function loom_settings_defaults() {
	return array(
		'container_width'   => 1200,
		'breakpoint_tablet' => 1024,
		'breakpoint_mobile' => 767,
		'load_everywhere'   => 0,
		'delete_data'       => 0,
	);
}

/**
 * Read the full builder settings array (merged with defaults).
 *
 * @return array
 */
function loom_settings_all() {
	$saved = get_option( 'loom_settings', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), loom_settings_defaults() );
}

/**
 * Read one builder setting.
 *
 * @param string $key     Key.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function loom_settings_get( $key, $default = '' ) {
	$all = loom_settings_all();
	return isset( $all[ $key ] ) ? $all[ $key ] : $default;
}

/**
 * Override the responsive breakpoints from the saved settings.
 *
 * @param array $bp Default breakpoints.
 * @return array
 */
function loom_settings_filter_breakpoints( $bp ) {
	$s = loom_settings_all();
	if ( (int) $s['breakpoint_tablet'] > 0 ) {
		$bp['tablet'] = (int) $s['breakpoint_tablet'];
	}
	if ( (int) $s['breakpoint_mobile'] > 0 ) {
		$bp['mobile'] = (int) $s['breakpoint_mobile'];
	}
	return $bp;
}
add_filter( 'loom_breakpoints', 'loom_settings_filter_breakpoints' );

/**
 * Inject the container width as a CSS variable wherever the frontend loads.
 *
 * @return void
 */
function loom_settings_inline_css() {
	if ( ! wp_style_is( 'loom-frontend', 'enqueued' ) ) {
		return;
	}
	$width = (int) loom_settings_get( 'container_width', 1200 );
	if ( $width > 0 ) {
		wp_add_inline_style( 'loom-frontend', ':root{--loom-container-width:' . $width . 'px}' );
	}
}
add_action( 'wp_enqueue_scripts', 'loom_settings_inline_css', 101 );

/**
 * Render the Builder settings tab (called by the settings page).
 *
 * @return void
 */
function loom_settings_render_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['loom_settings_save'] ) && check_admin_referer( 'loom_settings_save', 'loom_settings_nonce' ) ) {
		loom_settings_handle_save();
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'loom' ) . '</p></div>';
	}

	$s = loom_settings_all();
	?>
	<form method="post">
		<?php wp_nonce_field( 'loom_settings_save', 'loom_settings_nonce' ); ?>

		<h2><?php esc_html_e( 'Layout', 'loom' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="container_width"><?php esc_html_e( 'Content width (px)', 'loom' ); ?></label></th>
				<td>
					<input type="number" id="container_width" name="loom_settings[container_width]" value="<?php echo esc_attr( $s['container_width'] ); ?>" class="small-text" min="320" max="2400">
					<p class="description"><?php esc_html_e( 'Maximum width of the centered section content area.', 'loom' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="breakpoint_tablet"><?php esc_html_e( 'Tablet breakpoint (px)', 'loom' ); ?></label></th>
				<td><input type="number" id="breakpoint_tablet" name="loom_settings[breakpoint_tablet]" value="<?php echo esc_attr( $s['breakpoint_tablet'] ); ?>" class="small-text" min="480" max="1600"></td>
			</tr>
			<tr>
				<th><label for="breakpoint_mobile"><?php esc_html_e( 'Mobile breakpoint (px)', 'loom' ); ?></label></th>
				<td><input type="number" id="breakpoint_mobile" name="loom_settings[breakpoint_mobile]" value="<?php echo esc_attr( $s['breakpoint_mobile'] ); ?>" class="small-text" min="320" max="1024"></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Assets', 'loom' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Frontend assets', 'loom' ); ?></th>
				<td>
					<label><input type="checkbox" name="loom_settings[load_everywhere]" value="1"<?php checked( $s['load_everywhere'], 1 ); ?>> <?php esc_html_e( 'Load builder CSS/JS on every page', 'loom' ); ?></label>
					<p class="description"><?php esc_html_e( 'Off by default: assets load only on builder pages and where a header/footer template is active.', 'loom' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Data', 'loom' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'On uninstall', 'loom' ); ?></th>
				<td>
					<label><input type="checkbox" name="loom_settings[delete_data]" value="1"<?php checked( $s['delete_data'], 1 ); ?>> <?php esc_html_e( 'Delete all Loom data when the plugin is deleted', 'loom' ); ?></label>
					<p class="description"><?php esc_html_e( 'Removes builder layouts, templates, SEO meta and options. This cannot be undone.', 'loom' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit"><button type="submit" name="loom_settings_save" value="1" class="button button-primary"><?php esc_html_e( 'Save settings', 'loom' ); ?></button></p>
	</form>
	<?php
}

/**
 * Sanitize and persist the builder settings form.
 *
 * @return void
 */
function loom_settings_handle_save() {
	$in  = isset( $_POST['loom_settings'] ) ? wp_unslash( $_POST['loom_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.MissingUnslash
	$def = loom_settings_defaults();
	$out = array();

	$out['container_width']   = max( 320, min( 2400, (int) ( isset( $in['container_width'] ) ? $in['container_width'] : $def['container_width'] ) ) );
	$out['breakpoint_tablet'] = max( 480, min( 1600, (int) ( isset( $in['breakpoint_tablet'] ) ? $in['breakpoint_tablet'] : $def['breakpoint_tablet'] ) ) );
	$out['breakpoint_mobile'] = max( 320, min( 1024, (int) ( isset( $in['breakpoint_mobile'] ) ? $in['breakpoint_mobile'] : $def['breakpoint_mobile'] ) ) );
	$out['load_everywhere']   = empty( $in['load_everywhere'] ) ? 0 : 1;
	$out['delete_data']       = empty( $in['delete_data'] ) ? 0 : 1;

	update_option( 'loom_settings', $out );
}
