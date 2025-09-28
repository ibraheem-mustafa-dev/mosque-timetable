<?php
/**
 * Block Information.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

$block_slug = 'uagb/loop-wrapper';
$block_data = array(
	'slug'           => '',
	'link'           => '',
	'title'          => __( 'Wrapper', 'spectra-pro' ),
	'description'    => __( 'Loop Builder wrapper.', 'spectra-pro' ), // Needs to be updated.
	'default'        => true,
	'is_child'       => true,
	'deprecated'     => false,
	'dynamic_assets' => array(
		'dir'        => 'loop-wrapper',
		'plugin-dir' => SPECTRA_PRO_DIR . '/',
	),
);
