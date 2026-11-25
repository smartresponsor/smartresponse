$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo=Join-Path $root 'Vendoring'
$base='local-priority-vendoring-finish'
$release='release/vendoring-local-head-20260904'
Write-Output '=== INSPECTION BRANCH vs RELEASE ==='
& git -C $repo rev-list --left-right --count ($base+'...'+$release)
& git -C $repo cherry $release $base
Write-Output '=== INSPECTION COMMITS ==='
& git -C $repo log --format='%H|%s' ('master..'+$base)
Write-Output '=== INSPECTION CHANGE CATEGORIES ==='
& git -C $repo diff --dirstat=files,0 ('master...'+$base) -- src tests config tools | Select-Object -First 28
Write-Output '=== RELEASE COMMIT DIFF SUMMARY ==='
& git -C $repo diff --shortstat ($base+'...'+$release) -- src tests config tools
