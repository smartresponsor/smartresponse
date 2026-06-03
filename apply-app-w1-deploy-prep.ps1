param(
    [Parameter(Mandatory=$true)]
    [string]$RootPath
)

$ErrorActionPreference = 'Stop'
$patchRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$root = (Resolve-Path $RootPath).Path

$deleteList = Get-Content (Join-Path $patchRoot 'PATCH_DELETE_FILES.txt') | Where-Object { $_ -and -not $_.StartsWith('#') }
foreach ($relative in $deleteList) {
    $target = Join-Path $root $relative
    if (Test-Path $target -PathType Leaf) {
        Remove-Item $target -Force
        Write-Host "Deleted exact legacy file: $relative"
    }
}

$fileList = Get-Content (Join-Path $patchRoot 'PATCH_TOUCHED_FILES.txt') | Where-Object { $_ -and -not $_.StartsWith('#') }
foreach ($relative in $fileList) {
    $source = Join-Path $patchRoot $relative
    $target = Join-Path $root $relative
    $targetDir = Split-Path -Parent $target
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir | Out-Null
    }
    Copy-Item $source $target -Force
    Write-Host "Applied touched file: $relative"
}

Write-Host 'Done. Only explicit touched files were copied and only explicit legacy files were deleted.'
