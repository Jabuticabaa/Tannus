#!/bin/bash
set -e

# Resolve memory limit for the build phase.
# PHP_MEMORY_LIMIT may be available as a Replit Secret (build context) or via
# [userenv.shared] (run context). Using -1 (unlimited) as fallback so PhpDumper
# and other memory-intensive compilation steps never hit the 128MB system default.
_MEM_LIMIT="${PHP_MEMORY_LIMIT:--1}"

echo "[build] PHP_MEMORY_LIMIT: ${_MEM_LIMIT}"

# Create a temp directory with a custom .ini file that sets memory_limit = -1.
# PHP_INI_SCAN_DIR is inherited by every child process regardless of how it is
# spawned, ensuring unlimited memory for the entire build chain (including children
# of children that do not receive the parent's -d flags).
# IMPORTANT: We APPEND to the existing scan dir (colon-separated) rather than
# replacing it, so that extension/module .ini files in the default scan path
# (e.g. /etc/php.d or /nix/store/.../conf.d) are still loaded by child processes.
# PHP_INI_SCAN_DIR is set as a Replit shared env var and already contains the
# Nix extensions path + config/php-cli/99-memory.ini (memory_limit=-1).
# We APPEND a fresh temp dir so any further per-build ini tweaks are also picked
# up by ALL child processes (grandchildren of composer hooks, etc.).
# IMPORTANT: Use the ENV VAR value as the base — NOT PHP_CONFIG_FILE_SCAN_DIR
# (the compiled-in PHP constant), which is always "" on Nix PHP builds and would
# silently drop all extension .ini files if used as the base.
_INI_SCAN_DIR="$(mktemp -d)"
cat > "${_INI_SCAN_DIR}/99-memory.ini" <<EOF
memory_limit = -1
max_execution_time = 0
EOF
_BASE_SCAN_DIR="${PHP_INI_SCAN_DIR:-$(php -r 'echo php_ini_scanned_files() ? dirname(explode(",",php_ini_scanned_files())[0]) : "";')}"
export PHP_INI_SCAN_DIR="${_BASE_SCAN_DIR:+${_BASE_SCAN_DIR}:}${_INI_SCAN_DIR}"

# PHPRC is set as a Replit shared env var → /home/runner/workspace/php.ini.
# Re-export with the absolute pwd path so it also works if CWD shifts during build.
export PHPRC="$(pwd)/php.ini"
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

# STEP 2: Generate JWT keys if absent.
# Keys are in .gitignore so a fresh deployment container will not have them.
# Generating during build bakes them into the image so start-prod.sh never
# has to wait for key generation on the critical request-serving path.
echo "[build] Checking JWT keys ..."
if [ ! -f config/jwt/private.pem ]; then
    mkdir -p config/jwt
    openssl genrsa -out config/jwt/private.pem 2048 2>/dev/null
    openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem 2>/dev/null
    echo "[build] JWT keys generated."
else
    echo "[build] JWT keys already present — skipping."
fi

# STEP 3: Clear bootstrap cache before compiling the DI container.
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
# Force HTTPS for all GitHub git operations — the deploy container has no SSH keys.
git config --global url."https://github.com/".insteadOf "git@github.com:"
git config --global --add url."https://github.com/".insteadOf "ssh://git@github.com/"
echo "[build] yarn install ..."
yarn install
echo "[build] yarn install done."
echo "[build] yarn build ..."
yarn build
echo "[build] Build complete."
