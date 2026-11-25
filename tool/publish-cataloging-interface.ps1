$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$source = Join-Path $root 'Cataloging/src/EventInterface/CatalogCategoryWorkflowEntityTransitionedInterface.php'
$worktree = Join-Path $root '.worktrees/ubuntu-cataloging-interface-20261121'
$relative = 'src/EventInterface/CatalogCategoryWorkflowEntityTransitionedInterface.php'
$destination = Join-Path $worktree $relative
if (!(Test-Path -LiteralPath $source)) { throw 'Source file missing' }
if (Test-Path -LiteralPath $destination) { throw 'Destination already exists' }
New-Item -ItemType Directory -Path (Split-Path $destination) -Force | Out-Null
Copy-Item -LiteralPath $source -Destination $destination -ErrorAction Stop
& git -C $worktree add -- $relative
if ($LASTEXITCODE -ne 0) { throw 'Git add failed' }
& git -C $worktree diff --cached --check
if ($LASTEXITCODE -ne 0) { throw 'Diff check failed' }
& git -C $worktree diff --cached --name-only
& git -C $worktree commit -m 'fix(cataloging): restore category workflow transition interface'
if ($LASTEXITCODE -ne 0) { throw 'Commit failed' }
& git -C $worktree push -u origin fix/ubuntu-cataloging-interface-20261121
if ($LASTEXITCODE -ne 0) { throw 'Push failed' }
Write-Output ('PUBLISHED ' + (& git -C $worktree rev-parse HEAD))
