[CmdletBinding()]
param()

$ErrorActionPreference = 'Continue'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
$oldEnv = $env:APP_ENV
$oldDebug = $env:APP_DEBUG
try {
    $env:APP_ENV = 'prod'
    $env:APP_DEBUG = '0'
    & php bin/console app:url-audit:inventory --output=var/url-audit/latest-inventory.json
    exit $LASTEXITCODE
}
finally {
    $env:APP_ENV = $oldEnv
    $env:APP_DEBUG = $oldDebug
    Pop-Location
}
