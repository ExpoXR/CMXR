/* CMXR — Admin Dashboard JS */
(function () {
	'use strict';

	var api = window.CMXRAdmin || {};
	var restUrl = api.restUrl || '';
	var nonce = api.nonce || '';
	var strings = api.strings || {};
	var DEBUG = !!(api.debugMode || api.wpDebug || api.scriptDebug);
	var Core = window.CMXRCore;

	window.CMXRDebug = window.CMXRDebug || {
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

	function cmxrText(key, fallback) {
		return strings[key] || fallback;
	}

	function apiFetch(path, method, body) {
		return fetch(restUrl + path, {
			method: method || 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': nonce,
			},
			body: body ? JSON.stringify(body) : undefined,
		}).then(function (r) { return r.json(); });
	}

	function showNotice(msg, isError) {
		var el = document.createElement('div');
		el.className = 'cmxr-notice' + (isError ? ' error' : '');
		el.textContent = msg;
		document.body.appendChild(el);
		requestAnimationFrame(function () { el.classList.add('show'); });
		setTimeout(function () {
			el.classList.remove('show');
			setTimeout(function () { el.remove(); }, 300);
		}, 2600);
	}

	function init() {
		// Copy ID buttons
		document.querySelectorAll('.cmxr-copy-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var text = btn.getAttribute('data-copy');
				if (navigator.clipboard) {
					navigator.clipboard.writeText(text).then(function () {
						showNotice(cmxrText('copied', 'Copied:') + ' ' + text);
					});
				} else {
					var ta = document.createElement('textarea');
					ta.value = text;
					document.body.appendChild(ta);
					ta.select();
					document.execCommand('copy');
					ta.remove();
					showNotice(cmxrText('copied', 'Copied:') + ' ' + text);
				}
			});
		});

		// Toggle active
		document.querySelectorAll('.cmxr-toggle-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var postId = btn.getAttribute('data-post-id');
				apiFetch('/animations/' + postId + '/toggle', 'POST').then(function (data) {
					if (data.id) {
						btn.classList.toggle('is-active', data.active);
						btn.textContent = data.active ? cmxrText('active', 'Active') : cmxrText('inactive', 'Inactive');
						showNotice(data.active ? cmxrText('animationActivated', 'Animation activated.') : cmxrText('animationDeactivated', 'Animation deactivated.'));
					}
				});
			});
		});

		// Duplicate
		document.querySelectorAll('.cmxr-duplicate-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var postId = btn.getAttribute('data-post-id');
				apiFetch('/animations/' + postId + '/duplicate', 'POST').then(function (data) {
					if (data.id) {
						showNotice(cmxrText('duplicatedReloading', 'Duplicated. Reloading...'));
						setTimeout(function () { location.reload(); }, 800);
					}
				});
			});
		});

		// Delete
		document.querySelectorAll('.cmxr-delete-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var msg = btn.getAttribute('data-confirm') || 'Delete this animation?';
				if (!confirm(msg)) return;
				var postId = btn.getAttribute('data-post-id');
				apiFetch('/animations/' + postId, 'DELETE').then(function (data) {
					if (data.deleted) {
						var row = btn.closest('tr');
						if (row) row.remove();
						showNotice(cmxrText('animationDeleted', 'Animation deleted.'));
					}
				});
			});
		});

		// Debug page — toggle JSON blocks
		document.querySelectorAll('.cmxr-debug-toggle-json').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var targetId = btn.getAttribute('data-target');
				var target = document.getElementById(targetId);
				if (!target) return;
				var isHidden = target.classList.contains('is-hidden');
				target.classList.toggle('is-hidden', !isHidden);
				btn.textContent = isHidden ? cmxrText('hideConfig', 'Hide Config') : cmxrText('showConfig', 'Show Config');
			});
		});

		// Preview buttons
		document.querySelectorAll('.cmxr-preview-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var postId = btn.getAttribute('data-post-id');
				var title  = btn.getAttribute('data-title') || 'Preview';
				openPreviewModal(postId, title);
			});
		});
	}

	/* ------------------------------------------------------------------ */
	/* Preview Modal                                                        */
	/* ------------------------------------------------------------------ */

	var _modalRaf = 0;
	var _modalPointer = null;
	var _modalResizeObserver = null;

	function injectPreviewModal() {
		if (document.getElementById('cmxr-dash-modal')) return;
		var el = document.createElement('div');
		el.id = 'cmxr-dash-modal';
		el.innerHTML =
			'<div id="cmxr-dash-modal-backdrop"></div>' +
			'<div id="cmxr-dash-modal-inner" role="dialog" aria-modal="true" aria-labelledby="cmxr-dash-modal-title">' +
				'<div id="cmxr-dash-modal-header">' +
					'<span id="cmxr-dash-modal-title"></span>' +
					'<div id="cmxr-dash-modal-bg-row">' +
						'<input type="color" id="cmxr-dash-modal-bg-hex" value="#ffffff" title="Background color">' +
						'<input type="text"  id="cmxr-dash-modal-bg-text" value="transparent" placeholder="rgba()">' +
						'<button id="cmxr-dash-modal-bg-transparent" class="button button-small cmxr-preview-btn" title="Transparent">Reset</button>' +
					'</div>' +
					'<button id="cmxr-dash-modal-close" title="Close">&times;</button>' +
				'</div>' +
				'<div id="cmxr-dash-modal-canvas-wrap">' +
					'<canvas id="cmxr-dash-modal-canvas" aria-hidden="true"></canvas>' +
					'<div id="cmxr-dash-modal-loading">Loading&hellip;</div>' +
				'</div>' +
			'</div>';
		document.body.appendChild(el);

		document.getElementById('cmxr-dash-modal-close').addEventListener('click', closePreviewModal);
		document.getElementById('cmxr-dash-modal-backdrop').addEventListener('click', closePreviewModal);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') closePreviewModal();
		});

		var bgHex   = document.getElementById('cmxr-dash-modal-bg-hex');
		var bgText  = document.getElementById('cmxr-dash-modal-bg-text');
		var bgTransp = document.getElementById('cmxr-dash-modal-bg-transparent');
		bgHex.addEventListener('input', function () {
			bgText.value = bgHex.value;
			applyModalBg(bgHex.value);
		});
		bgText.addEventListener('input', function () {
			var v = bgText.value.trim() || 'transparent';
			applyModalBg(v);
			if (/^#[0-9a-f]{6}$/i.test(v)) bgHex.value = v;
		});
		if (bgTransp) {
			bgTransp.addEventListener('click', function () {
				bgText.value = 'transparent';
				applyModalBg('transparent');
			});
		}
	}

	function applyModalBg(val) {
		var wrap = document.getElementById('cmxr-dash-modal-canvas-wrap');
		if (wrap) wrap.style.background = val;
	}

	function closePreviewModal() {
		var modal = document.getElementById('cmxr-dash-modal');
		if (modal) modal.style.display = 'none';
		if (_modalRaf) { cancelAnimationFrame(_modalRaf); _modalRaf = 0; }
		if (_modalPointer) { _modalPointer.dispose(); _modalPointer = null; }
		if (_modalResizeObserver) { _modalResizeObserver.disconnect(); _modalResizeObserver = null; }
	}

	function openPreviewModal(postId, title) {
		injectPreviewModal();
		var modal   = document.getElementById('cmxr-dash-modal');
		var titleEl = document.getElementById('cmxr-dash-modal-title');
		var loading = document.getElementById('cmxr-dash-modal-loading');
		if (titleEl) titleEl.textContent = title;
		if (loading) loading.style.display = 'flex';
		modal.style.display = 'flex';

		if (_modalRaf) { cancelAnimationFrame(_modalRaf); _modalRaf = 0; }

		apiFetch('/animations/' + postId).then(function (data) {
			if (!data || !data.config) return;
			var cfg = data.config;
			var bg  = (cfg.global && cfg.global.preview_bg) || 'transparent';
			applyModalBg(bg);
			var bgHex  = document.getElementById('cmxr-dash-modal-bg-hex');
			var bgText = document.getElementById('cmxr-dash-modal-bg-text');
			if (bgText) bgText.value = bg;
			if (bgHex && /^#[0-9a-f]{6}$/i.test(bg)) bgHex.value = bg;
			if (loading) loading.style.display = 'none';
			startModalPreview(cfg);
		});
	}

	/* ---- Mini preview (shared CMXRCore rendering, with interactivity) ---- */

	function startModalPreview(config) {
		if (_modalRaf) { cancelAnimationFrame(_modalRaf); _modalRaf = 0; }
		if (_modalPointer) { _modalPointer.dispose(); _modalPointer = null; }
		if (_modalResizeObserver) { _modalResizeObserver.disconnect(); _modalResizeObserver = null; }
		if (!Core) return;

		var canvas = document.getElementById('cmxr-dash-modal-canvas');
		var wrap = document.getElementById('cmxr-dash-modal-canvas-wrap');
		if (!canvas || !wrap) return;
		var ctx  = canvas.getContext('2d', { alpha: true });

		var ms = { w: 0, h: 0, dpr: 1, time: 0, lastTime: 0 };
		var ptr = Core.createPointerTracker(wrap, null, {
			debug: DEBUG,
			scope: 'dashboard-modal',
			label: config.animation_id || 'preview',
			getState: function () {
				var inter = (config.global && config.global.interactivity) || {};
				return {
					animationId: config.animation_id || '',
					orbs: (config.orbs || []).length,
					canvas: { width: ms.w, height: ms.h, dpr: ms.dpr },
					interactivity: {
						enabled: inter.enabled !== false,
						mode: inter.mode || 'parallax',
						strength: inter.strength || 0.5,
						radius: inter.radius || 30,
					},
				};
			},
		});
		_modalPointer = ptr;

		function resize() {
			ms.w   = wrap.clientWidth  || 800;
			ms.h   = wrap.clientHeight || 500;
			ms.dpr = Math.min(window.devicePixelRatio || 1, 1.75);
			canvas.width  = Math.round(ms.w * ms.dpr);
			canvas.height = Math.round(ms.h * ms.dpr);
			window.CMXRDebug.log('[CMXR dashboard-modal] resize', { width: ms.w, height: ms.h, dpr: ms.dpr });
		}

		function tick(now) {
			var dt = Math.min(40, Math.max(0, now - (ms.lastTime || now)));
			ms.lastTime = now;

			ptr.update();

			var speed = (config.global && config.global.speed) || 1.0;
			ms.time += dt * 0.001 * speed * (1 + ptr.hover * 0.35);

			var w = ms.w, h = ms.h, t = ms.time;
			ctx.setTransform(ms.dpr, 0, 0, ms.dpr, 0, 0);
			ctx.clearRect(0, 0, w, h);

			var blendMode  = (config.global && config.global.blend_mode)  || 'screen';
			var safeMargin = (config.global && config.global.safe_margin) || 0;

			var inter    = (config.global && config.global.interactivity) || {};
			var iEnabled = (inter.enabled !== false) && inter.mode !== 'none';
			var iMode    = iEnabled ? inter.mode : 'none';
			var iStr     = inter.strength || 0.5;
			var iRad     = inter.radius || 30;
			var mx       = ptr.mx;
			var my       = ptr.my;
			var hover    = ptr.hover;

			ctx.globalCompositeOperation = blendMode;

			var orbs = config.orbs || [];
			for (var i = orbs.length - 1; i >= 0; i--) {
				var orb   = orbs[i];
				var seed  = Core.hashSeed(orb.id);
				var scale = Core.computeOrbScale(orb, t);
				var pos   = Core.computeOrbPos(orb, seed, t, w, h, safeMargin, mx, my, hover, iMode, iStr, iRad);
				Core.drawOrb(ctx, orb, pos, scale, t, seed);
			}

			ctx.globalCompositeOperation = 'source-over';
			_modalRaf = requestAnimationFrame(tick);
		}

		resize();
		if (typeof ResizeObserver !== 'undefined') {
			_modalResizeObserver = new ResizeObserver(function () { resize(); });
			_modalResizeObserver.observe(wrap);
		}

		_modalRaf = requestAnimationFrame(tick);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
