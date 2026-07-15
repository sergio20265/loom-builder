<?php
/**
 * WooCommerce integration loader.
 *
 * Everything here is guarded by loom_wc_active(); the plugin works fine with no
 * WooCommerce installed. Provides product widgets, native faceted filters and
 * helpers shared with the editor config.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether WooCommerce is active.
 *
 * @return bool
 */
function loom_wc_active() {
	return class_exists( 'WooCommerce' );
}

if ( loom_wc_active() ) {
	require_once LOOM_INC . 'woocommerce/filters.php';
	require_once LOOM_INC . 'woocommerce/widgets.php';

	// Declare basic theme support so WC galleries/markup behave on any theme.
	add_action(
		'after_setup_theme',
		static function () {
			add_theme_support( 'woocommerce' );
		}
	);

	// Ensure WC cart/add-to-cart scripts load on builder pages that use products.
	add_action(
		'wp_enqueue_scripts',
		static function () {
			if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() ) ) {
				return;
			}
			$post_id = get_queried_object_id();
			if ( $post_id && function_exists( 'loom_is_enabled' ) && loom_is_enabled( $post_id ) ) {
				if ( function_exists( 'wc_enqueue_js' ) ) {
					wp_enqueue_script( 'wc-add-to-cart' );
				}
			}
		},
		20
	);
}

/**
 * Product categories as id => "Name (count)" for editor selects.
 *
 * @return array<string,string>
 */
function loom_wc_category_choices() {
	if ( ! loom_wc_active() ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => 200,
		)
	);
	$out = array( '' => __( 'All categories', 'loom-builder' ) );
	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			$out[ $term->slug ] = $term->name . ' (' . $term->count . ')';
		}
	}
	return $out;
}
