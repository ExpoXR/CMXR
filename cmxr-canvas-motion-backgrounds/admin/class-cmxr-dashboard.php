<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMXR_Dashboard {

	/**
	 * Shared branded admin page header (gradient banner + quick actions).
	 *
	 * @param string $page_title Current page name (e.g. "Settings").
	 * @param string $actions_html Optional escaped HTML for right-side actions.
	 */
	public static function render_header( $page_title, $actions_html = '' ) {
		$header_actions = $actions_html;
		include CMXR_PLUGIN_DIR . 'templates/admin/partials/header.php';
	}

	/**
	 * Shared ExpoXR-family branding footer.
	 */
	public static function render_footer() {
		?>
		<div class="cmxr-admin-footer">
			<div class="cmxr-footer-content">
				<div class="cmxr-footer-branding">
					<?php
					// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Plugin logo for admin footer
					printf(
						'<img src="%s" alt="%s" class="cmxr-footer-logo" loading="lazy">',
						esc_url( CMXR_PLUGIN_URL . 'assets/img/ExpoXR-Logo.png' ),
						esc_attr__( 'ExpoXR Logo', 'cmxr-canvas-motion-backgrounds' )
					);
					?>
					<p class="cmxr-footer-text">
						<?php esc_html_e( 'CMXR is part of the', 'cmxr-canvas-motion-backgrounds' ); ?> <strong><?php esc_html_e( 'ExpoXR Family', 'cmxr-canvas-motion-backgrounds' ); ?></strong> -
						<?php esc_html_e( 'XR solutions for the modern web', 'cmxr-canvas-motion-backgrounds' ); ?>
						<span class="cmxr-footer-version">v<?php echo esc_html( CMXR_VERSION ); ?></span>
					</p>
				</div>
				<div class="cmxr-footer-links">
					<a href="https://expoxr.com" target="_blank" rel="noopener"><?php esc_html_e( 'Visit ExpoXR.com', 'cmxr-canvas-motion-backgrounds' ); ?></a>
					<a href="https://expoxr.com/support/" target="_blank" rel="noopener"><?php esc_html_e( 'Support', 'cmxr-canvas-motion-backgrounds' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	public function render() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cmxr-canvas-motion-backgrounds' ) );
		}

		$posts = get_posts( array(
			'post_type'   => 'cmxr_animation',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		$animations = array();
		foreach ( $posts as $post ) {
			$raw    = get_post_meta( $post->ID, '_cmxr_config', true );
			$config = $raw ? json_decode( $raw, true ) : array();
			$animations[] = array(
				'post'      => $post,
				'config'    => $config,
				'active'    => ! empty( $config['active'] ),
				'orb_count' => count( $config['orbs'] ?? array() ),
				'anim_id'   => $config['animation_id'] ?? '',
			);
		}

		include CMXR_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}
}
