<?php
/**
 * SMF Headless Installer - Creates core SMF tables without web interface
 * Run from CLI: php /var/www/html/install_smf_tables.php
 */
error_reporting(E_ALL);
require_once('/var/www/html/Settings.php');

echo "=== SMF Headless Installer ===\n";
echo "Database: $db_name @ $db_server\n";

$conn = new mysqli($db_server, $db_user, $db_passwd, $db_name);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error . "\n");
echo "Connected to MySQL.\n";

// Check if already installed
$result = $conn->query("SELECT COUNT(*) as cnt FROM {$db_prefix}members");
if ($result && $row = $result->fetch_assoc()) {
    if ($row['cnt'] > 0) {
        echo "SMF already installed. Skipping.\n";
        exit(0);
    }
}

echo "Creating SMF core tables...\n";

$tables = [
    "CREATE TABLE IF NOT EXISTS {$db_prefix}settings (variable VARCHAR(255) PRIMARY KEY, value TEXT NOT NULL) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}members (
        id_member MEDIUMINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        member_name VARCHAR(80) NOT NULL DEFAULT '',
        date_registered INT UNSIGNED NOT NULL DEFAULT 0,
        posts MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
        id_group SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        lngfile VARCHAR(255) NOT NULL DEFAULT '',
        last_login INT UNSIGNED NOT NULL DEFAULT 0,
        real_name VARCHAR(255) NOT NULL DEFAULT '',
        passwd VARCHAR(64) NOT NULL DEFAULT '',
        email_address VARCHAR(255) NOT NULL DEFAULT '',
        personal_text VARCHAR(255) NOT NULL DEFAULT '',
        birthdate DATE NOT NULL DEFAULT '1004-01-01',
        website_title VARCHAR(255) NOT NULL DEFAULT '',
        website_url VARCHAR(255) NOT NULL DEFAULT '',
        signature TEXT,
        avatar VARCHAR(255) NOT NULL DEFAULT '',
        member_ip VARCHAR(255) NOT NULL DEFAULT '',
        is_activated TINYINT UNSIGNED NOT NULL DEFAULT 1,
        validation_code VARCHAR(10) NOT NULL DEFAULT '',
        additional_groups VARCHAR(255) NOT NULL DEFAULT '',
        total_time_logged_in INT UNSIGNED NOT NULL DEFAULT 0,
        password_salt VARCHAR(255) NOT NULL DEFAULT '',
        timezone VARCHAR(80) NOT NULL DEFAULT '',
        buddy_list TEXT,
        pm_ignore_list TEXT,
        ignore_boards TEXT,
        INDEX idx_member_name (member_name),
        INDEX idx_real_name (real_name),
        INDEX idx_email_address (email_address)
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}membergroups (
        id_group SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(80) NOT NULL DEFAULT '',
        description TEXT,
        online_color VARCHAR(20) NOT NULL DEFAULT '',
        min_posts MEDIUMINT NOT NULL DEFAULT -1,
        icons VARCHAR(255) NOT NULL DEFAULT '',
        group_type TINYINT NOT NULL DEFAULT 0,
        hidden TINYINT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}boards (
        id_board SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_cat TINYINT UNSIGNED NOT NULL DEFAULT 0,
        name VARCHAR(255) NOT NULL DEFAULT '',
        description TEXT,
        num_topics MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
        num_posts MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
        member_groups VARCHAR(255) NOT NULL DEFAULT '-1,0'
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}categories (
        id_cat TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        cat_order TINYINT NOT NULL DEFAULT 0,
        name VARCHAR(255) NOT NULL DEFAULT '',
        description TEXT
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}messages (
        id_msg INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_topic MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
        id_board SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        poster_time INT UNSIGNED NOT NULL DEFAULT 0,
        id_member MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
        subject VARCHAR(255) NOT NULL DEFAULT '',
        poster_name VARCHAR(255) NOT NULL DEFAULT '',
        poster_email VARCHAR(255) NOT NULL DEFAULT '',
        poster_ip VARCHAR(255) NOT NULL DEFAULT '',
        body TEXT,
        approved TINYINT NOT NULL DEFAULT 1,
        INDEX idx_topic (id_topic),
        INDEX idx_id_board (id_board)
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}topics (
        id_topic MEDIUMINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_board SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        id_first_msg INT UNSIGNED NOT NULL DEFAULT 0,
        id_last_msg INT UNSIGNED NOT NULL DEFAULT 0,
        id_member_started MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
        id_member_updated MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
        num_replies INT UNSIGNED NOT NULL DEFAULT 0,
        num_views INT UNSIGNED NOT NULL DEFAULT 0,
        locked TINYINT NOT NULL DEFAULT 0,
        approved TINYINT NOT NULL DEFAULT 1
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}sessions (
        session_id VARCHAR(128) PRIMARY KEY,
        last_update INT UNSIGNED NOT NULL DEFAULT 0,
        data TEXT
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}themes (
        id_member MEDIUMINT NOT NULL DEFAULT 0,
        id_theme TINYINT UNSIGNED NOT NULL DEFAULT 1,
        variable VARCHAR(255) NOT NULL DEFAULT '',
        value TEXT,
        PRIMARY KEY (id_theme, id_member, variable(30))
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}permissions (
        id_group SMALLINT NOT NULL DEFAULT 0,
        permission VARCHAR(30) NOT NULL DEFAULT '',
        add_deny TINYINT NOT NULL DEFAULT 1,
        PRIMARY KEY (id_group, permission)
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}board_permissions (
        id_group SMALLINT NOT NULL DEFAULT 0,
        id_profile SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        permission VARCHAR(30) NOT NULL DEFAULT '',
        add_deny TINYINT NOT NULL DEFAULT 1,
        PRIMARY KEY (id_group, id_profile, permission)
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}permission_profiles (
        id_profile SMALLINT AUTO_INCREMENT PRIMARY KEY,
        profile_name VARCHAR(255) NOT NULL DEFAULT ''
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}scheduled_tasks (
        id_task SMALLINT AUTO_INCREMENT PRIMARY KEY,
        next_time INT NOT NULL DEFAULT 0,
        time_offset INT NOT NULL DEFAULT 0,
        time_regularity SMALLINT NOT NULL DEFAULT 0,
        time_unit VARCHAR(1) NOT NULL DEFAULT 'h',
        disabled TINYINT NOT NULL DEFAULT 0,
        task VARCHAR(24) NOT NULL DEFAULT '',
        callable VARCHAR(60) NOT NULL DEFAULT ''
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}log_actions (
        id_action INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_log TINYINT UNSIGNED NOT NULL DEFAULT 1,
        log_time INT UNSIGNED NOT NULL DEFAULT 0,
        id_member MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
        ip VARCHAR(255) NOT NULL DEFAULT '',
        action VARCHAR(30) NOT NULL DEFAULT '',
        extra TEXT
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}user_alerts (
        id_alert INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        alert_time INT UNSIGNED NOT NULL DEFAULT 0,
        id_member MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
        content_type VARCHAR(255) NOT NULL DEFAULT '',
        content_id INT UNSIGNED NOT NULL DEFAULT 0,
        content_action VARCHAR(255) NOT NULL DEFAULT '',
        is_read INT UNSIGNED NOT NULL DEFAULT 0,
        extra TEXT
    ) ENGINE=InnoDB",
    "CREATE TABLE IF NOT EXISTS {$db_prefix}background_tasks (
        id_task INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        task_file VARCHAR(255) NOT NULL DEFAULT '',
        task_class VARCHAR(255) NOT NULL DEFAULT '',
        task_data MEDIUMTEXT,
        claimed_time INT UNSIGNED NOT NULL DEFAULT 0
    ) ENGINE=InnoDB",
];

