[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$workspace = Split-Path -Parent $PSScriptRoot
Push-Location $workspace
try {
    $env:APP_ENV = 'prod'
    $env:APP_DEBUG = '0'
    $env:APP_BASE_URI = 'https://smartresponsor.com'
    $env:APP_CACHE_DIR = Join-Path $workspace 'var/cache/domaining-consumer-prod'

    $php = 'C:\PHP\php-8.4.13-nts-Win32-vs17-x64\php.exe'
    if (-not (Test-Path $php)) {
        $php = 'php'
    }

    function Run-Console([string[]] $Arguments) {
        & $php bin/console @Arguments
        if ($LASTEXITCODE -ne 0) {
            throw "Symfony console command failed ($LASTEXITCODE): $($Arguments -join ' ')"
        }
    }

    $previousErrorActionPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $migrationLines = & $php bin/console doctrine:migrations:list --no-interaction 2>&1
    $migrationExitCode = $LASTEXITCODE
    $ErrorActionPreference = $previousErrorActionPreference
    if ($migrationExitCode -ne 0) {
        throw 'Unable to inspect Doctrine migration status.'
    }
    $migrationLine = $migrationLines | Where-Object { $_ -match 'Version20260812214000' } | Select-Object -First 1
    if ($null -eq $migrationLine) {
        throw 'Domaining migration Version20260812214000 is not registered in the Host.'
    }
    if ([string] $migrationLine -match 'not migrated') {
        Run-Console @(
            'doctrine:migrations:execute',
            'App\Domaining\Migrations\Version20260812214000',
            '--up',
            '--no-interaction'
        )
    }

    Run-Console @(
        'domaining:consumer:ensure',
        '--domain=smartresponsor.com',
        '--application=smartresponsor',
        '--brand=smartresponsor',
        '--environment=prod',
        '--declaration-only',
        '--no-interaction'
    )

    Run-Console @(
        'domaining:consumer:ensure',
        '--domain=1tasker.com',
        '--application=1tasker',
        '--brand=1tasker',
        '--environment=prod',
        '--owner=1tasker',
        '--surface=application',
        '--surface-key=1tasker',
        '--no-interaction'
    )

    Run-Console @('doctrine:schema:validate', '--em=postgres', '--skip-sync', '--no-interaction')
    Run-Console @('domaining:release:gate', '--no-interaction')
}
finally {
    Pop-Location
}
