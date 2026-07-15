<?php
/**
 * In-editor SEO audit for an individual post or page.
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Render the SEO Assistant below the per-post SEO fields.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function loom_seo_render_assistant( $post ) {
	$html    = loom_seo_assistant_page_html( $post );
	$issues  = loom_seo_assistant_issues( $post, $html );
	$metrics = loom_seo_assistant_metrics( $post, $html );
	?>
	<div class="loom-seo-assistant" style="margin-top:20px;padding-top:16px;border-top:1px solid #dcdcde;">
		<h3 style="margin:0 0 5px;"><?php esc_html_e( 'SEO Assistant', 'loom-builder' ); ?></h3>
		<p class="description" style="margin-top:0;"><?php esc_html_e( 'A quick audit of this page. It does not generate or change content.', 'loom-builder' ); ?></p>
		<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin:14px 0;">
			<?php foreach ( $metrics as $metric ) : ?>
				<div style="padding:9px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:3px;">
					<div style="font-size:11px;color:#50575e;"><?php echo esc_html( $metric['label'] ); ?></div>
					<strong style="display:block;margin-top:2px;color:<?php echo esc_attr( $metric['color'] ); ?>;"><?php echo esc_html( $metric['value'] ); ?></strong>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( empty( $issues ) ) : ?>
			<p style="margin:12px 0 0;color:#008a20;"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> <?php esc_html_e( 'No issues found in the checked items.', 'loom-builder' ); ?></p>
		<?php else : ?>
			<ul style="margin:12px 0 0;list-style:none;">
				<?php foreach ( $issues as $issue ) : ?>
					<li style="display:flex;gap:8px;margin:0 0 10px;">
						<span class="dashicons dashicons-warning" style="color:#d63638;" aria-hidden="true"></span>
						<span><?php echo wp_kses_post( $issue ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Collect actionable SEO checks for a post.
 *
 * @param WP_Post $post Current post.
 * @return string[] HTML-safe issue messages.
 */
