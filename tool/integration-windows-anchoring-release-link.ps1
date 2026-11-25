$ErrorActionPreference='Stop'
$root='D:\PhpstormProjects\www'
$app=Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
$old=Join-Path $root 'Anchoring\sdk\php'
$repo=Join-Path $root '.worktrees\anchoring-sha-acceptance-20261121'
$target=Join-Path $repo 'sdk\php'
if ((& git -C $repo rev-parse HEAD).Trim() -ne '2fd8aa71d299ce3b9eac0479da4be693e17579cb') {throw 'SHA mismatch'}
if (@(& git -C $repo status --porcelain).Count -gt 0) {throw 'Dirty clean worktree'}
$links=@((Join-Path $root '.worktrees\Anchoring\sdk\php'),(Join-Path $app 'vendor\anchoring\anchor'))
foreach($link in $links) {
  $item=Get-Item -LiteralPath $link -Force
  if(-not ($item.Attributes -band [IO.FileAttributes]::ReparsePoint)){throw 'Not junction'}
  if(($item.Target -join '').TrimEnd('\') -ine $old.TrimEnd('\')){throw 'Unexpected target'}
}
foreach($link in $links){
  (Get-Item -LiteralPath $link -Force).Delete()
  New-Item -ItemType Junction -Path $link -Target $target -ErrorAction Stop | Out-Null
  Write-Output ('PINNED='+$link)
}
