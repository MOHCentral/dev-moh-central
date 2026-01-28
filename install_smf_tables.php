<?php
/**
 * SMF Headless Installer - Creates SMF tables using official schema
 * Run from CLI: php /var/www/html/install_smf_tables.php
 */
error_reporting(E_ALL);
require_once('/var/www/html/Settings.php');

echo "=== SMF Headless Installer ===\n";
echo "Database: $db_name @ $db_server\n";

$conn = new mysqli($db_server, $db_user, $db_passwd, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
echo "Connected to MySQL.\n";

// Check if already installed
mysqli_report(MYSQLI_REPORT_OFF);
$result = @$conn->query("SELECT COUNT(*) as cnt FROM {$db_prefix}members");
if ($result && $row = $result->fetch_assoc()) {
    if ($row['cnt'] > 0) {
        echo "SMF already installed ({$row['cnt']} members). Skipping table creation.\n";
        $conn->close();
        exit(0);
    }
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Load official SMF SQL
$sqlFile = '/var/www/html/install_2-1_mysql.sql';
if (!file_exists($sqlFile)) {
    die("ERROR: Cannot find $sqlFile\n");
}

echo "Loading official SMF schema from $sqlFile...\n";
$sql = file_get_contents($sqlFile);

// Replace placeholders
$engine = 'InnoDB';
$sql = str_replace('{$db_prefix}', $db_prefix, $sql);
$sql = str_replace('{$engine}', $engine, $sql);

// Split by statement (CREATE TABLE, INSERT, etc.)
// Remove comments and empty lines
$statements = [];
$lines = explode("\n", $sql);
$currentStatement = '';
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || $line[0] === '#' || strpos($line, '---') === 0) {
        continue;
    }
    $currentStatement .= ' ' . $line;
    if (substr($line, -1) === ';') {
        $statements[] = trim($currentStatement);
        $currentStatement = '';
    }
}

echo "Executing " . count($statements) . " SQL statements...\n";
$success = 0;
$errors = 0;
foreach ($statements as $stmt) {
    if (empty(trim($stmt))) continue;
    try {
        if ($conn->query($stmt)) {
            $success++;
            if ($success % 20 === 0) echo ".";
        }
    } catch (Exception $e) {
        // Ignore "already exists" errors
        if (strpos($e->getMessage(), 'already exists') === false) {
            $errors++;
        }
    }
}
echo "\n";
echo "Executed: $success statements, Errors: $errors\n";

// Create admin user
echo "Creating admin user...\n";
$admin_user = 'admin';
$admin_email = getenv('SMF_ADMIN_EMAIL') ?: 'admin@mohcentral.com';
$admin_pass_raw = getenv('SMF_ADMIN_PASS') ?: 'admin123';
$admin_pass = hash('sha256', strtolower($admin_user) . $admin_pass_raw);
$now = time();

$stmt = $conn->prepare("INSERT IGNORE INTO {$db_prefix}members 
    (id_member, member_name, real_name, passwd, email_address, date_registered, id_group, is_activated, buddy_list, pm_ignore_list, signature, ignore_boards) 
    VALUES (1, ?, ?, ?, ?, ?, 1, 1, '', '', '', '')");
$stmt->bind_param('ssssi', $admin_user, $admin_user, $admin_pass, $admin_email, $now);
$stmt->execute();

// Update version so SMF doesn't complain
$conn->query("REPLACE INTO {$db_prefix}settings (variable, value) VALUES ('smfVersion', '2.1.6')");

// Create default category/board if needed
$conn->query("INSERT IGNORE INTO {$db_prefix}categories (id_cat, cat_order, name) VALUES (1, 0, 'General')");
$conn->query("INSERT IGNORE INTO {$db_prefix}boards (id_board, id_cat, name, description, member_groups) VALUES (1, 1, 'General Discussion', 'Talk about anything.', '-1,0,2')");

// Essential settings - fix placeholders and set required values
$attachdir = json_encode([1 => '/var/www/html/attachments']);
$settings = [
    ['latestMember', '1'],
    ['latestRealName', 'admin'],
    ['totalMembers', '1'],
    ['totalTopics', '0'],
    ['totalMessages', '0'],
    ['settings_updated', time()],
    // Fix placeholders from install SQL
    ['attachmentUploadDir', $attachdir],
    ['attachment_basedirectories', json_encode([])],
    ['currentAttachmentUploadDir', '1'],
    ['boarddir', '/var/www/html'],
    ['boardurl', getenv('SMF_URL') ?: 'http://localhost:8083'],
    ['sourcedir', '/var/www/html/Sources'],
    ['cachedir', '/var/www/html/cache'],
    ['packagesdir', '/var/www/html/Packages'],
    ['tasksdir', '/var/www/html/Sources/Tasks'],
    ['avatar_url', (getenv('SMF_URL') ?: 'http://localhost:8083') . '/avatars'],
    ['avatar_directory', '/var/www/html/avatars'],
    ['smileys_url', (getenv('SMF_URL') ?: 'http://localhost:8083') . '/Smileys'],
    ['smileys_dir', '/var/www/html/Smileys'],
    ['language', 'english'],
    ['theme_guests', '1'],
    ['theme_default', '1'],
    ['knownThemes', '1'],
];
$stmtS = $conn->prepare("REPLACE INTO {$db_prefix}settings (variable, value) VALUES (?, ?)");
foreach ($settings as $s) {
    $stmtS->bind_param('ss', $s[0], $s[1]);
    $stmtS->execute();
}

// Fix theme placeholders
$boardurl = getenv('SMF_URL') ?: 'http://localhost:8083';
$themeUpdates = [
    ['images_url', $boardurl . '/Themes/default/images'],
    ['name', 'SMF Default Theme - Curve2'],
    ['theme_url', $boardurl . '/Themes/default'],
    ['theme_dir', '/var/www/html/Themes/default'],
];
$stmtT = $conn->prepare("UPDATE {$db_prefix}themes SET value = ? WHERE id_theme = 1 AND id_member = 0 AND variable = ?");
foreach ($themeUpdates as $t) {
    $stmtT->bind_param('ss', $t[1], $t[0]);
    $stmtT->execute();
}
echo "Fixed theme settings.\n";

echo "\n=== SMF Installation Complete ===\n";
echo "Admin: $admin_user / $admin_pass_raw\n";
$conn->close();
