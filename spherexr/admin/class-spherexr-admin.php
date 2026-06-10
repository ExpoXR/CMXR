<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SphereXR_Admin {

	public function add_menu_pages() {
		add_menu_page(
			__( 'SphereXR', 'spherexr' ),
			__( 'SphereXR', 'spherexr' ),
			'edit_posts',
			'spherexr',
			array( new SphereXR_Dashboard(), 'render' ),
			'dashicons-art',
			30
		);

		add_submenu_page(
			'spherexr',
			__( 'Animations', 'spherexr' ),
			__( 'Animations', 'spherexr' ),
			'edit_posts',
			'spherexr'
			// No callback — same slug as parent, parent's callback handles rendering
		);

		add_submenu_page(
			'spherexr',
			__( 'New Animation', 'spherexr' ),
			__( 'New Animation', 'spherexr' ),
			'edit_posts',
			'spherexr-new',
			array( new SphereXR_Configurator(), 'render' )
		);

		add_submenu_page(
			'spherexr',
			__( 'Settings', 'spherexr' ),
			__( 'Settings', 'spherexr' ),
			'manage_options',
			'spherexr-settings',
			array( new SphereXR_Settings(), 'render' )
		);

		add_submenu_page(
			'spherexr',
			__( 'Debug', 'spherexr' ),
			__( 'Debug', 'spherexr' ),
			'manage_options',
			'spherexr-debug',
			array( new SphereXR_Debug(), 'render' )
		);

		add_submenu_page(
			'spherexr',
			__( 'ExploreXR', 'spherexr' ),
			__( 'ExploreXR', 'spherexr' ),
			'edit_posts',
			'spherexr-explorexr',
			array( new SphereXR_ExploreXR(), 'render' )
		);

		// Hidden page for editing an existing animation (no submenu entry)
		add_submenu_page(
			'',
			__( 'Edit Animation', 'spherexr' ),
			'',
			'edit_posts',
			'spherexr-edit',
			array( new SphereXR_Configurator(), 'render' )
		);
	}

	public function enqueue_assets( $hook ) {
		$spherexr_pages = array(
			'toplevel_page_spherexr',
			'spherexr_page_spherexr-new',
			'spherexr_page_spherexr-settings',
			'spherexr_page_spherexr-debug',
			'spherexr_page_spherexr-explorexr',
			'admin_page_spherexr-edit',
		);

		if ( ! in_array( $hook, $spherexr_pages, true ) ) return;

		wp_enqueue_style(
			'spherexr-admin',
			SPHEREXR_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			SPHEREXR_VERSION
		);

		// Shared rendering core (math + canvas helpers) used by previews
		wp_register_script(
			'spherexr-core',
			SPHEREXR_PLUGIN_URL . 'public/js/spherexr-core.js',
			array(),
			SPHEREXR_VERSION,
			true
		);

		wp_enqueue_script(
			'spherexr-admin',
			SPHEREXR_PLUGIN_URL . 'admin/js/admin.js',
			array( 'wp-api-fetch', 'spherexr-core' ),
			SPHEREXR_VERSION,
			true
		);

		wp_localize_script( 'spherexr-admin', 'SphereXRAdmin', array(
			'restUrl' => esc_url_raw( rest_url( 'spherexr/v1' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'version' => SPHEREXR_VERSION,
		) );

		// Configurator pages get extra assets
		if ( in_array( $hook, array( 'spherexr_page_spherexr-new', 'admin_page_spherexr-edit' ), true ) ) {
			wp_enqueue_style(
				'spherexr-configurator',
				SPHEREXR_PLUGIN_URL . 'admin/css/configurator.css',
				array( 'spherexr-admin', 'wp-color-picker' ),
				SPHEREXR_VERSION
			);

			wp_enqueue_script(
				'spherexr-configurator',
				SPHEREXR_PLUGIN_URL . 'admin/js/configurator.js',
				array( 'spherexr-admin', 'spherexr-core', 'wp-color-picker', 'jquery-ui-sortable' ),
				SPHEREXR_VERSION,
				true
			);
		}
	}
}
