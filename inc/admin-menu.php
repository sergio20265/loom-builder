<?php
/**
 * Single consolidated "Loom" admin menu, the editor launch points, and the
 * full-screen editor page host.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'loom_admin_menu' );

/**
 * Register the top-level menu and its subpages. CPTs attach themselves via
 * show_in_menu => 'loom-builder'.
 *
 * @return void
 */
function loom_admin_menu() {
	add_menu_page(
		__( 'Loom Builder', 'loom' ),
		__( 'Loom', 'loom' ),
		'edit_posts',
		'loom-builder',
		'loom_dashboard_page',
		'dashicons-screenoptions',
		3
	);

	add_submenu_page(
		'loom-builder',
		__( 'Loom Builder', 'loom' ),
		__( 'Dashboard', 'loom' ),
		'edit_posts',
		'loom-builder',
		'loom_dashboard_page'
	);

	add_submenu_page(
		'loom-builder',
		__( 'Settings', 'loom' ),
		__( 'Settings', 'loom' ),
		'manage_options',
		'loom-settings',
		'loom_settings_page'
	);

	// Full-screen editor host. The submenu entry must stay registered so the
	// admin page-access check resolves its hook name correctly; it is hidden
	// from the visible menu with CSS (see loom_admin_menu_css) rather than
	// remove_submenu_page(), which would break direct access to the page.
	add_submenu_page(
		'loom-builder',
		__( 'Edit with Loom', 'loom' ),
		__( 'Editor', 'loom' ),
		'edit_posts',
		'loom-editor',
		'loom_editor_page'
	);
}

/**
 * Hide the editor host item from the admin menu without unregistering it.
 *
 * @return void
 */
function loom_admin_menu_css() {
	echo '<style>#toplevel_page_loom-builder ul li a[href*="page=loom-editor"]{display:none;}</style>';
}
add_action( 'admin_head', 'loom_admin_menu_css' );

/**
 * Dashboard overview page.
 *
 * @return void
 */
function loom_dashboard_page() {
	$pages     = wp_count_posts( 'page' );
	$templates = wp_count_posts( 'loom_template' );
	?>
	<div class="wrap">
		<h1><span class="dashicons dashicons-screenoptions" style="font-size:30px;height:30px;width:30px;margin-right:8px;color:#2563eb;"></span><?php esc_html_e( 'Loom Builder', 'loom' ); ?></h1>
		<p style="color:#666;max-width:720px;"><?php esc_html_e( 'Visual page builder with a built-in SEO module. Open any page or post and click "Edit with Loom" to start building.', 'loom' ); ?></p>

		<div style="display:flex;gap:20px;margin-top:20px;flex-wrap:wrap;">
			<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px 24px;min-width:200px;">
				<h3 style="margin:0 0 6px;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.04em;"><?php esc_html_e( 'Pages', 'loom' ); ?></h3>
				<span style="font-size:34px;font-weight:700;color:#2563eb;"><?php echo (int) $pages->publish; ?></span>
				<div style="margin-top:12px;">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>" class="button"><?php esc_html_e( 'All pages', 'loom' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" class="button button-primary" style="margin-left:6px;">+ <?php esc_html_e( 'Page', 'loom' ); ?></a>
				</div>
			</div>
			<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px 24px;min-width:200px;">
				<h3 style="margin:0 0 6px;font-size:13px;color:#888;text-transform:uppercase;letter-spacing:.04em;"><?php esc_html_e( 'Templates', 'loom' ); ?></h3>
				<span style="font-size:34px;font-weight:700;color:#16a34a;"><?php echo (int) $templates->publish; ?></span>
				<div style="margin-top:12px;">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=loom_template' ) ); ?>" class="button"><?php esc_html_e( 'All templates', 'loom' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=loom_template' ) ); ?>" class="button button-primary" style="margin-left:6px;">+ <?php esc_html_e( 'Template', 'loom' ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Settings page: Builder and SEO tabs. Each tab renders from its own module.
 *
 * @return void
 */
function loom_settings_page() {
	$tabs = array( 'builder' => __( 'Builder', 'loom' ) );
	if ( function_exists( 'loom_code_render_tab' ) && current_user_can( 'unfiltered_html' ) ) {
		$tabs['code'] = __( 'Code & metrics', 'loom' );
	}
	if ( function_exists( 'loom_render_settings_page' ) ) {
		$tabs['seo'] = __( 'SEO', 'loom' );
	}

	$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'builder'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $tabs[ $active ] ) ) {
		$active = 'builder';
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Loom Settings', 'loom' ); ?></h1>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( $tabs as $slug => $label ) {
				$url = add_query_arg(
					array(
						'page' => 'loom-settings',
						'tab'  => $slug,
					),
					admin_url( 'admin.php' )
				);
				printf(
					'<a href="%s" class="nav-tab%s">%s</a>',
					esc_url( $url ),
					$active === $slug ? ' nav-tab-active' : '',
					esc_html( $label )
				);
			}
			?>
		</h2>
		<?php
		if ( 'seo' === $active && function_exists( 'loom_render_settings_page' ) ) {
			loom_render_settings_page();
		} elseif ( 'code' === $active && function_exists( 'loom_code_render_tab' ) ) {
			loom_code_render_tab();
		} elseif ( function_exists( 'loom_settings_render_tab' ) ) {
			loom_settings_render_tab();
		}
		?>
	</div>
	<?php
}

