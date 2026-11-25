$ErrorActionPreference='Stop'
$root='D:\PhpstormProjects\www'
$app=Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
$manifest=Get-Content (Join-Path $app 'composer.json') -Raw | ConvertFrom-Json
$lock=Get-Content (Join-Path $app 'composer.lock') -Raw | ConvertFrom-Json
$rows=@()
foreach($repo in @($manifest.repositories | Where-Object {$_.type -eq 'path' -and $_.url -like '../*'})) {
  $relative=$repo.url.Substring(3)
  $path=Join-Path $root $relative
  $link=Join-Path (Join-Path $root '.worktrees') $relative
  $package=Get-Content (Join-Path $path 'composer.json') -Raw | ConvertFrom-Json
  $locked=@($lock.packages+$lock.'packages-dev' | Where-Object {$_.name -eq $package.name}) | Select-Object -First 1
  $head=(& git -C $path rev-parse HEAD 2>$null)
  $ref=if($locked){$locked.dist.reference}else{''}
  $rows += [pscustomobject]@{Name=$package.name;SourceHead=$head;LockReference=$ref;SourceMatchesLock=($head -eq $ref);AcceptanceLink=(Test-Path -LiteralPath $link)}
}
$rows | Sort-Object Name | Format-Table -AutoSize | Out-String -Width 260 | Write-Output
Write-Output ('TOTAL='+$rows.Count)
Write-Output ('DRIFT='+@($rows | Where-Object {-not $_.SourceMatchesLock}).Count)
Write-Output ('NO_LOCK='+@($rows | Where-Object {-not $_.LockReference}).Count)
