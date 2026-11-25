$ErrorActionPreference='Stop'
$names=@('SMARTRESPONSOR_SSH_HOST','SMARTRESPONSOR_SSH_USER','SMARTRESPONSOR_SSH_KEY','SMARTRESPONSOR_REMOTE_ROOT','SMARTRESPONSOR_SMOKE_URL','SMARTRESPONSOR_SSH_PORT','SMARTRESPONSOR_DEPLOY_BRANCH')
foreach($name in $names){
$process=[Environment]::GetEnvironmentVariable($name,'Process')
$user=[Environment]::GetEnvironmentVariable($name,'User')
$machine=[Environment]::GetEnvironmentVariable($name,'Machine')
$p=if([string]::IsNullOrWhiteSpace($process)){'MISSING'}else{'SET'}
$u=if([string]::IsNullOrWhiteSpace($user)){'MISSING'}else{'SET'}
$m=if([string]::IsNullOrWhiteSpace($machine)){'MISSING'}else{'SET'}
Write-Output ($name+' PROCESS='+$p+' USER='+$u+' MACHINE='+$m)
}
$ssh=Join-Path $HOME '.ssh/config'
Write-Output ('SSH_CONFIG_PRESENT='+(Test-Path -LiteralPath $ssh))
