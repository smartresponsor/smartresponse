$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$base='39030f2166a835462463504b0438627272a993a0'
$branches=@('origin/codex/gather-criteria-for-developer-experience-evaluation','origin/local-priority-vendoring-finish','origin/security/gitleaks-semgrep')
foreach($b in $branches){Write-Output ('=== BRANCH '+$b+' ===');$tip=(& git -C $repo rev-parse $b).Trim();Write-Output ('TIP '+$tip);& git -C $repo rev-list --left-right --count "$base...$b";Write-Output 'UNIQUE COMMITS';& git -C $repo log --format='%h %s' "$base..$b" | Select-Object -First 25;Write-Output 'DIFF STATS';& git -C $repo diff --shortstat "$base...$b";Write-Output 'CHANGED PATHS';& git -C $repo diff --name-status "$base...$b" | Select-Object -First 40}
