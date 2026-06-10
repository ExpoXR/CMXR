<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap spherexr-wrap spherexr-configurator-wrap">

	<h1 class="screen-reader-text"><?php echo $is_new ? esc_html__( 'New Animation', 'spherexr' ) : esc_html__( 'Edit Animation', 'spherexr' ); ?></h1>
	<hr class="wp-header-end">

	<div class="spherexr-config-header">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr' ) ); ?>" class="spherexr-back-link">
			&#8592; <?php esc_html_e( 'All Animations', 'spherexr' ); ?>
		</a>
		<div class="spherexr-config-meta">
			<input type="text" id="sxr-title" class="spherexr-config-title-input"
				value="<?php echo esc_attr( $is_new ? '' : $post->post_title ); ?>"
				placeholder="<?php esc_attr_e( 'Animation Name', 'spherexr' ); ?>">
			<div class="spherexr-config-id-row">
				<span class="spherexr-config-id-hash">#</span>
				<input type="text" id="sxr-anim-id" class="spherexr-config-id-input"
					placeholder="hero-orbs"
					value="<?php echo esc_attr( $config['animation_id'] ?? '' ); ?>">
			</div>
		</div>
		<div class="spherexr-config-actions">
			<span class="spherexr-save-status"></span>
			<button id="spherexr-save-btn" class="button button-primary" data-post-id="<?php echo $is_new ? '0' : esc_attr( $post->ID ); ?>">
				<?php esc_html_e( 'Save', 'spherexr' ); ?>
			</button>
		</div>
	</div>

	<?php
	// Pass config + settings to JS via data attribute
	$js_data = array(
		'postId'      => $is_new ? 0 : (int) $post->ID,
		'isNew'       => $is_new,
		'config'      => $config,
		'settings'    => get_option( 'spherexr_settings', array() ),
		'restUrl'     => esc_url_raw( rest_url( 'spherexr/v1' ) ),
		'nonce'       => wp_create_nonce( 'wp_rest' ),
		'breakpoints' => $breakpoints,
	);
	?>
	<div id="spherexr-configurator" data-config="<?php echo esc_attr( wp_json_encode( $js_data ) ); ?>">

		<!-- Global bar -->
		<div class="spherexr-global-bar">
			<label>
				<?php esc_html_e( 'Preview BG', 'spherexr' ); ?>
				<?php
				$preview_bg_val = $config['global']['preview_bg'] ?? 'transparent';
				$preview_bg_hex = sanitize_hex_color( $preview_bg_val );
				if ( ! $preview_bg_hex ) { $preview_bg_hex = '#0f0c1a'; }
				?>
				<div class="sxr-preview-bg-row">
					<input type="color" id="sxr-preview-bg-hex" value="<?php echo esc_attr( $preview_bg_hex ); ?>">
					<input type="text" id="sxr-preview-bg-text" value="<?php echo esc_attr( $preview_bg_val ); ?>" placeholder="rgba(0,0,0,0.8)">
					<button type="button" id="sxr-preview-bg-transparent" class="button button-small"><?php esc_html_e( 'Transparent', 'spherexr' ); ?></button>
				</div>
			</label>

			<label>
				<?php esc_html_e( 'Speed', 'spherexr' ); ?>
				<div class="sxr-slider-row">
					<input type="range" id="sxr-speed" min="0.1" max="5" step="0.1" value="<?php echo esc_attr( $config['global']['speed'] ?? 1.0 ); ?>">
					<input type="number" class="sxr-num" id="sxr-speed-num" min="0.1" max="5" step="0.1" value="<?php echo esc_attr( $config['global']['speed'] ?? 1.0 ); ?>">
				</div>
			</label>

			<label>
				<?php esc_html_e( 'Safe Margin', 'spherexr' ); ?>
				<div class="sxr-slider-row">
					<input type="range" id="sxr-safe-margin" min="0" max="30" step="1" value="<?php echo esc_attr( $config['global']['safe_margin'] ?? 5 ); ?>">
					<input type="number" class="sxr-num" id="sxr-safe-margin-num" min="0" max="30" step="1" value="<?php echo esc_attr( $config['global']['safe_margin'] ?? 5 ); ?>">
					<span>%</span>
				</div>
			</label>

			<label>
				<?php esc_html_e( 'Blend Mode', 'spherexr' ); ?>
				<select id="sxr-blend-mode">
					<?php foreach ( SphereXR_Schema::BLEND_MODES as $bm ) : ?>
						<option value="<?php echo esc_attr( $bm ); ?>" <?php selected( $config['global']['blend_mode'] ?? 'screen', $bm ); ?>><?php echo esc_html( $bm ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>

			<label class="sxr-interactivity-toggle">
				<input type="checkbox" id="sxr-interactivity-enabled" <?php checked( ! empty( $config['global']['interactivity']['enabled'] ) ); ?>>
				<?php esc_html_e( 'Interactivity', 'spherexr' ); ?>
			</label>

			<div class="sxr-interactivity-fields" id="sxr-interactivity-fields">
				<label>
					<?php esc_html_e( 'Mode', 'spherexr' ); ?>
					<select id="sxr-interact-mode">
						<?php foreach ( SphereXR_Schema::INTERACTIVITY_MODES as $m ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $config['global']['interactivity']['mode'] ?? 'parallax', $m ); ?>><?php echo esc_html( $m ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<?php esc_html_e( 'Strength', 'spherexr' ); ?>
					<div class="sxr-slider-row">
						<input type="range" id="sxr-interact-strength" min="0" max="1" step="0.05" value="<?php echo esc_attr( $config['global']['interactivity']['strength'] ?? 0.5 ); ?>">
						<input type="number" class="sxr-num" id="sxr-interact-strength-num" min="0" max="1" step="0.05" value="<?php echo esc_attr( $config['global']['interactivity']['strength'] ?? 0.5 ); ?>">
					</div>
				</label>
				<label>
					<?php esc_html_e( 'Radius (%)', 'spherexr' ); ?>
					<div class="sxr-slider-row">
						<input type="range" id="sxr-interact-radius" min="5" max="80" step="1" value="<?php echo esc_attr( $config['global']['interactivity']['radius'] ?? 30 ); ?>">
						<input type="number" class="sxr-num" id="sxr-interact-radius-num" min="5" max="80" step="1" value="<?php echo esc_attr( $config['global']['interactivity']['radius'] ?? 30 ); ?>">
					</div>
				</label>
			</div>
		</div>
		<!-- /Global bar -->

		<div class="spherexr-config-body">

			<!-- Left: Orb list -->
			<div class="sxr-panel sxr-panel-left">
				<div class="sxr-panel-header">
					<h3><?php esc_html_e( 'Orbs', 'spherexr' ); ?></h3>
					<button id="sxr-add-orb-btn" class="button button-small">+ <?php esc_html_e( 'Add Orb', 'spherexr' ); ?></button>
				</div>
				<p class="sxr-orb-list-hint"><?php esc_html_e( 'Top = above others · drag to reorder', 'spherexr' ); ?></p>
				<ul id="sxr-orb-list" class="sxr-orb-list">
					<!-- Populated by JS -->
				</ul>
			</div>

			<!-- Center: Canvas preview -->
			<div class="sxr-panel sxr-panel-center">
				<div class="sxr-preview-label">
					<span><?php esc_html_e( 'Live Preview', 'spherexr' ); ?></span>
					<div class="sxr-breakpoint-picker">
						<!-- Populated by JS from breakpoints data -->
					</div>
					<div class="sxr-preview-size-row sxr-custom-size is-hidden">
						<input type="number" id="sxr-preview-w" min="100" max="3000" placeholder="W" value="<?php echo esc_attr( $config['global']['preview_w'] ?? '' ); ?>">
						<span>×</span>
						<input type="number" id="sxr-preview-h" min="100" max="2000" placeholder="H" value="<?php echo esc_attr( $config['global']['preview_h'] ?? '' ); ?>">
						<span>px</span>
						<button type="button" id="sxr-preview-size-fill" class="button button-small"><?php esc_html_e( 'Fill', 'spherexr' ); ?></button>
					</div>
				</div>
				<div id="sxr-preview-container">
					<canvas id="sxr-preview-canvas" aria-hidden="true"></canvas>
					<div class="sxr-preview-placeholder"><?php esc_html_e( 'Add orbs to preview', 'spherexr' ); ?></div>
				</div>
			</div>

			<!-- Right: Orb config panel -->
			<div class="sxr-panel sxr-panel-right">
				<div id="sxr-orb-config" class="sxr-orb-config">
					<div class="sxr-no-selection">
						<p><?php esc_html_e( 'Select an orb from the list to configure it.', 'spherexr' ); ?></p>
					</div>

					<div class="sxr-orb-fields is-hidden">
						<!-- Tabs -->
						<div class="sxr-tabs" role="tablist">
							<button type="button" class="sxr-tab is-active" data-tab="shape" role="tab" aria-selected="true" aria-controls="sxr-pane-shape"><?php esc_html_e( 'Shape', 'spherexr' ); ?></button>
							<button type="button" class="sxr-tab" data-tab="color" role="tab" aria-selected="false" aria-controls="sxr-pane-color"><?php esc_html_e( 'Color', 'spherexr' ); ?></button>
							<button type="button" class="sxr-tab" data-tab="size" role="tab" aria-selected="false" aria-controls="sxr-pane-size"><?php esc_html_e( 'Size & Position', 'spherexr' ); ?></button>
							<button type="button" class="sxr-tab" data-tab="anim" role="tab" aria-selected="false" aria-controls="sxr-pane-anim"><?php esc_html_e( 'Animation', 'spherexr' ); ?></button>
							<button type="button" class="sxr-tab" data-tab="interact" role="tab" aria-selected="false" aria-controls="sxr-pane-interact"><?php esc_html_e( 'Interaction', 'spherexr' ); ?></button>
						</div>

						<!-- Shape tab -->
						<div class="sxr-tab-pane is-active" data-pane="shape" id="sxr-pane-shape" role="tabpanel">
							<div class="sxr-shape-grid">
								<?php foreach ( array(
									'circle'  => __( 'Circle', 'spherexr' ),
									'double'  => __( 'Double', 'spherexr' ),
									'triple'  => __( 'Triple', 'spherexr' ),
									'blob'    => __( 'Blob', 'spherexr' ),
								) as $val => $label ) : ?>
									<label class="sxr-shape-option">
										<input type="radio" name="sxr-orb-shape" value="<?php echo esc_attr( $val ); ?>">
										<span class="sxr-shape-preview sxr-shape-<?php echo esc_attr( $val ); ?>"></span>
										<span><?php echo esc_html( $label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>

							<div class="sxr-field">
								<label><?php esc_html_e( 'Blur', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-blur" min="0" max="200" step="1" value="72">
									<input type="number" class="sxr-num" id="sxr-orb-blur-num" min="0" max="200" step="1" value="72">
									<span>px</span>
								</div>
							</div>

							<div class="sxr-field">
								<label><?php esc_html_e( 'Opacity', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-opacity" min="0" max="1" step="0.01" value="0.8">
									<input type="number" class="sxr-num" id="sxr-orb-opacity-num" min="0" max="1" step="0.01" value="0.8">
								</div>
							</div>
						</div>

						<!-- Color tab -->
						<div class="sxr-tab-pane" data-pane="color" id="sxr-pane-color" role="tabpanel">
							<div class="sxr-field">
								<label><?php esc_html_e( 'Color Mode', 'spherexr' ); ?></label>
								<select id="sxr-orb-color-mode">
									<option value="solid"><?php esc_html_e( 'Solid', 'spherexr' ); ?></option>
									<option value="dual"><?php esc_html_e( 'Dual Color', 'spherexr' ); ?></option>
									<option value="gradient"><?php esc_html_e( 'Gradient', 'spherexr' ); ?></option>
								</select>
							</div>
							<div class="sxr-field">
								<label><?php esc_html_e( 'Primary Color', 'spherexr' ); ?></label>
								<input type="text" id="sxr-orb-color" class="sxr-color-picker" value="#38a3d7">
							</div>
							<div class="sxr-field sxr-color-b-field">
								<label><?php esc_html_e( 'Secondary Color', 'spherexr' ); ?></label>
								<input type="text" id="sxr-orb-color-b" class="sxr-color-picker" value="#8bb84a">
							</div>
						</div>

						<!-- Size & Position tab -->
						<div class="sxr-tab-pane" data-pane="size" id="sxr-pane-size" role="tabpanel">
							<div class="sxr-field">
								<label><?php esc_html_e( 'Size Unit', 'spherexr' ); ?></label>
								<select id="sxr-orb-size-unit">
									<option value="percent">%</option>
									<option value="px">px</option>
									<option value="vw">vw</option>
									<option value="vh">vh</option>
								</select>
							</div>
							<div class="sxr-field">
								<label><?php esc_html_e( 'Width', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-w" min="1" max="200" step="1" value="40">
									<input type="number" class="sxr-num" id="sxr-orb-w-num" min="1" max="200" step="1" value="40">
									<span class="sxr-unit-label">%</span>
								</div>
							</div>
							<div class="sxr-field">
								<label><?php esc_html_e( 'Height', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-h" min="1" max="200" step="1" value="40">
									<input type="number" class="sxr-num" id="sxr-orb-h-num" min="1" max="200" step="1" value="40">
									<span class="sxr-unit-label">%</span>
								</div>
							</div>

							<hr>

							<div class="sxr-field">
								<label><?php esc_html_e( 'Position Unit', 'spherexr' ); ?></label>
								<select id="sxr-orb-pos-unit">
									<option value="percent">%</option>
									<option value="px">px</option>
									<option value="vw">vw</option>
									<option value="vh">vh</option>
								</select>
								<p class="description"><?php esc_html_e( 'Position is relative to the container holding the animation ID.', 'spherexr' ); ?></p>
							</div>
							<div class="sxr-field">
								<label><?php esc_html_e( 'X Position', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-x" min="0" max="100" step="1" value="50">
									<input type="number" class="sxr-num" id="sxr-orb-x-num" min="0" max="100" step="1" value="50">
									<span class="sxr-pos-unit-label">%</span>
								</div>
							</div>
							<div class="sxr-field">
								<label><?php esc_html_e( 'Y Position', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-y" min="0" max="100" step="1" value="50">
									<input type="number" class="sxr-num" id="sxr-orb-y-num" min="0" max="100" step="1" value="50">
									<span class="sxr-pos-unit-label">%</span>
								</div>
							</div>
						</div>

						<!-- Animation tab -->
						<div class="sxr-tab-pane" data-pane="anim" id="sxr-pane-anim" role="tabpanel">
							<div class="sxr-field">
								<label><?php esc_html_e( 'Animation Type', 'spherexr' ); ?></label>
								<select id="sxr-orb-anim-type">
									<option value="drift"><?php esc_html_e( 'Drift (compound harmonic)', 'spherexr' ); ?></option>
									<option value="orbit"><?php esc_html_e( 'Orbit (elliptical)', 'spherexr' ); ?></option>
									<option value="pulse"><?php esc_html_e( 'Pulse (breathing)', 'spherexr' ); ?></option>
									<option value="wave"><?php esc_html_e( 'Wave (sine path)', 'spherexr' ); ?></option>
									<option value="fixed"><?php esc_html_e( 'Fixed (no movement)', 'spherexr' ); ?></option>
									<option value="figure8"><?php esc_html_e( 'Figure 8 (Lissajous)', 'spherexr' ); ?></option>
								</select>
							</div>

							<div class="sxr-field sxr-anim-amp">
								<label><?php esc_html_e( 'Amplitude X (%)', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-amp-x" min="0" max="50" step="0.5" value="5">
									<input type="number" class="sxr-num" id="sxr-orb-amp-x-num" min="0" max="50" step="0.5" value="5">
								</div>
							</div>
							<div class="sxr-field sxr-anim-amp">
								<label><?php esc_html_e( 'Amplitude Y (%)', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-amp-y" min="0" max="50" step="0.5" value="5">
									<input type="number" class="sxr-num" id="sxr-orb-amp-y-num" min="0" max="50" step="0.5" value="5">
								</div>
							</div>
							<div class="sxr-field sxr-anim-freq">
								<label><?php esc_html_e( 'Frequency X', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-freq-x" min="0.05" max="5" step="0.05" value="0.4">
									<input type="number" class="sxr-num" id="sxr-orb-freq-x-num" min="0.05" max="5" step="0.05" value="0.4">
								</div>
							</div>
							<div class="sxr-field sxr-anim-freq">
								<label><?php esc_html_e( 'Frequency Y', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-freq-y" min="0.05" max="5" step="0.05" value="0.5">
									<input type="number" class="sxr-num" id="sxr-orb-freq-y-num" min="0.05" max="5" step="0.05" value="0.5">
								</div>
							</div>
							<div class="sxr-field">
								<label><?php esc_html_e( 'Phase Offset (rad)', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-phase" min="0" max="6.28" step="0.1" value="0">
									<input type="number" class="sxr-num" id="sxr-orb-phase-num" min="0" max="6.28" step="0.1" value="0">
								</div>
							</div>
						</div>

						<!-- Interaction tab -->
						<div class="sxr-tab-pane" data-pane="interact" id="sxr-pane-interact" role="tabpanel">
							<div class="sxr-field">
								<label><?php esc_html_e( 'Parallax Depth', 'spherexr' ); ?></label>
								<div class="sxr-slider-row">
									<input type="range" id="sxr-orb-parallax" min="0" max="1" step="0.05" value="0.5">
									<input type="number" class="sxr-num" id="sxr-orb-parallax-num" min="0" max="1" step="0.05" value="0.5">
								</div>
								<p class="description"><?php esc_html_e( '0 = no reaction, 1 = full reaction. Used by parallax, repel, attract modes.', 'spherexr' ); ?></p>
							</div>
						</div>

					</div><!-- /.sxr-orb-fields -->
				</div><!-- /#sxr-orb-config -->
			</div><!-- /.sxr-panel-right -->

		</div><!-- /.spherexr-config-body -->
	</div><!-- /#spherexr-configurator -->

	<?php SphereXR_Dashboard::render_footer(); ?>
</div><!-- /.wrap -->
