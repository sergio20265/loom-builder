<?php
/**
 * Scoped, responsive CSS generator.
 *
 * Walks the layout tree and emits CSS rules scoped to each node by its id.
 * Style settings live in node.settings._style, keyed by device:
 *   _style: { desktop: {...props}, tablet: {...props}, mobile: {...props} }
 *
 * Supported props are mapped to real CSS declarations in loom_css_prop_map().
 *
 * @package Loom
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Breakpoints (max-width) for tablet and mobile. Desktop has no media query.
 *
 * @return array<string,int>
 */
function loom_breakpoints() {
	return (array) apply_filters(
		'loom_breakpoints',
		array(
			'tablet' => 1024,
			'mobile' => 767,
		)
	);
}

/**
 * Map of supported style props to a CSS emitter.
 * Each emitter returns a "prop: value;" string or '' to skip.
 *
 * @return array<string,callable>
 */
function loom_css_prop_map() {
	$map = array(
		'bgColor'       => static function ( $v ) {
			$v = loom_css_color( $v );
			return $v ? 'background-color:' . $v . ';' : '';
		},
		'bgImage'       => static function ( $v ) {
			// The bgSize sibling prop is folded in by loom_css_declarations()
			// (a single prop's emitter never sees its neighbours), so this
			// fallback only fires when bgImage is generated in isolation.
			return loom_css_bg_image( $v, 'cover' );
		},
		'bgSize'        => static function () {
			return ''; // Combined into bgImage's declaration, see loom_css_declarations().
		},
		'color'         => static function ( $v ) {
			$v = loom_css_color( $v );
			return $v ? 'color:' . $v . ';' : '';
		},
		'align'         => static function ( $v ) {
			$v = loom_css_keyword( $v, array( 'left', 'right', 'center', 'justify', 'start', 'end' ) );
			return $v ? 'text-align:' . $v . ';' : '';
		},
		'maxWidth'      => static function ( $v ) {
			$v = loom_css_length( $v, false );
			return $v ? 'max-width:' . $v . ';margin-left:auto;margin-right:auto;' : '';
		},
		'minHeight'     => static function ( $v ) {
			$v = loom_css_length( $v, false );
			return $v ? 'min-height:' . $v . ';' : '';
		},
		'radius'        => static function ( $v ) {
			$v = loom_css_length( $v, false );
			return $v ? 'border-radius:' . $v . ';' : '';
		},
		'fontSize'      => static function ( $v ) {
			$v = loom_css_length( $v, false );
			return $v ? 'font-size:' . $v . ';' : '';
		},
		'fontWeight'    => static function ( $v ) {
			$v = loom_css_font_weight( $v );
			return $v ? 'font-weight:' . $v . ';' : '';
		},
		'lineHeight'    => static function ( $v ) {
			$v = loom_css_line_height( $v );
			return $v ? 'line-height:' . $v . ';' : '';
		},
		'letterSpacing' => static function ( $v ) {
			$v = loom_css_length( $v, true );
			return $v ? 'letter-spacing:' . $v . ';' : '';
		},
		'width'         => static function ( $v ) {
			$v = loom_css_length( $v, true, array( 'auto' ) );
			return $v ? 'width:' . $v . ';' : '';
		},
		'gap'           => static function ( $v ) {
			$v = loom_css_length( $v, false );
			return $v ? 'gap:' . $v . ';' : '';
		},
		'flexGrow'      => static function ( $v ) {
			$v = is_numeric( $v ) ? (float) $v : -1;
			return $v >= 0 && $v <= 10 ? 'flex-grow:' . $v . ';' : '';
		},
		'flexShrink'    => static function ( $v ) {
			$v = is_numeric( $v ) ? (float) $v : -1;
			return $v >= 0 && $v <= 10 ? 'flex-shrink:' . $v . ';' : '';
		},
		'justify'       => static function ( $v ) {
			$v = loom_css_keyword( $v, array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly', 'start', 'end' ) );
			return $v ? 'justify-content:' . $v . ';' : '';
		},
		'valign'        => static function ( $v ) {
			$v = loom_css_keyword( $v, array( 'stretch', 'flex-start', 'center', 'flex-end', 'baseline', 'start', 'end' ) );
			return $v ? 'align-items:' . $v . ';' : '';
		},
		'direction'     => static function ( $v ) {
			$v = loom_css_keyword( $v, array( 'row', 'column' ) );
			return $v ? 'flex-direction:' . $v . ';' : '';
		},
		'padding'       => static function ( $v ) {
			return loom_css_box( 'padding', $v );
		},
		'margin'        => static function ( $v ) {
			return loom_css_box( 'margin', $v );
		},
	);

	/**
	 * Filters the supported responsive style properties.
	 *
	 * Add-ons may register non-CSS values too. Their emitters can return an
	 * empty string and consume the stored value while rendering markup.
	 *
	 * @param array<string,callable> $map Style property emitters.
	 */
	return apply_filters( 'loom_css_prop_map', $map );
}

/**
 * Sanitize a fallback scalar CSS token.
 *
 * @param mixed $v Raw value.
 * @return string
 */
function loom_css_safe( $v ) {
	return trim( preg_replace( '/[<>{};]/', '', (string) $v ) );
}

/**
 * Sanitize a CSS keyword against an allow-list.
 *
 * @param mixed $v       Raw value.
 * @param array $allowed Allowed keywords.
 * @return string
 */
function loom_css_keyword( $v, array $allowed ) {
	$v = strtolower( trim( (string) $v ) );
	return in_array( $v, $allowed, true ) ? $v : '';
}

/**
 * Sanitize a CSS color value.
 *
 * @param mixed $v Raw value.
 * @return string
 */
function loom_css_color( $v ) {
	$v = trim( (string) $v );
	if ( '' === $v ) {
		return '';
	}
	if ( sanitize_hex_color( $v ) ) {
		return sanitize_hex_color( $v );
	}
	if ( preg_match( '/^var\(--loom-pro-(?:primary|secondary|accent|surface|surface-alt|text|muted|border|on-primary)\)$/', $v ) ) {
		return $v;
	}
	if ( in_array( strtolower( $v ), array( 'transparent', 'currentcolor', 'inherit' ), true ) ) {
		return strtolower( $v );
	}
	if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $v ) ) {
		return $v;
	}
	return '';
}

