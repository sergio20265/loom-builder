/**
 * Loom Builder editor — schema-driven controls.
 *
 * One component per control type declared in the widget registry, plus the
 * ContentControl dispatcher and the dynamic-field binding row. Exposed on
 * LoomEd.controls and LoomEd.ContentControl for the Inspector to consume.
 *
 * @package Loom
 */
( function () {
	'use strict';

	var L = window.LoomEd;
	if ( ! L ) { return; }

	var el = L.el, Fragment = L.Fragment, useState = L.useState, wp = L.wp, cfg = L.cfg, t = L.t, clone = L.clone;

	function Field( props ) {
		return el( 'label', { className: 'loom-field' }, el( 'span', { className: 'loom-field-label' }, props.label ), props.children );
	}

	function TextControl( p ) {
		return el( Field, { label: p.label }, el( 'input', { type: 'text', value: p.value || '', onChange: function ( e ) { p.onChange( e.target.value ); } } ) );
	}

	function TextareaControl( p ) {
		return el( Field, { label: p.label }, el( 'textarea', { rows: 5, value: p.value || '', onChange: function ( e ) { p.onChange( e.target.value ); } } ) );
	}

	function CodeControl( p ) {
		return el( Field, { label: p.label }, el( 'textarea', { className: 'loom-code', spellCheck: false, rows: 8, value: p.value || '', onChange: function ( e ) { p.onChange( e.target.value ); } } ) );
	}

	function NumberControl( p ) {
		return el( Field, { label: p.label }, el( 'input', { type: 'number', value: p.value === '' || p.value == null ? '' : p.value, onChange: function ( e ) { p.onChange( e.target.value === '' ? '' : Number( e.target.value ) ); } } ) );
	}

	function RangeControl( p ) {
		return el( Field, { label: p.label + ( p.value || p.value === 0 ? ' (' + p.value + ')' : '' ) }, el( 'input', { type: 'range', min: p.min != null ? p.min : 0, max: p.max != null ? p.max : 100, value: p.value || 0, onChange: function ( e ) { p.onChange( Number( e.target.value ) ); } } ) );
	}

	/**
	 * CSS dimension input for layout values. Unlike a range slider it does not
	 * hide useful responsive units behind an arbitrary upper bound.
	 */
	function DimensionControl( p ) {
		var raw = p.value == null ? '' : String( p.value ).trim();
		var match = raw.match( /^(-?(?:\d+|\d*\.\d+))(px|%|vw|vh|em|rem)$/i );
		var defaultUnit = ( p.units && p.units[ 0 ] ) || 'px';
		var number = match ? match[ 1 ] : ( raw && raw !== 'auto' ? raw : '' );
		var unit = match ? match[ 2 ].toLowerCase() : ( raw === 'auto' ? 'auto' : defaultUnit );
		var units = p.units || [ 'px', '%', 'vw', 'vh' ];
		var labels = {
			px: t.unitPx || 'px',
			'%': t.unitPercent || '%',
			vw: t.unitVw || 'vw',
			vh: t.unitVh || 'vh',
			em: 'em', rem: 'rem', auto: t.unitAuto || 'Auto'
		};

		function apply( nextNumber, nextUnit ) {
			if ( nextUnit === 'auto' ) { p.onChange( 'auto' ); return; }
			if ( nextNumber === '' || nextNumber == null ) { p.onChange( '' ); return; }
			p.onChange( String( nextNumber ) + nextUnit );
		}

		return el( Field, { label: p.label },
			el( 'span', { className: 'loom-dimension' },
				el( 'input', { type: 'number', step: '0.1', value: number, placeholder: '0', disabled: unit === 'auto', onChange: function ( e ) { apply( e.target.value, unit ); } } ),
				el( 'select', { value: unit, onChange: function ( e ) { apply( number, e.target.value ); } },
					units.map( function ( key ) { return el( 'option', { key: key, value: key }, labels[ key ] || key ); } )
				),
				raw ? el( 'button', { type: 'button', className: 'loom-clear', title: t.clear || 'Clear', onClick: function () { p.onChange( '' ); } }, '×' ) : null
			),
			p.presets && p.presets.length ? el( 'span', { className: 'loom-dimension-presets' }, p.presets.map( function ( preset ) {
				return el( 'button', { key: preset.value, type: 'button', onClick: function () { p.onChange( preset.value ); } }, preset.label || preset.value );
			} ) ) : null
		);
	}

	function ColorControl( p ) {
		return el( Field, { label: p.label }, el( 'span', { className: 'loom-color' },
			el( 'input', { type: 'color', value: p.value || '#000000', onChange: function ( e ) { p.onChange( e.target.value ); } } ),
			el( 'input', { type: 'text', value: p.value || '', placeholder: '#rrggbb', onChange: function ( e ) { p.onChange( e.target.value ); } } ),
			el( 'button', { type: 'button', className: 'loom-clear', onClick: function () { p.onChange( '' ); } }, '×' )
		) );
	}

	function SelectControl( p ) {
		var opts = p.options || {};
		return el( Field, { label: p.label }, el( 'select', { value: p.value || '', onChange: function ( e ) { p.onChange( e.target.value ); } },
			Object.keys( opts ).map( function ( k ) { return el( 'option', { key: k, value: k }, opts[ k ] ); } )
		) );
	}

	function ToggleControl( p ) {
		return el( 'label', { className: 'loom-field loom-toggle' },
			el( 'input', { type: 'checkbox', checked: !! p.value, onChange: function ( e ) { p.onChange( e.target.checked ); } } ),
			el( 'span', null, p.label )
		);
	}

	function UrlControl( p ) {
		return el( Field, { label: p.label }, el( 'input', { type: 'url', value: p.value || '', placeholder: 'https://', onChange: function ( e ) { p.onChange( e.target.value ); } } ) );
	}

	function BoxControl( p ) {
		var v = p.value || {};
		function set( k, val ) {
			var nv = Object.assign( {}, v ); nv[ k ] = val === '' ? '' : Number( val ); p.onChange( nv );
		}
		var fields = [ [ 't', 'T' ], [ 'r', 'R' ], [ 'b', 'B' ], [ 'l', 'L' ] ];
		return el( Field, { label: p.label }, el( 'span', { className: 'loom-box' },
			fields.map( function ( f ) {
				return el( 'input', { key: f[ 0 ], type: 'number', placeholder: f[ 1 ], value: v[ f[ 0 ] ] == null ? '' : v[ f[ 0 ] ], onChange: function ( e ) { set( f[ 0 ], e.target.value ); } } );
			} )
		) );
	}

	function MediaControl( p ) {
		function open() {
			var frame = wp.media( { title: t.selectMedia || 'Select image', multiple: false, library: { type: 'image' } } );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				p.onChange( { id: att.id, url: att.url, alt: att.alt } );
			} );
			frame.open();
		}
		return el( Field, { label: p.label }, el( 'span', { className: 'loom-media' },
			p.preview ? el( 'img', { src: p.preview, className: 'loom-media-thumb' } ) : null,
			el( 'button', { type: 'button', className: 'button', onClick: open }, t.selectMedia || 'Select image' ),
			p.value ? el( 'button', { type: 'button', className: 'loom-clear', onClick: function () { p.onClear ? p.onClear() : p.onChange( { id: 0, url: '', alt: '' } ); } }, '×' ) : null
		) );
	}

	// Single image stored as an object { id, url, alt } in the field value.
	function ImageObjControl( p ) {
		var v = p.value || {};
		function open() {
			var frame = wp.media( { title: t.selectMedia || 'Select image', multiple: false, library: { type: 'image' } } );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				p.onChange( { id: att.id, url: att.url, alt: att.alt } );
			} );
			frame.open();
		}
		return el( Field, { label: p.label }, el( 'span', { className: 'loom-media' },
			v.url ? el( 'img', { src: v.url, className: 'loom-media-thumb' } ) : null,
			el( 'button', { type: 'button', className: 'button', onClick: open }, t.selectMedia || 'Select image' ),
			v.url ? el( 'button', { type: 'button', className: 'loom-clear', onClick: function () { p.onChange( {} ); } }, '×' ) : null
		) );
	}

	// Multi-image picker stored as an array of { id, url, alt }.
	function GalleryControl( p ) {
		var list = Array.isArray( p.value ) ? p.value : [];
		function open() {
			var frame = wp.media( { title: p.label, multiple: 'add', library: { type: 'image' } } );
			frame.on( 'select', function () {
				var sel = frame.state().get( 'selection' ).toJSON();
				var add = sel.map( function ( a ) { return { id: a.id, url: a.url, alt: a.alt }; } );
				p.onChange( list.concat( add ) );
			} );
			frame.open();
		}
		function removeAt( i ) {
			var copyArr = list.slice(); copyArr.splice( i, 1 ); p.onChange( copyArr );
		}
		return el( Field, { label: p.label + ' (' + list.length + ')' },
			el( 'div', { className: 'loom-gallery-ctrl' },
				list.map( function ( im, i ) {
					return el( 'span', { key: i, className: 'loom-gthumb' },
						el( 'img', { src: im.url } ),
						el( 'button', { type: 'button', onClick: function () { removeAt( i ); } }, '×' )
					);
				} )
			),
			el( 'button', { type: 'button', className: 'button', onClick: open }, '+ ' + ( t.selectMedia || 'Add images' ) ),
			list.length ? el( 'button', { type: 'button', className: 'loom-clear', onClick: function () { p.onChange( [] ); } }, '×' ) : null
		);
	}

	// Repeater: array of rows, each a set of sub-fields.
	function RepeaterControl( p ) {
		var rows = Array.isArray( p.value ) ? p.value : [];
		var fields = p.control.fields || {};
		var openState = useState( rows.length ? 0 : -1 );
		var openIdx = openState[ 0 ], setOpen = openState[ 1 ];

		function newRow() {
			var r = {}; Object.keys( fields ).forEach( function ( k ) { r[ k ] = fields[ k ].default != null ? clone( fields[ k ].default ) : ''; } ); return r;
		}
		function setRow( i, key, val ) {
			var copyArr = clone( rows ); copyArr[ i ][ key ] = val; p.onChange( copyArr );
		}
		function add() { var copyArr = rows.concat( [ newRow() ] ); p.onChange( copyArr ); setOpen( copyArr.length - 1 ); }
		function remove( i ) { var copyArr = rows.slice(); copyArr.splice( i, 1 ); p.onChange( copyArr ); }
		function move( i, dir ) {
			var j = i + dir; if ( j < 0 || j >= rows.length ) { return; }
			var copyArr = rows.slice(); var tmp = copyArr[ i ]; copyArr[ i ] = copyArr[ j ]; copyArr[ j ] = tmp; p.onChange( copyArr );
		}
		function rowTitle( row, i ) {
			var tf = p.control.titleField;
			var label = tf && row[ tf ] ? row[ tf ] : ( p.control.label.replace( /s$/, '' ) + ' ' + ( i + 1 ) );
			return label;
		}

		return el( Field, { label: p.label },
			el( 'div', { className: 'loom-repeater' },
				rows.map( function ( row, i ) {
					return el( 'div', { key: i, className: 'loom-rep-row' + ( openIdx === i ? ' is-open' : '' ) },
						el( 'div', { className: 'loom-rep-head' },
							el( 'button', { type: 'button', className: 'loom-rep-toggle', onClick: function () { setOpen( openIdx === i ? -1 : i ); } }, ( openIdx === i ? '▾ ' : '▸ ' ) + rowTitle( row, i ) ),
							el( 'span', { className: 'loom-rep-tools' },
								el( 'button', { type: 'button', title: t.moveUp || 'Up', onClick: function () { move( i, -1 ); } }, '↑' ),
								el( 'button', { type: 'button', title: t.moveDown || 'Down', onClick: function () { move( i, 1 ); } }, '↓' ),
								el( 'button', { type: 'button', title: t.delete, onClick: function () { remove( i ); } }, '×' )
							)
						),
						openIdx === i ? el( 'div', { className: 'loom-rep-body' },
							Object.keys( fields ).map( function ( fk ) {
								var fc = fields[ fk ];
								return el( SubControl, { key: fk, control: fc, value: row[ fk ], onChange: function ( val ) { setRow( i, fk, val ); } } );
							} )
						) : null
					);
				} ),
				el( 'button', { type: 'button', className: 'loom-rep-add button', onClick: add }, '+ ' + p.control.label )
			)
		);
	}

	// A control used inside repeater rows (no settings-level side effects).
	function SubControl( p ) {
		var c = p.control;
		switch ( c.type ) {
			case 'textarea': return el( TextareaControl, { label: c.label, value: p.value, onChange: p.onChange } );
			case 'url': return el( UrlControl, { label: c.label, value: p.value, onChange: p.onChange } );
			case 'imageobj': return el( ImageObjControl, { label: c.label, value: p.value, onChange: p.onChange } );
			case 'select': return el( SelectControl, { label: c.label, value: p.value, options: c.options, onChange: p.onChange } );
			case 'toggle': return el( ToggleControl, { label: c.label, value: p.value, onChange: p.onChange } );
			case 'color': return el( ColorControl, { label: c.label, value: p.value, onChange: p.onChange } );
			default: return el( TextControl, { label: c.label, value: p.value, onChange: p.onChange } );
		}
	}

	// A dynamic-binding select that maps a setting key to a custom field.
	function DynamicBinding( props ) {
		var choices = props.choices || [];
		if ( ! choices.length ) { return null; }
		var bound = props.value || '';
		return el( 'div', { className: 'loom-dynamic' + ( bound ? ' is-bound' : '' ) },
			el( 'span', { className: 'loom-dynamic-ic', title: t.dynamicField || 'Dynamic field' }, '⚡' ),
			el( 'select', { value: bound, onChange: function ( e ) { props.onChange( e.target.value ); } },
				el( 'option', { value: '' }, t.staticValue || '— Static —' ),
				choices.map( function ( f ) { return el( 'option', { key: f.name, value: f.name }, f.label ); } )
			)
		);
	}

	// Render one schema-driven content control, with optional dynamic binding.
	function ContentControl( props ) {
		var c = props.control, val = props.value, set = props.set;
		var base;
		switch ( c.type ) {
			case 'textarea': base = el( TextareaControl, { label: c.label, value: val, onChange: set } ); break;
			case 'code': base = el( CodeControl, { label: c.label, value: val, onChange: set } ); break;
			case 'richtext': base = el( TextareaControl, { label: c.label + ' (HTML)', value: val, onChange: set } ); break;
			case 'number': base = el( NumberControl, { label: c.label, value: val, onChange: set } ); break;
			case 'range': base = el( RangeControl, { label: c.label, value: val, min: c.min, max: c.max, onChange: set } ); break;
			case 'color': base = el( ColorControl, { label: c.label, value: val, onChange: set } ); break;
			case 'select': base = el( SelectControl, { label: c.label, value: val, options: c.options, onChange: set } ); break;
			case 'toggle': base = el( ToggleControl, { label: c.label, value: val, onChange: set } ); break;
			case 'url': base = el( UrlControl, { label: c.label, value: val, onChange: set } ); break;
			case 'image':
				base = el( MediaControl, {
					label: c.label,
					value: val,
					preview: props.node.settings.preview,
					onChange: function ( media ) {
						props.setMany( { id: media.id, preview: media.url, url: media.url } );
					},
				} );
				break;
			case 'imageobj': base = el( ImageObjControl, { label: c.label, value: val, onChange: set } ); break;
			case 'gallery': base = el( GalleryControl, { label: c.label, value: val, onChange: set } ); break;
			case 'repeater': base = el( RepeaterControl, { label: c.label, control: c, value: val, onChange: set } ); break;
			default: base = el( TextControl, { label: c.label, value: val, onChange: set } );
		}

		// Dynamic binding: text-like fields bind by control name; image binds 'url'.
		var dyn = node_dynamic( props );
		return dyn ? el( Fragment, null, base, dyn ) : base;
	}

	// Build the dynamic-binding row for a control, or null when not applicable.
	function node_dynamic( props ) {
		var c = props.control, dynMap = ( props.node.settings._dynamic ) || {};
		var textTypes = { text: 1, textarea: 1, richtext: 1 };
		if ( textTypes[ c.type ] && ( cfg.dynamicText || [] ).length ) {
			return el( DynamicBinding, { choices: cfg.dynamicText, value: dynMap[ props.name ], onChange: function ( f ) { props.setDynamic( props.name, f ); } } );
		}
		if ( c.type === 'image' && ( cfg.dynamicImage || [] ).length ) {
			return el( DynamicBinding, { choices: cfg.dynamicImage, value: dynMap.url, onChange: function ( f ) { props.setDynamic( 'url', f ); } } );
		}
		return null;
	}

	L.controls = {
		Field: Field,
		TextControl: TextControl,
		TextareaControl: TextareaControl,
		CodeControl: CodeControl,
		NumberControl: NumberControl,
		RangeControl: RangeControl,
		DimensionControl: DimensionControl,
		ColorControl: ColorControl,
		SelectControl: SelectControl,
		ToggleControl: ToggleControl,
		UrlControl: UrlControl,
		BoxControl: BoxControl,
		MediaControl: MediaControl,
		ImageObjControl: ImageObjControl,
		GalleryControl: GalleryControl,
		RepeaterControl: RepeaterControl,
		SubControl: SubControl,
		DynamicBinding: DynamicBinding,
	};
	L.ContentControl = ContentControl;

} )();
