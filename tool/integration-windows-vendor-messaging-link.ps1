$ErrorActionPreference='Stop'
$root='D:\PhpstormProjects\www'
$app=Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
$link=Join-Path $app 'vendor\messaging\message'
$old=Join-Path $root 'Messaging'
$target=Join-Path $root '.worktrees\messaging-release-acceptance-20261121'
$item=Get-Item -LiteralPath $link -Force
if (-not ($item.Attributes -band [IO.FileAttributes]::ReparsePoint)) { throw 'Package is not a reparse point' }
if (($item.Target -join '').TrimEnd('\') -ine $old.TrimEnd('\')) { throw 'Unexpected installed package target' }
if ((& git -C $target rev-parse HEAD) -ne 'e03460b8b8361ebffb4a44e3947312dd37c7cc94') { throw 'Release SHA mismatch' }
$item.Delete()
New-Item -ItemType Junction -Path $link -Target $target -ErrorAction Stop | Out-Null
Write-Output ('VENDOR_MESSAGING_TARGET='+((Get-Item -LiteralPath $link -Force).Target -join ''))
Write-Output ('CLASS_FILE_PRESENT='+(Test-Path (Join-Path $link 'src\Service\ACL\MessageScopeResolverService.php')))
