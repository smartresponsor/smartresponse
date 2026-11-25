$ErrorActionPreference='Stop'
$root='D:\PhpstormProjects\www'
$app=Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
$old=Join-Path $root 'Delivering'
$target=Join-Path $root '.worktrees\delivering-release-acceptance-20261121'
$expected='30088d7792f4a7c552db3ee34416778969546ad7'
if ((& git -C $target rev-parse HEAD) -ne $expected) { throw 'SHA mismatch' }
$links=@((Join-Path $root '.worktrees\Delivering'),(Join-Path $app 'vendor\delivering\delivery'))
foreach($link in $links) {
  $item=Get-Item -LiteralPath $link -Force
  if (-not ($item.Attributes -band [IO.FileAttributes]::ReparsePoint)) { throw ('Not a link: '+$link) }
  if (($item.Target -join '').TrimEnd('\') -ine $old.TrimEnd('\')) { throw ('Unexpected target: '+$link) }
}
foreach($link in $links) {
  (Get-Item -LiteralPath $link -Force).Delete()
  New-Item -ItemType Junction -Path $link -Target $target -ErrorAction Stop | Out-Null
  Write-Output ('PINNED='+$link+' -> '+$expected)
}
