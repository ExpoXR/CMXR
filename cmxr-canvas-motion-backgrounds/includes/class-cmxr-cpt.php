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
			if ( 2 === (int) ( $config['config_version'] ?? 1 ) ) {
				if ( empty( $config['target']['selector'] ) ) continue;
			} elseif ( empty( $config['animation_id'] ) ) {
				continue;
			}
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
		$version = isset( $raw['config_version'] ) ? (int) $raw['config_version'] : 1;
		if ( 2 === $version ) return self::sanitize_config_v2( $raw );
		if ( 1 !== $version ) return false;
		return self::sanitize_config_v1( $raw );
	}

	private static function sanitize_config_v1( $raw ) {

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

			$size_unit = self::sanitize_enum( $size['unit'] ?? 'percent', $allowed_units, 'percent' );
			$size_max  = ( 'px' === $size_unit ) ? 2000 : 200;

			$pos_unit = self::sanitize_enum( $pos['unit'] ?? 'percent', $allowed_units, 'percent' );
			// px positions are absolute pixels (matches the client posUnitRanges max);
			// percent/vw/vh are viewport/container fractions and stay 0–100.
			$pos_max  = ( 'px' === $pos_unit ) ? 3000 : 100;

			$sanitized_orb = array(
				'id'         => sanitize_key( $orb['id'] ?? uniqid( 'o' ) ),
				'shape'      => self::sanitize_enum( $orb['shape'] ?? 'circle', $allowed_shapes, 'circle' ),
				'color'      => sanitize_hex_color( $orb['color'] ?? '#38a3d7' ) ?: '#38a3d7',
				'color_mode' => self::sanitize_enum( $orb['color_mode'] ?? 'solid', $allowed_cmodes, 'solid' ),
				'color_b'    => sanitize_hex_color( $orb['color_b'] ?? '' ) ?: '',
				'color_stops' => $color_stops,
				'color_animation' => self::sanitize_enum( $orb['color_animation'] ?? 'none', $allowed_canims, 'none' ),
				'size'       => array(
					'w'    => self::clamp_float( $size['w'] ?? 40, 1, $size_max ),
					'h'    => self::clamp_float( $size['h'] ?? 40, 1, $size_max ),
					'unit' => $size_unit,
				),
				'position' => array(
					'x'    => self::clamp_float( $pos['x'] ?? 50, 0, $pos_max ),
					'y'    => self::clamp_float( $pos['y'] ?? 50, 0, $pos_max ),
					'unit' => $pos_unit,
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

	private static function sanitize_config_v2( $raw ) {
		if ( 'procedural-orbs' !== ( $raw['effect_type'] ?? '' ) ) return false;

		$template_slug = sanitize_key( $raw['template_slug'] ?? '' );
		$definition    = class_exists( 'CMXR_Template_Registry' ) ? CMXR_Template_Registry::definition( $template_slug ) : null;
		if ( ! $definition || 'procedural-orbs' !== ( $definition['effect_type'] ?? '' ) ) return false;

		$target   = is_array( $raw['target'] ?? null ) ? $raw['target'] : array();
		$selector = (string) ( $target['selector'] ?? '' );
		if ( 'id' !== ( $target['mode'] ?? '' ) || 'attached' !== ( $target['placement'] ?? '' ) || ! self::is_valid_target_token( $selector ) ) {
			return false;
		}

		$settings = is_array( $raw['settings'] ?? null ) ? $raw['settings'] : array();
		$defaults = $definition['settings'];
		$palette  = is_array( $settings['palette'] ?? null ) ? $settings['palette'] : array();
		$pd       = $defaults['palette'];

		$seed = $settings['seed'] ?? $defaults['seed'];
		if ( null !== $seed ) {
			$seed = substr( sanitize_text_field( (string) $seed ), 0, 64 );
			if ( '' === $seed ) $seed = null;
		}

		$hue_min = self::clamp_int( $palette['hue_min'] ?? $pd['hue_min'], 0, 360 );
		$hue_max = self::clamp_int( $palette['hue_max'] ?? $pd['hue_max'], $hue_min, 360 );
		$offsets = array();
		foreach ( array_slice( (array) ( $palette['hue_offsets'] ?? $pd['hue_offsets'] ), 0, 6 ) as $offset ) {
			$offsets[] = self::clamp_int( $offset, -360, 360 );
		}
		if ( empty( $offsets ) ) $offsets = array( 0, 30, 60 );

		$fixed_colors = array();
		foreach ( array_slice( (array) ( $palette['fixed_colors'] ?? $pd['fixed_colors'] ?? array() ), 0, 6 ) as $color ) {
			$hex = sanitize_hex_color( $color );
			if ( $hex ) $fixed_colors[] = $hex;
		}
		if ( empty( $fixed_colors ) ) $fixed_colors = array( '#2AACE2', '#8062AA', '#EF4681' );

		$counts = is_array( $settings['counts'] ?? null ) ? $settings['counts'] : array();
		$cd     = $defaults['counts'];
		$breakpoints = is_array( $settings['breakpoints'] ?? null ) ? $settings['breakpoints'] : array();
		$bd          = $defaults['breakpoints'];
		$tablet_bp   = self::clamp_int( $breakpoints['tablet'] ?? $bd['tablet'], 320, 2000 );
		$desktop_bp  = self::clamp_int( $breakpoints['desktop'] ?? $bd['desktop'], $tablet_bp + 1, 3000 );

		$scale = is_array( $settings['scale'] ?? null ) ? $settings['scale'] : array();
		$sd    = $defaults['scale'];
		$scale_min = self::clamp_float( $scale['min'] ?? $sd['min'], 0.1, 2.0 );
		$scale_max = self::clamp_float( $scale['max'] ?? $sd['max'], $scale_min, 3.0 );

		$blur = is_array( $settings['blur'] ?? null ) ? $settings['blur'] : array();
		$bld  = $defaults['blur'];

		$clean_settings = array(
			'seed' => $seed,
			'palette' => array(
				'mode'         => self::sanitize_enum( $palette['mode'] ?? $pd['mode'], array( 'random-hsl', 'fixed' ), 'random-hsl' ),
				'hue_min'      => $hue_min,
				'hue_max'      => $hue_max,
				'hue_offsets'  => $offsets,
				'saturation'   => self::clamp_int( $palette['saturation'] ?? $pd['saturation'], 0, 100 ),
				'lightness'    => self::clamp_int( $palette['lightness'] ?? $pd['lightness'], 0, 100 ),
				'fixed_colors' => $fixed_colors,
			),
			'counts' => array(
				'mobile'  => self::clamp_int( $counts['mobile'] ?? $cd['mobile'], 1, 20 ),
				'tablet'  => self::clamp_int( $counts['tablet'] ?? $cd['tablet'], 1, 20 ),
				'desktop' => self::clamp_int( $counts['desktop'] ?? $cd['desktop'], 1, 20 ),
			),
			'breakpoints' => array(
				'tablet'  => $tablet_bp,
				'desktop' => $desktop_bp,
			),
			'alpha'             => self::clamp_float( $settings['alpha'] ?? $defaults['alpha'], 0.0, 1.0 ),
			'simplex_increment' => self::clamp_float( $settings['simplex_increment'] ?? $defaults['simplex_increment'], 0.0001, 0.02 ),
			'scale'             => array( 'min' => $scale_min, 'max' => $scale_max ),
			'blur'              => array(
				'mobile'  => self::clamp_int( $blur['mobile'] ?? $bld['mobile'], 0, 200 ),
				'tablet'  => self::clamp_int( $blur['tablet'] ?? $bld['tablet'], 0, 200 ),
				'desktop' => self::clamp_int( $blur['desktop'] ?? $bld['desktop'], 0, 200 ),
			),
			'dpr_cap'      => self::clamp_float( $settings['dpr_cap'] ?? $defaults['dpr_cap'], 1.0, 2.0 ),
			'minimum_size' => self::clamp_int( $settings['minimum_size'] ?? $defaults['minimum_size'], 100, 1000 ),
		);

		$clean_settings['radius'] = self::sanitize_responsive_radius( $settings['radius'] ?? array(), $defaults['radius'] );

		if ( isset( $defaults['physics'] ) ) {
			$bounds = is_array( $settings['bounds'] ?? null ) ? $settings['bounds'] : array();
			$clean_settings['bounds'] = array(
				'padding' => self::clamp_int( $bounds['padding'] ?? $defaults['bounds']['padding'], 0, 500 ),
			);

			$physics = is_array( $settings['physics'] ?? null ) ? $settings['physics'] : array();
			$phd     = $defaults['physics'];
			$clean_settings['physics'] = array(
				'wander_force'     => self::clamp_float( $physics['wander_force'] ?? $phd['wander_force'], 0.0, 2.0 ),
				'attraction'       => self::clamp_float( $physics['attraction'] ?? $phd['attraction'], 0.0, 2.0 ),
				'attraction_radius'=> self::clamp_int( $physics['attraction_radius'] ?? $phd['attraction_radius'], 1, 2000 ),
				'damping'          => self::clamp_float( $physics['damping'] ?? $phd['damping'], 0.5, 0.999 ),
				'boundary_margin'  => self::clamp_int( $physics['boundary_margin'] ?? $phd['boundary_margin'], 0, 500 ),
				'boundary_spring'  => self::clamp_float( $physics['boundary_spring'] ?? $phd['boundary_spring'], 0.0, 5.0 ),
			);

			$aura = is_array( $settings['aura'] ?? null ) ? $settings['aura'] : array();
			$ad   = $defaults['aura'];
			$clean_settings['aura'] = array(
				'enabled' => isset( $aura['enabled'] ) ? ! empty( $aura['enabled'] ) : ! empty( $ad['enabled'] ),
				'radius'  => self::clamp_int( $aura['radius'] ?? $ad['radius'], 1, 1000 ),
				'color'   => self::sanitize_canvas_color( $aura['color'] ?? $ad['color'], $ad['color'] ),
			);

			$burst = is_array( $settings['burst'] ?? null ) ? $settings['burst'] : array();
			$bud   = $defaults['burst'];
			$clean_settings['burst'] = array(
				'enabled'     => isset( $burst['enabled'] ) ? ! empty( $burst['enabled'] ) : ! empty( $bud['enabled'] ),
				'force'       => self::clamp_float( $burst['force'] ?? $bud['force'], 0.0, 30.0 ),
				'duration_ms' => self::clamp_int( $burst['duration_ms'] ?? $bud['duration_ms'], 50, 5000 ),
			);

			$ripple = is_array( $settings['ripple'] ?? null ) ? $settings['ripple'] : array();
			$rd     = $defaults['ripple'];
			$clean_settings['ripple'] = array(
				'enabled'     => isset( $ripple['enabled'] ) ? ! empty( $ripple['enabled'] ) : ! empty( $rd['enabled'] ),
				'duration_ms' => self::clamp_int( $ripple['duration_ms'] ?? $rd['duration_ms'], 50, 5000 ),
				'max_radius'  => self::clamp_int( $ripple['max_radius'] ?? $rd['max_radius'], 1, 2000 ),
				'alpha'       => self::clamp_float( $ripple['alpha'] ?? $rd['alpha'], 0.0, 1.0 ),
				'line_width'  => self::clamp_float( $ripple['line_width'] ?? $rd['line_width'], 0.1, 20.0 ),
				'color'       => self::sanitize_canvas_color( $ripple['color'] ?? $rd['color'], $rd['color'] ),
			);

			$touch = is_array( $settings['touch'] ?? null ) ? $settings['touch'] : array();
			$td    = $defaults['touch'];
			$clean_settings['touch'] = array(
				'enabled' => isset( $touch['enabled'] ) ? ! empty( $touch['enabled'] ) : ! empty( $td['enabled'] ),
				'passive' => true,
			);
		} else {
			$clean_settings['bounds'] = self::sanitize_static_bounds( $settings['bounds'] ?? array(), $defaults['bounds'] );
		}

		return array(
			'config_version' => 2,
			'template_slug'  => $template_slug,
			'effect_type'    => 'procedural-orbs',
			'kind'           => 'background',
			'active'         => ! empty( $raw['active'] ),
			'target'         => array(
				'mode'      => 'id',
				'selector'  => $selector,
				'placement' => 'attached',
			),
			'settings'       => $clean_settings,
			'dependencies'   => array(),
			'fallback'       => array(
				'reduced_motion' => 'static',
				'unsupported'    => 'static',
			),
		);
	}

	public static function is_valid_target_token( $token ) {
		return is_string( $token ) && 1 === preg_match( '/^[A-Za-z][A-Za-z0-9_-]{0,99}$/', $token );
	}

	public static function config_target( $config ) {
		if ( 2 === (int) ( $config['config_version'] ?? 1 ) ) {
			return (string) ( $config['target']['selector'] ?? '' );
		}
		return (string) ( $config['animation_id'] ?? '' );
	}

	public static function target_in_use( $target, $exclude_post_id = 0 ) {
		if ( ! self::is_valid_target_token( $target ) ) return false;
		$ids = get_posts( array(
			'post_type'      => 'cmxr_animation',
			'post_status'    => array( 'publish', 'draft' ),
			'numberposts'    => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		foreach ( $ids as $post_id ) {
			if ( (int) $post_id === (int) $exclude_post_id ) continue;
			$raw    = get_post_meta( $post_id, '_cmxr_config', true );
			$config = $raw ? json_decode( $raw, true ) : array();
			if ( is_array( $config ) && $target === self::config_target( $config ) ) return true;
		}
		return false;
	}

	public static function unique_target_token( $preferred, $exclude_post_id = 0 ) {
		$base = self::is_valid_target_token( $preferred ) ? (string) $preferred : sanitize_title( $preferred );
		if ( ! self::is_valid_target_token( $base ) ) $base = 'cmxr-animation';
		if ( ! self::target_in_use( $base, $exclude_post_id ) ) return $base;
		for ( $i = 2; $i < 10000; $i++ ) {
			$suffix    = '-' . $i;
			$candidate = substr( $base, 0, 100 - strlen( $suffix ) ) . $suffix;
			if ( ! self::target_in_use( $candidate, $exclude_post_id ) ) return $candidate;
		}
		return 'cmxr-animation-' . wp_rand( 10000, 99999 );
	}

	private static function sanitize_static_bounds( $raw, $defaults ) {
		$raw = is_array( $raw ) ? $raw : array();
		$out = array();
		foreach ( array( 'mobile', 'tablet', 'desktop' ) as $key ) {
			$value = is_array( $raw[ $key ] ?? null ) ? $raw[ $key ] : array();
			$def   = $defaults[ $key ];
			$out[ $key ] = array(
				'origin_x'         => self::clamp_float( $value['origin_x'] ?? $def['origin_x'], 0.0, 1.0 ),
				'origin_y'         => self::clamp_float( $value['origin_y'] ?? $def['origin_y'], 0.0, 1.0 ),
				'distance_basis'   => self::sanitize_enum( $value['distance_basis'] ?? $def['distance_basis'], array( 'width', 'height', 'min-dimension' ), $def['distance_basis'] ),
				'distance_divisor' => self::clamp_float( $value['distance_divisor'] ?? $def['distance_divisor'], 0.5, 20.0 ),
			);
		}
		return $out;
	}

	private static function sanitize_responsive_radius( $raw, $defaults ) {
		$raw = is_array( $raw ) ? $raw : array();
		$out = array();
		foreach ( array( 'mobile', 'tablet', 'desktop' ) as $key ) {
			$value = is_array( $raw[ $key ] ?? null ) ? array_values( $raw[ $key ] ) : array();
			$def   = array_values( $defaults[ $key ] );
			$min   = self::clamp_float( $value[0] ?? $def[0], 0.01, 2.0 );
			$max   = self::clamp_float( $value[1] ?? $def[1], $min, 3.0 );
			$out[ $key ] = array(
				$min,
				$max,
				self::sanitize_enum( $value[2] ?? $def[2], array( 'width', 'height', 'min-dimension' ), $def[2] ),
			);
		}
		return $out;
	}

	private static function sanitize_canvas_color( $value, $default ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		$hex = sanitize_hex_color( $value );
		if ( $hex ) return $hex;
		if ( preg_match( '/^rgba?\(\s*(?:\d{1,3}\s*,\s*){2}\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/i', $value ) ) {
			return $value;
		}
		return $default;
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
