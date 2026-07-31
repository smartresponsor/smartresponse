[CmdletBinding()]
param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [int]$Timeout = 15
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$previousAppDebug = $env:APP_DEBUG
$previousXdebugMode = $env:XDEBUG_MODE
$env:APP_DEBUG = '0'
$env:XDEBUG_MODE = 'off'
Push-Location $root
try {
    php bin/console app:url-audit:inventory --output=var/url-audit/latest-inventory.json
    if ($LASTEXITCODE -ne 0) {
        throw "Inventory failed with exit code $LASTEXITCODE."
    }

    php bin/console app:url-audit:run --base-url=$BaseUrl --timeout=$Timeout
    if ($LASTEXITCODE -ne 0) {
        throw "URL audit failed with exit code $LASTEXITCODE."
    }

    Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
        Sort-Object LastWriteTimeUtc -Descending |
        Select-Object -First 1 -ExpandProperty FullName
}
finally {
    Pop-Location
    $env:APP_DEBUG = $previousAppDebug
    $env:XDEBUG_MODE = $previousXdebugMode
}
