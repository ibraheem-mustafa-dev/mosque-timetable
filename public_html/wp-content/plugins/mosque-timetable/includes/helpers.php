<?php

declare(strict_types=1);

/**
 * Mosque Timetable - Global Helper Functions
 *
 * @package MosqueTimetable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === Load ACF stubs only if ACF isn't available (after other plugins) ===
add_action(
	'plugins_loaded',
	static function () {
		// If ACF Pro (or ACF) is active, do nothing
		if ( function_exists( 'get_field' ) || defined( 'ACF_VERSION' ) || class_exists( 'ACF' ) ) {
			return;
		}

		// As a final guard, only load the stub if get_field isn't defined
		$stub = MOSQUE_TIMETABLE_PLUGIN_DIR . 'tools/stubs-acf.php';
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

/**
 * Get option value with ACF fallback
 * Tries ACF first if available, then falls back to WordPress options
 *
 * @param string $key Option key
 * @param mixed  $default Default value if not found
 * @return mixed Option value
 */
if ( ! function_exists( 'mt_get_option' ) ) {
	function mt_get_option( string $key, $default = false ) {
		if ( mt_has_acf() ) {
			$value = get_field( $key, 'option' );
			if ( $value !== false && $value !== null && $value !== '' ) {
				return $value;
			}
		}
		return get_option( $key, $default );
	}
}

/**
 * Update option value with ACF support
 * Updates both ACF and WordPress options for compatibility
 *
 * @param string $key Option key
 * @param mixed  $value Option value
 * @return bool Success status
 */
if ( ! function_exists( 'mt_update_option' ) ) {
	function mt_update_option( string $key, $value ): bool {
		$wp_result = update_option( $key, $value );

		if ( mt_has_acf() ) {
			update_field( $key, $value, 'option' );
		}

		return $wp_result;
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

// === Subscribe to Calendar Helper Functions ===
if ( ! function_exists( 'mt_get_calendar_subscribe_url' ) ) {
	/**
	 * Get the calendar subscribe URL with optional filters
	 * Supports custom mosque calendar URL override
	 *
	 * @param array $args Optional filter parameters
	 * @return string Subscribe URL
	 */
	function mt_get_calendar_subscribe_url( array $args = array() ): string {
		// Check for mosque's custom subscribe URL first
		$custom_url = '';
		if ( mt_has_acf() ) {
			$custom_url = get_field( 'custom_subscribe_url', 'option' );
		} else {
			$custom_url = get_option( 'custom_subscribe_url', '' );
		}

		if ( ! empty( $custom_url ) && filter_var( $custom_url, FILTER_VALIDATE_URL ) ) {
			// Use mosque's existing calendar (may include events + prayers)
			return $custom_url;
		}

		// Fall back to auto-generated prayer times feed
		$base_url = get_site_url() . '/prayer-times/calendar.ics';

		if ( ! empty( $args ) ) {
			$query_string = http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
			$base_url    .= '?' . $query_string;
		}

		return $base_url;
	}
}

if ( ! function_exists( 'mt_get_google_subscribe_url' ) ) {
	/**
	 * Build a Google Calendar "Add by URL" subscribe link
	 *
	 * @param string $ics_url The ICS feed URL to subscribe to
	 * @return string Google Calendar subscribe URL
	 */
	function mt_get_google_subscribe_url( string $ics_url ): string {
		return 'https://calendar.google.com/calendar/r?cid=' . rawurlencode( $ics_url );
	}
}

if ( ! function_exists( 'mt_get_webcal_url' ) ) {
	/**
	 * Convert https:// URL to webcal:// for native calendar apps
	 *
	 * @param string $ics_url The ICS feed URL
	 * @return string Webcal URL for Apple Calendar, Outlook, etc.
	 */
	function mt_get_webcal_url( string $ics_url ): string {
		return preg_replace( '#^https?://#i', 'webcal://', $ics_url );
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
		$result                 = update_option( 'mosque_timetable_rows', $all, false );
		return $result !== false;
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

	function mt_set_pdf_for_month( int $month, int $year, string $pdf_url ): bool {
		return mt_save_pdf_for_month( $month, $pdf_url, $year );
	}

	function mt_remove_pdf_for_month( int $month, int $year ): bool {
		$month = max( 1, min( 12, (int) $month ) );
		$year  = (int) $year;

		if ( mt_has_acf() ) {
			$field_name = "daily_prayers_{$month}";
			$rows       = get_field( $field_name, 'option' ) ?: array();

			if ( is_array( $rows ) && isset( $rows[0] ) && is_array( $rows[0] ) ) {
				$rows[0]['pdf_url'] = '';
				return (bool) update_field( $field_name, $rows, 'option' );
			}
		}

		$option_name = sprintf( 'mt_pdf_%04d_%d', $year, $month );
		return (bool) delete_option( $option_name );
	}

	// Ensure Composer autoload is available for SimpleXLSX
	$mt_vendor = MOSQUE_TIMETABLE_PLUGIN_DIR . 'vendor/autoload.php';
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


} // end conditional wrapper from mt_clear_all_rows guard
