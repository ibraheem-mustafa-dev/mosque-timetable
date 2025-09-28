<?php
/**
 * Plugin Name: Spectra Pro
 * Plugin URI: https://wpspectra.com/
 * Author: Brainstorm Force
 * Author URI: https://www.brainstormforce.com
 * Description: Enhance Spectra with new features and blocks, as well as extended functionality for existing blocks.
 * Version: 1.2.7
 * License: GPL v2
 * Text Domain: spectra-pro
 *
 * @package spectra-pro
 */

/**
 * Set constants.
 */
define( 'SPECTRA_PRO_FILE', __FILE__ );
define( 'SPECTRA_PRO_BASE', plugin_basename( SPECTRA_PRO_FILE ) );
define( 'SPECTRA_PRO_DIR', plugin_dir_path( SPECTRA_PRO_FILE ) );
define( 'SPECTRA_PRO_URL', plugins_url( '/', SPECTRA_PRO_FILE ) );
define( 'SPECTRA_PRO_VER', '1.2.7' );
define( 'SPECTRA_CORE_REQUIRED_VER', '2.19.13' );

require_once 'plugin-loader.php';
