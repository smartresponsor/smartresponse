[CmdletBinding()]
param()

$runner = Join-Path (Split-Path -Parent $PSScriptRoot) 'tool/domaining-consumers-prepare.ps1'
& $runner
exit $LASTEXITCODE
