<?php
/**
 * MOHAA Stats System - Master Installer (Idempotent)
 * 
 * This installer is designed to be run multiple times safely.
 * It will create missing tables, add missing columns, update hooks,
 * and ensure settings are correct without duplicating data.
 * 
 * Run from browser: http://your-forum/mohaa_install.php
 * Or from CLI: php -f /path/to/smf/mohaa_install.php
 * 
 * @package MohaaStats
 * @version 2.0.0
 */

// Standalone mode - find SSI.php
$ssi_paths = [
    dirname(__FILE__) . '/../../../SSI.php',      // install/ directory
    dirname(__FILE__) . '/../../SSI.php',         // Sources/MohaaStats/
    dirname(__FILE__) . '/../SSI.php',            // Sources/
    dirname(__FILE__) . '/SSI.php',               // root
    '/var/www/html/SSI.php',                      // Docker location
    '/var/www/smf/SSI.php',                       // common location
];

$ssi_found = false;
foreach ($ssi_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $ssi_found = true;
        break;
    }
}

if (!$ssi_found && !defined('SMF')) {
    die('<b>Error:</b> Could not find SSI.php. Please run from within SMF directory.');
}

global $smcFunc, $modSettings, $db_prefix, $sourcedir, $db_connection, $db_name;

// Verify database is selected (SSI.php should have done this via Settings.php)
if (empty($db_name)) {
    die('<b>Error:</b> Database name not set. Check your Settings.php configuration.');
}

// Ensure database is selected - fixes "No database selected" error
// SMF 2.1 may not auto-select the database in all scenarios
if (!empty($db_connection) && !empty($db_name)) {
    // Try to select database using mysqli
    if (is_object($db_connection) || is_resource($db_connection)) {
        @mysqli_select_db($db_connection, $db_name);
    }
}

// Double-check by running a USE query through SMF's abstraction
if (!empty($smcFunc['db_query']) && !empty($db_name)) {
    @$smcFunc['db_query']('', "USE `$db_name`", []);
}

// Load database packages functions (db_create_table, db_add_column, etc.)
// SSI.php only loads basic CRUD - package functions are in DbPackages-mysql.php
// We must require the file AND call db_packages_init() to load functions into $smcFunc
if (!empty($sourcedir) && file_exists($sourcedir . '/DbPackages-mysql.php')) {
    require_once($sourcedir . '/DbPackages-mysql.php');
    db_packages_init();
} elseif (file_exists('/var/www/html/Sources/DbPackages-mysql.php')) {
    require_once('/var/www/html/Sources/DbPackages-mysql.php');
    db_packages_init();
}

// Installer version - increment when schema changes 
define('MOHAA_INSTALLER_VERSION', '2.1.0');

// Output header
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    echo "<html><head><title>MOHAA Stats Installer</title>";
    echo "<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 40px; background: #1a1a2e; color: #eee; }
