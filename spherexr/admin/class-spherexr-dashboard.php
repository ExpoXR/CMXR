<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SphereXR_Dashboard {

	/**
	 * Shared admin page header — breadcrumb style for subpages.
	 *
	 * @param string $page_title Current page name (e.g. "Settings").
	 * @param string $actions_html Optional escaped HTML for right-side actions.
	 */
	public static function render_header( $page_title, $actions_html = '' ) {
		?>
		<div class="spherexr-header">
			<h1 class="spherexr-logo">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr' ) ); ?>">SphereXR</a>
			</h1>
			<span class="spherexr-header-sep">›</span>
			<span class="spherexr-page-title"><?php echo esc_html( $page_title ); ?></span>
			<?php if ( $actions_html ) : ?>
				<div class="spherexr-header-actions"><?php echo $actions_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function render_footer() {
		?>
		<div class="sxr-page-footer">
			<span class="sxr-page-footer-brand">SphereXR</span>
			<span class="sxr-page-footer-sep"><?php esc_html_e( 'by', 'spherexr' ); ?></span>
			<a href="https://expoxr.com" target="_blank" rel="noopener" class="sxr-page-footer-link">ExpoXR</a>
			<span class="sxr-page-footer-version">v<?php echo esc_html( SPHEREXR_VERSION ); ?></span>
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
