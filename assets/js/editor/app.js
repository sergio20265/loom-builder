/**
 * Loom Builder editor — App shell, history, persistence and mount.
 *
 * @package Loom
 */
( function () {
	'use strict';

	var L = window.LoomEd;
	if ( ! L ) { return; }

	var el = L.el, useState = L.useState, useEffect = L.useEffect, useRef = L.useRef;
	var apiFetch = L.apiFetch, cfg = L.cfg, t = L.t;
	var mapTree = L.mapTree, removeFromTree = L.removeFromTree, findNode = L.findNode, findParent = L.findParent;
	var newSection = L.newSection, newColumn = L.newColumn, newWidget = L.newWidget, regenIds = L.regenIds;
	var moveNodeRelative = L.moveNodeRelative, moveInto = L.moveInto, downloadJson = L.downloadJson;

	function App() {
		var s = useState( [] ); var tree = s[ 0 ], setTree = s[ 1 ];
		var se = useState( false ); var enabled = se[ 0 ], setEnabled = se[ 1 ];
		var sel = useState( null ); var selectedId = sel[ 0 ], setSelectedId = sel[ 1 ];
		var dev = useState( 'desktop' ); var device = dev[ 0 ], setDevice = dev[ 1 ];
		var sv = useState( 'idle' ); var saveState = sv[ 0 ], setSaveState = sv[ 1 ];
		var tb = useState( 'content' ); var tab = tb[ 0 ], setTab = tb[ 1 ];
		var sc = useState( '' ); var search = sc[ 0 ], setSearch = sc[ 1 ];
		var hv = useState( 0 ); var histVer = hv[ 0 ], setHistVer = hv[ 1 ];
		var ds = useState( false ); var dirty = ds[ 0 ], setDirty = ds[ 1 ];
		var er = useState( '' ); var saveError = er[ 0 ], setSaveError = er[ 1 ];

		var treeRef = useRef( tree ); treeRef.current = tree;
		var dirtyRef = useRef( dirty ); dirtyRef.current = dirty;
		var undoRef = useRef( [] );
		var redoRef = useRef( [] );
		var dragRef = useRef( null );
		var fileRef = useRef( null );
		var actionsRef = useRef( {} );

		useEffect( function () {
			apiFetch( { url: cfg.restUrl + '/layout/' + cfg.postId } ).then( function ( res ) {
				setTree( res.tree && res.tree.length ? res.tree : [] );
				setEnabled( !! res.enabled );
				undoRef.current = []; redoRef.current = [];
				setDirty( false );
				setSaveError( '' );
			} );
		}, [] );

		// Record the current tree on the undo stack, then apply the next tree.
		function commit( next ) {
			undoRef.current.push( treeRef.current );
			if ( undoRef.current.length > 60 ) { undoRef.current.shift(); }
			redoRef.current = [];
			setTree( next );
			setDirty( true );
			setSaveError( '' );
			setSaveState( 'idle' );
			setHistVer( function ( v ) { return v + 1; } );
		}
		function doUndo() {
			if ( ! undoRef.current.length ) { return; }
			redoRef.current.push( treeRef.current );
			setTree( undoRef.current.pop() );
			setDirty( true );
			setSaveError( '' );
			setSaveState( 'idle' );
			setHistVer( function ( v ) { return v + 1; } );
		}
		function doRedo() {
			if ( ! redoRef.current.length ) { return; }
			undoRef.current.push( treeRef.current );
			setTree( redoRef.current.pop() );
			setDirty( true );
			setSaveError( '' );
			setSaveState( 'idle' );
			setHistVer( function ( v ) { return v + 1; } );
		}
		actionsRef.current.undo = doUndo;
		actionsRef.current.redo = doRedo;

		// Keyboard: Ctrl/Cmd+Z undo, Ctrl/Cmd+Shift+Z or Ctrl+Y redo.
		useEffect( function () {
			function onKey( e ) {
				if ( ! ( e.ctrlKey || e.metaKey ) ) { return; }
				var k = e.key.toLowerCase();
				if ( k === 'z' && ! e.shiftKey ) { e.preventDefault(); actionsRef.current.undo(); }
				else if ( ( k === 'z' && e.shiftKey ) || k === 'y' ) { e.preventDefault(); actionsRef.current.redo(); }
			}
			document.addEventListener( 'keydown', onKey );
			return function () { document.removeEventListener( 'keydown', onKey ); };
		}, [] );

		useEffect( function () {
			function beforeUnload( e ) {
				if ( ! dirtyRef.current ) { return; }
				e.preventDefault();
				e.returnValue = '';
			}
			window.addEventListener( 'beforeunload', beforeUnload );
			return function () { window.removeEventListener( 'beforeunload', beforeUnload ); };
		}, [] );

		function applyToNode( id, fn ) { return mapTree( tree, id, fn ); }
		function changeTree( next ) { commit( next ); }

		function addSection() { commit( tree.concat( [ newSection() ] ) ); }
		function addColumn( sectionId ) {
			commit( mapTree( tree, sectionId, function ( n ) { return Object.assign( {}, n, { children: n.children.concat( [ newColumn() ] ) } ); } ) );
		}
		function addWidget( columnId, widgetId ) {
			var w = newWidget( widgetId );
			commit( mapTree( tree, columnId, function ( n ) { return Object.assign( {}, n, { children: n.children.concat( [ w ] ) } ); } ) );
			setSelectedId( w.id );
			setTab( 'content' );
		}
		function addWidgetSmart( widgetId ) {
			var targetCol = null;
			if ( selectedId ) {
				var n = findNode( tree, selectedId );
				if ( n && n.type === 'column' ) { targetCol = n; }
				else if ( n && n.type === 'widget' ) { targetCol = findParent( tree, selectedId, null ); }
			}
			if ( ! targetCol ) {
				if ( ! tree.length ) { var sec = newSection(); var col = sec.children[ 0 ]; var w = newWidget( widgetId ); col.children.push( w ); commit( tree.concat( [ sec ] ) ); setSelectedId( w.id ); return; }
				targetCol = tree[ tree.length - 1 ].children[ 0 ];
			}
			addWidget( targetCol.id, widgetId );
		}
		function deleteNode( id ) { commit( removeFromTree( tree, id ) ); if ( selectedId === id ) { setSelectedId( null ); } }
		function duplicateNode( id ) {
			var walk = function ( nodes ) {
				var res = [];
				nodes.forEach( function ( node ) {
					var nn = node;
					if ( node.children ) { nn = Object.assign( {}, node, { children: walk( node.children ) } ); }
					res.push( nn );
					if ( node.id === id ) { res.push( regenIds( node ) ); }
				} );
				return res;
			};
			commit( walk( tree ) );
		}

		// Drag reorder.
		function moveBefore( dragId, targetId ) { commit( moveNodeRelative( tree, dragId, targetId ) ); }
		function moveIntoColumn( dragId, columnId ) { commit( moveInto( tree, dragId, columnId ) ); }

		// Export / import.
		function exportPage() { downloadJson( tree, 'loom-layout-' + cfg.postId + '.json' ); }
		function exportNode( id ) { var n = findNode( tree, id ); if ( n ) { downloadJson( [ n ], 'loom-section.json' ); } }
		function importFiles( fileList ) {
			var file = fileList && fileList[ 0 ];
			if ( ! file ) { return; }
			var reader = new FileReader();
			reader.onload = function () {
				try {
					var data = JSON.parse( reader.result );
					var nodes = Array.isArray( data ) ? data : [ data ];
					var sections = nodes.filter( function ( nn ) { return nn && nn.type === 'section'; } ).map( regenIds );
					if ( sections.length ) { commit( tree.concat( sections ) ); }
				} catch ( err ) {
					window.alert( t.invalidFile || 'Invalid layout file.' );
				}
			};
			reader.readAsText( file );
		}

		function confirmExit( e ) {
			if ( dirty && ! window.confirm( t.unsavedChanges || 'You have unsaved changes. Leave without saving?' ) ) {
				e.preventDefault();
			}
		}

		function save() {
			setSaveState( 'saving' );
			setSaveError( '' );
			apiFetch( {
				url: cfg.restUrl + '/layout/' + cfg.postId,
				method: 'POST',
				data: { tree: tree, enabled: true },
			} ).then( function () {
				setEnabled( true );
				setDirty( false );
				setSaveState( 'saved' );
				setTimeout( function () { setSaveState( 'idle' ); }, 1500 );
			} ).catch( function ( err ) {
				var msg = err && err.message ? err.message : ( t.saveError || 'Could not save. Please try again.' );
				setSaveError( msg );
				setSaveState( 'error' );
			} );
		}

		var selectedNode = selectedId ? findNode( tree, selectedId ) : null;

		var canvasProps = {
			tree: tree, device: device, selectedId: selectedId, dragRef: dragRef,
			onSelect: setSelectedId, onAddSection: addSection, onAddColumn: addColumn,
			onAddWidget: addWidget, onDelete: deleteNode, onDuplicate: duplicateNode,
			onExport: exportNode, onMoveBefore: moveBefore, onMoveInto: moveIntoColumn,
		};

		return el( 'div', { className: 'loom-app' },
			el( 'div', { className: 'loom-topbar' },
				el( 'div', { className: 'loom-brand' }, el( 'span', { className: 'dashicons dashicons-screenoptions' } ), 'Loom' ),
				( L.editorTopbarActions || [] ).map( function ( render, index ) {
					return render( { key: index, postId: cfg.postId } );
				} ),
				el( 'div', { className: 'loom-history' },
					el( 'button', { title: ( t.undo || 'Undo' ) + ' (Ctrl+Z)', disabled: ! undoRef.current.length, onClick: doUndo }, el( 'span', { className: 'dashicons dashicons-undo' } ) ),
					el( 'button', { title: ( t.redo || 'Redo' ) + ' (Ctrl+Shift+Z)', disabled: ! redoRef.current.length, onClick: doRedo }, el( 'span', { className: 'dashicons dashicons-redo' } ) )
				),
				el( 'div', { className: 'loom-devices' },
					[ [ 'desktop', 'desktop' ], [ 'tablet', 'tablet' ], [ 'mobile', 'smartphone' ] ].map( function ( d ) {
						return el( 'button', { key: d[ 0 ], className: device === d[ 0 ] ? 'is-active' : '', title: t[ d[ 0 ] ] || d[ 0 ], onClick: function () { setDevice( d[ 0 ] ); } }, el( 'span', { className: 'dashicons dashicons-' + d[ 1 ] } ) );
					} )
				),
				el( 'div', { className: 'loom-actions' },
					el( 'input', { type: 'file', accept: 'application/json,.json', ref: fileRef, style: { display: 'none' }, onChange: function ( e ) { importFiles( e.target.files ); e.target.value = ''; } } ),
					el( 'button', { className: 'button', title: t.importSections || 'Import sections', onClick: function () { fileRef.current && fileRef.current.click(); } }, el( 'span', { className: 'dashicons dashicons-upload' } ) ),
					el( 'button', { className: 'button', title: t.exportPage || 'Export page', onClick: exportPage }, el( 'span', { className: 'dashicons dashicons-download' } ) ),
					el( 'a', { className: 'button', href: 'post.php?post=' + cfg.postId + '&action=edit', onClick: confirmExit }, t.exit || 'Exit' ),
					el( 'button', { className: 'button button-primary', onClick: save, disabled: saveState === 'saving' },
												saveState === 'saving' ? ( t.saving || 'Saving...' ) : ( saveState === 'saved' ? ( t.saved || 'Saved' ) : ( saveState === 'error' ? ( t.error || 'Error' ) : ( dirty ? ( t.unsaved || 'Unsaved' ) : ( t.save || 'Save' ) ) ) )
										),
										saveError ? el( 'span', { className: 'loom-save-error', role: 'alert' }, saveError ) : null
				)
			),
			el( 'div', { className: 'loom-body' },
				el( 'div', { className: 'loom-left' },
					el( L.WidgetPanel, { search: search, setSearch: setSearch, onAdd: addWidgetSmart } )
				),
				el( 'div', { className: 'loom-center' }, el( L.Canvas, canvasProps ) ),
				el( 'div', { className: 'loom-right' },
					el( L.Inspector, {
						node: selectedNode, tab: tab, setTab: setTab, device: device,
						applyToNode: applyToNode, onChange: changeTree,
					} )
				)
			)
		);
	}

	function mount() {
		var root = document.getElementById( 'loom-editor-root' );
		if ( ! root ) { return; }
		if ( L.wp.element.createRoot ) {
			L.wp.element.createRoot( root ).render( el( App ) );
		} else {
			L.wp.element.render( el( App ), root );
		}
	}

	L.App = App;

	if ( document.readyState !== 'loading' ) { mount(); } else { document.addEventListener( 'DOMContentLoaded', mount ); }

} )();
