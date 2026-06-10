<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap spherexr-wrap">

	<?php
	SphereXR_Dashboard::render_header(
		__( 'ExploreXR', 'spherexr' )
	);
	?>

	<div class="sxr-xr-hero">
		<?php
		// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Plugin promo logo
		printf(
			'<img src="%s" alt="%s" class="sxr-xr-hero-logo" loading="lazy">',
			esc_url( SPHEREXR_PLUGIN_URL . 'assets/img/exploreXR-Logo.png' ),
			esc_attr__( 'ExploreXR Logo', 'spherexr' )
		);
		?>
		<h2><?php esc_html_e( 'Interactive 3D models for WordPress', 'spherexr' ); ?></h2>
		<p>
			<?php esc_html_e( 'ExploreXR brings interactive 3D models to your website with zero coding required — from the same ExpoXR family as SphereXR. Upload GLB, GLTF and USDZ files, embed them anywhere with a shortcode, and let visitors rotate, zoom and even view your models in Augmented Reality.', 'spherexr' ); ?>
		</p>
		<ul class="sxr-xr-highlights">
			<li><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Drag-and-drop model uploads', 'spherexr' ); ?></li>
			<li><span class="dashicons dashicons-smartphone"></span> <?php esc_html_e( 'Augmented Reality on mobile devices', 'spherexr' ); ?></li>
			<li><span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Progressive loading optimization', 'spherexr' ); ?></li>
			<li><span class="dashicons dashicons-layout"></span> <?php esc_html_e( 'Elementor, Divi & Gutenberg integrations', 'spherexr' ); ?></li>
		</ul>
	</div>

	<div class="sxr-xr-tiers">

		<div class="sxr-xr-card">
			<h3><?php esc_html_e( 'ExploreXR Free', 'spherexr' ); ?></h3>
			<p class="sxr-xr-card-tagline"><?php esc_html_e( 'Get started with 3D on WordPress.', 'spherexr' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'Core 3D viewer (GLB / GLTF / USDZ)', 'spherexr' ); ?></li>
				<li><?php esc_html_e( 'One free addon (AR, Animation, Loading, or Annotations)', 'spherexr' ); ?></li>
				<li><?php esc_html_e( 'Compression decoders: Draco, KTX2, Meshopt', 'spherexr' ); ?></li>
				<li><?php esc_html_e( 'Community support', 'spherexr' ); ?></li>
			</ul>
			<a href="https://wordpress.org/plugins/explorexr/" target="_blank" rel="noopener" class="button button-secondary">
				<?php esc_html_e( 'Install from WordPress.org', 'spherexr' ); ?>
			</a>
		</div>

		<div class="sxr-xr-card sxr-xr-card-highlight">
			<h3><?php esc_html_e( 'ExploreXR Premium', 'spherexr' ); ?></h3>
			<p class="sxr-xr-card-tagline"><?php esc_html_e( 'Rich production sites with AR and commerce.', 'spherexr' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'Everything in Free', 'spherexr' ); ?></li>
				<li><?php esc_html_e( 'Commercial addons: AR, Animation, Annotations, Camera controls & more', 'spherexr' ); ?></li>
				<li><?php esc_html_e( 'Page builder widgets (Elementor, Divi, Avada)', 'spherexr' ); ?></li>
				<li><?php esc_html_e( 'Priority email support & automatic updates', 'spherexr' ); ?></li>
			</ul>
			<a href="https://expoxr.com/explorexr/pricing/" target="_blank" rel="noopener" class="button button-primary">
				<?php esc_html_e( 'View Pricing', 'spherexr' ); ?>
			</a>
		</div>

	</div>

	<p class="sxr-xr-links">
		<a href="https://expoxr.com/explorexr/demo/" target="_blank" rel="noopener"><?php esc_html_e( 'Live Demo', 'spherexr' ); ?></a> ·
		<a href="https://expoxr.com/explorexr/documentation/" target="_blank" rel="noopener"><?php esc_html_e( 'Documentation', 'spherexr' ); ?></a>
	</p>

	<?php SphereXR_Dashboard::render_footer(); ?>

</div>
