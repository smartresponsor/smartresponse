$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$sha='326e99cd5feb031be91ed1fc3163766ea071e638'
$paths=@('src/Service/Payout/VendorPayoutApproveService.php','src/Service/Payout/VendorPayoutPayService.php','src/Service/Category/VendorCategoryAssignService.php','src/Service/Document/VendorDocumentVerifyService.php','src/Service/Product/VendorProductAssignService.php','src/Form/Payout/VendorPayoutApproveType.php','src/Form/Category/VendorCategoryAssignType.php','src/Form/Document/VendorDocumentVerifyType.php')
foreach($path in $paths){Write-Output ('=== '+$path+' ===');$content=@(& git -C $repo show ($sha+':'+$path) 2>$null);if($LASTEXITCODE -ne 0){Write-Output 'MISSING';continue};Write-Output ('LINES='+$content.Count);$content | Select-Object -First 115}
