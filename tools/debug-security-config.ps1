[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$workspace = Split-Path -Parent $PSScriptRoot
Push-Location $workspace
try {
    & php bin/console debug:config security --env=dev --no-debug
    exit $LASTEXITCODE
}
finally {
    Pop-Location
}
