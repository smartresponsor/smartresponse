$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
  & php bin/console router:match / --method=GET --env=dev --no-debug
  Write-Output "ROOT_MATCH_EXIT $LASTEXITCODE"
  & php bin/console debug:router --env=dev --no-debug --show-controllers
  Write-Output "ROUTES_EXIT $LASTEXITCODE"
} finally { Pop-Location }