/**
 * Render the full-screen editor host and enqueue its assets.
 *
 * @return void
 */
function loom_editor_page() {
	$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You cannot edit this item.', 'loom' ) );
	}

	$config = loom_get_editor_config( $post_id );

	// WordPress ships these handles; no external dependencies are pulled in.
	wp_enqueue_media();
	wp_enqueue_style( 'wp-components' );
	wp_enqueue_style( 'loom-editor', LOOM_URL . 'assets/css/editor.css', array( 'wp-components' ), LOOM_VERSION );

	// The editor ships as ordered modules sharing the window.LoomEd namespace:
	// core first, then preview/controls, the Inspector and Canvas, the App last.
	$editor_modules = array( 'core', 'preview', 'controls', 'inspector', 'canvas' );
	$deps           = array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' );

	foreach ( $editor_modules as $module ) {
		$handle = 'loom-editor-' . $module;
		wp_enqueue_script(
			$handle,
			LOOM_URL . 'assets/js/editor/' . $module . '.js',
			$deps,
			LOOM_VERSION,
			true
		);
		$deps[] = $handle;
	}

	// The config rides on the first module; every later module reads it.
	wp_localize_script( 'loom-editor-core', 'LoomConfig', $config );

	/**
	 * Let add-ons enqueue editor modules after the base Inspector is available.
	 * They may append their handle through `loom_editor_app_dependencies` so the
	 * module is guaranteed to load before the App mounts.
	 *
	 * @param int $post_id Edited post ID.
	 */
	do_action( 'loom_editor_enqueue_assets', $post_id );

	$deps = (array) apply_filters( 'loom_editor_app_dependencies', $deps, $post_id );
	wp_enqueue_script(
		'loom-editor-app',
		LOOM_URL . 'assets/js/editor/app.js',
		$deps,
		LOOM_VERSION,
		true
	);

	echo '<div id="loom-editor-root" class="loom-editor-root"></div>';
}

/**
 * Add an "Edit with Loom" button to the post submit box.
 *
 * @return void
 */
function loom_edit_button_submitbox() {
	global $post;
	if ( ! $post || ! in_array( $post->post_type, loom_builder_post_types(), true ) ) {
		return;
	}
	$url = add_query_arg(
		array(
			'page' => 'loom-editor',
			'post' => $post->ID,
		),
		admin_url( 'admin.php' )
	);
	echo '<div style="padding:10px 0;text-align:center;">';
	echo '<a href="' . esc_url( $url ) . '" class="button button-primary button-hero" style="width:100%;text-align:center;background:#2563eb;border-color:#1d4ed8;">';
	echo '<span class="dashicons dashicons-screenoptions" style="margin:4px 4px 0 0;"></span>' . esc_html__( 'Edit with Loom', 'loom' );
	echo '</a></div>';
}
add_action( 'post_submitbox_misc_actions', 'loom_edit_button_submitbox' );

/**
 * Add an "Edit with Loom" row action on page/post/template list tables.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Current post.
 * @return array
 */
function loom_edit_row_action( $actions, $post ) {
	if ( in_array( $post->post_type, loom_builder_post_types(), true ) && current_user_can( 'edit_post', $post->ID ) ) {
		$url                  = add_query_arg(
			array(
				'page' => 'loom-editor',
				'post' => $post->ID,
			),
			admin_url( 'admin.php' )
		);
		$actions['loom_edit'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Edit with Loom', 'loom' ) . '</a>';
	}
	return $actions;
}
add_filter( 'page_row_actions', 'loom_edit_row_action', 10, 2 );
add_filter( 'post_row_actions', 'loom_edit_row_action', 10, 2 );

/**
 * Give the editor page a clean, full-screen body class.
 *
 * @param string $classes Space-separated admin body classes.
 * @return string
 */
function loom_editor_body_class( $classes ) {
	if ( isset( $_GET['page'] ) && 'loom-editor' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$classes .= ' loom-editor-fullscreen';
	}
	return $classes;
}
add_filter( 'admin_body_class', 'loom_editor_body_class' );
