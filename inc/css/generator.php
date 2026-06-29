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
	return array(
		'bgColor'       => static function ( $v ) {
			$v = loom_css_color( $v );
			return $v ? 'background-color:' . $v . ';' : '';
		},
		'bgImage'       => static function ( $v ) {
			$v = loom_css_url( $v );
			return $v ? 'background-image:url("' . $v . '");background-size:cover;background-position:center;' : '';
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
		'justify'       => static function ( $v ) {
			$v = loom_css_keyword( $v, array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly', 'start', 'end' ) );
			return $v ? 'justify-content:' . $v . ';' : '';
		},
		'valign'        => static function ( $v ) {
			$v = loom_css_keyword( $v, array( 'stretch', 'flex-start', 'center', 'flex-end', 'baseline', 'start', 'end' ) );
			return $v ? 'align-items:' . $v . ';' : '';
		},
		'padding'       => static function ( $v ) {
			return loom_css_box( 'padding', $v );
		},
		'margin'        => static function ( $v ) {
			return loom_css_box( 'margin', $v );
		},
	);
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
		return rtrim( rtrim( (string) $num, '0' ), '.' ) . 'px';
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
			$sel   = $prefix . '.loom-node-' . preg_replace( '/[^a-zA-Z0-9_-]/', '', $node['id'] );
			$style = isset( $node['settings']['_style'] ) ? $node['settings']['_style'] : array();

			$d = loom_css_declarations( isset( $style['desktop'] ) ? $style['desktop'] : array() );
			$t = loom_css_declarations( isset( $style['tablet'] ) ? $style['tablet'] : array() );
			$m = loom_css_declarations( isset( $style['mobile'] ) ? $style['mobile'] : array() );

			if ( $d ) {
				$desktop .= $sel . '{' . $d . '}';
			}
			if ( $t ) {
				$tablet .= $sel . '{' . $t . '}';
			}
			if ( $m ) {
				$mobile .= $sel . '{' . $m . '}';
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
