<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Loads the plugin text domain for translations.
 */
class CMXR_i18n {

	public function load_textdomain() {
		load_plugin_textdomain(
			'cmxr-canvas-motion-backgrounds',
			false,
			dirname( plugin_basename( CMXR_PLUGIN_FILE ) ) . '/languages'
		);
	}
}
