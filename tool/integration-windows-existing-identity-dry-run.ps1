$ErrorActionPreference='Stop'
$names=@('SMARTRESPONSOR_SSH_HOST','SMARTRESPONSOR_SSH_USER','SMARTRESPONSOR_SSH_KEY','SMARTRESPONSOR_REMOTE_ROOT','SMARTRESPONSOR_SMOKE_URL','SMARTRESPONSOR_SSH_PORT','SMARTRESPONSOR_DEPLOY_BRANCH')
foreach($name in $names){$v=[Environment]::GetEnvironmentVariable($name,'User');if([string]::IsNullOrWhiteSpace($v)){throw ('Missing '+$name)};[Environment]::SetEnvironmentVariable($name,$v,'Process')}
if($env:SMARTRESPONSOR_DEPLOY_BRANCH -ne 'master'){throw 'Unexpected deploy branch; refusing'}
$app='D:\PhpstormProjects\www\App'
Push-Location $app
try { & pwsh -NoProfile -File (Join-Path $app 'deploy.ps1') -DryRun; if($LASTEXITCODE -ne 0){throw ('Dry-run failed '+$LASTEXITCODE)} } finally { Pop-Location }
