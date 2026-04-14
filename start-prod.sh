#!/bin/bash
# Production run script for Chamilo 2.x on Replit autoscale (Cloud Run).
# Assumes build.sh has already run (composer install, cache:warmup, yarn build).
# Database: remote TCP via DATABASE_HOST/PORT Replit Secrets (no local MySQL).

set -e

echo "[prod] Starting Chamilo 2.x (APP_ENV=${APP_ENV:-prod})"

# Generate JWT keys if not present in this container.
# build.sh does not generate them; they must exist at runtime.
if [ ! -f config/jwt/private.pem ]; then
    mkdir -p config/jwt
    openssl genrsa -out config/jwt/private.pem 2048 2>/dev/null
    openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem 2>/dev/null
    echo "[prod] JWT keys generated"
fi

# Harden core Symfony config directories (read-only).
chmod -R 0555 \
    config/packages \
    config/routes \
    config/routes.yaml \
    config/services.yaml \
    config/bundles.php \
    config/preload.php \
    2>/dev/null || true
echo "[prod] Config permissions hardened (0555)"

# var/cache/prod/ and var/log/ must be writable at runtime.
mkdir -p var/cache/prod var/log
chmod -R 0755 var/cache var/log 2>/dev/null || true

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
