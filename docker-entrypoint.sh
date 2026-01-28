#!/bin/bash
set -e

CONFIG_DIR="/var/www/html/smf-config"
SETTINGS_FILE="/var/www/html/Settings.php"
SETTINGS_BWC="/var/www/html/Settings_bak.php"

# Ensure config directory exists
mkdir -p "$CONFIG_DIR"
chown www-data:www-data "$CONFIG_DIR"

# If Settings.php exists in the persistent volume and is configured, restore it
if [ -f "$CONFIG_DIR/Settings.php" ] && grep -q "db_server" "$CONFIG_DIR/Settings.php" 2>/dev/null; then
    echo "Found persisted Settings.php, restoring..."
    cp "$CONFIG_DIR/Settings.php" "$SETTINGS_FILE"
    if [ -f "$CONFIG_DIR/Settings_bak.php" ]; then
        cp "$CONFIG_DIR/Settings_bak.php" "$SETTINGS_BWC"
    fi
    chmod 666 "$SETTINGS_FILE" "$SETTINGS_BWC"
    chown www-data:www-data "$SETTINGS_FILE" "$SETTINGS_BWC"
    echo "Settings restored from volume."
else
    echo "No persisted Settings.php found - run SMF installer at /install.php"
    # Ensure files are writable for installer
    chmod 666 "$SETTINGS_FILE" 2>/dev/null || true
    chmod 666 "$SETTINGS_BWC" 2>/dev/null || true
    chown www-data:www-data "$SETTINGS_FILE" 2>/dev/null || true
    chown www-data:www-data "$SETTINGS_BWC" 2>/dev/null || true
fi

# Start Apache
exec apache2-foreground
