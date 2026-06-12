<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap spherexr-wrap">

	<?php
	SphereXR_Dashboard::render_header(
		__( 'Settings', 'spherexr' )
	);

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only param set by our own wp_safe_redirect after a nonce-verified action.
	$spherexr_notice       = isset( $_GET['sxr_notice'] ) ? sanitize_key( $_GET['sxr_notice'] ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$spherexr_import_count = isset( $_GET['sxr_import_count'] ) ? absint( $_GET['sxr_import_count'] ) : 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$spherexr_fail_count   = isset( $_GET['sxr_fail_count'] ) ? absint( $_GET['sxr_fail_count'] ) : 0;

	if ( 'cache_cleared' === $spherexr_notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cache cleared successfully.', 'spherexr' ); ?></p></div>
	<?php elseif ( 'imported' === $spherexr_notice ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: 1: number of animations imported, 2: number of failures */
					esc_html__( 'Import complete. %1$d animation(s) imported, %2$d failed.', 'spherexr' ),
					absint( $spherexr_import_count ),
					absint( $spherexr_fail_count )
				);
				?>
			</p>
		</div>
	<?php elseif ( 'import_error' === $spherexr_notice ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Import failed. Please upload a valid SphereXR JSON export file.', 'spherexr' ); ?></p></div>
	<?php endif; ?>

	<div class="sxr-page-card">
		<?php settings_errors( 'spherexr_settings_group' ); ?>
		<form method="post" action="options.php" class="spherexr-settings-form">
			<?php settings_fields( 'spherexr_settings_group' ); ?>
			<?php do_settings_sections( 'spherexr-settings' ); ?>
			<?php submit_button( __( 'Save Settings', 'spherexr' ) ); ?>
		</form>
	</div>

	<div class="sxr-tools-grid">

		<!-- Cache -->
		<div class="sxr-page-card">
			<h2 class="sxr-section-title"><?php esc_html_e( 'Cache', 'spherexr' ); ?></h2>
			<p><?php esc_html_e( 'Clear all SphereXR transient caches. Use after bulk changes or if animations appear stale.', 'spherexr' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'spherexr_clear_cache' ); ?>
				<input type="hidden" name="action" value="spherexr_clear_cache">
				<button type="submit" class="button"><?php esc_html_e( 'Clear Cache', 'spherexr' ); ?></button>
			</form>
		</div>

		<!-- Export -->
		<div class="sxr-page-card">
			<h2 class="sxr-section-title"><?php esc_html_e( 'Export Animations', 'spherexr' ); ?></h2>
			<p><?php esc_html_e( 'Download all animations as a JSON file. Use this to back up or migrate to another site.', 'spherexr' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'spherexr_export' ); ?>
				<input type="hidden" name="action" value="spherexr_export">
				<button type="submit" class="button"><?php esc_html_e( 'Export Animations', 'spherexr' ); ?></button>
			</form>
		</div>

		<!-- Import -->
		<div class="sxr-page-card">
			<h2 class="sxr-section-title"><?php esc_html_e( 'Import Animations', 'spherexr' ); ?></h2>
			<p><?php esc_html_e( 'Import animations from a previously exported JSON file. Creates new animations — never overwrites existing ones.', 'spherexr' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'spherexr_import' ); ?>
				<input type="hidden" name="action" value="spherexr_import">
				<div class="sxr-tool-file-row">
					<input type="file" name="spherexr_import_file" accept=".json" required>
				</div>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'spherexr' ); ?></button>
			</form>
		</div>

	</div>

	<?php SphereXR_Dashboard::render_footer(); ?>

</div>
