$ErrorActionPreference = 'Stop'
$outputPath = Join-Path $PSScriptRoot '..\var\relating-create.sql'
& php bin/console app:doctrine:mapped-schema:validate '--em=postgres' '--table-prefix=relating_' '--dump-missing-create-sql' '--env=dev' '--no-interaction' | Set-Content -LiteralPath $outputPath
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
