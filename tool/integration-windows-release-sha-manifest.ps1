$ErrorActionPreference='Stop'
$root='D:\PhpstormProjects\www'
$app=Join-Path $root '.worktrees\host-app-integration-acceptance-20261121'
$composer=Get-Content (Join-Path $app 'composer.json') -Raw | ConvertFrom-Json
$lock=Get-Content (Join-Path $app 'composer.lock') -Raw | ConvertFrom-Json
$entries=@()
foreach($repo in @($composer.repositories | Where-Object {$_.type -eq 'path' -and $_.url -like '../*'})) {
  $name=$repo.url.Substring(3)
  $link=Join-Path (Join-Path $root '.worktrees') $name
  $source=Join-Path $root $name
  $path=if(Test-Path -LiteralPath $link){$link}else{$source}
  $resolved=(Resolve-Path -LiteralPath $path).Path
  $package=Get-Content (Join-Path $path 'composer.json') -Raw | ConvertFrom-Json
  $locked=@($lock.packages+$lock.'packages-dev' | Where-Object {$_.name -eq $package.name}) | Select-Object -First 1
  $head=(& git -C $path rev-parse HEAD).Trim()
  $dirty=(& git -C $path status --porcelain | Measure-Object -Line).Lines
  $entries += [ordered]@{package=$package.name;path=$name;git_sha=$head;dirty_files=$dirty;composer_lock_reference=if($locked){$locked.dist.reference}else{$null}}
}
$entries=@($entries | Sort-Object {$_.package})
$manifest=[ordered]@{schema='smartresponsor.host-app.component-sha-manifest.v1';host_app_sha=(& git -C $app rev-parse HEAD).Trim();generated_utc=[DateTime]::UtcNow.ToString('o');components=$entries}
$json=$manifest | ConvertTo-Json -Depth 8
$out=Join-Path $app 'var\component-release-sha-manifest.json'
New-Item -ItemType Directory -Force -Path (Split-Path $out) | Out-Null
[IO.File]::WriteAllText($out,$json,[Text.UTF8Encoding]::new($false))
Write-Output ('MANIFEST='+$out)
Write-Output ('TOTAL='+$entries.Count)
Write-Output ('DIRTY_COMPONENTS='+@($entries|Where-Object {$_.dirty_files -gt 0}).Count)
$entries | Where-Object {$_.package -in @('messaging/message','addressing/address','delivering/delivery','anchoring/anchor','domaining/domain')} | ConvertTo-Json -Depth 4 | Write-Output
