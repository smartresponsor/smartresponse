$ErrorActionPreference = 'Stop'
$source = Join-Path $PSScriptRoot '..\var\relating-create.sql'
$destination = Join-Path $PSScriptRoot '..\migrations\Version20260915174500RelatingCurrentBaseline.php'
if (-not (Test-Path -LiteralPath $source)) { throw 'Scoped Relating SQL source is missing.' }
$sqlLines = Get-Content -LiteralPath $source | Where-Object { $_.Trim() -ne '' }
$tables = @()
foreach ($line in $sqlLines) {
    if ($line -match '^CREATE TABLE\s+([A-Za-z0-9_]+)\s') { $tables += $Matches[1] }
}
$tables = $tables | Sort-Object -Unique
$php = @('<?php','','declare(strict_types=1);','','namespace DoctrineMigrations;','','use Doctrine\DBAL\Platforms\PostgreSQLPlatform;','use Doctrine\DBAL\Schema\Schema;','use Doctrine\Migrations\AbstractMigration;','','final class Version20260915174500RelatingCurrentBaseline extends AbstractMigration','{','    public function getDescription(): string','    {',"        return 'Materialize the current Relating ORM baseline in the Host database.';",'    }','','    public function up(Schema $schema): void','    {',"        `$this->abortIf(!`$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'Relating baseline supports PostgreSQL only.');",'        $tables = [')
foreach ($table in $tables) { $php += "            '$table'," }
$php += '        ];'
$php += '        $existing = array_values(array_filter($tables, static fn (string $table): bool => $schema->hasTable($table)));'
$php += '        if (count($existing) === count($tables)) { return; }'
$php += "        `$this->abortIf([] !== `$existing, sprintf('Partial Relating baseline detected (%d/%d tables); refusing mixed create/adopt execution.', count(`$existing), count(`$tables)));"
$php += ''
foreach ($line in $sqlLines) {
    $sql = $line.Trim()
    if ($sql.EndsWith(';')) { $sql = $sql.Substring(0, $sql.Length - 1) }
    $escaped = $sql.Replace('\', '\\').Replace("'", "\'")
    $php += "        `$this->addSql('$escaped');"
}
$php += @('    }','','    public function down(Schema $schema): void','    {',"        `$this->throwIrreversibleMigrationException('Current Relating ORM baseline is the forward Host schema contract.');",'    }','}')
Set-Content -LiteralPath $destination -Value $php
Write-Output ('tables=' + $tables.Count)
Write-Output ('sql=' + $sqlLines.Count)
Write-Output ('migration=' + $destination)
