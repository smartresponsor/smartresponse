<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523174000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move repeated active/status fields to Objecting object_state embeddables.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->migrateActiveTable('module');
        $this->migrateActiveTable('category');
        $this->migrateActiveTable('featured');
        $this->migrateMenuTable();
        $this->migrateActiveTable('product_type');
        $this->migrateActiveTable('project_type');
        $this->migrateReviewTable();
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->restoreActiveTable('module');
        $this->restoreActiveTable('category');
        $this->restoreActiveTable('featured');
        $this->restoreMenuTable();
        $this->restoreActiveTable('product_type');
        $this->restoreActiveTable('project_type');
        $this->restoreReviewTable();
    }

    private function migrateActiveTable(string $table): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_active BOOLEAN NOT NULL DEFAULT TRUE', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_enabled BOOLEAN NOT NULL DEFAULT TRUE', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_status VARCHAR(64) DEFAULT NULL', $table));
        $this->addSql(sprintf('UPDATE %s SET object_active = active, object_enabled = TRUE, object_status = NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN active', $table));
    }

    private function migrateMenuTable(): void
    {
        $this->addSql('ALTER TABLE menu ADD COLUMN object_active BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE menu ADD COLUMN object_enabled BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE menu ADD COLUMN object_status VARCHAR(64) DEFAULT NULL');
    }

    private function migrateReviewTable(): void
    {
        $this->addSql('ALTER TABLE review ADD COLUMN object_active BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE review ADD COLUMN object_enabled BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE review ADD COLUMN object_status VARCHAR(64) DEFAULT NULL');
        $this->addSql('UPDATE review SET object_active = TRUE, object_enabled = TRUE, object_status = status');
        $this->addSql('ALTER TABLE review DROP COLUMN status');
    }

    private function restoreActiveTable(string $table): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN active BOOLEAN NOT NULL DEFAULT TRUE', $table));
        $this->addSql(sprintf('UPDATE %s SET active = object_active', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_status', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_enabled', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_active', $table));
    }

    private function restoreMenuTable(): void
    {
        $this->addSql('ALTER TABLE menu DROP COLUMN object_status');
        $this->addSql('ALTER TABLE menu DROP COLUMN object_enabled');
        $this->addSql('ALTER TABLE menu DROP COLUMN object_active');
    }

    private function restoreReviewTable(): void
    {
        $this->addSql('ALTER TABLE review ADD COLUMN status VARCHAR(32) DEFAULT NULL');
        $this->addSql('UPDATE review SET status = object_status');
        $this->addSql('ALTER TABLE review ALTER COLUMN status SET NOT NULL');
        $this->addSql('ALTER TABLE review DROP COLUMN object_status');
        $this->addSql('ALTER TABLE review DROP COLUMN object_enabled');
        $this->addSql('ALTER TABLE review DROP COLUMN object_active');
    }
}
