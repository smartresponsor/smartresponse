$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$release='326e99cd5feb031be91ed1fc3163766ea071e638'
$other='ea60599dfeb69feae1c8d12322b0a094115afd13'
$a=@(& git -C $repo ls-tree -r --name-only $release -- src tests config)
$b=@(& git -C $repo ls-tree -r --name-only $other -- src tests config)
$only=@($b | Where-Object {$_ -notin $a})
$groups=$only | ForEach-Object { $parts=$_ -split '/'; if($parts.Length -ge 3){$parts[0]+'/'+$parts[1]} else {$parts[0]} } | Group-Object | Sort-Object Count -Descending
Write-Output '=== MISSING PATH GROUPS ==='
$groups | ForEach-Object {Write-Output ('{0,4} {1}' -f $_.Count,$_.Name)}
Write-Output '=== LEGACY ROUTE CONFIG VS RELEASE ==='
$only | Where-Object {$_ -like 'config/platform/routes*' -or $_ -like 'config/component*'} | ForEach-Object {Write-Output $_}
Write-Output '=== UNIQUE TESTS NOT IN RELEASE ==='
$only | Where-Object {$_ -like 'tests/*'} | Select-Object -First 70
Write-Output '=== RELEASE ROUTE AND CONTROLLER INVENTORY ==='
& git -C $repo ls-tree -r --name-only $release -- config src | Select-String '(route|Controller)' | Select-Object -First 65
Write-Output '=== UNIQUE BRANCH PATHS THAT LOOK LIKE PUBLIC API ==='
$only | Where-Object {$_ -match '^(src/(EntityInterface|EventInterface|ServiceInterface|Form)|config/platform/routes)' } | Select-Object -First 100
