# Chamilo 2.0 LMS — Tannus IA

## Overview
Chamilo is an open-source Learning Management System (LMS) and e-learning collaboration platform. This is version 2.0, customized with Tannus IA branding, built with Symfony 6.4 (PHP) and Vue.js 3.

## Tech Stack
- **Backend**: PHP 8.2 + Symfony 6.4 + Doctrine ORM
- **Frontend**: Vue.js 3, Webpack Encore, Tailwind CSS, SCSS
- **API**: API Platform 3.0, JWT Authentication
- **Database**: MySQL 8.0 (local dev via socket, prod via Replit Secrets)
- **Package Managers**: Composer (PHP), Yarn 4 (JS)

## Architecture
- `src/` - Symfony PHP source (CoreBundle, CourseBundle, LtiBundle)
- `assets/` - Frontend source (Vue components, CSS/SCSS, JS)
- `public/` - Web root (index.php, router.php, build assets)
- `config/` - Symfony configuration
- `translations/` - i18n translation files
- `var/` - Cache, logs, themes, Twig templates
- `scripts/` - Node.js utility scripts (mammoth_convert.js for docx→HTML)

## Routes
| Route | Controller | Description |
|---|---|---|
| `/` | IndexController | Chamilo home / landing |
| `/TannusIA` | TannusIaController | Landing page tech premium Tannus IA |
| `/TannusAI` | TannusIaController (alias) | Alias da landing page |
| `/login` | IndexController | Login page |
| `/document/upload` | DocumentPageController | Upload genérico de .docx |
| `/manifest.json` | PwaController | PWA manifest (dynamic) |
| `/themes/{name}/{path}` | ThemeController | Theme assets via Flysystem |

## Running the App
The `start.sh` script handles everything:
1. Starts MySQL 8.0 server on port 3306
2. Creates the `chamilo` database if not present
3. Aligns MySQL timezone to America/Sao_Paulo (-03:00)
4. Generates JWT keys if missing
5. Clears Symfony cache
6. Builds frontend assets synchronously if not built (first run takes ~3 min)
7. Starts PHP built-in server on port 5000 with `router.php`

### Router Script (`public/router.php`)
PHP's built-in server doesn't forward requests with file extensions (`.css`, `.js`, `.ico`, `.json`) to the Symfony router. The `router.php` script ensures ALL requests pass through Symfony when no matching static file exists in `public/`. This fixes theme assets, manifest.json, and favicon.ico.

## Database Configuration
- **Dev**: Socket `/home/runner/mysql_run/mysql.sock`, DB `chamilo`, User `chamilo`/`chamilo_pass`
- **Prod**: TCP via Replit Secrets (`DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD`)
- MySQL data dir: `/home/runner/mysql_data`

## Environment Variables

### `.env` (defaults, committed)
- `APP_ENV=dev`, `APP_DEBUG=1`, `APP_LOCALE=pt_BR`
- `APP_INSTALLED=1`
- `TRUSTED_PROXIES=127.0.0.1`
- Placeholder values for secrets (`<configurar_via_Replit_Secret>`)

### `.env.local` (overrides, gitignored)
- `APP_LOCALE=pt_BR`
- `TRUSTED_PROXIES=0.0.0.0/0` (for Replit proxy)
- `CORS_ALLOW_ORIGIN` expanded for `.replit.dev` and `.replit.app`

### Replit Secrets (runtime, never in code)
- `APP_SECRET` (64-char hex)
- `JWT_PASSPHRASE` (64-char hex)
- `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD`

### Production env vars (`.replit` [userenv.production])
- `APP_INSTALLED=1`, `APP_ENV=prod`

### Shared env vars (`.replit` [userenv.shared])
- `PHP_MEMORY_LIMIT=512M`, `COMPOSER_MEMORY_LIMIT=-1`
- `PHPRC`, `PHP_INI_SCAN_DIR`

## Build & Deploy
- **Dev startup**: `bash start.sh` (workflow "Start application")
- **Build (deploy)**: `bash build.sh` — composer install, JWT keys, cache warmup, yarn build
- **Run (deploy)**: `bash start-prod.sh` — PHP server on `${PORT:-5000}`
- **Target**: Cloud Run autoscale (`deploymentTarget = "autoscale"`)
- **Port**: 5000 internal → 80 external

## Frontend Assets
Built with Webpack Encore.
- Dev: `yarn dev`
- Production: `yarn build`
- Watch mode: `yarn watch`

## PHP Runtime Configuration
Flags set via `start.sh` / `start-prod.sh`:
- `memory_limit=256M`
- `upload_max_filesize=100M`, `post_max_size=100M`
- `max_execution_time=300`
- `date.timezone=America/Sao_Paulo`
- Socket paths for MySQL

## Project Documentation
- `ARCHITECTURE.md` — Full architecture doc: stack, directory structure, extension points
- `CUSTOMIZATIONS.md` — Complete inventory of all changes made (v2.0)
- `ROADMAP.md` — Production gaps, planned extensions, update rules
- `DEVELOPMENT_LOG.md` — Detailed log of all verified actions with command outputs

## Security
- Installer (`public/main/install/`) removed post-installation
- `public/check.php` removed (legacy Symfony 2/3/4 requirements checker)
- Secrets stored as Replit Secrets, not in committed files
- `.env` contains only placeholders for sensitive values
- JWT keys generated at runtime (in `.gitignore`)

## Deployment Notes
- **Ephemeral filesystem**: Cloud Run wipes MySQL data on each redeploy
- For persistent data: migrate to an external MySQL/PostgreSQL service
- `start.sh` auto-initializes MySQL data directory on fresh containers
- `router.php` is required for both dev and prod to route theme assets correctly

## Known Limitations
- PHP built-in server is single-thread (adequate for demo/dev)
- `xsl` PHP extension declared in replit.nix but not active (no impact — twig/inky-extra was removed)
- Doctrine migrations table `version` does not exist (wizard created tables directly)
- Browser console shows caught exception `{}` on `/` page (Replit iframe cross-origin restriction, not a bug)
