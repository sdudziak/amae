#!/bin/bash
set -e

# Configuration
APP_NAME="amae"
ARTIFACT_NAME="$APP_NAME.tar.gz"
DEPLOY_PATH="/var/www/$APP_NAME"
BACKUP_PATH="/var/backups/$APP_NAME"

# Create backup
./backup.sh

# Download artifact from CI
echo "Downloading artifact from CI..."
scp "$CI_SERVER:$ARTIFACT_PATH/$ARTIFACT_NAME" /tmp/

# Unpack artifact
echo "Unpacking artifact..."
tar -xzf "/tmp/$ARTIFACT_NAME" -C "$DEPLOY_PATH"

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data "$DEPLOY_PATH"
find "$DEPLOY_PATH" -type d -exec chmod 755 {} \;
find "$DEPLOY_PATH" -type f -exec chmod 644 {} \;

# Apply nginx config
echo "Applying nginx configuration..."
cp ../templates/nginx.conf /etc/nginx/sites-available/$APP_NAME
ln -sf /etc/nginx/sites-available/$APP_NAME /etc/nginx/sites-enabled/

# Reload services
echo "Reloading services..."
systemctl reload nginx
systemctl reload php8.1-fpm

# Clear cache
echo "Clearing cache..."
wp cache flush --path="$DEPLOY_PATH/web"

echo "Deployment completed successfully!"