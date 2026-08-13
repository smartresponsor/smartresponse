[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $EntityPrefix,
    [string] $OutputPath = '',
    [switch] $SummaryOnly
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
    $env:APP_ENV = 'prod'
    $env:APP_DEBUG = '0'
    $env:APP_BASE_URI = 'https://smartresponsor.com'
    $cacheKey = ($EntityPrefix -replace '[^A-Za-z0-9_.-]', '_')
    $env:APP_CACHE_DIR = Join-Path $root ("var/cache/database-component-schema/$cacheKey")

    $php = 'C:\PHP\php-8.4.13-nts-Win32-vs17-x64\php.exe'
    if (-not (Test-Path $php)) { $php = 'php' }

    if ('' -eq $OutputPath) {
        if ($SummaryOnly) {
            & $php tool/database-component-schema.php $EntityPrefix --summary-only
        } else {
            & $php tool/database-component-schema.php $EntityPrefix
        }
    } else {
        if ($SummaryOnly) {
            & $php tool/database-component-schema.php $EntityPrefix $OutputPath --summary-only
        } else {
            & $php tool/database-component-schema.php $EntityPrefix $OutputPath
        }
    }
    if ($LASTEXITCODE -ne 0) {
        throw "Component schema projection failed ($LASTEXITCODE) for $EntityPrefix"
    }
}
finally {
    Pop-Location
}
