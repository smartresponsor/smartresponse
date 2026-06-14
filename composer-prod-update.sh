#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_DIR="${1:-$(pwd)}"

PHP_BIN="${PHP_BIN:-/opt/alt/php84/usr/bin/php}"
COMPOSER_PHAR="${COMPOSER_PHAR:-/opt/alt/php84/usr/bin/composer.phar}"
COMPOSER_FILE="${COMPOSER_FILE:-composer.prod.json}"

MODE="${MODE:-update}"
PREFER="${PREFER:-dist}"

APP_ARCHIVE_URL="${APP_ARCHIVE_URL:-https://codeload.github.com/smartresponsor/smartresponse/tar.gz/refs/heads/master}"
APP_SYNC="${APP_SYNC:-1}"

NPM_SYNC="${NPM_SYNC:-1}"
NPM_MODE="${NPM_MODE:-auto}"
NPM_BUILD="${NPM_BUILD:-1}"
NPM_BUILD_SCRIPT="${NPM_BUILD_SCRIPT:-build}"

cd "$PROJECT_DIR"

fail() {
    echo "ERROR: $*" >&2
    exit 1
}

cleanup() {
    if [[ -n "${TMP_DIR:-}" && -d "${TMP_DIR:-}" ]]; then
        rm -rf "$TMP_DIR"
    fi
}
trap cleanup EXIT

[[ -f "$COMPOSER_FILE" ]] || fail "Composer file not found: $PROJECT_DIR/$COMPOSER_FILE"
[[ -x "$PHP_BIN" ]] || fail "PHP binary is not executable: $PHP_BIN"
[[ -f "$COMPOSER_PHAR" ]] || fail "Composer PHAR not found: $COMPOSER_PHAR"

case "$MODE" in
    update|install) ;;
    *) fail "MODE must be 'update' or 'install', got: $MODE" ;;
esac

case "$PREFER" in
    dist|source) ;;
    *) fail "PREFER must be 'dist' or 'source', got: $PREFER" ;;
esac

case "$NPM_MODE" in
    auto|ci|install|update) ;;
    *) fail "NPM_MODE must be 'auto', 'ci', 'install', or 'update', got: $NPM_MODE" ;;
esac

COMPOSER_LOCK="${COMPOSER_FILE%.json}.lock"

echo "Project:       $PROJECT_DIR"
echo "Application:   $APP_ARCHIVE_URL"
echo "Composer:      $COMPOSER_FILE"
echo "Lock:          $COMPOSER_LOCK"
echo "Mode:          $MODE"
echo "Preference:    $PREFER"
echo "App sync:      $APP_SYNC"
echo "NPM sync:      $NPM_SYNC"
echo "NPM mode:      $NPM_MODE"
echo "NPM build:     $NPM_BUILD"
echo

if [[ "$APP_SYNC" == "1" ]]; then
    command -v curl >/dev/null 2>&1 || fail "curl is not available"
    command -v tar >/dev/null 2>&1 || fail "tar is not available"
    command -v rsync >/dev/null 2>&1 || fail "rsync is not available"

    TMP_DIR="$(mktemp -d)"
    ARCHIVE_FILE="$TMP_DIR/app.tar.gz"
    EXTRACT_DIR="$TMP_DIR/extracted"

    mkdir -p "$EXTRACT_DIR"

    echo "Downloading current application archive..."
    curl \
        --fail \
        --location \
        --silent \
        --show-error \
        --retry 3 \
        --retry-delay 2 \
        --output "$ARCHIVE_FILE" \
        "$APP_ARCHIVE_URL"

    echo "Extracting application archive..."
    tar -xzf "$ARCHIVE_FILE" -C "$EXTRACT_DIR"

    SOURCE_DIR="$(find "$EXTRACT_DIR" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
    [[ -n "$SOURCE_DIR" && -d "$SOURCE_DIR" ]] \
        || fail "Could not locate extracted application directory"

    echo "Synchronizing application files without Git metadata..."

    rsync -a --delete \
        --exclude='.git/' \
        --exclude='.github/' \
        --exclude='.env' \
        --exclude='.env.*' \
        --exclude='vendor/' \
        --exclude='var/' \
        --exclude='public/uploads/' \
        --exclude='public/upload/' \
        --exclude='public/media/' \
        --exclude='public/storage/' \
        --exclude='public/build/' \
        --exclude='composer-prod-update.sh' \
        --exclude='composer-prod-install.sh' \
        --exclude='error_log' \
        "$SOURCE_DIR/" "$PROJECT_DIR/"

    if [[ -d "$PROJECT_DIR/.git" ]]; then
        echo "Removing obsolete Git metadata from production..."
        rm -rf "$PROJECT_DIR/.git"
    fi

    echo "Application files synchronized."
    echo
