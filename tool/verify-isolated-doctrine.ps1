$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$cache = Join-Path $root 'var/cache/diagnostic-doctrine'
if (-not (Test-Path -LiteralPath $cache -PathType Container)) { throw 'Diagnostic cache is missing' }
$previous = [Environment]::GetEnvironmentVariable('APP_CACHE_DIR', 'Process')
try {
    [Environment]::SetEnvironmentVariable('APP_CACHE_DIR', $cache, 'Process')
    Push-Location $root
    try {
        & php tools/inspection/app-rolling-host-guard.php $root
        $code = $LASTEXITCODE
        Write-Output "ISOLATED_GUARD_EXIT $code"
        if ($code -ne 0) { exit $code }
    } finally { Pop-Location }
} finally {
    [Environment]::SetEnvironmentVariable('APP_CACHE_DIR', $previous, 'Process')
}
