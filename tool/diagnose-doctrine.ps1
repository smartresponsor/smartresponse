$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
    $code = @'
<?php
require 'vendor/autoload.php';
$ref = new ReflectionMethod(Doctrine\Bundle\DoctrineBundle\ConnectionFactory::class, '__construct');
echo "FACTORY_SIGNATURE ".$ref."\n";
$cache = 'var/cache/prod/ContainerZxmxTgM/App_KernelProdContainer.php';
if (is_file($cache)) {
    $lines = file($cache);
    for ($i = 1765; $i <= 1775; $i++) {
        if (isset($lines[$i-1])) echo "CACHE_LINE ".$i." ".trim($lines[$i-1])."\n";
    }
}
'@
    $tmp = Join-Path $root 'var/diagnostic-doctrine-factory.php'
    [IO.File]::WriteAllText($tmp, $code)
    try { & php $tmp; if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE } }
    finally { Remove-Item -LiteralPath $tmp -ErrorAction SilentlyContinue }
} finally { Pop-Location }
