$ErrorActionPreference='Continue'
$app='D:\PhpstormProjects\www\.worktrees\host-app-integration-acceptance-20261121'
Push-Location $app
try {
  $env:APP_ENV='prod'
  $env:APP_DEBUG='0'
  foreach($command in @('about','lint:container')) {
    Write-Output ('COMMAND='+$command)
    $output=& php bin/console $command --no-interaction --no-ansi 2>&1
    $exit=$LASTEXITCODE
    Write-Output ('EXIT='+$exit)
    $filtered=@($output | Where-Object { $_ -notmatch 'Deprecated:|doctrine.orm.entity_managers|"channel":"deprecation"' })
    $filtered | Select-Object -Last 18 | ForEach-Object { Write-Output $_ }
  }
} finally { Pop-Location }
