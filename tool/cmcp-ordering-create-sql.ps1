$ErrorActionPreference = 'Stop'
$outputPath = Join-Path $PSScriptRoot '..\var\ordering-create.sql'
& php bin/console app:doctrine:mapped-schema:validate '--em=postgres' '--namespace-prefix=App\Ordering\Entity' '--dump-missing-create-sql' '--env=dev' '--no-interaction' | Set-Content -LiteralPath $outputPath
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
