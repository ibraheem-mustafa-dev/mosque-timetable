<?php
/**
 * Block Information & Attributes File.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

$block_slug = 'uagb/register';
$block_data = array(
	'slug'                => '',
	'admin_categories'    => array( 'form', 'pro' ),
	'link'                => 'register',
	'doc'                 => 'register',
	'title'               => __( 'Registration Form', 'spectra-pro' ),
	'description'         => __( 'This block lets you add a user register form.', 'spectra-pro' ),
	'default'             => true,
	'extension'           => false,
	'priority'            => Spectra_Block_Prioritization::get_block_priority( 'register' ),
	'static_dependencies' => array(
		'spectra-pro-register-css' => array(
			'src'  => SpectraPro\Core\Utils::get_block_css_url( 'register' ),
			'dep'  => array(),
			'type' => 'css',
		),
		'uagb-register-js'         => array(
			'src'  => SpectraPro\Core\Utils::get_js_url( 'register' ),
			'dep'  => array( 'password-strength-meter' ),
			'type' => 'js',
		),
	),
	'dynamic_assets'      => array(
		'dir'        => 'register',
		'plugin-dir' => SPECTRA_PRO_DIR . '/',
	),
);