function loom_seo_assistant_issues( $post, $html = null ) {
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$html   = null === $html ? loom_seo_assistant_page_html( $post ) : $html;
	$issues = array();

	$headings = loom_seo_assistant_headings( $html );
	$is_builder_page = function_exists( 'loom_is_enabled' ) && loom_is_enabled( $post->ID );
	if ( $is_builder_page && empty( $headings['counts']['h1'] ) ) {
		$issues[] = esc_html__( 'There is no H1 heading in the Loom page content.', 'loom-builder' );
	}
	if ( $headings['counts']['h1'] > 1 ) {
		$issues[] = sprintf(
			/* translators: %d: number of H1 headings. */
			esc_html__( 'There are %d H1 headings. Use one main H1 per page.', 'loom-builder' ),
			$headings['counts']['h1']
		);
	}
	if ( 0 === loom_seo_assistant_tag_count( $html, 'h2' ) ) {
		$issues[] = esc_html__( 'There is no H2 heading on this page.', 'loom-builder' );
	}
	if ( ! empty( $headings['jump'] ) ) {
		$issues[] = sprintf(
			/* translators: 1: previous heading level, 2: skipped-to heading level. */
			esc_html__( 'Heading hierarchy jumps from %1$s to %2$s. Do not skip levels.', 'loom-builder' ),
			$headings['jump'][0],
			$headings['jump'][1]
		);
	}

	$image_stats    = loom_seo_assistant_image_stats( $html );
	$duplicate_alts = $image_stats['duplicate'];
	if ( $image_stats['missing'] > 0 ) {
		$issues[] = sprintf(
			/* translators: %d: images without an alt attribute. */
			esc_html__( '%d images have no alt attribute.', 'loom-builder' ),
			$image_stats['missing']
		);
	}
	if ( ! empty( $duplicate_alts ) ) {
		$examples = array_slice( $duplicate_alts, 0, 2 );
		$issues[] = sprintf(
			/* translators: %s: duplicate alt text examples. */
			esc_html__( 'Image alt text is repeated: %s.', 'loom-builder' ),
			esc_html( implode( ', ', $examples ) )
		);
	}

	$title = get_post_meta( $post->ID, '_loom_seo_title', true );
	$title = $title ? $title : $post->post_title;
	$title_length = loom_seo_assistant_length( $title );
	if ( $title_length < 30 ) {
		$issues[] = sprintf(
			/* translators: %d: title length in characters. */
			esc_html__( 'The SEO title is too short (%d characters; aim for 30–60).', 'loom-builder' ),
			$title_length
		);
	} elseif ( $title_length > 60 ) {
		$issues[] = sprintf(
			/* translators: %d: title length in characters. */
			esc_html__( 'The SEO title is too long (%d characters; aim for 60 or fewer).', 'loom-builder' ),
			loom_seo_assistant_length( $title )
		);
	}

	$description = get_post_meta( $post->ID, '_loom_seo_desc', true );
	$description_length = loom_seo_assistant_length( $description );
	if ( 0 === $description_length ) {
		$issues[] = esc_html__( 'Meta description is missing.', 'loom-builder' );
	} elseif ( $description_length < 120 || $description_length > 160 ) {
		$issues[] = sprintf(
			/* translators: %d: meta description length in characters. */
			esc_html__( 'Meta description is %d characters; aim for 120–160.', 'loom-builder' ),
			$description_length
		);
	}

	$word_count = loom_seo_assistant_word_count( $html );
	if ( $word_count < 300 ) {
		$issues[] = sprintf(
			/* translators: %d: words in the page content. */
			esc_html__( 'The content is short (%d words). Add useful detail where it helps the reader.', 'loom-builder' ),
			$word_count
		);
	}

	$internal_links = loom_seo_assistant_internal_link_count( $html );
	if ( $internal_links < 2 ) {
		$issues[] = sprintf(
			/* translators: %d: internal links found on the page. */
			esc_html__( 'There are only %d internal links. Add relevant links to other pages.', 'loom-builder' ),
			$internal_links
		);
	}

	$competitors = loom_seo_assistant_competitors( $post, $title );
	if ( ! empty( $competitors ) ) {
		$links = array();
		foreach ( array_slice( $competitors, 0, 3 ) as $competitor ) {
			$links[] = '<a href="' . esc_url( get_edit_post_link( $competitor->ID ) ) . '">' . esc_html( get_the_title( $competitor ) ) . '</a>';
		}
		$issues[] = sprintf(
			/* translators: %s: links to potentially competing pages. */
			__( 'This page may compete with: %s.', 'loom-builder' ),
			implode( ', ', $links )
		);
	}

	return $issues;
}

/**
 * Return the headline SEO metrics displayed above the audit findings.
 *
 * @param WP_Post $post Current post.
 * @param string  $html Rendered page HTML.
 * @return array<int,array{label:string,value:string,color:string}>
 */
function loom_seo_assistant_metrics( $post, $html ) {
	$title       = get_post_meta( $post->ID, '_loom_seo_title', true );
	$title       = $title ? $title : $post->post_title;
	$description = get_post_meta( $post->ID, '_loom_seo_desc', true );
	$words       = loom_seo_assistant_word_count( $html );
	$minutes     = max( 1, (int) ceil( $words / 200 ) );
	$headings    = loom_seo_assistant_headings( $html );

	return array(
		array(
			'label' => __( 'SEO title', 'loom-builder' ),
			'value' => sprintf( '%d / 60', loom_seo_assistant_length( $title ) ),
			'color' => loom_seo_assistant_length( $title ) >= 30 && loom_seo_assistant_length( $title ) <= 60 ? '#008a20' : '#d63638',
		),
		array(
			'label' => __( 'Meta description', 'loom-builder' ),
			'value' => sprintf( '%d / 160', loom_seo_assistant_length( $description ) ),
			'color' => loom_seo_assistant_length( $description ) >= 120 && loom_seo_assistant_length( $description ) <= 160 ? '#008a20' : '#d63638',
		),
		array(
			'label' => __( 'Content', 'loom-builder' ),
			'value' => sprintf( _n( '%d word · %d min', '%d words · %d min', $words, 'loom-builder' ), $words, $minutes ),
			'color' => $words >= 300 ? '#008a20' : '#d63638',
		),
		array(
			'label' => __( 'Headings', 'loom-builder' ),
			'value' => sprintf( 'H1: %d · H2: %d · H3: %d', $headings['counts']['h1'], $headings['counts']['h2'], $headings['counts']['h3'] ),
			'color' => '#1d2327',
		),
		array(
			'label' => __( 'Internal links', 'loom-builder' ),
			'value' => (string) loom_seo_assistant_internal_link_count( $html ),
			'color' => loom_seo_assistant_internal_link_count( $html ) >= 2 ? '#008a20' : '#d63638',
		),
	);
}

