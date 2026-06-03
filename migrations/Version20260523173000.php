<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move repeated name/title/comment fields to Objecting object_title embeddables.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->migrateTitleTable('module', true);
        $this->migrateTitleTable('category', true);
        $this->migrateTitleTable('featured', false);
        $this->migrateTitleTable('menu', false);
        $this->migrateTitleTable('product_type', false);
        $this->migrateTitleTable('project_type', false);
        $this->migrateReviewTable();
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->restoreTitleTable('module', true);
        $this->restoreTitleTable('category', true);
        $this->restoreTitleTable('featured', false);
        $this->restoreTitleTable('menu', false);
        $this->restoreTitleTable('product_type', false);
        $this->restoreTitleTable('project_type', false);
        $this->restoreReviewTable();
    }

    private function migrateTitleTable(string $table, bool $hasDescription): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_first_title VARCHAR(255) DEFAULT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_middle_title TEXT DEFAULT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_last_title TEXT DEFAULT NULL', $table));
        $this->addSql(sprintf('UPDATE %s SET object_first_title = name, object_middle_title = NULL', $table));

        if ($hasDescription) {
            $this->addSql(sprintf('UPDATE %s SET object_last_title = description', $table));
        }

        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN object_first_title SET NOT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN name', $table));

        if ($hasDescription) {
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN description', $table));
        }
    }

    private function restoreTitleTable(string $table, bool $hasDescription): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN name VARCHAR(160) DEFAULT NULL', $table));
        if ($hasDescription) {
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN description TEXT DEFAULT NULL', $table));
        }

        $this->addSql(sprintf('UPDATE %s SET name = object_first_title', $table));
        if ($hasDescription) {
            $this->addSql(sprintf('UPDATE %s SET description = object_last_title', $table));
        }

        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN name SET NOT NULL', $table));

        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_last_title', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_middle_title', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_first_title', $table));
    }

    private function migrateReviewTable(): void
    {
        $this->addSql('ALTER TABLE review ADD COLUMN object_first_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD COLUMN object_middle_title TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD COLUMN object_last_title TEXT DEFAULT NULL');
        $this->addSql('UPDATE review SET object_first_title = title, object_last_title = comment, object_middle_title = NULL');
        $this->addSql('ALTER TABLE review ALTER COLUMN object_first_title SET NOT NULL');
        $this->addSql('ALTER TABLE review DROP COLUMN title');
        $this->addSql('ALTER TABLE review DROP COLUMN comment');
    }

    private function restoreReviewTable(): void
    {
        $this->addSql('ALTER TABLE review ADD COLUMN title VARCHAR(160) DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD COLUMN comment TEXT DEFAULT NULL');
        $this->addSql('UPDATE review SET title = object_first_title, comment = object_last_title');
        $this->addSql('ALTER TABLE review ALTER COLUMN title SET NOT NULL');
        $this->addSql('ALTER TABLE review ALTER COLUMN comment SET NOT NULL');
        $this->addSql('ALTER TABLE review DROP COLUMN object_last_title');
        $this->addSql('ALTER TABLE review DROP COLUMN object_middle_title');
        $this->addSql('ALTER TABLE review DROP COLUMN object_first_title');
    }
}
