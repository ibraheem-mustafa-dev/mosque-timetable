<?php
namespace SpectraPro\BlocksConfig\Login;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SpectraPro\BlocksConfig\Utils\RecaptchaVerifier;


/**
 * Class Login.
 */
class Block extends Base {

	/**
	 * Hold Block Attributes Data
	 *
	 * @since 1.0.0
	 * @var attributes
	 */
	private $attributes = [];

	/**
	 * Hold Block Settings
	 *
	 * @since 1.0.0
	 * @var settings
	 */
	private $settings = [];

	/**
	 * Micro Constructor
	 */
	public static function init() {
		$self = new self();

		add_action( 'init', [ $self, 'register_blocks' ] );
		add_action( 'wp_ajax_spectra_pro_block_login', array( $self, 'login_form_handler' ) );
		add_action( 'wp_ajax_nopriv_spectra_pro_block_login', array( $self, 'login_form_handler' ) );

		add_action( 'wp_ajax_spectra_pro_block_login_forgot_password', array( $self, 'forgot_password_handler' ) );
		add_action( 'wp_ajax_nopriv_spectra_pro_block_login_forgot_password', array( $self, 'forgot_password_handler' ) );
	}


	/**
	 * Registers the `uagb/login` block on server.
	 *
	 * @since 1.0.0
	 */
	public function register_blocks() {
		// Check if the register function exists.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'uagb/login',
			array(
				'attributes'      => $this->get_default_attributes(),
				'render_callback' => array( $this, 'render_callback' ),
			)
		);
	}

	/**
	 * // Adding data to array and using this data in this file from frontend.
	 *
	 * @since 1.0.0
	 */
	public function get_default_attributes() {
		// Adding data to array.
		return array(
			'block_id'               => array(
				'type' => 'string',
			),
			'usernameLabel'          => array(
				'type'    => 'string',
				'default' => 'Username or Email Address',
			),
			'usernamePlaceholder'    => array(
				'type'    => 'string',
				'default' => 'Username',
			),
			'passwordLabel'          => array(
				'type'    => 'string',
				'default' => 'Password',
			),
			'passwordPlaceholder'    => array(
				'type'    => 'string',
				'default' => 'Password',
			),
			'rememberMeLabel'        => array(
				'type'    => 'string',
				'default' => 'Remember Me',
			),
			'forgotPasswordLabel'    => array(
				'type'    => 'string',
				'default' => 'Forgot Password',
			),
			'loginButtonLabel'       => array(
				'type'    => 'string',
				'default' => 'Login',
			),
			'showRegisterInfo'       => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'registerInfo'           => array(
				'type'    => 'string',
				'default' => 'Don\'t have an account?',
			),
			'registerButtonLabel'    => array(
				'type'    => 'string',
				'default' => 'Register',
			),
			'registerButtonLink'     => array(
				'type'    => 'object',
				'default' => '',
			),

			// settings.
			'disableFormFields'      => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'enableLoggedInMessage'  => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'redirectAfterLoginURL'  => array(
				'type'    => 'object',
				'default' => '',
			),
			'redirectAfterLogoutURL' => array(
				'type'    => 'object',
				'default' => '',
			),
			// recaptcha.
			'reCaptchaEnable'        => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'hidereCaptchaBatch'     => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'reCaptchaType'          => array(
				'type'    => 'string',
				'default' => 'v2',
			),
			// icon.
			'isHideIcon'             => array(
				'type'    => 'boolean',
				'default' => true,
			),

			// button size.
			'loginSize'              => array(
				'type'    => 'string',
				'default' => 'full',
			),
			'formWidth'              => array(
				'type'    => 'number',
				'default' => 100,
			),
			'formWidthTablet'        => array(
				'type'    => 'number',
				'default' => 100,
			),
			'formWidthMobile'        => array(
				'type'    => 'number',
				'default' => 100,
			),
			'formWidthType'          => array(
				'type'    => 'string',
				'default' => '%',
			),
			'formWidthTypeTablet'    => array(
				'type'    => 'string',
				'default' => '%',
			),
			'formWidthTypeMobile'    => array(
				'type'    => 'string',
				'default' => '%',
			),
			'ctaIcon'                => array(
				'type'    => 'string',
				'default' => '',
			),
			'ctaIconPosition'        => array(
				'type'    => 'string',
				'default' => 'after',
			),
			'ctaIconSpace'           => array(
				'type'    => 'number',
				'default' => 5,
			),
			'ctaIconSpaceTablet'     => array(
				'type' => 'number',
			),
			'ctaIconSpaceMobile'     => array(
				'type' => 'number',
			),
			'ctaIconSpaceType'       => array(
				'type'    => 'string',
				'default' => 'px',
			),

			'formBorderStyle'        => array(
				'type'    => 'string',
				'default' => 'default',
			),
			'fieldsBorderStyle'      => array(
				'type'    => 'string',
				'default' => 'default',
			),
			'loginBorderStyle'       => array(
				'type'    => 'string',
				'default' => 'default',
			),
		);
	}

	/**
	 * Renders the login block on server.
	 *
	 * @param array  $attributes Array of block attributes.
	 * @param string $content String of block Markup.
	 * @return markup
	 * @since 1.0.0
	 **/
	public function render_callback( $attributes, $content ) {
		$this->attributes = $attributes;

		$desktop_class = '';
		$tab_class     = '';
		$mob_class     = '';

		$uagb_common_selector_class = ''; // Required for z-index.

		if ( array_key_exists( 'UAGHideDesktop', $attributes ) || array_key_exists( 'UAGHideTab', $attributes ) || array_key_exists( 'UAGHideMob', $attributes ) ) {

			$desktop_class = ( isset( $attributes['UAGHideDesktop'] ) ) ? 'uag-hide-desktop' : '';

			$tab_class = ( isset( $attributes['UAGHideTab'] ) ) ? 'uag-hide-tab' : '';

			$mob_class = ( isset( $attributes['UAGHideMob'] ) ) ? 'uag-hide-mob' : '';
		}

		$zindex_wrap = array();
		if ( array_key_exists( 'zIndex', $attributes ) || array_key_exists( 'zIndexTablet', $attributes ) || array_key_exists( 'zIndexMobile', $attributes ) ) {
			$uagb_common_selector_class = 'uag-blocks-common-selector';
			$zindex_desktop             = array_key_exists( 'zIndex', $attributes ) && ( '' !== $attributes['zIndex'] ) ? '--z-index-desktop:' . $attributes['zIndex'] . ';' : false;
			$zindex_tablet              = array_key_exists( 'zIndexTablet', $attributes ) && ( '' !== $attributes['zIndexTablet'] ) ? '--z-index-tablet:' . $attributes['zIndexTablet'] . ';' : false;
			$zindex_mobile              = array_key_exists( 'zIndexMobile', $attributes ) && ( '' !== $attributes['zIndexMobile'] ) ? '--z-index-mobile:' . $attributes['zIndexMobile'] . ';' : false;

			if ( $zindex_desktop ) {
				array_push( $zindex_wrap, $zindex_desktop );
			}

			if ( $zindex_tablet ) {
				array_push( $zindex_wrap, $zindex_tablet );
			}

			if ( $zindex_mobile ) {
				array_push( $zindex_wrap, $zindex_mobile );
			}
		}

		$wrapper_classes = array(
			'uagb-block-' . $attributes['block_id'],
			'wp-block-spectra-pro-login',
			$desktop_class,
			$tab_class,
			$mob_class,
			$uagb_common_selector_class,
		);

		if ( is_user_logged_in() && ! $attributes['enableLoggedInMessage'] ) {
			return;
		}

		$recaptcha_site_key = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_site_key_v2', '' );
		if ( ! is_string( $recaptcha_site_key ) ) {
			$recaptcha_site_key = '';
		}

		ob_start();
		?>
			<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" style="<?php echo esc_attr( implode( '', $zindex_wrap ) ); ?>">
				<?php
				if ( is_user_logged_in() ) :
					$current_user = wp_get_current_user();
					?>
					<div class="wp-block-spectra-pro-login__logged-in-message">
						<?php
						$user_name   = $current_user->display_name;
						$a_tag       = '<a href="' . esc_url( wp_logout_url( isset( $attributes['redirectAfterLogoutURL']['url'] ) && $attributes['redirectAfterLogoutURL']['url'] ? $attributes['redirectAfterLogoutURL']['url'] : home_url( '/' ) ) ) . '">';
						$close_a_tag = '</a>';
						/* translators: %1$s user name */
						printf( esc_html__( 'You are logged in as %1$s (%2$sLogout%3$s)', 'spectra-pro' ), wp_kses_post( $user_name ), wp_kses_post( $a_tag ), wp_kses_post( $close_a_tag ) );
						?>
					</div>
					<?php
				else :
						// inner block content will be here.
						echo wp_kses_post( $content );
					?>

					<?php
					if ( ! $attributes['disableFormFields'] ) :
						?>

					<form id="<?php echo esc_attr( 'spectra-pro-login-form-' . $attributes['block_id'] ); ?>" action="#" method="post" class="spectra-pro-login-form">
						<?php wp_nonce_field( 'spectra-pro-login-nonce', '_nonce' ); ?>

						<div class="spectra-pro-login-form__user-login">
							<?php
							if ( ! empty( $attributes['usernameLabel'] ) ) :
								?>
							<label for="<?php echo esc_attr( 'username-' . $attributes['block_id'] ); ?>"><?php echo wp_kses_post( $attributes['usernameLabel'] ); ?></label>
							<?php endif; ?>

							<div class='<?php echo ( ! $attributes['isHideIcon'] ? 'spectra-pro-login-form-username-wrap spectra-pro-login-form-username-wrap--have-icon' : 'spectra-pro-login-form-username-wrap' ); ?>'>
								<?php
								if ( ! $attributes['isHideIcon'] ) {
										\UAGB_Helper::render_svg_html( 'user' );
								}
								?>
								<input id="<?php echo esc_attr( 'username-' . $attributes['block_id'] ); ?>" type="text" name="username" placeholder="<?php echo esc_attr( $attributes['usernamePlaceholder'] ); ?>" autocomplete="username" />
							</div>
						</div>
						<div class="spectra-pro-login-form__user-pass">
								<?php
								if ( ! empty( $attributes['passwordLabel'] ) ) :
									?>
							<label for="<?php echo esc_attr( 'password-' . $attributes['block_id'] ); ?>"><?php echo wp_kses_post( $attributes['passwordLabel'] ); ?></label>
									<?php
								endif;
								?>
							<div class='<?php echo ( ! $attributes['isHideIcon'] ? 'spectra-pro-login-form-pass-wrap spectra-pro-login-form-pass-wrap--have-icon' : 'spectra-pro-login-form-pass-wrap' ); ?>'>
								<?php
								if ( ! $attributes['isHideIcon'] ) {
									\UAGB_Helper::render_svg_html( 'lock' );
								}
								?>
								<input id="<?php echo esc_attr( 'password-' . $attributes['block_id'] ); ?>" type="password" name="password" placeholder="<?php echo esc_attr( $attributes['passwordPlaceholder'] ); ?>" autocomplete="off" />
								<button id="<?php echo esc_attr( 'password-visibility-' . $attributes['block_id'] ); ?>" type='button' aria-label="<?php echo esc_attr( __( 'Show Password', 'spectra-pro' ) ); ?>" ><span class="dashicons dashicons-visibility"></span></button>
							</div>
						</div>
						<div class="spectra-pro-login-form__forgetmenot">
							<div class="spectra-pro-login-form-rememberme">
								<label for="<?php echo esc_attr( 'rememberme-' . $attributes['block_id'] ); ?>">
									<input name="rememberme" type="checkbox" id="<?php echo esc_attr( 'rememberme-' . $attributes['block_id'] ); ?>" />
									<span class="spectra-pro-login-form-rememberme__checkmark"></span>
									<?php
									if ( ! empty( $attributes['rememberMeLabel'] ) ) :
										// The div below ensures that the label is unaffected by flex styling on it's parent.
										// Flex styling strips away the spaces in rich-text.
										?>
											<div class="spectra-pro-login-form-rememberme__checkmark-label">
												<?php
													echo wp_kses_post( $attributes['rememberMeLabel'] );
												?>
											</div>
										<?php
										endif;
									?>
								</label>
							</div>
								<?php
								if ( ! empty( $attributes['forgotPasswordLabel'] ) ) :
									?>
							<div class="spectra-pro-login-form-forgot-password">
								<a>
									<?php echo esc_html( $attributes['forgotPasswordLabel'] ); ?>
								</a>
							</div>
							<?php endif; ?>
						</div>
						<?php if ( $attributes['reCaptchaEnable'] && 'v2' === $attributes['reCaptchaType'] ) : ?>
						<div class="spectra-pro-login-form__recaptcha">
							<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $recaptcha_site_key ); ?>"></div>
							<input
								type="hidden"
								id="g-recaptcha-response"
							/>
						</div>
						<?php endif; ?>
						<div class="spectra-pro-login-form__submit wp-block-button">
							<button class="spectra-pro-login-form-submit-button wp-block-button__link" type="submit">
								<?php
								if ( 'before' === $attributes['ctaIconPosition'] ) {
									\UAGB_Helper::render_svg_html( $attributes['ctaIcon'] );
								}
								?>
								<span className='label-wrap'>
									<?php echo esc_attr( $attributes['loginButtonLabel'] ); ?>
								</span>
								<?php
								if ( 'after' === $attributes['ctaIconPosition'] ) {
									\UAGB_Helper::render_svg_html( $attributes['ctaIcon'] );
								}
								?>
							</button>
						</div>
					</form>
						<?php
						endif;
					?>

					<div id="<?php echo esc_attr( 'spectra-pro-login-form-status-' . $attributes['block_id'] ); ?>" class="spectra-pro-login-form-status"></div>

					<?php
					if ( $attributes['showRegisterInfo'] ) :
						?>
					<div class='wp-block-spectra-pro-login__footer'>
						<p class='wp-block-spectra-pro-login-info'><?php echo esc_html( $attributes['registerInfo'] ); ?>
							<a
								class="spectra-pro-login-form-register"
								href="<?php echo ( ! empty( $attributes['registerButtonLink']['url'] ) ? esc_url( $attributes['registerButtonLink']['url'] ) : esc_url( wp_registration_url() ) ); ?>"
								<?php
									echo ( isset( $attributes['registerButtonLink']['opensInNewTab'] ) && $attributes['registerButtonLink']['opensInNewTab'] ) ? ' target="_blank"' : '';
								?>
								<?php
									echo ( isset( $attributes['registerButtonLink']['noFollow'] ) && $attributes['registerButtonLink']['noFollow'] ) ? ' rel="noFollow"' : '';
								?>
							>
								<?php echo esc_html( $attributes['registerButtonLabel'] ); ?>
							</a>
						</p>
					</div>
						<?php
						endif;
					?>
					<?php
			endif;
				?>
			</div>
		<?php
		return ob_get_clean();
	}



	/**
	 * Ajax Login Functionality
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function login_form_handler() {
		check_ajax_referer( 'spectra-pro-login-nonce', '_nonce' );
		$recaptcha_status = ( isset( $_POST['recaptchaStatus'] ) ? filter_var( sanitize_text_field( $_POST['recaptchaStatus'] ), FILTER_VALIDATE_BOOLEAN ) : false );
		if ( $recaptcha_status ) {
			$recaptcha_type   = ( isset( $_POST['reCaptchaType'] ) ? sanitize_text_field( $_POST['reCaptchaType'] ) : 'v2' );
			$recaptcha_secret = '';
			if ( 'v2' === $recaptcha_type ) {
				$recaptcha_secret = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_secret_key_v2', '' );
			} else {
				$recaptcha_secret = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_secret_key_v3', '' );
			}

			if ( ! is_string( $recaptcha_secret ) ) {
				$recaptcha_secret = '';
			}

			$recaptcha_verifier   = new RecaptchaVerifier();
			$g_recaptcha_response = ( isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( $_POST['g-recaptcha-response'] ) : '' );
			$remote_addr          = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );
			$remote_addr          = is_string( $remote_addr ) ? $remote_addr : '';
			$verify               = $recaptcha_verifier->verify( $g_recaptcha_response, $remote_addr, $recaptcha_secret );
			if ( false === $verify ) {
				wp_send_json_error( __( 'Captcha is not matching, please try again.', 'spectra-pro' ) );
			}
		}//end if

		$username   = ( isset( $_POST['username'] ) ? sanitize_text_field( $_POST['username'] ) : '' );
		$password   = ( isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '' );
		$rememberme = ( isset( $_POST['rememberme'] ) ? true : false );
		$user       = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $rememberme,
			),
			false
		);

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( $user->get_error_message() );
		}

		wp_set_auth_cookie( $user->ID );  // Ensures there is seamless experience while navigating to WP Dashboard (without reauth=1).

		wp_send_json_success( esc_html__( 'You have successfully logged in. Redirecting...', 'spectra-pro' ) );
	}

	/**
	 * Forgot Password - Custom Error Message Sender.
	 *
	 * @param string $msg Error message in i18n function.
	 * @since 1.0.1
	 * @return void
	 */
	public function send_custom_error_msg( $msg ) {
		wp_send_json_error(
			array(
				'type'    => 'custom',
				'message' => $msg,
			)
		);
	}

	/**
	 * Ajax Login - Forgot Password Functionality
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function forgot_password_handler() {

		check_ajax_referer( 'spectra-pro-login-nonce', '_nonce' );

		if ( empty( $_POST['username'] ) ) {
			$this->send_custom_error_msg( esc_html__( 'The username/password field is empty. Please add a valid username/email to reset your password.', 'spectra-pro' ) );
		}

		$user_login = sanitize_text_field( $_POST['username'] );

		$user_data = get_user_by( 'login', $user_login );

		// If user data is not found by username, then find by email.
		if ( ! $user_data instanceof \WP_User ) {
			$user_data = get_user_by( 'email', $user_login );
		}

		// We need to check $user_data again since get_user_by() used above might return false value.
		if ( ! $user_data instanceof \WP_User ) {
			$this->send_custom_error_msg( esc_html__( 'No user found. Please add a registered username/email to reset your password, else create an account.', 'spectra-pro' ) );
			return; // Return statement required to adhere to PHPStan standards (prevent $user_data to be false).
		}

		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		$key = get_password_reset_key( $user_data );

		if ( is_wp_error( $key ) ) {
			wp_send_json_error( $key );
			return; // Return statement required to adhere to PHPStan standards (prevent $user_data to be false).
		}

		$key = ! is_string( $key ) ? '' : $key;

		$message  = __( 'Someone has requested a password reset for the following account:', 'spectra-pro' ) . "\r\n\r\n";
		$message .= network_home_url( '/' ) . "\r\n\r\n";
		$message .= sprintf(
			// translators: %s: Username.
			__( 'Username: %s', 'spectra-pro' ),
			$user_login
		) . "\r\n\r\n";
		$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'spectra-pro' ) . "\r\n\r\n";
		$message .= __( 'To reset your password, visit the following address:', 'spectra-pro' ) . "\r\n\r\n";
		$message .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n";

		// Get site name and ensure it's a string.
		$blog_name = get_option( 'blogname' );
		$blog_name = is_string( $blog_name ) ? $blog_name : __( 'Unknown Site', 'spectra-pro' );

		// Send email.
		$send_wp_mail = wp_mail( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
			$user_email,
			sprintf(
				// translators: %s: Password reset.
				__( '[%s] Password Reset', 'spectra-pro' ),
				wp_specialchars_decode( $blog_name )  // strval() - we use this function as wp_specialchars_decode() expects 'string' type parameter (and not 'mixed').
			),
			$message
		);

		// Check if email is sent and reply accordingly.
		if ( $send_wp_mail ) {
			wp_send_json_success( esc_html__( 'Please check your email for the password reset link.', 'spectra-pro' ) );
		} else {
			$this->send_custom_error_msg( __( 'Email failed to send.', 'spectra-pro' ) );
		}
	}

}
