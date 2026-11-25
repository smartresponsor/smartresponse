$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$sourceRoot = Join-Path $root 'Cataloging'
$worktree = Join-Path $root '.worktrees/ubuntu-cataloging-interface-20261121'
$manifest = Get-Content (Join-Path $sourceRoot 'composer.json') -Raw | ConvertFrom-Json
$files = @($manifest.autoload.files)
foreach ($relative in $files) {
  $src = Join-Path $sourceRoot $relative
  $dst = Join-Path $worktree $relative
  if (!(Test-Path -LiteralPath $src)) { throw ('Missing source: ' + $relative) }
  if (Test-Path -LiteralPath $dst) { continue }
  New-Item -ItemType Directory -Path (Split-Path $dst) -Force | Out-Null
  Copy-Item -LiteralPath $src -Destination $dst
  & git -C $worktree add -- $relative
  if ($LASTEXITCODE -ne 0) { throw ('Git add failed: ' + $relative) }
}
& git -C $worktree diff --cached --check
if ($LASTEXITCODE -ne 0) { throw 'Diff check failed' }
& git -C $worktree diff --cached --name-only
& git -C $worktree commit -m 'fix(cataloging): restore Composer autoload files set'
if ($LASTEXITCODE -ne 0) { throw 'Commit failed' }
& git -C $worktree push origin fix/ubuntu-cataloging-interface-20261121
if ($LASTEXITCODE -ne 0) { throw 'Push failed' }
Write-Output ('PUBLISHED ' + (& git -C $worktree rev-parse HEAD))
