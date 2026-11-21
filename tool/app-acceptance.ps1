[CmdletBinding()]
param()
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
    $checks = @(
        @{ Name = 'PHPStan max'; Args = @('vendor/bin/phpstan', 'analyse', '--no-progress', '--error-format=table') },
        @{ Name = 'App PHPUnit distribution'; Args = @('vendor/bin/phpunit', '--configuration', 'phpunit.dist.xml', '--colors=never') }
    )
    $failures = 0
    foreach ($check in $checks) {
        Write-Output "BEGIN $($check.Name)"
        & php @($check.Args)
        $code = $LASTEXITCODE
        Write-Output "END $($check.Name) exit=$code"
        if ($code -ne 0) { $failures++ }
    }
    if ($failures -gt 0) { throw "App acceptance failed: $failures check(s)" }
    Write-Output 'PASS App acceptance'
} finally {
    Pop-Location
}
