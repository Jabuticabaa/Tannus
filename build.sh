#!/bin/bash
set -e

# Resolve memory limit for the build phase.
# PHP_MEMORY_LIMIT is only injected in the run context ([userenv.shared]), NOT in the
# autoscale build context. Using -1 (unlimited) as fallback so PhpDumper and other
# memory-intensive compilation steps never hit the 128MB system default.
_MEM_LIMIT="${PHP_MEMORY_LIMIT:--1}"

echo "[build] PHP_MEMORY_LIMIT: ${_MEM_LIMIT}"

# Write ~/.php.ini so every PHP CLI subprocess picks up the user-ini automatically.
cat > ~/.php.ini <<EOF
memory_limit = ${_MEM_LIMIT}
max_execution_time = 0
date.timezone = America/Sao_Paulo
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
log_errors = On
EOF

# Also update the project-root php.ini loaded via PHPRC.
export PHPRC="$(pwd)"
sed -i "s/^memory_limit[[:space:]]*=.*/memory_limit = ${_MEM_LIMIT}/" php.ini

export COMPOSER_MEMORY_LIMIT=-1

# Compile for production.
export APP_ENV=prod

_COMPOSER="$(which composer)"

echo "[build] Composer: ${_COMPOSER}"
echo "[build] PHP: $(php -d memory_limit=${_MEM_LIMIT} -r 'echo PHP_VERSION;')"
echo "[build] memory_limit: $(php -d memory_limit=${_MEM_LIMIT} -r 'echo ini_get("memory_limit");')"

# STEP 1: composer install --no-scripts
# Rationale: Composer's post-install-cmd scripts run as child processes that
# do NOT inherit the parent's -d memory_limit flag. Running scripts inside
# Composer (default) causes OOM in PhpDumper at the system default of 128MB.
# Scripts are executed explicitly below with a controlled memory limit.
echo "[build] composer install --no-scripts ..."
php -d memory_limit=${_MEM_LIMIT} \
    -d max_execution_time=0 \
    -d date.timezone=America/Sao_Paulo \
    "${_COMPOSER}" install --no-dev --optimize-autoloader --no-scripts
echo "[build] composer install done."

# STEP 2: Clear bootstrap cache before compiling the DI container.
echo "[build] cache:clear --no-warmup ..."
php -d memory_limit=${_MEM_LIMIT} \
    -d max_execution_time=0 \
    bin/console cache:clear --no-warmup --no-debug
echo "[build] cache:clear done."

# STEP 3: Install bundle assets into public/.
echo "[build] assets:install ..."
php -d memory_limit=${_MEM_LIMIT} \
    -d max_execution_time=0 \
    -d date.timezone=America/Sao_Paulo \
    bin/console assets:install public --no-debug
echo "[build] assets:install done."

# STEP 4: Warm up the DI container so it is compiled before the first request.
# The warmed cache is kept intentionally — deleting it after warmup would be
# contradictory and force recompilation at request time.
echo "[build] cache:warmup ..."
php -d memory_limit=${_MEM_LIMIT} \
    -d max_execution_time=0 \
    -d date.timezone=America/Sao_Paulo \
    bin/console cache:warmup --no-debug
echo "[build] cache:warmup done."

# STEP 5: Build frontend assets.
echo "[build] yarn build ..."
yarn build
echo "[build] Build complete."
