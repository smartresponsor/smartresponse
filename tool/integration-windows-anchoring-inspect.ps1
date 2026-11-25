$root='D:\PhpstormProjects\www'
$app=Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
foreach($p in @((Join-Path $root '.worktrees\Anchoring'),(Join-Path $root '.worktrees\Anchoring\sdk\php'),(Join-Path $app 'vendor\anchoring\anchor'))) {
  $item=Get-Item -LiteralPath $p -Force -ErrorAction SilentlyContinue
  if($item){Write-Output ('PATH='+$p+' TARGET='+($item.Target -join '')+' LINK='+[bool]($item.Attributes -band [IO.FileAttributes]::ReparsePoint))}else{Write-Output ('MISSING='+$p)}
}
