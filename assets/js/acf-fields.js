/**
 * Value meta box enhancers: media pickers, gallery, repeater and color sync.
 * Vanilla JS, event-delegated so dynamically added rows work too.
 *
 * @package Loom
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( e ) {
		var target = e.target;

		// Single image picker.
		if ( target.classList.contains( 'loom-image-pick' ) ) {
			e.preventDefault();
			var wrap = target.closest( '[data-loom-image]' );
			var frame = window.wp.media( { title: 'Select image', multiple: false, library: { type: 'image' } } );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				wrap.querySelector( '.loom-image-id' ).value = att.id;
				var thumb = wrap.querySelector( '.loom-image-thumb' );
				if ( thumb ) { thumb.innerHTML = '<img src="' + ( att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url ) + '">'; }
			} );
			frame.open();
			return;
		}

		// Clear single image.
		if ( target.classList.contains( 'loom-image-clear' ) ) {
			e.preventDefault();
			var iwrap = target.closest( '[data-loom-image]' );
			iwrap.querySelector( '.loom-image-id' ).value = '';
			var ithumb = iwrap.querySelector( '.loom-image-thumb' );
			if ( ithumb ) { ithumb.innerHTML = ''; }
			return;
		}

		// Gallery picker (multiple).
		if ( target.classList.contains( 'loom-gallery-pick' ) ) {
			e.preventDefault();
			var gwrap = target.closest( '[data-loom-gallery]' );
			var input = gwrap.querySelector( '.loom-gallery-ids' );
			var thumbs = gwrap.querySelector( '.loom-gallery-thumbs' );
			var gframe = window.wp.media( { title: 'Add images', multiple: 'add', library: { type: 'image' } } );
			gframe.on( 'select', function () {
				var ids = input.value ? input.value.split( ',' ).filter( Boolean ) : [];
				gframe.state().get( 'selection' ).toJSON().forEach( function ( a ) {
					if ( ids.indexOf( String( a.id ) ) === -1 ) {
						ids.push( String( a.id ) );
						var url = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
						var span = document.createElement( 'span' );
						span.className = 'loom-gthumb';
						span.setAttribute( 'data-id', a.id );
						span.innerHTML = '<img src="' + url + '"><button type="button" class="loom-gthumb-rm">×</button>';
						thumbs.appendChild( span );
					}
				} );
				input.value = ids.join( ',' );
			} );
			gframe.open();
			return;
		}

		// Remove a gallery thumb.
		if ( target.classList.contains( 'loom-gthumb-rm' ) ) {
			e.preventDefault();
			var span = target.closest( '.loom-gthumb' );
			var gw = target.closest( '[data-loom-gallery]' );
			var id = span.getAttribute( 'data-id' );
			var gi = gw.querySelector( '.loom-gallery-ids' );
			gi.value = gi.value.split( ',' ).filter( function ( v ) { return v && v !== id; } ).join( ',' );
			span.parentNode.removeChild( span );
			return;
		}

		// Repeater add row.
		if ( target.classList.contains( 'loom-rep-add' ) ) {
			e.preventDefault();
			var rep = target.closest( '[data-loom-repeater]' );
			var tpl = rep.querySelector( '.loom-rep-tpl' ).innerHTML;
			var html = tpl.replace( /__i__/g, 'r' + Date.now() );
			var rows = rep.querySelector( '.loom-rep-rows' );
			var div = document.createElement( 'div' );
			div.innerHTML = html;
			rows.appendChild( div.firstElementChild );
			return;
		}

		// Repeater remove row.
		if ( target.classList.contains( 'loom-rep-remove' ) ) {
			e.preventDefault();
			var row = target.closest( '.loom-rep-row' );
			row.parentNode.removeChild( row );
			return;
		}
	} );

	// Keep the color text field in sync with the native picker.
	document.addEventListener( 'input', function ( e ) {
		if ( e.target.type === 'color' ) {
			var hex = e.target.parentNode.querySelector( '.loom-color-hex' );
			if ( hex ) { hex.value = e.target.value; }
		}
	} );
} )();
