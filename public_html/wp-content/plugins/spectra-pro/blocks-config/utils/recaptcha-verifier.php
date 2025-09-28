<?php
namespace SpectraPro\BlocksConfig\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class RecaptchaVerifier
 * Handles reCAPTCHA verification for Spectra Pro blocks
 *
 * @package SpectraPro\BlocksConfig\Utils
 * @since 1.2.2
 */
class RecaptchaVerifier {

	/**
	 * Verify Google reCAPTCHA response
	 *
	 * @param string $form_recaptcha_response reCaptcha token from form submission.
	 * @param string $server_remoteip User's IP address.
	 * @param string $recaptcha_secret_key reCaptcha secret key from settings.
	 * @return boolean True if verification successful, false otherwise.
	 * @since 1.2.2
	 */
	public function verify( $form_recaptcha_response, $server_remoteip, $recaptcha_secret_key ) {
		$google_url      = 'https://www.google.com/recaptcha/api/siteverify';
		$google_response = add_query_arg(
			array(
				'secret'   => $recaptcha_secret_key,
				'response' => $form_recaptcha_response,
				'remoteip' => $server_remoteip,
			),
			$google_url
		);
		$response        = wp_remote_get( $google_response );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$decoded_response = json_decode( $response['body'] );
		if ( ! is_object( $decoded_response ) || ! property_exists( $decoded_response, 'success' ) ) {
			return false;
		}
		return (bool) $decoded_response->success;
	}
}
