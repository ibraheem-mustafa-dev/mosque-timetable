<?php
namespace SpectraPro;

use SpectraPro\Admin\License_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Plugin Loader.
 *
 * @package spectra-pro
 * @since 1.0.0
 */
class PluginLoader {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class Instance.
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class class name.
	 */
	public function autoload( $class ) {
		if ( 0 !== strpos( $class, __NAMESPACE__ ) ) {
			return;
		}

		$class_to_load = $class;

		$filename = preg_replace(
			[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
			[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
			$class_to_load
		);

		if ( is_string( $filename ) ) {
			$filename = strtolower( $filename );

			$file = SPECTRA_PRO_DIR . $filename . '.php';

			// if the file redable, include it.
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
		register_activation_hook( SPECTRA_PRO_FILE, array( $this, 'activation_reset' ) );
		add_action( 'plugins_loaded', array( $this, 'on_plugin_init' ) );
		add_filter( 'bsf_core_stats', array( $this, 'add_spectra_pro_analytics_data' ), 11 );
		add_filter( 'spectra_deactivation_survey_data', array( $this, 'add_pro_deactivation_data' ) );
	}

	/**
	 * After Finish loading UAG Free, then loaded pro core functionality
	 *
	 * Hooked - uagb_core_loaded
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function on_plugin_init() {
		// Default languages directory.
		$lang_dir = SPECTRA_PRO_DIR . 'languages/';

		// Traditional WordPress plugin locale filter.
		global $wp_version;

		$get_locale = get_locale();

		if ( $wp_version >= 4.7 ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Language Locale for plugin
		 *
		 * @var string $get_locale The locale to use.
		 * Uses get_user_locale()` in WordPress 4.7 or greater,
		 * otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'spectra-pro' );
		$mofile = '';
		if ( is_string( $locale ) ) {
			$mofile = sprintf( '%1$s-%2$s.mo', 'spectra-pro', $locale );
		}

		// Setup paths to current locale file.
		$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
		$mofile_local  = $lang_dir . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/spectra-pro/ folder.
			load_textdomain( 'spectra-pro', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/spectra-pro/languages/ folder.
			load_textdomain( 'spectra-pro', $mofile_local );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'spectra-pro', false, $lang_dir );
		}

		if ( ! defined( 'UAGB_VER' ) ) {
			add_action( 'admin_notices', array( $this, 'spectra_pro_fail_load' ) );
			return;
		}

		if ( ! did_action( 'spectra_core_loaded' ) || ! version_compare( UAGB_VER, SPECTRA_CORE_REQUIRED_VER, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'spectra_pro_fail_load_out_of_date' ) );
			return;
		}

		if ( is_admin() ) {
			Core\Admin::init();
		}

		( new License_Handler() )->init();
		Core\Base::init();
		Core\Assets::init();
		BlocksConfig\Config::init();
		Core\Extensions_Manager::init();

	}

	/**
	 * Set Redirect flag on activation.
	 *
	 * @Hooked - register_activation_hook
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function activation_reset() {
		update_option( '__spectra_pro_do_redirect', true );
	}

	/**
	 * Check spectra core is installed or not.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_spectra_core_installed() {
		$path    = 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php';
		$plugins = get_plugins();

		return isset( $plugins[ $path ] );
	}

	/**
	 * Admon Notice Callback if failed to load core.
	 *
	 * Hooked - admin_notices
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function spectra_pro_fail_load() {
		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		$plugin = 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php';

		if ( $this->is_spectra_core_installed() ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );

			$message  = '<h3>' . esc_html__( 'Activate the Spectra Plugin', 'spectra-pro' ) . '</h3>';
			$message .= '<p>' . esc_html__( 'Before you can use all the features of Spectra Pro, you need to activate the Spectra plugin first.', 'spectra-pro' ) . '</p>';
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__( 'Activate Now', 'spectra-pro' ) ) . '</p>';
		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}

			$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=ultimate-addons-for-gutenberg' ), 'install-plugin_ultimate-addons-for-gutenberg' );

			$message  = '<h3>' . esc_html__( 'Install and Activate the Spectra Plugin', 'spectra-pro' ) . '</h3>';
			$message .= '<p>' . esc_html__( 'Before you can use all the features of Spectra Pro, you need to install and activate the Spectra plugin first.', 'spectra-pro' ) . '</p>';
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__( 'Install Spectra', 'spectra-pro' ) ) . '</p>';
		}//end if

		// Phpcs ignore comment is required as $message variable is already escaped.
		echo '<div class="error">' . $message . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $message is already escaped above.
	}

	/**
	 * Admon Notice Callback if failed to load updated core.
	 *
	 * Hooked - admin_notices
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function spectra_pro_fail_load_out_of_date() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$file_path = 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php';

		$upgrade_link = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file_path, 'upgrade-plugin_' . $file_path );
		$message      = '<p>' . esc_html__( 'Spectra Pro is not working because you are using an old version of Spectra.', 'spectra-pro' ) . '</p>';
		$message     .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $upgrade_link, esc_html__( 'Update Spectra Now', 'spectra-pro' ) ) . '</p>';

		// Phpcs ignore comment is required as $message variable is already escaped.
		echo '<div class="error">' . $message . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $message is already escaped above.
	}

	/**
	 * Add pro data required for plugin deactivation survey.
	 *
	 * @param array $deactivation_data array of free deactivation data.
	 * @since 1.2.1
	 * @return array
	 */
	public function add_pro_deactivation_data( $deactivation_data ) {
		$deactivation_data[] = [
			'id'                => 'deactivation-survey-spectra-pro',
			'popup_logo'        => SPECTRA_PRO_URL . 'assets/images/spectra.svg',
			'plugin_slug'       => 'spectra-pro',
			'popup_title'       => 'Quick Feedback',
			'support_url'       => 'https://wpspectra.com/contact/',
			// Translators: Message asking users for deactivation feedback. %1s is the product name.
			'popup_description' => sprintf( 'If you have a moment, please share why you are deactivating %1s:', 'Spectra Pro' ),
			'show_on_screens'   => [ 'plugins' ],
			'plugin_version'    => SPECTRA_PRO_VER,
		];

		return $deactivation_data;
	}

	/**
	 * Callback function to add spectra pro specific analytics data.
	 *
	 * @param array $default_stats existing default stats.
	 * @return array $default_stats stats with spectra specific stats array.
	 * @since 1.2.1
	 */
	public function add_spectra_pro_analytics_data( $default_stats ) {
		if ( ! empty( $default_stats['plugin_data']['spectra'] ) && is_array( $default_stats['plugin_data']['spectra'] ) ) {
			$default_stats['plugin_data']['spectra'] = array_merge_recursive( $default_stats['plugin_data']['spectra'], $this->pro_analytics_data() );
		}

		return $default_stats;
	}

	/**
	 * Returns pro analytics data
	 *
	 * @since 1.2.1
	 * @return array
	 */
	public function pro_analytics_data() {
		$pro_data                = [];
		$pro_data['pro_version'] = SPECTRA_PRO_VER;
		if ( class_exists( 'SpectraPro\Admin\License_Handler' ) ) {
			$pro_data['boolean_values']['license_active'] = ( new License_Handler() )->is_license_active();
		} else {
			$pro_data['boolean_values']['license_active'] = false;
		}
		$pro_data['dynamic_content']      = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_enable_dynamic_content' );
		$pro_data['gbs_extension']        = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_enable_gbs_extension' );
		$pro_data['dynamic_content_mode'] = \UAGB_Admin_Helper::get_admin_settings_option( 'uag_dynamic_content_mode' );

		return $pro_data;
	}

}

/**
 * Kicking this off by calling 'get_instance()' method
 */
PluginLoader::get_instance();
