<?php
/**
 * Mosque Timetable - Uninstall Script
 *
 * Runs when the plugin is deleted from WordPress admin.
 * Cleans up all plugin data from the database.
 *
 * @package MosqueTimetable
 * @since   3.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove all plugin options.
$option_prefixes = array(
	'mt_mosque_',
	'mt_daily_prayers_',
	'mt_month_pdf_',
	'mt_settings_',
	'mt_push_',
	'mt_terminology_',
	'mosque_timetable_',
);

foreach ( $option_prefixes as $prefix ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $prefix ) . '%'
		)
	);
}

// Remove scheduled cron events.
$cron_hooks = array(
	'mt_send_prayer_notifications',
	'mt_cleanup_expired_subscriptions',
	'mt_check_year_advancement',
);

foreach ( $cron_hooks as $hook ) {
	$timestamp = wp_next_scheduled( $hook );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
	}
}

// Clear any cached data.
wp_cache_flush();
