<?php

// configuration file for bakir-contact system

// prefix already set in php-components-conf.php

// absolute path to php-components directory
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/vendor/extendgears/php-components/');

// load php-components
require(__DIR__ . '/php-components-config.php');

// set constants for custom tables (without prefix)
define('DB_CALLS', 'calls');
define('DB_ASSIGNMENTS', 'assignments');

// load sql files for custom tables
require('sql_calls.php');
require('sql_assignments.php');
