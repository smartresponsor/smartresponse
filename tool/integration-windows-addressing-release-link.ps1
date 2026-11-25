$ErrorActionPreference='Stop'
$root='D:\PhpstormProjects\www'
$app=Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
$old=Join-Path $root 'Addressing'
$target=Join-Path $root '.worktrees\addressing-release-acceptance-20261121'
if ((& git -C $target rev-parse HEAD) -ne '330e059b28c5f08c4015717e8173cda5dac3cf78') { throw 'SHA mismatch' }
$links=@((Join-Path $root '.worktrees\Addressing'),(Join-Path $app 'vendor\addressing\address'))
foreach ($link in $links) {
  $item=Get-Item -LiteralPath $link -Force
  if (-not ($item.Attributes -band [IO.FileAttributes]::ReparsePoint)) { throw ('Not a link: '+$link) }
  if (($item.Target -join '').TrimEnd('\') -ine $old.TrimEnd('\')) { throw ('Unexpected target: '+$link+' -> '+($item.Target -join '')) }
}
foreach ($link in $links) {
  (Get-Item -LiteralPath $link -Force).Delete()
  New-Item -ItemType Junction -Path $link -Target $target -ErrorAction Stop | Out-Null
  Write-Output ('LINK='+$link+' -> '+$target)
}
