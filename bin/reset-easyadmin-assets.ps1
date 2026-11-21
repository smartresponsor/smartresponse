$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
    php bin/console assets:install public --symlink
    php bin/console cache:clear --env dev --no-warmup
} finally {
    Pop-Location
}
