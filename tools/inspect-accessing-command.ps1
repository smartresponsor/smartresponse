[CmdletBinding()]
param()

$ErrorActionPreference = 'Continue'
$workspace = Split-Path -Parent $PSScriptRoot
Push-Location $workspace
Write-Host 'CLASS_EXISTS:'
php tools/inspect-accessing-command.php
Write-Host 'SERVICE:'
php bin/console debug:container "App\Accessing\Command\AccessDemoResetCommand" --show-private --env=dev --no-debug
Write-Host 'COMMAND_TAGS:'
php bin/console debug:container --tag=console.command --env=dev --no-debug | Select-String 'AccessDemoReset|accessing:demo'
Write-Host 'COMMAND_LIST:'
php bin/console list accessing --env=dev --no-debug
Pop-Location
