$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$release='326e99cd5feb031be91ed1fc3163766ea071e638'
$other='ea60599dfeb69feae1c8d12322b0a094115afd13'
$old=@(& git -C $repo ls-tree -r --name-only $other -- config/platform/routes)
$new=@(& git -C $repo ls-tree -r --name-only $release -- config/platform/routes)
function Read-Routes($sha,$paths) { $map=@{}; foreach($path in $paths){foreach($line in (& git -C $repo show ($sha+':'+$path))){if($line -match '^\s*([a-z][a-z0-9_.-]+):\s*\{\s*path:\s*(.+?),\s*(?:type|service|resolver|methods):'){ $map[$Matches[1]]=[pscustomobject]@{Path=$Matches[2].Trim();File=$path;Line=$line.Trim()} }}}; return $map }
$a=Read-Routes $other $old
$b=Read-Routes $release $new
Write-Output ('OLD='+$a.Count+' NEW='+$b.Count)
$missing=@($a.Keys | Where-Object {!$b.ContainsKey($_)} | Sort-Object)
$changed=@($a.Keys | Where-Object {$b.ContainsKey($_) -and $a[$_].Path -ne $b[$_].Path} | Sort-Object)
Write-Output ('MISSING='+$missing.Count)
$missing | ForEach-Object {Write-Output ('MISSING '+$_+' '+$a[$_].Path)}
Write-Output ('CHANGED='+$changed.Count)
$changed | ForEach-Object {Write-Output ('CHANGED '+$_+' '+$a[$_].Path+' -> '+$b[$_].Path)}
Write-Output '=== PAYOUT LEGACY AND RELEASE FULL ENTRIES ==='
foreach($k in @('vendor.payout.approve','vendor.payout.pay','vendor.payout.approve_id','vendor.payout.pay_id')){if($a.ContainsKey($k)){Write-Output ('OLD '+$a[$k].Line)};if($b.ContainsKey($k)){Write-Output ('NEW '+$b[$k].Line)}}
