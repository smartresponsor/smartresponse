$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo=Join-Path $root 'Vendoring'
$old='local-priority-vendoring-finish';$new='release/vendoring-local-head-20260904'
$oldPaths=@('tests/Unit/Service/VendorCoreServiceTest.php','tests/Unit/Service/VendorCrudServiceTest.php','tests/Unit/Payout/VendorPayoutProviderServiceTest.php')
foreach ($f in $oldPaths) {
Write-Output ('=== '+$f+' ===')
$methods=@(& git -C $repo show ($old+':'+$f) | Select-String 'function test' | ForEach-Object {if ($_.Line -match 'function\s+(test\w+)') {$Matches[1]}})
foreach ($method in $methods) {
$hits=@(& git -C $repo grep -n -F $method $new -- tests 2>$null)
Write-Output ($method+' => '+$(if ($hits) {$hits -join ';'} else {'NOT_FOUND'}))
}
}
Write-Output '=== PAYOUT PROVIDER SOURCE AT RELEASE ==='
& git -C $repo ls-tree -r --name-only $new -- src | Select-String 'PayoutProvider' | Select-Object -First 20
