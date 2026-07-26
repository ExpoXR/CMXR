/* CMXR — Detect loader (~700 bytes min). Scans DOM for animation IDs, loads engine only if found. */
(function () {
	'use strict';

	// Guard against accidental double-include
	if (window.__cmxrDetectRan) return;
	window.__cmxrDetectRan = true;

	var cfgEl = document.getElementById('cmxr-config');
	if (!cfgEl) return;

	var cfg;
	try { cfg = JSON.parse(cfgEl.textContent || cfgEl.innerHTML); } catch (e) { return; }
	if (!cfg || !cfg.animations || !cfg.animations.length) return;

	function targetFor(a) {
		return Number(a.config_version || 1) === 2
			? (a.target && a.target.mode === 'id' ? a.target.selector : '')
			: a.animation_id;
	}

	var found = cfg.animations.filter(function (a) {
		var target = targetFor(a);
		return target && !!document.getElementById(target);
	});

	var debug = !!(cfg.settings && (cfg.settings.debugMode || cfg.settings.wpDebug || cfg.settings.scriptDebug));
	window.CMXRDebug = window.CMXRDebug || {
		enabled: debug,
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

	// Hand the engine the full set so its DOM re-scan can pick up containers
	// added after initial load (not just the ones present right now).
	window.CMXRAnimations = cfg.animations;
	window.CMXRSettings   = cfg.settings || {};

	// Inject CSS (kept eager — tiny, and lets layout settle before the engine runs)
	if (cfg.cssUrl) {
		var link = document.createElement('link');
		link.rel  = 'stylesheet';
		link.href = cfg.cssUrl;
		link.onerror = function () { window.CMXRDebug.error('[CMXR] Failed to load CSS:', cfg.cssUrl); };
		document.head.appendChild(link);
	}

	// Inject core + engine in order. async=false forces ordered execution of
	// dynamically-inserted scripts (core must run before engine).
	function injectScript(src, label) {
		var script = document.createElement('script');
		script.src = src;
		script.async = false;
		script.onerror = function () { window.CMXRDebug.error('[CMXR] Failed to load ' + label + ':', src); };
		document.head.appendChild(script);
	}

	var injected = false;
	function injectEngine() {
		if (injected) return;
		injected = true;
		if (cfg.coreUrl)   injectScript(cfg.coreUrl, 'core');
		if (cfg.renderersUrl) injectScript(cfg.renderersUrl, 'renderers');
		if (cfg.engineUrl) injectScript(cfg.engineUrl, 'engine');
		window.CMXRDebug.log('[CMXR] Loaded engine for ' + found.length + ' animation(s):', found.map(function (a) { return '#' + targetFor(a); }));
	}

	// Lazy-load: defer the ~27KB core+engine until a matched element nears the
	// viewport, so pages with below-the-fold animations skip it on initial load.
	// Falls back to immediate injection when IntersectionObserver is unavailable.
	if (!found.length && 'MutationObserver' in window) {
		var waiting = new MutationObserver(function () {
			for (var i = 0; i < cfg.animations.length; i++) {
				var target = targetFor(cfg.animations[i]);
				if (target && document.getElementById(target)) {
					waiting.disconnect();
					injectEngine();
					return;
				}
			}
		});
		if (document.body) waiting.observe(document.body, { childList: true, subtree: true });
		else document.addEventListener('DOMContentLoaded', function () {
			waiting.observe(document.body, { childList: true, subtree: true });
		});
	} else if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			for (var i = 0; i < entries.length; i++) {
				if (entries[i].isIntersecting) {
					injectEngine();
					io.disconnect();
					return;
				}
			}
		}, { rootMargin: '200px' });
		found.forEach(function (a) {
			var el = document.getElementById(targetFor(a));
			if (el) io.observe(el);
		});
	} else {
		injectEngine();
	}
})();
