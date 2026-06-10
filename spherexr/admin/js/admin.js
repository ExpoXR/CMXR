/* SphereXR — Admin Dashboard JS */
(function () {
	'use strict';

	var api = window.SphereXRAdmin || {};
	var restUrl = api.restUrl || '';
	var nonce = api.nonce || '';
	var Core = window.SphereXRCore;

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
		el.className = 'spherexr-notice' + (isError ? ' error' : '');
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
		document.querySelectorAll('.spherexr-copy-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var text = btn.getAttribute('data-copy');
				if (navigator.clipboard) {
					navigator.clipboard.writeText(text).then(function () {
						showNotice('Copied: ' + text);
					});
				} else {
					var ta = document.createElement('textarea');
					ta.value = text;
					document.body.appendChild(ta);
					ta.select();
					document.execCommand('copy');
					ta.remove();
					showNotice('Copied: ' + text);
				}
			});
		});

		// Toggle active
		document.querySelectorAll('.spherexr-toggle-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var postId = btn.getAttribute('data-post-id');
				apiFetch('/animations/' + postId + '/toggle', 'POST').then(function (data) {
					if (data.id) {
						btn.classList.toggle('is-active', data.active);
						btn.textContent = data.active ? 'Active' : 'Inactive';
						showNotice(data.active ? 'Animation activated.' : 'Animation deactivated.');
					}
				});
			});
		});

		// Duplicate
		document.querySelectorAll('.spherexr-duplicate-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var postId = btn.getAttribute('data-post-id');
				apiFetch('/animations/' + postId + '/duplicate', 'POST').then(function (data) {
					if (data.id) {
						showNotice('Duplicated. Reloading…');
						setTimeout(function () { location.reload(); }, 800);
					}
				});
			});
		});

		// Delete
		document.querySelectorAll('.spherexr-delete-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var msg = btn.getAttribute('data-confirm') || 'Delete this animation?';
				if (!confirm(msg)) return;
				var postId = btn.getAttribute('data-post-id');
				apiFetch('/animations/' + postId, 'DELETE').then(function (data) {
					if (data.deleted) {
						var row = btn.closest('tr');
						if (row) row.remove();
						showNotice('Animation deleted.');
					}
				});
			});
		});

		// Debug page — toggle JSON blocks
		document.querySelectorAll('.spherexr-debug-toggle-json').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var targetId = btn.getAttribute('data-target');
				var target = document.getElementById(targetId);
				if (!target) return;
				var isHidden = target.classList.contains('is-hidden');
				target.classList.toggle('is-hidden', !isHidden);
				btn.textContent = isHidden ? 'Hide Config' : 'Show Config';
			});
		});

		// Preview buttons
		document.querySelectorAll('.spherexr-preview-btn').forEach(function (btn) {
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

	function injectPreviewModal() {
		if (document.getElementById('sxr-dash-modal')) return;
		var el = document.createElement('div');
		el.id = 'sxr-dash-modal';
		el.innerHTML =
			'<div id="sxr-dash-modal-backdrop"></div>' +
			'<div id="sxr-dash-modal-inner" role="dialog" aria-modal="true" aria-labelledby="sxr-dash-modal-title">' +
				'<div id="sxr-dash-modal-header">' +
					'<span id="sxr-dash-modal-title"></span>' +
					'<div id="sxr-dash-modal-bg-row">' +
						'<input type="color" id="sxr-dash-modal-bg-hex" value="#0f0c1a" title="Background color">' +
						'<input type="text"  id="sxr-dash-modal-bg-text" value="transparent" placeholder="rgba()">' +
						'<button id="sxr-dash-modal-bg-transparent" title="Transparent">&#9633;</button>' +
					'</div>' +
					'<button id="sxr-dash-modal-close" title="Close">&times;</button>' +
				'</div>' +
				'<div id="sxr-dash-modal-canvas-wrap">' +
					'<canvas id="sxr-dash-modal-canvas" aria-hidden="true"></canvas>' +
					'<div id="sxr-dash-modal-loading">Loading&hellip;</div>' +
				'</div>' +
			'</div>';
		document.body.appendChild(el);

		document.getElementById('sxr-dash-modal-close').addEventListener('click', closePreviewModal);
		document.getElementById('sxr-dash-modal-backdrop').addEventListener('click', closePreviewModal);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') closePreviewModal();
		});

		var bgHex   = document.getElementById('sxr-dash-modal-bg-hex');
		var bgText  = document.getElementById('sxr-dash-modal-bg-text');
		var bgTransp = document.getElementById('sxr-dash-modal-bg-transparent');
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
		var wrap = document.getElementById('sxr-dash-modal-canvas-wrap');
		if (wrap) wrap.style.background = val;
	}

	function closePreviewModal() {
		var modal = document.getElementById('sxr-dash-modal');
		if (modal) modal.style.display = 'none';
		if (_modalRaf) { cancelAnimationFrame(_modalRaf); _modalRaf = 0; }
	}

	function openPreviewModal(postId, title) {
		injectPreviewModal();
		var modal   = document.getElementById('sxr-dash-modal');
		var titleEl = document.getElementById('sxr-dash-modal-title');
		var loading = document.getElementById('sxr-dash-modal-loading');
		if (titleEl) titleEl.textContent = title;
		if (loading) loading.style.display = 'flex';
		modal.style.display = 'flex';

		if (_modalRaf) { cancelAnimationFrame(_modalRaf); _modalRaf = 0; }

		apiFetch('/animations/' + postId).then(function (data) {
			if (!data || !data.config) return;
			var cfg = data.config;
			var bg  = (cfg.global && cfg.global.preview_bg) || 'transparent';
			applyModalBg(bg);
			var bgHex  = document.getElementById('sxr-dash-modal-bg-hex');
			var bgText = document.getElementById('sxr-dash-modal-bg-text');
			if (bgText) bgText.value = bg;
			if (bgHex && /^#[0-9a-f]{6}$/i.test(bg)) bgHex.value = bg;
			if (loading) loading.style.display = 'none';
			startModalPreview(cfg);
		});
	}

	/* ---- Mini preview (shared SphereXRCore rendering, no interactivity) ---- */

	function startModalPreview(config) {
		if (_modalRaf) { cancelAnimationFrame(_modalRaf); _modalRaf = 0; }
		if (!Core) return;

		var canvas = document.getElementById('sxr-dash-modal-canvas');
		if (!canvas) return;
		var ctx  = canvas.getContext('2d', { alpha: true });
		var wrap = document.getElementById('sxr-dash-modal-canvas-wrap');

		var ms = { w: 0, h: 0, dpr: 1, time: 0, lastTime: 0 };

		function resize() {
			ms.w   = wrap.clientWidth  || 800;
			ms.h   = wrap.clientHeight || 500;
			ms.dpr = Math.min(window.devicePixelRatio || 1, 1.75);
			canvas.width  = Math.round(ms.w * ms.dpr);
			canvas.height = Math.round(ms.h * ms.dpr);
		}

		function tick(now) {
			var dt = Math.min(40, Math.max(0, now - (ms.lastTime || now)));
			ms.lastTime = now;
			ms.time += dt * 0.001 * ((config.global && config.global.speed) || 1.0);

			var w = ms.w, h = ms.h, t = ms.time;
			ctx.setTransform(ms.dpr, 0, 0, ms.dpr, 0, 0);
			ctx.clearRect(0, 0, w, h);

			var blendMode  = (config.global && config.global.blend_mode)  || 'screen';
			var safeMargin = (config.global && config.global.safe_margin) || 0;

			ctx.globalCompositeOperation = blendMode;

			var orbs = config.orbs || [];
			for (var i = orbs.length - 1; i >= 0; i--) {
				var orb   = orbs[i];
				var seed  = Core.hashSeed(orb.id);
				var scale = Core.computeOrbScale(orb, t);
				var pos   = Core.computeOrbPos(orb, seed, t, w, h, safeMargin, 0, 0, 0, 'none', 0, 0);
				Core.drawOrb(ctx, orb, pos, scale, t, seed);
			}

			ctx.globalCompositeOperation = 'source-over';
			_modalRaf = requestAnimationFrame(tick);
		}

		resize();
		if (typeof ResizeObserver !== 'undefined') {
			new ResizeObserver(function () { resize(); }).observe(wrap);
		}

		_modalRaf = requestAnimationFrame(tick);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
