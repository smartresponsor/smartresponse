$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo=Join-Path $root 'Vendoring'
$release='release/vendoring-local-head-20260904'
$base='local-priority-vendoring-finish'
Write-Output '=== INSPECTION TESTS vs RELEASE ==='
$files=@(& git -C $repo ls-tree -r --name-only $base -- tests | Where-Object { $_ -match '\.php$' })
$missing=@();$same=0;$changed=0
foreach ($f in $files) { $old=(& git -C $repo rev-parse --verify ($base+':'+$f) 2>$null) -join ''; $new=(& git -C $repo rev-parse --verify ($release+':'+$f) 2>$null) -join ''; if (!$new) { $missing+=$f } elseif ($old -eq $new) { $same++ } else { $changed++ } }
Write-Output ('TEST_FILES='+$files.Count+' SAME='+$same+' CHANGED='+$changed+' PATH_MISSING='+$missing.Count)
$missing | Select-Object -First 32
Write-Output '=== IMPORTANT INSPECTION TESTS IN RELEASE ==='
foreach ($pattern in @('CanonicalNoPlaceholderRepositoryContractTest','CanonicalNoStubRepositoryContractTest','VendorFixtureStackContractTest','VendorTransactionLifecycleServiceTest','VendorProfileProjectionBuilderTest')) {
$hits=@(& git -C $repo ls-tree -r --name-only $release -- tests | Select-String $pattern)
Write-Output ($pattern+' => '+($hits -join ','))
}
