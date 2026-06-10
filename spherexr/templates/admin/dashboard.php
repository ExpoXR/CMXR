<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap spherexr-wrap">

	<?php
	SphereXR_Dashboard::render_header(
		__( 'SphereXR Dashboard', 'spherexr' ),
		'<a href="' . esc_url( admin_url( 'admin.php?page=spherexr-new' ) ) . '" class="button button-primary">+ ' . esc_html__( 'New Animation', 'spherexr' ) . '</a>'
	);
	?>

	<?php if ( empty( $animations ) ) : ?>
		<div class="spherexr-empty-state">
			<div class="spherexr-empty-icon">&#9711;</div>
			<h2><?php esc_html_e( 'No animations yet', 'spherexr' ); ?></h2>
			<p><?php esc_html_e( 'Create your first orb background animation and attach it to any Elementor section via CSS ID.', 'spherexr' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr-new' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Create Animation', 'spherexr' ); ?>
			</a>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped spherexr-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'spherexr' ); ?></th>
					<th><?php esc_html_e( 'CSS ID', 'spherexr' ); ?></th>
					<th><?php esc_html_e( 'Orbs', 'spherexr' ); ?></th>
					<th><?php esc_html_e( 'Status', 'spherexr' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'spherexr' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $animations as $anim ) : ?>
					<tr data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>">
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr-edit&id=' . $anim['post']->ID ) ); ?>">
									<?php echo esc_html( $anim['post']->post_title ); ?>
								</a>
							</strong>
						</td>
						<td>
							<?php if ( $anim['anim_id'] ) : ?>
								<code class="spherexr-id-chip">#<?php echo esc_html( $anim['anim_id'] ); ?></code>
								<button class="spherexr-copy-btn" data-copy="#<?php echo esc_attr( $anim['anim_id'] ); ?>" title="<?php esc_attr_e( 'Copy ID', 'spherexr' ); ?>">&#9650;</button>
							<?php else : ?>
								<span class="spherexr-no-id"><?php esc_html_e( '— not set —', 'spherexr' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $anim['orb_count'] ); ?></td>
						<td>
							<button class="spherexr-toggle-btn <?php echo $anim['active'] ? 'is-active' : ''; ?>"
								data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>"
								title="<?php esc_attr_e( 'Toggle active', 'spherexr' ); ?>">
								<?php echo $anim['active'] ? esc_html__( 'Active', 'spherexr' ) : esc_html__( 'Inactive', 'spherexr' ); ?>
							</button>
						</td>
						<td class="spherexr-actions">
							<button class="button button-small spherexr-preview-btn"
								data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>"
								data-title="<?php echo esc_attr( $anim['post']->post_title ); ?>">
								<?php esc_html_e( 'Preview', 'spherexr' ); ?>
							</button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=spherexr-edit&id=' . $anim['post']->ID ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'spherexr' ); ?>
							</a>
							<button class="button button-small spherexr-duplicate-btn" data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>">
								<?php esc_html_e( 'Duplicate', 'spherexr' ); ?>
							</button>
							<button class="button button-small spherexr-delete-btn" data-post-id="<?php echo esc_attr( $anim['post']->ID ); ?>"
								data-confirm="<?php esc_attr_e( 'Delete this animation? This cannot be undone.', 'spherexr' ); ?>">
								<?php esc_html_e( 'Delete', 'spherexr' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php SphereXR_Dashboard::render_footer(); ?>

</div>
