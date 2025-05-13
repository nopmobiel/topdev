<?php
/**
 * Version Builder Script
 * This script automatically updates the version.php file
 * 
 * Usage:
 * - Run as is to bump patch version (2.0.1 -> 2.0.2)
 * - Run with --minor to bump minor version (2.0.1 -> 2.1.0)
 * - Run with --major to bump major version (2.0.1 -> 3.0.0)
 * - Run with --set=X.Y.Z to set specific version
 */

// Default version if not exists
$defaultVersion = '2.0.1';
$versionFile = __DIR__ . '/version.php';

// Check if version file exists
if (file_exists($versionFile)) {
    include $versionFile;
    $currentVersion = defined('APP_VERSION') ? APP_VERSION : $defaultVersion;
} else {
    $currentVersion = $defaultVersion;
}

// Parse arguments
$bumpType = 'patch'; // Default is patch
$newVersion = null;

if ($argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        if ($arg === '--minor') {
            $bumpType = 'minor';
        } elseif ($arg === '--major') {
            $bumpType = 'major';
        } elseif (strpos($arg, '--set=') === 0) {
            $newVersion = substr($arg, 6);
        }
    }
}

// If no specific version is set, calculate new version based on bump type
if ($newVersion === null) {
    $parts = explode('.', $currentVersion);
    if (count($parts) !== 3) {
        $parts = [2, 0, 1]; // Fallback if version format is invalid
    }
    
    if ($bumpType === 'patch') {
        $parts[2]++;
    } elseif ($bumpType === 'minor') {
        $parts[1]++;
        $parts[2] = 0;
    } elseif ($bumpType === 'major') {
        $parts[0]++;
        $parts[1] = 0;
        $parts[2] = 0;
    }
    
    $newVersion = implode('.', $parts);
}

// Current date in YYYY-MM-DD format
$date = date('Y-m-d');

// Create version file content
$content = "<?php
// Version information - automatically generated on {$date}
define('APP_VERSION', '{$newVersion}');
define('APP_VERSION_DATE', '{$date}');
define('APP_BUILD_TIME', '".time()."');
?>";

// Write to file
file_put_contents($versionFile, $content);

echo "Version updated to {$newVersion}\n"; 