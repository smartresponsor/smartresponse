$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo=Join-Path $root 'Vendoring'
$old='local-priority-vendoring-finish';$new='release/vendoring-local-head-20260904'
$pairs=@(@('tests/Unit/Interfacing/VendorInterfacingTemplateCandidateProviderServiceTest.php','tests/Unit/Interfacing/VendorInterfacingTemplateCandidateProviderTest.php'),@('tests/Unit/Observability/VendorMonitoringSnapshotBuilderServiceTest.php','tests/Unit/Observability/VendorMonitoringSnapshotBuilderTest.php'),@('tests/Unit/Payout/VendorPayoutProviderServiceTest.php','tests/Unit/Payout/VendorPayoutProviderTest.php'),@('tests/Unit/Profile/VendorAttachingProfileAttachmentResolverServiceTest.php','tests/Unit/Profile/VendorAttachingProfileAttachmentResolverTest.php'),@('tests/Unit/Service/VendorTransactionInputResolverServiceTest.php','tests/Unit/Service/VendorTransactionInputResolverTest.php'))
foreach ($pair in $pairs) {
$a=@(& git -C $repo show ($old+':'+$pair[0]) | Select-String 'function test' | ForEach-Object { if ($_.Line -match 'function\s+(test\w+)') {$Matches[1]} })
$b=@(& git -C $repo show ($new+':'+$pair[1]) | Select-String 'function test' | ForEach-Object { if ($_.Line -match 'function\s+(test\w+)') {$Matches[1]} })
Write-Output ('=== '+[System.IO.Path]::GetFileName($pair[0])+' => '+[System.IO.Path]::GetFileName($pair[1])+' ===')
Write-Output ('OLD='+($a -join ','))
Write-Output ('NEW='+($b -join ','))
Write-Output ('MISSING_METHODS='+(@($a | Where-Object {$_ -notin $b}) -join ','))
}
Write-Output '=== CORE SERVICE NAME SEARCH ==='
& git -C $repo ls-tree -r --name-only $new -- tests | Select-String 'Core|Crud|Brand' | Select-Object -First 25
