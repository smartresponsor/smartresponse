$env:APP_ENV='prod'
$env:APP_DEBUG='0'
$env:APP_CACHE_DIR=(Join-Path $PSScriptRoot '..\var\cache\walleting_db_identity')
php bin/console doctrine:query:sql "SELECT current_database() AS database_name, to_regclass('public.wallet') AS wallet_table, to_regclass('public.ledger_transaction') AS ledger_table" --env=prod --no-debug
exit $LASTEXITCODE
