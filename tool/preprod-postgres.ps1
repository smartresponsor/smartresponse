# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
[CmdletBinding()]
param(
    [string] $DatabaseName = 'app_preprod',
    [string] $DatabaseHost,
    [int] $DatabasePort = 0,
    [string] $DatabaseUser,
    [string] $DatabasePassword,
    [string] $AdminPassword = 'AccessingAdmin123!',
    [int] $HttpPort = 8088,
    [switch] $UseDocker,
    [switch] $SkipDatabaseReset,
    [switch] $SkipHttpSmoke
)
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$workspace = Split-Path -Parent $PSScriptRoot
$compose = Join-Path $workspace 'deploy/docker/compose.yaml'
$composeEnv = Join-Path $workspace 'deploy/docker/.env'
$server = $null

function Resolve-ProjectDatabaseConnection {
    $resolver = Join-Path $PSScriptRoot 'resolve-database-url.php'
    $lines = & php $resolver
    if ($LASTEXITCODE -ne 0) { throw 'Unable to resolve DATABASE_URL from the project environment.' }

    $values = @{}
    foreach ($line in $lines) {
        $parts = ([string] $line).Split('=', 2)
        if ($parts.Count -ne 2) { continue }
        $values[$parts[0]] = [Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($parts[1]))
    }

    foreach ($required in @('host', 'port', 'user', 'password')) {
        if (-not $values.ContainsKey($required)) { throw "Database resolver did not return '$required'." }
    }

    return $values
}

$projectDatabase = Resolve-ProjectDatabaseConnection
if ([string]::IsNullOrWhiteSpace($DatabaseHost)) { $DatabaseHost = [string] $projectDatabase.host }
if ($DatabasePort -le 0) { $DatabasePort = [int] $projectDatabase.port }
if ([string]::IsNullOrWhiteSpace($DatabaseUser)) { $DatabaseUser = [string] $projectDatabase.user }
if ([string]::IsNullOrWhiteSpace($DatabasePassword)) { $DatabasePassword = [string] $projectDatabase.password }

function Run([string] $exe, [string[]] $argv) {
    & $exe @argv
    if ($LASTEXITCODE -ne 0) { throw "Command failed ($LASTEXITCODE): $exe $($argv -join ' ')" }
}
function Console([string[]] $argv) { Run 'php' (@('bin/console') + $argv) }
function Stop-OwnedHttpServer {
    $listeners = Get-NetTCPConnection -LocalPort $HttpPort -State Listen -ErrorAction SilentlyContinue
    foreach ($listener in $listeners) {
        $process = Get-CimInstance Win32_Process -Filter "ProcessId = $($listener.OwningProcess)" -ErrorAction SilentlyContinue
        if ($null -eq $process) { continue }
        $commandLine = [string] $process.CommandLine
        $isPhpServer = $process.Name -like 'php*' -and $commandLine -match '\s-S\s' -and $commandLine -match [regex]::Escape("127.0.0.1:$HttpPort")
        if (-not $isPhpServer) { throw "Port $HttpPort is occupied by a non-owned process (PID $($listener.OwningProcess))." }
        Stop-Process -Id $listener.OwningProcess -Force -ErrorAction Stop
    }
    for ($i = 0; $i -lt 20; $i++) {
        if (-not (Get-NetTCPConnection -LocalPort $HttpPort -State Listen -ErrorAction SilentlyContinue)) { return }
        Start-Sleep -Milliseconds 100
    }
    throw "Unable to release HTTP smoke port $HttpPort."
}
function Reset-LocalDatabase {
    $previousPassword = $env:PGPASSWORD
    try {
        $env:PGPASSWORD = $DatabasePassword
        Run 'psql' @('-h', $DatabaseHost, '-p', [string] $DatabasePort, '-U', $DatabaseUser, '-d', 'postgres',
            '-v', 'ON_ERROR_STOP=1', '-c', "DROP DATABASE IF EXISTS `"$DatabaseName`" WITH (FORCE);")
        Run 'psql' @('-h', $DatabaseHost, '-p', [string] $DatabasePort, '-U', $DatabaseUser, '-d', 'postgres',
            '-v', 'ON_ERROR_STOP=1', '-c', "CREATE DATABASE `"$DatabaseName`" OWNER `"$DatabaseUser`";")
    } finally {
        $env:PGPASSWORD = $previousPassword
    }
}
function Start-DockerPostgres {
    Run 'docker' @('compose', '--env-file', $composeEnv, '-f', $compose, 'up', '-d', 'postgres')
    $health = ''
    for ($i = 0; $i -lt 60; $i++) {
        $health = docker inspect --format '{{.State.Health.Status}}' app-host-postgres 2>$null
        if ($LASTEXITCODE -eq 0 -and ($health | Out-String).Trim() -eq 'healthy') { return }
        Start-Sleep -Seconds 2
    }
    throw 'PostgreSQL Docker health check failed.'
}
function Reset-DockerDatabase {
    Run 'docker' @('exec', 'app-host-postgres', 'psql', '-U', $DatabaseUser, '-d', 'postgres',
        '-v', 'ON_ERROR_STOP=1', '-c', "DROP DATABASE IF EXISTS `"$DatabaseName`" WITH (FORCE);")
    Run 'docker' @('exec', 'app-host-postgres', 'psql', '-U', $DatabaseUser, '-d', 'postgres',
        '-v', 'ON_ERROR_STOP=1', '-c', "CREATE DATABASE `"$DatabaseName`" OWNER `"$DatabaseUser`";")
}

