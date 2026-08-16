Set-Content -LiteralPath (Join-Path $PSScriptRoot '..\public\walleting-health.txt') -Value 'ok' -NoNewline
$env:APP_ENV='dev'
php bin/console navigation:database:update --env=dev
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php bin/console navigation:database:import-config --env=dev
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
exit 0
