<?php
/**
 * Shared branded admin header + quick actions bar.
 *
 * Expects:
 *   $page_title     string  Page heading.
 *   $header_actions string  Optional escaped HTML for right-side actions.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$sxr_settings   = get_option( 'spherexr_settings', array() );
$sxr_show_debug = ! empty( $sxr_settings['debug_mode'] ) && current_user_can( 'manage_options' );
?>
<div class="spherexr-admin-header">
	<h1>
		<?php echo esc_html( $page_title ); ?>
		<span class="spherexr-version">v<?php echo esc_html( SPHEREXR_VERSION ); ?></span>
	</h1>
	<?php if ( ! empty( $header_actions ) ) : ?>
		<div class="spherexr-header-actions"><?php echo wp_kses_post( $header_actions ); ?></div>
	<?php endif; ?>
</div>

<div class="spherexr-quick-actions">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr' ) ); ?>">
		<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'spherexr' ); ?>
	</a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr-new' ) ); ?>">
		<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'New Animation', 'spherexr' ); ?>
	</a>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr-settings' ) ); ?>">
			<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'spherexr' ); ?>
		</a>
	<?php endif; ?>
	<?php if ( $sxr_show_debug ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr-debug' ) ); ?>">
			<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Debug', 'spherexr' ); ?>
		</a>
	<?php endif; ?>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr-explorexr' ) ); ?>" class="spherexr-premium-action">
		<span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'ExploreXR', 'spherexr' ); ?>
	</a>
</div>

<hr class="wp-header-end">
