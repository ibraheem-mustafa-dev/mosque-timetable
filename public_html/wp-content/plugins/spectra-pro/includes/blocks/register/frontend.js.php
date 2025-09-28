<?php
/**
 * Frontend JS File.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

/**
 * Adding this comment to avoid PHPStan errors of undefined variable as these variables are defined elsewhere.
 *
 * @var int $id  The block ID.
 */
$form_selector = '#spectra-pro-register-form-' . $id;
$selector      = '.uagb-block-' . $id;  // Block selector.

$register_options = apply_filters(
	'uagb_register_options',
	array(
		'redirect_url'                 => home_url(),
		'ajax_url'                     => esc_url( admin_url( 'admin-ajax.php' ) ),
		'post_id'                      => get_the_ID(),
		'block_id'                     => $id,
		'afterRegisterActions'         => $attr['afterRegisterActions'],
		'reCaptchaEnable'              => $attr['reCaptchaEnable'],
		'reCaptchaType'                => $attr['reCaptchaType'],
		'hidereCaptchaBatch'           => $attr['hidereCaptchaBatch'],
		'recaptchaSiteKey'             => \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_site_key_' . $attr['reCaptchaType'], '' ),

		'wp_version'                   => version_compare( get_bloginfo( 'version' ), '5.4.99', '>=' ),
		'messageInvalidEmailError'     => $attr['messageInvalidEmailError'],
		'messageEmailMissingError'     => $attr['messageEmailMissingError'],
		'messageEmailAlreadyUsedError' => $attr['messageEmailAlreadyUsedError'],
		'messageInvalidUsernameError'  => $attr['messageInvalidUsernameError'],
		'messageUsernameAlreadyUsed'   => $attr['messageUsernameAlreadyUsed'],
		'messageInvalidPasswordError'  => $attr['messageInvalidPasswordError'],
		'messagePasswordConfirmError'  => $attr['messagePasswordConfirmError'],
		'messageTermsError'            => $attr['messageTermsError'],
		'messageOtherError'            => $attr['messageOtherError'],
		'messageSuccessRegistration'   => $attr['messageSuccessRegistration'],
	),
	$id
);
ob_start();
?>
window.addEventListener( 'load', function() {
	UAGBRegister.init( '<?php echo esc_attr( $form_selector ); ?>', '<?php echo esc_attr( $selector ); ?>', <?php echo wp_json_encode( $register_options ); ?> );
});
<?php
return ob_get_clean();
?>