/**
 * Sanitize a CSS length.
 *
 * @param mixed $v              Raw value.
 * @param bool  $allow_negative Whether negative values are allowed.
 * @param array $keywords       Extra allowed keywords.
 * @return string
 */
function loom_css_length( $v, $allow_negative = false, array $keywords = array() ) {
	$v = trim( (string) $v );
	if ( '' === $v ) {
		return '';
	}
	if ( in_array( strtolower( $v ), $keywords, true ) ) {
		return strtolower( $v );
	}
	if ( is_numeric( $v ) ) {
		$num = (float) $v;
		if ( ! $allow_negative && $num < 0 ) {
			return '';
		}
		// Trim trailing zeros from a *decimal* remainder only (10.50 -> 10.5) —
		// applying rtrim('0') to the whole string also ate whole numbers that
		// end in zero (10 -> 1, 100 -> 1), corrupting almost any round value.
		$str = (string) $num;
		if ( false !== strpos( $str, '.' ) ) {
			$str = rtrim( rtrim( $str, '0' ), '.' );
		}
		return $str . 'px';
	}
	$sign = $allow_negative ? '-?' : '';
	if ( preg_match( '/^' . $sign . '\d+(?:\.\d+)?(?:px|%|em|rem|vw|vh)$/', $v ) ) {
		return strtolower( $v );
	}
	return '';
}

/**
 * Sanitize a CSS line-height value.
 *
 * @param mixed $v Raw value.
 * @return string
 */
function loom_css_line_height( $v ) {
	$v = trim( (string) $v );
	if ( preg_match( '/^\d+(?:\.\d+)?$/', $v ) ) {
		return $v;
	}
	return loom_css_length( $v, false, array( 'normal' ) );
}

/**
 * Sanitize a CSS font-weight value.
 *
 * @param mixed $v Raw value.
 * @return string
 */
function loom_css_font_weight( $v ) {
	$v = strtolower( trim( (string) $v ) );
	if ( in_array( $v, array( 'normal', 'bold', 'bolder', 'lighter' ), true ) ) {
		return $v;
	}
	if ( preg_match( '/^[1-9]00$/', $v ) ) {
		return $v;
	}
	return '';
}