foreach ($tables as $sql) {
    if ($conn->query($sql)) echo ".";
    else echo "\nError: " . $conn->error . "\n";
}
echo "\n";

// Insert core settings
echo "Inserting settings...\n";
$settings = [
    ['smfVersion', '2.1.4'], ['news', 'SMF - Just Installed!'], ['compactTopicPagesEnable', '1'],
    ['todayMod', '1'], ['enableBBC', '1'], ['defaultMaxMessages', '15'], ['defaultMaxTopics', '20'],
    ['requireAgreement', '1'], ['registration_method', '0'], ['send_welcomeEmail', '1'],
    ['allow_editDisplayName', '1'], ['allow_hideOnline', '1'], ['autoLinkUrls', '1'],
    ['failed_login_threshold', '3'], ['autoFixDatabase', '1'], ['allow_guestAccess', '1'],
    ['time_format', '%B %d, %Y, %I:%M:%S %p'], ['enableCompressedOutput', '1'],
    ['databaseSession_enable', '1'], ['databaseSession_lifetime', '2880'], ['cache_enable', '0'],
    ['latestMember', '1'], ['latestRealName', 'admin'], ['totalMembers', '1'], ['totalTopics', '1'],
    ['totalMessages', '1'], ['knownThemes', '1'], ['settings_updated', time()],
    ['attachmentUploadDir', json_encode([1 => '/var/www/html/attachments'])],
];
$stmt = $conn->prepare("INSERT IGNORE INTO {$db_prefix}settings (variable, value) VALUES (?, ?)");
foreach ($settings as $s) { $stmt->bind_param('ss', $s[0], $s[1]); $stmt->execute(); }

