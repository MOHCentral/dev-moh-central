#!/bin/bash
set -e

CONFIG_DIR="/var/www/html/smf-config"
SETTINGS_FILE="/var/www/html/Settings.php"
SETTINGS_BAK="/var/www/html/Settings_bak.php"

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

# PRIORITY 2: If Settings.php exists locally (from previous install), backup to volume
elif [ -f "$SETTINGS_FILE" ] && grep -q "db_server" "$SETTINGS_FILE" 2>/dev/null; then
    echo "[entrypoint] Found local Settings.php, backing up to volume..."
    cp "$SETTINGS_FILE" "$CONFIG_DIR/Settings.php"
    [ -f "$SETTINGS_BAK" ] && cp "$SETTINGS_BAK" "$CONFIG_DIR/Settings_bak.php"
    echo "[entrypoint] Settings backed up to volume."

# PRIORITY 3: Fresh install needed
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
