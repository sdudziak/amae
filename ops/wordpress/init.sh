#!/bin/bash
set -e

# Make sure the wp-content directory exists
mkdir -p /var/www/web/wp-content
if chown -R www-data:www-data /var/www/web/wp-content 2>/dev/null; then
    echo "[init] chown: ownership set to www-data:www-data for /var/www/web/wp-content"
else
    echo "[init] warning: chown not permitted on /var/www/web/wp-content; continuing without changing ownership"
fi

# If /var/www/html exists and is not a symlink, move any existing content to web
# But avoid modifying it if it's a bind mount (mountpoint) or if we don't have permission.
if [ -d "/var/www/html" ] && [ ! -L "/var/www/html" ]; then
    if mountpoint -q /var/www/html; then
        echo "[init] /var/www/html is a mountpoint; skipping move/remove"
    else
        if [ "$(ls -A /var/www/html)" ]; then
            mv /var/www/html/* /var/www/web/ 2>/dev/null || echo "[init] warning: mv failed or not permitted"
        fi
        if rm -rf /var/www/html 2>/tmp/rm_err.log; then
            echo "[init] removed /var/www/html"
        else
            if grep -q "Device or resource busy" /tmp/rm_err.log 2>/dev/null; then
                echo "[init] warning: /var/www/html is busy (mountpoint); skipping removal"
            else
                echo "[init] warning: rm -rf /var/www/html failed:"
                sed -n '1,120p' /tmp/rm_err.log || true
            fi
        fi
        rm -f /tmp/rm_err.log || true
    fi
fi

# Create symlink from /var/www/html to /var/www/web
ln -sfn /var/www/web /var/www/html

# Create necessary directories with correct permissions
mkdir -p /var/www/web/wp-content/{plugins,themes,uploads,upgrade}
if chown -R www-data:www-data /var/www/web/wp-content 2>/dev/null; then
    echo "[init] chown: ownership set to www-data:www-data for /var/www/web/wp-content (subdirs)"
else
    echo "[init] warning: chown not permitted on /var/www/web/wp-content subdirs; attempting permissive chmod"
    chmod -R u+rwX,g+rwX,o+rX /var/www/web/wp-content || true
fi

# Execute php-fpm directly (avoid invoking the official wordpress entrypoint which may try to modify mounts)
exec php-fpm