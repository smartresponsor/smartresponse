$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo=Join-Path $root 'Vendoring'
$old='local-priority-vendoring-finish'
$new='release/vendoring-local-head-20260904'
$files=@(& git -C $repo ls-tree -r --name-only $old -- tests | Where-Object { $_ -match '\.php$' })
$missing=@()
foreach ($f in $files) { $blob=(& git -C $repo rev-parse --verify ($new+':'+$f) 2>$null) -join ''; if (!$blob) { $missing+=$f } }
$all=@(& git -C $repo ls-tree -r --name-only $new -- tests | Where-Object { $_ -match '\.php$' })
foreach ($f in $missing) {
Write-Output ('=== MISSING '+$f+' ===')
$name=[System.IO.Path]::GetFileNameWithoutExtension($f)
$token=($name -replace '(Service|Test|Vendor)','')
$candidates=@($all | Where-Object { $_ -match [regex]::Escape($token) -or $_ -match [regex]::Escape($name) } | Select-Object -First 8)
Write-Output ('CANDIDATES='+($candidates -join ','))
$methods=@(& git -C $repo show ($old+':'+$f) | Select-String 'function test|#\[Test\]' | ForEach-Object {$_.Line.Trim()})
$methods | Select-Object -First 8 | ForEach-Object {Write-Output ('METHOD='+$_)}
}
