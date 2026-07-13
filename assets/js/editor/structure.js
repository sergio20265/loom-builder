/**
 * Loom Builder editor — compact document structure / layers panel.
 *
 * @package Loom
 */
( function () {
	'use strict';

	var L = window.LoomEd;
	if ( ! L ) { return; }

	var el = L.el, useState = L.useState, t = L.t, widgetDef = L.widgetDef;

	function labelFor( node, index ) {
		if ( node.type === 'section' ) { return ( t.section || 'Section' ) + ' ' + ( index + 1 ); }
		if ( node.type === 'column' ) { return ( t.column || 'Column' ) + ' ' + ( index + 1 ); }
		var def = widgetDef( node.widget );
		return def ? def.title : ( node.widget || ( t.widgets || 'Widget' ) );
	}

	function iconFor( node ) {
		if ( node.type === 'section' ) { return 'screenoptions'; }
		if ( node.type === 'column' ) { return 'columns'; }
		var def = widgetDef( node.widget );
		return def && def.icon ? def.icon : 'admin-generic';
	}

	function StructurePanel( props ) {
		var openState = useState( {} );
		var collapsed = openState[ 0 ], setCollapsed = openState[ 1 ];

		function toggle( id ) {
			setCollapsed( function ( current ) {
				var next = Object.assign( {}, current ); next[ id ] = ! next[ id ]; return next;
			} );
		}

		function StructureNode( nodeProps ) {
			var node = nodeProps.node, children = node.children || [];
			var hasChildren = children.length > 0;
			var isCollapsed = !! collapsed[ node.id ];
			var selected = node.id === props.selectedId;
			function stop( fn ) { return function ( e ) { e.stopPropagation(); fn(); }; }
			return el( 'div', { className: 'loom-structure-node loom-structure-' + node.type },
				el( 'div', { className: 'loom-structure-row' + ( selected ? ' is-selected' : '' ), onClick: function () { props.onSelect( node.id ); } },
					hasChildren ? el( 'button', { type: 'button', className: 'loom-structure-toggle', title: isCollapsed ? ( t.expand || 'Expand' ) : ( t.collapse || 'Collapse' ), onClick: stop( function () { toggle( node.id ); } ) }, el( 'span', { className: 'dashicons dashicons-arrow-' + ( isCollapsed ? 'right' : 'down' ) } ) ) : el( 'span', { className: 'loom-structure-spacer' } ),
					el( 'span', { className: 'dashicons dashicons-' + iconFor( node ) } ),
					el( 'span', { className: 'loom-structure-label', title: labelFor( node, nodeProps.index ) }, labelFor( node, nodeProps.index ) ),
					el( 'span', { className: 'loom-structure-actions' },
						nodeProps.index > 0 ? el( 'button', { type: 'button', title: t.moveUp || 'Up', onClick: stop( function () { props.onMove( node.id, -1 ); } ) }, el( 'span', { className: 'dashicons dashicons-arrow-up-alt2' } ) ) : null,
						nodeProps.index < nodeProps.count - 1 ? el( 'button', { type: 'button', title: t.moveDown || 'Down', onClick: stop( function () { props.onMove( node.id, 1 ); } ) }, el( 'span', { className: 'dashicons dashicons-arrow-down-alt2' } ) ) : null,
						el( 'button', { type: 'button', title: t.duplicate || 'Duplicate', onClick: stop( function () { props.onDuplicate( node.id ); } ) }, el( 'span', { className: 'dashicons dashicons-admin-page' } ) ),
						el( 'button', { type: 'button', className: 'is-delete', title: t.delete || 'Delete', onClick: stop( function () { props.onDelete( node.id ); } ) }, el( 'span', { className: 'dashicons dashicons-trash' } ) )
					)
				),
				hasChildren && ! isCollapsed ? el( 'div', { className: 'loom-structure-children' }, children.map( function ( child, index ) {
					return el( StructureNode, { key: child.id, node: child, index: index, count: children.length } );
				} ) ) : null
			);
		}

		return el( 'div', { className: 'loom-structure' },
			props.tree.length ? props.tree.map( function ( node, index ) {
				return el( StructureNode, { key: node.id, node: node, index: index, count: props.tree.length } );
			} ) : el( 'p', { className: 'loom-structure-empty' }, t.pageEmpty || 'Your page is empty.' )
		);
	}

	L.StructurePanel = StructurePanel;
}() );
