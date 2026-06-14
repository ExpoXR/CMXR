<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap cmxr-wrap cmxr-configurator-wrap">

	<h1 class="screen-reader-text"><?php echo $is_new ? esc_html__( 'New Animation', 'cmxr-canvas-motion-backgrounds' ) : esc_html__( 'Edit Animation', 'cmxr-canvas-motion-backgrounds' ); ?></h1>
	<hr class="wp-header-end">

	<div class="cmxr-studio-hero">
			<div>
				<span class="cmxr-studio-kicker"><?php esc_html_e( 'ExpoXR', 'cmxr-canvas-motion-backgrounds' ); ?></span>
				<h2><?php echo $is_new ? esc_html__( 'New Animation', 'cmxr-canvas-motion-backgrounds' ) : esc_html__( 'Edit Animation', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
			</div>
		</div>
		<div class="cmxr-studio-meta">
			<span><?php esc_html_e( 'Canvas Background Builder', 'cmxr-canvas-motion-backgrounds' ); ?></span>
			<span>v<?php echo esc_html( CMXR_VERSION ); ?></span>
		</div>
	</div>

	<div class="cmxr-config-header">
		<div class="cmxr-config-meta">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr' ) ); ?>" class="cmxr-back-link">
				&larr; <?php esc_html_e( 'All Animations', 'cmxr-canvas-motion-backgrounds' ); ?>
			</a>
			<div class="cmxr-title-row">
				<label for="cmxr-title"><?php esc_html_e( 'Animation Name', 'cmxr-canvas-motion-backgrounds' ); ?></label>
				<input type="text" id="cmxr-title" class="cmxr-config-title-input"
					value="<?php echo esc_attr( $is_new ? '' : $post->post_title ); ?>"
					placeholder="<?php esc_attr_e( 'Hero background', 'cmxr-canvas-motion-backgrounds' ); ?>">
			</div>
			<div class="cmxr-config-id-row">
				<label for="cmxr-anim-id"><?php esc_html_e( 'CSS Target ID', 'cmxr-canvas-motion-backgrounds' ); ?></label>
				<div class="cmxr-id-control">
					<span class="cmxr-config-id-hash">#</span>
					<input type="text" id="cmxr-anim-id" class="cmxr-config-id-input"
						placeholder="cmxr_hero-shapes"
						value="<?php echo esc_attr( $config['animation_id'] ?? '' ); ?>">
				</div>
			</div>
		</div>
		<div class="cmxr-config-actions">
			<span class="cmxr-save-status"></span>
			<button id="cmxr-save-btn" class="button button-primary" data-post-id="<?php echo $is_new ? '0' : esc_attr( $post->ID ); ?>">
				<?php esc_html_e( 'Save', 'cmxr-canvas-motion-backgrounds' ); ?>
			</button>
		</div>
	</div>

	<?php
	// Pass config + settings to JS via data attribute
	$cmxr_js_data = array(
		'postId'      => $is_new ? 0 : (int) $post->ID,
		'isNew'       => $is_new,
		'config'      => $config,
		'settings'    => get_option( 'cmxr_settings', array() ),
		'restUrl'     => esc_url_raw( rest_url( 'cmxr/v1' ) ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'breakpoints' => $breakpoints,
	);
	?>
	<div id="cmxr-configurator" class="cmxr-configurator" data-config="<?php echo esc_attr( wp_json_encode( $cmxr_js_data ) ); ?>">

		<!-- Global bar -->
		<div class="cmxr-global-bar">
			<div class="cmxr-global-tabs" role="tablist">
				<button type="button" class="cmxr-global-tab is-active" data-gtab="background" role="tab" aria-selected="true"><?php esc_html_e( 'Background', 'cmxr-canvas-motion-backgrounds' ); ?></button>
				<button type="button" class="cmxr-global-tab" data-gtab="motion" role="tab" aria-selected="false"><?php esc_html_e( 'Motion', 'cmxr-canvas-motion-backgrounds' ); ?></button>
				<button type="button" class="cmxr-global-tab" data-gtab="interaction" role="tab" aria-selected="false"><?php esc_html_e( 'Interaction', 'cmxr-canvas-motion-backgrounds' ); ?></button>
			</div>

			<div class="cmxr-global-panes">

				<!-- Background pane -->
				<div class="cmxr-global-pane is-active" data-gpane="background" role="tabpanel">
					<label>
						<?php esc_html_e( 'Background Color', 'cmxr-canvas-motion-backgrounds' ); ?>
						<?php
						$cmxr_preview_bg_val = $config['global']['preview_bg'] ?? 'transparent';
						$cmxr_preview_bg_hex = sanitize_hex_color( $cmxr_preview_bg_val );
						if ( ! $cmxr_preview_bg_hex ) { $cmxr_preview_bg_hex = '#ffffff'; }
						?>
						<div class="cmxr-preview-bg-row">
							<input type="color" id="cmxr-preview-bg-hex" value="<?php echo esc_attr( $cmxr_preview_bg_hex ); ?>">
							<input type="text" id="cmxr-preview-bg-text" value="<?php echo esc_attr( $cmxr_preview_bg_val ); ?>" placeholder="rgba(0,0,0,0.8)">
							<button type="button" id="cmxr-preview-bg-transparent" class="button button-small"><?php esc_html_e( 'Transparent', 'cmxr-canvas-motion-backgrounds' ); ?></button>
						</div>
					</label>
				</div>

				<!-- Motion pane -->
				<div class="cmxr-global-pane" data-gpane="motion" role="tabpanel">
					<label>
						<?php esc_html_e( 'Speed', 'cmxr-canvas-motion-backgrounds' ); ?>
						<div class="cmxr-slider-row">
							<input type="range" id="cmxr-speed" min="0.1" max="5" step="0.1" value="<?php echo esc_attr( $config['global']['speed'] ?? 1.0 ); ?>">
							<input type="number" class="cmxr-num" id="cmxr-speed-num" min="0.1" max="5" step="0.1" value="<?php echo esc_attr( $config['global']['speed'] ?? 1.0 ); ?>">
						</div>
					</label>

					<label>
						<?php esc_html_e( 'Safe Margin', 'cmxr-canvas-motion-backgrounds' ); ?>
						<div class="cmxr-slider-row">
							<input type="range" id="cmxr-safe-margin" min="0" max="30" step="1" value="<?php echo esc_attr( $config['global']['safe_margin'] ?? 5 ); ?>">
							<input type="number" class="cmxr-num" id="cmxr-safe-margin-num" min="0" max="30" step="1" value="<?php echo esc_attr( $config['global']['safe_margin'] ?? 5 ); ?>">
							<span>%</span>
						</div>
					</label>

					<label>
						<?php esc_html_e( 'Blend Mode', 'cmxr-canvas-motion-backgrounds' ); ?>
						<select id="cmxr-blend-mode">
							<?php foreach ( CMXR_Schema::BLEND_MODES as $bm ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
								<option value="<?php echo esc_attr( $bm ); ?>" <?php selected( $config['global']['blend_mode'] ?? 'screen', $bm ); ?>><?php echo esc_html( $bm ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>

				<!-- Interaction pane -->
				<div class="cmxr-global-pane" data-gpane="interaction" role="tabpanel">
					<label class="cmxr-interactivity-toggle">
						<input type="checkbox" id="cmxr-interactivity-enabled" <?php checked( $is_new || ! empty( $config['global']['interactivity']['enabled'] ) ); ?>>
						<?php esc_html_e( 'Enable Interactivity', 'cmxr-canvas-motion-backgrounds' ); ?>
					</label>

					<div class="cmxr-interactivity-fields" id="cmxr-interactivity-fields">
						<label>
							<?php esc_html_e( 'Mode', 'cmxr-canvas-motion-backgrounds' ); ?>
							<select id="cmxr-interact-mode">
								<?php foreach ( CMXR_Schema::INTERACTIVITY_MODES as $m ) : ?>
									<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $config['global']['interactivity']['mode'] ?? 'parallax', $m ); ?>><?php echo esc_html( ucfirst( $m ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label>
							<?php esc_html_e( 'Strength', 'cmxr-canvas-motion-backgrounds' ); ?>
							<div class="cmxr-slider-row">
								<input type="range" id="cmxr-interact-strength" min="0" max="1" step="0.05" value="<?php echo esc_attr( $config['global']['interactivity']['strength'] ?? 0.5 ); ?>">
								<input type="number" class="cmxr-num" id="cmxr-interact-strength-num" min="0" max="1" step="0.05" value="<?php echo esc_attr( $config['global']['interactivity']['strength'] ?? 0.5 ); ?>">
							</div>
						</label>
						<label>
							<?php esc_html_e( 'Radius (%)', 'cmxr-canvas-motion-backgrounds' ); ?>
							<div class="cmxr-slider-row">
								<input type="range" id="cmxr-interact-radius" min="5" max="80" step="1" value="<?php echo esc_attr( $config['global']['interactivity']['radius'] ?? 30 ); ?>">
								<input type="number" class="cmxr-num" id="cmxr-interact-radius-num" min="5" max="80" step="1" value="<?php echo esc_attr( $config['global']['interactivity']['radius'] ?? 30 ); ?>">
							</div>
						</label>
						<p class="cmxr-hint"><?php esc_html_e( 'Move your mouse over the preview to test interaction.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
					</div>
				</div>

			</div>
		</div>
		<!-- /Global bar -->

		<div class="cmxr-config-body">

			<!-- Left: Orb list -->
			<div class="cmxr-panel cmxr-panel-left">
				<div class="cmxr-panel-header">
					<h3><?php esc_html_e( 'Shapes', 'cmxr-canvas-motion-backgrounds' ); ?></h3>
					<button id="cmxr-add-orb-btn" class="button button-small">+ <?php esc_html_e( 'Add Shape', 'cmxr-canvas-motion-backgrounds' ); ?></button>
				</div>
				<p class="cmxr-orb-list-hint"><?php esc_html_e( 'Top = above others · drag to reorder', 'cmxr-canvas-motion-backgrounds' ); ?></p>
				<ul id="cmxr-orb-list" class="cmxr-orb-list">
					<!-- Populated by JS -->
				</ul>
				<div id="cmxr-orb-empty" class="cmxr-orb-empty">
					<div class="cmxr-orb-empty-icon"></div>
					<strong><?php esc_html_e( 'Start with a shape', 'cmxr-canvas-motion-backgrounds' ); ?></strong>
					<p><?php esc_html_e( 'Add one visual layer, then tune color, motion, and position.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
					<button type="button" id="cmxr-add-first-shape-btn" class="button button-primary"><?php esc_html_e( 'Add First Shape', 'cmxr-canvas-motion-backgrounds' ); ?></button>
				</div>
			</div>

			<!-- Center: Canvas preview -->
			<div class="cmxr-panel cmxr-panel-center">
				<div class="cmxr-preview-label">
					<span class="cmxr-preview-label-title"><?php esc_html_e( 'Live Preview', 'cmxr-canvas-motion-backgrounds' ); ?></span>
					<span class="cmxr-preview-dims" id="cmxr-preview-dims"></span>
					<div class="cmxr-breakpoint-picker">
						<!-- Populated by JS from breakpoints data -->
					</div>
					<div class="cmxr-preview-size-row cmxr-custom-size is-hidden">
						<input type="number" id="cmxr-preview-w" min="100" max="3000" placeholder="W" value="<?php echo esc_attr( $config['global']['preview_w'] ?? '' ); ?>">
						<span>×</span>
						<input type="number" id="cmxr-preview-h" min="100" max="2000" placeholder="H" value="<?php echo esc_attr( $config['global']['preview_h'] ?? '' ); ?>">
						<span>px</span>
						<button type="button" id="cmxr-preview-size-fill" class="button button-small"><?php esc_html_e( 'Fill', 'cmxr-canvas-motion-backgrounds' ); ?></button>
					</div>
				</div>
				<div class="cmxr-preview-stage">
					<div id="cmxr-preview-container">
						<canvas id="cmxr-preview-canvas" aria-hidden="true"></canvas>
						<div class="cmxr-preview-placeholder"><?php esc_html_e( 'Add a shape to preview', 'cmxr-canvas-motion-backgrounds' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Right: Orb config panel -->
			<div class="cmxr-panel cmxr-panel-right">
				<div id="cmxr-orb-config" class="cmxr-orb-config">
					<div class="cmxr-no-selection">
						<p><?php esc_html_e( 'Select a shape from the list to configure it.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
					</div>

					<div class="cmxr-orb-fields is-hidden">
						<!-- Tabs -->
						<div class="cmxr-tabs" role="tablist">
							<button type="button" class="cmxr-tab is-active" data-tab="shape" role="tab" aria-selected="true" aria-controls="cmxr-pane-shape"><?php esc_html_e( 'Shape', 'cmxr-canvas-motion-backgrounds' ); ?></button>
							<button type="button" class="cmxr-tab" data-tab="color" role="tab" aria-selected="false" aria-controls="cmxr-pane-color"><?php esc_html_e( 'Color', 'cmxr-canvas-motion-backgrounds' ); ?></button>
							<button type="button" class="cmxr-tab" data-tab="size" role="tab" aria-selected="false" aria-controls="cmxr-pane-size"><?php esc_html_e( 'Size & Position', 'cmxr-canvas-motion-backgrounds' ); ?></button>
							<button type="button" class="cmxr-tab" data-tab="anim" role="tab" aria-selected="false" aria-controls="cmxr-pane-anim"><?php esc_html_e( 'Animation', 'cmxr-canvas-motion-backgrounds' ); ?></button>
							<button type="button" class="cmxr-tab" data-tab="interact" role="tab" aria-selected="false" aria-controls="cmxr-pane-interact"><?php esc_html_e( 'Interaction', 'cmxr-canvas-motion-backgrounds' ); ?></button>
						</div>

						<!-- Shape tab -->
						<div class="cmxr-tab-pane is-active" data-pane="shape" id="cmxr-pane-shape" role="tabpanel">
							<?php foreach ( CMXR_Schema::get_shape_groups() as $shape_group ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
								<div class="cmxr-shape-group">
									<h4><?php echo esc_html( $shape_group['label'] ); ?></h4>
									<div class="cmxr-shape-grid">
										<?php foreach ( $shape_group['shapes'] as $val => $label ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
											<label class="cmxr-shape-option">
												<input type="radio" name="cmxr-orb-shape" value="<?php echo esc_attr( $val ); ?>">
												<span class="cmxr-shape-preview cmxr-shape-<?php echo esc_attr( $val ); ?>"></span>
												<span><?php echo esc_html( $label ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>

							<div class="cmxr-field">
								<label><?php esc_html_e( 'Blur', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-blur" min="0" max="200" step="1" value="72">
									<input type="number" class="cmxr-num" id="cmxr-orb-blur-num" min="0" max="200" step="1" value="72">
									<span>px</span>
								</div>
							</div>

							<div class="cmxr-field">
								<label><?php esc_html_e( 'Opacity', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-opacity" min="0" max="1" step="0.01" value="0.8">
									<input type="number" class="cmxr-num" id="cmxr-orb-opacity-num" min="0" max="1" step="0.01" value="0.8">
								</div>
							</div>
						</div>

						<!-- Color tab -->
						<div class="cmxr-tab-pane" data-pane="color" id="cmxr-pane-color" role="tabpanel">
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Color Mode', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<select id="cmxr-orb-color-mode">
									<option value="solid"><?php esc_html_e( 'Solid', 'cmxr-canvas-motion-backgrounds' ); ?></option>
									<option value="dual"><?php esc_html_e( 'Dual Color', 'cmxr-canvas-motion-backgrounds' ); ?></option>
									<option value="gradient"><?php esc_html_e( 'Gradient', 'cmxr-canvas-motion-backgrounds' ); ?></option>
								</select>
							</div>
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Primary Color', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<input type="text" id="cmxr-orb-color" class="cmxr-color-picker" value="#38a3d7">
							</div>
							<div class="cmxr-field cmxr-color-b-field">
								<label><?php esc_html_e( 'Secondary Color', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<input type="text" id="cmxr-orb-color-b" class="cmxr-color-picker" value="#8bb84a">
							</div>
							<div class="cmxr-field cmxr-gradient-colors-field">
								<label><?php esc_html_e( 'Gradient Colors', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div id="cmxr-gradient-colors" class="cmxr-gradient-colors"></div>
								<button type="button" id="cmxr-add-gradient-color" class="button button-small"><?php esc_html_e( 'Add Color', 'cmxr-canvas-motion-backgrounds' ); ?></button>
								<p class="description"><?php esc_html_e( 'Use up to 5 colors. First two stay synced with Primary and Secondary.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
							</div>
							<div class="cmxr-field cmxr-color-animation-field">
								<label><?php esc_html_e( 'Color Animation', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<select id="cmxr-orb-color-animation">
									<?php foreach ( CMXR_Schema::get_color_animation_labels() as $cmxr_animation => $cmxr_label ) : ?>
										<option value="<?php echo esc_attr( $cmxr_animation ); ?>"><?php echo esc_html( $cmxr_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<!-- Size & Position tab -->
						<div class="cmxr-tab-pane" data-pane="size" id="cmxr-pane-size" role="tabpanel">
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Size Unit', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<select id="cmxr-orb-size-unit">
									<option value="percent">%</option>
									<option value="px">px</option>
									<option value="vw">vw</option>
									<option value="vh">vh</option>
								</select>
							</div>
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Width', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-w" min="1" max="200" step="1" value="40">
									<input type="number" class="cmxr-num" id="cmxr-orb-w-num" min="1" max="200" step="1" value="40">
									<span class="cmxr-unit-label">%</span>
								</div>
							</div>
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Height', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-h" min="1" max="200" step="1" value="40">
									<input type="number" class="cmxr-num" id="cmxr-orb-h-num" min="1" max="200" step="1" value="40">
									<span class="cmxr-unit-label">%</span>
								</div>
							</div>
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Rotation (°)', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-rotation" min="0" max="360" step="1" value="0">
									<input type="number" class="cmxr-num" id="cmxr-orb-rotation-num" min="0" max="360" step="1" value="0">
									<span>°</span>
								</div>
							</div>

							<hr>

							<div class="cmxr-field">
								<label><?php esc_html_e( 'Position Unit', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<select id="cmxr-orb-pos-unit">
									<option value="percent">%</option>
									<option value="px">px</option>
									<option value="vw">vw</option>
									<option value="vh">vh</option>
								</select>
								<p class="description"><?php esc_html_e( 'Position is relative to the container holding the animation ID.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
							</div>
							<div class="cmxr-field">
								<label><?php esc_html_e( 'X Position', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-x" min="0" max="100" step="1" value="50">
									<input type="number" class="cmxr-num" id="cmxr-orb-x-num" min="0" max="100" step="1" value="50">
									<span class="cmxr-pos-unit-label">%</span>
								</div>
							</div>
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Y Position', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-y" min="0" max="100" step="1" value="50">
									<input type="number" class="cmxr-num" id="cmxr-orb-y-num" min="0" max="100" step="1" value="50">
									<span class="cmxr-pos-unit-label">%</span>
								</div>
							</div>
						</div>

						<!-- Animation tab -->
						<div class="cmxr-tab-pane" data-pane="anim" id="cmxr-pane-anim" role="tabpanel">
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Animation Type', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<select id="cmxr-orb-anim-type">
									<option value="drift"><?php esc_html_e( 'Drift (compound harmonic)', 'cmxr-canvas-motion-backgrounds' ); ?></option>
									<option value="orbit"><?php esc_html_e( 'Orbit (elliptical)', 'cmxr-canvas-motion-backgrounds' ); ?></option>
									<option value="pulse"><?php esc_html_e( 'Pulse (breathing)', 'cmxr-canvas-motion-backgrounds' ); ?></option>
									<option value="wave"><?php esc_html_e( 'Wave (sine path)', 'cmxr-canvas-motion-backgrounds' ); ?></option>
									<option value="fixed"><?php esc_html_e( 'Fixed (no movement)', 'cmxr-canvas-motion-backgrounds' ); ?></option>
									<option value="figure8"><?php esc_html_e( 'Figure 8 (Lissajous)', 'cmxr-canvas-motion-backgrounds' ); ?></option>
								</select>
							</div>

							<div class="cmxr-field cmxr-anim-amp">
								<label><?php esc_html_e( 'Amplitude X (%)', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-amp-x" min="0" max="50" step="0.5" value="5">
									<input type="number" class="cmxr-num" id="cmxr-orb-amp-x-num" min="0" max="50" step="0.5" value="5">
								</div>
							</div>
							<div class="cmxr-field cmxr-anim-amp">
								<label><?php esc_html_e( 'Amplitude Y (%)', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-amp-y" min="0" max="50" step="0.5" value="5">
									<input type="number" class="cmxr-num" id="cmxr-orb-amp-y-num" min="0" max="50" step="0.5" value="5">
								</div>
							</div>
							<div class="cmxr-field cmxr-anim-freq">
								<label><?php esc_html_e( 'Frequency X', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-freq-x" min="0.05" max="5" step="0.05" value="0.4">
									<input type="number" class="cmxr-num" id="cmxr-orb-freq-x-num" min="0.05" max="5" step="0.05" value="0.4">
								</div>
							</div>
							<div class="cmxr-field cmxr-anim-freq">
								<label><?php esc_html_e( 'Frequency Y', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-freq-y" min="0.05" max="5" step="0.05" value="0.5">
									<input type="number" class="cmxr-num" id="cmxr-orb-freq-y-num" min="0.05" max="5" step="0.05" value="0.5">
								</div>
							</div>
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Phase Offset (rad)', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-phase" min="0" max="6.28" step="0.1" value="0">
									<input type="number" class="cmxr-num" id="cmxr-orb-phase-num" min="0" max="6.28" step="0.1" value="0">
								</div>
							</div>
						</div>

						<!-- Interaction tab -->
						<div class="cmxr-tab-pane" data-pane="interact" id="cmxr-pane-interact" role="tabpanel">
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Interaction Direction', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<select id="cmxr-orb-interaction-direction">
									<?php foreach ( CMXR_Schema::get_interaction_direction_labels() as $cmxr_direction => $cmxr_label ) : ?>
										<option value="<?php echo esc_attr( $cmxr_direction ); ?>"><?php echo esc_html( $cmxr_label ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Normal follows the global interaction behavior. Reverse flips this layer response.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
							</div>
							<div class="cmxr-field">
								<label><?php esc_html_e( 'Parallax Depth', 'cmxr-canvas-motion-backgrounds' ); ?></label>
								<div class="cmxr-slider-row">
									<input type="range" id="cmxr-orb-parallax" min="0" max="1" step="0.05" value="0.5">
									<input type="number" class="cmxr-num" id="cmxr-orb-parallax-num" min="0" max="1" step="0.05" value="0.5">
								</div>
								<p class="description"><?php esc_html_e( '0 = no reaction, 1 = full reaction. Used by parallax, repel, attract modes.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
							</div>
						</div>

					</div><!-- /.cmxr-orb-fields -->
				</div><!-- /#cmxr-orb-config -->
			</div><!-- /.cmxr-panel-right -->

		</div><!-- /.cmxr-config-body -->
	</div><!-- /#cmxr-configurator -->

	<?php CMXR_Dashboard::render_footer(); ?>
</div><!-- /.wrap -->
