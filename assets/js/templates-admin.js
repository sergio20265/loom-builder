/**
 * Display-conditions builder for loom_template. Vanilla JS, serializes rows to
 * the hidden #loom_template_conditions input on every change.
 *
 * @package Loom
 */
( function () {
	'use strict';

	var cfg = window.LoomTpl || {};
	var host = document.getElementById( 'loom-tpl-conditions' );
	var store = document.getElementById( 'loom_template_conditions' );
	if ( ! host || ! store ) { return; }

	var PARAMS = {
		entire_site: 'Entire site',
		front_page: 'Front page',
		post_type: 'Post type',
		post: 'Specific post (ID)',
		post_type_archive: 'Post type archive',
		is_404: '404 page',
		search: 'Search results',
	};

	var rows = [];
	try { rows = JSON.parse( store.value ) || []; } catch ( e ) { rows = []; }
	if ( ! Array.isArray( rows ) || ! rows.length ) {
		rows = [ { param: 'entire_site', operator: '==', value: '' } ];
	}

	function serialize() {
		store.value = JSON.stringify( rows );
	}

	function valueField( row, i ) {
		var needsType = row.param === 'post_type' || row.param === 'post_type_archive';
		if ( needsType ) {
			var sel = document.createElement( 'select' );
			var blank = document.createElement( 'option' ); blank.value = ''; blank.textContent = '—'; sel.appendChild( blank );
			Object.keys( cfg.postTypes || {} ).forEach( function ( k ) {
				var o = document.createElement( 'option' ); o.value = k; o.textContent = cfg.postTypes[ k ];
				if ( row.value === k ) { o.selected = true; }
				sel.appendChild( o );
			} );
			sel.addEventListener( 'change', function () { rows[ i ].value = sel.value; serialize(); } );
			return sel;
		}
		if ( row.param === 'post' ) {
			var inp = document.createElement( 'input' );
			inp.type = 'number'; inp.placeholder = 'Post ID'; inp.value = row.value || '';
			inp.addEventListener( 'input', function () { rows[ i ].value = inp.value; serialize(); } );
			return inp;
		}
		return null; // entire_site / front_page / is_404 / search need no value
	}

	function render() {
		host.innerHTML = '';
		rows.forEach( function ( row, i ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'loom-acf-rule';

			var pSel = document.createElement( 'select' );
			Object.keys( PARAMS ).forEach( function ( k ) {
				var o = document.createElement( 'option' ); o.value = k; o.textContent = PARAMS[ k ];
				if ( row.param === k ) { o.selected = true; }
				pSel.appendChild( o );
			} );
			pSel.addEventListener( 'change', function () { rows[ i ].param = pSel.value; rows[ i ].value = ''; serialize(); render(); } );

			var oSel = document.createElement( 'select' );
			[ [ '==', 'is' ], [ '!=', 'is not' ] ].forEach( function ( pair ) {
				var o = document.createElement( 'option' ); o.value = pair[ 0 ]; o.textContent = pair[ 1 ];
				if ( ( row.operator || '==' ) === pair[ 0 ] ) { o.selected = true; }
				oSel.appendChild( o );
			} );
			oSel.addEventListener( 'change', function () { rows[ i ].operator = oSel.value; serialize(); } );

			var del = document.createElement( 'button' );
			del.type = 'button'; del.className = 'button-link'; del.textContent = '×';
			del.addEventListener( 'click', function () { rows.splice( i, 1 ); if ( ! rows.length ) { rows.push( { param: 'entire_site', operator: '==', value: '' } ); } serialize(); render(); } );

			wrap.appendChild( pSel );
			wrap.appendChild( oSel );
			var vf = valueField( row, i );
			if ( vf ) { wrap.appendChild( vf ); }
			wrap.appendChild( del );
			host.appendChild( wrap );
		} );

		var add = document.createElement( 'button' );
		add.type = 'button'; add.className = 'button'; add.textContent = '+ Add condition';
		add.addEventListener( 'click', function () { rows.push( { param: 'entire_site', operator: '==', value: '' } ); serialize(); render(); } );
		host.appendChild( add );
	}

	serialize();
	render();
} )();
