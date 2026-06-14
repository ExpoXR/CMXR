<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_Admin {

	public function add_menu_pages() {
		add_menu_page(
			__( 'CMXR', 'cmxr-canvas-motion-backgrounds' ),
			__( 'CMXR', 'cmxr-canvas-motion-backgrounds' ),
			'edit_posts',
			'cmxr',
			array( new CMXR_Dashboard(), 'render' ),
			'dashicons-art',
			30
		);

		add_submenu_page(
			'cmxr',
			__( 'Animations', 'cmxr-canvas-motion-backgrounds' ),
			__( 'Animations', 'cmxr-canvas-motion-backgrounds' ),
			'edit_posts',
			'cmxr'
			// No callback — same slug as parent, parent's callback handles rendering
		);

		add_submenu_page(
			'cmxr',
			__( 'New Animation', 'cmxr-canvas-motion-backgrounds' ),
			__( 'New Animation', 'cmxr-canvas-motion-backgrounds' ),
			'edit_posts',
			'cmxr-new',
			array( new CMXR_Configurator(), 'render' )
		);

		add_submenu_page(
			'cmxr',
			__( 'Settings', 'cmxr-canvas-motion-backgrounds' ),
			__( 'Settings', 'cmxr-canvas-motion-backgrounds' ),
			'manage_options',
			'cmxr-settings',
			array( new CMXR_Settings(), 'render' )
		);

		add_submenu_page(
			'cmxr',
			__( 'ExploreXR', 'cmxr-canvas-motion-backgrounds' ),
			__( 'ExploreXR', 'cmxr-canvas-motion-backgrounds' ),
			'edit_posts',
			'cmxr-explorexr',
			array( new CMXR_ExploreXR(), 'render' )
		);

		// Hidden page for editing an existing animation (no submenu entry)
		add_submenu_page(
			'',
			__( 'Edit Animation', 'cmxr-canvas-motion-backgrounds' ),
			'',
			'edit_posts',
			'cmxr-edit',
			array( new CMXR_Configurator(), 'render' )
		);
	}

	/**
	 * Admin page hook suffixes (= current_screen IDs) owned by CMXR.
	 */
	private static function page_hooks() {
		return array(
			'toplevel_page_cmxr',
			'cmxr_page_cmxr-new',
			'cmxr_page_cmxr-settings',
			'cmxr_page_cmxr-explorexr',
			'admin_page_cmxr-edit',
		);
	}

	/**
	 * Hide admin notices emitted by WordPress core and other plugins on
	 * CMXR's own admin screens to keep the branded UI clean. CMXR's
	 * Settings feedback is rendered via settings_errors() directly in the
	 * template, so it is unaffected.
	 */
	public function suppress_foreign_notices() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, self::page_hooks(), true ) ) return;

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
	}

	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, self::page_hooks(), true ) ) return;

		wp_enqueue_style(
			'cmxr-admin',
			CMXR_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			CMXR_VERSION
		);

		// Shared rendering core (math + canvas helpers) used by previews
		wp_register_script(
			'cmxr-core',
			CMXR_PLUGIN_URL . 'public/js/cmxr-core.js',
			array(),
			CMXR_VERSION,
			true
		);

		wp_enqueue_script(
			'cmxr-admin',
			CMXR_PLUGIN_URL . 'admin/js/admin.js',
			array( 'wp-api-fetch', 'cmxr-core' ),
			CMXR_VERSION,
			true
		);

		$settings = get_option( 'cmxr_settings', array() );
		wp_localize_script( 'cmxr-admin', 'CMXRAdmin', array(
			'restUrl'   => esc_url_raw( rest_url( 'cmxr/v1' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'version'   => CMXR_VERSION,
			'debugMode' => ! empty( $settings['debug_mode'] ),
			'wpDebug'   => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'scriptDebug' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			'strings'   => array(
				'save'                  => __( 'Save', 'cmxr-canvas-motion-backgrounds' ),
				'saving'                => __( 'Saving...', 'cmxr-canvas-motion-backgrounds' ),
				'saved'                 => __( 'Saved', 'cmxr-canvas-motion-backgrounds' ),
				'savedStatus'           => __( 'Saved!', 'cmxr-canvas-motion-backgrounds' ),
				'error'                 => __( 'Error', 'cmxr-canvas-motion-backgrounds' ),
				'errorSaving'           => __( 'Error saving.', 'cmxr-canvas-motion-backgrounds' ),
				'newAnimation'          => __( 'New Animation', 'cmxr-canvas-motion-backgrounds' ),
				'copied'                => __( 'Copied:', 'cmxr-canvas-motion-backgrounds' ),
				'animationActivated'    => __( 'Animation activated.', 'cmxr-canvas-motion-backgrounds' ),
				'animationDeactivated'  => __( 'Animation deactivated.', 'cmxr-canvas-motion-backgrounds' ),
				'active'                => __( 'Active', 'cmxr-canvas-motion-backgrounds' ),
				'inactive'              => __( 'Inactive', 'cmxr-canvas-motion-backgrounds' ),
				'duplicatedReloading'   => __( 'Duplicated. Reloading...', 'cmxr-canvas-motion-backgrounds' ),
				'animationDeleted'      => __( 'Animation deleted.', 'cmxr-canvas-motion-backgrounds' ),
				'hideConfig'            => __( 'Hide Config', 'cmxr-canvas-motion-backgrounds' ),
				'showConfig'            => __( 'Show Config', 'cmxr-canvas-motion-backgrounds' ),
				'custom'                => __( 'Custom', 'cmxr-canvas-motion-backgrounds' ),
				'fill'                  => __( 'Fill', 'cmxr-canvas-motion-backgrounds' ),
			),
		) );

		// Configurator pages get extra assets
		if ( in_array( $hook, array( 'cmxr_page_cmxr-new', 'admin_page_cmxr-edit' ), true ) ) {
			wp_enqueue_style(
				'cmxr-configurator',
				CMXR_PLUGIN_URL . 'admin/css/configurator.css',
				array( 'cmxr-admin', 'wp-color-picker' ),
				CMXR_VERSION
			);

			wp_enqueue_script(
				'cmxr-configurator',
				CMXR_PLUGIN_URL . 'admin/js/configurator.js',
				array( 'cmxr-admin', 'cmxr-core', 'wp-color-picker', 'jquery-ui-sortable' ),
				CMXR_VERSION,
				true
			);
		}
	}
}
