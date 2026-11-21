$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$statePath = Join-Path $root '.console-mcp/local-php-server-8000.json'
if (-not (Test-Path -LiteralPath $statePath)) { throw 'Managed server state missing' }
$state = Get-Content -LiteralPath $statePath -Raw | ConvertFrom-Json
$proc = Get-CimInstance Win32_Process -Filter "ProcessId=$($state.pid)" -ErrorAction SilentlyContinue
if ($null -eq $proc) { Write-Output 'SERVER_PROCESS_MISSING' } else {
  Write-Output "SERVER_PID $($proc.ProcessId)"
  Write-Output "SERVER_NAME $($proc.Name)"
  Write-Output "SERVER_PARENT_PID $($proc.ParentProcessId)"
  Write-Output "SERVER_STARTED $($proc.CreationDate)"
}
foreach ($kind in @('stdoutLog','stderrLog')) {
  $path = $state.$kind
  if (-not $path -or -not (Test-Path -LiteralPath $path)) { Write-Output "$kind MISSING"; continue }
  $file = Get-Item -LiteralPath $path
  Write-Output "$kind bytes=$($file.Length) modified=$($file.LastWriteTimeUtc.ToString('o'))"
  Get-Content -LiteralPath $path -Tail 35 | ForEach-Object {
    $line = $_ -replace '(?i)(password|token|secret|DATABASE_URL|Authorization)(\s*[=:]\s*)\S+', '$1$2[REDACTED]'
    Write-Output "LOG $line"
  }
}
Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue | Select-Object LocalAddress,LocalPort,State,OwningProcess | Format-Table -HideTableHeaders | Out-String | Write-Output
