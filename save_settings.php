<?php
/**
 * Persist Settings.php to volume
 * Run this ONCE after SMF install to save config for future deploys.
 * DELETE THIS FILE after running!
 */

$settings = __DIR__ . '/Settings.php';
$backup = __DIR__ . '/Settings_bak.php';
$configDir = __DIR__ . '/smf-config';

// Check if Settings.php is configured
if (!file_exists($settings) || !preg_match('/db_server/', file_get_contents($settings))) {
    die('<h1 style="color:red;">Error</h1><p>Settings.php is not configured. Run <a href="install.php">install.php</a> first.</p>');
}

// Ensure volume directory exists
if (!is_dir($configDir)) {
    mkdir($configDir, 0777, true);
}

// Copy to volume
$copied = copy($settings, $configDir . '/Settings.php');
if (file_exists($backup)) {
    copy($backup, $configDir . '/Settings_bak.php');
}

if ($copied) {
    echo '<html><head><title>Settings Saved</title></head><body>';
    echo '<h1 style="color:green;">✓ Settings.php Saved to Volume!</h1>';
    echo '<p>Your SMF configuration is now persisted. Container rebuilds will restore it automatically.</p>';
    echo '<p><strong style="color:red;">⚠️ DELETE THIS FILE NOW for security:</strong></p>';
    echo '<pre>rm save_settings.php</pre>';
    echo '<p><a href="index.php">Go to Forum →</a></p>';
    echo '</body></html>';
} else {
    echo '<h1 style="color:red;">Error</h1><p>Failed to copy Settings.php. Check volume permissions.</p>';
}
