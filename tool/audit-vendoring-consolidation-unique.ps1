$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$release='326e99cd5feb031be91ed1fc3163766ea071e638'
$other='ea60599dfeb69feae1c8d12322b0a094115afd13'
$base=(& git -C $repo merge-base $release $other).Trim()
Write-Output ('RELEASE='+$release)
Write-Output ('OTHER='+$other)
Write-Output ('MERGE_BASE='+$base)
Write-Output '=== UNIQUE COMMITS (OTHER) ==='
& git -C $repo log --format='%h %s' ($base+'..'+$other)
Write-Output '=== BRANCH DIFF SUMMARY ==='
& git -C $repo diff --shortstat $release $other
Write-Output '=== TOP CHANGED DIRECTORIES ==='
& git -C $repo diff --name-only $release $other | ForEach-Object { ($_ -split '/')[0..([Math]::Min(2,($_ -split '/').Length-1))] -join '/' } | Group-Object | Sort-Object Count -Descending | Select-Object -First 30 | ForEach-Object { '{0} {1}' -f $_.Count,$_.Name }
Write-Output '=== CODE DIFF BETWEEN TIPS: 70-FILE SAMPLE ==='
& git -C $repo diff --name-status $release $other -- src | Select-Object -First 70
Write-Output '=== OTHER UNIQUE CONTENT ADDED VS RELEASE ==='
& git -C $repo diff --numstat $release $other -- src tests | Select-Object -First 50
