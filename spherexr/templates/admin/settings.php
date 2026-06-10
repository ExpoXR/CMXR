<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap spherexr-wrap">

	<?php
	SphereXR_Dashboard::render_header(
		__( 'Settings', 'spherexr' )
	);
	?>

	<div class="sxr-page-card">
		<?php settings_errors( 'spherexr_settings_group' ); ?>
		<form method="post" action="options.php" class="spherexr-settings-form">
			<?php settings_fields( 'spherexr_settings_group' ); ?>
			<?php do_settings_sections( 'spherexr-settings' ); ?>
			<?php submit_button( __( 'Save Settings', 'spherexr' ) ); ?>
		</form>
	</div>

	<?php SphereXR_Dashboard::render_footer(); ?>

</div>
