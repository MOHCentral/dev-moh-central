<?php
/**
 * MOHAA Stats Plugin Installer
 * 
 * Run this ONCE after SMF installation to register plugin hooks.
 * Access via: http://your-site/mohaa_install.php
 * DELETE THIS FILE after installation!
 */

// Bootstrap SMF
require_once(__DIR__ . '/Settings.php');
require_once(__DIR__ . '/Sources/Subs.php');
require_once(__DIR__ . '/Sources/Load.php');
require_once(__DIR__ . '/Sources/Subs-Db-mysql.php');

// Initialize database connection
loadDatabase();

// Check if already installed
$request = $smcFunc['db_query']('', '
    SELECT value FROM {db_prefix}settings WHERE variable = {string:var}',
    ['var' => 'mohaa_stats_installed']
);
$row = $smcFunc['db_fetch_assoc']($request);
$smcFunc['db_free_result']($request);

if (!empty($row['value'])) {
    die('<h1>MOHAA Stats Already Installed</h1><p>Delete this file for security.</p>');
}

// Register integration hooks
$hooks = [
    'integrate_pre_include' => '$sourcedir/MohaaStats/MohaaStats.php',
    'integrate_actions' => 'MohaaStats_Actions',
    'integrate_menu_buttons' => 'MohaaStats_MenuButtons',
    'integrate_admin_areas' => 'MohaaStats_AdminAreas',
];

foreach ($hooks as $hook => $function) {
    // Check if hook already exists
    $request = $smcFunc['db_query']('', '
        SELECT value FROM {db_prefix}settings WHERE variable = {string:hook}',
        ['hook' => $hook]
    );
    $existing = $smcFunc['db_fetch_assoc']($request);
    $smcFunc['db_free_result']($request);
    
    if ($existing) {
        // Append to existing hooks if not already there
        $current = $existing['value'];
        if (strpos($current, $function) === false) {
            $new_value = $current . ',' . $function;
            $smcFunc['db_query']('', '
                UPDATE {db_prefix}settings SET value = {string:val} WHERE variable = {string:hook}',
                ['val' => $new_value, 'hook' => $hook]
            );
        }
    } else {
        // Insert new hook
        $smcFunc['db_insert']('replace',
            '{db_prefix}settings',
            ['variable' => 'string', 'value' => 'string'],
            [$hook, $function],
            ['variable']
        );
    }
}

// Insert plugin settings
$settings = [
    ['mohaa_stats_installed', '1'],
    ['mohaa_stats_enabled', '1'],
    ['mohaa_stats_api_url', 'http://77.42.64.214:8084/api/v1'],
];

foreach ($settings as $setting) {
    $smcFunc['db_insert']('replace',
        '{db_prefix}settings',
        ['variable' => 'string', 'value' => 'string'],
        $setting,
        ['variable']
    );
}

// Create plugin tables
$tables_sql = [
    "CREATE TABLE IF NOT EXISTS {db_prefix}mohaa_identities (
        id_identity INT AUTO_INCREMENT PRIMARY KEY,
        id_member INT NOT NULL,
        player_guid VARCHAR(64) NOT NULL UNIQUE,
        player_name VARCHAR(100),
        linked_date INT UNSIGNED DEFAULT 0,
        verified TINYINT(1) DEFAULT 0,
        INDEX idx_member (id_member)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS {db_prefix}mohaa_claim_codes (
        id_claim INT AUTO_INCREMENT PRIMARY KEY,
        id_member INT NOT NULL,
        claim_code VARCHAR(16) NOT NULL UNIQUE,
        created_at INT UNSIGNED DEFAULT 0,
        expires_at INT UNSIGNED DEFAULT 0,
        used TINYINT(1) DEFAULT 0,
        INDEX idx_member (id_member)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS {db_prefix}mohaa_device_tokens (
        id_token INT AUTO_INCREMENT PRIMARY KEY,
        id_member INT NOT NULL,
        user_code VARCHAR(16) NOT NULL UNIQUE,
        device_code VARCHAR(64),
        created_at INT UNSIGNED DEFAULT 0,
        expires_at INT UNSIGNED DEFAULT 0,
        verified TINYINT(1) DEFAULT 0,
        INDEX idx_device (device_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables_sql as $sql) {
    $smcFunc['db_query']('', $sql, []);
}

// Clear cache
$cache_dir = __DIR__ . '/cache';
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '/*.php');
    foreach ($files as $file) {
        @unlink($file);
    }
}

// Persist Settings.php to volume so it survives container rebuilds
$config_dir = __DIR__ . '/smf-config';
if (is_dir($config_dir)) {
    if (file_exists(__DIR__ . '/Settings.php') && !is_link(__DIR__ . '/Settings.php')) {
        copy(__DIR__ . '/Settings.php', $config_dir . '/Settings.php');
        if (file_exists(__DIR__ . '/Settings_bak.php')) {
            copy(__DIR__ . '/Settings_bak.php', $config_dir . '/Settings_bak.php');
        }
        echo '<li>Settings.php backed up to persistent volume</li>';
    }
}

echo '<html><head><title>MOHAA Stats Installed</title></head><body>';
echo '<h1 style="color: green;">✓ MOHAA Stats Plugin Installed Successfully!</h1>';
echo '<ul>';
echo '<li>Integration hooks registered</li>';
echo '<li>Plugin tables created</li>';
echo '<li>Cache cleared</li>';
echo '</ul>';
echo '<p><strong style="color: red;">⚠️ DELETE THIS FILE NOW:</strong> <code>rm mohaa_install.php</code></p>';
echo '<p><a href="index.php">Go to Forum →</a></p>';
echo '</body></html>';
