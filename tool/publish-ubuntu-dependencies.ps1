$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
foreach ($name in @('Agentizing','Resolving','Anchoring','Subscripting')) {
  $slug = $name.ToLowerInvariant()
  $path = Join-Path $root $name
  $full = 'smartresponsor/' + $slug
  & gh repo view $full --json name 2>$null | Out-Null
  if ($LASTEXITCODE -ne 0) {
    & gh repo create $full --private --description ('SmartResponsor ' + $name + ' component')
    if ($LASTEXITCODE -ne 0) { throw ('Repository creation failed: ' + $full) }
  }
  $remote = 'git@github.com:' + $full + '.git'
  $existing = & git -C $path remote get-url origin 2>$null
  if ($LASTEXITCODE -ne 0) { & git -C $path remote add origin $remote }
  elseif ($existing -ne $remote) { throw ('Unexpected origin for ' + $name) }
  if ($LASTEXITCODE -ne 0) { throw ('Remote setup failed: ' + $name) }
  $branch = (& git -C $path branch --show-current).Trim()
  & git -C $path push -u origin $branch
  if ($LASTEXITCODE -ne 0) { throw ('Push failed: ' + $name) }
  Write-Output ('PUBLISHED ' + $full + ' ' + $branch + ' ' + (& git -C $path rev-parse HEAD))
}
