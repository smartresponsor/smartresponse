$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$cases=@(@('Cruding','checkpoint/cruding-old-local-master-20260929'),@('Cruding','checkpoint/cruding-20260929-openapi-capability'),@('Cruding','cruding-rc-bulk-mutation-v2'),@('Vendoring','release/vendoring-local-head-20260904'),@('Vendoring','local-priority-vendoring-finish'))
foreach ($c in $cases) {
$repo=Join-Path $root $c[0]; $ref=$c[1]
Write-Output ('=== '+$c[0]+' '+$ref+' ===')
& git -C $repo diff --shortstat ('master...'+$ref) -- src config tests
& git -C $repo diff --name-status ('master...'+$ref) -- src config tests | Select-Object -First 24
Write-Output '--- UNIQUE COMMITS ---'
& git -C $repo log --format='%h|%s' ('master..'+$ref) -n 12
}
