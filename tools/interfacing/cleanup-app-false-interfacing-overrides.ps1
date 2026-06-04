param(
    [string]$AppRoot = (Get-Location).Path,
    [string]$InterfacingRoot = ""
)

$ErrorActionPreference = 'Stop'

function Resolve-FullPath([string]$PathValue) {
    return [System.IO.Path]::GetFullPath($PathValue)
}

$AppRoot = Resolve-FullPath $AppRoot
if ([string]::IsNullOrWhiteSpace($InterfacingRoot)) {
    $InterfacingRoot = Resolve-FullPath (Join-Path $AppRoot '..\Interfacing')
} else {
    $InterfacingRoot = Resolve-FullPath $InterfacingRoot
}

if (!(Test-Path $AppRoot -PathType Container)) {
    throw "App root does not exist: $AppRoot"
}
if (!(Test-Path $InterfacingRoot -PathType Container)) {
    throw "Interfacing root does not exist: $InterfacingRoot"
}

$requiredTargets = @(
    (Join-Path $InterfacingRoot 'templates\interfacing'),
    (Join-Path $InterfacingRoot 'public\interfacing')
)
foreach ($target in $requiredTargets) {
    if (!(Test-Path $target -PathType Container)) {
        throw "Required Interfacing target is missing: $target"
    }
}

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupRoot = Join-Path $AppRoot "var\backups\interfacing-false-overrides-$stamp"
New-Item -ItemType Directory -Force -Path $backupRoot | Out-Null

$links = @(
    @{ App = 'templates\interfacing'; Target = 'templates\interfacing'; Required = $true },
    @{ App = 'public\interfacing';   Target = 'public\interfacing';   Required = $true },
    @{ App = 'assets\interfacing';   Target = 'assets\interfacing';   Required = $false }
)

foreach ($link in $links) {
    $appPath = Join-Path $AppRoot $link.App
    $targetPath = Join-Path $InterfacingRoot $link.Target

    if (!(Test-Path $targetPath -PathType Container)) {
        if ($link.Required) {
            throw "Required Interfacing target is missing: $targetPath"
        }
        Write-Host "[skip] Optional Interfacing target not found: $targetPath"
        continue
    }

    $parent = Split-Path $appPath -Parent
    New-Item -ItemType Directory -Force -Path $parent | Out-Null

    if (Test-Path $appPath) {
        $item = Get-Item $appPath -Force
        $isReparse = ($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0
        if ($isReparse) {
            Remove-Item $appPath -Force
            Write-Host "[remove-link] $appPath"
        } else {
            $backupPath = Join-Path $backupRoot $link.App
            New-Item -ItemType Directory -Force -Path (Split-Path $backupPath -Parent) | Out-Null
            Move-Item -Path $appPath -Destination $backupPath -Force
            Write-Host "[backup] $appPath -> $backupPath"
        }
    }

    New-Item -ItemType Junction -Path $appPath -Target $targetPath | Out-Null
    Write-Host "[junction] $appPath -> $targetPath"
}

$marker = Join-Path $backupRoot 'README.txt'
@"
This backup was created by cleanup-app-false-interfacing-overrides.ps1.

Purpose:
- Host App must not own duplicate Interfacing templates/assets.
- App templates/public/assets Interfacing paths are now directory junctions to the sibling Interfacing repository.
- Interfacing repository is the source of truth.

AppRoot: $AppRoot
InterfacingRoot: $InterfacingRoot
Created: $stamp

Rollback manually if needed:
1. Remove the junction path in App.
2. Move the corresponding backup folder back to its original location.
"@ | Set-Content -Path $marker -Encoding UTF8

Write-Host "[done] False Interfacing overrides cleaned from App. Backup: $backupRoot"


