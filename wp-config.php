<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '2UlAVpo0+J29l7/98T0354F0thp5gFh/QSz1hsH7WZS0F4X8QoAVmEzBpt9DdfNDSxK9xLvYc81HSErF+EvheQ==');
define('SECURE_AUTH_KEY',  'MtTyB1SvblDVmzPBSWDzN9aB2zVsAmQqa47Y3iPUkjULKU8CWFQkP5oqAVKOppWt0TxVY4uuPTF0DEEJGg7+Sg==');
define('LOGGED_IN_KEY',    'c1lZHVGKhescO1inYiSENRJZqLi1JRoxihlpyCzGr5wqQbJc+NCUCqwg/tAYVCDr7UetGoZ+MradoGOCUk2ibA==');
define('NONCE_KEY',        'jO5p2N9Fk3Nhjiw8cBsIaSxb/eZIJgOUlmqophVk40cQpXCmPpmgHsiqpYvNmc0bp+J7WQIYmXolEQH14uJV+g==');
define('AUTH_SALT',        'siUMYhbzwH89cC9bhvwaLDhl/xTw7Bd0yJPtBQqTY1WjKTm65j3wBLsJrRuz9B6qm8cSDOUIc0oRd5301+o76Q==');
define('SECURE_AUTH_SALT', 'v+PNFFqa+g2TPX39BEpyaoBN7W2/+gxIu3xpXlLOPT2By457EvjueAotgQm16KpPswBaZPR2O9E3/aiOPOWORQ==');
define('LOGGED_IN_SALT',   'avn3sWRy5tBp4rnzhFHffM0l499moBLTqCJjqc0aPs/xfHTxGCffFC9d2AEA/iGHh1NHTYgIzDkXL/Bffu3EIg==');
define('NONCE_SALT',       'vl7d1XEECF0jj3VEd9uQoKB59oHrVquiXw3k2bLduK1uyR4eXKxTOIS//cITf34YI4A6hrqB20mwv+HWkNIN2g==');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';




/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
