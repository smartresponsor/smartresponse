$ErrorActionPreference = 'Stop'

$commands = @(
    @('debug:config', 'doctrine', 'orm'),
    @('doctrine:mapping:info', '--em=postgres'),
    @('doctrine:schema:validate', '--em=postgres'),
    @('lint:container')
)

foreach ($arguments in $commands) {
    Write-Host "`n> php bin/console $($arguments -join ' ')"
    & php bin/console @arguments
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}
