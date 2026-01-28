#!/bin/bash
set -e

CONFIG_DIR="/var/www/html/smf-config"
SETTINGS_FILE="/var/www/html/Settings.php"
SETTINGS_BAK="/var/www/html/Settings_bak.php"

# Ensure config directory exists
mkdir -p "$CONFIG_DIR"
chown www-data:www-data "$CONFIG_DIR"

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
    echo "[entrypoint] Then visit /save_settings.php to persist config!"
    chmod 666 "$SETTINGS_FILE" "$SETTINGS_BAK" 2>/dev/null || true
    chown www-data:www-data "$SETTINGS_FILE" "$SETTINGS_BAK" 2>/dev/null || true
fi

# Start Apache
exec apache2-foreground
