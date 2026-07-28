# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp

[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$repositoryRoot = Split-Path -Parent $PSScriptRoot
Push-Location $repositoryRoot
try { & php bin/console debug:container 'App\Controller\Access\ApiAccessController' --show-hidden } finally { Pop-Location }
exit $LASTEXITCODE
