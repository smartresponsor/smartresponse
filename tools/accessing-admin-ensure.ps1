[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$workspace = Split-Path -Parent $PSScriptRoot
Push-Location $workspace
try {
    & php bin/console accessing:admin:ensure --email='admin@smartresponsor.local' --password='AccessingAdmin123!' --reset-password --env=dev --no-interaction
    exit $LASTEXITCODE
}
finally {
    Pop-Location
}
