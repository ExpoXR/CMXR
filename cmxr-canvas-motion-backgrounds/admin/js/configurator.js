/* CMXR — Configurator UI + Live Preview */
(function () {
	'use strict';

	/* ------------------------------------------------------------------ */
	/* Bootstrap                                                            */
	/* ------------------------------------------------------------------ */

	var container = document.getElementById('cmxr-configurator');
	if (!container) return;

	var Core = window.CMXRCore;

	var raw        = JSON.parse(container.getAttribute('data-config') || '{}');
	var postId     = raw.postId || 0;
	var isNew      = raw.isNew || false;
	var config     = raw.config || {};
	var restUrl    = raw.restUrl || '';
	var nonce      = raw.nonce || '';
	var breakpoints = raw.breakpoints || [];
	var settings   = raw.settings || {};
	var adminApi   = window.CMXRAdmin || {};
	var strings    = adminApi.strings || {};
	var DEBUG      = !!(settings.debug_mode || adminApi.debugMode || adminApi.wpDebug || adminApi.scriptDebug);
	var Logger     = window.CMXRDebug || {
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

	// Ensure orbs array exists
	config.orbs = config.orbs || [];

	var selectedOrbIdx = -1;
	var previewEngine  = null;

	/* ------------------------------------------------------------------ */
	/* API helpers                                                          */
	/* ------------------------------------------------------------------ */

	function apiFetch(path, method, body) {
		return fetch(restUrl + path, {
			method: method || 'GET',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			body: body ? JSON.stringify(body) : undefined,
		}).then(function (r) {
			return r.json().catch(function () { return {}; }).then(function (data) {
				if (!r.ok) throw data;
				return data;
			});
		});
	}

	function showStatus(msg, isError) {
		var el = document.querySelector('.cmxr-save-status');
		if (el) { el.textContent = msg; el.style.color = isError ? '#ef4444' : '#22c55e'; }
	}

	function debugLog(label, data) {
		Logger.log('[CMXR Configurator] ' + label, data || {});
	}

	/* ------------------------------------------------------------------ */
	/* Save                                                                 */
	/* ------------------------------------------------------------------ */

	var saveBtn = document.getElementById('cmxr-save-btn');
	var saveBtnLabel = saveBtn ? saveBtn.textContent.trim() : cmxrText('save', 'Save');
	var saveBtnTimer = 0;

	function setSaveButtonState(state) {
		if (!saveBtn) return;
		if (saveBtnTimer) { clearTimeout(saveBtnTimer); saveBtnTimer = 0; }
		saveBtn.classList.remove('is-saving', 'is-saved', 'is-error');

		if (state === 'saving') {
			saveBtn.disabled = true;
			saveBtn.classList.add('is-saving');
			saveBtn.textContent = cmxrText('saving', 'Saving...');
			return;
		}

		if (state === 'saved') {
			saveBtn.disabled = false;
			saveBtn.classList.add('is-saved');
			saveBtn.textContent = cmxrText('saved', 'Saved');
			saveBtnTimer = setTimeout(function () {
				saveBtn.classList.remove('is-saved');
				saveBtn.textContent = saveBtnLabel;
			}, 1400);
			return;
		}

		if (state === 'error') {
			saveBtn.disabled = false;
			saveBtn.classList.add('is-error');
			saveBtn.textContent = cmxrText('error', 'Error');
			saveBtnTimer = setTimeout(function () {
				saveBtn.classList.remove('is-error');
				saveBtn.textContent = saveBtnLabel;
			}, 1800);
			return;
		}

		saveBtn.disabled = false;
		saveBtn.textContent = saveBtnLabel;
	}

	function handleSaveSuccess(data) {
		if (!data || !data.id) throw data || {};
		showStatus(cmxrText('savedStatus', 'Saved!'));
		setSaveButtonState('saved');
		debugLog('save success', { postId: data.id, isNew: isNew, animationId: config.animation_id });
	}

	function handleSaveError(error) {
		showStatus(cmxrText('errorSaving', 'Error saving.'), true);
		setSaveButtonState('error');
		debugLog('save error', error || {});
	}

	if (saveBtn) saveBtn.addEventListener('click', function () {
		if (saveBtn.classList.contains('is-saving')) return;
		var title = (document.getElementById('cmxr-title') || {}).value || '';
		var payload = { title: title, config: config };
		setSaveButtonState('saving');
		debugLog('save start', { postId: postId, isNew: isNew, animationId: config.animation_id });

		if (isNew || !postId) {
			apiFetch('/animations', 'POST', { title: title || cmxrText('newAnimation', 'New Animation') }).then(function (data) {
				if (data.id) {
					postId = data.id;
					isNew = false;
					if (saveBtn) saveBtn.setAttribute('data-post-id', postId);
					// Now update with full config
					return apiFetch('/animations/' + postId, 'PUT', payload);
				}
				throw data || {};
			}).then(function (data) {
				handleSaveSuccess(data);
				// Redirect to edit URL
				history.replaceState(null, '', '?page=cmxr-edit&id=' + postId);
			}).catch(handleSaveError);
		} else {
			apiFetch('/animations/' + postId, 'PUT', payload).then(function (data) {
				handleSaveSuccess(data);
			}).catch(handleSaveError);
		}
	});

	/* ------------------------------------------------------------------ */
	/* Global bar bindings                                                  */
	/* ------------------------------------------------------------------ */

	function bindSliderPair(sliderId, numId, configPath) {
		var slider = document.getElementById(sliderId);
		var num    = document.getElementById(numId);
		if (!slider || !num) return;

		function update(v) {
			slider.value = v;
			num.value = v;
			setPath(config, configPath, parseFloat(v));
			refreshPreview();
		}

		slider.addEventListener('input', function () { update(slider.value); });
		num.addEventListener('change', function () { update(num.value); });
	}

	function setPath(obj, path, val) {
		var keys = path.split('.');
		for (var i = 0; i < keys.length - 1; i++) {
			if (!obj[keys[i]]) obj[keys[i]] = {};
			obj = obj[keys[i]];
		}
		obj[keys[keys.length - 1]] = val;
	}

	function getPath(obj, path) {
		return path.split('.').reduce(function (o, k) { return o && o[k]; }, obj);
	}

	bindSliderPair('cmxr-speed', 'cmxr-speed-num', 'global.speed');
	bindSliderPair('cmxr-safe-margin', 'cmxr-safe-margin-num', 'global.safe_margin');

	var blendSelect = document.getElementById('cmxr-blend-mode');
	if (blendSelect) {
		blendSelect.addEventListener('change', function () {
			config.global = config.global || {};
			config.global.blend_mode = blendSelect.value;
			refreshPreview();
		});
	}

	/* ---- Animation ID (auto-generated from name, still editable) ---- */
	var ID_PREFIX = 'cmxr_';
	var titleInput = document.getElementById('cmxr-title');
	var animIdInput = document.getElementById('cmxr-anim-id');
	// Treat a pre-existing (saved) ID as user-owned so we never clobber it.
	var idTouched = !!(config.animation_id);

	function slugify(str) {
		return String(str)
			.toLowerCase()
			.replace(/[^a-z0-9\-_]+/g, '-')   // non-alphanumerics -> hyphen
			.replace(/-+/g, '-')              // collapse repeats
			.replace(/^[-_]+|[-_]+$/g, '');   // trim leading/trailing separators
	}

	function applyId(value) {
		config.animation_id = value;
		if (animIdInput) animIdInput.value = value;
	}

	if (titleInput) {
		titleInput.addEventListener('input', function () {
			if (idTouched) return;
			var slug = slugify(titleInput.value);
			applyId(slug ? ID_PREFIX + slug : '');
		});
	}

	if (animIdInput) {
		animIdInput.addEventListener('change', function () {
			idTouched = true;
			var clean = slugify(animIdInput.value.replace(/^cmxr_/i, ''));
			applyId(clean ? ID_PREFIX + clean : '');
		});
	}

	// Interactivity
	var interEnabled = document.getElementById('cmxr-interactivity-enabled');
	var interFields  = document.getElementById('cmxr-interactivity-fields');
	if (interEnabled && interFields) {
		// Sync enabled state from PHP-rendered checkbox into in-memory config on load
		config.global = config.global || {};
		config.global.interactivity = config.global.interactivity || {};
		config.global.interactivity.enabled = interEnabled.checked;

		interEnabled.addEventListener('change', function () {
			config.global = config.global || {};
			config.global.interactivity = config.global.interactivity || {};
			config.global.interactivity.enabled = interEnabled.checked;
			interFields.style.display = interEnabled.checked ? 'flex' : 'none';
			refreshPreview();
		});
		interFields.style.display = interEnabled.checked ? 'flex' : 'none';
	}

	var interMode = document.getElementById('cmxr-interact-mode');
	if (interMode) {
		// Sync mode from rendered select on load (handles old configs without saved mode)
		config.global = config.global || {};
		config.global.interactivity = config.global.interactivity || {};
		if (!config.global.interactivity.mode) {
			config.global.interactivity.mode = interMode.value;
		}

		interMode.addEventListener('change', function () {
			config.global.interactivity = config.global.interactivity || {};
			config.global.interactivity.mode = interMode.value;
			refreshPreview();
		});
	}

	bindSliderPair('cmxr-interact-strength', 'cmxr-interact-strength-num', 'global.interactivity.strength');
	bindSliderPair('cmxr-interact-radius', 'cmxr-interact-radius-num', 'global.interactivity.radius');

	/* ------------------------------------------------------------------ */
	/* Preview background                                                   */
	/* ------------------------------------------------------------------ */

	var previewBgHex  = document.getElementById('cmxr-preview-bg-hex');
	var previewBgText = document.getElementById('cmxr-preview-bg-text');
	var previewBgBtn  = document.getElementById('cmxr-preview-bg-transparent');
	var previewFrameEl = document.getElementById('cmxr-preview-container');

	function applyPreviewBg(value) {
		// Background lives on the framed device preview, not the surrounding stage.
		if (previewFrameEl) previewFrameEl.style.background = value;
		config.global = config.global || {};
		config.global.preview_bg = value;
	}

	if (previewBgHex) {
		previewBgHex.addEventListener('input', function () {
			if (previewBgText) previewBgText.value = previewBgHex.value;
			applyPreviewBg(previewBgHex.value);
		});
	}
	if (previewBgText) {
		previewBgText.addEventListener('input', function () {
			var v = previewBgText.value.trim() || 'transparent';
			applyPreviewBg(v);
			if (/^#[0-9a-f]{6}$/i.test(v) && previewBgHex) previewBgHex.value = v;
		});
	}
	if (previewBgBtn) {
		previewBgBtn.addEventListener('click', function () {
			if (previewBgText) previewBgText.value = 'transparent';
			applyPreviewBg('transparent');
		});
	}

	// Init from saved config
	var initPreviewBg = (config.global && config.global.preview_bg) || 'transparent';
	applyPreviewBg(initPreviewBg);

	/* ------------------------------------------------------------------ */
	/* Preview size                                                         */
	/* ------------------------------------------------------------------ */

	var previewSizeW       = document.getElementById('cmxr-preview-w');
	var previewSizeH       = document.getElementById('cmxr-preview-h');
	var previewSizeFillBtn = document.getElementById('cmxr-preview-size-fill');
	var previewContainerEl = document.getElementById('cmxr-preview-container');

	var previewDimsEl = document.getElementById('cmxr-preview-dims');

	function applyPreviewSize(w, h) {
		config.global = config.global || {};
		if (w > 0 && h > 0) {
			previewContainerEl.style.width  = w + 'px';
			previewContainerEl.style.height = h + 'px';
			previewContainerEl.classList.add('is-sized');
			config.global.preview_w = w;
			config.global.preview_h = h;
			if (previewDimsEl) previewDimsEl.textContent = w + ' × ' + h;
		} else {
			previewContainerEl.style.width  = '';
			previewContainerEl.style.height = '';
			previewContainerEl.classList.remove('is-sized');
			config.global.preview_w = 0;
			config.global.preview_h = 0;
			if (previewDimsEl) previewDimsEl.textContent = 'Fill';
		}
		resizePreview();
	}

	function renderBreakpointPicker() {
		var picker = document.querySelector('.cmxr-breakpoint-picker');
		if (!picker) return;
		var customSizeRow = document.querySelector('.cmxr-custom-size');
		var savedW = (config.global && config.global.preview_w) || 0;
		var savedH = (config.global && config.global.preview_h) || 0;
		var activeSet = false;

		breakpoints.forEach(function (bp) {
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'cmxr-bp-btn button button-small';
			btn.textContent = bp.label;
			btn.addEventListener('click', function () {
				document.querySelectorAll('.cmxr-bp-btn').forEach(function (b) { b.classList.remove('is-active'); });
				btn.classList.add('is-active');
				if (customSizeRow) customSizeRow.classList.add('is-hidden');
				applyPreviewSize(bp.w, bp.h);
			});
			if (savedW === bp.w && savedH === bp.h) {
				btn.classList.add('is-active');
				activeSet = true;
			}
			picker.appendChild(btn);
		});

		var customBtn = document.createElement('button');
		customBtn.type = 'button';
		customBtn.className = 'cmxr-bp-btn button button-small';
		customBtn.textContent = cmxrText('custom', 'Custom');
		customBtn.addEventListener('click', function () {
			document.querySelectorAll('.cmxr-bp-btn').forEach(function (b) { b.classList.remove('is-active'); });
			customBtn.classList.add('is-active');
			if (customSizeRow) customSizeRow.classList.remove('is-hidden');
		});
		picker.appendChild(customBtn);

		if (!activeSet && savedW > 0 && savedH > 0) {
			customBtn.classList.add('is-active');
			if (customSizeRow) customSizeRow.classList.remove('is-hidden');
			if (previewSizeW) previewSizeW.value = savedW;
			if (previewSizeH) previewSizeH.value = savedH;
			applyPreviewSize(savedW, savedH);
		} else if (activeSet) {
			applyPreviewSize(savedW, savedH);
		}
	}

	if (previewSizeW) {
		previewSizeW.addEventListener('change', function () {
			applyPreviewSize(parseInt(previewSizeW.value) || 0, parseInt(previewSizeH.value) || 0);
		});
	}
	if (previewSizeH) {
		previewSizeH.addEventListener('change', function () {
			applyPreviewSize(parseInt(previewSizeW.value) || 0, parseInt(previewSizeH.value) || 0);
		});
	}
	if (previewSizeFillBtn) {
		previewSizeFillBtn.addEventListener('click', function () {
			document.querySelectorAll('.cmxr-bp-btn').forEach(function (b) { b.classList.remove('is-active'); });
			if (previewSizeW) previewSizeW.value = '';
			if (previewSizeH) previewSizeH.value = '';
			applyPreviewSize(0, 0);
		});
	}

	/* ------------------------------------------------------------------ */
	/* Orb list                                                             */
	/* ------------------------------------------------------------------ */

	function newOrb() {
		return {
			id: 'o' + Date.now(),
			shape: 'circle',
			color: '#38a3d7',
			color_mode: 'solid',
			color_b: '#8bb84a',
			color_stops: ['#38a3d7', '#8bb84a'],
			color_animation: 'none',
			size: { w: 40, h: 40, unit: 'percent' },
			position: { x: 50, y: 50, unit: 'percent' },
			blur: 72,
			opacity: 0.8,
			animation: { type: 'drift', amplitude_x: 5, amplitude_y: 5, frequency_x: 0.4, frequency_y: 0.5, phase: 0 },
			parallax: 0.5,
			interaction_direction: 'normal',
			rotation: 0,
		};
	}

	var orbListEl    = document.getElementById('cmxr-orb-list');
	var sortableInit = false;
	var addOrbBtn    = document.getElementById('cmxr-add-orb-btn');
	var addFirstBtn  = document.getElementById('cmxr-add-first-shape-btn');

	function addShape() {
		config.orbs.push(newOrb());
		renderOrbList();
		selectOrb(config.orbs.length - 1);
		refreshPreview();
	}

	if (addOrbBtn) addOrbBtn.addEventListener('click', addShape);
	if (addFirstBtn) addFirstBtn.addEventListener('click', addShape);

	// Single delegated click handler (set up once) — avoids per-row listeners
	if (orbListEl) {
		orbListEl.addEventListener('click', function (e) {
			var li = e.target.closest('.cmxr-orb-item');
			if (!li) return;
			var idx = Array.prototype.indexOf.call(orbListEl.children, li);
			if (idx < 0) return;

			if (e.target.classList.contains('cmxr-orb-remove')) {
				config.orbs.splice(idx, 1);
				if (selectedOrbIdx >= config.orbs.length) selectedOrbIdx = config.orbs.length - 1;
				renderOrbList();
				if (selectedOrbIdx >= 0) {
					selectOrb(selectedOrbIdx);
				} else {
					var fieldsEl = document.querySelector('.cmxr-orb-fields');
					var noSelEl  = document.querySelector('.cmxr-no-selection');
					if (fieldsEl) fieldsEl.classList.add('is-hidden');
					if (noSelEl)  noSelEl.classList.remove('is-hidden');
				}
				refreshPreview();
				return;
			}
			if (!e.target.classList.contains('cmxr-drag-handle')) {
				selectOrb(idx);
			}
		});
	}

	// Rebuild the list DOM (structural changes only: add / remove / reorder / relabel)
	function escapeHtml(value) {
		return String(value || '').replace(/[&<>"']/g, function (ch) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[ch];
		});
	}

	function getShapeLabel(shape) {
		var radios = document.querySelectorAll('[name="cmxr-orb-shape"]');
		for (var i = 0; i < radios.length; i++) {
			if (radios[i].value === shape) {
				var label = radios[i].closest('.cmxr-shape-option');
				return label ? label.textContent.trim() : shape;
			}
		}
		return shape || 'circle';
	}

	function renderOrbList() {
		if (!orbListEl) return;
		var emptyEl = document.getElementById('cmxr-orb-empty');
		if (emptyEl) emptyEl.classList.toggle('is-hidden', !!config.orbs.length);
		var placeholderEl = document.querySelector('.cmxr-preview-placeholder');
		if (placeholderEl) placeholderEl.classList.toggle('is-hidden', !!config.orbs.length);
		var html = '';
		config.orbs.forEach(function (orb, idx) {
			var shapeLabel = escapeHtml(getShapeLabel(orb.shape));
			html +=
				'<li class="cmxr-orb-item' + (idx === selectedOrbIdx ? ' is-selected' : '') + '" data-orb-id="' + escapeHtml(orb.id) + '">' +
					'<span class="cmxr-drag-handle" title="Drag to reorder" aria-label="Drag to reorder">&#8942;&#8942;</span>' +
					'<span class="cmxr-orb-swatch" style="background:' + orb.color + ';color:' + orb.color + '"></span>' +
					'<span class="cmxr-orb-label">Layer ' + (idx + 1) + ' <small>(' + shapeLabel + ')</small></span>' +
					'<span class="cmxr-layer-badge" title="Layer (1 = renders on top)">' + (idx + 1) + '</span>' +
					'<button class="cmxr-orb-remove" title="Remove" aria-label="Remove orb">&times;</button>' +
				'</li>';
		});
		orbListEl.innerHTML = html;
		ensureSortable();
	}

	// Update only the selected-row highlight without rebuilding the DOM
	function updateOrbSelection() {
		if (!orbListEl) return;
		var items = orbListEl.children;
		for (var i = 0; i < items.length; i++) {
			items[i].classList.toggle('is-selected', i === selectedOrbIdx);
		}
	}

	// Initialise jQuery UI Sortable once, then just refresh on subsequent rebuilds
	function ensureSortable() {
		if (typeof jQuery === 'undefined' || !jQuery.fn.sortable) return;
		var $list = jQuery(orbListEl);
		if (sortableInit) { $list.sortable('refresh'); return; }
		sortableInit = true;
		$list.sortable({
			handle: '.cmxr-drag-handle',
			axis: 'y',
			tolerance: 'pointer',
			update: function () {
				var selectedId = selectedOrbIdx >= 0 && config.orbs[selectedOrbIdx]
					? config.orbs[selectedOrbIdx].id
					: null;

				var newOrder = [];
				$list.find('.cmxr-orb-item').each(function () {
					var orbId = jQuery(this).attr('data-orb-id');
					var orb   = config.orbs.filter(function (o) { return o.id === orbId; })[0];
					if (orb) newOrder.push(orb);
				});
				config.orbs = newOrder;

				if (selectedId) {
					selectedOrbIdx = -1;
					for (var si = 0; si < config.orbs.length; si++) {
						if (config.orbs[si].id === selectedId) { selectedOrbIdx = si; break; }
					}
				}

				renderOrbList();
				refreshPreview();
			},
		});
	}

	/* ------------------------------------------------------------------ */
	/* Orb config panel                                                     */
	/* ------------------------------------------------------------------ */

	function selectOrb(idx) {
		selectedOrbIdx = idx;
		updateOrbSelection();

		var fieldsEl   = document.querySelector('.cmxr-orb-fields');
		var noSelEl    = document.querySelector('.cmxr-no-selection');
		var orb        = config.orbs[idx];

		if (!orb) {
			if (fieldsEl) fieldsEl.classList.add('is-hidden');
			if (noSelEl) noSelEl.classList.remove('is-hidden');
			return;
		}

		if (fieldsEl) fieldsEl.classList.remove('is-hidden');
		if (noSelEl) noSelEl.classList.add('is-hidden');

		// Shape
		document.querySelectorAll('[name="cmxr-orb-shape"]').forEach(function (r) {
			r.checked = r.value === orb.shape;
			r.closest('.cmxr-shape-option').classList.toggle('selected', r.checked);
		});

		setValue('cmxr-orb-blur', 'cmxr-orb-blur-num', orb.blur);
		setValue('cmxr-orb-opacity', 'cmxr-orb-opacity-num', orb.opacity);

		// Color
		var cmEl = document.getElementById('cmxr-orb-color-mode');
		if (cmEl) cmEl.value = orb.color_mode || 'solid';
		setColorPicker('cmxr-orb-color', orb.color);
		setColorPicker('cmxr-orb-color-b', orb.color_b || '#8bb84a');
		renderGradientColors(orb);
		updateColorFieldVisibility(orb);

		// Size & position
		setValue('cmxr-orb-w', 'cmxr-orb-w-num', orb.size.w);
		setValue('cmxr-orb-h', 'cmxr-orb-h-num', orb.size.h);
		var sizeUnitEl = document.getElementById('cmxr-orb-size-unit');
		if (sizeUnitEl) sizeUnitEl.value = orb.size.unit;
		updateUnitLabels('.cmxr-unit-label', orb.size.unit);
		sizeUnitRanges(orb.size.unit);

		setValue('cmxr-orb-x', 'cmxr-orb-x-num', orb.position.x);
		setValue('cmxr-orb-y', 'cmxr-orb-y-num', orb.position.y);
		var posUnitEl = document.getElementById('cmxr-orb-pos-unit');
		if (posUnitEl) posUnitEl.value = orb.position.unit;
		updateUnitLabels('.cmxr-pos-unit-label', orb.position.unit);
		posUnitRanges(orb.position.unit);

		// Animation
		var animTypeEl = document.getElementById('cmxr-orb-anim-type');
		if (animTypeEl) animTypeEl.value = orb.animation.type;
		setValue('cmxr-orb-amp-x', 'cmxr-orb-amp-x-num', orb.animation.amplitude_x);
		setValue('cmxr-orb-amp-y', 'cmxr-orb-amp-y-num', orb.animation.amplitude_y);
		setValue('cmxr-orb-freq-x', 'cmxr-orb-freq-x-num', orb.animation.frequency_x);
		setValue('cmxr-orb-freq-y', 'cmxr-orb-freq-y-num', orb.animation.frequency_y);
		setValue('cmxr-orb-phase', 'cmxr-orb-phase-num', orb.animation.phase);

		// Interaction
		var interactionDirectionEl = document.getElementById('cmxr-orb-interaction-direction');
		if (interactionDirectionEl) interactionDirectionEl.value = orb.interaction_direction || 'normal';
		setValue('cmxr-orb-parallax', 'cmxr-orb-parallax-num', orb.parallax);

		// Rotation
		setValue('cmxr-orb-rotation', 'cmxr-orb-rotation-num', orb.rotation || 0);
	}

	function setValue(sliderId, numId, val) {
		var s = document.getElementById(sliderId);
		var n = document.getElementById(numId);
		if (s) s.value = val;
		if (n) n.value = val;
	}

	function setColorPicker(inputId, val) {
		var el = document.getElementById(inputId);
		if (!el) return;
		el.value = val;
		if (el._wpColorPicker && jQuery) {
			jQuery(el).wpColorPicker('color', val);
		}
	}

	function getColorStops(orb) {
		var stops = Array.isArray(orb.color_stops) ? orb.color_stops.slice(0, 5) : [];
		if (!stops.length) stops = [orb.color || '#38a3d7', orb.color_b || '#8bb84a'];
		if (stops.length === 1) stops.push(orb.color_b || '#8bb84a');
		stops[0] = orb.color || stops[0] || '#38a3d7';
		stops[1] = orb.color_b || stops[1] || '#8bb84a';
		return stops.slice(0, 5);
	}

	function syncOrbColorsFromStops(orb) {
		orb.color_stops = getColorStops(orb);
		orb.color = orb.color_stops[0] || '#38a3d7';
		orb.color_b = orb.color_stops[1] || '#8bb84a';
		setColorPicker('cmxr-orb-color', orb.color);
		setColorPicker('cmxr-orb-color-b', orb.color_b);
	}

	function updateColorFieldVisibility(orb) {
		var mode = (orb && orb.color_mode) || 'solid';
		var colorBField = document.querySelector('.cmxr-color-b-field');
		var gradientField = document.querySelector('.cmxr-gradient-colors-field');
		var animationField = document.querySelector('.cmxr-color-animation-field');
		var animationEl = document.getElementById('cmxr-orb-color-animation');
		if (colorBField) colorBField.style.display = mode !== 'solid' ? 'block' : 'none';
		if (gradientField) gradientField.style.display = mode === 'gradient' ? 'block' : 'none';
		if (animationField) animationField.style.display = mode !== 'solid' ? 'block' : 'none';
		if (animationEl) animationEl.value = (orb && orb.color_animation) || 'none';
	}

	function renderGradientColors(orb) {
		var wrap = document.getElementById('cmxr-gradient-colors');
		var addBtn = document.getElementById('cmxr-add-gradient-color');
		if (!wrap || !orb) return;
		var stops = getColorStops(orb);
		orb.color_stops = stops;
		wrap.innerHTML = '';

		stops.forEach(function (color, idx) {
			var row = document.createElement('div');
			row.className = 'cmxr-gradient-color-row';

			var input = document.createElement('input');
			input.type = 'color';
			input.value = color;
			input.setAttribute('aria-label', 'Gradient color ' + (idx + 1));
			input.addEventListener('input', function () {
				orb.color_stops[idx] = input.value;
				if (idx === 0) orb.color = input.value;
				if (idx === 1) orb.color_b = input.value;
				if (idx < 2) syncOrbColorsFromStops(orb);
				refreshPreview();
				renderOrbList();
			});

			var label = document.createElement('span');
			label.textContent = idx === 0 ? 'Primary' : idx === 1 ? 'Secondary' : 'Color ' + (idx + 1);

			var remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'button button-small cmxr-gradient-remove';
			remove.textContent = 'Remove';
			remove.disabled = idx < 2;
			remove.addEventListener('click', function () {
				if (idx < 2) return;
				orb.color_stops.splice(idx, 1);
				syncOrbColorsFromStops(orb);
				renderGradientColors(orb);
				refreshPreview();
			});

			row.appendChild(input);
			row.appendChild(label);
			row.appendChild(remove);
			wrap.appendChild(row);
		});

		if (addBtn) addBtn.disabled = stops.length >= 5;
	}

	function updateUnitLabels(selector, unit) {
		document.querySelectorAll(selector).forEach(function (el) {
			el.textContent = unit === 'percent' ? '%' : unit;
		});
	}

	function updateSliderRange(sliderId, numId, min, max) {
		var s = document.getElementById(sliderId);
		var n = document.getElementById(numId);
		if (s) { s.min = min; s.max = max; }
		if (n) { n.min = min; n.max = max; }
	}

	function sizeUnitRanges(unit) {
		var max = (unit === 'px') ? 2000 : 200;
		updateSliderRange('cmxr-orb-w', 'cmxr-orb-w-num', 1, max);
		updateSliderRange('cmxr-orb-h', 'cmxr-orb-h-num', 1, max);
	}

	function posUnitRanges(unit) {
		var max = (unit === 'px') ? 3000 : 100;
		updateSliderRange('cmxr-orb-x', 'cmxr-orb-x-num', 0, max);
		updateSliderRange('cmxr-orb-y', 'cmxr-orb-y-num', 0, max);
	}

	function bindOrbField(sliderId, numId, orbPath, isInt) {
		var slider = document.getElementById(sliderId);
		var num    = document.getElementById(numId);
		if (!slider || !num) return;

		function update(v) {
			slider.value = v;
			num.value = v;
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			setPath(orb, orbPath, isInt ? parseInt(v) : parseFloat(v));
			refreshPreview();
		}

		slider.addEventListener('input', function () { update(slider.value); });
		num.addEventListener('change', function () { update(num.value); });
	}

	// Bind all orb fields
	bindOrbField('cmxr-orb-blur', 'cmxr-orb-blur-num', 'blur', true);
	bindOrbField('cmxr-orb-opacity', 'cmxr-orb-opacity-num', 'opacity', false);
	bindOrbField('cmxr-orb-w', 'cmxr-orb-w-num', 'size.w', false);
	bindOrbField('cmxr-orb-h', 'cmxr-orb-h-num', 'size.h', false);
	bindOrbField('cmxr-orb-x', 'cmxr-orb-x-num', 'position.x', false);
	bindOrbField('cmxr-orb-y', 'cmxr-orb-y-num', 'position.y', false);
	bindOrbField('cmxr-orb-amp-x', 'cmxr-orb-amp-x-num', 'animation.amplitude_x', false);
	bindOrbField('cmxr-orb-amp-y', 'cmxr-orb-amp-y-num', 'animation.amplitude_y', false);
	bindOrbField('cmxr-orb-freq-x', 'cmxr-orb-freq-x-num', 'animation.frequency_x', false);
	bindOrbField('cmxr-orb-freq-y', 'cmxr-orb-freq-y-num', 'animation.frequency_y', false);
	bindOrbField('cmxr-orb-phase', 'cmxr-orb-phase-num', 'animation.phase', false);
	bindOrbField('cmxr-orb-parallax', 'cmxr-orb-parallax-num', 'parallax', false);
	bindOrbField('cmxr-orb-rotation', 'cmxr-orb-rotation-num', 'rotation', false);

	var interactionDirectionEl = document.getElementById('cmxr-orb-interaction-direction');
	if (interactionDirectionEl) {
		interactionDirectionEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.interaction_direction = interactionDirectionEl.value === 'reverse' ? 'reverse' : 'normal';
			refreshPreview();
		});
	}

	// Shape radios
	document.querySelectorAll('[name="cmxr-orb-shape"]').forEach(function (radio) {
		radio.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.shape = radio.value;
			renderOrbList();
			refreshPreview();
		});
	});

	// Color pickers (WP Color Picker)
	if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
		jQuery('.cmxr-color-picker').wpColorPicker({
			change: function (e, ui) {
				var color = ui.color.toString();
				var id    = e.target.id;
				var orb   = config.orbs[selectedOrbIdx];
				if (!orb) return;
				orb.color_stops = getColorStops(orb);
				if (id === 'cmxr-orb-color') {
					orb.color = color;
					orb.color_stops[0] = color;
				}
				if (id === 'cmxr-orb-color-b') {
					orb.color_b = color;
					orb.color_stops[1] = color;
				}
				renderGradientColors(orb);
				renderOrbList();
				refreshPreview();
			},
		});
	}

	// Color mode
	var colorModeEl = document.getElementById('cmxr-orb-color-mode');
	if (colorModeEl) {
		colorModeEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.color_mode = colorModeEl.value;
			orb.color_stops = getColorStops(orb);
			updateColorFieldVisibility(orb);
			renderGradientColors(orb);
			refreshPreview();
		});
	}

	var addGradientColorBtn = document.getElementById('cmxr-add-gradient-color');
	if (addGradientColorBtn) {
		addGradientColorBtn.addEventListener('click', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.color_stops = getColorStops(orb);
			if (orb.color_stops.length >= 5) return;
			orb.color_stops.push(orb.color_stops[orb.color_stops.length - 1] || '#8bb84a');
			renderGradientColors(orb);
			refreshPreview();
		});
	}

	var colorAnimationEl = document.getElementById('cmxr-orb-color-animation');
	if (colorAnimationEl) {
		colorAnimationEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.color_animation = colorAnimationEl.value || 'none';
			refreshPreview();
		});
	}

	// Anim type
	var animTypeEl = document.getElementById('cmxr-orb-anim-type');
	if (animTypeEl) {
		animTypeEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.animation.type = animTypeEl.value;
			refreshPreview();
		});
	}

	// Size unit
	var sizeUnitEl = document.getElementById('cmxr-orb-size-unit');
	if (sizeUnitEl) {
		sizeUnitEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.size.unit = sizeUnitEl.value;
			updateUnitLabels('.cmxr-unit-label', sizeUnitEl.value);
			sizeUnitRanges(sizeUnitEl.value);
			refreshPreview();
		});
	}

	// Position unit
	var posUnitEl = document.getElementById('cmxr-orb-pos-unit');
	if (posUnitEl) {
		posUnitEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.position.unit = posUnitEl.value;
			updateUnitLabels('.cmxr-pos-unit-label', posUnitEl.value);
			posUnitRanges(posUnitEl.value);
			refreshPreview();
		});
	}

	/* ------------------------------------------------------------------ */
	/* Tabs                                                                 */
	/* ------------------------------------------------------------------ */

	document.querySelectorAll('.cmxr-tab').forEach(function (tab) {
		tab.addEventListener('click', function () {
			var paneName = tab.getAttribute('data-tab');
			document.querySelectorAll('.cmxr-tab').forEach(function (t) {
				t.classList.remove('is-active');
				t.setAttribute('aria-selected', 'false');
			});
			document.querySelectorAll('.cmxr-tab-pane').forEach(function (p) { p.classList.remove('is-active'); });
			tab.classList.add('is-active');
			tab.setAttribute('aria-selected', 'true');
			var pane = document.querySelector('[data-pane="' + paneName + '"]');
			if (pane) pane.classList.add('is-active');
		});
	});

	// Global bar tabs (Background / Motion / Interaction)
	document.querySelectorAll('.cmxr-global-tab').forEach(function (tab) {
		tab.addEventListener('click', function () {
			var name = tab.getAttribute('data-gtab');
			document.querySelectorAll('.cmxr-global-tab').forEach(function (t) {
				t.classList.remove('is-active');
				t.setAttribute('aria-selected', 'false');
			});
			document.querySelectorAll('.cmxr-global-pane').forEach(function (p) { p.classList.remove('is-active'); });
			tab.classList.add('is-active');
			tab.setAttribute('aria-selected', 'true');
			var pane = document.querySelector('[data-gpane="' + name + '"]');
			if (pane) pane.classList.add('is-active');
		});
	});

	/* ------------------------------------------------------------------ */
	/* Preview engine (mirrors public engine logic, simplified)            */
	/* ------------------------------------------------------------------ */

	var previewCanvas  = document.getElementById('cmxr-preview-canvas');
	var previewCtx     = previewCanvas ? previewCanvas.getContext('2d', { alpha: true }) : null;
	var previewRaf     = 0;
	var previewState   = { w: 0, h: 0, dpr: 1, time: 0, lastTime: 0, running: false };

	var previewPointerSurface = previewFrameEl || previewCanvas;
	function getPreviewDebugState() {
		var inter = (config.global && config.global.interactivity) || {};
		return {
			animationId: config.animation_id || '',
			orbs: (config.orbs || []).length,
			canvas: { width: previewState.w, height: previewState.h, dpr: previewState.dpr },
			interactivity: {
				enabled: inter.enabled !== false,
				mode: inter.mode || 'parallax',
				strength: inter.strength || 0.5,
				radius: inter.radius || 30,
			},
		};
	}

	var ptr = (Core && Core.createPointerTracker && previewPointerSurface)
		? Core.createPointerTracker(previewPointerSurface, refreshPreview, {
			debug: DEBUG,
			scope: 'configurator',
			label: 'live-preview',
			getState: getPreviewDebugState,
		})
		: { mx: 0, my: 0, tx: 0, ty: 0, hover: 0, targetHover: 0, update: function () {} };

	function resizePreview() {
		if (!previewCanvas) return;
		var parent = previewCanvas.parentElement;
		var w = parent.clientWidth;
		var h = parent.clientHeight;
		previewState.w = w;
		previewState.h = h;
		previewState.dpr = Math.min(window.devicePixelRatio || 1, 1.75);
		previewCanvas.width  = Math.round(w * previewState.dpr);
		previewCanvas.height = Math.round(h * previewState.dpr);
		previewCanvas.style.width  = w + 'px';
		previewCanvas.style.height = h + 'px';
		debugLog('preview resize', getPreviewDebugState());
	}

	function tickPreview(now) {
		if (!previewCtx || !Core) return;
		var state = previewState;
		var dt = Math.min(40, Math.max(0, now - (state.lastTime || now)));
		state.lastTime = now;

		ptr.update();

		var speed = (config.global && config.global.speed) || 1.0;
		state.time += dt * 0.001 * speed * (1 + ptr.hover * 0.35);

		var w = state.w, h = state.h, t = state.time;
		previewCtx.setTransform(state.dpr, 0, 0, state.dpr, 0, 0);
		previewCtx.clearRect(0, 0, w, h);

		var blendMode = config.global && config.global.blend_mode || 'screen';
		var safeMargin = (config.global && config.global.safe_margin) || 0;

		// Interactivity — mirrors cmxr-engine.js draw()
		var inter    = (config.global && config.global.interactivity) || {};
		var iEnabled = (inter.enabled !== false) && inter.mode !== 'none';
		var iMode    = iEnabled ? inter.mode : 'none';
		var iStr     = inter.strength || 0.5;
		var iRad     = inter.radius || 30;
		var mx       = ptr.mx;
		var my       = ptr.my;
		var hover    = ptr.hover;

		previewCtx.globalCompositeOperation = blendMode;

		var orbs = config.orbs || [];
		for (var i = orbs.length - 1; i >= 0; i--) {
			var orb   = orbs[i];
			var seed  = Core.hashSeed(orb.id);
			var scale = Core.computeOrbScale(orb, t);
			var pos   = Core.computeOrbPos(orb, seed, t, w, h, safeMargin, mx, my, hover, iMode, iStr, iRad);
			Core.drawOrb(previewCtx, orb, pos, scale, t, seed);
		}

		previewCtx.globalCompositeOperation = 'source-over';
		previewRaf = requestAnimationFrame(tickPreview);
	}

	function refreshPreview() {
		if (!previewRaf) {
			previewRaf = requestAnimationFrame(tickPreview);
		}
	}

	function startPreview() {
		resizePreview();
		refreshPreview();
	}

	// Resize observer for preview
	if (typeof ResizeObserver !== 'undefined' && previewCanvas) {
		new ResizeObserver(function () { resizePreview(); }).observe(previewCanvas.parentElement);
	}

	/* ------------------------------------------------------------------ */
	/* Boot                                                                 */
	/* ------------------------------------------------------------------ */

	startPreview();
	renderOrbList();
	renderBreakpointPicker();
	if (config.orbs.length > 0) selectOrb(0);

})();
