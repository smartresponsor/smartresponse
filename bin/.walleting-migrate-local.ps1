$versions = @(
  'Version20260801021000','Version20260801030345','Version20260801080200','Version20260801214500','Version20260801232000','Version20260802005500','Version20260802013500','Version20260802034500','Version20260802044200','Version20260802051800','Version20260803023500','Version20260807063100','Version20260807071100','Version20260808002000','Version20260808012000','Version20260808015500','Version20260808023000','Version20260808024500','Version20260808033000','Version20260808182000','Version20260808190000','Version20260808203000','Version20260808210000','Version20260808213000'
)
$env:APP_ENV='prod'
$env:APP_DEBUG='0'
$env:APP_BASE_URI='http://127.0.0.1:8000'
$env:APP_CACHE_DIR=(Join-Path $PSScriptRoot '..\var\cache\walleting_probe')
foreach ($version in $versions) {
  $fqcn = "App\Walleting\Migrations\$version"
  php bin/console doctrine:migrations:execute $fqcn --up --no-interaction --env=prod --no-debug
  if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
exit 0
