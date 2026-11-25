$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$wt=Join-Path $root '.worktrees/vendoring-master-brand-fix-20261121'
$vendor=Join-Path $wt 'vendor'
if (!(Test-Path $vendor)) {New-Item -ItemType Junction -Path $vendor -Target (Join-Path $root 'Vendoring/vendor') | Out-Null}
& php -l (Join-Path $wt 'src/Service/Vendor/VendorUpdateService.php')
& php -l (Join-Path $wt 'tests/Unit/Service/VendorRouteServicesTest.php')
& git -C $wt diff --check
Write-Output ('DIFF_CHECK_EXIT='+$LASTEXITCODE)
Push-Location $wt
try { & php (Join-Path $root 'Vendoring/vendor/phpunit/phpunit/phpunit') --configuration (Join-Path $wt 'phpunit.xml.dist') --bootstrap (Join-Path $wt 'tool/preservation-master-bootstrap.php') (Join-Path $wt 'tests/Unit/Service/VendorRouteServicesTest.php') --no-coverage; Write-Output ('PHPUNIT_EXIT='+$LASTEXITCODE) } finally {Pop-Location}
