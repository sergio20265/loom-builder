<?php
/**
 * Frontend hardening toggles.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a hardening option is enabled.
 *
 * @param string $key Option key.
 * @return bool
 */
function loom_hardening_enabled( $key ) {
	return function_exists( 'loom_seo_get' ) && (bool) loom_seo_get( $key, 0 );
}

/**
 * Hide the admin bar on the public site when enabled.
 */
add_filter(
	'show_admin_bar',
	static function ( $show ) {
		if ( ! is_admin() && loom_hardening_enabled( 'hide_admin_bar' ) ) {
			return false;
		}
		return $show;
	}
);

/**
 * Restrict REST API access for logged-out visitors when enabled.
 */
add_filter(
	'rest_authentication_errors',
	static function ( $result ) {
		if ( ! empty( $result ) || is_user_logged_in() || ! loom_hardening_enabled( 'disable_rest' ) ) {
			return $result;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'REST API access is disabled for logged-out visitors.', 'loom' ),
			array( 'status' => 401 )
		);
	}
);

/**
 * Remove common WordPress frontend extras when enabled.
 */
add_action(
	'init',
	static function () {
		if ( is_admin() || ! loom_hardening_enabled( 'cleanup_wp_head' ) ) {
			return;
		}

		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_print_scripts', 'print_emoji_detection_script' );
	}
);

/**
 * Dequeue oEmbed runtime script on the frontend when cleanup is enabled.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( loom_hardening_enabled( 'cleanup_wp_head' ) ) {
			wp_dequeue_script( 'wp-embed' );
			wp_deregister_script( 'wp-embed' );
		}
	},
	100
);

/**
 * Remove emoji CDN resource hints when cleanup is enabled.
 *
 * @param array  $urls          Resource hint URLs.
 * @param string $relation_type Relation type.
 * @return array
 */
function loom_cleanup_resource_hints( $urls, $relation_type ) {
	if ( 'dns-prefetch' !== $relation_type || ! loom_hardening_enabled( 'cleanup_wp_head' ) ) {
		return $urls;
	}

	return array_values(
		array_filter(
			$urls,
			static function ( $url ) {
				return false === strpos( (string) $url, 's.w.org' );
			}
		)
	);
}
add_filter( 'wp_resource_hints', 'loom_cleanup_resource_hints', 10, 2 );
