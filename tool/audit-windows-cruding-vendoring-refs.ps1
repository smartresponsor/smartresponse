$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
foreach ($name in @('Cruding','Vendoring')) {
$repo=Join-Path $root $name
Write-Output ('=== ' + $name + ' ===')
& git -C $repo branch -a --format='%(refname:short)|%(objectname:short)|%(committerdate:short)' | Select-Object -Last 55
Write-Output '--- ABSTRACT CRUD PATHS ACROSS REFS ---'
$refs=@(& git -C $repo for-each-ref --format='%(refname)' refs/heads refs/remotes/origin)
foreach ($ref in $refs) {
$hits=@(& git -C $repo ls-tree -r --name-only $ref -- src | Select-String 'AbstractCrudService.php|AbstractVendorCrudRouteService.php')
if ($hits.Count -gt 0) { Write-Output ($ref + ' => ' + ($hits -join ',')) }
}
}
