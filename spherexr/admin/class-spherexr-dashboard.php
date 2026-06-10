<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SphereXR_Dashboard {

	/**
	 * Shared branded admin page header (gradient banner + quick actions).
	 *
	 * @param string $page_title Current page name (e.g. "Settings").
	 * @param string $actions_html Optional escaped HTML for right-side actions.
	 */
	public static function render_header( $page_title, $actions_html = '' ) {
		$header_actions = $actions_html;
		include SPHEREXR_PLUGIN_DIR . 'templates/admin/partials/header.php';
	}

	/**
	 * Shared ExpoXR-family branding footer.
	 */
	public static function render_footer() {
		?>
		<div class="spherexr-admin-footer">
			<div class="spherexr-footer-content">
				<div class="spherexr-footer-branding">
					<?php
					// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Plugin logo for admin footer
					printf(
						'<img src="%s" alt="%s" class="spherexr-footer-logo" loading="lazy">',
						esc_url( SPHEREXR_PLUGIN_URL . 'assets/img/ExpoXR-Logo.png' ),
						esc_attr__( 'ExpoXR Logo', 'spherexr' )
					);
					?>
					<p class="spherexr-footer-text">
						<?php esc_html_e( 'SphereXR is part of the', 'spherexr' ); ?> <strong><?php esc_html_e( 'ExpoXR Family', 'spherexr' ); ?></strong> -
						<?php esc_html_e( 'XR solutions for the modern web', 'spherexr' ); ?>
						<span class="spherexr-footer-version">v<?php echo esc_html( SPHEREXR_VERSION ); ?></span>
					</p>
				</div>
				<div class="spherexr-footer-links">
					<a href="https://expoxr.com" target="_blank" rel="noopener"><?php esc_html_e( 'Visit ExpoXR.com', 'spherexr' ); ?></a>
					<a href="https://expoxr.com/spherexr/documentation/" target="_blank" rel="noopener"><?php esc_html_e( 'Documentation', 'spherexr' ); ?></a>
					<a href="https://expoxr.com/support/" target="_blank" rel="noopener"><?php esc_html_e( 'Support', 'spherexr' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	public function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'spherexr' ) );
		}

		$posts = get_posts( array(
			'post_type'   => 'spherexr_animation',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		$animations = array();
		foreach ( $posts as $post ) {
			$raw    = get_post_meta( $post->ID, '_spherexr_config', true );
			$config = $raw ? json_decode( $raw, true ) : array();
			$animations[] = array(
				'post'      => $post,
				'config'    => $config,
				'active'    => ! empty( $config['active'] ),
				'orb_count' => count( $config['orbs'] ?? array() ),
				'anim_id'   => $config['animation_id'] ?? '',
			);
		}

		include SPHEREXR_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}
}
