$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$cache = Join-Path $root 'var/cache/diagnostic-doctrine'
$oldCache = [Environment]::GetEnvironmentVariable('APP_CACHE_DIR','Process')
$oldDb = [Environment]::GetEnvironmentVariable('DATABASE_URL','Process')
try {
    [Environment]::SetEnvironmentVariable('APP_CACHE_DIR',$cache,'Process')
    if ([string]::IsNullOrWhiteSpace($oldDb)) {
        [Environment]::SetEnvironmentVariable('DATABASE_URL','sqlite:///:memory:','Process')
        Write-Output 'DATABASE_URL source: ephemeral in-memory diagnostic SQLite (not a real database)'
    } else {
        Write-Output 'DATABASE_URL source: preexisting process environment (value redacted)'
    }
    Push-Location $root
    try {
        & php tools/inspection/app-rolling-host-guard.php $root
        $exit = $LASTEXITCODE
        Write-Output "ISOLATED_LIFECYCLE_EXIT $exit"
        if ($exit -ne 0) { exit $exit }
    } finally { Pop-Location }
} finally {
    [Environment]::SetEnvironmentVariable('APP_CACHE_DIR',$oldCache,'Process')
    [Environment]::SetEnvironmentVariable('DATABASE_URL',$oldDb,'Process')
}
