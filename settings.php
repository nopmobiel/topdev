<?php
// Global error suppression - MUST be first
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Multi-environment .env loading
$envFiles = [
    __DIR__ . '/.env',        // Local development (in project root)
    __DIR__ . '/../.env'      // Production (in home folder)
];

$envLoaded = false;
foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        $envVars = parse_ini_file($envFile);
        foreach ($envVars as $key => $value) {
            putenv("$key=$value");
        }
        $envLoaded = true;
        break; // Stop after finding the first .env file
    }
}

// Error reporting settings for PRODUCTION
ini_set('log_errors', 1);            // Still log errors to file
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Database settings
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'trombose');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'topdev');

// SCP settings
define('SCP_HOST', getenv('SCP_HOST') ?: '');
define('SCP_PORT', getenv('SCP_PORT') ?: 22);
define('SCP_USERNAME', getenv('SCP_USERNAME') ?: 'trombose');
define('SCP_PASSWORD', getenv('SCP_PASSWORD') ?: '');

// Email settings
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'vps.transip.email');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: '');
?>
