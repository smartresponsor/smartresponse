$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
  $env:APP_ENV = 'dev'
  $env:APP_DEBUG = '0'
  $env:DATABASE_URL = 'sqlite:///:memory:'
  & php -d max_execution_time=12 -r '$autoload=require "vendor/autoload.php"; $k=new App\Kernel("dev",false); $k->boot(); echo "BOOT_OK\n"; $c=$k->getContainer()->get("test.service_container"); echo "TEST_CONTAINER_READY\n";'
  Write-Output "PROBE_EXIT $LASTEXITCODE"
} finally { Pop-Location }
