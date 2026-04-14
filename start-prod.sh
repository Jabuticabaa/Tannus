#!/bin/bash
# Production run script for Chamilo 2.x on Replit autoscale (Cloud Run).
# Assumes build.sh has already run (composer install, JWT keys, cache:warmup, yarn build).
# Database: remote TCP via DATABASE_HOST/PORT Replit Secrets (no local MySQL).

set -e

echo "[prod] Starting Chamilo 2.x (APP_ENV=${APP_ENV:-prod})"

# Harden core Symfony config directories (read-only).
# Only targets specific files/dirs — avoids slow recursive chmod on var/cache.
chmod -R 0555 \
    config/packages \
    config/routes \
    config/routes.yaml \
    config/services.yaml \
    config/bundles.php \
    config/preload.php \
    2>/dev/null || true
echo "[prod] Config permissions hardened (0555)"

# Ensure runtime-writable directories exist.
# var/cache/prod is created by build.sh cache:warmup; var/log may be absent.
mkdir -p var/log
chmod 0775 var/log 2>/dev/null || true

echo "[prod] Starting PHP built-in server on 0.0.0.0:5000 ..."
exec php \
    -d memory_limit=256M \
    -d upload_max_filesize=100M \
    -d post_max_size=100M \
    -d max_execution_time=300 \
    -d date.timezone=America/Sao_Paulo \
    -d display_errors=Off \
    -d log_errors=On \
    -S 0.0.0.0:5000 \
    -t public/
