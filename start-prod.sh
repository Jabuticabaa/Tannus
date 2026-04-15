#!/bin/bash
# Production run script — Chamilo 2.x on Replit autoscale (Cloud Run).
# Build is handled by build.sh (composer, JWT keys, cache:warmup, yarn build).
# Database: remote TCP via DATABASE_* Replit Secrets (no local MySQL).
#
# PORT: Replit/Cloud Run injects $PORT dynamically. We fall back to 5000
# for local testing where $PORT is not set.

set -e

_PORT="${PORT:-5000}"
echo "[prod] Starting Chamilo 2.x (APP_ENV=${APP_ENV:-prod}, port=${_PORT})"

# Ensure runtime-writable directories exist.
mkdir -p var/log var/cache var/themes var/templates
chmod 0775 var/log var/cache 2>/dev/null || true

if [ -n "$PHP_INI_SCAN_DIR" ]; then
  echo "[prod] PHP_INI_SCAN_DIR: ${PHP_INI_SCAN_DIR}"
fi

echo "[prod] Starting PHP built-in server on 0.0.0.0:${_PORT} ..."
exec php \
    -d memory_limit=256M \
    -d upload_max_filesize=100M \
    -d post_max_size=100M \
    -d max_execution_time=300 \
    -d date.timezone=America/Sao_Paulo \
    -d display_errors=Off \
    -d log_errors=On \
    -S "0.0.0.0:${_PORT}" \
    -t public/ public/router.php