/**
 * Sanitize a URL for use inside a CSS url("") token.
 *
 * @param mixed $v Raw value.
 * @return string
 */
function loom_css_url( $v ) {
	$v = esc_url_raw( (string) $v );
	if ( ! $v ) {
		return '';
	}
	return str_replace( array( '\\', '"', "'", '(', ')', "\r", "\n" ), '', $v );
}

/**
 * Build the background-image declaration, folding in the bgSize sibling prop.
 *
 * @param mixed  $v            Raw image URL.
 * @param string $default_size Fallback size when bgSize is not set.
 * @return string
 */
function loom_css_bg_image( $v, $default_size = 'cover' ) {
	$v = loom_css_url( $v );
	if ( ! $v ) {
		return '';
	}
	$size = in_array( $default_size, array( 'cover', 'contain', 'auto' ), true ) ? $default_size : 'cover';
	return 'background-image:url("' . $v . '");background-size:' . $size . ';background-position:center;';
}

/**
 * Sanitize a 1-4 part CSS length shorthand.
 *
 * @param mixed $v Raw value.
 * @return string
 */
function loom_css_length_list( $v ) {
	$parts = preg_split( '/\s+/', trim( (string) $v ) );
	if ( ! $parts || count( $parts ) > 4 ) {
		return '';
	}
	$out = array();
	foreach ( $parts as $part ) {
		$part = loom_css_length( $part, false );
		if ( '' === $part ) {
			return '';
		}
		$out[] = $part;
	}
	return implode( ' ', $out );
}

/**
 * Emit a box shorthand (padding/margin) from a {t,r,b,l} object.
 *
 * @param string $prop CSS property name.
 * @param mixed  $v    Box object or scalar.
 * @return string
 */
function loom_css_box( $prop, $v ) {
	if ( ! is_array( $v ) ) {
		return '';
	}
	$t = isset( $v['t'] ) && '' !== $v['t'] ? (float) $v['t'] : 0;
	$r = isset( $v['r'] ) && '' !== $v['r'] ? (float) $v['r'] : 0;
	$b = isset( $v['b'] ) && '' !== $v['b'] ? (float) $v['b'] : 0;
	$l = isset( $v['l'] ) && '' !== $v['l'] ? (float) $v['l'] : 0;
	if ( ! $t && ! $r && ! $b && ! $l ) {
		return '';
	}
	return sprintf( '%s:%spx %spx %spx %spx;', $prop, $t, $r, $b, $l );
}

/**
 * Build a declaration block for one device from a props array.
 *
 * @param array $props Device props (key => value).
 * @return string Concatenated declarations.
 */
function loom_css_declarations( $props ) {
	if ( ! is_array( $props ) ) {
		return '';
	}
	$map = loom_css_prop_map();
	$out = '';
	foreach ( $props as $key => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}
		if ( 'bgSize' === $key ) {
			continue; // Folded into 'bgImage' below; not a standalone declaration.
		}
		if ( 'bgImage' === $key ) {
			$out .= loom_css_bg_image( $value, isset( $props['bgSize'] ) ? $props['bgSize'] : 'cover' );
			continue;
		}
		if ( isset( $map[ $key ] ) ) {
			$out .= call_user_func( $map[ $key ], $value );
		}
	}
	return $out;
}

/**
 * Generate the full CSS string for a layout tree.
 *
 * @param array  $tree   Layout nodes.
 * @param string $prefix Selector prefix scope (e.g. ".loom-doc-123 ").
 * @return string
 */
