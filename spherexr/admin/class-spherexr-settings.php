<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SphereXR_Settings {

	const OPTION_KEY = 'spherexr_settings';

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		register_setting(
			'spherexr_settings_group',
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);

		add_settings_section(
			'spherexr_performance',
			__( 'Performance', 'spherexr' ),
			null,
			'spherexr-settings'
		);

		add_settings_field(
			'dpr_cap',
			__( 'Device Pixel Ratio Cap', 'spherexr' ),
			array( $this, 'field_dpr_cap' ),
			'spherexr-settings',
			'spherexr_performance'
		);

		add_settings_field(
			'intersection_threshold',
			__( 'IntersectionObserver Threshold', 'spherexr' ),
			array( $this, 'field_io_threshold' ),
			'spherexr-settings',
			'spherexr_performance'
		);

		add_settings_section(
			'spherexr_defaults',
			__( 'Default Animation Values', 'spherexr' ),
			null,
			'spherexr-settings'
		);

		add_settings_field(
			'default_speed',
			__( 'Default Speed', 'spherexr' ),
			array( $this, 'field_default_speed' ),
			'spherexr-settings',
			'spherexr_defaults'
		);

		add_settings_field(
			'default_safe_margin',
			__( 'Default Safe Margin (%)', 'spherexr' ),
			array( $this, 'field_default_safe_margin' ),
			'spherexr-settings',
			'spherexr_defaults'
		);

		add_settings_field(
			'default_blend_mode',
			__( 'Default Blend Mode', 'spherexr' ),
			array( $this, 'field_default_blend_mode' ),
			'spherexr-settings',
			'spherexr_defaults'
		);

		add_settings_section(
			'spherexr_debug_section',
			__( 'Developer', 'spherexr' ),
			null,
			'spherexr-settings'
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'spherexr' ),
			array( $this, 'field_debug_mode' ),
			'spherexr-settings',
			'spherexr_debug_section'
		);
	}

	public function sanitize( $input ) {
		$allowed_blend = SphereXR_Schema::BLEND_MODES;
		$blend_mode    = $input['default_blend_mode'] ?? 'screen';

		return array(
			'dpr_cap'                => max( 1.0, min( 3.0, (float) ( $input['dpr_cap'] ?? 1.75 ) ) ),
			'intersection_threshold' => max( 0.01, min( 1.0, (float) ( $input['intersection_threshold'] ?? 0.01 ) ) ),
			'debug_mode'             => ! empty( $input['debug_mode'] ),
			'default_speed'          => max( 0.1, min( 10.0, (float) ( $input['default_speed'] ?? 1.0 ) ) ),
			'default_safe_margin'    => max( 0, min( 30, (int) ( $input['default_safe_margin'] ?? 5 ) ) ),
			'default_blend_mode'     => in_array( $blend_mode, $allowed_blend, true ) ? $blend_mode : 'screen',
		);
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'spherexr' ) );
		}
		include SPHEREXR_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	public function field_dpr_cap() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = $opts['dpr_cap'] ?? 1.75;
		printf(
			'<input type="number" name="%s[dpr_cap]" value="%s" min="1" max="3" step="0.25" class="small-text">
			<p class="description">%s</p>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val ),
			esc_html__( 'Cap device pixel ratio to limit canvas size on high-density screens. Recommended: 1.75.', 'spherexr' )
		);
	}

	public function field_io_threshold() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = $opts['intersection_threshold'] ?? 0.01;
		printf(
			'<input type="number" name="%s[intersection_threshold]" value="%s" min="0.01" max="1" step="0.01" class="small-text">
			<p class="description">%s</p>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val ),
			esc_html__( 'Visibility fraction required before animation starts. 0.01 = start when 1% visible.', 'spherexr' )
		);
	}

	public function field_default_speed() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = $opts['default_speed'] ?? 1.0;
		printf(
			'<input type="number" name="%s[default_speed]" value="%s" min="0.1" max="10" step="0.1" class="small-text">',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val )
		);
	}

	public function field_default_safe_margin() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = $opts['default_safe_margin'] ?? 5;
		printf(
			'<input type="number" name="%s[default_safe_margin]" value="%s" min="0" max="30" step="1" class="small-text"> %%',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val )
		);
	}

	public function field_default_blend_mode() {
		$opts    = get_option( self::OPTION_KEY, array() );
		$current = $opts['default_blend_mode'] ?? 'screen';
		$modes   = SphereXR_Schema::BLEND_MODES;
		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[default_blend_mode]">';
		foreach ( $modes as $mode ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $mode ),
				selected( $current, $mode, false ),
				esc_html( $mode )
			);
		}
		echo '</select>';
	}

	public function field_debug_mode() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = ! empty( $opts['debug_mode'] );
		printf(
			'<input type="checkbox" name="%s[debug_mode]" value="1" %s>
			<label>%s</label>',
			esc_attr( self::OPTION_KEY ),
			checked( $val, true, false ),
			esc_html__( 'Output engine debug info to browser console.', 'spherexr' )
		);
	}
}
