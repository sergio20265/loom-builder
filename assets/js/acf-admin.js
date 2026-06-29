/**
 * Field-group builder UI. Edits the list of fields and the location rules of a
 * loom_field_group, serializing both to hidden inputs read on save.
 *
 * Native wp.element, no JSX / no build step.
 *
 * @package Loom
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.element ) { return; }

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var cfg = window.LoomAcf || {};
	var t = cfg.i18n || {};

	function parse( json, fallback ) {
		try { var v = JSON.parse( json ); return Array.isArray( v ) ? v : fallback; } catch ( e ) { return fallback; }
	}
	function slug( s ) {
		return String( s ).toLowerCase().replace( /[^a-z0-9_]+/g, '_' ).replace( /^_+|_+$/g, '' );
	}

	function Row( props ) {
		return el( 'div', { className: 'loom-acf-row' }, props.children );
	}

	function FieldEditor( props ) {
		var f = props.field, i = props.index;
		function set( key, val ) { props.onChange( i, Object.assign( {}, f, ( function () { var o = {}; o[ key ] = val; return o; } )() ) ); }
		function setLabel( val ) {
			var patch = { label: val };
			if ( ! f.name || f.name === slug( f.label ) ) { patch.name = slug( val ); }
			props.onChange( i, Object.assign( {}, f, patch ) );
		}
		function setSub( subs ) { set( 'sub_fields', subs ); }

		return el( 'div', { className: 'loom-acf-field' },
			el( 'div', { className: 'loom-acf-field-head' },
				el( 'span', { className: 'loom-acf-handle' }, '≡' ),
				el( 'input', { type: 'text', placeholder: t.label || 'Label', value: f.label || '', onChange: function ( e ) { setLabel( e.target.value ); } } ),
				el( 'input', { type: 'text', placeholder: t.name || 'name', value: f.name || '', className: 'loom-acf-name', onChange: function ( e ) { set( 'name', slug( e.target.value ) ); } } ),
				el( 'select', { value: f.type || 'text', onChange: function ( e ) { set( 'type', e.target.value ); } },
					Object.keys( cfg.types || {} ).map( function ( k ) { return el( 'option', { key: k, value: k }, cfg.types[ k ] ); } )
				),
				el( 'button', { type: 'button', className: 'button-link loom-acf-del', onClick: function () { props.onRemove( i ); } }, '×' )
			),
			f.type === 'select' ? el( 'textarea', { className: 'loom-acf-choices', placeholder: t.choices || 'value:Label per line', value: f.choices || '', onChange: function ( e ) { set( 'choices', e.target.value ); } } ) : null,
			f.type === 'repeater' ? el( SubFields, { subs: f.sub_fields || [], onChange: setSub } ) : null
		);
	}

	function SubFields( props ) {
		var subs = props.subs;
		function update( i, val ) { var c = subs.slice(); c[ i ] = val; props.onChange( c ); }
		function add() { props.onChange( subs.concat( [ { label: '', name: '', type: 'text' } ] ) ); }
		function remove( i ) { var c = subs.slice(); c.splice( i, 1 ); props.onChange( c ); }
		return el( 'div', { className: 'loom-acf-subs' },
			el( 'div', { className: 'loom-acf-subs-title' }, t.subFields || 'Sub fields' ),
			subs.map( function ( sf, i ) {
				return el( 'div', { key: i, className: 'loom-acf-sub' },
					el( 'input', { type: 'text', placeholder: t.label || 'Label', value: sf.label || '', onChange: function ( e ) { var v = e.target.value; update( i, Object.assign( {}, sf, { label: v, name: sf.name || slug( v ) } ) ); } } ),
					el( 'input', { type: 'text', placeholder: t.name || 'name', value: sf.name || '', onChange: function ( e ) { update( i, Object.assign( {}, sf, { name: slug( e.target.value ) } ) ); } } ),
					el( 'select', { value: sf.type || 'text', onChange: function ( e ) { update( i, Object.assign( {}, sf, { type: e.target.value } ) ); } },
						[ 'text', 'textarea', 'image' ].map( function ( k ) { return el( 'option', { key: k, value: k }, k ); } )
					),
					el( 'button', { type: 'button', className: 'button-link', onClick: function () { remove( i ); } }, '×' )
				);
			} ),
			el( 'button', { type: 'button', className: 'button', onClick: add }, '+ ' + ( t.subFields || 'Sub field' ) )
		);
	}

	function LocationRule( props ) {
		var r = props.rule, i = props.index;
		function set( key, val ) { props.onChange( i, Object.assign( {}, r, ( function () { var o = {}; o[ key ] = val; return o; } )() ) ); }
		var valueInput;
		if ( r.param === 'post_type' ) {
			valueInput = el( 'select', { value: r.value || '', onChange: function ( e ) { set( 'value', e.target.value ); } },
				el( 'option', { value: '' }, '—' ),
				Object.keys( cfg.postTypes || {} ).map( function ( k ) { return el( 'option', { key: k, value: k }, cfg.postTypes[ k ] ); } )
			);
		} else if ( r.param === 'post_template' ) {
			valueInput = el( 'select', { value: r.value || '', onChange: function ( e ) { set( 'value', e.target.value ); } },
				el( 'option', { value: '' }, '— Default —' ),
				Object.keys( cfg.templates || {} ).map( function ( name ) { return el( 'option', { key: name, value: cfg.templates[ name ] }, name ); } )
			);
		} else {
			valueInput = el( 'input', { type: 'text', placeholder: 'Post ID', value: r.value || '', onChange: function ( e ) { set( 'value', e.target.value ); } } );
		}
		return el( 'div', { className: 'loom-acf-rule' },
			el( 'select', { value: r.param || 'post_type', onChange: function ( e ) { set( 'param', e.target.value ); } },
				el( 'option', { value: 'post_type' }, 'Post type' ),
				el( 'option', { value: 'post_template' }, 'Page template' ),
				el( 'option', { value: 'post' }, 'Specific post' )
			),
			el( 'select', { value: r.operator || '==', onChange: function ( e ) { set( 'operator', e.target.value ); } },
				el( 'option', { value: '==' }, 'is equal to' ),
				el( 'option', { value: '!=' }, 'is not equal to' )
			),
			valueInput,
			el( 'button', { type: 'button', className: 'button-link', onClick: function () { props.onRemove( i ); } }, '×' )
		);
	}

	function App() {
		var fs = useState( parse( cfg.fields, [] ) ); var fields = fs[ 0 ], setFields = fs[ 1 ];
		var ls = useState( parse( cfg.location, [] ) ); var location = ls[ 0 ], setLocation = ls[ 1 ];

		useEffect( function () {
			var fi = document.getElementById( 'loom_group_fields' );
			var li = document.getElementById( 'loom_group_location' );
			if ( fi ) { fi.value = JSON.stringify( fields ); }
			if ( li ) { li.value = JSON.stringify( location ); }
		}, [ fields, location ] );

		function changeField( i, val ) { var c = fields.slice(); c[ i ] = val; setFields( c ); }
		function removeField( i ) { var c = fields.slice(); c.splice( i, 1 ); setFields( c ); }
		function addField() { setFields( fields.concat( [ { label: '', name: '', type: 'text' } ] ) ); }

		function changeRule( i, val ) { var c = location.slice(); c[ i ] = val; setLocation( c ); }
		function removeRule( i ) { var c = location.slice(); c.splice( i, 1 ); setLocation( c ); }
		function addRule() { setLocation( location.concat( [ { param: 'post_type', operator: '==', value: 'page' } ] ) ); }

		return el( 'div', { className: 'loom-acf' },
			el( 'div', { className: 'loom-acf-fields' },
				fields.map( function ( f, i ) { return el( FieldEditor, { key: i, field: f, index: i, onChange: changeField, onRemove: removeField } ); } ),
				el( 'button', { type: 'button', className: 'button button-primary', onClick: addField }, '+ ' + ( t.addField || 'Add Field' ) )
			),
			el( 'div', { className: 'loom-acf-location' },
				el( 'h4', null, t.location || 'Show this group on' ),
				location.map( function ( r, i ) { return el( LocationRule, { key: i, rule: r, index: i, onChange: changeRule, onRemove: removeRule } ); } ),
				el( 'button', { type: 'button', className: 'button', onClick: addRule }, '+ ' + ( t.addRule || 'Add Rule' ) )
			)
		);
	}

	function mount() {
		var root = document.getElementById( 'loom-acf-root' );
		if ( ! root ) { return; }
		if ( wp.element.createRoot ) { wp.element.createRoot( root ).render( el( App ) ); }
		else { wp.element.render( el( App ), root ); }
	}

	if ( document.readyState !== 'loading' ) { mount(); } else { document.addEventListener( 'DOMContentLoaded', mount ); }

} )( window.wp );
