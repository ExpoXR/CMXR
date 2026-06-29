/* CMXR Engine v1.0.0 — Ayal Othman / ExpoXR (expoxr.com) */
(function () {
	'use strict';

	/* ------------------------------------------------------------------ */
	/* Constants from detect script                                         */
	/* ------------------------------------------------------------------ */

	var animations = window.CMXRAnimations || [];
	var settings   = window.CMXRSettings || {};
	var DPR_CAP    = settings.dprCap || 1.75;
	var IO_THRESH  = settings.ioThresh || 0.01;
	var DEBUG      = !!(settings.debugMode || settings.wpDebug || settings.scriptDebug);
	var Debug      = window.CMXRDebug || {
		enabled: DEBUG,
		log: function () {
			if (!this.enabled || !window.console) return;
			console.log.apply(console, arguments);
		},
		warn: function () {
			if (!this.enabled || !window.console) return;
			console.warn.apply(console, arguments);
		},
		error: function () {
			if (!this.enabled || !window.console) return;
			console.error.apply(console, arguments);
		},
	};
	var Core = window.CMXRCore;
	if (!Core) {
		Debug.error('[CMXR] Core not loaded - engine aborted.');
		return;
	}

	var reducedMotion = window.matchMedia
		? window.matchMedia('(prefers-reduced-motion: reduce)').matches
		: false;

	// Registry of elements with a live animation, so we can dispose them when
	// they are removed from the DOM (Elementor/AJAX re-renders) instead of
	// leaking their canvas, observers, listeners and rAF loop.
	var liveEls = [];

	/* ------------------------------------------------------------------ */
	/* Per-animation init                                                   */
	/* ------------------------------------------------------------------ */

	function initAnimation(el, cfg) {
		if (el.__cmxrReady) return;
		el.__cmxrReady = true;
		if (liveEls.indexOf(el) === -1) liveEls.push(el);

		el.classList.add('cmxr-ready');

		// Create canvas
		var canvas = document.createElement('canvas');
		canvas.className = 'cmxr-canvas';
		canvas.setAttribute('aria-hidden', 'true');
		el.insertBefore(canvas, el.firstChild);

		var ctx = canvas.getContext('2d', { alpha: true });

		// Per-animation state
		var state = {
			w: 0, h: 0, dpr: 1,
			time: 0, lastTime: 0,
			running: false, visible: true,
		};

		// Pre-compute orb seeds for drift
		var orbSeeds = (cfg.orbs || []).map(function (orb) { return Core.hashSeed(orb.id || orb.color); });

		var rafId = 0, io = null, ro = null;
		var pointer = Core.createPointerTracker(el, start, {
			debug: DEBUG,
			scope: 'frontend',
			label: '#' + cfg.animation_id,
			getState: getInteractionDebugState,
		});

		function getInteractionDebugState() {
			var g = cfg.global || {};
			var interact = g.interactivity || {};
			var computed = window.getComputedStyle ? window.getComputedStyle(el) : null;
			return {
				animationId: cfg.animation_id,
				orbs: (cfg.orbs || []).length,
				canvas: { width: state.w, height: state.h, dpr: state.dpr },
				visible: state.visible,
				running: state.running,
				interactivity: {
					enabled: interact.enabled !== false,
					mode: interact.mode || 'parallax',
					strength: interact.strength || 0.5,
					radius: interact.radius || 30,
				},
				element: {
					pointerEvents: computed ? computed.pointerEvents : '',
					position: computed ? computed.position : '',
					minHeight: computed ? computed.minHeight : '',
				},
			};
		}

		/* ---- Resize ---- */
		function resize() {
			var rect = el.getBoundingClientRect();
			var rawW = rect.width  || el.offsetWidth  || 0;
			var rawH = rect.height || el.offsetHeight || 0;
			// Use computed min-width/height as floor so dynamically-injected CSS is respected
			var computed = window.getComputedStyle(el);
			var minH = parseInt(computed.minHeight, 10) || 0;
			var minW = parseInt(computed.minWidth,  10) || 0;
			state.w   = Math.max(rawW, minW) || 300;
			state.h   = Math.max(rawH, minH) || 200;
			state.dpr = Math.min(window.devicePixelRatio || 1, DPR_CAP);
			canvas.width  = Math.round(state.w * state.dpr);
			canvas.height = Math.round(state.h * state.dpr);
			logResizeDebug();
			// Do NOT set canvas.style.width/height — CSS inset:0 controls display size.

			if (DEBUG && state.h <= 1) {
				Debug.warn('[CMXR] #' + cfg.animation_id + ' has height ' + state.h + 'px. Set a min-height on the container (Elementor -> Advanced -> Min Height, or CSS --cmxr-min-height).');
			}
		}

		function logResizeDebug() {
			if (DEBUG) {
				Debug.log('[CMXR frontend] #' + cfg.animation_id + ' resize', getInteractionDebugState());
			}
		}

		/* ---- Animation loop ---- */
		function tick(now) {
			if (!state.running) return;

			var dt = Math.min(40, Math.max(0, now - (state.lastTime || now)));
			state.lastTime = now;

			var speed = (cfg.global && cfg.global.speed) || 1.0;
			state.time += dt * 0.001 * speed * (1 + pointer.hover * 0.35);

			pointer.update();

			// Pause if reduced motion and no hover activity
			if (reducedMotion && pointer.hover < 0.01 && pointer.targetHover < 0.01) {
				state.running = false;
				return;
			}

			draw();
			rafId = requestAnimationFrame(tick);
		}

		function start() {
			if (state.running || !state.visible) return;
			state.running  = true;
			state.lastTime = 0;
			rafId = requestAnimationFrame(tick);
		}

		function stop() {
			state.running = false;
			if (rafId) { cancelAnimationFrame(rafId); rafId = 0; }
		}

		/* ---- Draw scene ---- */
		function draw() {
			var w = state.w, h = state.h, t = state.time;
			ctx.setTransform(state.dpr, 0, 0, state.dpr, 0, 0);
			ctx.clearRect(0, 0, w, h);

			var g          = cfg.global || {};
			var blendMode  = Core.blendOp(g.blend_mode || 'normal');
			var safeMargin = g.safe_margin || 0;
			var interact   = g.interactivity || {};
			var iEnabled   = (interact.enabled !== false) && interact.mode !== 'none';
			var iMode      = iEnabled ? interact.mode : 'none';
			var iStrength  = interact.strength || 0.5;
			var iRadius    = interact.radius   || 30;

			var mx = pointer.mx, my = pointer.my, hover = pointer.hover;

			ctx.globalCompositeOperation = blendMode;

			var orbs = cfg.orbs || [];
			for (var i = orbs.length - 1; i >= 0; i--) {
				var orb   = orbs[i];
				var seed  = orbSeeds[i] || 0;
				var scale = Core.computeOrbScale(orb, t);
				var pos   = Core.computeOrbPos(orb, seed, t, w, h, safeMargin, mx, my, hover, iMode, iStrength, iRadius);
				Core.drawOrb(ctx, orb, pos, scale, t, seed);
			}

			ctx.globalCompositeOperation = 'source-over';
		}

		/* ---- IntersectionObserver (pause when off-screen) ---- */
		if ('IntersectionObserver' in window) {
			io = new IntersectionObserver(function (entries) {
				state.visible = entries[0].isIntersecting;
				if (state.visible) {
					state.lastTime = 0;
					start();
				} else {
					stop();
				}
			}, { threshold: IO_THRESH });
			io.observe(el);
		}

		/* ---- ResizeObserver ---- */
		if ('ResizeObserver' in window) {
			ro = new ResizeObserver(function () {
				resize();
				if (!state.running) start();
			});
			ro.observe(el);
		} else {
			window.addEventListener('resize', resize, { passive: true });
		}

		/* ---- Teardown (for AJAX / Elementor re-renders) ---- */
		el.__cmxrDispose = function () {
			stop();
			pointer.dispose();
			if (io) io.disconnect();
			if (ro) ro.disconnect();
			window.removeEventListener('resize', resize);
			if (canvas.parentNode) canvas.parentNode.removeChild(canvas);
			el.__cmxrReady = false;
			el.classList.remove('cmxr-ready');
		};

		/* ---- Boot ---- */
		// Defer one rAF so dynamically-injected CSS (from detect script) is applied
		// before we measure the container height for the first time.
		requestAnimationFrame(function () {
			resize();
			start();

			Debug.log('[CMXR] Initialized #' + cfg.animation_id, {
				orbs: (cfg.orbs || []).length,
				speed: (cfg.global || {}).speed,
				blend: (cfg.global || {}).blend_mode,
				w: state.w,
				h: state.h,
				interaction: getInteractionDebugState().interactivity,
			});
		});
	}

	/* ------------------------------------------------------------------ */
	/* Boot all found animations                                            */
	/* ------------------------------------------------------------------ */

	// Init any matching elements not yet running, and dispose ones whose element
	// has been detached from the document.
	function scan() {
		animations.forEach(function (cfg) {
			var el = document.getElementById(cfg.animation_id);
			if (el) initAnimation(el, cfg);
		});

		for (var i = liveEls.length - 1; i >= 0; i--) {
			var el = liveEls[i];
			if (!document.contains(el)) {
				if (typeof el.__cmxrDispose === 'function') el.__cmxrDispose();
				liveEls.splice(i, 1);
			}
		}
	}

	// Debounced re-scan so bursts of DOM mutations collapse into one pass.
	var scanScheduled = false;
	function scheduleScan() {
		if (scanScheduled) return;
		scanScheduled = true;
		setTimeout(function () {
			scanScheduled = false;
			scan();
		}, 150);
	}

	function init() {
		scan();

		// Watch for elements added/removed after load (page builders, AJAX, SPA
		// navigation) so animations init on new containers and tear down on
		// removed ones.
		if ('MutationObserver' in window && document.body) {
			var mo = new MutationObserver(function (mutations) {
				for (var i = 0; i < mutations.length; i++) {
					if (mutations[i].addedNodes.length || mutations[i].removedNodes.length) {
						scheduleScan();
						return;
					}
				}
			});
			mo.observe(document.body, { childList: true, subtree: true });
		}
	}

	// Public hook for themes/builders to force a re-scan after custom DOM swaps.
	window.CMXR = window.CMXR || {};
	window.CMXR.refresh = scheduleScan;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
