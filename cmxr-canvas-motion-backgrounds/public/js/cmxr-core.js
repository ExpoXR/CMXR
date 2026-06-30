/* CMXR Core v1.0.1 — Shared animation math + canvas rendering.
   Single source of truth used by the public engine, the configurator preview
   and the dashboard modal preview. Exposed as window.CMXRCore. */
(function () {
	'use strict';

	var TWO_PI = Math.PI * 2;

	/* Math constants (from child theme orb system) */
	var PHI = 1.61803; // Golden ratio — irrational harmonic secondary
	var E   = 2.71828; // Euler's number — secondary harmonic mixing

	/* ------------------------------------------------------------------ */
	/* Utilities                                                            */
	/* ------------------------------------------------------------------ */

	function clamp(v, lo, hi) { return v < lo ? lo : v > hi ? hi : v; }

	function createPointerTracker(surfaceEl, onActivity, options) {
		options = options || {};
		var debug = !!options.debug;
		var label = options.label || 'pointer';
		var scope = options.scope || 'core';
		var lastMoveLog = 0;
		var pointer = {
			mx: 0,
			my: 0,
			tx: 0,
			ty: 0,
			hover: 0,
			targetHover: 0,
			lastPX: -1,
			lastPY: -1,
		};

		function debugData(extra) {
			var data = {
				mx: pointer.mx,
				my: pointer.my,
				tx: pointer.tx,
				ty: pointer.ty,
				hover: pointer.hover,
				targetHover: pointer.targetHover,
			};
			if (typeof options.getState === 'function') {
				data.state = options.getState() || {};
			}
			if (extra) {
				for (var key in extra) {
					if (Object.prototype.hasOwnProperty.call(extra, key)) data[key] = extra[key];
				}
			}
			return data;
		}

		function debugLog(eventName, extra) {
			if (!debug) return;
			if (window.CMXRDebug && typeof window.CMXRDebug.log === 'function') {
				window.CMXRDebug.log('[CMXR ' + scope + '] ' + label + ' ' + eventName, debugData(extra));
				return;
			}
		}

		function setPosition(e) {
			var rect = surfaceEl.getBoundingClientRect();
			if (!rect.width || !rect.height) {
				debugLog('blocked: empty pointer surface', { width: rect.width, height: rect.height });
				return false;
			}
			pointer.tx = (e.clientX - rect.left) / rect.width - 0.5;
			pointer.ty = (e.clientY - rect.top) / rect.height - 0.5;
			return true;
		}

		function activity() {
			if (typeof onActivity === 'function') onActivity();
		}

		function onPointerEnter(e) {
			setPosition(e);
			pointer.targetHover = 0.72;
			debugLog('pointerenter');
			activity();
		}

		function onPointerMove(e) {
			if (!setPosition(e)) return;
			var vel = 0;
			if (pointer.lastPX >= 0) {
				var dx = e.clientX - pointer.lastPX;
				var dy = e.clientY - pointer.lastPY;
				vel = Math.min(Math.sqrt(dx * dx + dy * dy) / 30, 1);
				pointer.targetHover = 0.72 + vel * 0.28;
			}
			pointer.lastPX = e.clientX;
			pointer.lastPY = e.clientY;
			if (debug) {
				var now = Date.now();
				if (now - lastMoveLog > 250) {
					lastMoveLog = now;
					debugLog('pointermove', { velocity: vel });
				}
			}
			activity();
		}

		function onPointerLeave() {
			pointer.targetHover = 0;
			pointer.tx = 0;
			pointer.ty = 0;
			pointer.lastPX = -1;
			pointer.lastPY = -1;
			debugLog('pointerleave');
		}

		pointer.update = function () {
			pointer.mx += (pointer.tx - pointer.mx) * 0.055;
			pointer.my += (pointer.ty - pointer.my) * 0.055;
			pointer.hover += (pointer.targetHover - pointer.hover) * 0.045;
		};

		pointer.dispose = function () {
			surfaceEl.removeEventListener('pointerenter', onPointerEnter);
			surfaceEl.removeEventListener('pointermove', onPointerMove);
			surfaceEl.removeEventListener('pointerleave', onPointerLeave);
		};

		surfaceEl.addEventListener('pointerenter', onPointerEnter, { passive: true });
		surfaceEl.addEventListener('pointermove', onPointerMove, { passive: true });
		surfaceEl.addEventListener('pointerleave', onPointerLeave, { passive: true });
		debugLog('bound', {
			tagName: surfaceEl.tagName,
			id: surfaceEl.id || '',
			pointerEvents: window.getComputedStyle ? window.getComputedStyle(surfaceEl).pointerEvents : '',
		});

		return pointer;
	}

	// Memoised hex → rgba string (colours/alpha repeat heavily across frames).
	// Bounded so long-lived pages with many distinct alpha values can't grow it
	// without limit — a finite palette stays well under the cap.
	var RGBA_CACHE_MAX = 512;
	var rgbaCache = {};
	var rgbaCacheSize = 0;
	function hexToRgba(hex, alpha) {
		var key = hex + '|' + alpha;
		var cached = rgbaCache[key];
		if (cached !== undefined) return cached;
		var r = parseInt(hex.slice(1, 3), 16) || 0;
		var g = parseInt(hex.slice(3, 5), 16) || 0;
		var b = parseInt(hex.slice(5, 7), 16) || 0;
		var out = 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
		if (rgbaCacheSize >= RGBA_CACHE_MAX) { rgbaCache = {}; rgbaCacheSize = 0; }
		rgbaCache[key] = out;
		rgbaCacheSize++;
		return out;
	}

	// Deterministic seed from string id (prevents sync between orbs)
	function hashSeed(str) {
		str = str || '';
		var h = 0;
		for (var i = 0; i < str.length; i++) { h = ((h << 5) - h + str.charCodeAt(i)) | 0; }
		return (h & 0xffff) / 0xffff * Math.PI * 6;
	}

	// Resolve a sized value with unit to px within a container
	function resolvePx(val, unit, containerW, containerH, axis) {
		if (unit === 'percent') return (val / 100) * (axis === 'x' ? containerW : containerH);
		if (unit === 'vw')      return (val / 100) * window.innerWidth;
		if (unit === 'vh')      return (val / 100) * window.innerHeight;
		return parseFloat(val); // px — use raw
	}

	// Memoised blur filter string (rebuilt only when the orb's blur changes)
	function blurFilter(orb) {
		if (orb._fblur !== orb.blur) {
			orb._fstr  = orb.blur > 0 ? 'blur(' + orb.blur + 'px)' : 'none';
			orb._fblur = orb.blur;
		}
		return orb._fstr;
	}

	/* ------------------------------------------------------------------ */
	/* Gradient cache (per-context)                                         */
	/*                                                                      */
	/* A unit-radius gradient (0..1) is created once per colour/alpha/core  */
	/* combination and re-used every frame. Canvas paints gradients under   */
	/* the current transform, so a single unit gradient scales to any orb   */
	/* size — eliminating per-frame createRadialGradient() calls.           */
	/* ------------------------------------------------------------------ */

	var gradCaches = (typeof WeakMap !== 'undefined') ? new WeakMap() : null;

	function getGradient(ctx, color, alpha, coreStop) {
		var cache = gradCaches ? gradCaches.get(ctx) : null;
		if (!cache) { cache = {}; if (gradCaches) gradCaches.set(ctx, cache); }

		var key = color + '|' + alpha + '|' + coreStop;
		var grad = cache[key];
		if (!grad) {
			grad = ctx.createRadialGradient(0, 0, 0, 0, 0, 1);
			grad.addColorStop(0, hexToRgba(color, alpha));
			grad.addColorStop(coreStop + 0.46, hexToRgba(color, alpha * 0.68));
			grad.addColorStop(1, hexToRgba(color, 0));
			cache[key] = grad;
		}
		return grad;
	}

	/* Draw a single radial gradient blob with a soft falloff core */
	function drawBlob(ctx, x, y, rx, ry, color, alpha, core) {
		var maxR = Math.max(rx, ry);
		if (maxR <= 0) return;
		var a = (alpha !== undefined) ? alpha : 1;
		var coreStop = (core !== undefined) ? core : 0.08;
		ctx.save();
		ctx.translate(x, y);
		ctx.scale(rx, ry); // unit circle → ellipse (rx, ry); gradient scales with it
		ctx.fillStyle = getGradient(ctx, color, a, coreStop);
		ctx.beginPath();
		ctx.arc(0, 0, 1, 0, TWO_PI);
		ctx.fill();
		ctx.restore();
	}

	function paintColors(orb) {
		var colorA = orb.color || '#38a3d7';
		var colorB = orb.color_b || colorA;
		var mode = orb.color_mode || 'solid';
		var stops = [];

		if (mode === 'gradient' && Array.isArray(orb.color_stops) && orb.color_stops.length) {
			stops = orb.color_stops.slice(0, 5);
		} else if (mode === 'dual' || mode === 'gradient') {
			stops = [colorA, colorB];
		} else {
			stops = [colorA];
		}

		return stops.filter(function (color) { return /^#[0-9a-f]{6}$/i.test(color); }).slice(0, 5);
	}

	function animatedGradientCoords(x1, y1, x2, y2, animation, t) {
		var minX = Math.min(x1, x2);
		var maxX = Math.max(x1, x2);
		var minY = Math.min(y1, y2);
		var maxY = Math.max(y1, y2);
		var w = Math.max(1, maxX - minX);
		var h = Math.max(1, maxY - minY);
		var phase = Math.sin((t || 0) * 0.8) * 0.32;
		var ox = 0;
		var oy = 0;
		var coords;

		if (animation === 'right-left') {
			coords = [maxX, minY, minX, minY];
			ox = -phase * w;
		} else if (animation === 'top-bottom') {
			coords = [minX, minY, minX, maxY];
			oy = phase * h;
		} else if (animation === 'bottom-top') {
			coords = [minX, maxY, minX, minY];
			oy = -phase * h;
		} else if (animation === 'both') {
			coords = [minX, minY, maxX, maxY];
			ox = phase * w;
			oy = phase * h;
		} else {
			coords = [minX, minY, maxX, minY];
			if (animation === 'left-right') ox = phase * w;
		}

		return {
			x1: coords[0] + ox,
			y1: coords[1] + oy,
			x2: coords[2] + ox,
			y2: coords[3] + oy,
		};
	}

	function shapePaint(ctx, orb, x1, y1, x2, y2, t) {
		var colors = paintColors(orb);
		var mode = orb.color_mode || 'solid';

		if ((mode === 'dual' || mode === 'gradient') && colors.length > 1) {
			var coords = animatedGradientCoords(x1, y1, x2, y2, orb.color_animation || 'none', t);
			var grad = ctx.createLinearGradient(coords.x1, coords.y1, coords.x2, coords.y2);
			colors.forEach(function (color, idx) {
				var stop = colors.length === 1 ? 0 : idx / (colors.length - 1);
				grad.addColorStop(stop, hexToRgba(color, 1));
			});
			return grad;
		}

		return hexToRgba(colors[0] || '#38a3d7', 1);
	}

	function drawPaintedEllipse(ctx, x, y, rx, ry, orb, t) {
		ctx.fillStyle = shapePaint(ctx, orb, x - rx, y - ry, x + rx, y + ry, t);
		ctx.beginPath();
		ctx.ellipse(x, y, rx, ry, 0, 0, TWO_PI);
		ctx.fill();
	}

	function strokeWidth(rx, ry, factor) {
		return Math.max(1, Math.min(rx, ry) * (factor || 0.16));
	}

	function roundedRectPath(ctx, x, y, w, h, r) {
		var rr = Math.min(r, Math.abs(w) / 2, Math.abs(h) / 2);
		var x2 = x + w;
		var y2 = y + h;
		ctx.beginPath();
		ctx.moveTo(x + rr, y);
		ctx.lineTo(x2 - rr, y);
		ctx.quadraticCurveTo(x2, y, x2, y + rr);
		ctx.lineTo(x2, y2 - rr);
		ctx.quadraticCurveTo(x2, y2, x2 - rr, y2);
		ctx.lineTo(x + rr, y2);
		ctx.quadraticCurveTo(x, y2, x, y2 - rr);
		ctx.lineTo(x, y + rr);
		ctx.quadraticCurveTo(x, y, x + rr, y);
		ctx.closePath();
	}

	function drawEllipseOutline(ctx, x, y, rx, ry, orb, t) {
		var lw = strokeWidth(rx, ry, 0.16);
		ctx.strokeStyle = shapePaint(ctx, orb, x - rx, y - ry, x + rx, y + ry, t);
		ctx.lineWidth = lw;
		ctx.beginPath();
		ctx.ellipse(x, y, Math.max(1, rx - lw * 0.5), Math.max(1, ry - lw * 0.5), 0, 0, TWO_PI);
		ctx.stroke();
	}

	function drawRing(ctx, x, y, rx, ry, orb, t) {
		ctx.fillStyle = shapePaint(ctx, orb, x - rx, y - ry, x + rx, y + ry, t);
		ctx.beginPath();
		ctx.ellipse(x, y, rx, ry, 0, 0, TWO_PI);
		ctx.ellipse(x, y, Math.max(1, rx * 0.54), Math.max(1, ry * 0.54), 0, 0, TWO_PI, true);
		ctx.fill('evenodd');
	}

	function drawLineShape(ctx, x, y, rx, ry, orb, t) {
		ctx.strokeStyle = shapePaint(ctx, orb, x - rx, y - ry, x + rx, y + ry, t);
		ctx.lineWidth = Math.max(1, ry * 2);
		ctx.lineCap = 'round';
		ctx.beginPath();
		ctx.moveTo(x - rx, y);
		ctx.lineTo(x + rx, y);
		ctx.stroke();
	}

	function drawWaveLine(ctx, x, y, rx, ry, orb, t, seed) {
		var segments = 36;
		var amp = Math.max(1, ry * 0.8);
		var phase = t * 0.9 + seed;
		ctx.strokeStyle = shapePaint(ctx, orb, x - rx, y - ry, x + rx, y + ry, t);
		ctx.lineWidth = Math.max(1, Math.min(ry * 0.45, rx * 0.16));
		ctx.lineCap = 'round';
		ctx.lineJoin = 'round';
		ctx.beginPath();
		for (var i = 0; i <= segments; i++) {
			var p = i / segments;
			var px = x - rx + p * rx * 2;
			var py = y + Math.sin(p * TWO_PI * 2 + phase) * amp;
			if (i === 0) ctx.moveTo(px, py);
			else ctx.lineTo(px, py);
		}
		ctx.stroke();
	}

	function drawRectShape(ctx, x, y, rx, ry, orb, outline, rounded, t) {
		var left = x - rx;
		var top = y - ry;
		var w = rx * 2;
		var h = ry * 2;
		var r = rounded ? Math.min(rx, ry) : 0;
		var paint = shapePaint(ctx, orb, left, top, left + w, top + h, t);

		if (rounded) {
			roundedRectPath(ctx, left, top, w, h, r);
		} else {
			ctx.beginPath();
			ctx.rect(left, top, w, h);
		}

		if (outline) {
			ctx.strokeStyle = paint;
			ctx.lineWidth = strokeWidth(rx, ry, 0.14);
			ctx.stroke();
		} else {
			ctx.fillStyle = paint;
			ctx.fill();
		}
	}

	/* ------------------------------------------------------------------ */
	/* Animation math                                                       */
	/* ------------------------------------------------------------------ */

	function computeOrbScale(orb, t) {
		if (orb.animation.type !== 'pulse') return 1;
		return 1 + Math.sin(t * orb.animation.frequency_x + (orb.animation.phase || 0)) * (orb.animation.amplitude_x / 100);
	}

	function computeOrbPos(orb, seed, t, w, h, safeMarginPct, mx, my, hover, interMode, interStrength, interRadius) {
		var ax = resolvePx(orb.animation.amplitude_x, 'percent', w, h, 'x');
		var ay = resolvePx(orb.animation.amplitude_y, 'percent', w, h, 'y');
		var fx = orb.animation.frequency_x;
		var fy = orb.animation.frequency_y;
		var ph = orb.animation.phase || 0;

		var baseX = resolvePx(orb.position.x, orb.position.unit, w, h, 'x');
		var baseY = resolvePx(orb.position.y, orb.position.unit, w, h, 'y');
		var bw    = resolvePx(orb.size.w, orb.size.unit, w, h, 'x') * 0.5; // half-width radius
		var bh    = resolvePx(orb.size.h, orb.size.unit, w, h, 'y') * 0.5; // half-height radius

		var ox = 0, oy = 0;
		var type = orb.animation.type;

		if (type === 'drift') {
			// Compound harmonic — primary wave plus an irrational-frequency secondary for organic drift
			ox = (Math.sin(t * fx + ph) * 0.68 + Math.sin(t * fx * E + seed) * 0.32) * ax;
			oy = (Math.cos(t * fy + ph) * 0.68 + Math.cos(t * fy * PHI + seed + 1.4) * 0.32) * ay;
		} else if (type === 'orbit') {
			ox = Math.cos(t * fx + ph) * ax;
			oy = Math.sin(t * fy + ph) * ay;
		} else if (type === 'wave') {
			oy = Math.sin(t * fy + ph) * ay;
		} else if (type === 'figure8') {
			ox = Math.sin(t * fx + ph) * ax;
			oy = Math.sin(t * fy * 2 + ph) * ay * 0.5;
		}
		// 'pulse' and 'fixed' use no positional offset

		// Safe margin clamp — orb center stays within safeMarginPct% of container edge
		var smX  = w * (safeMarginPct / 100);
		var smY  = h * (safeMarginPct / 100);
		var posX = clamp(baseX + ox, smX, w - smX);
		var posY = clamp(baseY + oy, smY, h - smY);

		// Interactivity offset
		var direction = orb.interaction_direction === 'reverse' ? -1 : 1;
		var depth = (orb.parallax || 0.5) * (interStrength || 0) * direction;

		if (interMode === 'parallax') {
			posX += mx * depth * w * 0.08;
			posY += my * depth * h * 0.06;
		} else if (interMode === 'repel' || interMode === 'attract') {
			// Gaussian force field — falloff strongest near the pointer, fading with distance
			var pointerX = (mx + 0.5) * w;
			var pointerY = (my + 0.5) * h;
			var dx = posX - pointerX;
			var dy = posY - pointerY;
			var r2 = (interRadius / 100) * Math.max(w, h);
			var force = Math.exp(-(dx * dx + dy * dy) / (r2 * r2)) * hover * depth;
			var dir = interMode === 'repel' ? 1 : -1;
			posX += dx * force * 0.08 * dir;
			posY += dy * force * 0.06 * dir;
		}

		return { x: posX, y: posY, rx: bw, ry: bh };
	}

	/* ------------------------------------------------------------------ */
	/* Composite orb draw (shape dispatch)                                  */
	/* Callers set globalCompositeOperation (blend mode) once before the    */
	/* loop; this handles per-orb blur + opacity + shape geometry.          */
	/* ------------------------------------------------------------------ */

	function drawOrb(ctx, orb, pos, scale, t, seed) {
		var rx = pos.rx * scale;
		var ry = pos.ry * scale;
		var x = pos.x, y = pos.y;

		ctx.save();
		ctx.filter = blurFilter(orb);
		ctx.globalAlpha = clamp(orb.opacity, 0, 1);

		var rotRad = ((orb.rotation || 0) * Math.PI) / 180;
		if (rotRad) {
			ctx.translate(x, y);
			ctx.rotate(rotRad);
			ctx.translate(-x, -y);
		}

		switch (orb.shape) {
			case 'double':
				if ((orb.color_mode || 'solid') === 'solid') {
					drawBlob(ctx, x - rx * 0.25, y, rx * 0.85, ry * 0.85, orb.color, 1, 0.12);
					drawBlob(ctx, x + rx * 0.25, y, rx * 0.85, ry * 0.85, orb.color_b || orb.color, 1, 0.12);
				} else {
					drawPaintedEllipse(ctx, x - rx * 0.25, y, rx * 0.85, ry * 0.85, orb, t);
					drawPaintedEllipse(ctx, x + rx * 0.25, y, rx * 0.85, ry * 0.85, orb, t);
				}
				break;
			case 'triple':
				if ((orb.color_mode || 'solid') === 'solid') {
					drawBlob(ctx, x,            y - ry * 0.3, rx * 0.75, ry * 0.75, orb.color, 1, 0.10);
					drawBlob(ctx, x - rx * 0.3, y + ry * 0.2, rx * 0.75, ry * 0.75, orb.color_b || orb.color, 1, 0.10);
					drawBlob(ctx, x + rx * 0.3, y + ry * 0.2, rx * 0.75, ry * 0.75, orb.color_b || orb.color, 1, 0.10);
				} else {
					drawPaintedEllipse(ctx, x,            y - ry * 0.3, rx * 0.75, ry * 0.75, orb, t);
					drawPaintedEllipse(ctx, x - rx * 0.3, y + ry * 0.2, rx * 0.75, ry * 0.75, orb, t);
					drawPaintedEllipse(ctx, x + rx * 0.3, y + ry * 0.2, rx * 0.75, ry * 0.75, orb, t);
				}
				break;
			case 'blob':
				// Morphing blob — slightly irregular ellipse with time-based deformation
				var brx = rx * (1 + Math.sin(t * 0.38 + seed) * 0.15);
				var bry = ry * (1 + Math.cos(t * 0.31 + seed + 1.2) * 0.15);
				if ((orb.color_mode || 'solid') === 'solid') drawBlob(ctx, x, y, brx, bry, orb.color, 1, 0.14);
				else drawPaintedEllipse(ctx, x, y, brx, bry, orb, t);
				break;
			case 'circle-outline':
				drawEllipseOutline(ctx, x, y, rx, ry, orb, t);
				break;
			case 'ring':
				drawRing(ctx, x, y, rx, ry, orb, t);
				break;
			case 'line':
				drawLineShape(ctx, x, y, rx, ry, orb, t);
				break;
			case 'wave-line':
				drawWaveLine(ctx, x, y, rx, ry, orb, t, seed);
				break;
			case 'rect':
				drawRectShape(ctx, x, y, rx, ry, orb, false, false, t);
				break;
			case 'rect-outline':
				drawRectShape(ctx, x, y, rx, ry, orb, true, false, t);
				break;
			case 'capsule':
				drawRectShape(ctx, x, y, rx, ry, orb, false, true, t);
				break;
			case 'capsule-outline':
				drawRectShape(ctx, x, y, rx, ry, orb, true, true, t);
				break;
			default: // circle
				if ((orb.color_mode || 'solid') === 'solid') drawBlob(ctx, x, y, rx, ry, orb.color, 1, 0.08);
				else drawPaintedEllipse(ctx, x, y, rx, ry, orb, t);
		}

		ctx.restore();
	}

	// Map a config blend mode to a valid canvas globalCompositeOperation.
	// 'normal' is a CSS keyword, not a canvas op — assigning it is silently
	// ignored by the browser, so translate it to 'source-over' (standard alpha
	// compositing: an opaque upper orb fully covers lower ones, partial opacity
	// blends proportionally). Unknown values fall back to 'source-over'.
	var CANVAS_BLEND_OPS = {
		'normal': 'source-over',
		'source-over': 'source-over',
		'screen': 'screen',
		'multiply': 'multiply',
		'overlay': 'overlay',
		'lighten': 'lighten',
		'darken': 'darken',
		'hard-light': 'hard-light',
		'soft-light': 'soft-light',
		'color-dodge': 'color-dodge',
		'color-burn': 'color-burn',
	};

	function blendOp(mode) {
		return CANVAS_BLEND_OPS[mode] || 'source-over';
	}

	window.CMXRCore = {
		PHI: PHI,
		E: E,
		clamp: clamp,
		hexToRgba: hexToRgba,
		blendOp: blendOp,
		createPointerTracker: createPointerTracker,
		hashSeed: hashSeed,
		resolvePx: resolvePx,
		blurFilter: blurFilter,
		drawBlob: drawBlob,
		computeOrbScale: computeOrbScale,
		computeOrbPos: computeOrbPos,
		drawOrb: drawOrb,
	};
})();
