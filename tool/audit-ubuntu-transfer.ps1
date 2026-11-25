$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
Write-Output ('GH=' + [bool](Get-Command gh -ErrorAction SilentlyContinue))
if (Get-Command gh -ErrorAction SilentlyContinue) { & gh auth status 2>&1 | ForEach-Object { if ($_ -notmatch 'Token:|token:') { Write-Output $_ } } }
foreach ($name in @('Agentizing','Resolving','Anchoring','Subscripting')) {
  $dir = Join-Path $root $name
  Write-Output ('REPO ' + $name)
  & git -C $dir status --short
  & git -C $dir remote -v
  & git -C $dir log -1 --format='%H %s'
}
