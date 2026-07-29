# App

App is the core host Symfony application for the Smart Responsor platform. It coordinates and integrates all individual domain bundles (`Carting`, `Billing`, `Exchanging`, `Domaining`, etc.) which are registered as symlinked composer path repositories.

This project is the main runtime execution boundary and deployment target.

## Current Posture

### What the component already does
- Orchestrates domain components into a unified execution kernel.
- Links local modules as PHP dependencies via path symlinks.
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
- Customized host gates and validations are configured under `.gating/`.

## Local Setup

Install dependencies (automatically symlinks path repositories):
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
php -S 127.0.0.1:8000 -t public
```

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

## Symlinked Modules List

The application links to:
- `Gating` (`../Gating`)
- `Carting` (`../Carting`)
- `Searching` (`../Searching`)
- `Domaining` (`../Domaining`)
- `Billing` (`../Billing`)
- `Analysing` (`../Analysing`)
- `Exchanging` (`../Exchanging`)
- `Localizing` (`../Localizing`)
- `Managing` (`../Managing`)
- `Observabiliting` (`../Observabiliting`)
- `Projecting` (`../Projecting`)
- `Viewing` (`../Viewing`)
- and others (see `composer.json` for details).
