$env:APP_ENV='prod'
$env:APP_DEBUG='0'
$env:APP_CACHE_DIR=(Join-Path $PSScriptRoot '..\var\cache\walleting_cruding_probe')
php bin/console debug:container 'App\Cruding\Routing\CrudingRouteManifest' --show-hidden --env=prod --no-debug
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php bin/console debug:container --tag=routing.loader --env=prod --no-debug
exit $LASTEXITCODE
