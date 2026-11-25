$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
foreach ($name in @('Cruding','Vendoring')) {
$repo=Join-Path $root $name
Write-Output ('=== ' + $name + ' ===')
$refs=@(& git -C $repo for-each-ref --format='%(refname)' refs/heads refs/remotes/origin | Where-Object { $_ -notmatch '/HEAD$' })
Write-Output ('REF_COUNT=' + $refs.Count)
$master=(& git -C $repo rev-parse master).Trim()
foreach ($ref in $refs) {
$sha=(& git -C $repo rev-parse $ref).Trim()
$counts=(& git -C $repo rev-list --left-right --count ('master...'+$ref)).Trim()
$unique=@(& git -C $repo log --format='%h|%s' ('master..'+$ref) -n 3)
$changes=@(& git -C $repo diff --name-only ('master...'+$ref) -- src config tests tools | Select-Object -First 5)
Write-Output (($ref -replace '^refs/(heads/|remotes/)','')+'|'+$sha.Substring(0,12)+'|'+$counts+'|files='+($changes -join ',')+'|commits='+($unique -join ';'))
}
}
