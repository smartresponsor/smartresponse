$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$base='39030f2166a835462463504b0438627272a993a0'
$branch='origin/codex/gather-criteria-for-developer-experience-evaluation'
Write-Output '=== BOM TEST AND IMPLEMENTATION IN BRANCH ==='
& git -C $repo show "$($branch):tests/Unit/Observability/MonitoringSnapshotBuilderTest.php" | Select-String -Pattern 'BOM|bom|UTF|utf' -Context 2,4 | Select-Object -First 60
Write-Output '=== RELEASE BOM COVERAGE ==='
& git -C $repo grep -n -i -e 'bom' -e 'ef\\xbb\\xbf' $base -- src/Builder src/Service tests/Unit/Observability | Select-Object -First 55
Write-Output '=== RELEASE MONITORING PATHS ==='
& git -C $repo ls-tree -r --name-only $base -- src tests | Select-String 'MonitoringSnapshotBuilder|RuntimeLogger' | Select-Object -First 35
Write-Output '=== SECURITY WORKFLOW ==='
& git -C $repo show 'origin/security/gitleaks-semgrep:.github/workflows/security.yml'
