<?php
/**
 * Plugin Name:       SphereXR
 * Plugin URI:        https://expoxr.com/
 * Description:       Create and manage canvas-based orb background animations. Attach to any Elementor section by CSS ID.
 * Version:           1.1.0
 * Author:            Ayal Othman
 * Author URI:        https://expoxr.com
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       spherexr
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SPHEREXR_VERSION', '1.1.0' );
define( 'SPHEREXR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPHEREXR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SPHEREXR_PLUGIN_FILE', __FILE__ );

require_once SPHEREXR_PLUGIN_DIR . 'includes/class-spherexr-activator.php';
require_once SPHEREXR_PLUGIN_DIR . 'includes/class-spherexr-deactivator.php';

register_activation_hook( __FILE__, array( 'SphereXR_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SphereXR_Deactivator', 'deactivate' ) );

require_once SPHEREXR_PLUGIN_DIR . 'includes/class-spherexr-loader.php';

function spherexr_run() {
	$plugin = new SphereXR_Loader();
	$plugin->run();
}

spherexr_run();
