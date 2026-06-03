<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move shared timestamp audit columns to Objecting object_audit embeddables.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        foreach (['module', 'category', 'featured', 'menu', 'product_type', 'project_type', 'review'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL', $table));
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL', $table));
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_created_by VARCHAR(190) DEFAULT NULL', $table));
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_updated_by VARCHAR(190) DEFAULT NULL', $table));
            $this->addSql(sprintf('UPDATE %s SET object_created_at = created_at WHERE object_created_at IS NULL', $table));
            $this->addSql(sprintf('UPDATE %s SET object_updated_at = updated_at WHERE object_updated_at IS NULL', $table));
            $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN object_created_at SET NOT NULL', $table));
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        foreach (['module', 'category', 'featured', 'menu', 'product_type', 'project_type', 'review'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_updated_by', $table));
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_created_by', $table));
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_updated_at', $table));
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_created_at', $table));
        }
    }
}
