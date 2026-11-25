$ErrorActionPreference='Stop'
$root=(Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$repo=Join-Path $root 'Vendoring'
$base='local-priority-vendoring-finish'
$release='release/vendoring-local-head-20260904'
$commits=@(& git -C $repo rev-list --reverse ('master..'+$base))
foreach ($sha in $commits) {
$subject=(& git -C $repo log -1 --format='%s' $sha).Trim()
$files=@(& git -C $repo diff-tree --no-commit-id --name-only -r $sha)
$source=@($files | Where-Object { $_ -match '^(src|tests|config|tools)/' })
$exists=0; $same=0; $changed=0; $missing=0
foreach ($file in $source) {
$old=(& git -C $repo rev-parse --verify ($sha+':'+$file) 2>$null) -join ''
$new=(& git -C $repo rev-parse --verify ($release+':'+$file) 2>$null) -join ''
if ($new) { $exists++; if ($old -and $old -eq $new) { $same++ } else { $changed++ } } else { $missing++ }
}
Write-Output ($sha.Substring(0,12)+'|'+$subject+'|FILES='+$files.Count+'|SCOPED='+$source.Count+'|SAME='+$same+'|CHANGED='+$changed+'|MISSING='+$missing)
if ($source.Count -le 12) { Write-Output ('PATHS='+($source -join ',')) }
}
