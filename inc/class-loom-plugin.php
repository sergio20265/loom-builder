<?php
/**
 * Plugin orchestrator. Loads modules and wires top-level hooks.
 *
 * @package Loom
 */

namespace Loom;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton that boots every Loom module in a deterministic order.
 */
final class Plugin {

	/**
	 * Single instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Guard against double boot.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Get the shared instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton).
	 */
	private function __construct() {}

	/**
	 * Load module files and register hooks. Safe to call once.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		// Procedural modules (each guards its own hooks).
		$modules = array(
			'post-types.php',
			'settings.php',
			'code.php',
			'builder/registry.php',
			'builder/render.php',
			'builder/sanitize.php',
			'builder/templates.php',
			'builder/templates-io.php',
			'builder/assets.php',
			'builder/rest.php',
			'css/generator.php',
			'acf/fields.php',
			'acf/api.php',
			'acf/group.php',
			'acf/meta-box.php',
			'woocommerce/woocommerce.php',
			'seo/settings.php',
			'seo/assistant.php',
			'hardening.php',
			'seo/meta.php',
			'seo/schema.php',
			'seo/sitemap.php',
			'admin-menu.php',
		);

		foreach ( $modules as $module ) {
			$path = LOOM_INC . $module;
			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}

		// Register Phase 1 widgets into the registry once it exists.
		add_action( 'init', array( $this, 'register_core_widgets' ), 5 );

		// Load translations.
		add_action(
			'init',
			static function () {
				load_plugin_textdomain( 'loom-builder', false, dirname( plugin_basename( LOOM_FILE ) ) . '/languages' );
			}
		);
	}

	/**
	 * Pull in the per-widget render callbacks shipped with the plugin.
	 *
	 * @return void
	 */
	public function register_core_widgets() {
		$dir = LOOM_INC . 'builder/widgets/';
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( glob( $dir . '*.php' ) as $file ) {
			require_once $file;
		}

		/**
		 * Fires after core widgets are loaded so add-ons can register more.
		 *
		 * @param \Loom\Builder\Registry $registry Widget registry instance.
		 */
		do_action( 'loom_register_widgets', \Loom\Builder\Registry::instance() );
	}
}
