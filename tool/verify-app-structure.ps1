$ErrorActionPreference = 'Stop'
php tools/inspection/app-source-structure-guard.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
composer dump-autoload --no-scripts
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php vendor/bin/phpunit tests/Unit/Architecture/AppSourceStructureTest.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php vendor/bin/phpunit tests/Unit/EventSubscriber tests/Unit/Runtime/AppEffectiveRuntimeServiceTest.php tests/Unit/Entity/Trait/TimestampableTraitTest.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php bin/console lint:container
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
Write-Output 'verification passed'
