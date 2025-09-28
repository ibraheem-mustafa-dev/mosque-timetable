<?php
/**
 * Block Information.
 *
 * @since 1.2.0
 *
 * @package spectra-pro
 */

$block_slug = 'uagb/loop-search';
$block_data = array(
	'slug'           => '',
	'link'           => '',
	'title'          => __( 'Search - Filter', 'spectra-pro' ),
	'description'    => __( 'Search filter allows user to search posts in the loop.', 'spectra-pro' ),
	'default'        => true,
	'is_child'       => true,
	'deprecated'     => false,
	'dynamic_assets' => array(
		'dir'        => 'loop-search',
		'plugin-dir' => SPECTRA_PRO_DIR . '/',
	),
);
