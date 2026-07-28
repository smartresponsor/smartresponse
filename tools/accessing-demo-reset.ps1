[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$workspace = Split-Path -Parent $PSScriptRoot
Push-Location $workspace
try {
    $env:ACCESSING_ADMIN_PASSWORD = 'AccessingAdmin123!'
    & php bin/console accessing:demo:reset --env=dev --no-interaction --force
    exit $LASTEXITCODE
}
finally {
    Pop-Location
}
