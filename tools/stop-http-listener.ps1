[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [int] $Port
)

$listeners = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
foreach ($listener in $listeners) {
    $process = Get-CimInstance Win32_Process -Filter "ProcessId = $($listener.OwningProcess)" -ErrorAction SilentlyContinue
    if ($null -eq $process) { continue }
    $commandLine = [string] $process.CommandLine
    $owned = $process.Name -eq 'php.exe' -and $commandLine -match '\s-S\s' -and $commandLine -match 'public/index\.php'
    if (-not $owned) {
        throw "Refusing to stop non-owned listener on port $Port (PID $($listener.OwningProcess))."
    }
    Stop-Process -Id $listener.OwningProcess -Force -ErrorAction Stop
    Write-Host "Stopped PHP smoke server PID $($listener.OwningProcess) on port $Port."
}
