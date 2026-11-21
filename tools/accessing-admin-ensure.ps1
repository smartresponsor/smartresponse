[CmdletBinding()]
param(
    [string]$Password = $env:ACCESSING_ADMIN_PASSWORD
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($Password)) {
    throw 'ACCESSING_ADMIN_PASSWORD must be set before running this helper.'
}

$workspace = Split-Path -Parent $PSScriptRoot
Push-Location $workspace
try {
    & php bin/console accessing:admin:ensure --email='admin@smartresponsor.local' --password=$Password --reset-password --env=dev --no-interaction
    exit $LASTEXITCODE
}
finally {
    Pop-Location
}
