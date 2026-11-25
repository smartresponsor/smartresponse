$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$sha='326e99cd5feb031be91ed1fc3163766ea071e638'
Write-Output '=== RELEASE DOMAIN SERVICES ==='
& git -C $repo ls-tree -r --name-only $sha -- src/Service src/Provider src/RepositoryInterface | Select-String 'Payout|Category|Document|Product|Commission' | Select-Object -First 110
Write-Output '=== OPERATION SERVICE REFERENCES ==='
foreach($needle in @('approvePayout','payPayout','assignCategory','verifyDocument','VendorPayoutEntity','VendorPayoutRepositoryInterface','VendorCategoryRepositoryInterface','VendorDocumentRepositoryInterface')){$hits=@(& git -C $repo grep -l -F $needle $sha -- src tests 2>$null);Write-Output ('NEEDLE '+$needle+' count='+$hits.Count);$hits | Select-Object -First 13}
Write-Output '=== PAYOUT ENTITY AND STATE CONTRACTS ==='
& git -C $repo ls-tree -r --name-only $sha -- src/Entity src/Enum src/DTO src/ServiceInterface | Select-String 'Payout|Category|Document' | Select-Object -First 80
