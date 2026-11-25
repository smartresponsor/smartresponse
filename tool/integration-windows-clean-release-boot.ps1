$ErrorActionPreference='Continue'
$app='D:\PhpstormProjects\www\.worktrees\host-app-release-clean-20261121'
Push-Location $app
try {
$env:APP_ENV='prod';$env:APP_DEBUG='0'
foreach($command in @('about','lint:container')) {
$output=@(& php bin/console $command --no-interaction --no-ansi 2>&1)
$exit=$LASTEXITCODE
Write-Output ('COMMAND='+$command+' EXIT='+$exit)
$output|Where-Object {$_ -notmatch 'Deprecated:|doctrine.orm.entity_managers|"channel":"deprecation"'}|Select-Object -Last 12|ForEach-Object {Write-Output $_}
if($exit -ne 0){throw ('Symfony failed: '+$command)}
}
}finally{Pop-Location}
