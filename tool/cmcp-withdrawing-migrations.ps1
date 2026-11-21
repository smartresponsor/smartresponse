param(
    [switch] $Apply
)

$ErrorActionPreference = 'Stop'
$php = 'php'
$arguments = @(
    'bin/console',
    'doctrine:migrations:migrate',
    '--configuration',
    'bin/.withdrawing-migrations.yaml',
    '--no-interaction',
    '--env=dev'
)

if (-not $Apply) {
    $arguments += '--dry-run'
}

& $php @arguments
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
