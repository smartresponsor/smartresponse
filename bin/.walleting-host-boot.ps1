$env:APP_ENV='prod'
$env:APP_DEBUG='0'
$env:APP_BASE_URI='http://127.0.0.1:8000'
$env:APP_CACHE_DIR=(Join-Path $PSScriptRoot '..\var\cache\walleting_host_probe2')
php bin/console lint:container --env=prod --no-debug
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php bin/console doctrine:schema:validate --skip-sync --env=prod --no-debug
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php bin/console router:match /wallet/index --env=prod --no-debug
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php bin/console doctrine:migrations:list --env=prod --no-interaction --no-debug | Select-String 'App\\Walleting\\Migrations'
exit $LASTEXITCODE
