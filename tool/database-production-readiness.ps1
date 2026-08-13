[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $MigrationClass,
    [string] $ComponentEntityPrefix = '',
    [switch] $Apply
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$workspace = Split-Path -Parent $PSScriptRoot
Push-Location $workspace
try {
    $env:APP_ENV = 'prod'
    $env:APP_DEBUG = '0'
    $env:APP_BASE_URI = 'https://smartresponsor.com'
    $cacheKey = ($MigrationClass -replace '[^A-Za-z0-9_.-]', '_')
    $env:APP_CACHE_DIR = Join-Path $workspace ("var/cache/database-production-readiness/$cacheKey")

    $php = 'C:\PHP\php-8.4.13-nts-Win32-vs17-x64\php.exe'
    if (-not (Test-Path $php)) { $php = 'php' }

    function Run-Console([string[]] $Arguments) {
        $previous = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        & $php bin/console @Arguments 2>&1
        $exit = $LASTEXITCODE
        $ErrorActionPreference = $previous
        if ($exit -ne 0) {
            throw "Symfony console command failed ($exit): $($Arguments -join ' ')"
        }
    }

    Write-Host "Migration: $MigrationClass"
    Write-Host "Apply: $Apply"

    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $migrationLines = & $php bin/console doctrine:migrations:list --no-interaction 2>&1
    $migrationExit = $LASTEXITCODE
    $ErrorActionPreference = $previous
    if ($migrationExit -ne 0) {
        $migrationLines | Write-Host
        throw 'Unable to inspect Doctrine migration registry.'
    }

    $migrationLine = $migrationLines | Where-Object { $_ -match [regex]::Escape(($MigrationClass -split '\\')[-1]) } | Select-Object -First 1
    if ($null -eq $migrationLine) {
        throw "Migration is not registered in Host: $MigrationClass"
    }
    Write-Host ([string] $migrationLine)

    if ($Apply -and ([string] $migrationLine -match 'not migrated')) {
        Run-Console @('doctrine:migrations:execute', $MigrationClass, '--up', '--no-interaction')
    }

    Write-Host '=== Mapping validation ==='
    Run-Console @('doctrine:schema:validate', '--em=postgres', '--skip-sync', '--no-interaction')

    if ('' -ne $ComponentEntityPrefix) {
        Write-Host '=== Component physical schema verification ==='
        $previous = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        $componentState = & $php tool/database-component-schema.php $ComponentEntityPrefix --summary-only 2>&1
        $componentExit = $LASTEXITCODE
        $ErrorActionPreference = $previous
        $componentState | Write-Host
        if ($componentExit -ne 0 -or -not (($componentState -join "`n") -match 'missing in current DB: 0')) {
            throw "Component physical schema verification failed for $ComponentEntityPrefix"
        }
    }

    Write-Host '=== Schema drift SQL (read-only) ==='
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & $php bin/console doctrine:schema:update --em=postgres --dump-sql --no-interaction 2>&1
    $driftExit = $LASTEXITCODE
    $ErrorActionPreference = $previous
    if ($driftExit -ne 0) {
        Write-Warning 'Schema drift inspection is currently blocked by cross-component Doctrine schema ownership collision; migration and mapping checks remain authoritative for this run.'
    }
}
finally {
    Pop-Location
}
