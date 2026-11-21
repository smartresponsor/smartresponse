$ErrorActionPreference = 'Stop'
$arguments = @('bin/console','app:doctrine:mapped-schema:validate','--em=postgres','--ignore-index-names','--env=dev','--no-interaction') + $args
& php @arguments
exit $LASTEXITCODE