/**
 * Get the HTML that visitors see, including the Loom layout when enabled.
 *
 * @param WP_Post $post Current post.
 * @return string
 */
function loom_seo_assistant_page_html( $post ) {
	if ( function_exists( 'loom_is_enabled' ) && loom_is_enabled( $post->ID ) && function_exists( 'loom_render_post' ) ) {
		return loom_render_post( $post->ID );
	}
	return do_shortcode( $post->post_content );
}

/**
 * Count a tag in HTML.
 *
 * @param string $html HTML to inspect.
 * @param string $tag  HTML tag name.
 * @return int
 */
function loom_seo_assistant_tag_count( $html, $tag ) {
	if ( ! class_exists( 'DOMDocument' ) ) {
		return preg_match_all( '/<' . preg_quote( $tag, '/' ) . '\\b/i', $html, $matches );
	}
	$dom = loom_seo_assistant_dom( $html );
	return $dom ? $dom->getElementsByTagName( $tag )->length : 0;
}

/**
 * Count headings and find the first skipped heading level.
 *
 * @param string $html HTML to inspect.
 * @return array{counts:array<string,int>,jump:array<int,string>}
 */
function loom_seo_assistant_headings( $html ) {
	$counts = array( 'h1' => 0, 'h2' => 0, 'h3' => 0, 'h4' => 0, 'h5' => 0, 'h6' => 0 );
	$levels = array();
	$dom    = loom_seo_assistant_dom( $html );
	if ( $dom ) {
		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query( '//h1 | //h2 | //h3 | //h4 | //h5 | //h6' );
		foreach ( $nodes as $node ) {
			$tag = strtolower( $node->nodeName );
			++$counts[ $tag ];
			$levels[] = (int) substr( $tag, 1 );
		}
	} elseif ( preg_match_all( '/<(h[1-6])\\b/i', $html, $matches ) ) {
		foreach ( $matches[1] as $tag ) {
			$tag = strtolower( $tag );
			++$counts[ $tag ];
			$levels[] = (int) substr( $tag, 1 );
		}
	}

	$previous = null;
	foreach ( $levels as $level ) {
		if ( null !== $previous && $level > $previous + 1 ) {
			return array( 'counts' => $counts, 'jump' => array( 'H' . $previous, 'H' . $level ) );
		}
		$previous = $level;
	}
	return array( 'counts' => $counts, 'jump' => array() );
}

/**
 * Get image-alt audit data. An explicitly empty alt is valid for a decorative
 * image, so only a missing attribute is considered an issue.
 *
 * @param string $html HTML to inspect.
 * @return array{missing:int,duplicate:string[]}
 */
function loom_seo_assistant_image_stats( $html ) {
	$alts = array();
	$missing = 0;
	$dom  = loom_seo_assistant_dom( $html );
	if ( $dom ) {
		foreach ( $dom->getElementsByTagName( 'img' ) as $image ) {
			if ( ! $image->hasAttribute( 'alt' ) ) {
				++$missing;
				continue;
			}
			$alt = trim( $image->getAttribute( 'alt' ) );
			if ( '' !== $alt ) {
				$alts[] = $alt;
			}
		}
	} elseif ( preg_match_all( '/<img\\b[^>]*>/i', $html, $images ) ) {
		foreach ( $images[0] as $image ) {
			if ( ! preg_match( '/\\balt=["\\\']([^"\\\']*)["\\\']/i', $image, $match ) ) {
				++$missing;
			} elseif ( '' !== trim( $match[1] ) ) {
				$alts[] = trim( $match[1] );
			}
		}
	}

	$counts = array_count_values( $alts );
	return array(
		'missing'   => $missing,
		'duplicate' => array_keys( array_filter( $counts, static function ( $count ) {
			return $count > 1;
		} ) ),
	);
}

