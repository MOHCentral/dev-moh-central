#!/bin/bash
set -e

CONFIG_DIR="/var/www/html/smf-config"
SETTINGS_FILE="/var/www/html/Settings.php"
SETTINGS_BWC="/var/www/html/Settings_bak.php"

# Ensure config directory exists
mkdir -p "$CONFIG_DIR"
chown www-data:www-data "$CONFIG_DIR"

# If Settings.php exists in the persistent volume, symlink it
if [ -f "$CONFIG_DIR/Settings.php" ]; then
    echo "Found persisted Settings.php, linking..."
    ln -sf "$CONFIG_DIR/Settings.php" "$SETTINGS_FILE"
    if [ -f "$CONFIG_DIR/Settings_bak.php" ]; then
        ln -sf "$CONFIG_DIR/Settings_bak.php" "$SETTINGS_BWC"
    fi
# If Settings.php exists locally (just installed), copy to volume
elif [ -f "$SETTINGS_FILE" ] && [ ! -L "$SETTINGS_FILE" ]; then
    echo "Copying Settings.php to persistent volume..."
    cp "$SETTINGS_FILE" "$CONFIG_DIR/Settings.php"
    if [ -f "$SETTINGS_BWC" ]; then
        cp "$SETTINGS_BWC" "$CONFIG_DIR/Settings_bak.php"
    fi
    ln -sf "$CONFIG_DIR/Settings.php" "$SETTINGS_FILE"
else
    echo "Settings.php not found - run SMF installer at /install.php"
    echo "After installation, settings will be persisted across container rebuilds."
fi

# Start Apache
exec apache2-foreground
