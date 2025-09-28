<?php
/**
 * Block Information.
 *
 * @since 1.2.0
 *
 * @package spectra-pro
 */

$block_slug = 'uagb/loop-sort';
$block_data = array(
	'slug'           => '',
	'link'           => '',
	'title'          => __( 'Sorting - Filter', 'spectra-pro' ),
	'description'    => __( 'Sorting filter allows user to sort posts in the loop.', 'spectra-pro' ),
	'default'        => true,
	'is_child'       => true,
	'deprecated'     => false,
	'dynamic_assets' => array(
		'dir'        => 'loop-sort',
		'plugin-dir' => SPECTRA_PRO_DIR . '/',
	),
);
