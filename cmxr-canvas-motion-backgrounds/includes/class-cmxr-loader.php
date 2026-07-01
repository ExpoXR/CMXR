<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_Loader {

	public function run() {
		$this->load_dependencies();
		$this->define_shared_hooks();
		if ( is_admin() ) {
			$this->define_admin_hooks();
		}
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once CMXR_PLUGIN_DIR . 'includes/class-cmxr-schema.php';
		require_once CMXR_PLUGIN_DIR . 'includes/class-cmxr-cpt.php';
		require_once CMXR_PLUGIN_DIR . 'includes/class-cmxr-rest.php';
		require_once CMXR_PLUGIN_DIR . 'includes/class-cmxr-public.php';

		if ( is_admin() ) {
			require_once CMXR_PLUGIN_DIR . 'admin/class-cmxr-admin.php';
			require_once CMXR_PLUGIN_DIR . 'admin/class-cmxr-dashboard.php';
			require_once CMXR_PLUGIN_DIR . 'admin/class-cmxr-configurator.php';
			require_once CMXR_PLUGIN_DIR . 'admin/class-cmxr-settings.php';
			require_once CMXR_PLUGIN_DIR . 'admin/class-cmxr-debug.php';
			require_once CMXR_PLUGIN_DIR . 'admin/class-cmxr-explorexr.php';
		}
	}

	/**
	 * Hooks needed on every request: CPT registration, REST routes, config cache busting.
	 */
	private function define_shared_hooks() {
		$cpt = new CMXR_CPT();
		add_action( 'init', array( $cpt, 'register' ) );

		$rest = new CMXR_REST();
		add_action( 'rest_api_init', array( $rest, 'register_routes' ) );

		// Bust the active-config transient whenever _cmxr_config meta is written.
		add_action( 'added_post_meta',   array( 'CMXR_CPT', 'bust_config_cache_on_meta' ), 10, 3 );
		add_action( 'updated_post_meta', array( 'CMXR_CPT', 'bust_config_cache_on_meta' ), 10, 3 );
		add_action( 'delete_post',       array( 'CMXR_CPT', 'bust_config_cache_on_delete' ) );
	}

	private function define_admin_hooks() {
		// Early instantiation so admin_post_* hooks register before admin_menu fires.
		new CMXR_Settings();

		$admin = new CMXR_Admin();
		add_action( 'admin_menu', array( $admin, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) );
		add_action( 'in_admin_header', array( $admin, 'suppress_foreign_notices' ), 1 );
	}

	private function define_public_hooks() {
		$public = new CMXR_Public();
		add_action( 'wp_footer', array( $public, 'output_config_json' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $public, 'enqueue_detect_script' ), 20 );
	}
}
