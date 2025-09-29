<?php

declare(strict_types=1);

/**
 * IDE Suppression Notes:
 * - filter_var() and FILTER_* constants are core PHP functions but may not be recognized by all IDE configurations
 * - SimpleXLSX classes are optional dependencies with proper error handling
 * - All @phpstan-ignore annotations are intentional for optional/configuration-dependent code
 */

/**
 * Plugin Name: Mosque Prayer Timetable System v3.0
 * Description: Complete prayer timetable system with ACF Pro, PWA, SEO, and Hijri calendar features
 * Version: 3.0.0
 * Supports: Advanced Custom Fields Pro for enhanced admin interface
 * Author: Mosque Timetable Team
 * Text Domain: mosque-timetable
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'MOSQUE_TIMETABLE_VERSION', '3.0.1' );
define( 'MOSQUE_TIMETABLE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOSQUE_TIMETABLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MOSQUE_TIMETABLE_ASSETS_URL', MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/' );

// Load Composer autoloader with fallback strategy
// Prefer the plugin's own autoloader for self-contained operation
$plugin_autoload = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $plugin_autoload ) ) {
	require $plugin_autoload;
} else {
	// Fallback for Composer-managed sites that load a global autoloader
	$root_autoload = ABSPATH . 'vendor/autoload.php';
	if ( is_readable( $root_autoload ) ) {
		require $root_autoload;
	}
	// If no autoloader is found, libraries will be handled by conditional checks
}

// Use statements for SimpleXLSX libraries
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

// === Load ACF stubs only if ACF isn't available (after other plugins) ===
add_action(
	'plugins_loaded',
	static function () {
		// If ACF Pro (or ACF) is active, do nothing
		if ( function_exists( 'get_field' ) || defined( 'ACF_VERSION' ) || class_exists( 'ACF' ) ) {
			return;
		}

		// As a final guard, only load the stub if get_field isn't defined
		$stub = __DIR__ . '/tools/stubs-acf.php';
		if ( ! function_exists( 'get_field' ) && file_exists( $stub ) ) {
			require_once $stub;
		}
	},
	20
); // run after ACF (usually priority 10)


/**
 * Check if ACF Pro is properly available
 */
function is_acf_pro_available(): bool {
	return ( function_exists( 'acf_form' )
		&& function_exists( 'acf_form_head' )
		&& ( class_exists( 'ACF' ) || defined( 'ACF_PRO' ) )
		&& function_exists( 'acf_add_local_field_group' ) );
}

// === ACF detection & fallback helpers ===
if ( ! function_exists( 'mt_has_acf' ) ) {
	function mt_has_acf(): bool {
		return function_exists( 'get_field' ) && function_exists( 'update_field' );
	}
}

// ---- Add these two helpers once (above your import functions) ----

// Map common headings to your ACF field keys.
// You can add more aliases on the left if your CSV/XLSX uses them.
$MT_FIELD_MAP = array(
	'fajr_start'     => 'fajr',
	'fajr_jamaat'    => 'fajr_jamat',
	'sunrise'        => 'sunrise',
	'zuhr_start'     => 'zuhr',
	'zuhr_jamaat'    => 'zuhr_jamat',
	'asr_start'      => 'asr',
	'asr_jamaat'     => 'asr_jamat',
	'maghrib_start'  => 'maghrib',
	'maghrib_jamaat' => 'maghrib_jamat',
	'isha_start'     => 'isha',
	'isha_jamaat'    => 'isha_jamat',
	'jummah_1'       => 'jumah1',
	'jummah_2'       => 'jumah2',
);

// Convert messy times to 24-hour HH:MM or blank.
function mt_normalise_time( $time ): string {
	$time = trim( (string) $time );
	if ( '' === $time || '--:--' === $time ) {
		return '';
	}
	// already like 09:05 or 19:30
	if ( preg_match( '/^(\\d{2}):(\\d{2})$/', $time ) ) {
		return $time;
	}
	// like 2:5 or 2:39 or 10:51
	if ( preg_match( '/^(\\d{1,2}):(\\d{1,2})$/', $time, $m ) ) {
		$h = (int) $m[1];
		$i = (int) $m[2];
		// Early morning (0–2) leave as-is; 3–11 assume PM and add 12
		if ( $h >= 3 && $h <= 11 ) {
			$h += 12;
		}
		return sprintf( '%02d:%02d', $h, $i );
	}
	return $time;
}


// === Read rows for a month (works with/without ACF) ===
if ( ! function_exists( 'mt_get_month_rows' ) ) {
	function mt_get_month_rows( int $month, ?int $year = null ): array {
		$month = max( 1, min( 12, $month ) );
		$year  = $year ?? (int) get_option( 'default_year', (int) wp_date( 'Y' ) );

		if ( mt_has_acf() ) {
			// Use year-specific ACF field names for multi-year support
			$field_name = "daily_prayers_{$year}_{$month}";
			$rows       = get_field( $field_name, 'option' );

			// Fallback to legacy field name if year-specific doesn't exist
			if ( ! $rows || ! is_array( $rows ) ) {
				$legacy_field_name = "daily_prayers_{$month}";
				$rows              = get_field( $legacy_field_name, 'option' );
			}

			return is_array( $rows ) ? array_values( $rows ) : array();
		}

		$all = get_option( 'mosque_timetable_rows', array() );
		if ( isset( $all[ $year ][ $month ] ) && is_array( $all[ $year ][ $month ] ) ) {
			return array_values( $all[ $year ][ $month ] );
		}
		return array();
	}
}

// === Apply terminology overrides to text labels ===
if ( ! function_exists( 'mt_apply_terminology' ) ) {
	function mt_apply_terminology( string $text ): string {
		static $overrides_cache = null;

		// Load overrides once and cache
		if ( null === $overrides_cache ) {
			$overrides_cache = array();

			if ( mt_has_acf() ) {
				$terminology_overrides = get_field( 'terminology_overrides', 'option' );
			} else {
				$terminology_overrides = get_option( 'terminology_overrides', array() );
			}

			if ( is_array( $terminology_overrides ) ) {
				foreach ( $terminology_overrides as $override ) {
					if ( ! empty( $override['from'] ) && ! empty( $override['to'] ) && ( $override['enabled'] ?? 1 ) ) {
						$overrides_cache[ $override['from'] ] = $override['to'];
					}
				}
			}
		}

		// Apply overrides (case-sensitive)
		return str_replace( array_keys( $overrides_cache ), array_values( $overrides_cache ), $text );
	}
}

// === Get subscribe calendar URL (with custom override support) ===
if ( ! function_exists( 'mt_get_subscribe_url' ) ) {
	function mt_get_subscribe_url(): string {
		// Check for custom override first
		if ( mt_has_acf() ) {
			$custom_url = get_field( 'custom_subscribe_url', 'option' );
		} else {
			$custom_url = get_option( 'custom_subscribe_url', '' );
		}

		// Return custom URL if set and valid, otherwise return default
		/** @phpstan-ignore-next-line */
		if ( ! empty( $custom_url ) && filter_var( $custom_url, FILTER_VALIDATE_URL ) ) {
			return $custom_url;
		}

		return get_site_url() . '/prayer-times/calendar.ics';
	}
}

// === Save rows for a month (works with/without ACF) ===
if ( ! function_exists( 'mt_save_month_rows' ) ) {
	function mt_save_month_rows( int $month, array $rows, ?int $year = null ): bool {
		$month = max( 1, min( 12, $month ) );
		$year  = $year ?? (int) get_option( 'default_year', (int) wp_date( 'Y' ) );
		$rows  = array_values( $rows );

		if ( mt_has_acf() ) {
			// Use year-specific ACF field names for multi-year support
			$field_name = "daily_prayers_{$year}_{$month}";
			return (bool) update_field( $field_name, $rows, 'option' );
		}

		$all                    = get_option( 'mosque_timetable_rows', array() );
		$all[ $year ]           = $all[ $year ] ?? array();
		$all[ $year ][ $month ] = $rows;
		update_option( 'mosque_timetable_rows', $all, false );
		return true;
	}
}

