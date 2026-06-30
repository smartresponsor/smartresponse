# App Vaulting Integration

App declares runtime secret requirements by reference only.

## Declaration files

```text
config/secret/secret.required.json
config/secret/secret.map.example.json
```

These files do not contain resolved values.

## Vaulting owner

Vaulting owns value resolution and child-process runtime delivery.

App owns only the list of required runtime references.

## Runtime boundary

App reads environment variables through Symfony configuration. The environment variables are injected by Vaulting at process start.

```text
Vaulting secret-run.ps1
  -> temporary child-process environment variables
  -> Symfony App runtime
```

## Current required references

```text
APP_SECRET
PLATFORM_DATA_DATABASE
OBJECT_REVIEW_DATABASE
PLATFORM_SYSTEM_DATABASE
GOOGLE_API_KEY
GOOGLE_CLIENT_SECRET
GOOGLE_AUTH_CONFIG
LOCK_DSN
MAILER_DSN
APP_BACK_URI_TOKEN
```

## Non-secret runtime configuration

`GOOGLE_CLIENT_ID` is intentionally not declared in the first Vaulting pass because it is not a secret value. It may remain regular configuration unless deployment policy requires all runtime configuration to flow through Vaulting.

## Local smoke shape

```powershell
D:\PhpstormProjects\www\Vaulting\tool\windows\secret-run.ps1 `
  -AwsProfile codex-dev `
  -MapPath D:\PhpstormProjects\www\App\config\secret\secret.map.example.json `
  -ExpectedEnvironment dev `
  -Run { php bin/console about }
```
