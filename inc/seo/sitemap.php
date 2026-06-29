<?php
/**
 * Native XML sitemap (index + per-post-type) and robots.txt integration.
 *
 * Endpoints (pretty permalinks):
 *   /sitemap.xml            — sitemap index
 *   /sitemap-<type>.xml     — URLs for one public post type
 * Fallback without pretty permalinks: /?loom_sitemap=index | <type>
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the Loom sitemap is enabled.
 *
 * @return bool
 */
function loom_sitemap_enabled() {
	return (bool) loom_seo_get( 'enable_sitemap', 1 );
}

// Replace core sitemaps with ours when enabled.
add_filter(
	'wp_sitemaps_enabled',
	static function ( $enabled ) {
		return loom_sitemap_enabled() ? false : $enabled;
	}
);

add_filter( 'query_vars', 'loom_sitemap_query_var' );

/**
 * Register the sitemap query var.
 *
 * @param array $vars Query vars.
 * @return array
 */
function loom_sitemap_query_var( $vars ) {
	$vars[] = 'loom_sitemap';
	return $vars;
}

add_action( 'init', 'loom_sitemap_rewrite' );

/**
 * Register sitemap rewrite rules.
 *
 * @return void
 */
function loom_sitemap_rewrite() {
	if ( ! loom_sitemap_enabled() ) {
		return;
	}
	add_rewrite_rule( '^sitemap\.xml$', 'index.php?loom_sitemap=index', 'top' );
	add_rewrite_rule( '^sitemap-([a-z0-9_-]+)\.xml$', 'index.php?loom_sitemap=$matches[1]', 'top' );
}

add_action( 'template_redirect', 'loom_sitemap_render' );

/**
 * Render the requested sitemap and stop.
 *
 * @return void
 */
function loom_sitemap_render() {
	$which = get_query_var( 'loom_sitemap' );
	if ( ! $which || ! loom_sitemap_enabled() ) {
		return;
	}

	header( 'Content-Type: application/xml; charset=UTF-8' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

	if ( 'index' === $which ) {
		loom_sitemap_index();
	} else {
		loom_sitemap_type( sanitize_key( $which ) );
	}
	exit;
}

/**
 * Post types included in the sitemap.
 *
 * @return string[]
 */
function loom_sitemap_post_types() {
	$types = get_post_types( array( 'public' => true ), 'names' );
	unset( $types['attachment'] );
	/**
	 * Filter the post types listed in the Loom sitemap.
	 *
	 * @param string[] $types Post type slugs.
	 */
	return (array) apply_filters( 'loom_sitemap_post_types', array_values( $types ) );
}

/**
 * Output the sitemap index.
 *
 * @return void
 */
function loom_sitemap_index() {
	echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	foreach ( loom_sitemap_post_types() as $type ) {
		$count = (int) wp_count_posts( $type )->publish;
		if ( $count < 1 ) {
			continue;
		}
		$latest = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		$lastmod = $latest ? get_post_modified_time( 'c', true, $latest[0] ) : '';

		echo "\t<sitemap>\n";
		echo "\t\t<loc>" . esc_url( home_url( '/sitemap-' . $type . '.xml' ) ) . "</loc>\n";
		if ( $lastmod ) {
			echo "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
		}
		echo "\t</sitemap>\n";
	}
	echo '</sitemapindex>';
}

/**
 * Output the URL set for one post type.
 *
 * @param string $type Post type slug.
 * @return void
 */
function loom_sitemap_type( $type ) {
	if ( ! in_array( $type, loom_sitemap_post_types(), true ) ) {
		status_header( 404 );
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
		return;
	}

	$posts = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => 2000,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);

	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

	// Front page in the page sitemap.
	if ( 'page' === $type ) {
		echo "\t<url>\n\t\t<loc>" . esc_url( home_url( '/' ) ) . "</loc>\n\t\t<priority>1.0</priority>\n\t</url>\n";
	}

	foreach ( $posts as $post ) {
		if ( '1' === get_post_meta( $post->ID, '_loom_seo_noindex', true ) ) {
			continue;
		}
		echo "\t<url>\n";
		echo "\t\t<loc>" . esc_url( get_permalink( $post ) ) . "</loc>\n";
		echo "\t\t<lastmod>" . esc_html( get_post_modified_time( 'c', true, $post ) ) . "</lastmod>\n";
		echo "\t</url>\n";
	}

	echo '</urlset>';
}

add_filter( 'robots_txt', 'loom_seo_robots_txt', 10, 2 );

/**
 * Append the sitemap reference and any custom rules to robots.txt.
 *
 * @param string $output Robots.txt content.
 * @param bool   $public Whether the site is public.
 * @return string
 */
function loom_seo_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	if ( loom_sitemap_enabled() ) {
		$output .= "\nSitemap: " . home_url( '/sitemap.xml' ) . "\n";
	}
	$extra = loom_seo_get( 'robots_extra' );
	if ( $extra ) {
		$output .= "\n" . $extra . "\n";
	}
	return $output;
}
