<?php
/**
 * Block Information & Attributes File.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

$block_slug = 'uagb/instagram-feed';
$block_data = array(
	'doc'                 => 'instagram-feed',
	'slug'                => '',
	'admin_categories'    => array( 'social', 'pro' ),
	'link'                => 'instagram-feed',
	'title'               => __( 'Instagram Feed', 'spectra-pro' ),
	'description'         => __( 'This block allows you to display Instagram Feeds on your website.', 'spectra-pro' ),
	'default'             => true,
	'extension'           => false,
	'priority'            => Spectra_Block_Prioritization::get_block_priority( 'instagram-feed' ),
	'static_dependencies' => array(
		'uagb-slick-css'          => array(
			'src'  => SPECTRA_PRO_URL . 'assets/css/slick.min.css',
			'dep'  => array(),
			'type' => 'css',
		),
		'uagb-slick-js'           => array(
			'src'  => SPECTRA_PRO_URL . 'assets/js/slick.min.js',
			'dep'  => array(),
			'type' => 'js',
		),
		'uagb-isotope-js'         => array(
			'src'  => SPECTRA_PRO_URL . 'assets/js/isotope.min.js',
			'dep'  => array(),
			'type' => 'js',
		),
		'uagb-imagesloaded-js'    => array(
			'src'  => SPECTRA_PRO_URL . 'assets/js/imagesloaded.min.js',
			'dep'  => array(),
			'type' => 'js',
		),
		'uagb-instagram-feed-css' => array(
			'src'  => SpectraPro\Core\Utils::get_block_css_url( 'instagram-feed' ),
			'dep'  => array(),
			'type' => 'css',
		),
		'uagb-instagram-feed-js'  => array(
			'src'  => SpectraPro\Core\Utils::get_js_url( 'instagram-feed' ),
			'dep'  => array( 'jquery' ),
			'type' => 'js',
		),
	),
	'dynamic_assets'      => array(
		'dir'        => 'instagram-feed',
		'plugin-dir' => SPECTRA_PRO_DIR,
	),
);
