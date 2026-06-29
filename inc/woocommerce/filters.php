<?php
/**
 * Native product filters (replaces third-party faceted-filter plugins).
 *
 * Handled GET parameters on WooCommerce archives:
 *   filter_cat    — comma-separated product_cat slugs
 *   filter_tag    — comma-separated product_tag slugs
 *   filter_pa_*   — comma-separated attribute term slugs (e.g. filter_pa_color)
 *   min_price     — minimum price
 *   max_price     — maximum price
 *   orderby       — WooCommerce native ordering
 *
 * Ported and generalized from the willowtales theme product-filter.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'pre_get_posts', 'loom_wc_apply_filters' );

/**
 * Apply Loom filter params to the main WooCommerce product query.
 *
 * @param WP_Query $query Query.
 * @return void
 */
function loom_wc_apply_filters( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) ) {
		return;
	}

	$tax_query  = (array) $query->get( 'tax_query' );
	$meta_query = (array) $query->get( 'meta_query' );

	// Category slugs.
	$tax_query = loom_wc_term_filter( $tax_query, 'product_cat', 'filter_cat' );
	// Tag slugs.
	$tax_query = loom_wc_term_filter( $tax_query, 'product_tag', 'filter_tag' );

	// Product attributes: any filter_pa_* param.
	foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( strpos( $key, 'filter_pa_' ) !== 0 ) {
			continue;
		}
		$taxonomy = sanitize_key( substr( $key, strlen( 'filter_' ) ) );
		$tax_query = loom_wc_term_filter( $tax_query, $taxonomy, $key );
	}

	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}
	if ( ! empty( $tax_query ) ) {
		$query->set( 'tax_query', $tax_query );
	}

	// Price range.
	$min = isset( $_GET['min_price'] ) ? (float) $_GET['min_price'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max = isset( $_GET['max_price'] ) ? (float) $_GET['max_price'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( null !== $min || null !== $max ) {
		$price = array( 'key' => '_price', 'type' => 'NUMERIC' );
		if ( null !== $min && null !== $max ) {
			$price['value']   = array( $min, $max );
			$price['compare'] = 'BETWEEN';
		} elseif ( null !== $min ) {
			$price['value']   = $min;
			$price['compare'] = '>=';
		} else {
			$price['value']   = $max;
			$price['compare'] = '<=';
		}
		$meta_query[] = $price;
		$query->set( 'meta_query', $meta_query );
	}
}

/**
 * Append a term-slug tax_query clause from a comma-separated GET param.
 *
 * @param array  $tax_query Existing tax query.
 * @param string $taxonomy  Taxonomy slug.
 * @param string $param     GET param name.
 * @return array
 */
function loom_wc_term_filter( $tax_query, $taxonomy, $param ) {
	if ( empty( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $tax_query;
	}
	// Accept both an array (checkbox[] form fields) and a comma-separated string.
	$raw = wp_unslash( $_GET[ $param ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidationSanitization.MissingUnslash
	$raw = is_array( $raw ) ? $raw : explode( ',', $raw );
	$slugs = array_filter( array_map( 'sanitize_title', $raw ) );
	if ( empty( $slugs ) ) {
		return $tax_query;
	}
	$tax_query[] = array(
		'taxonomy' => $taxonomy,
		'field'    => 'slug',
		'terms'    => $slugs,
		'operator' => 'IN',
	);
	return $tax_query;
}
