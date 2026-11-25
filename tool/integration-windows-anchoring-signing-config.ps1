$ErrorActionPreference='Stop'
$repo='D:\PhpstormProjects\www\.worktrees\anchoring-sha-acceptance-20261121'
$before=(& git -C $repo config --local --get commit.gpgsign)
if($before -ne 'false' -and $before -ne 'true'){throw ('Unexpected signing config: '+$before)}
& git -C $repo config --local commit.gpgsign true
if($LASTEXITCODE -ne 0){throw 'Unable to configure signing'}
Write-Output ('PREVIOUS='+$before)
Write-Output ('CURRENT='+(& git -C $repo config --local --get commit.gpgsign))
Write-Output ('SIGNING_FORMAT='+(& git -C $repo config --get gpg.format))
