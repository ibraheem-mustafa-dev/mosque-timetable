<?php

// BEGIN iThemes Security - Do not modify or remove this line
// iThemes Security Config Details: 2
define( 'DISALLOW_FILE_EDIT', true ); // Disable File Editor - Security > Settings > WordPress Tweaks > File Editor
// END iThemes Security - Do not modify or remove this line

/** WP 2FA plugin data encryption key. For more information please visit melapress.com */
define( 'WP2FA_ENCRYPT_KEY', '***REMOVED***' );

define( 'ITSEC_ENCRYPTION_KEY', '***REMOVED***' );

define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', '***REMOVED***' );

/** Database username */
define( 'DB_USER', '***REMOVED***' );

/** Database password */
define( 'DB_PASSWORD', '***REMOVED***' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          ';t_E(4]Y?$[c{gghmNm}zWg*G{[|%Rg?1U Q{s~XaJ<x#N!r}0f+wZjfz4?Q :{|' );
define( 'SECURE_AUTH_KEY',   '!NJ+e~atghY~_[?znA*1q5A8Dur9U-Ts:^^NCAveHYc{i,0=-0>ZK,/2u[^;*(^R' );
define( 'LOGGED_IN_KEY',     '`zo+e!XEi&aNhX~=f]iy$ly;{wVaDp8Em-cDw^84^*K *V>.G%0WEw5pXcUFsBOS' );
define( 'NONCE_KEY',         'f1LlU1DkS!<3:m):2A;LajlWnoRVMZjPH&re@a]XK|dONNOo$`Qu8g02^s4+b>L~' );
define( 'AUTH_SALT',         '?6rnEpz&y=119EVquhA`DhZ2Lz.#8C6!rs+heqL;FaK/FrK}D47->@9sjE(yuMr:' );
define( 'SECURE_AUTH_SALT',  '#UcDxb.y|i,^9#c]CGNwMeb;CM8lQ]+EIIN^%M2k`2_rpf*IVOUw_ Z{qUC8CYCl' );
define( 'LOGGED_IN_SALT',    '`+675J7/l`lby!<`4O>!,65EA>.o*NY=R8)},BV*,mZe3JX*&mU/A9<hv%Ku&8mi' );
define( 'NONCE_SALT',        'BIoxo-{#<;#_&gHhoh.TxP#F:Ymt<Z [<7-cYU:a<IY3%a[|2<8-fW-5x>3(3Q(7' );
define( 'WP_CACHE_KEY_SALT', 'sz4&a,iLqFzsUKeUdTGw&u*O^[oK`3K&euP2o+Ezd&kQ.BD-U-qmBL<rC1=0tAK[' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '2da942bea10c29d60f5b7a72fdb214bf' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
