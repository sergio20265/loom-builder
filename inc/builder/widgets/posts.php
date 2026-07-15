<?php
/**
 * Dynamic posts grid widget. Queries posts and renders a responsive card grid.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public post types as slug => label for the editor select.
 *
 * @return array<string,string>
 */
function loom_post_type_choices() {
	$out   = array();
	$types = get_post_types( array( 'public' => true ), 'objects' );
	foreach ( $types as $type ) {
		if ( in_array( $type->name, array( 'attachment', 'loom_template' ), true ) ) {
			continue;
		}
		$out[ $type->name ] = $type->label;
	}
	return $out;
}

add_action(
	'loom_register_widgets',
	static function ( $registry ) {
		$registry->register(
			array(
				'id'       => 'posts',
				'title'    => __( 'Posts', 'loom-builder' ),
				'icon'     => 'admin-post',
				'category' => 'basic',
				'controls' => array(
					'postType'    => array(
						'type'    => 'select',
						'label'   => __( 'Post type', 'loom-builder' ),
						'default' => 'post',
						'options' => loom_post_type_choices(),
						'section' => 'content',
					),
					'category'    => array(
						'type'    => 'text',
						'label'   => __( 'Category slug (posts only)', 'loom-builder' ),
						'default' => '',
						'section' => 'content',
					),
					'count'       => array( 'type' => 'number', 'label' => __( 'Number of posts', 'loom-builder' ), 'default' => 6, 'section' => 'content' ),
					'colsD'       => array( 'type' => 'range', 'label' => __( 'Columns: desktop', 'loom-builder' ), 'default' => 3, 'min' => 1, 'max' => 6, 'section' => 'content' ),
					'colsT'       => array( 'type' => 'range', 'label' => __( 'Columns: tablet', 'loom-builder' ), 'default' => 2, 'min' => 1, 'max' => 4, 'section' => 'content' ),
					'colsM'       => array( 'type' => 'range', 'label' => __( 'Columns: mobile', 'loom-builder' ), 'default' => 1, 'min' => 1, 'max' => 3, 'section' => 'content' ),
					'orderby'     => array(
						'type'    => 'select',
						'label'   => __( 'Order by', 'loom-builder' ),
						'default' => 'date',
						'options' => array(
							'date'       => __( 'Newest', 'loom-builder' ),
							'title'      => __( 'Title', 'loom-builder' ),
							'menu_order' => __( 'Menu order', 'loom-builder' ),
							'rand'       => __( 'Random', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'order'       => array(
						'type'    => 'select',
						'label'   => __( 'Order', 'loom-builder' ),
						'default' => 'DESC',
						'options' => array(
							'DESC' => __( 'Descending', 'loom-builder' ),
							'ASC'  => __( 'Ascending', 'loom-builder' ),
						),
						'section' => 'content',
					),
					'showImage'   => array( 'type' => 'toggle', 'label' => __( 'Show image', 'loom-builder' ), 'default' => true, 'section' => 'content' ),
					'showDate'    => array( 'type' => 'toggle', 'label' => __( 'Show date', 'loom-builder' ), 'default' => true, 'section' => 'content' ),
					'showExcerpt' => array( 'type' => 'toggle', 'label' => __( 'Show excerpt', 'loom-builder' ), 'default' => true, 'section' => 'content' ),
					'excerptWords' => array( 'type' => 'number', 'label' => __( 'Excerpt words', 'loom-builder' ), 'default' => 20, 'section' => 'content' ),
					'gap'         => array( 'type' => 'range', 'label' => __( 'Gap (px)', 'loom-builder' ), 'default' => 24, 'min' => 0, 'max' => 60, 'section' => 'style' ),
				),
				'render'   => 'loom_render_posts',
			)
		);
	}
);

/**
 * Render the posts grid widget.
 *
 * @param array $s Settings.
 * @return string
 */
function loom_render_posts( $s ) {
	$post_type = isset( $s['postType'] ) ? sanitize_key( $s['postType'] ) : 'post';
	if ( ! post_type_exists( $post_type ) ) {
		$post_type = 'post';
	}

	$args = array(
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, (int) $s['count'] ),
		'orderby'             => in_array( $s['orderby'], array( 'date', 'title', 'menu_order', 'rand' ), true ) ? $s['orderby'] : 'date',
		'order'               => 'ASC' === $s['order'] ? 'ASC' : 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if ( 'post' === $post_type && ! empty( $s['category'] ) ) {
		$args['category_name'] = sanitize_title( $s['category'] );
	}

	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		return '<div class="loom-posts-empty">' . esc_html__( 'No posts found.', 'loom-builder' ) . '</div>';
	}

	$cols_d = max( 1, (int) $s['colsD'] );
	$cols_t = max( 1, (int) $s['colsT'] );
	$cols_m = max( 1, (int) $s['colsM'] );
	$gap    = (int) $s['gap'];
	$words  = max( 1, (int) $s['excerptWords'] );

	$style = sprintf(
		'--loom-cols-d:%d;--loom-cols-t:%d;--loom-cols-m:%d;--loom-gap:%dpx',
		$cols_d,
		$cols_t,
		$cols_m,
		$gap
	);

	$out = '<div class="loom-posts" style="' . esc_attr( $style ) . '">';

	while ( $query->have_posts() ) {
		$query->the_post();
		$permalink = get_permalink();

		$out .= '<article class="loom-post-card">';

		if ( ! empty( $s['showImage'] ) && has_post_thumbnail() ) {
			$out .= '<a class="loom-post-thumb" href="' . esc_url( $permalink ) . '">' . get_the_post_thumbnail( get_the_ID(), 'medium_large' ) . '</a>';
		}

		$out .= '<div class="loom-post-body">';
		$out .= '<h3 class="loom-post-title"><a href="' . esc_url( $permalink ) . '">' . esc_html( get_the_title() ) . '</a></h3>';

		if ( ! empty( $s['showDate'] ) ) {
			$out .= '<div class="loom-post-date">' . esc_html( get_the_date() ) . '</div>';
		}

		if ( ! empty( $s['showExcerpt'] ) ) {
			$excerpt = get_the_excerpt();
			$out    .= '<p class="loom-post-excerpt">' . esc_html( wp_trim_words( $excerpt, $words ) ) . '</p>';
		}

		$out .= '</div></article>';
	}

	$out .= '</div>';
	wp_reset_postdata();

	return $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
