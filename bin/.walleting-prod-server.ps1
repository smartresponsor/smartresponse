$env:APP_ENV='prod'
$env:APP_DEBUG='0'
$env:APP_BASE_URI='http://127.0.0.1:8002'
$env:APP_CACHE_DIR=(Join-Path $PSScriptRoot '..\var\cache\walleting_http_probe')
Set-Location (Join-Path $PSScriptRoot '..')
php -S 127.0.0.1:8002 -t public public/index.php
exit $LASTEXITCODE
