<?php
/**
 * Quick Admin Status Check
 * Access: https://mosquewebdesign.com/wp-content/plugins/mosque-timetable/quick-admin-check.php
 */

require_once '../../../wp-config.php';

echo '<h1>Quick Admin Status Check</h1>';

// Check plugin activation
$active_plugins = get_option( 'active_plugins', array() );
$plugin_active  = in_array( 'mosque-timetable/mosque-timetable.php', $active_plugins, true );

echo '<h2>Plugin Status</h2>';
echo $plugin_active ? '✅ ACTIVE' : '❌ NOT ACTIVE';

// Check if we can access admin URLs
echo '<h2>Test Admin URLs</h2>';
$admin_urls = array(
	'wp-admin/'                                 => 'WordPress Admin Dashboard',
	'wp-admin/admin.php?page=mosque-main'       => 'Mosque Main Page',
	'wp-admin/admin.php?page=mosque-timetables' => 'Mosque Timetables Page',
	'wp-admin/admin.php?page=mosque-settings'   => 'Mosque Settings Page',
);

foreach ( $admin_urls as $url => $name ) {
	$full_url = home_url( $url );
	echo '<p><a href="' . esc_url( $full_url ) . '" target="_blank">' . esc_html( $name ) . '</a></p>';
}

// Check user capabilities
echo '<h2>Current User</h2>';
if ( is_user_logged_in() ) {
	$user = wp_get_current_user();
	echo 'Logged in as: ' . esc_html( $user->display_name ) . ' (ID: ' . esc_html( $user->ID ) . ')<br>';
	echo 'Can edit posts: ' . ( current_user_can( 'edit_posts' ) ? 'YES' : 'NO' ) . '<br>';
	echo 'Can manage options: ' . ( current_user_can( 'manage_options' ) ? 'YES' : 'NO' ) . '<br>';
} else {
	echo 'Not logged in - <a href="' . esc_url( wp_login_url() ) . '">Login here</a>';
}
