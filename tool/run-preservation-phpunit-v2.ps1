$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$wt=Join-Path $root '.worktrees/preservation-vendoring-20261121'
$source=Join-Path $root 'Vendoring'
$runner=Join-Path $source 'vendor/phpunit/phpunit/phpunit'
$bootstrap=Join-Path $wt 'tool/preservation-bootstrap.php'
$test=Join-Path $wt 'tests/Unit/Service/VendorRouteServicesTest.php'
Push-Location $wt
try { & php $runner --configuration (Join-Path $wt 'phpunit.xml.dist') --bootstrap $bootstrap $test --no-coverage; Write-Output ('PHPUNIT_EXIT='+$LASTEXITCODE) } finally { Pop-Location }
