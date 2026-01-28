#!/bin/bash
set -e









































































































































































































































































































































































































































































































































































































$conn->close();echo "You can now access the forum.\n";echo "Admin: $admin_user / $admin_pass_raw\n";echo "\n=== SMF Installation Complete! ===\n";$conn->query("UPDATE {$db_prefix}boards SET id_last_msg = 1, id_msg_updated = 1, num_topics = 1, num_posts = 1 WHERE id_board = 1");$conn->query("INSERT IGNORE INTO {$db_prefix}topics (id_topic, id_board, id_first_msg, id_last_msg, id_member_started, id_member_updated, num_views) VALUES (1, 1, 1, 1, 1, 1, 0)");$stmt->execute();$stmt->bind_param('iss', $now, $admin_email, $welcome_body);$stmt = $conn->prepare("INSERT IGNORE INTO {$db_prefix}messages (id_msg, id_topic, id_board, poster_time, id_member, subject, poster_name, poster_email, poster_ip, body, icon) VALUES (1, 1, 1, ?, 1, 'Welcome to MOH Central!', 'admin', ?, '127.0.0.1', ?, 'xx')");$welcome_body = 'Welcome to MOH Central!\n\nThis forum is powered by SMF and integrated with MOHAA Stats tracking.';echo "Creating welcome topic...\n";// Insert welcome topic$conn->query("INSERT IGNORE INTO {$db_prefix}themes (id_theme, variable, value, id_member) VALUES (1, 'theme_dir', '/var/www/html/Themes/default', 0)");$conn->query("INSERT IGNORE INTO {$db_prefix}themes (id_theme, variable, value, id_member) VALUES (1, 'images_url', '/Themes/default/images', 0)");$conn->query("INSERT IGNORE INTO {$db_prefix}themes (id_theme, variable, value, id_member) VALUES (1, 'theme_url', '/Themes/default', 0)");$conn->query("INSERT IGNORE INTO {$db_prefix}themes (id_theme, variable, value, id_member) VALUES (1, 'name', 'SMF Default Theme - Curve2', 0)");// Create default theme entry$conn->query("INSERT IGNORE INTO {$db_prefix}permission_profiles (id_profile, profile_name) VALUES (1, 'default')");// Create default permission profile$conn->query("INSERT IGNORE INTO {$db_prefix}boards (id_board, id_cat, name, description, board_order, member_groups) VALUES (1, 1, 'General Discussion', 'Feel free to talk about anything.', 1, '-1,0,2')");$conn->query("INSERT IGNORE INTO {$db_prefix}categories (id_cat, cat_order, name, description, can_collapse) VALUES (1, 0, 'General', '', 1)");echo "Creating default category and board...\n";// Create default category and boardecho "Admin user created: $admin_user / $admin_pass_raw\n";$stmt->execute();$stmt->bind_param('ssssi', $admin_user, $admin_user, $admin_pass, $admin_email, $now);    VALUES (1, ?, ?, ?, ?, ?, 1, 1, '', '', '', '')");    (id_member, member_name, real_name, passwd, email_address, date_registered, id_group, is_activated, buddy_list, pm_ignore_list, signature, ignore_boards) $stmt = $conn->prepare("INSERT IGNORE INTO {$db_prefix}members $now = time();$admin_pass = hash('sha256', strtolower($admin_user) . $admin_pass_raw);$admin_pass_raw = getenv('SMF_ADMIN_PASS') ?: 'admin123';$admin_email = getenv('SMF_ADMIN_EMAIL') ?: 'admin@mohcentral.com';$admin_user = 'admin';echo "Creating admin user...\n";// Create default admin user (password: admin123)echo "Created " . count($groups) . " member groups.\n";}    $stmt->execute();    $stmt->bind_param('isssiisiiis', $g[0], $g[1], $g[2], $g[3], $g[4], $g[5], $g[6], $g[7], $g[8], $g[9], $g[10]);foreach ($groups as $g) {$stmt = $conn->prepare("INSERT IGNORE INTO {$db_prefix}membergroups (id_group, group_name, description, online_color, min_posts, max_messages, icons, group_type, hidden, id_parent, tfa_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");];    [8, 'Hero Member', '', '', 500, 0, '5#icon.png', 0, 0, -2, 0],    [7, 'Sr. Member', '', '', 250, 0, '4#icon.png', 0, 0, -2, 0],    [6, 'Full Member', '', '', 100, 0, '3#icon.png', 0, 0, -2, 0],    [5, 'Jr. Member', '', '', 50, 0, '2#icon.png', 0, 0, -2, 0],    [4, 'Newbie', '', '', 0, 0, '1#icon.png', 0, 0, -2, 0],    [3, 'Moderator', '', '', -1, 0, '5#iconmod.png', 0, 0, -2, 0],    [2, 'Global Moderator', '', '#0000FF', -1, 0, '5#icongmod.png', 0, 0, -2, 0],    [1, 'Administrator', '', '#FF0000', -1, 0, '5#iconadmin.png', 1, 0, -2, 0],$groups = [echo "Creating default member groups...\n";// Insert default member groupsecho "Inserted " . count($settings) . " settings.\n";}    $stmt->execute();    $stmt->bind_param('ss', $s[0], $s[1]);foreach ($settings as $s) {$stmt = $conn->prepare("INSERT IGNORE INTO {$db_prefix}settings (variable, value) VALUES (?, ?)");];    ['minimize_hash', ''],    ['minimize_files', '1'],    ['maillist_short_link', '1'],    ['login_token_ttl', '2'],    ['enable_sm_stats', '0'],    ['proxy_ip_header', 'autodetect'],    ['frame_security', 'SAMEORIGIN'],    ['export_rate', '250'],    ['export_min_diskspace_pct', '5'],    ['export_expiry', '7'],    ['export_dir', '/var/www/html/cache'],    ['tfa_mode', '1'],    ['samesiteCookies', 'lax'],    ['httponlyCookies', '1'],    ['drafts_keep_days', '7'],    ['drafts_show_saved_enabled', '1'],    ['drafts_autosave_enabled', '1'],    ['drafts_pm_enabled', '1'],    ['drafts_post_enabled', '1'],    ['gravatarAllowExtraEmail', '1'],    ['gravatarOverride', '0'],    ['gravatarEnabled', '1'],    ['alerts_auto_purge', '30'],    ['enable_ajax_alerts', '1'],    ['show_profile_buttons', '1'],    ['show_blurb', '1'],    ['show_user_images', '1'],    ['show_modify', '1'],    ['additional_options_collapsable', '1'],    ['enableVBStyleLogin', '1'],    ['pollMode', '1'],    ['enable_unwatch', '0'],    ['topic_move_any', '0'],    ['custom_avatar_enabled', '0'],    ['titlesEnable', '1'],    ['userLanguage', '1'],    ['allow_hideEmail', '1'],    ['maxMsgID', '1'],    ['admin_session_lifetime', '10'],    ['xmlnews_maxlen', '255'],    ['xmlnews_enable', '1'],    ['enableEmbeddedFlash', '0'],    ['knownThemes', '1'],    ['mostDate', time()],    ['mostOnlineToday', '1'],    ['mostOnline', '1'],    ['censor_proper', ''],    ['censor_vulgar', ''],    ['totalMessages', '1'],    ['totalTopics', '1'],    ['totalMembers', '1'],    ['latestRealName', 'admin'],    ['latestMember', '1'],    ['attachment_basedirectories', json_encode([])],    ['attachmentUploadDir', json_encode([1 => '/var/www/html/attachments'])],    ['currentAttachmentUploadDir', '1'],    ['enable_disregard', '0'],    ['avatar_reencode', '0'],    ['avatar_paranoid', '0'],    ['attachment_thumb_png', '1'],    ['attachment_image_paranoid', '0'],    ['attachment_image_reencode', '1'],    ['mail_type', '0'],    ['dont_repeat_resolve_topics', '0'],    ['dont_repeat_resolve', '0'],    ['birthday_email', 'happy_birthday'],    ['enable_buddylist', '1'],    ['reg_verification', '1'],    ['cache_enable', '0'],    ['pruningOptions', ''],    ['last_mod_report_action', '0'],    ['warning_settings', '1,20,0'],    ['next_task_time', '1'],    ['settings_updated', time()],    ['mail_recent', '0000000000|0'],    ['mail_next_send', '0'],    ['permission_enable_postgroups', '0'],    ['permission_enable_deny', '0'],    ['search_floodcontrol_time', '5'],    ['search_max_results', '1200'],    ['search_weight_sticky', '0'],    ['search_weight_first_message', '10'],    ['search_weight_subject', '15'],    ['search_weight_length', '20'],    ['search_weight_age', '25'],    ['search_weight_frequency', '30'],    ['search_results_per_page', '30'],    ['search_cache_size', '50'],    ['databaseSession_lifetime', '2880'],    ['databaseSession_loose', '1'],    ['databaseSession_enable', '1'],    ['enableCompressedData', '1'],    ['enableCompressedOutput', '1'],    ['number_format', '1234.00'],    ['time_format', '%B %d, %Y, %I:%M:%S %p'],    ['allow_guestAccess', '1'],    ['autoFixDatabase', '1'],    ['edit_disable_time', '0'],    ['edit_wait_time', '90'],    ['oldTopicDays', '120'],    ['failed_login_threshold', '3'],    ['gravatar_rating', 'g'],    ['avatar_download_png', '1'],    ['avatar_resize_upload', '1'],    ['avatar_max_width_upload', '65'],    ['avatar_max_height_upload', '65'],    ['avatar_action_too_large', 'option_html_resize'],    ['avatar_max_width_external', '65'],    ['avatar_max_height_external', '65'],    ['banLastUpdated', '0'],    ['autoLinkUrls', '1'],    ['reserveNames', ''],    ['reserveName', '1'],    ['reserveUser', '1'],    ['reserveCase', '1'],    ['reserveWord', '0'],    ['pm_spam_settings', '10,5,20'],    ['spamWaitTime', '5'],    ['guest_hideContacts', '1'],    ['allow_hideOnline', '1'],    ['allow_editDisplayName', '1'],    ['send_welcomeEmail', '1'],    ['send_validation_onChange', '0'],    ['registration_method', '0'],    ['requireAgreement', '1'],    ['cal_enabled', '0'],    ['recycle_enable', '0'],    ['enableParticipation', '1'],    ['defaultMaxMembers', '30'],    ['defaultMaxTopics', '20'],    ['defaultMaxMessages', '15'],    ['max_image_height', '0'],    ['max_image_width', '0'],    ['enableBBC', '1'],    ['todayMod', '1'],    ['compactTopicPagesEnable', '1'],    ['compactTopicPagesContiguous', '5'],    ['news', 'SMF - Just Installed!'],    ['smfVersion', '2.1.4'],$settings = [echo "Inserting default settings...\n";// Insert default settingsecho "\n";}    }        echo "\nError: " . $conn->error . "\n";    } else {        echo ".";    if ($conn->query($sql)) {foreach ($tables as $sql) {];    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (id_task)        claimed_time INT UNSIGNED NOT NULL DEFAULT 0,        task_data MEDIUMTEXT NOT NULL,        task_class VARCHAR(255) NOT NULL DEFAULT '',        task_file VARCHAR(255) NOT NULL DEFAULT '',        id_task INT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}background_tasks (    // Background tasks    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (id_member, alert_pref)        alert_value TINYINT NOT NULL DEFAULT 0,        alert_pref VARCHAR(32) NOT NULL DEFAULT '',        id_member MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,    "CREATE TABLE IF NOT EXISTS {$db_prefix}user_alerts_prefs (    // User alerts preferences    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        INDEX idx_alert_time (alert_time)        INDEX idx_id_member (id_member),        PRIMARY KEY (id_alert),        extra TEXT NOT NULL,        is_read INT UNSIGNED NOT NULL DEFAULT 0,        content_action VARCHAR(255) NOT NULL DEFAULT '',        content_id INT UNSIGNED NOT NULL DEFAULT 0,        content_type VARCHAR(255) NOT NULL DEFAULT '',        member_name VARCHAR(255) NOT NULL DEFAULT '',        id_member_started MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        id_member MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        alert_time INT UNSIGNED NOT NULL DEFAULT 0,        id_alert INT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}user_alerts (    // User alerts    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (id_log)        time_taken FLOAT NOT NULL DEFAULT 0,        time_run INT NOT NULL DEFAULT 0,        id_task SMALLINT NOT NULL DEFAULT 0,        id_log MEDIUMINT AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}log_scheduled_tasks (    // Log scheduled tasks    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        UNIQUE KEY idx_task (task)        INDEX idx_disabled (disabled),        INDEX idx_next_time (next_time),        PRIMARY KEY (id_task),        callable VARCHAR(60) NOT NULL DEFAULT '',        task VARCHAR(24) NOT NULL DEFAULT '',        disabled TINYINT NOT NULL DEFAULT 0,        time_unit VARCHAR(1) NOT NULL DEFAULT 'h',        time_regularity SMALLINT NOT NULL DEFAULT 0,        time_offset INT NOT NULL DEFAULT 0,        next_time INT NOT NULL DEFAULT 0,        id_task SMALLINT AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}scheduled_tasks (    // Scheduled tasks    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (id_smiley)        hidden TINYINT UNSIGNED NOT NULL DEFAULT 0,        smiley_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,        smiley_row TINYINT UNSIGNED NOT NULL DEFAULT 0,        description VARCHAR(80) NOT NULL DEFAULT '',        code VARCHAR(30) NOT NULL DEFAULT '',        id_smiley SMALLINT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}smileys (    // Smileys    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        INDEX idx_id_member (id_member)        PRIMARY KEY (id_theme, id_member, variable(30)),        value TEXT NOT NULL,        variable VARCHAR(255) NOT NULL DEFAULT '',        id_theme TINYINT UNSIGNED NOT NULL DEFAULT 1,        id_member MEDIUMINT NOT NULL DEFAULT 0,    "CREATE TABLE IF NOT EXISTS {$db_prefix}themes (    // Themes    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (id_profile)        profile_name VARCHAR(255) NOT NULL DEFAULT '',        id_profile SMALLINT AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}permission_profiles (    // Permission profiles    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (id_group, id_profile, permission)        add_deny TINYINT NOT NULL DEFAULT 1,        permission VARCHAR(30) NOT NULL DEFAULT '',        id_profile SMALLINT UNSIGNED NOT NULL DEFAULT 0,        id_group SMALLINT NOT NULL DEFAULT 0,    "CREATE TABLE IF NOT EXISTS {$db_prefix}board_permissions (    // Board permissions    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (id_group, permission)        add_deny TINYINT NOT NULL DEFAULT 1,        permission VARCHAR(30) NOT NULL DEFAULT '',        id_group SMALLINT NOT NULL DEFAULT 0,    "CREATE TABLE IF NOT EXISTS {$db_prefix}permissions (    // Permissions    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        INDEX idx_id_topic_id_log (id_topic, id_log)        INDEX idx_id_msg (id_msg),        INDEX idx_id_board (id_board),        INDEX idx_id_member (id_member),        INDEX idx_log_time (log_time),        INDEX idx_id_log (id_log),        PRIMARY KEY (id_action),        extra TEXT NOT NULL,        id_msg INT UNSIGNED NOT NULL DEFAULT 0,        id_topic MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        id_board SMALLINT UNSIGNED NOT NULL DEFAULT 0,        action VARCHAR(30) NOT NULL DEFAULT '',        ip VARCHAR(255) NOT NULL DEFAULT '',        id_member MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        log_time INT UNSIGNED NOT NULL DEFAULT 0,        id_log TINYINT UNSIGNED NOT NULL DEFAULT 1,        id_action INT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}log_actions (    // Log actions    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (session_id)        data TEXT NOT NULL,        last_update INT UNSIGNED NOT NULL DEFAULT 0,        session_id VARCHAR(128) NOT NULL DEFAULT '',    "CREATE TABLE IF NOT EXISTS {$db_prefix}sessions (    // Sessions    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        INDEX idx_board_news (id_board, id_first_msg)        INDEX idx_last_message_sticky (id_board, is_sticky, id_last_msg),        INDEX idx_member_started (id_member_started, id_board),        INDEX idx_approved (approved),        INDEX idx_is_sticky (is_sticky),        INDEX idx_poll (id_poll, id_topic),        INDEX idx_first_message (id_first_msg, id_board),        INDEX idx_last_message (id_last_msg, id_board),        PRIMARY KEY (id_topic),        approved TINYINT NOT NULL DEFAULT 1,        unapproved_posts SMALLINT NOT NULL DEFAULT 0,        id_redirect_topic MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        redirect_expires INT UNSIGNED NOT NULL DEFAULT 0,        locked TINYINT NOT NULL DEFAULT 0,        num_views INT UNSIGNED NOT NULL DEFAULT 0,        num_replies INT UNSIGNED NOT NULL DEFAULT 0,        id_previous_topic MEDIUMINT NOT NULL DEFAULT 0,        id_previous_board SMALLINT NOT NULL DEFAULT 0,        id_poll MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        id_member_updated MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        id_member_started MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        id_last_msg INT UNSIGNED NOT NULL DEFAULT 0,        id_first_msg INT UNSIGNED NOT NULL DEFAULT 0,        id_board SMALLINT UNSIGNED NOT NULL DEFAULT 0,        is_sticky TINYINT NOT NULL DEFAULT 0,        id_topic MEDIUMINT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}topics (    // Topics    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        INDEX idx_related_ip (id_member, poster_ip, id_msg)        INDEX idx_current_topic (id_topic, id_msg, id_member, approved),        INDEX idx_id_member_msg (id_member, approved, id_msg),        INDEX idx_show_posts (id_member, id_board),        INDEX idx_participation (id_member, id_topic),        INDEX idx_ip_index (poster_ip, id_topic),        INDEX idx_approved (approved),        INDEX idx_id_member (id_member),        INDEX idx_id_board (id_board),        INDEX idx_topic (id_topic),        PRIMARY KEY (id_msg),        likes SMALLINT UNSIGNED NOT NULL DEFAULT 0,        approved TINYINT NOT NULL DEFAULT 1,        icon VARCHAR(16) NOT NULL DEFAULT 'xx',        body TEXT NOT NULL,        modified_reason VARCHAR(255) NOT NULL DEFAULT '',        modified_name VARCHAR(255) NOT NULL DEFAULT '',        modified_time INT UNSIGNED NOT NULL DEFAULT 0,        smileys_enabled TINYINT NOT NULL DEFAULT 1,        poster_ip VARCHAR(255) NOT NULL DEFAULT '',        poster_email VARCHAR(255) NOT NULL DEFAULT '',        poster_name VARCHAR(255) NOT NULL DEFAULT '',        subject VARCHAR(255) NOT NULL DEFAULT '',        id_msg_modified INT UNSIGNED NOT NULL DEFAULT 0,        id_member MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        poster_time INT UNSIGNED NOT NULL DEFAULT 0,        id_board SMALLINT UNSIGNED NOT NULL DEFAULT 0,        id_topic MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        id_msg INT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}messages (    // Messages    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (id_cat)        can_collapse TINYINT NOT NULL DEFAULT 1,        description TEXT NOT NULL,        name VARCHAR(255) NOT NULL DEFAULT '',        cat_order TINYINT NOT NULL DEFAULT 0,        id_cat TINYINT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}categories (    // Categories    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        INDEX idx_member_groups (member_groups(48))        INDEX idx_id_msg_updated (id_msg_updated),        INDEX idx_id_parent (id_parent),        INDEX idx_categories (id_cat),        PRIMARY KEY (id_board),        deny_member_groups VARCHAR(255) NOT NULL DEFAULT '',        redirect VARCHAR(255) NOT NULL DEFAULT '',        unapproved_topics SMALLINT NOT NULL DEFAULT 0,        unapproved_posts SMALLINT NOT NULL DEFAULT 0,        override_theme TINYINT UNSIGNED NOT NULL DEFAULT 0,        id_theme TINYINT UNSIGNED NOT NULL DEFAULT 0,        count_posts TINYINT NOT NULL DEFAULT 0,        num_posts MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        num_topics MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        description TEXT NOT NULL,        name VARCHAR(255) NOT NULL DEFAULT '',        id_profile SMALLINT UNSIGNED NOT NULL DEFAULT 1,        member_groups VARCHAR(255) NOT NULL DEFAULT '-1,0',        id_msg_updated INT UNSIGNED NOT NULL DEFAULT 0,        id_last_msg INT UNSIGNED NOT NULL DEFAULT 0,        board_order SMALLINT NOT NULL DEFAULT 0,        id_parent SMALLINT UNSIGNED NOT NULL DEFAULT 0,        child_level TINYINT UNSIGNED NOT NULL DEFAULT 0,        id_cat TINYINT UNSIGNED NOT NULL DEFAULT 0,        id_board SMALLINT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}boards (    // Boards    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        INDEX idx_min_posts (min_posts)        PRIMARY KEY (id_group),        tfa_required TINYINT NOT NULL DEFAULT 0,        id_parent SMALLINT NOT NULL DEFAULT -2,        hidden TINYINT NOT NULL DEFAULT 0,        group_type TINYINT NOT NULL DEFAULT 0,        icons VARCHAR(255) NOT NULL DEFAULT '',        max_messages SMALLINT UNSIGNED NOT NULL DEFAULT 0,        min_posts MEDIUMINT NOT NULL DEFAULT -1,        online_color VARCHAR(20) NOT NULL DEFAULT '',        description TEXT NOT NULL,        group_name VARCHAR(80) NOT NULL DEFAULT '',        id_group SMALLINT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}membergroups (    // Member groups    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        INDEX idx_id_theme (id_theme)        INDEX idx_total_time_logged_in (total_time_logged_in),        INDEX idx_warning (warning),        INDEX idx_id_post_group (id_post_group),        INDEX idx_lngfile (lngfile(30)),        INDEX idx_last_login (last_login),        INDEX idx_posts (posts),        INDEX idx_birthdate (birthdate),        INDEX idx_id_group (id_group),        INDEX idx_date_registered (date_registered),        INDEX idx_email_address (email_address),        INDEX idx_real_name (real_name),        INDEX idx_member_name (member_name),        PRIMARY KEY (id_member),        tfa_backup VARCHAR(64) NOT NULL DEFAULT '',        tfa_secret VARCHAR(24) NOT NULL DEFAULT '',        timezone VARCHAR(80) NOT NULL DEFAULT '',        pm_receive_from TINYINT UNSIGNED NOT NULL DEFAULT 1,        passwd_flood VARCHAR(12) NOT NULL DEFAULT '',        warning TINYINT NOT NULL DEFAULT 0,        ignore_boards TEXT NOT NULL,        password_salt VARCHAR(255) NOT NULL DEFAULT '',        total_time_logged_in INT UNSIGNED NOT NULL DEFAULT 0,        id_post_group SMALLINT UNSIGNED NOT NULL DEFAULT 0,        smiley_set VARCHAR(48) NOT NULL DEFAULT '',        additional_groups VARCHAR(255) NOT NULL DEFAULT '',        id_msg_last_visit INT UNSIGNED NOT NULL DEFAULT 0,        validation_code VARCHAR(10) NOT NULL DEFAULT '',        is_activated TINYINT UNSIGNED NOT NULL DEFAULT 1,        id_theme TINYINT UNSIGNED NOT NULL DEFAULT 0,        secret_answer VARCHAR(64) NOT NULL DEFAULT '',        secret_question VARCHAR(255) NOT NULL DEFAULT '',        member_ip2 VARCHAR(255) NOT NULL DEFAULT '',        member_ip VARCHAR(255) NOT NULL DEFAULT '',        usertitle VARCHAR(255) NOT NULL DEFAULT '',        avatar VARCHAR(255) NOT NULL DEFAULT '',        time_offset FLOAT NOT NULL DEFAULT 0,        signature TEXT NOT NULL,        time_format VARCHAR(80) NOT NULL DEFAULT '',        show_online TINYINT NOT NULL DEFAULT 1,        website_url VARCHAR(255) NOT NULL DEFAULT '',        website_title VARCHAR(255) NOT NULL DEFAULT '',        birthdate DATE NOT NULL DEFAULT '1004-01-01',        personal_text VARCHAR(255) NOT NULL DEFAULT '',        email_address VARCHAR(255) NOT NULL DEFAULT '',        passwd VARCHAR(64) NOT NULL DEFAULT '',        mod_prefs VARCHAR(20) NOT NULL DEFAULT '',        pm_prefs MEDIUMINT NOT NULL DEFAULT 0,        pm_ignore_list TEXT NOT NULL,        buddy_list TEXT NOT NULL,        alerts INT UNSIGNED NOT NULL DEFAULT 0,        new_pm TINYINT UNSIGNED NOT NULL DEFAULT 0,        unread_messages SMALLINT NOT NULL DEFAULT 0,        instant_messages SMALLINT NOT NULL DEFAULT 0,        real_name VARCHAR(255) NOT NULL DEFAULT '',        last_login INT UNSIGNED NOT NULL DEFAULT 0,        lngfile VARCHAR(255) NOT NULL DEFAULT '',        id_group SMALLINT UNSIGNED NOT NULL DEFAULT 0,        posts MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,        date_registered INT UNSIGNED NOT NULL DEFAULT 0,        member_name VARCHAR(80) NOT NULL DEFAULT '',        id_member MEDIUMINT UNSIGNED AUTO_INCREMENT,    "CREATE TABLE IF NOT EXISTS {$db_prefix}members (    // Members table    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",        PRIMARY KEY (variable)        value TEXT NOT NULL,        variable VARCHAR(255) NOT NULL DEFAULT '',    "CREATE TABLE IF NOT EXISTS {$db_prefix}settings (    // Settings table (critical)$tables = [// Core SMF 2.1 tables (minimal set for operation)echo "Creating SMF core tables...\n";}    }        exit(0);        echo "SMF already installed (found {$row['cnt']} members). Skipping.\n";    if ($row['cnt'] > 0) {if ($result && $row = $result->fetch_assoc()) {$result = $conn->query("SELECT COUNT(*) as cnt FROM {$db_prefix}members");// Check if SMF is already installed (members table exists with data)echo "Connected to MySQL successfully.\n";}    die("Connection failed: " . $conn->connect_error . "\n");if ($conn->connect_error) {$conn = new mysqli($db_server, $db_user, $db_passwd, $db_name);// Connect to MySQLecho "Database: $db_name @ $db_server\n";echo "=== SMF Headless Installer ===\n";require_once('/var/www/html/Settings.php');// Load Settingserror_reporting(E_ALL); */ * Run from CLI: php /var/www/html/install_smf_tables.php * This script creates the essential SMF tables and a default admin user. *  * SMF Headless Installer - Creates core SMF tables without web interface
CONFIG_DIR="/var/www/html/smf-config"
SETTINGS_FILE="/var/www/html/Settings.php"
SETTINGS_BAK="/var/www/html/Settings_bak.php"
SETTINGS_TEMPLATE="/var/www/html/Settings.php.template"

