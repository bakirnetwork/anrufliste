<?php
// php-components-configuration file

// MySql-settings
define( 'DB_NAME',     'bc_contact' );
define( 'DB_USER',     'root' );
define( 'DB_PASSWORD', 'test123' );
define( 'DB_HOST',     'localhost' );
define( 'DB_CHARSET',  'utf8' );

// MySQL database table prefix.
define( 'DB_PREFIX',   'bc_' );

// MySQL user table name (without prefix)
define( 'DB_USERS',    'users');

// load php-components files
require(ABSPATH . 'php-components.php');

?>
