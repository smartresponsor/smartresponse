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

Deployment always targets the exact commit currently published at `origin/<branch>`. Local uncommitted changes, the checked-out local branch, and unpublished local commits are reported for visibility but do not block deployment and are never sent to the server. Deployment refuses to run only when a required variable or key is missing, the remote branch cannot be fetched or resolved, SSH verification fails, or a remote deployment stage fails.

## Remote assumptions

The configured remote root must already be a Git checkout with an `origin` remote and production environment configuration outside Git. The SSH user must be allowed to update the checkout and run:

```text
git
php
composer
curl
```

The server resets the checkout to the exact `origin/master` commit and therefore does not preserve uncommitted server-side changes.

## Notification push workers

The Host owns the Notifying → Delivering orchestration boundary. `app:notification:dispatch` claims one bounded Notifying batch, enqueues `DeliveringSendPush` messages, and marks successfully enqueued plans as handed off. Physical APNs/FCM delivery and retries stay on the `delivering_async` Messenger transport.

For a host with a long-running process supervisor, run the Scheduler and Delivering queue together:

```bash
/opt/alt/php84/usr/bin/php bin/console messenger:consume scheduler_default delivering_async --env=prod --no-debug --time-limit=3600 --memory-limit=256M --no-interaction
```

The default Host schedule emits `AppNotificationDispatchTick` every minute. Restart the consumer normally through the process supervisor when its time limit expires.

For cPanel cron, do not start a sub-minute Scheduler worker that exits before the first recurring tick. Instead configure both commands once per minute, using the real production checkout path:

```bash
cd /path/to/App && /opt/alt/php84/usr/bin/php bin/console app:notification:dispatch --env=prod --no-debug --limit=100 --lease=60 --no-interaction
cd /path/to/App && /opt/alt/php84/usr/bin/php bin/console messenger:consume delivering_async --env=prod --no-debug --time-limit=50 --memory-limit=256M --limit=100 --no-interaction
```

The dispatch command is safe to run concurrently because Notifying claims are atomic and lease-bound. Delivering uses the deterministic `notification-dispatch:{planId}` idempotency key, so a reclaimed plan cannot create a second physical delivery record.

### Push provider environment

Provider credentials belong in production environment/secrets, never Git:

```text
DELIVERING_APNS_TEAM_ID
DELIVERING_APNS_KEY_ID
DELIVERING_APNS_PRIVATE_KEY
DELIVERING_APNS_TOPIC_MAP
DELIVERING_APNS_ENVIRONMENT
DELIVERING_FCM_SERVICE_ACCOUNT_JSON
DELIVERING_FCM_PROJECT_MAP
```

`DELIVERING_APNS_TOPIC_MAP` maps application keys to bundle topics, for example `{"one-tasker":"com.smartresponsor.mobile.onetasker"}`. `DELIVERING_FCM_PROJECT_MAP` maps the same application keys to Firebase project IDs. The APNs private key may be stored with literal `\\n` separators; Delivering normalizes them before signing. The FCM service-account value must contain the complete JSON credential object. Missing credentials or missing app mappings fail closed.
