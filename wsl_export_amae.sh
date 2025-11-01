#!/usr/bin/env bash
set -euo pipefail

# --- simple logger --------------------------------------------------------
# LOG_LEVEL can be set to DEBUG, INFO, WARN, ERROR (default INFO)
LOG_LEVEL=${LOG_LEVEL:-INFO}
_log_level_num() {
  case "${1^^}" in
    DEBUG) echo 0 ;;
    INFO)  echo 1 ;;
    WARN)  echo 2 ;;
    ERROR) echo 3 ;;
    *) echo 1 ;;
  esac
}
_cur_level=$(_log_level_num "$LOG_LEVEL")
log() {
  local level="$1"; shift
  local msg="$*"
  local lvlnum=$(_log_level_num "$level")
  if [ "$lvlnum" -ge "${_cur_level}" ]; then
    printf '%s %s: %s\n' "$(date -Iseconds)" "$level" "$msg" >&2
  fi
}
debug() { log DEBUG "$*"; }
info()  { log INFO  "$*"; }
warn()  { log WARN  "$*"; }
error() { log ERROR "$*"; }

# Track last command for better diagnostics
last_cmd=''
current_cmd=''
trap 'last_cmd=$current_cmd; current_cmd=$BASH_COMMAND' DEBUG
trap 'rc=$?; if [ $rc -ne 0 ]; then error "Script failed with exit $rc near: $last_cmd"; fi' EXIT
# -------------------------------------------------------------------------

DB_SERVICE=${DB_SERVICE:-db}
DB_CONTAINER=${DB_CONTAINER:-relax-hub-db}

OUT=backup-amae-$(date +%F)
mkdir -p "$OUT"

# Ensure docker is present
if ! command -v docker >/dev/null 2>&1; then
  error "docker is required but not installed"
  exit 1
fi

info "Starting backup generation: $OUT"

info "Pulling images (docker compose pull)"
docker compose pull
info "Resolving compose config"
docker compose config > docker-compose.resolved.yml
info "Bringing compose services down"
docker compose down

# Dump DB
# Start the DB service via compose, target the actual container name for docker exec
info "Starting DB service: ${DB_SERVICE} (expecting container name: ${DB_CONTAINER})"
docker compose up -d "${DB_SERVICE}"
# give the DB some time to start; for more robust wait replace with a healthcheck loop
sleep 5
info "Dumping database to $OUT/db.sql"
# Try to use mysqldump or mariadb-dump inside the container; exit 127 if none found
docker exec -i "${DB_CONTAINER}" sh -lc '
  set -e
  if command -v mysqldump >/dev/null 2>&1; then
    exec mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --databases "$MYSQL_DATABASE" --routines --events --single-transaction --quick
  elif command -v mariadb-dump >/dev/null 2>&1; then
    exec mariadb-dump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --databases "$MYSQL_DATABASE" --routines --events --single-transaction --quick
  else
    echo "No dump utility found (mysqldump or mariadb-dump)" >&2
    exit 127
  fi
' > "$OUT/db.sql"
info "Stopping DB service: ${DB_SERVICE}"
docker compose stop "${DB_SERVICE}"

# Media
if [ -d "./app/wp-content/uploads" ]; then
  info "Archiving uploads to $OUT/uploads.tar.gz"
  tar -czf "$OUT/uploads.tar.gz" -C ./app wp-content/uploads
else
  warn "Uploads directory not found, skipping uploads archive"
fi

# Wolumen DB (opcjonalnie)
mkdir -p "$OUT/export"
info "Exporting db_data volume to $OUT/export/db_data.tar"
# Use absolute path for the host bind mount to avoid issues with relative paths/spaces
docker run --rm -v db_data:/from -v "$(pwd)/$OUT/export":/to busybox sh -c 'cd /from && tar cf /to/db_data.tar .'

# Kod i pliki compose
git rev-parse --short HEAD > .gitref 2>/dev/null || true
info "Packing compose and env files"
tar -czf "$OUT/backup-code.tar.gz" .env docker-compose.yml docker-compose.override.yml docker-compose.resolved.yml .gitref 2>/dev/null || true
cp .env "$OUT/" 2>/dev/null || true

info "Backup ready: $OUT/"
