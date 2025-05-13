<?php
// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Database settings
define('DB_HOST', 'localhost');
define('DB_USER', 'trombose');
define('DB_PASS', 'blsae88');
define('DB_NAME', 'topdev');

// SCP settings
define('SCP_HOST', '37.97.174.134');
define('SCP_PORT', 22);
define('SCP_USERNAME', 'trombose');
define('SCP_PASSWORD', 'RySwamp8');

// Email settings
define('SMTP_HOST', 'vps.transip.email');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'ddcare@vps.transip.email');
define('SMTP_PASSWORD', 'gskvfkzqTRKfGbKQ');
define('SMTP_FROM_EMAIL', 'ddcare@ddcare.nl');
define('SMTP_FROM_NAME', 'Trombose.net webmaster');
?>