fi

echo "Validating Composer configuration..."

if [[ "$MODE" == "update" ]]; then
    COMPOSER="$PROJECT_DIR/$COMPOSER_FILE" \
    "$PHP_BIN" "$COMPOSER_PHAR" validate --no-check-publish || {
        echo
        echo "Composer validation reported lock-file drift."
        echo "Continuing because MODE=update will regenerate $COMPOSER_LOCK."
    }
else
    COMPOSER="$PROJECT_DIR/$COMPOSER_FILE" \
    "$PHP_BIN" "$COMPOSER_PHAR" validate --no-check-publish
fi

echo

COMMON_ARGS=(
    --no-dev
    "--prefer-$PREFER"
    --optimize-autoloader
    --classmap-authoritative
    --no-interaction
)

if [[ "$MODE" == "update" ]]; then
    echo "Resolving and pulling all dependencies allowed by $COMPOSER_FILE..."

    COMPOSER="$PROJECT_DIR/$COMPOSER_FILE" \
    "$PHP_BIN" "$COMPOSER_PHAR" update "${COMMON_ARGS[@]}"
else
    echo "Installing dependencies exactly from $COMPOSER_LOCK..."

    [[ -f "$COMPOSER_LOCK" ]] \
        || fail "Lock file not found: $PROJECT_DIR/$COMPOSER_LOCK"

    COMPOSER="$PROJECT_DIR/$COMPOSER_FILE" \
    "$PHP_BIN" "$COMPOSER_PHAR" install "${COMMON_ARGS[@]}"
fi

if [[ "$NPM_SYNC" == "1" && -f "$PROJECT_DIR/package.json" ]]; then
    command -v npm >/dev/null 2>&1 || fail "package.json exists, but npm is not available"

    EFFECTIVE_NPM_MODE="$NPM_MODE"

    if [[ "$EFFECTIVE_NPM_MODE" == "auto" ]]; then
        if [[ -f "$PROJECT_DIR/package-lock.json" ]]; then
            EFFECTIVE_NPM_MODE="ci"
        else
            EFFECTIVE_NPM_MODE="install"
        fi
    fi

    echo
    echo "Installing frontend dependencies with npm $EFFECTIVE_NPM_MODE..."

    case "$EFFECTIVE_NPM_MODE" in
        ci)
            [[ -f "$PROJECT_DIR/package-lock.json" ]] \
                || fail "NPM_MODE=ci requires package-lock.json"
            npm ci --omit=dev
            ;;
        install)
            npm install --omit=dev
            ;;
        update)
            npm update --omit=dev
            ;;
    esac

    if [[ "$NPM_BUILD" == "1" ]]; then
        if npm run | grep -Eq "^[[:space:]]+$NPM_BUILD_SCRIPT([[:space:]]|$)"; then
            echo "Building frontend assets with npm run $NPM_BUILD_SCRIPT..."
            npm run "$NPM_BUILD_SCRIPT"
        else
            echo "No npm script named '$NPM_BUILD_SCRIPT'; skipping frontend build."
        fi
    fi
elif [[ "$NPM_SYNC" == "1" ]]; then
    echo
    echo "No package.json found; skipping npm."
fi

echo
echo "Application and production dependencies are ready."
