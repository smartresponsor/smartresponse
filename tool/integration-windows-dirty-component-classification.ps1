$ErrorActionPreference='Stop'
$root='D:\PhpstormProjects\www'
$app=Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
$manifest=Get-Content (Join-Path $app 'var\component-release-sha-manifest.json') -Raw | ConvertFrom-Json
$dirty=@($manifest.components | Where-Object {$_.dirty_files -gt 0})
$rows=@()
foreach($item in $dirty) {
  $path=Join-Path (Join-Path $root '.worktrees') $item.path
  $status=@(& git -C $path status --porcelain)
  $tracked=@($status|Where-Object {$_ -notmatch '^\?\?'})
  $untracked=@($status|Where-Object {$_ -match '^\?\?'})
  $rows += [pscustomobject]@{Package=$item.package;SHA=$item.git_sha.Substring(0,8);Tracked=$tracked.Count;Untracked=$untracked.Count;Sample=($status|Select-Object -First 2)-join ' | '}
}
$rows | Sort-Object -Property Tracked -Descending | Format-Table -AutoSize | Out-String -Width 210 | Write-Output
Write-Output ('DIRTY_TOTAL='+$rows.Count)
Write-Output ('TRACKED_DIRTY='+@($rows|Where-Object {$_.Tracked -gt 0}).Count)
Write-Output ('UNTRACKED_ONLY='+@($rows|Where-Object {$_.Tracked -eq 0}).Count)
