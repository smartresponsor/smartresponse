# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp

[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$repositoryRoot = Split-Path -Parent $PSScriptRoot
Push-Location $repositoryRoot
try {
    & php bin/console debug:router api_access --show-controllers --env=dev
    if ($LASTEXITCODE -ne 0) { throw "Accessing route inspection failed." }
}
finally {
    Pop-Location
}
