<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_Settings {

	const OPTION_KEY = 'cmxr_settings';

	private static $hooked = false;

	public function __construct() {
		if ( ! self::$hooked ) {
			self::$hooked = true;
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_post_cmxr_clear_cache', array( $this, 'handle_clear_cache' ) );
			add_action( 'admin_post_cmxr_export',      array( $this, 'handle_export' ) );
			add_action( 'admin_post_cmxr_import',      array( $this, 'handle_import' ) );
		}
	}

	public function register_settings() {
		register_setting(
			'cmxr_settings_group',
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);

		add_settings_section(
			'cmxr_performance',
			__( 'Performance', 'cmxr-canvas-motion-backgrounds' ),
			null,
			'cmxr-settings'
		);

		add_settings_field(
			'dpr_cap',
			__( 'Device Pixel Ratio Cap', 'cmxr-canvas-motion-backgrounds' ),
			array( $this, 'field_dpr_cap' ),
			'cmxr-settings',
			'cmxr_performance'
		);

		add_settings_field(
			'intersection_threshold',
			__( 'IntersectionObserver Threshold', 'cmxr-canvas-motion-backgrounds' ),
			array( $this, 'field_io_threshold' ),
			'cmxr-settings',
			'cmxr_performance'
		);

		add_settings_section(
			'cmxr_defaults',
			__( 'Default Animation Values', 'cmxr-canvas-motion-backgrounds' ),
			null,
			'cmxr-settings'
		);

		add_settings_field(
			'default_speed',
			__( 'Default Speed', 'cmxr-canvas-motion-backgrounds' ),
			array( $this, 'field_default_speed' ),
			'cmxr-settings',
			'cmxr_defaults'
		);

		add_settings_field(
			'default_safe_margin',
			__( 'Default Safe Margin (%)', 'cmxr-canvas-motion-backgrounds' ),
			array( $this, 'field_default_safe_margin' ),
			'cmxr-settings',
			'cmxr_defaults'
		);

		add_settings_field(
			'default_blend_mode',
			__( 'Default Blend Mode', 'cmxr-canvas-motion-backgrounds' ),
			array( $this, 'field_default_blend_mode' ),
			'cmxr-settings',
			'cmxr_defaults'
		);

		add_settings_section(
			'cmxr_debug_section',
			__( 'Developer', 'cmxr-canvas-motion-backgrounds' ),
			null,
			'cmxr-settings'
		);

		add_settings_field(
			'debug_mode',
			__( 'Debug Mode', 'cmxr-canvas-motion-backgrounds' ),
			array( $this, 'field_debug_mode' ),
			'cmxr-settings',
			'cmxr_debug_section'
		);
	}

	public function sanitize( $input ) {
		$allowed_blend = CMXR_Schema::BLEND_MODES;
		$blend_mode    = $input['default_blend_mode'] ?? 'normal';

		return array(
			'dpr_cap'                => max( 1.0, min( 3.0, (float) ( $input['dpr_cap'] ?? 1.75 ) ) ),
			'intersection_threshold' => max( 0.01, min( 1.0, (float) ( $input['intersection_threshold'] ?? 0.01 ) ) ),
			'debug_mode'             => ! empty( $input['debug_mode'] ),
			'default_speed'          => max( 0.1, min( 10.0, (float) ( $input['default_speed'] ?? 1.0 ) ) ),
			'default_safe_margin'    => max( 0, min( 30, (int) ( $input['default_safe_margin'] ?? 5 ) ) ),
			'default_blend_mode'     => in_array( $blend_mode, $allowed_blend, true ) ? $blend_mode : 'normal',
		);
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cmxr-canvas-motion-backgrounds' ) );
		}
		include CMXR_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	// -------------------------------------------------------------------------
	// Tool handlers
	// -------------------------------------------------------------------------

	public function handle_clear_cache() {
		check_admin_referer( 'cmxr_clear_cache' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'cmxr-canvas-motion-backgrounds' ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_cmxr_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_cmxr_' ) . '%'
			)
		);
		wp_cache_flush();

		wp_safe_redirect( add_query_arg( 'cmxr_notice', 'cache_cleared', admin_url( 'admin.php?page=cmxr-settings' ) ) );
		exit;
	}

	public function handle_export() {
		check_admin_referer( 'cmxr_export' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'cmxr-canvas-motion-backgrounds' ) );
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
			$animations[] = array(
				'title'  => $post->post_title,
				'status' => $post->post_status,
				'config' => $config,
			);
		}

		$payload  = array(
			'plugin'      => 'cmxr',
			'version'     => CMXR_VERSION,
			'exported_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'animations'  => $animations,
		);
		$filename = 'cmxr-export-' . gmdate( 'Y-m-d' ) . '.json';

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded data, binary download, exit follows.
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	public function handle_import() {
		check_admin_referer( 'cmxr_import' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'cmxr-canvas-motion-backgrounds' ) );
		}

		$redirect_base = admin_url( 'admin.php?page=cmxr-settings' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is a file path, not user content.
		$tmp = $_FILES['cmxr_import_file']['tmp_name'] ?? '';
		if ( ! $tmp || ! is_uploaded_file( $tmp ) ) {
			wp_safe_redirect( add_query_arg( 'cmxr_notice', 'import_error', $redirect_base ) );
			exit;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading uploaded tmp file.
		$raw = file_get_contents( $tmp );
		if ( ! $raw ) {
			wp_safe_redirect( add_query_arg( 'cmxr_notice', 'import_error', $redirect_base ) );
			exit;
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			wp_safe_redirect( add_query_arg( 'cmxr_notice', 'import_error', $redirect_base ) );
			exit;
		}

		// Accept both the export bundle format { animations: [...] } and a bare array.
		$animations = isset( $data['animations'] ) && is_array( $data['animations'] ) ? $data['animations'] : $data;

		$imported = 0;
		$failed   = 0;
		foreach ( $animations as $item ) {
			if ( ! is_array( $item ) || empty( $item['config'] ) ) {
				$failed++;
				continue;
			}
			$clean = CMXR_CPT::sanitize_config( $item['config'] );
			if ( ! $clean ) {
				$failed++;
				continue;
			}
			$title   = sanitize_text_field( $item['title'] ?? __( 'Imported Animation', 'cmxr-canvas-motion-backgrounds' ) );
			$status  = ( isset( $item['status'] ) && 'draft' === $item['status'] ) ? 'draft' : 'publish';
			$post_id = wp_insert_post( array(
				'post_title'  => $title,
				'post_type'   => 'cmxr_animation',
				'post_status' => $status,
			), true );
			if ( is_wp_error( $post_id ) ) {
				$failed++;
				continue;
			}
			update_post_meta( $post_id, '_cmxr_config', wp_json_encode( $clean ) );
			$imported++;
		}

		wp_safe_redirect( add_query_arg( array(
			'cmxr_notice'       => 'imported',
			'cmxr_import_count' => $imported,
			'cmxr_fail_count'   => $failed,
		), $redirect_base ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	public function field_dpr_cap() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = $opts['dpr_cap'] ?? 1.75;
		printf(
			'<input type="number" name="%s[dpr_cap]" value="%s" min="1" max="3" step="0.25" class="small-text">
			<p class="description">%s</p>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val ),
			esc_html__( 'Cap device pixel ratio to limit canvas size on high-density screens. Recommended: 1.75.', 'cmxr-canvas-motion-backgrounds' )
		);
	}

	public function field_io_threshold() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = $opts['intersection_threshold'] ?? 0.01;
		printf(
			'<input type="number" name="%s[intersection_threshold]" value="%s" min="0.01" max="1" step="0.01" class="small-text">
			<p class="description">%s</p>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val ),
			esc_html__( 'Visibility fraction required before animation starts. 0.01 = start when 1% visible.', 'cmxr-canvas-motion-backgrounds' )
		);
	}

	public function field_default_speed() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = $opts['default_speed'] ?? 1.0;
		printf(
			'<input type="number" name="%s[default_speed]" value="%s" min="0.1" max="10" step="0.1" class="small-text">',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val )
		);
	}

	public function field_default_safe_margin() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = $opts['default_safe_margin'] ?? 5;
		printf(
			'<input type="number" name="%s[default_safe_margin]" value="%s" min="0" max="30" step="1" class="small-text"> %%',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $val )
		);
	}

	public function field_default_blend_mode() {
		$opts    = get_option( self::OPTION_KEY, array() );
		$current = $opts['default_blend_mode'] ?? 'normal';
		$modes   = CMXR_Schema::BLEND_MODES;
		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[default_blend_mode]">';
		foreach ( $modes as $mode ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $mode ),
				selected( $current, $mode, false ),
				esc_html( $mode )
			);
		}
		echo '</select>';
	}

	public function field_debug_mode() {
		$opts = get_option( self::OPTION_KEY, array() );
		$val  = ! empty( $opts['debug_mode'] );
		printf(
			'<input type="checkbox" name="%s[debug_mode]" value="1" %s>
			<label>%s</label>',
			esc_attr( self::OPTION_KEY ),
			checked( $val, true, false ),
			esc_html__( 'Output engine debug info to browser console.', 'cmxr-canvas-motion-backgrounds' )
		);
	}
}
