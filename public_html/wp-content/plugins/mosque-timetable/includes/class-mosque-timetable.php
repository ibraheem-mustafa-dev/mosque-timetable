<?php
/**
 * Mosque Timetable - Main Plugin Class
 *
 * @package MosqueTimetable
 */

// phpcs:disable Universal.Operators.DisallowShortTernary.Found -- Elvis operator is intentionally used throughout for concise null/empty fallbacks.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		register_activation_hook( MOSQUE_TIMETABLE_MAIN_FILE, array( $this, 'activate_plugin' ) );
		register_deactivation_hook( MOSQUE_TIMETABLE_MAIN_FILE, array( $this, 'deactivate_plugin' ) );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'check_year_advancement' ) ); // Auto-check year advancement.
		add_action( 'acf/init', array( $this, 'register_acf_fields' ) );
		add_action( 'acf/save_post', array( $this, 'handle_acf_save_redirect' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_endpoints' ) );
		add_action( 'wp_head', array( $this, 'add_pwa_meta_tags' ) );
		add_action( 'wp_head', array( $this, 'add_structured_data' ) );
		add_action( 'wp_footer', array( $this, 'add_pwa_cta_buttons' ) );
		add_action( 'wp_body_open', array( $this, 'output_global_sticky_prayer_bar' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect', array( $this, 'handle_virtual_pages' ) );
		add_action( 'init', array( $this, 'handle_service_worker_request' ) );
		add_action( 'init', array( $this, 'init_push_notifications_cron' ) );
		add_action( 'mt_send_push_notifications', array( $this, 'process_push_notifications' ) );
		add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- 5-min interval required for timely prayer notifications.

		// AJAX handlers for push notifications.
		add_action( 'wp_ajax_subscribe_push_notifications', array( $this, 'ajax_subscribe_push_notifications' ) );
		add_action( 'wp_ajax_nopriv_subscribe_push_notifications', array( $this, 'ajax_subscribe_push_notifications' ) );
		add_action( 'wp_ajax_unsubscribe_push_notifications', array( $this, 'ajax_unsubscribe_push_notifications' ) );
		add_action( 'wp_ajax_nopriv_unsubscribe_push_notifications', array( $this, 'ajax_unsubscribe_push_notifications' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_credit' ) );
		add_action( 'wp_footer', array( $this, 'frontend_credit' ) );
		add_filter( 'robots_txt', array( $this, 'add_robots_txt_entries' ), 10, 2 );
		add_action( 'init', array( $this, 'add_rtl_support' ) );

		// AJAX hooks with proper security.
		add_action( 'wp_ajax_save_month_timetable', array( $this, 'ajax_save_month_timetable' ) );
		add_action( 'wp_ajax_import_csv_timetable', array( $this, 'ajax_import_csv_timetable' ) );
		add_action( 'wp_ajax_export_ics_calendar', array( $this, 'ajax_export_ics_calendar' ) );
		add_action( 'wp_ajax_export_csv_calendar', array( $this, 'ajax_export_csv_calendar' ) );

		// Additional AJAX hooks are registered at the bottom of the file to avoid duplicates.

		// Admin hooks.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 9 ); // Priority 9 to load before ACF.
	}

	/**
	 * Plugin initialization
	 */
	public function init(): void {
		load_plugin_textdomain(
			'mosque-timetable',
			false,
			dirname( plugin_basename( MOSQUE_TIMETABLE_MAIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Plugin activation
	 */
	public function activate_plugin(): void {
		// Create necessary database tables if needed.
		$this->create_plugin_tables();

		// Add rewrite rules and flush.
		$this->add_rewrite_rules();
		flush_rewrite_rules();

		// Set default options.
		$this->set_default_options();

		// Auto-populate monthly timetables based on default year.
		$this->auto_populate_monthly_structure();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate_plugin(): void {
		// Clean up temporary data.
		flush_rewrite_rules();
	}

	/**
	 * Create plugin database tables
	 */
	private function create_plugin_tables(): void {
		// Tables will be managed via ACF Pro, but we can add custom tables here if needed.
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

		// Inject custom appearance colors as CSS variables.
		$today_color  = get_option( 'mt_today_color', '#FFF9C4' );
		$friday_color = get_option( 'mt_friday_color', '#E8F5E9' );
		$custom_css   = "
			:root {
				--mosque-today-color: {$today_color};
				--mosque-friday-color: {$friday_color};
			}
		";
		wp_add_inline_style( 'mosque-timetable-style', $custom_css );

		// Enqueue modal assets - depends on main CSS for variables.
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

		// Get VAPID public key for push notifications.
		$vapid_public_key = mt_get_option( 'vapid_public_key', '' );

		// Localize script with AJAX URL and nonce.
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
				'serviceWorkerUrl' => MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/sw.js',
				'manifestUrl'      => MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/manifest.json',
				'offlineUrl'       => MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/offline.html',
				'vapidPublicKey'   => $vapid_public_key ?: '',
				'strings'          => array(
					'nextPrayer'    => __( 'Next Prayer', 'mosque-timetable' ),
					'timeRemaining' => __( 'Time Remaining', 'mosque-timetable' ),
					'prayerTime'    => __( 'Prayer Time', 'mosque-timetable' ),
				),
			)
		);

		// Localize modal script with export-specific configuration.
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
	 * Render admin assets
	 */
	public function render_timetables_admin_page() {
		// Use options with fallbacks instead of ACF.
		$available_months = get_option( 'available_months', range( 1, 12 ) );
		if ( ! is_array( $available_months ) || empty( $available_months ) ) {
			$available_months = range( 1, 12 );
		}

		$default_year = (int) get_option( 'default_year', wp_date( 'Y' ) );
		if ( $default_year < 2020 || $default_year > 2050 ) {
			$default_year = (int) wp_date( 'Y' );
		}

		$mosque_name = get_option( 'mosque_name', get_bloginfo( 'name' ) );

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
			<div class="wrap mosque-timetable-admin">
				<div class="mosque-admin-container">
					<!-- Modern Header -->
					<div class="mosque-admin-header">
						<div class="mosque-admin-title-wrapper">
							<h1 class="mosque-admin-title">
								<span class="dashicons dashicons-calendar-alt" style="font-size: 32px; width: 32px; height: 32px;"></span>
							<?php echo esc_html( $mosque_name ); ?> — Prayer Timetables
							</h1>
							<p class="mosque-admin-subtitle">
								Managing <?php echo esc_html( (string) $default_year ); ?> Prayer Times
							</p>
						</div>
					</div>

					<!-- Control Cards -->
					<div class="mt-control-cards">
						<!-- Year Archive Browser Card -->
						<div class="mt-control-card">
							<div class="mt-card-header">
								<h2> Year Archive Browser</h2>
							</div>
							<div class="mt-card-body">
								<p class="mt-card-description">Browse and manage prayer times across different years</p>

								<div class="mt-form-row">
									<label class="mt-label" for="year-selector">Select Year:</label>
									<select id="year-selector" class="mt-select">
									<?php
									$current_year = (int) wp_date( 'Y' );
									for ( $y = 2020; $y <= $current_year + 5; $y++ ) :
										?>
											<option value="<?php echo esc_attr( (string) $y ); ?>" <?php selected( $y, $default_year ); ?>>
											<?php echo esc_html( (string) $y ); ?>
											</option>
										<?php endfor; ?>
									</select>
									<button type="button" class="mosque-btn mosque-btn-primary" id="load-year-data">
										<span class="dashicons dashicons-download"></span> Load Year
									</button>
									<button type="button" class="mosque-btn mosque-btn-secondary" id="create-new-year">
										<span class="dashicons dashicons-plus-alt"></span> New Year
									</button>
								</div>

								<div class="mt-bulk-actions">
									<p class="mt-help-text" style="margin-bottom: var(--mt-space-md);">Bulk Actions:</p>
									<div class="mt-form-row">
										<button type="button" class="mosque-btn mosque-btn-secondary" id="generate-all-dates">
											<span class="dashicons dashicons-calendar"></span> Generate All Dates
										</button>
										<button type="button" class="mosque-btn mosque-btn-success" id="save-all-months">
											<span class="dashicons dashicons-saved"></span> Save All Months
										</button>
									</div>
								</div>
							</div>
						</div>

						<!-- Import Tools Card -->
						<div class="mt-control-card">
							<div class="mt-card-header">
								<h2> Import Tools</h2>
							</div>
							<div class="mt-card-body">
								<p class="mt-card-description">Import prayer times from various sources</p>

								<div class="mt-import-buttons">
									<button type="button" class="mosque-btn mosque-btn-primary" id="csv-import-btn">
										<span class="dashicons dashicons-media-spreadsheet"></span> Import CSV
									</button>
									<button type="button" class="mosque-btn mosque-btn-primary" id="xlsx-import-btn">
										<span class="dashicons dashicons-analytics"></span> Import XLSX
									</button>
									<button type="button" class="mosque-btn mosque-btn-secondary" id="paste-import-btn">
										<span class="dashicons dashicons-clipboard"></span> Copy/Paste Data
									</button>
								</div>

								<p class="mt-help-text">
									 <strong>Need examples?</strong>
									<a href="admin.php?page=mosque-import-export">Download sample templates</a>
								</p>
							</div>
						</div>
					</div>

				<!-- Month Tabs -->
				<div class="mosque-month-tabs">
					<?php
					$first = true;
					foreach ( $available_months as $m ) :
						$m = (int) $m;
						?>
						<button type="button"
							class="mosque-month-tab <?php echo $first ? 'active' : ''; ?>"
							data-month="<?php echo esc_attr( (string) $m ); ?>">
							<?php echo esc_html( $months[ $m ] ); ?>
						</button>
						<?php
						$first = false;
					endforeach;
					?>
				</div>

				<!-- Month Content -->
				<div class="mosque-month-content">
					<?php
					$first = true; foreach ( $available_months as $m ) :
						$m                = (int) $m;
						$existing_pdf_url = function_exists( 'mt_get_pdf_for_month' )
							? mt_get_pdf_for_month( $m, (int) $default_year )
							: '';
						$has_pdf          = ! empty( $existing_pdf_url );
						?>
						<div id="month-panel-<?php echo esc_attr( (string) $m ); ?>" class="mosque-month-panel <?php echo $first ? 'active' : ''; ?>">
							<!-- Month Header -->
							<div class="mosque-month-header">
								<h3 class="mosque-month-title"><?php echo esc_html( $months[ $m ] ); ?> <?php echo esc_html( (string) $default_year ); ?></h3>
								<div class="mosque-month-actions">
									<button type="button"
											class="mosque-btn mosque-btn-success save-month-btn"
											data-month="<?php echo esc_attr( (string) $m ); ?>"
											data-year="<?php echo esc_attr( (string) $default_year ); ?>">
										<span class="dashicons dashicons-saved"></span> Save <?php echo esc_html( $months[ $m ] ); ?>
									</button>
									<button type="button"
											class="mosque-btn mosque-btn-secondary generate-month-dates"
											data-month="<?php echo esc_attr( (string) $m ); ?>"
											data-year="<?php echo esc_attr( (string) $default_year ); ?>">
										<span class="dashicons dashicons-calendar"></span> Generate Dates
									</button>
								</div>
							</div>

							<!-- Hijri Controls -->
							<div class="mt-hijri-controls">
								<label class="mt-label" for="hijri-adj-<?php echo esc_attr( (string) $m ); ?>">Hijri Adjustment:</label>
								<input type="number"
										id="hijri-adj-<?php echo esc_attr( (string) $m ); ?>"
										class="mt-number-input hijri-adjust-input"
										value="0"
										step="1"
										min="-2"
										max="2">
								<button type="button"
										class="mosque-btn mosque-btn-secondary recalc-hijri-btn"
										data-month="<?php echo esc_attr( (string) $m ); ?>"
										data-year="<?php echo esc_attr( (string) $default_year ); ?>">
									<span class="dashicons dashicons-update"></span> Recalculate Hijri
								</button>
								<span class="mt-help-text">Adjust Hijri dates by 2 days if needed</span>
							</div>

							<!-- PDF Upload Section -->
							<div class="mt-pdf-upload-section">
								<h4 class="mt-section-title">
									<span class="dashicons dashicons-media-document"></span> Print-ready PDF
								</h4>

								<?php if ( $has_pdf ) : ?>
									<div class="mt-pdf-current">
										<span class="mt-pdf-info"><span class="dashicons dashicons-yes-alt"></span> PDF uploaded</span>
										<a href="<?php echo esc_url( $existing_pdf_url ); ?>" target="_blank" class="mosque-btn mosque-btn-secondary">
											<span class="dashicons dashicons-visibility"></span> View PDF
										</a>
										<button type="button"
												class="mosque-btn mosque-btn-danger mt-remove-pdf-btn"
												data-month="<?php echo esc_attr( (string) $m ); ?>"
												data-year="<?php echo esc_attr( (string) $default_year ); ?>">
											<span class="dashicons dashicons-trash"></span> Remove
										</button>
									</div>
								<?php endif; ?>

								<form enctype="multipart/form-data" class="mt-pdf-upload-form" data-month="<?php echo esc_attr( (string) $m ); ?>">
									<input type="file"
											name="pdf_file"
											accept="application/pdf"
											class="mt-pdf-file-input"
											id="pdf-file-<?php echo esc_attr( (string) $m ); ?>">
									<label for="pdf-file-<?php echo esc_attr( (string) $m ); ?>" class="mosque-btn mosque-btn-secondary">
										<span class="dashicons dashicons-media-default"></span> Choose PDF File
									</label>

									<button type="button"
											class="mosque-btn mosque-btn-primary mt-upload-pdf-btn"
											data-month="<?php echo esc_attr( (string) $m ); ?>"
											data-year="<?php echo esc_attr( (string) $default_year ); ?>"
											style="display:none;">
										<span class="dashicons dashicons-upload"></span> <?php echo $has_pdf ? 'Replace' : 'Upload'; ?>
									</button>
								</form>
							</div>

							<!-- Prayer Times Table -->
							<div class="mosque-admin-table-wrapper">
								<div class="mt-loading-placeholder">
									<div class="mt-spinner"></div>
									<p>Loading <?php echo esc_html( $months[ $m ] ); ?> prayer times...</p>
								</div>
							</div>
						</div>
						<?php
						$first = false;
endforeach;
					?>
				</div>

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
								<?php
								foreach ( $available_months as $m ) :
									$m = (int) $m;
									?>
										<option value="<?php echo esc_attr( (string) $m ); ?>"><?php echo esc_html( $months[ $m ] ); ?></option>
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
							<button type="button" class="mosque-btn mosque-btn-primary" id="execute-import">
								<span class="dashicons dashicons-upload"></span> Import Data
							</button>
							<button type="button" class="mosque-btn mosque-btn-secondary" id="cancel-import">
								Cancel
							</button>
						</div>
					</div>
				</div>

				</div><!-- .mosque-admin-container -->
			</div><!-- .wrap -->
			<?php
	}

	/**
	 * Enqueue admin assets for Mosque Timetable pages
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Admin hook: ' . $hook ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		$is_mosque_page =
			( 'toplevel_page_mosque-main' === $hook ) ||
			( 'toplevel_page_mosque-timetables' === $hook ) ||
			( 'mosque-timetable_page_mosque-import-export' === $hook ) ||
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only page detection, no data modification.
			( isset( $_GET['page'] ) && str_starts_with(
				sanitize_text_field( wp_unslash( $_GET['page'] ) ),
				'mosque-'
			) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $is_mosque_page ) {
			return;
		}

		// Islamic typography for admin — El Messiri headings, DM Sans body, Space Mono times.
		wp_enqueue_style(
			'mosque-timetable-admin-fonts',
			'https://fonts.googleapis.com/css2?family=El+Messiri:wght@400;600;700&family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'mosque-timetable-admin-style',
			MOSQUE_TIMETABLE_ASSETS_URL . 'mosque-timetable-admin.css',
			array( 'mosque-timetable-admin-fonts' ),
			MOSQUE_TIMETABLE_VERSION . '-' . wp_date( 'YmdHi' )
		);

		wp_enqueue_script(
			'mosque-timetable-admin-script',
			MOSQUE_TIMETABLE_ASSETS_URL . 'mosque-timetable-admin.js',
			array( 'jquery' ),
			MOSQUE_TIMETABLE_VERSION . '-' . wp_date( 'YmdHi' ),
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
				'currentYear'  => (int) mt_get_option( 'default_year', wp_date( 'Y' ) ),
				'currentMonth' => (int) wp_date( 'n' ),
				'strings'      => array(
					'saveSuccess'          => __( 'Month timetable saved successfully!', 'mosque-timetable' ),
					'saveError'            => __( 'Error saving month timetable. Please try again.', 'mosque-timetable' ),
					'unsavedChanges'       => __( 'You have unsaved changes. Are you sure you want to leave?', 'mosque-timetable' ),
					'confirmLeave'         => __( 'You have unsaved changes. Are you sure you want to leave this tab?', 'mosque-timetable' ),
					'generateSuccess'      => __( 'All dates generated successfully! Hijri dates calculated automatically.', 'mosque-timetable' ),
					'generateError'        => __( 'Failed to generate dates', 'mosque-timetable' ),
					'hijriRecalculated'    => __( 'Hijri dates recalculated successfully!', 'mosque-timetable' ),
					'importSuccess'        => __( 'Import completed successfully!', 'mosque-timetable' ),
					'importError'          => __( 'Error importing file. Please check format and try again.', 'mosque-timetable' ),
					'noMonth'              => __( 'Please select a month.', 'mosque-timetable' ),
					'noFile'               => __( 'Please select a file before importing.', 'mosque-timetable' ),
					'noPaste'              => __( 'Please paste your timetable data before importing.', 'mosque-timetable' ),
					'networkError'         => __( 'Network error: Could not connect to server', 'mosque-timetable' ),
					'permissionError'      => __( 'Permission denied: Please refresh the page', 'mosque-timetable' ),
					'serverError'          => __( 'Server error: Please try again later', 'mosque-timetable' ),
					'connectionError'      => __( 'Error connecting to server: ', 'mosque-timetable' ),
					'loadError'            => __( 'Failed to load month data', 'mosque-timetable' ),
					'invalidTime'          => __( 'Invalid time format. Please use HH:MM format.', 'mosque-timetable' ),
					'saveNow'              => __( 'Save Now', 'mosque-timetable' ),
					'confirmGenerateAll'   => __( 'Generate date structure for all 12 months?', 'mosque-timetable' ),
					'confirmSaveAll'       => __( 'Save all months?', 'mosque-timetable' ),
					'confirmHijri'         => __( 'Recalculate Hijri dates with adjustment?', 'mosque-timetable' ),
					'generateMonthSuccess' => __( 'Month dates generated.', 'mosque-timetable' ),
					'yearCreated'          => __( 'Year created.', 'mosque-timetable' ),
					'yearCreateError'      => __( 'Failed to create year.', 'mosque-timetable' ),
					'loadingYear'          => __( 'Loading data for year...', 'mosque-timetable' ),
					'exportingCsv'         => __( 'CSV export started...', 'mosque-timetable' ),
					'pdfUploadSuccess'     => __( 'PDF uploaded successfully!', 'mosque-timetable' ),
					'pdfRemoveSuccess'     => __( 'PDF removed successfully!', 'mosque-timetable' ),
					'pdfUploadError'       => __( 'PDF upload failed', 'mosque-timetable' ),
					'pdfRemoveError'       => __( 'PDF removal failed', 'mosque-timetable' ),
					'pdfSelectFirst'       => __( 'Please select a PDF file first', 'mosque-timetable' ),
					'pdfInvalidFile'       => __( 'Please select a valid PDF file', 'mosque-timetable' ),
					'confirmRemovePdf'     => __( 'Are you sure you want to remove this PDF?', 'mosque-timetable' ),
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

	//  next thing here must be ANOTHER method of the class,.
	// e.g. private function register_mosque_settings_fields() { ... }.
	// Do NOT put global functions here.

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
						'key'          => 'field_prayer_calc_tab',
						'label'        => 'Automatic Prayer Time Calculation',
						'name'         => '',
						'type'         => 'tab',
						'instructions' => '',
						'placement'    => 'top',
					),
					array(
						'key'           => 'field_enable_auto_times',
						'label'         => __( 'Enable Automatic Prayer Times', 'mosque-timetable' ),
						'name'          => 'enable_auto_times',
						'type'          => 'true_false',
						'instructions'  => __( 'When enabled, the "Generate Dates" button will automatically fetch prayer times from Aladhan API based on your mosque location. You can still manually adjust times after generation.', 'mosque-timetable' ),
						'default_value' => 0,
						'ui'            => 1,
						'wrapper'       => array( 'width' => '100' ),
					),
					array(
						'key'               => 'field_mosque_latitude',
						'label'             => __( 'Mosque Latitude', 'mosque-timetable' ),
						'name'              => 'mosque_latitude',
						'type'              => 'number',
						'instructions'      => __( 'Latitude coordinate of your mosque (e.g., 52.4862 for Birmingham, UK). Used for automatic prayer time calculation. Find your coordinates at latlong.net', 'mosque-timetable' ),
						'required'          => 0,
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_enable_auto_times',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
						'min'               => -90,
						'max'               => 90,
						'step'              => 0.000001,
						'wrapper'           => array( 'width' => '50' ),
						'placeholder'       => '52.4862',
					),
					array(
						'key'               => 'field_mosque_longitude',
						'label'             => __( 'Mosque Longitude', 'mosque-timetable' ),
						'name'              => 'mosque_longitude',
						'type'              => 'number',
						'instructions'      => __( 'Longitude coordinate of your mosque (e.g., -1.8904 for Birmingham, UK). Used for automatic prayer time calculation.', 'mosque-timetable' ),
						'required'          => 0,
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_enable_auto_times',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
						'min'               => -180,
						'max'               => 180,
						'step'              => 0.000001,
						'wrapper'           => array( 'width' => '50' ),
						'placeholder'       => '-1.8904',
					),
					array(
						'key'               => 'field_calculation_method',
						'label'             => __( 'Calculation Method', 'mosque-timetable' ),
						'name'              => 'calculation_method',
						'type'              => 'select',
						'instructions'      => __( 'Select the calculation method used by your mosque. Different regions and organizations use different methods.', 'mosque-timetable' ),
						'required'          => 0,
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_enable_auto_times',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
						'choices'           => array(
							'1'  => __( 'MWL - Muslim World League (Europe, Far East, parts of America)', 'mosque-timetable' ),
							'2'  => __( 'ISNA - Islamic Society of North America', 'mosque-timetable' ),
							'3'  => __( 'Egyptian General Authority of Survey', 'mosque-timetable' ),
							'4'  => __( 'Umm Al-Qura University, Makkah', 'mosque-timetable' ),
							'5'  => __( 'University of Islamic Sciences, Karachi', 'mosque-timetable' ),
							'7'  => __( 'Institute of Geophysics, University of Tehran', 'mosque-timetable' ),
							'8'  => __( 'Gulf Region', 'mosque-timetable' ),
							'9'  => __( 'Kuwait', 'mosque-timetable' ),
							'10' => __( 'Qatar', 'mosque-timetable' ),
							'11' => __( 'Majlis Ugama Islam Singapura, Singapore', 'mosque-timetable' ),
							'12' => __( 'Union Organization islamic de France', 'mosque-timetable' ),
							'13' => __( 'Diyanet Isleri Baskanligi, Turkey', 'mosque-timetable' ),
							'14' => __( 'Spiritual Administration of Muslims of Russia', 'mosque-timetable' ),
							'15' => __( 'Moonsighting Committee Worldwide', 'mosque-timetable' ),
						),
						'default_value'     => '2',
						'wrapper'           => array( 'width' => '100' ),
					),
					array(
						'key'               => 'field_jamaat_offsets_message',
						'label'             => __( 'Jamaah Time Offsets', 'mosque-timetable' ),
						'name'              => '',
						'type'              => 'message',
						'instructions'      => '',
						'message'           => __( 'Set how many minutes after the start time your mosque holds Jamaah (congregation). For example, if Zuhr starts at 12:00 and Jamaah is at 12:15, enter 15 minutes.', 'mosque-timetable' ),
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_enable_auto_times',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_fajr_jamaat_offset',
						'label'             => __( 'Fajr Jamaah Offset (minutes)', 'mosque-timetable' ),
						'name'              => 'fajr_jamaat_offset',
						'type'              => 'number',
						'instructions'      => __( 'Minutes after Fajr start time for Jamaah', 'mosque-timetable' ),
						'default_value'     => 10,
						'min'               => 0,
						'max'               => 60,
						'wrapper'           => array( 'width' => '20' ),
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_enable_auto_times',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_zuhr_jamaat_offset',
						'label'             => __( 'Zuhr Jamaah Offset (minutes)', 'mosque-timetable' ),
						'name'              => 'zuhr_jamaat_offset',
						'type'              => 'number',
						'instructions'      => __( 'Minutes after Zuhr start time for Jamaah', 'mosque-timetable' ),
						'default_value'     => 15,
						'min'               => 0,
						'max'               => 60,
						'wrapper'           => array( 'width' => '20' ),
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_enable_auto_times',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_asr_jamaat_offset',
						'label'             => __( 'Asr Jamaah Offset (minutes)', 'mosque-timetable' ),
						'name'              => 'asr_jamaat_offset',
						'type'              => 'number',
						'instructions'      => __( 'Minutes after Asr start time for Jamaah', 'mosque-timetable' ),
						'default_value'     => 15,
						'min'               => 0,
						'max'               => 60,
						'wrapper'           => array( 'width' => '20' ),
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_enable_auto_times',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_maghrib_jamaat_offset',
						'label'             => __( 'Maghrib Jamaah Offset (minutes)', 'mosque-timetable' ),
						'name'              => 'maghrib_jamaat_offset',
						'type'              => 'number',
						'instructions'      => __( 'Minutes after Maghrib start time for Jamaah', 'mosque-timetable' ),
						'default_value'     => 5,
						'min'               => 0,
						'max'               => 30,
						'wrapper'           => array( 'width' => '20' ),
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_enable_auto_times',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'               => 'field_isha_jamaat_offset',
						'label'             => __( 'Isha Jamaah Offset (minutes)', 'mosque-timetable' ),
						'name'              => 'isha_jamaat_offset',
						'type'              => 'number',
						'instructions'      => __( 'Minutes after Isha start time for Jamaah', 'mosque-timetable' ),
						'default_value'     => 15,
						'min'               => 0,
						'max'               => 60,
						'wrapper'           => array( 'width' => '20' ),
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_enable_auto_times',
									'operator' => '==',
									'value'    => '1',
								),
							),
						),
					),
					array(
						'key'          => 'field_terminology_overrides',
						'label'        => __( 'Terminology Overrides', 'mosque-timetable' ),
						'name'         => 'terminology_overrides',
						'type'         => 'repeater',
						'instructions' => __( 'Customize terminology used throughout the plugin interface. Changes apply to labels only, not internal data. Examples: "Mosque"  "Masjid", "Zuhr"  "Dhuhr", "Maghrib"  "Maghreb".', 'mosque-timetable' ),
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
		// Register individual month field groups dynamically.
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

		// Only populate if no existing data.
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
		// Normalise inputs.
		$day_number = (int) $day_number;
		$month      = max( 1, min( 12, (int) $month ) );
		$year       = $year ? (int) $year : (int) wp_date( 'Y' );

		if ( $day_number < 1 || $day_number > 31 ) {
			return false;
		}

		$date_string = is_string( $date_string ) ? trim( $date_string ) : '';

		// If we were given a plausible date string, try to parse and normalise it.
		if ( '' !== $date_string && $this->looks_like_date( $date_string ) ) {
			$ts = strtotime( $date_string );
			if ( false !== $ts ) {
				return gmdate( 'Y-m-d', $ts );
			}
		}

		// Fallback: compose from Y, M, D and validate.
		$candidate = sprintf( '%04d-%02d-%02d', $year, $month, $day_number );
		$dt        = DateTime::createFromFormat( 'Y-m-d', $candidate );

		return ( $dt && $dt->format( 'Y-m-d' ) === $candidate ) ? $candidate : false;
	}

	/**
	 * Calculate Hijri date from Gregorian date
	 */
	public function calculate_hijri_date( $gregorian_date, $adjustment = 0 ) {
		$timestamp = strtotime( $gregorian_date );

		// Apply adjustment.
		if ( 0 !== $adjustment ) {
			$timestamp += ( $adjustment * 24 * 60 * 60 ); // Add/subtract days.
		}

		$gregorian_year  = gmdate( 'Y', $timestamp );
		$gregorian_month = gmdate( 'n', $timestamp );
		$gregorian_day   = gmdate( 'j', $timestamp );

		// More accurate Hijri conversion using Julian Day Number algorithm.
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
		// Calculate Julian Day Number.
		if ( $g_month <= 2 ) {
			--$g_year;
			$g_month += 12;
		}

		$a = floor( $g_year / 100 );
		$b = 2 - $a + floor( $a / 4 );

		$jd = floor( 365.25 * ( $g_year + 4716 ) ) + floor( 30.6001 * ( $g_month + 1 ) ) + $g_day + $b - 1524;

		// Convert Julian Day to Hijri.
		$l = $jd - 1948439; // Difference between Julian and Hijri epochs.
		$n = floor( ( $l - 1 ) / 10631 );
		$l = $l - 10631 * $n + 354;

		$j = floor( ( 10985 - $l ) / 5316 ) * floor( ( 50 * $l ) / 17719 ) +
			floor( $l / 5670 ) * floor( ( 43 * $l ) / 15238 );

		$l = $l - floor( ( 30 - $j ) / 15 ) * floor( ( 17719 * $j ) / 50 ) -
			floor( $j / 16 ) * floor( ( 15238 * $j ) / 43 ) + 29;

		$hijri_month = floor( ( 24 * $l ) / 709 );
		$hijri_day   = $l - floor( ( 709 * $hijri_month ) / 24 );
		$hijri_year  = 30 * $n + $j - 30;

		// Adjust for proper ranges.
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
		// Hijri months alternate between 30 and 29 days.
		// with adjustments for leap years.
		$days = array( 30, 29, 30, 29, 30, 29, 30, 29, 30, 29, 30, 29 );

		// In leap years, the last month has 30 days instead of 29.
		if ( $this->is_hijri_leap_year( $year ) && 12 === $month ) {
			return 30;
		}

		return $days[ $month - 1 ];
	}

	/**
	 * Check if a Hijri year is a leap year
	 */
	private function is_hijri_leap_year( $year ) {
		// Hijri leap year calculation: 11 leap years in every 30-year cycle.
		$cycle_position = $year % 30;
		$leap_years     = array( 2, 5, 7, 10, 13, 16, 18, 21, 24, 26, 29 );
		return in_array( $cycle_position, $leap_years, true );
	}

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode( 'mosque_timetable', array( $this, 'shortcode_mosque_timetable' ) );
		add_shortcode( 'todays_prayers', array( $this, 'shortcode_todays_prayers' ) );
		add_shortcode( 'prayer_countdown', array( $this, 'shortcode_prayer_countdown' ) );
		add_shortcode( 'mosque_prayer_bar', array( $this, 'shortcode_mosque_prayer_bar' ) );
		add_shortcode( 'ramadan_info', array( $this, 'shortcode_ramadan_info' ) );
	}

	// -------------------------------------------------------------------------
	// Ramadan Mode Helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns true if today falls within the configured Ramadan period.
	 * Falls back to a rough Hijri-based heuristic when no dates set.
	 */
	private function is_ramadan() {
		$start = mt_get_option( 'ramadan_start_date', '' );
		$end   = mt_get_option( 'ramadan_end_date', '' );

		if ( $start && $end ) {
			$today = wp_date( 'Y-m-d' );
			return $today >= $start && $today <= $end;
		}

		// Heuristic: skip — return false when not configured.
		return false;
	}

	/**
	 * Calculate suhoor time = Fajr start minus configured margin (default 15 min).
	 *
	 * @param string $fajr_start  Time string like "05:14".
	 * @return string             Suhoor time string or empty string.
	 */
	private function calc_suhoor( $fajr_start ) {
		if ( empty( $fajr_start ) ) {
			return '';
		}
		$margin = (int) mt_get_option( 'ramadan_suhoor_margin', 15 );
		$parts  = explode( ':', $fajr_start );
		if ( count( $parts ) < 2 ) {
			return '';
		}
		$total_min = ( (int) $parts[0] * 60 ) + (int) $parts[1] - $margin;
		if ( $total_min < 0 ) {
			$total_min += 1440;
		}
		return sprintf( '%02d:%02d', intdiv( $total_min, 60 ), $total_min % 60 );
	}

	/**
	 * Shortcode: [ramadan_info]
	 *
	 * Attributes:
	 *   layout      card (default) | banner | compact
	 *   show_day    true|false — show Ramadan day number
	 *   show_countdown true|false — JS iftar/suhoor countdown
	 */
	public function shortcode_ramadan_info( $atts ) {
		$atts = shortcode_atts(
			array(
				'layout'         => 'card',
				'show_day'       => 'true',
				'show_countdown' => 'true',
			),
			$atts,
			'ramadan_info'
		);

		$today_data    = $this->get_today_prayer_data();
		$fajr          = $today_data['fajr_start'] ?? '';
		$maghrib       = $today_data['maghrib_start'] ?? '';
		$suhoor        = $this->calc_suhoor( $fajr );
		$mosque_name   = mt_get_option( 'mosque_name', get_bloginfo( 'name' ) );

		// Ramadan day number.
		$ramadan_day   = '';
		$start_date    = mt_get_option( 'ramadan_start_date', '' );
		if ( $start_date ) {
			$start = new DateTime( $start_date );
			$today = new DateTime( wp_date( 'Y-m-d' ) );
			$diff  = $start->diff( $today );
			if ( ! $diff->invert && $diff->days < 32 ) {
				$ramadan_day = $diff->days + 1;
			}
		}

		$show_countdown = ( 'true' === $atts['show_countdown'] );
		$show_day       = ( 'true' === $atts['show_day'] && $ramadan_day );
		$layout         = sanitize_text_field( $atts['layout'] );

		// Build UTC timestamp strings for JS countdown targets.
		$today_str      = wp_date( 'Y-m-d' );
		$iftar_ts       = $maghrib ? esc_attr( $today_str . 'T' . $maghrib . ':00' ) : '';
		$suhoor_next    = '';  // suhoor tomorrow — keep simple for now.

		ob_start();
		?>
		<div class="mt-ramadan-info mt-ramadan-<?php echo esc_attr( $layout ); ?>">

			<?php if ( 'banner' === $layout ) : ?>

				<div class="mt-ramadan-banner">
					<div class="mt-ramadan-banner-left">
						<span class="mt-ramadan-moon">&#9790;</span>
						<?php if ( $show_day ) : ?>
							<span class="mt-ramadan-day-label">Day <?php echo esc_html( (string) $ramadan_day ); ?></span>
						<?php endif; ?>
						<span class="mt-ramadan-title">Ramadan Mubarak</span>
					</div>
					<div class="mt-ramadan-banner-times">
						<div class="mt-ramadan-time-block">
							<span class="mt-ramadan-time-label">Suhoor ends</span>
							<span class="mt-ramadan-time-value"><?php echo esc_html( $suhoor ); ?></span>
						</div>
						<div class="mt-ramadan-divider">|</div>
						<div class="mt-ramadan-time-block">
							<span class="mt-ramadan-time-label">Iftar</span>
							<span class="mt-ramadan-time-value mt-iftar-time"><?php echo esc_html( $maghrib ); ?></span>
						</div>
						<?php if ( $show_countdown && $iftar_ts ) : ?>
							<div class="mt-ramadan-divider">|</div>
							<div class="mt-ramadan-countdown-block">
								<span class="mt-ramadan-time-label">Until Iftar</span>
								<span class="mt-ramadan-countdown" data-target="<?php echo $iftar_ts; ?>" data-label="Iftar">--:--:--</span>
							</div>
						<?php endif; ?>
					</div>
				</div>

			<?php elseif ( 'compact' === $layout ) : ?>

				<div class="mt-ramadan-compact">
					<span class="mt-ramadan-moon">&#9790;</span>
					<?php if ( $show_day ) : ?>
						<span class="mt-ramadan-day-label">Day <?php echo esc_html( (string) $ramadan_day ); ?></span>
						<span class="mt-ramadan-compact-sep">&bull;</span>
					<?php endif; ?>
					<span>Suhoor: <strong><?php echo esc_html( $suhoor ); ?></strong></span>
					<span class="mt-ramadan-compact-sep">&bull;</span>
					<span>Iftar: <strong><?php echo esc_html( $maghrib ); ?></strong></span>
					<?php if ( $show_countdown && $iftar_ts ) : ?>
						<span class="mt-ramadan-compact-sep">&bull;</span>
						<span class="mt-ramadan-countdown" data-target="<?php echo $iftar_ts; ?>" data-label="Iftar">--:--</span>
					<?php endif; ?>
				</div>

			<?php else : // card layout (default) ?>

				<div class="mt-ramadan-card">
					<div class="mt-ramadan-card-header">
						<span class="mt-ramadan-moon">&#9790;</span>
						<div class="mt-ramadan-card-title">
							<strong>Ramadan Mubarak</strong>
							<?php if ( $show_day ) : ?>
								<span class="mt-ramadan-day-badge">Day <?php echo esc_html( (string) $ramadan_day ); ?></span>
							<?php endif; ?>
						</div>
					</div>
					<div class="mt-ramadan-card-times">
						<div class="mt-ramadan-time-col">
							<div class="mt-ramadan-time-col-label">Suhoor ends</div>
							<div class="mt-ramadan-time-col-value"><?php echo esc_html( $suhoor ); ?></div>
						</div>
						<div class="mt-ramadan-time-col mt-ramadan-iftar-col">
							<div class="mt-ramadan-time-col-label">Iftar (Maghrib)</div>
							<div class="mt-ramadan-time-col-value"><?php echo esc_html( $maghrib ); ?></div>
						</div>
						<?php if ( $show_countdown && $iftar_ts ) : ?>
							<div class="mt-ramadan-time-col">
								<div class="mt-ramadan-time-col-label">Countdown</div>
								<div class="mt-ramadan-countdown mt-ramadan-time-col-value" data-target="<?php echo $iftar_ts; ?>" data-label="Iftar">--:--:--</div>
							</div>
						<?php endif; ?>
					</div>
				</div>

			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [mosque_prayer_bar]
	 *
	 * A thin sticky hello-bar strip showing today's prayer times.
	 * Can also carry an optional charity/appeal CTA button.
	 *
	 * Attributes:
	 *   position     = top | bottom           (default: top)
	 *   dismissible  = true | false           (default: true  — shows × close button)
	 *   show_jamaat  = true | false           (default: false — show jamaat below start time)
	 *   pulse_next   = true | false           (default: true  — highlight next prayer)
	 *   cta_text     = "Support Our Appeal"   (optional CTA label)
	 *   cta_url      = "https://..."          (optional CTA link)
	 *   cta_target   = _self | _blank         (default: _self)
	 *   bg           = teal | midnight | gold | custom (default: teal)
	 *   bg_color     = "#0D7377"              (only used when bg="custom")
	 *   offset_top   = "80px"                 (CSS top when position=top, useful below sticky navs)
	 *
	 * Usage examples:
	 *   [mosque_prayer_bar]
	 *   [mosque_prayer_bar position="bottom" dismissible="false"]
	 *   [mosque_prayer_bar cta_text="Support Gaza" cta_url="/donate" cta_target="_blank"]
	 *   [mosque_prayer_bar bg="midnight" position="top" offset_top="90px"]
	 */
	public function shortcode_mosque_prayer_bar( $atts ) {
		$atts = shortcode_atts(
			array(
				'position'    => 'top',
				'dismissible' => 'true',
				'show_jamaat' => 'false',
				'pulse_next'  => 'true',
				'cta_text'    => '',
				'cta_url'     => '',
				'cta_target'  => '_self',
				'bg'          => 'teal',
				'bg_color'    => '',
				'offset_top'  => '',
			),
			$atts,
			'mosque_prayer_bar'
		);

		$position    = in_array( $atts['position'], array( 'top', 'bottom' ), true ) ? $atts['position'] : 'top';
		$dismissible = filter_var( $atts['dismissible'], FILTER_VALIDATE_BOOLEAN );
		$show_jamaat = filter_var( $atts['show_jamaat'], FILTER_VALIDATE_BOOLEAN );
		$pulse_next  = filter_var( $atts['pulse_next'], FILTER_VALIDATE_BOOLEAN );
		$cta_text    = sanitize_text_field( $atts['cta_text'] );
		$cta_url     = esc_url( $atts['cta_url'] );
		$cta_target  = in_array( $atts['cta_target'], array( '_self', '_blank' ), true ) ? $atts['cta_target'] : '_self';
		$bg          = in_array( $atts['bg'], array( 'teal', 'midnight', 'gold', 'custom' ), true ) ? $atts['bg'] : 'teal';
		$bg_color    = sanitize_hex_color( $atts['bg_color'] );
		$offset_top  = preg_match( '/^[0-9]+(px|em|rem|vh|%)$/', $atts['offset_top'] ) ? $atts['offset_top'] : '';

		// Unique ID per shortcode instance (supports multiple bars).
		static $bar_count = 0;
		++$bar_count;
		$bar_id = 'mpb-' . $bar_count;

		// Get today's prayer data.
		$today_data   = $this->get_today_prayer_data();
		$next_prayer  = $this->get_next_prayer_data();
		$next_name    = isset( $next_prayer['name'] ) ? strtolower( $next_prayer['name'] ) : '';

		// Prayer slots to display (order: Fajr, Sunrise, Zuhr, Asr, Maghrib, Isha).
		$slots = array(
			'fajr'    => array( 'label' => __( 'Fajr', 'mosque-timetable' ),    'start' => 'fajr_start',    'jamaat' => 'fajr_jamaat' ),
			'sunrise' => array( 'label' => __( 'Sunrise', 'mosque-timetable' ), 'start' => 'sunrise',       'jamaat' => '' ),
			'zuhr'    => array( 'label' => __( 'Zuhr', 'mosque-timetable' ),    'start' => 'zuhr_start',    'jamaat' => 'zuhr_jamaat' ),
			'asr'     => array( 'label' => __( 'Asr', 'mosque-timetable' ),     'start' => 'asr_start',     'jamaat' => 'asr_jamaat' ),
			'maghrib' => array( 'label' => __( 'Maghrib', 'mosque-timetable' ), 'start' => 'maghrib_start', 'jamaat' => 'maghrib_jamaat' ),
			'isha'    => array( 'label' => __( 'Isha', 'mosque-timetable' ),    'start' => 'isha_start',    'jamaat' => 'isha_jamaat' ),
		);

		// Inline style overrides — hardcode background to beat theme CSS specificity.
		$bg_map = array(
			'teal'     => array( 'bg' => '#0D7377', 'color' => '#ffffff' ),
			'midnight' => array( 'bg' => '#1A3A5C', 'color' => '#ffffff' ),
			'gold'     => array( 'bg' => '#C5A55A', 'color' => '#1A3A5C' ),
		);
		$bar_style = '';
		if ( 'custom' === $bg && $bg_color ) {
			$bar_style .= 'background:' . $bg_color . ';color:#fff;';
		} elseif ( isset( $bg_map[ $bg ] ) ) {
			$bar_style .= 'background:' . $bg_map[ $bg ]['bg'] . ';color:' . $bg_map[ $bg ]['color'] . ';';
		}
		if ( $offset_top && 'top' === $position ) {
			$bar_style .= '--mpb-offset:' . $offset_top . ';';
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $bar_id ); ?>"
			class="mpb-hello-bar mpb-pos-<?php echo esc_attr( $position ); ?> mpb-bg-<?php echo esc_attr( $bg ); ?><?php echo $dismissible ? ' mpb-dismissible' : ''; ?>"
			style="<?php echo esc_attr( $bar_style ); ?>"
			role="complementary"
			aria-label="<?php esc_attr_e( 'Today\'s Prayer Times', 'mosque-timetable' ); ?>">

			<div class="mpb-inner">

				<!-- Prayer times list -->
				<ul class="mpb-times" aria-label="<?php esc_attr_e( 'Prayer times', 'mosque-timetable' ); ?>">
					<?php foreach ( $slots as $key => $slot ) : ?>
						<?php
						$start  = $today_data ? ( $today_data[ $slot['start'] ] ?? '' ) : '';
						$jamaat = $today_data && $slot['jamaat'] ? ( $today_data[ $slot['jamaat'] ] ?? '' ) : '';
						if ( ! $start ) {
							continue;
						}
						$is_next = $pulse_next && ( strtolower( $slot['label'] ) === $next_name || $key === $next_name );
						?>
						<li class="mpb-prayer<?php echo $is_next ? ' mpb-next' : ''; ?>">
							<span class="mpb-prayer-name"><?php echo esc_html( $slot['label'] ); ?></span>
							<span class="mpb-prayer-times">
								<span class="mpb-start"><?php echo esc_html( $start ); ?></span>
								<?php if ( $show_jamaat && $jamaat ) : ?>
									<span class="mpb-jamaat"><?php echo esc_html( $jamaat ); ?></span>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>

				<!-- Optional CTA -->
				<?php if ( $cta_text && $cta_url ) : ?>
					<a href="<?php echo $cta_url; ?>"
						class="mpb-cta"
						target="<?php echo esc_attr( $cta_target ); ?>"
						<?php echo '_blank' === $cta_target ? 'rel="noopener noreferrer"' : ''; ?>>
						<?php echo esc_html( $cta_text ); ?>
						<span class="mpb-cta-arrow" aria-hidden="true">&#8250;</span>
					</a>
				<?php endif; ?>

				<!-- Dismiss button -->
				<?php if ( $dismissible ) : ?>
					<button class="mpb-dismiss"
						aria-label="<?php esc_attr_e( 'Close prayer times bar', 'mosque-timetable' ); ?>"
						data-bar-id="<?php echo esc_attr( $bar_id ); ?>">
						<span aria-hidden="true">&#10005;</span>
					</button>
				<?php endif; ?>

			</div><!-- .mpb-inner -->
		</div><!-- #bar_id -->
		<?php
		return ob_get_clean();
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Create main menu page (not using ACF to avoid conflicts).
		add_menu_page(
			mt_apply_terminology( 'Mosque Timetable' ), // Page title.
			mt_apply_terminology( 'Mosque Timetable' ), // Menu title.
			'edit_posts', // Capability.
			'mosque-main', // Menu slug.
			array( $this, 'render_main_admin_page' ), // Function.
			'dashicons-clock', // Icon.
			30 // Position.
		);

		// Submenu: Mosque Settings.
		add_submenu_page(
			'mosque-main',
			mt_apply_terminology( 'Mosque Configuration' ),
			mt_apply_terminology( 'Configuration' ),
			'edit_posts',
			'mosque-settings',
			array( $this, 'render_settings_page' )
		);

		// Submenu: Timetables (main functionality).
		add_submenu_page(
			'mosque-main',
			'Prayer Timetables',
			'Timetables',
			'edit_posts',
			'mosque-timetables',
			array( $this, 'render_timetables_admin_page' )
		);

		// Submenu: Appearance.
		add_submenu_page(
			'mosque-main',
			'Appearance & PWA Settings',
			'Appearance',
			'edit_posts',
			'mosque-appearance',
			array( $this, 'render_appearance_page' )
		);

		// Debug submenu (temporary).
		add_submenu_page(
			'mosque-main',
			'Debug Timetables',
			' Debug',
			'edit_posts',
			'mosque-debug',
			array( $this, 'render_debug_page' )
		);

		// Submenu: Import/Export.
		add_submenu_page(
			'mosque-main',
			'Import/Export Tools',
			'Import/Export',
			'edit_posts',
			'mosque-import-export',
			array( $this, 'render_import_export_page' )
		);

		// ACF fields are registered separately and will work with our pages.
	}

	/**
	 * Setup ACF fields on our custom admin pages
	 */
	public function setup_acf_on_custom_pages() {
		$screen = get_current_screen();

		// We don't need to do anything special here anymore.
		// ACF fields are loaded directly in the render functions.
	}

	/**
	 * Render main admin page (dashboard/overview)
	 */
	public function render_main_admin_page() {
		$mosque_name      = mt_get_option( 'mosque_name', get_bloginfo( 'name' ) );
		$default_year     = mt_get_option( 'default_year', wp_date( 'Y' ) );
		$available_months = mt_get_option( 'available_months', array() );

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
							<h3> Quick Setup</h3>
							<p>Configure your mosque details and select which months you need.</p>
							<a href="admin.php?page=mosque-settings" class="button button-primary">Configure Settings</a>
						</div>

						<div class="action-card">
							<h3> Manage Timetables</h3>
							<p>Add and edit prayer times for <?php echo esc_html( (string) $default_year ); ?>. <?php echo count( $available_months ); ?> months configured.</p>
							<a href="admin.php?page=mosque-timetables" class="button button-primary">Edit Timetables</a>
						</div>

						<div class="action-card">
							<h3> Customize Look</h3>
							<p>Change colors, enable PWA features, and customize the appearance.</p>
							<a href="admin.php?page=mosque-appearance" class="button">Customize Appearance</a>
						</div>

						<div class="action-card">
							<h3> Import/Export</h3>
							<p>Import CSV data or export calendars for sharing.</p>
							<a href="admin.php?page=mosque-import-export" class="button">Import/Export Tools</a>
						</div>
					</div>

					<div class="mosque-status-panel">
						<h3>System Status</h3>
						<ul>
							<li><strong>Mosque:</strong> <?php echo esc_html( $mosque_name ); ?></li>
							<li><strong>Current Year:</strong> <?php echo esc_html( (string) $default_year ); ?></li>
							<li><strong>Active Months:</strong> <?php echo esc_html( (string) count( $available_months ) ); ?>/12</li>
							<li><strong>ACF Pro:</strong> <?php echo function_exists( 'acf' ) ? ' Installed' : ' Missing'; ?></li>
							<li><strong>PWA:</strong> <?php echo mt_get_option( 'enable_pwa', false ) ? ' Enabled' : ' Disabled'; ?></li>
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
				/* ---- Mosque Timetable Admin Dashboard — Islamic Design System ---- */
				:root {
					--mta-primary:   #0D7377;
					--mta-dark:      #1A3A5C;
					--mta-gold:      #C5A55A;
					--mta-cream:     #F5F1EB;
					--mta-border:    #e0dbd2;
					--mta-text:      #2d2d2d;
					--mta-font-h:    'El Messiri', 'Segoe UI', Georgia, serif;
					--mta-font-b:    'DM Sans', -apple-system, 'Segoe UI', sans-serif;
				}

				.mosque-page-header {
					display: flex;
					align-items: center;
					gap: 14px;
					margin-bottom: 20px;
				}

				.mosque-logo {
					width: 48px;
					height: 48px;
					border-radius: 10px;
					box-shadow: 0 3px 10px rgba(13, 115, 119, 0.2);
				}

				.mosque-page-header h1 {
					margin: 0;
					font-family: var(--mta-font-h);
					color: var(--mta-primary);
					font-size: 22px;
					font-weight: 700;
					letter-spacing: 0.01em;
				}

				.mosque-dashboard {
					display: grid;
					grid-template-columns: 2fr 1fr;
					gap: 20px;
					margin-top: 20px;
				}

				.mosque-welcome-panel {
					grid-column: span 2;
					background: linear-gradient(135deg, #0D7377 0%, #1A3A5C 100%);
					background-image:
						radial-gradient(circle at 15% 60%, rgba(197, 165, 90, 0.18) 0%, transparent 55%),
						radial-gradient(circle at 85% 15%, rgba(255,255,255,0.06) 0%, transparent 40%),
						linear-gradient(135deg, #0D7377 0%, #1A3A5C 100%);
					color: white;
					padding: 28px 32px;
					border-radius: 10px;
					margin-bottom: 20px;
					box-shadow: 0 8px 28px rgba(13, 115, 119, 0.28);
					position: relative;
					overflow: hidden;
				}

				.mosque-welcome-panel h2 {
					margin-top: 0;
					font-family: var(--mta-font-h);
					font-size: 26px;
					font-weight: 700;
					letter-spacing: 0.01em;
				}

				.mosque-welcome-panel p {
					margin-bottom: 0;
					opacity: 0.88;
					font-size: 14px;
				}

				.mosque-quick-actions {
					display: grid;
					grid-template-columns: 1fr 1fr;
					gap: 15px;
				}

				.action-card {
					background: white;
					padding: 20px;
					border: 1px solid var(--mta-border);
					border-left: 3px solid var(--mta-primary);
					border-radius: 8px;
					box-shadow: 0 2px 8px rgba(13, 115, 119, 0.07);
					transition: box-shadow 0.2s, border-color 0.2s;
				}

				.action-card:hover {
					box-shadow: 0 4px 16px rgba(13, 115, 119, 0.14);
					border-left-color: var(--mta-gold);
				}

				.action-card h3 {
					margin-top: 0;
					font-family: var(--mta-font-h);
					font-size: 15px;
					font-weight: 700;
					color: var(--mta-primary);
					letter-spacing: 0.01em;
				}

				.action-card .button-primary {
					background: var(--mta-primary);
					border-color: var(--mta-primary);
				}

				.action-card .button-primary:hover {
					background: #0a5d61;
					border-color: #0a5d61;
				}

				.mosque-status-panel,
				.mosque-shortcodes-panel {
					background: white;
					padding: 20px;
					border: 1px solid var(--mta-border);
					border-top: 3px solid var(--mta-gold);
					border-radius: 8px;
					box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
					margin-bottom: 15px;
				}

				.mosque-status-panel h3,
				.mosque-shortcodes-panel h3 {
					font-family: var(--mta-font-h);
					color: var(--mta-dark);
					font-size: 15px;
					margin-top: 0;
					margin-bottom: 14px;
				}

				.mosque-status-panel ul,
				.mosque-shortcodes-panel ul {
					list-style: none;
					padding: 0;
					margin: 0;
				}

				.mosque-status-panel li {
					padding: 7px 0;
					border-bottom: 1px solid #f0ede8;
					font-size: 13px;
					color: var(--mta-text);
				}

				.mosque-status-panel li:last-child { border-bottom: none; }

				.mosque-shortcodes-panel code {
					background: #f0f8f8;
					color: var(--mta-primary);
					padding: 2px 7px;
					border-radius: 4px;
					font-family: 'Space Mono', monospace;
					font-size: 12px;
					border: 1px solid rgba(13, 115, 119, 0.15);
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
					padding: 22px 24px;
					background: var(--mta-cream);
					border: 1px solid var(--mta-border);
					border-top: 3px solid var(--mta-gold);
					border-radius: 8px;
					text-align: center;
					color: #5a5347;
					grid-column: span 2;
				}

				.mosque-support-footer h3 {
					font-family: var(--mta-font-h);
					color: var(--mta-primary);
					margin-top: 0;
					font-size: 16px;
				}

				.mosque-support-footer a {
					color: var(--mta-primary);
					text-decoration: none;
					font-weight: 500;
				}

				.mosque-support-footer a:hover {
					color: var(--mta-dark);
					text-decoration: underline;
				}
			</style>

			<div class="mosque-support-footer">
				<h3> Supporting the Muslim Community</h3>
				<p>We're dedicated to empowering mosques and Islamic centres worldwide with professional digital solutions. Our team provides comprehensive support for website development, digital marketing, and technology solutions to help strengthen your community's online presence.</p>
				<p>Need assistance with your mosque's digital needs? We're here to help with everything from prayer time systems to complete website solutions.</p>
				<p><strong>Contact us:</strong> <a href="mailto:ibraheem@mosquewebdesign.com">ibraheem@mosquewebdesign.com</a> | <a href="https://mosquewebdesign.com" target="_blank">mosquewebdesign.com</a></p>
			</div>

			<?php
	}

	/**
	 * Render settings page with ACF fields
	 */
	/**
	 * Output the standard teal admin page header used across all plugin pages.
	 *
	 * @param string $title    Page title.
	 * @param string $subtitle Subtitle/description.
	 * @param string $icon     Dashicon name (without dashicons- prefix).
	 */
	private function admin_page_header( $title, $subtitle = '', $icon = 'calendar-alt' ) {
		?>
		<div class="mosque-admin-header" style="background:linear-gradient(135deg,#0D7377 0%,#1A3A5C 100%);color:#fff;padding:24px 30px;border-radius:8px;margin-bottom:20px;box-shadow:0 6px 24px rgba(13,115,119,.28);position:relative;overflow:hidden;">
			<div style="display:flex;align-items:center;gap:14px;">
				<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>" style="font-size:32px;width:32px;height:32px;flex-shrink:0;"></span>
				<div>
					<h1 style="margin:0;font-family:'El Messiri','Segoe UI',Georgia,serif;font-size:22px;font-weight:700;letter-spacing:.01em;color:#fff;"><?php echo esc_html( $title ); ?></h1>
					<?php if ( $subtitle ) : ?>
						<p style="margin:4px 0 0;font-size:13px;opacity:.85;color:#fff;"><?php echo esc_html( $subtitle ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_settings_page() {
		// Handle form submission first.
		if ( isset( $_POST['submit'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mosque_timetable_nonce'] ?? '' ) ), 'mosque_timetable_action' ) ) {
			$this->save_mosque_settings();
			$message = 'Configuration updated successfully!';
		}

		// Check for ACF form save success.
		if ( isset( $_GET['updated'] ) && sanitize_text_field( wp_unslash( $_GET['updated'] ) ) === 'true' ) {
			$message = 'Configuration updated successfully!';
		}

		?>
			<div class="wrap mosque-timetable-admin">
				<?php $this->admin_page_header( 'Mosque Configuration', 'Configure your mosque details and system settings.', 'admin-settings' ); ?>

			<?php if ( isset( $message ) ) : ?>
					<div class="notice notice-success">
						<p><?php echo esc_html( $message ); ?></p>
					</div>
				<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'mosque_timetable_action', 'mosque_timetable_nonce' ); ?>
			<?php $this->render_fallback_settings_form(); ?>
			</form>
			</div>
			<?php
	}

	/**
	 * Save mosque settings (fallback method)
	 */
	private function save_mosque_settings() {
		// Verify nonce for state-changing operations.
		check_admin_referer( 'mosque_timetable_action', 'mosque_timetable_nonce' );

		if ( isset( $_POST['mosque_name'] ) ) {
			update_option( 'mosque_name', sanitize_text_field( wp_unslash( $_POST['mosque_name'] ) ) );
		}
		if ( isset( $_POST['mosque_address'] ) ) {
			update_option( 'mosque_address', sanitize_textarea_field( wp_unslash( $_POST['mosque_address'] ) ) );
		}
		if ( isset( $_POST['mosque_street_address'] ) ) {
			update_option( 'mosque_street_address', sanitize_text_field( wp_unslash( $_POST['mosque_street_address'] ) ) );
		}
		if ( isset( $_POST['mosque_city'] ) ) {
			update_option( 'mosque_city', sanitize_text_field( wp_unslash( $_POST['mosque_city'] ) ) );
		}
		if ( isset( $_POST['mosque_postcode'] ) ) {
			update_option( 'mosque_postcode', sanitize_text_field( wp_unslash( $_POST['mosque_postcode'] ) ) );
		}
		if ( isset( $_POST['mosque_country'] ) ) {
			update_option( 'mosque_country', sanitize_text_field( wp_unslash( $_POST['mosque_country'] ) ) );
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
		// Prayer calculation settings.
		if ( isset( $_POST['enable_auto_times'] ) ) {
			update_option( 'enable_auto_times', 1 );
		} else {
			update_option( 'enable_auto_times', 0 );
		}
		if ( isset( $_POST['mosque_latitude'] ) ) {
			$latitude = floatval( wp_unslash( $_POST['mosque_latitude'] ) );
			if ( $latitude >= -90 && $latitude <= 90 ) {
				update_option( 'mosque_latitude', $latitude );
			}
		}
		if ( isset( $_POST['mosque_longitude'] ) ) {
			$longitude = floatval( wp_unslash( $_POST['mosque_longitude'] ) );
			if ( $longitude >= -180 && $longitude <= 180 ) {
				update_option( 'mosque_longitude', $longitude );
			}
		}
		if ( isset( $_POST['calculation_method'] ) ) {
			$method = sanitize_text_field( wp_unslash( $_POST['calculation_method'] ) );
			update_option( 'calculation_method', $method );
		}
		// Jamaah offsets.
		if ( isset( $_POST['fajr_jamaat_offset'] ) ) {
			update_option( 'fajr_jamaat_offset', intval( wp_unslash( $_POST['fajr_jamaat_offset'] ) ) );
		}
		if ( isset( $_POST['zuhr_jamaat_offset'] ) ) {
			update_option( 'zuhr_jamaat_offset', intval( wp_unslash( $_POST['zuhr_jamaat_offset'] ) ) );
		}
		if ( isset( $_POST['asr_jamaat_offset'] ) ) {
			update_option( 'asr_jamaat_offset', intval( wp_unslash( $_POST['asr_jamaat_offset'] ) ) );
		}
		if ( isset( $_POST['maghrib_jamaat_offset'] ) ) {
			update_option( 'maghrib_jamaat_offset', intval( wp_unslash( $_POST['maghrib_jamaat_offset'] ) ) );
		}
		if ( isset( $_POST['isha_jamaat_offset'] ) ) {
			update_option( 'isha_jamaat_offset', intval( wp_unslash( $_POST['isha_jamaat_offset'] ) ) );
		}
		if ( isset( $_POST['terminology_overrides'] ) && is_array( $_POST['terminology_overrides'] ) ) {
			$terminology_overrides = array();
			$clean_overrides       = wp_unslash( $_POST['terminology_overrides'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array elements sanitized individually in loop below
			foreach ( $clean_overrides as $override ) {
				if ( ! empty( $override['from'] ) && ! empty( $override['to'] ) ) {
					$terminology_overrides[] = array(
						'from'    => sanitize_text_field( $override['from'] ), // Already unslashed above.
						'to'      => sanitize_text_field( $override['to'] ), // Already unslashed above.
						'enabled' => isset( $override['enabled'] ) ? 1 : 0,
					);
				}
			}
			update_option( 'terminology_overrides', $terminology_overrides );
		}
		// Push notification settings.
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
		// Ramadan Mode settings.
		if ( isset( $_POST['ramadan_start_date'] ) ) {
			$rsd = sanitize_text_field( wp_unslash( $_POST['ramadan_start_date'] ) );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $rsd ) ) {
				update_option( 'ramadan_start_date', $rsd );
			}
		}
		if ( isset( $_POST['ramadan_end_date'] ) ) {
			$red = sanitize_text_field( wp_unslash( $_POST['ramadan_end_date'] ) );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $red ) ) {
				update_option( 'ramadan_end_date', $red );
			}
		}
		if ( isset( $_POST['ramadan_suhoor_margin'] ) ) {
			$margin = max( 0, min( 60, intval( wp_unslash( $_POST['ramadan_suhoor_margin'] ) ) ) );
			update_option( 'ramadan_suhoor_margin', $margin );
		}
	}

	/**
	 * Handle ACF form save redirects properly
	 */
	public function handle_acf_save_redirect( $post_id ) {
		// Only handle options page saves.
		if ( 'options' !== $post_id ) {
			return;
		}

		// Verify nonce for state-changing operations.
		// If ACF Pro is active, it handles nonce verification; otherwise we need to verify.
		if ( function_exists( 'acf_verify_nonce' ) || class_exists( 'ACF' ) ) {
			// ACF Pro handles nonce verification internally.
			$acf_screen = isset( $_POST['_acf_screen'] ) ? sanitize_text_field( wp_unslash( $_POST['_acf_screen'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF Pro handles nonce verification
		} else {
			// When using fallback stubs, we must verify nonce ourselves.
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
		// Handle form submission first.
		if ( isset( $_POST['submit'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mosque_timetable_nonce'] ?? '' ) ), 'mosque_timetable_action' ) ) {
			$this->save_appearance_settings();
			$message = 'Appearance settings updated successfully!';
		}

		// Check for ACF form save success.
		if ( isset( $_GET['updated'] ) && sanitize_text_field( wp_unslash( $_GET['updated'] ) ) === 'true' ) {
			$message = 'Appearance settings updated successfully!';
		}

		?>
			<div class="wrap mosque-timetable-admin">
				<?php $this->admin_page_header( 'Appearance & PWA Settings', 'Customize colors, fonts, and Progressive Web App features.', 'art' ); ?>

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
		// Verify nonce for state-changing operations.
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
		update_option( 'enable_sticky_prayer_bar', isset( $_POST['enable_sticky_prayer_bar'] ) ? 1 : 0 );
		if ( isset( $_POST['notification_text'] ) ) {
			update_option( 'notification_text', sanitize_text_field( wp_unslash( $_POST['notification_text'] ) ) );
		}
	}

	/**
	 * Render fallback settings form (when ACF is not available)
	 */
	private function render_fallback_settings_form() {
		$mosque_name        = get_option( 'mosque_name', get_bloginfo( 'name' ) );
		$mosque_address     = get_option( 'mosque_address', 'Birmingham, UK' );
		$street_address     = get_option( 'mosque_street_address', '' );
		$city               = get_option( 'mosque_city', '' );
		$postcode           = get_option( 'mosque_postcode', '' );
		$country            = get_option( 'mosque_country', 'United Kingdom' );
		$latitude           = get_option( 'mosque_latitude', '' );
		$longitude          = get_option( 'mosque_longitude', '' );
		$enable_auto_times  = get_option( 'enable_auto_times', 1 );
		$calculation_method = get_option( 'calculation_method', '2' );
		$default_year       = get_option( 'default_year', wp_date( 'Y' ) );
		$auto_calendar_url  = mt_get_subscribe_url();
		$available_months   = get_option( 'available_months', array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' ) );

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
					<th><label>Mosque Address</label></th>
					<td>
						<div style="margin-bottom:15px"><label for="mosque_street_address" style="display:block;font-weight:600;margin-bottom:5px">Street Address</label><input type="text" id="mosque_street_address" name="mosque_street_address" value="<?php echo esc_attr( $street_address ); ?>" class="regular-text" placeholder="e.g., 123 High Street" style="margin-bottom:10px"/><label for="mosque_city" style="display:block;font-weight:600;margin-bottom:5px">City/Town</label><input type="text" id="mosque_city" name="mosque_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" placeholder="e.g., Birmingham" style="margin-bottom:10px"/><label for="mosque_postcode" style="display:block;font-weight:600;margin-bottom:5px">Postcode/ZIP</label><input type="text" id="mosque_postcode" name="mosque_postcode" value="<?php echo esc_attr( $postcode ); ?>" class="regular-text" placeholder="e.g., B11 4EA" style="margin-bottom:10px"/><label for="mosque_country" style="display:block;font-weight:600;margin-bottom:5px">Country</label><select id="mosque_country" name="mosque_country" class="regular-text" style="margin-bottom:10px"><option value="United Kingdom" <?php selected( $country, 'United Kingdom' ); ?>>United Kingdom</option><option value="United States" <?php selected( $country, 'United States' ); ?>>United States</option><option value="Canada" <?php selected( $country, 'Canada' ); ?>>Canada</option><option value="Australia" <?php selected( $country, 'Australia' ); ?>>Australia</option><option value="Pakistan" <?php selected( $country, 'Pakistan' ); ?>>Pakistan</option><option value="India" <?php selected( $country, 'India' ); ?>>India</option><option value="Bangladesh" <?php selected( $country, 'Bangladesh' ); ?>>Bangladesh</option><option value="South Africa" <?php selected( $country, 'South Africa' ); ?>>South Africa</option><option value="United Arab Emirates" <?php selected( $country, 'United Arab Emirates' ); ?>>United Arab Emirates</option><option value="Saudi Arabia" <?php selected( $country, 'Saudi Arabia' ); ?>>Saudi Arabia</option><option value="Malaysia" <?php selected( $country, 'Malaysia' ); ?>>Malaysia</option><option value="Indonesia" <?php selected( $country, 'Indonesia' ); ?>>Indonesia</option><option value="Turkey" <?php selected( $country, 'Turkey' ); ?>>Turkey</option><option value="Egypt" <?php selected( $country, 'Egypt' ); ?>>Egypt</option><option value="Morocco" <?php selected( $country, 'Morocco' ); ?>>Morocco</option><option value="Nigeria" <?php selected( $country, 'Nigeria' ); ?>>Nigeria</option><option value="Other" <?php selected( $country, 'Other' ); ?>>Other</option></select><div style="margin-top:15px"><button type="button" id="find-coordinates-btn" class="button button-primary" style="margin-bottom:10px"><span class="dashicons dashicons-location" style="margin-top:3px"></span> Find My Coordinates</button><span id="geocode-status" style="margin-left:10px;font-weight:600"></span></div></div><div style="background:#f0f0f1;padding:15px;border-radius:4px;border-left:4px solid #667eea"><label for="mosque_latitude" style="display:block;font-weight:600;margin-bottom:5px">Latitude</label><input type="text" id="mosque_latitude" name="mosque_latitude" value="<?php echo esc_attr( $latitude ); ?>" class="regular-text" placeholder="e.g., 52.4862" style="margin-bottom:10px"/><label for="mosque_longitude" style="display:block;font-weight:600;margin-bottom:5px">Longitude</label><input type="text" id="mosque_longitude" name="mosque_longitude" value="<?php echo esc_attr( $longitude ); ?>" class="regular-text" placeholder="e.g., -1.8904"/><p class="description" style="margin-top:10px"><strong> Coordinates are used for automatic prayer time calculation.</strong><br>Click "Find My Coordinates" above to automatically populate these fields, or enter them manually.<br>Find coordinates at <a href="https://www.latlong.net/" target="_blank">latlong.net</a></p></div><input type="hidden" id="mosque_address" name="mosque_address" value="<?php echo esc_attr( $mosque_address ); ?>"/>
					</td>
				</tr>
				<tr>
					<th><label>Automatic Prayer Times</label></th>
					<td>
						<fieldset><label><input type="checkbox" name="enable_auto_times" value="1" <?php checked( $enable_auto_times, 1 ); ?>/> <strong>Enable automatic prayer time generation</strong></label><p class="description">When enabled, the "Generate Dates" button will automatically fetch prayer times from Aladhan API based on your mosque coordinates.</p><div style="margin-top:15px"><label for="calculation_method" style="display:block;font-weight:600;margin-bottom:5px">Calculation Method</label><select id="calculation_method" name="calculation_method" class="regular-text"><option value="0" <?php selected( $calculation_method, '0' ); ?>>Shia Ithna-Ansari</option><option value="1" <?php selected( $calculation_method, '1' ); ?>>University of Islamic Sciences, Karachi</option><option value="2" <?php selected( $calculation_method, '2' ); ?>>Islamic Society of North America (ISNA)</option><option value="3" <?php selected( $calculation_method, '3' ); ?>>Muslim World League (MWL)</option><option value="4" <?php selected( $calculation_method, '4' ); ?>>Umm Al-Qura University, Makkah</option><option value="5" <?php selected( $calculation_method, '5' ); ?>>Egyptian General Authority of Survey</option><option value="7" <?php selected( $calculation_method, '7' ); ?>>Institute of Geophysics, University of Tehran</option><option value="8" <?php selected( $calculation_method, '8' ); ?>>Gulf Region</option><option value="9" <?php selected( $calculation_method, '9' ); ?>>Kuwait</option><option value="10" <?php selected( $calculation_method, '10' ); ?>>Qatar</option><option value="11" <?php selected( $calculation_method, '11' ); ?>>Majlis Ugama Islam Singapura, Singapore</option><option value="12" <?php selected( $calculation_method, '12' ); ?>>Union Organization islamic de France</option><option value="13" <?php selected( $calculation_method, '13' ); ?>>Diyanet Isleri Baskanligi, Turkey</option><option value="14" <?php selected( $calculation_method, '14' ); ?>>Spiritual Administration of Muslims of Russia</option></select><p class="description">Select the calculation method appropriate for your region.</p></div></fieldset>
					</td>
				</tr>
				<tr>
					<th><label for="default_year">Default Year</label></th>
					<td>
						<input type="number" id="default_year" name="default_year" value="<?php echo esc_attr( (string) $default_year ); ?>" min="2020" max="2035" />
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
									<span></span>
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
						<p class="description">Customize terminology used throughout the plugin interface. Changes apply to labels only, not internal data. Examples: "Mosque"  "Masjid", "Zuhr"  "Dhuhr".</p>
						<script>
							document.getElementById('add-terminology-override').addEventListener('click', function() {
								const container = document.getElementById('terminology-overrides');
								const index = container.children.length;
								const newRow = document.createElement('div');
								newRow.className = 'terminology-override-row';
								newRow.style.cssText = 'margin-bottom: 10px; display: flex; gap: 10px; align-items: center;';
								newRow.innerHTML = `
							<input type="text" name="terminology_overrides[${index}][from]" placeholder="From (e.g., Mosque)" style="width: 150px;" />
							<span></span>
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
								$checked = in_array( (int) $num, $available_months, true ) ? 'checked' : '';
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

			<!-- ====== Ramadan Mode ====== -->
			<div style="background:linear-gradient(135deg,#0D7377,#1A3A5C);color:#fff;padding:20px 24px;border-radius:8px;margin:24px 0 20px;">
				<h2 style="margin:0 0 6px;font-family:'El Messiri',Georgia,serif;font-size:18px;font-weight:700;">&#9790; Ramadan Mode</h2>
				<p style="margin:0;font-size:13px;opacity:.85;">Set Ramadan dates to enable the <code style="background:rgba(255,255,255,.15);border-radius:3px;padding:1px 5px;">[ramadan_info]</code> shortcode and automatic Suhoor/Iftar display.</p>
			</div>
			<table class="form-table">
				<tr>
					<th><label for="ramadan_start_date">Ramadan Start Date</label></th>
					<td>
						<input type="date" id="ramadan_start_date" name="ramadan_start_date"
							value="<?php echo esc_attr( mt_get_option( 'ramadan_start_date', '' ) ); ?>" />
						<p class="description">First day of Ramadan <?php echo esc_html( wp_date( 'Y' ) ); ?> (e.g. 2026-03-01)</p>
					</td>
				</tr>
				<tr>
					<th><label for="ramadan_end_date">Ramadan End Date</label></th>
					<td>
						<input type="date" id="ramadan_end_date" name="ramadan_end_date"
							value="<?php echo esc_attr( mt_get_option( 'ramadan_end_date', '' ) ); ?>" />
						<p class="description">Last day of Ramadan / Eid al-Fitr eve</p>
					</td>
				</tr>
				<tr>
					<th><label for="ramadan_suhoor_margin">Suhoor Ends Before Fajr</label></th>
					<td>
						<input type="number" id="ramadan_suhoor_margin" name="ramadan_suhoor_margin" min="0" max="60"
							value="<?php echo esc_attr( (string) mt_get_option( 'ramadan_suhoor_margin', 15 ) ); ?>" style="width:80px;" />
						<span>minutes before Fajr</span>
						<p class="description">Suhoor end time = Fajr - this margin (default 15 min). Adjust to match your local practice.</p>
					</td>
				</tr>
			</table>
			<p style="background:#f0f8f8;padding:12px 16px;border-left:4px solid #0D7377;border-radius:0 6px 6px 0;font-size:13px;">
				<strong>Usage:</strong>
				<code>[ramadan_info]</code> &mdash; card layout &nbsp;|&nbsp;
				<code>[ramadan_info layout="banner"]</code> &mdash; full-width banner &nbsp;|&nbsp;
				<code>[ramadan_info layout="compact"]</code> &mdash; inline pill
			</p>

			<?php submit_button( 'Save Configuration' ); ?>

			<script>
			jQuery(document).ready(function($) {
				$('#find-coordinates-btn').on('click', function() {
					const button = $(this);
					const status = $('#geocode-status');

					// Get address components.
					const street = $('#mosque_street_address').val().trim();
					const city = $('#mosque_city').val().trim();
					const postcode = $('#mosque_postcode').val().trim();
					const country = $('#mosque_country').val().trim();

					// Validation.
					if (!street && !city && !postcode) {
						status.css('color', '#d63638').text(' Please enter at least street, city, or postcode');
						return;
					}

					// Build address string.
					const addressParts = [street, city, postcode, country].filter(part => part.length > 0);
					const fullAddress = addressParts.join(', ');

					// Show loading state.
					button.prop('disabled', true);
					status.css('color', '#0D7377').html('<span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite;"></span> Finding coordinates...');

					// Call Nominatim API (OpenStreetMap).
					$.ajax({
						url: 'https://nominatim.openstreetmap.org/search',
						data: {
							q: fullAddress,
							format: 'json',
							limit: 1,
							addressdetails: 1
						},
						dataType: 'json',
						headers: {
							'User-Agent': 'Mosque Timetable WordPress Plugin'
						},
						success: function(data) {
							if (data && data.length > 0) {
								const lat = parseFloat(data[0].lat).toFixed(6);
								const lon = parseFloat(data[0].lon).toFixed(6);

								// Populate fields.
								$('#mosque_latitude').val(lat);
								$('#mosque_longitude').val(lon);

								// Show success.
								status.css('color', '#00a32a').html(' Coordinates found! <small>(' + lat + ', ' + lon + ')</small>');

								// Highlight the coordinate fields briefly.
								$('#mosque_latitude, #mosque_longitude').css({
									'background-color': '#e6f4ea',
									'transition': 'background-color 0.3s'
								});
								setTimeout(function() {
									$('#mosque_latitude, #mosque_longitude').css('background-color', '');
								}, 2000);

							} else {
								status.css('color', '#d63638').text(' No results found. Try a different address or enter coordinates manually.');
							}
						},
						error: function() {
							status.css('color', '#d63638').text(' Geocoding failed. Please check your internet connection or enter coordinates manually.');
						},
						complete: function() {
							button.prop('disabled', false);
						}
					});
				});
			});
			</script>
			<style>
			@keyframes spin {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
			</style>
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
					<th><label for="enable_sticky_prayer_bar">Enable Sticky Prayer Bar</label></th>
					<td>
						<input type="checkbox" id="enable_sticky_prayer_bar" name="enable_sticky_prayer_bar" value="1" <?php checked( get_option( 'enable_sticky_prayer_bar', 1 ) ); ?> />
						<label for="enable_sticky_prayer_bar">Display sticky prayer bar under the navbar on all pages showing today's prayer times</label>
						<p class="description">Shows current prayer times in a sticky bar at the top of pages. Includes swipeable prayer chips on mobile.</p>
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
			<div class="wrap mosque-timetable-admin">
				<?php $this->admin_page_header( 'Import / Export Tools', 'Import CSV/XLSX prayer times or export ICS calendars.', 'upload' ); ?>

				<div class="mosque-import-export-container">
					<div class="card">
						<h2> Import Prayer Times</h2>
						<p>Import prayer times from various sources:</p>
						<ul>
							<li> <strong>CSV Files</strong> - Standard comma-separated values</li>
							<li> <strong>XLSX Files</strong> - Excel spreadsheets</li>
							<li> <strong>Copy/Paste</strong> - Direct from Google Sheets</li>
						</ul>

						<h3> Sample Templates</h3>
						<p>Download example files to see the correct format for your prayer time data:</p>
						<div class="sample-downloads">
							<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=download_sample_csv&nonce=' . wp_create_nonce( 'mosque_sample_download' ) ) ); ?>" // Escape output.
								class="button button-secondary" target="_blank">
								 Download Sample CSV
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=download_sample_xlsx&nonce=' . wp_create_nonce( 'mosque_sample_download' ) ) ); ?>" // Escape output.
								class="button button-secondary" target="_blank">
								 Download Sample XLSX
							</a>
						</div>
						<p class="description">These templates show the exact column headers and data format required for successful imports. Fill in your mosque's prayer times using the same structure.</p>

						<p><a href="admin.php?page=mosque-timetables" class="button button-primary">Go to Timetables Page to Import</a></p>
					</div>

					<div class="card">
						<h2> Export Prayer Times</h2>
						<p>Export your prayer times in various formats:</p>

						<h3> ICS Calendar Export</h3>
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

							<p><input type="submit" class="button button-primary" value=" Export ICS Calendar"></p>
						</form>

						<h3> CSV Export</h3>
						<p>Export prayer times as CSV for use in spreadsheets.</p>
						<button class="button" id="export-csv-btn"> Export CSV</button>

						<h3> Auto-Generated Calendar Subscription</h3>
						<?php $auto_calendar_url = mt_get_subscribe_url(); ?>
						<p><strong>Your automatic prayer calendar URL:</strong><br>
							<code><?php echo esc_url( $auto_calendar_url ); ?></code>
						</p>
						<p><a href="<?php echo esc_url( $auto_calendar_url ); ?>" target="_blank" class="button"> Download Prayer Calendar</a></p>
						<p class="description">This calendar is automatically generated from your prayer timetables and updates whenever you change prayer times. Share this URL with your congregation so they can subscribe to prayer times in their calendar apps.</p>
					</div>

					<div class="card">
						<h2> Data Management</h2>
						<p>Manage your prayer time data:</p>

						<h3> Clear All Data</h3>
						<p><strong>Warning:</strong> This will remove all prayer times from all months.</p>
						<button class="button button-secondary" id="clear-all-data-btn" onclick="if(confirm('Are you sure you want to delete ALL prayer time data? This cannot be undone!')) { clearAllData(); }"> Clear All Prayer Times</button>

						<h3> Reset to Empty Structure</h3>
						<p>Keep the date structure but remove all prayer times.</p>
						<button class="button" id="reset-structure-btn" onclick="if(confirm('Reset all months to empty date structure?')) { resetToEmptyStructure(); }"> Reset Prayer Times</button>

						<h3> Regenerate Dates</h3>
						<p>Regenerate all dates based on current default year setting.</p>
						<button class="button" id="regenerate-dates-btn" onclick="if(confirm('Regenerate all dates? This will update Hijri dates too.')) { regenerateAllDates(); }"> Regenerate All Dates</button>
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
						nonce: '<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>' // Escape output.
					}, function(response) {
						if (response.success) {
							alert(' All prayer data cleared successfully!');
						} else {
							alert(' Error: ' + response.data);
						}
					});
				}

				function resetToEmptyStructure() {
					jQuery.post(ajaxurl, {
						action: 'reset_to_empty_structure',
						nonce: '<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>' // Escape output.
					}, function(response) {
						if (response.success) {
							alert(' Prayer times reset to empty structure!');
						} else {
							alert(' Error: ' + response.data);
						}
					});
				}

				function regenerateAllDates() {
					jQuery.post(ajaxurl, {
						action: 'regenerate_all_dates',
						nonce: '<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>' // Escape output.
					}, function(response) {
						if (response.success) {
							alert(' All dates regenerated successfully!');
						} else {
							alert(' Error: ' + response.data);
						}
					});
				}

				jQuery(document).ready(function($) {
					// prevent wiring twice.
					if (window.__mt_import_wired__) return;
					window.__mt_import_wired__ = true;

					$('#export-csv-btn').on('click', function() {
						window.open(ajaxurl + '?action=export_csv_calendar&nonce=' + '<?php echo esc_attr( wp_create_nonce( 'mosque_timetable_nonce' ) ); ?>', '_blank'); // Escape output.
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
			<div class="wrap mosque-timetable-admin">
				<?php $this->admin_page_header( 'Debug Report', 'Diagnostic information for your timetable system.', 'search' ); ?>

				<div style="background:#f0f8f8;padding:15px;margin:10px 0;border-left:4px solid #0D7377;border-radius:0 6px 6px 0;">
					<h3>Quick Test: Go to Timetables Page</h3>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=mosque-timetables' ) ); ?>" class="button button-primary" target="_blank">Open Timetables Page</a></p> <!-- Escape output -->
					<p>Then come back here to see the diagnostic results below.</p>
				</div>

			<?php
			// Test 1: AJAX Actions.
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
					echo '<p style="color: green;"> ' . esc_html( $description ) . ' (' . esc_html( $action ) . ')</p>'; // Escape output.
				} else {
					echo '<p style="color: red;"> ' . esc_html( $description ) . ' (' . esc_html( $action ) . ') - MISSING!</p>'; // Escape output.
				}
			}
			echo '</div>';

			// Test 2: JavaScript Files.
			echo '<div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px;">';
			echo '<h2>Test 2: Admin Assets</h2>';

			$admin_js_file = MOSQUE_TIMETABLE_PLUGIN_DIR . 'assets/mosque-timetable-admin.js';
			if ( file_exists( $admin_js_file ) ) {
				echo '<p style="color: green;"> Admin JS file exists (' . number_format( filesize( $admin_js_file ) ) . ' bytes)</p>';
			} else {
				echo '<p style="color: red;"> Admin JS file missing</p>';
			}

			$admin_css_file = MOSQUE_TIMETABLE_PLUGIN_DIR . 'assets/mosque-timetable-admin.css';
			if ( file_exists( $admin_css_file ) ) {
				echo '<p style="color: green;"> Admin CSS file exists (' . number_format( filesize( $admin_css_file ) ) . ' bytes)</p>';
			} else {
				echo '<p style="color: red;"> Admin CSS file missing</p>';
			}
			echo '</div>';

			// Test 3: Month Data.
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
					echo '<p style="color: green;"> ' . esc_html( $months[ $month - 1 ] ) . ': ' . esc_html( count( $month_data ) ) . ' days</p>'; // Escape output.
				} else {
					echo '<p style="color: orange;"> ' . esc_html( $months[ $month - 1 ] ) . ': No data</p>'; // Escape output.
				}
			}
			echo '</div>';

			// Test 4: Browser Console Test.
			echo '<div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px;">';
			echo '<h2>Test 4: Browser Console Test</h2>';
			echo '<p>Copy this code and paste it into the browser console on the Timetables page:</p>';
			?>
				<textarea readonly style="width: 100%; height: 200px; font-family: monospace; font-size: 12px;">console.log("=== MOSQUE TIMETABLE DEBUG ===");

// Check main objects.
if (typeof MosqueTimetableAdmin !== 'undefined') {
	console.log(' MosqueTimetableAdmin exists:', MosqueTimetableAdmin);
} else {
	console.error(' MosqueTimetableAdmin missing');
}

if (typeof mosqueTimetableAdmin !== 'undefined') {
	console.log(' mosqueTimetableAdmin config:', mosqueTimetableAdmin);
} else {
	console.error(' mosqueTimetableAdmin config missing');
}

// Test AJAX.
if (typeof jQuery !== 'undefined' && mosqueTimetableAdmin?.ajaxUrl) {
	jQuery.post(mosqueTimetableAdmin.ajaxUrl, {
		action: 'get_month_timetable',
		month: 9,
		year: 2024,
		nonce: mosqueTimetableAdmin.nonce
	}).done(function(response) {
		console.log(' AJAX test successful:', response);
	}).fail(function(xhr) {
		console.error(' AJAX test failed:', xhr.responseText);
	});
}

console.log("=== DEBUG COMPLETE ===");</textarea>
			<?php
			echo '<p><strong>Instructions:</strong></p>';
			echo '<ol>';
			echo '<li>Go to the <a href="' . esc_url( admin_url( 'admin.php?page=mosque-timetables' ) ) . '" target="_blank">Timetables page</a></li>'; // Escape output.
			echo '<li>Press F12 to open browser dev tools</li>';
			echo '<li>Go to Console tab</li>';
			echo '<li>Copy and paste the code above</li>';
			echo '<li>Press Enter and check the results</li>';
			echo '</ol>';
			echo '</div>';
			?>

				<div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107;">
					<h3>What to Do Next</h3>
					<p><strong>If you see red  errors above:</strong></p>
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
		// Register namespace.
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
					// Legacy parameters for backward compatibility.
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

		// Widget endpoints for PWA home screen widgets.
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

		// Push notification endpoints.
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
		// Extract parameters with defaults and legacy support.
		$date_range = $request->get_param( 'date_range' ) ?: 'year';
		$month      = $request->get_param( 'month' );
		$year       = $request->get_param( 'year' ) ?: wp_date( 'Y' );

		// Handle include_jamah parameter (with legacy prayer_types fallback).
		$include_jamah = $request->get_param( 'include_jamah' );
		if ( null === $include_jamah ) {
			// Legacy fallback.
			$prayer_types  = $request->get_param( 'prayer_types' ) ?: 'both';
			$include_jamah = in_array( $prayer_types, array( 'jamaat', 'both' ), true );
		} else {
			// Convert string booleans.
			/** @phpstan-ignore-next-line */
			// @phpstan-ignore-line
			$include_jamah = filter_var( $include_jamah, FILTER_VALIDATE_BOOLEAN );
		}

		// Alarms array.
		$alarms = $request->get_param( 'alarms' ) ?: array();
		if ( ! is_array( $alarms ) ) {
			$alarms = array();
		}

		// Legacy reminder fallback.
		$legacy_reminder = $request->get_param( 'reminder' );
		if ( $legacy_reminder && empty( $alarms ) ) {
			$alarms = array( intval( $legacy_reminder ) );
		}

		// Other parameters.
		$jummah        = $request->get_param( 'jummah' ) ?: 'both';
		$sunrise_alarm = $request->get_param( 'sunrise_alarm' ) ?: '';
		/** @phpstan-ignore-next-line */
		// @phpstan-ignore-line
		$subscribe = filter_var( $request->get_param( 'subscribe' ), FILTER_VALIDATE_BOOLEAN );

		// Generate ICS content with new parameters.
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

		// For subscribe mode, return content directly.
		if ( $subscribe ) {
			header( 'Content-Type: text/calendar; charset=utf-8' );
			header( 'Content-Disposition: inline; filename="prayer-times.ics"' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
			echo $ics_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545 // ICS calendar format - content sanitized at creation, escaping would break format
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped output
			exit;
		}

		// For download mode, create temporary file.
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

		// Set download headers.
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $ics_content ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped output
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
		echo $ics_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545 // ICS calendar format - content sanitized at creation, escaping would break format

		// Clean up temporary file.
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

		// Get widget customization settings.
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

			// Add Jummah if it's Friday.
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

		// Get widget customization settings.
		$widget_bg_color   = get_field( 'widget_bg_color', 'option' ) ?: '#0D7377';
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
			$current_time = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Local timezone comparison required for prayer time calculations.
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
		// Verify nonce for security.
		if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Security check failed', 'mosque-timetable' ), array( 'status' => 403 ) );
		}

		$subscription    = $request->get_param( 'subscription' );
		$offsets         = $request->get_param( 'offsets' ) ?: array();
		$sunrise_warning = filter_var( $request->get_param( 'sunrise_warning' ), FILTER_VALIDATE_BOOLEAN );

		// Validate VAPID keys are configured.
		$vapid_public  = mt_has_acf() ? get_field( 'vapid_public_key', 'option' ) : get_option( 'vapid_public_key' );
		$vapid_private = mt_has_acf() ? get_field( 'vapid_private_key', 'option' ) : get_option( 'vapid_private_key' );

		if ( empty( $vapid_public ) || empty( $vapid_private ) ) {
			return new WP_Error( 'vapid_not_configured', 'Push notifications not properly configured', array( 'status' => 500 ) );
		}

		// Store subscription in database.
		$subscription_data = array(
			'endpoint'        => $subscription['endpoint'],
			'keys'            => $subscription['keys'],
			'offsets'         => $offsets,
			'sunrise_warning' => $sunrise_warning,
			'created_at'      => current_time( 'mysql' ),
			'user_agent'      => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), // Sanitize input.
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
		// Verify nonce for security.
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

		// Check required fields.
		if ( empty( $param['endpoint'] ) || ! is_string( $param['endpoint'] ) ) {
			return false;
		}

		if ( empty( $param['keys'] ) || ! is_array( $param['keys'] ) ) {
			return false;
		}

		if ( empty( $param['keys']['p256dh'] ) || empty( $param['keys']['auth'] ) ) {
			return false;
		}

		// Validate endpoint URL.
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
			return '<em> Mosque Prayer Timetable System v3.0 - Enhanced with <a href="https://claude.ai/code" target="_blank">Claude Code</a></em>';
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
                    <em>Prayer times powered by <a href="https://claude.ai/code" target="_blank" style="color: #0D7377;">Claude Code</a></em>
                  </div>';
		}
	}

	/**
	 * Initialize push notifications cron job
	 */
	public function init_push_notifications_cron() {
		if ( ! wp_next_scheduled( 'mt_send_push_notifications' ) ) {
			wp_schedule_event( time(), 'mt_every_five_minutes', 'mt_send_push_notifications' );
		}
	}

	/**
	 * Add custom cron interval: every five minutes for push notification checks.
	 */
	public function add_cron_intervals( $schedules ) {
		$schedules['mt_every_five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', 'mosque-timetable' ),
		);
		return $schedules;
	}

	/**
	 * Process and send push notifications
	 */
	public function process_push_notifications() {
		// Get VAPID keys.
		$vapid_public  = mt_has_acf() ? get_field( 'vapid_public_key', 'option' ) : get_option( 'vapid_public_key' );
		$vapid_private = mt_has_acf() ? get_field( 'vapid_private_key', 'option' ) : get_option( 'vapid_private_key' );

		if ( empty( $vapid_public ) || empty( $vapid_private ) ) {
			return; // Push notifications not configured.
		}

		// Get all subscriptions.
		$subscriptions = get_option( 'mt_push_subscriptions', array() );
		if ( empty( $subscriptions ) ) {
			return; // No subscriptions.
		}

		// Load web push library.
		if ( ! class_exists( 'Minishlink\WebPush\WebPush' ) ) {
			return; // Library not available.
		}

		$current_time = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Local timezone comparison required for prayer time calculations.
		$today_data   = $this->get_today_prayer_data();

		if ( ! $today_data ) {
			return; // No prayer data for today.
		}

		$prayer_times = array(
			'fajr'    => $today_data['fajr_start'],
			'sunrise' => $today_data['sunrise'],
			'zuhr'    => wp_date( 'w' ) === 5 ? null : $today_data['zuhr_start'], // Skip Zuhr on Friday.
			'asr'     => $today_data['asr_start'],
			'maghrib' => $today_data['maghrib_start'],
			'isha'    => $today_data['isha_start'],
		);

		// Add Jummah times on Friday.
		if ( wp_date( 'w' ) === 5 ) {
			if ( ! empty( $today_data['jummah_1'] ) ) {
				$prayer_times['jummah_1'] = $today_data['jummah_1'];
			}
			if ( ! empty( $today_data['jummah_2'] ) ) {
				$prayer_times['jummah_2'] = $today_data['jummah_2'];
			}
		}

		// Initialize WebPush.
		try {
			$web_push = new \Minishlink\WebPush\WebPush(
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

				// Check each prayer time for notifications.
				foreach ( $prayer_times as $prayer => $time_str ) {
					if ( empty( $time_str ) ) {
						continue;
					}

					$prayer_time = strtotime( $time_str );
					if ( ! $prayer_time ) {
						continue;
					}

					// Check each offset for this subscription.
					foreach ( $offsets as $offset ) {
						$notification_time = $prayer_time - ( $offset * 60 );

						// Check if we should send notification now (within 1 minute window).
						if ( abs( $current_time - $notification_time ) <= 30 ) {
							$this->send_prayer_notification( $web_push, $subscription_data, $prayer, $time_str, $offset );
						}
					}

					// Handle sunrise warning.
					if ( $sunrise_warning && 'sunrise' === $prayer ) {
						$warning_offset = mt_has_acf() ? get_field( 'sunrise_warning_offset', 'option' ) : get_option( 'sunrise_warning_offset', 30 );
						$warning_time   = $prayer_time - ( $warning_offset * 60 );

						if ( abs( $current_time - $warning_time ) <= 30 ) {
							$this->send_sunrise_warning( $web_push, $subscription_data, $time_str, $warning_offset );
						}
					}
				}
			}

			// Send all queued notifications.
			foreach ( $web_push->flush() as $report ) {
				$endpoint = $report->getRequest()->getUri()->__toString();
				if ( ! $report->isSuccess() ) {
					// Remove failed subscriptions (expired/invalid).
					$error = $report->getReason();
					if ( strpos( $error, '410' ) !== false || strpos( $error, '404' ) !== false ) {
						$this->remove_invalid_subscription( $endpoint );
					}
				}
			}
		} catch ( Exception $e ) {
			// Log error but continue.
			error_log( 'Push notification error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Send prayer notification
	 */
	private function send_prayer_notification( $web_push, $subscription_data, $prayer, $time_str, $offset ) {
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

		$web_push->queueNotification(
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
	private function send_sunrise_warning( $web_push, $subscription_data, $time_str, $offset ) {
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

		$web_push->queueNotification(
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

		echo '<link rel="manifest" href="' . esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/manifest.json' ) . '">'; // Escape output.
		echo '<meta name="theme-color" content="' . esc_attr( get_field( 'mt_btn_bg', 'option' ) ) . '">'; // Escape output.
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

		// Don't show on admin pages.
		if ( is_admin() ) {
			return;
		}

		// Only show on pages with mosque timetable content.
		global $post;
		if ( ! $post || ( ! has_shortcode( $post->post_content, 'mosque_timetable' ) &&
			! has_shortcode( $post->post_content, 'todays_prayers' ) &&
			! has_shortcode( $post->post_content, 'prayer_countdown' ) ) ) {
			return;
		}

		// Enhanced PWA install prompt.
		?>
			<style>
				.mosque-pwa-banner {
					position: fixed;
					bottom: 20px;
					left: 50%;
					transform: translateX(-50%);
					background: linear-gradient(135deg, #0D7377 0%, #1A3A5C 100%);
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
					color: #0D7377;
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
					// Check if app is already installed.
					if (window.matchMedia('(display-mode: standalone)').matches ||
						window.navigator.standalone === true) {
						return; // Don't show banner if already installed.
					}

					let deferredPrompt;

					// Listen for beforeinstallprompt event.
					window.addEventListener('beforeinstallprompt', function(e) {
						e.preventDefault();
						deferredPrompt = e;
						showPWABanner();
					});

					function showPWABanner() {
						const banner = document.createElement('div');
						banner.className = 'mosque-pwa-banner';
						banner.setAttribute('role', 'complementary');
						banner.setAttribute('aria-label', 'Install Prayer Times App');
						banner.innerHTML = `
						<div class="icon"></div>
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

						// Show banner with animation.
						setTimeout(() => banner.classList.add('show'), 100);

						// Handle install button.
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

						// Handle dismiss button.
						banner.querySelector('.dismiss').addEventListener('click', function() {
							banner.remove();
							// Don't show again for 24 hours.
							localStorage.setItem('pwa-banner-dismissed', Date.now());
						});

						// Auto-hide after 10 seconds.
						setTimeout(function() {
							if (banner.parentNode) {
								banner.remove();
							}
						}, 10000);
					}

					// Check if banner was recently dismissed.
					const dismissed = localStorage.getItem('pwa-banner-dismissed');
					if (dismissed && (Date.now() - parseInt(dismissed)) < 24 * 60 * 60 * 1000) {
						return; // Don't show if dismissed within 24 hours.
					}

					// Fallback: show banner after 3 seconds if no install prompt.
					setTimeout(function() {
						if (!deferredPrompt && !document.querySelector('.mosque-pwa-banner')) {
							// Create a simpler banner for browsers that don't support beforeinstallprompt.
							const fallbackBanner = document.createElement('div');
							fallbackBanner.className = 'mosque-pwa-banner';
							fallbackBanner.setAttribute('role', 'complementary');
							fallbackBanner.setAttribute('aria-label', 'Add to Home Screen');
							fallbackBanner.innerHTML = `
							<div class="icon"></div>
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

							// Handle today button.
							fallbackBanner.querySelector('.today').addEventListener('click', function() {
								window.location.href = '/today';
							});

							// Handle dismiss.
							fallbackBanner.querySelector('.dismiss').addEventListener('click', function() {
								fallbackBanner.remove();
								localStorage.setItem('pwa-banner-dismissed', Date.now());
							});

							// Auto-hide after 8 seconds.
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
	 * Output global sticky prayer bar
	 */
	public function output_global_sticky_prayer_bar() {
		// Check if sticky prayer bar is enabled.
		if ( ! get_option( 'enable_sticky_prayer_bar', 1 ) ) {
			return;
		}

		// Don't show on admin pages.
		if ( is_admin() ) {
			return;
		}

		// Get today's prayer data.
		$today_data = $this->get_today_prayer_data();
		if ( ! $today_data ) {
			return; // No data, no bar.
		}

		?>
			<!-- Global Sticky Prayer Bar -->
			<div class="mosque-prayer-bar mosque-prayer-bar-global" role="tablist" aria-label="<?php echo esc_attr( mt_apply_terminology( "Today's Prayer Times" ) ); ?>">
				<div class="mosque-prayer-bar-date">
					<span class="mosque-prayer-bar-gregorian"><?php echo esc_html( wp_date( 'l, F j, Y' ) ); ?></span>
				<?php if ( ! empty( $today_data['hijri_date'] ) ) : ?>
						<span class="mosque-prayer-bar-hijri"><?php echo esc_html( $today_data['hijri_date'] ); ?></span>
					<?php endif; ?>
				</div>
				<div class="mosque-prayer-bar-prayers">
				<?php
				$prayers = array(
					'Fajr'    => $today_data['fajr_start'],
					'Sunrise' => $today_data['sunrise'],
					'Zuhr'    => $today_data['zuhr_start'],
					'Asr'     => $today_data['asr_start'],
					'Maghrib' => $today_data['maghrib_start'],
					'Isha'    => $today_data['isha_start'],
				);

				// Replace Zuhr with Jummah on Friday.
				if ( '5' === wp_date( 'N' ) && ( $today_data['jummah_1'] || $today_data['jummah_2'] ) ) {
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
					$is_active = $is_next;
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
				?>
				</div>
			</div>
			<?php
	}

	/**
	 * Add structured data
	 */
	public function add_structured_data() {
		// Only add on pages that contain prayer timetable shortcodes.
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

		// Get mosque settings.
		$mosque_name    = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';
		$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';

		// Generate enhanced AI-readable structured data.
		$structured_data = array();

		// Add comprehensive prayer time data for AI.
		$ai_prayer_data = $this->generate_ai_readable_prayer_data();
		if ( $ai_prayer_data ) {
			$structured_data[] = $ai_prayer_data;
		}

		// Mosque Organization Schema.
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

		// Organization Schema for the mosque.
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

		// WebSite Schema with SearchAction.
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

		// Dataset Schema for prayer times data.
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

		// Today's Prayer Times Schema (if shortcode present).
		if ( has_shortcode( $content, 'todays_prayers' ) || has_shortcode( $content, 'mosque_timetable' ) ) {
			$today_data = $this->get_today_prayer_data();

			if ( $today_data ) {
				$prayer_events   = $this->generate_prayer_events_schema( $today_data, $mosque_name, $mosque_address );
				$structured_data = array_merge( $structured_data, $prayer_events );
			}
		}

		// FAQ Schema for common questions.
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

		// Website Schema.
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

		// Output structured data.
		foreach ( $structured_data as $data ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}

		// Add Open Graph tags.
		echo '<meta property="og:title" content="' . esc_attr( $mosque_name . ' - Prayer Times' ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( 'Daily prayer times and Islamic services at ' . $mosque_name ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:url" content="' . esc_attr( get_permalink() ) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";

		// Add Twitter Card tags.
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

		// Create comprehensive AI-readable schema.
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
					'unitCode'    => 'H14', // Time format code.
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

		// Add structured prayer time entries.
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
		// Year and month specific pages.
		add_rewrite_rule(
			'prayer-times/([0-9]{4})/([^/]+)/?$',
			'index.php?mosque_year=$matches[1]&mosque_month=$matches[2]',
			'top'
		);
		// Year archive pages.
		add_rewrite_rule(
			'prayer-times/([0-9]{4})/?$',
			'index.php?mosque_year_archive=$matches[1]',
			'top'
		);
		// Main prayer times page (dynamic current month).
		add_rewrite_rule(
			'prayer-times/?$',
			'index.php?mosque_prayer_times=1',
			'top'
		);
		// Prayer times archive.
		add_rewrite_rule(
			'prayer-times/archive/?$',
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
		add_rewrite_tag( '%mosque_prayer_times%', '([^&]+)' );
	}

	/**
	 * Handle virtual pages
	 */
	public function handle_virtual_pages() {
		global $wp_query;

		// Handle calendar.ics requests.
		if ( get_query_var( 'mosque_calendar' ) === 'ics' ) {
			$this->serve_ics_calendar();
			exit;
		}

		// Handle prayer times sitemap.
		if ( get_query_var( 'mosque_sitemap' ) === 'xml' ) {
			$this->serve_prayer_times_sitemap();
			exit;
		}

		// Handle llms.txt requests.
		if ( get_query_var( 'llms_txt' ) === '1' ) {
			$this->serve_llms_txt();
			exit;
		}

		// Handle /today page requests.
		if ( get_query_var( 'mosque_today' ) === '1' ) {
			$this->serve_today_page();
			exit;
		}

		// Handle main prayer times page (dynamic current month).
		if ( get_query_var( 'mosque_prayer_times' ) === '1' ) {
			$this->serve_dynamic_prayer_times_page();
			exit;
		}

		// Handle prayer times archive requests.
		if ( get_query_var( 'mosque_archive' ) === '1' ) {
			$this->serve_prayer_times_archive();
			exit;
		}

		// Handle year archive requests.
		$year_archive = get_query_var( 'mosque_year_archive' );
		if ( $year_archive ) {
			$this->serve_year_archive_page( (int) $year_archive );
			exit;
		}

		// Handle month-specific prayer times pages (e.g., /prayer-times/2024/10/).
		$year  = get_query_var( 'mosque_year' );
		$month = get_query_var( 'mosque_month' );
		if ( $year && $month ) {
			$this->serve_month_timetable_page( (int) $year, $month );
			exit;
		}
	}

	/**
	 * Serve ICS calendar file
	 */
	private function serve_ics_calendar() {
		// Set proper headers.
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="mosque-prayer-times.ics"' );
		header( 'Cache-Control: no-cache, must-revalidate' );

		// Get mosque details.
		$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
		$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';

		// Start ICS content.
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

		// Get all available prayer data.
		$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
		$available_months = get_field( 'available_months', 'option' ) ?: array();

		// If no specific months are set, include all 12 months.
		if ( empty( $available_months ) ) {
			$available_months = range( 1, 12 );
		}

		foreach ( $available_months as $month ) {
			$prayer_data = $this->get_month_prayer_data( $default_year, $month );

			if ( $prayer_data && ! empty( $prayer_data['days'] ) ) {
				foreach ( $prayer_data['days'] as $day ) {
					$date      = new DateTime( $day['date_full'] );
					$is_friday = $date->format( 'N' ) === 5;

					// Create events for each prayer time.
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
		// Set proper headers.
		header( 'Content-Type: application/xml; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' ); // Cache for 1 hour.

		$site_url         = get_site_url();
		$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
		$available_months = get_field( 'available_months', 'option' ) ?: range( 1, 12 );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		// Add entry for each available month.
		foreach ( $available_months as $month ) {
			$month_num  = intval( $month );
			$month_name = wp_date( 'F', mktime( 0, 0, 0, $month_num, 1 ) );
			$url        = $site_url . '/prayer-times/' . $default_year . '/' . $month_num;
			$lastmod    = wp_date( 'Y-m-d\TH:i:s+00:00' ); // Use current time as modification date.

			echo "\t<url>\n";
			echo "\t\t<loc>" . esc_url( $url ) . "</loc>\n";
			echo "\t\t<lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
			echo "\t\t<changefreq>weekly</changefreq>\n";
			echo "\t\t<priority>0.8</priority>\n";
			echo "\t</url>\n";
		}

		// Add main prayer times archive page.
		echo "\t<url>\n";
		echo "\t\t<loc>" . esc_url( $site_url . '/prayer-times/' ) . "</loc>\n";
		echo "\t\t<lastmod>" . esc_html( wp_date( 'Y-m-d\TH:i:s+00:00' ) ) . "</lastmod>\n";
		echo "\t\t<changefreq>daily</changefreq>\n";
		echo "\t\t<priority>1.0</priority>\n";
		echo "\t</url>\n";

		// Add available year archive pages.
		$available_years = $this->get_available_years();
		foreach ( $available_years as $year ) {
			echo "\t<url>\n";
			echo "\t\t<loc>" . esc_url( $site_url . '/prayer-times/' . $year . '/' ) . "</loc>\n";
			echo "\t\t<lastmod>" . esc_html( wp_date( 'Y-m-d\TH:i:s+00:00' ) ) . "</lastmod>\n";
			echo "\t\t<changefreq>weekly</changefreq>\n";
			echo "\t\t<priority>0.9</priority>\n";
			echo "\t</url>\n";
		}

		// Add today page.
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
		// Set proper headers.
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=86400' ); // Cache for 24 hours.

		$site_url       = get_site_url();
		$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
		$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';
		$admin_email    = get_option( 'admin_email' );

		echo '# LLMs.txt - Machine-readable metadata for ' . esc_html( $mosque_name ) . "\n\n"; // Escape output.
		echo "## Source of Truth\n";
		echo 'This file provides metadata about the prayer timetable system for ' . esc_html( $mosque_name ) . ".\n"; // Escape output.
		echo "The data is maintained by mosque administrators and updated regularly.\n\n";

		echo "## Organization\n";
		echo 'Name: ' . esc_html( $mosque_name ) . "\n"; // Escape output.
		if ( $mosque_address ) {
			echo 'Address: ' . esc_html( $mosque_address ) . "\n"; // Escape output.
		}
		echo 'Website: ' . esc_url( $site_url ) . "\n\n"; // Escape output.

		echo "## API Endpoints\n";
		echo 'REST API Base: ' . esc_url( $site_url ) . "/wp-json/mosque/v1/\n"; // Escape output.
		echo 'Prayer Times ICS: ' . esc_url( $site_url ) . "/prayer-times/calendar.ics\n"; // Escape output.
		echo 'Prayer Times Sitemap: ' . esc_url( $site_url ) . "/prayer-times-sitemap.xml\n\n"; // Escape output.

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
		echo 'Technical inquiries: ' . esc_html( $admin_email ) . "\n"; // Escape output.
		echo 'Generated by: Mosque Timetable Plugin v' . esc_html( MOSQUE_TIMETABLE_VERSION ) . "\n"; // Escape output.
		echo 'Last updated: ' . esc_html( wp_date( 'Y-m-d H:i:s T' ) ) . "\n"; // Escape output.
	}

	/**
	 * Serve dedicated /today page
	 */
	private function serve_today_page() {
		// Set proper headers.
		header( 'Content-Type: text/html; charset=utf-8' );

		// Get today's prayer data.
		$today = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );
		$month = (int) $today->format( 'n' );
		$year  = (int) $today->format( 'Y' );
		$day   = (int) $today->format( 'j' );

		$prayer_data   = $this->get_month_prayer_data( $year, $month );
		$today_prayers = isset( $prayer_data[ $day ] ) ? $prayer_data[ $day ] : null;

		$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
		$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';

		// Get terminology overrides.
		$terminology = $this->get_terminology_overrides();

		// Apply terminology overrides to prayer names.
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

		// Get Hijri date.
		$hijri_date = $this->get_hijri_date( $today );

		// Calculate next prayer.
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

			// If no prayer found today, get tomorrow's Fajr.
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
				<link rel="manifest" href="<?php echo esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/manifest.json' ); ?>"> <!-- Escape output -->
				<meta name="theme-color" content="#0D7377">
				<meta name="apple-mobile-web-app-capable" content="yes">
				<meta name="apple-mobile-web-app-status-bar-style" content="default">
				<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $today_label ); ?>">
				<link rel="preconnect" href="https://fonts.googleapis.com">
				<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
				<link href="https://fonts.googleapis.com/css2?family=El+Messiri:wght@400;600;700&family=DM+Sans:wght@400;500;600&family=Space+Mono&display=swap" rel="stylesheet">

				<style>
					:root {
						--mosque-primary:       #0D7377;
						--mosque-primary-dark:  #0a5d61;
						--mosque-secondary:     #C5A55A;
						--mosque-accent:        #1A3A5C;
						--mosque-gradient:      linear-gradient(135deg, #0D7377 0%, #1A3A5C 100%);
						--text-primary:         #1A2332;
						--text-secondary:       #5A6978;
						--bg-card:              #ffffff;
						--bg-cream:             #F5F1EB;
						--border-color:         #d8d2c8;
						--shadow:               0 4px 20px rgba(13, 115, 119, 0.15);
					}

					* {
						margin: 0;
						padding: 0;
						box-sizing: border-box;
					}

					body {
						font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
						background: rgba(13, 115, 119, 0.1);
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
						box-shadow: 0 4px 15px rgba(13, 115, 119, 0.3);
					}

					.btn-primary:hover {
						transform: translateY(-2px);
						box-shadow: 0 6px 20px rgba(13, 115, 119, 0.4);
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
					<div class="mosque-icon"></div>
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

							// Handle Friday/Jummah display.
							if ( $today->format( 'N' ) === 5 ) { // Friday.
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
										<div class="jamaah">Jamaah: <?php echo esc_html( $times['jamaah'] ); ?></div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div class="actions">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary">
							 Full Timetable
						</a>
						<a href="<?php echo esc_url( home_url( '/prayer-times/calendar.ics' ) ); ?>" class="btn btn-secondary">
							 Subscribe
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

						// Update countdown immediately and then every second.
						updateCountdown();
						setInterval(updateCountdown, 1000);

						// Register service worker for PWA functionality.
						if ('serviceWorker' in navigator) {
							navigator.serviceWorker.register('<?php echo esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/sw.js' ); ?>') // Escape output.
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
		// Set proper headers.
		header( 'Content-Type: text/html; charset=utf-8' );

		$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
		$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';
		$terminology    = $this->get_terminology_overrides();

		// Get all available years.
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
				<link rel="manifest" href="<?php echo esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/manifest.json' ); ?>"> <!-- Escape output -->
				<meta name="theme-color" content="#0D7377">

				<style>
					:root {
						--mosque-primary: #0D7377;
						--mosque-gradient: linear-gradient(135deg, #0D7377 0%, #1A3A5C 100%);
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
						box-shadow: 0 4px 12px rgba(13, 115, 119, 0.4);
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
						<h2>Current Year - <?php echo esc_html( (string) $current_year ); ?></h2>
						<p>View this year's complete prayer timetable with monthly breakdowns.</p>
						<a href="<?php echo esc_url( home_url( "/prayer-times/{$current_year}/" ) ); ?>" class="btn">
							 View <?php echo esc_html( (string) $current_year ); ?> Timetable
						</a>
					</div>

				<?php if ( ! empty( $available_years ) ) : ?>
						<div class="archive-grid">
							<?php foreach ( $available_years as $year ) : ?>
								<div class="year-card <?php echo ( $year === $current_year ) ? 'current' : ''; ?>">
									<h3><?php echo esc_html( (string) $year ); ?></h3>
									<p class="description">
										<?php echo ( $year === $current_year ) ? 'Current year - Active timetable' : 'Historical prayer times'; ?>
									</p>
									<a href="<?php echo esc_url( home_url( "/prayer-times/{$year}/" ) ); ?>" class="btn">
										Browse <?php echo esc_html( (string) $year ); ?>
									</a>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div class="navigation">
						<a href="<?php echo esc_url( home_url( '/today' ) ); ?>" class="btn">
							 Today's Prayers
						</a>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-secondary">
							 Home
						</a>
					</div>
				</div>
			</body>

			</html>
			<?php
	}

	/**
	 * Serve dynamic prayer times page (current month with controls)
	 */
	private function serve_dynamic_prayer_times_page() {
		$current_year  = (int) wp_date( 'Y' );
		$current_month = (int) wp_date( 'n' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display navigation, no data modification.
		$display_year  = isset( $_GET['year'] ) ? (int) $_GET['year'] : $current_year;
		$display_month = isset( $_GET['month'] ) ? (int) $_GET['month'] : $current_month;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $display_year < 2020 || $display_year > $current_year + 5 ) {
			$display_year = $current_year;
		}
		if ( $display_month < 1 || $display_month > 12 ) {
			$display_month = $current_month;
		}

		$prayer_data     = $this->get_month_prayer_data( $display_year, $display_month );
		$mosque_name     = mt_get_option( 'mosque_name', get_bloginfo( 'name' ) );
		$mosque_address  = mt_get_option( 'mosque_address', '' );
		$month_names     = array(
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
		$month_name      = $month_names[ $display_month ];
		$available_years = $this->get_available_years();
		if ( empty( $available_years ) ) {
			$available_years = range( $current_year - 1, $current_year + 2 );
		}

		wp_enqueue_style( 'mosque-timetable-css', MOSQUE_TIMETABLE_ASSETS_URL . 'mosque-timetable.css', array(), MOSQUE_TIMETABLE_VERSION );
		header( 'Content-Type: text/html; charset=utf-8' );
		?>
			<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title><?php echo esc_html( $mosque_name ); ?> - Prayer Times</title>
			<?php wp_head(); ?>
				<style>
					:root{--mosque-primary:#0D7377;--mosque-gradient:linear-gradient(135deg,#0D7377 0%,#1A3A5C 100%);--text-primary:#2c3e50;--text-secondary:#5c636a;--border-color:#ddd}*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;line-height:1.6;color:var(--text-primary);background:#f8f9fa}.header{background:var(--mosque-gradient);color:white;padding:2rem 1rem;text-align:center;box-shadow:0 4px 12px rgba(102,126,234,0.2)}.header h1{font-size:2.5rem;margin-bottom:0.5rem}.header p{font-size:1.2rem;opacity:0.95}.container{max-width:1200px;margin:2rem auto;padding:0 1rem}.controls{background:white;padding:1.5rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);margin-bottom:2rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between}.controls-left{display:flex;gap:1rem;align-items:center;flex-wrap:wrap}.controls-left label{font-weight:600;color:var(--text-primary)}.controls-left select{padding:0.5rem 1rem;border:2px solid var(--border-color);border-radius:8px;font-size:1rem;cursor:pointer;transition:border-color 0.2s}.controls-left select:focus{outline:none;border-color:var(--mosque-primary)}.controls-right{display:flex;gap:0.75rem;flex-wrap:wrap}.btn{display:inline-flex;align-items:center;gap:0.5rem;padding:0.75rem 1.25rem;border:none;border-radius:8px;font-size:0.95rem;font-weight:600;text-decoration:none;cursor:pointer;transition:all 0.2s}.btn-primary{background:var(--mosque-gradient);color:white;box-shadow:0 2px 8px rgba(102,126,234,0.3)}.btn-primary:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4)}.btn-secondary{background:white;color:var(--mosque-primary);border:2px solid var(--mosque-primary)}.btn-secondary:hover{background:var(--mosque-primary);color:white}.timetable-wrapper{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);overflow:hidden}.timetable-header{background:var(--mosque-gradient);color:white;padding:1.5rem;text-align:center}.timetable-header h2{font-size:1.8rem;margin:0}.footer{text-align:center;padding:2rem 1rem;color:var(--text-secondary)}.footer a{color:var(--mosque-primary);text-decoration:none;font-weight:600}.footer a:hover{text-decoration:underline}@media (max-width:768px){.header h1{font-size:1.8rem}.controls{flex-direction:column;align-items:stretch}.controls-left,.controls-right{width:100%;justify-content:center}.controls-right{flex-direction:column}.btn{width:100%;justify-content:center}}
				</style>
			</head>
			<body>
				<main id="main-content">
				<div class="header"><h1><?php echo esc_html( $mosque_name ); ?></h1><p>Prayer Times</p></div>
				<div class="container">
					<div class="controls">
						<div class="controls-left">
							<label for="month-select">Month:</label>
							<select id="month-select">
							<?php foreach ( $month_names as $m => $name ) : ?>
									<option value="<?php echo esc_attr( (string) $m ); ?>" <?php selected( $m, $display_month ); ?>><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
							<label for="year-select">Year:</label>
							<select id="year-select">
							<?php foreach ( $available_years as $y ) : ?>
									<option value="<?php echo esc_attr( (string) $y ); ?>" <?php selected( $y, $display_year ); ?>><?php echo esc_html( (string) $y ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="controls-right">
							<a href="#" id="download-btn" class="btn btn-primary"> Download Timetable</a>
							<a href="<?php echo esc_url( home_url( '/prayer-times/calendar.ics' ) ); ?>" class="btn btn-secondary"> Subscribe to Calendar</a>
							<a href="#" id="add-to-calendar-btn" class="btn btn-secondary"> Add to Calendar</a>
						</div>
					</div>
					<div class="timetable-wrapper">
						<div class="timetable-header"><h2><?php echo esc_html( $month_name . ' ' . $display_year ); ?></h2></div>
						<div id="timetable-content">
						<?php
						if ( ! empty( $prayer_data ) && ! empty( $prayer_data['days'] ) ) {
							echo do_shortcode( '[mosque_timetable month="' . $display_month . '" year="' . $display_year . '" show_controls="false"]' );
						} else {
							echo '<div style="padding:3rem;text-align:center;color:#6c757d"><p style="font-size:1.2rem;margin-bottom:1rem">No prayer times available for ' . esc_html( $month_name . ' ' . $display_year ) . '</p><p>Please check back later or contact the mosque administration.</p></div>';
						}
						?>
						</div>
					</div>
					<div class="footer">
						<p><a href="<?php echo esc_url( home_url( '/prayer-times/archive/' ) ); ?>">View Archive</a> | <a href="<?php echo esc_url( home_url( '/today' ) ); ?>">Today's Prayers</a> | <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></p>
					</div>
				</div>
				</main>
				<script>
					const monthSelect=document.getElementById('month-select'),yearSelect=document.getElementById('year-select');function updateTimetable(){const month=monthSelect.value,year=yearSelect.value;window.location.href='<?php echo esc_js( home_url( '/prayer-times/' ) ); ?>?month='+month+'&year='+year}monthSelect.addEventListener('change',updateTimetable);yearSelect.addEventListener('change',updateTimetable);document.getElementById('download-btn').addEventListener('click',function(e){e.preventDefault();const month=monthSelect.value,year=yearSelect.value;window.location.href='<?php echo esc_js( rest_url( 'mosque/v1/export-pdf' ) ); ?>?month='+month+'&year='+year});document.getElementById('add-to-calendar-btn').addEventListener('click',function(e){e.preventDefault();const month=monthSelect.value,year=yearSelect.value;window.location.href='<?php echo esc_js( rest_url( 'mosque/v1/export-ics' ) ); ?>?month='+month+'&year='+year});
				</script>
				<?php wp_footer(); ?>
			</body>
			</html>
			<?php
	}

	/**
	 * Serve year archive page
	 */
	private function serve_year_archive_page( $year ) {
		// Set proper headers.
		header( 'Content-Type: text/html; charset=utf-8' );

		$mosque_name    = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
		$mosque_address = get_field( 'mosque_address', 'option' ) ?: '';
		$terminology    = $this->get_terminology_overrides();
		$current_year   = get_option( 'default_year', wp_date( 'Y' ) );

		// Get months with data for this year.
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
				<link rel="manifest" href="<?php echo esc_url( MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/manifest.json' ); ?>"> <!-- Escape output -->
				<meta name="theme-color" content="#0D7377">

				<style>
					:root {
						--mosque-primary: #0D7377;
						--mosque-gradient: linear-gradient(135deg, #0D7377 0%, #1A3A5C 100%);
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
						box-shadow: 0 4px 12px rgba(13, 115, 119, 0.4);
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
					<p><?php echo esc_html( (string) $year ); ?> Prayer Times
					<?php if ( $year === $current_year ) : ?>
							<span class="current</span>
						<?php endif; ?>
					</p>
				</div>

				<div class=" container">
								<div class="year-info">
									<h2><?php echo esc_html( (string) $year ); ?> Prayer Timetable</h2>
									<p>Browse monthly prayer times for <?php echo esc_html( (string) $year ); ?>.
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
									$has_data   = in_array( $month_num, $months_with_data, true );
									$month_slug = strtolower( $month_name );
									?>
										<div class="month-card <?php echo $has_data ? 'available' : 'unavailable'; ?>">
											<h3><?php echo esc_html( $month_name ); ?></h3>
											<p class="status">
											<?php echo $has_data ? ' Available' : ' No data'; ?>
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
										 All Years
									</a>
									<a href="<?php echo esc_url( home_url( '/today' ) ); ?>" class="btn">
										 Today's Prayers
									</a>
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-secondary">
										 Home
									</a>
								</div>
				</div>
			</body>

			</html>
			<?php
	}

	/**
	 * Serve month-specific timetable page with UI controls
	 * URL: /prayer-times/2024/october/
	 */
	private function serve_month_timetable_page( int $year, string $month_slug ) {
		// Convert month slug to number.
		$month_names = array(
			'january'   => 1,
			'february'  => 2,
			'march'     => 3,
			'april'     => 4,
			'may'       => 5,
			'june'      => 6,
			'july'      => 7,
			'august'    => 8,
			'september' => 9,
			'october'   => 10,
			'november'  => 11,
			'december'  => 12,
		);

		$month = $month_names[ strtolower( $month_slug ) ] ?? (int) $month_slug;

		if ( ! $month || $month < 1 || $month > 12 ) {
			// Invalid month, redirect to year archive.
			wp_safe_redirect( home_url( "/prayer-times/{$year}/" ) );
			exit;
		}

		// Get prayer data for the month.
		$prayer_data = $this->get_month_prayer_data( $year, $month );

		if ( empty( $prayer_data ) || empty( $prayer_data['days'] ) ) {
			// No data, redirect to year archive with message.
			wp_safe_redirect( home_url( "/prayer-times/{$year}/" ) );
			exit;
		}

		// Get mosque details.
		$mosque_name = get_field( 'mosque_name', 'option' ) ?: get_bloginfo( 'name' );
		$month_name  = $prayer_data['month'];

		// Enqueue modal assets.
		wp_enqueue_style( 'mosque-timetable-css', MOSQUE_TIMETABLE_ASSETS_URL . 'mosque-timetable.css', array(), MOSQUE_TIMETABLE_VERSION );
		wp_enqueue_style( 'mt-modal-css', MOSQUE_TIMETABLE_ASSETS_URL . 'mt-modal.css', array(), MOSQUE_TIMETABLE_VERSION );
		wp_enqueue_script( 'mt-modal-js', MOSQUE_TIMETABLE_ASSETS_URL . 'mt-modal.js', array(), MOSQUE_TIMETABLE_VERSION, true );
		wp_localize_script(
			'mt-modal-js',
			'mosqueTimetableModal',
			array(
				'restUrl'      => rest_url( 'mosque/v1/' ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'currentMonth' => $month,
				'currentYear'  => $year,
				'siteUrl'      => get_site_url(),
				'strings'      => array(),
			)
		);

		// Get all available years for dropdown.
		$available_years = $this->get_available_years();

		// Output HTML.
		?>
			<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title><?php echo esc_html( $month_name . ' ' . $year . ' - ' . $mosque_name ); ?> | Prayer Timetable</title>
			<?php wp_head(); ?>
				<style>
					:root {
						--mosque-primary: #0D7377;
						--mosque-gradient: linear-gradient(135deg, #0D7377 0%, #1A3A5C 100%);
						--text-primary: #2c3e50;
						--text-secondary: #6c757d;
						--border-color: #ddd;
					}

					* {
						margin: 0;
						padding: 0;
						box-sizing: border-box;
					}

					body {
						font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
						line-height: 1.6;
						color: var(--text-primary);
						background: #f8f9fa;
					}

					.header {
						background: var(--mosque-gradient);
						color: white;
						padding: 2rem 1rem;
						text-align: center;
						box-shadow: 0 4px 12px rgba(13, 115, 119, 0.2);
					}

					.header h1 {
						font-size: 2.5rem;
						margin-bottom: 0.5rem;
					}

					.header p {
						font-size: 1.2rem;
						opacity: 0.95;
					}

					.container {
						max-width: 1200px;
						margin: 2rem auto;
						padding: 2rem;
						background: white;
						border-radius: 12px;
						box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
					}

					.controls {
						display: flex;
						flex-wrap: wrap;
						gap: 1rem;
						margin-bottom: 2rem;
						padding-bottom: 1.5rem;
						border-bottom: 2px solid var(--border-color);
						align-items: center;
						justify-content: space-between;
					}

					.controls-left {
						display: flex;
						gap: 1rem;
						flex-wrap: wrap;
					}

					.controls-right {
						display: flex;
						gap: 0.5rem;
						flex-wrap: wrap;
					}

					.btn {
						display: inline-flex;
						align-items: center;
						gap: 0.5rem;
						padding: 10px 20px;
						background: var(--mosque-gradient);
						color: white;
						text-decoration: none;
						border: none;
						border-radius: 8px;
						font-weight: 600;
						cursor: pointer;
						transition: all 0.3s ease;
						font-size: 14px;
					}

					.btn:hover {
						transform: translateY(-1px);
						box-shadow: 0 4px 12px rgba(13, 115, 119, 0.4);
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

					select {
						padding: 10px 15px;
						border: 2px solid var(--border-color);
						border-radius: 8px;
						background: white;
						color: var(--text-primary);
						font-size: 14px;
						font-weight: 600;
						cursor: pointer;
						transition: all 0.2s ease;
					}

					select:hover {
						border-color: var(--mosque-primary);
					}

					select:focus {
						outline: none;
						border-color: var(--mosque-primary);
						box-shadow: 0 0 0 3px rgba(13, 115, 119, 0.1);
					}

					table {
						width: 100%;
						border-collapse: collapse;
						margin-top: 1rem;
					}

					table thead {
						background: var(--mosque-gradient);
						color: white;
					}

					table th,
					table td {
						padding: 12px;
						text-align: left;
						border-bottom: 1px solid var(--border-color);
					}

					table th {
						font-weight: 600;
						font-size: 0.9rem;
						text-transform: uppercase;
						letter-spacing: 0.5px;
					}

					table tbody tr:hover {
						background: #f8f9fa;
					}

					table tbody tr.friday {
						background: #fff3cd;
					}

					table tbody tr.friday:hover {
						background: #ffe69c;
					}

					.navigation {
						text-align: center;
						margin-top: 2rem;
						padding-top: 2rem;
						border-top: 1px solid var(--border-color);
					}

					@media print {
						.controls, .navigation {
							display: none;
						}

						body {
							background: white;
						}

						.container {
							box-shadow: none;
							padding: 0;
						}
					}

					@media (max-width: 768px) {
						.header h1 {
							font-size: 2rem;
						}

						.controls {
							flex-direction: column;
							align-items: stretch;
						}

						.controls-left,
						.controls-right {
							width: 100%;
							justify-content: center;
						}

						table {
							font-size: 0.85rem;
						}

						table th,
						table td {
							padding: 8px 6px;
						}
					}
				</style>
			</head>
			<body>
				<div class="header">
					<h1><?php echo esc_html( $mosque_name ); ?></h1>
					<p><?php echo esc_html( $month_name . ' ' . $year ); ?> Prayer Timetable</p>
				</div>

				<div class="container">
					<!-- Controls Section -->
					<div class="controls">
						<div class="controls-left">
							<!-- Dropdown 1: Month -->
							<select id="month-selector" onchange="navigateToMonth(this.value, <?php echo esc_js( $year ); ?>)">
							<?php
							foreach ( $month_names as $m_slug => $m_num ) :
								$m_name     = ucfirst( $m_slug );
								$is_current = ( $m_num === $month );
								?>
									<option value="<?php echo esc_attr( $m_slug ); ?>" <?php selected( $is_current ); ?>>
									<?php echo esc_html( $m_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>

							<!-- Dropdown 2: Year -->
							<select id="year-selector" onchange="navigateToYear(this.value, '<?php echo esc_js( $month_slug ); ?>')">
								<?php foreach ( $available_years as $yr ) : ?>
									<option value="<?php echo esc_attr( $yr ); ?>" <?php selected( $yr === $year ); ?>>
										<?php echo esc_html( $yr ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="controls-right">
							<!-- Button 1: Print Timetable -->
							<button class="btn btn-secondary" onclick="window.print()">
								 Print
							</button>

							<!-- Button 2: Subscribe to Calendar -->
							<a href="<?php echo esc_url( mt_get_subscribe_url() ); ?>" class="btn btn-secondary">
								 Subscribe
							</a>

							<!-- Button 3: Add to Calendar (opens export modal) -->
							<button class="btn mosque-export-btn">
								 Export
							</button>
						</div>
					</div>

					<!-- Timetable -->
					<table>
						<thead>
							<tr>
								<th>Date</th>
								<th>Day</th>
								<th>Hijri</th>
								<th>Fajr</th>
								<th>Sunrise</th>
								<th>Zuhr</th>
								<th>Asr</th>
								<th>Maghrib</th>
								<th>Isha</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $prayer_data['days'] as $day ) : ?>
								<?php $is_friday = ( strtolower( $day['day_name'] ) === 'friday' ); ?>
								<tr <?php echo $is_friday ? 'class="friday"' : ''; ?>>
									<td><?php echo esc_html( $day['day_number'] ); ?></td>
									<td><?php echo esc_html( $day['day_name'] ); ?></td>
									<td><?php echo esc_html( $day['hijri_date'] ?? '-' ); ?></td>
									<td>
										<?php echo esc_html( $day['fajr_start'] ?: '-' ); ?>
										<?php if ( ! empty( $day['fajr_jamaat'] ) ) : ?>
											<br><small>(<?php echo esc_html( $day['fajr_jamaat'] ); ?>)</small>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $day['sunrise'] ?: '-' ); ?></td>
									<td>
										<?php if ( $is_friday && ! empty( $day['jummah_1'] ) ) : ?>
											<strong>Jummah:</strong><br>
											<?php echo esc_html( $day['jummah_1'] ); ?>
											<?php if ( ! empty( $day['jummah_2'] ) ) : ?>
												<br><?php echo esc_html( $day['jummah_2'] ); ?>
											<?php endif; ?>
										<?php else : ?>
											<?php echo esc_html( $day['zuhr_start'] ?: '-' ); ?>
											<?php if ( ! empty( $day['zuhr_jamaat'] ) ) : ?>
												<br><small>(<?php echo esc_html( $day['zuhr_jamaat'] ); ?>)</small>
											<?php endif; ?>
										<?php endif; ?>
									</td>
									<td>
										<?php echo esc_html( $day['asr_start'] ?: '-' ); ?>
										<?php if ( ! empty( $day['asr_jamaat'] ) ) : ?>
											<br><small>(<?php echo esc_html( $day['asr_jamaat'] ); ?>)</small>
										<?php endif; ?>
									</td>
									<td>
										<?php echo esc_html( $day['maghrib_start'] ?: '-' ); ?>
										<?php if ( ! empty( $day['maghrib_jamaat'] ) ) : ?>
											<br><small>(<?php echo esc_html( $day['maghrib_jamaat'] ); ?>)</small>
										<?php endif; ?>
									</td>
									<td>
										<?php echo esc_html( $day['isha_start'] ?: '-' ); ?>
										<?php if ( ! empty( $day['isha_jamaat'] ) ) : ?>
											<br><small>(<?php echo esc_html( $day['isha_jamaat'] ); ?>)</small>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div class="navigation">
						<a href="<?php echo esc_url( home_url( "/prayer-times/{$year}/" ) ); ?>" class="btn btn-secondary">
							 View All Months
						</a>
						<a href="<?php echo esc_url( home_url( '/today' ) ); ?>" class="btn">
							 Today's Prayers
						</a>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-secondary">
							 Home
						</a>
					</div>
				</div>

				<script>
					function navigateToMonth(month, year) {
						window.location.href = '<?php echo esc_url( home_url( '/prayer-times/' ) ); ?>' + year + '/' + month + '/';
					}

					function navigateToYear(year, month) {
						window.location.href = '<?php echo esc_url( home_url( '/prayer-times/' ) ); ?>' + year + '/' + month + '/';
					}
				</script>

				<?php wp_footer(); ?>
			</body>
			</html>
			<?php
	}

	/**
	 * Get available years with prayer data
	 */
	private function get_available_years() {
		// This would normally query the database for years with data.
		// For now, return a range including current year and recent years.
		$current_year = wp_date( 'Y' );
		$years        = array();

		// Add current year and previous 2 years, next 1 year.
		for ( $i = -2; $i <= 1; $i++ ) {
			$years[] = (int) $current_year + $i;
		}

		// Sort in descending order (newest first).
		rsort( $years );

		return $years;
	}

	/**
	 * Get months with data for a specific year
	 */
	private function get_months_with_data( int $year ): array {
		$months_with_data = array();

		// Check each month for data.
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
		// Use ACF field first, then option, then current year.
		$year = get_field( 'default_year', 'option' );
		if ( ! $year ) {
			$year = get_option( 'default_year', wp_date( 'Y' ) );
		}

		// Validate year is reasonable.
		$current_year = (int) wp_date( 'Y' );
		$year         = (int) $year;

		if ( $year < ( $current_year - 5 ) || $year > ( $current_year + 5 ) ) {
			// Reset to current year if unreasonable.
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

		// Validate year is reasonable (within 5 years of current).
		if ( $year >= ( $current_year - 5 ) && $year <= ( $current_year + 5 ) ) {
			update_option( 'default_year', $year );

			// Update ACF field if available.
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

		// If we're in a new year and still using old default, consider updating.
		if ( $actual_current > $current_default ) {
			// Check if new year has any data.
			$new_year_data = $this->get_months_with_data( $actual_current );

			// If new year has data for current month or later, auto-advance.
			$current_month          = (int) wp_date( 'n' );
			$has_current_month_data = in_array( $current_month, $new_year_data, true );

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
		return $hijri; // This already returns a formatted string.
	}

	/**
	 * Add entries to robots.txt
	 */
	public function add_robots_txt_entries( $output, $public ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.publicFound -- Matches WordPress robots_txt filter signature.
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
			return; // Skip invalid times.
		}

		$event_id  = md5( $event_date . $prayer_name . $prayer_time . $mosque_name );
		$timestamp = gmdate( 'Ymd\THis\Z' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format constants
		echo "BEGIN:VEVENT\r\n";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format
		echo 'UID:' . $event_id . '@' . wp_parse_url( get_site_url(), PHP_URL_HOST ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ICS calendar format requires unescaped content per RFC 5545
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

		// Check if this is a service worker request.
		if ( strpos( $request_uri, '/wp-content/plugins/mosque-timetable/assets/sw.js' ) !== false ) {
			$this->serve_dynamic_service_worker();
			exit;
		}
	}

	/**
	 * Serve dynamically generated service worker
	 */
	private function serve_dynamic_service_worker() {
		// Set proper headers.
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Cache-Control: max-age=3600' ); // Cache for 1 hour.

		// Get plugin URLs.
		$plugin_url  = MOSQUE_TIMETABLE_PLUGIN_URL;
		$assets_url  = MOSQUE_TIMETABLE_ASSETS_URL;
		$offline_url = MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/offline.html';

		// Generate service worker content.
		?>
/**
 * Mosque Prayer Timetable Service Worker
 * Version: 3.0.0 - Dynamically Generated
 */

const CACHE_NAME = 'mosque-timetable-v3.0.0';
			const OFFLINE_PAGE = '<?php echo esc_url( $offline_url ); ?>';

			// Assets to cache immediately.
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

			// Prayer times cache duration (1 hour).
			const PRAYER_CACHE_DURATION = 60 * 60 * 1000;

			// Install event - cache static assets.
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

			// Activate event - clean up old caches.
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

			// Fetch event - serve from cache when offline.
			self.addEventListener('fetch', (event) => {
			const request = event.request;
			const url = new URL(request.url);

			// Skip non-GET requests.
			if (request.method !== 'GET') {
			return;
			}

			// Handle navigation requests.
			if (request.mode === 'navigate') {
			event.respondWith(
			fetch(request)
			.catch(() => caches.match(OFFLINE_PAGE))
			);
			return;
			}

			// Handle static assets.
			if (url.pathname.includes('<?php echo esc_js( wp_parse_url( $assets_url, PHP_URL_PATH ) ); ?>')) {
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

			// Handle API requests (prayer times).
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

			// Default: try network first, fall back to cache.
			event.respondWith(
			fetch(request)
			.catch(() => caches.match(request))
			);
			});

			// Push notification event.
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

			// Notification click event.
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

		// Properly sanitize the nested array structure.
		$data = array();
		if ( isset( $_POST['data'] ) && is_array( $_POST['data'] ) ) {
			$raw_data = wp_unslash( $_POST['data'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $raw_data['days'] ) && is_array( $raw_data['days'] ) ) {
				foreach ( $raw_data['days'] as $day_data ) {
					if ( is_array( $day_data ) ) {
						$sanitized_day = array();
						foreach ( $day_data as $key => $value ) {
							$sanitized_day[ sanitize_key( $key ) ] = sanitize_text_field( $value );
						}
						$data[] = $sanitized_day;
					}
				}
			}
		}

		if ( $month < 1 || $month > 12 ) {
			wp_send_json_error( __( 'Invalid month', 'mosque-timetable' ) );
		}

		// normalise rows (ensure day_number int).
		$rows = array();
		foreach ( $data as $d ) {
			if ( empty( $d['day_number'] ) ) {
				continue;
			}
			$d['day_number'] = (int) $d['day_number'];
			$rows[]          = $d;
		}
		usort( $rows, fn( $a, $b ) => ( $a['day_number'] ?? 0 ) <=> ( $b['day_number'] ?? 0 ) );

		$result = mt_save_month_rows( $month, $rows, $year );

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Month saved successfully.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to save month data to database.' ) );
		}
	}

	/**
	 * AJAX: Recalculate Hijri dates
	 */
	public function ajax_recalculate_hijri_dates() {
		// Verify nonce for security.
		if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
		}

		$month      = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
		$year       = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : (int) wp_date( 'Y' );
		$adjustment = isset( $_POST['adjustment'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['adjustment'] ) ) ) : 0;

		if ( ! $month || $month < 1 || $month > 12 ) {
			wp_send_json_error( __( 'Invalid month specified', 'mosque-timetable' ) );
		}

		// Use helper function to get data with year-based field names.
		$rows = mt_get_month_rows( $month, $year );

		if ( empty( $rows ) ) {
			wp_send_json_error( __( 'No prayer data found for this month', 'mosque-timetable' ) );
		}

		$hijri_dates = array();

		// Recalculate Hijri dates for each row.
		foreach ( $rows as $index => $row ) {
			if ( ! empty( $row['date_full'] ) ) {
				$hijri_date                   = $this->calculate_hijri_date( $row['date_full'], $adjustment );
				$rows[ $index ]['hijri_date'] = $hijri_date;
				$hijri_dates[]                = $hijri_date;
			}
		}

		// Save using helper function with year-based field names.
		mt_save_month_rows( $month, $rows, $year );

		wp_send_json_success(
			array(
				'count' => count( $hijri_dates ),
				'dates' => $hijri_dates,
			)
		);
	}

	/**
	 * AJAX: Export CSV calendar
	 */
	public function ajax_export_csv_calendar() {
		// Verify nonce for security.
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'mosque_timetable_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed', 'mosque-timetable' ) );
		}

		$default_year = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
		$mosque_name  = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';

		// Set headers for CSV download.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="prayer-times-' . $default_year . '.csv"' );
		header( 'Cache-Control: max-age=0' );

		// Create output buffer.
		$output = fopen( 'php://output', 'w' );

		// Add CSV header.
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

		// Export all available months.
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

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Direct stream required for CSV export buffering.
		exit;
	}

	/**
	 * AJAX: Clear all prayer data
	 */
	public function ajax_clear_all_prayer_data() {
		// Verify nonce for security.
		if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
		}

		// Clear all monthly prayer data.
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
		// Verify nonce for security.
		if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
		}

		$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
		$available_months = get_field( 'available_months', 'option' ) ?: array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' );

		// Reset structure for each available month.
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
		// Verify nonce for security.
		if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
		}

		$default_year     = get_field( 'default_year', 'option' ) ?: wp_date( 'Y' );
		$available_months = get_field( 'available_months', 'option' ) ?: array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' );
		$processed        = 0;

		// Regenerate dates for each available month (preserving prayer times).
		foreach ( $available_months as $month_num ) {
			$month         = intval( $month_num );
			$year          = $default_year;
			$field_name    = 'daily_prayers_' . $month_num;
			$existing_data = get_field( $field_name, 'option' );

			// Get new date structure.
			$days_in_month = cal_days_in_month( CAL_GREGORIAN, intval( $month_num ), $default_year );
			$month_data    = array();

			for ( $day = 1; $day <= $days_in_month; $day++ ) {
				$date       = sprintf( '%04d-%02d-%02d', $default_year, intval( $month_num ), $day );
				$date_obj   = new DateTime( $date );
				$day_name   = $date_obj->format( 'l' );
				$hijri_date = $this->calculate_hijri_date( $date );

				// Preserve existing prayer times if available.
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
			// Remove undefined success() call - handled by wp_send_json_success below.
		}

		wp_send_json_success( 'All dates regenerated successfully' );
	}

	/**
	 * AJAX: Import CSV timetable
	 */
	public function ajax_import_csv_timetable() {
		// Security.
		if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
		}

		// Input.
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

		// Helpers.
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
			$data_row_count = 0; // Track actual data rows (excluding headers and empty lines).

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				++$row_number;
				continue;
			}

			$data = str_getcsv( $line );

			// Optional header skip.
			if ( 1 === $row_number && $this->is_header_row( $data ) ) {
				++$row_number;
				continue;
			}

			// This is a data row, increment the data row counter.
			++$data_row_count;

			// Determine mode.
			$day_num = null;
			$date    = null;
			$start   = 0;

			// Case A: first col = day, optional second col = date, then times.
			if ( isset( $data[0] ) && is_numeric( $data[0] ) && (int) $data[0] >= 1 && (int) $data[0] <= 31 ) {
				$day_num = (int) $data[0];
				$start   = 1;
				if ( isset( $data[1] ) && $this->looks_like_date( $data[1] ) ) {
					$date  = sanitize_text_field( $data[1] );
					$start = 2;
				}
			}

			// If no day number provided, fall back to data row count (not row number).
			if ( ! $day_num ) {
				$day_num = $data_row_count;
			}

			// Auto date if not provided.
			if ( ! $date ) {
				$date = sprintf( '%04d-%02d-%02d', $year, $month, $day_num );
			}

			// Extract times.
			$times = array_slice( $data, $start );

			// Accept either "date+times or "times only.
			// For times-only we expect at least 12 fields (fajr start..jummah2).
			// For date+times same expectation once start offset is applied.
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

			// Sort and save as a numeric array for ACF repeater.
			usort( $month_data, fn( $a, $b ) => ( $a['day_number'] ?? 0 ) <=> ( $b['day_number'] ?? 0 ) );

			// Ensure data is saved in the correct structure expected by get_month_prayer_data.
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
		// Verify nonce for security.
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

		// Get mosque settings.
		$mosque_name       = get_field( 'mosque_name', 'option' ) ?: 'Local Mosque';
		$mosque_address    = get_field( 'mosque_address', 'option' ) ?: '';
		$auto_calendar_url = mt_get_subscribe_url();

		// Get prayer data for the month.
		$prayer_data = $this->get_month_prayer_data( $year, $month );

		ob_start();
		?>
			<div class="mosque-timetable-container" role="region" aria-label="<?php echo esc_attr( mt_apply_terminology( 'Prayer Timetable' ) ); ?>">
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
								 Export Calendar
							</button>

							<?php
							// Print/Download PDF button.
							$pdf_url = mt_get_pdf_for_month( $month, $year );
							if ( $pdf_url ) :
								?>
								<a href="<?php echo esc_url( $pdf_url ); ?>"
									class="mosque-print-btn"
									target="_blank"
									title="Download printable PDF timetable">
									 Download Timetable
								</a>
							<?php else : ?>
								<button class="mosque-print-btn"
									onclick="window.print()"
									title="Print this timetable">
									 Print Timetable
								</button>
							<?php endif; ?>

							<a href="<?php echo esc_url( $auto_calendar_url ); ?>"
								class="mosque-subscribe-btn"
								target="_blank"
								title="Click to add our prayer times to your calendar app">
								 Subscribe to Our Prayer Calendar
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

								// Replace Zuhr with Jummah on Friday.
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
									$is_active = $is_next; // We can expand this logic later.
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

					<?php
					// Banner: only show when TODAY is in Ramadan (shows live suhoor/iftar countdown).
					$is_ramadan_today = $this->is_ramadan();
					if ( $is_ramadan_today ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode_ramadan_info returns pre-escaped HTML
						echo $this->shortcode_ramadan_info( array( 'layout' => 'banner', 'show_day' => 'true', 'show_countdown' => 'true' ) );
					}

					// Column: show Suhoor column whenever the *displayed month* overlaps Ramadan.
					$ramadan_col_start = mt_get_option( 'ramadan_start_date', '' );
					$ramadan_col_end   = mt_get_option( 'ramadan_end_date', '' );
					$month_first_day   = sprintf( '%04d-%02d-01', $year, $month );
					$month_last_day    = wp_date( 'Y-m-t', mktime( 0, 0, 0, $month, 1, $year ) );
					$is_ramadan        = $ramadan_col_start && $ramadan_col_end
						&& $month_first_day <= $ramadan_col_end
						&& $month_last_day  >= $ramadan_col_start;
					?>

					<table class="mosque-timetable">
						<thead>
							<tr>
								<th><?php echo esc_html( mt_apply_terminology( 'Date' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Hijri' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Day' ) ); ?></th>
								<th><?php echo esc_html( mt_apply_terminology( 'Fajr' ) ); ?></th>
								<?php if ( $is_ramadan ) : ?>
								<th class="suhoor-col"><?php esc_html_e( 'Suhoor', 'mosque-timetable' ); ?></th>
								<?php endif; ?>
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

								$date_str = isset( $day['date_full'] ) ? (string) $day['date_full'] : '';
								$date     = $date_str ? DateTime::createFromFormat( 'Y-m-d', $date_str ) : false;

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

									<?php if ( $is_ramadan ) : ?>
									<?php
									$rdstart        = mt_get_option( 'ramadan_start_date', '' );
									$rdend          = mt_get_option( 'ramadan_end_date', '' );
									$row_date_str   = $day['date_full'] ?? '';
									$row_in_ramadan = $rdstart && $rdend && $row_date_str >= $rdstart && $row_date_str <= $rdend;
									?>
									<td class="suhoor-col">
										<?php if ( $row_in_ramadan ) : ?>
										<div class="prayer-single"><?php echo esc_html( $this->calc_suhoor( $day['fajr_start'] ?? '' ) ); ?></div>
										<?php endif; ?>
									</td>
									<?php endif; ?>

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
					// Mobile card layout (hidden on desktop).
					if ( $prayer_data && ! empty( $prayer_data['days'] ) ) :
						?>
						<div class="mosque-timetable-mobile">
							<?php
							$days = isset( $prayer_data['days'] ) && is_array( $prayer_data['days'] ) ? $prayer_data['days'] : array();
							foreach ( $days as $day ) :
								if ( ! is_array( $day ) ) {
									continue;
								}
								$date_str   = isset( $day['date_full'] ) ? (string) $day['date_full'] : '';
								$date       = $date_str ? DateTime::createFromFormat( 'Y-m-d', $date_str ) : false;
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

											<?php if ( $is_ramadan ) : ?>
												<?php
												$rdstart_c      = mt_get_option( 'ramadan_start_date', '' );
												$rdend_c        = mt_get_option( 'ramadan_end_date', '' );
												$row_date_c     = $day['date_full'] ?? '';
												$card_in_ramadan = $rdstart_c && $rdend_c && $row_date_c >= $rdstart_c && $row_date_c <= $rdend_c;
												$suhoor_card    = $card_in_ramadan ? $this->calc_suhoor( $day['fajr_start'] ?? '' ) : '';
												?>
												<?php if ( $suhoor_card ) : ?>
												<div class="mosque-prayer-time-item suhoor-item">
													<div class="mosque-prayer-time-name">Suhoor</div>
													<div class="mosque-prayer-time-start"><?php echo esc_html( $suhoor_card ); ?></div>
												</div>
												<?php endif; ?>
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
						No prayer time data available for <?php echo esc_html( wp_date( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ); ?>. // Escape output.
						Please contact the mosque administrator.
					</div>
				<?php endif; ?>

				<div class="mosque-timetable-footer">
					<div class="mosque-system-credit">
						<p> Need a prayer timetable system or new website for your masjid?
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

		// Get today's prayer data.
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

						// Replace Zuhr with Jummah if it's Friday.
						if ( wp_date( 'N' ) === 5 && ( $today_data['jummah_1'] || $today_data['jummah_2'] ) ) {
							unset( $prayers['Zuhr'] ); // Remove Zuhr on Friday.

							// Build Jummah time display.
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
				'type'   => 'next',   // 'next' or specific prayer name.
				'layout' => 'card',   // 'card' (default full widget) or 'header' (compact inline pill).
				'size'   => 'normal', // 'small', 'normal', 'large' — applies to card layout only.
			),
			$atts,
			'prayer_countdown'
		);

		$type   = sanitize_text_field( $atts['type'] );
		$layout = in_array( $atts['layout'], array( 'card', 'header' ), true ) ? $atts['layout'] : 'card';
		$size   = in_array( $atts['size'], array( 'small', 'normal', 'large' ), true ) ? $atts['size'] : 'normal';

		// Get next prayer data.
		$next_prayer_data = $this->get_next_prayer_data();

		$data_target  = isset( $next_prayer_data['datetime'] ) ? esc_attr( $next_prayer_data['datetime'] ) : '';
		$data_prayer  = isset( $next_prayer_data['name'] ) ? esc_attr( $next_prayer_data['name'] ) : '';
		$prayer_name  = isset( $next_prayer_data['name'] ) ? esc_html( $next_prayer_data['name'] ) : '';
		$prayer_time  = isset( $next_prayer_data['time'] ) ? esc_html( $next_prayer_data['time'] ) : '';

		ob_start();

		// ── Header layout: compact single-line pill for nav/header use ─────────
		if ( 'header' === $layout ) :
			?>
			<span class="prayer-countdown-inline prayer-countdown"
				data-target="<?php echo $data_target; ?>"
				data-prayer="<?php echo $data_prayer; ?>"
				data-layout="header">
				<?php if ( $next_prayer_data ) : ?>
					<span class="pci-icon" aria-hidden="true">&#127775;</span>
					<span class="pci-name"><?php echo $prayer_name; ?></span>
					<span class="pci-time"><?php echo $prayer_time; ?></span>
					<span class="pci-divider" aria-hidden="true"></span>
					<span class="pci-countdown">--:--</span>
				<?php else : ?>
					<span class="pci-name pci-error"><?php esc_html_e( 'No timetable data', 'mosque-timetable' ); ?></span>
				<?php endif; ?>
			</span>
		<?php
		// ── Card layout: full-size countdown widget ────────────────────────────
		else :
			$size_class = 'normal' !== $size ? ' size-' . $size : '';
			?>
			<div class="prayer-countdown-container<?php echo $size_class; ?>">
				<div class="countdown-header">
					<div class="countdown-title"><?php esc_html_e( 'Next Prayer', 'mosque-timetable' ); ?></div>
					<?php if ( $next_prayer_data ) : ?>
						<div class="countdown-next-prayer"><?php echo $prayer_name; ?></div>
						<div class="countdown-next-time"><?php echo $prayer_time; ?></div>
					<?php endif; ?>
				</div>

				<div class="prayer-countdown"
					data-target="<?php echo $data_target; ?>"
					data-prayer="<?php echo $data_prayer; ?>">
					<?php if ( $next_prayer_data ) : ?>
						<div class="countdown-timer">
							<div class="countdown-unit">
								<span class="countdown-number">00</span>
								<span class="countdown-label"><?php esc_html_e( 'Hours', 'mosque-timetable' ); ?></span>
							</div>
							<div class="countdown-unit">
								<span class="countdown-number">00</span>
								<span class="countdown-label"><?php esc_html_e( 'Minutes', 'mosque-timetable' ); ?></span>
							</div>
							<div class="countdown-unit">
								<span class="countdown-number">00</span>
								<span class="countdown-label"><?php esc_html_e( 'Seconds', 'mosque-timetable' ); ?></span>
							</div>
						</div>
					<?php else : ?>
						<div class="mosque-error">
							<?php esc_html_e( 'Unable to calculate next prayer time. Please check your timetable data.', 'mosque-timetable' ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php
		endif;

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
		// Use the year-aware mt_get_month_rows function instead of direct ACF access.
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

		// Define prayer times in order.
		$prayer_times = array(
			'Fajr'    => $today_data['fajr_start'],
			'Sunrise' => $today_data['sunrise'],
			'Zuhr'    => $today_data['zuhr_start'],
			'Asr'     => $today_data['asr_start'],
			'Maghrib' => $today_data['maghrib_start'],
			'Isha'    => $today_data['isha_start'],
		);

		// Replace Zuhr with Jummah on Friday.
		if ( $now->format( 'N' ) === 5 ) { // Friday.
			if ( $today_data['jummah_1'] ) {
				$prayer_times['Jummah'] = $today_data['jummah_1'];
				unset( $prayer_times['Zuhr'] );
			}
		}

		// Find next prayer.
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

		// If no prayer found today, get tomorrow's Fajr.
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
		// Convert to integers to match type hints.
		$year  = (int) $year;
		$month = (int) $month;
		$day   = (int) $day;

		$month_data = $this->get_month_prayer_data( $year, $month );

		if ( ! $month_data || ! $month_data['days'] ) {
			return null;
		}

		foreach ( $month_data['days'] as $day_data ) {
			// Support both 'day' and legacy 'day_number' field names.
			$day_num = isset( $day_data['day'] ) ? (int) $day_data['day'] : ( isset( $day_data['day_number'] ) ? (int) $day_data['day_number'] : 0 );
			if ( $day_num === $day ) {
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

		// Get prayer data.
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

				// Prayer times to include.
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

				// Replace Zuhr with Jummah on Friday.
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
					// Add start times.
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

					// Add jamaat times (except for Sunrise and Jummah).
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

		// Generate ICS header.
		$ics_content  = "BEGIN:VCALENDAR\r\n";
		$ics_content .= "VERSION:2.0\r\n";
		$ics_content .= "PRODID:-//Mosque Timetable Plugin//Prayer Times//EN\r\n";
		$ics_content .= "CALSCALE:GREGORIAN\r\n";
		$ics_content .= "METHOD:PUBLISH\r\n";
		$ics_content .= 'X-WR-CALNAME:' . $this->escape_ics_text( $mosque_name . ' Prayer Times' ) . "\r\n";
		$ics_content .= 'X-WR-CALDESC:' . $this->escape_ics_text( 'Prayer timetable for ' . $mosque_name ) . "\r\n";
		$timezone     = wp_timezone_string();
		$ics_content .= 'X-WR-TIMEZONE:' . $timezone . "\r\n";

		// Determine months to process.
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

				// Build prayer list.
				$prayers = array();

				// Always include start times.
				$prayers['Fajr']    = array(
					'time' => $day['fajr_start'],
					'type' => 'start',
				);
				$prayers['Sunrise'] = array(
					'time' => $day['sunrise'],
					'type' => 'info',
				);

				// Handle Friday/Jummah logic.
				if ( $is_friday ) {
					// On Fridays, include Jummah instead of Zuhr based on selection.
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

				// Add Jamaah times if requested.
				if ( $options['include_jamah'] ) {
					$prayers['Fajr Jamaah'] = array(
						'time' => $day['fajr_jamaat'],
						'type' => 'jamaat',
					);
					if ( ! $is_friday ) {
						$prayers['Zuhr Jamaah'] = array(
							'time' => $day['zuhr_jamaat'],
							'type' => 'jamaat',
						);
					}
					$prayers['Asr Jamaah']     = array(
						'time' => $day['asr_jamaat'],
						'type' => 'jamaat',
					);
					$prayers['Maghrib Jamaah'] = array(
						'time' => $day['maghrib_jamaat'],
						'type' => 'jamaat',
					);
					$prayers['Isha Jamaah']    = array(
						'time' => $day['isha_jamaat'],
						'type' => 'jamaat',
					);
				}

				// Generate events for each prayer.
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

				// Add sunrise warning if requested.
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
							array( 0 ), // No additional alarms for warnings.
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

		// Parse time.
		$time_parts = explode( ':', $time );
		if ( count( $time_parts ) !== 2 ) {
			return '';
		}

		$dt = clone $date_obj;
		$dt->setTime( intval( $time_parts[0] ), intval( $time_parts[1] ) );

		// Event duration (5 minutes for prayers, 1 minute for info/warnings).
		$duration = ( 'info' === $type || 'warning' === $type ) ? 1 : 5;
		$end      = clone $dt;
		$end->add( new DateInterval( 'PT' . $duration . 'M' ) );

		// Generate unique ID.
		$uid = md5( $title . $dt->format( 'Y-m-d H:i:s' ) ) . '@mosque-timetable';
		$now = wp_date( 'Ymd\THis\Z' );

		$event  = "BEGIN:VEVENT\r\n";
		$event .= 'UID:' . $uid . "\r\n";
		$event .= 'DTSTAMP:' . $now . "\r\n";
		$event .= "DTSTART;TZID={$timezone}:" . $dt->format( 'Ymd\THis' ) . "\r\n";
		$event .= "DTEND;TZID={$timezone}:" . $end->format( 'Ymd\THis' ) . "\r\n";
		$event .= 'SUMMARY:' . $this->escape_ics_text( $title ) . "\r\n";

		// Different descriptions based on type.
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

		// Add multiple alarms.
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

		// Parse header (first line).
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

		// Validate header.
		if ( count( $header ) < 13 ) { // At least date + 12 prayer times.
			return new WP_Error( 'invalid_format', 'CSV file does not have required columns' );
		}

		$prayer_data = array();
		$year        = wp_date( 'Y' );

		// Process data rows.
		$line_count = count( $lines );
		for ( $i = 1; $i < $line_count; $i++ ) {
			if ( empty( trim( $lines[ $i ] ) ) ) {
				continue;
			}

			$row = str_getcsv( $lines[ $i ] );
			if ( count( $row ) < 13 ) {
				continue;
			}

			// Parse date.
			$date_str    = trim( $row[0] );
			$parsed_date = $this->parse_flexible_date( $date_str, $year, $month );

			if ( ! $parsed_date ) {
				continue; // Skip invalid dates.
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

		// Save to ACF.
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
		// Remove extra spaces.
		$date_str = preg_replace( '/\s+/', ' ', trim( $date_str ) );

		// Try different date formats.
		$formats = array(
			'd/m/Y',
			'd.m.Y',
			'd-m-Y', // Day first with year.
			'd/m',
			'd.m',
			'd-m', // Day first without year.
			'j/n/Y',
			'j.n.Y',
			'j-n-Y', // Single digits.
			'j/n',
			'j.n',
			'j-n', // Single digits without year.
			'j', // Just day number.
		);

		foreach ( $formats as $format ) {
			$parsed = DateTime::createFromFormat( $format, $date_str );
			if ( false !== $parsed ) {
				// If year is missing, use provided year.
				if ( false === strpos( $format, 'Y' ) ) {
					$parsed->setDate( $year, $month, (int) $parsed->format( 'j' ) );
				}
				// If month is missing, use provided month.
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

		// Handle different time formats.
		if ( preg_match( '/^(\d{1,2}):(\d{2})$/', $time_str, $matches ) ) {
			return sprintf( '%02d:%02d', $matches[1], $matches[2] );
		}

		if ( preg_match( '/^(\d{1,2})\.(\d{2})$/', $time_str, $matches ) ) {
			return sprintf( '%02d:%02d', $matches[1], $matches[2] );
		}

		if ( ( preg_match( '/^(\d{1,2})(\d{2})$/', $time_str, $matches ) && 3 === strlen( $time_str ) ) || 4 === strlen( $time_str ) ) {
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

		// Save to ACF using the new field structure.
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

		// Replace Zuhr with Jummah on Friday.
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
			$end_datetime   = $today_date . 'T' . $times['start'] . ':00'; // Same time for start/end.

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

			// Add jamaat time if available.
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
	 * Fetch prayer times from Aladhan API
	 *
	 * @param int $year Year
	 * @param int $month Month
	 * @return array|WP_Error Array of prayer times or error
	 */
	private function fetch_prayer_times_from_api( $year, $month ) {
		// Get settings (with ACF/options fallback).
		$latitude  = mt_get_option( 'mosque_latitude', '' );
		$longitude = mt_get_option( 'mosque_longitude', '' );
		$method    = mt_get_option( 'calculation_method', '2' );

		// Validate coordinates.
		if ( empty( $latitude ) || empty( $longitude ) ) {
			return new WP_Error( 'missing_coordinates', 'Latitude and longitude are required for automatic prayer times' );
		}

		// Construct Aladhan API URL.
		$api_url = sprintf(
			'https://api.aladhan.com/v1/calendar/%d/%d?latitude=%s&longitude=%s&method=%s',
			$year,
			$month,
			$latitude,
			$longitude,
			$method
		);

		// Fetch from API.
		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', 'Failed to fetch prayer times: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new WP_Error( 'api_error', 'API returned error code: ' . $response_code );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return new WP_Error( 'invalid_response', 'Invalid API response format' );
		}

		return $data['data'];
	}

	/**
	 * Calculate Jamaah time by adding offset to start time
	 *
	 * @param string $start_time Start time (HH:MM format)
	 * @param int    $offset_minutes Minutes to add
	 * @return string Jamaah time (HH:MM format)
	 */
	private function calculate_jamaat_time( $start_time, $offset_minutes ) {
		if ( empty( $start_time ) || $offset_minutes <= 0 ) {
			return '';
		}

		try {
			$time = new DateTime( $start_time );
			$time->add( new DateInterval( 'PT' . $offset_minutes . 'M' ) );
			return $time->format( 'H:i' );
		} catch ( Exception $e ) {
			return '';
		}
	}

	/**
	 * Generate month date structure with optional automatic prayer times
	 */
	public function generate_month_structure( $year, $month ) {
		$days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );
		$month_data    = array();

		// Check if automatic prayer times are enabled.
		$auto_times_enabled = mt_get_option( 'enable_auto_times', 1 );
		$api_data           = null;

		// Fetch prayer times from API if enabled.
		if ( $auto_times_enabled ) {
			$api_response = $this->fetch_prayer_times_from_api( $year, $month );

			if ( ! is_wp_error( $api_response ) ) {
				$api_data = $api_response;

				// Get Jamaah offsets.
				$fajr_offset    = (int) mt_get_option( 'fajr_jamaat_offset', 10 );
				$zuhr_offset    = (int) mt_get_option( 'zuhr_jamaat_offset', 15 );
				$asr_offset     = (int) mt_get_option( 'asr_jamaat_offset', 15 );
				$maghrib_offset = (int) mt_get_option( 'maghrib_jamaat_offset', 5 );
				$isha_offset    = (int) mt_get_option( 'isha_jamaat_offset', 15 );
			}
		}

		// Build month data with dates and prayer times.
		for ( $day = 1; $day <= $days_in_month; $day++ ) {
			$date_str   = sprintf( '%04d-%02d-%02d', $year, $month, $day );
			$hijri_date = $this->calculate_hijri_date( $date_str );

			// Initialize with empty times.
			$day_data = array(
				'day_number'     => $day,
				'date_full'      => $date_str,
				'day_name'       => wp_date( 'l', strtotime( $date_str ) ),
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

			// Populate with API data if available.
			if ( $api_data && isset( $api_data[ $day - 1 ]['timings'] ) ) {
				$timings = $api_data[ $day - 1 ]['timings'];

				// Extract times (Aladhan returns format like "05:30 (GMT)").
				$day_data['fajr_start']    = $this->parse_api_time( $timings['Fajr'] ?? '' );
				$day_data['sunrise']       = $this->parse_api_time( $timings['Sunrise'] ?? '' );
				$day_data['zuhr_start']    = $this->parse_api_time( $timings['Dhuhr'] ?? '' );
				$day_data['asr_start']     = $this->parse_api_time( $timings['Asr'] ?? '' );
				$day_data['maghrib_start'] = $this->parse_api_time( $timings['Maghrib'] ?? '' );
				$day_data['isha_start']    = $this->parse_api_time( $timings['Isha'] ?? '' );

				// Calculate Jamaah times.
				$day_data['fajr_jamaat']    = $this->calculate_jamaat_time( $day_data['fajr_start'], $fajr_offset );
				$day_data['zuhr_jamaat']    = $this->calculate_jamaat_time( $day_data['zuhr_start'], $zuhr_offset );
				$day_data['asr_jamaat']     = $this->calculate_jamaat_time( $day_data['asr_start'], $asr_offset );
				$day_data['maghrib_jamaat'] = $this->calculate_jamaat_time( $day_data['maghrib_start'], $maghrib_offset );
				$day_data['isha_jamaat']    = $this->calculate_jamaat_time( $day_data['isha_start'], $isha_offset );

				// For Friday, copy Zuhr time to Jummah 1 (admin can adjust manually).
				if ( 'Friday' === $day_data['day_name'] && ! empty( $day_data['zuhr_jamaat'] ) ) {
					$day_data['jummah_1'] = $day_data['zuhr_jamaat'];
				}
			}

			$month_data[] = $day_data;
		}

		// Save using year-specific field name.
		return mt_save_month_rows( $month, $month_data, $year );
	}

	/**
	 * Parse API time format (removes timezone suffix)
	 *
	 * @param string $api_time Time from API (e.g., "05:30 (GMT)")
	 * @return string Clean time (e.g., "05:30")
	 */
	private function parse_api_time( $api_time ) {
		if ( empty( $api_time ) ) {
			return '';
		}

		// Remove timezone suffix like " (GMT)" or " (BST)".
		$time = trim( preg_replace( '/\s*\([^)]+\)\s*$/', '', $api_time ) );

		// Validate format.
		if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			return $time;
		}

		return '';
	}

	/**
	 * Save month data
	 */
	public function save_month_data( $year, $month, $data ) {
		$year = $year ?: (int) get_option( 'default_year', (int) wp_date( 'Y' ) );

		// Extract days array if data is wrapped.
		$days = isset( $data['days'] ) && is_array( $data['days'] ) ? $data['days'] : $data;

		if ( mt_has_acf() ) {
			// ACF local fields are registered as daily_prayers_{month} (no year).
			// Check if year-specific field is registered before using it.
			$year_field   = "daily_prayers_{$year}_{$month}";
			$legacy_field = "daily_prayers_{$month}";
			$field_exists = function_exists( 'acf_get_field' ) && acf_get_field( $year_field );
			$field_name   = $field_exists ? $year_field : $legacy_field;
			return (bool) update_field( $field_name, $days, 'option' );
		}

		// Fallback to options table.
		$all                    = get_option( 'mosque_timetable_rows', array() );
		$all[ $year ]           = $all[ $year ] ?? array();
		$all[ $year ][ $month ] = $days;
		update_option( 'mosque_timetable_rows', $all, false );
		return true;
	}

	/**
	 * Reset month structure (keep dates, clear prayer times)
	 */
	public function reset_month_structure( $year, $month ) {
		$existing_data = $this->get_month_prayer_data( $year, $month );

		if ( $existing_data && isset( $existing_data['days'] ) ) {
			// Keep date structure, clear prayer times.
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
		// List of RTL language codes.
		$rtl_languages = array(
			'ar', // Arabic.
			'he', // Hebrew.
			'fa', // Persian/Farsi.
			'ur', // Urdu.
			'ku', // Kurdish.
			'sd', // Sindhi.
			'ps', // Pashto.
			'dv', // Divehi.
			'yi', // Yiddish.
			'arc', // Aramaic.
		);

		// Get current site language.
		$locale        = get_locale();
		$language_code = substr( $locale, 0, 2 );

		// Check if current language is RTL.
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

	/**
	 * AJAX handler for push notification subscription
	 */
	public function ajax_subscribe_push_notifications() {
		// Verify nonce for security.
		if ( ! check_ajax_referer( 'mosque_timetable_nonce', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid security token' ) );
		}

		// Get JSON input.
		$input = file_get_contents( 'php://input' );
		$data  = json_decode( $input, true );

		if ( ! $data || ! isset( $data['subscription'] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid subscription data' ) );
		}

		$subscription    = $data['subscription'];
		$alarms          = $data['alarms'] ?? array( 5, 15 );
		$sunrise_warning = $data['sunrise_warning'] ?? false;

		// Validate subscription structure.
		if ( ! isset( $subscription['endpoint'] ) || ! isset( $subscription['keys'] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid subscription format' ) );
		}

		// Get current subscriptions.
		$subscriptions = get_option( 'mt_push_subscriptions', array() );

		// Store subscription with user preferences.
		$subscriptions[ $subscription['endpoint'] ] = array(
			'subscription'    => $subscription,
			'offsets'         => array_map( 'intval', $alarms ),
			'sunrise_warning' => (bool) $sunrise_warning,
			'subscribed_at'   => time(),
			'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'ip_address'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		);

		// Save updated subscriptions.
		if ( update_option( 'mt_push_subscriptions', $subscriptions ) ) {
			wp_send_json_success( array( 'message' => 'Successfully subscribed to notifications' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to save subscription' ) );
		}
	}

	/**
	 * AJAX handler for push notification unsubscription
	 */
	public function ajax_unsubscribe_push_notifications() {
		// Verify nonce for security.
		if ( ! check_ajax_referer( 'mosque_timetable_nonce', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid security token' ) );
		}

		// Get JSON input.
		$input = file_get_contents( 'php://input' );
		$data  = json_decode( $input, true );

		if ( ! $data || ! isset( $data['endpoint'] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid endpoint data' ) );
		}

		$endpoint = $data['endpoint'];

		// Get current subscriptions.
		$subscriptions = get_option( 'mt_push_subscriptions', array() );

		// Remove subscription.
		if ( isset( $subscriptions[ $endpoint ] ) ) {
			unset( $subscriptions[ $endpoint ] );

			// Save updated subscriptions.
			if ( update_option( 'mt_push_subscriptions', $subscriptions ) ) {
				wp_send_json_success( array( 'message' => 'Successfully unsubscribed from notifications' ) );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to remove subscription' ) );
			}
		} else {
			wp_send_json_error( array( 'message' => 'Subscription not found' ) );
		}
	}
}



