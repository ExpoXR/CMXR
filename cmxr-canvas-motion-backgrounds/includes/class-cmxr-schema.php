<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Single source of truth for allowed enum values used across config
 * sanitization (CMXR_CPT), plugin settings (CMXR_Settings) and the
 * admin UI <select> option lists. Keeps validation and UI from drifting apart.
 */
class CMXR_Schema {

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
				'label'  => __( 'Soft Orbs', 'cmxr-canvas-motion-backgrounds' ),
				'shapes' => array(
					'circle'         => __( 'Circle', 'cmxr-canvas-motion-backgrounds' ),
					'double'         => __( 'Double', 'cmxr-canvas-motion-backgrounds' ),
					'triple'         => __( 'Triple', 'cmxr-canvas-motion-backgrounds' ),
					'blob'           => __( 'Blob', 'cmxr-canvas-motion-backgrounds' ),
					'circle-outline' => __( 'Outline', 'cmxr-canvas-motion-backgrounds' ),
					'ring'           => __( 'Ring', 'cmxr-canvas-motion-backgrounds' ),
				),
			),
			array(
				'label'  => __( 'Geometry', 'cmxr-canvas-motion-backgrounds' ),
				'shapes' => array(
					'rect'            => __( 'Box', 'cmxr-canvas-motion-backgrounds' ),
					'rect-outline'    => __( 'Box Outline', 'cmxr-canvas-motion-backgrounds' ),
					'capsule'         => __( 'Capsule', 'cmxr-canvas-motion-backgrounds' ),
					'capsule-outline' => __( 'Capsule Outline', 'cmxr-canvas-motion-backgrounds' ),
				),
			),
			array(
				'label'  => __( 'Lines', 'cmxr-canvas-motion-backgrounds' ),
				'shapes' => array(
					'line'      => __( 'Line', 'cmxr-canvas-motion-backgrounds' ),
					'wave-line' => __( 'Wave Line', 'cmxr-canvas-motion-backgrounds' ),
				),
			),
		);
	}

	public static function get_interaction_direction_labels() {
		return array(
			'normal'  => __( 'Normal', 'cmxr-canvas-motion-backgrounds' ),
			'reverse' => __( 'Reverse', 'cmxr-canvas-motion-backgrounds' ),
		);
	}

	public static function get_color_animation_labels() {
		return array(
			'none'       => __( 'None', 'cmxr-canvas-motion-backgrounds' ),
			'left-right' => __( 'Left to Right', 'cmxr-canvas-motion-backgrounds' ),
			'right-left' => __( 'Right to Left', 'cmxr-canvas-motion-backgrounds' ),
			'top-bottom' => __( 'Top to Bottom', 'cmxr-canvas-motion-backgrounds' ),
			'bottom-top' => __( 'Bottom to Top', 'cmxr-canvas-motion-backgrounds' ),
			'both'       => __( 'Both Axes', 'cmxr-canvas-motion-backgrounds' ),
		);
	}
}
