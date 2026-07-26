<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap cmxr-wrap" id="cmxr-template-library" data-rest-url="<?php echo esc_url( rest_url( 'cmxr/v1' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
	<?php CMXR_Dashboard::render_header( __( 'New Animation', 'cmxr-canvas-motion-backgrounds' ) ); ?>

	<div class="cmxr-library-heading">
		<div>
			<h2><?php esc_html_e( 'Choose a template', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
			<p><?php esc_html_e( 'Start from a responsive ExpoXR effect or use the classic shape editor.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
		</div>
	</div>

	<div class="cmxr-template-grid">
		<?php foreach ( $templates as $cmxr_template ) : ?>
			<article class="cmxr-template-card">
				<div class="cmxr-template-card-preview cmxr-template-preview-<?php echo esc_attr( $cmxr_template['variant'] ); ?>" aria-hidden="true">
					<span></span><span></span><span></span>
				</div>
				<div class="cmxr-template-card-body">
					<div class="cmxr-template-badges">
						<span><?php esc_html_e( 'Priority 1', 'cmxr-canvas-motion-backgrounds' ); ?></span>
						<span><?php echo esc_html( ucfirst( $cmxr_template['performance'] ?? 'low' ) ); ?> <?php esc_html_e( 'load', 'cmxr-canvas-motion-backgrounds' ); ?></span>
					</div>
					<h3><?php echo esc_html( $cmxr_template['title'] ); ?></h3>
					<p><?php echo esc_html( $cmxr_template['description'] ?? '' ); ?></p>
					<ul class="cmxr-template-capabilities">
						<?php foreach ( array_slice( (array) ( $cmxr_template['capabilities'] ?? array() ), 0, 4 ) as $cmxr_capability ) : ?>
							<li><?php echo esc_html( ucwords( str_replace( '-', ' ', $cmxr_capability ) ) ); ?></li>
						<?php endforeach; ?>
					</ul>
					<button type="button" class="button button-primary cmxr-use-template" data-template-slug="<?php echo esc_attr( $cmxr_template['slug'] ); ?>" data-template-title="<?php echo esc_attr( $cmxr_template['title'] ); ?>">
						<?php esc_html_e( 'Use Template', 'cmxr-canvas-motion-backgrounds' ); ?>
					</button>
				</div>
			</article>
		<?php endforeach; ?>

		<article class="cmxr-template-card cmxr-template-card-legacy">
			<div class="cmxr-template-card-preview cmxr-template-preview-legacy" aria-hidden="true"><span></span><span></span><span></span></div>
			<div class="cmxr-template-card-body">
				<div class="cmxr-template-badges"><span><?php esc_html_e( 'Classic', 'cmxr-canvas-motion-backgrounds' ); ?></span></div>
				<h3><?php esc_html_e( 'Blank Layered Shapes', 'cmxr-canvas-motion-backgrounds' ); ?></h3>
				<p><?php esc_html_e( 'Build a custom background from individual shapes, colors, and motion paths.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr-new&legacy=1' ) ); ?>"><?php esc_html_e( 'Start Blank', 'cmxr-canvas-motion-backgrounds' ); ?></a>
			</div>
		</article>
	</div>

	<div class="cmxr-template-dialog" id="cmxr-template-dialog" hidden>
		<div class="cmxr-template-dialog-backdrop"></div>
		<div class="cmxr-template-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="cmxr-template-dialog-title">
			<h2 id="cmxr-template-dialog-title"><?php esc_html_e( 'Create from template', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
			<label>
				<span><?php esc_html_e( 'Animation name', 'cmxr-canvas-motion-backgrounds' ); ?></span>
				<input type="text" id="cmxr-template-title" maxlength="200">
			</label>
			<label>
				<span><?php esc_html_e( 'CSS ID', 'cmxr-canvas-motion-backgrounds' ); ?></span>
				<input type="text" id="cmxr-template-target" maxlength="100" pattern="[A-Za-z][A-Za-z0-9_-]*">
			</label>
			<p class="cmxr-template-dialog-error" aria-live="polite"></p>
			<div class="cmxr-template-dialog-actions">
				<button type="button" class="button" id="cmxr-template-cancel"><?php esc_html_e( 'Cancel', 'cmxr-canvas-motion-backgrounds' ); ?></button>
				<button type="button" class="button button-primary" id="cmxr-template-create"><?php esc_html_e( 'Create Animation', 'cmxr-canvas-motion-backgrounds' ); ?></button>
			</div>
		</div>
	</div>
	<?php CMXR_Dashboard::render_footer(); ?>
</div>
