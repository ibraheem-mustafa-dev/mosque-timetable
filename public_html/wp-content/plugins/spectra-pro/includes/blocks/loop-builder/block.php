<?php
/**
 * Block Information.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

$block_slug = 'uagb/loop-builder';
$block_data = array(
	'doc'              => 'loop-builder',
	'slug'             => '',
	'admin_categories' => array( 'content', 'post', 'pro' ),
	'link'             => 'loop-builder',
	'title'            => __( 'Loop Builder', 'spectra-pro' ),
	'description'      => __( 'This block allows you to generate custom loop from different posts.', 'spectra-pro' ), // Need to be improved.
	'default'          => true,
	'extension'        => false,
	'priority'         => Spectra_Block_Prioritization::get_block_priority( 'loop-builder' ),
);
