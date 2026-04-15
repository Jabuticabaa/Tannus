# Chamilo LMS 2.x — Inventário de Customizações

---
Version: 2.0
Last updated: 2026-04-15 (Task #29 — debug, config, deploy, docs)
Status: Active
Owner: Project maintainer

---

> **Regra de ouro**: Todo código novo vai em `public/plugin/[nome]/` ou em arquivos com sufixo `_custom`.
> Nunca edite arquivos do core sem documentar o diff, o motivo e a versão afetada neste arquivo.

---

## Inventário Completo

| Data | Arquivo / Diretório | Tipo | Motivo | Risco de Conflito | Estratégia |
|---|---|---|---|---|---|
| 2026-04-12 | `start.sh` (criado) | Config / Infra | Script de inicialização Replit: MySQL, JWT, cache, PHP server | Baixo — arquivo específico do Replit | Manter como arquivo Replit-only |
| 2026-04-12 | `.replit` (criado) | Config / Infra | Configuração de workflows, portas e deployment Cloud Run | Baixo — arquivo específico do Replit | Manter separado |
| 2026-04-12 | `replit.nix` (criado) | Config / Infra | Dependências Nix: mysql80, php82Extensions.xsl | Baixo — específico Nix | Atualizar com PHP/MySQL |
| 2026-04-13 | `start.sh` — MySQL init | Config / Infra | `mkdir -p`, `mysqld --initialize-insecure` para primeira execução | Baixo | Parte do start.sh |
| 2026-04-13 | `start.sh` — socket symlink | Config / Infra | Symlink `/run/mysqld/mysqld.sock` | Baixo | Parte do start.sh |
| 2026-04-13 | `start.sh` — `php -S` flags | Config / Infra | Flags de runtime: socket, memória, upload, timezone | Baixo | Parte do start.sh |
| 2026-04-13 | `composer.json` — remoção `twig/inky-extra` | Dependência | `lorenzo/pinky` exigia `ext-xsl` ausente | Baixo | Seguro — nenhum template usa Inky |
| 2026-04-14 | `public/main/install/` — remoção física | Segurança | Diretório removido; HTTP 404 confirmado | Nenhum | Permanente — não recriar |
| 2026-04-14 | `public/check.php` — removido | Segurança | Requirements Checker legado exposto via proxy | Nenhum | Não restaurar |
| 2026-04-14 | `src/CoreBundle/Controller/TannusIaController.php` | Feature | Rotas `/TannusIA` e `/TannusAI` — landing page tech premium | Baixo | Manter |
| 2026-04-14 | `src/CoreBundle/Controller/DocumentPageController.php` | Feature | Rota `/document/upload` — upload de `.docx` | Baixo | Manter |
| 2026-04-14 | `scripts/mammoth_convert.js` | Feature | Converter .docx → HTML via mammoth | Baixo | Dep: mammoth npm |
| 2026-04-14 | `src/CoreBundle/Resources/views/TannusIa/view.html.twig` | Feature | Landing page: hero, capacidades, métricas, CTAs | Baixo | Manter |
| 2026-04-14 | `public/css/tannus-design-system.css` | Feature | CSS design system dark/light | Baixo | Manter |
| 2026-04-14 | `assets/css/document-page.css` | Feature | CSS docx-to-web | Baixo | Entry webpack |
| 2026-04-14 | `webpack.config.js` — addStyleEntry | Feature | Entry `css/document-page` | Baixo | Rebuild necessário |
| 2026-04-14 | `.gitignore` — uploads | Config | `public/uploads/documents/*` | Baixo | Manter |
| 2026-04-14 | `SettingsValueTemplateFixtures.php` | Segurança | Valores de exemplo → placeholders | Baixo | Manter |
| 2026-04-14 | `.env.example` | Docs | Template de referência para env vars | Nenhum | Sincronizar |
| 2026-04-14 | `.env` — credenciais neutralizadas | Segurança | 7 valores hardcoded → placeholders | Nenhum | Replit Secrets fornecem valores |
| 2026-04-14 | `start.sh` — timezone MySQL | Config | `SET GLOBAL time_zone = '-03:00'` | Baixo | Reaplicar a cada restart |
| 2026-04-14 | `start.sh` — build síncrono | Config | Race condition corrigida: build antes do PHP server | Nenhum | Manter |
| 2026-04-14 | `build.sh` — OOM fix | Config / Deploy | `php -d memory_limit=...`, assets explícito | Baixo | Revalidar |
| 2026-04-14 | `composer.json` — `memory-limit: -1` | Config | Composer respeita COMPOSER_MEMORY_LIMIT | Baixo | Manter |
| 2026-04-15 | `public/router.php` — **criado** (Task #29) | Config / Infra | Router script para PHP built-in server. PHP -S retorna 404 para URLs com extensão de arquivo quando o arquivo não existe fisicamente em `public/`. O router.php intercepta esses requests e os encaminha ao Symfony kernel, que então os serve via ThemeController (Flysystem) ou PwaController. **Corrige:** `/themes/chamilo/colors.css`, `/themes/chamilo/tiny-settings.js`, `/themes/chamilo/images/favicon.ico`, `/manifest.json`. Duplica a lógica de boot do `index.php` (autoload_runtime + Kernel) porque Symfony Runtime exige que o script chamador retorne um callable. | Baixo — arquivo separado, não altera index.php | Obrigatório em `start.sh` e `start-prod.sh`; atualizar se `index.php` mudar |
| 2026-04-15 | `start.sh` — router.php (Task #29) | Config / Infra | Adicionado `public/router.php` como argumento ao `php -S` | Baixo | Manter |
| 2026-04-15 | `start-prod.sh` — router.php (Task #29) | Config / Infra | Idem ao start.sh | Baixo | Manter |
| 2026-04-15 | `.env.local` — **criado** (Task #29) | Config | `APP_LOCALE=pt_BR`, `TRUSTED_PROXIES=0.0.0.0/0`, CORS expandido para `*.replit.dev` e `*.replit.app`. Gitignored — não versionado. | Baixo | Recriar em ambientes novos |
| 2026-04-15 | `view.html.twig` — JSON-LD schema (Task #29) | SEO | Schema markup: Organization + WebSite + SoftwareApplication no bloco `{% block stylesheets %}` | Nenhum | Atualizar se dados mudarem |

---

## Arquivos NÃO versionados (gitignore)

| Arquivo / Diretório | Motivo |
|---|---|
| `vendor/` | Dependências Composer |
| `public/build/` | Assets compilados |
| `var/cache/` | Cache Symfony |
| `var/log/` | Logs |
| `config/jwt/*.pem` | Chaves JWT — geradas em runtime |
| `.env.local` | Overrides de ambiente |

---

## Arquivos que NÃO foram modificados

| Arquivo | Motivo de NÃO modificar |
|---|---|
| `src/CoreBundle/Entity/*.php` | Schema já atende ao projeto |
| `config/packages/*.yaml` | Configuração padrão adequada (exceto security.yaml access_control) |
| `assets/vue/` | Sem customizações de frontend |
| `public/plugin/HelloWorld/` | Plugin de exemplo — não utilizado |

---

## Changelog

| Versão | Data | Autor | Descrição |
|---|---|---|---|
| 1.0 | 2026-04-14 | Agent | Inventário inicial completo pós-instalação |
<<<<<<< HEAD
| 1.1 | 2026-04-14 | Agent | FASE 0: remoção de public/check.php (exposto, sem referências internas, legado Symfony 2/3/4) |
| 1.2 | 2026-04-14 | Agent | FASE 2.3: criação de .env.example; APP_SECRET movido para Replit Secret; JWT_PASSPHRASE analisado e mantido em .env (chave JWT sem passphrase, valor ignorado pelo bundle) |
| 1.3 | 2026-04-14 | Agent | FASE 2.2: start.sh — bloco timezone MySQL adicionado (`SET GLOBAL time_zone = '-03:00'`); Gap #4 encerrado |
| 1.4 | 2026-04-14 | Agent | FASE 2.1: start.sh — build síncrono (race condition corrigida); replit.md atualizado; ROADMAP.md gaps fechados |
| 1.5 | 2026-04-14 | Agent | FASE 2 (Task #15): remoção física de public/main/install/ do disco; HTTP 409 → 404; FASE 3 confirmada (config perms 0555 via start.sh); documentação atualizada com outputs reais |
| 1.6 | 2026-04-14 | Agent | Task #16: fix OOM no build — build.sh reescrito (php -d, hard gate removido, assets explícito); composer.json (memory-limit + --no-scripts); PHP_MEMORY_LIMIT=512M e COMPOSER_MEMORY_LIMIT=-1 como shared env vars |
| 1.7 | 2026-04-14 | Agent | Task #18 (FINAL): .env — 7 credenciais neutralizadas via sed (DATABASE_*='' APP_SECRET='' JWT_PASSPHRASE=''); JWT_PASSPHRASE → Replit Secret (length=64, hex); todos os Replit Secrets validados em runtime; DB prod OK (tables=317); HTTP 200 |
| 1.8 | 2026-04-14 | Agent | Task #26: /TannusIA reconstruído como landing page tech premium — dark theme teal gradient; hero com badge + CTAs; 4 capacidades com ícones SVG; métricas reais do DB (Doctrine DBAL); 3-step "Como funciona"; 6 quick-access chips; dark/light toggle; animated counters (IntersectionObserver); CSS design system separado; controller reescrito sem DocxConverterService |
| 1.9 | 2026-04-15 | Agent | Task #27: migração de rotas — / e /home → TannusIaController (landing page premium); /TannusIA e /TannusAI → TannusPitchController (pitch deck DOCX interativo); IndexController: rotas / e /home removidas; security.yaml: PUBLIC_ACCESS para /, /home, /api/tannus-chat; TannusPitch view.html.twig com sidebar TOC navigável (52 itens), scrollspy IntersectionObserver, CTA final destacado (R$ 997.000/18 meses/50-50), dark theme; public/js/tannus-pitch.js; CSS pitch deck adicionado ao design system; HTTP 200 confirmado em todas as 4 rotas |
| 2.0 | 2026-04-15 | Agent | Task #29: router.php (fix ALL 404s); .env.local (TRUSTED_PROXIES, CORS, locale pt_BR); schema markup JSON-LD (Organization+WebSite+SoftwareApplication); replit.md atualizado (chmod 0555 removido, router.php documentado); ARCHITECTURE.md v2.0; ROADMAP.md v2.0; .env.example atualizado |

---

## Task #27 — Entradas do Inventário

| Data | Arquivo / Diretório | Tipo | Motivo | Risco de Conflito | Estratégia |
|---|---|---|---|---|---|
| 2026-04-15 | `src/CoreBundle/Controller/TannusIaController.php` — rotas alteradas | Feature | Adicionadas rotas / (name: index) e /home (name: home); rotas /TannusIA e /TannusAI movidas para TannusPitchController | Baixo — rotas / e /home transferidas do IndexController; nomes index e home mantidos para não quebrar referências internas | Manter; IndexController.php documentado como modificado |
| 2026-04-15 | `src/CoreBundle/Controller/IndexController.php` — rotas / e /home removidas | Core (modificado) | Remoção das rotas `index` (/) e `home` (/home) do IndexController para permitir que TannusIaController as sirva com a landing page premium | Médio — arquivo de core; nomes de rota `index` e `home` preservados em TannusIaController | Documentar diff; não reverter; se Chamilo upstream reintroduzir, reaplicar remoção |
| 2026-04-15 | `src/CoreBundle/Controller/TannusPitchController.php` — **criado** | Feature | Controller com rotas /TannusIA e /TannusAI; injeta DocxConverterService; converte TANNUS_-_PLANO_DE_NEGÓCIOS_1776210166304.docx via mammoth; passa title, subtitle, html_content, toc ao template | Baixo — controller isolado | Manter; atualizar path DOCX se arquivo mudar |
| 2026-04-15 | `src/CoreBundle/Resources/views/TannusPitch/view.html.twig` — **criado** | Feature | Template pitch deck: header sticky com logo + link Entrar; layout 2 colunas (sidebar esquerda TOC + corpo principal); sidebar com offcanvas mobile; cover com badge Confidencial + title/subtitle do DOCX; doc-prose HTML gerado pelo mammoth; CTA destacado (R$ 997.000 / 18 meses / 50/50); dark theme com CSS vars do design system | Baixo — template isolado | Manter |
| 2026-04-15 | `public/js/tannus-pitch.js` — **criado** | Feature | IntersectionObserver scrollspy para TOC; sidebar mobile toggle (hamburger + overlay + close); smooth scroll para anchors; phase-card wrapping para seções de fase (regex 01–05); table visual wrapper; theme toggle reaproveitando localStorage | Baixo — script standalone | Manter |
| 2026-04-15 | `public/css/tannus-design-system.css` — extensão (pitch deck) | Feature | Adicionadas classes: .pitch-sidebar, .pitch-content, .pitch-phase-card, .pitch-cta, .pitch-metric-grid, .pitch-table-visual, .doc-prose, .pitch-toc__*, .pitch-cover__*; media queries offcanvas mobile; desktop: sidebar sticky 280px fixed; nenhuma regra existente removida | Baixo — apenas adição ao final do arquivo | Manter |
| 2026-04-15 | `config/packages/security.yaml` — access_control | Config/Segurança | Adicionadas entradas PUBLIC_ACCESS para ^/$ (raiz), ^/home(/\|$) e ^/api/tannus-chat; /TannusIA e /TannusAI já existiam | Baixo — amplia acesso público; não remove restrições existentes | Manter |
