/**
 * Loom Builder editor — Canvas, NodeView and the Widget panel.
 *
 * @package Loom
 */
( function () {
	'use strict';

	var L = window.LoomEd;
	if ( ! L ) { return; }

	var el = L.el, t = L.t, cfg = L.cfg, mergedStyle = L.mergedStyle, widgetPreview = L.widgetPreview;

	function NodeView( props ) {
		var node = props.node, device = props.device;
		var selected = props.selectedId === node.id;
		var style = mergedStyle( node, device );
		var dragRef = props.dragRef;

		function selectThis( e ) {
			e.stopPropagation();
			props.onSelect( node.id );
		}
		function startDrag( e ) {
			e.stopPropagation();
			if ( dragRef ) { dragRef.current = { id: node.id, type: node.type }; }
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData( 'loom/move', node.id );
		}
		function endDrag() { if ( dragRef ) { dragRef.current = null; } }

		// Accept a same-type node dropped on this one (reorder before it).
		function reorderOver( e ) {
			var d = dragRef && dragRef.current;
			if ( d && d.type === node.type && d.id !== node.id ) {
				e.preventDefault(); e.stopPropagation();
				e.currentTarget.classList.add( 'loom-reorder-target' );
			}
		}
		function reorderLeave( e ) { e.currentTarget.classList.remove( 'loom-reorder-target' ); }
		function reorderDrop( e ) {
			var d = dragRef && dragRef.current;
			e.currentTarget.classList.remove( 'loom-reorder-target' );
			if ( d && d.type === node.type && d.id !== node.id ) {
				e.preventDefault(); e.stopPropagation();
				props.onMoveBefore( d.id, node.id );
			}
		}

		var handle = el( 'span', { className: 'loom-drag-handle', draggable: true, title: t.dragMove || 'Drag to move', onDragStart: startDrag, onDragEnd: endDrag, onClick: function ( e ) { e.stopPropagation(); } }, '✥' );
		var toolbar = selected ? el( 'div', { className: 'loom-node-toolbar' },
			handle,
			node.type === 'section' ? el( 'button', { title: t.exportSection || 'Export section', onClick: function ( e ) { e.stopPropagation(); props.onExport( node.id ); } }, '⤓' ) : null,
			el( 'button', { title: t.duplicate, onClick: function ( e ) { e.stopPropagation(); props.onDuplicate( node.id ); } }, '⎘' ),
			el( 'button', { title: t.delete, onClick: function ( e ) { e.stopPropagation(); props.onDelete( node.id ); } }, '🗑' )
		) : null;

		if ( node.type === 'section' ) {
			return el( 'section', {
				className: 'loom-c-section' + ( selected ? ' is-selected' : '' ), style: style, onClick: selectThis,
				onDragOver: reorderOver, onDragLeave: reorderLeave, onDrop: reorderDrop,
			},
				toolbar,
				el( 'div', { className: 'loom-c-columns', style: { display: 'flex', gap: style.gap || '20px', alignItems: style.alignItems || 'stretch' } },
					node.children.map( function ( col ) {
						return el( NodeView, Object.assign( {}, props, { key: col.id, node: col } ) );
					} )
				),
				el( 'button', { className: 'loom-add-col', onClick: function ( e ) { e.stopPropagation(); props.onAddColumn( node.id ); } }, '+ ' + ( t.column || 'Column' ) )
			);
		}

		if ( node.type === 'column' ) {
			return el( 'div', {
				className: 'loom-c-column' + ( selected ? ' is-selected' : '' ),
				style: Object.assign( { flex: '1 1 0', minHeight: '40px' }, style ),
				onClick: selectThis,
				onDragOver: function ( e ) {
					var d = dragRef && dragRef.current;
					if ( d && d.type === 'column' && d.id !== node.id ) { return reorderOver( e ); }
					// Otherwise accept widget create / widget move into this column.
					e.preventDefault(); e.currentTarget.classList.add( 'loom-drop' );
				},
				onDragLeave: function ( e ) { e.currentTarget.classList.remove( 'loom-drop' ); reorderLeave( e ); },
				onDrop: function ( e ) {
					var d = dragRef && dragRef.current;
					if ( d && d.type === 'column' && d.id !== node.id ) { return reorderDrop( e ); }
					e.preventDefault(); e.stopPropagation(); e.currentTarget.classList.remove( 'loom-drop' );
					var wid = e.dataTransfer.getData( 'loom/widget' );
					if ( wid ) { props.onAddWidget( node.id, wid ); }
					else if ( d && d.type === 'widget' ) { props.onMoveInto( d.id, node.id ); }
				},
			},
				toolbar,
				node.children.length ? node.children.map( function ( w ) {
					return el( NodeView, Object.assign( {}, props, { key: w.id, node: w } ) );
				} ) : el( 'div', { className: 'loom-col-empty' }, t.empty || 'Drag a widget here' )
			);
		}

		// widget
		return el( 'div', {
			className: 'loom-c-widget' + ( selected ? ' is-selected' : '' ), style: style, onClick: selectThis,
			onDragOver: reorderOver, onDragLeave: reorderLeave, onDrop: reorderDrop,
		},
			toolbar,
			el( 'div', { className: 'loom-c-widget-inner' }, widgetPreview( node ) )
		);
	}

	function Canvas( props ) {
		var device = props.device;
		var width = device === 'mobile' ? 390 : ( device === 'tablet' ? 768 : '100%' );
		return el( 'div', { className: 'loom-canvas', onClick: function () { props.onSelect( null ); } },
			el( 'div', { className: 'loom-canvas-frame loom-device-' + device, style: { width: width, maxWidth: '100%', margin: '0 auto' } },
				props.tree.length ? props.tree.map( function ( s ) {
					return el( NodeView, Object.assign( {}, props, { key: s.id, node: s } ) );
				} ) : el( 'div', { className: 'loom-canvas-empty' }, el( 'p', null, t.pageEmpty || 'Your page is empty.' ) ),
				el( 'button', { className: 'loom-add-section', onClick: function ( e ) { e.stopPropagation(); props.onAddSection(); } }, '+ ' + ( t.addSection || 'Add Section' ) )
			)
		);
	}

	function WidgetPanel( props ) {
		var search = props.search, setSearch = props.setSearch;
		var cats = cfg.categories || {};
		var list = ( cfg.widgets || [] ).filter( function ( w ) {
			return ! search || w.title.toLowerCase().indexOf( search.toLowerCase() ) !== -1;
		} );
		var grouped = {};
		list.forEach( function ( w ) { ( grouped[ w.category ] = grouped[ w.category ] || [] ).push( w ); } );

		return el( 'div', { className: 'loom-widgets' },
			el( 'input', { className: 'loom-search', type: 'search', placeholder: t.search || 'Search widget...', value: search, onChange: function ( e ) { setSearch( e.target.value ); } } ),
			Object.keys( grouped ).map( function ( cat ) {
				return el( 'div', { className: 'loom-wcat', key: cat },
					el( 'div', { className: 'loom-wcat-title' }, cats[ cat ] || cat ),
					el( 'div', { className: 'loom-wgrid' },
						grouped[ cat ].map( function ( w ) {
							return el( 'div', {
								key: w.id, className: 'loom-wcard', draggable: true,
								onDragStart: function ( e ) { e.dataTransfer.setData( 'loom/widget', w.id ); },
								onClick: function () { props.onAdd( w.id ); },
								title: w.title,
							},
								el( 'span', { className: 'dashicons dashicons-' + w.icon } ),
								el( 'span', { className: 'loom-wcard-label' }, w.title )
							);
						} )
					)
				);
			} )
		);
	}

	L.NodeView = NodeView;
	L.Canvas = Canvas;
	L.WidgetPanel = WidgetPanel;

} )();
