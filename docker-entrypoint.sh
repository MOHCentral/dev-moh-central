#!/bin/bash
set -e

CONFIG_DIR="/var/www/html/smf-config"
SETTINGS_FILE="/var/www/html/Settings.php"
SETTINGS_BAK="/var/www/html/Settings_bak.php"
SETTINGS_TEMPLATE="/var/www/html/Settings.php.template"

# Ensure config directory exists with proper permissions
mkdir -p "$CONFIG_DIR"
chown -R www-data:www-data "$CONFIG_DIR"
chmod -R 777 "$CONFIG_DIR"

# Allow skipping headless install via environment variable
if [ "$SKIP_HEADLESS_INSTALL" = "1" ]; then
    echo "[entrypoint] SKIP_HEADLESS_INSTALL=1, using SMF web installer..."
    SETTINGS_TEMPLATE=""  # Force web installer path
fi

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
