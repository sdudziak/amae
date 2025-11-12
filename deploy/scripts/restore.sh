#!/bin/bash
set -e

# Configuration
APP_NAME="amae"
DEPLOY_PATH="/var/www/$APP_NAME"
BACKUP_PATH="/var/backups/$APP_NAME"

# Check if backup files are provided
DB_BACKUP=$1
FILES_BACKUP=$2

if [ -z "$DB_BACKUP" ] || [ -z "$FILES_BACKUP" ]; then
    echo "Usage: $0 <database_backup.sql> <files_backup.tar.gz>"
    exit 1
fi

# Restore database
echo "Restoring database..."
wp db import "$DB_BACKUP" --path="$DEPLOY_PATH/web"

# Restore files
echo "Restoring files..."
tar -xzf "$FILES_BACKUP" -C "$DEPLOY_PATH"

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data "$DEPLOY_PATH"
find "$DEPLOY_PATH" -type d -exec chmod 755 {} \;
find "$DEPLOY_PATH" -type f -exec chmod 644 {} \;

# Clear cache
echo "Clearing cache..."
wp cache flush --path="$DEPLOY_PATH/web"

echo "Restore completed successfully!"