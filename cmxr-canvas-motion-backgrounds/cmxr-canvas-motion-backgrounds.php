<?php
/**
 * Plugin Name:       CMXR — Canvas Motion Backgrounds
 * Plugin URI:        https://expoxr.com/
 * Description:       Create animated HTML5 canvas motion backgrounds with shapes, orbs, blobs, and interactive effects for WordPress, Elementor, Gutenberg, and any theme with a visual editor.
 * Version:           1.0.1
 * Author:            Ayal Othman
 * Author URI:        https://expoxr.com
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cmxr-canvas-motion-backgrounds
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CMXR_VERSION', '1.0.1' );
define( 'CMXR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CMXR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CMXR_PLUGIN_FILE', __FILE__ );

require_once CMXR_PLUGIN_DIR . 'includes/class-cmxr-activator.php';
require_once CMXR_PLUGIN_DIR . 'includes/class-cmxr-deactivator.php';

register_activation_hook( __FILE__, array( 'CMXR_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CMXR_Deactivator', 'deactivate' ) );

require_once CMXR_PLUGIN_DIR . 'includes/class-cmxr-loader.php';

function cmxr_run() {
	$plugin = new CMXR_Loader();
	$plugin->run();
}

cmxr_run();
