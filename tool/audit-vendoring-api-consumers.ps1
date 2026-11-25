$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo=Join-Path $root 'Vendoring'
$release='326e99cd5feb031be91ed1fc3163766ea071e638'
$legacy='ea60599dfeb69feae1c8d12322b0a094115afd13'
$patterns=@('/vendor/payout/approve/','/vendor/payout/pay/','/vendor/category/assign/','/vendor/category/unassign/','/vendor/document/verify/','/vendor/document/reject/','/vendor/commission/adjust/','/vendor/product/assign/','/vendor/product/unassign/')
Write-Output '=== WINDOWS HOST WORKSPACE CROSS-REPO CALLSITE AUDIT ==='
$repos=@('App','Cruding','Vendoring','Viewing','Paying','Cataloging','OneTasker')
foreach($name in $repos){$path=Join-Path $root $name;if(!(Test-Path $path)){continue};Write-Output ('REPO '+$name);$files=@(& rg -l --fixed-strings --glob '*.php' --glob '*.yaml' --glob '*.yml' --glob '*.twig' --glob '*.js' --glob '*.ts' --glob '*.json' --glob '!vendor/**' --glob '!node_modules/**' --glob '!var/**' --glob '!tests/**' '/vendor/payout/' $path 2>$null);Write-Output ('PAYOUT_REFERENCING_FILES='+$files.Count);$files | Select-Object -First 12 | ForEach-Object {Write-Output ('  '+$_)};foreach($needle in @('/vendor/category/assign/','/vendor/document/verify/','/vendor/product/assign/')){$hits=@(& rg -l --fixed-strings --glob '!vendor/**' --glob '!node_modules/**' --glob '!var/**' --glob '!tests/**' $needle $path 2>$null);if($hits.Count){Write-Output ('  '+$needle+' files='+$hits.Count);$hits | Select-Object -First 5}}}
Write-Output '=== RELEASE SOURCE SERVICE CONSUMER AUDIT ==='
$srcFiles=@(& git -C $repo ls-tree -r --name-only $release -- src)
foreach($needle in @('payoutId','categoryId','documentId','productId')){$hits=0;$sample=@();foreach($file in $srcFiles){if($file -notlike '*.php'){continue};$content=(& git -C $repo show ($release+':'+$file) 2>$null) -join "`n";if($content.Contains($needle)){$hits++;if($sample.Count -lt 9){$sample+= $file}}};Write-Output ('PARAM '+$needle+' files='+$hits+' samples='+($sample -join ';'))}
