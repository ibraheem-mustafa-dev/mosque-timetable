<?php
/**
 * Block Information & Attributes File.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

$block_slug = 'uagb/login';
$block_data = array(
	'slug'                => '',
	'admin_categories'    => array( 'form', 'pro' ),
	'link'                => 'login',
	'doc'                 => 'login',
	'title'               => __( 'Login Form', 'spectra-pro' ),
	'description'         => __( 'This block lets you add a user login form.', 'spectra-pro' ),
	'default'             => true,
	'extension'           => false,
	'priority'            => Spectra_Block_Prioritization::get_block_priority( 'login' ),
	'static_dependencies' => array(
		'spectra-pro-login-css' => array(
			'src'  => SpectraPro\Core\Utils::get_block_css_url( 'login' ),
			'dep'  => array( 'dashicons' ),
			'type' => 'css',
		),
		'spectra-pro-login-js'  => array(
			'src'  => SpectraPro\Core\Utils::get_js_url( 'login' ),
			'dep'  => array( 'wp-escape-html' ),
			'type' => 'js',
		),
	),
	'dynamic_assets'      => array(
		'dir'        => 'login',
		'plugin-dir' => SPECTRA_PRO_DIR . '/',
	),
);
