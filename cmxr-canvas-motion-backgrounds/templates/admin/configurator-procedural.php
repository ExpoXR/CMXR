<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$cmxr_js_data = array(
	'postId'   => (int) $post->ID,
	'isNew'    => false,
	'title'    => $post->post_title,
	'config'   => $config,
	'restUrl'  => esc_url_raw( rest_url( 'cmxr/v1' ) ),
	'nonce'    => wp_create_nonce( 'wp_rest' ),
);
$cmxr_get = static function ( $path, $fallback = '' ) use ( $config ) {
	$value = $config;
	foreach ( explode( '.', $path ) as $key ) {
		if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) return $fallback;
		$value = $value[ $key ];
	}
	return $value;
};
$cmxr_interactive = ! empty( $config['settings']['physics'] );
?>
<div class="wrap cmxr-wrap cmxr-configurator-wrap">
	<h1 class="screen-reader-text"><?php esc_html_e( 'Edit Animation', 'cmxr-canvas-motion-backgrounds' ); ?></h1>
	<hr class="wp-header-end">

	<div class="cmxr-studio-hero">
		<div>
			<span class="cmxr-studio-kicker"><?php esc_html_e( 'ExpoXR Template', 'cmxr-canvas-motion-backgrounds' ); ?></span>
			<h2><?php echo esc_html( $template_definition['title'] ?? __( 'Procedural Orbs', 'cmxr-canvas-motion-backgrounds' ) ); ?></h2>
		</div>
		<div class="cmxr-studio-meta"><span>v<?php echo esc_html( CMXR_VERSION ); ?></span></div>
	</div>

	<div class="cmxr-config-header">
		<div class="cmxr-config-meta">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr' ) ); ?>" class="cmxr-back-link">&larr; <?php esc_html_e( 'All Animations', 'cmxr-canvas-motion-backgrounds' ); ?></a>
			<div class="cmxr-title-row">
				<label for="cmxr-proc-title"><?php esc_html_e( 'Animation Name', 'cmxr-canvas-motion-backgrounds' ); ?></label>
				<input type="text" id="cmxr-proc-title" class="cmxr-config-title-input" value="<?php echo esc_attr( $post->post_title ); ?>">
			</div>
			<div class="cmxr-config-id-row">
				<label for="cmxr-proc-target"><?php esc_html_e( 'CSS Target ID', 'cmxr-canvas-motion-backgrounds' ); ?></label>
				<div class="cmxr-id-control">
					<span class="cmxr-config-id-hash">#</span>
					<input type="text" id="cmxr-proc-target" class="cmxr-config-id-input" value="<?php echo esc_attr( $cmxr_get( 'target.selector' ) ); ?>">
				</div>
			</div>
		</div>
		<div class="cmxr-config-actions">
			<span class="cmxr-save-status" aria-live="polite"></span>
			<button type="button" class="button" id="cmxr-proc-reset"><?php esc_html_e( 'Reset to Template', 'cmxr-canvas-motion-backgrounds' ); ?></button>
			<button type="button" class="button button-primary" id="cmxr-proc-save"><?php esc_html_e( 'Save', 'cmxr-canvas-motion-backgrounds' ); ?></button>
		</div>
	</div>

	<div id="cmxr-procedural-configurator" class="cmxr-configurator cmxr-procedural-configurator" data-config="<?php echo esc_attr( wp_json_encode( $cmxr_js_data ) ); ?>">
		<div class="cmxr-config-body">
			<div class="cmxr-panel cmxr-panel-left cmxr-proc-controls">
				<div class="cmxr-panel-header"><h3><?php esc_html_e( 'Color & Density', 'cmxr-canvas-motion-backgrounds' ); ?></h3></div>

				<label class="cmxr-field"><?php esc_html_e( 'Palette Mode', 'cmxr-canvas-motion-backgrounds' ); ?>
					<select data-cmxr-path="settings.palette.mode">
						<option value="random-hsl" <?php selected( $cmxr_get( 'settings.palette.mode' ), 'random-hsl' ); ?>><?php esc_html_e( 'Random HSL', 'cmxr-canvas-motion-backgrounds' ); ?></option>
						<option value="fixed" <?php selected( $cmxr_get( 'settings.palette.mode' ), 'fixed' ); ?>><?php esc_html_e( 'Fixed Colors', 'cmxr-canvas-motion-backgrounds' ); ?></option>
					</select>
				</label>
				<label class="cmxr-field"><?php esc_html_e( 'Seed', 'cmxr-canvas-motion-backgrounds' ); ?>
					<div class="cmxr-proc-inline"><input type="text" id="cmxr-proc-seed" data-cmxr-path="settings.seed" value="<?php echo esc_attr( $cmxr_get( 'settings.seed' ) ); ?>"><button type="button" class="button" id="cmxr-proc-new-seed"><?php esc_html_e( 'New', 'cmxr-canvas-motion-backgrounds' ); ?></button></div>
				</label>

				<div class="cmxr-proc-grid-2">
					<label><?php esc_html_e( 'Hue Min', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="360" data-cmxr-number data-cmxr-path="settings.palette.hue_min" value="<?php echo esc_attr( $cmxr_get( 'settings.palette.hue_min' ) ); ?>"></label>
					<label><?php esc_html_e( 'Hue Max', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="360" data-cmxr-number data-cmxr-path="settings.palette.hue_max" value="<?php echo esc_attr( $cmxr_get( 'settings.palette.hue_max' ) ); ?>"></label>
					<label><?php esc_html_e( 'Saturation', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="100" data-cmxr-number data-cmxr-path="settings.palette.saturation" value="<?php echo esc_attr( $cmxr_get( 'settings.palette.saturation' ) ); ?>"></label>
					<label><?php esc_html_e( 'Lightness', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="100" data-cmxr-number data-cmxr-path="settings.palette.lightness" value="<?php echo esc_attr( $cmxr_get( 'settings.palette.lightness' ) ); ?>"></label>
				</div>
				<div class="cmxr-proc-grid-3 cmxr-proc-colors">
					<?php foreach ( array( 0, 1, 2 ) as $cmxr_color_index ) : ?>
						<?php /* translators: %d: palette color number. */ ?>
						<label><?php echo esc_html( sprintf( __( 'Color %d', 'cmxr-canvas-motion-backgrounds' ), $cmxr_color_index + 1 ) ); ?><input type="color" data-cmxr-path="settings.palette.fixed_colors.<?php echo esc_attr( $cmxr_color_index ); ?>" value="<?php echo esc_attr( $cmxr_get( 'settings.palette.fixed_colors.' . $cmxr_color_index, '#2AACE2' ) ); ?>"></label>
					<?php endforeach; ?>
				</div>

				<h4><?php esc_html_e( 'Responsive Count', 'cmxr-canvas-motion-backgrounds' ); ?></h4>
				<div class="cmxr-proc-grid-3">
					<?php foreach ( array( 'mobile', 'tablet', 'desktop' ) as $cmxr_device ) : ?>
						<label><?php echo esc_html( ucfirst( $cmxr_device ) ); ?><input type="number" min="1" max="20" data-cmxr-number data-cmxr-path="settings.counts.<?php echo esc_attr( $cmxr_device ); ?>" value="<?php echo esc_attr( $cmxr_get( 'settings.counts.' . $cmxr_device ) ); ?>"></label>
					<?php endforeach; ?>
				</div>

				<h4><?php esc_html_e( 'Blur', 'cmxr-canvas-motion-backgrounds' ); ?></h4>
				<div class="cmxr-proc-grid-3">
					<?php foreach ( array( 'mobile', 'tablet', 'desktop' ) as $cmxr_device ) : ?>
						<label><?php echo esc_html( ucfirst( $cmxr_device ) ); ?><input type="number" min="0" max="200" data-cmxr-number data-cmxr-path="settings.blur.<?php echo esc_attr( $cmxr_device ); ?>" value="<?php echo esc_attr( $cmxr_get( 'settings.blur.' . $cmxr_device ) ); ?>"></label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="cmxr-panel cmxr-panel-center">
				<div class="cmxr-preview-label">
					<span class="cmxr-preview-label-title"><?php esc_html_e( 'Live Preview', 'cmxr-canvas-motion-backgrounds' ); ?></span>
					<div class="cmxr-proc-breakpoints">
						<button type="button" class="button button-small" data-cmxr-preview-size="390,640"><?php esc_html_e( 'Mobile', 'cmxr-canvas-motion-backgrounds' ); ?></button>
						<button type="button" class="button button-small" data-cmxr-preview-size="768,640"><?php esc_html_e( 'Tablet', 'cmxr-canvas-motion-backgrounds' ); ?></button>
						<button type="button" class="button button-small" data-cmxr-preview-size="0,0"><?php esc_html_e( 'Desktop', 'cmxr-canvas-motion-backgrounds' ); ?></button>
					</div>
					<button type="button" class="button button-small" id="cmxr-proc-pause"><?php esc_html_e( 'Pause', 'cmxr-canvas-motion-backgrounds' ); ?></button>
					<label class="cmxr-proc-reduced"><input type="checkbox" id="cmxr-proc-reduced"> <?php esc_html_e( 'Reduced Motion', 'cmxr-canvas-motion-backgrounds' ); ?></label>
				</div>
				<div class="cmxr-preview-stage">
					<div id="cmxr-proc-preview" class="cmxr-proc-preview">
						<canvas id="cmxr-proc-canvas" aria-hidden="true"></canvas>
						<div class="cmxr-proc-preview-content">
							<strong><?php esc_html_e( 'Interactive content stays clickable', 'cmxr-canvas-motion-backgrounds' ); ?></strong>
							<button type="button" class="button"><?php esc_html_e( 'Test Button', 'cmxr-canvas-motion-backgrounds' ); ?></button>
						</div>
					</div>
				</div>
			</div>

			<div class="cmxr-panel cmxr-panel-right cmxr-proc-controls">
				<div class="cmxr-panel-header"><h3><?php esc_html_e( 'Motion & Shape', 'cmxr-canvas-motion-backgrounds' ); ?></h3></div>
				<div class="cmxr-proc-grid-2">
					<label><?php esc_html_e( 'Speed', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0.0001" max="0.02" step="0.0001" data-cmxr-number data-cmxr-path="settings.simplex_increment" value="<?php echo esc_attr( $cmxr_get( 'settings.simplex_increment' ) ); ?>"></label>
					<label><?php esc_html_e( 'Opacity', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="1" step="0.01" data-cmxr-number data-cmxr-path="settings.alpha" value="<?php echo esc_attr( $cmxr_get( 'settings.alpha' ) ); ?>"></label>
					<label><?php esc_html_e( 'Scale Min', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0.1" max="2" step="0.05" data-cmxr-number data-cmxr-path="settings.scale.min" value="<?php echo esc_attr( $cmxr_get( 'settings.scale.min' ) ); ?>"></label>
					<label><?php esc_html_e( 'Scale Max', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0.1" max="3" step="0.05" data-cmxr-number data-cmxr-path="settings.scale.max" value="<?php echo esc_attr( $cmxr_get( 'settings.scale.max' ) ); ?>"></label>
				</div>

				<h4><?php esc_html_e( 'Radius Ratios', 'cmxr-canvas-motion-backgrounds' ); ?></h4>
				<?php foreach ( array( 'mobile', 'tablet', 'desktop' ) as $cmxr_device ) : ?>
					<div class="cmxr-proc-radius-row">
						<span><?php echo esc_html( ucfirst( $cmxr_device ) ); ?></span>
						<input type="number" min="0.01" max="2" step="0.01" data-cmxr-number data-cmxr-path="settings.radius.<?php echo esc_attr( $cmxr_device ); ?>.0" value="<?php echo esc_attr( $cmxr_get( 'settings.radius.' . $cmxr_device . '.0' ) ); ?>">
						<input type="number" min="0.01" max="3" step="0.01" data-cmxr-number data-cmxr-path="settings.radius.<?php echo esc_attr( $cmxr_device ); ?>.1" value="<?php echo esc_attr( $cmxr_get( 'settings.radius.' . $cmxr_device . '.1' ) ); ?>">
					</div>
				<?php endforeach; ?>

				<?php if ( $cmxr_interactive ) : ?>
					<h4><?php esc_html_e( 'Interaction Physics', 'cmxr-canvas-motion-backgrounds' ); ?></h4>
					<div class="cmxr-proc-grid-2">
						<label><?php esc_html_e( 'Attraction', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="2" step="0.01" data-cmxr-number data-cmxr-path="settings.physics.attraction" value="<?php echo esc_attr( $cmxr_get( 'settings.physics.attraction' ) ); ?>"></label>
						<label><?php esc_html_e( 'Radius', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="1" max="2000" data-cmxr-number data-cmxr-path="settings.physics.attraction_radius" value="<?php echo esc_attr( $cmxr_get( 'settings.physics.attraction_radius' ) ); ?>"></label>
						<label><?php esc_html_e( 'Wander', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="2" step="0.01" data-cmxr-number data-cmxr-path="settings.physics.wander_force" value="<?php echo esc_attr( $cmxr_get( 'settings.physics.wander_force' ) ); ?>"></label>
						<label><?php esc_html_e( 'Damping', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0.5" max="0.999" step="0.001" data-cmxr-number data-cmxr-path="settings.physics.damping" value="<?php echo esc_attr( $cmxr_get( 'settings.physics.damping' ) ); ?>"></label>
						<label><?php esc_html_e( 'Boundary Spring', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="5" step="0.1" data-cmxr-number data-cmxr-path="settings.physics.boundary_spring" value="<?php echo esc_attr( $cmxr_get( 'settings.physics.boundary_spring' ) ); ?>"></label>
						<label><?php esc_html_e( 'Position Padding', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="500" data-cmxr-number data-cmxr-path="settings.bounds.padding" value="<?php echo esc_attr( $cmxr_get( 'settings.bounds.padding' ) ); ?>"></label>
					</div>
					<div class="cmxr-proc-toggles">
						<label><input type="checkbox" data-cmxr-bool data-cmxr-path="settings.aura.enabled" <?php checked( $cmxr_get( 'settings.aura.enabled' ) ); ?>> <?php esc_html_e( 'Cursor Aura', 'cmxr-canvas-motion-backgrounds' ); ?></label>
						<label><input type="checkbox" data-cmxr-bool data-cmxr-path="settings.burst.enabled" <?php checked( $cmxr_get( 'settings.burst.enabled' ) ); ?>> <?php esc_html_e( 'Tap Burst', 'cmxr-canvas-motion-backgrounds' ); ?></label>
						<label><input type="checkbox" data-cmxr-bool data-cmxr-path="settings.ripple.enabled" <?php checked( $cmxr_get( 'settings.ripple.enabled' ) ); ?>> <?php esc_html_e( 'Ripple', 'cmxr-canvas-motion-backgrounds' ); ?></label>
						<label><input type="checkbox" data-cmxr-bool data-cmxr-path="settings.touch.enabled" <?php checked( $cmxr_get( 'settings.touch.enabled' ) ); ?>> <?php esc_html_e( 'Touch', 'cmxr-canvas-motion-backgrounds' ); ?></label>
					</div>
					<div class="cmxr-proc-grid-2 cmxr-proc-effect-values">
						<label><?php esc_html_e( 'Aura Radius', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="1" max="1000" data-cmxr-number data-cmxr-path="settings.aura.radius" value="<?php echo esc_attr( $cmxr_get( 'settings.aura.radius' ) ); ?>"></label>
						<label><?php esc_html_e( 'Burst Force', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="30" step="0.1" data-cmxr-number data-cmxr-path="settings.burst.force" value="<?php echo esc_attr( $cmxr_get( 'settings.burst.force' ) ); ?>"></label>
						<label><?php esc_html_e( 'Burst Duration', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="50" max="5000" data-cmxr-number data-cmxr-path="settings.burst.duration_ms" value="<?php echo esc_attr( $cmxr_get( 'settings.burst.duration_ms' ) ); ?>"></label>
						<label><?php esc_html_e( 'Ripple Duration', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="50" max="5000" data-cmxr-number data-cmxr-path="settings.ripple.duration_ms" value="<?php echo esc_attr( $cmxr_get( 'settings.ripple.duration_ms' ) ); ?>"></label>
						<label><?php esc_html_e( 'Ripple Radius', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="1" max="2000" data-cmxr-number data-cmxr-path="settings.ripple.max_radius" value="<?php echo esc_attr( $cmxr_get( 'settings.ripple.max_radius' ) ); ?>"></label>
						<label><?php esc_html_e( 'Ripple Opacity', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="1" step="0.01" data-cmxr-number data-cmxr-path="settings.ripple.alpha" value="<?php echo esc_attr( $cmxr_get( 'settings.ripple.alpha' ) ); ?>"></label>
					</div>
				<?php else : ?>
					<h4><?php esc_html_e( 'Position Bias', 'cmxr-canvas-motion-backgrounds' ); ?></h4>
					<div class="cmxr-proc-grid-2">
						<label><?php esc_html_e( 'Desktop X', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="1" step="0.01" data-cmxr-number data-cmxr-path="settings.bounds.desktop.origin_x" value="<?php echo esc_attr( $cmxr_get( 'settings.bounds.desktop.origin_x' ) ); ?>"></label>
						<label><?php esc_html_e( 'Desktop Y', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="1" step="0.01" data-cmxr-number data-cmxr-path="settings.bounds.desktop.origin_y" value="<?php echo esc_attr( $cmxr_get( 'settings.bounds.desktop.origin_y' ) ); ?>"></label>
						<label><?php esc_html_e( 'Tablet X', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="1" step="0.01" data-cmxr-number data-cmxr-path="settings.bounds.tablet.origin_x" value="<?php echo esc_attr( $cmxr_get( 'settings.bounds.tablet.origin_x' ) ); ?>"></label>
						<label><?php esc_html_e( 'Tablet Y', 'cmxr-canvas-motion-backgrounds' ); ?><input type="number" min="0" max="1" step="0.01" data-cmxr-number data-cmxr-path="settings.bounds.tablet.origin_y" value="<?php echo esc_attr( $cmxr_get( 'settings.bounds.tablet.origin_y' ) ); ?>"></label>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
