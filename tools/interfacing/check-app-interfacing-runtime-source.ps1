param(
    [string]$AppRoot = (Get-Location).Path,
    [string]$InterfacingRoot = ""
)

$ErrorActionPreference = 'Stop'
$AppRoot = [System.IO.Path]::GetFullPath($AppRoot)
if ([string]::IsNullOrWhiteSpace($InterfacingRoot)) {
    $InterfacingRoot = [System.IO.Path]::GetFullPath((Join-Path $AppRoot '..\Interfacing'))
} else {
    $InterfacingRoot = [System.IO.Path]::GetFullPath($InterfacingRoot)
}

$paths = @(
    @{ Name = 'template/interfacing'; App = Join-Path $AppRoot 'template\interfacing'; Target = Join-Path $InterfacingRoot 'template\interfacing' },
    @{ Name = 'public/interfacing'; App = Join-Path $AppRoot 'public\interfacing'; Target = Join-Path $InterfacingRoot 'public\interfacing' },
    @{ Name = 'assets/interfacing'; App = Join-Path $AppRoot 'assets\interfacing'; Target = Join-Path $InterfacingRoot 'assets\interfacing' }
)

foreach ($p in $paths) {
    Write-Host "--- $($p.Name)"
    if (!(Test-Path $p.App)) {
        Write-Host "App path missing: $($p.App)"
        continue
    }
    $item = Get-Item $p.App -Force
    $isReparse = ($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0
    Write-Host "App path: $($p.App)"
    Write-Host "Target expected: $($p.Target)"
    Write-Host "Is junction/symlink: $isReparse"
}

Write-Host "--- marker scan in App non-vendor files"
Get-ChildItem -Path $AppRoot -Recurse -File |
  Where-Object {
    $_.FullName -notmatch '\\vendor\\' -and
    $_.FullName -notmatch '\\node_modules\\' -and
    $_.FullName -notmatch '\\var\\cache\\' -and
    $_.Extension -in '.twig','.php','.html','.css','.js'
  } |
  Select-String -Pattern 'Smart Responsor','sr-shell-top','provider-baseline-20260527b','interfacing-shell-grid--top','\[data-interfacing-shell-slot="top"\] > div' |
  Select-Object Path, LineNumber, Line
