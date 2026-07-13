<?php
/**
 * SEO settings store, the settings page (under the Loom menu) and the per-post
 * SEO meta box. Values live in the single option `loom_seo` and per-post meta.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default global SEO settings.
 *
 * @return array
 */
function loom_seo_defaults() {
	return array(
		'separator'         => '-',
		'home_title'        => '',
		'home_description'  => '',
		'org_type'          => 'Organization',
		'org_name'          => '',
		'org_logo'          => 0,
		'social_profiles'   => '',
		'default_og'        => 0,
		'twitter'           => '',
		'robots_extra'      => '',
		'enable_sitemap'    => 1,
		'schema_org'        => 1,
		'schema_website'    => 1,
		'schema_breadcrumb' => 1,
		'schema_article'    => 1,
		'schema_product'    => 1,
		'hide_admin_bar'    => 0,
		'disable_rest'      => 0,
		'cleanup_wp_head'   => 0,
	);
}

/**
 * Read the full settings array (merged with defaults).
 *
 * @return array
 */
function loom_seo_all() {
	$saved = get_option( 'loom_seo', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), loom_seo_defaults() );
}

/**
 * Read one setting.
 *
 * @param string $key     Key.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function loom_seo_get( $key, $default = '' ) {
	$all = loom_seo_all();
	return isset( $all[ $key ] ) ? $all[ $key ] : $default;
}

/**
 * Render the SEO settings page (hooked from the Loom Settings menu item).
 *
 * @return void
 */
