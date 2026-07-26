<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Immutable bundled animation-template registry.
 */
class CMXR_Template_Registry {

	const EFFECT_PROCEDURAL_ORBS = 'procedural-orbs';

	private static $templates = null;

	public static function all() {
		if ( null !== self::$templates ) {
			return self::$templates;
		}

		$base    = CMXR_PLUGIN_DIR . 'templates/animations/';
		$catalog = self::read_json( $base . 'catalog.json' );
		$loaded  = array();

		if ( ! is_array( $catalog ) || 1 !== (int) ( $catalog['schema_version'] ?? 0 ) ) {
			self::$templates = array();
			return self::$templates;
		}

		foreach ( (array) ( $catalog['templates'] ?? array() ) as $metadata ) {
			$file = basename( (string) ( $metadata['definition'] ?? '' ) );
			if ( ! $file || ! preg_match( '/^[a-z0-9-]+\.json$/', $file ) ) continue;

			$definition = self::read_json( $base . $file );
			if ( ! self::valid_definition( $metadata, $definition ) ) continue;

			$slug = $definition['slug'];
			if ( isset( $loaded[ $slug ] ) ) continue;

			$loaded[ $slug ] = array(
				'metadata'   => $metadata,
				'definition' => $definition,
			);
		}

		uasort( $loaded, function ( $a, $b ) {
			$ap = (int) ( $a['metadata']['priority'] ?? 99 );
			$bp = (int) ( $b['metadata']['priority'] ?? 99 );
			if ( $ap !== $bp ) return $ap - $bp;
			return (int) ( $a['metadata']['order'] ?? 999 ) - (int) ( $b['metadata']['order'] ?? 999 );
		} );

		self::$templates = $loaded;
		return self::$templates;
	}

	public static function get( $slug ) {
		$templates = self::all();
		return $templates[ sanitize_key( $slug ) ] ?? null;
	}

	public static function metadata() {
		$out = array();
		foreach ( self::all() as $entry ) {
			$meta = $entry['metadata'];
			unset( $meta['definition'] );
			$meta['capabilities'] = array_values( (array) ( $entry['definition']['capabilities'] ?? array() ) );
			$meta['dependencies'] = array_values( (array) ( $entry['definition']['dependencies'] ?? array() ) );
			$meta['fallback']     = $entry['definition']['fallback'] ?? array();
			$out[] = $meta;
		}
		return $out;
	}

	public static function definition( $slug ) {
		$entry = self::get( $slug );
		return $entry ? self::copy( $entry['definition'] ) : null;
	}

	/**
	 * Create a standalone saved config from immutable template defaults.
	 */
	public static function instantiate( $slug, $target = '', $overrides = array() ) {
		$definition = self::definition( $slug );
		if ( ! $definition ) {
			return new WP_Error( 'invalid_template', __( 'Unknown animation template.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 400 ) );
		}

		if ( is_array( $target ) ) {
			if ( isset( $target['mode'] ) && 'id' !== $target['mode'] ) {
				return new WP_Error( 'invalid_target', __( 'Priority 1 templates support ID targets only.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 400 ) );
			}
			$target = $target['selector'] ?? '';
		}
		$target = $target ? (string) $target : (string) ( $definition['target']['selector'] ?? '' );
		if ( ! CMXR_CPT::is_valid_target_token( $target ) ) {
			return new WP_Error( 'invalid_target', __( 'Target must be one safe CSS ID token.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 400 ) );
		}
		$target = CMXR_CPT::unique_target_token( $target );

		$settings = self::copy( $definition['settings'] );
		if ( is_array( $overrides ) ) {
			$settings = self::merge_settings( $settings, $overrides );
		}

		$config = array(
			'config_version' => 2,
			'template_slug'  => $definition['slug'],
			'effect_type'    => self::EFFECT_PROCEDURAL_ORBS,
			'kind'           => 'background',
			'active'         => true,
			'target'         => array(
				'mode'      => 'id',
				'selector'  => $target,
				'placement' => 'attached',
			),
			'settings'       => $settings,
			'dependencies'   => array(),
			'fallback'       => array(
				'reduced_motion' => 'static',
				'unsupported'    => 'static',
			),
		);

		$clean = CMXR_CPT::sanitize_config( $config );
		if ( ! $clean ) {
			return new WP_Error( 'invalid_config', __( 'Template configuration is invalid.', 'cmxr-canvas-motion-backgrounds' ), array( 'status' => 400 ) );
		}
		return $clean;
	}

	private static function merge_settings( $defaults, $overrides ) {
		foreach ( $overrides as $key => $value ) {
			if ( ! array_key_exists( $key, $defaults ) ) continue;
			if ( is_array( $defaults[ $key ] ) && is_array( $value ) && self::is_assoc( $defaults[ $key ] ) ) {
				$defaults[ $key ] = self::merge_settings( $defaults[ $key ], $value );
			} else {
				$defaults[ $key ] = $value;
			}
		}
		return $defaults;
	}

	private static function valid_definition( $metadata, $definition ) {
		if ( ! is_array( $metadata ) || ! is_array( $definition ) ) return false;
		$slug = (string) ( $definition['slug'] ?? '' );
		if ( ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug ) ) return false;
		if ( 2 !== (int) ( $definition['config_version'] ?? 0 ) ) return false;
		if ( $slug !== (string) ( $metadata['slug'] ?? '' ) ) return false;
		if ( self::EFFECT_PROCEDURAL_ORBS !== ( $definition['effect_type'] ?? '' ) ) return false;
		if ( self::EFFECT_PROCEDURAL_ORBS !== ( $definition['renderer'] ?? '' ) ) return false;
		if ( 'background' !== ( $definition['kind'] ?? '' ) ) return false;
		if ( 'id' !== ( $definition['target']['mode'] ?? '' ) ) return false;
		if ( ! is_array( $definition['settings'] ?? null ) ) return false;
		if ( ! empty( $definition['dependencies'] ) ) return false;
		return CMXR_CPT::is_valid_target_token( $definition['target']['selector'] ?? '' );
	}

	private static function read_json( $path ) {
		if ( ! is_readable( $path ) ) return null;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fixed plugin file.
		$raw = file_get_contents( $path );
		if ( false === $raw ) return null;
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : null;
	}

	private static function copy( $value ) {
		return json_decode( wp_json_encode( $value ), true );
	}

	private static function is_assoc( $value ) {
		if ( ! is_array( $value ) ) return false;
		return array_keys( $value ) !== range( 0, count( $value ) - 1 );
	}
}
