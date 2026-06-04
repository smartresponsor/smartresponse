$ErrorActionPreference = "Stop"

$env:COMPOSER = "composer.prod.json"

try {
    composer install --no-dev --prefer-dist --optimize-autoloader --classmap-authoritative
}
finally {
    Remove-Item Env:COMPOSER -ErrorAction SilentlyContinue
}