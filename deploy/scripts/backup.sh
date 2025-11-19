#!/bin/bash
set -e

# Configuration
APP_NAME="amae"
DEPLOY_PATH="/var/www/$APP_NAME"
BACKUP_PATH="/var/backups/$APP_NAME"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_PATH"

# Backup database
echo "Creating database backup..."
wp db export "$BACKUP_PATH/db_$DATE.sql" --path="$DEPLOY_PATH/web"

# Backup files
echo "Creating files backup..."
tar -czf "$BACKUP_PATH/files_$DATE.tar.gz" -C "$DEPLOY_PATH" .

# Cleanup old backups (keep last 5)
echo "Cleaning up old backups..."
ls -t "$BACKUP_PATH"/db_* | tail -n +6 | xargs -r rm
ls -t "$BACKUP_PATH"/files_* | tail -n +6 | xargs -r rm

echo "Backup completed successfully!"