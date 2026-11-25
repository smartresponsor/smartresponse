$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo = Join-Path $root '.worktrees/ubuntu-cataloging-interface-20261121'
$source = Join-Path $root 'Cataloging'
foreach ($file in @('src/CatalogingBundle.php','src/DependencyInjection/CatalogingExtension.php')) {
  $dest = Join-Path $repo $file
  if (Test-Path -LiteralPath $dest) { throw ('Target exists: ' + $file) }
  New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
  $content = & git -C $source show ('7b237f9033a722be90e1c94981a6290008cb4ad8:' + $file)
  if ($LASTEXITCODE -ne 0) { throw ('Git show failed: ' + $file) }
  [System.IO.File]::WriteAllText($dest, (($content -join "`n") + "`n"), [System.Text.UTF8Encoding]::new($false))
  & git -C $repo add -- $file
  if ($LASTEXITCODE -ne 0) { throw 'Git add failed' }
}
& git -C $repo diff --cached --check
if ($LASTEXITCODE -ne 0) { throw 'Diff check failed' }
& git -C $repo commit -m 'fix(cataloging): restore Symfony bundle and extension'
if ($LASTEXITCODE -ne 0) { throw 'Commit failed' }
& git -C $repo push origin fix/ubuntu-cataloging-interface-20261121
if ($LASTEXITCODE -ne 0) { throw 'Push failed' }
Write-Output ('PUBLISHED ' + (& git -C $repo rev-parse HEAD))
