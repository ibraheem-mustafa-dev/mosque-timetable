<?php
/**
 * Emergency Admin User Creation Script
 * SECURITY WARNING: Delete this file after use!
 * Access: https://mosquewebdesign.com/wp-content/plugins/mosque-timetable/create-admin.php
 */

// Load WordPress
require_once '../../../wp-config.php';

// Check if user already exists
$username = 'mosque_admin';
$user     = get_user_by( 'login', $username );

if ( $user ) {
	echo 'User "' . esc_html( $username ) . '" already exists. User ID: ' . esc_html( $user->ID );
} else {
	// Create new admin user
	$password = wp_generate_password( 12, false );

	$user_id = wp_create_user(
		$username,                    // Username
		$password,                    // Password
		'admin@mosquewebdesign.com'   // Email
	);

	if ( is_wp_error( $user_id ) ) {
		echo 'Error creating user: ' . esc_html( $user_id->get_error_message() );
	} else {
		// Make user administrator
		$user = new WP_User( $user_id );
		$user->set_role( 'administrator' );

		echo '<h2>Admin User Created Successfully!</h2>';
		echo '<p><strong>Username:</strong> ' . esc_html( $username ) . '</p>';
		echo '<p><strong>Password:</strong> ' . esc_html( $password ) . '</p>';
		echo "<p><strong>Login URL:</strong> <a href='/wp-admin/'>https://mosquewebdesign.com/wp-admin/</a></p>";
		echo '<hr>';
		echo "<p style='color: red;'><strong>IMPORTANT:</strong> Delete this file immediately after use for security!</p>";
	}
}

echo '<hr>';
echo "<p><a href='/wp-admin/'>Go to WordPress Admin</a></p>";
