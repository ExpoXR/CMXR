<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all cmxr_animation posts and their meta
$posts = get_posts( array(
	'post_type'      => 'cmxr_animation',
	'post_status'    => 'any',
	'numberposts'    => -1,
	'fields'         => 'ids',
) );

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}

delete_option( 'cmxr_settings' );
