#!/usr/bin/env bash
set -Eeuo pipefail

remote_root="${1:?remote root is required}"
branch="${2:?branch is required}"
expected_commit="${3:?expected commit is required}"
smoke_url="${4:?smoke URL is required}"

PHP_BIN="${PHP_BIN:-/opt/alt/php84/usr/bin/php}"

step() {
  printf '\n==> %s\n' "$1"
}

fail() {
  printf '\nDEPLOYMENT VERDICT: RED\n' >&2
  exit 1
}
trap fail ERR

step 'Preflight'
command -v git >/dev/null
command -v curl >/dev/null
command -v bash >/dev/null
test -d "$remote_root/.git"
cd "$remote_root"
test -x "$PHP_BIN"
test -f "$remote_root/composer-prod-install.sh"

step 'Repository synchronization'
git fetch --prune origin "$branch"
git checkout "$branch"
git reset --hard "origin/$branch"
actual_commit="$(git rev-parse HEAD)"
test "$actual_commit" = "$expected_commit"

step 'Composer production install'
export APP_ENV=prod
export APP_DEBUG=0
bash "$remote_root/composer-prod-install.sh" "$remote_root"

step 'Doctrine mapping and schema validation'
"$PHP_BIN" bin/console doctrine:schema:validate --env=prod --no-debug --no-interaction

if "$PHP_BIN" bin/console list --raw --env=prod --no-debug | grep -q '^gating:check '; then
  step 'Application gating'
  "$PHP_BIN" bin/console gating:check --target=. --env=prod --no-debug --no-interaction
fi

step 'HTTP smoke'
curl \
  --fail \
  --silent \
  --show-error \
  --location \
  --max-time 30 \
  "$smoke_url" >/dev/null

printf '\nDEPLOYMENT VERDICT: GREEN\n'
printf 'Commit: %s\n' "$actual_commit"
