#!/usr/bin/env bash
set -Eeuo pipefail

# --- Load .env from repo root ---
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

if [[ -f "${PROJECT_ROOT}/.env" ]]; then
  set -a
  # shellcheck disable=SC1091
  source "${PROJECT_ROOT}/.env"
  set +a
else
  echo "ERROR: .env not found in ${PROJECT_ROOT}"
  exit 1
fi

cd "${PROJECT_ROOT}"

# --- Helpers ---
# Run wp-cli via the dedicated compose service, with tmp HOME to avoid cache warnings
wp() { docker compose run --rm -e HOME=/tmp -e WP_CLI_CACHE_DIR=/tmp/wp-cli-cache wpcli "$@"; }

# Optional: wait until DB is healthy (when compose's health doesn't propagate fast enough)
echo "==> Ensuring DB is healthy..."
for _ in {1..60}; do
  if docker compose ps --format json | grep -q '"db".*healthy'; then break; fi
  sleep 2
done

echo "==> Configuring site settings..."
# Locale / timezone / permalinks
wp language core install "${WP_LOCALE:-pl_PL}" --activate || true
wp option update timezone_string "${WP_TIMEZONE:-Europe/Warsaw}" || true
wp rewrite structure '/%postname%/' --hard || true
wp rewrite flush --hard || true

# Create and set Front Page
HOME_ID="$(wp post create --post_type=page --post_status=publish --post_title='Home' --porcelain || true)"
if [[ -n "${HOME_ID}" ]]; then
  wp option update page_on_front "${HOME_ID}" || true
  wp option update show_on_front page || true
fi

# Theme
wp theme install blocksy --activate || wp theme activate twentytwentyfive || true

# Plugins (core set)
plugins=(
  paid-memberships-pro
  wp-mail-smtp
  seo-by-rank-math
  all-in-one-wp-security-and-firewall
  updraftplus
  antispam-bee
  complianz-gdpr
  autoptimize
  wp-super-cache
  seriously-simple-podcasting
  query-monitor
  health-check
)
for p in "${plugins[@]}"; do
  wp plugin install "$p" --activate || wp plugin activate "$p" || true
done

# MU plugins directory (use WP to ensure correct path/permissions)
wp eval 'wp_mkdir_p( WP_CONTENT_DIR . "/mu-plugins" );' || true

# Flush again after plugins
wp rewrite flush --hard || true

echo "==> Bootstrap done. Login: ${WP_ADMIN_USER:-admin} / ${WP_ADMIN_PASSWORD:-admin123}"
