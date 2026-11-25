$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo=Join-Path $root 'Vendoring'
$base='local-priority-vendoring-finish';$release='release/vendoring-local-head-20260904'
$names=@('tests/Unit/Infrastructure/CanonicalNoPlaceholderRepositoryContractTest.php','tests/Unit/Infrastructure/CanonicalNoStubRepositoryContractTest.php','tests/Unit/Infrastructure/VendorFixtureStackContractTest.php','tests/Unit/Service/VendorTransactionLifecycleServiceTest.php','tests/Unit/Service/VendorProfileProjectionBuilderTest.php')
foreach ($f in $names) {
Write-Output ('=== '+$f+' ===')
$a=(& git -C $repo show ($base+':'+$f) | Select-String -Pattern 'function test|#\[Test\]|assert[A-Z]|expectException' | ForEach-Object {$_.Line.Trim()})
$b=(& git -C $repo show ($release+':'+$f) | Select-String -Pattern 'function test|#\[Test\]|assert[A-Z]|expectException' | ForEach-Object {$_.Line.Trim()})
Write-Output ('INSPECTION_ASSERT_LINES='+@($a).Count+' RELEASE_ASSERT_LINES='+@($b).Count)
$removed=@($a | Where-Object { $_ -notin $b });$added=@($b | Where-Object { $_ -notin $a })
Write-Output ('REMOVED_OR_CHANGED='+$removed.Count+' ADDED_OR_CHANGED='+$added.Count)
$removed | Select-Object -First 7 | ForEach-Object {Write-Output ('OLD: '+$_)}
$added | Select-Object -First 7 | ForEach-Object {Write-Output ('NEW: '+$_)}
}
