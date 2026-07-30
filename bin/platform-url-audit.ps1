[CmdletBinding()]
param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [int]$Timeout = 15,
    [switch]$CacheClear,
    [switch]$AuditStatus,
    [switch]$AuditSummary,
    [switch]$PublishLatest
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
    if ($CacheClear) {
        php bin/console cache:clear --env=dev --no-warmup -vvv
        exit $LASTEXITCODE
    }

    if ($AuditStatus) {
        $checkpoint = Get-ChildItem -Path 'var/url-audit' -Filter probes.jsonl -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $checkpoint) {
            Write-Output '{"checkpoint":null,"probes":0}'
            exit 0
        }
        $count = (Get-Content -Path $checkpoint.FullName | Measure-Object -Line).Lines
        Write-Output (@{ checkpoint = $checkpoint.FullName; probes = $count } | ConvertTo-Json -Compress)
        exit 0
    }

    if ($AuditSummary) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            Write-Output '{"report":null}'
            exit 0
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $types = $report.failures | Group-Object type | Sort-Object Count -Descending | Select-Object Count, Name
        $statuses = $report.probes | Group-Object status | Sort-Object Count -Descending | Select-Object Count, Name
        Write-Output (@{ report = $reportFile.FullName; summary = $report.summary; types = $types; statuses = $statuses } | ConvertTo-Json -Depth 6)
        exit 0
    }

    if ($PublishLatest) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        php bin/console app:url-audit:publish-github $reportFile.FullName --repository=smartresponsor/smartresponse --date=2026-07-30 --no-debug
        exit $LASTEXITCODE
    }

    & (Join-Path $root 'tools/platform-url-audit.ps1') -BaseUrl $BaseUrl -Timeout $Timeout
}
finally {
    Pop-Location
}