// === Clear all rows (works with/without ACF) ===
if ( ! function_exists( 'mt_clear_all_rows' ) ) {
	function mt_clear_all_rows( ?int $year = null ): void {
		if ( mt_has_acf() ) {
			$year = $year ?? (int) get_option( 'default_year', (int) wp_date( 'Y' ) );

			// Clear year-specific fields
			for ( $m = 1; $m <= 12; $m++ ) {
				update_field( "daily_prayers_{$year}_{$m}", array(), 'option' );
			}

			// Also clear legacy fields if clearing current year
			$current_year = (int) get_option( 'default_year', (int) wp_date( 'Y' ) );
			if ( $year === $current_year ) {
				for ( $m = 1; $m <= 12; $m++ ) {
					update_field( "daily_prayers_{$m}", array(), 'option' );
				}
			}
		} elseif ( $year ) {
			$all = get_option( 'mosque_timetable_rows', array() );
			unset( $all[ $year ] );
			update_option( 'mosque_timetable_rows', $all, false );
		} else {
			update_option( 'mosque_timetable_rows', array(), false );
		}
	}

	/**
	 * Get PDF URL for a specific month and year
	 *
	 * @param int      $month Month number (1-12)
	 * @param int|null $year Year (defaults to current year)
	 * @return string|null PDF URL or null if not set
	 */
	function mt_get_pdf_for_month( ?int $month = null, ?int $year = null ): ?string {
		// Defaults if any arg missing
		$month = (int) ( $month ?? wp_date( 'n' ) ); // 1..12
		$year  = (int) ( $year ?? wp_date( 'Y' ) );

		// Clamp month safely
		if ( $month < 1 || $month > 12 ) {
			$month = (int) wp_date( 'n' );
		}

		if ( mt_has_acf() ) {
			// ACF option repeater
			$field_name = "daily_prayers_{$month}";
			$rows       = get_field( $field_name, 'option' );

			if ( is_array( $rows ) && ! empty( $rows ) ) {
				$first = $rows[0] ?? array();
				if ( is_array( $first ) && ! empty( $first['pdf_url'] ) && is_string( $first['pdf_url'] ) ) {
					return $first['pdf_url'];
				}
			}
		}

		// Fallback to options
		$option_name = sprintf( 'mt_pdf_%04d_%d', $year, $month );
		$pdf_url     = get_option( $option_name, '' );

		return ( is_string( $pdf_url ) && '' !== $pdf_url ) ? $pdf_url : null;
	}

	/**
	 * Save PDF URL for a specific month and year
	 *
	 * @param int      $month Month number (1–12).
	 * @param int|null $year  Year (defaults to current year).
	 * @return bool
	 */
	function mt_save_pdf_for_month( int $month, string $pdf_url, ?int $year = null ): bool {
		$month   = max( 1, min( 12, (int) $month ) );
		$year    = (int) ( $year ?? wp_date( 'Y' ) );
		$pdf_url = esc_url_raw( $pdf_url );

		if ( mt_has_acf() ) {
			$field_name = "daily_prayers_{$month}";
			$rows       = get_field( $field_name, 'option' ) ?: array();

			if ( empty( $rows ) || ! is_array( $rows[0] ) ) {
				$rows = array( array( 'pdf_url' => $pdf_url ) );
			} else {
				$rows[0]['pdf_url'] = $pdf_url;
			}
			return (bool) update_field( $field_name, $rows, 'option' );
		}

		$option_name = sprintf( 'mt_pdf_%04d_%d', $year, $month );
		return (bool) update_option( $option_name, $pdf_url, false );
	}

	// Ensure Composer autoload is available for SimpleXLSX
	$mt_vendor = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
	if ( is_readable( $mt_vendor ) ) {
		require_once $mt_vendor;
	} else {
		// Optional: Helpful message for missing deps
		// error_log('mosque-timetable: vendor/autoload.php not found. Run composer install.');
	}

	/**
	 * Get/boot the global WP_Filesystem.
	 *
	 * @return WP_Filesystem_Base|false
	 */
	function mt_fs() {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem;
	}

	/**
	 * Main Mosque Timetable Plugin Class
	 */
	class MosqueTimetablePlugin {





		/**
		 * Plugin instance
		 */
		private static $instance = null;

		/**
		 * Get plugin instance
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			$this->init_hooks();
		}

		/**
		 * Initialize WordPress hooks
		 */
		private function init_hooks(): void {
			register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );

			add_action( 'init', array( $this, 'init' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_init', array( $this, 'check_year_advancement' ) ); // Auto-check year advancement
			add_action( 'acf/init', array( $this, 'register_acf_fields' ) );
			add_action( 'acf/save_post', array( $this, 'handle_acf_save_redirect' ) );
			add_action( 'rest_api_init', array( $this, 'register_rest_endpoints' ) );
			add_action( 'wp_head', array( $this, 'add_pwa_meta_tags' ) );
			add_action( 'wp_head', array( $this, 'add_structured_data' ) );
			add_action( 'wp_footer', array( $this, 'add_pwa_cta_buttons' ) );
			add_action( 'init', array( $this, 'register_shortcodes' ) );
			add_action( 'init', array( $this, 'add_rewrite_rules' ) );
			add_action( 'template_redirect', array( $this, 'handle_virtual_pages' ) );
			add_action( 'init', array( $this, 'handle_service_worker_request' ) );
			add_action( 'init', array( $this, 'init_push_notifications_cron' ) );
			add_action( 'mt_send_push_notifications', array( $this, 'process_push_notifications' ) );
			add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_credit' ) );
			add_action( 'wp_footer', array( $this, 'frontend_credit' ) );
			add_filter( 'robots_txt', array( $this, 'add_robots_txt_entries' ), 10, 2 );
			add_action( 'init', array( $this, 'add_rtl_support' ) );

			// AJAX hooks with proper security
			add_action( 'wp_ajax_save_month_timetable', array( $this, 'ajax_save_month_timetable' ) );
			add_action( 'wp_ajax_import_csv_timetable', array( $this, 'ajax_import_csv_timetable' ) );
			add_action( 'wp_ajax_export_ics_calendar', array( $this, 'ajax_export_ics_calendar' ) );
			add_action( 'wp_ajax_export_csv_calendar', array( $this, 'ajax_export_csv_calendar' ) );

			// Additional AJAX hooks are registered at the bottom of the file to avoid duplicates

			// Admin hooks
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 9 ); // Priority 9 to load before ACF
		}

		/**
		 * Plugin initialization
		 */
		public function init(): void {
			load_plugin_textdomain(
				'mosque-timetable',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages'
			);
		}

		/**
		 * Plugin activation
		 */
		public function activate_plugin(): void {
			// Create necessary database tables if needed
			$this->create_plugin_tables();

			// Add rewrite rules and flush
			$this->add_rewrite_rules();
			flush_rewrite_rules();

			// Set default options
			$this->set_default_options();

			// Auto-populate monthly timetables based on default year
			$this->auto_populate_monthly_structure();
		}

		/**
		 * Plugin deactivation
		 */
		public function deactivate_plugin(): void {
			// Clean up temporary data
			flush_rewrite_rules();
		}

		/**
		 * Create plugin database tables
		 */
		private function create_plugin_tables(): void {
			// Tables will be managed via ACF Pro, but we can add custom tables here if needed
		}

		/**
		 * Set default plugin options
		 */
		private function set_default_options(): void {
			$defaults = array(
				'mt_today_color'        => '#FFF9C4',
				'mt_friday_color'       => '#E8F5E8',
				'mt_row_alt_bg'         => '#F8F9FA',
				'mt_next_prayer_bg'     => '#E3F2FD',
				'mt_btn_bg'             => '#1976D2',
				'mt_table_text_color'   => '#333333',
				'mt_header_text_color'  => '#FFFFFF',
				'enable_pwa'            => true,
				'enable_countdown'      => true,
				'notification_text'     => 'Time for {prayer} prayer!',
				'custom_subscribe_url'  => '',
				'terminology_overrides' => array(),
			);

			foreach ( $defaults as $key => $value ) {
				if ( get_field( $key, 'option' ) === false ) {
					update_field( $key, $value, 'option' );
				}
			}
		}

		/**
		 * Enqueue frontend assets
		 */
		public function enqueue_frontend_assets(): void {
			wp_enqueue_style(
				'mosque-timetable-style',
				MOSQUE_TIMETABLE_ASSETS_URL . 'mosque-timetable.css',
				array(),
				MOSQUE_TIMETABLE_VERSION
			);

			wp_enqueue_script(
				'mosque-timetable-script',
				MOSQUE_TIMETABLE_ASSETS_URL . 'mosque-timetable.js',
				array( 'jquery' ),
				MOSQUE_TIMETABLE_VERSION,
				true
			);

			// Enqueue modal assets - depends on main CSS for variables
			wp_enqueue_style(
				'mosque-timetable-modal',
				MOSQUE_TIMETABLE_ASSETS_URL . 'mt-modal.css',
				array( 'mosque-timetable-style' ),
				MOSQUE_TIMETABLE_VERSION
			);

			wp_enqueue_script(
				'mosque-timetable-modal',
				MOSQUE_TIMETABLE_ASSETS_URL . 'mt-modal.js',
				array( 'jquery', 'mosque-timetable-script' ),
				MOSQUE_TIMETABLE_VERSION,
				true
			);

			// Get VAPID public key for push notifications
			$vapid_public_key = mt_has_acf() ? get_field( 'vapid_public_key', 'option' ) : get_option( 'vapid_public_key' );

			// Localize script with AJAX URL and nonce
			wp_localize_script(
				'mosque-timetable-script',
				'mosqueTimetable',
				array(
					'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
					'nonce'            => wp_create_nonce( 'mosque_timetable_nonce' ),
					'restUrl'          => rest_url( 'mosque/v1/' ),
					'restNonce'        => wp_create_nonce( 'wp_rest' ),
					'pluginUrl'        => MOSQUE_TIMETABLE_PLUGIN_URL,
					'assetsUrl'        => MOSQUE_TIMETABLE_ASSETS_URL,
					'serviceWorkerUrl' => plugins_url( 'assets/sw.js', __FILE__ ),
					'manifestUrl'      => plugins_url( 'assets/manifest.json', __FILE__ ),
					'offlineUrl'       => plugins_url( 'assets/offline.html', __FILE__ ),
					'vapidPublicKey'   => $vapid_public_key ?: '',
					'strings'          => array(
						'nextPrayer'    => __( 'Next Prayer', 'mosque-timetable' ),
						'timeRemaining' => __( 'Time Remaining', 'mosque-timetable' ),
						'prayerTime'    => __( 'Prayer Time', 'mosque-timetable' ),
					),
				)
			);

			// Localize modal script with export-specific configuration
			wp_localize_script(
				'mosque-timetable-modal',
				'mosqueTimetableModal',
				array(
					'restUrl'      => rest_url( 'mosque/v1/' ),
					'restNonce'    => wp_create_nonce( 'wp_rest' ),
					'currentYear'  => get_field( 'default_year', 'option' ) ?: wp_date( 'Y' ),
					'currentMonth' => wp_date( 'n' ),
					'siteUrl'      => get_site_url(),
					'strings'      => array(
						'exportCalendar'  => __( 'Export Prayer Calendar', 'mosque-timetable' ),
						'downloadSuccess' => __( 'Calendar downloaded successfully!', 'mosque-timetable' ),
						'downloadError'   => __( 'Error downloading calendar. Please try again.', 'mosque-timetable' ),
						'googleCalendar'  => __( 'Opening Google Calendar...', 'mosque-timetable' ),
						'selectOptions'   => __( 'Please configure your export options.', 'mosque-timetable' ),
					),
				)
			);
		}

		/**
		 * Enqueue admin assets
		 */
		public function enqueue_admin_assets( $hook ) {
			// Debug: Log the hook name to help diagnose loading issues
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Admin hook: ' . $hook );
			}

			// Allow loading on all mosque-related admin pages
			$is_mosque_page = (
				strpos( $hook, 'mosque-' ) !== false ||
				strpos( $hook, 'mosque_' ) !== false ||
				strpos( $hook, 'timetables' ) !== false ||
				isset( $_GET['page'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'mosque' ) !== false // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page detection for asset loading
			);

			if ( ! $is_mosque_page ) {
				return;
			}

			wp_enqueue_style(
				'mosque-timetable-admin-style',
				MOSQUE_TIMETABLE_ASSETS_URL . 'mosque-timetable-admin.css',
				array(),
				MOSQUE_TIMETABLE_VERSION
			);

			wp_enqueue_script(
				'mosque-timetable-admin-script',
				MOSQUE_TIMETABLE_ASSETS_URL . 'mosque-timetable-admin.js',
				array( 'jquery' ),
				MOSQUE_TIMETABLE_VERSION,
				true
			);

			wp_localize_script(
				'mosque-timetable-admin-script',
				'mosqueTimetableAdmin',
				array(
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'nonce'        => wp_create_nonce( 'mosque_timetable_nonce' ),
					'pluginUrl'    => MOSQUE_TIMETABLE_PLUGIN_URL,
					'assetsUrl'    => MOSQUE_TIMETABLE_ASSETS_URL,
					'currentYear'  => get_field( 'default_year', 'option' ) ?: wp_date( 'Y' ),
					'currentMonth' => wp_date( 'n' ),
					'strings'      => array(
						'saveSuccess'       => __( 'Month timetable saved successfully!', 'mosque-timetable' ),
						'saveError'         => __( 'Error saving month timetable. Please try again.', 'mosque-timetable' ),
						'unsavedChanges'    => __( 'You have unsaved changes. Are you sure you want to leave?', 'mosque-timetable' ),
						'confirmLeave'      => __( 'You have unsaved changes. Are you sure you want to leave this tab?', 'mosque-timetable' ),
						'generateSuccess'   => __( 'All dates generated successfully! Hijri dates calculated automatically.', 'mosque-timetable' ),
						'generateError'     => __( 'Failed to generate dates', 'mosque-timetable' ),
						'hijriRecalculated' => __( 'Hijri dates recalculated successfully!', 'mosque-timetable' ),

						// NEW ones used by updated JS:
						'importSuccess'     => __( 'Import completed successfully!', 'mosque-timetable' ),
						'importError'       => __( 'Error importing file. Please check format and try again.', 'mosque-timetable' ),
						'noMonth'           => __( 'Please select a month.', 'mosque-timetable' ),
						'noFile'            => __( 'Please select a file before importing.', 'mosque-timetable' ),
						'noPaste'           => __( 'Please paste your timetable data before importing.', 'mosque-timetable' ),
						'networkError'      => __( 'Network error: Could not connect to server', 'mosque-timetable' ),
						'permissionError'   => __( 'Permission denied: Please refresh the page', 'mosque-timetable' ),
						'serverError'       => __( 'Server error: Please try again later', 'mosque-timetable' ),
						'connectionError'   => __( 'Error connecting to server: ', 'mosque-timetable' ),
						'loadError'         => __( 'Failed to load month data', 'mosque-timetable' ),
						'invalidTime'       => __( 'Invalid time format. Please use HH:MM format.', 'mosque-timetable' ),
					),
				)
			);
		}

		/**
		 * Register ACF field groups
		 */
		public function register_acf_fields(): void {
			if ( ! is_acf_pro_available() ) {
				return;
			}

			$this->register_mosque_settings_fields();
			$this->register_monthly_timetables_fields();
			$this->register_appearance_settings_fields();
		}

		// ← next thing here must be ANOTHER method of the class,
		// e.g. private function register_mosque_settings_fields() { … }
		// Do NOT put global functions here

		/**
		 * Register Mosque Settings ACF fields
		 */
		private function register_mosque_settings_fields(): void {
			acf_add_local_field_group(
				array(
					'key'        => 'group_mosque_configuration',
					'title'      => __( 'Mosque Configuration', 'mosque-timetable' ),
					'fields'     => array(
						array(
							'key'           => 'field_mosque_name',
							'label'         => __( 'Mosque Name', 'mosque-timetable' ),
							'name'          => 'mosque_name',
							'type'          => 'text',
							'instructions'  => __( 'Enter the name of your mosque', 'mosque-timetable' ),
							'required'      => 1,
							'default_value' => get_bloginfo( 'name' ),
							'wrapper'       => array( 'width' => '50' ),
						),
						array(
							'key'           => 'field_mosque_address',
							'label'         => __( 'Mosque Address', 'mosque-timetable' ),
							'name'          => 'mosque_address',
							'type'          => 'textarea',
							'instructions'  => __( 'Enter the complete address of your mosque (street, city, postcode, country)', 'mosque-timetable' ),
							'required'      => 1,
							'default_value' => 'Birmingham, UK',
							'wrapper'       => array( 'width' => '50' ),
							'rows'          => 3,
						),
						array(
							'key'           => 'field_default_year',
							'label'         => __( 'Default Year', 'mosque-timetable' ),
							'name'          => 'default_year',
							'type'          => 'number',
							'instructions'  => __( 'Set the default year for displaying timetables and auto-filling dates', 'mosque-timetable' ),
							'required'      => 1,
							'default_value' => wp_date( 'Y' ),
							'min'           => 2020,
							'max'           => 2035,
							'wrapper'       => array( 'width' => '50' ),
						),
						array(
							'key'           => 'field_available_months',
							'label'         => __( 'Available Months', 'mosque-timetable' ),
							'name'          => 'available_months',
							'type'          => 'checkbox',
							'instructions'  => __( 'Select which months you have prayer times for. Only checked months will show tabs in the admin.', 'mosque-timetable' ),
							'required'      => 1,
							'choices'       => array(
								'1'  => __( 'January', 'mosque-timetable' ),
								'2'  => __( 'February', 'mosque-timetable' ),
								'3'  => __( 'March', 'mosque-timetable' ),
								'4'  => __( 'April', 'mosque-timetable' ),
								'5'  => __( 'May', 'mosque-timetable' ),
								'6'  => __( 'June', 'mosque-timetable' ),
								'7'  => __( 'July', 'mosque-timetable' ),
								'8'  => __( 'August', 'mosque-timetable' ),
								'9'  => __( 'September', 'mosque-timetable' ),
								'10' => __( 'October', 'mosque-timetable' ),
								'11' => __( 'November', 'mosque-timetable' ),
								'12' => __( 'December', 'mosque-timetable' ),
							),
							'default_value' => array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' ),
							'layout'        => 'horizontal',
							'wrapper'       => array( 'width' => '100' ),
						),
						array(
							'key'           => 'field_custom_subscribe_url',
							'label'         => __( 'Custom Subscribe URL (Override)', 'mosque-timetable' ),
							'name'          => 'custom_subscribe_url',
							'type'          => 'url',
							'instructions'  => __( 'Optional: Override the default calendar subscription URL. Leave empty to use the automatic URL. This allows you to point to external calendar feeds if needed.', 'mosque-timetable' ),
							'required'      => 0,
							'default_value' => '',
							'wrapper'       => array( 'width' => '100' ),
							'placeholder'   => 'https://example.com/custom-calendar.ics',
						),
						array(
							'key'          => 'field_terminology_overrides',
							'label'        => __( 'Terminology Overrides', 'mosque-timetable' ),
							'name'         => 'terminology_overrides',
							'type'         => 'repeater',
							'instructions' => __( 'Customize terminology used throughout the plugin interface. Changes apply to labels only, not internal data. Examples: "Mosque" → "Masjid", "Zuhr" → "Dhuhr", "Maghrib" → "Maghreb".', 'mosque-timetable' ),
							'required'     => 0,
							'layout'       => 'table',
							'button_label' => __( 'Add Override', 'mosque-timetable' ),
							'min'          => 0,
							'max'          => 20,
							'sub_fields'   => array(
								array(
									'key'          => 'field_terminology_from',
									'label'        => __( 'From', 'mosque-timetable' ),
									'name'         => 'from',
									'type'         => 'text',
									'instructions' => __( 'Original term to replace (case-sensitive)', 'mosque-timetable' ),
									'required'     => 1,
									'wrapper'      => array( 'width' => '40' ),
									'placeholder'  => __( 'Mosque', 'mosque-timetable' ),
								),
								array(
									'key'          => 'field_terminology_to',
									'label'        => __( 'To', 'mosque-timetable' ),
									'name'         => 'to',
									'type'         => 'text',
									'instructions' => __( 'Replacement term', 'mosque-timetable' ),
									'required'     => 1,
									'wrapper'      => array( 'width' => '40' ),
									'placeholder'  => __( 'Masjid', 'mosque-timetable' ),
								),
								array(
									'key'           => 'field_terminology_enabled',
									'label'         => __( 'Enabled', 'mosque-timetable' ),
									'name'          => 'enabled',
									'type'          => 'true_false',
									'instructions'  => __( 'Toggle this override on/off', 'mosque-timetable' ),
									'default_value' => 1,
									'wrapper'       => array( 'width' => '20' ),
								),
							),
							'wrapper'      => array( 'width' => '100' ),
						),
						array(
							'key'          => 'field_push_notifications_tab',
							'label'        => 'Push Notifications Settings',
							'name'         => '',
							'type'         => 'tab',
							'instructions' => '',
							'placement'    => 'top',
						),
						array(
							'key'          => 'field_vapid_public_key',
							'label'        => __( 'VAPID Public Key', 'mosque-timetable' ),
							'name'         => 'vapid_public_key',
							'type'         => 'text',
							'instructions' => __( 'VAPID public key for web push notifications. Required for push notifications to work.', 'mosque-timetable' ),
							'required'     => 0,
							'wrapper'      => array( 'width' => '50' ),
							'placeholder'  => 'BAbC...',
						),
						array(
							'key'          => 'field_vapid_private_key',
							'label'        => __( 'VAPID Private Key', 'mosque-timetable' ),
							'name'         => 'vapid_private_key',
							'type'         => 'password',
							'instructions' => __( 'VAPID private key for web push notifications. Keep this secure and private.', 'mosque-timetable' ),
							'required'     => 0,
							'wrapper'      => array( 'width' => '50' ),
							'placeholder'  => 'ABC123...',
						),
						array(
							'key'           => 'field_default_reminder_offsets',
							'label'         => __( 'Default Reminder Offsets', 'mosque-timetable' ),
							'name'          => 'default_reminder_offsets',
							'type'          => 'checkbox',
							'instructions'  => __( 'Default reminder times available to users for push notifications (in minutes before prayer)', 'mosque-timetable' ),
							'choices'       => array(
								'5'  => __( '5 minutes', 'mosque-timetable' ),
								'10' => __( '10 minutes', 'mosque-timetable' ),
								'15' => __( '15 minutes', 'mosque-timetable' ),
								'20' => __( '20 minutes', 'mosque-timetable' ),
								'30' => __( '30 minutes', 'mosque-timetable' ),
							),
							'default_value' => array( '10', '15', '20' ),
							'layout'        => 'horizontal',
							'wrapper'       => array( 'width' => '50' ),
						),
						array(
							'key'           => 'field_sunrise_warning_enabled',
							'label'         => __( 'Enable Sunrise Warning', 'mosque-timetable' ),
							'name'          => 'sunrise_warning_enabled',
							'type'          => 'true_false',
							'instructions'  => __( 'Allow users to receive notifications before sunrise (end of Fajr time)', 'mosque-timetable' ),
							'default_value' => 1,
							'wrapper'       => array( 'width' => '25' ),
						),
						array(
							'key'               => 'field_sunrise_warning_offset',
							'label'             => __( 'Sunrise Warning Offset', 'mosque-timetable' ),
							'name'              => 'sunrise_warning_offset',
							'type'              => 'select',
							'instructions'      => __( 'Default warning time before sunrise', 'mosque-timetable' ),
							'choices'           => array(
								'15' => __( '15 minutes', 'mosque-timetable' ),
								'30' => __( '30 minutes', 'mosque-timetable' ),
								'45' => __( '45 minutes', 'mosque-timetable' ),
								'60' => __( '1 hour', 'mosque-timetable' ),
							),
							'default_value'     => '30',
							'wrapper'           => array( 'width' => '25' ),
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_sunrise_warning_enabled',
										'operator' => '==',
										'value'    => '1',
									),
								),
							),
						),
						array(
							'key'           => 'field_privacy_note_text',
							'label'         => __( 'Privacy Note Text', 'mosque-timetable' ),
							'name'          => 'privacy_note_text',
							'type'          => 'textarea',
							'instructions'  => __( 'Text shown to users about data privacy when subscribing to push notifications', 'mosque-timetable' ),
							'default_value' => __( 'We will only send prayer reminder notifications. No personal data is stored beyond your subscription preferences. You can unsubscribe at any time.', 'mosque-timetable' ),
							'rows'          => 3,
							'wrapper'       => array( 'width' => '100' ),
						),
					),
					'location'   => array(
						array(
							array(
								'param'    => 'options_page',
								'operator' => '==',
								'value'    => 'mosque-settings',
							),
						),
						array(
							array(
								'param'    => 'page_template',
								'operator' => '==',
								'value'    => 'mosque-settings',
							),
						),
					),
					'menu_order' => 0,
				)
			);
		}

		/**
		 * Register Monthly Timetables ACF fields
		 */
		private function register_monthly_timetables_fields(): void {
			// Register individual month field groups dynamically
			$months = array(
				1  => 'January',
				2  => 'February',
				3  => 'March',
				4  => 'April',
				5  => 'May',
				6  => 'June',
				7  => 'July',
				8  => 'August',
				9  => 'September',
				10 => 'October',
				11 => 'November',
				12 => 'December',
			);

			foreach ( $months as $month_num => $month_name ) {
				acf_add_local_field_group(
					array(
						'key'        => 'group_month_' . $month_num,
						'title'      => $month_name . ' Prayer Times',
						'fields'     => array(
							array(
								'key'           => 'field_hijri_month_adjustment_' . $month_num,
								'label'         => 'Hijri Date Adjustment',
								'name'          => 'hijri_month_adjustment_' . $month_num,
								'type'          => 'select',
								'instructions'  => 'Adjust Hijri dates for this month if needed',
								'choices'       => array(
									'-1' => '-1 day',
									'0'  => 'Calculated (default)',
									'1'  => '+1 day',
								),
								'default_value' => '0',
								'wrapper'       => array( 'width' => '100' ),
							),
							array(
								'key'          => 'field_daily_prayers_' . $month_num,
								'label'        => 'Daily Prayer Times',
								'name'         => 'daily_prayers_' . $month_num,
								'type'         => 'repeater',
								'instructions' => 'Prayer times for each day of ' . $month_name,
								'max'          => 31,
								'layout'       => 'table',
								'button_label' => 'Add Day',
								'sub_fields'   => array(
									array(
										'key'      => 'field_day_number_' . $month_num,
										'label'    => 'Day',
										'name'     => 'day_number',
										'type'     => 'number',
										'required' => 1,
										'min'      => 1,
										'max'      => 31,
										'readonly' => 1,
										'wrapper'  => array( 'width' => '5' ),
									),
									array(
										'key'            => 'field_date_full_' . $month_num,
										'label'          => 'Date',
										'name'           => 'date_full',
										'type'           => 'date_picker',
										'required'       => 1,
										'display_format' => 'Y-m-d',
										'return_format'  => 'Y-m-d',
										'readonly'       => 1,
										'wrapper'        => array( 'width' => '8' ),
									),
									array(
										'key'      => 'field_day_name_' . $month_num,
										'label'    => 'Day Name',
										'name'     => 'day_name',
										'type'     => 'text',
										'readonly' => 1,
										'wrapper'  => array( 'width' => '7' ),
									),
									array(
										'key'          => 'field_hijri_date_' . $month_num,
										'label'        => 'Hijri Date',
										'name'         => 'hijri_date',
										'type'         => 'text',
										'instructions' => 'Auto-calculated from date with adjustment',
										'readonly'     => 1,
										'wrapper'      => array( 'width' => '10' ),
									),
									array(
										'key'            => 'field_fajr_start_' . $month_num,
										'label'          => 'Fajr Start',
										'name'           => 'fajr_start',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_fajr_jamaat_' . $month_num,
										'label'          => 'Fajr Jamaat',
										'name'           => 'fajr_jamaat',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_sunrise_' . $month_num,
										'label'          => 'Sunrise',
										'name'           => 'sunrise',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_zuhr_start_' . $month_num,
										'label'          => 'Zuhr Start',
										'name'           => 'zuhr_start',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_zuhr_jamaat_' . $month_num,
										'label'          => 'Zuhr Jamaat',
										'name'           => 'zuhr_jamaat',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_asr_start_' . $month_num,
										'label'          => 'Asr Start',
										'name'           => 'asr_start',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_asr_jamaat_' . $month_num,
										'label'          => 'Asr Jamaat',
										'name'           => 'asr_jamaat',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_maghrib_start_' . $month_num,
										'label'          => 'Maghrib Start',
										'name'           => 'maghrib_start',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_maghrib_jamaat_' . $month_num,
										'label'          => 'Maghrib Jamaat',
										'name'           => 'maghrib_jamaat',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_isha_start_' . $month_num,
										'label'          => 'Isha Start',
										'name'           => 'isha_start',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_isha_jamaat_' . $month_num,
										'label'          => 'Isha Jamaat',
										'name'           => 'isha_jamaat',
										'type'           => 'time_picker',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_jummah_1_' . $month_num,
										'label'          => 'Jummah 1',
										'name'           => 'jummah_1',
										'type'           => 'time_picker',
										'instructions'   => 'First Jummah prayer time (Fridays only)',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
									array(
										'key'            => 'field_jummah_2_' . $month_num,
										'label'          => 'Jummah 2',
										'name'           => 'jummah_2',
										'type'           => 'time_picker',
										'instructions'   => 'Second Jummah prayer time (Fridays only)',
										'display_format' => 'H:i',
										'return_format'  => 'H:i',
										'wrapper'        => array( 'width' => '5.8' ),
									),
								),
							),
						),
						'location'   => array(
							array(
								array(
									'param'    => 'options_page',
									'operator' => '==',
									'value'    => 'mosque-timetables',
								),
							),
						),
						'menu_order' => $month_num,
						'style'      => 'seamless',
					)
				);
			}
		}

		/**
		 * Register Appearance Settings ACF fields
		 */
		private function register_appearance_settings_fields() {
			acf_add_local_field_group(
				array(
					'key'        => 'group_appearance_settings',
					'title'      => 'Appearance & PWA Settings',
					'fields'     => array(
						array(
							'key'           => 'field_mt_today_color',
							'label'         => 'Today Row Color',
							'name'          => 'mt_today_color',
							'type'          => 'color_picker',
							'instructions'  => 'Background color for today\'s row in the timetable',
							'default_value' => '#FFF9C4',
							'wrapper'       => array( 'width' => '25' ),
						),
						array(
							'key'           => 'field_mt_friday_color',
							'label'         => 'Friday Row Color',
							'name'          => 'mt_friday_color',
							'type'          => 'color_picker',
							'instructions'  => 'Background color for Friday rows in the timetable',
							'default_value' => '#E8F5E8',
							'wrapper'       => array( 'width' => '25' ),
						),
						array(
							'key'           => 'field_mt_row_alt_bg',
							'label'         => 'Alternate Row Color',
							'name'          => 'mt_row_alt_bg',
							'type'          => 'color_picker',
							'instructions'  => 'Background color for alternate rows',
							'default_value' => '#F8F9FA',
							'wrapper'       => array( 'width' => '25' ),
						),
						array(
							'key'           => 'field_mt_next_prayer_bg',
							'label'         => 'Next Prayer Color',
							'name'          => 'mt_next_prayer_bg',
							'type'          => 'color_picker',
							'instructions'  => 'Background color for next prayer highlight',
							'default_value' => '#E3F2FD',
							'wrapper'       => array( 'width' => '25' ),
						),
						array(
							'key'           => 'field_mt_btn_bg',
							'label'         => 'Button Color',
							'name'          => 'mt_btn_bg',
							'type'          => 'color_picker',
							'instructions'  => 'Background color for buttons',
							'default_value' => '#1976D2',
							'wrapper'       => array( 'width' => '33.33' ),
						),
						array(
							'key'           => 'field_mt_table_text_color',
							'label'         => 'Table Text Color',
							'name'          => 'mt_table_text_color',
							'type'          => 'color_picker',
							'instructions'  => 'Text color for table content',
							'default_value' => '#333333',
							'wrapper'       => array( 'width' => '33.33' ),
						),
						array(
							'key'           => 'field_mt_header_text_color',
							'label'         => 'Header Text Color',
							'name'          => 'mt_header_text_color',
							'type'          => 'color_picker',
							'instructions'  => 'Text color for table headers',
							'default_value' => '#FFFFFF',
							'wrapper'       => array( 'width' => '33.33' ),
						),
						array(
							'key'           => 'field_enable_pwa',
							'label'         => 'Enable PWA Features',
							'name'          => 'enable_pwa',
							'type'          => 'true_false',
							'instructions'  => 'Enable Progressive Web App features including offline access and home screen installation',
							'default_value' => 1,
							'wrapper'       => array( 'width' => '50' ),
						),
						array(
							'key'           => 'field_enable_countdown',
							'label'         => 'Enable Prayer Countdown',
							'name'          => 'enable_countdown',
							'type'          => 'true_false',
							'instructions'  => 'Show countdown timer to next prayer',
							'default_value' => 1,
							'wrapper'       => array( 'width' => '50' ),
						),
						array(
							'key'           => 'field_notification_text',
							'label'         => 'Notification Text Template',
							'name'          => 'notification_text',
							'type'          => 'text',
							'instructions'  => 'Template for prayer notifications. Use {prayer} placeholder for prayer name.',
							'default_value' => 'Time for {prayer} prayer!',
							'wrapper'       => array( 'width' => '100' ),
						),
						array(
							'key'     => 'field_widget_size_header',
							'label'   => 'Widget Size & Color Customization',
							'name'    => 'widget_size_header',
							'type'    => 'message',
							'message' => 'Customize the appearance and size of prayer widgets for different displays',
							'wrapper' => array( 'width' => '100' ),
						),
						array(
							'key'           => 'field_widget_width',
							'label'         => 'Widget Width',
							'name'          => 'widget_width',
							'type'          => 'number',
							'instructions'  => 'Default width for prayer widgets (in pixels)',
							'default_value' => 320,
							'min'           => 200,
							'max'           => 800,
							'wrapper'       => array( 'width' => '25' ),
						),
						array(
							'key'           => 'field_widget_height',
							'label'         => 'Widget Height',
							'name'          => 'widget_height',
							'type'          => 'number',
							'instructions'  => 'Default height for prayer widgets (in pixels)',
							'default_value' => 180,
							'min'           => 120,
							'max'           => 400,
							'wrapper'       => array( 'width' => '25' ),
						),
						array(
							'key'           => 'field_widget_bg_color',
							'label'         => 'Widget Background',
							'name'          => 'widget_bg_color',
							'type'          => 'color_picker',
							'instructions'  => 'Background color for prayer widgets',
							'default_value' => '#FFFFFF',
							'wrapper'       => array( 'width' => '25' ),
						),
						array(
							'key'           => 'field_widget_text_color',
							'label'         => 'Widget Text Color',
							'name'          => 'widget_text_color',
							'type'          => 'color_picker',
							'instructions'  => 'Text color for prayer widgets',
							'default_value' => '#333333',
							'wrapper'       => array( 'width' => '25' ),
						),
						array(
							'key'           => 'field_widget_border_radius',
							'label'         => 'Widget Border Radius',
							'name'          => 'widget_border_radius',
							'type'          => 'number',
							'instructions'  => 'Border radius for rounded corners (in pixels)',
							'default_value' => 8,
							'min'           => 0,
							'max'           => 50,
							'wrapper'       => array( 'width' => '33.33' ),
						),
						array(
							'key'           => 'field_widget_shadow',
							'label'         => 'Widget Shadow',
							'name'          => 'widget_shadow',
							'type'          => 'true_false',
							'instructions'  => 'Add shadow effect to widgets',
							'default_value' => 1,
							'wrapper'       => array( 'width' => '33.33' ),
						),
						array(
							'key'           => 'field_widget_responsive',
							'label'         => 'Responsive Widgets',
							'name'          => 'widget_responsive',
							'type'          => 'true_false',
							'instructions'  => 'Make widgets responsive to screen size',
							'default_value' => 1,
							'wrapper'       => array( 'width' => '33.33' ),
						),
					),
					'location'   => array(
						array(
							array(
								'param'    => 'options_page',
								'operator' => '==',
								'value'    => 'mosque-appearance',
							),
						),
						array(
							array(
								'param'    => 'page_template',
								'operator' => '==',
								'value'    => 'mosque-appearance',
							),
						),
					),
					'menu_order' => 2,
				)
			);
		}

		/**
		 * Auto-populate monthly structure based on default year
		 */
		private function auto_populate_monthly_structure() {
			$default_year     = get_field( 'default_year', 'option' );
			$available_months = get_field( 'available_months', 'option' );

			if ( ! $default_year || ! $available_months ) {
				return;
			}

			foreach ( $available_months as $month_num ) {
				$this->populate_month_dates( $default_year, intval( $month_num ) );
			}
		}

		public function is_header_row( $row ): bool {
			if ( ! is_array( $row ) ) {
				return false;
			}
			$joined = strtolower( trim( implode( ',', array_map( 'strval', $row ) ) ) );
			foreach ( array( 'fajr', 'sunrise', 'zuhr', 'asr', 'maghrib', 'isha', 'jummah', 'jamaat', 'start', 'date', 'day' ) as $k ) {
				if ( strpos( $joined, $k ) !== false ) {
					return true;
				}
			}
			return false;
		}

		public function looks_like_date( $s ): bool {
			$s = is_string( $s ) ? trim( $s ) : '';
			if ( '' === $s ) {
				return false;
			}
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s ) ) {
				return true;
			}
			if ( preg_match( '/^\d{2}[\/-]\d{2}[\/-]\d{4}$/', $s ) ) {
				return true;
			}
			return strtotime( $s ) !== false;
		}

		/**
		 * Populate dates for a specific month
		 */
		private function populate_month_dates( $year, $month ) {
			$field_name    = 'daily_prayers_' . $month;
			$existing_data = get_field( $field_name, 'option' );

			// Only populate if no existing data
			if ( ! empty( $existing_data ) ) {
				return;
			}

			$days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );
			$prayer_data   = array();

			for ( $day = 1; $day <= $days_in_month; $day++ ) {
				$date       = sprintf( '%04d-%02d-%02d', $year, $month, $day );
				$date_obj   = new DateTime( $date );
				$day_name   = $date_obj->format( 'l' );
				$hijri_date = $this->calculate_hijri_date( $date );

				$prayer_data[] = array(
					'day_number'     => $day,
					'date_full'      => $date,
					'day_name'       => $day_name,
					'hijri_date'     => $hijri_date,
					'fajr_start'     => '',
					'fajr_jamaat'    => '',
					'sunrise'        => '',
					'zuhr_start'     => '',
					'zuhr_jamaat'    => '',
					'asr_start'      => '',
					'asr_jamaat'     => '',
					'maghrib_start'  => '',
					'maghrib_jamaat' => '',
					'isha_start'     => '',
					'isha_jamaat'    => '',
					'jummah_1'       => '',
					'jummah_2'       => '',
				);
			}

			update_field( $field_name, $prayer_data, 'option' );
		}

		/**
		 * Validate and normalize a date for imports.
		 * Returns 'YYYY-MM-DD' string on success, or false on failure.
		 */
		private function validate_import_date( $date_string, int $day_number, int $month, ?int $year = null ) {
			// Normalise inputs
			$day_number = (int) $day_number;
			$month      = max( 1, min( 12, (int) $month ) );
			$year       = $year ? (int) $year : (int) wp_date( 'Y' );

			if ( $day_number < 1 || $day_number > 31 ) {
				return false;
			}

			$date_string = is_string( $date_string ) ? trim( $date_string ) : '';

			// If we were given a plausible date string, try to parse and normalise it
			if ( '' !== $date_string && $this->looks_like_date( $date_string ) ) {
				$ts = strtotime( $date_string );
				if ( false !== $ts ) {
					return gmdate( 'Y-m-d', $ts );
				}
			}

			// Fallback: compose from Y, M, D and validate
			$candidate = sprintf( '%04d-%02d-%02d', $year, $month, $day_number );
			$dt        = DateTime::createFromFormat( 'Y-m-d', $candidate );

			return ( $dt && $dt->format( 'Y-m-d' ) === $candidate ) ? $candidate : false;
		}

		/**
		 * Calculate Hijri date from Gregorian date
		 */
		public function calculate_hijri_date( $gregorian_date, $adjustment = 0 ) {
			$timestamp = strtotime( $gregorian_date );

			// Apply adjustment
			if ( 0 !== $adjustment ) {
				$timestamp += ( $adjustment * 24 * 60 * 60 ); // Add/subtract days
			}

			$gregorian_year  = gmdate( 'Y', $timestamp );
			$gregorian_month = gmdate( 'n', $timestamp );
			$gregorian_day   = gmdate( 'j', $timestamp );

			// More accurate Hijri conversion using Julian Day Number algorithm
			$hijri_date = $this->gregorian_to_hijri_accurate( $gregorian_year, $gregorian_month, $gregorian_day );

			$hijri_months = array(
				1  => 'Muharram',
				2  => 'Safar',
				3  => 'Rabi\' al-awwal',
				4  => 'Rabi\' al-thani',
				5  => 'Jumada al-awwal',
				6  => 'Jumada al-thani',
				7  => 'Rajab',
				8  => 'Sha\'ban',
				9  => 'Ramadan',
				10 => 'Shawwal',
				11 => 'Dhu al-Qi\'dah',
				12 => 'Dhu al-Hijjah',
			);

			return sprintf(
				'%d %s %d AH',
				$hijri_date['day'],
				$hijri_months[ $hijri_date['month'] ],
				$hijri_date['year']
			);
		}

		/**
		 * More accurate Gregorian to Hijri conversion
		 */
		private function gregorian_to_hijri_accurate( $g_year, $g_month, $g_day ) {
			// Calculate Julian Day Number
			if ( $g_month <= 2 ) {
				$g_year  -= 1;
				$g_month += 12;
			}

			$a = floor( $g_year / 100 );
			$b = 2 - $a + floor( $a / 4 );

			$jd = floor( 365.25 * ( $g_year + 4716 ) ) + floor( 30.6001 * ( $g_month + 1 ) ) + $g_day + $b - 1524;

			// Convert Julian Day to Hijri
			$l = $jd - 1948439; // Difference between Julian and Hijri epochs
			$n = floor( ( $l - 1 ) / 10631 );
			$l = $l - 10631 * $n + 354;

			$j = floor( ( 10985 - $l ) / 5316 ) * floor( ( 50 * $l ) / 17719 ) +
				floor( $l / 5670 ) * floor( ( 43 * $l ) / 15238 );

			$l = $l - floor( ( 30 - $j ) / 15 ) * floor( ( 17719 * $j ) / 50 ) -
				floor( $j / 16 ) * floor( ( 15238 * $j ) / 43 ) + 29;

			$hijri_month = floor( ( 24 * $l ) / 709 );
			$hijri_day   = $l - floor( ( 709 * $hijri_month ) / 24 );
			$hijri_year  = 30 * $n + $j - 30;

			// Adjust for proper ranges
			if ( $hijri_day <= 0 ) {
				--$hijri_month;
				if ( $hijri_month <= 0 ) {
					$hijri_month = 12;
					--$hijri_year;
				}
				$hijri_day = $this->get_hijri_month_days( $hijri_month, $hijri_year ) + $hijri_day;
			}

			if ( $hijri_month <= 0 ) {
				$hijri_month = 12;
				--$hijri_year;
			} elseif ( $hijri_month > 12 ) {
				$hijri_month = 1;
				++$hijri_year;
			}

			return array(
				'year'  => $hijri_year,
				'month' => $hijri_month,
				'day'   => max( 1, $hijri_day ),
			);
		}

		/**
		 * Get number of days in a Hijri month
		 */
		private function get_hijri_month_days( $month, $year ) {
			// Hijri months alternate between 30 and 29 days
			// with adjustments for leap years
			$days = array( 30, 29, 30, 29, 30, 29, 30, 29, 30, 29, 30, 29 );

			// In leap years, the last month has 30 days instead of 29
			if ( $this->is_hijri_leap_year( $year ) && 12 === $month ) {
				return 30;
			}

			return $days[ $month - 1 ];
		}

		/**
		 * Check if a Hijri year is a leap year
		 */
		private function is_hijri_leap_year( $year ) {
			// Hijri leap year calculation: 11 leap years in every 30-year cycle
			$cycle_position = $year % 30;
			$leap_years     = array( 2, 5, 7, 10, 13, 16, 18, 21, 24, 26, 29 );
			return in_array( $cycle_position, $leap_years );
		}

		/**
		 * Register shortcodes
		 */
		public function register_shortcodes() {
			add_shortcode( 'mosque_timetable', array( $this, 'shortcode_mosque_timetable' ) );
			add_shortcode( 'todays_prayers', array( $this, 'shortcode_todays_prayers' ) );
			add_shortcode( 'prayer_countdown', array( $this, 'shortcode_prayer_countdown' ) );
		}

		/**
		 * Add admin menu
		 */
		public function add_admin_menu() {
			// Create main menu page (not using ACF to avoid conflicts)
			add_menu_page(
				mt_apply_terminology( 'Mosque Timetable' ), // Page title
				mt_apply_terminology( 'Mosque Timetable' ), // Menu title
				'edit_posts',       // Capability
				'mosque-main',      // Menu slug
				array( $this, 'render_main_admin_page' ), // Function
				'dashicons-clock',  // Icon
				30                  // Position
			);

			// Submenu: Mosque Settings
			add_submenu_page(
				'mosque-main',
				mt_apply_terminology( 'Mosque Configuration' ),
				mt_apply_terminology( 'Configuration' ),
				'edit_posts',
				'mosque-settings',
				array( $this, 'render_settings_page' )
			);

			// Submenu: Timetables (main functionality)
			add_submenu_page(
				'mosque-main',
				'Prayer Timetables',
				'Timetables',
				'edit_posts',
				'mosque-timetables',
				array( $this, 'render_timetables_admin_page' )
			);

			// Submenu: Appearance
			add_submenu_page(
				'mosque-main',
				'Appearance & PWA Settings',
				'Appearance',
				'edit_posts',
				'mosque-appearance',
				array( $this, 'render_appearance_page' )
			);

			// Debug submenu (temporary)
			add_submenu_page(
				'mosque-main',
				'Debug Timetables',
				'🔧 Debug',
				'edit_posts',
				'mosque-debug',
				array( $this, 'render_debug_page' )
			);

			// Submenu: Import/Export
			add_submenu_page(
				'mosque-main',
				'Import/Export Tools',
				'Import/Export',
				'edit_posts',
				'mosque-import-export',
				array( $this, 'render_import_export_page' )
			);

			// ACF fields are registered separately and will work with our pages
		}

		/**
		 * Setup ACF fields on our custom admin pages
		 */
		public function setup_acf_on_custom_pages() {
			$screen = get_current_screen();

			// We don't need to do anything special here anymore
			// ACF fields are loaded directly in the render functions
		}

		/**
		 * Render main admin page (dashboard/overview)
		 */
		public function render_main_admin_page() {
			$mosque_name      = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
			$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
			$available_months = get_field( 'available_months', 'option' ) ?: array();

			?>
			<div class="wrap">
				<div class="mosque-page-header">
					<img src="<?php echo esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/icon-192.png' ); ?>" alt="Mosque Logo" class="mosque-logo">
					<h1>Mosque Timetable System</h1>
				</div>

				<div class="mosque-dashboard">
					<div class="mosque-welcome-panel">
						<h2>Welcome to <?php echo esc_html( $mosque_name ); ?></h2>
						<p>Manage your prayer timetables, customize appearance, and handle imports/exports from this central dashboard.</p>
					</div>

					<div class="mosque-quick-actions">
						<div class="action-card">
							<h3>⚙️ Quick Setup</h3>
							<p>Configure your mosque details and select which months you need.</p>
							<a href="admin.php?page=mosque-settings" class="button button-primary">Configure Settings</a>
						</div>

						<div class="action-card">
							<h3>📅 Manage Timetables</h3>
							<p>Add and edit prayer times for <?php echo esc_html( $default_year ); ?>. <?php echo count( $available_months ); ?> months configured.</p>
							<a href="admin.php?page=mosque-timetables" class="button button-primary">Edit Timetables</a>
						</div>

						<div class="action-card">
							<h3>🎨 Customize Look</h3>
							<p>Change colors, enable PWA features, and customize the appearance.</p>
							<a href="admin.php?page=mosque-appearance" class="button">Customize Appearance</a>
						</div>

						<div class="action-card">
							<h3>📥📤 Import/Export</h3>
							<p>Import CSV data or export calendars for sharing.</p>
							<a href="admin.php?page=mosque-import-export" class="button">Import/Export Tools</a>
						</div>
					</div>

					<div class="mosque-status-panel">
						<h3>System Status</h3>
						<ul>
							<li><strong>Mosque:</strong> <?php echo esc_html( $mosque_name ); ?></li>
							<li><strong>Current Year:</strong> <?php echo esc_html( $default_year ); ?></li>
							<li><strong>Active Months:</strong> <?php echo esc_html( (string) count( $available_months ) ); ?>/12</li>
							<li><strong>ACF Pro:</strong> <?php echo function_exists( 'acf' ) ? '✅ Installed' : '❌ Missing'; ?></li>
							<li><strong>PWA:</strong> <?php echo get_field( 'enable_pwa', 'option' ) ? '✅ Enabled' : '❌ Disabled'; ?></li>
						</ul>
					</div>

					<div class="mosque-shortcodes-panel">
						<h3>Available Shortcodes</h3>
						<p>Use these shortcodes in your posts and pages:</p>
						<ul>
							<li><code>[mosque_timetable]</code> - Full monthly prayer table</li>
							<li><code>[todays_prayers]</code> - Today's prayer times widget</li>
							<li><code>[prayer_countdown]</code> - Countdown to next prayer</li>
						</ul>
					</div>
				</div>
			</div>

			<style>
				.mosque-page-header {
					display: flex;
					align-items: center;
					gap: 15px;
					margin-bottom: 20px;
				}

				.mosque-logo {
					width: 48px;
					height: 48px;
					border-radius: 8px;
					box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
				}

				.mosque-page-header h1 {
					margin: 0;
					color: #1976D2;
				}

				.mosque-dashboard {
					display: grid;
					grid-template-columns: 2fr 1fr;
					gap: 20px;
					margin-top: 20px;
				}

				.mosque-welcome-panel {
					grid-column: span 2;
					background: linear-gradient(135deg, #1976D2, #1565C0);
					color: white;
					padding: 30px;
					border-radius: 8px;
					margin-bottom: 20px;
				}

				.mosque-welcome-panel h2 {
					margin-top: 0;
					font-size: 28px;
				}

				.mosque-quick-actions {
					display: grid;
					grid-template-columns: 1fr 1fr;
					gap: 15px;
				}

				.action-card {
					background: white;
					padding: 20px;
					border: 1px solid #ddd;
					border-radius: 6px;
					box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
				}

				.action-card h3 {
					margin-top: 0;
					color: #1976D2;
				}

				.mosque-status-panel,
				.mosque-shortcodes-panel {
					background: white;
					padding: 20px;
					border: 1px solid #ddd;
					border-radius: 6px;
					box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
					margin-bottom: 15px;
				}

				.mosque-status-panel ul,
				.mosque-shortcodes-panel ul {
					list-style: none;
					padding: 0;
				}

				.mosque-status-panel li {
					padding: 5px 0;
					border-bottom: 1px solid #eee;
				}

				.mosque-shortcodes-panel code {
					background: #f1f1f1;
					padding: 2px 6px;
					border-radius: 3px;
					font-family: monospace;
				}

				@media (max-width: 1200px) {
					.mosque-dashboard {
						grid-template-columns: 1fr;
					}

					.mosque-welcome-panel {
						grid-column: span 1;
					}

					.mosque-quick-actions {
						grid-template-columns: 1fr;
					}
				}

				.mosque-support-footer {
					margin-top: 40px;
					padding: 20px;
					background: linear-gradient(135deg, #f8f9fa, #e9ecef);
					border: 1px solid #dee2e6;
					border-radius: 8px;
					text-align: center;
					color: #6c757d;
				}

				.mosque-support-footer h3 {
					color: #1976D2;
					margin-top: 0;
				}

				.mosque-support-footer a {
					color: #1976D2;
					text-decoration: none;
				}

				.mosque-support-footer a:hover {
					text-decoration: underline;
				}
			</style>

			<div class="mosque-support-footer">
				<h3>🤲 Supporting the Muslim Community</h3>
				<p>We're dedicated to empowering mosques and Islamic centres worldwide with professional digital solutions. Our team provides comprehensive support for website development, digital marketing, and technology solutions to help strengthen your community's online presence.</p>
				<p>Need assistance with your mosque's digital needs? We're here to help with everything from prayer time systems to complete website solutions.</p>
				<p><strong>Contact us:</strong> <a href="mailto:ibraheem@mosquewebdesign.com">ibraheem@mosquewebdesign.com</a> | <a href="https://mosquewebdesign.com" target="_blank">mosquewebdesign.com</a></p>
			</div>

			<?php
		}

		/**
		 * Render settings page with ACF fields
		 */
		public function render_settings_page() {
			// Handle form submission first
			if ( isset( $_POST['submit'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mosque_settings_nonce' ) ) {
				$this->save_mosque_settings();
				$message = 'Configuration updated successfully!';
			}

			// Check for ACF form save success
			if ( isset( $_GET['updated'] ) && sanitize_text_field( wp_unslash( $_GET['updated'] ) ) === 'true' ) {
				$message = 'Configuration updated successfully!';
			}

			?>
			<div class="wrap">
				<div class="mosque-page-header">
					<img src="<?php echo esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/icon-192.png' ); ?>" alt="Mosque Logo" class="mosque-logo">
					<h1>Mosque Configuration</h1>
				</div>
				<p>Configure your mosque details and system settings.</p>

				<?php if ( isset( $message ) ) : ?>
					<div class="notice notice-success">
						<p><?php echo esc_html( $message ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( is_acf_pro_available() ) : ?>
					<?php acf_form_head(); ?>
					<?php
					acf_form(
						array(
							'post_id'      => 'options',
							'field_groups' => array( 'group_mosque_configuration' ),
							'submit_value' => 'Save Configuration',
						)
					);
					?>
				<?php else : ?>
					<div class="notice notice-info">
						<p>Admin view enhanced for ACF Pro users.</p>
					</div>

					<form method="post" action="">
						<?php wp_nonce_field( 'mosque_timetable_action', 'mosque_timetable_nonce' ); ?>
						<?php $this->render_fallback_settings_form(); ?>
					</form>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Save mosque settings (fallback method)
		 */
		private function save_mosque_settings() {
			// Verify nonce for state-changing operations
			check_admin_referer( 'mosque_timetable_action', 'mosque_timetable_nonce' );

			if ( isset( $_POST['mosque_name'] ) ) {
				update_option( 'mosque_name', sanitize_text_field( wp_unslash( $_POST['mosque_name'] ) ) );
			}
			if ( isset( $_POST['mosque_address'] ) ) {
				update_option( 'mosque_address', sanitize_textarea_field( wp_unslash( $_POST['mosque_address'] ) ) );
			}
			if ( isset( $_POST['default_year'] ) ) {
				$year = intval( wp_unslash( $_POST['default_year'] ) );
				if ( $year >= 2020 && $year <= 2035 ) {
					update_option( 'default_year', $year );
				}
			}
			if ( isset( $_POST['available_months'] ) && is_array( $_POST['available_months'] ) ) {
				$months = array_map( 'intval', wp_unslash( $_POST['available_months'] ) );
				update_option( 'available_months', $months );
			}
			if ( isset( $_POST['custom_subscribe_url'] ) ) {
				$custom_url = sanitize_url( wp_unslash( $_POST['custom_subscribe_url'] ) );
				update_option( 'custom_subscribe_url', $custom_url );
			}
			if ( isset( $_POST['terminology_overrides'] ) && is_array( $_POST['terminology_overrides'] ) ) {
				$terminology_overrides = array();
				$clean_overrides       = wp_unslash( $_POST['terminology_overrides'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array elements sanitized individually in loop below
				foreach ( $clean_overrides as $override ) {
					if ( ! empty( $override['from'] ) && ! empty( $override['to'] ) ) {
						$terminology_overrides[] = array(
							'from'    => sanitize_text_field( $override['from'] ), // Already unslashed above
							'to'      => sanitize_text_field( $override['to'] ), // Already unslashed above
							'enabled' => isset( $override['enabled'] ) ? 1 : 0,
						);
					}
				}
				update_option( 'terminology_overrides', $terminology_overrides );
			}
			// Push notification settings
			if ( isset( $_POST['vapid_public_key'] ) ) {
				update_option( 'vapid_public_key', sanitize_text_field( wp_unslash( $_POST['vapid_public_key'] ) ) );
			}
			if ( isset( $_POST['vapid_private_key'] ) ) {
				update_option( 'vapid_private_key', sanitize_text_field( wp_unslash( $_POST['vapid_private_key'] ) ) );
			}
			if ( isset( $_POST['default_reminder_offsets'] ) && is_array( $_POST['default_reminder_offsets'] ) ) {
				$offsets = array_map( 'intval', wp_unslash( $_POST['default_reminder_offsets'] ) );
				update_option( 'default_reminder_offsets', $offsets );
			}
			update_option( 'sunrise_warning_enabled', isset( $_POST['sunrise_warning_enabled'] ) ? 1 : 0 );
			if ( isset( $_POST['sunrise_warning_offset'] ) ) {
				$offset = intval( wp_unslash( $_POST['sunrise_warning_offset'] ) );
				update_option( 'sunrise_warning_offset', $offset );
			}
			if ( isset( $_POST['privacy_note_text'] ) ) {
				update_option( 'privacy_note_text', sanitize_textarea_field( wp_unslash( $_POST['privacy_note_text'] ) ) );
			}
		}

		/**
		 * Handle ACF form save redirects properly
		 */
		public function handle_acf_save_redirect( $post_id ) {
			// Only handle options page saves
			if ( 'options' !== $post_id ) {
				return;
			}

			// Verify nonce for state-changing operations
			// If ACF Pro is active, it handles nonce verification; otherwise we need to verify
			if ( function_exists( 'acf_verify_nonce' ) || class_exists( 'ACF' ) ) {
				// ACF Pro handles nonce verification internally
				$acf_screen = isset( $_POST['_acf_screen'] ) ? sanitize_text_field( wp_unslash( $_POST['_acf_screen'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF Pro handles nonce verification
			} else {
				// When using fallback stubs, we must verify nonce ourselves
				if ( ! isset( $_POST['mosque_timetable_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mosque_timetable_nonce'] ) ), 'mosque_timetable_action' ) ) {
					wp_die( esc_html__( 'Security check failed', 'mosque-timetable' ) );
				}
				$acf_screen = isset( $_POST['_acf_screen'] ) ? sanitize_text_field( wp_unslash( $_POST['_acf_screen'] ) ) : '';
			}
			if (
				! empty( $acf_screen ) &&
				( strpos( $acf_screen, 'mosque-settings' ) !== false ||
					strpos( $acf_screen, 'mosque-appearance' ) !== false )
			) {

				$redirect_url = '';
				if ( strpos( $acf_screen, 'mosque-settings' ) !== false ) {
					$redirect_url = add_query_arg( 'updated', 'true', admin_url( 'admin.php?page=mosque-settings' ) );
				} elseif ( strpos( $acf_screen, 'mosque-appearance' ) !== false ) {
					$redirect_url = add_query_arg( 'updated', 'true', admin_url( 'admin.php?page=mosque-appearance' ) );
				}

				if ( ! empty( $redirect_url ) ) {
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
		}

		/**
		 * Render appearance page with ACF fields
		 */
		public function render_appearance_page() {
			// Handle form submission first
			if ( isset( $_POST['submit'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mosque_appearance_nonce' ) ) {
				$this->save_appearance_settings();
				$message = 'Appearance settings updated successfully!';
			}

			// Check for ACF form save success
			if ( isset( $_GET['updated'] ) && sanitize_text_field( wp_unslash( $_GET['updated'] ) ) === 'true' ) {
				$message = 'Appearance settings updated successfully!';
			}

			?>
			<div class="wrap">
				<div class="mosque-page-header">
					<img src="<?php echo esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/icon-192.png' ); ?>" alt="Mosque Logo" class="mosque-logo">
					<h1>Appearance & PWA Settings</h1>
				</div>
				<p>Customize colors, fonts, and Progressive Web App features.</p>

				<?php if ( isset( $message ) ) : ?>
					<div class="notice notice-success">
						<p><?php echo esc_html( $message ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( is_acf_pro_available() ) : ?>
					<?php acf_form_head(); ?>
					<?php
					acf_form(
						array(
							'post_id'      => 'options',
							'field_groups' => array( 'group_appearance_settings' ),
							'submit_value' => 'Save Appearance Settings',
						)
					);
					?>
				<?php else : ?>
					<div class="notice notice-info">
						<p>Admin view enhanced for ACF Pro users.</p>
					</div>

					<form method="post" action="">
						<?php wp_nonce_field( 'mosque_timetable_action', 'mosque_timetable_nonce' ); ?>
						<?php $this->render_fallback_appearance_form(); ?>
					</form>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Save appearance settings (fallback method)
		 */
		private function save_appearance_settings() {
			// Verify nonce for state-changing operations
			check_admin_referer( 'mosque_timetable_action', 'mosque_timetable_nonce' );

			if ( isset( $_POST['mt_today_color'] ) ) {
				update_option( 'mt_today_color', sanitize_hex_color( wp_unslash( $_POST['mt_today_color'] ) ) );
			}
			if ( isset( $_POST['mt_friday_color'] ) ) {
				update_option( 'mt_friday_color', sanitize_hex_color( wp_unslash( $_POST['mt_friday_color'] ) ) );
			}
			if ( isset( $_POST['mt_row_alt_bg'] ) ) {
				update_option( 'mt_row_alt_bg', sanitize_hex_color( wp_unslash( $_POST['mt_row_alt_bg'] ) ) );
			}
			if ( isset( $_POST['mt_next_prayer_bg'] ) ) {
				update_option( 'mt_next_prayer_bg', sanitize_hex_color( wp_unslash( $_POST['mt_next_prayer_bg'] ) ) );
			}
			if ( isset( $_POST['mt_btn_bg'] ) ) {
				update_option( 'mt_btn_bg', sanitize_hex_color( wp_unslash( $_POST['mt_btn_bg'] ) ) );
			}
			if ( isset( $_POST['mt_table_text_color'] ) ) {
				update_option( 'mt_table_text_color', sanitize_hex_color( wp_unslash( $_POST['mt_table_text_color'] ) ) );
			}
			if ( isset( $_POST['mt_header_text_color'] ) ) {
				update_option( 'mt_header_text_color', sanitize_hex_color( wp_unslash( $_POST['mt_header_text_color'] ) ) );
			}
			update_option( 'enable_pwa', isset( $_POST['enable_pwa'] ) ? 1 : 0 );
			update_option( 'enable_countdown', isset( $_POST['enable_countdown'] ) ? 1 : 0 );
			if ( isset( $_POST['notification_text'] ) ) {
				update_option( 'notification_text', sanitize_text_field( wp_unslash( $_POST['notification_text'] ) ) );
			}
		}

		/**
		 * Render custom timetables admin page with tabs
		 */
		public function render_timetables_admin_page() {
			$available_months = get_field( 'available_months', 'option' ) ?: array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' );
			$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );

			$months = array(
				1  => 'January',
				2  => 'February',
				3  => 'March',
				4  => 'April',
				5  => 'May',
				6  => 'June',
				7  => 'July',
				8  => 'August',
				9  => 'September',
				10 => 'October',
				11 => 'November',
				12 => 'December',
			);

			?>
			<div class="wrap">
				<div class="mosque-page-header">
					<img src="<?php echo esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/icon-192.png' ); ?>" alt="Mosque Logo" class="mosque-logo">
					<h1>Prayer Timetables - <?php echo esc_html( $default_year ); ?></h1>
				</div>

				<div class="mosque-admin-header">
					<div class="year-archive-browser">
						<h3>📅 Year Archive Browser</h3>
						<div class="year-controls">
							<label for="year-selector">Select Year:</label>
							<select id="year-selector" class="year-selector">
								<?php
								$current_year = wp_date( 'Y' );
								for ( $y = 2020; $y <= $current_year + 5; $y++ ) :
									$selected = ( $y === $default_year ) ? 'selected' : '';
									?>
									<option value="<?php echo esc_attr( (string) $y ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( (string) $y ); ?></option>
								<?php endfor; ?>
							</select>
							<button type="button" class="button button-primary" id="load-year-data">Load Year</button>
							<button type="button" class="button button-secondary" id="create-new-year">+ New Year</button>
						</div>
						<div class="year-info">
							<small>Current: <?php echo esc_html( $default_year ); ?> | Available Years:
								<?php
								$available_years = array();
								for ( $y = 2020; $y <= $current_year + 5; $y++ ) {
									$has_data = false;
									foreach ( $available_months as $month ) {
										$field_name = 'daily_prayers_' . $month;
										$data       = get_field( $field_name, 'option' );
										if ( ! empty( $data ) ) {
											$has_data = true;
											break;
										}
									}
									if ( $has_data ) {
										$available_years[] = $y;
									}
								}
								echo esc_html( implode( ', ', $available_years ) );
								?>
							</small>
						</div>
					</div>

					<div class="import-tools">
						<h3>Import Tools</h3>
						<button type="button" class="button button-primary" id="csv-import-btn">📄 Import CSV</button>
						<button type="button" class="button" id="xlsx-import-btn">📊 Import XLSX</button>
						<button type="button" class="button" id="paste-import-btn">📋 Copy/Paste Data</button>
						<p class="description">
							💡 <strong>Need examples?</strong>
							<a href="admin.php?page=mosque-import-export">Download sample templates</a>
							to see the correct format for your data.
						</p>
					</div>

					<div class="bulk-actions">
						<button type="button" class="button" id="generate-all-dates">🗓 Generate All Dates</button>
						<button type="button" class="button button-secondary" id="save-all-months">💾 Save All Months</button>
					</div>
				</div>

				<div class="mosque-timetable-tabs">
					<nav class="nav-tab-wrapper">
						<?php
						foreach ( $available_months as $month_num ) :
							$month_name = $months[ intval( $month_num ) ];
							$is_first   = ( reset( $available_months ) === $month_num );
							?>
							<a href="#month-<?php echo esc_attr( $month_num ); ?>"
								class="nav-tab <?php echo esc_attr( $is_first ? 'nav-tab-active' : '' ); ?>"
								data-month="<?php echo esc_attr( $month_num ); ?>">
								<?php echo esc_html( $month_name ); ?>
							</a>
						<?php endforeach; ?>
					</nav>

					<?php
					foreach ( $available_months as $month_num ) :
						$is_first      = ( reset( $available_months ) === $month_num );
						$field_name    = 'daily_prayers_' . $month_num;
						$daily_prayers = get_field( $field_name, 'option' ) ?: array();
						?>
						<div id="month-<?php echo esc_attr( $month_num ); ?>"
							class="tab-content <?php echo esc_attr( $is_first ? 'active' : '' ); ?>"
							data-month="<?php echo esc_attr( $month_num ); ?>">

							<div class="month-header">
								<h2><?php echo esc_html( $months[ intval( $month_num ) ] . ' ' . $default_year ); ?></h2>
								<div class="month-actions">
									<button type="button" class="button save-month-btn" data-month="<?php echo esc_attr( $month_num ); ?>">💾 Save <?php echo esc_html( $months[ intval( $month_num ) ] ); ?></button>
									<button type="button" class="button populate-month-btn" data-month="<?php echo esc_attr( $month_num ); ?>">🗓 Generate Dates</button>
								</div>
							</div>

							<div class="hijri-adjustment">
								<label for="hijri-adj-<?php echo esc_attr( $month_num ); ?>">Hijri Date Adjustment:</label>
								<select id="hijri-adj-<?php echo esc_attr( $month_num ); ?>" data-month="<?php echo esc_attr( $month_num ); ?>">
									<option value="-1">-1 day</option>
									<option value="0" selected>Calculated (default)</option>
									<option value="1">+1 day</option>
								</select>
								<button type="button" class="button recalc-hijri-btn" data-month="<?php echo esc_attr( $month_num ); ?>">🔄 Recalculate Hijri</button>
							</div>

							<?php
							// PDF uploader section
							// Decide which year to use (option can be numeric or "current")
							$opt          = get_option( 'default_year', 'current' );
							$current_year = ( ! is_numeric( $opt ) || 'current' === $opt )
								? (int) wp_date( 'Y' )
								: (int) $opt;

							// Now fetch PDF for this month/year
							$pdf_url = mt_get_pdf_for_month( (int) $month_num, (int) $current_year );
							?>

							<div class="mt-pdf-upload-section">
								<h4>📄 Print-ready PDF</h4>
								<?php if ( $pdf_url ) : ?>
									<div class="mt-pdf-current">
										<span class="mt-pdf-info">✅ PDF uploaded</span>
										<a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" class="button button-secondary">📖 View PDF</a>
										<button type="button" class="button button-link-delete mt-remove-pdf-btn" data-month="<?php echo esc_attr( $month_num ); ?>">Remove</button>
									</div>
								<?php else : ?>
									<div class="mt-pdf-upload">
										<form enctype="multipart/form-data" class="mt-pdf-upload-form" data-month="<?php echo esc_attr( $month_num ); ?>">
											<input type="file" name="pdf_file" accept=".pdf" class="mt-pdf-file-input" id="pdf-file-<?php echo esc_attr( $month_num ); ?>">
											<label for="pdf-file-<?php echo esc_attr( $month_num ); ?>" class="button button-secondary">📁 Choose PDF File</label>
											<button type="button" class="button button-primary mt-upload-pdf-btn" data-month="<?php echo esc_attr( $month_num ); ?>">📤 Upload</button>
										</form>
										<p class="description">Upload a print-ready PDF for <?php echo esc_html( $months[ intval( $month_num ) ] ); ?>. Visitors can download this instead of using browser print.</p>
									</div>
								<?php endif; ?>
							</div>

							<?php
							// Make sure we have a year to read from (use your current UI/year variable if different)
							$current_year = isset( $current_year ) ? (int) $current_year : (int) get_option( 'default_year', (int) wp_date( 'Y' ) );

							// Ensure this appears AFTER you've set $current_year
							$pdf_url = mt_get_pdf_for_month( (int) $month_num, (int) $current_year );

							// Always read via the shim (works with and without ACF)
							$daily_prayers = mt_get_month_rows( (int) $month_num, $current_year );

							// Extra hardening in case something legacy wrote a string
							if ( ! is_array( $daily_prayers ) ) {
								$maybe         = maybe_unserialize( $daily_prayers );
								$daily_prayers = is_array( $maybe ) ? $maybe : array();
							}
							?>

							<div class="prayer-times-table-container">
								<?php if ( ! empty( $daily_prayers ) ) : ?>
									<table class="wp-list-table widefat fixed striped">
										<thead>
											<tr>
												<th>Day</th>
												<th>Date</th>
												<th>Day Name</th>
												<th>Hijri Date</th>
												<th>Fajr Start</th>
												<th>Fajr Jamaat</th>
												<th>Sunrise</th>
												<th>Zuhr Start</th>
												<th>Zuhr Jamaat</th>
												<th>Asr Start</th>
												<th>Asr Jamaat</th>
												<th>Maghrib Start</th>
												<th>Maghrib Jamaat</th>
												<th>Isha Start</th>
												<th>Isha Jamaat</th>
												<th>Jummah 1</th>
												<th>Jummah 2</th>
											</tr>
										</thead>
										<tbody>
											<?php
											// $daily_prayers is already coerced to array above
											foreach ( $daily_prayers as $index => $day ) :
												if ( ! is_array( $day ) ) {
													continue;
												}

												// Be defensive with dates (avoid warnings on empty/invalid strings)
												$dateStr   = isset( $day['date_full'] ) ? (string) $day['date_full'] : '';
												$date_obj  = $dateStr ? DateTime::createFromFormat( 'Y-m-d', $dateStr ) : false;
												$is_friday = ( $date_obj && $date_obj->format( 'N' ) === 5 );
												?>
												<tr class="<?php echo esc_attr( $is_friday ? 'friday-row' : '' ); ?>">
													<td><input type="number" value="<?php echo esc_attr( $day['day_number'] ?? '' ); ?>" readonly /></td>
													<td><input type="date" value="<?php echo esc_attr( $day['date_full'] ?? '' ); ?>" readonly /></td>
													<td><input type="text" value="<?php echo esc_attr( $day['day_name'] ?? '' ); ?>" readonly /></td>
													<td><input type="text" value="<?php echo esc_attr( $day['hijri_date'] ?? '' ); ?>" readonly class="hijri-field" /></td>

													<td><input type="time" value="<?php echo esc_attr( $day['fajr_start'] ?? '' ); ?>" name="fajr_start[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['fajr_jamaat'] ?? '' ); ?>" name="fajr_jamaat[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['sunrise'] ?? '' ); ?>" name="sunrise[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['zuhr_start'] ?? '' ); ?>" name="zuhr_start[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['zuhr_jamaat'] ?? '' ); ?>" name="zuhr_jamaat[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['asr_start'] ?? '' ); ?>" name="asr_start[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['asr_jamaat'] ?? '' ); ?>" name="asr_jamaat[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['maghrib_start'] ?? '' ); ?>" name="maghrib_start[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['maghrib_jamaat'] ?? '' ); ?>" name="maghrib_jamaat[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['isha_start'] ?? '' ); ?>" name="isha_start[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['isha_jamaat'] ?? '' ); ?>" name="isha_jamaat[]" /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['jummah_1'] ?? '' ); ?>" name="jummah_1[]" <?php echo $is_friday ? '' : esc_attr( 'style="background:#f0f0f0;"' ); ?> /></td>
													<td><input type="time" value="<?php echo esc_attr( $day['jummah_2'] ?? '' ); ?>" name="jummah_2[]" <?php echo $is_friday ? '' : esc_attr( 'style="background:#f0f0f0;"' ); ?> /></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								<?php else : ?>
									<div class="no-data-message">
										<p><strong>No prayer times data for <?php echo esc_html( $months[ intval( $month_num ) ] ); ?>.</strong></p>
										<p>Click "Generate Dates" to create empty date structure, then add your prayer times.</p>
										<button type="button" class="button button-primary populate-month-btn" data-month="<?php echo esc_attr( $month_num ); ?>">🗓 Generate Dates for <?php echo esc_html( $months[ intval( $month_num ) ] ); ?></button>
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Import Modal -->
				<div id="import-modal" class="mosque-modal-overlay" style="display:none;">
					<div class="mosque-modal">
						<div class="mosque-modal-header">
							<h3 class="mosque-modal-title">Import Prayer Times</h3>
							<button class="mosque-modal-close" type="button">&times;</button>
						</div>

						<div class="mosque-modal-body">
							<div class="import-method-tabs">
								<button class="import-tab-btn active" data-method="csv">CSV File</button>
								<button class="import-tab-btn" data-method="xlsx">XLSX File</button>
								<button class="import-tab-btn" data-method="paste">Copy/Paste</button>
							</div>

							<div class="import-method-content">
								<div id="csv-import" class="import-method active">
									<label for="csv-file">Select CSV File:</label>
									<input type="file" id="csv-file" accept=".csv" />
								</div>

								<div id="xlsx-import" class="import-method">
									<label for="xlsx-file">Select XLSX File:</label>
									<input type="file" id="xlsx-file" accept=".xlsx,.xls" />
								</div>

								<div id="paste-import" class="import-method">
									<label for="paste-data">Paste Google Sheets Data:</label>
									<textarea id="paste-data" placeholder="Copy and paste from Google Sheets..." rows="10"></textarea>
								</div>
							</div>

							<div class="import-options">
								<label for="import-month">Import to Month:</label>
								<select id="import-month">
									<?php foreach ( $available_months as $month_num ) : ?>
										<option value="<?php echo esc_attr( $month_num ); ?>"><?php echo esc_html( $months[ intval( $month_num ) ] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="import-format-info">
								<h4>Expected Format:</h4>
								<p><strong>Times-only import (dates will be auto-generated):</strong></p>
								<code>Fajr_Start,Fajr_Jamaat,Sunrise,Zuhr_Start,Zuhr_Jamaat,Asr_Start,Asr_Jamaat,Maghrib_Start,Maghrib_Jamaat,Isha_Start,Isha_Jamaat,Jummah_1,Jummah_2</code>
							</div>
						</div>

						<div class="mosque-modal-footer">
							<button type="button" class="button button-primary" id="execute-import">Import Data</button>
							<button type="button" class="button" id="cancel-import">Cancel</button>
						</div>
					</div>
				</div>
			</div>

			<style>
				.mosque-admin-header {
					display: flex;
					justify-content: space-between;
					margin-bottom: 20px;
					padding: 20px;
					background: #f9f9f9;
					border: 1px solid #ddd;
				}

				.mosque-timetable-tabs .nav-tab-wrapper {
					margin-bottom: 0;
				}

				.tab-content {
					display: none;
					padding: 20px;
					border: 1px solid #ccd0d4;
					border-top: none;
					background: white;
				}

				.tab-content.active {
					display: block;
				}

				.month-header {
					display: flex;
					justify-content: space-between;
					align-items: center;
					margin-bottom: 15px;
				}

				.hijri-adjustment {
					margin-bottom: 15px;
					padding: 10px;
					background: #f0f8ff;
					border-left: 4px solid #1976D2;
				}

				.friday-row {
					background-color: #E8F5E8 !important;
				}

				.prayer-times-table-container {
					overflow-x: auto;
				}

				.prayer-times-table-container table {
					min-width: 1200px;
				}

				.prayer-times-table-container input[type="time"] {
					width: 80px;
				}

				.prayer-times-table-container input[type="text"],
				.prayer-times-table-container input[type="date"] {
					width: 100px;
				}

				.prayer-times-table-container input[readonly] {
					background-color: #f9f9f9;
					border: 1px solid #ddd;
				}

				.no-data-message {
					text-align: center;
					padding: 40px;
					background: #f9f9f9;
					border: 2px dashed #ddd;
				}


				.import-method-tabs {
					display: flex;
					margin-bottom: 15px;
				}

				.import-tab-btn {
					padding: 10px 15px;
					border: 1px solid #ddd;
					background: #f9f9f9;
					cursor: pointer;
				}

				.import-tab-btn.active {
					background: #0073aa;
					color: white;
				}

				.import-method {
					display: none;
				}

				.import-method.active {
					display: block;
				}

				.import-format-info {
					margin-top: 15px;
					padding: 10px;
					background: #fff3cd;
					border: 1px solid #ffeaa7;
				}

				#paste-data {
					width: 100%;
					font-family: monospace;
				}
			</style>

			<script>
				jQuery(document).ready(function($) {

					// prevent wiring twice
					if (window.__mt_import_wired__) return;
					window.__mt_import_wired__ = true;

					// WP AJAX url + nonce
					window.ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>'; // Escape output
					var importNonce = '<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>'; // Escape output

					// ---------- Import modal open/close ----------
					$('#csv-import-btn, #xlsx-import-btn, #paste-import-btn').on('click', function() {
						var method = $(this).attr('id')
							.replace('-btn', '')
							.replace('csv-import', 'csv')
							.replace('xlsx-import', 'xlsx')
							.replace('paste-import', 'paste');

						$('#import-modal').addClass('show').fadeIn();
						$('.import-tab-btn').removeClass('active');
						$('.import-tab-btn[data-method="' + method + '"]').addClass('active');
						$('.import-method').removeClass('active');
						$('#' + method + '-import').addClass('active');
					});

					$('.mosque-modal-close, #cancel-import').on('click', function() {
						$('#import-modal').removeClass('show').fadeOut();
					});

					// Switch between CSV / XLSX / Paste tabs inside modal
					$('.import-tab-btn').on('click', function() {
						var method = $(this).data('method');
						$('.import-tab-btn').removeClass('active');
						$(this).addClass('active');
						$('.import-method').removeClass('active');
						$('#' + method + '-import').addClass('active');
					});

					// Helper for consistent success messages
					function msgFrom(resp, fallback) {
						return (resp && resp.data && (resp.data.message || resp.data)) || fallback;
					}


					// ---------- Single "Import Data" button (Option 1 owner) ----------
					$('#execute-import').on('click', function(e) {
						e.preventDefault();

						var $btn = $(this);
						var method = $('.import-tab-btn.active').data('method');
						var month = parseInt($('#import-month').val(), 10) || 0;

						if (!month || month < 1 || month > 12) {
							alert('Please select a valid month.');
							return;
						}

						$btn.prop('disabled', true).addClass('button-disabled');

						// Helper for consistent success messages
						function msgFrom(resp, fallback) {
							return (resp && resp.data && (resp.data.message || resp.data)) || fallback;
						}

						// Helper to finish up UI + refresh current month if the modern admin is present
						function done(success, msg) {
							$btn.prop('disabled', false).removeClass('button-disabled is-loading');
							if (success) {
								// close modal
								$('#import-modal').removeClass('show').fadeOut();

								// Prefer modern reload if available, otherwise fall back to full page reload
								if (window.MosqueTimetableAdmin && MosqueTimetableAdmin.loadMonthData) {
									MosqueTimetableAdmin.showSuccess(msg || 'Import completed.');
									MosqueTimetableAdmin.loadMonthData(MosqueTimetableAdmin.config.currentMonth);
									if (MosqueTimetableAdmin.updateMonthIndicators) {
										MosqueTimetableAdmin.updateMonthIndicators();
									}
								} else {
									alert(msg || 'Import completed.');
									location.reload();
								}
							} else {
								alert(msg || 'Import failed.');
							}
						}
						// Dispatch by active method
						if (method === 'csv') {
							var f = document.getElementById('csv-file');
							if (!f || !f.files || !f.files[0]) {
								done(false, 'Please choose a CSV file.');
								return;
							}
							var fd = new FormData();
							fd.append('action', 'import_csv_timetable');
							fd.append('nonce', importNonce);
							fd.append('month', month);
							fd.append('csv_file', f.files[0]);

							$.ajax({
								url: ajaxurl,
								method: 'POST',
								data: fd,
								processData: false,
								contentType: false
							}).done(function(resp) {
								if (resp && resp.success) {
									done(true, msgFrom(resp, 'CSV imported successfully.'));
								} else {
									done(false, msgFrom(resp, 'CSV import failed.'));
								}
							}).fail(function(xhr) {
								done(false, 'CSV import error: ' + (xhr.statusText || xhr.status));
							});

						} else if (method === 'xlsx') {
							var xf = document.getElementById('xlsx-file');
							if (!xf || !xf.files || !xf.files[0]) {
								done(false, 'Please choose an Excel file.');
								return;
							}
							var xfd = new FormData();
							xfd.append('action', 'import_xlsx_timetable');
							xfd.append('nonce', importNonce);
							xfd.append('month', month);
							xfd.append('xlsx_file', xf.files[0]);

							$.ajax({
								url: ajaxurl,
								method: 'POST',
								data: xfd,
								processData: false,
								contentType: false
							}).done(function(resp) {
								if (resp && resp.success) {
									done(true, msgFrom(resp, 'Excel file imported successfully.'));
								} else {
									done(false, msgFrom(resp, 'Excel import failed.'));
								}
							}).fail(function(xhr) {
								done(false, 'Excel import error: ' + (xhr.statusText || xhr.status));
							});

						} else if (method === 'paste') {
							var text = ($('#paste-data').val() || '').trim();
							if (!text) {
								done(false, 'Please paste your data.');
								return;
							}
							$.post(ajaxurl, {
								action: 'import_paste_data',
								nonce: importNonce,
								month: month,
								paste_data: text
							}).done(function(resp) {
								if (resp && resp.success) {
									done(true, msgFrom(resp, 'Pasted data imported successfully.'));
								} else {
									done(false, msgFrom(resp, 'Paste import failed.'));
								}
							}).fail(function(xhr) {
								done(false, 'Paste import error: ' + (xhr.statusText || xhr.status));
							});

						} else {
							done(false, 'Unknown import method.');
						}
					});

					// ---------- Legacy/fallback handlers (only if modern JS is NOT loaded) ----------
					if (!window.MosqueTimetableAdmin) {
						// Main month tab switching (legacy UI)
						$('.nav-tab').on('click', function(e) {
							e.preventDefault();

							var currentTab = $('.nav-tab-active').data('month');
							if (currentTab && hasUnsavedChanges(currentTab)) {
								if (!confirm('You have unsaved changes. Are you sure you want to leave this tab?')) {
									return;
								}
							}

							var targetMonth = $(this).data('month');
							$('.nav-tab').removeClass('nav-tab-active');
							$(this).addClass('nav-tab-active');
							$('.tab-content').removeClass('active');
							$('#month-' + targetMonth).addClass('active');
						});

						// Generate dates for month (legacy UI)
						$('.populate-month-btn').on('click', function() {
							var month = $(this).data('month');
							$.post(ajaxurl, {
								action: 'populate_month_dates',
								month: month,
								nonce: importNonce
							}, function(response) {
								if (response.success) {
									location.reload();
								} else {
									alert('Error: ' + response.data);
								}
							});
						});

						// Save month (legacy UI expects different DOM names like fajr_start[])
						$('.save-month-btn').on('click', function() {
							var month = $(this).data('month');
							saveMonthLegacy(month);
						});

						// Hijri recalculation (support both .hijri-field and .hijri-date)
						$('.recalc-hijri-btn').on('click', function() {
							var month = $(this).data('month');
							var adjustment = $('#hijri-adj-' + month).val();

							$.post(ajaxurl, {
								action: 'recalculate_hijri_dates',
								month: month,
								adjustment: adjustment,
								nonce: importNonce
							}, function(response) {
								if (response.success && Array.isArray(response.data)) {
									response.data.forEach(function(h, idx) {
										var $scope = $('#month-' + month);
										$scope.find('.hijri-field').eq(idx).val(h);
										$scope.find('.hijri-date').eq(idx).val(h);
									});
								}
							});
						});

						function saveMonthLegacy(month) {
							var monthData = [];

							$('#month-' + month + ' tbody tr').each(function() {
								var row = $(this);
								monthData.push({
									day_number: row.find('input[type="number"]').val(),
									date_full: row.find('input[type="date"]').val(),
									day_name: row.find('input[type="text"]').first().val(),
									hijri_date: row.find('.hijri-field, .hijri-date').first().val(),
									fajr_start: row.find('input[name="fajr_start[]"]').val(),
									fajr_jamaat: row.find('input[name="fajr_jamaat[]"]').val(),
									sunrise: row.find('input[name="sunrise[]"]').val(),
									zuhr_start: row.find('input[name="zuhr_start[]"]').val(),
									zuhr_jamaat: row.find('input[name="zuhr_jamaat[]"]').val(),
									asr_start: row.find('input[name="asr_start[]"]').val(),
									asr_jamaat: row.find('input[name="asr_jamaat[]"]').val(),
									maghrib_start: row.find('input[name="maghrib_start[]"]').val(),
									maghrib_jamaat: row.find('input[name="maghrib_jamaat[]"]').val(),
									isha_start: row.find('input[name="isha_start[]"]').val(),
									isha_jamaat: row.find('input[name="isha_jamaat[]"]').val(),
									jummah_1: row.find('input[name="jummah_1[]"]').val(),
									jummah_2: row.find('input[name="jummah_2[]"]').val()
								});
							});

							$.post(ajaxurl, {
								action: 'save_month_timetable',
								month: month,
								data: monthData,
								nonce: importNonce
							}, function(response) {
								if (response.success) {
									$('<div class="notice notice-success"><p>✅ ' + response.data + '</p></div>')
										.insertAfter('.wrap h1').delay(3000).fadeOut();
								} else {
									alert('Error: ' + response.data);
								}
							});
						}

						function hasUnsavedChanges(month) {
							return false; // legacy placeholder
						}
					}
				});
			</script>

			<?php
		}

		/**
		 * Render fallback settings form (when ACF is not available)
		 */
		private function render_fallback_settings_form() {
			$mosque_name       = get_option( 'mosque_name', get_bloginfo( 'name' ) );
			$mosque_address    = get_option( 'mosque_address', 'Birmingham, UK' );
			$default_year      = get_option( 'default_year', wp_date( 'Y' ) );
			$auto_calendar_url = mt_get_subscribe_url();
			$available_months  = get_option( 'available_months', array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' ) );

			?>
			<table class="form-table">
				<tr>
					<th><label for="mosque_name">Mosque Name</label></th>
					<td>
						<input type="text" id="mosque_name" name="mosque_name" value="<?php echo esc_attr( $mosque_name ); ?>" class="regular-text" />
						<p class="description">Enter the name of your mosque</p>
					</td>
				</tr>
				<tr>
					<th><label for="mosque_address">Mosque Address</label></th>
					<td>
						<textarea id="mosque_address" name="mosque_address" rows="3" class="large-text"><?php echo esc_textarea( $mosque_address ); ?></textarea>
						<p class="description">Enter the complete address of your mosque (street, city, postcode, country)</p>
					</td>
				</tr>
				<tr>
					<th><label for="default_year">Default Year</label></th>
					<td>
						<input type="number" id="default_year" name="default_year" value="<?php echo esc_attr( $default_year ); ?>" min="2020" max="2035" />
						<p class="description">Set the default year for displaying timetables and auto-filling dates</p>
					</td>
				</tr>
				<tr>
					<th><label>Calendar Subscription</label></th>
					<td>
						<strong>Calendar subscription automatically available at:</strong><br>
						<code><?php echo esc_html( $auto_calendar_url ); ?></code><br>
						<p class="description">This URL is automatically generated and always available for your congregation to subscribe to prayer times. Share this link with your community or they can find it on your prayer timetable pages.</p>
					</td>
				</tr>
				<tr>
					<th><label for="custom_subscribe_url">Custom Subscribe URL (Override)</label></th>
					<td>
						<input type="url" id="custom_subscribe_url" name="custom_subscribe_url" value="<?php echo esc_attr( get_option( 'custom_subscribe_url', '' ) ); ?>" class="regular-text" placeholder="https://example.com/custom-calendar.ics" />
						<p class="description">Optional: Override the default calendar subscription URL. Leave empty to use the automatic URL. This allows you to point to external calendar feeds if needed.</p>
					</td>
				</tr>
				<tr>
					<th><label>Terminology Overrides</label></th>
					<td>
						<div id="terminology-overrides">
							<?php
							$terminology_overrides = get_option( 'terminology_overrides', array() );
							if ( empty( $terminology_overrides ) ) {
								$terminology_overrides = array(
									array(
										'from'    => '',
										'to'      => '',
										'enabled' => 1,
									),
								);
							}
							foreach ( $terminology_overrides as $index => $override ) :
								?>
								<div class="terminology-override-row" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
									<input type="text" name="terminology_overrides[<?php echo esc_attr( $index ); ?>][from]" value="<?php echo esc_attr( $override['from'] ?? '' ); ?>" placeholder="From (e.g., Mosque)" style="width: 150px;" />
									<span>→</span>
									<input type="text" name="terminology_overrides[<?php echo esc_attr( $index ); ?>][to]" value="<?php echo esc_attr( $override['to'] ?? '' ); ?>" placeholder="To (e.g., Masjid)" style="width: 150px;" />
									<label>
										<input type="checkbox" name="terminology_overrides[<?php echo esc_attr( $index ); ?>][enabled]" value="1" <?php checked( $override['enabled'] ?? 1, 1 ); ?> />
										Enabled
									</label>
									<button type="button" class="button terminology-remove-btn" onclick="this.parentElement.remove()">Remove</button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" id="add-terminology-override">Add Override</button>
						<p class="description">Customize terminology used throughout the plugin interface. Changes apply to labels only, not internal data. Examples: "Mosque" → "Masjid", "Zuhr" → "Dhuhr".</p>
						<script>
							document.getElementById('add-terminology-override').addEventListener('click', function() {
								const container = document.getElementById('terminology-overrides');
								const index = container.children.length;
								const newRow = document.createElement('div');
								newRow.className = 'terminology-override-row';
								newRow.style.cssText = 'margin-bottom: 10px; display: flex; gap: 10px; align-items: center;';
								newRow.innerHTML = `
							<input type="text" name="terminology_overrides[${index}][from]" placeholder="From (e.g., Mosque)" style="width: 150px;" />
							<span>→</span>
							<input type="text" name="terminology_overrides[${index}][to]" placeholder="To (e.g., Masjid)" style="width: 150px;" />
							<label>
								<input type="checkbox" name="terminology_overrides[${index}][enabled]" value="1" checked />
								Enabled
							</label>
							<button type="button" class="button terminology-remove-btn" onclick="this.parentElement.remove()">Remove</button>
						`;
								container.appendChild(newRow);
							});
						</script>
					</td>
				</tr>
				<tr>
					<th><label>Available Months</label></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">Select which months you have prayer times for</legend>
							<?php
							$months = array(
								'1'  => 'January',
								'2'  => 'February',
								'3'  => 'March',
								'4'  => 'April',
								'5'  => 'May',
								'6'  => 'June',
								'7'  => 'July',
								'8'  => 'August',
								'9'  => 'September',
								'10' => 'October',
								'11' => 'November',
								'12' => 'December',
							);

							foreach ( $months as $num => $name ) :
								$checked = in_array( $num, $available_months ) ? 'checked' : '';
								?>
								<label style="display: inline-block; margin-right: 15px; margin-bottom: 5px;">
									<input type="checkbox" name="available_months[]" value="<?php echo esc_attr( $num ); ?>" <?php echo esc_attr( $checked ); ?> />
									<?php echo esc_html( $name ); ?>
								</label>
							<?php endforeach; ?>
							<p class="description">Select which months you have prayer times for. Only checked months will show tabs in the admin.</p>
						</fieldset>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Save Configuration' ); ?>
			<?php
		}

		/**
		 * Render fallback appearance form (when ACF is not available)
		 */
		private function render_fallback_appearance_form() {
			?>
			<table class="form-table">
				<tr>
					<th><label for="mt_today_color">Today Row Color</label></th>
					<td>
						<input type="color" id="mt_today_color" name="mt_today_color" value="<?php echo esc_attr( get_option( 'mt_today_color', '#FFF9C4' ) ); ?>" />
						<p class="description">Background color for today's row in the timetable</p>
					</td>
				</tr>
				<tr>
					<th><label for="mt_friday_color">Friday Row Color</label></th>
					<td>
						<input type="color" id="mt_friday_color" name="mt_friday_color" value="<?php echo esc_attr( get_option( 'mt_friday_color', '#E8F5E8' ) ); ?>" />
						<p class="description">Background color for Friday rows in the timetable</p>
					</td>
				</tr>
				<tr>
					<th><label for="mt_row_alt_bg">Alternate Row Color</label></th>
					<td>
						<input type="color" id="mt_row_alt_bg" name="mt_row_alt_bg" value="<?php echo esc_attr( get_option( 'mt_row_alt_bg', '#F8F9FA' ) ); ?>" />
						<p class="description">Background color for alternate rows</p>
					</td>
				</tr>
				<tr>
					<th><label for="mt_next_prayer_bg">Next Prayer Color</label></th>
					<td>
						<input type="color" id="mt_next_prayer_bg" name="mt_next_prayer_bg" value="<?php echo esc_attr( get_option( 'mt_next_prayer_bg', '#E3F2FD' ) ); ?>" />
						<p class="description">Background color for next prayer highlight</p>
					</td>
				</tr>
				<tr>
					<th><label for="mt_btn_bg">Button Color</label></th>
					<td>
						<input type="color" id="mt_btn_bg" name="mt_btn_bg" value="<?php echo esc_attr( get_option( 'mt_btn_bg', '#1976D2' ) ); ?>" />
						<p class="description">Background color for buttons</p>
					</td>
				</tr>
				<tr>
					<th><label for="mt_table_text_color">Table Text Color</label></th>
					<td>
						<input type="color" id="mt_table_text_color" name="mt_table_text_color" value="<?php echo esc_attr( get_option( 'mt_table_text_color', '#333333' ) ); ?>" />
						<p class="description">Text color for table content</p>
					</td>
				</tr>
				<tr>
					<th><label for="mt_header_text_color">Header Text Color</label></th>
					<td>
						<input type="color" id="mt_header_text_color" name="mt_header_text_color" value="<?php echo esc_attr( get_option( 'mt_header_text_color', '#FFFFFF' ) ); ?>" />
						<p class="description">Text color for table headers</p>
					</td>
				</tr>
				<tr>
					<th><label for="enable_pwa">Enable PWA Features</label></th>
					<td>
						<input type="checkbox" id="enable_pwa" name="enable_pwa" value="1" <?php checked( get_option( 'enable_pwa', 1 ) ); ?> />
						<label for="enable_pwa">Enable Progressive Web App features including offline access and home screen installation</label>
					</td>
				</tr>
				<tr>
					<th><label for="enable_countdown">Enable Prayer Countdown</label></th>
					<td>
						<input type="checkbox" id="enable_countdown" name="enable_countdown" value="1" <?php checked( get_option( 'enable_countdown', 1 ) ); ?> />
						<label for="enable_countdown">Show countdown timer to next prayer</label>
					</td>
				</tr>
				<tr>
					<th><label for="notification_text">Notification Text Template</label></th>
					<td>
						<input type="text" id="notification_text" name="notification_text" value="<?php echo esc_attr( get_option( 'notification_text', 'Time for {prayer} prayer!' ) ); ?>" class="regular-text" />
						<p class="description">Template for prayer notifications. Use {prayer} placeholder for prayer name.</p>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Save Appearance Settings' ); ?>
			<?php
		}

		/**
		 * Render import/export tools page
		 */
		public function render_import_export_page() {
			?>
			<div class="wrap">
				<div class="mosque-page-header">
					<img src="<?php echo esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/icon-192.png' ); ?>" alt="Mosque Logo" class="mosque-logo">
					<h1>Import/Export Tools</h1>
				</div>

				<div class="mosque-import-export-container">
					<div class="card">
						<h2>📥 Import Prayer Times</h2>
						<p>Import prayer times from various sources:</p>
						<ul>
							<li>📄 <strong>CSV Files</strong> - Standard comma-separated values</li>
							<li>📊 <strong>XLSX Files</strong> - Excel spreadsheets</li>
							<li>📋 <strong>Copy/Paste</strong> - Direct from Google Sheets</li>
						</ul>

						<h3>📋 Sample Templates</h3>
						<p>Download example files to see the correct format for your prayer time data:</p>
						<div class="sample-downloads">
							<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=download_sample_csv&nonce=' . wp_create_nonce( 'mosque_sample_download' ) ) ); ?>" // Escape output
								class="button button-secondary" target="_blank">
								📄 Download Sample CSV
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=download_sample_xlsx&nonce=' . wp_create_nonce( 'mosque_sample_download' ) ) ); ?>" // Escape output
								class="button button-secondary" target="_blank">
								📊 Download Sample XLSX
							</a>
						</div>
						<p class="description">These templates show the exact column headers and data format required for successful imports. Fill in your mosque's prayer times using the same structure.</p>

						<p><a href="admin.php?page=mosque-timetables" class="button button-primary">Go to Timetables Page to Import</a></p>
					</div>

					<div class="card">
						<h2>📤 Export Prayer Times</h2>
						<p>Export your prayer times in various formats:</p>

						<h3>📅 ICS Calendar Export</h3>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"> <!-- Escape output -->
							<input type="hidden" name="action" value="export_ics_calendar">
							<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>"> <!-- Escape output -->

							<table class="form-table">
								<tr>
									<th><label for="export_year">Year:</label></th>
									<td>
										<select name="year" id="export_year">
											<?php
											$current_year = wp_date( 'Y' );
											for ( $y = $current_year - 1; $y <= $current_year + 2; $y++ ) :
												?>
												<option value="<?php echo esc_attr( (string) $y ); ?>" <?php selected( $y, $current_year ); ?>><?php echo esc_html( (string) $y ); ?></option>
											<?php endfor; ?>
										</select>
									</td>
								</tr>
								<tr>
									<th><label for="export_month">Month:</label></th>
									<td>
										<select name="month" id="export_month">
											<option value="">All Months</option>
											<?php
											$months = array(
												1  => 'January',
												2  => 'February',
												3  => 'March',
												4  => 'April',
												5  => 'May',
												6  => 'June',
												7  => 'July',
												8  => 'August',
												9  => 'September',
												10 => 'October',
												11 => 'November',
												12 => 'December',
											);
											foreach ( $months as $num => $name ) :
												?>
												<option value="<?php echo esc_attr( $num ); ?>"><?php echo esc_html( $name ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<th><label for="prayer_types">Prayer Types:</label></th>
									<td>
										<select name="prayer_types" id="prayer_types">
											<option value="both">Start Times & Jamaat Times</option>
											<option value="start">Start Times Only</option>
											<option value="jamaat">Jamaat Times Only</option>
										</select>
									</td>
								</tr>
								<tr>
									<th><label for="reminder">Reminder (minutes before):</label></th>
									<td>
										<select name="reminder" id="reminder">
											<option value="0">No Reminder</option>
											<option value="5">5 minutes</option>
											<option value="10">10 minutes</option>
											<option value="15" selected>15 minutes</option>
											<option value="30">30 minutes</option>
											<option value="60">1 hour</option>
										</select>
									</td>
								</tr>
							</table>

							<p><input type="submit" class="button button-primary" value="📅 Export ICS Calendar"></p>
						</form>

						<h3>📊 CSV Export</h3>
						<p>Export prayer times as CSV for use in spreadsheets.</p>
						<button class="button" id="export-csv-btn">📊 Export CSV</button>

						<h3>🔗 Auto-Generated Calendar Subscription</h3>
						<?php $auto_calendar_url = mt_get_subscribe_url(); ?>
						<p><strong>Your automatic prayer calendar URL:</strong><br>
							<code><?php echo esc_url( $auto_calendar_url ); ?></code>
						</p>
						<p><a href="<?php echo esc_url( $auto_calendar_url ); ?>" target="_blank" class="button">📅 Download Prayer Calendar</a></p>
						<p class="description">This calendar is automatically generated from your prayer timetables and updates whenever you change prayer times. Share this URL with your congregation so they can subscribe to prayer times in their calendar apps.</p>
					</div>

					<div class="card">
						<h2>🔄 Data Management</h2>
						<p>Manage your prayer time data:</p>

						<h3>🗑️ Clear All Data</h3>
						<p><strong>Warning:</strong> This will remove all prayer times from all months.</p>
						<button class="button button-secondary" id="clear-all-data-btn" onclick="if(confirm('Are you sure you want to delete ALL prayer time data? This cannot be undone!')) { clearAllData(); }">🗑️ Clear All Prayer Times</button>

						<h3>🔄 Reset to Empty Structure</h3>
						<p>Keep the date structure but remove all prayer times.</p>
						<button class="button" id="reset-structure-btn" onclick="if(confirm('Reset all months to empty date structure?')) { resetToEmptyStructure(); }">🔄 Reset Prayer Times</button>

						<h3>📅 Regenerate Dates</h3>
						<p>Regenerate all dates based on current default year setting.</p>
						<button class="button" id="regenerate-dates-btn" onclick="if(confirm('Regenerate all dates? This will update Hijri dates too.')) { regenerateAllDates(); }">📅 Regenerate All Dates</button>
					</div>
				</div>
			</div>

			<style>
				.mosque-import-export-container {
					display: grid;
					grid-template-columns: 1fr 1fr 1fr;
					gap: 20px;
					margin-top: 20px;
				}

				@media (max-width: 1200px) {
					.mosque-import-export-container {
						grid-template-columns: 1fr 1fr;
					}
				}

				@media (max-width: 800px) {
					.mosque-import-export-container {
						grid-template-columns: 1fr;
					}
				}

				.card {
					background: white;
					border: 1px solid #ccd0d4;
					box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
					padding: 20px;
				}

				.card h2 {
					margin-top: 0;
					border-bottom: 1px solid #eee;
					padding-bottom: 10px;
				}

				.card h3 {
					color: #333;
					margin-top: 20px;
				}

				.card ul {
					padding-left: 20px;
				}

				.card code {
					background: #f1f1f1;
					padding: 5px 10px;
					border-radius: 3px;
					word-break: break-all;
				}

				.sample-downloads {
					display: flex;
					gap: 15px;
					margin: 15px 0;
					flex-wrap: wrap;
				}

				.sample-downloads .button {
					display: inline-flex;
					align-items: center;
					gap: 8px;
					padding: 10px 20px;
					text-decoration: none;
					border-radius: 5px;
					transition: all 0.3s ease;
				}

				.sample-downloads .button:hover {
					transform: translateY(-2px);
					box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
				}

				.sample-downloads .button-secondary {
					background: #2271b1;
					color: white;
					border: 1px solid #2271b1;
				}

				.sample-downloads .button-secondary:hover {
					background: #135e96;
					border-color: #135e96;
					color: white;
				}

				@media (max-width: 600px) {
					.sample-downloads {
						flex-direction: column;
					}

					.sample-downloads .button {
						justify-content: center;
						width: 100%;
					}
				}
			</style>

			<script>
				function clearAllData() {
					jQuery.post(ajaxurl, {
						action: 'clear_all_prayer_data',
						nonce: '<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>' // Escape output
					}, function(response) {
						if (response.success) {
							alert('✅ All prayer data cleared successfully!');
						} else {
							alert('❌ Error: ' + response.data);
						}
					});
				}

				function resetToEmptyStructure() {
					jQuery.post(ajaxurl, {
						action: 'reset_to_empty_structure',
						nonce: '<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>' // Escape output
					}, function(response) {
						if (response.success) {
							alert('✅ Prayer times reset to empty structure!');
						} else {
							alert('❌ Error: ' + response.data);
						}
					});
				}

				function regenerateAllDates() {
					jQuery.post(ajaxurl, {
						action: 'regenerate_all_dates',
						nonce: '<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>' // Escape output
					}, function(response) {
						if (response.success) {
							alert('✅ All dates regenerated successfully!');
						} else {
							alert('❌ Error: ' + response.data);
						}
					});
				}

				jQuery(document).ready(function($) {
					// prevent wiring twice
					if (window.__mt_import_wired__) return;
					window.__mt_import_wired__ = true;

					$('#export-csv-btn').on('click', function() {
						window.open(ajaxurl + '?action=export_csv_calendar&nonce=' + '<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>', '_blank'); // Escape output
					});
				});
			</script>
			<?php
		}

		/**
		 * Render debug page (temporary)
		 */
		public function render_debug_page() {
			?>
			<div class="wrap">
				<h1>🔧 Timetables Debug Report</h1>

				<div style="background: #f0f8ff; padding: 15px; margin: 10px 0; border-left: 4px solid #0073aa;">
					<h3>Quick Test: Go to Timetables Page</h3>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=mosque-timetables' ) ); ?>" class="button button-primary" target="_blank">Open Timetables Page</a></p> <!-- Escape output -->
					<p>Then come back here to see the diagnostic results below.</p>
				</div>

				<?php
				// Test 1: AJAX Actions
				echo '<div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px;">';
				echo '<h2>Test 1: AJAX Action Registration</h2>';

				$critical_ajax_actions = array(
					'save_month_timetable' => 'Save prayer times',
					'get_month_timetable'  => 'Load prayer times',
					'import_csv_timetable' => 'CSV import',
					'generate_all_dates'   => 'Generate all dates',
					'generate_month_dates' => 'Generate month dates',
				);

				foreach ( $critical_ajax_actions as $action => $description ) {
					if ( has_action( "wp_ajax_$action" ) ) {
						echo '<p style="color: green;">✅ ' . esc_html( $description ) . ' (' . esc_html( $action ) . ')</p>'; // Escape output
					} else {
						echo '<p style="color: red;">❌ ' . esc_html( $description ) . ' (' . esc_html( $action ) . ') - MISSING!</p>'; // Escape output
					}
				}
				echo '</div>';

				// Test 2: JavaScript Files
				echo '<div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px;">';
				echo '<h2>Test 2: Admin Assets</h2>';

				$admin_js_file = MOSQUE_TIMETABLE_PLUGIN_DIR . 'assets/mosque-timetable-admin.js';
				if ( file_exists( $admin_js_file ) ) {
					echo '<p style="color: green;">✅ Admin JS file exists (' . number_format( filesize( $admin_js_file ) ) . ' bytes)</p>';
				} else {
					echo '<p style="color: red;">❌ Admin JS file missing</p>';
				}

				$admin_css_file = MOSQUE_TIMETABLE_PLUGIN_DIR . 'assets/mosque-timetable-admin.css';
				if ( file_exists( $admin_css_file ) ) {
					echo '<p style="color: green;">✅ Admin CSS file exists (' . number_format( filesize( $admin_css_file ) ) . ' bytes)</p>';
				} else {
					echo '<p style="color: red;">❌ Admin CSS file missing</p>';
				}
				echo '</div>';

				// Test 3: Month Data
				echo '<div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px;">';
				echo '<h2>Test 3: Prayer Data Availability</h2>';

				$months = array(
					'January',
					'February',
					'March',
					'April',
					'May',
					'June',
					'July',
					'August',
					'September',
					'October',
					'November',
					'December',
				);

				for ( $month = 1; $month <= 12; $month++ ) {
					$field_name = "daily_prayers_$month";
					$month_data = get_field( $field_name, 'option' );

					if ( $month_data && is_array( $month_data ) ) {
						echo '<p style="color: green;">✅ ' . esc_html( $months[ $month - 1 ] ) . ': ' . esc_html( count( $month_data ) ) . ' days</p>'; // Escape output
					} else {
						echo '<p style="color: orange;">⚠️ ' . esc_html( $months[ $month - 1 ] ) . ': No data</p>'; // Escape output
					}
				}
				echo '</div>';

				// Test 4: Browser Console Test
				echo '<div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px;">';
				echo '<h2>Test 4: Browser Console Test</h2>';
				echo '<p>Copy this code and paste it into the browser console on the Timetables page:</p>';
				?>
				<textarea readonly style="width: 100%; height: 200px; font-family: monospace; font-size: 12px;">console.log("=== MOSQUE TIMETABLE DEBUG ===");

// Check main objects
if (typeof MosqueTimetableAdmin !== 'undefined') {
	console.log('✅ MosqueTimetableAdmin exists:', MosqueTimetableAdmin);
} else {
	console.error('❌ MosqueTimetableAdmin missing');
}

if (typeof mosqueTimetableAdmin !== 'undefined') {
	console.log('✅ mosqueTimetableAdmin config:', mosqueTimetableAdmin);
} else {
	console.error('❌ mosqueTimetableAdmin config missing');
}

// Test AJAX
if (typeof jQuery !== 'undefined' && mosqueTimetableAdmin?.ajaxUrl) {
	jQuery.post(mosqueTimetableAdmin.ajaxUrl, {
		action: 'get_month_timetable',
		month: 9,
		year: 2024,
		nonce: mosqueTimetableAdmin.nonce
	}).done(function(response) {
		console.log('✅ AJAX test successful:', response);
	}).fail(function(xhr) {
		console.error('❌ AJAX test failed:', xhr.responseText);
	});
}

console.log("=== DEBUG COMPLETE ===");</textarea>
				<?php
				echo '<p><strong>Instructions:</strong></p>';
				echo '<ol>';
				echo '<li>Go to the <a href="' . esc_url( admin_url( 'admin.php?page=mosque-timetables' ) ) . '" target="_blank">Timetables page</a></li>'; // Escape output
				echo '<li>Press F12 to open browser dev tools</li>';
				echo '<li>Go to Console tab</li>';
				echo '<li>Copy and paste the code above</li>';
				echo '<li>Press Enter and check the results</li>';
				echo '</ol>';
				echo '</div>';
				?>

				<div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107;">
					<h3>What to Do Next</h3>
					<p><strong>If you see red ❌ errors above:</strong></p>
					<ul>
						<li>Missing AJAX actions = Backend problem</li>
						<li>Missing asset files = File upload problem</li>
						<li>Browser console errors = Frontend problem</li>
					</ul>
					<p><strong>Report back:</strong> Tell me which specific errors you see and I'll provide targeted fixes.</p>
				</div>
			</div>
			<?php
		}

		/**
		 * Register REST API endpoints
		 */
		public function register_rest_endpoints() {
			// Register namespace
			register_rest_route(
				'mosque/v1',
				'/prayer-times/(?P<year>\d{4})/(?P<month>\d{1,2})',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_get_month_prayers' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'year'  => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param ) && $param >= 2020 && $param <= 2030;
							},
						),
						'month' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param ) && $param >= 1 && $param <= 12;
							},
						),
					),
				)
			);

			register_rest_route(
				'mosque/v1',
				'/today-prayers',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_get_today_prayers' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'mosque/v1',
				'/next-prayer',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_get_next_prayer' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'mosque/v1',
				'/export-ics',
				array(
					'methods'             => array( 'GET', 'POST' ),
					'callback'            => array( $this, 'rest_export_ics' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'date_range'    => array(
							'required' => false,
							'default'  => 'year',
							'enum'     => array( 'year', 'month' ),
						),
						'month'         => array(
							'required'          => false,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param ) && $param >= 1 && $param <= 12;
							},
						),
						'year'          => array(
							'required'          => false,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param ) && $param >= 2020 && $param <= 2030;
							},
						),
						'include_jamah' => array(
							'required'          => false,
							'default'           => true,
							'validate_callback' => function ( $param ) {
								return is_bool( $param ) || in_array( $param, array( '0', '1', 'true', 'false' ), true );
							},
						),
						'alarms'        => array(
							'required' => false,
							'default'  => array(),
							'type'     => 'array',
						),
						'jummah'        => array(
							'required' => false,
							'default'  => 'both',
							'enum'     => array( 'both', '1st', '2nd' ),
						),
						'sunrise_alarm' => array(
							'required'          => false,
							'default'           => '',
							'validate_callback' => function ( $param ) {
								return empty( $param ) || ( is_numeric( $param ) && $param >= 0 && $param <= 120 );
							},
						),
						'subscribe'     => array(
							'required'          => false,
							'default'           => false,
							'validate_callback' => function ( $param ) {
								return is_bool( $param ) || in_array( $param, array( '0', '1', 'true', 'false' ), true );
							},
						),
						// Legacy parameters for backward compatibility
						'prayer_types'  => array(
							'required' => false,
							'default'  => 'both',
							'enum'     => array( 'start', 'jamaat', 'both' ),
						),
						'reminder'      => array(
							'required'          => false,
							'default'           => 15,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param ) && $param >= 0 && $param <= 60;
							},
						),
					),
				)
			);

			register_rest_route(
				'mosque/v1',
				'/import-csv',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'rest_import_csv' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				)
			);

			// Widget endpoints for PWA home screen widgets
			register_rest_route(
				'mosque/v1',
				'/widget/prayer-times',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_get_widget_prayer_times' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'mosque/v1',
				'/widget/countdown',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'rest_get_widget_countdown' ),
					'permission_callback' => '__return_true',
				)
			);

			// Push notification endpoints
			register_rest_route(
				'mosque/v1',
				'/subscribe',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'rest_subscribe_push' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'subscription'    => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_push_subscription' ),
						),
						'offsets'         => array(
							'required' => false,
							'default'  => array(),
							'type'     => 'array',
						),
						'sunrise_warning' => array(
							'required'          => false,
							'default'           => false,
							'validate_callback' => function ( $param ) {
								return is_bool( $param ) || in_array( $param, array( '0', '1', 'true', 'false' ), true );
							},
						),
					),
				)
			);

			register_rest_route(
				'mosque/v1',
				'/unsubscribe',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'rest_unsubscribe_push' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'endpoint' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_string( $param ) && ! empty( $param );
							},
						),
					),
				)
			);
		}

		/**
		 * REST: Get month prayers
		 */
		public function rest_get_month_prayers( $request ) {
			$year  = intval( $request['year'] );
			$month = intval( $request['month'] );

			$prayer_data = $this->get_month_prayer_data( $year, $month );

			if ( ! $prayer_data ) {
				return new WP_Error( 'no_data', 'No prayer data found for this month', array( 'status' => 404 ) );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => $prayer_data,
				)
			);
		}

		/**
		 * REST: Get today's prayers
		 */
		public function rest_get_today_prayers( $request ) {
			$today_data  = $this->get_today_prayer_data();
			$next_prayer = $this->get_next_prayer_data();

			if ( ! $today_data ) {
				return new WP_Error( 'no_data', 'No prayer data found for today', array( 'status' => 404 ) );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => array(
						'today'      => $today_data,
						'nextPrayer' => $next_prayer,
					),
				)
			);
		}

		/**
		 * REST: Get next prayer
		 */
		public function rest_get_next_prayer( $request ) {
			$next_prayer = $this->get_next_prayer_data();

			if ( ! $next_prayer ) {
				return new WP_Error( 'no_data', 'Unable to determine next prayer', array( 'status' => 404 ) );
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => $next_prayer,
				)
			);
		}

		/**
		 * REST: Export ICS calendar
		 */
		public function rest_export_ics( $request ) {
			// Extract parameters with defaults and legacy support
			$date_range = $request->get_param( 'date_range' ) ?: 'year';
			$month      = $request->get_param( 'month' );
			$year       = $request->get_param( 'year' ) ?: wp_date( 'Y' );

			// Handle include_jamah parameter (with legacy prayer_types fallback)
			$include_jamah = $request->get_param( 'include_jamah' );
			if ( null === $include_jamah ) {
				// Legacy fallback
				$prayer_types  = $request->get_param( 'prayer_types' ) ?: 'both';
				$include_jamah = in_array( $prayer_types, array( 'jamaat', 'both' ), true );
			} else {
				// Convert string booleans
				/** @phpstan-ignore-next-line */
				// @phpstan-ignore-line
				$include_jamah = filter_var( $include_jamah, FILTER_VALIDATE_BOOLEAN );
			}

			// Alarms array
			$alarms = $request->get_param( 'alarms' ) ?: array();
			if ( ! is_array( $alarms ) ) {
				$alarms = array();
			}

			// Legacy reminder fallback
			$legacy_reminder = $request->get_param( 'reminder' );
			if ( $legacy_reminder && empty( $alarms ) ) {
				$alarms = array( intval( $legacy_reminder ) );
			}

			// Other parameters
			$jummah        = $request->get_param( 'jummah' ) ?: 'both';
			$sunrise_alarm = $request->get_param( 'sunrise_alarm' ) ?: '';
			/** @phpstan-ignore-next-line */
			// @phpstan-ignore-line
			$subscribe = filter_var( $request->get_param( 'subscribe' ), FILTER_VALIDATE_BOOLEAN );

			// Generate ICS content with new parameters
			$ics_content = $this->generate_enhanced_ics_content(
				array(
					'date_range'    => $date_range,
					'year'          => intval( $year ),
					'month'         => $month ? intval( $month ) : null,
					'include_jamah' => $include_jamah,
					'alarms'        => array_map( 'intval', $alarms ),
					'jummah'        => $jummah,
					'sunrise_alarm' => $sunrise_alarm ? intval( $sunrise_alarm ) : 0,
				)
			);

			if ( ! $ics_content ) {
				return new WP_Error( 'generation_failed', 'Failed to generate ICS content', array( 'status' => 500 ) );
			}

			// For subscribe mode, return content directly
			if ( $subscribe ) {
				header( 'Content-Type: text/calendar; charset=utf-8' );
				header( 'Content-Disposition: inline; filename="prayer-times.ics"' );
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
				echo $ics_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545 // ICS calendar format - content sanitized at creation, escaping would break format
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped output
				exit;
			}

			// For download mode, create temporary file
			$upload_dir = wp_upload_dir();
			$filename   = 'prayer-times-' . $year;
			if ( 'month' === $date_range && $month ) {
				$filename .= '-' . str_pad( $month, 2, '0', STR_PAD_LEFT );
			}
			$filename .= '.ics';
			$file_path = $upload_dir['path'] . '/' . $filename;

			$fs = mt_fs();
			if ( ! $fs || ! $fs->put_contents( $file_path, $ics_content, FS_CHMOD_FILE ) ) {
				return new WP_Error( 'file_creation_failed', 'Failed to create ICS file', array( 'status' => 500 ) );
			}

			// Set download headers
			header( 'Content-Type: text/calendar; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Content-Length: ' . strlen( $ics_content ) );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped output
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo $ics_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545 // ICS calendar format - content sanitized at creation, escaping would break format

			// Clean up temporary file
			$fs->delete( $file_path );

			exit;
		}

		/**
		 * REST: Import CSV
		 */
		public function rest_import_csv( $request ) {
			$files = $request->get_file_params();

			if ( ! isset( $files['csv_file'] ) ) {
				return new WP_Error( 'no_file', 'No CSV file uploaded', array( 'status' => 400 ) );
			}

			$csv_file = $files['csv_file'];
			$month    = intval( $request->get_param( 'month' ) );

			if ( ! $month || $month < 1 || $month > 12 ) {
				return new WP_Error( 'invalid_month', __( 'Invalid month specified', 'mosque-timetable' ), array( 'status' => 400 ) );
			}

			$result = $this->process_csv_import( $csv_file, $month );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => $result,
				)
			);
		}

		/**
		 * REST: Get widget prayer times data
		 */
		public function rest_get_widget_prayer_times( $request ) {
			$today_data  = $this->get_today_prayer_data();
			$next_prayer = $this->get_next_prayer_data();

			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';

			// Get widget customization settings
			$widget_bg_color      = get_field( 'widget_bg_color', 'option' ) ?: '#ffffff';
			$widget_text_color    = get_field( 'widget_text_color', 'option' ) ?: '#333333';
			$widget_border_radius = get_field( 'widget_border_radius', 'option' ) ?: 8;
			$widget_shadow        = get_field( 'widget_shadow', 'option' ) !== false;

			$widget_data = array(
				'type'        => 'prayer-times-widget',
				'version'     => '1.0',
				'lastUpdated' => current_time( 'c' ),
				'mosque'      => array(
					'name'    => $mosque_name,
					'address' => $mosque_address,
				),
				'today'       => array(
					'date'       => wp_date( 'Y-m-d' ),
					'hijri_date' => ! empty( $today_data['hijri_date'] ) ? $today_data['hijri_date'] : '',
					'day_name'   => wp_date( 'l' ),
					'prayers'    => array(),
				),
				'next_prayer' => $next_prayer,
				'styling'     => array(
					'backgroundColor' => $widget_bg_color,
					'textColor'       => $widget_text_color,
					'borderRadius'    => $widget_border_radius . 'px',
					'boxShadow'       => $widget_shadow ? '0 2px 8px rgba(0,0,0,0.1)' : 'none',
				),
				'metadata'    => array(
					'timezone'         => wp_timezone_string(),
					'cache_duration'   => 300,
					'refresh_interval' => 60000,
				),
			);

			if ( $today_data ) {
				$prayer_names = array(
					'fajr'    => 'Fajr',
					'sunrise' => 'Sunrise',
					'zuhr'    => 'Zuhr',
					'asr'     => 'Asr',
					'maghrib' => 'Maghrib',
					'isha'    => 'Isha',
				);

				foreach ( $prayer_names as $key => $name ) {
					if ( ! empty( $today_data[ $key . '_start' ] ) ) {
						$widget_data['today']['prayers'][] = array(
							'name'           => $name,
							'start_time'     => $today_data[ $key . '_start' ],
							'jamaat_time'    => ! empty( $today_data[ $key . '_jamaat' ] ) ? $today_data[ $key . '_jamaat' ] : null,
							'is_current'     => ( $next_prayer && $next_prayer['prayer'] === $name ),
							'formatted_time' => wp_date( 'g:i A', strtotime( $today_data[ $key . '_start' ] ) ),
						);
					}
				}

				// Add Jummah if it's Friday
				if ( wp_date( 'w' ) === 5 ) {
					if ( ! empty( $today_data['jummah_1'] ) ) {
						$widget_data['today']['prayers'][] = array(
							'name'           => 'Jummah 1',
							'start_time'     => $today_data['jummah_1'],
							'jamaat_time'    => $today_data['jummah_1'],
							'is_current'     => false,
							'formatted_time' => wp_date( 'g:i A', strtotime( $today_data['jummah_1'] ) ),
						);
					}
					if ( ! empty( $today_data['jummah_2'] ) ) {
						$widget_data['today']['prayers'][] = array(
							'name'           => 'Jummah 2',
							'start_time'     => $today_data['jummah_2'],
							'jamaat_time'    => $today_data['jummah_2'],
							'is_current'     => false,
							'formatted_time' => wp_date( 'g:i A', strtotime( $today_data['jummah_2'] ) ),
						);
					}
				}
			}

			return rest_ensure_response( $widget_data );
		}

		/**
		 * REST: Get widget countdown data
		 */
		public function rest_get_widget_countdown( $request ) {
			$next_prayer = $this->get_next_prayer_data();
			$mosque_name = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';

			// Get widget customization settings
			$widget_bg_color   = get_field( 'widget_bg_color', 'option' ) ?: '#667eea';
			$widget_text_color = get_field( 'widget_text_color', 'option' ) ?: '#ffffff';

			$countdown_data = array(
				'type'        => 'prayer-countdown-widget',
				'version'     => '1.0',
				'lastUpdated' => current_time( 'c' ),
				'mosque'      => array(
					'name' => $mosque_name,
				),
				'countdown'   => null,
				'styling'     => array(
					'backgroundColor' => $widget_bg_color,
					'textColor'       => $widget_text_color,
					'borderRadius'    => '8px',
					'boxShadow'       => '0 2px 8px rgba(0,0,0,0.2)',
				),
				'metadata'    => array(
					'timezone'         => wp_timezone_string(),
					'cache_duration'   => 60,
					'refresh_interval' => 60000,
				),
			);

			if ( $next_prayer ) {
				$current_time = current_time( 'timestamp' );
				$next_time    = strtotime( $next_prayer['time'] );
				$time_diff    = $next_time - $current_time;

				if ( $time_diff > 0 ) {
					$hours   = floor( $time_diff / 3600 );
					$minutes = floor( ( $time_diff % 3600 ) / 60 );
					$seconds = $time_diff % 60;

					$countdown_data['countdown'] = array(
						'prayer_name'         => $next_prayer['prayer'],
						'prayer_time'         => $next_prayer['time'],
						'formatted_time'      => wp_date( 'g:i A', strtotime( $next_prayer['time'] ) ),
						'remaining_seconds'   => $time_diff,
						'formatted_countdown' => sprintf( '%02d:%02d:%02d', $hours, $minutes, $seconds ),
						'hours'               => $hours,
						'minutes'             => $minutes,
						'seconds'             => $seconds,
						'is_today'            => ( wp_date( 'Y-m-d' ) === wp_date( 'Y-m-d', $next_time ) ),
					);
				}
			}

			return rest_ensure_response( $countdown_data );
		}

		/**
		 * REST: Subscribe to push notifications
		 */
		public function rest_subscribe_push( $request ) {
			// Verify nonce for security
			if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
				return new WP_Error( 'invalid_nonce', __( 'Security check failed', 'mosque-timetable' ), array( 'status' => 403 ) );
			}

			$subscription    = $request->get_param( 'subscription' );
			$offsets         = $request->get_param( 'offsets' ) ?: array();
			$sunrise_warning = filter_var( $request->get_param( 'sunrise_warning' ), FILTER_VALIDATE_BOOLEAN );

			// Validate VAPID keys are configured
			$vapid_public  = mt_has_acf() ? get_field( 'vapid_public_key', 'option' ) : get_option( 'vapid_public_key' );
			$vapid_private = mt_has_acf() ? get_field( 'vapid_private_key', 'option' ) : get_option( 'vapid_private_key' );

			if ( empty( $vapid_public ) || empty( $vapid_private ) ) {
				return new WP_Error( 'vapid_not_configured', 'Push notifications not properly configured', array( 'status' => 500 ) );
			}

			// Store subscription in database
			$subscription_data = array(
				'endpoint'        => $subscription['endpoint'],
				'keys'            => $subscription['keys'],
				'offsets'         => $offsets,
				'sunrise_warning' => $sunrise_warning,
				'created_at'      => current_time( 'mysql' ),
				'user_agent'      => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), // Sanitize input
			);

			$subscriptions                              = get_option( 'mt_push_subscriptions', array() );
			$subscriptions[ $subscription['endpoint'] ] = $subscription_data;
			update_option( 'mt_push_subscriptions', $subscriptions );

			return rest_ensure_response(
				array(
					'success' => true,
					'message' => 'Successfully subscribed to prayer reminders',
				)
			);
		}

		/**
		 * REST: Unsubscribe from push notifications
		 */
		public function rest_unsubscribe_push( $request ) {
			// Verify nonce for security
			if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
				return new WP_Error( 'invalid_nonce', __( 'Security check failed', 'mosque-timetable' ), array( 'status' => 403 ) );
			}

			$endpoint = $request->get_param( 'endpoint' );

			$subscriptions = get_option( 'mt_push_subscriptions', array() );

			if ( isset( $subscriptions[ $endpoint ] ) ) {
				unset( $subscriptions[ $endpoint ] );
				update_option( 'mt_push_subscriptions', $subscriptions );

				return rest_ensure_response(
					array(
						'success' => true,
						'message' => 'Successfully unsubscribed from prayer reminders',
					)
				);
			}

			return new WP_Error( 'subscription_not_found', 'Subscription not found', array( 'status' => 404 ) );
		}

		/**
		 * Validate push subscription data
		 */
		public function validate_push_subscription( $param ) {
			if ( ! is_array( $param ) ) {
				return false;
			}

			// Check required fields
			if ( empty( $param['endpoint'] ) || ! is_string( $param['endpoint'] ) ) {
				return false;
			}

			if ( empty( $param['keys'] ) || ! is_array( $param['keys'] ) ) {
				return false;
			}

			if ( empty( $param['keys']['p256dh'] ) || empty( $param['keys']['auth'] ) ) {
				return false;
			}

			// Validate endpoint URL
			if ( ! filter_var( $param['endpoint'], FILTER_VALIDATE_URL ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Admin footer credit
		 */
		public function admin_footer_credit( $text ) {
			$screen = get_current_screen();
			if ( strpos( $screen->base, 'mosque' ) !== false ) {
				return '<em>🕌 Mosque Prayer Timetable System v3.0 - Enhanced with <a href="https://claude.ai/code" target="_blank">Claude Code</a></em>';
			}
			return $text;
		}

		/**
		 * Frontend credit (only on shortcode pages)
		 */
		public function frontend_credit() {
			global $post;
			if ( is_object( $post ) && ( has_shortcode( $post->post_content, 'mosque_timetable' ) ||
				has_shortcode( $post->post_content, 'todays_prayers' ) ||
				has_shortcode( $post->post_content, 'prayer_countdown' ) ) ) {
				echo '<div style="text-align: center; margin: 20px 0; font-size: 0.8em; color: #666;">
                    <em>Prayer times powered by <a href="https://claude.ai/code" target="_blank" style="color: #667eea;">Claude Code</a></em>
                  </div>';
			}
		}

		/**
		 * Initialize push notifications cron job
		 */
		public function init_push_notifications_cron() {
			if ( ! wp_next_scheduled( 'mt_send_push_notifications' ) ) {
				wp_schedule_event( time(), 'mt_every_minute', 'mt_send_push_notifications' );
			}
		}

		/**
		 * Add custom cron interval for every minute
		 */
		public function add_cron_intervals( $schedules ) {
			$schedules['mt_every_minute'] = array(
				'interval' => 60,
				'display'  => __( 'Every Minute', 'mosque-timetable' ),
			);
			return $schedules;
		}

		/**
		 * Process and send push notifications
		 */
		public function process_push_notifications() {
			// Get VAPID keys
			$vapid_public  = mt_has_acf() ? get_field( 'vapid_public_key', 'option' ) : get_option( 'vapid_public_key' );
			$vapid_private = mt_has_acf() ? get_field( 'vapid_private_key', 'option' ) : get_option( 'vapid_private_key' );

			if ( empty( $vapid_public ) || empty( $vapid_private ) ) {
				return; // Push notifications not configured
			}

			// Get all subscriptions
			$subscriptions = get_option( 'mt_push_subscriptions', array() );
			if ( empty( $subscriptions ) ) {
				return; // No subscriptions
			}

			// Load web push library
			if ( ! class_exists( 'Minishlink\WebPush\WebPush' ) ) {
				return; // Library not available
			}

			$current_time = current_time( 'timestamp' );
			$today_data   = $this->get_today_prayer_data();

			if ( ! $today_data ) {
				return; // No prayer data for today
			}

			$prayer_times = array(
				'fajr'    => $today_data['fajr_start'],
				'sunrise' => $today_data['sunrise'],
				'zuhr'    => wp_date( 'w' ) === 5 ? null : $today_data['zuhr_start'], // Skip Zuhr on Friday
				'asr'     => $today_data['asr_start'],
				'maghrib' => $today_data['maghrib_start'],
				'isha'    => $today_data['isha_start'],
			);

			// Add Jummah times on Friday
			if ( wp_date( 'w' ) === 5 ) {
				if ( ! empty( $today_data['jummah_1'] ) ) {
					$prayer_times['jummah_1'] = $today_data['jummah_1'];
				}
				if ( ! empty( $today_data['jummah_2'] ) ) {
					$prayer_times['jummah_2'] = $today_data['jummah_2'];
				}
			}

			// Initialize WebPush
			try {
				$webPush = new \Minishlink\WebPush\WebPush(
					array(
						'VAPID' => array(
							'subject'    => get_home_url(),
							'publicKey'  => $vapid_public,
							'privateKey' => $vapid_private,
						),
					)
				);

				foreach ( $subscriptions as $endpoint => $subscription_data ) {
					$offsets         = $subscription_data['offsets'] ?? array();
					$sunrise_warning = $subscription_data['sunrise_warning'] ?? false;

					// Check each prayer time for notifications
					foreach ( $prayer_times as $prayer => $time_str ) {
						if ( empty( $time_str ) ) {
							continue;
						}

						$prayer_time = strtotime( $time_str );
						if ( ! $prayer_time ) {
							continue;
						}

						// Check each offset for this subscription
						foreach ( $offsets as $offset ) {
							$notification_time = $prayer_time - ( $offset * 60 );

							// Check if we should send notification now (within 1 minute window)
							if ( abs( $current_time - $notification_time ) <= 30 ) {
								$this->send_prayer_notification( $webPush, $subscription_data, $prayer, $time_str, $offset );
							}
						}

						// Handle sunrise warning
						if ( $sunrise_warning && 'sunrise' === $prayer ) {
							$warning_offset = mt_has_acf() ? get_field( 'sunrise_warning_offset', 'option' ) : get_option( 'sunrise_warning_offset', 30 );
							$warning_time   = $prayer_time - ( $warning_offset * 60 );

							if ( abs( $current_time - $warning_time ) <= 30 ) {
								$this->send_sunrise_warning( $webPush, $subscription_data, $time_str, $warning_offset );
							}
						}
					}
				}

				// Send all queued notifications
				foreach ( $webPush->flush() as $report ) {
					$endpoint = $report->getRequest()->getUri()->__toString();
					if ( ! $report->isSuccess() ) {
						// Remove failed subscriptions (expired/invalid)
						$error = $report->getReason();
						if ( strpos( $error, '410' ) !== false || strpos( $error, '404' ) !== false ) {
							$this->remove_invalid_subscription( $endpoint );
						}
					}
				}
			} catch ( Exception $e ) {
				// Log error but continue
				error_log( 'Push notification error: ' . $e->getMessage() );
			}
		}

		/**
		 * Send prayer notification
		 */
		private function send_prayer_notification( $webPush, $subscription_data, $prayer, $time_str, $offset ) {
			$prayer_name = ucfirst( $prayer );
			if ( 'jummah_1' === $prayer ) {
				$prayer_name = 'Jummah 1';
			}
			if ( 'jummah_2' === $prayer ) {
				$prayer_name = 'Jummah 2';
			}

			$formatted_time = wp_date( 'g:i A', strtotime( $time_str ) );
			$title          = mt_apply_terminology( $prayer_name . ' in ' . $offset . ' minutes' );
			$body           = mt_apply_terminology( 'Prayer time: ' . $formatted_time );

			$payload = wp_json_encode(
				array(
					'title'              => $title,
					'body'               => $body,
					'icon'               => MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/icon-192.png',
					'badge'              => MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/icon-192.png',
					'tag'                => 'prayer-' . $prayer,
					'requireInteraction' => false,
					'data'               => array(
						'prayer' => $prayer,
						'time'   => $time_str,
						'offset' => $offset,
						'url'    => home_url( '/prayer-times' ),
					),
				)
			);

			$webPush->queueNotification(
				\Minishlink\WebPush\Subscription::create(
					array(
						'endpoint' => $subscription_data['endpoint'],
						'keys'     => $subscription_data['keys'],
					)
				),
				$payload
			);
		}

		/**
		 * Send sunrise warning notification
		 */
		private function send_sunrise_warning( $webPush, $subscription_data, $time_str, $offset ) {
			$formatted_time = wp_date( 'g:i A', strtotime( $time_str ) );
			$title          = mt_apply_terminology( 'End of Fajr in ' . $offset . ' minutes' );
			$body           = mt_apply_terminology( 'Sunrise: ' . $formatted_time );

			$payload = wp_json_encode(
				array(
					'title'              => $title,
					'body'               => $body,
					'icon'               => MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/icon-192.png',
					'badge'              => MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/icon-192.png',
					'tag'                => 'sunrise-warning',
					'requireInteraction' => false,
					'data'               => array(
						'type'   => 'sunrise_warning',
						'time'   => $time_str,
						'offset' => $offset,
						'url'    => home_url( '/prayer-times' ),
					),
				)
			);

			$webPush->queueNotification(
				\Minishlink\WebPush\Subscription::create(
					array(
						'endpoint' => $subscription_data['endpoint'],
						'keys'     => $subscription_data['keys'],
					)
				),
				$payload
			);
		}

		/**
		 * Remove invalid subscription
		 */
		private function remove_invalid_subscription( $endpoint ) {
			$subscriptions = get_option( 'mt_push_subscriptions', array() );
			if ( isset( $subscriptions[ $endpoint ] ) ) {
				unset( $subscriptions[ $endpoint ] );
				update_option( 'mt_push_subscriptions', $subscriptions );
			}
		}

		/**
		 * Add PWA meta tags
		 */
		public function add_pwa_meta_tags() {
			if ( ! get_field( 'enable_pwa', 'option' ) ) {
				return;
			}

			echo '<link rel="manifest" href="' . esc_url( plugins_url( 'assets/manifest.json', __FILE__ ) ) . '">'; // Escape output
			echo '<meta name="theme-color" content="' . esc_attr( get_field( 'mt_btn_bg', 'option' ) ) . '">'; // Escape output
			echo '<meta name="apple-mobile-web-app-capable" content="yes">';
			echo '<meta name="apple-mobile-web-app-status-bar-style" content="black">';
		}

		/**
		 * Add PWA CTA buttons to frontend
		 */
		public function add_pwa_cta_buttons() {
			if ( ! get_field( 'enable_pwa', 'option' ) ) {
				return;
			}

			// Don't show on admin pages
			if ( is_admin() ) {
				return;
			}

			// Only show on pages with mosque timetable content
			global $post;
			if ( ! $post || ( ! has_shortcode( $post->post_content, 'mosque_timetable' ) &&
				! has_shortcode( $post->post_content, 'todays_prayers' ) &&
				! has_shortcode( $post->post_content, 'prayer_countdown' ) ) ) {
				return;
			}

			// Enhanced PWA install prompt
			?>
			<style>
				.mosque-pwa-banner {
					position: fixed;
					bottom: 20px;
					left: 50%;
					transform: translateX(-50%);
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					color: white;
					padding: 16px 20px;
					border-radius: 16px;
					box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
					display: none;
					align-items: center;
					gap: 12px;
					max-width: 90vw;
					z-index: 999999;
					backdrop-filter: blur(10px);
					border: 1px solid rgba(255, 255, 255, 0.2);
				}

				.mosque-pwa-banner.show {
					display: flex;
					animation: slideUpFade 0.5s ease-out;
				}

				@keyframes slideUpFade {
					from {
						opacity: 0;
						transform: translateX(-50%) translateY(20px);
					}

					to {
						opacity: 1;
						transform: translateX(-50%) translateY(0);
					}
				}

				.mosque-pwa-banner .icon {
					font-size: 24px;
				}

				.mosque-pwa-banner .content {
					flex: 1;
				}

				.mosque-pwa-banner .title {
					font-weight: 600;
					margin-bottom: 4px;
				}

				.mosque-pwa-banner .subtitle {
					font-size: 14px;
					opacity: 0.9;
				}

				.mosque-pwa-banner .actions {
					display: flex;
					gap: 8px;
				}

				.mosque-pwa-banner button {
					background: rgba(255, 255, 255, 0.2);
					color: white;
					border: 1px solid rgba(255, 255, 255, 0.3);
					padding: 8px 16px;
					border-radius: 8px;
					font-size: 14px;
					font-weight: 600;
					cursor: pointer;
					transition: all 0.2s ease;
				}

				.mosque-pwa-banner button:hover {
					background: rgba(255, 255, 255, 0.3);
				}

				.mosque-pwa-banner button.primary {
					background: white;
					color: #667eea;
				}

				.mosque-pwa-banner button.primary:hover {
					background: #f8fafc;
				}

				@media (max-width: 480px) {
					.mosque-pwa-banner {
						bottom: 10px;
						left: 10px;
						right: 10px;
						transform: none;
						max-width: none;
						flex-direction: column;
						text-align: center;
					}

					.mosque-pwa-banner .actions {
						width: 100%;
						justify-content: space-between;
					}

					.mosque-pwa-banner button {
						flex: 1;
					}
				}
			</style>

			<script>
				document.addEventListener('DOMContentLoaded', function() {
					// Check if app is already installed
					if (window.matchMedia('(display-mode: standalone)').matches ||
						window.navigator.standalone === true) {
						return; // Don't show banner if already installed
					}

					let deferredPrompt;

					// Listen for beforeinstallprompt event
					window.addEventListener('beforeinstallprompt', function(e) {
						e.preventDefault();
						deferredPrompt = e;
						showPWABanner();
					});

					function showPWABanner() {
						const banner = document.createElement('div');
						banner.className = 'mosque-pwa-banner';
						banner.innerHTML = `
						<div class="icon">🕌</div>
						<div class="content">
							<div class="title">Install Prayer Times App</div>
							<div class="subtitle">Get quick access to prayer times offline</div>
						</div>
						<div class="actions">
							<button type="button" class="dismiss">Later</button>
							<button type="button" class="primary install">Install</button>
						</div>
					`;

						document.body.appendChild(banner);

						// Show banner with animation
						setTimeout(() => banner.classList.add('show'), 100);

						// Handle install button
						banner.querySelector('.install').addEventListener('click', function() {
							if (deferredPrompt) {
								deferredPrompt.prompt();
								deferredPrompt.userChoice.then(function(choiceResult) {
									if (choiceResult.outcome === 'accepted') {
										console.log('PWA installed');
									}
									deferredPrompt = null;
									banner.remove();
								});
							}
						});

						// Handle dismiss button
						banner.querySelector('.dismiss').addEventListener('click', function() {
							banner.remove();
							// Don't show again for 24 hours
							localStorage.setItem('pwa-banner-dismissed', Date.now());
						});

						// Auto-hide after 10 seconds
						setTimeout(function() {
							if (banner.parentNode) {
								banner.remove();
							}
						}, 10000);
					}

					// Check if banner was recently dismissed
					const dismissed = localStorage.getItem('pwa-banner-dismissed');
					if (dismissed && (Date.now() - parseInt(dismissed)) < 24 * 60 * 60 * 1000) {
						return; // Don't show if dismissed within 24 hours
					}

					// Fallback: show banner after 3 seconds if no install prompt
					setTimeout(function() {
						if (!deferredPrompt && !document.querySelector('.mosque-pwa-banner')) {
							// Create a simpler banner for browsers that don't support beforeinstallprompt
							const fallbackBanner = document.createElement('div');
							fallbackBanner.className = 'mosque-pwa-banner';
							fallbackBanner.innerHTML = `
							<div class="icon">🕌</div>
							<div class="content">
								<div class="title">Add to Home Screen</div>
								<div class="subtitle">Access prayer times quickly from your home screen</div>
							</div>
							<div class="actions">
								<button type="button" class="dismiss">Close</button>
								<button type="button" class="primary today">View Today</button>
							</div>
						`;

							document.body.appendChild(fallbackBanner);
							setTimeout(() => fallbackBanner.classList.add('show'), 100);

							// Handle today button
							fallbackBanner.querySelector('.today').addEventListener('click', function() {
								window.location.href = '/today';
							});

							// Handle dismiss
							fallbackBanner.querySelector('.dismiss').addEventListener('click', function() {
								fallbackBanner.remove();
								localStorage.setItem('pwa-banner-dismissed', Date.now());
							});

							// Auto-hide after 8 seconds
							setTimeout(function() {
								if (fallbackBanner.parentNode) {
									fallbackBanner.remove();
								}
							}, 8000);
						}
					}, 3000);
				});
			</script>
			<?php
		}

		/**
		 * Add structured data
		 */
		public function add_structured_data() {
			// Only add on pages that contain prayer timetable shortcodes
			global $post;

			if ( ! is_singular() || ! $post ) {
				return;
			}

			$content       = $post->post_content;
			$has_shortcode = (
				has_shortcode( $content, 'mosque_timetable' ) ||
				has_shortcode( $content, 'todays_prayers' ) ||
				has_shortcode( $content, 'prayer_countdown' )
			);

			if ( ! $has_shortcode ) {
				return;
			}

			// Get mosque settings
			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';

			// Generate enhanced AI-readable structured data
			$structured_data = array();

			// Add comprehensive prayer time data for AI
			$ai_prayer_data = $this->generate_ai_readable_prayer_data();
			if ( $ai_prayer_data ) {
				$structured_data[] = $ai_prayer_data;
			}

			// Mosque Organization Schema
			$mosque_schema = array(
				'@context'       => 'https://schema.org',
				'@type'          => 'Place',
				'@id'            => get_site_url() . '#mosque',
				'name'           => $mosque_name,
				'description'    => 'Islamic place of worship providing daily prayer times and religious services',
				'url'            => get_site_url(),
				'additionalType' => 'https://schema.org/PlaceOfWorship',
				'amenityFeature' => array(
					array(
						'@type' => 'LocationFeatureSpecification',
						'name'  => 'Prayer Times',
						'value' => 'Daily prayer schedule available',
					),
					array(
						'@type' => 'LocationFeatureSpecification',
						'name'  => 'Religious Services',
						'value' => 'Islamic worship services',
					),
				),
			);

			if ( $mosque_address ) {
				$mosque_schema['address'] = array(
					'@type'         => 'PostalAddress',
					'streetAddress' => $mosque_address,
				);
			}

			$structured_data[] = $mosque_schema;

			// Organization Schema for the mosque
			$organization_schema = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'Organization',
				'@id'             => get_site_url() . '#organization',
				'name'            => $mosque_name,
				'url'             => get_site_url(),
				'sameAs'          => array( get_site_url() ),
				'description'     => 'Islamic religious organization providing prayer services and community support',
				'hasOfferCatalog' => array(
					'@type'           => 'OfferCatalog',
					'name'            => 'Religious Services',
					'itemListElement' => array(
						array(
							'@type'       => 'Offer',
							'name'        => 'Daily Prayers',
							'description' => 'Five daily Islamic prayers with congregation times',
						),
						array(
							'@type'       => 'Offer',
							'name'        => 'Friday Prayers (Jummah)',
							'description' => 'Weekly congregational prayers on Friday',
						),
					),
				),
			);

			if ( $mosque_address ) {
				$organization_schema['address'] = array(
					'@type'         => 'PostalAddress',
					'streetAddress' => $mosque_address,
				);
			}

			$structured_data[] = $organization_schema;

			// WebSite Schema with SearchAction
			$website_schema = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'WebSite',
				'@id'             => get_site_url() . '#website',
				'name'            => $mosque_name . ' - Prayer Times',
				'url'             => get_site_url(),
				'description'     => 'Official prayer timetable and religious services information for ' . $mosque_name,
				'publisher'       => array(
					'@id' => get_site_url() . '#organization',
				),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => get_site_url() . '/prayer-times/{search_term_string}',
					),
					'query-input' => 'required name=search_term_string',
				),
			);

			$structured_data[] = $website_schema;

			// Dataset Schema for prayer times data
			$year           = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
			$dataset_schema = array(
				'@context'         => 'https://schema.org',
				'@type'            => 'Dataset',
				'@id'              => get_site_url() . '/prayer-times/#dataset',
				'name'             => $mosque_name . ' Prayer Times Dataset',
				'description'      => 'Comprehensive dataset of Islamic prayer times including daily prayers, Jummah times, and Hijri calendar dates',
				'url'              => get_site_url() . '/prayer-times/',
				'keywords'         => 'prayer times, Islamic prayers, mosque schedule, Fajr, Zuhr, Asr, Maghrib, Isha, Jummah, Hijri calendar',
				'creator'          => array( '@id' => get_site_url() . '#organization' ),
				'publisher'        => array( '@id' => get_site_url() . '#organization' ),
				'dateModified'     => wp_date( 'c' ),
				'license'          => 'https://creativecommons.org/licenses/by/4.0/',
				'distribution'     => array(
					array(
						'@type'          => 'DataDownload',
						'encodingFormat' => 'text/calendar',
						'contentUrl'     => get_site_url() . '/prayer-times/calendar.ics',
						'name'           => 'ICS Calendar Format',
					),
					array(
						'@type'          => 'DataDownload',
						'encodingFormat' => 'application/json',
						'contentUrl'     => get_site_url() . '/wp-json/mosque/v1/prayer-times/' . $year,
						'name'           => 'JSON API Format',
					),
				),
				'spatialCoverage'  => array(
					'@type' => 'Place',
					'name'  => $mosque_name,
				),
				'temporalCoverage' => $year,
			);

			if ( $mosque_address ) {
				$dataset_schema['spatialCoverage']['address'] = array(
					'@type'         => 'PostalAddress',
					'streetAddress' => $mosque_address,
				);
			}

			$structured_data[] = $dataset_schema;

			// Today's Prayer Times Schema (if shortcode present)
			if ( has_shortcode( $content, 'todays_prayers' ) || has_shortcode( $content, 'mosque_timetable' ) ) {
				$today_data = $this->get_today_prayer_data();

				if ( $today_data ) {
					$prayer_events   = $this->generate_prayer_events_schema( $today_data, $mosque_name, $mosque_address );
					$structured_data = array_merge( $structured_data, $prayer_events );
				}
			}

			// FAQ Schema for common questions
			$faq_schema = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => array(
					array(
						'@type'          => 'Question',
						'name'           => 'What are the prayer times at ' . $mosque_name . '?',
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => 'Prayer times are updated daily and include Fajr, Sunrise, Zuhr, Asr, Maghrib, and Isha prayers. Friday prayers (Jummah) times are also available.',
						),
					),
					array(
						'@type'          => 'Question',
						'name'           => 'How often are prayer times updated?',
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => 'Prayer times are updated monthly to ensure accuracy throughout the year.',
						),
					),
					array(
						'@type'          => 'Question',
						'name'           => 'Can I get notifications for prayer times?',
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => 'Yes, you can install our web app and enable notifications to receive prayer time reminders.',
						),
					),
				),
			);

			$structured_data[] = $faq_schema;

			// Website Schema
			$website_schema = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'WebSite',
				'@id'             => get_site_url() . '#website',
				'name'            => get_bloginfo( 'name' ),
				'description'     => get_bloginfo( 'description' ),
				'url'             => get_site_url(),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => get_site_url() . '?s={search_term_string}',
					),
					'query-input' => 'required name=search_term_string',
				),
			);

			$structured_data[] = $website_schema;

			// Output structured data
			foreach ( $structured_data as $data ) {
				echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
			}

			// Add Open Graph tags
			echo '<meta property="og:title" content="' . esc_attr( $mosque_name . ' - Prayer Times' ) . '">' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( 'Daily prayer times and Islamic services at ' . $mosque_name ) . '">' . "\n";
			echo '<meta property="og:type" content="website">' . "\n";
			echo '<meta property="og:url" content="' . esc_attr( get_permalink() ) . '">' . "\n";
			echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";

			// Add Twitter Card tags
			echo '<meta name="twitter:card" content="summary">' . "\n";
			echo '<meta name="twitter:title" content="' . esc_attr( $mosque_name . ' - Prayer Times' ) . '">' . "\n";
			echo '<meta name="twitter:description" content="' . esc_attr( 'Daily prayer times and Islamic services at ' . $mosque_name ) . '">' . "\n";
		}

		/**
		 * Generate AI-readable prayer time data
		 */
		private function generate_ai_readable_prayer_data() {
			$today = wp_date( 'Y-m-d' );
			$month = wp_date( 'n' );
			$year  = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );

			$prayer_data = $this->get_month_prayer_data( (int) $year, (int) $month );
			if ( ! $prayer_data || empty( $prayer_data['days'] ) ) {
				return null;
			}

			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';
			$timezone       = wp_timezone_string();

			// Create comprehensive AI-readable schema
			$ai_schema = array(
				'@context'         => 'https://schema.org',
				'@type'            => 'Dataset',
				'name'             => $mosque_name . ' Prayer Times Dataset',
				'description'      => 'Comprehensive Islamic prayer times including daily and Jummah prayers with Hijri calendar dates',
				'keywords'         => array(
					'prayer times',
					'Islamic prayers',
					'mosque schedule',
					'Fajr',
					'Zuhr',
					'Asr',
					'Maghrib',
					'Isha',
					'Jummah',
					'Friday prayers',
					'Hijri calendar',
					'Muslim worship',
				),
				'creator'          => array(
					'@type'   => 'Organization',
					'name'    => $mosque_name,
					'address' => $mosque_address,
				),
				'dateModified'     => gmdate( 'c' ),
				'license'          => 'https://creativecommons.org/licenses/by/4.0/',
				'distribution'     => array(
					'@type'          => 'DataDownload',
					'encodingFormat' => 'application/ld+json',
					'contentUrl'     => get_site_url() . '/wp-json/mosque/v1/prayer-times/' . $year . '/' . $month,
				),
				'temporalCoverage' => $year . '-' . sprintf( '%02d', $month ),
				'spatialCoverage'  => array(
					'@type'   => 'Place',
					'name'    => $mosque_name,
					'address' => $mosque_address,
				),
				'variableMeasured' => array(
					array(
						'@type'       => 'PropertyValue',
						'name'        => 'Fajr Prayer Time',
						'description' => 'Dawn prayer - first prayer of the day',
						'unitCode'    => 'H14', // Time format code
					),
					array(
						'@type'       => 'PropertyValue',
						'name'        => 'Sunrise Time',
						'description' => 'Sunrise - end of Fajr prayer time',
					),
					array(
						'@type'       => 'PropertyValue',
						'name'        => 'Zuhr Prayer Time',
						'description' => 'Noon prayer - second prayer of the day',
					),
					array(
						'@type'       => 'PropertyValue',
						'name'        => 'Asr Prayer Time',
						'description' => 'Afternoon prayer - third prayer of the day',
					),
					array(
						'@type'       => 'PropertyValue',
						'name'        => 'Maghrib Prayer Time',
						'description' => 'Sunset prayer - fourth prayer of the day',
					),
					array(
						'@type'       => 'PropertyValue',
						'name'        => 'Isha Prayer Time',
						'description' => 'Night prayer - fifth and final prayer of the day',
					),
					array(
						'@type'       => 'PropertyValue',
						'name'        => 'Jummah Prayer Time',
						'description' => 'Friday congregational prayer (replaces Zuhr on Fridays)',
					),
					array(
						'@type'       => 'PropertyValue',
						'name'        => 'Hijri Date',
						'description' => 'Islamic lunar calendar date corresponding to Gregorian date',
					),
				),
				'mainEntity'       => array(),
			);

			// Add structured prayer time entries
			foreach ( $prayer_data['days'] as $day ) {
				if ( empty( $day['date_full'] ) ) {
					continue;
				}

				$date        = $day['date_full'];
				$hijri_date  = ! empty( $day['hijri_date'] ) ? $day['hijri_date'] : $this->calculate_hijri_date( $date );
				$day_of_week = wp_date( 'l', strtotime( $date ) );
				$is_friday   = ( wp_date( 'w', strtotime( $date ) ) === 5 );

				$prayer_entry = array(
					'@type'              => 'StructuredValue',
					'name'               => 'Prayer Times for ' . wp_date( 'F j, Y', strtotime( $date ) ),
					'description'        => 'Complete prayer schedule with both start times and congregation (Jamaat) times',
					'additionalProperty' => array(
						array(
							'@type'       => 'PropertyValue',
							'name'        => 'date_gregorian',
							'value'       => $date,
							'description' => 'Gregorian calendar date in YYYY-MM-DD format',
						),
						array(
							'@type'       => 'PropertyValue',
							'name'        => 'date_hijri',
							'value'       => $hijri_date,
							'description' => 'Islamic Hijri calendar date',
						),
						array(
							'@type'       => 'PropertyValue',
							'name'        => 'day_of_week',
							'value'       => $day_of_week,
							'description' => 'Day of the week',
						),
						array(
							'@type'       => 'PropertyValue',
							'name'        => 'timezone',
							'value'       => $timezone,
							'description' => 'Local timezone for all prayer times',
						),
					),
					'value'              => array(
						'fajr_start'     => ! empty( $day['fajr_start'] ) ? $day['fajr_start'] : null,
						'fajr_jamaat'    => ! empty( $day['fajr_jamaat'] ) ? $day['fajr_jamaat'] : null,
						'sunrise'        => ! empty( $day['sunrise'] ) ? $day['sunrise'] : null,
						'zuhr_start'     => ! empty( $day['zuhr_start'] ) ? $day['zuhr_start'] : null,
						'zuhr_jamaat'    => ! empty( $day['zuhr_jamaat'] ) ? $day['zuhr_jamaat'] : null,
						'asr_start'      => ! empty( $day['asr_start'] ) ? $day['asr_start'] : null,
						'asr_jamaat'     => ! empty( $day['asr_jamaat'] ) ? $day['asr_jamaat'] : null,
						'maghrib_start'  => ! empty( $day['maghrib_start'] ) ? $day['maghrib_start'] : null,
						'maghrib_jamaat' => ! empty( $day['maghrib_jamaat'] ) ? $day['maghrib_jamaat'] : null,
						'isha_start'     => ! empty( $day['isha_start'] ) ? $day['isha_start'] : null,
						'isha_jamaat'    => ! empty( $day['isha_jamaat'] ) ? $day['isha_jamaat'] : null,
						'jummah_1'       => ( $is_friday && ! empty( $day['jummah_1'] ) ) ? $day['jummah_1'] : null,
						'jummah_2'       => ( $is_friday && ! empty( $day['jummah_2'] ) ) ? $day['jummah_2'] : null,
						'is_friday'      => $is_friday,
					),
				);

				$ai_schema['mainEntity'][] = $prayer_entry;
			}

			return $ai_schema;
		}

		/**
		 * Add rewrite rules for SEO URLs
		 */
		public function add_rewrite_rules() {
			// Year and month specific pages
			add_rewrite_rule(
				'prayer-times/([0-9]{4})/([^/]+)/?$',
				'index.php?mosque_year=$matches[1]&mosque_month=$matches[2]',
				'top'
			);
			// Year archive pages
			add_rewrite_rule(
				'prayer-times/([0-9]{4})/?$',
				'index.php?mosque_year_archive=$matches[1]',
				'top'
			);
			// Main prayer times archive
			add_rewrite_rule(
				'prayer-times/?$',
				'index.php?mosque_archive=1',
				'top'
			);
			add_rewrite_rule(
				'prayer-times/calendar\.ics$',
				'index.php?mosque_calendar=ics',
				'top'
			);
			add_rewrite_rule(
				'prayer-times-sitemap\.xml$',
				'index.php?mosque_sitemap=xml',
				'top'
			);
			add_rewrite_rule(
				'llms\.txt$',
				'index.php?llms_txt=1',
				'top'
			);
			add_rewrite_rule(
				'today/?$',
				'index.php?mosque_today=1',
				'top'
			);
			add_rewrite_tag( '%mosque_year%', '([0-9]{4})' );
			add_rewrite_tag( '%mosque_month%', '([^&]+)' );
			add_rewrite_tag( '%mosque_calendar%', '([^&]+)' );
			add_rewrite_tag( '%mosque_sitemap%', '([^&]+)' );
			add_rewrite_tag( '%llms_txt%', '([^&]+)' );
			add_rewrite_tag( '%mosque_today%', '([^&]+)' );
			add_rewrite_tag( '%mosque_year_archive%', '([0-9]{4})' );
			add_rewrite_tag( '%mosque_archive%', '([^&]+)' );
		}

		/**
		 * Handle virtual pages
		 */
		public function handle_virtual_pages() {
			global $wp_query;

			// Handle calendar.ics requests
			if ( get_query_var( 'mosque_calendar' ) === 'ics' ) {
				$this->serve_ics_calendar();
				exit;
			}

			// Handle prayer times sitemap
			if ( get_query_var( 'mosque_sitemap' ) === 'xml' ) {
				$this->serve_prayer_times_sitemap();
				exit;
			}

			// Handle llms.txt requests
			if ( get_query_var( 'llms_txt' ) === '1' ) {
				$this->serve_llms_txt();
				exit;
			}

			// Handle /today page requests
			if ( get_query_var( 'mosque_today' ) === '1' ) {
				$this->serve_today_page();
				exit;
			}

			// Handle prayer times archive requests
			if ( get_query_var( 'mosque_archive' ) === '1' ) {
				$this->serve_prayer_times_archive();
				exit;
			}

			// Handle year archive requests
			$year_archive = get_query_var( 'mosque_year_archive' );
			if ( $year_archive ) {
				$this->serve_year_archive_page( (int) $year_archive );
				exit;
			}

			// Handle other virtual pages (existing functionality)
			// Virtual page handling for prayer-times pages will be implemented in next phase
		}

		/**
		 * Serve ICS calendar file
		 */
		private function serve_ics_calendar() {
			// Set proper headers
			header( 'Content-Type: text/calendar; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="mosque-prayer-times.ics"' );
			header( 'Cache-Control: no-cache, must-revalidate' );

			// Get mosque details
			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';

			// Start ICS content
			echo "BEGIN:VCALENDAR\r\n";
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format constants
			echo "VERSION:2.0\r\n";
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format constants
			echo "PRODID:-//Mosque Timetable System//Prayer Times//EN\r\n";
			echo "CALSCALE:GREGORIAN\r\n";
			echo "METHOD:PUBLISH\r\n";
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo 'X-WR-CALNAME:' . $this->ics_escape( $mosque_name . ' Prayer Times' ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo 'X-WR-CALDESC:' . $this->ics_escape( 'Prayer times for ' . $mosque_name . ( $mosque_address ? ', ' . $mosque_address : '' ) ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			$timezone = wp_timezone_string();
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo 'X-WR-TIMEZONE:' . $timezone . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545

			// Get all available prayer data
			$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
			$available_months = get_field( 'available_months', 'option' ) ?: array();

			// If no specific months are set, include all 12 months
			if ( empty( $available_months ) ) {
				$available_months = range( 1, 12 );
			}

			foreach ( $available_months as $month ) {
				$prayer_data = $this->get_month_prayer_data( $default_year, $month );

				if ( $prayer_data && ! empty( $prayer_data['days'] ) ) {
					foreach ( $prayer_data['days'] as $day ) {
						$date      = new DateTime( $day['date_full'] );
						$is_friday = $date->format( 'N' ) === 5;

						// Create events for each prayer time
						$prayers = array(
							'Fajr Start'     => $day['fajr_start'],
							'Fajr Jamaat'    => $day['fajr_jamaat'],
							'Sunrise'        => $day['sunrise'],
							'Zuhr Start'     => $is_friday ? null : $day['zuhr_start'],
							'Zuhr Jamaat'    => $is_friday ? null : $day['zuhr_jamaat'],
							'Jummah 1'       => $is_friday ? $day['jummah_1'] : null,
							'Jummah 2'       => $is_friday ? $day['jummah_2'] : null,
							'Asr Start'      => $day['asr_start'],
							'Asr Jamaat'     => $day['asr_jamaat'],
							'Maghrib Start'  => $day['maghrib_start'],
							'Maghrib Jamaat' => $day['maghrib_jamaat'],
							'Isha Start'     => $day['isha_start'],
							'Isha Jamaat'    => $day['isha_jamaat'],
						);

						foreach ( $prayers as $prayer_name => $prayer_time ) {
							if ( ! empty( $prayer_time ) ) {
								$this->add_prayer_event( $date, $prayer_name, $prayer_time, $mosque_name, $mosque_address, $timezone );
							}
						}
					}
				}
			}

			echo "END:VCALENDAR\r\n";
		}

		/**
		 * Serve prayer times sitemap XML
		 */
		private function serve_prayer_times_sitemap() {
			// Set proper headers
			header( 'Content-Type: application/xml; charset=utf-8' );
			header( 'Cache-Control: public, max-age=3600' ); // Cache for 1 hour

			$site_url         = get_site_url();
			$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
			$available_months = get_field( 'available_months', 'option' ) ?: range( 1, 12 );

			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

			// Add entry for each available month
			foreach ( $available_months as $month ) {
				$month_num  = intval( $month );
				$month_name = wp_date( 'F', mktime( 0, 0, 0, $month_num, 1 ) );
				$url        = $site_url . '/prayer-times/' . $default_year . '/' . $month_num;
				$lastmod    = wp_date( 'Y-m-d\TH:i:s+00:00' ); // Use current time as modification date

				echo "\t<url>\n";
				echo "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
				echo "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
				echo "\t\t<changefreq>weekly</changefreq>\n";
				echo "\t\t<priority>0.8</priority>\n";
				echo "\t</url>\n";
			}

			// Add main prayer times archive page
			echo "\t<url>\n";
			echo "\t\t<loc>" . esc_url( $site_url . '/prayer-times/' ) . "</loc>\n";
			echo "\t\t<lastmod>" . esc_html( wp_date( 'Y-m-d\TH:i:s+00:00' ) ) . "</lastmod>\n";
			echo "\t\t<changefreq>daily</changefreq>\n";
			echo "\t\t<priority>1.0</priority>\n";
			echo "\t</url>\n";

			// Add available year archive pages
			$available_years = $this->get_available_years();
			foreach ( $available_years as $year ) {
				echo "\t<url>\n";
				echo "\t\t<loc>" . esc_url( $site_url . '/prayer-times/' . $year . '/' ) . "</loc>\n";
				echo "\t\t<lastmod>" . esc_html( wp_date( 'Y-m-d\TH:i:s+00:00' ) ) . "</lastmod>\n";
				echo "\t\t<changefreq>weekly</changefreq>\n";
				echo "\t\t<priority>0.9</priority>\n";
				echo "\t</url>\n";
			}

			// Add today page
			echo "\t<url>\n";
			echo "\t\t<loc>" . esc_url( $site_url . '/today' ) . "</loc>\n";
			echo "\t\t<lastmod>" . esc_html( wp_date( 'Y-m-d\TH:i:s+00:00' ) ) . "</lastmod>\n";
			echo "\t\t<changefreq>daily</changefreq>\n";
			echo "\t\t<priority>0.9</priority>\n";
			echo "\t</url>\n";

			echo '</urlset>' . "\n";
		}

		/**
		 * Serve llms.txt file
		 */
		private function serve_llms_txt() {
			// Set proper headers
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'Cache-Control: public, max-age=86400' ); // Cache for 24 hours

			$site_url       = get_site_url();
			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';
			$admin_email    = get_option( 'admin_email' );

			echo '# LLMs.txt - Machine-readable metadata for ' . esc_html( $mosque_name ) . "\n\n"; // Escape output
			echo "## Source of Truth\n";
			echo 'This file provides metadata about the prayer timetable system for ' . esc_html( $mosque_name ) . ".\n"; // Escape output
			echo "The data is maintained by mosque administrators and updated regularly.\n\n";

			echo "## Organization\n";
			echo 'Name: ' . esc_html( $mosque_name ) . "\n"; // Escape output
			if ( $mosque_address ) {
				echo 'Address: ' . esc_html( $mosque_address ) . "\n"; // Escape output
			}
			echo 'Website: ' . esc_url( $site_url ) . "\n\n"; // Escape output

			echo "## API Endpoints\n";
			echo 'REST API Base: ' . esc_url( $site_url ) . "/wp-json/mosque/v1/\n"; // Escape output
			echo 'Prayer Times ICS: ' . esc_url( $site_url ) . "/prayer-times/calendar.ics\n"; // Escape output
			echo 'Prayer Times Sitemap: ' . esc_url( $site_url ) . "/prayer-times-sitemap.xml\n\n"; // Escape output

			echo "## Data Format\n";
			echo "Prayer times are available in multiple formats:\n";
			echo "- Human-readable web pages\n";
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo "- ICS/iCal calendar format\n";
			echo "- JSON via REST API\n";
			echo "- CSV export (admin only)\n\n";

			echo "## License\n";
			echo "Prayer time data is provided for community use.\n";
			echo "Please contact the mosque for any commercial usage.\n\n";

			echo "## Contact\n";
			echo 'Technical inquiries: ' . esc_html( $admin_email ) . "\n"; // Escape output
			echo 'Generated by: Mosque Timetable Plugin v' . esc_html( MOSQUE_TIMETABLE_VERSION ) . "\n"; // Escape output
			echo 'Last updated: ' . esc_html( wp_date( 'Y-m-d H:i:s T' ) ) . "\n"; // Escape output
		}

		/**
		 * Serve dedicated /today page
		 */
		private function serve_today_page() {
			// Set proper headers
			header( 'Content-Type: text/html; charset=utf-8' );

			// Get today's prayer data
			$today = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );
			$month = (int) $today->format( 'n' );
			$year  = (int) $today->format( 'Y' );
			$day   = (int) $today->format( 'j' );

			$prayer_data   = $this->get_month_prayer_data( $year, $month );
			$today_prayers = isset( $prayer_data[ $day ] ) ? $prayer_data[ $day ] : null;

			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';

			// Get terminology overrides
			$terminology = $this->get_terminology_overrides();

			// Apply terminology overrides to prayer names
			$prayer_labels = array(
				'fajr'    => isset( $terminology['Fajr'] ) ? $terminology['Fajr'] : 'Fajr',
				'sunrise' => isset( $terminology['Sunrise'] ) ? $terminology['Sunrise'] : 'Sunrise',
				'zuhr'    => isset( $terminology['Zuhr'] ) ? $terminology['Zuhr'] : 'Zuhr',
				'asr'     => isset( $terminology['Asr'] ) ? $terminology['Asr'] : 'Asr',
				'maghrib' => isset( $terminology['Maghrib'] ) ? $terminology['Maghrib'] : 'Maghrib',
				'isha'    => isset( $terminology['Isha'] ) ? $terminology['Isha'] : 'Isha',
			);

			$page_title  = $terminology['Mosque'] ?? 'Mosque';
			$today_label = $terminology['Today'] ?? 'Today';

			// Get Hijri date
			$hijri_date = $this->get_hijri_date( $today );

			// Calculate next prayer
			$next_prayer      = null;
			$next_prayer_time = null;
			$countdown_data   = null;

			if ( $today_prayers ) {
				$current_time = $today->format( 'H:i' );
				$prayers      = array(
					'fajr'    => $today_prayers['fajr_start'],
					'sunrise' => $today_prayers['sunrise'],
					'zuhr'    => $today_prayers['zuhr_start'],
					'asr'     => $today_prayers['asr_start'],
					'maghrib' => $today_prayers['maghrib_start'],
					'isha'    => $today_prayers['isha_start'],
				);

				foreach ( $prayers as $prayer => $time ) {
					if ( $time && $time > $current_time ) {
						$next_prayer      = $prayer;
						$next_prayer_time = $time;
						break;
					}
				}

				// If no prayer found today, get tomorrow's Fajr
				if ( ! $next_prayer ) {
					$tomorrow = clone $today;
					$tomorrow->modify( '+1 day' );
					$tomorrow_month = (int) $tomorrow->format( 'n' );
					$tomorrow_year  = (int) $tomorrow->format( 'Y' );
					$tomorrow_day   = (int) $tomorrow->format( 'j' );

					$tomorrow_data = $this->get_month_prayer_data( $tomorrow_year, $tomorrow_month );
					if ( isset( $tomorrow_data[ $tomorrow_day ]['fajr_start'] ) ) {
						$next_prayer      = 'fajr';
						$next_prayer_time = $tomorrow_data[ $tomorrow_day ]['fajr_start'];
						$countdown_data   = array(
							'is_tomorrow' => true,
							'date'        => $tomorrow->format( 'Y-m-d' ),
							'time'        => $next_prayer_time,
						);
					}
				} else {
					$countdown_data = array(
						'is_tomorrow' => false,
						'date'        => $today->format( 'Y-m-d' ),
						'time'        => $next_prayer_time,
					);
				}
			}

			?>
			<!DOCTYPE html>
			<html lang="en">

			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title><?php echo esc_html( $today_label . ' - ' . $page_title ); ?></title>
				<meta name="description" content="<?php echo esc_attr( "Today's prayer times for " . $mosque_name . ( $mosque_address ? ', ' . $mosque_address : '' ) ); ?>">

				<!-- PWA Meta Tags -->
				<link rel="manifest" href="<?php echo esc_url( plugins_url( 'assets/manifest.json', __FILE__ ) ); ?>"> <!-- Escape output -->
				<meta name="theme-color" content="#667eea">
				<meta name="apple-mobile-web-app-capable" content="yes">
				<meta name="apple-mobile-web-app-status-bar-style" content="default">
				<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $today_label ); ?>">

				<style>
					:root {
						--mosque-primary: #667eea;
						--mosque-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
						--text-primary: #1f2937;
						--text-secondary: #6b7280;
						--bg-card: #ffffff;
						--border-color: #e5e7eb;
						--shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
					}

					* {
						margin: 0;
						padding: 0;
						box-sizing: border-box;
					}

					body {
						font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
						background: var(--mosque-gradient);
						min-height: 100vh;
						display: flex;
						align-items: center;
						justify-content: center;
						padding: 20px;
						color: var(--text-primary);
					}

					.today-container {
						background: var(--bg-card);
						border-radius: 20px;
						padding: 2rem;
						max-width: 600px;
						width: 100%;
						box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
						text-align: center;
					}

					.mosque-icon {
						font-size: 3rem;
						margin-bottom: 1rem;
					}

					h1 {
						font-size: 2rem;
						margin-bottom: 0.5rem;
						color: var(--text-primary);
					}

					.date-info {
						margin-bottom: 2rem;
						padding: 1rem;
						background: #f8fafc;
						border-radius: 12px;
					}

					.date-info .gregorian {
						font-size: 1.2rem;
						font-weight: 600;
						color: var(--text-primary);
						margin-bottom: 0.5rem;
					}

					.date-info .hijri {
						font-size: 1rem;
						color: var(--text-secondary);
					}

					.next-prayer {
						margin-bottom: 2rem;
						padding: 1.5rem;
						background: var(--mosque-gradient);
						color: white;
						border-radius: 16px;
					}

					.next-prayer h2 {
						font-size: 1.3rem;
						margin-bottom: 1rem;
					}

					.prayer-name {
						font-size: 2rem;
						font-weight: 700;
						margin-bottom: 0.5rem;
					}

					.prayer-time {
						font-size: 1.5rem;
						margin-bottom: 1rem;
					}

					.countdown {
						font-size: 1.1rem;
						opacity: 0.9;
					}

					.prayers-grid {
						display: grid;
						grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
						gap: 1rem;
						margin-bottom: 2rem;
					}

					.prayer-card {
						padding: 1rem;
						background: #f8fafc;
						border-radius: 12px;
						border: 1px solid var(--border-color);
					}

					.prayer-card.next {
						background: rgba(102, 126, 234, 0.1);
						border-color: var(--mosque-primary);
					}

					.prayer-card h3 {
						font-size: 0.9rem;
						font-weight: 600;
						color: var(--text-secondary);
						margin-bottom: 0.5rem;
						text-transform: uppercase;
						letter-spacing: 0.5px;
					}

					.prayer-card .time {
						font-size: 1.2rem;
						font-weight: 700;
						color: var(--text-primary);
					}

					.prayer-card .jamaah {
						font-size: 0.9rem;
						color: var(--text-secondary);
						margin-top: 0.25rem;
					}

					.actions {
						display: flex;
						gap: 1rem;
						justify-content: center;
						flex-wrap: wrap;
					}

					.btn {
						padding: 12px 24px;
						border: none;
						border-radius: 25px;
						font-size: 1rem;
						font-weight: 600;
						cursor: pointer;
						text-decoration: none;
						display: inline-flex;
						align-items: center;
						gap: 8px;
						transition: all 0.3s ease;
					}

					.btn-primary {
						background: var(--mosque-gradient);
						color: white;
						box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
					}

					.btn-primary:hover {
						transform: translateY(-2px);
						box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
					}

					.btn-secondary {
						background: white;
						color: var(--text-primary);
						border: 2px solid var(--border-color);
					}

					.btn-secondary:hover {
						background: #f9fafb;
						border-color: var(--mosque-primary);
					}

					@media (max-width: 480px) {
						.today-container {
							padding: 1.5rem;
						}

						h1 {
							font-size: 1.5rem;
						}

						.prayers-grid {
							grid-template-columns: repeat(2, 1fr);
						}

						.actions {
							flex-direction: column;
						}

						.btn {
							width: 100%;
							justify-content: center;
						}
					}

					.loading {
						display: none;
					}

					@media (prefers-reduced-motion: reduce) {

						*,
						*::before,
						*::after {
							animation-duration: 0.01ms !important;
							animation-iteration-count: 1 !important;
							transition-duration: 0.01ms !important;
						}
					}
				</style>
			</head>

			<body>
				<div class="today-container">
					<div class="mosque-icon">🕌</div>
					<h1><?php echo esc_html( $page_title . ' ' . $today_label ); ?></h1>

					<div class="date-info">
						<div class="gregorian"><?php echo esc_html( date_i18n( 'l, F j, Y', $today->getTimestamp() ) ); ?></div>
						<div class="hijri"><?php echo esc_html( $hijri_date ); ?></div>
					</div>

					<?php if ( $next_prayer && $next_prayer_time ) : ?>
						<div class="next-prayer">
							<h2>Next Prayer</h2>
							<div class="prayer-name"><?php echo esc_html( $prayer_labels[ $next_prayer ] ); ?></div>
							<div class="prayer-time"><?php echo esc_html( $next_prayer_time ); ?></div>
							<div class="countdown" id="countdown">Calculating...</div>
						</div>
					<?php endif; ?>

					<?php if ( $today_prayers ) : ?>
						<div class="prayers-grid">
							<?php
							$prayers_display = array(
								'fajr'    => array(
									'start'  => $today_prayers['fajr_start'],
									'jamaah' => $today_prayers['fajr_jamaah'],
								),
								'sunrise' => array(
									'start'  => $today_prayers['sunrise'],
									'jamaah' => null,
								),
								'zuhr'    => array(
									'start'  => $today_prayers['zuhr_start'],
									'jamaah' => $today_prayers['zuhr_jamaah'],
								),
								'asr'     => array(
									'start'  => $today_prayers['asr_start'],
									'jamaah' => $today_prayers['asr_jamaah'],
								),
								'maghrib' => array(
									'start'  => $today_prayers['maghrib_start'],
									'jamaah' => $today_prayers['maghrib_jamaah'],
								),
								'isha'    => array(
									'start'  => $today_prayers['isha_start'],
									'jamaah' => $today_prayers['isha_jamaah'],
								),
							);

							// Handle Friday/Jummah display
							if ( $today->format( 'N' ) === 5 ) { // Friday
								if ( $today_prayers['jummah1_start'] && $today_prayers['jummah2_start'] ) {
									$prayers_display['zuhr'] = array(
										'start'  => $today_prayers['jummah1_start'] . ' / ' . $today_prayers['jummah2_start'],
										'jamaah' => null,
									);
								}
							}

							foreach ( $prayers_display as $prayer => $times ) :
								$is_next = ( $next_prayer === $prayer && ! ( $countdown_data['is_tomorrow'] ?? false ) );
								?>
								<div class="prayer-card <?php echo $is_next ? 'next' : ''; ?>">
									<h3><?php echo esc_html( $prayer_labels[ $prayer ] ); ?></h3>
									<div class="time"><?php echo esc_html( $times['start'] ?: 'N/A' ); ?></div>
									<?php if ( $times['jamaah'] ) : ?>
										<div class="jamaah">Jamāʿah: <?php echo esc_html( $times['jamaah'] ); ?></div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div class="actions">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary">
							📅 Full Timetable
						</a>
						<a href="<?php echo esc_url( home_url( '/prayer-times/calendar.ics' ) ); ?>" class="btn btn-secondary">
							📱 Subscribe
						</a>
					</div>
				</div>

				<?php if ( $countdown_data ) : ?>
					<script>
						function updateCountdown() {
							const targetDate = '<?php echo esc_js( $countdown_data['date'] ); ?>';
							const targetTime = '<?php echo esc_js( $countdown_data['time'] ); ?>';
							const isTomorrow = <?php echo $countdown_data['is_tomorrow'] ? 'true' : 'false'; ?>;

							const now = new Date();
							const target = new Date(targetDate + 'T' + targetTime);

							if (isTomorrow && target < now) {
								target.setDate(target.getDate() + 1);
							}

							const diff = target - now;

							if (diff <= 0) {
								document.getElementById('countdown').textContent = 'Time has passed';
								return;
							}

							const hours = Math.floor(diff / (1000 * 60 * 60));
							const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
							const seconds = Math.floor((diff % (1000 * 60)) / 1000);

							let countdownText = '';
							if (hours > 0) countdownText += hours + 'h ';
							if (minutes > 0) countdownText += minutes + 'm ';
							countdownText += seconds + 's';

							if (isTomorrow) countdownText += ' (tomorrow)';

							document.getElementById('countdown').textContent = countdownText;
						}

						// Update countdown immediately and then every second
						updateCountdown();
						setInterval(updateCountdown, 1000);

						// Register service worker for PWA functionality
						if ('serviceWorker' in navigator) {
							navigator.serviceWorker.register('<?php echo esc_url( plugins_url( 'assets/sw.js', __FILE__ ) ); ?>') // Escape output
								.catch(function(error) {
									console.log('Service Worker registration failed:', error);
								});
						}
					</script>
				<?php endif; ?>
			</body>

			</html>
			<?php
		}

		/**
		 * Serve prayer times archive page
		 */
		private function serve_prayer_times_archive() {
			// Set proper headers
			header( 'Content-Type: text/html; charset=utf-8' );

			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';
			$terminology    = $this->get_terminology_overrides();

			// Get all available years
			$available_years = $this->get_available_years();
			$current_year    = get_option( 'default_year', wp_date( 'Y' ) );

			?>
			<!DOCTYPE html>
			<html lang="en">

			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title><?php echo esc_html( $mosque_name . ' - Prayer Times Archive' ); ?></title>
				<meta name="description" content="<?php echo esc_attr( 'Browse prayer times by year for ' . $mosque_name . ( $mosque_address ? ', ' . $mosque_address : '' ) ); ?>">

				<!-- PWA Meta Tags -->
				<link rel="manifest" href="<?php echo esc_url( plugins_url( 'assets/manifest.json', __FILE__ ) ); ?>"> <!-- Escape output -->
				<meta name="theme-color" content="#667eea">

				<style>
					:root {
						--mosque-primary: #667eea;
						--mosque-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
						--text-primary: #1f2937;
						--text-secondary: #6b7280;
						--bg-card: #ffffff;
						--border-color: #e5e7eb;
					}

					* {
						margin: 0;
						padding: 0;
						box-sizing: border-box;
					}

					body {
						font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
						background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
						min-height: 100vh;
						color: var(--text-primary);
					}

					.header {
						background: var(--mosque-gradient);
						color: white;
						padding: 2rem;
						text-align: center;
					}

					.header h1 {
						font-size: 2.5rem;
						margin-bottom: 0.5rem;
					}

					.header p {
						font-size: 1.1rem;
						opacity: 0.9;
					}

					.container {
						max-width: 1200px;
						margin: 0 auto;
						padding: 2rem;
					}

					.current-year {
						background: var(--bg-card);
						border-radius: 16px;
						padding: 2rem;
						margin-bottom: 2rem;
						box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
						border-left: 4px solid var(--mosque-primary);
					}

					.current-year h2 {
						color: var(--mosque-primary);
						margin-bottom: 1rem;
					}

					.archive-grid {
						display: grid;
						grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
						gap: 1.5rem;
					}

					.year-card {
						background: var(--bg-card);
						border-radius: 12px;
						padding: 1.5rem;
						box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
						transition: all 0.3s ease;
						border: 1px solid var(--border-color);
					}

					.year-card:hover {
						transform: translateY(-2px);
						box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
					}

					.year-card.current {
						border-color: var(--mosque-primary);
						background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
					}

					.year-card h3 {
						font-size: 1.5rem;
						color: var(--text-primary);
						margin-bottom: 0.5rem;
					}

					.year-card .description {
						color: var(--text-secondary);
						margin-bottom: 1rem;
						font-size: 0.9rem;
					}

					.btn {
						display: inline-block;
						padding: 10px 20px;
						background: var(--mosque-gradient);
						color: white;
						text-decoration: none;
						border-radius: 8px;
						font-weight: 600;
						transition: all 0.3s ease;
					}

					.btn:hover {
						transform: translateY(-1px);
						box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
					}

					.btn-secondary {
						background: white;
						color: var(--mosque-primary);
						border: 2px solid var(--border-color);
					}

					.btn-secondary:hover {
						background: #f9fafb;
						border-color: var(--mosque-primary);
					}

					.navigation {
						text-align: center;
						margin-top: 2rem;
						padding-top: 2rem;
						border-top: 1px solid var(--border-color);
					}

					@media (max-width: 768px) {
						.header h1 {
							font-size: 2rem;
						}

						.container {
							padding: 1rem;
						}

						.archive-grid {
							grid-template-columns: 1fr;
						}
					}
				</style>
			</head>

			<body>
				<div class="header">
					<h1><?php echo esc_html( $mosque_name ); ?></h1>
					<p>Prayer Times Archive</p>
				</div>

				<div class="container">
					<div class="current-year">
						<h2>Current Year - <?php echo esc_html( $current_year ); ?></h2>
						<p>View this year's complete prayer timetable with monthly breakdowns.</p>
						<a href="<?php echo esc_url( home_url( "/prayer-times/{$current_year}/" ) ); ?>" class="btn">
							📅 View <?php echo esc_html( $current_year ); ?> Timetable
						</a>
					</div>

					<?php if ( ! empty( $available_years ) ) : ?>
						<div class="archive-grid">
							<?php foreach ( $available_years as $year ) : ?>
								<div class="year-card <?php echo ( $year === $current_year ) ? 'current' : ''; ?>">
									<h3><?php echo esc_html( $year ); ?></h3>
									<p class="description">
										<?php echo ( $year === $current_year ) ? 'Current year - Active timetable' : 'Historical prayer times'; ?>
									</p>
									<a href="<?php echo esc_url( home_url( "/prayer-times/{$year}/" ) ); ?>" class="btn">
										Browse <?php echo esc_html( $year ); ?>
									</a>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div class="navigation">
						<a href="<?php echo esc_url( home_url( '/today' ) ); ?>" class="btn">
							📱 Today's Prayers
						</a>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-secondary">
							🏠 Home
						</a>
					</div>
				</div>
			</body>

			</html>
			<?php
		}

		/**
		 * Serve year archive page
		 */
		private function serve_year_archive_page( $year ) {
			// Set proper headers
			header( 'Content-Type: text/html; charset=utf-8' );

			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';
			$terminology    = $this->get_terminology_overrides();
			$current_year   = get_option( 'default_year', wp_date( 'Y' ) );

			// Get months with data for this year
			$months_with_data = $this->get_months_with_data( $year );

			?>
			<!DOCTYPE html>
			<html lang="en">

			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title><?php echo esc_html( $mosque_name . ' - ' . $year . ' Prayer Times' ); ?></title>
				<meta name="description" content="<?php echo esc_attr( "Prayer times for {$year} at {$mosque_name}" . ( $mosque_address ? ', ' . $mosque_address : '' ) ); ?>">

				<!-- PWA Meta Tags -->
				<link rel="manifest" href="<?php echo esc_url( plugins_url( 'assets/manifest.json', __FILE__ ) ); ?>"> <!-- Escape output -->
				<meta name="theme-color" content="#667eea">

				<style>
					:root {
						--mosque-primary: #667eea;
						--mosque-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
						--text-primary: #1f2937;
						--text-secondary: #6b7280;
						--bg-card: #ffffff;
						--border-color: #e5e7eb;
					}

					* {
						margin: 0;
						padding: 0;
						box-sizing: border-box;
					}

					body {
						font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
						background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
						min-height: 100vh;
						color: var(--text-primary);
					}

					.header {
						background: var(--mosque-gradient);
						color: white;
						padding: 2rem;
						text-align: center;
					}

					.header h1 {
						font-size: 2.5rem;
						margin-bottom: 0.5rem;
					}

					.header p {
						font-size: 1.1rem;
						opacity: 0.9;
					}

					.container {
						max-width: 1200px;
						margin: 0 auto;
						padding: 2rem;
					}

					.year-info {
						background: var(--bg-card);
						border-radius: 16px;
						padding: 2rem;
						margin-bottom: 2rem;
						box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
						text-align: center;
					}

					.months-grid {
						display: grid;
						grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
						gap: 1.5rem;
						margin-bottom: 2rem;
					}

					.month-card {
						background: var(--bg-card);
						border-radius: 12px;
						padding: 1.5rem;
						box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
						transition: all 0.3s ease;
						border: 1px solid var(--border-color);
					}

					.month-card:hover {
						transform: translateY(-2px);
						box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
					}

					.month-card.available {
						border-color: var(--mosque-primary);
					}

					.month-card.unavailable {
						opacity: 0.6;
						background: #f9fafb;
					}

					.month-card h3 {
						font-size: 1.3rem;
						color: var(--text-primary);
						margin-bottom: 0.5rem;
					}

					.month-card .status {
						color: var(--text-secondary);
						margin-bottom: 1rem;
						font-size: 0.9rem;
					}

					.btn {
						display: inline-block;
						padding: 10px 20px;
						background: var(--mosque-gradient);
						color: white;
						text-decoration: none;
						border-radius: 8px;
						font-weight: 600;
						transition: all 0.3s ease;
					}

					.btn:hover {
						transform: translateY(-1px);
						box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
					}

					.btn:disabled,
					.btn.disabled {
						background: #e5e7eb;
						color: #9ca3af;
						cursor: not-allowed;
						transform: none;
						box-shadow: none;
					}

					.btn-secondary {
						background: white;
						color: var(--mosque-primary);
						border: 2px solid var(--border-color);
					}

					.btn-secondary:hover {
						background: #f9fafb;
						border-color: var(--mosque-primary);
					}

					.navigation {
						text-align: center;
						padding-top: 2rem;
						border-top: 1px solid var(--border-color);
					}

					.current-badge {
						background: var(--mosque-primary);
						color: white;
						padding: 4px 12px;
						border-radius: 20px;
						font-size: 0.8rem;
						font-weight: 600;
						margin-left: 8px;
					}

					@media (max-width: 768px) {
						.header h1 {
							font-size: 2rem;
						}

						.container {
							padding: 1rem;
						}

						.months-grid {
							grid-template-columns: repeat(2, 1fr);
						}
					}

					@media (max-width: 480px) {
						.months-grid {
							grid-template-columns: 1fr;
						}
					}
				</style>
			</head>

			<body>
				<div class="header">
					<h1><?php echo esc_html( $mosque_name ); ?></h1>
					<p><?php echo esc_html( $year ); ?> Prayer Times
						<?php if ( $year === $current_year ) : ?>
							<span class="current</span>
						<?php endif; ?>
					</p>
				</div>

				<div class=" container">
								<div class="year-info">
									<h2><?php echo esc_html( $year ); ?> Prayer Timetable</h2>
									<p>Browse monthly prayer times for <?php echo esc_html( $year ); ?>.
										<?php if ( $year === $current_year ) : ?>
											This is the current active year.
										<?php else : ?>
											Historical prayer times archive.
										<?php endif; ?>
									</p>
								</div>

								<div class="months-grid">
									<?php
									$month_names = array(
										1  => 'January',
										2  => 'February',
										3  => 'March',
										4  => 'April',
										5  => 'May',
										6  => 'June',
										7  => 'July',
										8  => 'August',
										9  => 'September',
										10 => 'October',
										11 => 'November',
										12 => 'December',
									);

									foreach ( $month_names as $month_num => $month_name ) :
										$has_data   = in_array( $month_num, $months_with_data );
										$month_slug = strtolower( $month_name );
										?>
										<div class="month-card <?php echo $has_data ? 'available' : 'unavailable'; ?>">
											<h3><?php echo esc_html( $month_name ); ?></h3>
											<p class="status">
												<?php echo $has_data ? '✅ Available' : '⏳ No data'; ?>
											</p>
											<?php if ( $has_data ) : ?>
												<a href="<?php echo esc_url( home_url( "/prayer-times/{$year}/{$month_slug}/" ) ); ?>" class="btn">
													View <?php echo esc_html( $month_name ); ?>
												</a>
											<?php else : ?>
												<span class="btn disabled">
													Not Available
												</span>
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="navigation">
									<a href="<?php echo esc_url( home_url( '/prayer-times/' ) ); ?>" class="btn btn-secondary">
										📚 All Years
									</a>
									<a href="<?php echo esc_url( home_url( '/today' ) ); ?>" class="btn">
										📱 Today's Prayers
									</a>
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-secondary">
										🏠 Home
									</a>
								</div>
				</div>
			</body>

			</html>
			<?php
		}

		/**
		 * Get available years with prayer data
		 */
		private function get_available_years() {
			// This would normally query the database for years with data
			// For now, return a range including current year and recent years
			$current_year = wp_date( 'Y' );
			$years        = array();

			// Add current year and previous 2 years, next 1 year
			for ( $i = -2; $i <= 1; $i++ ) {
				$years[] = (int) $current_year + $i;
			}

			// Sort in descending order (newest first)
			rsort( $years );

			return $years;
		}

		/**
		 * Get months with data for a specific year
		 */
		private function get_months_with_data( int $year ): array {
			$months_with_data = array();

			// Check each month for data
			for ( $month = 1; $month <= 12; $month++ ) {
				$prayer_data = $this->get_month_prayer_data( $year, $month );
				if ( ! empty( $prayer_data ) ) {
					$months_with_data[] = $month;
				}
			}

			return $months_with_data;
		}

		/**
		 * Enhanced default year management
		 */
		public function get_current_year() {
			// Use ACF field first, then option, then current year
			$year = get_field( 'default_year', 'option' );
			if ( ! $year ) {
				$year = get_option( 'default_year', wp_date( 'Y' ) );
			}

			// Validate year is reasonable
			$current_year = (int) wp_date( 'Y' );
			$year         = (int) $year;

			if ( $year < ( $current_year - 5 ) || $year > ( $current_year + 5 ) ) {
				// Reset to current year if unreasonable
				$year = $current_year;
				$this->update_default_year( $year );
			}

			return $year;
		}

		/**
		 * Update default year with validation
		 */
		public function update_default_year( $year ) {
			$year         = (int) $year;
			$current_year = (int) wp_date( 'Y' );

			// Validate year is reasonable (within 5 years of current)
			if ( $year >= ( $current_year - 5 ) && $year <= ( $current_year + 5 ) ) {
				update_option( 'default_year', $year );

				// Update ACF field if available
				if ( function_exists( 'update_field' ) ) {
					update_field( 'default_year', $year, 'option' );
				}

				return true;
			}

			return false;
		}

		/**
		 * Auto-advance year functionality
		 */
		public function check_year_advancement() {
			$current_default = $this->get_current_year();
			$actual_current  = (int) wp_date( 'Y' );

			// If we're in a new year and still using old default, consider updating
			if ( $actual_current > $current_default ) {
				// Check if new year has any data
				$new_year_data = $this->get_months_with_data( $actual_current );

				// If new year has data for current month or later, auto-advance
				$current_month          = (int) wp_date( 'n' );
				$has_current_month_data = in_array( $current_month, $new_year_data );

				if ( $has_current_month_data || count( $new_year_data ) >= 3 ) {
					$this->update_default_year( $actual_current );
					return true;
				}
			}

			return false;
		}

		/**
		 * Get terminology overrides as associative array
		 */
		private function get_terminology_overrides(): array {
			$overrides = array();

			if ( mt_has_acf() ) {
				$terminology_overrides = get_field( 'terminology_overrides', 'option' );
			} else {
				$terminology_overrides = get_option( 'terminology_overrides', array() );
			}

			if ( is_array( $terminology_overrides ) ) {
				foreach ( $terminology_overrides as $override ) {
					if ( ! empty( $override['from'] ) && ! empty( $override['to'] ) && ( $override['enabled'] ?? 1 ) ) {
						$overrides[ $override['from'] ] = $override['to'];
					}
				}
			}

			return $overrides;
		}

		/**
		 * Get formatted Hijri date string
		 */
		private function get_hijri_date( DateTime $date ): string {
			$hijri = $this->calculate_hijri_date( $date->format( 'Y-m-d' ) );
			return $hijri; // This already returns a formatted string
		}

		/**
		 * Add entries to robots.txt
		 */
		public function add_robots_txt_entries( $output, $public ) {
			if ( $public ) {
				$site_url = get_site_url();
				$output  .= "\n# Mosque Timetable Plugin\n";
				$output  .= "Sitemap: {$site_url}/prayer-times-sitemap.xml\n";
				$output  .= "Allow: /llms.txt\n";
			}
			return $output;
		}

		/**
		 * Add a prayer event to ICS calendar
		 */
		private function add_prayer_event( $date, $prayer_name, $prayer_time, $mosque_name, $mosque_address, $timezone ) {
			$event_date      = $date->format( 'Ymd' );
			$prayer_datetime = DateTime::createFromFormat(
				'Y-m-d H:i',
				$date->format( 'Y-m-d' ) . ' ' . $prayer_time,
				new DateTimeZone( $timezone )
			);

			if ( ! $prayer_datetime ) {
				return; // Skip invalid times
			}

			$event_id  = md5( $event_date . $prayer_name . $prayer_time . $mosque_name );
			$timestamp = gmdate( 'Ymd\THis\Z' );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format constants
			echo "BEGIN:VEVENT\r\n";
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo 'UID:' . $event_id . '@' . parse_url( get_site_url(), PHP_URL_HOST ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo 'DTSTAMP:' . $timestamp . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo "DTSTART;TZID={$timezone}:" . $prayer_datetime->format( 'Ymd\THis' ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo "DTEND;TZID={$timezone}:" . $prayer_datetime->modify( '+30 minutes' )->format( 'Ymd\THis' ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo 'SUMMARY:' . $this->ics_escape( $prayer_name . ' - ' . $mosque_name ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo 'DESCRIPTION:' . $this->ics_escape( 'Prayer time at ' . $mosque_name . ( $mosque_address ? ', ' . $mosque_address : '' ) ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo 'LOCATION:' . $this->ics_escape( $mosque_name . ( $mosque_address ? ', ' . $mosque_address : '' ) ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			echo "CATEGORIES:Prayer,Islamic\r\n";
			echo "BEGIN:VALARM\r\n";
			echo "ACTION:DISPLAY\r\n";
			echo "DESCRIPTION:Prayer time reminder\r\n";
			echo "TRIGGER:-PT5M\r\n";
			echo "END:VALARM\r\n";
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format constants
			echo "END:VEVENT\r\n";
		}

		/**
		 * Escape text for ICS format
		 */
		private function ics_escape( $text ) {
			$text = str_replace( '\\', '\\\\', $text );
			$text = str_replace( ',', '\\,', $text );
			$text = str_replace( ';', '\\;', $text );
			$text = str_replace( "\n", "\\n", $text );
			$text = str_replace( "\r", '', $text );
			return $text;
		}

		/**
		 * Handle service worker requests dynamically
		 */
		public function handle_service_worker_request() {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

			// Check if this is a service worker request
			if ( strpos( $request_uri, '/wp-content/plugins/mosque-timetable/assets/sw.js' ) !== false ) {
				$this->serve_dynamic_service_worker();
				exit;
			}
		}

		/**
		 * Serve dynamically generated service worker
		 */
		private function serve_dynamic_service_worker() {
			// Set proper headers
			header( 'Content-Type: application/javascript; charset=utf-8' );
			header( 'Cache-Control: max-age=3600' ); // Cache for 1 hour

			// Get plugin URLs
			$plugin_url  = MOSQUE_TIMETABLE_PLUGIN_URL;
			$assets_url  = MOSQUE_TIMETABLE_ASSETS_URL;
			$offline_url = plugins_url( 'assets/offline.html', __FILE__ );

			// Generate service worker content
			?>
			/**
			* Mosque Prayer Timetable Service Worker
			* Version: 3.0.0 - Dynamically Generated
			*/

			const CACHE_NAME = 'mosque-timetable-v3.0.0';
			const OFFLINE_PAGE = '<?php echo esc_url( $offline_url ); ?>';

			// Assets to cache immediately
			const STATIC_ASSETS = [
			'/',
			'/today',
			'<?php echo esc_url( $assets_url ); ?>mosque-timetable.css',
			'<?php echo esc_url( $assets_url ); ?>mosque-timetable.js',
			'<?php echo esc_url( $assets_url ); ?>mt-modal.css',
			'<?php echo esc_url( $assets_url ); ?>icon-192.png',
			'<?php echo esc_url( $assets_url ); ?>icon-512.png',
			'<?php echo esc_url( $assets_url ); ?>manifest.json',
			'<?php echo esc_url( $offline_url ); ?>'
			];

			// Prayer times cache duration (1 hour)
			const PRAYER_CACHE_DURATION = 60 * 60 * 1000;

			// Install event - cache static assets
			self.addEventListener('install', (event) => {
			console.log('Service Worker installing...');

			event.waitUntil(
			caches.open(CACHE_NAME).then((cache) => {
			console.log('Caching static assets');
			return cache.addAll(STATIC_ASSETS);
			}).then(() => {
			return self.skipWaiting();
			})
			);
			});

			// Activate event - clean up old caches
			self.addEventListener('activate', (event) => {
			console.log('Service Worker activating...');

			event.waitUntil(
			Promise.all([
			caches.keys().then((cacheNames) => {
			return Promise.all(
			cacheNames.map((cacheName) => {
			if (cacheName !== CACHE_NAME) {
			console.log('Deleting old cache:', cacheName);
			return caches.delete(cacheName);
			}
			})
			);
			}),
			self.clients.claim()
			])
			);
			});

			// Fetch event - serve from cache when offline
			self.addEventListener('fetch', (event) => {
			const request = event.request;
			const url = new URL(request.url);

			// Skip non-GET requests
			if (request.method !== 'GET') {
			return;
			}

			// Handle navigation requests
			if (request.mode === 'navigate') {
			event.respondWith(
			fetch(request)
			.catch(() => caches.match(OFFLINE_PAGE))
			);
			return;
			}

			// Handle static assets
			if (url.pathname.includes('<?php echo esc_js( parse_url( $assets_url, PHP_URL_PATH ) ); ?>')) {
			event.respondWith(
			caches.match(request)
			.then((response) => {
			if (response) {
			return response;
			}
			return fetch(request).then((response) => {
			if (response.status === 200) {
			const responseClone = response.clone();
			caches.open(CACHE_NAME).then((cache) => {
			cache.put(request, responseClone);
			});
			}
			return response;
			});
			})
			);
			return;
			}

			// Handle API requests (prayer times)
			if (url.pathname.includes('/wp-json/mosque/v1/')) {
			event.respondWith(
			fetch(request)
			.then((response) => {
			if (response.status === 200) {
			const responseClone = response.clone();
			caches.open(CACHE_NAME).then((cache) => {
			cache.put(request, responseClone);
			});
			}
			return response;
			})
			.catch(() => {
			return caches.match(request)
			.then((cachedResponse) => {
			if (cachedResponse) {
			return cachedResponse;
			}
			const placeholder = caches.match('<?php echo esc_url( $assets_url ); ?>icon-192.png');
			return placeholder;
			});
			})
			);
			return;
			}

			// Default: try network first, fall back to cache
			event.respondWith(
			fetch(request)
			.catch(() => caches.match(request))
			);
			});

			// Push notification event
			self.addEventListener('push', (event) => {
			if (!event.data) {
			return;
			}

			const data = event.data.json();
			const options = {
			body: data.body || 'Prayer time notification',
			icon: '<?php echo esc_url( $assets_url ); ?>icon-192.png',
			badge: '<?php echo esc_url( $assets_url ); ?>icon-192.png',
			tag: 'prayer-notification',
			data: data.data || {},
			actions: [
			{
			action: 'view',
			title: 'View Prayer Times',
			icon: '<?php echo esc_url( $assets_url ); ?>icon-192.png'
			},
			{
			action: 'dismiss',
			title: 'Dismiss',
			icon: '<?php echo esc_url( $assets_url ); ?>icon-192.png'
			}
			]
			};

			event.waitUntil(
			self.registration.showNotification(data.title || 'Prayer Time', options)
			);
			});

			// Notification click event
			self.addEventListener('notificationclick', (event) => {
			event.notification.close();

			if (event.action === 'view') {
			event.waitUntil(
			clients.openWindow('/')
			);
			}
			});
			<?php
		}

		/**
		 * AJAX: Save month timetable
		 */
		public function ajax_save_month_timetable() {
			if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
				wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
			}
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
			}

			$month = isset( $_POST['month'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['month'] ) ) : 0;
			$year  = isset( $_POST['year'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['year'] ) ) : (int) get_option( 'default_year', wp_date( 'Y' ) );
			$data  = isset( $_POST['data']['days'] ) && is_array( $_POST['data']['days'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['data']['days'] ) ) : array();
			if ( $month < 1 || $month > 12 ) {
				wp_send_json_error( __( 'Invalid month', 'mosque-timetable' ) );
			}

			// normalise rows (ensure day_number int)
			$rows = array();
			foreach ( $data as $d ) {
				if ( empty( $d['day_number'] ) ) {
					continue;
				}
				$d['day_number'] = (int) $d['day_number'];
				$rows[]          = $d;
			}
			usort( $rows, fn( $a, $b ) => ( $a['day_number'] ?? 0 ) <=> ( $b['day_number'] ?? 0 ) );

			mt_save_month_rows( $month, $rows, $year );
			wp_send_json_success( 'Month saved successfully.' );
		}

		/**
		 * AJAX: Recalculate Hijri dates
		 */
		public function ajax_recalculate_hijri_dates() {
			// Verify nonce for security
			if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
				wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
			}

			$month      = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
			$year       = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : (int) wp_date( 'Y' );
			$adjustment = isset( $_POST['adjustment'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['adjustment'] ) ) ) : 0;

			if ( ! $month || $month < 1 || $month > 12 ) {
				wp_send_json_error( __( 'Invalid month specified', 'mosque-timetable' ) );
			}

			$field_name    = 'daily_prayers_' . $month;
			$daily_prayers = get_field( $field_name, 'option' );

			if ( ! $daily_prayers ) {
				wp_send_json_error( __( 'No prayer data found for this month', 'mosque-timetable' ) );
			}

			$hijri_dates = array();

			foreach ( $daily_prayers as $index => $day ) {
				if ( $day['date_full'] ) {
					$hijri_date                            = $this->calculate_hijri_date( $day['date_full'], $adjustment );
					$daily_prayers[ $index ]['hijri_date'] = $hijri_date;
					$hijri_dates[]                         = $hijri_date;
				}
			}

			// Update the field with recalculated Hijri dates
			update_field( $field_name, $daily_prayers, 'option' );

			wp_send_json_success( $hijri_dates );
		}

		/**
		 * AJAX: Export CSV calendar
		 */
		public function ajax_export_csv_calendar() {
			// Verify nonce for security
			if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'mosque_timetable_nonce' ) ) {
				wp_die( esc_html__( 'Security check failed', 'mosque-timetable' ) );
			}

			$default_year = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
			$mosque_name  = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';

			// Set headers for CSV download
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="prayer-times-' . $default_year . '.csv"' );
			header( 'Cache-Control: max-age=0' );

			// Create output buffer
			$output = fopen( 'php://output', 'w' );

			// Add CSV header
			fputcsv(
				$output,
				array(
					'Month',
					'Day',
					'Date',
					'Day Name',
					'Hijri Date',
					'Fajr Start',
					'Fajr Jamaat',
					'Sunrise',
					'Zuhr Start',
					'Zuhr Jamaat',
					'Asr Start',
					'Asr Jamaat',
					'Maghrib Start',
					'Maghrib Jamaat',
					'Isha Start',
					'Isha Jamaat',
					'Jummah 1',
					'Jummah 2',
				)
			);

			// Export all available months
			for ( $month = 1; $month <= 12; $month++ ) {
				$field_name    = 'daily_prayers_' . $month;
				$daily_prayers = get_field( $field_name, 'option' );

				if ( ! $daily_prayers ) {
					continue;
				}

				$month_names = array(
					1  => 'January',
					2  => 'February',
					3  => 'March',
					4  => 'April',
					5  => 'May',
					6  => 'June',
					7  => 'July',
					8  => 'August',
					9  => 'September',
					10 => 'October',
					11 => 'November',
					12 => 'December',
				);

				foreach ( $daily_prayers as $day ) {
					fputcsv(
						$output,
						array(
							$month_names[ $month ],
							isset( $day['day_number'] ) ? $day['day_number'] : '',
							isset( $day['date_full'] ) ? $day['date_full'] : '',
							isset( $day['day_name'] ) ? $day['day_name'] : '',
							isset( $day['hijri_date'] ) ? $day['hijri_date'] : '',
							isset( $day['fajr_start'] ) ? $day['fajr_start'] : '',
							isset( $day['fajr_jamaat'] ) ? $day['fajr_jamaat'] : '',
							isset( $day['sunrise'] ) ? $day['sunrise'] : '',
							isset( $day['zuhr_start'] ) ? $day['zuhr_start'] : '',
							isset( $day['zuhr_jamaat'] ) ? $day['zuhr_jamaat'] : '',
							isset( $day['asr_start'] ) ? $day['asr_start'] : '',
							isset( $day['asr_jamaat'] ) ? $day['asr_jamaat'] : '',
							isset( $day['maghrib_start'] ) ? $day['maghrib_start'] : '',
							isset( $day['maghrib_jamaat'] ) ? $day['maghrib_jamaat'] : '',
							isset( $day['isha_start'] ) ? $day['isha_start'] : '',
							isset( $day['isha_jamaat'] ) ? $day['isha_jamaat'] : '',
							isset( $day['jummah_1'] ) ? $day['jummah_1'] : '',
							isset( $day['jummah_2'] ) ? $day['jummah_2'] : '',
						)
					);
				}
			}

			fclose( $output );
			exit;
		}

		/**
		 * AJAX: Clear all prayer data
		 */
		public function ajax_clear_all_prayer_data() {
			// Verify nonce for security
			if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
				wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
			}

			// Clear all monthly prayer data
			for ( $month = 1; $month <= 12; $month++ ) {
				$field_name = 'daily_prayers_' . $month;
				delete_field( $field_name, 'option' );
			}

			wp_send_json_success( 'All prayer data cleared successfully' );
		}

		/**
		 * AJAX: Reset to empty structure
		 */
		public function ajax_reset_to_empty_structure() {
			// Verify nonce for security
			if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
				wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
			}

			$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
			$available_months = get_field( 'available_months', 'option' ) ?: array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' );

			// Reset structure for each available month
			foreach ( $available_months as $month_num ) {
				$field_name = 'daily_prayers_' . $month_num;
				delete_field( $field_name, 'option' );
				$this->populate_month_dates( $default_year, intval( $month_num ) );
			}

			wp_send_json_success( 'Prayer times reset to empty structure' );
		}

		/**
		 * AJAX: Regenerate all dates
		 */
		public function ajax_regenerate_all_dates() {
			// Verify nonce for security
			if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
				wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
			}

			$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
			$available_months = get_field( 'available_months', 'option' ) ?: array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' );
			$processed        = 0;

			// Regenerate dates for each available month (preserving prayer times)
			foreach ( $available_months as $month_num ) {
				$month         = intval( $month_num );
				$year          = $default_year;
				$field_name    = 'daily_prayers_' . $month_num;
				$existing_data = get_field( $field_name, 'option' );

				// Get new date structure
				$days_in_month = cal_days_in_month( CAL_GREGORIAN, intval( $month_num ), $default_year );
				$month_data    = array();

				for ( $day = 1; $day <= $days_in_month; $day++ ) {
					$date       = sprintf( '%04d-%02d-%02d', $default_year, intval( $month_num ), $day );
					$date_obj   = new DateTime( $date );
					$day_name   = $date_obj->format( 'l' );
					$hijri_date = $this->calculate_hijri_date( $date );

					// Preserve existing prayer times if available
					$existing_day = null;
					if ( $existing_data ) {
						foreach ( $existing_data as $existing ) {
							if ( $existing['day_number'] === $day ) {
								$existing_day = $existing;
								break;
							}
						}
					}

					$month_data[] = array(
						'day_number'     => $day,
						'date_full'      => $date,
						'day_name'       => $day_name,
						'hijri_date'     => $hijri_date,
						'fajr_start'     => isset( $existing_day['fajr_start'] ) ? $existing_day['fajr_start'] : '',
						'fajr_jamaat'    => isset( $existing_day['fajr_jamaat'] ) ? $existing_day['fajr_jamaat'] : '',
						'sunrise'        => isset( $existing_day['sunrise'] ) ? $existing_day['sunrise'] : '',
						'zuhr_start'     => isset( $existing_day['zuhr_start'] ) ? $existing_day['zuhr_start'] : '',
						'zuhr_jamaat'    => isset( $existing_day['zuhr_jamaat'] ) ? $existing_day['zuhr_jamaat'] : '',
						'asr_start'      => isset( $existing_day['asr_start'] ) ? $existing_day['asr_start'] : '',
						'asr_jamaat'     => isset( $existing_day['asr_jamaat'] ) ? $existing_day['asr_jamaat'] : '',
						'maghrib_start'  => isset( $existing_day['maghrib_start'] ) ? $existing_day['maghrib_start'] : '',
						'maghrib_jamaat' => isset( $existing_day['maghrib_jamaat'] ) ? $existing_day['maghrib_jamaat'] : '',
						'isha_start'     => isset( $existing_day['isha_start'] ) ? $existing_day['isha_start'] : '',
						'isha_jamaat'    => isset( $existing_day['isha_jamaat'] ) ? $existing_day['isha_jamaat'] : '',
						'jummah_1'       => isset( $existing_day['jummah_1'] ) ? $existing_day['jummah_1'] : '',
						'jummah_2'       => isset( $existing_day['jummah_2'] ) ? $existing_day['jummah_2'] : '',
					);
					++$processed;
				}

				mt_save_month_rows( $month, array_values( $month_data ), $year );
				// Remove undefined success() call - handled by wp_send_json_success below
			}

			wp_send_json_success( 'All dates regenerated successfully' );
		}

		/**
		 * AJAX: Import CSV timetable
		 */
		public function ajax_import_csv_timetable() {
			// Security
			if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
				wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
			}
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
			}

			// Input
			if ( ! isset( $_FILES['csv_file'] ) ) {
				wp_send_json_error( esc_html__( 'No file uploaded', 'mosque-timetable' ) );
			}
			$month = isset( $_POST['month'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['month'] ) ) : 0;
			if ( $month < 1 || $month > 12 ) {
				wp_send_json_error( __( 'Invalid month specified', 'mosque-timetable' ) );
			}
			$file = $_FILES['csv_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload validation handled above
			if ( UPLOAD_ERR_OK !== $file['error'] ) {
				/* translators: %d: Error code number */
				wp_send_json_error( sprintf( __( 'File upload error: %d', 'mosque-timetable' ), (int) $file['error'] ) );
			}
			$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
			if ( 'csv' !== $ext ) {
				wp_send_json_error( __( 'Please upload CSV files only', 'mosque-timetable' ) );
			}

			$fs          = mt_fs();
			$csv_content = '';
			if ( $fs && $fs->exists( $file['tmp_name'] ) ) {
				$csv_content = $fs->get_contents( $file['tmp_name'] );
			}
			if ( ! $csv_content ) {
				wp_send_json_error( __( 'Could not read uploaded file', 'mosque-timetable' ) );
			}

			// Helpers
			$year = isset( $_POST['year'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['year'] ) ) : (int) wp_date( 'Y' );

			$norm = function ( $s ) {
				$s = trim( (string) $s );
				if ( '' === $s ) {
					return '';
				}
				$s = str_replace( array( '.', '-' ), ':', $s );
				if ( ! preg_match( '/^(\d{1,2}):(\d{1,2})$/', $s, $m ) ) {
					return '';
				}
				$h = max( 0, min( 23, (int) $m[1] ) );
				$i = max( 0, min( 59, (int) $m[2] ) );
				return sprintf( '%02d:%02d', $h, $i );
			};

			$lines          = preg_split( "/\r\n|\n|\r/", $csv_content );
			$month_data     = array();
			$processed      = 0;
			$row_number     = 1;
			$data_row_count = 0; // Track actual data rows (excluding headers and empty lines)

			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( '' === $line ) {
					++$row_number;
					continue;
				}

				$data = str_getcsv( $line );

				// Optional header skip
				if ( 1 === $row_number && $this->is_header_row( $data ) ) {
					++$row_number;
					continue;
				}

				// This is a data row, increment the data row counter
				++$data_row_count;

				// Determine mode
				$day_num = null;
				$date    = null;
				$start   = 0;

				// Case A: first col = day, optional second col = date, then times
				if ( isset( $data[0] ) && is_numeric( $data[0] ) && (int) $data[0] >= 1 && (int) $data[0] <= 31 ) {
					$day_num = (int) $data[0];
					$start   = 1;
					if ( isset( $data[1] ) && $this->looks_like_date( $data[1] ) ) {
						$date  = sanitize_text_field( $data[1] );
						$start = 2;
					}
				}

				// If no day number provided, fall back to data row count (not row number)
				if ( ! $day_num ) {
					$day_num = $data_row_count;
				}

				// Auto date if not provided
				if ( ! $date ) {
					$date = sprintf( '%04d-%02d-%02d', $year, $month, $day_num );
				}

				// Extract times
				$times = array_slice( $data, $start );

				// Accept either “date+times” or “times only”
				// For times-only we expect at least 12 fields (fajr start..jummah2)
				// For date+times same expectation once start offset is applied
				if ( count( $times ) < 12 ) {
					++$row_number;
					continue;
				}

				if ( $day_num >= 1 && $day_num <= 31 ) {
					$month_data[] = array(
						'day_number'     => $day_num,
						'date_full'      => $date,
						'day_name'       => wp_date( 'l', strtotime( $date ) ),
						'hijri_date'     => $this->calculate_hijri_date( $date ),

						'fajr_start'     => $norm( $times[0] ?? '' ),
						'fajr_jamaat'    => $norm( $times[1] ?? '' ),
						'sunrise'        => $norm( $times[2] ?? '' ),
						'zuhr_start'     => $norm( $times[3] ?? '' ),
						'zuhr_jamaat'    => $norm( $times[4] ?? '' ),
						'asr_start'      => $norm( $times[5] ?? '' ),
						'asr_jamaat'     => $norm( $times[6] ?? '' ),
						'maghrib_start'  => $norm( $times[7] ?? '' ),
						'maghrib_jamaat' => $norm( $times[8] ?? '' ),
						'isha_start'     => $norm( $times[9] ?? '' ),
						'isha_jamaat'    => $norm( $times[10] ?? '' ),
						'jummah_1'       => $norm( $times[11] ?? '' ),
						'jummah_2'       => $norm( $times[12] ?? '' ),
					);
					++$processed;
				}

				++$row_number;
			}

			if ( 0 === $processed || empty( $month_data ) ) {
				wp_send_json_error( __( 'No valid data found in the uploaded file', 'mosque-timetable' ) );
			}

			// Sort and save as a numeric array for ACF repeater
			usort( $month_data, fn( $a, $b ) => ( $a['day_number'] ?? 0 ) <=> ( $b['day_number'] ?? 0 ) );

			// Ensure data is saved in the correct structure expected by get_month_prayer_data
			$ok = mt_save_month_rows( $month, $month_data, $year );

			if ( $ok ) {
				wp_send_json_success(
					array(
						'imported_rows' => $processed,
						'message'       => "Successfully imported {$processed} days from CSV file for month {$month}",
					)
				);
			} else {
				wp_send_json_error( __( 'Failed to save imported data', 'mosque-timetable' ) );
			}
		}

		/**
		 * AJAX: Export ICS calendar
		 */
		public function ajax_export_ics_calendar() {
			// Verify nonce for security
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'mosque_timetable_nonce' ) ) {
				wp_die( esc_html__( 'Security check failed', 'mosque-timetable' ) );
			}

			$year         = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : (int) wp_date( 'Y' );
			$month        = isset( $_POST['month'] ) && '' !== $_POST['month'] ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : null;
			$prayer_types = isset( $_POST['prayer_types'] ) ? sanitize_text_field( wp_unslash( $_POST['prayer_types'] ) ) : 'both';
			$reminder     = isset( $_POST['reminder'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['reminder'] ) ) ) : 15;

			$ics_content = $this->generate_ics_content( $year, $month, $prayer_types, $reminder );

			$filename = 'mosque-prayer-times-' . $year;
			if ( $month ) {
				$filename .= '-' . str_pad( (string) $month, 2, '0', STR_PAD_LEFT );
			}
			$filename .= '.ics';

			header( 'Content-Type: text/calendar; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Cache-Control: no-cache, must-revalidate' );

			echo $ics_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
			wp_die();
		}

		/**
		 * Shortcode: Mosque timetable
		 */
		public function shortcode_mosque_timetable( $atts ) {
			$atts = shortcode_atts(
				array(
					'month'         => wp_date( 'n' ),
					'year'          => wp_date( 'Y' ),
					'show_controls' => 'true',
				),
				$atts,
				'mosque_timetable'
			);

			$month         = intval( $atts['month'] );
			$year          = intval( $atts['year'] );
			$show_controls = 'true' === $atts['show_controls'];

			// Get mosque settings
			$mosque_name       = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';
			$mosque_address    = get_field( 'mosque_address', 'option' ) ?: '';
			$auto_calendar_url = mt_get_subscribe_url();

			// Get prayer data for the month
			$prayer_data = $this->get_month_prayer_data( $year, $month );

			ob_start();
			?>
			<div class="mosque-timetable-container">
				<div class="mosque-timetable-header">
					<h2 class="mosque-timetable-title">
						<?php echo esc_html( $mosque_name . ( $mosque_address ? ' - ' . $mosque_address : '' ) ); ?>
						<br><small><?php echo esc_html( wp_date( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ); ?></small>
					</h2>

					<?php if ( $show_controls ) : ?>
						<div class="mosque-timetable-controls">
							<select class="mosque-month-selector">
								<?php
								for ( $m = 1; $m <= 12; $m++ ) :
									$selected = ( $m === $month ) ? 'selected' : '';
									?>
									<option value="<?php echo esc_attr( $year . '-' . $m ); ?>" <?php echo esc_attr( $selected ); ?>>
										<?php echo esc_html( wp_date( 'F', mktime( 0, 0, 0, $m, 1 ) ) . ' ' . $year ); ?>
									</option>
								<?php endfor; ?>
							</select>

							<button class="mosque-export-btn" data-month="<?php echo esc_attr( $year . '-' . $month ); ?>">
								📅 Export Calendar
							</button>

							<?php
							// Print/Download PDF button
							$pdf_url = mt_get_pdf_for_month( $month, $year );
							if ( $pdf_url ) :
								?>
								<a href="<?php echo esc_url( $pdf_url ); ?>"
									class="mosque-print-btn"
									target="_blank"
									title="Download printable PDF timetable">
									📄 Download Timetable
								</a>
							<?php else : ?>
								<button class="mosque-print-btn"
									onclick="window.print()"
									title="Print this timetable">
									🖨️ Print Timetable
								</button>
							<?php endif; ?>

							<a href="<?php echo esc_url( $auto_calendar_url ); ?>"
								class="mosque-subscribe-btn"
								target="_blank"
								title="Click to add our prayer times to your calendar app">
								🔗 Subscribe to Our Prayer Calendar
							</a>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $prayer_data && ! empty( $prayer_data['days'] ) ) : ?>
					<!-- Sticky Prayer Bar -->
					<div class="mosque-prayer-bar" role="tablist" aria-label="Today's Prayer Times">
						<div class="mosque-prayer-bar-date">
							<span class="mosque-prayer-bar-gregorian"><?php echo esc_html( wp_date( 'l, F j, Y' ) ); ?></span>
							<?php $today_data = $this->get_today_prayer_data(); ?>
							<?php if ( $today_data && ! empty( $today_data['hijri_date'] ) ) : ?>
								<span class="mosque-prayer-bar-hijri"><?php echo esc_html( $today_data['hijri_date'] ); ?></span>
							<?php endif; ?>
						</div>
						<div class="mosque-prayer-bar-prayers">
							<?php
							if ( $today_data ) {
								$prayers = array(
									'Fajr'    => $today_data['fajr_start'],
									'Sunrise' => $today_data['sunrise'],
									'Zuhr'    => $today_data['zuhr_start'],
									'Asr'     => $today_data['asr_start'],
									'Maghrib' => $today_data['maghrib_start'],
									'Isha'    => $today_data['isha_start'],
								);

								// Replace Zuhr with Jummah on Friday
								if ( wp_date( 'N' ) === 5 && ( $today_data['jummah_1'] || $today_data['jummah_2'] ) ) {
									unset( $prayers['Zuhr'] );
									$jummah_times = array();
									if ( $today_data['jummah_1'] ) {
										$jummah_times[] = $today_data['jummah_1'];
									}
									if ( $today_data['jummah_2'] ) {
										$jummah_times[] = $today_data['jummah_2'];
									}
									$prayers = array_slice( $prayers, 0, 2, true ) +
										array( 'Jummah' => implode( ' / ', $jummah_times ) ) +
										array_slice( $prayers, 3, null, true );
								}

								$next_prayer = $this->get_next_prayer_name();
								$chip_index  = 0;

								foreach ( $prayers as $name => $time ) {
									if ( empty( $time ) ) {
										continue;
									}
									$is_next   = ( $next_prayer === $name );
									$is_active = $is_next; // We can expand this logic later
									?>
									<div class="mosque-prayer-chip <?php echo esc_attr( $is_active ? 'active' : '' ); ?> <?php echo esc_attr( $is_next ? 'next-prayer' : '' ); ?>"
										role="tab"
										tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
										aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
										data-prayer="<?php echo esc_attr( strtolower( $name ) ); ?>"
										data-index="<?php echo esc_attr( (string) $chip_index ); ?>">
										<div class="mosque-prayer-chip-name"><?php echo esc_html( mt_apply_terminology( $name ) ); ?></div>
										<div class="mosque-prayer-chip-time"><?php echo esc_html( $time ); ?></div>
									</div>
									<?php
									++$chip_index;
								}
							}
							?>
						</div>
					</div>

					<table class="mosque-timetable">
						<thead>
							<tr>
								<th><?php echo esc_html( mt_apply_terminology( 'Date' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Hijri' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Day' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Fajr' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Sunrise' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Zuhr/Jummah' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Asr' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Maghrib' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Isha' ) ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$days = isset( $prayer_data['days'] ) && is_array( $prayer_data['days'] ) ? $prayer_data['days'] : array();
							foreach ( $days as $day ) :
								if ( ! is_array( $day ) ) {
									continue;
								}

								$dateStr = isset( $day['date_full'] ) ? (string) $day['date_full'] : '';
								$date    = $dateStr ? DateTime::createFromFormat( 'Y-m-d', $dateStr ) : false;

								$is_today  = $date && ( $date->format( 'Y-m-d' ) === wp_date( 'Y-m-d' ) );
								$is_friday = $date && ( $date->format( 'N' ) === 5 );
								$day_name  = $date ? $date->format( 'D' ) : '';
								$row_class = trim( ( $is_today ? 'today ' : '' ) . ( $is_friday ? 'friday' : '' ) );
								?>
								<tr class="<?php echo esc_attr( $row_class ); ?>" data-date="<?php echo esc_attr( $day['date_full'] ?? '' ); ?>">
									<td>
										<div class="date-gregorian"><?php echo $date ? esc_html( $date->format( 'd' ) ) : ''; ?></div>
									</td>
									<td>
										<div class="date-hijri"><?php echo esc_html( $day['hijri_date'] ?? '' ); ?></div>
									</td>
									<td>
										<div class="day-name <?php echo esc_attr( $is_friday ? 'friday' : '' ); ?>">
											<?php echo esc_html( $day_name ); ?>
										</div>
									</td>

									<?php
									echo $this->render_prayer_cell( $day['fajr_start'] ?? '', $day['fajr_jamaat'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_prayer_cell returns pre-escaped HTML 
									?>

									<td>
										<div class="prayer-single"><?php echo esc_html( $day['sunrise'] ?? '' ); ?></div>
									</td>

									<?php if ( $is_friday && ( ! empty( $day['jummah_1'] ) || ! empty( $day['jummah_2'] ) ) ) : ?>
										<td>
											<div class="jummah-times">
												<?php
												$jummah_display = array();
												if ( ! empty( $day['jummah_1'] ) ) {
													$jummah_display[] = $day['jummah_1'];
												}
												if ( ! empty( $day['jummah_2'] ) ) {
													$jummah_display[] = $day['jummah_2'];
												}
												echo esc_html( implode( ' / ', $jummah_display ) );
												?>
											</div>
										</td>
									<?php else : ?>
										<?php
										echo $this->render_prayer_cell( $day['zuhr_start'] ?? '', $day['zuhr_jamaat'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_prayer_cell returns pre-escaped HTML 
										?>
									<?php endif; ?>

									<?php
									echo $this->render_prayer_cell( $day['asr_start'] ?? '', $day['asr_jamaat'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_prayer_cell returns pre-escaped HTML 
									?>
									<?php
									echo $this->render_prayer_cell( $day['maghrib_start'] ?? '', $day['maghrib_jamaat'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_prayer_cell returns pre-escaped HTML 
									?>
									<?php
									echo $this->render_prayer_cell( $day['isha_start'] ?? '', $day['isha_jamaat'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_prayer_cell returns pre-escaped HTML 
									?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<?php
					// Mobile card layout (hidden on desktop)
					if ( $prayer_data && ! empty( $prayer_data['days'] ) ) :
						?>
						<div class="mosque-timetable-mobile">
							<?php
							$days = isset( $prayer_data['days'] ) && is_array( $prayer_data['days'] ) ? $prayer_data['days'] : array();
							foreach ( $days as $day ) :
								if ( ! is_array( $day ) ) {
									continue;
								}
								$dateStr    = isset( $day['date_full'] ) ? (string) $day['date_full'] : '';
								$date       = $dateStr ? DateTime::createFromFormat( 'Y-m-d', $dateStr ) : false;
								$is_today   = $date && ( $date->format( 'Y-m-d' ) === wp_date( 'Y-m-d' ) );
								$is_friday  = $date && ( $date->format( 'N' ) === 5 );
								$day_name   = $date ? $date->format( 'l' ) : '';
								$card_class = trim( ( $is_today ? 'today ' : '' ) . ( $is_friday ? 'friday' : '' ) );
								?>
								<div class="mosque-prayer-card <?php echo esc_attr( $card_class ); ?>" data-date="<?php echo esc_attr( $day['date_full'] ?? '' ); ?>">
									<div class="mosque-prayer-card-header">
										<div class="mosque-prayer-card-date">
											<div class="mosque-prayer-card-date-gregorian">
												<?php echo $date ? esc_html( $date->format( 'd M' ) ) : ''; ?>
											</div>
											<div class="mosque-prayer-card-date-hijri">
												<?php echo esc_html( $day['hijri_date'] ?? '' ); ?>
											</div>
										</div>
										<div class="mosque-prayer-card-day <?php echo esc_attr( $is_friday ? 'friday' : '' ); ?>">
											<?php echo esc_html( $day_name ); ?>
										</div>
									</div>
									<div class="mosque-prayer-card-body">
										<div class="mosque-prayer-times-grid">
											<?php if ( ! empty( $day['fajr_start'] ) ) : ?>
												<div class="mosque-prayer-time-item">
													<div class="mosque-prayer-time-name"><?php echo esc_html( mt_apply_terminology( 'Fajr' ) ); ?></div>
													<div class="mosque-prayer-time-start"><?php echo esc_html( $day['fajr_start'] ); ?></div>
													<?php if ( ! empty( $day['fajr_jamaat'] ) ) : ?>
														<div class="mosque-prayer-time-jamaat"><?php echo esc_html( $day['fajr_jamaat'] ); ?></div>
													<?php endif; ?>
												</div>
											<?php endif; ?>

											<?php if ( ! empty( $day['sunrise'] ) ) : ?>
												<div class="mosque-prayer-time-item">
													<div class="mosque-prayer-time-name"><?php echo esc_html( mt_apply_terminology( 'Sunrise' ) ); ?></div>
													<div class="mosque-prayer-time-start"><?php echo esc_html( $day['sunrise'] ); ?></div>
												</div>
											<?php endif; ?>

											<?php if ( $is_friday && ( ! empty( $day['jummah_1'] ) || ! empty( $day['jummah_2'] ) ) ) : ?>
												<div class="mosque-prayer-time-item jummah">
													<div class="mosque-prayer-time-name"><?php echo esc_html( mt_apply_terminology( 'Jummah' ) ); ?></div>
													<div class="mosque-prayer-time-start">
														<?php
														$jummah_display = array();
														if ( ! empty( $day['jummah_1'] ) ) {
															$jummah_display[] = $day['jummah_1'];
														}
														if ( ! empty( $day['jummah_2'] ) ) {
															$jummah_display[] = $day['jummah_2'];
														}
														echo esc_html( implode( ' / ', $jummah_display ) );
														?>
													</div>
												</div>
											<?php else : ?>
												<?php if ( ! empty( $day['zuhr_start'] ) ) : ?>
													<div class="mosque-prayer-time-item">
														<div class="mosque-prayer-time-name"><?php echo esc_html( mt_apply_terminology( 'Zuhr' ) ); ?></div>
														<div class="mosque-prayer-time-start"><?php echo esc_html( $day['zuhr_start'] ); ?></div>
														<?php if ( ! empty( $day['zuhr_jamaat'] ) ) : ?>
															<div class="mosque-prayer-time-jamaat"><?php echo esc_html( $day['zuhr_jamaat'] ); ?></div>
														<?php endif; ?>
													</div>
												<?php endif; ?>
											<?php endif; ?>

											<?php if ( ! empty( $day['asr_start'] ) ) : ?>
												<div class="mosque-prayer-time-item">
													<div class="mosque-prayer-time-name"><?php echo esc_html( mt_apply_terminology( 'Asr' ) ); ?></div>
													<div class="mosque-prayer-time-start"><?php echo esc_html( $day['asr_start'] ); ?></div>
													<?php if ( ! empty( $day['asr_jamaat'] ) ) : ?>
														<div class="mosque-prayer-time-jamaat"><?php echo esc_html( $day['asr_jamaat'] ); ?></div>
													<?php endif; ?>
												</div>
											<?php endif; ?>

											<?php if ( ! empty( $day['maghrib_start'] ) ) : ?>
												<div class="mosque-prayer-time-item">
													<div class="mosque-prayer-time-name"><?php echo esc_html( mt_apply_terminology( 'Maghrib' ) ); ?></div>
													<div class="mosque-prayer-time-start"><?php echo esc_html( $day['maghrib_start'] ); ?></div>
													<?php if ( ! empty( $day['maghrib_jamaat'] ) ) : ?>
														<div class="mosque-prayer-time-jamaat"><?php echo esc_html( $day['maghrib_jamaat'] ); ?></div>
													<?php endif; ?>
												</div>
											<?php endif; ?>

											<?php if ( ! empty( $day['isha_start'] ) ) : ?>
												<div class="mosque-prayer-time-item">
													<div class="mosque-prayer-time-name"><?php echo esc_html( mt_apply_terminology( 'Isha' ) ); ?></div>
													<div class="mosque-prayer-time-start"><?php echo esc_html( $day['isha_start'] ); ?></div>
													<?php if ( ! empty( $day['isha_jamaat'] ) ) : ?>
														<div class="mosque-prayer-time-jamaat"><?php echo esc_html( $day['isha_jamaat'] ); ?></div>
													<?php endif; ?>
												</div>
											<?php endif; ?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

				<?php else : ?>
					<div class="mosque-error">
						No prayer time data available for <?php echo esc_html( wp_date( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ); ?>. // Escape output
						Please contact the mosque administrator.
					</div>
				<?php endif; ?>

				<div class="mosque-timetable-footer">
					<div class="mosque-system-credit">
						<p>🕌 Need a prayer timetable system for your masjid?
							<a href="mailto:ibraheem@mosquewebdesign.com">Contact us</a> or
							<a href="https://mosquewebdesign.com" target="_blank">visit mosquewebdesign.com</a>
							for professional mosque website solutions.
						</p>
					</div>
				</div>
			</div>

			<style>
				.mosque-timetable-footer {
					margin-top: 20px;
					padding: 15px;
					background: linear-gradient(135deg, #f8f9fa, #e9ecef);
					border: 1px solid #dee2e6;
					border-radius: 6px;
					font-size: 14px;
				}

				.mosque-system-credit {
					text-align: center;
					color: #6c757d;
				}

				.mosque-system-credit p {
					margin: 0;
				}

				.mosque-system-credit a {
					color: #1976D2;
					text-decoration: none;
					font-weight: 500;
				}

				aaaaaaaaaaabbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb .mosque-system-credit a:hover {
					text-decoration: underline;
				}
			</style>
			<?php
			return ob_get_clean();
		}

		/**
		 * Shortcode: Today's prayers
		 */
		public function shortcode_todays_prayers( $atts ) {
			$atts = shortcode_atts(
				array(
					'show_date' => 'true',
				),
				$atts,
				'todays_prayers'
			);

			$show_date = 'true' === $atts['show_date'];

			// Get today's prayer data
			$today_data  = $this->get_today_prayer_data();
			$mosque_name = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';

			ob_start();
			?>
			<div class="todays-prayers-widget">
				<?php if ( $show_date ) : ?>
					<div class="todays-prayers-header">
						<div class="todays-prayers-date"><?php echo esc_html( wp_date( 'l, F j, Y' ) ); ?></div> <!-- Escape output -->
						<?php if ( isset( $today_data['hijri_date'] ) ) : ?>
							<div class="todays-prayers-hijri"><?php echo esc_html( $today_data['hijri_date'] ); ?></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $today_data ) : ?>
					<div class="todays-prayers-grid">
						<?php
						$prayers = array(
							'Fajr'    => array(
								'start'  => $today_data['fajr_start'],
								'jamaat' => $today_data['fajr_jamaat'],
							),
							'Sunrise' => array(
								'start'  => $today_data['sunrise'],
								'jamaat' => null,
							),
							'Zuhr'    => array(
								'start'  => $today_data['zuhr_start'],
								'jamaat' => $today_data['zuhr_jamaat'],
							),
							'Asr'     => array(
								'start'  => $today_data['asr_start'],
								'jamaat' => $today_data['asr_jamaat'],
							),
							'Maghrib' => array(
								'start'  => $today_data['maghrib_start'],
								'jamaat' => $today_data['maghrib_jamaat'],
							),
							'Isha'    => array(
								'start'  => $today_data['isha_start'],
								'jamaat' => $today_data['isha_jamaat'],
							),
						);

						// Replace Zuhr with Jummah if it's Friday
						if ( wp_date( 'N' ) === 5 && ( $today_data['jummah_1'] || $today_data['jummah_2'] ) ) {
							unset( $prayers['Zuhr'] ); // Remove Zuhr on Friday

							// Build Jummah time display
							$jummah_times = array();
							if ( $today_data['jummah_1'] ) {
								$jummah_times[] = $today_data['jummah_1'];
							}
							if ( $today_data['jummah_2'] ) {
								$jummah_times[] = $today_data['jummah_2'];
							}

							$prayers = array_slice( $prayers, 0, 2, true ) +
								array(
									'Jummah' => array(
										'start'  => implode( ' / ', $jummah_times ),
										'jamaat' => null,
									),
								) +
								array_slice( $prayers, 3, null, true );
						}

						$next_prayer = $this->get_next_prayer_name();
						foreach ( $prayers as $name => $times ) :
							$is_next = ( $next_prayer === $name );
							?>
							<div class="prayer-item <?php echo esc_attr( $is_next ? 'next-prayer' : '' ); ?>">
								<div class="prayer-name"><?php echo esc_html( mt_apply_terminology( $name ) ); ?></div>
								<div class="prayer-times">
									<div class="prayer-start-time"><?php echo esc_html( $times['start'] ); ?></div>
									<?php if ( $times['jamaat'] && 'Sunrise' !== $name ) : ?>
										<div class="prayer-jamaat-time"><?php echo esc_html( mt_apply_terminology( 'Jamaat' ) ); ?>: <?php echo esc_html( $times['jamaat'] ); ?></div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="mosque-error">
						No prayer times available for today. Please contact <?php echo esc_html( $mosque_name ); ?>.
					</div>
				<?php endif; ?>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Shortcode: Prayer countdown
		 */
		public function shortcode_prayer_countdown( $atts ) {
			$atts = shortcode_atts(
				array(
					'type' => 'next', // 'next' or specific prayer name
				),
				$atts,
				'prayer_countdown'
			);

			$type = sanitize_text_field( $atts['type'] );

			// Get next prayer data
			$next_prayer_data = $this->get_next_prayer_data();

			ob_start();
			?>
			<div class="prayer-countdown-container">
				<div class="countdown-header">
					<div class="countdown-title">Next Prayer</div>
					<?php if ( $next_prayer_data ) : ?>
						<div class="countdown-next-prayer"><?php echo esc_html( $next_prayer_data['name'] ); ?></div>
						<div class="countdown-next-time"><?php echo esc_html( $next_prayer_data['time'] ); ?></div>
					<?php endif; ?>
				</div>

				<div class="prayer-countdown"
					data-target="<?php echo esc_attr( isset( $next_prayer_data['datetime'] ) ? $next_prayer_data['datetime'] : '' ); ?>"
					data-prayer="<?php echo esc_attr( isset( $next_prayer_data['name'] ) ? $next_prayer_data['name'] : '' ); ?>">
					<?php if ( $next_prayer_data ) : ?>
						<div class="countdown-timer">
							<div class="countdown-unit">
								<span class="countdown-number">00</span>
								<span class="countdown-label">Hours</span>
							</div>
							<div class="countdown-unit">
								<span class="countdown-number">00</span>
								<span class="countdown-label">Minutes</span>
							</div>
							<div class="countdown-unit">
								<span class="countdown-number">00</span>
								<span class="countdown-label">Seconds</span>
							</div>
						</div>
					<?php else : ?>
						<div class="mosque-error">
							Unable to calculate next prayer time. Please check your timetable data.
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Helper: Render prayer time cell
		 */
		private function render_prayer_cell( $start_time, $jamaat_time ) {
			if ( ! $start_time ) {
				return '<td></td>';
			}

			$html  = '<td><div class="prayer-time">';
			$html .= '<div class="prayer-start">' . esc_html( $start_time ) . '</div>';
			if ( $jamaat_time ) {
				$html .= '<div class="prayer-jamaat">' . esc_html( $jamaat_time ) . '</div>';
			}
			$html .= '</div></td>';

			return $html;
		}

		/**
		 * Get month prayer data
		 */
		public function get_month_prayer_data( int $year, int $month ): array {
			// Use the year-aware mt_get_month_rows function instead of direct ACF access
			$daily_prayers = mt_get_month_rows( $month, $year );

			if ( ! $daily_prayers ) {
				return array();
			}

			$months = array(
				1  => 'January',
				2  => 'February',
				3  => 'March',
				4  => 'April',
				5  => 'May',
				6  => 'June',
				7  => 'July',
				8  => 'August',
				9  => 'September',
				10 => 'October',
				11 => 'November',
				12 => 'December',
			);

			return array(
				'month_name' => $months[ $month ] . ' ' . $year,
				'days'       => $daily_prayers,
			);
		}

		/**
		 * Get today's prayer data
		 */
		public function get_today_prayer_data() {
			$today = wp_date( 'Y-m-d' );
			$year  = wp_date( 'Y' );
			$month = wp_date( 'n' );

			$month_data = $this->get_month_prayer_data( (int) $year, (int) $month );

			if ( ! $month_data || ! $month_data['days'] ) {
				return null;
			}

			foreach ( $month_data['days'] as $day ) {
				if ( $day['date_full'] === $today ) {
					return $day;
				}
			}

			return null;
		}

		/**
		 * Get next prayer data
		 */
		private function get_next_prayer_data() {
			$today_data = $this->get_today_prayer_data();

			if ( ! $today_data ) {
				return null;
			}

			$now        = new DateTime();
			$today_date = $now->format( 'Y-m-d' );

			// Define prayer times in order
			$prayer_times = array(
				'Fajr'    => $today_data['fajr_start'],
				'Sunrise' => $today_data['sunrise'],
				'Zuhr'    => $today_data['zuhr_start'],
				'Asr'     => $today_data['asr_start'],
				'Maghrib' => $today_data['maghrib_start'],
				'Isha'    => $today_data['isha_start'],
			);

			// Replace Zuhr with Jummah on Friday
			if ( $now->format( 'N' ) === 5 ) { // Friday
				if ( $today_data['jummah_1'] ) {
					$prayer_times['Jummah'] = $today_data['jummah_1'];
					unset( $prayer_times['Zuhr'] );
				}
			}

			// Find next prayer
			foreach ( $prayer_times as $prayer_name => $prayer_time ) {
				if ( ! $prayer_time ) {
					continue;
				}

				$prayer_datetime = new DateTime( $today_date . ' ' . $prayer_time );

				if ( $prayer_datetime > $now ) {
					return array(
						'name'     => $prayer_name,
						'time'     => $prayer_time,
						'datetime' => $prayer_datetime->format( 'Y-m-d H:i:s' ),
					);
				}
			}

			// If no prayer found today, get tomorrow's Fajr
			$tomorrow      = $now->modify( '+1 day' );
			$tomorrow_data = $this->get_day_prayer_data( $tomorrow->format( 'Y' ), $tomorrow->format( 'n' ), $tomorrow->format( 'j' ) );

			if ( $tomorrow_data && $tomorrow_data['fajr_start'] ) {
				$fajr_datetime = new DateTime( $tomorrow->format( 'Y-m-d' ) . ' ' . $tomorrow_data['fajr_start'] );

				return array(
					'name'     => 'Fajr',
					'time'     => $tomorrow_data['fajr_start'],
					'datetime' => $fajr_datetime->format( 'Y-m-d H:i:s' ),
				);
			}

			return null;
		}

		/**
		 * Get specific day prayer data
		 */
		private function get_day_prayer_data( $year, $month, $day ) {
			$month_data = $this->get_month_prayer_data( $year, $month );

			if ( ! $month_data || ! $month_data['days'] ) {
				return null;
			}

			foreach ( $month_data['days'] as $day_data ) {
				if ( $day_data['day_number'] === $day ) {
					return $day_data;
				}
			}

			return null;
		}

		/**
		 * Get next prayer name only
		 */
		private function get_next_prayer_name() {
			$next_prayer_data = $this->get_next_prayer_data();
			return $next_prayer_data ? $next_prayer_data['name'] : null;
		}

		/**
		 * Generate ICS calendar content
		 */
		private function generate_ics_content( $year, $month = null, $prayer_types = 'both', $reminder = 15 ) {
			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';

			$ics_content  = "BEGIN:VCALENDAR\r\n";
			$ics_content .= "VERSION:2.0\r\n";
			$ics_content .= "PRODID:-//Mosque Timetable Plugin//Prayer Times//EN\r\n";
			$ics_content .= "CALSCALE:GREGORIAN\r\n";
			$ics_content .= "METHOD:PUBLISH\r\n";
			$ics_content .= 'X-WR-CALNAME:' . $this->escape_ics_text( $mosque_name . ' Prayer Times' ) . "\r\n";
			$ics_content .= 'X-WR-CALDESC:' . $this->escape_ics_text( 'Prayer timetable for ' . $mosque_name ) . "\r\n";
			$timezone     = wp_timezone_string();
			$ics_content .= 'X-WR-TIMEZONE:' . $timezone . "\r\n";

			// Get prayer data
			if ( $month ) {
				$months = array( $month );
			} else {
				$months = range( 1, 12 );
			}

			foreach ( $months as $m ) {
				$month_data = $this->get_month_prayer_data( $year, $m );

				if ( ! $month_data || ! $month_data['days'] ) {
					continue;
				}

				foreach ( $month_data['days'] as $day ) {
					if ( ! $day['date_full'] ) {
						continue;
					}

					$date_obj    = new DateTime( $day['date_full'] );
					$date_string = $date_obj->format( 'Ymd' );
					$is_friday   = $date_obj->format( 'N' ) === 5;

					// Prayer times to include
					$prayers = array(
						'Fajr'    => array(
							'start'  => $day['fajr_start'],
							'jamaat' => $day['fajr_jamaat'],
						),
						'Sunrise' => array(
							'start'  => $day['sunrise'],
							'jamaat' => null,
						),
						'Zuhr'    => array(
							'start'  => $day['zuhr_start'],
							'jamaat' => $day['zuhr_jamaat'],
						),
						'Asr'     => array(
							'start'  => $day['asr_start'],
							'jamaat' => $day['asr_jamaat'],
						),
						'Maghrib' => array(
							'start'  => $day['maghrib_start'],
							'jamaat' => $day['maghrib_jamaat'],
						),
						'Isha'    => array(
							'start'  => $day['isha_start'],
							'jamaat' => $day['isha_jamaat'],
						),
					);

					// Replace Zuhr with Jummah on Friday
					if ( $is_friday && ( $day['jummah_1'] || $day['jummah_2'] ) ) {
						unset( $prayers['Zuhr'] );
						if ( $day['jummah_1'] ) {
							$prayers['Jummah 1'] = array(
								'start'  => $day['jummah_1'],
								'jamaat' => null,
							);
						}
						if ( $day['jummah_2'] ) {
							$prayers['Jummah 2'] = array(
								'start'  => $day['jummah_2'],
								'jamaat' => null,
							);
						}
					}

					foreach ( $prayers as $prayer_name => $times ) {
						// Add start times
						if ( $times['start'] && ( 'start' === $prayer_types || 'both' === $prayer_types ) ) {
							$ics_content .= $this->create_ics_event(
								$prayer_name . ' - Start',
								$date_string,
								$times['start'],
								$reminder,
								$mosque_name,
								$mosque_address,
								$timezone
							);
						}

						// Add jamaat times (except for Sunrise and Jummah)
						if ( $times['jamaat'] && ( 'jamaat' === $prayer_types || 'both' === $prayer_types ) && 'Sunrise' !== $prayer_name && ! strpos( $prayer_name, 'Jummah' ) ) {
							$ics_content .= $this->create_ics_event(
								$prayer_name . ' - Jamaat',
								$date_string,
								$times['jamaat'],
								$reminder,
								$mosque_name,
								$mosque_address,
								$timezone
							);
						}
					}
				}
			}

			$ics_content .= "END:VCALENDAR\r\n";

			return $ics_content;
		}

		/**
		 * Create ICS event
		 */
		private function create_ics_event( $title, $date, $time, $reminder, $mosque_name, $mosque_address, $timezone ) {
			$dt  = new DateTime( $date . ' ' . $time, new DateTimeZone( $timezone ) );
			$end = clone $dt;
			$end->modify( '+30 minutes' );
			$uid = md5( $title . $dt->format( 'Ymd\THis' ) . $mosque_name );
			$now = gmdate( 'Ymd\THis\Z' );

			$location = $mosque_name . ( $mosque_address ? ', ' . $mosque_address : '' );

			$event  = "BEGIN:VEVENT\r\n";
			$event .= 'UID:' . $uid . "\r\n";
			$event .= 'DTSTAMP:' . $now . "\r\n";
			$event .= "DTSTART;TZID={$timezone}:" . $dt->format( 'Ymd\THis' ) . "\r\n";
			$event .= "DTEND;TZID={$timezone}:" . $end->format( 'Ymd\THis' ) . "\r\n";
			$event .= 'SUMMARY:' . $this->escape_ics_text( $title ) . "\r\n";
			$event .= 'DESCRIPTION:' . $this->escape_ics_text( 'Prayer time at ' . $mosque_name ) . "\r\n";
			$event .= 'LOCATION:' . $this->escape_ics_text( $location ) . "\r\n";
			$event .= "CATEGORIES:Religious\r\n";

			if ( $reminder > 0 ) {
				$event .= "BEGIN:VALARM\r\n";
				$event .= 'TRIGGER:-PT' . $reminder . "M\r\n";
				$event .= 'DESCRIPTION:' . $this->escape_ics_text( 'Prayer time reminder' ) . "\r\n";
				$event .= "ACTION:DISPLAY\r\n";
				$event .= "END:VALARM\r\n";
			}

			$event .= "END:VEVENT\r\n";

			return $event;
		}

		/**
		 * Generate enhanced ICS content with new options
		 */
		private function generate_enhanced_ics_content( $options ) {
			$defaults = array(
				'date_range'    => 'year',
				'year'          => wp_date( 'Y' ),
				'month'         => null,
				'include_jamah' => true,
				'alarms'        => array(),
				'jummah'        => 'both',
				'sunrise_alarm' => 0,
			);

			$options = array_merge( $defaults, $options );

			$mosque_name    = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';
			$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';

			// Generate ICS header
			$ics_content  = "BEGIN:VCALENDAR\r\n";
			$ics_content .= "VERSION:2.0\r\n";
			$ics_content .= "PRODID:-//Mosque Timetable Plugin//Prayer Times//EN\r\n";
			$ics_content .= "CALSCALE:GREGORIAN\r\n";
			$ics_content .= "METHOD:PUBLISH\r\n";
			$ics_content .= 'X-WR-CALNAME:' . $this->escape_ics_text( $mosque_name . ' Prayer Times' ) . "\r\n";
			$ics_content .= 'X-WR-CALDESC:' . $this->escape_ics_text( 'Prayer timetable for ' . $mosque_name ) . "\r\n";
			$timezone     = wp_timezone_string();
			$ics_content .= 'X-WR-TIMEZONE:' . $timezone . "\r\n";

			// Determine months to process
			if ( 'month' === $options['date_range'] && $options['month'] ) {
				$months = array( $options['month'] );
			} else {
				$months = range( 1, 12 );
			}

			foreach ( $months as $m ) {
				$month_data = $this->get_month_prayer_data( (int) $options['year'], (int) $m );

				if ( ! $month_data || ! $month_data['days'] ) {
					continue;
				}

				foreach ( $month_data['days'] as $day ) {
					if ( ! $day['date_full'] ) {
						continue;
					}

					$date_obj  = new DateTime( $day['date_full'] );
					$is_friday = $date_obj->format( 'N' ) === 5;

					// Build prayer list
					$prayers = array();

					// Always include start times
					$prayers['Fajr']    = array(
						'time' => $day['fajr_start'],
						'type' => 'start',
					);
					$prayers['Sunrise'] = array(
						'time' => $day['sunrise'],
						'type' => 'info',
					);

					// Handle Friday/Jummah logic
					if ( $is_friday ) {
						// On Fridays, include Jummah instead of Zuhr based on selection
						if ( 'both' === $options['jummah'] || '1st' === $options['jummah'] ) {
							$prayers['Jummah 1'] = array(
								'time' => $day['jummah_1'],
								'type' => 'start',
							);
						}
						if ( 'both' === $options['jummah'] || '2nd' === $options['jummah'] ) {
							$prayers['Jummah 2'] = array(
								'time' => $day['jummah_2'],
								'type' => 'start',
							);
						}
					} else {
						$prayers['Zuhr'] = array(
							'time' => $day['zuhr_start'],
							'type' => 'start',
						);
					}

					$prayers['Asr']     = array(
						'time' => $day['asr_start'],
						'type' => 'start',
					);
					$prayers['Maghrib'] = array(
						'time' => $day['maghrib_start'],
						'type' => 'start',
					);
					$prayers['Isha']    = array(
						'time' => $day['isha_start'],
						'type' => 'start',
					);

					// Add Jamāʿah times if requested
					if ( $options['include_jamah'] ) {
						$prayers['Fajr Jamāʿah'] = array(
							'time' => $day['fajr_jamaat'],
							'type' => 'jamaat',
						);
						if ( ! $is_friday ) {
							$prayers['Zuhr Jamāʿah'] = array(
								'time' => $day['zuhr_jamaat'],
								'type' => 'jamaat',
							);
						}
						$prayers['Asr Jamāʿah']     = array(
							'time' => $day['asr_jamaat'],
							'type' => 'jamaat',
						);
						$prayers['Maghrib Jamāʿah'] = array(
							'time' => $day['maghrib_jamaat'],
							'type' => 'jamaat',
						);
						$prayers['Isha Jamāʿah']    = array(
							'time' => $day['isha_jamaat'],
							'type' => 'jamaat',
						);
					}

					// Generate events for each prayer
					foreach ( $prayers as $prayer_name => $prayer_data ) {
						if ( empty( $prayer_data['time'] ) ) {
							continue;
						}

						$ics_content .= $this->create_enhanced_ics_event(
							$prayer_name,
							$date_obj,
							$prayer_data['time'],
							$mosque_name,
							$mosque_address,
							$timezone,
							$options['alarms'],
							$prayer_data['type']
						);
					}

					// Add sunrise warning if requested
					if ( $options['sunrise_alarm'] > 0 && ! empty( $day['sunrise'] ) ) {
						$sunrise_time = DateTime::createFromFormat( 'Y-m-d H:i', $day['date_full'] . ' ' . $day['sunrise'] );
						if ( $sunrise_time ) {
							$warning_time = clone $sunrise_time;
							$warning_time->sub( new DateInterval( 'PT' . $options['sunrise_alarm'] . 'M' ) );

							$ics_content .= $this->create_enhanced_ics_event(
								'End of Fajr Time',
								$warning_time,
								$warning_time->format( 'H:i' ),
								$mosque_name,
								$mosque_address,
								$timezone,
								array( 0 ), // No additional alarms for warnings
								'warning'
							);
						}
					}
				}
			}

			$ics_content .= "END:VCALENDAR\r\n";

			return $ics_content;
		}

		/**
		 * Create enhanced ICS event with multiple alarms
		 */
		private function create_enhanced_ics_event( $title, $date_obj, $time, $mosque_name, $mosque_address, $timezone, $alarms, $type = 'start' ) {
			if ( empty( $time ) ) {
				return '';
			}

			// Parse time
			$time_parts = explode( ':', $time );
			if ( count( $time_parts ) !== 2 ) {
				return '';
			}

			$dt = clone $date_obj;
			$dt->setTime( intval( $time_parts[0] ), intval( $time_parts[1] ) );

			// Event duration (5 minutes for prayers, 1 minute for info/warnings)
			$duration = ( 'info' === $type || 'warning' === $type ) ? 1 : 5;
			$end      = clone $dt;
			$end->add( new DateInterval( 'PT' . $duration . 'M' ) );

			// Generate unique ID
			$uid = md5( $title . $dt->format( 'Y-m-d H:i:s' ) ) . '@mosque-timetable';
			$now = wp_date( 'Ymd\THis\Z' );

			$event  = "BEGIN:VEVENT\r\n";
			$event .= 'UID:' . $uid . "\r\n";
			$event .= 'DTSTAMP:' . $now . "\r\n";
			$event .= "DTSTART;TZID={$timezone}:" . $dt->format( 'Ymd\THis' ) . "\r\n";
			$event .= "DTEND;TZID={$timezone}:" . $end->format( 'Ymd\THis' ) . "\r\n";
			$event .= 'SUMMARY:' . $this->escape_ics_text( $title ) . "\r\n";

			// Different descriptions based on type
			$description = '';
			switch ( $type ) {
				case 'jamaat':
					$description = 'Congregation prayer at ' . $mosque_name;
					break;
				case 'info':
					$description = 'Sunrise time at ' . $mosque_name;
					break;
				case 'warning':
					$description = 'End of Fajr prayer time';
					break;
				default:
					$description = 'Prayer time at ' . $mosque_name;
			}

			$event .= 'DESCRIPTION:' . $this->escape_ics_text( $description ) . "\r\n";
			$event .= 'LOCATION:' . $this->escape_ics_text( $mosque_address ?: $mosque_name ) . "\r\n";
			$event .= "CATEGORIES:Religious\r\n";

			// Add multiple alarms
			foreach ( $alarms as $alarm_minutes ) {
				$alarm_minutes = intval( $alarm_minutes );
				if ( $alarm_minutes >= 0 ) {
					$event .= "BEGIN:VALARM\r\n";
					if ( 0 === $alarm_minutes ) {
						$event .= "TRIGGER:PT0M\r\n";
					} else {
						$event .= 'TRIGGER:-PT' . $alarm_minutes . "M\r\n";
					}
					$event .= 'DESCRIPTION:' . $this->escape_ics_text( $title . ' reminder' ) . "\r\n";
					$event .= "ACTION:DISPLAY\r\n";
					$event .= "END:VALARM\r\n";
				}
			}

			$event .= "END:VEVENT\r\n";

			return $event;
		}

		/**
		 * Escape text for ICS format
		 */
		private function escape_ics_text( $text ) {
			$text = str_replace( array( '\\', "\n", "\r", ',', ';' ), array( '\\\\', "\\n", "\\r", '\\,', '\\;' ), $text );
			return $text;
		}

		/**
		 * Process CSV import
		 */
		private function process_csv_import( $csv_file, $month ) {
			if ( UPLOAD_ERR_OK !== $csv_file['error'] ) {
				return new WP_Error( 'upload_error', 'File upload failed' );
			}

			$fs           = mt_fs();
			$file_content = '';
			if ( $fs && $fs->exists( $csv_file['tmp_name'] ) ) {
				$file_content = $fs->get_contents( $csv_file['tmp_name'] );
			}
			if ( ! $file_content ) {
				return new WP_Error( 'read_error', 'Could not read CSV file' );
			}

			$lines = str_getcsv( $file_content, "\n" );
			if ( empty( $lines ) ) {
				return new WP_Error( 'empty_file', 'CSV file is empty' );
			}

			// Parse header (first line)
			$header           = str_getcsv( $lines[0] );
			$expected_columns = array(
				'Date',
				'Fajr_Start',
				'Fajr_Jamaat',
				'Sunrise',
				'Zuhr_Start',
				'Zuhr_Jamaat',
				'Asr_Start',
				'Asr_Jamaat',
				'Maghrib_Start',
				'Maghrib_Jamaat',
				'Isha_Start',
				'Isha_Jamaat',
				'Jummah_1',
				'Jummah_2',
			);

			// Validate header
			if ( count( $header ) < 13 ) { // At least date + 12 prayer times
				return new WP_Error( 'invalid_format', 'CSV file does not have required columns' );
			}

			$prayer_data = array();
			$year        = wp_date( 'Y' );

			// Process data rows
			for ( $i = 1; $i < count( $lines ); $i++ ) {
				if ( empty( trim( $lines[ $i ] ) ) ) {
					continue;
				}

				$row = str_getcsv( $lines[ $i ] );
				if ( count( $row ) < 13 ) {
					continue;
				}

				// Parse date
				$date_str    = trim( $row[0] );
				$parsed_date = $this->parse_flexible_date( $date_str, $year, $month );

				if ( ! $parsed_date ) {
					continue; // Skip invalid dates
				}

				$day_data = array(
					'day_number'     => $parsed_date['day'],
					'date_full'      => $parsed_date['full_date'],
					'hijri_date'     => $this->calculate_hijri_date( $parsed_date['full_date'] ),
					'fajr_start'     => $this->parse_time( $row[1] ),
					'fajr_jamaat'    => $this->parse_time( $row[2] ),
					'sunrise'        => $this->parse_time( $row[3] ),
					'zuhr_start'     => $this->parse_time( $row[4] ),
					'zuhr_jamaat'    => $this->parse_time( $row[5] ),
					'asr_start'      => $this->parse_time( $row[6] ),
					'asr_jamaat'     => $this->parse_time( $row[7] ),
					'maghrib_start'  => $this->parse_time( $row[8] ),
					'maghrib_jamaat' => $this->parse_time( $row[9] ),
					'isha_start'     => $this->parse_time( $row[10] ),
					'isha_jamaat'    => $this->parse_time( $row[11] ),
					'jummah_1'       => isset( $row[12] ) ? $this->parse_time( $row[12] ) : '',
					'jummah_2'       => isset( $row[13] ) ? $this->parse_time( $row[13] ) : '',
				);

				$prayer_data[] = $day_data;
			}

			if ( empty( $prayer_data ) ) {
				return new WP_Error( 'no_valid_data', 'No valid prayer time data found in CSV' );
			}

			// Save to ACF
			$result = $this->save_month_prayer_data( $year, $month, $prayer_data );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return array(
				'imported_rows' => count( $prayer_data ),
				'month'         => $month,
				'year'          => $year,
			);
		}

		/**
		 * Parse flexible date formats
		 */
		private function parse_flexible_date( $date_str, $year, $month ) {
			// Remove extra spaces
			$date_str = preg_replace( '/\s+/', ' ', trim( $date_str ) );

			// Try different date formats
			$formats = array(
				'd/m/Y',
				'd.m.Y',
				'd-m-Y', // Day first with year
				'd/m',
				'd.m',
				'd-m',       // Day first without year
				'j/n/Y',
				'j.n.Y',
				'j-n-Y', // Single digits
				'j/n',
				'j.n',
				'j-n',       // Single digits without year
				'j',                         // Just day number
			);

			foreach ( $formats as $format ) {
				$parsed = DateTime::createFromFormat( $format, $date_str );
				if ( false !== $parsed ) {
					// If year is missing, use provided year
					if ( false === strpos( $format, 'Y' ) ) {
						$parsed->setDate( $year, $month, (int) $parsed->format( 'j' ) );
					}
					// If month is missing, use provided month
					if ( strpos( $format, 'm' ) === false && strpos( $format, 'n' ) === false ) {
						$parsed->setDate( $year, $month, intval( $date_str ) );
					}

					return array(
						'day'       => $parsed->format( 'j' ),
						'full_date' => $parsed->format( 'Y-m-d' ),
					);
				}
			}

			return null;
		}

		/**
		 * Parse time format
		 */
		private function parse_time( $time_str ) {
			$time_str = trim( $time_str );
			if ( empty( $time_str ) ) {
				return '';
			}

			// Handle different time formats
			if ( preg_match( '/^(\d{1,2}):(\d{2})$/', $time_str, $matches ) ) {
				return sprintf( '%02d:%02d', $matches[1], $matches[2] );
			}

			if ( preg_match( '/^(\d{1,2})\.(\d{2})$/', $time_str, $matches ) ) {
				return sprintf( '%02d:%02d', $matches[1], $matches[2] );
			}

			if ( preg_match( '/^(\d{1,2})(\d{2})$/', $time_str, $matches ) && strlen( $time_str ) === 3 || strlen( $time_str ) === 4 ) {
				$hour   = substr( $time_str, 0, -2 );
				$minute = substr( $time_str, -2 );
				return sprintf( '%02d:%02d', $hour, $minute );
			}

			return '';
		}

		/**
		 * Save month prayer data
		 */
		private function save_month_prayer_data( $year, $month, $prayer_data ) {
			$field_name = 'daily_prayers_' . $month;

			// Save to ACF using the new field structure
			$result = update_field( $field_name, $prayer_data, 'option' );

			if ( ! $result ) {
				return new WP_Error( 'save_error', 'Failed to save prayer data' );
			}

			return true;
		}

		/**
		 * Generate prayer events schema
		 */
		private function generate_prayer_events_schema( $today_data, $mosque_name, $mosque_address ) {
			$events     = array();
			$today_date = wp_date( 'Y-m-d' );
			$is_friday  = wp_date( 'N' ) === 5;

			$prayers = array(
				'Fajr'    => array(
					'start'  => $today_data['fajr_start'],
					'jamaat' => $today_data['fajr_jamaat'],
				),
				'Sunrise' => array(
					'start'  => $today_data['sunrise'],
					'jamaat' => null,
				),
				'Zuhr'    => array(
					'start'  => $today_data['zuhr_start'],
					'jamaat' => $today_data['zuhr_jamaat'],
				),
				'Asr'     => array(
					'start'  => $today_data['asr_start'],
					'jamaat' => $today_data['asr_jamaat'],
				),
				'Maghrib' => array(
					'start'  => $today_data['maghrib_start'],
					'jamaat' => $today_data['maghrib_jamaat'],
				),
				'Isha'    => array(
					'start'  => $today_data['isha_start'],
					'jamaat' => $today_data['isha_jamaat'],
				),
			);

			// Replace Zuhr with Jummah on Friday
			if ( $is_friday && ( $today_data['jummah_1'] || $today_data['jummah_2'] ) ) {
				unset( $prayers['Zuhr'] );
				if ( $today_data['jummah_1'] ) {
					$prayers['Jummah 1'] = array(
						'start'  => $today_data['jummah_1'],
						'jamaat' => null,
					);
				}
				if ( $today_data['jummah_2'] ) {
					$prayers['Jummah 2'] = array(
						'start'  => $today_data['jummah_2'],
						'jamaat' => null,
					);
				}
			}

			foreach ( $prayers as $prayer_name => $times ) {
				if ( ! $times['start'] ) {
					continue;
				}

				$start_datetime = $today_date . 'T' . $times['start'] . ':00';
				$end_datetime   = $today_date . 'T' . $times['start'] . ':00'; // Same time for start/end

				$event_schema = array(
					'@context'            => 'https://schema.org',
					'@type'               => 'Event',
					'@id'                 => get_site_url() . '#prayer-' . strtolower( str_replace( ' ', '-', $prayer_name ) ) . '-' . $today_date,
					'name'                => $prayer_name . ' Prayer',
					'description'         => $prayer_name . ' prayer time at ' . $mosque_name,
					'startDate'           => $start_datetime,
					'endDate'             => $end_datetime,
					'location'            => array(
						'@type'   => 'Place',
						'name'    => $mosque_name,
						'address' => array(
							'@type'         => 'PostalAddress',
							'streetAddress' => $mosque_address,
						),
					),
					'organizer'           => array(
						'@type' => 'Organization',
						'name'  => $mosque_name,
						'url'   => get_site_url(),
					),
					'eventStatus'         => 'https://schema.org/EventScheduled',
					'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
					'isAccessibleForFree' => true,
					'audience'            => array(
						'@type'        => 'Audience',
						'audienceType' => 'Muslims',
					),
				);

				// Add jamaat time if available
				if ( $times['jamaat'] && 'Sunrise' !== $prayer_name ) {
					$jamaat_datetime = $today_date . 'T' . $times['jamaat'] . ':00';
					$jamaat_schema   = array(
						'@context'            => 'https://schema.org',
						'@type'               => 'Event',
						'@id'                 => get_site_url() . '#prayer-' . strtolower( str_replace( ' ', '-', $prayer_name ) ) . '-jamaat-' . $today_date,
						'name'                => $prayer_name . ' Prayer - Congregation (Jamaat)',
						'description'         => $prayer_name . ' prayer congregation time at ' . $mosque_name,
						'startDate'           => $jamaat_datetime,
						'endDate'             => $jamaat_datetime,
						'location'            => $event_schema['location'],
						'organizer'           => $event_schema['organizer'],
						'eventStatus'         => $event_schema['eventStatus'],
						'eventAttendanceMode' => $event_schema['eventAttendanceMode'],
						'isAccessibleForFree' => true,
						'audience'            => $event_schema['audience'],
					);

					$events[] = $jamaat_schema;
				}

				$events[] = $event_schema;
			}

			return $events;
		}

		/**
		 * Generate month date structure
		 */
		public function generate_month_structure( $year, $month ) {
			$days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );
			$month_data    = array();

			for ( $day = 1; $day <= $days_in_month; $day++ ) {
				$date_str   = sprintf( '%04d-%02d-%02d', $year, $month, $day );
				$hijri_date = $this->calculate_hijri_date( $date_str );

				$month_data[] = array(
					'day_number'     => $day,
					'date_full'      => $date_str,
					'hijri_date'     => $hijri_date,
					'fajr_start'     => '',
					'fajr_jamaat'    => '',
					'sunrise'        => '',
					'zuhr_start'     => '',
					'zuhr_jamaat'    => '',
					'asr_start'      => '',
					'asr_jamaat'     => '',
					'maghrib_start'  => '',
					'maghrib_jamaat' => '',
					'isha_start'     => '',
					'isha_jamaat'    => '',
					'jummah_1'       => '',
					'jummah_2'       => '',
				);
			}

			// Save to ACF field
			$field_name = "daily_prayers_{$month}";
			return update_field( $field_name, array( 'days' => $month_data ), 'option' );
		}

		/**
		 * Save month data
		 */
		public function save_month_data( $year, $month, $data ) {
			$field_name = "daily_prayers_{$month}";
			return update_field( $field_name, $data, 'option' );
		}

		/**
		 * Reset month structure (keep dates, clear prayer times)
		 */
		public function reset_month_structure( $year, $month ) {
			$existing_data = $this->get_month_prayer_data( $year, $month );

			if ( $existing_data && isset( $existing_data['days'] ) ) {
				// Keep date structure, clear prayer times
				foreach ( $existing_data['days'] as &$day ) {
					$day['fajr_start']     = '';
					$day['fajr_jamaat']    = '';
					$day['sunrise']        = '';
					$day['zuhr_start']     = '';
					$day['zuhr_jamaat']    = '';
					$day['asr_start']      = '';
					$day['asr_jamaat']     = '';
					$day['maghrib_start']  = '';
					$day['maghrib_jamaat'] = '';
					$day['isha_start']     = '';
					$day['isha_jamaat']    = '';
					$day['jummah_1']       = '';
					$day['jummah_2']       = '';
				}

				$field_name = "daily_prayers_{$month}";
				return update_field( $field_name, $existing_data, 'option' );
			}

			return false;
		}

		/**
		 * Add RTL language detection and HTML dir attribute
		 */
		public function add_rtl_support() {
			// List of RTL language codes
			$rtl_languages = array(
				'ar',    // Arabic
				'he',    // Hebrew
				'fa',    // Persian/Farsi
				'ur',    // Urdu
				'ku',    // Kurdish
				'sd',    // Sindhi
				'ps',    // Pashto
				'dv',    // Divehi
				'yi',    // Yiddish
				'arc',   // Aramaic
			);

			// Get current site language
			$locale        = get_locale();
			$language_code = substr( $locale, 0, 2 );

			// Check if current language is RTL
			if ( in_array( $language_code, $rtl_languages, true ) ) {
				add_filter( 'language_attributes', array( $this, 'add_rtl_lang_attributes' ) );
			}
		}

		/**
		 * Add RTL direction to HTML lang attributes
		 */
		public function add_rtl_lang_attributes( $output ) {
			if ( strpos( $output, 'dir=' ) === false ) {
				$output .= ' dir="rtl"';
			}
			return $output;
		}
	}

	// Initialize the plugin
	MosqueTimetablePlugin::get_instance();

	// Hook to auto-calculate Hijri dates when date fields are updated
	add_filter(
		'acf/update_value/name=date_full',
		function ( $value, $post_id, $field ) {
			if ( $value ) {
				$plugin     = MosqueTimetablePlugin::get_instance();
				$hijri_date = $plugin->calculate_hijri_date( $value );

				// Find the current repeater row and update hijri_date field
				$parent_key = $field['parent'];
				if ( $parent_key && strpos( $parent_key, 'daily_prayers' ) !== false ) {
					// Extract row number from field key
					if ( preg_match( '/field_daily_prayers_(\d+)_date_full/', $field['key'], $matches ) ) {
						$row = $matches[1];
						update_field( "daily_prayers_{$row}_hijri_date", $hijri_date, $post_id );
					}
				}
			}
			return $value;
		},
		10,
		3
	);
	// This extends the existing class with the missing functionality
	add_action(
		'wp_loaded',
		function () {
			$mosque_plugin = MosqueTimetablePlugin::get_instance();

			// Generate all dates AJAX handler
			add_action(
				'wp_ajax_generate_all_dates',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					$year = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( get_option( 'default_year', intval( date( 'Y' ) ) ) );

					// Prefer ACF option when present, else fall back to all 12 months
					$months = array();
					if ( function_exists( 'get_field' ) ) {
						$acf_months = get_field( 'available_months', 'option' );
						if ( is_array( $acf_months ) && $acf_months ) {
							$months = array_map( 'intval', $acf_months );
						}
					}
					if ( ! $months ) {
						$months = range( 1, 12 );
					}

					$success_count = 0;
					foreach ( $months as $month ) {
						if ( $mosque_plugin->generate_month_structure( $year, intval( $month ) ) ) {
							$success_count++;
						}
					}

					wp_send_json_success( array( 'message' => "Generated {$success_count} out of " . count( $months ) . ' months' ) );
				}
			);

			// Generate month dates AJAX handler
			add_action(
				'wp_ajax_generate_month_dates',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					$month       = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					$year        = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : 0;
					$month_names = array(
						1  => 'January',
						2  => 'February',
						3  => 'March',
						4  => 'April',
						5  => 'May',
						6  => 'June',
						7  => 'July',
						8  => 'August',
						9  => 'September',
						10 => 'October',
						11 => 'November',
						12 => 'December',
					);

					if ( $mosque_plugin->generate_month_structure( $year, $month ) ) {
						wp_send_json_success( array( 'month_name' => $month_names[ $month ] ) );
					} else {
						wp_send_json_error( __( 'Failed to generate month dates', 'mosque-timetable' ) );
					}
				}
			);

			// Save all months AJAX handler
			add_action(
				'wp_ajax_save_all_months',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					$year          = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : 0;
					$data          = isset( $_POST['data'] ) && is_array( $_POST['data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['data'] ) ) : array();
					$success_count = 0;

					foreach ( $data as $month => $month_data ) {
						if ( $mosque_plugin->save_month_data( $year, intval( $month ), $month_data ) ) {
							$success_count++;
						}
					}

					wp_send_json_success( array( 'saved_months' => $success_count ) );
				}
			);

			// Import XLSX AJAX handler
			add_action(
				'wp_ajax_import_xlsx_timetable',
				function () use ( $mosque_plugin ) {
					// Security
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( 'Security check failed', 403 );
					}
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( 'Insufficient permissions', 403 );
					}

					// Inputs
					if ( ! isset( $_FILES['xlsx_file'] ) ) {
						wp_send_json_error( esc_html__( 'No file uploaded', 'mosque-timetable' ) );
					}
					$month = isset( $_POST['month'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['month'] ) ) : 0;
					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month specified', 'mosque-timetable' ) );
					}
					$year = isset( $_POST['year'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['year'] ) ) : (int) wp_date( 'Y' );

					$file = isset( $_FILES['xlsx_file'] ) && is_array( $_FILES['xlsx_file'] ) ? $_FILES['xlsx_file'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload validation handled below
					if ( ! $file ) {
						wp_send_json_error( esc_html__( 'No file uploaded', 'mosque-timetable' ) );
					}
					if ( UPLOAD_ERR_OK !== $file['error'] ) {
						/* translators: %d: Error code number */
						wp_send_json_error( sprintf( __( 'File upload error: %d', 'mosque-timetable' ), (int) $file['error'] ) );
					}
					$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
					if ( ! in_array( $ext, array( 'xlsx', 'xls' ), true ) ) {
						wp_send_json_error( __( 'Please upload Excel files (.xlsx or .xls) only.', 'mosque-timetable' ) );
					}

					// Library
					$simplexlsx_path = MOSQUE_TIMETABLE_PLUGIN_DIR . 'vendor/shuchkin/simplexlsx/src/SimpleXLSX.php';
					$fs              = mt_fs();
					if ( ! $fs || ! $fs->exists( $simplexlsx_path ) ) {
						wp_send_json_error( __( 'XLSX import requires SimpleXLSX library. Install composer deps or use CSV instead.', 'mosque-timetable' ) );
					}
					require_once $simplexlsx_path;

					// Time normaliser
					$norm = function ( $s ) {
						$s = trim( (string) $s );
						if ( '' === $s ) {
							return '';
						}
						$s = str_replace( array( '.', '-' ), ':', $s );
						if ( ! preg_match( '/^(\d{1,2}):(\d{1,2})$/', $s, $m ) ) {
							return '';
						}
						$h = max( 0, min( 23, (int) $m[1] ) );
						$i = max( 0, min( 59, (int) $m[2] ) );
						return sprintf( '%02d:%02d', $h, $i );
					};

					try {
						/** @phpstan-ignore-next-line */
						// @phpstan-ignore-line
						$xlsx = SimpleXLSX::parse( $file['tmp_name'] );
						if ( ! $xlsx ) {
							/** @phpstan-ignore-next-line */
							// @phpstan-ignore-line
							/* translators: %s: Error message from parser */
							wp_send_json_error( sprintf( __( 'Could not parse XLSX file: %s', 'mosque-timetable' ), SimpleXLSX::parseError() ) );
						}

						$rows           = $xlsx->rows();
						$month_rows     = array();
						$processed      = 0;
						$header_skipped = false;

						foreach ( $rows as $row_index => $data ) {
							// Optional header skip
							if ( ! $header_skipped && $mosque_plugin->is_header_row( $data ) ) {
								$header_skipped = true;
								continue;
							}

							// Determine mode
							$day_num = null;
							$date    = null;
							$start   = 0;

							// Case A: first col is day, optional second col is date
							if ( isset( $data[0] ) && is_numeric( $data[0] ) && (int) $data[0] >= 1 && (int) $data[0] <= 31 ) {
								$day_num = (int) $data[0];
								$start   = 1;
								if ( isset( $data[1] ) && $mosque_plugin->looks_like_date( $data[1] ) ) {
									$date  = sanitize_text_field( $data[1] );
									$start = 2;
								}
							}

							// If no explicit day, use sequential index (accounting for header)
							if ( ! $day_num ) {
								$day_num = count( $month_rows ) + 1; // sequential by accepted rows
							}

							// Auto-generate date if not provided
							if ( ! $date ) {
								$date = sprintf( '%04d-%02d-%02d', $year, $month, $day_num );
							}

							// Extract times (must be ≥ 12 for times-only)
							$times = array_slice( $data, $start );
							if ( count( $times ) < 12 ) {
								continue;
							}

							if ( $day_num >= 1 && $day_num <= 31 ) {
								$month_rows[] = array(
									'day_number'     => $day_num,
									'date_full'      => $date,
									'day_name'       => wp_date( 'l', strtotime( $date ) ),
									'hijri_date'     => $mosque_plugin->calculate_hijri_date( $date ),

									'fajr_start'     => $norm( $times[0] ?? '' ),
									'fajr_jamaat'    => $norm( $times[1] ?? '' ),
									'sunrise'        => $norm( $times[2] ?? '' ),
									'zuhr_start'     => $norm( $times[3] ?? '' ),
									'zuhr_jamaat'    => $norm( $times[4] ?? '' ),
									'asr_start'      => $norm( $times[5] ?? '' ),
									'asr_jamaat'     => $norm( $times[6] ?? '' ),
									'maghrib_start'  => $norm( $times[7] ?? '' ),
									'maghrib_jamaat' => $norm( $times[8] ?? '' ),
									'isha_start'     => $norm( $times[9] ?? '' ),
									'isha_jamaat'    => $norm( $times[10] ?? '' ),
									'jummah_1'       => $norm( $times[11] ?? '' ),
									'jummah_2'       => $norm( $times[12] ?? '' ),
								);
								$processed++;
							}
						}

						if ( 0 === $processed || empty( $month_rows ) ) {
							wp_send_json_error( __( 'No valid data found in the uploaded file', 'mosque-timetable' ) );
						}

						// Sort the imported rows by day_number in ascending order
						usort( $month_rows, fn( $a, $b ) => ( $a['day_number'] ?? 0 ) <=> ( $b['day_number'] ?? 0 ) );

						// Save the rows and capture success
						$ok = mt_save_month_rows( $month, $month_rows, $year );

						if ( $ok ) {
							wp_send_json_success(
								array(
									'imported_rows' => $processed,
									'message'       => "Successfully imported {$processed} days from Excel file for month {$month}",
								)
							);
						} else {
							wp_send_json_error( __( 'Failed to save imported data', 'mosque-timetable' ) );
						}
					} catch ( \Throwable $e ) {
						/* translators: %s: Error message from exception */
						wp_send_json_error( sprintf( __( 'Error parsing XLSX file: %s', 'mosque-timetable' ), $e->getMessage() ) );
					}
				}
			);

			// Import paste data AJAX handler
			add_action(
				'wp_ajax_import_paste_data',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( 'Security check failed', 403 );
					}
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( 'Insufficient permissions', 403 );
					}

					$month      = isset( $_POST['month'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['month'] ) ) : 0;
					$paste_data = isset( $_POST['paste_data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['paste_data'] ) ) : '';
					$year       = isset( $_POST['year'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['year'] ) ) : (int) wp_date( 'Y' );

					if ( $month < 1 || $month > 12 || empty( $paste_data ) ) {
						wp_send_json_error( __( 'Invalid month or empty data', 'mosque-timetable' ) );
					}

					$lines      = preg_split( "/\r\n|\n|\r/", trim( $paste_data ) );
					$processed  = 0;
					$month_rows = array();
					$row_index  = 0;

					// Normalise quick helper
					$norm = function ( $s ) {
						$s = trim( (string) $s );
						if ( '' === $s ) {
							return '';
						}
						$s = str_replace( array( '.', '-' ), ':', $s );
						if ( ! preg_match( '/^(\d{1,2}):(\d{1,2})$/', $s, $m ) ) {
							return '';
						}
						$h = max( 0, min( 23, (int) $m[1] ) );
						$i = max( 0, min( 59, (int) $m[2] ) );
						return sprintf( '%02d:%02d', $h, $i );
					};

					foreach ( $lines as $line ) {
						$line = trim( $line );
						if ( '' === $line ) {
							$row_index++;
							continue;
						}

						$cells   = str_getcsv( $line );
						$day_num = null;
						$date    = null;
						$start   = 0;

						// Case A: day + date + times (>= 13 columns)
						if ( count( $cells ) >= 13 && is_numeric( $cells[0] ) ) {
							$day_num = (int) $cells[0];
							$date    = sanitize_text_field( $cells[1] );
							$start   = 2;
						}
						// Case B: times-only (>= 12 columns)
						elseif ( count( $cells ) >= 12 ) {
							$day_num = $row_index + 1;
							$date    = sprintf( '%04d-%02d-%02d', $year, $month, $day_num );
							$start   = 0;
						} else {
							$row_index++;
							continue;
						}

						if ( $day_num < 1 || $day_num > 31 ) {
							$row_index++;
							continue;
						}

						$pt = array_slice( $cells, $start );

						$month_rows[] = array(
							'day_number'     => $day_num,
							'date_full'      => $date,
							'day_name'       => wp_date( 'l', strtotime( $date ) ),
							'hijri_date'     => $mosque_plugin->calculate_hijri_date( $date ),

							'fajr_start'     => $norm( $pt[0] ?? '' ),
							'fajr_jamaat'    => $norm( $pt[1] ?? '' ),
							'sunrise'        => $norm( $pt[2] ?? '' ),
							'zuhr_start'     => $norm( $pt[3] ?? '' ),
							'zuhr_jamaat'    => $norm( $pt[4] ?? '' ),
							'asr_start'      => $norm( $pt[5] ?? '' ),
							'asr_jamaat'     => $norm( $pt[6] ?? '' ),
							'maghrib_start'  => $norm( $pt[7] ?? '' ),
							'maghrib_jamaat' => $norm( $pt[8] ?? '' ),
							'isha_start'     => $norm( $pt[9] ?? '' ),
							'isha_jamaat'    => $norm( $pt[10] ?? '' ),
							'jummah_1'       => $norm( $pt[11] ?? '' ),
							'jummah_2'       => $norm( $pt[12] ?? '' ),
						);
						$processed++;
						$row_index++;
					}

					if ( 0 === $processed ) {
						wp_send_json_error( __( 'No valid rows found', 'mosque-timetable' ) );
					}

					usort( $month_rows, fn( $a, $b ) => ( $a['day_number'] ?? 0 ) <=> ( $b['day_number'] ?? 0 ) );

					$field = "daily_prayers_{$month}";
					$ok    = update_field( $field, $month_rows, 'option' );

					if ( $ok ) {
						wp_send_json_success(
							array(
								'imported_rows' => $processed,
								'message'       => "Successfully imported {$processed} days for month {$month}",
							)
						);
					} else {
						wp_send_json_error( __( 'Failed to save imported data', 'mosque-timetable' ) );
					}
				}
			);

			// Clear all data AJAX handler
			add_action(
				'wp_ajax_clear_all_data',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Clear all monthly timetable data
					for ( $month = 1; $month <= 12; $month++ ) {
						delete_field( "daily_prayers_{$month}", 'option' );
					}

					wp_send_json_success( 'All prayer time data cleared' );
				}
			);

			// Reset empty structure AJAX handler
			add_action(
				'wp_ajax_reset_empty_structure',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					$year        = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : 0;
					$reset_count = 0;

					for ( $month = 1; $month <= 12; $month++ ) {
						if ( $mosque_plugin->reset_month_structure( $year, $month ) ) {
							$reset_count++;
						}
					}

					wp_send_json_success( array( 'reset_months' => $reset_count ) );
				}
			);

			// Calculate Hijri date AJAX handler
			add_action(
				'wp_ajax_calculate_hijri_date',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					$date       = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
					$hijri_date = $mosque_plugin->calculate_hijri_date( $date );

					wp_send_json_success( $hijri_date );
				}
			);

			// AJAX: Refresh admin nonce

			function mosque_timetable_ajax_refresh_admin_nonce() {
				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_send_json_error( 'Insufficient permissions', 403 );
				}

				wp_send_json_success(
					array(
						'nonce' => wp_create_nonce( 'mosque_timetable_nonce' ),
					)
				);
			}
			add_action( 'wp_ajax_refresh_admin_nonce', 'mosque_timetable_ajax_refresh_admin_nonce' );

			// If guests should also refresh (usually no):
			// add_action( 'wp_ajax_nopriv_refresh_admin_nonce', 'mosque_timetable_ajax_refresh_admin_nonce' );

			// Get month timetable AJAX handler

			add_action(
				'wp_ajax_get_month_timetable',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( 'Security check failed', 400 );
					}

					$month = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					$year  = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : 0;

					$data = $mosque_plugin->get_month_prayer_data( $year, $month );
					wp_send_json_success( $data );
				}
			);
			// If front-end/guests call this, also add:
			// add_action( 'wp_ajax_nopriv_get_month_timetable', ...same callback... );

			// Download sample CSV template
			add_action(
				'wp_ajax_download_sample_csv',
				function () {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) ), 'mosque_sample_download' ) ) {
						wp_die( esc_html__( 'Security check failed', 'mosque-timetable' ) );
					}

					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_die( esc_html__( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					$filename = 'mosque-prayer-times-sample.csv';
					header( 'Content-Type: text/csv' );
					header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
					header( 'Cache-Control: no-cache, must-revalidate' );

					// Create sample CSV data with proper headers and example data
					$csv_data = array(
						array( 'Date', 'Day', 'Fajr Start', 'Fajr Jamaat', 'Sunrise', 'Zuhr Start', 'Zuhr Jamaat', 'Asr Start', 'Asr Jamaat', 'Maghrib Start', 'Maghrib Jamaat', 'Isha Start', 'Isha Jamaat', 'Jummah 1', 'Jummah 2' ),
						array( '2024-01-01', 'Monday', '06:15', '06:45', '08:05', '12:15', '13:00', '14:30', '15:00', '16:45', '16:50', '18:30', '19:00', '', '' ),
						array( '2024-01-02', 'Tuesday', '06:15', '06:45', '08:05', '12:16', '13:00', '14:31', '15:00', '16:46', '16:51', '18:31', '19:00', '', '' ),
						array( '2024-01-03', 'Wednesday', '06:15', '06:45', '08:04', '12:16', '13:00', '14:32', '15:00', '16:47', '16:52', '18:32', '19:00', '', '' ),
						array( '2024-01-04', 'Thursday', '06:15', '06:45', '08:04', '12:17', '13:00', '14:33', '15:00', '16:48', '16:53', '18:33', '19:00', '', '' ),
						array( '2024-01-05', 'Friday', '06:15', '06:45', '08:03', '12:17', '13:00', '14:34', '15:00', '16:49', '16:54', '18:34', '19:00', '13:30', '14:00' ),
						array( '2024-01-06', 'Saturday', '06:14', '06:45', '08:03', '12:18', '13:00', '14:35', '15:00', '16:50', '16:55', '18:35', '19:00', '', '' ),
						array( '2024-01-07', 'Sunday', '06:14', '06:45', '08:02', '12:18', '13:00', '14:36', '15:00', '16:51', '16:56', '18:36', '19:00', '', '' ),
					);

					$output = fopen( 'php://output', 'w' );
					foreach ( $csv_data as $row ) {
						fputcsv( $output, $row );
					}
					fclose( $output );
					exit;
				}
			);

			// Download sample XLSX template
			add_action(
				'wp_ajax_download_sample_xlsx',
				function () use ( $mosque_plugin ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) ), 'mosque_sample_download' ) ) {
						wp_die( esc_html__( 'Security check failed', 'mosque-timetable' ) );
					}

					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_die( esc_html__( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Check if SimpleXLSXGen is available
					$xlsx_path = MOSQUE_TIMETABLE_PLUGIN_DIR . 'vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php';
					$fs        = mt_fs();
					if ( ! $fs || ! $fs->exists( $xlsx_path ) ) {
						wp_die( 'XLSX generation not available. Please ensure SimpleXLSXGen is installed.' );
					}

					require_once $xlsx_path;

					$filename = 'mosque-prayer-times-sample.xlsx';

					// Create sample XLSX data
					$xlsx_data = array(
						array( 'Date', 'Day', 'Fajr Start', 'Fajr Jamaat', 'Sunrise', 'Zuhr Start', 'Zuhr Jamaat', 'Asr Start', 'Asr Jamaat', 'Maghrib Start', 'Maghrib Jamaat', 'Isha Start', 'Isha Jamaat', 'Jummah 1', 'Jummah 2' ),
						array( '2024-01-01', 'Monday', '06:15', '06:45', '08:05', '12:15', '13:00', '14:30', '15:00', '16:45', '16:50', '18:30', '19:00', '', '' ),
						array( '2024-01-02', 'Tuesday', '06:15', '06:45', '08:05', '12:16', '13:00', '14:31', '15:00', '16:46', '16:51', '18:31', '19:00', '', '' ),
						array( '2024-01-03', 'Wednesday', '06:15', '06:45', '08:04', '12:16', '13:00', '14:32', '15:00', '16:47', '16:52', '18:32', '19:00', '', '' ),
						array( '2024-01-04', 'Thursday', '06:15', '06:45', '08:04', '12:17', '13:00', '14:33', '15:00', '16:48', '16:53', '18:33', '19:00', '', '' ),
						array( '2024-01-05', 'Friday', '06:15', '06:45', '08:03', '12:17', '13:00', '14:34', '15:00', '16:49', '16:54', '18:34', '19:00', '13:30', '14:00' ),
						array( '2024-01-06', 'Saturday', '06:14', '06:45', '08:03', '12:18', '13:00', '14:35', '15:00', '16:50', '16:55', '18:35', '19:00', '', '' ),
						array( '2024-01-07', 'Sunday', '06:14', '06:45', '08:02', '12:18', '13:00', '14:36', '15:00', '16:51', '16:56', '18:36', '19:00', '', '' ),
					);

					try {
						/** @phpstan-ignore-next-line */
						// @phpstan-ignore-line
						$xlsx = SimpleXLSXGen::fromArray( $xlsx_data );

						// Use the correct method to download the file
						$xlsx->download( $filename );
						exit;
					} catch ( Exception $e ) {
						error_log( 'Mosque Timetable error: ' . $e->getMessage() ); // backend log
						echo esc_html__( 'Something went wrong. Please try again.', 'mosque-timetable' ); // safe UI
					}
				}
			);

			// PDF upload AJAX handler
			add_action(
				'wp_ajax_upload_month_pdf',
				function () {
					// Security check
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Validate inputs
					$month = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					$year  = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( wp_date( 'Y' ) );

					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month', 'mosque-timetable' ) );
					}

					// Check if file was uploaded
					if ( empty( $_FILES['pdf_file'] ) ) {
						wp_send_json_error( esc_html__( 'No file uploaded', 'mosque-timetable' ) );
					}

					$file = isset( $_FILES['pdf_file'] ) && is_array( $_FILES['pdf_file'] ) ? $_FILES['pdf_file'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload validation handled below

					// Validate uploaded file
					if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
						wp_send_json_error( esc_html__( 'Invalid upload', 'mosque-timetable' ) );
					}

					// Validate file type and extension
					$check = wp_check_filetype_and_ext(
						$file['tmp_name'],
						$file['name'],
						array( 'pdf' => 'application/pdf' )
					);
					if ( ! $check['ext'] || ! $check['type'] ) {
						wp_send_json_error( esc_html__( 'Unsupported file type', 'mosque-timetable' ) );
					}

					// Handle the upload
					if ( ! function_exists( 'wp_handle_upload' ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
					}

					$upload_overrides = array(
						'test_form' => false,
						'mimes'     => array( 'pdf' => 'application/pdf' ),
					);

					$movefile = wp_handle_upload( $file, $upload_overrides );

					if ( $movefile && ! isset( $movefile['error'] ) ) {
						// Save the PDF URL
						$pdf_url = $movefile['url'];
						if ( mt_save_pdf_for_month( $month, $pdf_url, $year ) ) {
							wp_send_json_success(
								array(
									'message' => 'PDF uploaded successfully',
									'pdf_url' => $pdf_url,
								)
							);
						} else {
							wp_send_json_error( __( 'Failed to save PDF URL', 'mosque-timetable' ) );
						}
					} else {
						wp_send_json_error( $movefile['error'] ?? 'Upload failed' );
					}
				}
			);

			// Remove PDF AJAX handler
			add_action(
				'wp_ajax_remove_month_pdf',
				function () {
					// Security check
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Validate inputs
					$month = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					$year  = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( wp_date( 'Y' ) );

					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month', 'mosque-timetable' ) );
					}

					// Remove the PDF URL
					if ( mt_save_pdf_for_month( $month, '', $year ) ) {
						wp_send_json_success( array( 'message' => 'PDF removed successfully' ) );
					} else {
						wp_send_json_error( __( 'Failed to remove PDF', 'mosque-timetable' ) );
					}
				}
			);

			// Generate All Dates AJAX handler
			add_action(
				'wp_ajax_generate_all_dates',
				function () use ( $mosque_plugin ) {
					// Security check
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Get year parameter
					$year = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( wp_date( 'Y' ) );

					// Generate all months
					$generated_count = 0;
					for ( $month = 1; $month <= 12; $month++ ) {
						if ( $mosque_plugin->generate_month_structure( $year, $month ) ) {
							$generated_count++;
						}
					}

					if ( $generated_count > 0 ) {
						wp_send_json_success(
							array(
								/* translators: %1$d: Number of months, %2$d: Year number */
								'message'         => sprintf( __( 'Generated dates for %1$d months in year %2$d', 'mosque-timetable' ), $generated_count, $year ),
								'generated_count' => $generated_count,
							)
						);
					} else {
						wp_send_json_error( __( 'Failed to generate dates', 'mosque-timetable' ) );
					}
				}
			);

			// Generate Month Dates AJAX handler
			add_action(
				'wp_ajax_generate_month_dates',
				function () use ( $mosque_plugin ) {
					// Security check
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Validate inputs
					$month = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					$year  = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( wp_date( 'Y' ) );

					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month', 'mosque-timetable' ) );
					}

					// Generate month structure
					if ( $mosque_plugin->generate_month_structure( $year, $month ) ) {
						wp_send_json_success(
							array(
								/* translators: %1$d: Month number, %2$d: Year number */
								'message' => sprintf( __( 'Generated dates for month %1$d in year %2$d', 'mosque-timetable' ), $month, $year ),
							)
						);
					} else {
						wp_send_json_error( __( 'Failed to generate month dates', 'mosque-timetable' ) );
					}
				}
			);

			// Recalculate Hijri Dates AJAX handler
			add_action(
				'wp_ajax_recalculate_hijri_dates',
				function () use ( $mosque_plugin ) {
					// Security check
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Validate inputs
					$month      = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					$year       = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( wp_date( 'Y' ) );
					$adjustment = isset( $_POST['adjustment'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['adjustment'] ) ) ) : 0;

					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month', 'mosque-timetable' ) );
					}

					// Get existing month data directly (ACF field contains just days array)
					$field_name = "daily_prayers_{$month}";
					$month_data = get_field( $field_name, 'option' );

					if ( ! $month_data || ! is_array( $month_data ) ) {
						wp_send_json_error( __( 'No month data found', 'mosque-timetable' ) );
					}

					// Recalculate Hijri dates with adjustment
					$updated_count = 0;
					foreach ( $month_data as &$day_data ) {
						if ( isset( $day_data['date_full'] ) ) {
							// Apply adjustment to date for Hijri calculation
							$date_obj = new DateTime( $day_data['date_full'] );
							if ( 0 !== $adjustment ) {
								$date_obj->modify( ( $adjustment > 0 ? '+' : '' ) . $adjustment . ' days' );
							}
							$adjusted_date = $date_obj->format( 'Y-m-d' );

							$day_data['hijri_date'] = $mosque_plugin->calculate_hijri_date( $adjusted_date );
							$updated_count++;
						}
					}

					// Save updated data
					if ( update_field( $field_name, $month_data, 'option' ) ) {
						wp_send_json_success(
							array(
								/* translators: %1$d: Number of updated dates, %2$d: Month number, %3$d: Day adjustment */
								'message' => sprintf( __( 'Updated %1$d Hijri dates for month %2$d with %3$d day adjustment', 'mosque-timetable' ), $updated_count, $month, $adjustment ),
							)
						);
					} else {
						wp_send_json_error( __( 'Failed to save updated Hijri dates', 'mosque-timetable' ) );
					}
				}
			);

			// Import XLSX AJAX handler
			add_action(
				'wp_ajax_import_xlsx_timetable',
				function () use ( $mosque_plugin ) {
					// Security check
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Input validation
					if ( ! isset( $_FILES['xlsx_file'] ) ) {
						wp_send_json_error( esc_html__( 'No file uploaded', 'mosque-timetable' ) );
					}

					$month = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month specified', 'mosque-timetable' ) );
					}

					$file = isset( $_FILES['xlsx_file'] ) && is_array( $_FILES['xlsx_file'] ) ? $_FILES['xlsx_file'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload validation handled below
					if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== $file['error'] ) {
						/* translators: %d: Error code number */
						wp_send_json_error( sprintf( __( 'File upload error: %d', 'mosque-timetable' ), $file['error'] ) );
					}

					// Validate uploaded file
					if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
						wp_send_json_error( esc_html__( 'Invalid upload', 'mosque-timetable' ) );
					}

					// Validate file type and extension
					$check = wp_check_filetype_and_ext(
						$file['tmp_name'],
						$file['name'],
						array(
							'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
							'xls'  => 'application/vnd.ms-excel',
						)
					);
					if ( ! $check['ext'] || ! $check['type'] ) {
						wp_send_json_error( esc_html__( 'Unsupported file type', 'mosque-timetable' ) );
					}

					// For now, return a not implemented message since XLSX parsing requires additional libraries
					wp_send_json_error( __( 'XLSX import is not yet implemented. Please use CSV format instead.', 'mosque-timetable' ) );
				}
			);

			// Import Paste Data AJAX handler
			add_action(
				'wp_ajax_import_paste_data',
				function () use ( $mosque_plugin ) {
					// Security check
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Input validation
					$month = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month specified', 'mosque-timetable' ) );
					}

					$paste_data = isset( $_POST['paste_data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['paste_data'] ) ) : '';
					if ( empty( $paste_data ) ) {
						wp_send_json_error( __( 'No data provided', 'mosque-timetable' ) );
					}

					// Process pasted data as CSV-like format
					$year = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( wp_date( 'Y' ) );

					// Normalize time format function
					$norm = function ( $s ) {
						$s = trim( (string) $s );
						if ( '' === $s ) {
							return '';
						}
						$s = str_replace( array( '.', '-' ), ':', $s );
						if ( ! preg_match( '/^(\d{1,2}):(\d{1,2})$/', $s, $m ) ) {
							return '';
						}
						$h = max( 0, min( 23, (int) $m[1] ) );
						$i = max( 0, min( 59, (int) $m[2] ) );
						return sprintf( '%02d:%02d', $h, $i );
					};

					// Parse pasted data (assume tab or comma separated)
					$lines          = preg_split( "/\r\n|\n|\r/", $paste_data );
					$month_data     = array();
					$processed      = 0;
					$data_row_count = 0;

					foreach ( $lines as $line ) {
						$line = trim( $line );
						if ( '' === $line ) {
							continue;
						}

						// Try tab first, then comma
						$data = str_getcsv( $line, "\t" );
						if ( count( $data ) < 2 ) {
							$data = str_getcsv( $line, ',' );
						}

						// Skip header-like rows
						if ( $mosque_plugin->is_header_row( $data ) ) {
							continue;
						}

						++$data_row_count;

						// Parse similar to CSV
						$day_num = null;
						$date    = null;
						$start   = 0;

						if ( isset( $data[0] ) && is_numeric( $data[0] ) && (int) $data[0] >= 1 && (int) $data[0] <= 31 ) {
							$day_num = (int) $data[0];
							$start   = 1;
							if ( isset( $data[1] ) && $mosque_plugin->looks_like_date( $data[1] ) ) {
								$date  = sanitize_text_field( $data[1] );
								$start = 2;
							}
						}

						if ( ! $day_num ) {
							$day_num = $data_row_count;
						}

						if ( ! $date ) {
							$date = sprintf( '%04d-%02d-%02d', $year, $month, $day_num );
						}

						$times = array_slice( $data, $start );

						if ( count( $times ) < 12 ) {
							continue;
						}

						if ( $day_num >= 1 && $day_num <= 31 ) {
							$month_data[] = array(
								'day_number'     => $day_num,
								'date_full'      => $date,
								'day_name'       => wp_date( 'l', strtotime( $date ) ),
								'hijri_date'     => $mosque_plugin->calculate_hijri_date( $date ),

								'fajr_start'     => $norm( $times[0] ?? '' ),
								'fajr_jamaat'    => $norm( $times[1] ?? '' ),
								'sunrise'        => $norm( $times[2] ?? '' ),
								'zuhr_start'     => $norm( $times[3] ?? '' ),
								'zuhr_jamaat'    => $norm( $times[4] ?? '' ),
								'asr_start'      => $norm( $times[5] ?? '' ),
								'asr_jamaat'     => $norm( $times[6] ?? '' ),
								'maghrib_start'  => $norm( $times[7] ?? '' ),
								'maghrib_jamaat' => $norm( $times[8] ?? '' ),
								'isha_start'     => $norm( $times[9] ?? '' ),
								'isha_jamaat'    => $norm( $times[10] ?? '' ),
								'jummah_1'       => $norm( $times[11] ?? '' ),
								'jummah_2'       => $norm( $times[12] ?? '' ),
							);
							++$processed;
						}
					}

					if ( 0 === $processed || empty( $month_data ) ) {
						wp_send_json_error( __( 'No valid data found in the pasted text', 'mosque-timetable' ) );
					}

					// Sort and save
					usort( $month_data, fn( $a, $b ) => ( $a['day_number'] ?? 0 ) <=> ( $b['day_number'] ?? 0 ) );
					$ok = mt_save_month_rows( $month, $month_data, $year );

					if ( $ok ) {
						wp_send_json_success(
							array(
								'imported_rows' => $processed,
								/* translators: %1$d: Number of imported days, %2$d: Month number */
								'message'       => sprintf( __( 'Successfully imported %1$d days from pasted data for month %2$d', 'mosque-timetable' ), $processed, $month ),
							)
						);
					} else {
						wp_send_json_error( __( 'Failed to save imported data', 'mosque-timetable' ) );
					}
				}
			);
		}
	);
}
?>