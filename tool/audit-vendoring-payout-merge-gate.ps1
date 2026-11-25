$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$fix='39030f2166a835462463504b0438627272a993a0'
$release='326e99cd5feb031be91ed1fc3163766ea071e638'
$master='origin/master'
foreach($ref in @($fix,$release,$master,'origin/fix/vendoring-payout-failclosed-20261121','origin/fix/preservation-vendoring-regression-20261121')){Write-Output ('REF '+$ref);& git -C $repo rev-parse $ref}
Write-Output '=== SIGNATURE ==='
& git -C $repo log -1 --format='%H %G? %s' $fix
Write-Output '=== MASTER DIVERGENCE ==='
& git -C $repo rev-list --left-right --count "$master...$fix"
Write-Output '=== RELEASE ANCESTRY ==='
& git -C $repo merge-base --is-ancestor $release $fix;Write-Output ('RELEASE_IS_ANCESTOR='+($LASTEXITCODE -eq 0))
Write-Output '=== UNMERGED BRANCH COUNTS ==='
& git -C $repo branch -r --no-merged $fix | Select-Object -First 35
Write-Output '=== HOST APP STATUS ==='
& git -C (Resolve-Path (Join-Path $PSScriptRoot '..')).Path status --short | Select-Object -First 25