function loom_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Save.
	if ( isset( $_POST['loom_seo_save'] ) && check_admin_referer( 'loom_seo_save', 'loom_seo_nonce' ) ) {
		loom_seo_handle_save();
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'loom' ) . '</p></div>';
	}

	$s = loom_seo_all();
	wp_enqueue_media();
	wp_enqueue_script( 'loom-acf-fields', LOOM_URL . 'assets/js/acf-fields.js', array(), LOOM_VERSION, true );
	wp_enqueue_style( 'loom-acf-admin', LOOM_URL . 'assets/css/acf-admin.css', array(), LOOM_VERSION );
	?>
		<form method="post">
			<?php wp_nonce_field( 'loom_seo_save', 'loom_seo_nonce' ); ?>

			<h2><?php esc_html_e( 'General', 'loom' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="separator"><?php esc_html_e( 'Title separator', 'loom' ); ?></label></th>
					<td><input type="text" id="separator" name="loom_seo[separator]" value="<?php echo esc_attr( $s['separator'] ); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th><label for="home_title"><?php esc_html_e( 'Home title', 'loom' ); ?></label></th>
					<td><input type="text" id="home_title" name="loom_seo[home_title]" value="<?php echo esc_attr( $s['home_title'] ); ?>" class="large-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td>
				</tr>
				<tr>
					<th><label for="home_description"><?php esc_html_e( 'Home description', 'loom' ); ?></label></th>
					<td><textarea id="home_description" name="loom_seo[home_description]" rows="2" class="large-text"><?php echo esc_textarea( $s['home_description'] ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="default_og"><?php esc_html_e( 'Default share image', 'loom' ); ?></label></th>
					<td><?php loom_seo_image_field( 'loom_seo[default_og]', $s['default_og'] ); ?></td>
				</tr>
				<tr>
					<th><label for="twitter"><?php esc_html_e( 'Twitter @username', 'loom' ); ?></label></th>
					<td><input type="text" id="twitter" name="loom_seo[twitter]" value="<?php echo esc_attr( $s['twitter'] ); ?>" class="regular-text" placeholder="@brand"></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Organization / Schema', 'loom' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Entity type', 'loom' ); ?></th>
					<td>
						<select name="loom_seo[org_type]">
							<option value="Organization" <?php selected( $s['org_type'], 'Organization' ); ?>><?php esc_html_e( 'Organization', 'loom' ); ?></option>
							<option value="Person" <?php selected( $s['org_type'], 'Person' ); ?>><?php esc_html_e( 'Person', 'loom' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="org_name"><?php esc_html_e( 'Name', 'loom' ); ?></label></th>
					<td><input type="text" id="org_name" name="loom_seo[org_name]" value="<?php echo esc_attr( $s['org_name'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Logo', 'loom' ); ?></th>
					<td><?php loom_seo_image_field( 'loom_seo[org_logo]', $s['org_logo'] ); ?></td>
				</tr>
				<tr>
					<th><label for="social_profiles"><?php esc_html_e( 'Social profile URLs', 'loom' ); ?></label></th>
					<td>
						<textarea id="social_profiles" name="loom_seo[social_profiles]" rows="4" class="large-text" placeholder="https://facebook.com/...&#10;https://instagram.com/..."><?php echo esc_textarea( $s['social_profiles'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One URL per line (used for sameAs).', 'loom' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Enabled schemas', 'loom' ); ?></th>
					<td>
						<?php
						$schemas = array(
							'schema_org'        => __( 'Organization / Person', 'loom' ),
							'schema_website'    => __( 'WebSite (+ search box)', 'loom' ),
							'schema_breadcrumb' => __( 'Breadcrumbs', 'loom' ),
							'schema_article'    => __( 'Article (posts)', 'loom' ),
							'schema_product'    => __( 'Product (WooCommerce)', 'loom' ),
						);
						foreach ( $schemas as $key => $label ) {
							echo '<label style="display:block;margin-bottom:4px"><input type="checkbox" name="loom_seo[' . esc_attr( $key ) . ']" value="1"' . checked( $s[ $key ], 1, false ) . '> ' . esc_html( $label ) . '</label>';
						}
						?>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Sitemap & Robots', 'loom' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'XML sitemap', 'loom' ); ?></th>
					<td>
						<label><input type="checkbox" name="loom_seo[enable_sitemap]" value="1"<?php checked( $s['enable_sitemap'], 1 ); ?>> <?php esc_html_e( 'Enable', 'loom' ); ?></label>
						<?php if ( $s['enable_sitemap'] ) : ?>
							<p class="description"><a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank"><?php echo esc_html( home_url( '/sitemap.xml' ) ); ?></a></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><label for="robots_extra"><?php esc_html_e( 'Extra robots.txt rules', 'loom' ); ?></label></th>
					<td><textarea id="robots_extra" name="loom_seo[robots_extra]" rows="4" class="large-text" placeholder="Disallow: /private/"><?php echo esc_textarea( $s['robots_extra'] ); ?></textarea></td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Frontend cleanup', 'loom' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Admin bar', 'loom' ); ?></th>
					<td>
						<label><input type="checkbox" name="loom_seo[hide_admin_bar]" value="1"<?php checked( $s['hide_admin_bar'], 1 ); ?>> <?php esc_html_e( 'Hide the admin bar on the frontend', 'loom' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'REST API', 'loom' ); ?></th>
					<td>
						<label><input type="checkbox" name="loom_seo[disable_rest]" value="1"<?php checked( $s['disable_rest'], 1 ); ?>> <?php esc_html_e( 'Disable REST API requests for logged-out visitors', 'loom' ); ?></label>
						<p class="description"><?php esc_html_e( 'Authenticated users and the Loom editor remain available.', 'loom' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'WordPress head output', 'loom' ); ?></th>
					<td>
						<label><input type="checkbox" name="loom_seo[cleanup_wp_head]" value="1"<?php checked( $s['cleanup_wp_head'], 1 ); ?>> <?php esc_html_e( 'Remove generator, shortlink, RSD, WLW, REST, oEmbed and emoji frontend extras', 'loom' ); ?></label>
					</td>
				</tr>
			</table>

			<p class="submit"><button type="submit" name="loom_seo_save" value="1" class="button button-primary"><?php esc_html_e( 'Save settings', 'loom' ); ?></button></p>
		</form>
	<?php
}

/**
 * Render a reusable image picker (shares acf-fields.js handlers).
 *
 * @param string $name  Field name attribute.
 * @param int    $value Attachment id.
 * @return void
 */
function loom_seo_image_field( $name, $value ) {
	$img = $value ? wp_get_attachment_image( (int) $value, 'thumbnail' ) : '';
	echo '<div class="loom-field-image" data-loom-image>';
	echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" class="loom-image-id">';
	echo '<div class="loom-image-thumb">' . $img . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<button type="button" class="button loom-image-pick">' . esc_html__( 'Select image', 'loom' ) . '</button> ';
	echo '<button type="button" class="button loom-image-clear">' . esc_html__( 'Remove', 'loom' ) . '</button>';
	echo '</div>';
}

/**
 * Sanitize and persist the settings form.
 *
 * @return void
 */
function loom_seo_handle_save() {
	$in  = isset( $_POST['loom_seo'] ) ? wp_unslash( $_POST['loom_seo'] ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.MissingUnslash
	$def = loom_seo_defaults();
	$out = array();

	$out['separator']        = sanitize_text_field( isset( $in['separator'] ) ? $in['separator'] : $def['separator'] );
	$out['home_title']       = sanitize_text_field( isset( $in['home_title'] ) ? $in['home_title'] : '' );
	$out['home_description']  = sanitize_textarea_field( isset( $in['home_description'] ) ? $in['home_description'] : '' );
	$out['org_type']         = in_array( ( isset( $in['org_type'] ) ? $in['org_type'] : '' ), array( 'Organization', 'Person' ), true ) ? $in['org_type'] : 'Organization';
	$out['org_name']         = sanitize_text_field( isset( $in['org_name'] ) ? $in['org_name'] : '' );
	$out['org_logo']         = (int) ( isset( $in['org_logo'] ) ? $in['org_logo'] : 0 );
	$out['social_profiles']  = sanitize_textarea_field( isset( $in['social_profiles'] ) ? $in['social_profiles'] : '' );
	$out['default_og']       = (int) ( isset( $in['default_og'] ) ? $in['default_og'] : 0 );
	$out['twitter']          = sanitize_text_field( isset( $in['twitter'] ) ? $in['twitter'] : '' );
	$out['robots_extra']     = sanitize_textarea_field( isset( $in['robots_extra'] ) ? $in['robots_extra'] : '' );
	$out['enable_sitemap']   = empty( $in['enable_sitemap'] ) ? 0 : 1;
	$out['hide_admin_bar']   = empty( $in['hide_admin_bar'] ) ? 0 : 1;
	$out['disable_rest']     = empty( $in['disable_rest'] ) ? 0 : 1;
	$out['cleanup_wp_head']  = empty( $in['cleanup_wp_head'] ) ? 0 : 1;

	foreach ( array( 'schema_org', 'schema_website', 'schema_breadcrumb', 'schema_article', 'schema_product' ) as $k ) {
		$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
	}

	update_option( 'loom_seo', $out );

	// Sitemap rewrite rules may need refreshing when toggled.
	loom_seo_maybe_flush();
}

/* ─── Per-post SEO meta box ─────────────────────────────────────────────── */

add_action( 'add_meta_boxes', 'loom_seo_add_meta_box' );

/**
 * Register the SEO meta box on public post types.
 *
 * @param string $post_type Current post type.
 * @return void
 */
function loom_seo_add_meta_box( $post_type ) {
	$pt = get_post_type_object( $post_type );
	if ( ! $pt || empty( $pt->public ) ) {
		return;
	}
	add_meta_box( 'loom-seo', __( 'Loom SEO', 'loom' ), 'loom_seo_render_meta_box', $post_type, 'normal', 'low' );
}

/**
 * Render the per-post SEO controls.
 *
 * @param WP_Post $post Post.
 * @return void
 */
function loom_seo_render_meta_box( $post ) {
	wp_nonce_field( 'loom_seo_meta', 'loom_seo_meta_nonce' );
	wp_enqueue_media();
	wp_enqueue_script( 'loom-acf-fields', LOOM_URL . 'assets/js/acf-fields.js', array(), LOOM_VERSION, true );
	wp_enqueue_style( 'loom-acf-admin', LOOM_URL . 'assets/css/acf-admin.css', array(), LOOM_VERSION );

	$title     = get_post_meta( $post->ID, '_loom_seo_title', true );
	$desc      = get_post_meta( $post->ID, '_loom_seo_desc', true );
	$canonical = get_post_meta( $post->ID, '_loom_seo_canonical', true );
	$noindex   = get_post_meta( $post->ID, '_loom_seo_noindex', true );
	$og        = get_post_meta( $post->ID, '_loom_seo_og', true );
	?>
	<div class="loom-values">
		<div class="loom-value-row">
			<label class="loom-value-label"><?php esc_html_e( 'SEO title', 'loom' ); ?></label>
			<div class="loom-value-input"><input type="text" name="loom_seo_title" value="<?php echo esc_attr( $title ); ?>" class="widefat" placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>"></div>
		</div>
		<div class="loom-value-row">
			<label class="loom-value-label"><?php esc_html_e( 'Meta description', 'loom' ); ?></label>
			<div class="loom-value-input"><textarea name="loom_seo_desc" rows="3" class="widefat"><?php echo esc_textarea( $desc ); ?></textarea></div>
		</div>
		<div class="loom-value-row">
			<label class="loom-value-label"><?php esc_html_e( 'Canonical URL', 'loom' ); ?></label>
			<div class="loom-value-input"><input type="url" name="loom_seo_canonical" value="<?php echo esc_attr( $canonical ); ?>" class="widefat" placeholder="<?php echo esc_attr( get_permalink( $post ) ); ?>"></div>
		</div>
		<div class="loom-value-row">
			<label class="loom-value-label"><?php esc_html_e( 'Share image', 'loom' ); ?></label>
			<div class="loom-value-input"><?php loom_seo_image_field( 'loom_seo_og', $og ); ?></div>
		</div>
		<div class="loom-value-row">
			<label class="loom-value-label"><?php esc_html_e( 'Indexing', 'loom' ); ?></label>
			<div class="loom-value-input"><label><input type="checkbox" name="loom_seo_noindex" value="1"<?php checked( $noindex, '1' ); ?>> <?php esc_html_e( 'Discourage search engines (noindex)', 'loom' ); ?></label></div>
		</div>
	</div>
	<?php loom_seo_render_assistant( $post ); ?>
	<?php
}

add_action( 'save_post', 'loom_seo_save_meta_box' );

/**
 * Persist per-post SEO meta.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function loom_seo_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['loom_seo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['loom_seo_meta_nonce'] ) ), 'loom_seo_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_loom_seo_title', sanitize_text_field( wp_unslash( isset( $_POST['loom_seo_title'] ) ? $_POST['loom_seo_title'] : '' ) ) );
	update_post_meta( $post_id, '_loom_seo_desc', sanitize_textarea_field( wp_unslash( isset( $_POST['loom_seo_desc'] ) ? $_POST['loom_seo_desc'] : '' ) ) );
	update_post_meta( $post_id, '_loom_seo_canonical', esc_url_raw( wp_unslash( isset( $_POST['loom_seo_canonical'] ) ? $_POST['loom_seo_canonical'] : '' ) ) );
	update_post_meta( $post_id, '_loom_seo_og', (int) ( isset( $_POST['loom_seo_og'] ) ? $_POST['loom_seo_og'] : 0 ) );
	update_post_meta( $post_id, '_loom_seo_noindex', empty( $_POST['loom_seo_noindex'] ) ? '' : '1' );
}

/**
 * Flush rewrite rules once after a sitemap toggle (guarded by a transient).
 *
 * @return void
 */
function loom_seo_maybe_flush() {
	flush_rewrite_rules( false );
}
