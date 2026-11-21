$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$probe = Join-Path $root 'var/probe-navigation-db.php'
$php = @'
<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel = new App\Kernel('dev', false);
$kernel->boot();
echo "BOOT_OK\n";
$container = $kernel->getContainer();
$started = microtime(true);
try {
  $connection = $container->get('doctrine.dbal.postgres_connection');
  echo 'CONNECTION_RESOLVED_MS='.round((microtime(true)-$started)*1000)."\n";
  $connection->executeQuery('SELECT 1')->fetchOne();
  echo 'DB_PING_MS='.round((microtime(true)-$started)*1000)."\n";
  $exists = $connection->executeQuery("SELECT to_regclass('navigation_menu')")->fetchOne();
  echo 'NAVIGATION_TABLE_PRESENT='.($exists !== null && $exists !== '' ? 'YES':'NO')."\n";
} catch (Throwable $e) {
  echo 'DB_PROBE_ERROR_CLASS='.get_class($e)."\n";
  echo 'DB_PROBE_ERROR_CODE='.$e->getCode()."\n";
  exit(2);
} finally { $kernel->shutdown(); }
'@
try {
  Set-Content -LiteralPath $probe -Value $php -Encoding utf8
  Push-Location $root
  try {
    & php -d max_execution_time=12 $probe
    Write-Output "DB_PROBE_EXIT $LASTEXITCODE"
  } finally { Pop-Location }
} finally { Remove-Item -LiteralPath $probe -Force -ErrorAction SilentlyContinue }
