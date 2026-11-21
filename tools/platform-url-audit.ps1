[CmdletBinding()]
param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [int]$Timeout = 15,
    [int]$Samples = 1,
    [int]$SlowMs = 250,
    [ValidateSet('dev','prod')][string]$AppEnv = 'prod',
    [switch]$SkipRuntimePreflight
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$previousAppEnv = $env:APP_ENV
$previousAppDebug = $env:APP_DEBUG
$previousXdebugMode = $env:XDEBUG_MODE
$env:APP_ENV = $AppEnv
$env:APP_DEBUG = '0'
$env:XDEBUG_MODE = 'off'
Push-Location $root
$runtimeStartedAt = [System.Diagnostics.Stopwatch]::StartNew()
if (-not $SkipRuntimePreflight) {
    try {
        $runtimeStatus = $null
        try {
            $runtimeResponse = Invoke-WebRequest -UseBasicParsing -Uri ($BaseUrl.TrimEnd('/') + '/access/signin') -TimeoutSec ([Math]::Max(1, [Math]::Min($Timeout, 5)))
            $runtimeStatus = [int]$runtimeResponse.StatusCode
        }
        catch [System.Net.WebException] {
            if ($null -ne $_.Exception.Response) {
                $runtimeStatus = [int]$_.Exception.Response.StatusCode
            }
            else {
                throw
            }
        }
        $runtimeStartedAt.Stop()
        if ($null -eq $runtimeStatus -or $runtimeStatus -ge 500) {
            throw "HTTP runtime preflight returned status $runtimeStatus."
        }
        Write-Output ("HTTP runtime preflight: {0} ms ({1})" -f $runtimeStartedAt.ElapsedMilliseconds, $runtimeStatus)
    }
    catch {
        $runtimeStartedAt.Stop()
        throw "HTTP runtime preflight failed after $($runtimeStartedAt.ElapsedMilliseconds) ms at $BaseUrl. The server may be stopped or blocked: $($_.Exception.Message)"
    }
}
else {
    $runtimeStartedAt.Stop()
    Write-Output 'HTTP runtime preflight: skipped (kernel-only audit mode)'
}

$runToken = "$PID-$([Guid]::NewGuid().ToString('N'))"
$inventoryErrorFile = Join-Path $root ("var/url-audit/inventory-$runToken.stderr.log")
$auditErrorFile = Join-Path $root ("var/url-audit/run-$runToken.stderr.log")
try {
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $inventoryErrorFile) | Out-Null
    $ErrorActionPreference = 'Continue'
    php bin/console app:url-audit:inventory --output=var/url-audit/latest-inventory.json 2> $inventoryErrorFile
    $inventoryExitCode = $LASTEXITCODE
    $ErrorActionPreference = 'Stop'
    if ($inventoryExitCode -ne 0) {
        $inventoryError = (Get-Content -Path $inventoryErrorFile -ErrorAction SilentlyContinue | Out-String).Trim()
        if ([string]::IsNullOrWhiteSpace($inventoryError)) { $inventoryError = 'No inventory stderr was produced.' }
        throw "Inventory failed with exit code $inventoryExitCode. $inventoryError"
    }

    # Keep the audit process stderr attached to the parent process. In production
    # Monolog writes to php://stderr; redirecting native stderr to a file with
    # Windows PowerShell can invalidate that handle and mask the real exception
    # with "Write failed with errno=22 Invalid argument".
    $ErrorActionPreference = 'Continue'
    php bin/console app:url-audit:run --base-url=$BaseUrl --timeout=$Timeout --samples=$Samples --slow-ms=$SlowMs
    $auditExitCode = $LASTEXITCODE
    $ErrorActionPreference = 'Stop'
    if ($auditExitCode -ne 0) {
        throw "URL audit failed with exit code $auditExitCode."
    }

    Remove-Item -Force $inventoryErrorFile, $auditErrorFile -ErrorAction SilentlyContinue

    Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
        Sort-Object LastWriteTimeUtc -Descending |
        Select-Object -First 1 -ExpandProperty FullName
}
finally {
    Remove-Item -Force $inventoryErrorFile, $auditErrorFile -ErrorAction SilentlyContinue
    Pop-Location
    $env:APP_ENV = $previousAppEnv
    $env:APP_DEBUG = $previousAppDebug
    $env:XDEBUG_MODE = $previousXdebugMode
}
