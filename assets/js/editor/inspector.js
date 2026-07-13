/**
 * Loom Builder editor — Inspector (Content / Style / Advanced panels).
 *
 * @package Loom
 */
( function () {
	'use strict';

	var L = window.LoomEd;
	if ( ! L ) { return; }

	var el = L.el, Fragment = L.Fragment, t = L.t, clone = L.clone, widgetDef = L.widgetDef, c = L.controls, ContentControl = L.ContentControl;

	/**
	 * Section content width: site default, or a custom px / % value (100% is
	 * an edge-to-edge "full width" section). The outer section box always
	 * stays full-bleed for backgrounds; this only sizes the inner content.
	 */
	function SectionWidthControl( props ) {
		var raw = props.value || '';
		var unit = raw.indexOf( '%' ) !== -1 ? 'pct' : ( raw ? 'px' : '' );
		var num = raw ? parseFloat( raw ) : ( unit === 'pct' ? 100 : 1200 );

		function setMode( mode ) {
			if ( ! mode ) { props.setStyle( 'maxWidth', '' ); }
			else if ( mode === 'px' ) { props.setStyle( 'maxWidth', ( unit === 'pct' ? 1200 : num ) + 'px' ); }
			else { props.setStyle( 'maxWidth', ( unit === 'px' ? 100 : num ) + '%' ); }
		}

		return el( Fragment, null,
			el( c.SelectControl, {
				label: t.contentWidth || 'Content width',
				value: unit,
				options: {
					'': t.contentWidthDefault || 'Site default',
					px: t.contentWidthPx || 'Custom (px)',
					pct: t.contentWidthPct || 'Custom (%) / full width',
				},
				onChange: setMode,
			} ),
			unit ? el( c.RangeControl, {
				label: t.maxWidth || 'Max width',
				value: num,
				min: unit === 'pct' ? 10 : 200,
				max: unit === 'pct' ? 100 : 2400,
				onChange: function ( v ) { props.setStyle( 'maxWidth', v + ( unit === 'pct' ? '%' : 'px' ) ); },
			} ) : null
		);
	}

	function StylePanel( props ) {
		var node = props.node, device = props.device, update = props.update;
		var st = ( node.settings._style && node.settings._style[ device ] ) || {};
		function setStyle( key, value ) {
			update( function ( n ) {
				var s = clone( n.settings );
				if ( ! s._style ) { s._style = { desktop: {}, tablet: {}, mobile: {} }; }
				if ( ! s._style[ device ] ) { s._style[ device ] = {}; }
				if ( value === '' || value == null ) { delete s._style[ device ][ key ]; }
				else { s._style[ device ][ key ] = value; }
				return Object.assign( {}, n, { settings: s } );
			} );
		}
		var isText = node.type === 'widget';
		var isSection = node.type === 'section';
		var isColumn = node.type === 'column';
		var isContainer = isSection || isColumn;
		return el( Fragment, null,
			el( 'h4', null, t.style || 'Style' ),
			el( c.BoxControl, { label: t.padding || 'Padding', value: st.padding, onChange: function ( v ) { setStyle( 'padding', v ); } } ),
			el( c.BoxControl, { label: t.margin || 'Margin', value: st.margin, onChange: function ( v ) { setStyle( 'margin', v ); } } ),
			el( c.ColorControl, { label: t.background || 'Background', value: st.bgColor, onChange: function ( v ) { setStyle( 'bgColor', v ); } } ),
			isContainer ? el( c.MediaControl, { label: t.backgroundImage || 'Background image', value: st.bgImage, preview: st.bgImage, onChange: function ( media ) { setStyle( 'bgImage', media.url ); }, onClear: function () { setStyle( 'bgImage', '' ); } } ) : null,
			( isContainer && st.bgImage ) ? el( c.SelectControl, { label: t.bgSize || 'Background size', value: st.bgSize || 'cover', options: { cover: t.bgSizeCover || 'Cover', contain: t.bgSizeContain || 'Contain', auto: t.bgSizeAuto || 'Auto' }, onChange: function ( v ) { setStyle( 'bgSize', v ); } } ) : null,
			el( c.SelectControl, { label: t.textAlign || 'Text align', value: st.align || '', options: { '': '—', left: t.left || 'Left', center: t.center || 'Center', right: t.right || 'Right' }, onChange: function ( v ) { setStyle( 'align', v ); } } ),
			isText ? el( c.ColorControl, { label: t.textColor || 'Text color', value: st.color, onChange: function ( v ) { setStyle( 'color', v ); } } ) : null,
			isText ? el( c.RangeControl, { label: t.fontSize || 'Font size', value: st.fontSize, min: 8, max: 120, onChange: function ( v ) { setStyle( 'fontSize', v ); } } ) : null,
			isText ? el( c.SelectControl, { label: t.fontWeight || 'Font weight', value: st.fontWeight || '', options: { '': '—', '300': '300', '400': '400', '500': '500', '600': '600', '700': '700', '800': '800', '900': '900' }, onChange: function ( v ) { setStyle( 'fontWeight', v ); } } ) : null,
			el( c.RangeControl, { label: t.minHeight || 'Min height', value: st.minHeight, min: 0, max: 900, onChange: function ( v ) { setStyle( 'minHeight', v ); } } ),
			el( c.RangeControl, { label: t.radius || 'Radius', value: st.radius, min: 0, max: 100, onChange: function ( v ) { setStyle( 'radius', v ); } } ),
			isSection ? el( SectionWidthControl, { value: st.maxWidth, setStyle: setStyle } ) : null,
			isSection ? el( c.RangeControl, { label: t.columnsGap || 'Columns gap', value: st.gap, min: 0, max: 80, onChange: function ( v ) { setStyle( 'gap', v ); } } ) : null,
			isSection ? el( c.SelectControl, { label: t.columnsLayout || 'Columns layout', value: st.direction || ( device === 'mobile' ? 'column' : 'row' ), options: { row: t.columnsRow || 'Row', column: t.columnsStack || 'Stack' }, onChange: function ( v ) { setStyle( 'direction', v ); } } ) : null,
			isSection ? el( c.SelectControl, { label: t.columnsHorizontal || 'Columns horizontal alignment', value: st.justify || 'flex-start', options: { 'flex-start': t.alignStart || 'Start', center: t.center || 'Center', 'flex-end': t.alignEnd || 'End', 'space-between': t.alignSpaceBetween || 'Space between' }, onChange: function ( v ) { setStyle( 'justify', v ); } } ) : null,
			isSection ? el( c.SelectControl, { label: t.columnsVertical || 'Columns vertical alignment', value: st.valign || 'stretch', options: { stretch: t.alignStretch || 'Stretch', 'flex-start': t.alignTop || 'Top', center: t.center || 'Center', 'flex-end': t.alignBottom || 'Bottom' }, onChange: function ( v ) { setStyle( 'valign', v ); } } ) : null,
			isColumn ? el( c.RangeControl, { label: t.columnWidth || 'Column width % (0 = auto)', value: st.width ? parseFloat( st.width ) : 0, min: 0, max: 90, onChange: function ( v ) { setStyle( 'width', v > 0 ? v + '%' : '' ); } } ) : null,
			isColumn ? el( c.SelectControl, { label: t.horizontalAlign || 'Horizontal alignment', value: st.valign || 'stretch', options: { stretch: t.alignStretch || 'Stretch', 'flex-start': t.alignStart || 'Start', center: t.center || 'Center', 'flex-end': t.alignEnd || 'End' }, onChange: function ( v ) { setStyle( 'valign', v ); } } ) : null,
			isColumn ? el( c.SelectControl, { label: t.verticalAlign || 'Vertical alignment', value: st.justify || 'flex-start', options: { 'flex-start': t.alignStart || 'Start', center: t.center || 'Center', 'flex-end': t.alignEnd || 'End', 'space-between': t.alignSpaceBetween || 'Space between' }, onChange: function ( v ) { setStyle( 'justify', v ); } } ) : null,
			// Some widgets (Testimonial, Alert, Contact Form, Team Member...) lay
			// their own content out with an internal flex box; these two target
			// that inner root, not the outer .loom-node wrapper (see generator.php).
			isText ? el( c.SelectControl, { label: t.contentValign || 'Content horizontal alignment', value: st.valign || '', options: { '': '—', stretch: t.alignStretch || 'Stretch', 'flex-start': t.alignStart || 'Start', center: t.center || 'Center', 'flex-end': t.alignEnd || 'End' }, onChange: function ( v ) { setStyle( 'valign', v ); } } ) : null,
			isText ? el( c.SelectControl, { label: t.contentJustify || 'Content vertical alignment', value: st.justify || '', options: { '': '—', 'flex-start': t.alignTop || 'Top', center: t.center || 'Center', 'flex-end': t.alignBottom || 'Bottom', 'space-between': t.alignSpaceBetween || 'Space between' }, onChange: function ( v ) { setStyle( 'justify', v ); } } ) : null,
			( L.styleExtensions || [] ).map( function ( render, index ) {
				return render( { key: index, node: node, device: device, style: st, setStyle: setStyle } );
			} )
		);
	}

	function AdvancedPanel( props ) {
		var node = props.node, update = props.update;
		var adv = node.settings._advanced || {};
		function setAdv( key, value ) {
			update( function ( n ) {
				var s = clone( n.settings );
				if ( ! s._advanced ) { s._advanced = {}; }
				s._advanced[ key ] = value;
				return Object.assign( {}, n, { settings: s } );
			} );
		}
		var none = t.none || 'None';
		return el( Fragment, null,
			el( 'h4', null, t.advanced || 'Advanced' ),
			el( c.TextControl, { label: t.cssId || 'CSS ID', value: adv.cssId, onChange: function ( v ) { setAdv( 'cssId', v ); } } ),
			el( c.TextControl, { label: t.cssClasses || 'CSS Classes', value: adv.cssClass, onChange: function ( v ) { setAdv( 'cssClass', v ); } } ),
			el( c.SelectControl, { label: t.entranceAnimation || 'Entrance animation', value: adv.animation || '', options: {
				'': none,
				'fade': 'Fade', 'fade-up': 'Fade up', 'fade-down': 'Fade down', 'fade-left': 'Fade left', 'fade-right': 'Fade right',
				'slide-up': 'Slide up', 'slide-down': 'Slide down', 'slide-left': 'Slide left', 'slide-right': 'Slide right',
				'zoom-in': 'Zoom in', 'zoom-out': 'Zoom out', 'rotate': 'Rotate', 'blur': 'Blur', 'bounce': 'Bounce',
				'flip-x': 'Flip X', 'flip-y': 'Flip Y'
			}, onChange: function ( v ) { setAdv( 'animation', v ); } } ),
			adv.animation ? el( c.RangeControl, { label: t.duration || 'Duration (ms)', value: adv.animationDuration || 600, min: 100, max: 3000, onChange: function ( v ) { setAdv( 'animationDuration', v ); } } ) : null,
			adv.animation ? el( c.NumberControl, { label: t.delay || 'Delay (ms)', value: adv.animationDelay, onChange: function ( v ) { setAdv( 'animationDelay', v ); } } ) : null,
			adv.animation ? el( c.SelectControl, { label: t.easing || 'Easing', value: adv.animationEasing || 'smooth', options: { 'smooth': 'Smooth', 'ease': 'Ease', 'ease-in': 'Ease in', 'ease-out': 'Ease out', 'ease-in-out': 'Ease in-out', 'linear': 'Linear', 'back': 'Back (overshoot)', 'spring': 'Spring' }, onChange: function ( v ) { setAdv( 'animationEasing', v ); } } ) : null,
			el( c.SelectControl, { label: t.loopAnimation || 'Loop animation', value: adv.loopAnimation || '', options: { '': none, 'pulse': 'Pulse', 'float': 'Float', 'bounce': 'Bounce', 'spin': 'Spin', 'shake': 'Shake', 'swing': 'Swing' }, onChange: function ( v ) { setAdv( 'loopAnimation', v ); } } ),
			el( c.SelectControl, { label: t.hoverAnimation || 'Hover animation', value: adv.hoverAnimation || '', options: { '': none, 'grow': 'Grow', 'shrink': 'Shrink', 'lift': 'Lift', 'rotate': 'Rotate', 'float': 'Float', 'pulse': 'Pulse', 'shadow': 'Shadow', 'bright': 'Brighten' }, onChange: function ( v ) { setAdv( 'hoverAnimation', v ); } } ),
			el( c.ToggleControl, { label: t.hideDesktop || 'Hide on desktop', value: adv.hideDesktop, onChange: function ( v ) { setAdv( 'hideDesktop', v ); } } ),
			el( c.ToggleControl, { label: t.hideTablet || 'Hide on tablet', value: adv.hideTablet, onChange: function ( v ) { setAdv( 'hideTablet', v ); } } ),
			el( c.ToggleControl, { label: t.hideMobile || 'Hide on mobile', value: adv.hideMobile, onChange: function ( v ) { setAdv( 'hideMobile', v ); } } )
		);
	}

	function Inspector( props ) {
		var node = props.node;
		var tab = props.tab, setTab = props.setTab;
		if ( ! node ) {
			return el( 'div', { className: 'loom-inspector loom-empty-inspector' }, el( 'p', null, t.selectElement || 'Select an element to edit it.' ) );
		}
		var def = node.type === 'widget' ? widgetDef( node.widget ) : null;
		var title = def ? def.title : ( node.type === 'section' ? ( t.section || 'Section' ) : ( t.column || 'Column' ) );

		function update( fn ) {
			props.onChange( props.applyToNode( node.id, fn ) );
		}
		function setSetting( key, value ) {
			update( function ( n ) {
				var s = clone( n.settings ); s[ key ] = value; return Object.assign( {}, n, { settings: s } );
			} );
		}
		function setMany( obj ) {
			update( function ( n ) {
				var s = Object.assign( clone( n.settings ), obj ); return Object.assign( {}, n, { settings: s } );
			} );
		}
		function setDynamic( key, field ) {
			update( function ( n ) {
				var s = clone( n.settings );
				if ( ! s._dynamic ) { s._dynamic = {}; }
				if ( field ) { s._dynamic[ key ] = field; } else { delete s._dynamic[ key ]; }
				return Object.assign( {}, n, { settings: s } );
			} );
		}

		var contentControls = [];
		if ( def && def.controls ) {
			Object.keys( def.controls ).forEach( function ( name ) {
				var ctl = def.controls[ name ];
				if ( ( ctl.section || 'content' ) !== ( tab === 'content' ? 'content' : 'style' ) && tab !== 'content' && tab !== 'style' ) { return; }
				if ( tab === 'content' && ( ctl.section || 'content' ) !== 'content' ) { return; }
				if ( tab === 'style' && ( ctl.section || 'content' ) !== 'style' ) { return; }
				contentControls.push( el( ContentControl, {
					key: name, control: ctl, name: name, value: node.settings[ name ], node: node,
					set: function ( v ) { setSetting( name, v ); }, setMany: setMany, setDynamic: setDynamic,
				} ) );
			} );
		}

		var extensionControls = ( L.contentExtensions || [] ).map( function ( render, index ) {
			return render( { key: index, node: node, setSetting: setSetting, setMany: setMany } );
		} );

		return el( 'div', { className: 'loom-inspector' },
			el( 'div', { className: 'loom-inspector-head' }, title ),
			el( 'div', { className: 'loom-tabs' },
				[ 'content', 'style', 'advanced' ].map( function ( id ) {
					return el( 'button', { key: id, className: 'loom-tab' + ( tab === id ? ' is-active' : '' ), onClick: function () { setTab( id ); } }, t[ id ] || id );
				} )
			),
			el( 'div', { className: 'loom-inspector-body' },
				tab === 'content' ? el( Fragment, null, contentControls.length ? contentControls : ( node.type === 'section' ? el( 'p', { className: 'loom-muted' }, t.adjustLayout || 'Adjust layout in the Style tab.' ) : null ), extensionControls ) : null,
				tab === 'style' ? el( Fragment, null, contentControls, el( StylePanel, { node: node, device: props.device, update: update } ) ) : null,
				tab === 'advanced' ? el( AdvancedPanel, { node: node, update: update } ) : null
			)
		);
	}

	L.Inspector = Inspector;

} )();
