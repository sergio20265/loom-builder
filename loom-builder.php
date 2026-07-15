<?php
/**
 * Plugin Name:       Loom Builder
 * Plugin URI:        https://plugins.arvexa.ru/loom.html
 * Description:       Visual drag and drop page builder for WordPress with a built-in SEO module. Build landing pages and full sites without code. No third-party dependencies.
 * Version:           1.7.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Arvexa
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       loom-builder
 * Domain Path:       /languages
 *
 * @package Loom
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Core constants.
define( 'LOOM_VERSION', '1.7.0' );
define( 'LOOM_FILE', __FILE__ );
define( 'LOOM_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOOM_URL', plugin_dir_url( __FILE__ ) );
define( 'LOOM_INC', LOOM_DIR . 'inc/' );

/**
 * Minimal PSR-4-ish autoloader for the Loom\ namespace.
 * Maps Loom\Sub\ClassName -> inc/sub/class-classname.php (lowercased, dashed).
 */
spl_autoload_register(
	static function ( $class ) {
		if ( strpos( $class, 'Loom\\' ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( 'Loom\\' ) );
		$relative = str_replace( '\\', '/', $relative );

		$parts      = explode( '/', $relative );
		$class_name = array_pop( $parts );
		$class_file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
		$sub_path   = $parts ? strtolower( implode( '/', $parts ) ) . '/' : '';

		$path = LOOM_INC . $sub_path . $class_file;

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

/**
 * Bootstrap the plugin on plugins_loaded so other plugins/themes can hook in.
 */
require_once LOOM_INC . 'class-loom-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		\Loom\Plugin::instance()->boot();
	}
);

/**
 * Activation: register CPT then flush rewrite rules so template/sitemap
 * endpoints resolve immediately.
 */
register_activation_hook(
	__FILE__,
	static function () {
		require_once LOOM_INC . 'post-types.php';
		loom_register_post_types();
		// Register sitemap rewrite rules so /sitemap.xml resolves immediately.
		if ( function_exists( 'loom_sitemap_rewrite' ) ) {
			loom_sitemap_rewrite();
		}
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		flush_rewrite_rules();
	}
);
