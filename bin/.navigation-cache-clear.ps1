$env:APP_ENV='dev'
$env:APP_DEBUG='0'
php bin/console cache:pool:clear cache.app --env=dev --no-debug
