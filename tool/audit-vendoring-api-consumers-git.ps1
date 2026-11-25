$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repos=@('App','Cruding','Vendoring','Viewing','Paying','Cataloging','OneTasker')
$needles=@('/vendor/payout/approve/','/vendor/payout/pay/','/vendor/category/assign/','/vendor/category/unassign/','/vendor/document/verify/','/vendor/document/reject/','/vendor/commission/adjust/','/vendor/product/assign/','/vendor/product/unassign/')
foreach($name in $repos){$path=Join-Path $root $name;if(!(Test-Path (Join-Path $path '.git'))){continue};Write-Output ('=== REPO '+$name+' ===');foreach($needle in $needles){$found=@(& git -C $path grep -n -F -- $needle -- ':!vendor' ':!var' ':!tests' ':!node_modules' 2>$null);if($found.Count){Write-Output ('MATCH '+$needle+' count='+$found.Count);$found | Select-Object -First 8}}}
Write-Output '=== RELEASE BRANCH TEST/CONFIG CONTRACTS ==='
$repo=Join-Path $root 'Vendoring'
$release='326e99cd5feb031be91ed1fc3163766ea071e638'
foreach($needle in @('vendor.payout.approve','vendor.payout.pay','vendor.category.assign','vendor.document.verify')){$hits=@(& git -C $repo grep -n -F $needle $release -- src tests config 2>$null);Write-Output ('KEY '+$needle+' hits='+$hits.Count);$hits | Select-Object -First 10}
