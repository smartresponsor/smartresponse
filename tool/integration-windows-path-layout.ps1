$ErrorActionPreference = 'Stop'
$root = 'D:\PhpstormProjects\www'
$worktree = Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
$layout = Split-Path -Parent $worktree
$manifest = Get-Content (Join-Path $worktree 'composer.json') -Raw | ConvertFrom-Json
$paths = @($manifest.repositories | Where-Object { $_.type -eq 'path' -and $_.url -like '../*' } | ForEach-Object { $_.url.Substring(3) }) | Sort-Object -Unique
$missing = @()
$created = @()
foreach ($relative in $paths) {
    $source = Join-Path $root $relative
    $destination = Join-Path $layout $relative
    if (-not (Test-Path -LiteralPath $source -PathType Container)) { $missing += $relative; continue }
    if (Test-Path -LiteralPath $destination) { continue }
    $parent = Split-Path -Parent $destination
    if (-not (Test-Path -LiteralPath $parent)) { New-Item -ItemType Directory -Path $parent -Force | Out-Null }
    New-Item -ItemType Junction -Path $destination -Target $source -ErrorAction Stop | Out-Null
    $created += $relative
}
Write-Output ('PATH_REPOS=' + $paths.Count)
Write-Output ('JUNCTIONS_CREATED=' + $created.Count)
Write-Output ('MISSING_SOURCES=' + ($missing -join ','))
if ($missing.Count -gt 0) { exit 2 }
