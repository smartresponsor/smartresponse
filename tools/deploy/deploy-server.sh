#!/usr/bin/env bash
set -Eeuo pipefail

remote_root="${1:?remote root is required}"
branch="${2:?branch is required}"
expected_commit="${3:?expected commit is required}"
smoke_url="${4:?smoke URL is required}"

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
command -v php >/dev/null
command -v composer >/dev/null
command -v curl >/dev/null
test -d "$remote_root/.git"
cd "$remote_root"

step 'Repository synchronization'
git fetch --prune origin "$branch"
git checkout "$branch"
git reset --hard "origin/$branch"
actual_commit="$(git rev-parse HEAD)"
test "$actual_commit" = "$expected_commit"

step 'Composer production install'
export APP_ENV=prod
export APP_DEBUG=0
composer install \
  --no-dev \
  --no-interaction \
  --no-progress \
  --prefer-dist \
  --optimize-autoloader

step 'Symfony cache'
php bin/console cache:clear --env=prod --no-debug --no-interaction
php bin/console cache:warmup --env=prod --no-debug --no-interaction

step 'Doctrine mapping and schema validation'
php bin/console doctrine:schema:validate --env=prod --no-debug --no-interaction

if php bin/console list --raw --env=prod --no-debug | grep -q '^gating:check '; then
  step 'Application gating'
  php bin/console gating:check --target=. --env=prod --no-debug --no-interaction
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
