git restore --source=HEAD -- composer.lock
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
composer update smartresponsor/walleting --no-interaction --no-scripts
exit $LASTEXITCODE
