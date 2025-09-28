<?php

namespace SpectraPro\Admin;

use \BSF_License_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class License_Handler {

	/**
	 * Product ID
	 *
	 * @access private
	 * @var string Product ID.
	 * @since 1.0.0
	 */
	private $product_id = 'spectra-pro';

	/**
	 * Errors
	 *
	 * @access private
	 * @var array Errors strings.
	 * @since 1.0.0
	 */
	private static $errors = array();

	/**
	 * Initialize actions and filters.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {

		$this->set_global_paths();

		add_action( 'wp_ajax_uag_license_activation', array( $this, 'license_activation' ) );
		add_action( 'wp_ajax_uag_license_deactivation', array( $this, 'license_deactivation' ) );
		add_filter( 'uag_react_admin_localize', array( $this, 'localize_admin_dashboard' ) );

		add_filter( 'bsf_registration_page_url_spectra-pro', array( $this, 'license_registration_page_url' ) );

		add_action( 'init', array( $this, 'load_bsf_core' ) );

	}

	/**
	 * Set License registration page URL.
	 *
	 * @param string $url BSF Registration page url.
	 * @since 1.0.0
	 * @return string
	 */
	public function license_registration_page_url( $url ) {
		$url = admin_url( 'admin.php?page=spectra&path=settings&settings=license' );
		return $url;
	}

	/**
	 * License Deactivation AJAX
	 *
	 * @Hooked - wp_ajax_uag_license_activation
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function license_activation() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'messsage' => self::$errors['permission'] ) );
		}
		if ( ! check_ajax_referer( 'uag_license_activation', 'security', false ) ) {
			wp_send_json_error( array( 'messsage' => self::$errors['nonce'] ) );
		}

		if ( ! isset( $_POST['key'] ) ) {
			wp_send_json_error( array( 'messsage' => __( 'License key not found.', 'spectra-pro' ) ) );
		}

		$license_key = sanitize_text_field( $_POST['key'] );

		$data = array(
			'privacy_consent'          => true,
			'terms_conditions_consent' => true,
			'product_id'               => $this->product_id,
			'license_key'              => $license_key,
		);

		$result = BSF_License_Manager::instance()->bsf_process_license_activation( $data );

		if ( ! is_bool( $result ) && ! $result['success'] ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => $result['message'],
				)
			);
		}

		wp_send_json_success(
			array(
				'success' => true,
				'message' => __( 'Plugin Successfully Activated', 'spectra-pro' ),
			)
		);
	}

	/**
	 * License Deactivation AJAX
	 *
	 * @Hooked - wp_ajax_uag_license_deactivation
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function license_deactivation() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'messsage' => self::$errors['permission'] ) );
		}
		if ( ! check_ajax_referer( 'uag_license_deactivation', 'security', false ) ) {
			wp_send_json_error( array( 'messsage' => self::$errors['nonce'] ) );
		}

		$result = BSF_License_Manager::instance()->process_license_deactivation( $this->product_id );

		if ( isset( $result['success'] ) && ! $result['success'] ) {
			wp_send_json_error(
				array(
					'success' => false,
					'message' => $result['message'],
				)
			);
		}

		wp_send_json_success(
			array(
				'success' => true,
				'message' => __( 'Plugin Successfully Deactivated', 'spectra-pro' ),
			)
		);
	}

	/**
	 * Set global paths for BSF Core.
	 *
	 * @Hooked - uag_react_admin_localize
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function set_global_paths() {
		$bsf_core_version_file = realpath( SPECTRA_PRO_DIR . '/admin/bsf-core/version.yml' );

		// Is file 'version.yml' exist?
		if ( is_file( $bsf_core_version_file ) ) {
			global $bsf_core_version, $bsf_core_path;
			$bsf_core_dir = realpath( SPECTRA_PRO_DIR . '/admin/bsf-core/' );
			$version      = file_get_contents( $bsf_core_version_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			// Compare versions.
			if ( version_compare( $version, strval( $bsf_core_version ), '>' ) ) {
				$bsf_core_version = $version; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				$bsf_core_path    = $bsf_core_dir; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			}
		}
	}

	/**
	 * Load BSF Core.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function load_bsf_core() {
		/**
		 * Set global variables for errors.
		 *
		 * @var array {
		 *     @type string $permission Error message if user doesn't have permission to perform an action.
		 *     @type string $nonce Error message if nonce validation fails.
		 *     @type string $default Default error message.
		 * }
		 */
		self::$errors = array(
			'permission' => __( 'Sorry, you are not allowed to do this operation.', 'spectra-pro' ),
			'nonce'      => __( 'Nonce validation failed', 'spectra-pro' ),
			'default'    => __( 'Sorry, something went wrong.', 'spectra-pro' ),
		);
		global $bsf_core_path;
		if ( is_file( realpath( $bsf_core_path . '/index.php' ) ) ) {
			include_once realpath( $bsf_core_path . '/index.php' );
		}
	}

	/**
	 * Localize inputs to the Spectra Admin Dashboard.
	 *
	 * @Hooked - uag_react_admin_localize
	 *
	 * @param array $localize Localized Filtered Variable.
	 * @return array $localize Updated Localize.
	 * @since 1.0.0
	 */
	public function localize_admin_dashboard( $localize ) {
		$localize['license_activation_nonce']   = wp_create_nonce( 'uag_license_activation' );
		$localize['license_deactivation_nonce'] = wp_create_nonce( 'uag_license_deactivation' );
		$localize['license_status']             = ( new License_Handler() )->is_license_active();
		$localize['bsf_graupi_nonce']           = wp_create_nonce( 'bsf_license_activation_deactivation_nonce' );
		return $localize;
	}

	/**
	 * Check if the license is active.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_license_active() {

		if ( ! class_exists( 'BSF_License_Manager' ) ) {
			return false;
		}

		return BSF_License_Manager::bsf_is_active_license( $this->product_id );
	}
}