.container { max-width: 900px; margin: 0 auto; }
h1 { color: #4ecca3; }
h2 { color: #64b5f6; border-bottom: 1px solid #333; padding-bottom: 10px; margin-top: 30px; }
.success { color: #4ecca3; }
.error { color: #ff6b6b; }
.warning { color: #ffd93d; }
.info { color: #64b5f6; }
.skip { color: #888; }
pre { background: #0f0f1a; padding: 15px; border-radius: 8px; overflow-x: auto; }
.step { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #4ecca3; }
.step.skipped { border-left-color: #888; opacity: 0.7; }
.step.error { border-left-color: #ff6b6b; }
.step.warning { border-left-color: #ffd93d; }
.btn { display: inline-block; padding: 10px 20px; background: #4ecca3; color: #1a1a2e; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px; }
.btn:hover { background: #3db892; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #333; }
th { background: #0f0f1a; color: #64b5f6; }
</style></head><body><div class='container'>";
    echo "<h1>🎮 MOHAA Stats System - Master Installer v" . MOHAA_INSTALLER_VERSION . "</h1>";
    echo "<p class='info'>This installer is idempotent - safe to run multiple times.</p>";
}

function output($message, $type = 'success', $cli = false) {
    $prefixes = [
        'success' => '✓',
        'error' => '✗',
        'warning' => '⚠',
        'skip' => '○',
        'info' => '→',
    ];
    $prefix = $prefixes[$type] ?? '→';
    
    if ($cli) {
        echo "$prefix $message\n";
    } else {
        $class = $type === 'skip' ? 'step skipped' : ($type === 'error' ? 'step error' : ($type === 'warning' ? 'step warning' : 'step'));
        echo "<div class='$class'><span class='$type'>$prefix</span> $message</div>";
    }
}

function section($title, $cli = false) {
    if ($cli) {
        echo "\n=== $title ===\n";
    } else {
        echo "<h2>$title</h2>";
    }
}

$errors = [];
$stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

// ============================================================================
// STEP 1: Create/Update Database Tables
// ============================================================================
section("Step 1: Database Tables", $is_cli);

/**
 * Complete table definitions with all columns
 * When adding new columns, just add them here - installer will add missing ones
 */
$tables = [
    'mohaa_identities' => [
        'columns' => [
            'id_identity' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'id_member' => ['type' => 'mediumint', 'size' => 8, 'unsigned' => true, 'default' => 0],
            'player_guid' => ['type' => 'varchar', 'size' => 64, 'default' => ''],
            'player_name' => ['type' => 'varchar', 'size' => 255, 'default' => ''],
            'linked_date' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'verified' => ['type' => 'tinyint', 'size' => 1, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_identity']],
            ['name' => 'idx_member', 'type' => 'index', 'columns' => ['id_member']],
            ['name' => 'idx_guid', 'type' => 'unique', 'columns' => ['player_guid']],
        ],
    ],
    'mohaa_claim_codes' => [
        'columns' => [
            'id_claim' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'id_member' => ['type' => 'mediumint', 'size' => 8, 'unsigned' => true, 'default' => 0],
            'claim_code' => ['type' => 'varchar', 'size' => 16, 'default' => ''],
            'created_at' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'expires_at' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'used' => ['type' => 'tinyint', 'size' => 1, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_claim']],
            ['name' => 'idx_code', 'type' => 'unique', 'columns' => ['claim_code']],
            ['name' => 'idx_member', 'type' => 'index', 'columns' => ['id_member']],
        ],
    ],
    'mohaa_device_tokens' => [
        'columns' => [
            'id_token' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'id_member' => ['type' => 'mediumint', 'size' => 8, 'unsigned' => true, 'default' => 0],
            'user_code' => ['type' => 'varchar', 'size' => 16, 'default' => ''],
            'device_code' => ['type' => 'varchar', 'size' => 64, 'default' => ''],
            'created_at' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'expires_at' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'verified' => ['type' => 'tinyint', 'size' => 1, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_token']],
            ['name' => 'idx_user_code', 'type' => 'unique', 'columns' => ['user_code']],
            ['name' => 'idx_device_code', 'type' => 'index', 'columns' => ['device_code']],
        ],
    ],
    'mohaa_achievement_defs' => [
        'columns' => [
            'id_achievement' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'code' => ['type' => 'varchar', 'size' => 50, 'default' => ''],
            'name' => ['type' => 'varchar', 'size' => 255, 'default' => ''],
            'description' => ['type' => 'text', 'default' => null],
            'category' => ['type' => 'varchar', 'size' => 50, 'default' => 'basic'],
            'tier' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 1],
            'icon' => ['type' => 'varchar', 'size' => 50, 'default' => 'trophy'],
            'requirement_type' => ['type' => 'varchar', 'size' => 50, 'default' => ''],
            'requirement_value' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 1],
            'points' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 10],
            'is_hidden' => ['type' => 'tinyint', 'size' => 1, 'unsigned' => true, 'default' => 0],
            'sort_order' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_achievement']],
            ['name' => 'idx_code', 'type' => 'unique', 'columns' => ['code']],
        ],
    ],
    'mohaa_player_achievements' => [
        'columns' => [
            'id_unlock' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'id_member' => ['type' => 'mediumint', 'size' => 8, 'unsigned' => true, 'default' => 0],
            'player_guid' => ['type' => 'varchar', 'size' => 64, 'default' => ''],
            'id_achievement' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'unlocked_date' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'progress' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_unlock']],
            ['name' => 'idx_member', 'type' => 'index', 'columns' => ['id_member']],
            ['name' => 'idx_guid_ach', 'type' => 'unique', 'columns' => ['player_guid', 'id_achievement']],
        ],
    ],
    'mohaa_achievement_progress' => [
        'columns' => [
            'id_progress' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'id_member' => ['type' => 'mediumint', 'size' => 8, 'unsigned' => true, 'default' => 0],
            'id_achievement' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'current_progress' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'updated_at' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_progress']],
            ['name' => 'idx_member_ach', 'type' => 'unique', 'columns' => ['id_member', 'id_achievement']],
        ],
    ],
    'mohaa_teams' => [
        'columns' => [
            'id_team' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'team_name' => ['type' => 'varchar', 'size' => 255, 'default' => ''],
            'team_tag' => ['type' => 'varchar', 'size' => 10, 'default' => ''],
            'description' => ['type' => 'text', 'default' => null],
            'logo_url' => ['type' => 'varchar', 'size' => 255, 'default' => ''],
            'website' => ['type' => 'varchar', 'size' => 255, 'default' => ''],
            'id_captain' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'founded_date' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'varchar', 'size' => 20, 'default' => 'active'],
            'rating' => ['type' => 'int', 'size' => 10, 'default' => 1000],
            'wins' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'losses' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'recruiting' => ['type' => 'tinyint', 'size' => 1, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_team']],
            ['name' => 'idx_name', 'type' => 'unique', 'columns' => ['team_name']],
            ['name' => 'idx_status', 'type' => 'index', 'columns' => ['status']],
            ['name' => 'idx_rating', 'type' => 'index', 'columns' => ['rating']],
        ],
    ],
    'mohaa_team_members' => [
        'columns' => [
            'id_team' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'id_member' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'role' => ['type' => 'varchar', 'size' => 20, 'default' => 'member'],
            'joined_date' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'varchar', 'size' => 20, 'default' => 'active'],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_team', 'id_member']],
            ['name' => 'idx_member', 'type' => 'index', 'columns' => ['id_member']],
        ],
    ],
    'mohaa_team_invites' => [
        'columns' => [
            'id_invite' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'id_team' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'id_member' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'id_inviter' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'invite_type' => ['type' => 'varchar', 'size' => 20, 'default' => 'invite'],
            'status' => ['type' => 'varchar', 'size' => 20, 'default' => 'pending'],
            'created_date' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_invite']],
            ['name' => 'idx_team', 'type' => 'index', 'columns' => ['id_team']],
            ['name' => 'idx_member', 'type' => 'index', 'columns' => ['id_member']],
        ],
    ],
    'mohaa_team_matches' => [
        'columns' => [
            'id_match' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'id_team' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'id_opponent' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'match_date' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'result' => ['type' => 'varchar', 'size' => 10, 'default' => ''],
            'map' => ['type' => 'varchar', 'size' => 50, 'default' => ''],
            'score_us' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'score_them' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_match']],
            ['name' => 'idx_team', 'type' => 'index', 'columns' => ['id_team']],
        ],
    ],
    'mohaa_tournaments' => [
        'columns' => [
            'id_tournament' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'name' => ['type' => 'varchar', 'size' => 255, 'default' => ''],
            'description' => ['type' => 'text', 'default' => null],
            'format' => ['type' => 'varchar', 'size' => 20, 'default' => 'single_elim'],
            'game_type' => ['type' => 'varchar', 'size' => 50, 'default' => 'tdm'],
            'max_teams' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 16],
            'status' => ['type' => 'varchar', 'size' => 20, 'default' => 'open'],
            'tournament_start' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'tournament_end' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'prize_description' => ['type' => 'text', 'default' => null],
            'rules' => ['type' => 'text', 'default' => null],
            'created_by' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_tournament']],
            ['name' => 'idx_status', 'type' => 'index', 'columns' => ['status']],
        ],
    ],
    'mohaa_tournament_registrations' => [
        'columns' => [
            'id_tournament' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'id_team' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'seed' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'registration_date' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'varchar', 'size' => 20, 'default' => 'approved'],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_tournament', 'id_team']],
            ['name' => 'idx_team', 'type' => 'index', 'columns' => ['id_team']],
        ],
    ],
    'mohaa_tournament_matches' => [
        'columns' => [
            'id_match' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'auto' => true],
            'id_tournament' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'round' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 1],
            'bracket_group' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'id_team_a' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'id_team_b' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'score_a' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'score_b' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'winner_id' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
            'match_date' => ['type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0],
        ],
        'indexes' => [
            ['type' => 'primary', 'columns' => ['id_match']],
            ['name' => 'idx_tournament', 'type' => 'index', 'columns' => ['id_tournament']],
        ],
    ],
];

/**
 * Get existing columns for a table
 */
function getExistingColumns($tableName) {
    global $smcFunc, $db_prefix, $db_name;
    
    $columns = [];
    $result = $smcFunc['db_query']('', "SHOW COLUMNS FROM `{$db_name}`.{db_prefix}$tableName", []);
    while ($row = $smcFunc['db_fetch_assoc']($result)) {
        $columns[$row['Field']] = $row;
    }
    $smcFunc['db_free_result']($result);
    return $columns;
}

/**
 * Check if table exists
 */
function tableExists($tableName) {
    global $smcFunc, $db_prefix, $db_name;
    
    $result = $smcFunc['db_query']('', "SHOW TABLES FROM `{$db_name}` LIKE '{db_prefix}$tableName'", []);
    $exists = $smcFunc['db_num_rows']($result) > 0;
    $smcFunc['db_free_result']($result);
    return $exists;
}

/**
 * Build column definition SQL
 */
function buildColumnSQL($name, $def) {
    $sql = "`$name` ";
    
    switch ($def['type']) {
        case 'int':
        case 'mediumint':
        case 'tinyint':
            $sql .= strtoupper($def['type']) . '(' . ($def['size'] ?? 10) . ')';
            if (!empty($def['unsigned'])) $sql .= ' UNSIGNED';
            break;
        case 'varchar':
            $sql .= 'VARCHAR(' . ($def['size'] ?? 255) . ')';
            break;
        case 'text':
            $sql .= 'TEXT';
            break;
    }
    
    if (isset($def['default'])) {
        if ($def['default'] === null) {
            $sql .= ' DEFAULT NULL';
        } else {
            $sql .= " DEFAULT '" . addslashes($def['default']) . "'";
        }
    }
    
    if (!empty($def['auto'])) {
        $sql .= ' AUTO_INCREMENT';
    }
    
    return $sql;
}

// Force mode - drop and recreate all tables
$forceRebuild = isset($_GET['force']) || isset($_SERVER['argv']) && in_array('--force', $_SERVER['argv']);

// Process each table
foreach ($tables as $tableName => $tableSpec) {
    $fullTableName = str_replace('{db_prefix}', $db_prefix, '{db_prefix}' . $tableName);
    $needsRebuild = false;
    
    if (tableExists($tableName)) {
        // Table exists - check if schema matches
        $existingCols = getExistingColumns($tableName);
        $expectedCols = array_keys($tableSpec['columns']);
        
        // Check for missing columns (columns we expect but don't exist)
        $missingCols = [];
        foreach ($expectedCols as $colName) {
            if (!isset($existingCols[$colName])) {
                $missingCols[] = $colName;
            }
        }
        
        // If any expected columns are missing, we need to rebuild
        if (!empty($missingCols) || $forceRebuild) {
            $needsRebuild = true;
            // Drop the old table
            try {
                $smcFunc['db_query']('', "DROP TABLE IF EXISTS {db_prefix}$tableName", []);
                output("Dropped table <b>$tableName</b> (missing columns: " . implode(', ', $missingCols) . ")", 'warning', $is_cli);
            } catch (Exception $e) {
                output("Failed to drop table <b>$tableName</b>: " . $e->getMessage(), 'error', $is_cli);
                $errors[] = "Drop $tableName: " . $e->getMessage();
                $stats['errors']++;
                continue;
            }
        }
    }
    
    if (!tableExists($tableName) || $needsRebuild) {
        // Create new table
        $columns_arr = [];
        foreach ($tableSpec['columns'] as $colName => $colDef) {
            $columns_arr[] = ['name' => $colName] + $colDef;
        }
        
        try {
            $smcFunc['db_create_table']('{db_prefix}' . $tableName, $columns_arr, $tableSpec['indexes'], [], 'ignore');
            output("Created table: <b>$tableName</b>" . ($needsRebuild ? " (rebuilt)" : ""), 'success', $is_cli);
            $stats['created']++;
        } catch (Exception $e) {
            output("Failed to create table: <b>$tableName</b> - " . $e->getMessage(), 'error', $is_cli);
            $errors[] = "Table $tableName: " . $e->getMessage();
            $stats['errors']++;
        }
    } else {
        // Table exists with all expected columns - up to date
        output("Table <b>$tableName</b> already up to date", 'skip', $is_cli);
        $stats['skipped']++;
    }
}

// ============================================================================
// STEP 2: Register Hooks (Idempotent)
// ============================================================================
section("Step 2: Register Hooks", $is_cli);

$hooks = [
    'integrate_actions' => [
        'MohaaPlayers.php|MohaaPlayers_Actions',
        'MohaaServers.php|MohaaServers_Actions',
        'MohaaAchievements.php|MohaaAchievements_Actions',
        'MohaaTournaments.php|MohaaTournaments_Actions',
        'MohaaTeams.php|MohaaTeams_Actions',
        'MohaaPredictions.php|MohaaPredictions_Actions',
        'MohaaComparison.php|MohaaComparison_Actions',
    ],
    'integrate_menu_buttons' => [
        'MohaaPlayers.php|MohaaPlayers_MenuButtons',
    ],
    'integrate_profile_areas' => [
        'MohaaPlayers.php|MohaaPlayers_ProfileAreas',
        'MohaaAchievements.php|MohaaAchievements_ProfileAreas',
        'MohaaTeams.php|MohaaTeams_ProfileAreas',
    ],
    'integrate_admin_areas' => [
        'MohaaTournaments.php|MohaaTournaments_AdminAreas',
    ],
];

/**
 * Register hook only if not already registered (prevents duplicates)
 */
function addHookIfMissing($hook, $function) {
    global $modSettings;
    
    $existing = !empty($modSettings[$hook]) ? explode(',', $modSettings[$hook]) : [];
    
    if (!in_array($function, $existing)) {
        add_integration_function($hook, $function, true);
        return true;
    }
    return false;
}

foreach ($hooks as $hook => $functions) {
    if (!is_array($functions)) {
        $functions = [$functions];
    }
    
    foreach ($functions as $function) {
        if (addHookIfMissing($hook, $function)) {
            output("Registered hook: <b>$hook</b> → $function", 'success', $is_cli);
            $stats['created']++;
        } else {
            output("Hook already exists: <b>$hook</b> → $function", 'skip', $is_cli);
            $stats['skipped']++;
        }
    }
}

// ============================================================================
// STEP 3: Configure Settings (Idempotent - updateSettings handles this)
// ============================================================================
section("Step 3: Configure Settings", $is_cli);

$settings = [
    'mohaa_stats_enabled' => 1,
    'mohaa_api_url' => 'http://opm-stats-api:8080/api/v1',  // Docker internal
    'mohaa_api_timeout' => 10,
    'mohaa_cache_duration' => 60,
    'mohaa_live_cache_duration' => 10,
    'mohaa_rate_limit' => 100,
    'mohaa_leaderboard_limit' => 25,
    'mohaa_recent_matches_limit' => 10,
    'mohaa_show_heatmaps' => 1,
    'mohaa_show_achievements' => 1,
    'mohaa_show_in_profile' => 1,
    'mohaa_allow_linking' => 1,
    'mohaa_max_identities' => 3,
    'mohaa_claim_expiry' => 10,
    'mohaa_token_expiry' => 10,
    'mohaa_installer_version' => MOHAA_INSTALLER_VERSION,
];

// Only update settings that are missing or need defaults
$updated = [];
$skipped = [];
foreach ($settings as $key => $value) {
    if (!isset($modSettings[$key]) || $key === 'mohaa_installer_version') {
        $updated[$key] = $value;
    } else {
        $skipped[] = $key;
    }
}

if (!empty($updated)) {
    updateSettings($updated);
    foreach ($updated as $key => $value) {
        output("Set: <b>$key</b> = $value", 'success', $is_cli);
    }
    $stats['updated'] += count($updated);
}

if (!empty($skipped)) {
    output("Settings already configured: " . count($skipped) . " settings", 'skip', $is_cli);
    $stats['skipped'] += count($skipped);
}

// ============================================================================
// STEP 4: Seed Achievement Definitions (Upsert pattern)
// ============================================================================
section("Step 4: Seed Achievement Definitions", $is_cli);

$achievements = [
    ['code' => 'surgical', 'name' => 'The Surgeon', 'description' => 'Achieve 100 Headshots in a single tournament event.', 'category' => 'tactical', 'tier' => 4, 'icon' => 'surgical', 'requirement_type' => 'headshots_tournament', 'requirement_value' => 100, 'points' => 500],
    ['code' => 'unstoppable', 'name' => 'Unstoppable Force', 'description' => 'Win 10 matches in a row without a single loss.', 'category' => 'dedication', 'tier' => 5, 'icon' => 'unstoppable', 'requirement_type' => 'win_streak', 'requirement_value' => 10, 'points' => 1000],
    ['code' => 'survivalist', 'name' => 'Survivalist', 'description' => 'Complete a full match with < 10% HP remaining and 0 deaths.', 'category' => 'hardcore', 'tier' => 3, 'icon' => 'survivalist', 'requirement_type' => 'hp_survival', 'requirement_value' => 1, 'points' => 250],
    ['code' => 'first_blood', 'name' => 'First Blood', 'description' => 'Get your first kill.', 'category' => 'basic', 'tier' => 1, 'icon' => 'blood', 'requirement_type' => 'kills', 'requirement_value' => 1, 'points' => 10],
    ['code' => 'centurion', 'name' => 'Centurion', 'description' => 'Reach 100 kills.', 'category' => 'basic', 'tier' => 2, 'icon' => 'sword', 'requirement_type' => 'kills', 'requirement_value' => 100, 'points' => 50],
    ['code' => 'thousand_souls', 'name' => '1000 Souls', 'description' => 'Reach 1000 kills.', 'category' => 'basic', 'tier' => 3, 'icon' => 'skull', 'requirement_type' => 'kills', 'requirement_value' => 1000, 'points' => 200],
    ['code' => 'headhunter', 'name' => 'Headhunter', 'description' => 'Get 50 headshots.', 'category' => 'precision', 'tier' => 2, 'icon' => 'target', 'requirement_type' => 'headshots', 'requirement_value' => 50, 'points' => 75],
    ['code' => 'sharpshooter', 'name' => 'Sharpshooter', 'description' => 'Achieve 50% accuracy over 1000 shots.', 'category' => 'precision', 'tier' => 3, 'icon' => 'crosshair', 'requirement_type' => 'accuracy', 'requirement_value' => 50, 'points' => 150],
    ['code' => 'marathon', 'name' => 'Marathon Runner', 'description' => 'Run 100 kilometers total.', 'category' => 'movement', 'tier' => 2, 'icon' => 'runner', 'requirement_type' => 'distance', 'requirement_value' => 100000, 'points' => 50],
    ['code' => 'veteran', 'name' => 'Veteran', 'description' => 'Play for 24 hours total.', 'category' => 'dedication', 'tier' => 2, 'icon' => 'medal', 'requirement_type' => 'playtime', 'requirement_value' => 86400, 'points' => 100],
    ['code' => 'ghost', 'name' => 'Ghost', 'description' => 'Complete 5 matches with 0 deaths.', 'category' => 'hardcore', 'tier' => 4, 'icon' => 'ghost', 'requirement_type' => 'no_death_matches', 'requirement_value' => 5, 'points' => 300],
    ['code' => 'rampage', 'name' => 'Rampage', 'description' => 'Get 10 kills without dying.', 'category' => 'combat', 'tier' => 3, 'icon' => 'fire', 'requirement_type' => 'kill_streak', 'requirement_value' => 10, 'points' => 150],
];

$inserted = 0;
foreach ($achievements as $ach) {
    // Use REPLACE to upsert (update if exists, insert if not)
    $smcFunc['db_insert']('replace',
        '{db_prefix}mohaa_achievement_defs',
        [
            'code' => 'string', 'name' => 'string', 'description' => 'string', 
            'category' => 'string', 'tier' => 'int', 'icon' => 'string', 
            'requirement_type' => 'string', 'requirement_value' => 'int', 'points' => 'int'
        ],
        [
            $ach['code'], $ach['name'], $ach['description'], $ach['category'],
            $ach['tier'], $ach['icon'], $ach['requirement_type'], 
            $ach['requirement_value'], $ach['points']
        ],
        ['code']
    );
    $inserted++;
}

output("Synced <b>$inserted</b> achievement definitions (insert or update)", 'success', $is_cli);
$stats['updated']++;

// ============================================================================
// STEP 5: Verify API Connection
// ============================================================================
section("Step 5: Verify API Connection", $is_cli);

$api_url = $modSettings['mohaa_api_url'] ?? $settings['mohaa_api_url'];
$health_url = str_replace('/api/v1', '/health', $api_url);

$ch = curl_init($health_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    output("API Connection: <b>OK</b> ($health_url)", 'success', $is_cli);
} else {
    output("API Connection: <b>UNAVAILABLE</b> ($health_url) - HTTP $http_code", 'warning', $is_cli);
    output("This is OK if running before API stack is deployed. Configure API URL in Admin settings.", 'info', $is_cli);
}

// ============================================================================
// Summary
// ============================================================================
section("Installation Summary", $is_cli);

if ($is_cli) {
    echo "\nResults:\n";
    echo "  Created: {$stats['created']}\n";
    echo "  Updated: {$stats['updated']}\n";
    echo "  Skipped: {$stats['skipped']}\n";
    echo "  Errors:  {$stats['errors']}\n";
    
    if (empty($errors)) {
        echo "\n✓ Installation complete!\n";
    } else {
        echo "\n⚠ Completed with errors:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
} else {
    echo "
    <table>
        <tr><th>Metric</th><th>Count</th></tr>
        <tr><td>Created</td><td>{$stats['created']}</td></tr>
        <tr><td>Updated</td><td>{$stats['updated']}</td></tr>
        <tr><td>Skipped (already exists)</td><td>{$stats['skipped']}</td></tr>
        <tr><td>Errors</td><td>{$stats['errors']}</td></tr>
    </table>";
    
    if (empty($errors)) {
        echo "<div class='step' style='border-color: #4ecca3; background: #1e3a2f;'>";
        echo "<span class='success'>🎉 Installation Complete!</span><br><br>";
        echo "Installer Version: " . MOHAA_INSTALLER_VERSION . "<br><br>";
        echo "<b>Quick Links:</b><br>";
        echo "<a href='index.php?action=mohaadashboard' class='btn'>Stats Dashboard</a>";
        echo "<a href='index.php?action=mohaaachievements' class='btn'>Achievements</a>";
        echo "<a href='index.php?action=mohaatournaments' class='btn'>Tournaments</a>";
        echo "</div>";
    } else {
        echo "<div class='step error'>";
        echo "<span class='error'>⚠ Completed with errors:</span><br>";
        foreach ($errors as $error) {
            echo "- $error<br>";
        }
        echo "</div>";
    }
    
    echo "</div></body></html>";
}
?>
