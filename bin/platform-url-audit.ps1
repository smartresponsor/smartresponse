[CmdletBinding()]
param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [int]$Timeout = 15,
    [int]$ProfileSamples = 1,
    [int]$SlowMs = 250,
    [switch]$SkipRuntimePreflight,
    [switch]$CacheClear,
    [switch]$AuditStatus,
    [switch]$AuditSummary,
    [switch]$AuditFailures,
    [switch]$AuditActionable,
    [switch]$AuditGenerated404,
    [switch]$AuditProfile,
    [switch]$AuditCold,
    [switch]$AuditFirstTouch,
    [switch]$AuditAccessProfile,
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
    [string]$ProbePath = '',
    [string]$WarmupPath = ''
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

    if ($AuditProfile) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $ranking = $report.performance.routes |
            Where-Object { $_.classification -in @('sustained_slow', 'unstable', 'payload_heavy') } |
            Sort-Object warmAvgMs -Descending |
            Select-Object -First 50 route, path, status, contentType, responseBytes, coldMs, warmAvgMs, p50Ms, p95Ms, maxMs, spreadMs, classification, investigate
        Write-Output (@{ report = $reportFile.FullName; summary = $report.performance.summary; ranking = $ranking } | ConvertTo-Json -Depth 6)
        exit 0
    }

    if ($AuditAccessProfile) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $ranking = $report.performance.routes |
            Where-Object { $_.path -like '/access*' -or $_.path -like '/api/access*' -or $_.path -like '/2fa*' } |
            Sort-Object p50Ms -Descending |
            Select-Object route, path, status, contentType, responseBytes, coldMs, warmAvgMs, p50Ms, p95Ms, maxMs, classification, investigate
        Write-Output (@{ report = $reportFile.FullName; ranking = $ranking } | ConvertTo-Json -Depth 6)
        exit 0
    }

    if ($AuditCold -or $AuditFirstTouch) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $ranking = $report.performance.routes |
            Where-Object { $_.classification -eq 'first_touch' } |
            Sort-Object coldMs -Descending |
            Select-Object route, path, status, contentType, responseBytes, coldMs, warmAvgMs, p50Ms, p95Ms, classification, investigate
        Write-Output (@{ report = $reportFile.FullName; summary = $report.performance.summary; ranking = $ranking } | ConvertTo-Json -Depth 6)
        exit 0
    }

    if ($AuditGenerated404) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $findings = $report.failures |
            Where-Object { $_.type -eq 'declared_component_route_404' } |
            Select-Object route, path, status, occurrences, evidence
        Write-Output ($findings | ConvertTo-Json -Depth 6)
        exit 0
    }

    if ($AuditActionable) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $findings = $report.failures |
            Where-Object { $_.type -ne 'declared_component_route_404' } |
            Select-Object type, route, path, status, occurrences, evidence
        Write-Output ($findings | ConvertTo-Json -Depth 6)
        exit 0
    }

    if ($PublishLatest) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        php bin/console app:url-audit:publish-github $reportFile.FullName --repository=smartresponsor/smartresponse --no-debug
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
        $arguments = @('bin/console', 'app:url-audit:run', "--path=$ProbePath", "--samples=$ProfileSamples", "--slow-ms=$SlowMs", '--env=prod', '--no-debug')
        if ('' -ne $WarmupPath) {
            $arguments += "--warmup-path=$WarmupPath"
        }
        & php @arguments
        exit $LASTEXITCODE
    }

    $auditArguments = @{
        BaseUrl = $BaseUrl
        Timeout = $Timeout
        Samples = $ProfileSamples
        SlowMs = $SlowMs
    }
    if ($SkipRuntimePreflight) {
        $auditArguments.SkipRuntimePreflight = $true
    }
    & (Join-Path $root 'tools/platform-url-audit.ps1') @auditArguments
}
finally {
    Pop-Location
}
