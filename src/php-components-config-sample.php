<?php

// php-components-configuration file

// MySql-settings
define('DB_NAME',     'your_db_name');
define('DB_USER',     'your_db_user');
define('DB_PASSWORD', 'your_db_password');
define('DB_HOST',     'your_db_host');
define('DB_CHARSET',  'utf8');

// MySQL database table prefix.
define('DB_PREFIX', 'bc_');

// MySQL user table name (without prefix)
define('DB_USERS', 'users');

// Slack Webhook used to send messages when calls get assigned
define('SLACK_WEBHOOK', 'http://your.slack.endpoint');

// load php-components files
require(ABSPATH . 'php-components.php');
