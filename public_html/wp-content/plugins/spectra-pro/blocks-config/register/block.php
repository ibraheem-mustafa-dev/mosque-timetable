<?php
namespace SpectraPro\BlocksConfig\Register;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use SpectraPro\BlocksConfig\Utils\RecaptchaVerifier;

/**
 * Class Login.
 */
class Block {

	/**
	 * Block Name
	 *
	 * @since 1.0.0
	 * @var block_name
	 */
	private $block_name = 'uagb/register';

	/**
	 * Hold Block Attributes Data
	 *
	 * @since 1.0.0
	 * @var attributes
	 */
	private $attributes = [];

	/**
	 * Hold Email Settings
	 *
	 * @since 1.0.0
	 * @var email_settings
	 */
	private $email_settings = [];

	/**
	 * Micro Constructor
	 */
	public static function init() {
		$self = new self();
		add_action( 'init', [ $self, 'register_blocks' ] );
		add_action( 'wp_ajax_spectra_pro_block_register', array( $self, 'register_new_user' ) );
		add_action( 'wp_ajax_nopriv_spectra_pro_block_register', array( $self, 'register_new_user' ) );
		add_action( 'wp_ajax_nopriv_spectra_pro_block_register_unique_username_and_email', array( $self, 'unique_username_and_email' ) );
		add_action( 'wp_ajax_spectra_pro_block_register_get_roles', array( $self, 'get_roles' ) );
		add_filter( 'wp_new_user_notification_email', array( $self, 'custom_wp_new_user_notification_email' ), 10, 3 );

		if ( ! is_admin() ) {
			add_filter( 'render_block', array( $self, 'register_render_block' ), 11, 2 );
		}
	}

