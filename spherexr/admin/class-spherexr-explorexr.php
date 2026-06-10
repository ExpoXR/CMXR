<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * "ExploreXR" promo page — showcases the ExploreXR plugin (Free and Premium)
 * from the ExpoXR family. Static HTML only, no remote calls.
 */
class SphereXR_ExploreXR {

	public function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'spherexr' ) );
		}

		include SPHEREXR_PLUGIN_DIR . 'templates/admin/explorexr.php';
	}
}
