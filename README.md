# App

App is the core host Symfony application for the multi-domain SaaS platform. It is the root composition host: it coordinates and integrates infrastructure and product/capability components into one runtime and may expose their local Composer path symlinks during development.

This project is the main runtime execution boundary and deployment target.

## Current Posture

### What the component already does
- Orchestrates domain components into a unified execution kernel.
- Composes platform packages through explicit Composer dependencies and local development path symlinks; Canon053 component-to-component isolation does not apply to this root host.
- Runs database migrations, entity integrations, and console routing.
- Validates changes against platform gating criteria (`gating:check`, `ai-review:validate`).
- Sets up public routing and assets compilation using Importmap.

### What this repository does not claim yet
- Holding domain logic (all business features must live in separate sub-modules).

## Runtime Surface & Entrypoints

The host application boots the Symfony microservices:
- `public/index.php` - Entry point for HTTP requests.
- `bin/console` - CLI command runner.
- Config files are loaded from `config/`.
- Gating policy and executable rules come from the installed `gating/gate` package; consumer `.gating/` is reserved for generated reports, evidence, cache, checksums, and other artifacts.

## Local Setup

Install dependencies (the App composition host may symlink local infrastructure and product components that it aggregates):
```bash
composer install
npm install
```

Verify gating policies:
```bash
composer run gating:check
composer run ai-review:validate
```

Resolve the Code Memory scope for the host application before graph-backed implementation work:
```bash
php tools/code-memory/scope-resolve.php --cwd=D:\PhpstormProjects\www\App --json
composer run memory:scope:resolve
```

The command is read-only by default. It reports the active project, Composer-linked read projects, edit projects, dependency fingerprint, and the navigation-only global `www` graph. Add `--write-cache` to generate `.codebase-memory/resolved-scope.json`; this cache is derived output, not a manual source-of-truth manifest.

Boot the development server:
```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

`127.0.0.1:8000` is the canonical persistent local Symfony/App host monitored by Console MCP. Port `8080` belongs to the separate Mobiling `mobile-edge` runtime and must not be used as an alternate App host port.

## Production deployment

Deployment is intentionally exposed as one root command while its implementation lives under `tools/`:

```powershell
make deploy
```

Validate configuration and print the planned remote action without changing the server:

```powershell
make deploy-dry-run
```

Required workstation environment variables and server behavior are documented in `tools/deploy/README.md`. SSH is an internal transport detail; no manual SSH session is required.

## Local Composer Symlink Contour

`App` is the canonical root composition host and is exempt from Canon053 sibling-symlink isolation. Its development Composer manifest may therefore link both infrastructure/foundation/helper components and product/capability components that participate in the assembled runtime.

Canon053's closed infrastructure exception list applies to ordinary component repositories, preventing component-to-component capability coupling; it does not constrain the App aggregator.