# Ensure config directory exists with proper permissions
mkdir -p "$CONFIG_DIR"
chown -R www-data:www-data "$CONFIG_DIR"
chmod -R 777 "$CONFIG_DIR"

# PRIORITY 1: Restore from volume if available
if [ -f "$CONFIG_DIR/Settings.php" ] && grep -q "db_server" "$CONFIG_DIR/Settings.php" 2>/dev/null; then
    echo "[entrypoint] Restoring Settings.php from persistent volume..."
    cp "$CONFIG_DIR/Settings.php" "$SETTINGS_FILE"
    [ -f "$CONFIG_DIR/Settings_bak.php" ] && cp "$CONFIG_DIR/Settings_bak.php" "$SETTINGS_BAK"
    chmod 666 "$SETTINGS_FILE" "$SETTINGS_BAK" 2>/dev/null || true
    chown www-data:www-data "$SETTINGS_FILE" "$SETTINGS_BAK" 2>/dev/null || true
    echo "[entrypoint] Settings restored successfully."

# PRIORITY 2: Use template if available (SKIP SMF Installer!)
elif [ -f "$SETTINGS_TEMPLATE" ]; then
    echo "[entrypoint] Creating Settings.php from template (skipping SMF installer)..."
    cp "$SETTINGS_TEMPLATE" "$SETTINGS_FILE"
    
    # Generate auth_secret if empty
    AUTH_SECRET=$(openssl rand -hex 32)
    sed -i "s|\$auth_secret = '';|\$auth_secret = '$AUTH_SECRET';|" "$SETTINGS_FILE"
    
    chmod 666 "$SETTINGS_FILE"
    chown www-data:www-data "$SETTINGS_FILE"
    
    # Save to volume
    cp "$SETTINGS_FILE" "$CONFIG_DIR/Settings.php"
    echo "[entrypoint] Settings.php created from template!"
    
    # Wait for MySQL and run SMF table creation
    echo "[entrypoint] Waiting for MySQL to be ready..."
    for i in {1..30}; do
        if php -r "
            \$conn = @new mysqli('${SMF_DB_SERVER:-dev-smf-mysql}', '${SMF_DB_USER:-smf}', '${SMF_DB_PASS:-smf_password}', '${SMF_DB_NAME:-smf}');
            if (\$conn->connect_error) exit(1);
            echo 'MySQL ready';
            exit(0);
        " 2>/dev/null; then
            break
        fi
        echo "[entrypoint] Waiting for MySQL... ($i/30)"
        sleep 2
    done
    
    # Create SMF core tables via install.php API
    echo "[entrypoint] Creating SMF core tables..."
    php /var/www/html/install_smf_tables.php 2>&1 || echo "[entrypoint] SMF tables may already exist"
    
