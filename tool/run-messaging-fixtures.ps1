[CmdletBinding()]
param()
$ErrorActionPreference = 'Continue'
$repo = Split-Path -Parent $PSScriptRoot
Set-Location $repo
Write-Host '=== cache clear/warmup ==='
php bin/console cache:clear --env=prod --no-warmup
Write-Host "cache_clear_exit=$LASTEXITCODE"
php bin/console cache:warmup --env=prod
Write-Host "warmup_exit=$LASTEXITCODE"
Write-Host '=== command list ==='
php bin/console list messaging --env=prod | Select-String -Pattern 'messaging:dev:fixtures:load','messaging:'
Write-Host "list_exit=$LASTEXITCODE"
Write-Host '=== load fixtures ==='
php bin/console messaging:dev:fixtures:load --env=prod -vv
Write-Host "fixtures_exit=$LASTEXITCODE"
Write-Host '=== probes ==='
$thread = '00000000-0000-4000-8000-000000020001'
$reply = @{ userId = '00000000-0000-4000-8000-000000000111'; text = 'Confirmed, I can come today between 3 and 5 PM.' } | ConvertTo-Json -Compress
foreach ($probe in @(
  @{ Method='GET'; Url='http://127.0.0.1:8000/api/threads'; Body=$null },
  @{ Method='GET'; Url="http://127.0.0.1:8000/api/threads/$thread/messages"; Body=$null },
  @{ Method='POST'; Url="http://127.0.0.1:8000/api/threads/$thread/reply"; Body=$reply },
  @{ Method='GET'; Url="http://127.0.0.1:8000/api/threads/$thread/messages"; Body=$null },
  @{ Method='GET'; Url='http://127.0.0.1:8080/message/thread'; Body=$null }
)) {
  try {
    if ($probe.Method -eq 'POST') {
      $response = Invoke-WebRequest -UseBasicParsing -Uri $probe.Url -Method POST -ContentType 'application/json' -Body $probe.Body -TimeoutSec 10
    } else {
      $response = Invoke-WebRequest -UseBasicParsing -Uri $probe.Url -TimeoutSec 10
    }
    $body = $response.Content
    if ($body.Length -gt 900) { $body = $body.Substring(0,900) + '...' }
    "{0} {1} => {2} {3}" -f $probe.Method,$probe.Url,$response.StatusCode,$body
  } catch {
    "{0} {1} => ERROR {2}" -f $probe.Method,$probe.Url,$_.Exception.Message
  }
}
Remove-Item -LiteralPath $MyInvocation.MyCommand.Path -Force -ErrorAction SilentlyContinue
exit 0

