$ErrorActionPreference='Stop'
$repo='D:\PhpstormProjects\www\.worktrees\host-app-master-reconcile-20261121'
$base='2405d8336377438a853532b94ce7b87c21eb6741'
$release='8c28e3f47cb5949d01821d5e63babd21ff4ea9d5'
Write-Output ('MERGE_BASE='+(& git -C $repo merge-base $base $release))
Write-Output ('BASE_ONLY='+(& git -C $repo rev-list --count ($release+'..'+$base)))
Write-Output ('RELEASE_ONLY='+(& git -C $repo rev-list --count ($base+'..'+$release)))
Write-Output 'DIFF_NAMES:'
& git -C $repo diff --name-status $base $release
Write-Output 'MERGE_TREE:'
& git -C $repo merge-tree $base $release | Select-Object -First 110
