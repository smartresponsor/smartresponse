$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
    $cache = Join-Path $root 'var/cache/diagnostic-doctrine'
    $env:APP_ENV = 'prod'
    $env:APP_DEBUG = '0'
    $env:APP_RUNTIME_OPTIONS = '{"env":"prod"}'
    $env:APP_CACHE_DIR = $cache
    Write-Output "DIAGNOSTIC_CACHE $cache"
    & php bin/console cache:warmup --env=prod --no-debug --no-optional-warmers
    $code = $LASTEXITCODE
    Write-Output "WARMUP_EXIT $code"
    if ($code -ne 0) { exit $code }
} finally {
    Pop-Location
}
