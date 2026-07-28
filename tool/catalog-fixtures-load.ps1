param()

$ErrorActionPreference = 'Stop'
$env:APP_ENV = 'dev'
$env:APP_DEBUG = '1'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$php = 'C:\PHP\php-8.4.13-nts-Win32-vs17-x64\php.exe'
if (-not (Test-Path $php)) {
    $php = 'php'
}

& $php bin/console doctrine:fixtures:load --group=cataloging --append --no-interaction
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

& $php bin/console cache:clear --env=dev --no-warmup
