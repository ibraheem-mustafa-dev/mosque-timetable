<?php

declare(strict_types=1);

/**
 * IDE Suppression Notes:
 * - filter_var() and FILTER_* constants are core PHP functions but may not be recognized by all IDE configurations
 * - SimpleXLSX classes are optional dependencies with proper error handling
 * - All @phpstan-ignore annotations are intentional for optional/configuration-dependent code
 */

/**
 * Plugin Name: Mosque Timetable - Prayer Times for Mosques
 * Plugin URI:  https: //mosquewebdesign.com/mosque-timetable.
 * Description: The most complete prayer times plugin for mosques. Auto-calculation, PWA, push notifications, offline mode, REST API, ICS calendar export, digital screen display, and 150+ features.
 * Version:     3.3.6
 * Author:      Ibraheem Mustafa
 * Author URI:  https: //mosquewebdesign.com.
 * License:     GPL-2.0+
 * License URI: https: //www.gnu.org/licenses/gpl-2.0.html.
 * Text Domain: mosque-timetable
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'MOSQUE_TIMETABLE_VERSION', '3.3.6' );
define( 'MOSQUE_TIMETABLE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOSQUE_TIMETABLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MOSQUE_TIMETABLE_ASSETS_URL', MOSQUE_TIMETABLE_PLUGIN_URL . 'assets/' );
define( 'MOSQUE_TIMETABLE_MAIN_FILE', __FILE__ );

// Load Composer autoloader with fallback strategy.
// Prefer the plugin's own autoloader for self-contained operation.
$plugin_autoload = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $plugin_autoload ) ) {
	require $plugin_autoload;
} else {
	// Fallback for Composer-managed sites that load a global autoloader.
	$root_autoload = ABSPATH . 'vendor/autoload.php';
	if ( is_readable( $root_autoload ) ) {
		require $root_autoload;
	}
	// If no autoloader is found, libraries will be handled by conditional checks.
}

// Use statements for SimpleXLSX libraries.
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

// Load plugin components.
require_once MOSQUE_TIMETABLE_PLUGIN_DIR . 'includes/helpers.php';
require_once MOSQUE_TIMETABLE_PLUGIN_DIR . 'includes/class-mosque-timetable.php';


	// Initialize the plugin.
	MosqueTimetablePlugin::get_instance();

	// Hook to auto-calculate Hijri dates when date fields are updated.
	add_filter(
		'acf/update_value/name=date_full',
		function ( $value, $post_id, $field ) {
			if ( $value ) {
				$plugin     = MosqueTimetablePlugin::get_instance();
				$hijri_date = $plugin->calculate_hijri_date( $value );

				// Find the current repeater row and update hijri_date field.
				$parent_key = $field['parent'];
				if ( $parent_key && strpos( $parent_key, 'daily_prayers' ) !== false ) {
					// Extract row number from field key.
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
	// This extends the existing class with the missing functionality.
	add_action(
		'wp_loaded',
		function () {
			$mosque_plugin = MosqueTimetablePlugin::get_instance();

			// Generate all dates AJAX handler.
			add_action(
				'wp_ajax_generate_all_dates',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					$year = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( get_option( 'default_year', intval( wp_date( 'Y' ) ) ) );

					// Prefer ACF option when present, else fall back to all 12 months.
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

			// Generate month dates AJAX handler.
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

			// Save all months AJAX handler.
			add_action(
				'wp_ajax_save_all_months',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					$year = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : 0;

					// Properly sanitize the nested array structure.
					$data = array();
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Iterative sanitization applied to each key/value below.
					if ( isset( $_POST['data'] ) && is_array( $_POST['data'] ) ) {
						$raw_data = wp_unslash( $_POST['data'] );
						foreach ( $raw_data as $month_num => $month_data ) {
							if ( is_array( $month_data ) && isset( $month_data['days'] ) && is_array( $month_data['days'] ) ) {
								$sanitized_days = array();
								foreach ( $month_data['days'] as $day_data ) {
									if ( is_array( $day_data ) ) {
										$sanitized_day = array();
										foreach ( $day_data as $key => $value ) {
											$sanitized_day[ sanitize_key( $key ) ] = sanitize_text_field( $value );
										}
										$sanitized_days[] = $sanitized_day;
									}
								}
								$data[ intval( $month_num ) ] = array( 'days' => $sanitized_days );
							}
						}
					}
					// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

					$success_count = 0;

					foreach ( $data as $month => $month_data ) {
						if ( $mosque_plugin->save_month_data( $year, intval( $month ), $month_data ) ) {
							$success_count++;
						}
					}

					wp_send_json_success( array( 'saved_months' => $success_count ) );
				}
			);

			// Import XLSX AJAX handler.
			add_action(
				'wp_ajax_import_xlsx_timetable',
				function () use ( $mosque_plugin ) {
					// Security.
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( 'Security check failed', 403 );
					}
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( 'Insufficient permissions', 403 );
					}

					// Inputs.
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

					// Library.
					$simplexlsx_path = MOSQUE_TIMETABLE_PLUGIN_DIR . 'vendor/shuchkin/simplexlsx/src/SimpleXLSX.php';
					$fs              = mt_fs();
					if ( ! $fs || ! $fs->exists( $simplexlsx_path ) ) {
						wp_send_json_error( __( 'XLSX import requires SimpleXLSX library. Install composer deps or use CSV instead.', 'mosque-timetable' ) );
					}
					require_once $simplexlsx_path;

					// Time normaliser.
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
							// Optional header skip.
							if ( ! $header_skipped && $mosque_plugin->is_header_row( $data ) ) {
								$header_skipped = true;
								continue;
							}

							// Determine mode.
							$day_num = null;
							$date    = null;
							$start   = 0;

							// Case A: first col is day, optional second col is date.
							if ( isset( $data[0] ) && is_numeric( $data[0] ) && (int) $data[0] >= 1 && (int) $data[0] <= 31 ) {
								$day_num = (int) $data[0];
								$start   = 1;
								if ( isset( $data[1] ) && $mosque_plugin->looks_like_date( $data[1] ) ) {
									$date  = sanitize_text_field( $data[1] );
									$start = 2;
								}
							}

							// If no explicit day, use sequential index (accounting for header).
							if ( ! $day_num ) {
								$day_num = count( $month_rows ) + 1; // sequential by accepted rows.
							}

							// Auto-generate date if not provided.
							if ( ! $date ) {
								$date = sprintf( '%04d-%02d-%02d', $year, $month, $day_num );
							}

							// Extract times (must be ≥ 12 for times-only).
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

						// Sort the imported rows by day_number in ascending order.
						usort( $month_rows, fn( $a, $b ) => ( $a['day_number'] ?? 0 ) <=> ( $b['day_number'] ?? 0 ) );

						// Save the rows and capture success.
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

			// Import paste data AJAX handler.
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

					// Normalise quick helper.
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

						// Case A: day + date + times (>= 13 columns).
						if ( count( $cells ) >= 13 && is_numeric( $cells[0] ) ) {
							$day_num = (int) $cells[0];
							$date    = sanitize_text_field( $cells[1] );
							$start   = 2;
						} elseif ( count( $cells ) >= 12 ) {
							// Case B: times-only (>= 12 columns).
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

			// Clear all data AJAX handler.
			add_action(
				'wp_ajax_clear_all_data',
				function () use ( $mosque_plugin ) {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Clear all monthly timetable data.
					for ( $month = 1; $month <= 12; $month++ ) {
						delete_field( "daily_prayers_{$month}", 'option' );
					}

					wp_send_json_success( 'All prayer time data cleared' );
				}
			);

			// Reset empty structure AJAX handler.
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

			// Calculate Hijri date AJAX handler.
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

			// AJAX: Refresh admin nonce.

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

			// If guests should also refresh (usually no):.
			// add_action( 'wp_ajax_nopriv_refresh_admin_nonce', 'mosque_timetable_ajax_refresh_admin_nonce' );.

			// Get month timetable AJAX handler.

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
			// If front-end/guests call this, also add:.
			// add_action( 'wp_ajax_nopriv_get_month_timetable', ...same callback... );.

			// Download sample CSV template.
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

					// Create sample CSV data with proper headers and example data.
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
					fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Direct stream required for CSV export.
					exit;
				}
			);

			// Download sample XLSX template.
			add_action(
				'wp_ajax_download_sample_xlsx',
				function () use ( $mosque_plugin ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) ), 'mosque_sample_download' ) ) {
						wp_die( esc_html__( 'Security check failed', 'mosque-timetable' ) );
					}

					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_die( esc_html__( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Check if SimpleXLSXGen is available.
					$xlsx_path = MOSQUE_TIMETABLE_PLUGIN_DIR . 'vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php';
					$fs        = mt_fs();
					if ( ! $fs || ! $fs->exists( $xlsx_path ) ) {
						wp_die( 'XLSX generation not available. Please ensure SimpleXLSXGen is installed.' );
					}

					require_once $xlsx_path;

					$filename = 'mosque-prayer-times-sample.xlsx';

					// Create sample XLSX data.
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

						// Set headers and output the file.
						header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
						header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
						header( 'Cache-Control: no-cache, must-revalidate' );

						// SimpleXLSXGen::download() doesn't take parameters - it outputs directly.
						$xlsx->saveToFile( 'php://output' );
						exit;
					} catch ( Exception $e ) {
						error_log( 'Mosque Timetable error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Exception logging in catch block.
						echo esc_html__( 'Something went wrong. Please try again.', 'mosque-timetable' ); // safe UI.
					}
				}
			);

			// --- PHP: AJAX handlers for PDF upload + removal ---.

			// Upload month PDF.
			add_action(
				'wp_ajax_upload_month_pdf',
				function () {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ), 403 );
					}
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ), 403 );
					}

					$month = isset( $_POST['month'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['month'] ) ) : 0;
					$year  = isset( $_POST['year'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['year'] ) ) : (int) wp_date( 'Y' );

					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month', 'mosque-timetable' ) );
					}
					if ( ! isset( $_FILES['pdf_file'] ) || empty( $_FILES['pdf_file']['name'] ) ) {
						wp_send_json_error( __( 'No file uploaded', 'mosque-timetable' ) );
					}

					// Only accept PDFs.
					$filetype = wp_check_filetype( $_FILES['pdf_file']['name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_check_filetype() validates file extension; media_handle_upload() handles full sanitization.
					if ( empty( $filetype['ext'] ) || strtolower( $filetype['ext'] ) !== 'pdf' ) {
						wp_send_json_error( __( 'Please upload a PDF file', 'mosque-timetable' ) );
					}

					// Prepare WP media includes.
					if ( ! function_exists( 'media_handle_upload' ) ) {
						require_once ABSPATH . 'wp-admin/includes/image.php';
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/media.php';
					}

					// Let WordPress handle upload + attachment creation.
					$attachment_id = media_handle_upload( 'pdf_file', 0 );
					if ( is_wp_error( $attachment_id ) ) {
						wp_send_json_error( $attachment_id->get_error_message() ?: __( 'Upload failed', 'mosque-timetable' ) ); // phpcs:ignore Universal.Operators.DisallowShortTernary.Found -- get_error_message() returns string or empty string; falsy fallback required.
					}

					$url = wp_get_attachment_url( $attachment_id );
					if ( ! $url ) {
						wp_send_json_error( __( 'Could not resolve uploaded URL', 'mosque-timetable' ) );
					}

					// Persist the URL (prefer helper if available; else store an option).
					if ( function_exists( 'mt_set_pdf_for_month' ) ) {
						$ok = mt_set_pdf_for_month( $month, $year, $url );
					} else {
						// Fallback storage key.
						$ok = update_option( "mt_pdf_{$year}_{$month}", esc_url_raw( $url ) );
					}

					if ( ! $ok ) {
						wp_send_json_error( __( 'Failed to save PDF reference', 'mosque-timetable' ) );
					}

					wp_send_json_success( array( 'url' => $url ) );
				}
			);

			// Remove month PDF.
			add_action(
				'wp_ajax_remove_month_pdf',
				function () {
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ), 403 );
					}
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ), 403 );
					}

					$month = isset( $_POST['month'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['month'] ) ) : 0;
					$year  = isset( $_POST['year'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['year'] ) ) : (int) wp_date( 'Y' );

					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month', 'mosque-timetable' ) );
					}

					$ok = false;
					if ( function_exists( 'mt_remove_pdf_for_month' ) ) {
						$ok = mt_remove_pdf_for_month( $month, $year );
					} else {
						// Fallback removes our stored option (URL only).
						$ok = delete_option( "mt_pdf_{$year}_{$month}" );
					}

					if ( ! $ok ) {
						wp_send_json_error( __( 'Nothing to remove or removal failed', 'mosque-timetable' ) );
					}

					wp_send_json_success( __( 'PDF removed', 'mosque-timetable' ) );
				}
			);

			// Generate All Dates AJAX handler.
			add_action(
				'wp_ajax_generate_all_dates',
				function () use ( $mosque_plugin ) {
					// Security check.
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities.
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Get year parameter.
					$year = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( wp_date( 'Y' ) );

					// Generate all months.
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

			// Generate Month Dates AJAX handler.
			add_action(
				'wp_ajax_generate_month_dates',
				function () use ( $mosque_plugin ) {
					// Security check.
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities.
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Validate inputs.
					$month = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					$year  = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( wp_date( 'Y' ) );

					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month', 'mosque-timetable' ) );
					}

					// Generate month structure.
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

			// Recalculate Hijri Dates AJAX handler (uses class method with year support).
			add_action( 'wp_ajax_recalculate_hijri_dates', array( $mosque_plugin, 'ajax_recalculate_hijri_dates' ) );

			// Import Paste Data AJAX handler.
			add_action(
				'wp_ajax_import_paste_data',
				function () use ( $mosque_plugin ) {
					// Security check.
					if ( ! check_ajax_referer( 'mosque_timetable_nonce', 'nonce', false ) ) {
						wp_send_json_error( __( 'Security check failed', 'mosque-timetable' ) );
					}

					// Check user capabilities.
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Insufficient permissions', 'mosque-timetable' ) );
					}

					// Input validation.
					$month = isset( $_POST['month'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['month'] ) ) ) : 0;
					if ( $month < 1 || $month > 12 ) {
						wp_send_json_error( __( 'Invalid month specified', 'mosque-timetable' ) );
					}

					$paste_data = isset( $_POST['paste_data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['paste_data'] ) ) : '';
					if ( empty( $paste_data ) ) {
						wp_send_json_error( __( 'No data provided', 'mosque-timetable' ) );
					}

					// Process pasted data as CSV-like format.
					$year = isset( $_POST['year'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['year'] ) ) ) : intval( wp_date( 'Y' ) );

					// Normalize time format functionCCLAUDE.
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

					// Parse pasted data (assume tab or comma separated).
					$lines          = preg_split( "/\r\n|\n|\r/", $paste_data );
					$month_data     = array();
					$processed      = 0;
					$data_row_count = 0;

					foreach ( $lines as $line ) {
						$line = trim( $line );
						if ( '' === $line ) {
							continue;
						}

						// Try tab first, then comma.
						$data = str_getcsv( $line, "\t" );
						if ( count( $data ) < 2 ) {
							$data = str_getcsv( $line, ',' );
						}

						// Skip header-like rows.
						if ( $mosque_plugin->is_header_row( $data ) ) {
							continue;
						}

						++$data_row_count;

						// Parse similar to CSV.
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

					// Sort and save.
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
