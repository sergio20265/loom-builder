/**
 * Loom Builder editor — shared core.
 *
 * Sets up the window.LoomEd namespace used by every editor module: the
 * wp.element runtime references, the localized config/i18n, and the immutable
 * tree helpers. Loaded first; later modules attach their components to LoomEd.
 *
 * Built on the WordPress-native React runtime (wp.element) with no JSX and no
 * bundler, so it runs as-is with zero build step and zero external libraries.
 *
 * @package Loom
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.element ) {
		return;
	}

	var cfg = window.LoomConfig || {};

	if ( wp.apiFetch && cfg.nonce ) {
		wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( cfg.nonce ) );
	}

	var L = window.LoomEd = window.LoomEd || {};

	// Runtime references shared by every module.
	L.wp = wp;
	L.el = wp.element.createElement;
	L.Fragment = wp.element.Fragment;
	L.useState = wp.element.useState;
	L.useEffect = wp.element.useEffect;
	L.useRef = wp.element.useRef;
	L.apiFetch = wp.apiFetch;
	L.cfg = cfg;
	L.t = cfg.i18n || {};
	L.editorTopbarActions = [];
	L.registerTopbarAction = function ( render ) {
		if ( typeof render === 'function' ) { L.editorTopbarActions.push( render ); }
	};
	L.styleExtensions = [];
	L.registerStyleControl = function ( render ) {
		if ( typeof render === 'function' ) { L.styleExtensions.push( render ); }
	};
	L.contentExtensions = [];
	L.registerContentControl = function ( render ) {
		if ( typeof render === 'function' ) { L.contentExtensions.push( render ); }
	};
	L.widgetPreviewExtensions = [];
	L.registerWidgetPreview = function ( render ) {
		if ( typeof render === 'function' ) { L.widgetPreviewExtensions.push( render ); }
	};

	// ---- helpers ----------------------------------------------------------

	L.uid = function () {
		return 'l' + Math.random().toString( 36 ).slice( 2, 9 );
	};

	L.clone = function ( v ) {
		return JSON.parse( JSON.stringify( v ) );
	};

	L.widgetDef = function ( id ) {
		var list = cfg.widgets || [];
		for ( var i = 0; i < list.length; i++ ) {
			if ( list[ i ].id === id ) {
				return list[ i ];
			}
		}
		return null;
	};

	// Immutable tree operations keyed by node id.
	L.mapTree = function mapTree( tree, id, fn ) {
		return tree.map( function ( node ) {
			if ( node.id === id ) {
				return fn( node );
			}
			if ( node.children && node.children.length ) {
				return Object.assign( {}, node, { children: mapTree( node.children, id, fn ) } );
			}
			return node;
		} );
	};

	L.removeFromTree = function removeFromTree( tree, id ) {
		var out = [];
		for ( var i = 0; i < tree.length; i++ ) {
			var node = tree[ i ];
			if ( node.id === id ) {
				continue;
			}
			if ( node.children && node.children.length ) {
				node = Object.assign( {}, node, { children: removeFromTree( node.children, id ) } );
			}
			out.push( node );
		}
		return out;
	};

	L.findNode = function findNode( tree, id ) {
		for ( var i = 0; i < tree.length; i++ ) {
			if ( tree[ i ].id === id ) {
				return tree[ i ];
			}
			if ( tree[ i ].children ) {
				var found = findNode( tree[ i ].children, id );
				if ( found ) {
					return found;
				}
			}
		}
		return null;
	};

	L.findParent = function findParent( tree, id, parent ) {
		for ( var i = 0; i < tree.length; i++ ) {
			if ( tree[ i ].id === id ) {
				return parent;
			}
			if ( tree[ i ].children ) {
				var p = findParent( tree[ i ].children, id, tree[ i ] );
				if ( p ) {
					return p;
				}
			}
		}
		return null;
	};

	// Remove a node by id and return { tree, node } (node = the removed subtree).
	L.extractNode = function ( tree, id ) {
		var removed = null;
		function walk( nodes ) {
			var out = [];
			for ( var i = 0; i < nodes.length; i++ ) {
				var n = nodes[ i ];
				if ( n.id === id ) { removed = n; continue; }
				if ( n.children && n.children.length ) { n = Object.assign( {}, n, { children: walk( n.children ) } ); }
				out.push( n );
			}
			return out;
		}
		var t = walk( tree );
		return { tree: t, node: removed };
	};

	// Insert a node immediately before the target id, wherever it lives.
	L.insertBefore = function ( tree, targetId, node ) {
		function walk( nodes ) {
			var out = [];
			for ( var i = 0; i < nodes.length; i++ ) {
				var n = nodes[ i ];
				if ( n.id === targetId ) { out.push( node ); }
				if ( n.children && n.children.length ) { n = Object.assign( {}, n, { children: walk( n.children ) } ); }
				out.push( n );
			}
			return out;
		}
		return walk( tree );
	};

	// Move a node so it sits just before another node of the same type.
	L.moveNodeRelative = function ( tree, dragId, targetId ) {
		if ( dragId === targetId ) { return tree; }
		var dragged = L.findNode( tree, dragId );
		var target = L.findNode( tree, targetId );
		if ( ! dragged || ! target || dragged.type !== target.type ) { return tree; }
		// Never drop a node into its own descendant.
		if ( dragged.children && L.findNode( dragged.children, targetId ) ) { return tree; }
		var ex = L.extractNode( tree, dragId );
		return L.insertBefore( ex.tree, targetId, ex.node );
	};

	// Move a widget node into the end of a column.
	L.moveInto = function ( tree, dragId, columnId ) {
		var dragged = L.findNode( tree, dragId );
		if ( ! dragged || dragged.type !== 'widget' ) { return tree; }
		var ex = L.extractNode( tree, dragId );
		return L.mapTree( ex.tree, columnId, function ( n ) {
			return Object.assign( {}, n, { children: n.children.concat( [ ex.node ] ) } );
		} );
	};

	// Deep-clone a node giving every node a fresh id (for duplicate / import).
	L.regenIds = function ( node ) {
		var c = L.clone( node );
		( function re( n ) { n.id = L.uid(); if ( n.children ) { n.children.forEach( re ); } } )( c );
		return c;
	};

	L.newSection = function () {
		return {
			id: L.uid(),
			type: 'section',
			settings: { _style: { desktop: { padding: { t: 40, r: 16, b: 40, l: 16 } }, tablet: {}, mobile: {} } },
			children: [ L.newColumn() ],
		};
	};

	L.newColumn = function () {
		return { id: L.uid(), type: 'column', settings: { _style: { desktop: {}, tablet: {}, mobile: {} } }, children: [] };
	};

	L.newWidget = function ( id ) {
		var def = L.widgetDef( id );
		var settings = def ? L.clone( def.defaults || {} ) : {};
		settings._style = { desktop: {}, tablet: {}, mobile: {} };
		settings._advanced = {};
		if ( id === 'heading' ) {
			settings._style.desktop = { fontSize: 32, fontWeight: '700' };
		}
		return { id: L.uid(), type: 'widget', widget: id, settings: settings, children: [] };
	};

	// ---- CSS prop mirror (editor preview only) ----------------------------

	L.box = function ( v ) {
		if ( ! v ) { return null; }
		var top = v.t || 0, r = v.r || 0, b = v.b || 0, l = v.l || 0;
		if ( ! top && ! r && ! b && ! l ) { return null; }
		return top + 'px ' + r + 'px ' + b + 'px ' + l + 'px';
	};

	L.styleObject = function ( props ) {
		var s = {};
		if ( ! props ) { return s; }
		if ( props.bgColor ) { s.backgroundColor = props.bgColor; }
		if ( props.bgImage ) { s.backgroundImage = 'url(' + props.bgImage + ')'; s.backgroundSize = props.bgSize || 'cover'; s.backgroundPosition = 'center'; }
		if ( props.color ) { s.color = props.color; }
		if ( props.align ) { s.textAlign = props.align; }
		if ( props.maxWidth ) { s.maxWidth = /[a-z%]$/i.test( String( props.maxWidth ) ) ? props.maxWidth : props.maxWidth + 'px'; s.marginLeft = 'auto'; s.marginRight = 'auto'; }
		if ( props.minHeight ) { s.minHeight = props.minHeight + 'px'; }
		if ( props.radius ) { s.borderRadius = props.radius + 'px'; }
		if ( props.fontSize ) { s.fontSize = props.fontSize + 'px'; }
		if ( props.fontWeight ) { s.fontWeight = props.fontWeight; }
		if ( props.lineHeight ) { s.lineHeight = props.lineHeight; }
		if ( props.letterSpacing ) { s.letterSpacing = props.letterSpacing + 'px'; }
		if ( props.width ) { s.width = props.width; }
		if ( props.gap ) { s.gap = props.gap + 'px'; }
		if ( props.justify ) { s.justifyContent = props.justify; }
		if ( props.valign ) { s.alignItems = props.valign; }
		if ( props.direction ) { s.flexDirection = props.direction; }
		var p = L.box( props.padding ); if ( p ) { s.padding = p; }
		var m = L.box( props.margin ); if ( m ) { s.margin = m; }
		return s;
	};

	L.mergedStyle = function ( node, device ) {
		var st = ( node.settings && node.settings._style ) || {};
		var merged = Object.assign( {}, st.desktop || {} );
		if ( device === 'tablet' || device === 'mobile' ) {
			merged = Object.assign( merged, st.tablet || {} );
		}
		if ( device === 'mobile' ) {
			merged = Object.assign( merged, st.mobile || {} );
		}
		return L.styleObject( merged );
	};

	// Trigger a browser download of a JSON payload.
	L.downloadJson = function ( data, filename ) {
		var blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
		var url = URL.createObjectURL( blob );
		var a = document.createElement( 'a' );
		a.href = url; a.download = filename;
		document.body.appendChild( a ); a.click(); document.body.removeChild( a );
		setTimeout( function () { URL.revokeObjectURL( url ); }, 1000 );
	};

} )( window.wp );
