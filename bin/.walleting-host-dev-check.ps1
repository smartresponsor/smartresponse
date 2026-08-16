$env:APP_ENV='dev'
$env:APP_DEBUG='0'
php bin/console lint:container --env=dev --no-debug
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php bin/console doctrine:schema:validate --skip-sync --env=dev --no-debug
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php bin/console doctrine:migrations:list --env=dev --no-debug | Select-String -Pattern 'App\\Walleting\\Migrations|Version20260808'
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php bin/console debug:router --env=dev --no-debug | Select-String -Pattern 'wallet|cruding'
exit 0
