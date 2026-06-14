<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_Activator {

	public static function activate() {
		// Register CPT so flush_rewrite_rules works
		require_once CMXR_PLUGIN_DIR . 'includes/class-cmxr-cpt.php';
		CMXR_CPT::register();
		flush_rewrite_rules();

		// Set default options if not present (autoload disabled — read on demand)
		if ( ! get_option( 'cmxr_settings' ) ) {
			add_option( 'cmxr_settings', array(
				'dpr_cap'                 => 1.75,
				'intersection_threshold'  => 0.01,
				'debug_mode'              => false,
				'default_speed'           => 1.0,
				'default_safe_margin'     => 5,
				'default_blend_mode'      => 'screen',
			), '', 'no' );
		}
	}
}
