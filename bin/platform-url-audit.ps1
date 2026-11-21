[CmdletBinding()]
param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [int]$Timeout = 15,
    [int]$ProfileSamples = 1,
    [int]$SlowMs = 250,
    [ValidateSet('dev','prod')][string]$AppEnv = 'prod',
    [switch]$SkipRuntimePreflight,
    [switch]$CacheClear,
    [switch]$AuditStatus,
    [switch]$AuditTail,
    [switch]$AuditSummary,
    [switch]$AuditFailures,
    [switch]$AuditFailureOwners,
    [switch]$AuditOwnerEnvelopes,
    [switch]$AuditActionable,
    [switch]$AuditGenerated404,
    [switch]$AuditProfile,
    [switch]$AuditCold,
    [switch]$AuditFirstTouch,
    [switch]$AuditAccessProfile,
    [switch]$PublishLatest,
    [switch]$GitHubAuditCount,
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

function Get-AuditOwner([string]$Route) {
    if ($Route -like 'generated_cruding_*' -or $Route -like 'cruding_*') { return 'Cruding' }
    if ($Route -like 'app_messaging_*' -or $Route -eq 'attachment_download') { return 'Messaging' }
    if ($Route -like 'access.*' -or $Route -like 'access_*') { return 'Accessing' }
    if ($Route -like 'api.cart.*' -or $Route -like 'carting_*') { return 'Carting' }
    if ($Route -like 'api_catalog_*' -or $Route -like 'api_category_*' -or $Route -like 'legacy_category_*' -or $Route -like 'cataloging_*') { return 'Cataloging' }
    if ($Route -like 'localizing_*') { return 'Localizing' }
    if ($Route -like 'navigation_*' -or $Route -like 'navigating_*') { return 'Navigating' }
    if ($Route -like 'vendoring_*') { return 'Vendoring' }
    if ($Route -like 'currencing_*') { return 'Currencing' }
    if ($Route -like 'admin_*') { return 'App/Admin' }
    if ($Route -like 'application_runtime_*' -or $Route -like 'app_host_*') { return 'App' }
    return 'Other'
}

function Get-AuditDisposition([string]$Type) {
    if ($Type -eq 'http_500' -or $Type -like 'body_marker:*') { return 'repair' }
    if ($Type -eq 'http_503' -or $Type -like 'redirect_*') { return 'review' }
    return 'observe'
}

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

    if ($AuditTail) {
        $checkpoint = Get-ChildItem -Path 'var/url-audit' -Filter probes.jsonl -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $checkpoint) {
            Write-Output '[]'
            exit 0
        }
        Get-Content -Path $checkpoint.FullName -Tail 12
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

    if ($AuditFailureOwners) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $groups = $report.failures |
            ForEach-Object {
                $route = [string]$_.route
                $owner = Get-AuditOwner $route
                $disposition = Get-AuditDisposition ([string]$_.type)
                [pscustomobject]@{ owner = $owner; disposition = $disposition; type = [string]$_.type; route = $route; path = [string]$_.path }
            } |
            Group-Object owner, disposition, type |
            Sort-Object Count -Descending |
            ForEach-Object {
                [pscustomobject]@{
                    count = $_.Count
                    owner = $_.Group[0].owner
                    disposition = $_.Group[0].disposition
                    type = $_.Group[0].type
                    sampleRoutes = @($_.Group | Select-Object -First 8 -ExpandProperty route)
                    samplePaths = @($_.Group | Select-Object -First 8 -ExpandProperty path)
                }
            }
        Write-Output ($groups | ConvertTo-Json -Depth 6)
        exit 0
    }

    if ($AuditOwnerEnvelopes) {
        $reportFile = Get-ChildItem -Path 'var/url-audit' -Filter report.json -Recurse |
            Sort-Object LastWriteTimeUtc -Descending |
            Select-Object -First 1
        if ($null -eq $reportFile) {
            throw 'No URL audit report was found.'
        }
        $report = Get-Content -Raw -Path $reportFile.FullName | ConvertFrom-Json
        $envelopeRoot = Join-Path $reportFile.Directory.FullName 'owner-envelopes'
        New-Item -ItemType Directory -Force -Path $envelopeRoot | Out-Null
        $items = @($report.failures |
            Where-Object { $_.type -ne 'declared_component_route_404' } |
            ForEach-Object {
                [pscustomobject]@{
                    owner = Get-AuditOwner ([string]$_.route)
                    disposition = Get-AuditDisposition ([string]$_.type)
                    type = [string]$_.type
                    route = [string]$_.route
                    path = [string]$_.path
                    status = [int]$_.status
                    occurrences = [int]$_.occurrences
                    evidence = [string]$_.evidence
                    affectedPaths = @($_.affectedPaths)
                }
            })
        $written = @()
        foreach ($group in @($items | Group-Object owner)) {
            $owner = [string]$group.Name
            $repair = @($group.Group | Where-Object { $_.disposition -eq 'repair' })
            $review = @($group.Group | Where-Object { $_.disposition -eq 'review' })
            $observe = @($group.Group | Where-Object { $_.disposition -eq 'observe' })
            $safeOwner = $owner -replace '[^A-Za-z0-9._-]', '_'
            $path = Join-Path $envelopeRoot ($safeOwner + '.json')
            [ordered]@{
                schema = 'app.url-audit.owner-envelope.v1'
                generatedAt = (Get-Date).ToUniversalTime().ToString('o')
                owner = $owner
                sourceReport = $reportFile.FullName
                repairRequired = $repair.Count -gt 0
                repairCount = $repair.Count
                reviewCount = $review.Count
                observeCount = $observe.Count
                repairFindings = $repair
                reviewFindings = $review
                observeFindings = $observe
            } | ConvertTo-Json -Depth 10 | Set-Content -LiteralPath $path -Encoding UTF8
            $written += [pscustomobject]@{
                owner=$owner
                path=$path
                repairRequired=($repair.Count -gt 0)
                repairCount=$repair.Count
                reviewCount=$review.Count
                observeCount=$observe.Count
            }
        }
        Write-Output ($written | Sort-Object owner | ConvertTo-Json -Depth 6)
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

    if ($GitHubAuditCount) {
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
        AppEnv = $AppEnv
    }
    if ($SkipRuntimePreflight) {
        $auditArguments.SkipRuntimePreflight = $true
    }
    & (Join-Path $root 'tools/platform-url-audit.ps1') @auditArguments
}
finally {
    Pop-Location
}