/**
 * Count human-readable words in the rendered content.
 *
 * @param string $html HTML to inspect.
 * @return int
 */
function loom_seo_assistant_word_count( $html ) {
	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	return preg_match_all( '/[\\p{L}\\p{N}]+/u', $text, $matches );
}

/**
 * Count non-anchor links which lead to the current WordPress site.
 *
 * @param string $html HTML to inspect.
 * @return int
 */
function loom_seo_assistant_internal_link_count( $html ) {
	$hrefs = array();
	$dom   = loom_seo_assistant_dom( $html );
	if ( $dom ) {
		foreach ( $dom->getElementsByTagName( 'a' ) as $link ) {
			$hrefs[] = $link->getAttribute( 'href' );
		}
	} elseif ( preg_match_all( '/<a\\b[^>]*\\bhref=["\\\']([^"\\\']+)["\\\']/i', $html, $matches ) ) {
		$hrefs = $matches[1];
	}

	$host  = wp_parse_url( home_url(), PHP_URL_HOST );
	$count = 0;
	foreach ( array_unique( $hrefs ) as $href ) {
		$href = trim( $href );
		if ( '' === $href || 0 === strpos( $href, '#' ) || preg_match( '/^(?:mailto|tel|javascript):/i', $href ) ) {
			continue;
		}
		$link_host = wp_parse_url( $href, PHP_URL_HOST );
		if ( ! $link_host || strtolower( $link_host ) === strtolower( $host ) ) {
			++$count;
		}
	}
	return $count;
}

/**
 * Find pages with an identical or highly similar SEO title.
 *
 * Similarity is intentionally conservative: at least two significant title
 * words must overlap and cover 70% of the shorter title.
 *
 * @param WP_Post $post  Current post.
 * @param string  $title Current SEO title.
 * @return WP_Post[]
 */
function loom_seo_assistant_competitors( $post, $title ) {
	$words = loom_seo_assistant_title_words( $title );
	if ( empty( $words ) ) {
		return array();
	}
	$query = new WP_Query(
		array(
			'post_type'              => 'any',
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'post__not_in'           => array( $post->ID ),
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);
	$matches = array();
	foreach ( $query->posts as $candidate ) {
		$candidate_title = get_post_meta( $candidate->ID, '_loom_seo_title', true );
		$candidate_words = loom_seo_assistant_title_words( $candidate_title ? $candidate_title : $candidate->post_title );
		$common          = array_intersect( $words, $candidate_words );
		$shorter         = min( count( $words ), count( $candidate_words ) );
		if ( $words === $candidate_words || ( $shorter >= 2 && count( $common ) >= 2 && count( $common ) / $shorter >= 0.7 ) ) {
			$matches[] = $candidate;
		}
	}
	wp_reset_postdata();
	return $matches;
}

/**
 * Turn a title into unique significant words for comparison.
 *
 * @param string $title Title.
 * @return string[]
 */
function loom_seo_assistant_title_words( $title ) {
	$title = wp_strip_all_tags( $title );
	$title = function_exists( 'mb_strtolower' ) ? mb_strtolower( $title, 'UTF-8' ) : strtolower( $title );
	$title = remove_accents( $title );
	$words = preg_split( '/[^\\p{L}\\p{N}]+/u', $title, -1, PREG_SPLIT_NO_EMPTY );
	$words = array_filter( $words, static function ( $word ) {
		return loom_seo_assistant_length( $word ) > 2;
	} );
	return array_values( array_unique( $words ) );
}

/**
 * Create a DOM document without leaking markup warnings into wp-admin.
 *
 * @param string $html HTML to parse.
 * @return DOMDocument|null
 */
function loom_seo_assistant_dom( $html ) {
	if ( ! class_exists( 'DOMDocument' ) || '' === trim( $html ) ) {
		return null;
	}
	$previous = libxml_use_internal_errors( true );
	$dom      = new DOMDocument();
	$loaded   = $dom->loadHTML( '<!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	return $loaded ? $dom : null;
}

/**
 * Calculate a string length in characters.
 *
 * @param string $value Text.
 * @return int
 */
function loom_seo_assistant_length( $value ) {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
}
