$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$assets = Join-Path $root 'public/bundles/easyadmin'
if (Test-Path -LiteralPath $assets) {
    Remove-Item -LiteralPath $assets -Recurse -Force
}
Push-Location $root
try {
    php bin/console assets:install public --symlink
    php bin/console cache:clear --env dev --no-warmup
} finally {
    Pop-Location
}
