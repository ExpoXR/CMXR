<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_Public {

	public function enqueue_detect_script() {
		$configs = CMXR_CPT::get_active_configs();
		if ( empty( $configs ) ) return;

		wp_register_script(
			'cmxr-detect',
			CMXR_PLUGIN_URL . 'public/js/cmxr-detect.js',
			array(),
			CMXR_VERSION,
			true
		);

		wp_enqueue_script( 'cmxr-detect' );
	}

	public function output_config_json() {
		$configs = CMXR_CPT::get_active_configs();
		if ( empty( $configs ) ) return;

		$settings = get_option( 'cmxr_settings', array() );

		$payload = array(
			'animations' => $configs,
			'coreUrl'    => CMXR_PLUGIN_URL . 'public/js/cmxr-core.js?ver=' . CMXR_VERSION,
			'engineUrl'  => CMXR_PLUGIN_URL . 'public/js/cmxr-engine.js?ver=' . CMXR_VERSION,
			'cssUrl'     => CMXR_PLUGIN_URL . 'public/css/cmxr.css?ver=' . CMXR_VERSION,
			'settings'   => array(
				'dprCap'    => (float) ( $settings['dpr_cap'] ?? 1.75 ),
				'ioThresh'  => (float) ( $settings['intersection_threshold'] ?? 0.01 ),
				'debugMode' => ! empty( $settings['debug_mode'] ),
				'wpDebug'   => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'scriptDebug' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			),
		);

		echo '<script id="cmxr-config" type="application/json">';
		echo wp_json_encode( $payload );
		echo '</script>' . "\n";
	}
}
