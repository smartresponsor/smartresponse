param()
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root
$failed = 0
$commands = @(
  @('cache:clear','--env=dev','--no-warmup'),
  @('debug:router','fact_journal_current','--env=dev'),
  @('debug:container','App\ServiceInterface\Fact\FactCurrentSubjectProviderInterface','--env=dev'),
  @('doctrine:mapping:info','--em=postgres','--env=dev')
)
foreach ($args in $commands) {
  & php bin/console @args
  if ($LASTEXITCODE -ne 0) { $failed = 1 }
}
Remove-Item -LiteralPath $PSCommandPath -Force
exit $failed
