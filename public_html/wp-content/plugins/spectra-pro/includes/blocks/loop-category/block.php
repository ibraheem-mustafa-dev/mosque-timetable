<?php
/**
 * Block Information.
 *
 * @since 1.2.0
 *
 * @package spectra-pro
 */

$block_slug = 'uagb/loop-category';
$block_data = array(
	'slug'           => '',
	'link'           => '',
	'title'          => __( 'Category - Filter', 'spectra-pro' ),
	'description'    => __( 'Add category filters to your loop builder.', 'spectra-pro' ),
	'default'        => true,
	'is_child'       => true,
	'deprecated'     => false,
	'dynamic_assets' => array(
		'dir'        => 'loop-category',
		'plugin-dir' => SPECTRA_PRO_DIR . '/',
	),
);
