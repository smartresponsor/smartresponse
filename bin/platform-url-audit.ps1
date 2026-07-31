[CmdletBinding()]
param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [int]$Timeout = 15,
    [switch]$CacheClear,
    [switch]$AuditStatus,
    [switch]$AuditSummary,
    [switch]$AuditFailures,
    [switch]$PublishLatest,
    [switch]$GithubAuditCount,
    [switch]$DoctrineSystemStatus,
    [switch]$DoctrineConfigStatus,
    [switch]$DoctrineFailurePaths,
    [switch]$DoctrineRegistryStatus,
    [switch]$CurrentDoctrineFailureCount,
    [switch]$TargetDoctrineProbe,
    [switch]$SystemSchemaUpdate,
    [switch]$MigrateDatabase,
    [switch]$MigrateRecordIndex,
    [switch]$CatalogRecordIndexSchema,
    [string]$ProbePath = ''
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

    if ($AuditFailures) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $failures = $report.failures |
            Where-Object { $_.type -eq 'http_500' } |
            Sort-Object occurrences -Descending |
            Select-Object -First 25 |
            ForEach-Object {
                [pscustomobject]@{
                    route = $_.route
                    path = $_.path
                    occurrences = $_.occurrences
                    samplePaths = @($_.affectedPaths | Select-Object -First 5)
                    evidence = $_.evidence
                }
            }
        Write-Output ($failures | ConvertTo-Json -Depth 6)
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

    if ($GithubAuditCount) {
        $issues = gh issue list --repo smartresponsor/smartresponse --state all --search 'in:body URL-AUDIT-FINGERPRINT:' --limit 1000 --json number
        if ($LASTEXITCODE -ne 0) {
            exit $LASTEXITCODE
        }
        $count = ($issues | ConvertFrom-Json | Measure-Object).Count
        Write-Output (@{ issues = $count } | ConvertTo-Json -Compress)
        exit 0
    }

    if ($DoctrineSystemStatus) {
        php bin/console doctrine:mapping:info --em=system --no-debug
        if ($LASTEXITCODE -ne 0) {
            exit $LASTEXITCODE
        }
        php bin/console doctrine:schema:validate --em=system --skip-sync --no-debug
        exit $LASTEXITCODE
    }

    if ($DoctrineConfigStatus) {
        php bin/console debug:config doctrine orm.entity_managers.system --no-debug
        exit $LASTEXITCODE
    }

    if ($DoctrineFailurePaths) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No previous URL audit report was found.'
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $matches = $report.failures | Where-Object { $_.evidence -like '*AdministrationConnectedComponentRecord*' }
        Write-Output ($matches | Select-Object route, path, affectedPaths, occurrences, evidence | ConvertTo-Json -Depth 6)
        exit 0
    }

    if ($DoctrineRegistryStatus) {
        php bin/console app:url-audit:inventory --doctrine-managers --no-debug
        exit $LASTEXITCODE
    }

    if ($CurrentDoctrineFailureCount) {
        $checkpoint = Get-ChildItem -Path 'var/url-audit' -Filter probes.jsonl -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $checkpoint) {
            Write-Output '{"checkpoint":null,"matches":0}'
            exit 0
        }
        $matches = Select-String -Path $checkpoint.FullName -Pattern 'There is no Doctrine Entity Manager defined for the .*App\\\\Administering\\\\Entity' -AllMatches
        Write-Output (@{ checkpoint = $checkpoint.FullName; matches = @($matches).Count } | ConvertTo-Json -Compress)
        exit 0
    }

    if ($TargetDoctrineProbe) {
        php bin/console app:url-audit:run --path='/admin/page/administration-connected-component-record' --no-debug
        exit $LASTEXITCODE
    }

    if ($SystemSchemaUpdate) {
        php bin/console doctrine:schema:update --em=system --force --no-debug
        exit $LASTEXITCODE
    }

    if ($MigrateDatabase) {
        php bin/console doctrine:migrations:migrate --no-interaction --no-debug
        exit $LASTEXITCODE
    }

    if ($CatalogRecordIndexSchema) {
        php bin/console app:url-audit:inventory --catalog-record-index-schema --no-debug
        exit $LASTEXITCODE
    }

    if ('' -ne $ProbePath) {
        php bin/console app:url-audit:run --path=$ProbePath --no-debug
        exit $LASTEXITCODE
    }

    & (Join-Path $root 'tools/platform-url-audit.ps1') -BaseUrl $BaseUrl -Timeout $Timeout
}
finally {
    Pop-Location
}
