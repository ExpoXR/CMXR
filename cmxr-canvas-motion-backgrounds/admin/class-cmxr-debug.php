<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_Debug {

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cmxr-canvas-motion-backgrounds' ) );
		}

		$posts = get_posts( array(
			'post_type'   => 'cmxr_animation',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
		) );

		$animations = array();
		foreach ( $posts as $post ) {
			$raw    = get_post_meta( $post->ID, '_cmxr_config', true );
			$config = $raw ? json_decode( $raw, true ) : array();
			$is_v2  = 2 === (int) ( $config['config_version'] ?? 1 );
			$counts = $is_v2 ? ( $config['settings']['counts'] ?? array() ) : array();
			$animations[] = array(
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'config'    => $config,
				'raw'       => $raw,
				'active'    => ! empty( $config['active'] ),
				'anim_id'   => CMXR_CPT::config_target( $config ),
				'orb_count' => $is_v2 ? (int) ( $counts['desktop'] ?? 0 ) : count( $config['orbs'] ?? array() ),
				'count_label' => $is_v2
					? sprintf( '%d / %d / %d', (int) ( $counts['mobile'] ?? 0 ), (int) ( $counts['tablet'] ?? 0 ), (int) ( $counts['desktop'] ?? 0 ) )
					: (string) count( $config['orbs'] ?? array() ),
				'effect_type' => $is_v2 ? ( $config['effect_type'] ?? '' ) : 'layered-shapes',
			);
		}

		$system = array(
			'php_version' => PHP_VERSION,
			'wp_version'  => get_bloginfo( 'version' ),
			'theme'       => get_stylesheet(),
			'plugin_ver'  => CMXR_VERSION,
			'engine_url'  => CMXR_PLUGIN_URL . 'public/js/cmxr-engine.js',
			'renderers_url' => CMXR_PLUGIN_URL . 'public/js/cmxr-renderers.js',
			'detect_url'  => CMXR_PLUGIN_URL . 'public/js/cmxr-detect.js',
			'css_url'     => CMXR_PLUGIN_URL . 'public/css/cmxr.css',
			'rest_url'    => rest_url( 'cmxr/v1' ),
			'settings'    => get_option( 'cmxr_settings', array() ),
		);

		include CMXR_PLUGIN_DIR . 'templates/admin/debug.php';
	}
}