// Create member groups
echo "Creating member groups...\n";
$groups = [
    [1, 'Administrator', '#FF0000', -1, '5#iconadmin.png', 1],
    [2, 'Global Moderator', '#0000FF', -1, '5#icongmod.png', 0],
    [3, 'Moderator', '', -1, '5#iconmod.png', 0],
    [4, 'Newbie', '', 0, '1#icon.png', 0],
];
$stmt = $conn->prepare("INSERT IGNORE INTO {$db_prefix}membergroups (id_group, group_name, online_color, min_posts, icons, group_type) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($groups as $g) { $stmt->bind_param('issisi', $g[0], $g[1], $g[2], $g[3], $g[4], $g[5]); $stmt->execute(); }

// Create admin user
echo "Creating admin user...\n";
$admin_user = 'admin';
$admin_email = getenv('SMF_ADMIN_EMAIL') ?: 'admin@mohcentral.com';
$admin_pass_raw = getenv('SMF_ADMIN_PASS') ?: 'admin123';
$admin_pass = hash('sha256', strtolower($admin_user) . $admin_pass_raw);
$now = time();

$stmt = $conn->prepare("INSERT IGNORE INTO {$db_prefix}members (id_member, member_name, real_name, passwd, email_address, date_registered, id_group, is_activated, buddy_list, pm_ignore_list, signature, ignore_boards) VALUES (1, ?, ?, ?, ?, ?, 1, 1, '', '', '', '')");
$stmt->bind_param('ssssi', $admin_user, $admin_user, $admin_pass, $admin_email, $now);
$stmt->execute();

// Create default category/board
$conn->query("INSERT IGNORE INTO {$db_prefix}categories (id_cat, cat_order, name) VALUES (1, 0, 'General')");
$conn->query("INSERT IGNORE INTO {$db_prefix}boards (id_board, id_cat, name, description, member_groups) VALUES (1, 1, 'General Discussion', 'Talk about anything.', '-1,0,2')");
$conn->query("INSERT IGNORE INTO {$db_prefix}permission_profiles (id_profile, profile_name) VALUES (1, 'default')");

// Theme settings
$conn->query("INSERT IGNORE INTO {$db_prefix}themes (id_theme, variable, value, id_member) VALUES (1, 'name', 'SMF Default', 0)");
$conn->query("INSERT IGNORE INTO {$db_prefix}themes (id_theme, variable, value, id_member) VALUES (1, 'theme_url', '/Themes/default', 0)");
$conn->query("INSERT IGNORE INTO {$db_prefix}themes (id_theme, variable, value, id_member) VALUES (1, 'theme_dir', '/var/www/html/Themes/default', 0)");

// Welcome message
$welcome = 'Welcome to MOH Central!';
$stmt = $conn->prepare("INSERT IGNORE INTO {$db_prefix}messages (id_msg, id_topic, id_board, poster_time, id_member, subject, poster_name, poster_email, poster_ip, body, approved) VALUES (1, 1, 1, ?, 1, 'Welcome!', 'admin', ?, '127.0.0.1', ?, 1)");
$stmt->bind_param('iss', $now, $admin_email, $welcome);
$stmt->execute();

$conn->query("INSERT IGNORE INTO {$db_prefix}topics (id_topic, id_board, id_first_msg, id_last_msg, id_member_started, id_member_updated) VALUES (1, 1, 1, 1, 1, 1)");
$conn->query("UPDATE {$db_prefix}boards SET num_topics = 1, num_posts = 1 WHERE id_board = 1");

echo "\n=== SMF Installation Complete ===\n";
echo "Admin: $admin_user / $admin_pass_raw\n";
$conn->close();
