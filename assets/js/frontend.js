/**
 * Loom Builder frontend runtime.
 *
 * Dependency-free. Phase 1 ships the entrance-animation observer; sliders,
 * carousels and the lightbox register through loom.register() in later phases.
 *
 * @package Loom
 */
( function () {
	'use strict';

	var loom = window.Loom = window.Loom || { modules: {} };

	/**
	 * Register a frontend module initializer, run on DOM ready and re-runnable.
	 *
	 * @param {string}   name Module id.
	 * @param {Function} init function( root ) called with a container element.
	 */
	loom.register = function ( name, init ) {
		loom.modules[ name ] = init;
	};

	// --- Entrance animations -------------------------------------------------

	// Named easings, including overshoot/spring curves not in the CSS spec.
	var EASINGS = {
		'ease': 'ease',
		'ease-in': 'ease-in',
		'ease-out': 'ease-out',
		'ease-in-out': 'ease-in-out',
		'linear': 'linear',
		'smooth': 'cubic-bezier(.25,.46,.45,.94)',
		'back': 'cubic-bezier(.34,1.56,.64,1)',
		'spring': 'cubic-bezier(.22,1.2,.36,1)',
	};

	function reveal( n ) {
		var delay = parseInt( n.getAttribute( 'data-loom-anim-delay' ), 10 ) || 0;
		var dur = parseInt( n.getAttribute( 'data-loom-anim-duration' ), 10 ) || 600;
		var ease = EASINGS[ n.getAttribute( 'data-loom-anim-ease' ) ] || 'cubic-bezier(.25,.46,.45,.94)';
		n.style.animationDuration = dur + 'ms';
		n.style.animationDelay = delay + 'ms';
		n.style.animationTimingFunction = ease;
		n.classList.add( 'loom-in' );
	}

	function initAnimations( root ) {
		var nodes = root.querySelectorAll( '.loom-anim[data-loom-anim]' );
		if ( ! nodes.length ) {
			return;
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			// Graceful fallback: just reveal everything.
			nodes.forEach( reveal );
			return;
		}

		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}
				reveal( entry.target );
				io.unobserve( entry.target );
			} );
		}, { threshold: 0.15 } );

		nodes.forEach( function ( n ) { io.observe( n ); } );
	}

	loom.register( 'animations', initAnimations );

	// --- Slider --------------------------------------------------------------

	function initSliders( root ) {
		root.querySelectorAll( '[data-loom-slider]' ).forEach( function ( box ) {
			if ( box.dataset.loomReady ) { return; }
			box.dataset.loomReady = '1';

			var slides = Array.prototype.slice.call( box.querySelectorAll( '.loom-slide' ) );
			if ( slides.length < 1 ) { return; }

			var effect = box.getAttribute( 'data-effect' ) || 'slide';
			var autoplay = box.getAttribute( 'data-autoplay' ) === '1';
			var interval = parseInt( box.getAttribute( 'data-interval' ), 10 ) || 5000;
			var current = 0;
			var timer = null;

			function layout() {
				slides.forEach( function ( sl, i ) {
					var off = i - current;
					sl.style.transition = 'transform .6s ease, opacity .6s ease';
					sl.style.position = 'absolute';
					sl.style.inset = '0';
					if ( effect === 'fade' ) {
						sl.style.transform = 'none';
						sl.style.opacity = i === current ? '1' : '0';
						sl.style.zIndex = i === current ? '2' : '1';
					} else if ( effect === 'cards' ) {
						sl.style.opacity = Math.abs( off ) <= 1 ? ( off === 0 ? '1' : '.5' ) : '0';
						sl.style.transform = 'translateX(' + ( off * 55 ) + '%) scale(' + ( off === 0 ? 1 : 0.82 ) + ')';
						sl.style.zIndex = String( 10 - Math.abs( off ) );
					} else {
						sl.style.opacity = '1';
						sl.style.transform = 'translateX(' + ( off * 100 ) + '%)';
						sl.style.zIndex = '1';
					}
				} );
				if ( dots ) {
					Array.prototype.forEach.call( dots.children, function ( d, i ) {
						d.classList.toggle( 'is-active', i === current );
					} );
				}
			}

			function go( i ) {
				current = ( i + slides.length ) % slides.length;
				layout();
			}
			function next() { go( current + 1 ); }
			function prev() { go( current - 1 ); }

			// Dots.
			var dots = box.querySelector( '.loom-slider-dots' );
			if ( dots ) {
				slides.forEach( function ( s, i ) {
					var b = document.createElement( 'button' );
					b.className = 'loom-dot';
					b.setAttribute( 'aria-label', 'Slide ' + ( i + 1 ) );
					b.addEventListener( 'click', function () { go( i ); restart(); } );
					dots.appendChild( b );
				} );
			}

			var pn = box.querySelector( '.loom-slider-next' );
			var pp = box.querySelector( '.loom-slider-prev' );
			if ( pn ) { pn.addEventListener( 'click', function () { next(); restart(); } ); }
			if ( pp ) { pp.addEventListener( 'click', function () { prev(); restart(); } ); }

			// Touch swipe.
			var sx = 0;
			box.addEventListener( 'touchstart', function ( e ) { sx = e.touches[ 0 ].clientX; }, { passive: true } );
			box.addEventListener( 'touchend', function ( e ) {
				var dx = e.changedTouches[ 0 ].clientX - sx;
				if ( Math.abs( dx ) > 40 ) { dx < 0 ? next() : prev(); restart(); }
			}, { passive: true } );

			function start() { if ( autoplay && slides.length > 1 ) { timer = setInterval( next, interval ); } }
			function stop() { if ( timer ) { clearInterval( timer ); timer = null; } }
			function restart() { stop(); start(); }
			box.addEventListener( 'mouseenter', stop );
			box.addEventListener( 'mouseleave', start );

			layout();
			start();
		} );
	}

	loom.register( 'slider', initSliders );

	// --- Carousel ------------------------------------------------------------

	function initCarousels( root ) {
		root.querySelectorAll( '[data-loom-carousel]' ).forEach( function ( box ) {
			if ( box.dataset.loomReady ) { return; }
			box.dataset.loomReady = '1';

			var track = box.querySelector( '.loom-carousel-track' );
			var items = Array.prototype.slice.call( box.querySelectorAll( '.loom-carousel-item' ) );
			if ( ! track || ! items.length ) { return; }

			var perD = parseInt( box.getAttribute( 'data-d' ), 10 ) || 3;
			var perT = parseInt( box.getAttribute( 'data-t' ), 10 ) || 2;
			var perM = parseInt( box.getAttribute( 'data-m' ), 10 ) || 1;
			var gap = parseInt( box.getAttribute( 'data-gap' ), 10 ) || 0;
			var autoplay = box.getAttribute( 'data-autoplay' ) === '1';
			var interval = parseInt( box.getAttribute( 'data-interval' ), 10 ) || 4000;
			var loop = box.getAttribute( 'data-loop' ) === '1';
			var index = 0;
			var per = perD;
			var timer = null;
			var dots = box.querySelector( '.loom-carousel-dots' );

			function perNow() {
				var w = window.innerWidth;
				if ( w <= 767 ) { return perM; }
				if ( w <= 1024 ) { return perT; }
				return perD;
			}

			function maxIndex() { return Math.max( 0, items.length - per ); }

			function size() {
				per = perNow();
				var vw = box.querySelector( '.loom-carousel-viewport' ).clientWidth;
				var iw = ( vw - gap * ( per - 1 ) ) / per;
				items.forEach( function ( it ) { it.style.flex = '0 0 ' + iw + 'px'; it.style.maxWidth = iw + 'px'; } );
				if ( index > maxIndex() ) { index = maxIndex(); }
				buildDots();
				move();
			}

			function move() {
				var iw = items[ 0 ].getBoundingClientRect().width;
				track.style.transition = 'transform .5s ease';
				track.style.transform = 'translateX(' + ( -index * ( iw + gap ) ) + 'px)';
				if ( dots ) {
					Array.prototype.forEach.call( dots.children, function ( d, i ) { d.classList.toggle( 'is-active', i === index ); } );
				}
			}

			function go( i ) {
				var max = maxIndex();
				if ( i < 0 ) { index = loop ? max : 0; }
				else if ( i > max ) { index = loop ? 0 : max; }
				else { index = i; }
				move();
			}
			function next() { go( index + 1 ); }
			function prev() { go( index - 1 ); }

			function buildDots() {
				if ( ! dots ) { return; }
				dots.innerHTML = '';
				for ( var i = 0; i <= maxIndex(); i++ ) {
					( function ( i ) {
						var b = document.createElement( 'button' );
						b.className = 'loom-dot';
						b.addEventListener( 'click', function () { go( i ); restart(); } );
						dots.appendChild( b );
					} )( i );
				}
			}

			var pn = box.querySelector( '.loom-carousel-next' );
			var pp = box.querySelector( '.loom-carousel-prev' );
			if ( pn ) { pn.addEventListener( 'click', function () { next(); restart(); } ); }
			if ( pp ) { pp.addEventListener( 'click', function () { prev(); restart(); } ); }

			var sx = 0;
			track.addEventListener( 'touchstart', function ( e ) { sx = e.touches[ 0 ].clientX; }, { passive: true } );
			track.addEventListener( 'touchend', function ( e ) {
				var dx = e.changedTouches[ 0 ].clientX - sx;
				if ( Math.abs( dx ) > 40 ) { dx < 0 ? next() : prev(); restart(); }
			}, { passive: true } );

			function start() { if ( autoplay ) { timer = setInterval( next, interval ); } }
			function stop() { if ( timer ) { clearInterval( timer ); timer = null; } }
			function restart() { stop(); start(); }
			box.addEventListener( 'mouseenter', stop );
			box.addEventListener( 'mouseleave', start );

			var rt;
			window.addEventListener( 'resize', function () { clearTimeout( rt ); rt = setTimeout( size, 150 ); } );

			size();
			start();
		} );
	}

	loom.register( 'carousel', initCarousels );

	// --- Lightbox ------------------------------------------------------------

	var lightbox = null;

	function buildLightbox() {
		if ( lightbox ) { return lightbox; }
		var ov = document.createElement( 'div' );
		ov.className = 'loom-lightbox';
		ov.innerHTML =
			'<button class="loom-lb-close" aria-label="Close">&times;</button>' +
			'<button class="loom-lb-prev" aria-label="Previous">&#8249;</button>' +
			'<figure class="loom-lb-stage"><img alt=""><figcaption></figcaption></figure>' +
			'<button class="loom-lb-next" aria-label="Next">&#8250;</button>';
		document.body.appendChild( ov );

		var img = ov.querySelector( 'img' );
		var cap = ov.querySelector( 'figcaption' );
		var group = [];
		var pos = 0;

		function show() {
			var a = group[ pos ];
			img.src = a.getAttribute( 'href' );
			var c = a.getAttribute( 'data-caption' ) || '';
			cap.textContent = c;
			cap.style.display = c ? 'block' : 'none';
		}
		function open( list, i ) { group = list; pos = i; ov.classList.add( 'is-open' ); show(); }
		function close() { ov.classList.remove( 'is-open' ); img.src = ''; }
		function next() { pos = ( pos + 1 ) % group.length; show(); }
		function prev() { pos = ( pos - 1 + group.length ) % group.length; show(); }

		ov.querySelector( '.loom-lb-close' ).addEventListener( 'click', close );
		ov.querySelector( '.loom-lb-next' ).addEventListener( 'click', next );
		ov.querySelector( '.loom-lb-prev' ).addEventListener( 'click', prev );
		ov.addEventListener( 'click', function ( e ) { if ( e.target === ov ) { close(); } } );
		document.addEventListener( 'keydown', function ( e ) {
			if ( ! ov.classList.contains( 'is-open' ) ) { return; }
			if ( e.key === 'Escape' ) { close(); }
			else if ( e.key === 'ArrowRight' ) { next(); }
			else if ( e.key === 'ArrowLeft' ) { prev(); }
		} );

		lightbox = { open: open };
		return lightbox;
	}

	function initLightbox( root ) {
		var anchors = root.querySelectorAll( '[data-loom-lightbox]' );
		if ( ! anchors.length ) { return; }
		var lb = buildLightbox();

		anchors.forEach( function ( a ) {
			if ( a.dataset.loomReady ) { return; }
			a.dataset.loomReady = '1';
			a.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var grp = a.getAttribute( 'data-loom-lightbox' );
				var list = Array.prototype.slice.call( root.querySelectorAll( '[data-loom-lightbox="' + grp + '"]' ) );
				lb.open( list, list.indexOf( a ) );
			} );
		} );
	}

	loom.register( 'lightbox', initLightbox );

	// --- Accordion -----------------------------------------------------------

	function initAccordions( root ) {
		root.querySelectorAll( '[data-loom-accordion]' ).forEach( function ( box ) {
			if ( box.dataset.loomReady ) { return; }
			box.dataset.loomReady = '1';

			var single = box.getAttribute( 'data-single' ) === '1';
			var items = Array.prototype.slice.call( box.querySelectorAll( '.loom-acc-item' ) );

			items.forEach( function ( item ) {
				var head = item.querySelector( '.loom-acc-head' );
				var panel = item.querySelector( '.loom-acc-panel' );
				if ( ! head || ! panel ) { return; }

				head.addEventListener( 'click', function () {
					var willOpen = ! item.classList.contains( 'is-open' );

					if ( single && willOpen ) {
						items.forEach( function ( other ) {
							if ( other === item ) { return; }
							other.classList.remove( 'is-open' );
							var oh = other.querySelector( '.loom-acc-head' );
							var op = other.querySelector( '.loom-acc-panel' );
							if ( oh ) { oh.setAttribute( 'aria-expanded', 'false' ); }
							if ( op ) { op.hidden = true; }
						} );
					}

					item.classList.toggle( 'is-open', willOpen );
					head.setAttribute( 'aria-expanded', willOpen ? 'true' : 'false' );
					panel.hidden = ! willOpen;
				} );
			} );
		} );
	}

	loom.register( 'accordion', initAccordions );

	// --- Tabs ----------------------------------------------------------------

	function initTabs( root ) {
		root.querySelectorAll( '[data-loom-tabs]' ).forEach( function ( box ) {
			if ( box.dataset.loomReady ) { return; }
			box.dataset.loomReady = '1';

			var btns = Array.prototype.slice.call( box.querySelectorAll( '.loom-tab-btn' ) );
			var panels = Array.prototype.slice.call( box.querySelectorAll( '.loom-tab-panel' ) );

			btns.forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					var idx = btn.getAttribute( 'data-loom-tab' );
					btns.forEach( function ( b ) {
						var on = b === btn;
						b.classList.toggle( 'is-active', on );
						b.setAttribute( 'aria-selected', on ? 'true' : 'false' );
					} );
					panels.forEach( function ( p ) {
						var on = p.getAttribute( 'data-loom-tab' ) === idx;
						p.classList.toggle( 'is-active', on );
						p.hidden = ! on;
					} );
				} );
			} );
		} );
	}

	loom.register( 'tabs', initTabs );

	// --- Counter ---------------------------------------------------------------

	function initCounters( root ) {
		var boxes = root.querySelectorAll( '[data-loom-counter]' );
		if ( ! boxes.length ) { return; }

		function run( box ) {
			var target = parseFloat( box.getAttribute( 'data-value' ) ) || 0;
			var duration = parseInt( box.getAttribute( 'data-duration' ), 10 ) || 2000;
			var valueEl = box.querySelector( '.loom-counter-value' );
			if ( ! valueEl ) { return; }
			var isInt = target === Math.round( target );
			var start = null;

			function step( ts ) {
				if ( start === null ) { start = ts; }
				var progress = Math.min( 1, ( ts - start ) / duration );
				var current = target * progress;
				valueEl.textContent = isInt ? Math.round( current ) : current.toFixed( 1 );
				if ( progress < 1 ) { window.requestAnimationFrame( step ); }
			}
			window.requestAnimationFrame( step );
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			boxes.forEach( run );
			return;
		}

		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) { return; }
				run( entry.target );
				io.unobserve( entry.target );
			} );
		}, { threshold: 0.4 } );

		boxes.forEach( function ( box ) { io.observe( box ); } );
	}

	loom.register( 'counter', initCounters );

	// --- Progress bar ------------------------------------------------------------

	function initProgressBars( root ) {
		var boxes = root.querySelectorAll( '[data-loom-progress]' );
		if ( ! boxes.length ) { return; }

		function fill( box ) {
			var percent = parseInt( box.getAttribute( 'data-percent' ), 10 ) || 0;
			var track = box.querySelector( '.loom-progress-fill' );
			if ( track ) { track.style.width = percent + '%'; }
		}

		var animated = [];
		boxes.forEach( function ( box ) {
			if ( box.getAttribute( 'data-animate' ) === '1' ) { animated.push( box ); }
		} );
		if ( ! animated.length ) { return; }

		if ( ! ( 'IntersectionObserver' in window ) ) {
			animated.forEach( fill );
			return;
		}

		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) { return; }
				fill( entry.target );
				io.unobserve( entry.target );
			} );
		}, { threshold: 0.3 } );

		animated.forEach( function ( box ) { io.observe( box ); } );
	}

	loom.register( 'progress', initProgressBars );

	// --- Alert dismiss -------------------------------------------------------

	function initAlerts( root ) {
		root.querySelectorAll( '[data-loom-alert]' ).forEach( function ( box ) {
			if ( box.dataset.loomReady ) { return; }
			box.dataset.loomReady = '1';
			var btn = box.querySelector( '.loom-alert-dismiss' );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function () {
				box.style.display = 'none';
			} );
		} );
	}

	loom.register( 'alert', initAlerts );

	// --- Boot ----------------------------------------------------------------

	function boot() {
		// Scan the whole document so header/footer templates (outside .loom-doc)
		// are initialized too.
		var root = document;
		Object.keys( loom.modules ).forEach( function ( name ) {
			try {
				loom.modules[ name ]( root );
			} catch ( e ) {
				// A single broken module must not take down the page.
				if ( window.console ) {
					window.console.warn( 'Loom module failed:', name, e );
				}
			}
		} );
	}

	if ( document.readyState !== 'loading' ) {
		boot();
	} else {
		document.addEventListener( 'DOMContentLoaded', boot );
	}
} )();
