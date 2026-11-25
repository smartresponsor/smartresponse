$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$release='326e99cd5feb031be91ed1fc3163766ea071e638'
$other='ea60599dfeb69feae1c8d12322b0a094115afd13'
$base=(& git -C $repo merge-base $release $other).Trim()
Write-Output '=== UNIQUE COMMIT PATH/CHANGE STATS ==='
$commits=@(& git -C $repo rev-list --reverse ($base+'..'+$other))
foreach($sha in $commits){ $meta=(& git -C $repo show -s --format='%h %s' $sha).Trim(); $files=@(& git -C $repo diff-tree --no-commit-id --name-only -r $sha); $code=@($files | Where-Object {$_ -match '^(src|tests|config)/'}); $test=@($files | Where-Object {$_ -match '^tests/'}); $non=@($files | Where-Object {$_ -notmatch '^(src|tests|config)/'}); Write-Output ('COMMIT '+$meta+' CODE='+$code.Count+' TEST='+$test.Count+' OTHER='+$non.Count); if($code.Count -le 12){$code | ForEach-Object {Write-Output ('  '+$_)}} }
Write-Output '=== RELEASE ONLY FILES ==='
$releasePaths=@(& git -C $repo ls-tree -r --name-only $release -- src tests config)
$otherPaths=@(& git -C $repo ls-tree -r --name-only $other -- src tests config)
$onlyOther=@($otherPaths | Where-Object {$_ -notin $releasePaths})
Write-Output ('ONLY_OTHER_COUNT='+$onlyOther.Count)
$onlyOther | Select-Object -First 90
Write-Output '=== RELEASE SOURCE COUNT ==='
Write-Output ('RELEASE_PHP='+(@($releasePaths | Where-Object {$_ -like '*.php'}).Count))
Write-Output ('OTHER_PHP='+(@($otherPaths | Where-Object {$_ -like '*.php'}).Count))
