<?php
/**
 * WooCommerce builder widgets: Products grid, Add to Cart, Product Filter.
 * Registered only when WooCommerce is active.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'loom_register_widgets',
	static function ( $registry ) {
		if ( ! loom_wc_active() ) {
			return;
		}

		$cats = loom_wc_category_choices();

		$registry->register(
			array(
				'id'       => 'products',
				'title'    => __( 'Products', 'loom-builder' ),
				'icon'     => 'cart',
				'category' => 'woocommerce',
				'controls' => array(
					'category'     => array( 'type' => 'select', 'label' => __( 'Category', 'loom-builder' ), 'default' => '', 'options' => $cats, 'section' => 'content' ),
					'count'        => array( 'type' => 'range', 'label' => __( 'Products', 'loom-builder' ), 'default' => 8, 'min' => 1, 'max' => 36, 'section' => 'content' ),
					'colsD'        => array( 'type' => 'range', 'label' => __( 'Columns: desktop', 'loom-builder' ), 'default' => 4, 'min' => 1, 'max' => 6, 'section' => 'content' ),
					'colsM'        => array( 'type' => 'range', 'label' => __( 'Columns: mobile', 'loom-builder' ), 'default' => 2, 'min' => 1, 'max' => 3, 'section' => 'content' ),
					'orderby'      => array(
						'type'    => 'select',
						'label'   => __( 'Order by', 'loom-builder' ),
						'default' => 'date',
						'options' => array(
							'date'       => __( 'Newest', 'loom-builder' ),
							'price'      => __( 'Price: low to high', 'loom-builder' ),
							'price-desc' => __( 'Price: high to low', 'loom-builder' ),
							'popularity' => __( 'Popularity', 'loom-builder' ),
							'rating'     => __( 'Rating', 'loom-builder' ),
							'rand'       => __( 'Random', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'onSale'       => array( 'type' => 'toggle', 'label' => __( 'On sale only', 'loom-builder' ), 'default' => false, 'section' => 'content' ),
					'featured'     => array( 'type' => 'toggle', 'label' => __( 'Featured only', 'loom-builder' ), 'default' => false, 'section' => 'content' ),
					'honorFilters' => array( 'type' => 'toggle', 'label' => __( 'Respect active filters', 'loom-builder' ), 'default' => false, 'section' => 'content' ),
				),
				'render'   => 'loom_render_products',
			)
		);

		$registry->register(
			array(
				'id'       => 'add_to_cart',
				'title'    => __( 'Add to Cart', 'loom-builder' ),
				'icon'     => 'cart',
				'category' => 'woocommerce',
				'controls' => array(
					'productId' => array( 'type' => 'number', 'label' => __( 'Product ID (blank = current)', 'loom-builder' ), 'default' => '', 'section' => 'content' ),
					'showPrice' => array( 'type' => 'toggle', 'label' => __( 'Show price', 'loom-builder' ), 'default' => true, 'section' => 'content' ),
				),
				'render'   => 'loom_render_add_to_cart',
			)
		);

		$registry->register(
			array(
				'id'       => 'product_filter',
				'title'    => __( 'Product Filter', 'loom-builder' ),
				'icon'     => 'filter',
				'category' => 'woocommerce',
				'controls' => array(
					'showCategories' => array( 'type' => 'toggle', 'label' => __( 'Categories', 'loom-builder' ), 'default' => true, 'section' => 'content' ),
					'showPrice'      => array( 'type' => 'toggle', 'label' => __( 'Price range', 'loom-builder' ), 'default' => true, 'section' => 'content' ),
					'attribute'      => array( 'type' => 'text', 'label' => __( 'Attribute taxonomy (e.g. pa_color)', 'loom-builder' ), 'default' => '', 'section' => 'content' ),
					'target'         => array(
						'type'    => 'select',
						'label'   => __( 'Submit to', 'loom-builder' ),
						'default' => 'shop',
						'options' => array(
							'shop'    => __( 'Shop page', 'loom-builder' ),
							'current' => __( 'Current page', 'loom-builder' ),
						),
						'section' => 'content',
					),
				),
				'render'   => 'loom_render_product_filter',
			)
		);
	}
);

/**
 * Build and run a product query, then render a responsive grid.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_products( $s ) {
	$count   = max( 1, (int) $s['count'] );
	$colsD   = max( 1, (int) $s['colsD'] );
	$colsM   = max( 1, (int) $s['colsM'] );
	$orderby = $s['orderby'];

	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'tax_query'           => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		'meta_query'          => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	);

	// Ordering.
	switch ( $orderby ) {
		case 'price':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['order']    = 'ASC';
			break;
		case 'price-desc':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['order']    = 'DESC';
			break;
		case 'popularity':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['order']    = 'DESC';
			break;
		case 'rating':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['order']    = 'DESC';
			break;
		case 'rand':
			$args['orderby'] = 'rand';
			break;
		default:
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
	}

	if ( ! empty( $s['category'] ) ) {
		$args['tax_query'][] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => sanitize_title( $s['category'] ) );
	}
	if ( ! empty( $s['featured'] ) ) {
		$args['tax_query'][] = array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'featured' );
	}
	if ( ! empty( $s['onSale'] ) ) {
		$args['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
	}

	// Respect active filters from a Product Filter widget / WC archive params.
	if ( ! empty( $s['honorFilters'] ) ) {
		$args['tax_query']  = loom_wc_term_filter( $args['tax_query'], 'product_cat', 'filter_cat' );
		$min = isset( $_GET['min_price'] ) ? (float) $_GET['min_price'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max = isset( $_GET['max_price'] ) ? (float) $_GET['max_price'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( null !== $min || null !== $max ) {
			$args['meta_query'][] = array(
				'key'     => '_price',
				'type'    => 'NUMERIC',
				'value'   => array( null !== $min ? $min : 0, null !== $max ? $max : PHP_INT_MAX ),
				'compare' => 'BETWEEN',
			);
		}
	}

	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return '<div class="loom-products-empty">' . esc_html__( 'No products found.', 'loom-builder' ) . '</div>';
	}

	$style = '--loom-pcols-d:' . $colsD . ';--loom-pcols-m:' . $colsM . ';';
	$out   = '<div class="loom-products" style="' . esc_attr( $style ) . '">';

	while ( $query->have_posts() ) {
		$query->the_post();
		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			continue;
		}
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		ob_start();
		woocommerce_template_loop_add_to_cart();
		$button = ob_get_clean();

		$out .= '<div class="loom-product">';
		$out .= '<a class="loom-product-thumb" href="' . esc_url( get_permalink() ) . '">' . $product->get_image( 'woocommerce_thumbnail' ) . '</a>';
		$out .= '<a class="loom-product-title" href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
		$out .= '<div class="loom-product-price">' . $product->get_price_html() . '</div>';
		$out .= '<div class="loom-product-cart">' . $button . '</div>';
		$out .= '</div>';
	}

	$out .= '</div>';
	wp_reset_postdata();

	return $out;
}

/**
 * Render the Add to Cart widget for a single product.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_add_to_cart( $s ) {
	$product_id = ! empty( $s['productId'] ) ? (int) $s['productId'] : get_the_ID();
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	if ( ! $product ) {
		return '<div class="loom-atc-empty">' . esc_html__( 'Product not found.', 'loom-builder' ) . '</div>';
	}

	$out = '<div class="loom-atc">';
	if ( ! empty( $s['showPrice'] ) ) {
		$out .= '<div class="loom-atc-price">' . $product->get_price_html() . '</div>';
	}
	$out .= '<a href="' . esc_url( $product->add_to_cart_url() ) . '" '
		. 'data-quantity="1" class="button loom-atc-btn add_to_cart_button ajax_add_to_cart" '
		. 'data-product_id="' . esc_attr( $product->get_id() ) . '" '
		. 'data-product_sku="' . esc_attr( $product->get_sku() ) . '" rel="nofollow">'
		. esc_html( $product->add_to_cart_text() ) . '</a>';
	$out .= '</div>';

	return $out;
}

/**
 * Render the Product Filter form (drives WC archives / Products widgets).
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_product_filter( $s ) {
	$action = 'shop' === $s['target'] && function_exists( 'wc_get_page_permalink' )
		? wc_get_page_permalink( 'shop' )
		: '';

	$out  = '<form class="loom-filter" method="get" action="' . esc_url( $action ) . '">';

	// Categories.
	if ( ! empty( $s['showCategories'] ) ) {
		$terms    = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
		$selected = isset( $_GET['filter_cat'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['filter_cat'] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_array( $terms ) && $terms ) {
			$out .= '<div class="loom-filter-group"><h4>' . esc_html__( 'Categories', 'loom-builder' ) . '</h4>';
			foreach ( $terms as $term ) {
				$checked = in_array( $term->slug, $selected, true ) ? ' checked' : '';
				$out    .= '<label class="loom-filter-opt"><input type="checkbox" name="filter_cat[]" value="' . esc_attr( $term->slug ) . '"' . $checked . '> ' . esc_html( $term->name ) . '</label>';
			}
			$out .= '</div>';
		}
	}

	// Attribute terms.
	if ( ! empty( $s['attribute'] ) ) {
		$tax = sanitize_key( $s['attribute'] );
		if ( taxonomy_exists( $tax ) ) {
			$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) );
			$selected = isset( $_GET[ 'filter_' . $tax ] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET[ 'filter_' . $tax ] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( is_array( $terms ) && $terms ) {
				$label = wc_attribute_label( $tax );
				$out  .= '<div class="loom-filter-group"><h4>' . esc_html( $label ) . '</h4>';
				foreach ( $terms as $term ) {
					$checked = in_array( $term->slug, $selected, true ) ? ' checked' : '';
					$out    .= '<label class="loom-filter-opt"><input type="checkbox" name="filter_' . esc_attr( $tax ) . '[]" value="' . esc_attr( $term->slug ) . '"' . $checked . '> ' . esc_html( $term->name ) . '</label>';
				}
				$out .= '</div>';
			}
		}
	}

	// Price range.
	if ( ! empty( $s['showPrice'] ) ) {
		$min = isset( $_GET['min_price'] ) ? (float) $_GET['min_price'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max = isset( $_GET['max_price'] ) ? (float) $_GET['max_price'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$out .= '<div class="loom-filter-group"><h4>' . esc_html__( 'Price', 'loom-builder' ) . '</h4><div class="loom-filter-price">';
		$out .= '<input type="number" name="min_price" placeholder="' . esc_attr__( 'Min', 'loom-builder' ) . '" value="' . esc_attr( $min ) . '">';
		$out .= '<input type="number" name="max_price" placeholder="' . esc_attr__( 'Max', 'loom-builder' ) . '" value="' . esc_attr( $max ) . '">';
		$out .= '</div></div>';
	}

	$out .= '<button type="submit" class="button loom-filter-submit">' . esc_html__( 'Apply filters', 'loom-builder' ) . '</button>';
	$out .= '</form>';

	return $out;
}
