$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
  Write-Output "PROCESS_DATABASE_URL_PRESENT $(-not [string]::IsNullOrWhiteSpace($env:DATABASE_URL))"
  foreach ($name in @('.env','.env.local','.env.dev','.env.dev.local','.env.prod','.env.prod.local')) {
    $path = Join-Path $root $name
    if (-not (Test-Path -LiteralPath $path)) { continue }
    $keys = @(Get-Content -LiteralPath $path | Where-Object { $_ -match '^\s*DATABASE_URL\s*=' })
    Write-Output "DOTENV_FILE $name DATABASE_URL_DECLARED $($keys.Count -gt 0)"
  }
  & php bin/console about --env=dev --no-debug --no-interaction
  Write-Output "ABOUT_EXIT $LASTEXITCODE"
} finally { Pop-Location }
