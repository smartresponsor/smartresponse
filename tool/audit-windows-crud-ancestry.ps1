$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$cr=Join-Path $root 'Cruding'
$ve=Join-Path $root 'Vendoring'
$refs=@('master','checkpoint/cruding-old-local-master-20260929','security/gitleaks-semgrep','origin/engine-20260911150014-cruding-34e436')
foreach ($ref in $refs) {
Write-Output ('=== CRUDING ' + $ref + ' ===')
$sha=(& git -C $cr rev-parse $ref).Trim()
Write-Output ('SHA=' + $sha)
$f='src/Service/Crud/AbstractCrudService.php'
$blob=(& git -C $cr rev-parse --verify ($ref+':'+$f) 2>$null)
Write-Output ('BASE_BLOB=' + ($blob -join ''))
if ($blob) { & git -C $cr show ($ref+':'+$f) | Select-Object -First 19 }
& git -C $cr rev-list --left-right --count ('master...'+$ref)
}
Write-Output '=== VENDORING RELEASE VS MASTER ==='
& git -C $ve rev-list --left-right --count 'master...release/vendoring-local-head-20260904'
& git -C $ve rev-list --left-right --count 'origin/master...release/vendoring-local-head-20260904'
