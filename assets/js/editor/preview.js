/**
 * Loom Builder editor — widget preview.
 *
 * In-canvas approximation of the PHP renderer. Intentionally loose: it is a
 * visual hint while editing, not the authoritative output (that is PHP).
 *
 * @package Loom
 */
( function () {
	'use strict';

	var L = window.LoomEd;
	if ( ! L ) { return; }

	var el = L.el, t = L.t, widgetDef = L.widgetDef;

	L.widgetPreview = function widgetPreview( node ) {
		var s = node.settings || {};
		var extensions = L.widgetPreviewExtensions || [];
		for ( var i = 0; i < extensions.length; i++ ) {
			var extensionPreview = extensions[ i ]( node );
			if ( extensionPreview ) { return extensionPreview; }
		}
		switch ( node.widget ) {
			case 'heading':
				var tag = s.tag || 'h2';
				return el( tag, { className: 'loom-heading' }, s.text || '' );
			case 'text':
				return el( 'div', { className: 'loom-text', dangerouslySetInnerHTML: { __html: s.content || '' } } );
			case 'image':
				if ( s.preview || s.url ) {
					return el( 'figure', { className: 'loom-image' }, el( 'img', { src: s.preview || s.url, alt: s.alt || '' } ), s.caption ? el( 'figcaption', null, s.caption ) : null );
				}
				return el( 'div', { className: 'loom-image-placeholder' }, '🖼 ' + ( t.selectMedia || 'Select image' ) );
			case 'button':
				var bs = { padding: s.padding || '12px 28px', borderRadius: ( s.radius || 8 ) + 'px' };
				if ( s.style === 'outline' ) { bs.background = 'transparent'; bs.color = s.bg; bs.border = '2px solid ' + s.bg; }
				else if ( s.style === 'ghost' ) { bs.background = 'transparent'; bs.color = s.bg; bs.border = '2px solid transparent'; }
				else { bs.background = s.bg; bs.color = s.fg; bs.border = '2px solid ' + s.bg; }
				return el( 'span', { className: 'loom-button', style: bs }, s.text || '' );
			case 'spacer':
				return el( 'div', { className: 'loom-spacer', style: { height: ( s.height || 50 ) + 'px', outline: '1px dashed #c7d2fe' } } );
			case 'html':
				return s.code ? el( 'div', { className: 'loom-html', dangerouslySetInnerHTML: { __html: s.code } } ) : el( 'div', { className: 'loom-prev-empty' }, '</> HTML — add code' );
			case 'shortcode':
				return el( 'div', { className: 'loom-prev-filter' }, el( 'strong', null, '[…] Shortcode' ), el( 'div', { className: 'loom-prev-fmeta' }, s.shortcode || 'Add a shortcode' ) );
			case 'video':
				var vlabel = ( s.source || 'youtube' ) + ( s.url ? ' · ' + s.url : '' );
				return el( 'div', { className: 'loom-prev-video', style: { aspectRatio: ( s.aspect || '16:9' ).replace( ':', ' / ' ) } },
					el( 'span', { className: 'loom-prev-play' }, '▶' ),
					el( 'div', { className: 'loom-prev-fmeta' }, vlabel )
				);
			case 'divider':
				return el( 'div', { style: { textAlign: s.align || 'center', padding: ( s.gap || 16 ) + 'px 0' } },
					el( 'span', { style: { display: 'inline-block', width: ( s.width || 100 ) + '%', borderTop: ( s.thickness || 1 ) + 'px ' + ( s.style || 'solid' ) + ' ' + ( s.color || '#e5e7eb' ) } } )
				);
			case 'icon_box':
				return el( 'div', { className: 'loom-icon-box loom-icon-box-' + ( s.align || 'center' ), style: { textAlign: s.align || 'center' } },
					el( 'span', { className: 'dashicons dashicons-' + ( s.icon || 'star-filled' ), style: { fontSize: ( s.iconSize || 40 ) + 'px', width: ( s.iconSize || 40 ) + 'px', height: ( s.iconSize || 40 ) + 'px', color: s.iconColor || '#2563eb' } } ),
					s.title ? el( 'h3', { className: 'loom-icon-box-title' }, s.title ) : null,
					s.text ? el( 'p', { className: 'loom-icon-box-text' }, s.text ) : null
				);
			case 'accordion':
				var accItems = Array.isArray( s.items ) ? s.items : [];
				if ( ! accItems.length ) { return el( 'div', { className: 'loom-prev-empty' }, '☰ Accordion — add items' ); }
				return el( 'div', { className: 'loom-prev-accordion' }, accItems.map( function ( it, i ) {
					return el( 'div', { key: i, className: 'loom-prev-acc-row' }, el( 'strong', null, it.title || ( 'Item ' + ( i + 1 ) ) ), el( 'span', null, '＋' ) );
				} ) );
			case 'tabs':
				var tabItems = Array.isArray( s.items ) ? s.items : [];
				if ( ! tabItems.length ) { return el( 'div', { className: 'loom-prev-empty' }, '▭ Tabs — add tabs' ); }
				return el( 'div', { className: 'loom-prev-tabs' },
					el( 'div', { className: 'loom-prev-tabs-nav' }, tabItems.map( function ( it, i ) {
						return el( 'span', { key: i, className: 'loom-prev-tab' + ( i === 0 ? ' is-active' : '' ) }, it.title || ( 'Tab ' + ( i + 1 ) ) );
					} ) ),
					el( 'div', { className: 'loom-prev-tab-body' }, ( tabItems[ 0 ] && tabItems[ 0 ].content ) || '' )
				);
			case 'menu':
				return el( 'div', { className: 'loom-prev-filter' }, el( 'strong', null, '☰ Nav Menu' ), el( 'div', { className: 'loom-prev-fmeta' }, s.menu ? ( 'Menu #' + s.menu ) : 'Select a menu' ) );
			case 'site_logo':
				return ( s.image && s.image.url ) ? el( 'img', { src: s.image.url, style: { width: ( s.width || 120 ) + 'px', height: 'auto' } } ) : el( 'div', { className: 'loom-prev-filter' }, el( 'strong', null, '🏠 Site Logo' ) );
			case 'site_title':
				return el( 'div', null, el( 'strong', { style: { fontSize: '20px' } }, 'Site Title' ), s.showTagline ? el( 'div', { style: { fontSize: '13px', color: '#6b7280' } }, 'Tagline' ) : null );
			case 'search':
				return el( 'div', { className: 'loom-prev-search' }, el( 'span', null, '🔍 ' + ( s.placeholder || 'Search…' ) ), el( 'span', { className: 'loom-button', style: { fontSize: '12px', padding: '4px 12px' } }, s.button || 'Search' ) );
			case 'social':
				var soc = Array.isArray( s.items ) ? s.items : [];
				if ( ! soc.length ) { return el( 'div', { className: 'loom-prev-empty' }, '◎ Social — add links' ); }
				return el( 'div', { className: 'loom-prev-social' }, soc.map( function ( it, i ) {
					return el( 'span', { key: i, className: 'loom-prev-soc' }, ( it.network || '?' ).slice( 0, 2 ) );
				} ) );
			case 'posts':
				var pcount = Math.min( 3, Math.max( 1, parseInt( s.colsD, 10 ) || 3 ) );
				return el( 'div', { className: 'loom-prev-products', style: { gridTemplateColumns: 'repeat(' + pcount + ',1fr)' } },
					[ 0, 1, 2 ].slice( 0, pcount ).map( function ( i ) {
						return el( 'div', { key: i, className: 'loom-prev-prod' },
							s.showImage !== false ? el( 'div', { className: 'loom-prev-ph' } ) : null,
							el( 'div', { className: 'loom-prev-pline' } ),
							s.showExcerpt !== false ? el( 'div', { className: 'loom-prev-pline', style: { width: '70%' } } ) : null
						);
					} ),
					el( 'div', { className: 'loom-prev-badge2' }, 'Posts: ' + ( s.postType || 'post' ) )
				);
			case 'slider':
				var slides = Array.isArray( s.slides ) ? s.slides : [];
				if ( ! slides.length ) { return el( 'div', { className: 'loom-prev-empty' }, '▭ Slider — add slides' ); }
				var first = slides[ 0 ];
				var bg = first.image && first.image.url ? first.image.url : '';
				return el( 'div', { className: 'loom-prev-slider', style: { height: Math.min( 320, s.height || 480 ) + 'px', backgroundImage: bg ? 'linear-gradient(rgba(0,0,0,.35),rgba(0,0,0,.35)),url(' + bg + ')' : 'none', backgroundColor: bg ? '' : '#1f2937' } },
					el( 'div', { className: 'loom-prev-slide-content' },
						first.title ? el( 'h2', null, first.title ) : null,
						first.text ? el( 'p', null, first.text ) : null,
						first.btnText ? el( 'span', { className: 'loom-button' }, first.btnText ) : null
					),
					el( 'div', { className: 'loom-prev-badge' }, '1 / ' + slides.length )
				);
			case 'carousel':
				var items = Array.isArray( s.items ) ? s.items : [];
				if ( ! items.length ) { return el( 'div', { className: 'loom-prev-empty' }, '▭▭▭ Carousel — add items' ); }
				var per = s.perD || 3;
				return el( 'div', { className: 'loom-prev-carousel' },
					items.slice( 0, per ).map( function ( it, i ) {
						return el( 'div', { key: i, className: 'loom-prev-citem', style: { flex: '1 1 0' } },
							it.image && it.image.url ? el( 'img', { src: it.image.url } ) : el( 'div', { className: 'loom-prev-ph' } ),
							it.caption ? el( 'div', { className: 'loom-prev-cap' }, it.caption ) : null
						);
					} )
				);
			case 'gallery':
				var imgs = Array.isArray( s.images ) ? s.images : [];
				if ( ! imgs.length ) { return el( 'div', { className: 'loom-prev-empty' }, '▦ Gallery — add images' ); }
				var cols = s.colsD || 3;
				return el( 'div', { className: 'loom-prev-gallery', style: { gridTemplateColumns: 'repeat(' + cols + ',1fr)', gap: ( s.gap || 12 ) + 'px' } },
					imgs.slice( 0, cols * 2 ).map( function ( im, i ) {
						return el( 'img', { key: i, src: im.url, style: { borderRadius: ( s.radius || 8 ) + 'px' } } );
					} )
				);
			case 'products':
				var pc = s.colsD || 4;
				return el( 'div', { className: 'loom-prev-products', style: { gridTemplateColumns: 'repeat(' + Math.min( pc, 4 ) + ',1fr)' } },
					[ 0, 1, 2, 3 ].slice( 0, Math.min( pc, 4 ) ).map( function ( i ) {
						return el( 'div', { key: i, className: 'loom-prev-prod' },
							el( 'div', { className: 'loom-prev-ph' } ),
							el( 'div', { className: 'loom-prev-pline' } ),
							el( 'div', { className: 'loom-prev-pprice' }, '$00.00' ),
							el( 'span', { className: 'loom-button', style: { fontSize: '11px', padding: '4px 10px' } }, 'Add to cart' )
						);
					} ),
					el( 'div', { className: 'loom-prev-badge2' }, 'Products: ' + ( s.category || 'all' ) )
				);
			case 'add_to_cart':
				return el( 'div', { className: 'loom-prev-atc' },
					s.showPrice ? el( 'span', { className: 'loom-prev-pprice' }, '$00.00' ) : null,
					el( 'span', { className: 'loom-button' }, 'Add to cart' )
				);
			case 'template':
				var def2 = widgetDef( 'template' );
				var opts = def2 && def2.controls && def2.controls.template_id ? def2.controls.template_id.options : {};
				var label2 = s.template_id && opts[ s.template_id ] ? opts[ s.template_id ] : 'No template selected';
				return el( 'div', { className: 'loom-prev-filter' }, el( 'strong', null, '⧉ Template' ), el( 'div', { className: 'loom-prev-fmeta' }, label2 ) );
			case 'product_filter':
				return el( 'div', { className: 'loom-prev-filter' },
					el( 'strong', null, '⛃ Product Filter' ),
					el( 'div', { className: 'loom-prev-fmeta' }, [ s.showCategories ? 'Categories' : null, s.showPrice ? 'Price' : null, s.attribute || null ].filter( Boolean ).join(' · ') || 'Configure in Content' )
				);
			default:
				var def = widgetDef( node.widget );
				return el( 'div', { className: 'loom-unknown' }, ( def && def.title ) || node.widget );
		}
	};

} )();
