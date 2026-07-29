# Quiet SSH deployment

The operator command is:

```powershell
make deploy
```

SSH is only the internal transport used by `tools/deploy/deploy.ps1`. No manual server login is required.

## Workstation environment

Define these values in the Windows user environment, never in repository files:

```powershell
[Environment]::SetEnvironmentVariable('SMARTRESPONSOR_SSH_HOST', 'host.example.com', 'User')
[Environment]::SetEnvironmentVariable('SMARTRESPONSOR_SSH_USER', 'deploy', 'User')
[Environment]::SetEnvironmentVariable('SMARTRESPONSOR_SSH_PORT', '22', 'User')
[Environment]::SetEnvironmentVariable('SMARTRESPONSOR_SSH_KEY', '%USERPROFILE%\.ssh\id_ed25519', 'User')
[Environment]::SetEnvironmentVariable('SMARTRESPONSOR_REMOTE_ROOT', '/var/www/smartresponsor/App', 'User')
[Environment]::SetEnvironmentVariable('SMARTRESPONSOR_SMOKE_URL', 'https://smartresponsor.com/health', 'User')
[Environment]::SetEnvironmentVariable('SMARTRESPONSOR_DEPLOY_BRANCH', 'master', 'User')
```

Open a new PowerShell session after setting user-level variables.

## Commands

Validate Git state, key presence, host-key trust and SSH connectivity without changing the server:

```powershell
make deploy-dry-run
```

Deploy the exact commit already pushed to `origin/master`:

```powershell
make deploy
```

Deployment refuses to run when a required variable or key is missing, the local tree is dirty, the wrong branch is checked out, local `HEAD` differs from `origin/<branch>`, SSH verification fails, or a remote deployment stage fails.

## Remote assumptions

The configured remote root must already be a Git checkout with an `origin` remote and production environment configuration outside Git. The SSH user must be allowed to update the checkout and run:

```text
git
php
composer
curl
```

The server resets the checkout to the exact `origin/master` commit and therefore does not preserve uncommitted server-side changes.
