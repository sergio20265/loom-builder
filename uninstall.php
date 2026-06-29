<?php
/**
 * Loom Builder uninstall routine.
 *
 * Runs only when the plugin is deleted from the admin. Data is removed only
 * when the site owner opted in via Settings -> Builder -> "Delete all Loom data".
 * Without that flag this routine is a no-op, so deactivate/reinstall keeps work.
 *
 * @package Loom
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$loom_settings = get_option( 'loom_settings', array() );
if ( empty( $loom_settings['delete_data'] ) ) {
	// Owner chose to keep data; leave everything in place.
	return;
}

global $wpdb;

// Delete plugin options.
delete_option( 'loom_settings' );
delete_option( 'loom_seo' );
delete_option( 'loom_code' );

// Delete every loom_template post (and its meta via wp_delete_post).
$loom_template_ids = get_posts(
	array(
		'post_type'      => 'loom_template',
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
		'suppress_filters' => true,
	)
);
foreach ( $loom_template_ids as $loom_id ) {
	wp_delete_post( (int) $loom_id, true );
}

// Delete every loom_field_group post (ACF builder) and its meta.
$loom_field_group_ids = get_posts(
	array(
		'post_type'      => 'loom_field_group',
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
		'suppress_filters' => true,
	)
);
foreach ( $loom_field_group_ids as $loom_id ) {
	wp_delete_post( (int) $loom_id, true );
}

// Remove all plugin post meta left on regular content (layouts, SEO, fields).
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_loom\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'loom\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Drop rewrite rules so the sitemap/template endpoints stop resolving.
flush_rewrite_rules();
