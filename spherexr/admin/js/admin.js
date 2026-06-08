/* SphereXR — Admin Dashboard JS */
(function () {
	'use strict';

	var api = window.SphereXRAdmin || {};
	var restUrl = api.restUrl || '';
	var nonce = api.nonce || '';

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
			'<div id="sxr-dash-modal-inner">' +
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

	/* ---- Mini rendering engine (mirrors configurator preview) ---- */

	var _PHI = 1.61803, _E = 2.71828;

	function _hexToRgba(hex, alpha) {
		var r = parseInt(hex.slice(1, 3), 16) || 0;
		var g = parseInt(hex.slice(3, 5), 16) || 0;
		var b = parseInt(hex.slice(5, 7), 16) || 0;
		return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
	}

	function _hashSeed(str) {
		var h = 0;
		for (var i = 0; i < str.length; i++) { h = ((h << 5) - h + str.charCodeAt(i)) | 0; }
		return (h & 0xffff) / 0xffff * Math.PI * 6;
	}

	function _resolveSize(val, unit, w, h, axis) {
		if (unit === 'percent') return (val / 100) * (axis === 'x' ? w : h);
		if (unit === 'vw') return (val / 100) * window.innerWidth;
		if (unit === 'vh') return (val / 100) * window.innerHeight;
		return parseFloat(val);
	}

	function _drawBlob(ctx, x, y, rx, ry, color, alpha, core) {
		var r = Math.max(rx, ry);
		var a = (alpha !== undefined) ? alpha : 1;
		var coreStop = (core !== undefined) ? core : 0.08;
		ctx.save();
		ctx.translate(x, y);
		if (rx !== ry) ctx.scale(rx / r, ry / r);
		// Gradient created inside transformed space so it scales with the ellipse
		var grad = ctx.createRadialGradient(0, 0, 0, 0, 0, r);
		grad.addColorStop(0, _hexToRgba(color, a));
		grad.addColorStop(coreStop + 0.46, _hexToRgba(color, a * 0.68));
		grad.addColorStop(1, _hexToRgba(color, 0));
		ctx.beginPath();
		ctx.arc(0, 0, r, 0, Math.PI * 2);
		ctx.fillStyle = grad;
		ctx.fill();
		ctx.restore();
	}

	function startModalPreview(config) {
		if (_modalRaf) { cancelAnimationFrame(_modalRaf); _modalRaf = 0; }

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
			var safeMargin = ((config.global && config.global.safe_margin) || 0) / 100;

			ctx.globalCompositeOperation = blendMode;

			var _modalOrbs = config.orbs || [];
			for (var _mi = _modalOrbs.length - 1; _mi >= 0; _mi--) {
				var orb    = _modalOrbs[_mi];
				var bw     = _resolveSize(orb.size.w, orb.size.unit, w, h, 'x');
				var bh     = _resolveSize(orb.size.h, orb.size.unit, w, h, 'y');
				var baseX  = _resolveSize(orb.position.x, orb.position.unit, w, h, 'x');
				var baseY  = _resolveSize(orb.position.y, orb.position.unit, w, h, 'y');
				var ax     = (orb.animation.amplitude_x / 100) * w;
				var ay     = (orb.animation.amplitude_y / 100) * h;
				var fx     = orb.animation.frequency_x;
				var fy     = orb.animation.frequency_y;
				var ph     = orb.animation.phase || 0;
				var seed   = _hashSeed(orb.id);
				var type   = orb.animation.type;
				var ox = 0, oy = 0;

				if (type === 'drift') {
					ox = (Math.sin(t * fx + ph) * 0.68 + Math.sin(t * fx * _E   + seed)       * 0.32) * ax;
					oy = (Math.cos(t * fy + ph) * 0.68 + Math.cos(t * fy * _PHI + seed + 1.4) * 0.32) * ay;
				} else if (type === 'orbit') {
					ox = Math.cos(t * fx + ph) * ax;
					oy = Math.sin(t * fy + ph) * ay;
				} else if (type === 'pulse') {
					var sc = 1 + Math.sin(t * fx + ph) * (orb.animation.amplitude_x / 100);
					bw *= sc; bh *= sc;
				} else if (type === 'wave') {
					oy = Math.sin(t * fy + ph) * ay;
				} else if (type === 'figure8') {
					ox = Math.sin(t * fx + ph) * ax;
					oy = Math.sin(t * fy * 2 + ph) * ay * 0.5;
				}

				var smX    = w * safeMargin;
				var smY    = h * safeMargin;
				var finalX = Math.max(smX, Math.min(w - smX, baseX + ox));
				var finalY = Math.max(smY, Math.min(h - smY, baseY + oy));

				ctx.save();
				ctx.filter = 'blur(' + orb.blur + 'px)';
				ctx.globalAlpha = orb.opacity;

				if (orb.shape === 'circle') {
					_drawBlob(ctx, finalX, finalY, bw * 0.5, bh * 0.5, orb.color, 1, 0.08);
				} else if (orb.shape === 'double') {
					_drawBlob(ctx, finalX - bw * 0.125, finalY, bw * 0.425, bh * 0.425, orb.color,            1, 0.12);
					_drawBlob(ctx, finalX + bw * 0.125, finalY, bw * 0.425, bh * 0.425, orb.color_b || orb.color, 1, 0.12);
				} else if (orb.shape === 'triple') {
					_drawBlob(ctx, finalX,              finalY - bh * 0.15, bw * 0.375, bh * 0.375, orb.color,            1, 0.10);
					_drawBlob(ctx, finalX - bw * 0.15, finalY + bh * 0.10, bw * 0.375, bh * 0.375, orb.color_b || orb.color, 1, 0.10);
					_drawBlob(ctx, finalX + bw * 0.15, finalY + bh * 0.10, bw * 0.375, bh * 0.375, orb.color_b || orb.color, 1, 0.10);
				} else if (orb.shape === 'blob') {
					var blobRx = bw * 0.5 * (1 + Math.sin(t * 0.38 + seed) * 0.15);
					var blobRy = bh * 0.5 * (1 + Math.cos(t * 0.31 + seed + 1.2) * 0.15);
					_drawBlob(ctx, finalX, finalY, blobRx, blobRy, orb.color, 1, 0.14);
				}

				ctx.restore();
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

	function relocateAdminNotices() {
		var wrap = document.querySelector('.spherexr-wrap');
		if (!wrap) return;
		var noticesZone = wrap.querySelector('.sxr-admin-notices');
		if (!noticesZone) return;
		var wpbody = document.getElementById('wpbody-content');
		if (!wpbody) return;
		var selectors = [
			'.notice', '.updated', '.update-nag',
			'.notice-success', '.notice-error', '.notice-warning', '.notice-info',
		];
		wpbody.querySelectorAll(selectors.join(', ')).forEach(function (notice) {
			if (!wrap.contains(notice)) {
				noticesZone.appendChild(notice);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { init(); relocateAdminNotices(); });
	} else {
		init();
		relocateAdminNotices();
	}
})();
