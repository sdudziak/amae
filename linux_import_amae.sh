#!/usr/bin/env bash
set -euo pipefail

# Usage: linux_import_amae.sh /path/to/backup-dir
BACKUP_DIR=${1:?
  "Podaj ścieżkę do katalogu backupu, np. /home/user/amae-backup/backup-amae-2025-10-29"
}

echo "Using backup dir: $BACKUP_DIR"

# copy .env if present
if [ -f "$BACKUP_DIR/.env" ]; then
  cp "$BACKUP_DIR/.env" ./
  echo "Copied .env"
else
  echo "Warning: .env not found in $BACKUP_DIR, continuing..." >&2
fi

# restore uploads (tarball should contain wp-content/uploads or uploads tree)
mkdir -p data/wp-content/uploads
if [ -f "$BACKUP_DIR/uploads.tar.gz" ]; then
  echo "Extracting uploads..."
  tar -xzf "$BACKUP_DIR/uploads.tar.gz" -C ./data
else
  echo "No uploads archive found at $BACKUP_DIR/uploads.tar.gz, skipping..."
fi

# Load environment variables from .env if it exists locally
if [ -f ./.env ]; then
  # shellcheck disable=SC1091
  . ./.env
fi

echo "Starting MariaDB service..."
docker compose up -d db

# wait for MariaDB to be ready (attempt for up to 30s)
echo "Waiting for MariaDB to be ready..."
MAX_WAIT=30
WAITED=0
while ! docker compose exec -T db sh -c '
  if command -v mariadb-admin >/dev/null 2>&1; then
    # use root credentials for ping (matches docker-compose healthcheck)
    mariadb-admin ping -uroot -p"${MYSQL_ROOT_PASSWORD}" -h 127.0.0.1 --silent
  elif command -v mariadb >/dev/null 2>&1; then
    mariadb --user="${MYSQL_USER}" --password="${MYSQL_PASSWORD}" -e "SELECT 1" >/dev/null 2>&1
  else
    echo "No MariaDB client tools found in container" >&2
    exit 1
  fi
' >/dev/null 2>&1; do
  sleep 1
  WAITED=$((WAITED + 1))
  if [ "$WAITED" -ge "$MAX_WAIT" ]; then
    echo "Database did not become ready after ${MAX_WAIT}s" >&2
    exit 1
  fi
done
echo "Database is ready"

# import database if present (use mysql or mariadb client inside container)
if [ -f "$BACKUP_DIR/db.sql" ]; then
  echo "Importing database from $BACKUP_DIR/db.sql"
  docker compose exec -T db sh -c '
    if command -v mariadb >/dev/null 2>&1; then
      mariadb --user="${MYSQL_USER}" --password="${MYSQL_PASSWORD}" "${MYSQL_DATABASE}"
    else
      echo "MariaDB client not found in container" >&2
      exit 1
    fi
  ' < "$BACKUP_DIR/db.sql"
  echo "Database import finished"
else
  echo "No db.sql found in $BACKUP_DIR, skipping import"
fi

echo "Starting remaining services..."
docker compose up -d
docker compose ps
