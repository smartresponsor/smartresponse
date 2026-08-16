$ErrorActionPreference = 'Stop'
$env:APP_ENV = 'prod'
$env:APP_DEBUG = '0'
Write-Output '--- DOCTRINE SCHEMA DIFF (READ ONLY) ---'
& php bin/console doctrine:schema:update --dump-sql --env=prod --no-debug
$code = $LASTEXITCODE
Remove-Item -LiteralPath $PSCommandPath -Force -ErrorAction SilentlyContinue
exit $code