	/**
	 * Registers the `uagb/register` block on server.
	 *
	 * @since 1.0.0
	 */
	public function register_blocks() {
		// Check if the register function exists.
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			$this->block_name,
			array(
				'attributes'      => $this->get_default_attributes(),
				'render_callback' => array( $this, 'render_callback' ),
			)
		);
	}

	/**
	 * Block Default Attributes
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_default_attributes() {
		return array(
			'block_id'                     => array(
				'type' => 'string',
			),
			'newUserRole'                  => array(
				'type'    => 'string',
				'default' => '',
			),
			'afterRegisterActions'         => array(
				'type'    => 'array',
				'default' => [ 'autoLogin' ],
			),
			// email.
			'emailTemplateType'            => array(
				'type'    => 'string',
				'default' => 'default',
			),
			'emailTemplateSubject'         => array(
				'type'    => 'string',
				'default' => 'Thank you for registering with "{{site_title}}"!',
			),
			'emailTemplateMessage'         => array(
				'type' => 'string',
			),
			'emailTemplateMessageType'     => array(
				'type'    => 'string',
				'default' => 'html',
			),
			// Email - error.
			'messageInvalidEmailError'     => array(
				'type'    => 'string',
				'default' => __( 'You have used an invalid email', 'spectra-pro' ),
			),
			'messageEmailMissingError'     => array(
				'type'    => 'string',
				'default' => __( 'Email is missing or invalid', 'spectra-pro' ),
			),
			'messageEmailAlreadyUsedError' => array(
				'type'    => 'string',
				'default' => __( 'The provided email is already registered with another account. Please login or reset password or use another email.', 'spectra-pro' ),
			),
			// Username - error.
			'messageInvalidUsernameError'  => array(
				'type'    => 'string',
				'default' => __( 'You have used an invalid username', 'spectra-pro' ),
			),
			'messageUsernameAlreadyUsed'   => array(
				'type'    => 'string',
				'default' => __( 'Invalid username provided or the username is already registered.', 'spectra-pro' ),
			),
			// Password - error.
			'messageInvalidPasswordError'  => array(
				'type'    => 'string',
				'default' => __( 'Your password is invalid.', 'spectra-pro' ),
			),
			'messagePasswordConfirmError'  => array(
				'type'    => 'string',
				'default' => __( 'Your passwords do not match.', 'spectra-pro' ),
			),
			// reCaptcha.
			'reCaptchaEnable'              => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'reCaptchaType'                => array(
				'type'    => 'string',
				'default' => 'v2',
			),
			'hidereCaptchaBatch'           => array(
				'type'    => 'boolean',
				'default' => false,
			),
			// Terms - error.
			'messageTermsError'            => array(
				'type'    => 'string',
				'default' => __( 'Please accept the Terms and Conditions, and try again.', 'spectra-pro' ),
			),
			'messageOtherError'            => array(
				'type'    => 'string',
				'default' => __( 'Something went wrong!', 'spectra-pro' ),
			),
			// success - message.
			'messageSuccessRegistration'   => array(
				'type'    => 'string',
				'default' => __( 'Registration completed successfully. Check your inbox for password if you did not provide it while registering.', 'spectra-pro' ),
			),

			// fields border defaults.
			'fieldBorderStyle'             => array(
				'type'    => 'string',
				'default' => 'solid',
			),
			'fieldBorderTopLeftRadius'     => array(
				'type'    => 'number',
				'default' => 3,
			),
			'fieldBorderTopRightRadius'    => array(
				'type'    => 'number',
				'default' => 3,
			),
			'fieldBorderBottomLeftRadius'  => array(
				'type'    => 'number',
				'default' => 3,
			),
			'fieldBorderBottomRightRadius' => array(
				'type'    => 'number',
				'default' => 3,
			),
			'fieldBorderTopWidth'          => array(
				'type'    => 'number',
				'default' => 1,
			),
			'fieldBorderRightWidth'        => array(
				'type'    => 'number',
				'default' => 1,
			),
			'fieldBorderBottomWidth'       => array(
				'type'    => 'number',
				'default' => 1,
			),
			'fieldBorderLeftWidth'         => array(
				'type'    => 'number',
				'default' => 1,
			),
			'fieldBorderColor'             => array(
				'type'    => 'string',
				'default' => '#E9E9E9',
			),
		);
	}

	/**
	 * Renders the register block on server.
	 *
	 * @param array  $attributes Array of block attributes.
	 * @param string $content String of block Content.
	 * @return markup
	 * @since 1.0.0
	 */
	public function render_callback( $attributes, $content ) {
		$wrapper_classes = array(
			'uagb-block-' . $attributes['block_id'],
			'wp-block-spectra-pro-register',
			'wp-block-spectra-pro-register__logged-in-message',
		);

		if ( ! get_option( 'users_can_register' ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			ob_start();
			?>
				<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
					<?php
						$user_name   = $current_user->display_name;
						$a_tag       = '<a href="' . esc_url( wp_logout_url( ! empty( $attributes['redirectAfterLogoutURL'] ) ? $attributes['redirectAfterLogoutURL'] : home_url( '/' ) ) ) . '">';
						$close_a_tag = '</a>';
						/* translators: %1$s user name */
						printf( esc_html__( 'You are logged in as %1$s (%2$sLogout%3$s)', 'spectra-pro' ), wp_kses_post( $user_name ), wp_kses_post( $a_tag ), wp_kses_post( $close_a_tag ) );
					?>
				</div>
			<?php
			return ob_get_clean();
		}

		// Replace the value of the input tag with the actual value.
		$actual_value = wp_create_nonce( 'spectra-pro-register-nonce' );

		// add nonce.
		$content = str_replace( '<input type="hidden" name="_nonce" value="ssr_nonce_replace"/>', '<input type="hidden" name="_nonce" value="' . $actual_value . '"/>', $content );
		// add recaptcha sitekey.
		$recaptcha_enable = (bool) ( isset( $attributes['reCaptchaEnable'] ) ? $attributes['reCaptchaEnable'] : false );
		if ( $recaptcha_enable ) {
			$recaptcha_type = ( isset( $attributes['reCaptchaType'] ) ? $attributes['reCaptchaType'] : 'v2' );
			if ( 'v2' === $recaptcha_type ) {
				$uag_recaptcha_site_key_v2 = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_recaptcha_site_key_v2', '' );

				if ( ! is_string( $uag_recaptcha_site_key_v2 ) ) {
					$uag_recaptcha_site_key_v2 = '';
				}

				$content = str_replace( 'ssr_sitekey_replace', $uag_recaptcha_site_key_v2, $content );
			}
		}

		return $content;
	}


	/**
	 * Get all attributes from post content recursively.
	 *
	 * @param array  $blocks     Blocks array.
	 * @param string $block_name Block Name.
	 * @param string $block_id   Block ID.
	 * @return array
	 * @since 1.1.4
	 */
	public function get_block_attributes_recursive( $blocks, $block_name, $block_id ) {
		$attributes = [];
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] === $block_name && $block['attrs']['block_id'] === $block_id ) {
				$attributes[ $block_name ] = $block['attrs'];
				if ( is_array( $block['innerBlocks'] ) && count( $block['innerBlocks'] ) ) {
					foreach ( $block['innerBlocks'] as $inner_block ) {
						if ( isset( $inner_block['attrs']['name'] ) ) {
							$attributes[ $inner_block['attrs']['name'] ] = $inner_block['attrs'];
						}
					}
				}
				return $attributes; // Found the block, return its attributes.
			} elseif ( is_array( $block['innerBlocks'] ) && count( $block['innerBlocks'] ) ) {
				// If the block is not found at this level, check inner blocks recursively.
				$inner_attributes = $this->get_block_attributes_recursive( $block['innerBlocks'], $block_name, $block_id );
				if ( ! empty( $inner_attributes ) ) {
					return $inner_attributes; // Found the block in inner blocks, return its attributes.
				}
			}
		}
		return $attributes; // Block not found in this branch.
	}

	/**
	 * Wrapper function to initiate recursive block attribute retrieval.
	 *
	 * @param string $content    post content.
	 * @param string $block_name Block Name.
	 * @param string $block_id   Block ID.
	 * @return array
	 * @since 1.0.0
	 */
	public function get_block_attributes( $content, $block_name, $block_id ) {
		$blocks = parse_blocks( $content );
		if ( empty( $blocks ) ) {
			return array();
		}
		return $this->get_block_attributes_recursive( $blocks, $block_name, $block_id );
	}


	/**
	 * Register new user
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function register_new_user() {
		check_ajax_referer( 'spectra-pro-register-nonce', '_nonce' );

		$allow_register = get_option( 'users_can_register' );
		if ( ! $allow_register ) {
			wp_send_json_error( esc_html__( 'Sorry, the site admin has disabled new user registration', 'spectra-pro' ) );
		}

		$error      = [];
		$post_id    = ( isset( $_POST['post_id'] ) ? sanitize_text_field( $_POST['post_id'] ) : '' );
		$block_id   = ( isset( $_POST['block_id'] ) ? sanitize_text_field( $_POST['block_id'] ) : '' );
		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
		$username   = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'], true ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_text_field( $_POST['email'] ) : '';
		$password   = isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';

		$content_post = get_post( intval( $post_id ) );
		if ( ! $content_post instanceof \WP_Post ) {
			wp_send_json_error( __( 'Not a valid post.', 'spectra-pro' ) );
			die();
		}
		$this->saved_attributes = $this->get_block_attributes( $content_post->post_content, $this->block_name, $block_id );
		$default_attributes     = $this->get_default_attributes();

		// verify reCaptcha.
		$recaptcha_enable = isset( $this->saved_attributes[ $this->block_name ]['reCaptchaEnable'] ) ? $this->saved_attributes[ $this->block_name ]['reCaptchaEnable'] : $default_attributes['reCaptchaEnable']['default'];
		if ( $recaptcha_enable ) {
			$recaptcha_type   = isset( $this->saved_attributes[ $this->block_name ]['reCaptchaType'] ) ? $this->saved_attributes[ $this->block_name ]['reCaptchaType'] : $default_attributes['reCaptchaType']['default'];
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
				wp_send_json_error( array( 'g-recaptcha-response' => __( 'Captcha is not matching, please try again.', 'spectra-pro' ) ) );
			}
		}//end if

		// Password.
		if ( empty( $password ) ) {
			$password = wp_generate_password();
		} elseif ( isset( $_POST['reenter_password'] ) && $password !== $_POST['reenter_password'] ) {
			$error['password'] = isset( $this->saved_attributes[ $this->block_name ]['messagePasswordConfirmError'] ) ? $this->saved_attributes[ $this->block_name ]['messagePasswordConfirmError'] : $default_attributes['messagePasswordConfirmError']['default'];
		} elseif ( false !== strpos( wp_unslash( $password ), '\\' ) ) {
			$error['password'] = __( 'Password may not contain the character "\\"', 'spectra-pro' );
		}

		// check required field.
		if ( empty( $first_name ) && isset( $this->saved_attributes['first_name']['required'] ) && $this->saved_attributes['first_name']['required'] ) {
			$error['first_name'] = esc_html__( 'This field is required.', 'spectra-pro' );
		}
		if ( empty( $last_name ) && isset( $this->saved_attributes['last_name']['required'] ) && $this->saved_attributes['last_name']['required'] ) {
			$error['last_name'] = esc_html__( 'This field is required.', 'spectra-pro' );
		}

		// User.
		if ( isset( $this->saved_attributes['username']['required'] ) && $this->saved_attributes['username']['required'] ) {
			if ( empty( $username ) ) {
				$error['username'] = isset( $this->saved_attributes[ $this->block_name ]['messageInvalidUsernameError'] ) ? $this->saved_attributes[ $this->block_name ]['messageInvalidUsernameError'] : $default_attributes['messageInvalidUsernameError']['default'];
			} elseif ( username_exists( $username ) ) {
				$error['username'] = isset( $this->saved_attributes[ $this->block_name ]['messageUsernameAlreadyUsed'] ) ? $this->saved_attributes[ $this->block_name ]['messageUsernameAlreadyUsed'] : $default_attributes['messageUsernameAlreadyUsed']['default'];
			}
		}

		// Email.
		if ( empty( $email ) ) {
			$error['email'] = isset( $this->saved_attributes[ $this->block_name ]['messageEmailMissingError'] ) ? $this->saved_attributes[ $this->block_name ]['messageEmailMissingError'] : $default_attributes['messageEmailMissingError']['default'];
		} elseif ( $email && ! is_email( $email ) ) {
			$error['email'] = isset( $this->saved_attributes[ $this->block_name ]['messageInvalidEmailError'] ) ? $this->saved_attributes[ $this->block_name ]['messageInvalidEmailError'] : $default_attributes['messageInvalidEmailError']['default'];
		} elseif ( email_exists( $email ) ) {
			$error['email'] = isset( $this->saved_attributes[ $this->block_name ]['messageEmailAlreadyUsedError'] ) ? $this->saved_attributes[ $this->block_name ]['messageEmailAlreadyUsedError'] : $default_attributes['messageEmailAlreadyUsedError']['default'];
		}

		// terms.
		if ( isset( $this->saved_attributes['terms']['required'] ) && $this->saved_attributes['terms']['required'] ) {
			$terms = (bool) isset( $_POST['terms'] ) ? sanitize_text_field( $_POST['terms'] ) : false;
			if ( ! $terms ) {
				$error['terms'] = isset( $this->saved_attributes[ $this->block_name ]['messageTermsError'] ) ? $this->saved_attributes[ $this->block_name ]['messageTermsError'] : $default_attributes['messageTermsError']['default'];
			}
		}
		// get all roles.
		$get_all_roles = array_keys( $this->get_all_roles() );
		// role.
		$default_role = get_option( 'default_role' );
		$role         = $default_role;
		if ( isset( $this->saved_attributes[ $this->block_name ]['newUserRole'] ) && ! empty( $this->saved_attributes[ $this->block_name ]['newUserRole'] ) ) {
			$role = $this->saved_attributes[ $this->block_name ]['newUserRole'];
			// check if role is valid.
			$role = in_array( $role, $get_all_roles ) ? $role : $default_role;
		}
		// apply filter.
		$role = apply_filters( 'spectra_pro_registration_form_change_new_user_role', $role );

		// Email.
		if (
			isset( $this->saved_attributes[ $this->block_name ]['afterRegisterActions'] ) &&
			in_array( 'sendMail', $this->saved_attributes[ $this->block_name ]['afterRegisterActions'], true ) &&
			'custom' === $this->saved_attributes[ $this->block_name ]['emailTemplateType']
		) {
			// form data.
			$this->email_settings['user_login'] = $username;
			$this->email_settings['user_pass']  = $password;
			$this->email_settings['user_email'] = $email;
			$this->email_settings['first_name'] = $first_name;
			$this->email_settings['last_name']  = $last_name;

			// email.
			$this->email_settings['subject'] = isset( $this->saved_attributes[ $this->block_name ]['emailTemplateSubject'] ) ? $this->saved_attributes[ $this->block_name ]['emailTemplateSubject'] : $default_attributes['emailTemplateSubject']['default'];
			$this->email_settings['message'] = isset( $this->saved_attributes[ $this->block_name ]['emailTemplateMessage'] ) ? $this->saved_attributes[ $this->block_name ]['emailTemplateMessage'] : $default_attributes['emailTemplateMessage']['default'];
			$headers                         = isset( $this->saved_attributes[ $this->block_name ]['emailTemplateMessageType'] ) ? $this->saved_attributes[ $this->block_name ]['emailTemplateMessageType'] : $default_attributes['emailTemplateMessageType']['default'];

			$this->email_settings['headers'] = 'Content-Type: text/' . ( 'plain' === $headers ? $headers : 'html; charset=UTF-8\r\n' );
		}

		// Create username from email.
		if ( empty( $username ) ) {
			$username = $this->create_username( $email, '' );
			$username = sanitize_user( $username );
		}

		// have error.
		if ( count( $error ) ) {
			wp_send_json_error( $error );
		}

		$user_args = apply_filters(
			'spectra_pro_block_register_insert_user_args',
			array(
				'user_login'      => $username,
				'user_pass'       => $password,
				'user_email'      => $email,
				'first_name'      => $first_name,
				'last_name'       => $last_name,
				'user_registered' => gmdate( 'Y-m-d H:i:s' ),
				'role'            => $role,
			)
		);

		$result = wp_insert_user( $user_args );

		wp_set_auth_cookie( $result );  // Ensures there is seamless experience while navigating to WP Dashboard (without reauth=1).

		/**
		 * Fires after a new user has been created.
		 *
		 * @since 1.18.0
		 *
		 * @param int    $user_id ID of the newly created user.
		 * @param string $notify  Type of notification that should happen. See wp_send_new_user_notifications()
		 *                        for more information on possible values.
		 */
		do_action( 'edit_user_created_user', $result, 'both' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result );
		}

		$message      = isset( $this->saved_attributes[ $this->block_name ]['messageSuccessRegistration'] ) ? $this->saved_attributes[ $this->block_name ]['messageSuccessRegistration'] : $default_attributes['messageSuccessRegistration']['default'];
		$redirect_url = isset( $this->saved_attributes[ $this->block_name ]['autoLoginRedirectURL']['url'] ) && $this->saved_attributes[ $this->block_name ]['autoLoginRedirectURL']['url'] ? esc_url( $this->saved_attributes[ $this->block_name ]['autoLoginRedirectURL']['url'] ) : esc_url( home_url( '/' ) );

		/* Login user after registration and redirect to home page if not currently logged in */
		$afterRegisterActions = isset( $this->saved_attributes[ $this->block_name ]['afterRegisterActions'] ) ? $this->saved_attributes[ $this->block_name ]['afterRegisterActions'] : $default_attributes['afterRegisterActions']['default'];
		if ( in_array( 'autoLogin', $afterRegisterActions, true ) ) {
			$creds                  = array();
			$creds['user_login']    = $username;
			$creds['user_password'] = $password;
			$creds['remember']      = true;
			$login_user             = wp_signon( $creds, false );
			if ( ! is_wp_error( $login_user ) ) {
				wp_send_json_success(
					[
						'message'      => $message,
						'redirect_url' => $redirect_url,
					]
				);
			}

			$error['other'] = isset( $this->saved_attributes[ $this->block_name ]['messageOtherError'] ) ? $this->saved_attributes[ $this->block_name ]['messageOtherError'] : $default_attributes['messageOtherError']['default'];
			wp_send_json_error( $error );
		}
		wp_send_json_success(
			[
				'message'      => $message,
				'redirect_url' => $redirect_url,
			]
		);
	}

	/**
	 * Ajax Login Functionality
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function unique_username_and_email() {
		check_ajax_referer( 'spectra-pro-register-nonce', 'security' );
		$field_name  = ( isset( $_POST['field_name'] ) ? sanitize_key( $_POST['field_name'] ) : '' );
		$field_value = ( isset( $_POST['field_value'] ) ? sanitize_text_field( $_POST['field_value'] ) : '' );
		if ( 'username' === $field_name ) {
			if ( username_exists( $field_value ) ) {
				wp_send_json_success(
					[
						'has_error' => true,
						'attribute' => 'messageUsernameAlreadyUsed',
					]
				);
			}
		} elseif ( 'email' === $field_name ) {
			if ( ! is_email( $field_value ) ) {
				wp_send_json_success(
					[
						'has_error' => true,
						'attribute' => 'messageInvalidEmailError',
					]
				);
			} elseif ( email_exists( $field_value ) ) {
				wp_send_json_success(
					[
						'has_error' => true,
						'attribute' => 'messageEmailAlreadyUsedError',
					]
				);
			}
		}//end if
		wp_send_json_success(
			[
				'has_error' => false,
				'attribute' => '',
			]
		);
	}

	/**
	 * Get all roles.
	 *
	 * @return array $all_roles.
	 * @since 1.1.5
	 */
	public function get_all_roles() {
		$all_roles = new \WP_Roles();
		$all_roles = $all_roles->get_names();

		// Roles to remove.
		$roles_to_remove = array( 'administrator', 'editor' );

		// Remove the specified roles from the array.
		foreach ( $roles_to_remove as $role ) {
			if ( isset( $all_roles[ $role ] ) ) {
				unset( $all_roles[ $role ] );
			}
		}

		return $all_roles;
	}

	/**
	 * Ajax Login Functionality
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function get_roles() {
		check_ajax_referer( 'spectra_pro_ajax_nonce', 'security' );
		$all_roles = $this->get_all_roles();
		$response  = [
			array(
				'value' => 'default',
				'label' => esc_html__( '– Select –', 'spectra-pro' ),
			),
		];
		foreach ( $all_roles as $value => $label ) {
			$response[] = array(
				'value' => $value,
				'label' => $label,
			);
		}
		wp_send_json_success( $response );
	}

	/**
	 * Modify Email Template.
	 *
	 * @param array  $wp_new_user_notification_email email data.
	 * @param object $user User object.
	 * @param string $blogname website name.
	 * @return array
	 * @since 1.0.0
	 */
	public function custom_wp_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
		if (
			isset( $this->saved_attributes[ $this->block_name ]['afterRegisterActions'] ) &&
			in_array( 'sendMail', $this->saved_attributes[ $this->block_name ]['afterRegisterActions'], true ) &&
			'custom' === $this->saved_attributes[ $this->block_name ]['emailTemplateType']
		) {

			$wp_new_user_notification_email['subject'] = preg_replace( '/\{{site_title}}/', $blogname, $this->email_settings['subject'] );

			$message = $this->email_settings['message'];

			$find = array( '/\{{login_url}}/', '/\[field=password\]/', '/\[field=username\]/', '/\[field=email\]/', '/\[field=first_name\]/', '/\[field=last_name\]/', '/\{{site_title}}/' );

			$replacement = array( esc_url( wp_login_url( get_permalink() ) ), $this->email_settings['user_pass'], $this->email_settings['user_login'], $this->email_settings['user_email'], $this->email_settings['first_name'], $this->email_settings['last_name'], $blogname );

			if ( isset( $this->email_settings['user_pass'] ) ) {
				$message = preg_replace( $find, $replacement, $message );
			}

			$wp_new_user_notification_email['message'] = $message;

			$wp_new_user_notification_email['headers'] = $this->email_settings['headers'];
		}
		return $wp_new_user_notification_email;
	}

	/**
	 * Generate User name from email.
	 *
	 * @param string $email email.
	 * @param string $suffix emial suffix.
	 * @return string
	 * @since 1.0.0
	 */
	public function create_username( $email, $suffix ) {

		$username_parts = array();

		// If there are no parts, e.g. name had unicode chars, or was not provided, fallback to email.
		if ( empty( $username_parts ) ) {
			$email_parts    = explode( '@', $email );
			$email_username = $email_parts[0];

			// Exclude common prefixes.
			if ( in_array(
				$email_username,
				array(
					'sales',
					'hello',
					'mail',
					'contact',
					'info',
				),
				true
			) ) {
				// Get the domain part.
				$email_username = $email_parts[1];
			}

			$username_parts[] = sanitize_user( $email_username, true );
		}//end if
		$username = strtolower( implode( '', $username_parts ) );

		if ( $suffix ) {
			$username .= $suffix;
		}

		if ( username_exists( $username ) ) {
			// Generate something unique to append to the username in case of a conflict with another user.
			$suffix = '-' . zeroise( wp_rand( 0, 9999 ), 4 );
			return $this->create_username( $email, $suffix );
		}

		return $username;
	}

	/**
	 * Render block function for Register.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block The block data.
	 * @since 1.0.0
	 * @return string|null|boolean Returns the new block content.
	 */
	public function register_render_block( $block_content, $block ) {

		// If not register block, skip it.
		if ( ( 'uagb/register' !== $block['blockName'] ) ) {
			return $block_content;
		}

		$count_of_innerblocks = count( $block['innerBlocks'] );

		// Array of child-block names.
		$innerblocks_list = array();

		// Add child-block names to $innerblocks_list.
		for ( $i = 0; $i < $count_of_innerblocks; $i++ ) {
			array_push( $innerblocks_list, $block['innerBlocks'][ $i ]['blockName'] );
		}

		// Since email is the minimum required field to create an account, don't render the form if email field isn't present.
		if ( ! in_array( 'uagb/register-email', $innerblocks_list ) ) {
			return null;
		}

		// If re-enter password field is present but the normal password field isn't, don't render it (re-enter password field).
		if (
			! in_array( 'uagb/register-password', $innerblocks_list ) &&
			in_array( 'uagb/register-reenter-password', $innerblocks_list )
		) {
			// Iterate through innerblocks till re-enter password field is found.
			foreach ( $block['innerBlocks'] as $innerblock ) {
				// If the current innerblock is re-enter password, don't render it.
				if ( ( 'uagb/register-reenter-password' === $innerblock['blockName'] ) ) {
					$block_content = str_replace( $innerblock['innerContent'], '', $block_content );
				}
			}
		}

		return $block_content;
	}
}
