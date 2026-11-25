$ErrorActionPreference='Stop'
$root='D:\PhpstormProjects\www'
$app=Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
$manifest=Get-Content (Join-Path $app 'var\component-release-sha-manifest.json') -Raw | ConvertFrom-Json
$items=@($manifest.components|Where-Object {$_.dirty_files -gt 0 -and $_.path -notlike '*/*'})
$operations=@()
foreach($item in $items) {
  $name=$item.path
  $target=Join-Path (Join-Path $root '.worktrees') ($name.ToLowerInvariant()+'-sha-acceptance-20261121')
  $head=(& git -C $target rev-parse HEAD 2>$null)
  if ($LASTEXITCODE -ne 0 -or $head.Trim() -ne $item.git_sha) {throw ('SHA mismatch: '+$name)}
  if (@(& git -C $target status --porcelain).Count -gt 0) {throw ('Worktree not clean: '+$name)}
  $source=Join-Path $root $name
  $links=@((Join-Path (Join-Path $root '.worktrees') $name),(Join-Path (Join-Path $app 'vendor') ($item.package.Replace('/','\'))))
  foreach($link in $links) {
    $obj=Get-Item -LiteralPath $link -Force -ErrorAction Stop
    if (-not ($obj.Attributes -band [IO.FileAttributes]::ReparsePoint)) {throw ('Not a junction: '+$link)}
    $actual=($obj.Target -join '').TrimEnd('\')
    if ($actual -ine $source.TrimEnd('\')) {throw ('Unexpected existing target: '+$link+' -> '+$actual)}
    $operations += [pscustomobject]@{Link=$link;Target=$target;Package=$item.package}
  }
}
Write-Output ('PREFLIGHT_PASS='+$operations.Count+' links')
foreach($op in $operations) {
  (Get-Item -LiteralPath $op.Link -Force).Delete()
  New-Item -ItemType Junction -Path $op.Link -Target $op.Target -ErrorAction Stop | Out-Null
  Write-Output ('PINNED='+$op.Package)
}
Write-Output ('COMPONENTS_PINNED='+$items.Count)
