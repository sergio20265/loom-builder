<?php
/**
 * Widget registry: the single source of truth for every builder element.
 *
 * Each widget declares:
 *   - id, title, icon, category
 *   - a controls schema (consumed by the JS editor to build the Inspector)
 *   - a PHP render callback (used by the server renderer on the frontend)
 *
 * The schema is shared with the editor via loom_get_editor_config(), so the
 * Inspector UI and the rendered HTML never drift apart.
 *
 * @package Loom
 */

namespace Loom\Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton store of widget definitions.
 */
final class Registry {

	/**
	 * Instance.
	 *
	 * @var Registry|null
	 */
	private static $instance = null;

	/**
	 * Registered widgets keyed by id.
	 *
	 * @var array<string,array>
	 */
	private $widgets = array();

	/**
	 * Get the shared instance.
	 *
	 * @return Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register (or replace) a widget definition.
	 *
	 * @param array $args {
	 *     @type string   $id       Unique widget id (e.g. "heading").
	 *     @type string   $title    Human label.
	 *     @type string   $icon     Dashicon slug (without the dashicons- prefix).
	 *     @type string   $category Panel category: layout|basic|media.
	 *     @type array    $controls Control schema (see below).
	 *     @type callable $render   function( array $settings, array $node ): string.
	 * }
	 * @return void
	 */
	public function register( array $args ) {
		if ( empty( $args['id'] ) || empty( $args['render'] ) ) {
			return;
		}

		$this->widgets[ $args['id'] ] = wp_parse_args(
			$args,
			array(
				'title'    => $args['id'],
				'icon'     => 'block-default',
				'category' => 'basic',
				'controls' => array(),
				'render'   => null,
			)
		);
	}

	/**
	 * Whether a widget id is known.
	 *
	 * @param string $id Widget id.
	 * @return bool
	 */
	public function has( $id ) {
		return isset( $this->widgets[ $id ] );
	}

	/**
	 * Get one widget definition.
	 *
	 * @param string $id Widget id.
	 * @return array|null
	 */
	public function get( $id ) {
		return isset( $this->widgets[ $id ] ) ? $this->widgets[ $id ] : null;
	}

	/**
	 * All widget definitions.
	 *
	 * @return array<string,array>
	 */
	public function all() {
		return $this->widgets;
	}

	/**
	 * Default settings for a widget, derived from its control schema.
	 *
	 * @param string $id Widget id.
	 * @return array
	 */
	public function defaults( $id ) {
		$widget = $this->get( $id );
		if ( ! $widget ) {
			return array();
		}
		$defaults = array();
		foreach ( $widget['controls'] as $key => $control ) {
			$defaults[ $key ] = isset( $control['default'] ) ? $control['default'] : '';
		}
		return $defaults;
	}

	/**
	 * Render one widget node to HTML using its registered callback.
	 *
	 * @param string $id       Widget id.
	 * @param array  $settings Resolved settings.
	 * @param array  $node     Full node (id, children, etc.).
	 * @return string
	 */
	public function render( $id, array $settings, array $node ) {
		$widget = $this->get( $id );
		if ( ! $widget || ! is_callable( $widget['render'] ) ) {
			return '';
		}
		return (string) call_user_func( $widget['render'], $settings, $node );
	}

	/**
	 * Editor-facing schema: everything the JS needs, minus PHP callbacks.
	 *
	 * @return array<int,array>
	 */
	public function editor_schema() {
		$out = array();
		foreach ( $this->widgets as $id => $widget ) {
			$out[] = array(
				'id'       => $id,
				'title'    => $widget['title'],
				'icon'     => $widget['icon'],
				'category' => $widget['category'],
				'controls' => $widget['controls'],
				'defaults' => $this->defaults( $id ),
			);
		}
		return $out;
	}
}

/**
 * Convenience accessor.
 *
 * @return Registry
 */
function loom_registry() {
	return Registry::instance();
}
