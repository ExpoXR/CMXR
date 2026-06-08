/* SphereXR — Configurator UI + Live Preview */
(function () {
	'use strict';

	/* ------------------------------------------------------------------ */
	/* Bootstrap                                                            */
	/* ------------------------------------------------------------------ */

	var container = document.getElementById('spherexr-configurator');
	if (!container) return;

	var raw        = JSON.parse(container.getAttribute('data-config') || '{}');
	var postId     = raw.postId || 0;
	var isNew      = raw.isNew || false;
	var config     = raw.config || {};
	var restUrl    = raw.restUrl || '';
	var nonce      = raw.nonce || '';
	var breakpoints = raw.breakpoints || [];

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
		}).then(function (r) { return r.json(); });
	}

	function showStatus(msg, isError) {
		var el = document.querySelector('.spherexr-save-status');
		if (el) { el.textContent = msg; el.style.color = isError ? '#ef4444' : '#22c55e'; }
	}

	/* ------------------------------------------------------------------ */
	/* Save                                                                 */
	/* ------------------------------------------------------------------ */

	document.getElementById('spherexr-save-btn').addEventListener('click', function () {
		var title = (document.getElementById('sxr-title') || {}).value || '';
		var payload = { title: title, config: config };

		if (isNew || !postId) {
			apiFetch('/animations', 'POST', { title: title || 'New Animation' }).then(function (data) {
				if (data.id) {
					postId = data.id;
					isNew = false;
					// Now update with full config
					return apiFetch('/animations/' + postId, 'PUT', payload);
				}
			}).then(function (data) {
				if (data && data.id) {
					showStatus('Saved!');
					// Redirect to edit URL
					history.replaceState(null, '', '?page=spherexr-edit&id=' + postId);
				}
			}).catch(function () { showStatus('Error saving.', true); });
		} else {
			apiFetch('/animations/' + postId, 'PUT', payload).then(function (data) {
				if (data.id) {
					showStatus('Saved!');
				} else {
					showStatus('Error saving.', true);
				}
			});
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

	bindSliderPair('sxr-speed', 'sxr-speed-num', 'global.speed');
	bindSliderPair('sxr-safe-margin', 'sxr-safe-margin-num', 'global.safe_margin');

	var blendSelect = document.getElementById('sxr-blend-mode');
	if (blendSelect) {
		blendSelect.addEventListener('change', function () {
			config.global = config.global || {};
			config.global.blend_mode = blendSelect.value;
			refreshPreview();
		});
	}

	var animIdInput = document.getElementById('sxr-anim-id');
	if (animIdInput) {
		animIdInput.addEventListener('change', function () {
			config.animation_id = animIdInput.value.replace(/[^a-z0-9\-_]/gi, '-').toLowerCase();
			animIdInput.value = config.animation_id;
		});
	}

	// Interactivity
	var interEnabled = document.getElementById('sxr-interactivity-enabled');
	var interFields  = document.getElementById('sxr-interactivity-fields');
	if (interEnabled && interFields) {
		interEnabled.addEventListener('change', function () {
			config.global = config.global || {};
			config.global.interactivity = config.global.interactivity || {};
			config.global.interactivity.enabled = interEnabled.checked;
			interFields.style.display = interEnabled.checked ? 'flex' : 'none';
			refreshPreview();
		});
		interFields.style.display = interEnabled.checked ? 'flex' : 'none';
	}

	var interMode = document.getElementById('sxr-interact-mode');
	if (interMode) {
		interMode.addEventListener('change', function () {
			config.global.interactivity = config.global.interactivity || {};
			config.global.interactivity.mode = interMode.value;
		});
	}

	bindSliderPair('sxr-interact-strength', 'sxr-interact-strength-num', 'global.interactivity.strength');
	bindSliderPair('sxr-interact-radius', 'sxr-interact-radius-num', 'global.interactivity.radius');

	/* ------------------------------------------------------------------ */
	/* Preview background                                                   */
	/* ------------------------------------------------------------------ */

	var previewBgHex  = document.getElementById('sxr-preview-bg-hex');
	var previewBgText = document.getElementById('sxr-preview-bg-text');
	var previewBgBtn  = document.getElementById('sxr-preview-bg-transparent');
	var previewPanel  = document.querySelector('.sxr-panel-center');

	function applyPreviewBg(value) {
		if (previewPanel) previewPanel.style.background = value;
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

	var previewSizeW       = document.getElementById('sxr-preview-w');
	var previewSizeH       = document.getElementById('sxr-preview-h');
	var previewSizeFillBtn = document.getElementById('sxr-preview-size-fill');
	var previewContainerEl = document.getElementById('sxr-preview-container');

	function applyPreviewSize(w, h) {
		config.global = config.global || {};
		if (w > 0 && h > 0) {
			previewContainerEl.style.width  = w + 'px';
			previewContainerEl.style.height = h + 'px';
			previewContainerEl.style.flex   = 'none';
			config.global.preview_w = w;
			config.global.preview_h = h;
		} else {
			previewContainerEl.style.width  = '';
			previewContainerEl.style.height = '';
			previewContainerEl.style.flex   = '';
			config.global.preview_w = 0;
			config.global.preview_h = 0;
		}
		resizePreview();
	}

	function renderBreakpointPicker() {
		var picker = document.querySelector('.sxr-breakpoint-picker');
		if (!picker) return;
		var customSizeRow = document.querySelector('.sxr-custom-size');
		var savedW = (config.global && config.global.preview_w) || 0;
		var savedH = (config.global && config.global.preview_h) || 0;
		var activeSet = false;

		breakpoints.forEach(function (bp) {
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'sxr-bp-btn button button-small';
			btn.textContent = bp.label;
			btn.addEventListener('click', function () {
				document.querySelectorAll('.sxr-bp-btn').forEach(function (b) { b.classList.remove('is-active'); });
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
		customBtn.className = 'sxr-bp-btn button button-small';
		customBtn.textContent = 'Custom';
		customBtn.addEventListener('click', function () {
			document.querySelectorAll('.sxr-bp-btn').forEach(function (b) { b.classList.remove('is-active'); });
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
			document.querySelectorAll('.sxr-bp-btn').forEach(function (b) { b.classList.remove('is-active'); });
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
			color: '#7c3aed',
			color_mode: 'solid',
			color_b: '',
			size: { w: 40, h: 40, unit: 'percent' },
			position: { x: 50, y: 50, unit: 'percent' },
			blur: 72,
			opacity: 0.8,
			animation: { type: 'drift', amplitude_x: 5, amplitude_y: 5, frequency_x: 0.4, frequency_y: 0.5, phase: 0 },
			parallax: 0.5,
		};
	}

	document.getElementById('sxr-add-orb-btn').addEventListener('click', function () {
		config.orbs.push(newOrb());
		renderOrbList();
		selectOrb(config.orbs.length - 1);
		refreshPreview();
	});

	function renderOrbList() {
		var list = document.getElementById('sxr-orb-list');
		// Destroy sortable before rebuilding DOM
		if (typeof jQuery !== 'undefined' && jQuery.fn.sortable) {
			try { jQuery(list).sortable('destroy'); } catch (e) {}
		}
		list.innerHTML = '';
		config.orbs.forEach(function (orb, idx) {
			var li = document.createElement('li');
			li.className = 'sxr-orb-item' + (idx === selectedOrbIdx ? ' is-selected' : '');
			li.setAttribute('data-orb-id', orb.id);
			li.innerHTML =
				'<span class="sxr-drag-handle" title="Drag to reorder">&#8942;&#8942;</span>' +
				'<span class="sxr-orb-swatch" style="background:' + orb.color + ';color:' + orb.color + '"></span>' +
				'<span class="sxr-orb-label">Orb ' + (idx + 1) + ' <small>(' + orb.shape + ')</small></span>' +
				'<span class="sxr-layer-badge" title="Layer (1 = renders on top)">' + (idx + 1) + '</span>' +
				'<button class="sxr-orb-remove" title="Remove">&times;</button>';

			li.addEventListener('click', function (e) {
				if (e.target.classList.contains('sxr-orb-remove')) {
					config.orbs.splice(idx, 1);
					if (selectedOrbIdx >= config.orbs.length) selectedOrbIdx = config.orbs.length - 1;
					renderOrbList();
					if (selectedOrbIdx >= 0) {
						selectOrb(selectedOrbIdx);
					} else {
						var fieldsEl = document.querySelector('.sxr-orb-fields');
						var noSelEl  = document.querySelector('.sxr-no-selection');
						if (fieldsEl) fieldsEl.classList.add('is-hidden');
						if (noSelEl)  noSelEl.classList.remove('is-hidden');
					}
					refreshPreview();
					return;
				}
				if (!e.target.classList.contains('sxr-drag-handle')) {
					selectOrb(idx);
				}
			});

			list.appendChild(li);
		});
		initSortable();
	}

	function initSortable() {
		if (typeof jQuery === 'undefined' || !jQuery.fn.sortable) return;
		var $list = jQuery('#sxr-orb-list');
		$list.sortable({
			handle: '.sxr-drag-handle',
			axis: 'y',
			tolerance: 'pointer',
			update: function () {
				var selectedId = selectedOrbIdx >= 0 && config.orbs[selectedOrbIdx]
					? config.orbs[selectedOrbIdx].id
					: null;

				var newOrder = [];
				$list.find('.sxr-orb-item').each(function () {
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
		renderOrbList();

		var fieldsEl   = document.querySelector('.sxr-orb-fields');
		var noSelEl    = document.querySelector('.sxr-no-selection');
		var orb        = config.orbs[idx];

		if (!orb) {
			if (fieldsEl) fieldsEl.classList.add('is-hidden');
			if (noSelEl) noSelEl.classList.remove('is-hidden');
			return;
		}

		if (fieldsEl) fieldsEl.classList.remove('is-hidden');
		if (noSelEl) noSelEl.classList.add('is-hidden');

		// Shape
		document.querySelectorAll('[name="sxr-orb-shape"]').forEach(function (r) {
			r.checked = r.value === orb.shape;
			r.closest('.sxr-shape-option').classList.toggle('selected', r.checked);
		});

		setValue('sxr-orb-blur', 'sxr-orb-blur-num', orb.blur);
		setValue('sxr-orb-opacity', 'sxr-orb-opacity-num', orb.opacity);

		// Color
		var cmEl = document.getElementById('sxr-orb-color-mode');
		if (cmEl) cmEl.value = orb.color_mode || 'solid';
		setColorPicker('sxr-orb-color', orb.color);
		setColorPicker('sxr-orb-color-b', orb.color_b || '#8bb84a');

		// Size & position
		setValue('sxr-orb-w', 'sxr-orb-w-num', orb.size.w);
		setValue('sxr-orb-h', 'sxr-orb-h-num', orb.size.h);
		var sizeUnitEl = document.getElementById('sxr-orb-size-unit');
		if (sizeUnitEl) sizeUnitEl.value = orb.size.unit;
		updateUnitLabels('.sxr-unit-label', orb.size.unit);
		sizeUnitRanges(orb.size.unit);

		setValue('sxr-orb-x', 'sxr-orb-x-num', orb.position.x);
		setValue('sxr-orb-y', 'sxr-orb-y-num', orb.position.y);
		var posUnitEl = document.getElementById('sxr-orb-pos-unit');
		if (posUnitEl) posUnitEl.value = orb.position.unit;
		updateUnitLabels('.sxr-pos-unit-label', orb.position.unit);
		posUnitRanges(orb.position.unit);

		// Secondary color field visibility
		var colorBFieldEl = document.querySelector('.sxr-color-b-field');
		if (colorBFieldEl) {
			colorBFieldEl.style.display = (orb.color_mode || 'solid') !== 'solid' ? 'block' : 'none';
		}

		// Animation
		var animTypeEl = document.getElementById('sxr-orb-anim-type');
		if (animTypeEl) animTypeEl.value = orb.animation.type;
		setValue('sxr-orb-amp-x', 'sxr-orb-amp-x-num', orb.animation.amplitude_x);
		setValue('sxr-orb-amp-y', 'sxr-orb-amp-y-num', orb.animation.amplitude_y);
		setValue('sxr-orb-freq-x', 'sxr-orb-freq-x-num', orb.animation.frequency_x);
		setValue('sxr-orb-freq-y', 'sxr-orb-freq-y-num', orb.animation.frequency_y);
		setValue('sxr-orb-phase', 'sxr-orb-phase-num', orb.animation.phase);

		// Interaction
		setValue('sxr-orb-parallax', 'sxr-orb-parallax-num', orb.parallax);
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
		updateSliderRange('sxr-orb-w', 'sxr-orb-w-num', 1, max);
		updateSliderRange('sxr-orb-h', 'sxr-orb-h-num', 1, max);
	}

	function posUnitRanges(unit) {
		var max = (unit === 'px') ? 3000 : 100;
		updateSliderRange('sxr-orb-x', 'sxr-orb-x-num', 0, max);
		updateSliderRange('sxr-orb-y', 'sxr-orb-y-num', 0, max);
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
	bindOrbField('sxr-orb-blur', 'sxr-orb-blur-num', 'blur', true);
	bindOrbField('sxr-orb-opacity', 'sxr-orb-opacity-num', 'opacity', false);
	bindOrbField('sxr-orb-w', 'sxr-orb-w-num', 'size.w', false);
	bindOrbField('sxr-orb-h', 'sxr-orb-h-num', 'size.h', false);
	bindOrbField('sxr-orb-x', 'sxr-orb-x-num', 'position.x', false);
	bindOrbField('sxr-orb-y', 'sxr-orb-y-num', 'position.y', false);
	bindOrbField('sxr-orb-amp-x', 'sxr-orb-amp-x-num', 'animation.amplitude_x', false);
	bindOrbField('sxr-orb-amp-y', 'sxr-orb-amp-y-num', 'animation.amplitude_y', false);
	bindOrbField('sxr-orb-freq-x', 'sxr-orb-freq-x-num', 'animation.frequency_x', false);
	bindOrbField('sxr-orb-freq-y', 'sxr-orb-freq-y-num', 'animation.frequency_y', false);
	bindOrbField('sxr-orb-phase', 'sxr-orb-phase-num', 'animation.phase', false);
	bindOrbField('sxr-orb-parallax', 'sxr-orb-parallax-num', 'parallax', false);

	// Shape radios
	document.querySelectorAll('[name="sxr-orb-shape"]').forEach(function (radio) {
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
		jQuery('.sxr-color-picker').wpColorPicker({
			change: function (e, ui) {
				var color = ui.color.toString();
				var id    = e.target.id;
				var orb   = config.orbs[selectedOrbIdx];
				if (!orb) return;
				if (id === 'sxr-orb-color')   { orb.color = color; }
				if (id === 'sxr-orb-color-b') { orb.color_b = color; }
				renderOrbList();
				refreshPreview();
			},
		});
	}

	// Color mode
	var colorModeEl = document.getElementById('sxr-orb-color-mode');
	if (colorModeEl) {
		colorModeEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.color_mode = colorModeEl.value;
			var colorBField = document.querySelector('.sxr-color-b-field');
			if (colorBField) colorBField.style.display = orb.color_mode !== 'solid' ? 'block' : 'none';
		});
	}

	// Anim type
	var animTypeEl = document.getElementById('sxr-orb-anim-type');
	if (animTypeEl) {
		animTypeEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.animation.type = animTypeEl.value;
			refreshPreview();
		});
	}

	// Size unit
	var sizeUnitEl = document.getElementById('sxr-orb-size-unit');
	if (sizeUnitEl) {
		sizeUnitEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.size.unit = sizeUnitEl.value;
			updateUnitLabels('.sxr-unit-label', sizeUnitEl.value);
			sizeUnitRanges(sizeUnitEl.value);
			refreshPreview();
		});
	}

	// Position unit
	var posUnitEl = document.getElementById('sxr-orb-pos-unit');
	if (posUnitEl) {
		posUnitEl.addEventListener('change', function () {
			var orb = config.orbs[selectedOrbIdx];
			if (!orb) return;
			orb.position.unit = posUnitEl.value;
			updateUnitLabels('.sxr-pos-unit-label', posUnitEl.value);
			posUnitRanges(posUnitEl.value);
			refreshPreview();
		});
	}

	/* ------------------------------------------------------------------ */
	/* Tabs                                                                 */
	/* ------------------------------------------------------------------ */

	document.querySelectorAll('.sxr-tab').forEach(function (tab) {
		tab.addEventListener('click', function () {
			var paneName = tab.getAttribute('data-tab');
			document.querySelectorAll('.sxr-tab').forEach(function (t) { t.classList.remove('is-active'); });
			document.querySelectorAll('.sxr-tab-pane').forEach(function (p) { p.classList.remove('is-active'); });
			tab.classList.add('is-active');
			var pane = document.querySelector('[data-pane="' + paneName + '"]');
			if (pane) pane.classList.add('is-active');
		});
	});

	/* ------------------------------------------------------------------ */
	/* Preview engine (mirrors public engine logic, simplified)            */
	/* ------------------------------------------------------------------ */

	var previewCanvas  = document.getElementById('sxr-preview-canvas');
	var previewCtx     = previewCanvas ? previewCanvas.getContext('2d', { alpha: true }) : null;
	var previewRaf     = 0;
	var previewState   = { w: 0, h: 0, dpr: 1, time: 0, lastTime: 0, running: false };
	var PHI = 1.61803, E = 2.71828;

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
	}

	function tickPreview(now) {
		if (!previewCtx) return;
		var state = previewState;
		var dt = Math.min(40, Math.max(0, now - (state.lastTime || now)));
		state.lastTime = now;
		state.time += dt * 0.001 * (config.global && config.global.speed || 1.0);

		var w = state.w, h = state.h;
		previewCtx.setTransform(state.dpr, 0, 0, state.dpr, 0, 0);
		previewCtx.clearRect(0, 0, w, h);

		var blendMode = config.global && config.global.blend_mode || 'screen';
		var safeMargin = ((config.global && config.global.safe_margin) || 0) / 100;
		var t = state.time;

		var _previewOrbs = config.orbs || [];
		for (var _pi = _previewOrbs.length - 1; _pi >= 0; _pi--) {
			var orb = _previewOrbs[_pi];
			var bw = resolveSize(orb.size.w, orb.size.unit, w, h, 'x');
			var bh = resolveSize(orb.size.h, orb.size.unit, w, h, 'y');
			var baseX = resolveSize(orb.position.x, orb.position.unit, w, h, 'x');
			var baseY = resolveSize(orb.position.y, orb.position.unit, w, h, 'y');
			var ax = (orb.animation.amplitude_x / 100) * w;
			var ay = (orb.animation.amplitude_y / 100) * h;
			var fx = orb.animation.frequency_x;
			var fy = orb.animation.frequency_y;
			var ph = orb.animation.phase || 0;
			var seed = hashSeed(orb.id);

			var ox = 0, oy = 0;
			var type = orb.animation.type;

			if (type === 'drift') {
				ox = (Math.sin(t * fx + ph) * 0.68 + Math.sin(t * fx * E + seed) * 0.32) * ax;
				oy = (Math.cos(t * fy + ph) * 0.68 + Math.cos(t * fy * PHI + seed + 1.4) * 0.32) * ay;
			} else if (type === 'orbit') {
				ox = Math.cos(t * fx + ph) * ax;
				oy = Math.sin(t * fy + ph) * ay;
			} else if (type === 'pulse') {
				var scale = 1 + Math.sin(t * fx + ph) * (orb.animation.amplitude_x / 100);
				bw *= scale; bh *= scale;
			} else if (type === 'wave') {
				oy = Math.sin(t * fy + ph) * ay;
			} else if (type === 'figure8') {
				ox = Math.sin(t * fx + ph) * ax;
				oy = Math.sin(t * fy * 2 + ph) * ay * 0.5;
			}

			// Safe margin clamp — orb center stays within safeMargin% of container edge
			var smX = w * safeMargin;
			var smY = h * safeMargin;
			var finalX = Math.max(smX, Math.min(w - smX, baseX + ox));
			var finalY = Math.max(smY, Math.min(h - smY, baseY + oy));

			previewCtx.save();
			previewCtx.filter = 'blur(' + orb.blur + 'px)';
			previewCtx.globalCompositeOperation = blendMode;
			previewCtx.globalAlpha = orb.opacity;

			if (orb.shape === 'circle') {
				drawBlob(previewCtx, finalX, finalY, bw * 0.5, bh * 0.5, orb.color, 1, 0.08);
			} else if (orb.shape === 'double') {
				drawBlob(previewCtx, finalX - bw * 0.125, finalY, bw * 0.425, bh * 0.425, orb.color, 1, 0.12);
				drawBlob(previewCtx, finalX + bw * 0.125, finalY, bw * 0.425, bh * 0.425, orb.color_b || orb.color, 1, 0.12);
			} else if (orb.shape === 'triple') {
				drawBlob(previewCtx, finalX,              finalY - bh * 0.15, bw * 0.375, bh * 0.375, orb.color,            1, 0.10);
				drawBlob(previewCtx, finalX - bw * 0.15, finalY + bh * 0.10, bw * 0.375, bh * 0.375, orb.color_b || orb.color, 1, 0.10);
				drawBlob(previewCtx, finalX + bw * 0.15, finalY + bh * 0.10, bw * 0.375, bh * 0.375, orb.color_b || orb.color, 1, 0.10);
			} else if (orb.shape === 'blob') {
				var blobRx = bw * 0.5 * (1 + Math.sin(t * 0.38 + seed) * 0.15);
				var blobRy = bh * 0.5 * (1 + Math.cos(t * 0.31 + seed + 1.2) * 0.15);
				drawBlob(previewCtx, finalX, finalY, blobRx, blobRy, orb.color, 1, 0.14);
			}

			previewCtx.restore();
		}

		previewRaf = requestAnimationFrame(tickPreview);
	}

	function drawBlob(ctx, x, y, rx, ry, color, alpha, core) {
		var r = Math.max(rx, ry);
		var a = (alpha !== undefined) ? alpha : 1;
		var coreStop = (core !== undefined) ? core : 0.08;
		ctx.save();
		ctx.translate(x, y);
		if (rx !== ry) ctx.scale(rx / r, ry / r);
		// Gradient created inside transformed space so it scales with the ellipse
		var grad = ctx.createRadialGradient(0, 0, 0, 0, 0, r);
		grad.addColorStop(0, hexToRgba(color, a));
		grad.addColorStop(coreStop + 0.46, hexToRgba(color, a * 0.68));
		grad.addColorStop(1, hexToRgba(color, 0));
		ctx.beginPath();
		ctx.arc(0, 0, r, 0, Math.PI * 2);
		ctx.fillStyle = grad;
		ctx.fill();
		ctx.restore();
	}

	function resolveSize(val, unit, w, h, axis) {
		if (unit === 'percent') return (val / 100) * (axis === 'x' ? w : h);
		if (unit === 'vw') return (val / 100) * window.innerWidth;
		if (unit === 'vh') return (val / 100) * window.innerHeight;
		return parseFloat(val); // px
	}

	function hexToRgba(hex, alpha) {
		var r = parseInt(hex.slice(1, 3), 16) || 0;
		var g = parseInt(hex.slice(3, 5), 16) || 0;
		var b = parseInt(hex.slice(5, 7), 16) || 0;
		return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
	}

	function hashSeed(str) {
		var h = 0;
		for (var i = 0; i < str.length; i++) { h = ((h << 5) - h + str.charCodeAt(i)) | 0; }
		return (h & 0xffff) / 0xffff * Math.PI * 6;
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
