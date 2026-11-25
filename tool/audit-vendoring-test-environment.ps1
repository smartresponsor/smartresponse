$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$wt=Join-Path $root '.worktrees/preservation-vendoring-20261121'
$source=Join-Path $root 'Vendoring'
foreach ($p in @((Join-Path $source 'vendor/autoload.php'),(Join-Path $source 'vendor/bin/phpunit'),(Join-Path $root 'App/vendor/bin/phpunit'),(Join-Path $wt 'phpunit.xml.dist'),(Join-Path $wt 'phpunit.xml'))) {Write-Output ((Test-Path $p).ToString()+' '+$p)}
Write-Output '=== SOURCE COMPOSER/TEST FILES ==='
Get-ChildItem $wt -Name | Where-Object {$_ -match 'composer|phpunit|vendor'}
Write-Output '=== WORKTREE TEST PATH ==='
& git -C $wt status --short
