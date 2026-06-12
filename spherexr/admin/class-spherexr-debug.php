<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SphereXR_Debug {

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'spherexr' ) );
		}

		if ( isset( $_GET['action'] ) && 'export' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified below.
			check_admin_referer( 'spherexr_export' );
			$this->stream_export();
			return;
		}

		$posts = get_posts( array(
			'post_type'   => 'spherexr_animation',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
		) );

		$animations = array();
		foreach ( $posts as $post ) {
			$raw    = get_post_meta( $post->ID, '_spherexr_config', true );
			$config = $raw ? json_decode( $raw, true ) : array();
			$animations[] = array(
				'id'        => $post->ID,
				'title'     => $post->post_title,
				'config'    => $config,
				'raw'       => $raw,
				'active'    => ! empty( $config['active'] ),
				'anim_id'   => $config['animation_id'] ?? '',
				'orb_count' => count( $config['orbs'] ?? array() ),
			);
		}

		$system = array(
			'php_version' => PHP_VERSION,
			'wp_version'  => get_bloginfo( 'version' ),
			'theme'       => get_stylesheet(),
			'plugin_ver'  => SPHEREXR_VERSION,
			'engine_url'  => SPHEREXR_PLUGIN_URL . 'public/js/spherexr-engine.js',
			'detect_url'  => SPHEREXR_PLUGIN_URL . 'public/js/spherexr-detect.js',
			'css_url'     => SPHEREXR_PLUGIN_URL . 'public/css/spherexr.css',
			'rest_url'    => rest_url( 'spherexr/v1' ),
			'settings'    => get_option( 'spherexr_settings', array() ),
		);

		include SPHEREXR_PLUGIN_DIR . 'templates/admin/debug.php';
	}

	private function stream_export() {
		$posts = get_posts( array(
			'post_type'   => 'spherexr_animation',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
		) );

		$animations = array();
		foreach ( $posts as $post ) {
			$raw    = get_post_meta( $post->ID, '_spherexr_config', true );
			$config = $raw ? json_decode( $raw, true ) : array();
			$animations[] = array(
				'title'  => $post->post_title,
				'status' => $post->post_status,
				'config' => $config,
			);
		}

		$payload = array(
			'plugin'      => 'spherexr',
			'version'     => SPHEREXR_VERSION,
			'exported_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'animations'  => $animations,
		);

		$filename = 'spherexr-export-' . gmdate( 'Y-m-d' ) . '.json';
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded, headers sent, exit follows.
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}
}
