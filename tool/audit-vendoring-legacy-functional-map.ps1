$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$base='474b7a6cd3742fcc099d58450cd1c9b80407eab6'
$old='origin/local-priority-vendoring-finish'
$candidates=@('src/Lifecycle/VendorLifecyclePolicy.php','src/Projection/Vendor/VendorLegacyMediaAttachmentCandidate.php','src/Projection/Vendor/VendorPublicProfileAttachmentProjection.php','src/Projection/Vendor/VendorPublicProfileSummary.php','src/Subscriber/Observability/VendorCorrelationIdSubscriber.php','src/Support/Http/VendorApiErrorResponseTrait.php','src/Support/Http/VendorBusinessOperationHttpTrait.php','src/Support/Http/VendorHttpRequestTrait.php','src/Support/Http/VendorStatementRequestHttpResolutionTrait.php')
$all=@(& git -C $repo ls-tree -r --name-only $base -- src)
foreach($p in $candidates){$name=[IO.Path]::GetFileName($p);$hits=@($all | Where-Object {([IO.Path]::GetFileName($_) -eq $name) -or ($_ -match [regex]::Escape(($name -replace '(Service|Trait|Subscriber|Policy)\.php$','')))});Write-Output ('CANDIDATE '+$p);Write-Output ('CANONICAL_MATCHES='+$hits.Count);$hits | Select-Object -First 12}
Write-Output '=== LEGACY BEHAVIOR LOCATIONS ==='
foreach($p in @('src/Lifecycle/VendorLifecyclePolicy.php','src/Subscriber/Observability/VendorCorrelationIdSubscriber.php','src/Support/Http/VendorBusinessOperationHttpTrait.php')){Write-Output ('--- '+$p);& git -C $repo show "$($old):$p" | Select-Object -First 95}
