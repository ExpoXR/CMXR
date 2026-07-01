<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap cmxr-wrap">

	<?php
	CMXR_Dashboard::render_header(
		__( 'Settings', 'cmxr-canvas-motion-backgrounds' )
	);

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only param set by our own wp_safe_redirect after a nonce-verified action.
	$cmxr_notice       = isset( $_GET['cmxr_notice'] ) ? sanitize_key( $_GET['cmxr_notice'] ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$cmxr_import_count = isset( $_GET['cmxr_import_count'] ) ? absint( $_GET['cmxr_import_count'] ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$cmxr_fail_count   = isset( $_GET['cmxr_fail_count'] ) ? absint( $_GET['cmxr_fail_count'] ) : 0;

	if ( 'cache_cleared' === $cmxr_notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cache cleared successfully.', 'cmxr-canvas-motion-backgrounds' ); ?></p></div>
	<?php elseif ( 'imported' === $cmxr_notice ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: 1: number of animations imported, 2: number of failures */
					esc_html__( 'Import complete. %1$d animation(s) imported, %2$d failed.', 'cmxr-canvas-motion-backgrounds' ),
					absint( $cmxr_import_count ),
					absint( $cmxr_fail_count )
				);
				?>
			</p>
		</div>
	<?php elseif ( 'import_error' === $cmxr_notice ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Import failed. Please upload a valid CMXR JSON export file.', 'cmxr-canvas-motion-backgrounds' ); ?></p></div>
	<?php endif; ?>

	<div class="cmxr-page-card">
		<?php settings_errors( 'cmxr_settings_group' ); ?>
		<form method="post" action="options.php" class="cmxr-settings-form">
			<?php settings_fields( 'cmxr_settings_group' ); ?>
			<?php do_settings_sections( 'cmxr-settings' ); ?>
			<?php submit_button( __( 'Save Settings', 'cmxr-canvas-motion-backgrounds' ) ); ?>
		</form>
	</div>

	<div class="cmxr-tools-grid">

		<!-- Cache -->
		<div class="cmxr-page-card">
			<h2 class="cmxr-section-title"><?php esc_html_e( 'Cache', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
			<p><?php esc_html_e( 'Clear all CMXR transient caches. Use after bulk changes or if animations appear stale.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cmxr_clear_cache' ); ?>
				<input type="hidden" name="action" value="cmxr_clear_cache">
				<button type="submit" class="button"><?php esc_html_e( 'Clear Cache', 'cmxr-canvas-motion-backgrounds' ); ?></button>
			</form>
		</div>

		<!-- Export -->
		<div class="cmxr-page-card">
			<h2 class="cmxr-section-title"><?php esc_html_e( 'Export Animations', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
			<p><?php esc_html_e( 'Download all animations as a JSON file. Use this to back up or migrate to another site.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cmxr_export' ); ?>
				<input type="hidden" name="action" value="cmxr_export">
				<button type="submit" class="button"><?php esc_html_e( 'Export Animations', 'cmxr-canvas-motion-backgrounds' ); ?></button>
			</form>
		</div>

		<!-- Import -->
		<div class="cmxr-page-card">
			<h2 class="cmxr-section-title"><?php esc_html_e( 'Import Animations', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
			<p><?php esc_html_e( 'Import animations from a previously exported JSON file. Creates new animations — never overwrites existing ones.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'cmxr_import' ); ?>
				<input type="hidden" name="action" value="cmxr_import">
				<div class="cmxr-tool-file-row">
					<input type="file" name="cmxr_import_file" accept=".json" required>
				</div>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'cmxr-canvas-motion-backgrounds' ); ?></button>
			</form>
		</div>

	</div>

	<?php CMXR_Dashboard::render_footer(); ?>

</div>
