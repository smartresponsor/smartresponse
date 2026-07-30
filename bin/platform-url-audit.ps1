[CmdletBinding()]
param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [int]$Timeout = 15,
    [switch]$CacheClear
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
    if ($CacheClear) {
        php bin/console cache:clear --env=dev --no-warmup -vvv
        exit $LASTEXITCODE
    }

    & (Join-Path $root 'tools/platform-url-audit.ps1') -BaseUrl $BaseUrl -Timeout $Timeout
}
finally {
    Pop-Location
}
