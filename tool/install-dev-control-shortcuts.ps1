# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
[CmdletBinding()]
param(
    [string] $FolderName = 'Mobile Dev Control'
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$workspace = Split-Path -Parent $PSScriptRoot
$launchers = Join-Path $PSScriptRoot 'launchers'
$desktop = [Environment]::GetFolderPath('Desktop')
$targetFolder = Join-Path $desktop $FolderName
$shell = New-Object -ComObject WScript.Shell

$items = @(
    @{ Name = '01 Start App Host'; File = '01-start-host.cmd' },
    @{ Name = '02 PostgreSQL Smoke'; File = '02-postgres-smoke.cmd' },
    @{ Name = '03 PostgreSQL Docker'; File = '03-postgres-docker.cmd' },
    @{ Name = '04 Validate Schema'; File = '04-schema-validate.cmd' },
    @{ Name = '05 Clear Cache'; File = '05-clear-cache.cmd' },
    @{ Name = '06 Open Logs'; File = '06-open-logs.cmd' },
    @{ Name = '07 Open Mobile App'; File = '07-open-mobile.cmd' }
)

New-Item -ItemType Directory -Path $targetFolder -Force | Out-Null

foreach ($item in $items) {
    $target = Join-Path $launchers $item.File
    if (-not (Test-Path $target -PathType Leaf)) {
        throw "Launcher not found: $target"
    }

    $shortcutPath = Join-Path $targetFolder ($item.Name + '.lnk')
    $shortcut = $shell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = $target
    $shortcut.WorkingDirectory = $workspace
    $shortcut.IconLocation = "$env:SystemRoot\System32\shell32.dll,24"
    $shortcut.Save()
}

Write-Host "Developer control shortcuts installed: $targetFolder"
Start-Process explorer.exe $targetFolder
