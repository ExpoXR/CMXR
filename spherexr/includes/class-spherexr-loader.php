<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SphereXR_Loader {

	public function run() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once SPHEREXR_PLUGIN_DIR . 'includes/class-spherexr-i18n.php';
		require_once SPHEREXR_PLUGIN_DIR . 'includes/class-spherexr-schema.php';
		require_once SPHEREXR_PLUGIN_DIR . 'includes/class-spherexr-cpt.php';
		require_once SPHEREXR_PLUGIN_DIR . 'includes/class-spherexr-rest.php';
		require_once SPHEREXR_PLUGIN_DIR . 'includes/class-spherexr-public.php';
		require_once SPHEREXR_PLUGIN_DIR . 'admin/class-spherexr-admin.php';
		require_once SPHEREXR_PLUGIN_DIR . 'admin/class-spherexr-dashboard.php';
		require_once SPHEREXR_PLUGIN_DIR . 'admin/class-spherexr-configurator.php';
		require_once SPHEREXR_PLUGIN_DIR . 'admin/class-spherexr-settings.php';
		require_once SPHEREXR_PLUGIN_DIR . 'admin/class-spherexr-debug.php';
		require_once SPHEREXR_PLUGIN_DIR . 'admin/class-spherexr-explorexr.php';
	}

	private function set_locale() {
		$i18n = new SphereXR_i18n();
		add_action( 'plugins_loaded', array( $i18n, 'load_plugin_textdomain' ) );
	}

	private function define_admin_hooks() {
		$admin = new SphereXR_Admin();
		add_action( 'admin_menu', array( $admin, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) );

		$cpt = new SphereXR_CPT();
		add_action( 'init', array( $cpt, 'register' ) );

		$rest = new SphereXR_REST();
		add_action( 'rest_api_init', array( $rest, 'register_routes' ) );
	}

	private function define_public_hooks() {
		$public = new SphereXR_Public();
		add_action( 'wp_footer', array( $public, 'output_config_json' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_detect_script' ), 20 );
	}
}
