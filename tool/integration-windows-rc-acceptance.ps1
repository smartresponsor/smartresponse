$ErrorActionPreference='Stop'
$app='D:\PhpstormProjects\www\.worktrees\host-app-integration-acceptance-20261121'
Write-Output ('WORKTREE='+$app)
Write-Output ('HEAD='+(& git -C $app rev-parse HEAD))
foreach($exe in @('php','composer')){$cmd=Get-Command $exe -ErrorAction SilentlyContinue;Write-Output ($exe+'='+[string]$cmd.Source)}
Write-Output ('VENDOR_PRESENT='+(Test-Path (Join-Path $app 'vendor/autoload.php')))
Write-Output ('CANON_VENDOR_PRESENT='+(Test-Path 'D:\PhpStormProjects\www\App\vendor\autoload.php'))
Write-Output ('APP_TESTS='+(Test-Path (Join-Path $app 'tests/Unit/Runtime/AppEffectiveRuntimeServiceTest.php')))
Write-Output ('COMPOSER_PATHS');Get-Content (Join-Path $app 'composer.json') | Select-String '"url": "../' | Select-Object -First 5
