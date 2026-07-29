/* CMXR renderer registry + shared renderer lifecycle. */
(function () {
	'use strict';

	var Core = window.CMXRCore;
	if (!Core) return;

	var factories = {};
	var scheduledFrames = [];
	var schedulerRaf = 0;

	function schedulerTick(now) {
		schedulerRaf = 0;
		var frames = scheduledFrames.slice();
		for (var i = 0; i < frames.length; i++) frames[i](now);
		if (scheduledFrames.length && !schedulerRaf) schedulerRaf = requestAnimationFrame(schedulerTick);
	}

	function schedulerAdd(frame) {
		if (scheduledFrames.indexOf(frame) === -1) scheduledFrames.push(frame);
		if (!schedulerRaf) schedulerRaf = requestAnimationFrame(schedulerTick);
	}

	function schedulerRemove(frame) {
		var index = scheduledFrames.indexOf(frame);
		if (index !== -1) scheduledFrames.splice(index, 1);
		if (!scheduledFrames.length && schedulerRaf) {
			cancelAnimationFrame(schedulerRaf);
			schedulerRaf = 0;
		}
	}

	function register(effectType, factory) {
		if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(effectType) || typeof factory !== 'function' || factories[effectType]) {
			return false;
		}
		factories[effectType] = factory;
		return true;
	}

	function create(effectType, surface, config, options) {
		var factory = factories[effectType];
		if (!factory) throw new Error('Unknown CMXR renderer: ' + effectType);
		return factory(surface, config, options || {});
	}

	function createHost(surface, config, effect, options) {
		var externalCanvas = !!options.canvas;
		var canvas = options.canvas || document.createElement('canvas');
		var ctx = canvas.getContext('2d', { alpha: true });
		if (!ctx) throw new Error('Canvas 2D context unavailable.');
		var environment = options.environment || 'frontend';
		var state = {
			w: 0,
			h: 0,
			dpr: 1,
			running: false,
			visible: true,
			destroyed: false,
			lastTime: 0,
		};
		var io = null;
		var ro = null;
		var resizeTimer = 0;
		var media = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
		var reducedOverride = null;

		if (!externalCanvas) {
			canvas.className = 'cmxr-canvas';
			canvas.setAttribute('aria-hidden', 'true');
			surface.insertBefore(canvas, surface.firstChild);
		} else {
			canvas.setAttribute('aria-hidden', 'true');
		}
		surface.classList.add('cmxr-ready');

		function isReduced() {
			return reducedOverride === null ? !!(media && media.matches) : !!reducedOverride;
		}

		function resize(immediate) {
			if (state.destroyed) return;
			if (resizeTimer) clearTimeout(resizeTimer);
			var apply = function () {
				resizeTimer = 0;
				var rect = surface.getBoundingClientRect();
				var rawW = rect.width || surface.clientWidth || surface.offsetWidth || 0;
				var rawH = rect.height || surface.clientHeight || surface.offsetHeight || 0;
				var computed = window.getComputedStyle ? window.getComputedStyle(surface) : null;
				var minW = computed ? parseInt(computed.minWidth, 10) || 0 : 0;
				var minH = computed ? parseInt(computed.minHeight, 10) || 0 : 0;
				var minimum = effect.minimumSize ? effect.minimumSize() : 0;
				state.w = Math.max(rawW, minW, minimum) || 300;
				state.h = Math.max(rawH, minH, minimum) || 200;
				var cap = effect.dprCap ? effect.dprCap() : (options.dprCap || 1.75);
				state.dpr = Math.min(window.devicePixelRatio || 1, cap);
				canvas.width = Math.round(state.w * state.dpr);
				canvas.height = Math.round(state.h * state.dpr);
				if (externalCanvas) {
					canvas.style.width = '100%';
					canvas.style.height = '100%';
				}
				effect.resize(state.w, state.h, state.dpr);
				drawOnce();
			};
			if (immediate || !effect.debounceResize) apply();
			else resizeTimer = setTimeout(apply, 250);
		}

		function frame(now) {
			if (!state.running || state.destroyed) return;
			var delta = Math.min(40, Math.max(0, now - (state.lastTime || now)));
			state.lastTime = now;
			effect.render(now, delta, isReduced());
			if (isReduced()) {
				state.running = false;
				schedulerRemove(frame);
				return;
			}
		}

		function resume() {
			if (state.destroyed || state.running || !state.visible) return;
			state.running = true;
			state.lastTime = 0;
			schedulerAdd(frame);
		}

		function pause() {
			state.running = false;
			schedulerRemove(frame);
		}

		function drawOnce() {
			if (state.destroyed || !state.w || !state.h) return;
			effect.render(performance.now(), 0, isReduced());
		}

		function onReducedChange() {
			pause();
			if (isReduced()) drawOnce();
			else resume();
		}

		function onVisibility() {
			if (document.hidden) pause();
			else if (state.visible) resume();
		}

		var hostApi = {
			canvas: canvas,
			context: ctx,
			state: state,
			resume: resume,
			pause: pause,
			isReduced: isReduced,
			drawOnce: drawOnce,
		};
		effect.init(hostApi);

		if (environment === 'frontend' && 'IntersectionObserver' in window) {
			io = new IntersectionObserver(function (entries) {
				state.visible = !!(entries[0] && entries[0].isIntersecting);
				if (state.visible) resume();
				else pause();
			}, { threshold: options.ioThreshold || 0.01 });
			io.observe(surface);
		}
		// vw/vh orbs are keyed to the browser viewport, which can change without the
		// surface box changing — so watch window resize on the frontend regardless of
		// ResizeObserver support. onWindowResize is also the no-RO surface fallback.
		function onWindowResize() { resize(false); }
		if ('ResizeObserver' in window) {
			ro = new ResizeObserver(function () { resize(false); });
			ro.observe(surface);
			if (environment === 'frontend') window.addEventListener('resize', onWindowResize, { passive: true });
		} else {
			window.addEventListener('resize', onWindowResize, { passive: true });
		}
		if (media) {
			if (media.addEventListener) media.addEventListener('change', onReducedChange);
			else if (media.addListener) media.addListener(onReducedChange);
		}
		document.addEventListener('visibilitychange', onVisibility);

		resize(true);
		if (!isReduced()) resume();

		return {
			init: function () {},
			resize: function () { resize(true); },
			render: drawOnce,
			pause: pause,
			resume: resume,
			previewPatch: function (settings) {
				if (effect.previewPatch) effect.previewPatch(settings);
				resize(true);
			},
			setReducedMotion: function (enabled) {
				reducedOverride = enabled === null ? null : !!enabled;
				onReducedChange();
			},
			getDebugState: function () {
				return {
					width: state.w,
					height: state.h,
					dpr: state.dpr,
					running: state.running,
					visible: state.visible,
					reducedMotion: isReduced(),
					effect: effect.debugState ? effect.debugState() : {},
				};
			},
			destroy: function () {
				if (state.destroyed) return;
				state.destroyed = true;
				pause();
				if (resizeTimer) clearTimeout(resizeTimer);
				if (io) io.disconnect();
				if (ro) ro.disconnect();
				window.removeEventListener('resize', onWindowResize);
				document.removeEventListener('visibilitychange', onVisibility);
				if (media) {
					if (media.removeEventListener) media.removeEventListener('change', onReducedChange);
					else if (media.removeListener) media.removeListener(onReducedChange);
				}
				effect.destroy();
				if (!externalCanvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
				surface.classList.remove('cmxr-ready');
			},
		};
	}

	function layeredShapesFactory(surface, config, options) {
		var pointer;
		var time = 0;
		// cfg is swapped by previewPatch so the configurator can adopt a fresh
		// config object (e.g. after save) without stranding this renderer.
		var cfg = config;
		// Preview surfaces (configurator, dashboard modal) simulate the viewport
		// with their own frame so vw/vh is WYSIWYG; only the frontend uses the
		// real browser viewport.
		var isPreview = options.environment && options.environment !== 'frontend';

		function reseed() {
			orbSeeds = (cfg.orbs || []).map(function (orb) {
				return Core.hashSeed(orb.id || orb.color);
			});
		}
		var orbSeeds;
		reseed();
		var host;

		var effect = {
			init: function (hostApi) {
				host = hostApi;
				pointer = Core.createPointerTracker(surface, host.resume, {
					debug: !!options.debug,
					scope: options.environment || 'frontend',
					label: cfg.animation_id || 'layered-shapes',
				});
			},
			resize: function () {},
			render: function (now, delta) {
				var state = host.state;
				var ctx = host.context;
				var speed = (cfg.global && cfg.global.speed) || 1;
				time += delta * 0.001 * speed * (1 + pointer.hover * 0.35);
				pointer.update();
				ctx.setTransform(state.dpr, 0, 0, state.dpr, 0, 0);
				ctx.clearRect(0, 0, state.w, state.h);

				// Viewport basis for vw/vh: the preview frame stands in for the
				// viewport in the configurator (WYSIWYG); the real window elsewhere.
				var vpW = isPreview ? state.w : window.innerWidth;
				var vpH = isPreview ? state.h : window.innerHeight;

				var global = cfg.global || {};
				var interact = global.interactivity || {};
				var enabled = interact.enabled !== false && interact.mode !== 'none';
				ctx.globalCompositeOperation = Core.blendOp(global.blend_mode || 'normal');
				var orbs = cfg.orbs || [];
				for (var i = orbs.length - 1; i >= 0; i--) {
					var orb = orbs[i];
					var seed = orbSeeds[i] || 0;
					var scale = Core.computeOrbScale(orb, time);
					var pos = Core.computeOrbPos(
						orb,
						seed,
						time,
						state.w,
						state.h,
						global.safe_margin || 0,
						pointer.mx,
						pointer.my,
						pointer.hover,
						enabled ? interact.mode : 'none',
						interact.strength || 0.5,
						interact.radius || 30,
						vpW,
						vpH,
						global.anchor
					);
					Core.drawOrb(ctx, orb, pos, scale, time, seed);
				}
				ctx.globalCompositeOperation = 'source-over';
			},
			previewPatch: function (next) {
				if (next) cfg = next;
				reseed();
			},
			destroy: function () {
				if (pointer) pointer.dispose();
			},
			minimumSize: function () { return 0; },
			dprCap: function () { return options.dprCap || 1.75; },
			debugState: function () { return { orbCount: (cfg.orbs || []).length }; },
		};
		return createHost(surface, config, effect, options);
	}

	function proceduralOrbsFactory(surface, sourceConfig, options) {
		var config = JSON.parse(JSON.stringify(sourceConfig));
		var settings = config.settings || {};
		var interactive = !!settings.physics;
		var configuredSeed = settings.seed;
		var seed = settings.seed === null || settings.seed === undefined
			? String(Math.random()) + ':' + String(Date.now())
			: String(settings.seed);
		var random = Core.createPRNG(seed);
		var noise = new Core.SimplexNoise(seed + ':noise');
		var orbs = [];
		var rings = [];
		var burst = null;
		var input = null;
		var host = null;
		var width = 0;
		var height = 0;

		function rand(min, max) {
			return random() * (max - min) + min;
		}

		function map(value, inMin, inMax, outMin, outMax) {
			return ((value - inMin) / (inMax - inMin)) * (outMax - outMin) + outMin;
		}

		function key() {
			return Core.responsiveKey(width, settings.breakpoints);
		}

		function basisValue(name) {
			if (name === 'width') return width;
			if (name === 'height') return height;
			return Math.min(width, height);
		}

		function palette() {
			var p = settings.palette || {};
			if (p.mode === 'fixed' && p.fixed_colors && p.fixed_colors.length) return p.fixed_colors.slice();
			var hueMin = p.hue_min === undefined ? 0 : p.hue_min;
			var hueMax = p.hue_max === undefined ? 360 : p.hue_max;
			var base = Math.floor(rand(hueMin, hueMax));
			return (p.hue_offsets || [0, 30, 60]).map(function (offset) {
				var saturation = p.saturation === undefined ? 95 : p.saturation;
				var lightness = p.lightness === undefined ? 50 : p.lightness;
				return 'hsl(' + (base + offset) + ', ' + saturation + '%, ' + lightness + '%)';
			});
		}

		function boundsFor(mode) {
			if (interactive) {
				var padding = settings.bounds && settings.bounds.padding !== undefined ? settings.bounds.padding : 60;
				return { minX: padding, maxX: width - padding, minY: padding, maxY: height - padding };
			}
			var b = settings.bounds[mode];
			var distance = basisValue(b.distance_basis) / b.distance_divisor;
			var originX = width * b.origin_x;
			var originY = height * b.origin_y;
			return { minX: originX - distance, maxX: originX + distance, minY: originY - distance, maxY: originY + distance };
		}

		function radiusFor(mode) {
			var r = settings.radius[mode];
			var basis = basisValue(r[2]);
			return { min: basis * r[0], max: basis * r[1] };
		}

		function createOrbs() {
			random = Core.createPRNG(seed + ':' + width + 'x' + height);
			noise = new Core.SimplexNoise(seed + ':noise');
			orbs = [];
			var mode = key();
			var bounds = boundsFor(mode);
			var radius = radiusFor(mode);
			var colors = palette();
			var count = Math.min(20, Math.max(1, settings.counts[mode]));
			for (var i = 0; i < count; i++) {
				orbs.push({
					x: rand(bounds.minX, bounds.maxX),
					y: rand(bounds.minY, bounds.maxY),
					vx: 0,
					vy: 0,
					radius: rand(radius.min, radius.max),
					scale: 1,
					color: colors[Math.floor(rand(0, colors.length))],
					xOff: rand(0, 1000),
					yOff: rand(0, 1000),
					bounds: bounds,
				});
			}
		}

		function trigger(x, y, now) {
			if (!interactive || host.isReduced()) return;
			if (input && input.pointerType === 'touch' && settings.touch && !settings.touch.enabled) return;
			if (settings.burst && settings.burst.enabled) burst = { x: x, y: y, createdAt: now };
			if (settings.ripple && settings.ripple.enabled) {
				rings.push({ x: x, y: y, createdAt: now });
				if (rings.length > 16) rings.shift();
			}
		}

		function updateOrb(orb, now, step, reduced) {
			if (reduced) return;
			var nx = noise.noise2D(orb.xOff, orb.xOff);
			var ny = noise.noise2D(orb.yOff, orb.yOff);
			var sn = noise.noise2D(orb.xOff, orb.yOff);
			orb.scale = map(sn, -1, 1, settings.scale.min, settings.scale.max);
			orb.xOff += settings.simplex_increment * step;
			orb.yOff += settings.simplex_increment * step;

			if (!interactive) {
				orb.x = map(nx, -1, 1, orb.bounds.minX, orb.bounds.maxX);
				orb.y = map(ny, -1, 1, orb.bounds.minY, orb.bounds.maxY);
				return;
			}

			var physics = settings.physics;
			orb.vx += nx * physics.wander_force * step;
			orb.vy += ny * physics.wander_force * step;

			if (input && input.active && !(input.pointerType === 'touch' && settings.touch && !settings.touch.enabled)) {
				var dx = input.x - orb.x;
				var dy = input.y - orb.y;
				var dist = Math.sqrt(dx * dx + dy * dy) || 1;
				var pull = Math.min(dist, physics.attraction_radius) / physics.attraction_radius;
				var attraction = physics.attraction * pull * step;
				orb.vx += (dx / dist) * attraction;
				orb.vy += (dy / dist) * attraction;
			}

			if (burst) {
				var burstSettings = settings.burst;
				var age = (now - burst.createdAt) / burstSettings.duration_ms;
				if (age >= 1) {
					burst = null;
				} else {
					var bx = orb.x - burst.x;
					var by = orb.y - burst.y;
					var bd = Math.sqrt(bx * bx + by * by) || 1;
					var force = (1 - age) * burstSettings.force * step;
					orb.vx += (bx / bd) * force;
					orb.vy += (by / bd) * force;
				}
			}

			var damping = Math.pow(physics.damping, step);
			orb.vx *= damping;
			orb.vy *= damping;
			orb.x += orb.vx * step;
			orb.y += orb.vy * step;

			var margin = physics.boundary_margin;
			var spring = physics.boundary_spring * step;
			if (orb.x < orb.bounds.minX - margin) orb.vx += spring;
			if (orb.x > orb.bounds.maxX + margin) orb.vx -= spring;
			if (orb.y < orb.bounds.minY - margin) orb.vy += spring;
			if (orb.y > orb.bounds.maxY + margin) orb.vy -= spring;
		}

		function drawOrb(ctx, orb) {
			var radius = orb.radius * orb.scale;
			var gradient = ctx.createRadialGradient(orb.x, orb.y, 0, orb.x, orb.y, radius);
			gradient.addColorStop(0, orb.color);
			gradient.addColorStop(1, 'transparent');
			ctx.save();
			ctx.globalAlpha = settings.alpha;
			ctx.fillStyle = gradient;
			ctx.beginPath();
			ctx.arc(orb.x, orb.y, radius, 0, Math.PI * 2);
			ctx.fill();
			ctx.restore();
		}

		var effect = {
			debounceResize: true,
			init: function (hostApi) {
				host = hostApi;
				if (interactive) input = Core.createLocalInputTracker(surface, host.resume, trigger);
			},
			resize: function (w, h) {
				width = w;
				height = h;
				createOrbs();
			},
			render: function (now, delta, reduced) {
				var ctx = host.context;
				var dpr = host.state.dpr;
				var mode = key();
				var blur = settings.blur[mode];
				var step = delta ? Math.min(2.4, delta / 16.667) : 1;
				ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
				ctx.clearRect(0, 0, width, height);
				ctx.filter = 'blur(' + blur + 'px)';

				if (interactive && input && input.active && !reduced && settings.aura.enabled) {
					var aura = ctx.createRadialGradient(input.x, input.y, 0, input.x, input.y, settings.aura.radius);
					aura.addColorStop(0, settings.aura.color);
					aura.addColorStop(1, 'transparent');
					ctx.save();
					ctx.fillStyle = aura;
					ctx.beginPath();
					ctx.arc(input.x, input.y, settings.aura.radius, 0, Math.PI * 2);
					ctx.fill();
					ctx.restore();
				}

				for (var i = 0; i < orbs.length; i++) {
					updateOrb(orbs[i], now, step, reduced);
					drawOrb(ctx, orbs[i]);
				}

				ctx.filter = 'none';
				if (interactive && !reduced) {
					rings = rings.filter(function (ring) {
						var age = (now - ring.createdAt) / settings.ripple.duration_ms;
						if (age >= 1) return false;
						ctx.save();
						ctx.globalAlpha = (1 - age) * settings.ripple.alpha;
						ctx.strokeStyle = settings.ripple.color;
						ctx.lineWidth = settings.ripple.line_width * (1 - age * 0.6);
						ctx.beginPath();
						ctx.arc(ring.x, ring.y, age * settings.ripple.max_radius, 0, Math.PI * 2);
						ctx.stroke();
						ctx.restore();
						return true;
					});
				}
			},
			previewPatch: function (nextSettings) {
				settings = JSON.parse(JSON.stringify(nextSettings));
				interactive = !!settings.physics;
				if (settings.seed !== configuredSeed) {
					configuredSeed = settings.seed;
					seed = settings.seed === null || settings.seed === undefined || settings.seed === ''
						? String(Math.random()) + ':' + String(Date.now())
						: String(settings.seed);
				}
				createOrbs();
			},
			destroy: function () {
				if (input) input.dispose();
				input = null;
				orbs = [];
				rings = [];
				burst = null;
			},
			minimumSize: function () { return settings.minimum_size || 200; },
			dprCap: function () { return settings.dpr_cap || 1.5; },
			debugState: function () {
				return { orbCount: orbs.length, responsiveMode: key(), seed: configuredSeed };
			},
		};

		return createHost(surface, config, effect, options);
	}

	register('layered-shapes', layeredShapesFactory);
	register('procedural-orbs', proceduralOrbsFactory);

	window.CMXRRenderers = {
		register: register,
		create: create,
		has: function (effectType) { return !!factories[effectType]; },
	};
})();
