$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$old='ea60599dfeb69feae1c8d12322b0a094115afd13'
$new='326e99cd5feb031be91ed1fc3163766ea071e638'
$terms=@('VendorPayoutApproveService','VendorPayoutPayService','VendorCategoryAssignService','VendorDocumentVerifyService','VendorPayoutProvider','VendorCategoryAssignment','VendorDocumentVerification')
foreach($term in $terms){Write-Output ('=== TERM '+$term+' ===');foreach($sha in @($old,$new)){$hits=@(& git -C $repo grep -l -F $term $sha -- src tests config 2>$null);Write-Output ('BRANCH '+$sha.Substring(0,7)+' MATCHES='+$hits.Count);$hits | Select-Object -First 12}}
Write-Output '=== LEGACY SERVICE CLASS FILES ==='
& git -C $repo ls-tree -r --name-only $old -- src/Service | Select-String 'PayoutApprove|PayoutPay|CategoryAssign|DocumentVerify' | Select-Object -First 50
Write-Output '=== RELEASE SERVICE CLASS FILES ==='
& git -C $repo ls-tree -r --name-only $new -- src/Service | Select-String 'PayoutApprove|PayoutPay|CategoryAssign|DocumentVerify' | Select-Object -First 50
