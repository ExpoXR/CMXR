<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_Deactivator {

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
