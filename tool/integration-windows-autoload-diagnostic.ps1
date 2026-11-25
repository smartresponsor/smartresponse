$ErrorActionPreference='Stop'
$app='D:\PhpstormProjects\www\.worktrees\host-app-integration-acceptance-20261121'
$vendor=Join-Path $app 'vendor\messaging\message'
$link=Join-Path $vendor 'src\Service\ACL\MessageScopeResolverService.php'
Write-Output ('VENDOR_FILE='+ (Test-Path -LiteralPath $link))
Write-Output ('VENDOR_PACKAGE='+ $vendor)
$package=Get-Item -LiteralPath $vendor -Force
Write-Output ('PACKAGE_LINK_TARGET='+($package.Target -join ''))
Push-Location $app
try {
  & php -r '$loader=require "vendor/autoload.php"; echo "CLASS_EXISTS=".(class_exists("App\\Messaging\\Service\\ACL\\MessageScopeResolverService")?"yes":"no").PHP_EOL; echo "FILE=".($loader->findFile("App\\Messaging\\Service\\ACL\\MessageScopeResolverService")?:"NONE").PHP_EOL;'
  Write-Output ('AUTOLOAD_EXIT='+$LASTEXITCODE)
} finally { Pop-Location }
