<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap cmxr-wrap">

	<?php
	CMXR_Dashboard::render_header(
		__( 'ExploreXR', 'cmxr-canvas-motion-backgrounds' )
	);
	?>

	<div class="cmxr-xr-hero">
		<?php
		// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Plugin promo logo
		printf(
			'<img src="%s" alt="%s" class="cmxr-xr-hero-logo" loading="lazy">',
			esc_url( CMXR_PLUGIN_URL . 'assets/img/exploreXR-Logo.png' ),
			esc_attr__( 'ExploreXR Logo', 'cmxr-canvas-motion-backgrounds' )
		);
		?>
		<h2><?php esc_html_e( 'Interactive 3D models for WordPress', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
		<p>
			<?php esc_html_e( 'ExploreXR brings interactive 3D models to your website with zero coding required — from the same ExpoXR family as CMXR. Upload GLB, GLTF and USDZ files, embed them anywhere with a shortcode, and let visitors rotate, zoom and even view your models in Augmented Reality.', 'cmxr-canvas-motion-backgrounds' ); ?>
		</p>
		<ul class="cmxr-xr-highlights">
			<li><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Drag-and-drop model uploads', 'cmxr-canvas-motion-backgrounds' ); ?></li>
			<li><span class="dashicons dashicons-smartphone"></span> <?php esc_html_e( 'Augmented Reality on mobile devices', 'cmxr-canvas-motion-backgrounds' ); ?></li>
			<li><span class="dashicons dashicons-performance"></span> <?php esc_html_e( 'Progressive loading optimization', 'cmxr-canvas-motion-backgrounds' ); ?></li>
			<li><span class="dashicons dashicons-layout"></span> <?php esc_html_e( 'Elementor, Divi & Gutenberg integrations', 'cmxr-canvas-motion-backgrounds' ); ?></li>
		</ul>
	</div>

	<div class="cmxr-xr-tiers">

		<div class="cmxr-xr-card">
			<h3><?php esc_html_e( 'ExploreXR Free', 'cmxr-canvas-motion-backgrounds' ); ?></h3>
			<p class="cmxr-xr-card-tagline"><?php esc_html_e( 'Get started with 3D on WordPress.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'Core 3D viewer (GLB / GLTF / USDZ)', 'cmxr-canvas-motion-backgrounds' ); ?></li>
				<li><?php esc_html_e( 'One free addon (AR, Animation, Loading, or Annotations)', 'cmxr-canvas-motion-backgrounds' ); ?></li>
				<li><?php esc_html_e( 'Compression decoders: Draco, KTX2, Meshopt', 'cmxr-canvas-motion-backgrounds' ); ?></li>
				<li><?php esc_html_e( 'Community support', 'cmxr-canvas-motion-backgrounds' ); ?></li>
			</ul>
			<a href="https://wordpress.org/plugins/explorexr/" target="_blank" rel="noopener" class="button button-secondary">
				<?php esc_html_e( 'Install from WordPress.org', 'cmxr-canvas-motion-backgrounds' ); ?>
			</a>
		</div>

		<div class="cmxr-xr-card cmxr-xr-card-highlight">
			<h3><?php esc_html_e( 'ExploreXR Premium', 'cmxr-canvas-motion-backgrounds' ); ?></h3>
			<p class="cmxr-xr-card-tagline"><?php esc_html_e( 'Rich production sites with AR and commerce.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'Everything in Free', 'cmxr-canvas-motion-backgrounds' ); ?></li>
				<li><?php esc_html_e( 'Commercial addons: AR, Animation, Annotations, Camera controls & more', 'cmxr-canvas-motion-backgrounds' ); ?></li>
				<li><?php esc_html_e( 'Page builder widgets (Elementor, Divi, Avada)', 'cmxr-canvas-motion-backgrounds' ); ?></li>
				<li><?php esc_html_e( 'Priority email support & automatic updates', 'cmxr-canvas-motion-backgrounds' ); ?></li>
			</ul>
			<a href="https://expoxr.com/explorexr/pricing/" target="_blank" rel="noopener" class="button button-primary">
				<?php esc_html_e( 'View Pricing', 'cmxr-canvas-motion-backgrounds' ); ?>
			</a>
		</div>

	</div>

	<?php
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$cmxr_addons = array(
		array(
			'title'    => __( 'Morphing Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/morphing/',
			'features' => array(
				__( 'Morph between different model versions or states', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Trigger via buttons or scroll', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Adjustable timing & easing curves', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Seamless visual transitions', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'Environment & Lighting Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/environment/',
			'features' => array(
				__( 'Background presets', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Reflection and ambience controls', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Environment lighting adjustments', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Visual consistency across models', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'Post-Processing Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/post-processing/',
			'features' => array(
				__( 'Bloom', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Depth of Field (DOF)', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Screen Space Ambient Occlusion (SSAO)', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Tone mapping & color grading', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'Annotations Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/annotations/',
			'features' => array(
				__( '4 annotation types: hotspots, animated, dimension labels, camera-view', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Unlimited annotations per model', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Style & color customization', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Hover or click interaction modes', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Rich text + image content with built-in editor', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Fully responsive', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'Animation Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/animation/',
			'features' => array(
				__( 'Multiple animation support per model', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Play, pause, reset controls', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Custom timing & sequencing', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Ping-pong loop mode', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Crossfade transitions between animations', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Responsive animation UI', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'Materials & Variants Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/materials-variants/',
			'features' => array(
				__( 'Automatic detection of glTF material variants', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Real-time material switching without reloads', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Multiple UI styles: buttons, tabs, dropdowns', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Custom labels, previews, and variant naming', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Smart caching for fast switching', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Fully responsive on all devices', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'Loading Options Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/loading-options/',
			'features' => array(
				__( 'Fully customizable loading bar (position, height, colors)', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Percentage counters & custom loading messages', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Multi-language support', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Background overlays, gradients & blur effects', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Lazy loading & script optimization', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Accessibility support (screen readers, reduced motion, dark mode)', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'Mouse3D Control Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/mouse3d/',
			'features' => array(
				__( 'Mouse-based camera rotation', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Optional zoom behavior', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Subtle parallax-style interaction', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Lightweight & performance-friendly', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'AR Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/ar/',
			'features' => array(
				__( 'WebXR support (browser-based AR)', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Android Scene Viewer integration', 'cmxr-canvas-motion-backgrounds' ),
				__( 'iOS Quick Look (USDZ support)', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Customizable AR buttons', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Floor, wall, and object placement', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Scaling & positioning controls', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'Draggable Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/draggable/',
			'features' => array(
				__( 'Drag & reposition models on frontend', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Optional position memory', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Boundary constraints for layouts', 'cmxr-canvas-motion-backgrounds' ),
			),
		),
		array(
			'title'    => __( 'Expert Camera Mode Add-On', 'cmxr-canvas-motion-backgrounds' ),
			'url'      => 'https://expoxr.com/addon/expert-camera-mode/',
			'features' => array(
				__( 'Custom orbit angles & camera presets', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Field of view (FOV) adjustment', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Min/max orbit & zoom limits', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Touch & mouse gesture customization', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Smooth camera interpolation & custom target focus', 'cmxr-canvas-motion-backgrounds' ),
				__( 'Optional user interaction prompts', 'cmxr-canvas-motion-backgrounds' ),
			),
		),		
	);
	?>

	<div class="cmxr-xr-demos-section">
		<h2 class="cmxr-section-title"><?php esc_html_e( 'ExploreXR Add-Ons & Demos', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
		<p class="cmxr-xr-demos-intro"><?php esc_html_e( 'Explore everything the ExploreXR family can do.', 'cmxr-canvas-motion-backgrounds' ); ?></p>

		<div class="cmxr-xr-demos">
			<?php foreach ( $cmxr_addons as $addon ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
				<div class="cmxr-page-card cmxr-xr-demo-card">
					<h3><?php echo esc_html( $addon['title'] ); ?></h3>
					<ul>
						<?php foreach ( $addon['features'] as $feature ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
					<a href="<?php echo esc_url( $addon['url'] ); ?>" target="_blank" rel="noopener" class="button button-secondary">
						<?php esc_html_e( 'Open Demo', 'cmxr-canvas-motion-backgrounds' ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php CMXR_Dashboard::render_footer(); ?>

</div>
