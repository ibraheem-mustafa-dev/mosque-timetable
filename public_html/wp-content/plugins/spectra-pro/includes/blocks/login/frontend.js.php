<?php
/**
 * Frontend JS File.
 *
 * @since 1.0.0
 *
 * @package spectra-pro
 */

/**
 * Adding this comment to avoid PHPStan errors of undefined variable as these variables are defined else where.
 *
 * @var mixed[] $attr
 * @var string $id
 */

$form_selector = '#spectra-pro-login-form-' . $id;
$selector      = '.uagb-block-' . $id;  // Block selector.

$login_block          = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_login_block', [] );
$this_field_error_msg = array(
	'username' => sprintf(
		// translators: %s: Attribute User Name Label value or This Field.
		__( '%s cannot be blank.', 'spectra-pro' ),
		! empty( $attr['usernameLabel'] ) && is_string( $attr['usernameLabel'] ) ? esc_html( $attr['usernameLabel'] ) : __( 'This Field', 'spectra-pro' )
	),
	'password' => sprintf(
		// translators: %s: Attribute Password Label value or This Field.
		__( '%s cannot be blank.', 'spectra-pro' ),
		! empty( $attr['passwordLabel'] ) && is_string( $attr['passwordLabel'] ) ? esc_html( $attr['passwordLabel'] ) : __( 'This Field', 'spectra-pro' )
	),
);
$login_block_options  = apply_filters(
	'uagb_pro_login_options',
	array(
		'ajax_url'             => esc_url( admin_url( 'admin-ajax.php' ) ),
		'post_id'              => get_the_ID(),
		'block_id'             => $id,
		'enableReCaptcha'      => $attr['reCaptchaEnable'],
		'recaptchaVersion'     => $attr['reCaptchaType'],
		'recaptchaSiteKey'     => \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_site_key_' . $attr['reCaptchaType'], '' ),
		'loginRedirectURL'     => esc_url( ( isset( $attr['redirectAfterLoginURL']['url'] ) && $attr['redirectAfterLoginURL']['url'] ? $attr['redirectAfterLoginURL']['url'] : home_url( '/' ) ) ),
		'logoutRedirectURL'    => esc_url( ( isset( $attr['redirectAfterLogoutURL']['url'] ) && $attr['redirectAfterLogoutURL']['url'] ? $attr['redirectAfterLogoutURL']['url'] : home_url( '/' ) ) ),
		'this_field_error_msg' => $this_field_error_msg,
	),
	$id
);
ob_start();
?>
window.addEventListener( 'load', function() {
	UAGBLogin.init( '<?php echo esc_attr( $form_selector ); ?>', '<?php echo esc_attr( $selector ); ?>', <?php echo wp_json_encode( $login_block_options ); ?> );
});
<?php
return ob_get_clean();
?>
