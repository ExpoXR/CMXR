<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Single source of truth for allowed enum values used across config
 * sanitization (SphereXR_CPT), plugin settings (SphereXR_Settings) and the
 * admin UI <select> option lists. Keeps validation and UI from drifting apart.
 */
class SphereXR_Schema {

	const BLEND_MODES        = array( 'screen', 'normal', 'multiply', 'overlay', 'lighten', 'hard-light' );
	const SHAPES             = array( 'circle', 'double', 'triple', 'blob', 'circle-outline', 'ring', 'line', 'wave-line', 'rect', 'rect-outline', 'capsule', 'capsule-outline' );
	const ANIM_TYPES         = array( 'drift', 'orbit', 'pulse', 'wave', 'fixed', 'figure8' );
	const UNITS              = array( 'percent', 'px', 'vw', 'vh' );
	const COLOR_MODES        = array( 'solid', 'dual', 'gradient' );
	const COLOR_ANIMATIONS   = array( 'none', 'left-right', 'right-left', 'top-bottom', 'bottom-top', 'both' );
	const INTERACTIVITY_MODES = array( 'parallax', 'repel', 'attract', 'none' );
	const INTERACTION_DIRECTIONS = array( 'normal', 'reverse' );

	public static function get_shape_groups() {
		return array(
			array(
				'label'  => __( 'Soft Orbs', 'spherexr' ),
				'shapes' => array(
					'circle'         => __( 'Circle', 'spherexr' ),
					'double'         => __( 'Double', 'spherexr' ),
					'triple'         => __( 'Triple', 'spherexr' ),
					'blob'           => __( 'Blob', 'spherexr' ),
					'circle-outline' => __( 'Outline', 'spherexr' ),
					'ring'           => __( 'Ring', 'spherexr' ),
				),
			),
			array(
				'label'  => __( 'Geometry', 'spherexr' ),
				'shapes' => array(
					'rect'            => __( 'Box', 'spherexr' ),
					'rect-outline'    => __( 'Box Outline', 'spherexr' ),
					'capsule'         => __( 'Capsule', 'spherexr' ),
					'capsule-outline' => __( 'Capsule Outline', 'spherexr' ),
				),
			),
			array(
				'label'  => __( 'Lines', 'spherexr' ),
				'shapes' => array(
					'line'      => __( 'Line', 'spherexr' ),
					'wave-line' => __( 'Wave Line', 'spherexr' ),
				),
			),
		);
	}

	public static function get_interaction_direction_labels() {
		return array(
			'normal'  => __( 'Normal', 'spherexr' ),
			'reverse' => __( 'Reverse', 'spherexr' ),
		);
	}

	public static function get_color_animation_labels() {
		return array(
			'none'       => __( 'None', 'spherexr' ),
			'left-right' => __( 'Left to Right', 'spherexr' ),
			'right-left' => __( 'Right to Left', 'spherexr' ),
			'top-bottom' => __( 'Top to Bottom', 'spherexr' ),
			'bottom-top' => __( 'Bottom to Top', 'spherexr' ),
			'both'       => __( 'Both Axes', 'spherexr' ),
		);
	}
}
