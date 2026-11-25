$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$base='474b7a6cd3742fcc099d58450cd1c9b80407eab6'
$old='origin/local-priority-vendoring-finish'
Write-Output '=== UNIQUE LEGACY FILES BY TOP FOLDERS ==='
$paths=@(& git -C $repo diff --name-only --diff-filter=A "$base...$old" -- src tests config)
Write-Output ('ADDED_PATHS='+$paths.Count)
$paths | ForEach-Object {($_ -split '/')[0..([Math]::Min(2,($_ -split '/').Length-1))] -join '/'} | Group-Object | Sort-Object Count -Descending | Select-Object -First 35 | ForEach-Object {Write-Output ($_.Count.ToString()+' '+$_.Name)}
Write-Output '=== LEGACY TESTS ABSENT BY NAME IN RELEASE ==='
$oldTests=@(& git -C $repo ls-tree -r --name-only $old -- tests | Where-Object {$_ -like '*.php'})
$newTests=@(& git -C $repo ls-tree -r --name-only $base -- tests | Where-Object {$_ -like '*.php'})
$newNames=@{};foreach($p in $newTests){$newNames[[IO.Path]::GetFileName($p)]=1}
foreach($p in $oldTests){if(!$newNames.ContainsKey([IO.Path]::GetFileName($p))){Write-Output $p}}
Write-Output '=== CANONICAL MISSING BEHAVIOR CANDIDATES ==='
$candidates=@(& git -C $repo diff --name-only --diff-filter=A "$base...$old" -- src/Policy src/Projection src/Lifecycle src/Support src/Subscriber)
$candidates | Select-Object -First 80
