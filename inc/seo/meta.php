<?php
/**
 * SEO head output: document title, meta description, canonical, robots and
 * Open Graph / Twitter Card tags.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ─── Title ─────────────────────────────────────────────────────────────── */

add_filter( 'pre_get_document_title', 'loom_seo_document_title', 20 );

/**
 * Compute the full <title>.
 *
 * @param string $title Incoming title.
 * @return string
 */
function loom_seo_document_title( $title ) {
	$computed = loom_seo_title_parts();
	return $computed ? $computed : $title;
}

/**
 * Build the title string for the current request.
 *
 * @return string
 */
function loom_seo_title_parts() {
	$sep  = loom_seo_get( 'separator', '-' );
	$site = get_bloginfo( 'name' );

	if ( is_front_page() ) {
		$home = loom_seo_get( 'home_title' );
		return $home ? $home : $site;
	}

	$base = '';
	if ( is_singular() ) {
		$custom = get_post_meta( get_queried_object_id(), '_loom_seo_title', true );
		$base   = $custom ? $custom : get_the_title( get_queried_object_id() );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$base = single_term_title( '', false );
	} elseif ( is_post_type_archive() ) {
		$base = post_type_archive_title( '', false );
	} elseif ( is_author() ) {
		$base = get_the_author_meta( 'display_name', get_queried_object_id() );
	} elseif ( is_search() ) {
		/* translators: %s: search query. */
		$base = sprintf( __( 'Search results for "%s"', 'loom-builder' ), get_search_query() );
	} elseif ( is_404() ) {
		$base = __( 'Page not found', 'loom-builder' );
	} elseif ( is_archive() ) {
		$base = get_the_archive_title();
	}

	$base = wp_strip_all_tags( $base );
	if ( ! $base ) {
		return '';
	}
	// Custom titles that already contain the separator are used verbatim.
	if ( is_singular() && get_post_meta( get_queried_object_id(), '_loom_seo_title', true ) ) {
		return $base;
	}
	return $base . ' ' . $sep . ' ' . $site;
}

/* ─── Head tags ─────────────────────────────────────────────────────────── */

// Replace core canonical with ours so per-post overrides win.
remove_action( 'wp_head', 'rel_canonical' );
add_action( 'wp_head', 'loom_seo_head', 1 );

/**
 * Print description, canonical, robots and social tags.
 *
 * @return void
 */
function loom_seo_head() {
	$desc      = loom_seo_current_description();
	$canonical = loom_seo_current_canonical();
	$image     = loom_seo_current_image();
	$title     = loom_seo_title_parts();
	$site      = get_bloginfo( 'name' );

	echo "\n<!-- Loom SEO -->\n";

	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . "\">\n";
	}
	if ( $canonical ) {
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . "\">\n";
	}

	// Open Graph.
	echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . "\">\n";
	echo '<meta property="og:type" content="' . ( is_singular() ? 'article' : 'website' ) . "\">\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . "\">\n";
	if ( $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . "\">\n";
	}
	if ( $canonical ) {
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . "\">\n";
	}
	echo '<meta property="og:site_name" content="' . esc_attr( $site ) . "\">\n";
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . "\">\n";
	}

	// Twitter.
	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . "\">\n";
	$twitter = loom_seo_get( 'twitter' );
	if ( $twitter ) {
		echo '<meta name="twitter:site" content="' . esc_attr( $twitter ) . "\">\n";
	}
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . "\">\n";
	if ( $desc ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . "\">\n";
	}
	if ( $image ) {
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . "\">\n";
	}

	echo "<!-- /Loom SEO -->\n";
}

add_filter( 'wp_robots', 'loom_seo_robots' );

/**
 * Add robots directives (noindex for search/404 and flagged posts).
 *
 * @param array $robots Robots directives.
 * @return array
 */
function loom_seo_robots( $robots ) {
	if ( is_search() || is_404() ) {
		$robots['noindex']  = true;
		$robots['follow']   = true;
		return $robots;
	}
	if ( is_singular() && '1' === get_post_meta( get_queried_object_id(), '_loom_seo_noindex', true ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}

/* ─── Computation helpers ───────────────────────────────────────────────── */

/**
 * The meta description for the current request.
 *
 * @return string
 */
function loom_seo_current_description() {
	if ( is_front_page() ) {
		$home = loom_seo_get( 'home_description' );
		return $home ? $home : get_bloginfo( 'description' );
	}
	if ( is_singular() ) {
		$custom = get_post_meta( get_queried_object_id(), '_loom_seo_desc', true );
		if ( $custom ) {
			return $custom;
		}
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$excerpt = $post->post_excerpt ? $post->post_excerpt : wp_strip_all_tags( $post->post_content );
			return wp_trim_words( $excerpt, 30, '' );
		}
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term && ! empty( $term->description ) ) {
			return wp_strip_all_tags( $term->description );
		}
	}
	return '';
}

/**
 * The canonical URL for the current request.
 *
 * @return string
 */
function loom_seo_current_canonical() {
	if ( is_singular() ) {
		$custom = get_post_meta( get_queried_object_id(), '_loom_seo_canonical', true );
		if ( $custom ) {
			return $custom;
		}
		return get_permalink( get_queried_object_id() );
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? '' : $link;
	}
	if ( is_post_type_archive() ) {
		$link = get_post_type_archive_link( get_queried_object()->name );
		return $link ? $link : '';
	}
	return '';
}

/**
 * The share image URL for the current request.
 *
 * @return string
 */
function loom_seo_current_image() {
	if ( is_singular() ) {
		$og = (int) get_post_meta( get_queried_object_id(), '_loom_seo_og', true );
		if ( $og ) {
			$url = wp_get_attachment_image_url( $og, 'large' );
			if ( $url ) {
				return $url;
			}
		}
		if ( has_post_thumbnail( get_queried_object_id() ) ) {
			$url = get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
			if ( $url ) {
				return $url;
			}
		}
	}
	$default = (int) loom_seo_get( 'default_og' );
	return $default ? (string) wp_get_attachment_image_url( $default, 'large' ) : '';
}
