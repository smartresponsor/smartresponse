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

function Resolve-SshExecutable {
    $command = Get-Command ssh -ErrorAction SilentlyContinue
    if ($null -ne $command) {
        return $command.Source
    }

    $candidates = [System.Collections.Generic.List[string]]::new()
    $candidates.Add((Join-Path $env:WINDIR 'System32\OpenSSH\ssh.exe'))
    $candidates.Add((Join-Path $env:ProgramFiles 'Git\usr\bin\ssh.exe'))
    $candidates.Add((Join-Path $env:ProgramFiles 'Git\mingw64\bin\ssh.exe'))
    if (-not [string]::IsNullOrWhiteSpace(${env:ProgramFiles(x86)})) {
        $candidates.Add((Join-Path ${env:ProgramFiles(x86)} 'Git\usr\bin\ssh.exe'))
    }
    if (-not [string]::IsNullOrWhiteSpace($env:LOCALAPPDATA)) {
        $candidates.Add((Join-Path $env:LOCALAPPDATA 'Programs\Git\usr\bin\ssh.exe'))
        $candidates.Add((Join-Path $env:LOCALAPPDATA 'Programs\Git\mingw64\bin\ssh.exe'))
    }
    if (-not [string]::IsNullOrWhiteSpace($env:USERPROFILE)) {
        $candidates.Add((Join-Path $env:USERPROFILE 'scoop\apps\git\current\usr\bin\ssh.exe'))
    }
    if (-not [string]::IsNullOrWhiteSpace($env:ChocolateyInstall)) {
        $candidates.Add((Join-Path $env:ChocolateyInstall 'bin\ssh.exe'))
    }

    $gitCommand = Get-Command git -ErrorAction SilentlyContinue
    if ($null -ne $gitCommand -and -not [string]::IsNullOrWhiteSpace($gitCommand.Source)) {
        $gitDirectory = Split-Path -Parent $gitCommand.Source
        $gitRoot = Split-Path -Parent $gitDirectory
        $candidates.Add((Join-Path $gitRoot 'usr\bin\ssh.exe'))
        $candidates.Add((Join-Path $gitRoot 'mingw64\bin\ssh.exe'))
    }

    foreach ($candidate in $candidates) {
        if (-not [string]::IsNullOrWhiteSpace($candidate) -and (Test-Path $candidate -PathType Leaf)) {
            return $candidate
        }
    }

    throw 'No SSH client was found. Install the Windows OpenSSH Client optional feature or install Git for Windows with OpenSSH support.'
}

$workspace = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$remoteScript = Join-Path $PSScriptRoot 'deploy-server.sh'
$sshExecutable = Resolve-SshExecutable

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

    $dirty = & git status --porcelain
    if ($LASTEXITCODE -ne 0) { throw 'Unable to inspect Git status.' }

    Run 'git' @('fetch', 'origin', $branch)
    $localCommit = (& git rev-parse HEAD).Trim()
    $deployCommit = (& git rev-parse "origin/$branch").Trim()
    if ($LASTEXITCODE -ne 0) { throw 'Unable to resolve deployment commits.' }

    $aheadCount = (& git rev-list --count "origin/$branch..HEAD").Trim()
    if ($LASTEXITCODE -ne 0) { throw 'Unable to resolve unpublished commit count.' }
    $behindCount = (& git rev-list --count "HEAD..origin/$branch").Trim()
    if ($LASTEXITCODE -ne 0) { throw 'Unable to resolve remote commit count.' }

    Write-Host 'Smart Responsor deployment'
    Write-Host "  Target       : $userName@$hostName`:$port"
    Write-Host "  Root         : $remoteRoot"
    Write-Host "  Source       : origin/$branch"
    Write-Host "  Deploy commit: $deployCommit"
    Write-Host "  Local branch : $branchName"
    Write-Host "  Local HEAD   : $localCommit"
    Write-Host "  Working tree : $(if ($dirty) { 'DIRTY (ignored)' } else { 'clean' })"
    Write-Host "  Local ahead  : $aheadCount commit(s) not deployed"
    Write-Host "  Local behind : $behindCount commit(s)"
    Write-Host "  Smoke        : $smokeUrl"

    $sshArguments = @(
        '-o', 'BatchMode=yes',
        '-o', 'StrictHostKeyChecking=yes',
        '-o', 'ConnectTimeout=15',
        '-i', $keyPath,
        '-p', $port,
        "$userName@$hostName"
    )

    Run $sshExecutable ($sshArguments + @('printf DEPLOY_SSH_OK'))

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

    $remoteCommand = "bash -s -- '$remoteRoot' '$branch' '$deployCommit' '$smokeUrl'"
    (Get-Content -LiteralPath $remoteScript -Raw).Replace("`r`n", "`n") |
        & $sshExecutable @sshArguments $remoteCommand
    if ($LASTEXITCODE -ne 0) {
        throw "Remote deployment failed with exit code $LASTEXITCODE."
    }

    Write-Host 'DEPLOYMENT VERDICT: GREEN'
}
finally {
    Pop-Location
}
