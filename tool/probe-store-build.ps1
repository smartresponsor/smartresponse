$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$probe = Join-Path $root 'var/probe-store-isolated.php'
$php = @'
<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
$kernel = new App\Kernel('dev', false);
$kernel->boot();
echo "BOOT_OK\n";
$container = $kernel->getContainer();
$service = $container->get('App\\Service\\Store\\AppStoreBuilderService');
echo "STORE_SERVICE_READY\n";
$request = Symfony\Component\HttpFoundation\Request::create('/');
$started = microtime(true);
$payload = $service->buildStore($request);
echo 'STORE_BUILD_OK elapsed_ms='.round((microtime(true)-$started)*1000)."\n";
echo 'PAYLOAD_TYPE='.get_debug_type($payload)."\n";
$kernel->shutdown();
echo "SHUTDOWN_OK\n";
'@
try {
  Set-Content -LiteralPath $probe -Value $php -Encoding utf8
  Push-Location $root
  try {
    $env:APP_ENV = 'dev'
    $env:APP_DEBUG = '0'
    if (-not $env:DATABASE_URL) { $env:DATABASE_URL = 'sqlite:///:memory:' }
    & php -d max_execution_time=15 $probe
    Write-Output "PROBE_EXIT $LASTEXITCODE"
  } finally { Pop-Location }
} finally { Remove-Item -LiteralPath $probe -Force -ErrorAction SilentlyContinue }
