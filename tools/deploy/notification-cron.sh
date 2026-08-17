#!/usr/bin/env bash
set -Eeuo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PHP_BIN="${PHP_BIN:-/opt/alt/php84/usr/bin/php}"
CLAIM_LIMIT="${SMARTRESPONSOR_NOTIFICATION_CLAIM_LIMIT:-100}"
LEASE_SECONDS="${SMARTRESPONSOR_NOTIFICATION_LEASE_SECONDS:-60}"
CONSUME_LIMIT="${SMARTRESPONSOR_DELIVERING_CONSUME_LIMIT:-100}"
CONSUME_SECONDS="${SMARTRESPONSOR_DELIVERING_CONSUME_SECONDS:-50}"
LOCK_DIR="$root/var/lock/notification-cron.lock"

mkdir -p "$(dirname "$LOCK_DIR")"
if ! mkdir "$LOCK_DIR" 2>/dev/null; then
  printf 'notification cron skipped: another invocation is active\n'
  exit 0
fi
trap 'rmdir "$LOCK_DIR" 2>/dev/null || true' EXIT

cd "$root"
export APP_ENV=prod
export APP_DEBUG=0

"$PHP_BIN" bin/console delivering:push:status --env=prod --no-debug --no-interaction

set +e
"$PHP_BIN" bin/console app:notification:dispatch \
  --env=prod \
  --no-debug \
  --limit="$CLAIM_LIMIT" \
  --lease="$LEASE_SECONDS" \
  --no-interaction
dispatch_exit=$?

"$PHP_BIN" bin/console messenger:consume delivering_async \
  --env=prod \
  --no-debug \
  --time-limit="$CONSUME_SECONDS" \
  --memory-limit=256M \
  --limit="$CONSUME_LIMIT" \
  --no-interaction
consume_exit=$?
set -e

if (( dispatch_exit != 0 )); then
  exit "$dispatch_exit"
fi

exit "$consume_exit"
