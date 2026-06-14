<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_REST {

	const NAMESPACE = 'cmxr/v1';

	public function register_routes() {
		register_rest_route( self::NAMESPACE, '/animations', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_animations' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_animation' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( self::NAMESPACE, '/animations/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_animation' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_animation' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_animation' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( self::NAMESPACE, '/animations/(?P<id>\d+)/duplicate', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'duplicate_animation' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE, '/animations/(?P<id>\d+)/toggle', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'toggle_active' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	public function check_permission() {
		return current_user_can( 'edit_posts' );
	}

	public function get_animations( $request ) {
		$posts = get_posts( array(
			'post_type'   => 'cmxr_animation',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		$data = array();
		foreach ( $posts as $post ) {
			$data[] = $this->format_list_item( $post );
		}

		return rest_ensure_response( $data );
	}

	public function get_animation( $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post || $post->post_type !== 'cmxr_animation' ) {
			return new WP_Error( 'not_found', __( 'Animation not found.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 404 ) );
		}

		$raw    = get_post_meta( $post->ID, '_cmxr_config', true );
		$config = $raw ? json_decode( $raw, true ) : array();

		return rest_ensure_response( array(
			'id'     => $post->ID,
			'title'  => $post->post_title,
			'status' => $post->post_status,
			'config' => $config ?: new stdClass(),
		) );
	}

	public function create_animation( $request ) {
		$params = $request->get_json_params();
		$title  = sanitize_text_field( $params['title'] ?? '' );
		if ( ! $title ) {
			return new WP_Error( 'missing_title', __( 'Title is required.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 400 ) );
		}

		$settings = get_option( 'cmxr_settings', array() );

		$default_config = array(
			'animation_id' => sanitize_title( $title ),
			'active'       => true,
			'global'       => array(
				'speed'       => (float) ( $settings['default_speed'] ?? 1.0 ),
				'safe_margin' => (int) ( $settings['default_safe_margin'] ?? 5 ),
				'blend_mode'  => $settings['default_blend_mode'] ?? 'screen',
				'interactivity' => array(
					'enabled'  => true,
					'mode'     => 'parallax',
					'strength' => 0.5,
					'radius'   => 30,
				),
			),
			'orbs' => array(),
		);

		$post_id = wp_insert_post( array(
			'post_title'  => $title,
			'post_type'   => 'cmxr_animation',
			'post_status' => 'publish',
		), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_cmxr_config', wp_json_encode( $default_config ) );

		return rest_ensure_response( array(
			'id'     => $post_id,
			'title'  => $title,
			'status' => 'publish',
			'config' => $default_config,
		) );
	}

	public function update_animation( $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post || $post->post_type !== 'cmxr_animation' ) {
			return new WP_Error( 'not_found', __( 'Animation not found.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 404 ) );
		}

		$params = $request->get_json_params();

		if ( isset( $params['title'] ) ) {
			wp_update_post( array(
				'ID'         => $post->ID,
				'post_title' => sanitize_text_field( $params['title'] ),
			) );
		}

		if ( isset( $params['config'] ) ) {
			$clean = CMXR_CPT::sanitize_config( $params['config'] );
			if ( ! $clean ) {
				return new WP_Error( 'invalid_config', __( 'Invalid configuration.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 400 ) );
			}
			update_post_meta( $post->ID, '_cmxr_config', wp_json_encode( $clean ) );
		}

		return $this->get_animation( $request );
	}

	public function delete_animation( $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post || $post->post_type !== 'cmxr_animation' ) {
			return new WP_Error( 'not_found', __( 'Animation not found.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 404 ) );
		}

		wp_delete_post( $post->ID, true );

		return rest_ensure_response( array( 'deleted' => true, 'id' => (int) $request['id'] ) );
	}

	public function duplicate_animation( $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post || $post->post_type !== 'cmxr_animation' ) {
			return new WP_Error( 'not_found', __( 'Animation not found.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 404 ) );
		}

		$raw    = get_post_meta( $post->ID, '_cmxr_config', true );
		$config = $raw ? json_decode( $raw, true ) : array();

		$new_title = $post->post_title . ' (Copy)';
		$new_id    = wp_insert_post( array(
			'post_title'  => $new_title,
			'post_type'   => 'cmxr_animation',
			'post_status' => 'publish',
		), true );

		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		if ( $config ) {
			// Suffix with the new post ID so repeated duplicates get unique IDs.
			$config['animation_id'] = sanitize_title( $post->post_title ) . '-copy-' . $new_id;
			// Re-validate the copied config so stale/invalid data can't propagate.
			$clean = CMXR_CPT::sanitize_config( $config );
			update_post_meta( $new_id, '_cmxr_config', wp_json_encode( $clean ?: $config ) );
		}

		return rest_ensure_response( array(
			'id'     => $new_id,
			'title'  => $new_title,
			'status' => 'publish',
		) );
	}

	public function toggle_active( $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post || $post->post_type !== 'cmxr_animation' ) {
			return new WP_Error( 'not_found', __( 'Animation not found.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 404 ) );
		}

		$raw    = get_post_meta( $post->ID, '_cmxr_config', true );
		$config = $raw ? json_decode( $raw, true ) : array();
		if ( ! is_array( $config ) ) $config = array();

		$config['active'] = empty( $config['active'] );
		// Re-validate when possible so a toggle can't persist malformed config.
		$clean = CMXR_CPT::sanitize_config( $config );
		if ( $clean ) $config = $clean;
		update_post_meta( $post->ID, '_cmxr_config', wp_json_encode( $config ) );

		return rest_ensure_response( array(
			'id'     => $post->ID,
			'active' => $config['active'],
		) );
	}

	private function format_list_item( $post ) {
		$raw    = get_post_meta( $post->ID, '_cmxr_config', true );
		$config = $raw ? json_decode( $raw, true ) : array();

		return array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'animation_id' => $config['animation_id'] ?? '',
			'active'       => ! empty( $config['active'] ),
			'orb_count'    => count( $config['orbs'] ?? array() ),
			'status'       => $post->post_status,
		);
	}
}
