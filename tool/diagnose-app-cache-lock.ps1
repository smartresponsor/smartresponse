# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp

[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$workspace = [System.IO.Path]::GetFullPath($PSScriptRoot + '\..')

$processes = Get-CimInstance Win32_Process |
    Where-Object {
        $_.Name -match '^php(?:-cgi)?\.exe$' -and
        $_.CommandLine -and
        $_.CommandLine.IndexOf($workspace, [System.StringComparison]::OrdinalIgnoreCase) -ge 0
    } |
    Select-Object ProcessId, Name, CommandLine

if (-not $processes) {
    Write-Output 'No PHP processes reference the App workspace.'
    exit 0
}
