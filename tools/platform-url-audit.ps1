[CmdletBinding()]
param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [int]$Timeout = 15,
    [int]$Samples = 1,
    [int]$SlowMs = 250
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$previousAppEnv = $env:APP_ENV
$previousAppDebug = $env:APP_DEBUG
$previousXdebugMode = $env:XDEBUG_MODE
$env:APP_ENV = 'prod'
$env:APP_DEBUG = '0'
$env:XDEBUG_MODE = 'off'
Push-Location $root
$inventoryErrorFile = Join-Path $root 'var/url-audit/latest-inventory.stderr.log'
$auditErrorFile = Join-Path $root 'var/url-audit/latest-run.stderr.log'
try {
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $inventoryErrorFile) | Out-Null
    $ErrorActionPreference = 'Continue'
    php bin/console app:url-audit:inventory --output=var/url-audit/latest-inventory.json 2> $inventoryErrorFile
    $inventoryExitCode = $LASTEXITCODE
    $ErrorActionPreference = 'Stop'
    if ($inventoryExitCode -ne 0) {
        Get-Content -Path $inventoryErrorFile -ErrorAction SilentlyContinue | Write-Error
        throw "Inventory failed with exit code $inventoryExitCode."
    }

    $ErrorActionPreference = 'Continue'
    php bin/console app:url-audit:run --base-url=$BaseUrl --timeout=$Timeout --samples=$Samples --slow-ms=$SlowMs 2> $auditErrorFile
    $auditExitCode = $LASTEXITCODE
    $ErrorActionPreference = 'Stop'
    if ($auditExitCode -ne 0) {
        Get-Content -Path $auditErrorFile -ErrorAction SilentlyContinue | Write-Error
        throw "URL audit failed with exit code $auditExitCode."
    }

    Remove-Item -Force $inventoryErrorFile, $auditErrorFile -ErrorAction SilentlyContinue

    Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
        Sort-Object LastWriteTimeUtc -Descending |
        Select-Object -First 1 -ExpandProperty FullName
}
finally {
    Remove-Item -Force $inventoryErrorFile, $auditErrorFile -ErrorAction SilentlyContinue
    Pop-Location
    $env:APP_ENV = $previousAppEnv
    $env:APP_DEBUG = $previousAppDebug
    $env:XDEBUG_MODE = $previousXdebugMode
}
