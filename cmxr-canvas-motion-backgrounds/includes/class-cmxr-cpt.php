<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_CPT {

	public static function register() {
		$labels = array(
			'name'          => __( 'CMXR Animations', 'cmxr-canvas-motion-backgrounds' ),
			'singular_name' => __( 'Animation', 'cmxr-canvas-motion-backgrounds' ),
			'add_new_item'  => __( 'Add New Animation', 'cmxr-canvas-motion-backgrounds' ),
			'edit_item'     => __( 'Edit Animation', 'cmxr-canvas-motion-backgrounds' ),
			'search_items'  => __( 'Search Animations', 'cmxr-canvas-motion-backgrounds' ),
			'not_found'     => __( 'No animations found.', 'cmxr-canvas-motion-backgrounds' ),
		);

		register_post_type( 'cmxr_animation', array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_rest'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'rewrite'             => false,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			// Map meta caps (edit_post/read_post/delete_post) to primitive post caps
			// so REST permission callbacks can enforce per-object capabilities.
			'map_meta_cap'        => true,
		) );
	}

	/**
	 * Return all active animation configs as array.
	 * Cached per-request (static) and across requests (transient).
	 */
	public static function get_active_configs() {
		static $cache = null;
		if ( null !== $cache ) return $cache;

		$transient = get_transient( 'cmxr_active_configs' );
		if ( false !== $transient ) {
			$cache = $transient;
			return $cache;
		}

		$posts = get_posts( array(
			'post_type'              => 'cmxr_animation',
			'post_status'            => 'publish',
			'numberposts'            => -1,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		) );

		$configs = array();
		foreach ( $posts as $post ) {
			$raw = get_post_meta( $post->ID, '_cmxr_config', true );
			if ( ! $raw ) continue;
			$config = json_decode( $raw, true );
			if ( ! is_array( $config ) ) continue;
			if ( empty( $config['active'] ) ) continue;
			if ( empty( $config['animation_id'] ) ) continue;
			$configs[] = $config;
		}

		set_transient( 'cmxr_active_configs', $configs, HOUR_IN_SECONDS );
		$cache = $configs;
		return $cache;
	}

	/** Bust transient when _cmxr_config meta is written. */
	public static function bust_config_cache_on_meta( $meta_id, $post_id, $meta_key ) {
		if ( '_cmxr_config' === $meta_key ) {
			delete_transient( 'cmxr_active_configs' );
		}
	}

	/** Bust transient when a cmxr_animation post is deleted. */
	public static function bust_config_cache_on_delete( $post_id ) {
		if ( get_post_type( $post_id ) === 'cmxr_animation' ) {
			delete_transient( 'cmxr_active_configs' );
		}
	}

	/**
	 * Sanitize and validate a config array before saving.
	 */
	public static function sanitize_config( $raw ) {
		if ( ! is_array( $raw ) ) return false;

		$allowed_blend  = CMXR_Schema::BLEND_MODES;
		$allowed_modes  = CMXR_Schema::INTERACTIVITY_MODES;
		$allowed_shapes = CMXR_Schema::SHAPES;
		$allowed_anims  = CMXR_Schema::ANIM_TYPES;
		$allowed_units  = CMXR_Schema::UNITS;
		$allowed_cmodes = CMXR_Schema::COLOR_MODES;
		$allowed_canims = CMXR_Schema::COLOR_ANIMATIONS;
		$allowed_dirs   = CMXR_Schema::INTERACTION_DIRECTIONS;

		$animation_id = sanitize_title( $raw['animation_id'] ?? '' );
		if ( ! $animation_id ) return false;

		$global = $raw['global'] ?? array();
		$interactivity = $global['interactivity'] ?? array();


		$config = array(
			'animation_id' => $animation_id,
			'active'       => ! empty( $raw['active'] ),
			'global'       => array(
				'speed'       => self::clamp_float( $global['speed'] ?? 1.0, 0.1, 10.0 ),
				'safe_margin' => self::clamp_int( $global['safe_margin'] ?? 5, 0, 30 ),
				'blend_mode'  => self::sanitize_enum( $global['blend_mode'] ?? 'normal', $allowed_blend, 'normal' ),
				'preview_bg'  => self::sanitize_preview_bg( $global['preview_bg'] ?? 'transparent' ),
				'preview_w'   => self::sanitize_preview_dim( $global['preview_w'] ?? null, 3000 ),
				'preview_h'   => self::sanitize_preview_dim( $global['preview_h'] ?? null, 2000 ),
				'interactivity' => array(
					'enabled'  => ! empty( $interactivity['enabled'] ),
					'mode'     => self::sanitize_enum( $interactivity['mode'] ?? 'parallax', $allowed_modes, 'parallax' ),
					'strength' => self::clamp_float( $interactivity['strength'] ?? 0.5, 0.0, 1.0 ),
					'radius'   => self::clamp_int( $interactivity['radius'] ?? 30, 5, 80 ),
				),
			),
			'orbs' => array(),
		);

		$raw_orbs = array_slice( (array) ( $raw['orbs'] ?? array() ), 0, 20 );
		foreach ( $raw_orbs as $orb ) {
			$anim = $orb['animation'] ?? array();
			$size = $orb['size'] ?? array();
			$pos  = $orb['position'] ?? array();
			$color_stops = self::sanitize_color_stops(
				$orb['color_stops'] ?? array(),
				$orb['color'] ?? '#38a3d7',
				$orb['color_b'] ?? '#8bb84a'
			);

			$sanitized_orb = array(
				'id'         => sanitize_key( $orb['id'] ?? uniqid( 'o' ) ),
				'shape'      => self::sanitize_enum( $orb['shape'] ?? 'circle', $allowed_shapes, 'circle' ),
				'color'      => sanitize_hex_color( $orb['color'] ?? '#38a3d7' ) ?: '#38a3d7',
				'color_mode' => self::sanitize_enum( $orb['color_mode'] ?? 'solid', $allowed_cmodes, 'solid' ),
				'color_b'    => sanitize_hex_color( $orb['color_b'] ?? '' ) ?: '',
				'color_stops' => $color_stops,
				'color_animation' => self::sanitize_enum( $orb['color_animation'] ?? 'none', $allowed_canims, 'none' ),
				'size'       => array(
					'w'    => self::clamp_float( $size['w'] ?? 40, 1, 200 ),
					'h'    => self::clamp_float( $size['h'] ?? 40, 1, 200 ),
					'unit' => self::sanitize_enum( $size['unit'] ?? 'percent', $allowed_units, 'percent' ),
				),
				'position' => array(
					'x'    => self::clamp_float( $pos['x'] ?? 50, 0, 100 ),
					'y'    => self::clamp_float( $pos['y'] ?? 50, 0, 100 ),
					'unit' => self::sanitize_enum( $pos['unit'] ?? 'percent', $allowed_units, 'percent' ),
				),
				'blur'    => self::clamp_int( $orb['blur'] ?? 72, 0, 200 ),
				'opacity' => self::clamp_float( $orb['opacity'] ?? 0.8, 0.0, 1.0 ),
				'animation' => array(
					'type'        => self::sanitize_enum( $anim['type'] ?? 'drift', $allowed_anims, 'drift' ),
					'amplitude_x' => self::clamp_float( $anim['amplitude_x'] ?? 5, 0, 50 ),
					'amplitude_y' => self::clamp_float( $anim['amplitude_y'] ?? 5, 0, 50 ),
					'frequency_x' => self::clamp_float( $anim['frequency_x'] ?? 0.4, 0.05, 5.0 ),
					'frequency_y' => self::clamp_float( $anim['frequency_y'] ?? 0.5, 0.05, 5.0 ),
					'phase'       => self::clamp_float( $anim['phase'] ?? 0.0, 0.0, 6.2832 ),
				),
				'parallax'              => self::clamp_float( $orb['parallax'] ?? 0.5, 0.0, 1.0 ),
				'interaction_direction' => self::sanitize_enum( $orb['interaction_direction'] ?? 'normal', $allowed_dirs, 'normal' ),
				'rotation'              => self::clamp_float( $orb['rotation'] ?? 0.0, 0.0, 360.0 ),
			);

			$config['orbs'][] = $sanitized_orb;
		}

		return $config;
	}

	/**
	 * Return $val if it is in the allowed list, otherwise $default.
	 */
	private static function sanitize_enum( $val, $allowed, $default ) {
		return in_array( $val, $allowed, true ) ? $val : $default;
	}

	/**
	 * Editor preview background: 'transparent', a hex color, or a safe
	 * rgb()/rgba() string. Anything else falls back to 'transparent'.
	 */
	private static function sanitize_preview_bg( $val ) {
		$val = is_string( $val ) ? trim( $val ) : '';
		if ( '' === $val || 'transparent' === $val ) return 'transparent';
		$hex = sanitize_hex_color( $val );
		if ( $hex ) return $hex;
		if ( preg_match( '/^rgba?\(\s*[\d.]+\s*,\s*[\d.]+\s*,\s*[\d.]+\s*(,\s*[\d.]+\s*)?\)$/i', $val ) ) {
			return $val;
		}
		return 'transparent';
	}

	/**
	 * Editor preview size: null (auto/fill — the editor also sends 0 for
	 * "fill") or a clamped pixel value.
	 */
	private static function sanitize_preview_dim( $val, $max ) {
		if ( null === $val || '' === $val || ! (int) $val ) return null;
		return self::clamp_int( $val, 100, $max );
	}

	private static function sanitize_color_stops( $raw_stops, $fallback_a, $fallback_b ) {
		$stops = array();
		foreach ( array_slice( (array) $raw_stops, 0, 5 ) as $stop ) {
			$hex = sanitize_hex_color( $stop );
			if ( $hex ) $stops[] = $hex;
		}

		if ( empty( $stops ) ) {
			$primary = sanitize_hex_color( $fallback_a ) ?: '#38a3d7';
			$secondary = sanitize_hex_color( $fallback_b ) ?: '#8bb84a';
			$stops = array( $primary, $secondary );
		}

		return array_values( array_slice( $stops, 0, 5 ) );
	}

	private static function clamp_float( $val, $min, $max ) {
		return max( $min, min( $max, (float) $val ) );
	}

	private static function clamp_int( $val, $min, $max ) {
		return max( $min, min( $max, (int) $val ) );
	}
}
