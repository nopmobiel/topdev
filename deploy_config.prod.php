<?php
/**
 * Production Configuration Helper
 * 
 * Include this file at the TOP of index.php in production to automatically 
 * configure the correct session settings for your production environment.
 */

// Production settings
ini_set('session.cookie_domain', 'top.trombose.net');
ini_set('session.cookie_secure', '1'); // Force HTTPS for cookies
error_log('Applied production session settings for top.trombose.net');

// Set common secure session settings
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', '3600');

// Ensure session directory exists and is writable
$sessionPath = sys_get_temp_dir() . '/php_sessions';
if (!file_exists($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
ini_set('session.save_path', $sessionPath);
?> 