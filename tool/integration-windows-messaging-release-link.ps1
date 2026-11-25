$ErrorActionPreference='Stop'
$root='D:\PhpstormProjects\www'
$link=Join-Path $root '.worktrees\Messaging'
$source=Join-Path $root 'Messaging'
$target=Join-Path $root '.worktrees\messaging-release-acceptance-20261121'
$item=Get-Item -LiteralPath $link -Force
if (-not ($item.Attributes -band [IO.FileAttributes]::ReparsePoint)) { throw 'Messaging link is not a reparse point' }
$actual=($item.Target -join '')
if ($actual.TrimEnd('\') -ine $source.TrimEnd('\')) { throw ('Unexpected junction target: '+$actual) }
if ((& git -C $target rev-parse HEAD) -ne 'e03460b8b8361ebffb4a44e3947312dd37c7cc94') { throw 'Release SHA mismatch' }
$item.Delete()
New-Item -ItemType Junction -Path $link -Target $target -ErrorAction Stop | Out-Null
Write-Output ('MESSAGING_LINK='+$link)
Write-Output ('TARGET='+$target)
Write-Output ('HEAD='+(& git -C $target rev-parse HEAD))
