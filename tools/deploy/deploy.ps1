[CmdletBinding()]
param(
    [switch] $Force,
    [switch] $DryRun
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Require-EnvironmentValue([string] $Name) {
    $value = [Environment]::GetEnvironmentVariable($Name)
    if ([string]::IsNullOrWhiteSpace($value)) {
        throw "Required environment variable is missing: $Name"
    }
    return $value
}

function Run([string] $Executable, [string[]] $Arguments) {
    & $Executable @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Command failed ($LASTEXITCODE): $Executable $($Arguments -join ' ')"
    }
}

$workspace = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$remoteScript = Join-Path $PSScriptRoot 'deploy-server.sh'

$hostName = Require-EnvironmentValue 'SMARTRESPONSOR_SSH_HOST'
$userName = Require-EnvironmentValue 'SMARTRESPONSOR_SSH_USER'
$keyPath = Require-EnvironmentValue 'SMARTRESPONSOR_SSH_KEY'
$remoteRoot = Require-EnvironmentValue 'SMARTRESPONSOR_REMOTE_ROOT'
$smokeUrl = Require-EnvironmentValue 'SMARTRESPONSOR_SMOKE_URL'
$port = [Environment]::GetEnvironmentVariable('SMARTRESPONSOR_SSH_PORT')
$branch = [Environment]::GetEnvironmentVariable('SMARTRESPONSOR_DEPLOY_BRANCH')

if ([string]::IsNullOrWhiteSpace($port)) { $port = '22' }
if ([string]::IsNullOrWhiteSpace($branch)) { $branch = 'master' }

$keyPath = [Environment]::ExpandEnvironmentVariables($keyPath)
if (-not (Test-Path $keyPath -PathType Leaf)) {
    throw "SSH private key does not exist: $keyPath"
}
if (-not (Test-Path $remoteScript -PathType Leaf)) {
    throw "Remote deployment script does not exist: $remoteScript"
}

Push-Location $workspace
try {
    $branchName = (& git branch --show-current).Trim()
    if ($LASTEXITCODE -ne 0) { throw 'Unable to resolve current Git branch.' }
    if ($branchName -ne $branch) {
        throw "Current branch '$branchName' does not match deployment branch '$branch'."
    }

    $dirty = & git status --porcelain
    if ($LASTEXITCODE -ne 0) { throw 'Unable to inspect Git status.' }
    if ($dirty) {
        throw 'Working tree is not clean. Commit or stash changes before deployment.'
    }

    Run 'git' @('fetch', 'origin', $branch)
    $localCommit = (& git rev-parse HEAD).Trim()
    $remoteCommit = (& git rev-parse "origin/$branch").Trim()
    if ($LASTEXITCODE -ne 0) { throw 'Unable to resolve deployment commits.' }
    if ($localCommit -ne $remoteCommit) {
        throw "Local HEAD ($localCommit) is not equal to origin/$branch ($remoteCommit). Push or pull before deployment."
    }

    Write-Host 'Smart Responsor deployment'
    Write-Host "  Target : $userName@$hostName`:$port"
    Write-Host "  Root   : $remoteRoot"
    Write-Host "  Branch : $branch"
    Write-Host "  Commit : $localCommit"
    Write-Host "  Smoke  : $smokeUrl"

    $sshArguments = @(
        '-o', 'BatchMode=yes',
        '-o', 'StrictHostKeyChecking=yes',
        '-o', 'ConnectTimeout=15',
        '-i', $keyPath,
        '-p', $port,
        "$userName@$hostName"
    )

    Run 'ssh' ($sshArguments + @('printf DEPLOY_SSH_OK'))

    if ($DryRun) {
        Write-Host 'DEPLOY DRY RUN: configuration, Git state, key and SSH connection are valid.'
        exit 0
    }

    if (-not $Force) {
        $answer = Read-Host 'Deploy this commit? Type DEPLOY to continue'
        if ($answer -cne 'DEPLOY') {
            throw 'Deployment cancelled.'
        }
    }

    $remoteCommand = "bash -s -- '$remoteRoot' '$branch' '$localCommit' '$smokeUrl'"
    (Get-Content -LiteralPath $remoteScript -Raw).Replace("`r`n", "`n") |
        & ssh @sshArguments $remoteCommand
    if ($LASTEXITCODE -ne 0) {
        throw "Remote deployment failed with exit code $LASTEXITCODE."
    }

    Write-Host 'DEPLOYMENT VERDICT: GREEN'
}
finally {
    Pop-Location
}