Push-Location $workspace
try {
    if (-not $SkipHttpSmoke) { Stop-OwnedHttpServer }

    if ($UseDocker) {
        Start-DockerPostgres
        if (-not $SkipDatabaseReset) { Reset-DockerDatabase }
    } elseif (-not $SkipDatabaseReset) {
        Reset-LocalDatabase
    }

    $encodedUser = [uri]::EscapeDataString($DatabaseUser)
    $encodedPassword = [uri]::EscapeDataString($DatabasePassword)
    $env:APP_ENV = 'prod'
    $env:APP_DEBUG = '0'
    $env:APP_CACHE_DIR = Join-Path $workspace "var/cache/preprod-$HttpPort"
    $connectionUrl = "postgresql://${encodedUser}:${encodedPassword}@${DatabaseHost}:${DatabasePort}/${DatabaseName}?serverVersion=16&charset=utf8"
    $env:DATABASE_URL = $connectionUrl
    $env:PLATFORM_DATA_DATABASE = $connectionUrl
    $env:ACCESSING_ADMIN_PASSWORD = $AdminPassword

    Console @('cache:clear', '--no-debug', '--no-interaction')
    Console @('doctrine:schema:update', '--em=postgres', '--force', '--complete', '--no-interaction')
    Console @('doctrine:schema:validate', '--em=postgres', '--no-interaction')
    Console @('accessing:admin:ensure', '--password', $AdminPassword, '--reset-password', '--no-interaction')
    Console @('doctrine:schema:validate', '--em=postgres', '--no-interaction')

    if (-not $SkipHttpSmoke) {
        $httpLogDirectory = Join-Path $workspace 'var/preprod'
        New-Item -ItemType Directory -Force -Path $httpLogDirectory | Out-Null
        $httpStdout = Join-Path $httpLogDirectory 'http-stdout.log'
        $httpStderr = Join-Path $httpLogDirectory 'http-stderr.log'
        Remove-Item $httpStdout, $httpStderr -Force -ErrorAction SilentlyContinue

        $server = Start-Process 'php' -ArgumentList @(
            '-S', "127.0.0.1:$HttpPort", '-t', 'public', 'public/index.php'
        ) -WorkingDirectory $workspace -PassThru -WindowStyle Hidden `
            -RedirectStandardOutput $httpStdout -RedirectStandardError $httpStderr
        $uri = "http://127.0.0.1:$HttpPort"
        $ready = $false
        for ($i = 0; $i -lt 40; $i++) {
            try {
                Invoke-WebRequest "$uri/api/access/session" -UseBasicParsing -TimeoutSec 2 | Out-Null
                $ready = $true
                break
            } catch { Start-Sleep -Milliseconds 500 }
        }
        if (-not $ready) {
            $stderrTail = if (Test-Path $httpStderr) { (Get-Content $httpStderr -Tail 80) -join [Environment]::NewLine } else { 'No HTTP stderr log was produced.' }
            throw "Symfony HTTP smoke server did not become ready.`n$stderrTail"
        }

        $body = @{ email = 'admin@smartresponsor.local'; password = $AdminPassword } | ConvertTo-Json
        $result = Invoke-RestMethod -Method Post -Uri "$uri/api/access/signin" `
            -ContentType 'application/json' -Headers @{ 'X-Device-Name' = 'PostgreSQL pre-production smoke' } `
            -Body $body -TimeoutSec 15
        if ($result.status -ne 'authenticated' -or [string]::IsNullOrWhiteSpace([string] $result.accessToken)) {
            throw 'Real HTTP authentication smoke failed.'
        }
    }

    $mode = if ($UseDocker) { 'Docker PostgreSQL' } else { 'local PostgreSQL' }
    Write-Host 'POSTGRES PRE-PRODUCTION VERDICT: GREEN'
    Write-Host "Mode: $mode"
    Write-Host "Database: $DatabaseName"
    Write-Host "Admin: admin@smartresponsor.local / $AdminPassword"
} finally {
    if ($null -ne $server -and -not $server.HasExited) { Stop-Process $server.Id -Force }
    Pop-Location
}
