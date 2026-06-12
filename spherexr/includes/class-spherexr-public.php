<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SphereXR_Public {

	public function enqueue_detect_script() {
		$configs = SphereXR_CPT::get_active_configs();
		if ( empty( $configs ) ) return;

		wp_register_script(
			'spherexr-detect',
			SPHEREXR_PLUGIN_URL . 'public/js/spherexr-detect.js',
			array(),
			SPHEREXR_VERSION,
			true
		);

		wp_enqueue_script( 'spherexr-detect' );
	}

	public function output_config_json() {
		$configs = SphereXR_CPT::get_active_configs();
		if ( empty( $configs ) ) return;

		$settings = get_option( 'spherexr_settings', array() );

		$payload = array(
			'animations' => $configs,
			'coreUrl'    => SPHEREXR_PLUGIN_URL . 'public/js/spherexr-core.js?ver=' . SPHEREXR_VERSION,
			'engineUrl'  => SPHEREXR_PLUGIN_URL . 'public/js/spherexr-engine.js?ver=' . SPHEREXR_VERSION,
			'cssUrl'     => SPHEREXR_PLUGIN_URL . 'public/css/spherexr.css?ver=' . SPHEREXR_VERSION,
			'settings'   => array(
				'dprCap'    => (float) ( $settings['dpr_cap'] ?? 1.75 ),
				'ioThresh'  => (float) ( $settings['intersection_threshold'] ?? 0.01 ),
				'debugMode' => ! empty( $settings['debug_mode'] ),
				'wpDebug'   => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'scriptDebug' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			),
		);

		echo '<script id="spherexr-config" type="application/json">';
		echo wp_json_encode( $payload );
		echo '</script>' . "\n";
	}
}
