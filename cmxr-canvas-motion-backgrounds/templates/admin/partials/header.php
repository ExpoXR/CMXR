<?php
/**
 * Shared branded admin header + quick actions bar.
 *
 * Expects:
 *   $page_title     string  Page heading.
 *   $header_actions string  Optional escaped HTML for right-side actions.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

?>
<div class="cmxr-admin-header">
	<h1>
		<?php echo esc_html( $page_title ); ?>
		<span class="cmxr-version">v<?php echo esc_html( CMXR_VERSION ); ?></span>
	</h1>
	<?php if ( ! empty( $header_actions ) ) : ?>
		<div class="cmxr-header-actions"><?php echo wp_kses_post( $header_actions ); ?></div>
	<?php endif; ?>
</div>

<div class="cmxr-quick-actions">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr' ) ); ?>">
		<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'cmxr-canvas-motion-backgrounds' ); ?>
	</a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr-new' ) ); ?>">
		<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'New Animation', 'cmxr-canvas-motion-backgrounds' ); ?>
	</a>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr-settings' ) ); ?>">
			<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'cmxr-canvas-motion-backgrounds' ); ?>
		</a>
	<?php endif; ?>
<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr-explorexr' ) ); ?>" class="cmxr-premium-action">
		<span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'ExploreXR', 'cmxr-canvas-motion-backgrounds' ); ?>
	</a>
</div>

<hr class="wp-header-end">
