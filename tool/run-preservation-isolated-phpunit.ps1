$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$wt=Join-Path $root '.worktrees/preservation-vendoring-20261121'
$source=Join-Path $root 'Vendoring'
$junction=Join-Path $wt 'vendor'
if (!(Test-Path $junction)) { New-Item -ItemType Junction -Path $junction -Target (Join-Path $source 'vendor') | Out-Null }
$runner=Join-Path $source 'vendor/phpunit/phpunit/phpunit'
$prepend=Join-Path $wt 'tool/preservation-autoload.php'
$test=Join-Path $wt 'tests/Unit/Service/VendorRouteServicesTest.php'
Write-Output '=== ISOLATED PHPUNIT ==='
Push-Location $wt
try { & php ('-dauto_prepend_file='+$prepend) $runner --configuration (Join-Path $wt 'phpunit.xml.dist') $test --no-coverage; $code=$LASTEXITCODE; Write-Output ('PHPUNIT_EXIT='+$code) } finally { Pop-Location }
Write-Output '=== WORKTREE STATUS ==='
& git -C $wt status --short
