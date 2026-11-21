$ErrorActionPreference = 'Stop'
Get-CimInstance Win32_Process |
    Where-Object { $_.Name -match '^php(\.exe)?$' } |
    Select-Object ProcessId, ParentProcessId, Name, CommandLine |
    Sort-Object ProcessId |
    Format-Table -AutoSize -Wrap
