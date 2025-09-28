<?php
/**
 * Block Information.
 *
 * @since 1.2.0
 *
 * @package spectra-pro
 */

$block_slug = 'uagb/loop-reset';
$block_data = array(
	'slug'           => '',
	'link'           => '',
	'title'          => __( 'Reset - Filter', 'spectra-pro' ),
	'description'    => __( 'Reset filter allows user to reset the loop filter.', 'spectra-pro' ),
	'default'        => true,
	'is_child'       => true,
	'deprecated'     => false,
	'dynamic_assets' => array(
		'dir'        => 'loop-reset',
		'plugin-dir' => SPECTRA_PRO_DIR . '/',
	),
);
