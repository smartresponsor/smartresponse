param(
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string] $RootPath
)

$ErrorActionPreference = 'Stop'
$RootPath = (Resolve-Path $RootPath).Path
$PatchRoot = Split-Path -Parent $MyInvocation.MyCommand.Path

$Files = @(
    'composer.prod.json',
    'config\bundles.prod-minimal.php',
    'deploy\prod\install-minimal-prod-composer.ps1',
    'deploy\prod\README-minimal-prod-composer.md'
)

foreach ($File in $Files) {
    $Source = Join-Path $PatchRoot $File
    $Target = Join-Path $RootPath $File
    $TargetDir = Split-Path -Parent $Target
    if (-not (Test-Path $Source)) {
        throw "Missing patch file: $Source"
    }
    if (-not (Test-Path $TargetDir)) {
        New-Item -ItemType Directory -Path $TargetDir -Force | Out-Null
    }
    Copy-Item -Path $Source -Destination $Target -Force
    Write-Host "Applied $File"
}

Write-Host 'W3 prod composer git contour applied.'
