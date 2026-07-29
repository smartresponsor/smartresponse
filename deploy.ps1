[CmdletBinding()]
param(
    [switch] $Force,
    [switch] $DryRun
)

$script = Join-Path $PSScriptRoot 'tools/deploy/deploy.ps1'
& $script @PSBoundParameters
exit $LASTEXITCODE
