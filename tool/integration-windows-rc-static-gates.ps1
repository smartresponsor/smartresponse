$ErrorActionPreference='Stop'
$app='D:\PhpstormProjects\www\.worktrees\host-app-integration-acceptance-20261121'
Push-Location $app
try {& composer validate --no-check-publish --no-interaction 2>&1 | Select-Object -Last 8;Write-Output ('COMPOSER_VALIDATE_EXIT='+$LASTEXITCODE);$files=@('src/DependencyInjection/Compiler/RegisterSiblingFixturesPass.php','src/Dto/Runtime/AppEffectiveRuntime.php','src/Service/Placement/Retail/RetailPlacementLocationService.php','tests/Unit/Runtime/AppEffectiveRuntimeServiceTest.php');foreach($f in $files){& php -l $f;Write-Output ('PHP_LINT_EXIT='+$LASTEXITCODE)};& git diff origin/master...HEAD --check;Write-Output ('DIFF_CHECK_EXIT='+$LASTEXITCODE);Write-Output ('TRACKED_DIRTY='+(@(& git status --porcelain --untracked-files=no).Count))}finally{Pop-Location}