# PRIORITY 3: If Settings.php exists locally (from previous install), backup to volume
elif [ -f "$SETTINGS_FILE" ] && grep -q "db_server" "$SETTINGS_FILE" 2>/dev/null; then
    echo "[entrypoint] Found local Settings.php, backing up to volume..."
    cp "$SETTINGS_FILE" "$CONFIG_DIR/Settings.php"
    [ -f "$SETTINGS_BAK" ] && cp "$SETTINGS_BAK" "$CONFIG_DIR/Settings_bak.php"
    echo "[entrypoint] Settings backed up to volume."

# PRIORITY 4: Fresh install needed
else
    echo "[entrypoint] No configured Settings.php found."
    echo "[entrypoint] Run SMF installer at /install.php"
    echo "[entrypoint] Settings will be auto-saved to volume after installation."
    chmod 666 "$SETTINGS_FILE" "$SETTINGS_BAK" 2>/dev/null || true
    chown www-data:www-data "$SETTINGS_FILE" "$SETTINGS_BAK" 2>/dev/null || true
fi

# MOHAA Stats Plugin Auto-Installation
if [ -f "$SETTINGS_FILE" ] && grep -q "db_server" "$SETTINGS_FILE" 2>/dev/null; then
    if [ -f "/var/www/html/mohaa_install.php" ]; then
        echo "[entrypoint] Scheduling MOHAA Stats Plugin auto-install..."
        (
            # Wait for Apache and MySQL to be ready
            sleep 15
            
            # Patch the installer to ensure db_create_table and db_insert are registered
            # (Required because standalone installer doesn't load all SMF extensions)
            sed -i "s|loadDatabase();|loadDatabase();\\nrequire_once(\'/var/www/html/Sources/DbPackages-mysql.php\');\\ndb_packages_init();\\n global \\$smcFunc; \\$smcFunc[\'db_create_table\'] = \'smf_db_create_table\'; \\$smcFunc[\'db_insert\'] = \'smf_db_insert\';|" /var/www/html/mohaa_install.php

            # Run the idempotent installer (v2.0+ handles db_create_table via SSI.php)
            php /var/www/html/mohaa_install.php > /tmp/plugin_install.log 2>&1
            if [ $? -eq 0 ]; then
                echo "[plugin-auto-install] ✓ Plugin installation/check completed."
                cat /tmp/plugin_install.log
            else
                echo "[plugin-auto-install] ✗ Plugin installation failed. Check /tmp/plugin_install.log"
                cat /tmp/plugin_install.log
            fi
        ) &
    fi
fi

echo "[entrypoint] Starting Apache..."

# Auto-backup Settings.php if it was created but not yet saved to volume
# (This runs every 60 seconds in the background to catch new installations)
(
    while true; do
        sleep 60
        if [ -f "$SETTINGS_FILE" ] && grep -q "db_server" "$SETTINGS_FILE" 2>/dev/null; then
            if [ ! -f "$CONFIG_DIR/Settings.php" ] || ! diff -q "$SETTINGS_FILE" "$CONFIG_DIR/Settings.php" >/dev/null 2>&1; then
                echo "[auto-backup] Detected Settings.php changes, saving to volume..."
                cp "$SETTINGS_FILE" "$CONFIG_DIR/Settings.php" 2>/dev/null || true
                [ -f "$SETTINGS_BAK" ] && cp "$SETTINGS_BAK" "$CONFIG_DIR/Settings_bak.php" 2>/dev/null || true
                echo "[auto-backup] Settings.php persisted to volume!"
            fi
        fi
    done
) &

# Start Apache
exec apache2-foreground
