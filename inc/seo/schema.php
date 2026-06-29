<?php
/**
 * JSON-LD structured data output. Builds a single @graph from the enabled
 * schema types and prints it in the head.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'loom_seo_schema', 5 );

/**
 * Assemble and print the JSON-LD graph.
 *
 * @return void
 */
function loom_seo_schema() {
	$graph = array();

	if ( loom_seo_get( 'schema_org' ) ) {
		$graph[] = loom_seo_schema_organization();
	}
	if ( loom_seo_get( 'schema_website' ) ) {
		$graph[] = loom_seo_schema_website();
	}
	if ( loom_seo_get( 'schema_breadcrumb' ) ) {
		$crumb = loom_seo_schema_breadcrumb();
		if ( $crumb ) {
			$graph[] = $crumb;
		}
	}
	if ( loom_seo_get( 'schema_article' ) && is_singular( 'post' ) ) {
		$graph[] = loom_seo_schema_article();
	}
	if ( loom_seo_get( 'schema_product' ) && function_exists( 'is_product' ) && is_product() ) {
		$product = loom_seo_schema_product();
		if ( $product ) {
			$graph[] = $product;
		}
	}

	$graph = array_filter( $graph );
	if ( empty( $graph ) ) {
		return;
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => array_values( $graph ),
	);

	echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}

/**
 * Organization (or Person) node.
 *
 * @return array
 */
function loom_seo_schema_organization() {
	$type = loom_seo_get( 'org_type', 'Organization' );
	$name = loom_seo_get( 'org_name' );
	$node = array(
		'@type' => $type,
		'@id'   => home_url( '/#organization' ),
		'name'  => $name ? $name : get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);

	$logo = (int) loom_seo_get( 'org_logo' );
	if ( $logo ) {
		$url = wp_get_attachment_image_url( $logo, 'full' );
		if ( $url ) {
			$node['logo']  = $url;
			$node['image'] = $url;
		}
	}

	$same = loom_seo_lines( loom_seo_get( 'social_profiles' ) );
	if ( $same ) {
		$node['sameAs'] = $same;
	}

	return $node;
}

/**
 * WebSite node with a search action.
 *
 * @return array
 */
function loom_seo_schema_website() {
	return array(
		'@type'           => 'WebSite',
		'@id'             => home_url( '/#website' ),
		'url'             => home_url( '/' ),
		'name'            => get_bloginfo( 'name' ),
		'description'     => get_bloginfo( 'description' ),
		'publisher'       => array( '@id' => home_url( '/#organization' ) ),
		'potentialAction' => array(
			array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		),
	);
}

/**
 * BreadcrumbList for the current request.
 *
 * @return array|null
 */
function loom_seo_schema_breadcrumb() {
	$items = array();
	$items[] = array( 'name' => __( 'Home', 'loom' ), 'url' => home_url( '/' ) );

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post && $post->post_parent ) {
			$ancestors = array_reverse( get_post_ancestors( $post ) );
			foreach ( $ancestors as $ancestor ) {
				$items[] = array( 'name' => get_the_title( $ancestor ), 'url' => get_permalink( $ancestor ) );
			}
		}
		$items[] = array( 'name' => get_the_title( $post ), 'url' => get_permalink( $post ) );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term    = get_queried_object();
		$items[] = array( 'name' => $term->name, 'url' => get_term_link( $term ) );
	} else {
		return null;
	}

	$list = array();
	foreach ( $items as $i => $item ) {
		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item['name'],
			'item'     => is_wp_error( $item['url'] ) ? home_url( '/' ) : $item['url'],
		);
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => loom_seo_current_canonical() . '#breadcrumb',
		'itemListElement' => $list,
	);
}

/**
 * Article node for single posts.
 *
 * @return array
 */
function loom_seo_schema_article() {
	$post  = get_queried_object();
	$image = loom_seo_current_image();

	$node = array(
		'@type'         => 'Article',
		'@id'           => get_permalink( $post ) . '#article',
		'headline'      => get_the_title( $post ),
		'datePublished' => get_the_date( 'c', $post ),
		'dateModified'  => get_the_modified_date( 'c', $post ),
		'author'        => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', $post->post_author ),
		),
		'publisher'     => array( '@id' => home_url( '/#organization' ) ),
		'mainEntityOfPage' => get_permalink( $post ),
	);
	if ( $image ) {
		$node['image'] = $image;
	}
	$desc = loom_seo_current_description();
	if ( $desc ) {
		$node['description'] = $desc;
	}
	return $node;
}

/**
 * Product node for WooCommerce single products.
 *
 * @return array|null
 */
function loom_seo_schema_product() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product ) {
		return null;
	}

	$node = array(
		'@type'       => 'Product',
		'@id'         => get_permalink( $product->get_id() ) . '#product',
		'name'        => $product->get_name(),
		'description' => wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ),
		'url'         => get_permalink( $product->get_id() ),
	);

	$sku = $product->get_sku();
	if ( $sku ) {
		$node['sku'] = $sku;
	}
	$image = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
	if ( $image ) {
		$node['image'] = $image;
	}

	$price = $product->get_price();
	if ( '' !== $price ) {
		$node['offers'] = array(
			'@type'         => 'Offer',
			'price'         => $price,
			'priceCurrency' => get_woocommerce_currency(),
			'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			'url'           => get_permalink( $product->get_id() ),
		);
	}

	if ( $product->get_review_count() > 0 ) {
		$node['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => $product->get_average_rating(),
			'reviewCount' => $product->get_review_count(),
		);
	}

	return $node;
}

/**
 * Split a textarea into a clean array of non-empty trimmed lines.
 *
 * @param string $text Multiline text.
 * @return array
 */
function loom_seo_lines( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$out[] = $line;
		}
	}
	return $out;
}
