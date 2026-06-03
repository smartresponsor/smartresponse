param(
    [Parameter(Mandatory = $false)]
    [ValidateNotNullOrEmpty()]
    [string] $RootPath = (Get-Location).Path,

    [Parameter(Mandatory = $false)]
    [switch] $SkipComposerInstall,

    [Parameter(Mandatory = $false)]
    [switch] $SkipCacheClear
)

$ErrorActionPreference = 'Stop'

$RootPath = (Resolve-Path $RootPath).Path
$ComposerProd = Join-Path $RootPath 'composer.prod.json'
$BundlesProd = Join-Path $RootPath 'config\bundles.prod-minimal.php'
$BundlesLive = Join-Path $RootPath 'config\bundles.php'

if (-not (Test-Path $ComposerProd)) {
    throw "Missing composer.prod.json at $ComposerProd"
}

if (-not (Test-Path $BundlesProd)) {
    throw "Missing config\bundles.prod-minimal.php at $BundlesProd"
}

$Stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$BundleBackup = Join-Path $RootPath "config\bundles.php.before-minimal-prod-$Stamp"
Copy-Item -Path $BundlesLive -Destination $BundleBackup -Force
Copy-Item -Path $BundlesProd -Destination $BundlesLive -Force
Write-Host "Minimal prod bundles applied. Backup: $BundleBackup"

Push-Location $RootPath
try {
    $env:COMPOSER = 'composer.prod.json'

    composer validate --strict

    if (-not $SkipComposerInstall) {
        composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
    }

    if (-not $SkipCacheClear) {
        php .\bin\console cache:clear --env=prod --no-debug
        php .\bin\console lint:container --env=prod
    }
}
finally {
    Remove-Item Env:\COMPOSER -ErrorAction SilentlyContinue
    Pop-Location
}
