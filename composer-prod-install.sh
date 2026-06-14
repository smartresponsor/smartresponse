#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_DIR="${1:-$(pwd)}"
PHP_BIN="${PHP_BIN:-/opt/alt/php84/usr/bin/php}"
COMPOSER_PHAR="${COMPOSER_PHAR:-/opt/alt/php84/usr/bin/composer.phar}"
COMPOSER_FILE="${COMPOSER_FILE:-composer.prod.json}"

cd "$PROJECT_DIR"

if [[ ! -f "$COMPOSER_FILE" ]]; then
    echo "ERROR: Composer file not found: $PROJECT_DIR/$COMPOSER_FILE" >&2
    exit 1
fi

if [[ ! -x "$PHP_BIN" ]]; then
    echo "ERROR: PHP binary is not executable: $PHP_BIN" >&2
    exit 1
fi

if [[ ! -f "$COMPOSER_PHAR" ]]; then
    echo "ERROR: Composer PHAR not found: $COMPOSER_PHAR" >&2
    exit 1
fi

COMPOSER="$PROJECT_DIR/$COMPOSER_FILE" \
"$PHP_BIN" "$COMPOSER_PHAR" install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-interaction
