<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap cmxr-wrap">

	<?php
	CMXR_Dashboard::render_header(
		__( 'Debug', 'cmxr-canvas-motion-backgrounds' ),
		'<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=cmxr-debug&action=export' ), 'cmxr_export' ) ) . '" class="button">' . esc_html__( 'Export All Configs (JSON)', 'cmxr-canvas-motion-backgrounds' ) . '</a>'
	);
	?>

	<!-- System Info -->
	<div class="cmxr-page-card">
		<h2 class="cmxr-section-title"><?php esc_html_e( 'System Info', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
		<table class="widefat fixed striped cmxr-debug-table">
			<tbody>
				<tr><th><?php esc_html_e( 'PHP Version', 'cmxr-canvas-motion-backgrounds' ); ?></th><td><?php echo esc_html( $system['php_version'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'WordPress Version', 'cmxr-canvas-motion-backgrounds' ); ?></th><td><?php echo esc_html( $system['wp_version'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Active Theme', 'cmxr-canvas-motion-backgrounds' ); ?></th><td><?php echo esc_html( $system['theme'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Plugin Version', 'cmxr-canvas-motion-backgrounds' ); ?></th><td><?php echo esc_html( $system['plugin_ver'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'REST API Namespace', 'cmxr-canvas-motion-backgrounds' ); ?></th><td><code><?php echo esc_html( $system['rest_url'] ); ?></code></td></tr>
			</tbody>
		</table>
	</div>

	<!-- Asset URLs -->
	<div class="cmxr-page-card">
		<h2 class="cmxr-section-title"><?php esc_html_e( 'Asset URLs', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
		<table class="widefat fixed striped cmxr-debug-table">
			<tbody>
				<tr><th><?php esc_html_e( 'Engine JS', 'cmxr-canvas-motion-backgrounds' ); ?></th><td><code><?php echo esc_html( $system['engine_url'] ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Detect JS', 'cmxr-canvas-motion-backgrounds' ); ?></th><td><code><?php echo esc_html( $system['detect_url'] ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Public CSS', 'cmxr-canvas-motion-backgrounds' ); ?></th><td><code><?php echo esc_html( $system['css_url'] ); ?></code></td></tr>
			</tbody>
		</table>
	</div>

	<!-- Active Settings -->
	<div class="cmxr-page-card">
		<h2 class="cmxr-section-title"><?php esc_html_e( 'Active Settings', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
		<pre class="cmxr-json-dump"><?php echo esc_html( wp_json_encode( $system['settings'], JSON_PRETTY_PRINT ) ); ?></pre>
	</div>

	<!-- Registered Animations -->
	<div class="cmxr-page-card">
		<h2 class="cmxr-section-title"><?php esc_html_e( 'Registered Animations', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
		<?php if ( empty( $animations ) ) : ?>
			<p><?php esc_html_e( 'No animations registered.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
		<?php else : ?>
			<?php foreach ( $animations as $anim ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
				<div class="cmxr-debug-anim-block <?php echo $anim['active'] ? 'is-active' : 'is-inactive'; ?>">
					<div class="cmxr-debug-anim-header">
						<strong><?php echo esc_html( $anim['title'] ); ?></strong>
						<code>#<?php echo esc_html( $anim['anim_id'] ); ?></code>
						<span class="cmxr-status-badge"><?php echo $anim['active'] ? esc_html__( 'Active', 'cmxr-canvas-motion-backgrounds' ) : esc_html__( 'Inactive', 'cmxr-canvas-motion-backgrounds' ); ?></span>
						<?php /* translators: %d: number of shapes */ ?>
						<span><?php echo esc_html( sprintf( _n( '%d shape', '%d shapes', $anim['orb_count'], 'cmxr-canvas-motion-backgrounds' ), $anim['orb_count'] ) ); ?></span>
						<button class="button button-small cmxr-debug-toggle-json" data-target="json-<?php echo esc_attr( $anim['id'] ); ?>">
							<?php esc_html_e( 'Show Config', 'cmxr-canvas-motion-backgrounds' ); ?>
						</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr-edit&id=' . $anim['id'] ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Edit', 'cmxr-canvas-motion-backgrounds' ); ?>
						</a>
					</div>
					<pre class="cmxr-json-dump cmxr-debug-json is-hidden" id="json-<?php echo esc_attr( $anim['id'] ); ?>"><?php echo esc_html( wp_json_encode( $anim['config'], JSON_PRETTY_PRINT ) ); ?></pre>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<!-- Browser Compatibility Info -->
	<div class="cmxr-page-card">
		<h2 class="cmxr-section-title"><?php esc_html_e( 'Browser Compatibility', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Checked in browser at runtime. Open browser console on any page with CMXR animations for a detailed report.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
		<table class="widefat fixed striped cmxr-debug-table cmxr-mt-12">
			<thead><tr><th><?php esc_html_e( 'Feature', 'cmxr-canvas-motion-backgrounds' ); ?></th><th><?php esc_html_e( 'Required', 'cmxr-canvas-motion-backgrounds' ); ?></th></tr></thead>
			<tbody>
				<tr><td>Canvas 2D</td><td><?php esc_html_e( 'Yes', 'cmxr-canvas-motion-backgrounds' ); ?></td></tr>
				<tr><td>IntersectionObserver</td><td><?php esc_html_e( 'Recommended (graceful fallback)', 'cmxr-canvas-motion-backgrounds' ); ?></td></tr>
				<tr><td>ResizeObserver</td><td><?php esc_html_e( 'Recommended (graceful fallback)', 'cmxr-canvas-motion-backgrounds' ); ?></td></tr>
				<tr><td>requestAnimationFrame</td><td><?php esc_html_e( 'Yes', 'cmxr-canvas-motion-backgrounds' ); ?></td></tr>
				<tr><td>pointer events</td><td><?php esc_html_e( 'Optional (interactivity)', 'cmxr-canvas-motion-backgrounds' ); ?></td></tr>
			</tbody>
		</table>
	</div>

	<?php CMXR_Dashboard::render_footer(); ?>

</div>
