/* CMXR Engine — renderer host bootstrap and dynamic DOM lifecycle. */
(function () {
	'use strict';

	var animations = window.CMXRAnimations || [];
	var settings = window.CMXRSettings || {};
	var Renderers = window.CMXRRenderers;
	var Debug = window.CMXRDebug || { log: function () {}, error: function () {} };
	if (!Renderers) {
		Debug.error('[CMXR] Renderer registry not loaded.');
		return;
	}

	var live = [];

	function targetFor(config) {
		if (Number(config.config_version || 1) === 2) {
			if (!config.target || config.target.mode !== 'id') return '';
			return config.target.selector || '';
		}
		return config.animation_id || '';
	}

	function effectFor(config) {
		return Number(config.config_version || 1) === 2
			? config.effect_type
			: 'layered-shapes';
	}

	function initAnimation(element, config) {
		if (element.__cmxrReady) return;
		var effectType = effectFor(config);
		if (!Renderers.has(effectType)) return;

		try {
			var instance = Renderers.create(effectType, element, config, {
				environment: 'frontend',
				dprCap: settings.dprCap || 1.75,
				ioThreshold: settings.ioThresh || 0.01,
				debug: !!(settings.debugMode || settings.wpDebug || settings.scriptDebug),
			});
			element.__cmxrReady = true;
			element.__cmxrDispose = function () {
				if (instance) instance.destroy();
				instance = null;
				element.__cmxrReady = false;
				delete element.__cmxrDispose;
			};
			live.push(element);
			Debug.log('[CMXR] Initialized #' + targetFor(config), { effect: effectType });
		} catch (error) {
			element.__cmxrReady = false;
			Debug.error('[CMXR] Renderer failed:', effectType, error);
		}
	}

	function scan() {
		animations.forEach(function (config) {
			var target = targetFor(config);
			if (!target) return;
			var element = document.getElementById(target);
			if (element) initAnimation(element, config);
		});

		for (var i = live.length - 1; i >= 0; i--) {
			if (!document.contains(live[i])) {
				if (typeof live[i].__cmxrDispose === 'function') live[i].__cmxrDispose();
				live.splice(i, 1);
			}
		}
	}

	var scheduled = false;
	function scheduleScan() {
		if (scheduled) return;
		scheduled = true;
		setTimeout(function () {
			scheduled = false;
			scan();
		}, 150);
	}

	function init() {
		scan();
		if ('MutationObserver' in window && document.body) {
			var observer = new MutationObserver(function (mutations) {
				for (var i = 0; i < mutations.length; i++) {
					if (mutations[i].addedNodes.length || mutations[i].removedNodes.length) {
						scheduleScan();
						return;
					}
				}
			});
			observer.observe(document.body, { childList: true, subtree: true });
		}
	}

	window.CMXR = window.CMXR || {};
	window.CMXR.refresh = scheduleScan;

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();
