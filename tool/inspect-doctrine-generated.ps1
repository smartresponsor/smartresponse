$root = Split-Path -Parent $PSScriptRoot
$path = Join-Path $root 'var/cache/diagnostic-doctrine'
$files = Get-ChildItem -LiteralPath $path -Recurse -Filter '*.php' -File -ErrorAction Stop
$matches = $files | Select-String -Pattern 'new \\Doctrine\\Bundle\\DoctrineBundle\\ConnectionFactory\(' -ErrorAction SilentlyContinue
foreach ($m in $matches) { Write-Output "GENERATED $($m.Path):$($m.LineNumber) $($m.Line.Trim())" }
if (-not $matches) { Write-Output 'NO_FACTORY_IN_GENERATED_PHP' }
