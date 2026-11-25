$ErrorActionPreference='Stop'
$names=@('SMARTRESPONSOR_SSH_HOST','SMARTRESPONSOR_SSH_USER','SMARTRESPONSOR_SSH_KEY','SMARTRESPONSOR_SSH_PORT')
foreach($name in $names){[Environment]::SetEnvironmentVariable($name,[Environment]::GetEnvironmentVariable($name,'User'),'Process')}
$key=[Environment]::ExpandEnvironmentVariables($env:SMARTRESPONSOR_SSH_KEY)
Write-Output ('KEY_EXISTS='+(Test-Path -LiteralPath $key -PathType Leaf))
$pub=$key+'.pub'
Write-Output ('PUBLIC_KEY_EXISTS='+(Test-Path -LiteralPath $pub -PathType Leaf))
if(Test-Path -LiteralPath $pub){& ssh-keygen -lf $pub}
$port=$env:SMARTRESPONSOR_SSH_PORT
$dest=$env:SMARTRESPONSOR_SSH_USER+'@'+$env:SMARTRESPONSOR_SSH_HOST
$output=@(& ssh -vv -o BatchMode=yes -o StrictHostKeyChecking=yes -o IdentitiesOnly=yes -o ConnectTimeout=12 -i $key -p $port $dest 'true' 2>&1)
Write-Output ('SSH_EXIT='+$LASTEXITCODE)
$output|Where-Object {$_ -match 'Offering public key|Server accepts key|Authentications that can continue|Permission denied|identity file|sign_and_send_pubkey|Authenticated to|no mutual signature|agent refused|Load key|Bad permissions'}|ForEach-Object {Write-Output $_}
