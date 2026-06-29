<?php
/**
 * Navigation menu widget. Renders a registered WordPress menu, primarily for
 * header / footer templates.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registered nav menus as id => name for the editor select.
 *
 * @return array<string,string>
 */
function loom_menu_choices() {
	$out   = array( '' => __( '— Select menu —', 'loom' ) );
	$menus = wp_get_nav_menus();
	foreach ( $menus as $menu ) {
		$out[ (string) $menu->term_id ] = $menu->name;
	}
	return $out;
}

add_action(
	'loom_register_widgets',
	static function ( $registry ) {
		$registry->register(
			array(
				'id'       => 'menu',
				'title'    => __( 'Nav Menu', 'loom' ),
				'icon'     => 'menu',
				'category' => 'site',
				'controls' => array(
					'menu'   => array(
						'type'    => 'select',
						'label'   => __( 'Menu', 'loom' ),
						'default' => '',
						'options' => loom_menu_choices(),
						'section' => 'content',
					),
					'layout' => array(
						'type'    => 'select',
						'label'   => __( 'Layout', 'loom' ),
						'default' => 'horizontal',
						'options' => array(
							'horizontal' => __( 'Horizontal', 'loom' ),
							'vertical'   => __( 'Vertical', 'loom' ),
						),
						'section' => 'content',
					),
					'align'  => array(
						'type'    => 'select',
						'label'   => __( 'Alignment', 'loom' ),
						'default' => 'left',
						'options' => array(
							'left'   => __( 'Left', 'loom' ),
							'center' => __( 'Center', 'loom' ),
							'right'  => __( 'Right', 'loom' ),
						),
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_menu',
			)
		);
	}
);

/**
 * Render the nav menu widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_menu( $s ) {
	$menu_id = isset( $s['menu'] ) ? (int) $s['menu'] : 0;
	if ( ! $menu_id ) {
		return '<div class="loom-menu-empty">' . esc_html__( 'Select a menu.', 'loom' ) . '</div>';
	}

	$layout = in_array( isset( $s['layout'] ) ? $s['layout'] : '', array( 'horizontal', 'vertical' ), true ) ? $s['layout'] : 'horizontal';
	$align  = in_array( isset( $s['align'] ) ? $s['align'] : '', array( 'left', 'center', 'right' ), true ) ? $s['align'] : 'left';

	$nav = wp_nav_menu(
		array(
			'menu'        => $menu_id,
			'container'   => false,
			'echo'        => false,
			'fallback_cb' => false,
			'menu_class'  => 'loom-menu-list',
			'depth'       => 2,
		)
	);

	if ( ! $nav ) {
		return '';
	}

	return '<nav class="loom-menu loom-menu-' . esc_attr( $layout ) . ' loom-menu-align-' . esc_attr( $align ) . '">' . $nav . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
