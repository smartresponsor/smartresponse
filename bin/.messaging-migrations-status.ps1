[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
    $env:COMPOSER = 'composer.prod.json'
    & composer update messaging/message smartresponsor/notifying --dry-run --no-install --no-scripts --no-interaction --with-all-dependencies
    exit $LASTEXITCODE
} finally { Pop-Location }