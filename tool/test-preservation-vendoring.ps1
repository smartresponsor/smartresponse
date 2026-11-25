$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo=Join-Path $root '.worktrees/preservation-vendoring-20261121'
Write-Output '=== PHP LINT ==='
& php -l (Join-Path $repo 'src/Service/VendorUpdateService.php')
& php -l (Join-Path $repo 'tests/Unit/Service/VendorRouteServicesTest.php')
Write-Output '=== TEST RUNNER ==='
$phpunit=Join-Path $repo 'vendor/bin/phpunit'
if (Test-Path $phpunit) { & php $phpunit --configuration (Join-Path $repo 'phpunit.xml.dist') (Join-Path $repo 'tests/Unit/Service/VendorRouteServicesTest.php'); Write-Output ('PHPUNIT_EXIT='+$LASTEXITCODE) } else {Write-Output 'PHPUNIT_UNAVAILABLE_IN_ISOLATED_WORKTREE'}
Write-Output '=== GIT DIFF CHECK ==='
& git -C $repo diff --check
Write-Output ('DIFF_CHECK_EXIT='+$LASTEXITCODE)
& git -C $repo status --short
