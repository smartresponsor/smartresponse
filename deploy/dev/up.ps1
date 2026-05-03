$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Push-Location $root

try {
    powershell -ExecutionPolicy Bypass -File .\deploy\docker\up.ps1
    $listener = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
    if ($listener) {
        $listener | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue }
    }

    Start-Process php -WorkingDirectory $root -ArgumentList @(
        '-S',
        '127.0.0.1:8000',
        '-t',
        'public',
        'public/router.php'
    )
}
finally {
    Pop-Location
}
