<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap cmxr-wrap">

	<?php
	CMXR_Dashboard::render_header(
		__( 'CMXR Dashboard', 'cmxr-canvas-motion-backgrounds' ),
		'<a href="' . esc_url( admin_url( 'admin.php?page=cmxr-new' ) ) . '" class="button button-primary">+ ' . esc_html__( 'New Animation', 'cmxr-canvas-motion-backgrounds' ) . '</a>'
	);
	?>

	<?php if ( empty( $animations ) ) : ?>
		<div class="cmxr-empty-state">
			<div class="cmxr-empty-icon">&#9711;</div>
			<h2><?php esc_html_e( 'No animations yet', 'cmxr-canvas-motion-backgrounds' ); ?></h2>
			<p><?php esc_html_e( 'Create your first animated canvas background and attach it to any Elementor section via CSS ID.', 'cmxr-canvas-motion-backgrounds' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr-new' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Create Animation', 'cmxr-canvas-motion-backgrounds' ); ?>
			</a>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped cmxr-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'cmxr-canvas-motion-backgrounds' ); ?></th>
					<th><?php esc_html_e( 'CSS ID', 'cmxr-canvas-motion-backgrounds' ); ?></th>
					<th><?php esc_html_e( 'Shapes', 'cmxr-canvas-motion-backgrounds' ); ?></th>
					<th><?php esc_html_e( 'Status', 'cmxr-canvas-motion-backgrounds' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'cmxr-canvas-motion-backgrounds' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $animations as $anim ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
					<tr data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>">
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr-edit&id=' . $anim['post']->ID ) ); ?>">
									<?php echo esc_html( $anim['post']->post_title ); ?>
								</a>
							</strong>
						</td>
						<td>
							<?php if ( $anim['anim_id'] ) : ?>
								<code class="cmxr-id-chip">#<?php echo esc_html( $anim['anim_id'] ); ?></code>
								<button class="cmxr-copy-btn" data-copy="#<?php echo esc_attr( $anim['anim_id'] ); ?>" title="<?php esc_attr_e( 'Copy ID', 'cmxr-canvas-motion-backgrounds' ); ?>">&#9650;</button>
							<?php else : ?>
								<span class="cmxr-no-id"><?php esc_html_e( '— not set —', 'cmxr-canvas-motion-backgrounds' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $anim['orb_count'] ); ?></td>
						<td>
							<button class="cmxr-toggle-btn <?php echo $anim['active'] ? 'is-active' : ''; ?>"
								data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>"
								title="<?php esc_attr_e( 'Toggle active', 'cmxr-canvas-motion-backgrounds' ); ?>">
								<?php echo $anim['active'] ? esc_html__( 'Active', 'cmxr-canvas-motion-backgrounds' ) : esc_html__( 'Inactive', 'cmxr-canvas-motion-backgrounds' ); ?>
							</button>
						</td>
						<td class="cmxr-actions">
							<button class="button button-small cmxr-preview-btn"
								data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>"
								data-title="<?php echo esc_attr( $anim['post']->post_title ); ?>">
								<?php esc_html_e( 'Preview', 'cmxr-canvas-motion-backgrounds' ); ?>
							</button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=cmxr-edit&id=' . $anim['post']->ID ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'cmxr-canvas-motion-backgrounds' ); ?>
							</a>
							<button class="button button-small cmxr-duplicate-btn" data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>">
								<?php esc_html_e( 'Duplicate', 'cmxr-canvas-motion-backgrounds' ); ?>
							</button>
							<button class="button button-small cmxr-delete-btn" data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>"
								data-confirm="<?php esc_attr_e( 'Delete this animation? This cannot be undone.', 'cmxr-canvas-motion-backgrounds' ); ?>">
								<?php esc_html_e( 'Delete', 'cmxr-canvas-motion-backgrounds' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php CMXR_Dashboard::render_footer(); ?>

</div>
