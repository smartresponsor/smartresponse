[CmdletBinding()]
param()

foreach ($port in 8088..8092) {
    $listeners = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
    foreach ($listener in $listeners) {
        $process = Get-CimInstance Win32_Process -Filter "ProcessId = $($listener.OwningProcess)" -ErrorAction SilentlyContinue
        [pscustomobject]@{
            Port = $port
            Pid = $listener.OwningProcess
            Name = $process.Name
            CommandLine = $process.CommandLine
        } | Format-List
    }
}
