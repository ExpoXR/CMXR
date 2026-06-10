<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap spherexr-wrap">

	<?php
	SphereXR_Dashboard::render_header(
		__( 'Debug', 'spherexr' ),
		'<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=spherexr-debug&action=export' ), 'spherexr_export' ) ) . '" class="button">' . esc_html__( 'Export All Configs (JSON)', 'spherexr' ) . '</a>'
	);
	?>

	<!-- System Info -->
	<div class="sxr-page-card">
		<h2 class="sxr-section-title"><?php esc_html_e( 'System Info', 'spherexr' ); ?></h2>
		<table class="widefat fixed striped spherexr-debug-table">
			<tbody>
				<tr><th><?php esc_html_e( 'PHP Version', 'spherexr' ); ?></th><td><?php echo esc_html( $system['php_version'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'WordPress Version', 'spherexr' ); ?></th><td><?php echo esc_html( $system['wp_version'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Active Theme', 'spherexr' ); ?></th><td><?php echo esc_html( $system['theme'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Plugin Version', 'spherexr' ); ?></th><td><?php echo esc_html( $system['plugin_ver'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'REST API Namespace', 'spherexr' ); ?></th><td><code><?php echo esc_html( $system['rest_url'] ); ?></code></td></tr>
			</tbody>
		</table>
	</div>

	<!-- Asset URLs -->
	<div class="sxr-page-card">
		<h2 class="sxr-section-title"><?php esc_html_e( 'Asset URLs', 'spherexr' ); ?></h2>
		<table class="widefat fixed striped spherexr-debug-table">
			<tbody>
				<tr><th><?php esc_html_e( 'Engine JS', 'spherexr' ); ?></th><td><code><?php echo esc_html( $system['engine_url'] ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Detect JS', 'spherexr' ); ?></th><td><code><?php echo esc_html( $system['detect_url'] ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Public CSS', 'spherexr' ); ?></th><td><code><?php echo esc_html( $system['css_url'] ); ?></code></td></tr>
			</tbody>
		</table>
	</div>

	<!-- Active Settings -->
	<div class="sxr-page-card">
		<h2 class="sxr-section-title"><?php esc_html_e( 'Active Settings', 'spherexr' ); ?></h2>
		<pre class="spherexr-json-dump"><?php echo esc_html( wp_json_encode( $system['settings'], JSON_PRETTY_PRINT ) ); ?></pre>
	</div>

	<!-- Registered Animations -->
	<div class="sxr-page-card">
		<h2 class="sxr-section-title"><?php esc_html_e( 'Registered Animations', 'spherexr' ); ?></h2>
		<?php if ( empty( $animations ) ) : ?>
			<p><?php esc_html_e( 'No animations registered.', 'spherexr' ); ?></p>
		<?php else : ?>
			<?php foreach ( $animations as $anim ) : ?>
				<div class="spherexr-debug-anim-block <?php echo $anim['active'] ? 'is-active' : 'is-inactive'; ?>">
					<div class="spherexr-debug-anim-header">
						<strong><?php echo esc_html( $anim['title'] ); ?></strong>
						<code>#<?php echo esc_html( $anim['anim_id'] ); ?></code>
						<span class="spherexr-status-badge"><?php echo $anim['active'] ? esc_html__( 'Active', 'spherexr' ) : esc_html__( 'Inactive', 'spherexr' ); ?></span>
						<span><?php echo esc_html( sprintf( _n( '%d orb', '%d orbs', $anim['orb_count'], 'spherexr' ), $anim['orb_count'] ) ); ?></span>
						<button class="button button-small spherexr-debug-toggle-json" data-target="json-<?php echo esc_attr( $anim['id'] ); ?>">
							<?php esc_html_e( 'Show Config', 'spherexr' ); ?>
						</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr-edit&id=' . $anim['id'] ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Edit', 'spherexr' ); ?>
						</a>
					</div>
					<pre class="spherexr-json-dump spherexr-debug-json is-hidden" id="json-<?php echo esc_attr( $anim['id'] ); ?>"><?php echo esc_html( wp_json_encode( $anim['config'], JSON_PRETTY_PRINT ) ); ?></pre>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<!-- Browser Compatibility Info -->
	<div class="sxr-page-card">
		<h2 class="sxr-section-title"><?php esc_html_e( 'Browser Compatibility', 'spherexr' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Checked in browser at runtime. Open browser console on any page with SphereXR animations for a detailed report.', 'spherexr' ); ?></p>
		<table class="widefat fixed striped spherexr-debug-table sxr-mt-12">
			<thead><tr><th><?php esc_html_e( 'Feature', 'spherexr' ); ?></th><th><?php esc_html_e( 'Required', 'spherexr' ); ?></th></tr></thead>
			<tbody>
				<tr><td>Canvas 2D</td><td><?php esc_html_e( 'Yes', 'spherexr' ); ?></td></tr>
				<tr><td>IntersectionObserver</td><td><?php esc_html_e( 'Recommended (graceful fallback)', 'spherexr' ); ?></td></tr>
				<tr><td>ResizeObserver</td><td><?php esc_html_e( 'Recommended (graceful fallback)', 'spherexr' ); ?></td></tr>
				<tr><td>requestAnimationFrame</td><td><?php esc_html_e( 'Yes', 'spherexr' ); ?></td></tr>
				<tr><td>pointer events</td><td><?php esc_html_e( 'Optional (interactivity)', 'spherexr' ); ?></td></tr>
			</tbody>
		</table>
	</div>

	<?php SphereXR_Dashboard::render_footer(); ?>

</div>
