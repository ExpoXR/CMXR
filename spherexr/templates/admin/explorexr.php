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

	<?php
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$sxr_addons = array(
		array(
			'title'    => __( 'Morphing Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/morphing/',
			'features' => array(
				__( 'Morph between different model versions or states', 'spherexr' ),
				__( 'Trigger via buttons or scroll', 'spherexr' ),
				__( 'Adjustable timing & easing curves', 'spherexr' ),
				__( 'Seamless visual transitions', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'Environment & Lighting Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/environment/',
			'features' => array(
				__( 'Background presets', 'spherexr' ),
				__( 'Reflection and ambience controls', 'spherexr' ),
				__( 'Environment lighting adjustments', 'spherexr' ),
				__( 'Visual consistency across models', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'Post-Processing Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/post-processing/',
			'features' => array(
				__( 'Bloom', 'spherexr' ),
				__( 'Depth of Field (DOF)', 'spherexr' ),
				__( 'Screen Space Ambient Occlusion (SSAO)', 'spherexr' ),
				__( 'Tone mapping & color grading', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'Annotations Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/annotations/',
			'features' => array(
				__( '4 annotation types: hotspots, animated, dimension labels, camera-view', 'spherexr' ),
				__( 'Unlimited annotations per model', 'spherexr' ),
				__( 'Style & color customization', 'spherexr' ),
				__( 'Hover or click interaction modes', 'spherexr' ),
				__( 'Rich text + image content with built-in editor', 'spherexr' ),
				__( 'Fully responsive', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'Animation Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/animation/',
			'features' => array(
				__( 'Multiple animation support per model', 'spherexr' ),
				__( 'Play, pause, reset controls', 'spherexr' ),
				__( 'Custom timing & sequencing', 'spherexr' ),
				__( 'Ping-pong loop mode', 'spherexr' ),
				__( 'Crossfade transitions between animations', 'spherexr' ),
				__( 'Responsive animation UI', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'Materials & Variants Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/materials-variants/',
			'features' => array(
				__( 'Automatic detection of glTF material variants', 'spherexr' ),
				__( 'Real-time material switching without reloads', 'spherexr' ),
				__( 'Multiple UI styles: buttons, tabs, dropdowns', 'spherexr' ),
				__( 'Custom labels, previews, and variant naming', 'spherexr' ),
				__( 'Smart caching for fast switching', 'spherexr' ),
				__( 'Fully responsive on all devices', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'Loading Options Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/loading-options/',
			'features' => array(
				__( 'Fully customizable loading bar (position, height, colors)', 'spherexr' ),
				__( 'Percentage counters & custom loading messages', 'spherexr' ),
				__( 'Multi-language support', 'spherexr' ),
				__( 'Background overlays, gradients & blur effects', 'spherexr' ),
				__( 'Lazy loading & script optimization', 'spherexr' ),
				__( 'Accessibility support (screen readers, reduced motion, dark mode)', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'Mouse3D Control Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/mouse3d/',
			'features' => array(
				__( 'Mouse-based camera rotation', 'spherexr' ),
				__( 'Optional zoom behavior', 'spherexr' ),
				__( 'Subtle parallax-style interaction', 'spherexr' ),
				__( 'Lightweight & performance-friendly', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'AR Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/ar/',
			'features' => array(
				__( 'WebXR support (browser-based AR)', 'spherexr' ),
				__( 'Android Scene Viewer integration', 'spherexr' ),
				__( 'iOS Quick Look (USDZ support)', 'spherexr' ),
				__( 'Customizable AR buttons', 'spherexr' ),
				__( 'Floor, wall, and object placement', 'spherexr' ),
				__( 'Scaling & positioning controls', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'Draggable Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/draggable/',
			'features' => array(
				__( 'Drag & reposition models on frontend', 'spherexr' ),
				__( 'Optional position memory', 'spherexr' ),
				__( 'Boundary constraints for layouts', 'spherexr' ),
			),
		),
		array(
			'title'    => __( 'Expert Camera Mode Add-On', 'spherexr' ),
			'url'      => 'https://expoxr.com/addon/expert-camera-mode/',
			'features' => array(
				__( 'Custom orbit angles & camera presets', 'spherexr' ),
				__( 'Field of view (FOV) adjustment', 'spherexr' ),
				__( 'Min/max orbit & zoom limits', 'spherexr' ),
				__( 'Touch & mouse gesture customization', 'spherexr' ),
				__( 'Smooth camera interpolation & custom target focus', 'spherexr' ),
				__( 'Optional user interaction prompts', 'spherexr' ),
			),
		),		
	);
	?>

	<div class="sxr-xr-demos-section">
		<h2 class="sxr-section-title"><?php esc_html_e( 'ExploreXR Add-Ons & Demos', 'spherexr' ); ?></h2>
		<p class="sxr-xr-demos-intro"><?php esc_html_e( 'Explore everything the ExploreXR family can do.', 'spherexr' ); ?></p>

		<div class="sxr-xr-demos">
			<?php foreach ( $sxr_addons as $addon ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
				<div class="sxr-page-card sxr-xr-demo-card">
					<h3><?php echo esc_html( $addon['title'] ); ?></h3>
					<ul>
						<?php foreach ( $addon['features'] as $feature ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
					<a href="<?php echo esc_url( $addon['url'] ); ?>" target="_blank" rel="noopener" class="button button-secondary">
						<?php esc_html_e( 'Open Demo', 'spherexr' ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php SphereXR_Dashboard::render_footer(); ?>

</div>