function loom_generate_css( array $tree, $prefix = '' ) {
	$bp      = loom_breakpoints();
	$desktop = '';
	$tablet  = '';
	$mobile  = '';

	$walk = static function ( $nodes ) use ( &$walk, &$desktop, &$tablet, &$mobile, $prefix ) {
		foreach ( $nodes as $node ) {
			if ( empty( $node['id'] ) ) {
				continue;
			}
			$sel      = $prefix . '.loom-node-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $node['id'] );
			$style    = isset( $node['settings']['_style'] ) ? $node['settings']['_style'] : array();
			$node_type = isset( $node['type'] ) ? $node['type'] : '';
			$is_section = 'section' === $node_type;
			$is_widget  = 'widget' === $node_type;

			// "maxWidth" on a section means the *content* width, not the outer
			// section box (which stays full-bleed for background color/image) —
			// it is redirected below, so exclude it from the generic emission.
			// A widget's "justify"/"valign" (content alignment) target its own
			// root element, not the outer .loom-node wrapper — also redirected.
			if ( $is_section ) {
				$skip = array( 'maxWidth' );
			} elseif ( $is_widget ) {
				$skip = array( 'justify', 'valign' );
			} else {
				$skip = array();
			}

			$d = loom_css_declarations( array_diff_key( isset( $style['desktop'] ) ? (array) $style['desktop'] : array(), array_flip( $skip ) ) );
			$t = loom_css_declarations( array_diff_key( isset( $style['tablet'] ) ? (array) $style['tablet'] : array(), array_flip( $skip ) ) );
			$m = loom_css_declarations( array_diff_key( isset( $style['mobile'] ) ? (array) $style['mobile'] : array(), array_flip( $skip ) ) );

			if ( $d ) {
				$desktop .= $sel . '{' . $d . '}';
			}
			if ( $t ) {
				$tablet .= $sel . '{' . $t . '}';
			}
			if ( $m ) {
				$mobile .= $sel . '{' . $m . '}';
			}

			// Layout values (and content width) apply to the section's inner flex
			// container, not the outer section element. Keeps desktop/tablet/mobile
			// behaviour in sync and lets the outer box stay full-bleed.
			if ( $is_section ) {
				$layout = array( 'gap', 'justify', 'valign', 'direction', 'maxWidth' );
				foreach ( array( 'desktop' => &$desktop, 'tablet' => &$tablet, 'mobile' => &$mobile ) as $device => &$target ) {
					$props = isset( $style[ $device ] ) && is_array( $style[ $device ] ) ? array_intersect_key( $style[ $device ], array_flip( $layout ) ) : array();
					$css   = loom_css_declarations( $props );
					if ( $css ) {
						$target .= $sel . '>.loom-section-inner{' . $css . '}';
					}
				}
				unset( $target );
			}

			// A column's "width" pins its flex-basis (the row default is an equal
			// 1 1 0 split), so it needs !important to beat that structural rule
			// regardless of selector specificity — same technique already used by
			// the mobile stacking override in frontend.css.
			if ( isset( $node['type'] ) && 'column' === $node['type'] ) {
				foreach ( array( 'desktop' => &$desktop, 'tablet' => &$tablet, 'mobile' => &$mobile ) as $device => &$target ) {
					$raw = isset( $style[ $device ]['width'] ) ? $style[ $device ]['width'] : '';
					$w   = loom_css_length( $raw, false );
					if ( $w ) {
						$target .= $sel . '{flex-basis:' . $w . ' !important;width:' . $w . ' !important;}';
					}
				}
				unset( $target );
			}

			// A widget's content alignment targets its own root element (the
			// widget's own markup, the single child of the .loom-node wrapper)
			// rather than the wrapper itself, which is never a flex container.
			if ( $is_widget ) {
				$layout = array( 'justify', 'valign' );
				foreach ( array( 'desktop' => &$desktop, 'tablet' => &$tablet, 'mobile' => &$mobile ) as $device => &$target ) {
					$props = isset( $style[ $device ] ) && is_array( $style[ $device ] ) ? array_intersect_key( $style[ $device ], array_flip( $layout ) ) : array();
					$css   = loom_css_declarations( $props );
					if ( $css ) {
						$target .= $sel . '>*{' . $css . '}';
					}
				}
				unset( $target );
			}

			if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
				$walk( $node['children'] );
			}
		}
	};

	$walk( $tree );

	$css = $desktop;
	if ( $tablet ) {
		$css .= sprintf( '@media(max-width:%dpx){%s}', $bp['tablet'], $tablet );
	}
	if ( $mobile ) {
		$css .= sprintf( '@media(max-width:%dpx){%s}', $bp['mobile'], $mobile );
	}

	return $css;
}
