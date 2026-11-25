$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$release='326e99cd5feb031be91ed1fc3163766ea071e638'
$other='ea60599dfeb69feae1c8d12322b0a094115afd13'
$old=@(& git -C $repo ls-tree -r --name-only $other -- config/platform/routes)
$new=@(& git -C $repo ls-tree -r --name-only $release -- config/platform/routes)
function Read-Routes($sha,$paths) { $map=@{}; foreach($path in $paths){$content=(& git -C $repo show ($sha+':'+$path)); foreach($line in $content){if($line -match '^\s*([a-z][a-z0-9_.-]+):\s*\{\s*path:\s*([^,}]+)'){ $map[$Matches[1]]=[pscustomobject]@{Path=$Matches[2].Trim();File=$path;Line=$line.Trim()} }}}; return $map }
$a=Read-Routes $other $old
$b=Read-Routes $release $new
Write-Output ('LEGACY_ROUTE_COUNT='+$a.Count)
Write-Output ('RELEASE_ROUTE_COUNT='+$b.Count)
$missing=@($a.Keys | Where-Object { !$b.ContainsKey($_) } | Sort-Object)
$added=@($b.Keys | Where-Object { !$a.ContainsKey($_) } | Sort-Object)
$changed=@($a.Keys | Where-Object {$b.ContainsKey($_) -and $a[$_].Path -ne $b[$_].Path} | Sort-Object)
Write-Output ('MISSING_ROUTE_KEYS='+$missing.Count)
$missing | ForEach-Object {Write-Output ('MISSING '+$_+' '+$a[$_].Path)}
Write-Output ('ADDED_ROUTE_KEYS='+$added.Count)
$added | Select-Object -First 65 | ForEach-Object {Write-Output ('ADDED '+$_+' '+$b[$_].Path)}
Write-Output ('CHANGED_ROUTE_PATHS='+$changed.Count)
$changed | ForEach-Object {Write-Output ('CHANGED '+$_+' old='+$a[$_].Path+' new='+$b[$_].Path)}
