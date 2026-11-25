$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
foreach ($name in @('Cruding','Vendoring')) {
$repo=Join-Path $root $name
Write-Output ('=== '+$name+' ===')
$refs=@(& git -C $repo for-each-ref --format='%(refname)' refs/heads refs/remotes/origin | Where-Object { $_ -notmatch '/HEAD$' })
$shaToRefs=@{}
foreach ($ref in $refs) { $sha=(& git -C $repo rev-parse $ref).Trim(); if (!$shaToRefs.ContainsKey($sha)) {$shaToRefs[$sha]=@()}; $shaToRefs[$sha]+=$ref }
Write-Output ('REFS='+$refs.Count+' UNIQUE_TIPS='+$shaToRefs.Count)
foreach ($sha in ($shaToRefs.Keys | Sort-Object)) {
$ref=$shaToRefs[$sha][0]
$counts=(& git -C $repo rev-list --left-right --count ('master...'+$sha)).Trim()
$unique=@(& git -C $repo log --format='%H' ('master..'+$sha))
$patches=@(& git -C $repo cherry master $sha 2>$null)
$equiv=@($patches | Where-Object { $_ -match '^-' }).Count
$new=@($patches | Where-Object { $_ -match '^\+' }).Count
Write-Output ($sha.Substring(0,12)+'|'+$counts+'|UNIQUE='+$unique.Count+'|PATCH_EQ='+$equiv+'|PATCH_NEW='+$new+'|REFS='+($shaToRefs[$sha] -join ','))
}
}
