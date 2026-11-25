$ErrorActionPreference='Continue'
$app='D:\PhpstormProjects\www\.worktrees\host-app-integration-acceptance-20261121'
Push-Location $app
try {
  Write-Output '=== PHP CONSOLE PROD ==='
  $env:APP_ENV='prod'
  $env:APP_DEBUG='0'
  & php bin/console list --raw --no-interaction 2>&1 | Select-Object -First 65
  Write-Output ('PROD_EXIT='+$LASTEXITCODE)
  Write-Output '=== PHP CONSOLE TEST ==='
  $env:APP_ENV='test'
  $env:APP_DEBUG='0'
  & php bin/console list --raw --no-interaction 2>&1 | Select-Object -First 65
  Write-Output ('TEST_EXIT='+$LASTEXITCODE)
} finally { Pop-Location }
