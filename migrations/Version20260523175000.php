<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523175000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move repeated tenant scope identifiers to Objecting scope embeddables.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        foreach (['module', 'category', 'featured', 'menu', 'product_type', 'project_type', 'review'] as $table) {
            $this->addScopeColumns($table);
            $this->backfillScopeColumns($table);
        }

        $this->replaceScopeConstraints();
        $this->replaceReviewIndexes();

        foreach (['module', 'category', 'featured', 'menu', 'product_type', 'project_type', 'review'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN tenant_id', $table));
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        foreach (['module', 'category', 'featured', 'menu', 'product_type', 'project_type', 'review'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN tenant_id VARCHAR(64) DEFAULT NULL', $table));
            $this->addSql(sprintf("UPDATE %s SET tenant_id = COALESCE(object_tenant, 'default')", $table));
            $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN tenant_id SET NOT NULL', $table));
        }

        $this->restoreScopeConstraints();
        $this->restoreReviewIndexes();

        foreach (['module', 'category', 'featured', 'menu', 'product_type', 'project_type', 'review'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_scope', $table));
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_tenant', $table));
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_organization', $table));
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_owner', $table));
        }
    }

    private function addScopeColumns(string $table): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_scope VARCHAR(190) DEFAULT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_tenant VARCHAR(190) DEFAULT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_organization VARCHAR(190) DEFAULT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_owner VARCHAR(190) DEFAULT NULL', $table));
    }

    private function backfillScopeColumns(string $table): void
    {
        $this->addSql(sprintf("UPDATE %s SET object_tenant = tenant_id, object_scope = NULL, object_organization = NULL, object_owner = NULL", $table));
    }

    private function replaceScopeConstraints(): void
    {
        $this->dropScopeConstraints();

        foreach (['module', 'featured', 'product_type', 'project_type'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (object_tenant, %s)', $table, $this->oldCodeConstraintName($table), 'object_code'));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (object_tenant, %s)', $table, $this->oldSlugConstraintName($table), 'object_slug'));
        }

        $this->addSql('ALTER TABLE category ADD CONSTRAINT uniq_category_tenant_module_slug UNIQUE (object_tenant, module_id, object_slug)');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT uniq_menu_tenant_module_slug UNIQUE (object_tenant, module_id, object_slug)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT uniq_review_tenant_slug UNIQUE (object_tenant, object_slug)');
    }

    private function restoreScopeConstraints(): void
    {
        $this->dropScopeConstraints();

        foreach (['module', 'featured', 'product_type', 'project_type'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (tenant_id, %s)', $table, $this->oldCodeConstraintName($table), 'object_code'));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (tenant_id, %s)', $table, $this->oldSlugConstraintName($table), 'object_slug'));
        }

        $this->addSql('ALTER TABLE category ADD CONSTRAINT uniq_category_tenant_module_slug UNIQUE (tenant_id, module_id, object_slug)');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT uniq_menu_tenant_module_slug UNIQUE (tenant_id, module_id, object_slug)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT uniq_review_tenant_slug UNIQUE (tenant_id, object_slug)');
    }

    private function dropScopeConstraints(): void
    {
        foreach (['module', 'featured', 'product_type', 'project_type'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->oldCodeConstraintName($table)));
            $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->oldSlugConstraintName($table)));
        }

        $this->addSql('ALTER TABLE category DROP CONSTRAINT IF EXISTS uniq_category_tenant_module_slug');
        $this->addSql('ALTER TABLE menu DROP CONSTRAINT IF EXISTS uniq_menu_tenant_module_slug');
        $this->addSql('ALTER TABLE review DROP CONSTRAINT IF EXISTS uniq_review_tenant_slug');
    }

    private function replaceReviewIndexes(): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_review_subject');
        $this->addSql('DROP INDEX IF EXISTS idx_review_author');
        $this->addSql('CREATE INDEX idx_review_subject ON review (object_tenant, subject_type, subject_id)');
        $this->addSql('CREATE INDEX idx_review_author ON review (object_tenant, author_id)');
    }

    private function restoreReviewIndexes(): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_review_subject');
        $this->addSql('DROP INDEX IF EXISTS idx_review_author');
        $this->addSql('CREATE INDEX idx_review_subject ON review (tenant_id, subject_type, subject_id)');
        $this->addSql('CREATE INDEX idx_review_author ON review (tenant_id, author_id)');
    }

    private function oldCodeConstraintName(string $table): string
    {
        return match ($table) {
            'module' => 'uniq_module_tenant_code',
            'featured' => 'uniq_featured_tenant_code',
            'product_type' => 'uniq_product_type_tenant_code',
            'project_type' => 'uniq_project_type_tenant_code',
            default => throw new \InvalidArgumentException(sprintf('Unsupported table "%s".', $table)),
        };
    }

    private function oldSlugConstraintName(string $table): string
    {
        return match ($table) {
            'module' => 'uniq_module_tenant_slug',
            'featured' => 'uniq_featured_tenant_slug',
            'product_type' => 'uniq_product_type_tenant_slug',
            'project_type' => 'uniq_project_type_tenant_slug',
            default => throw new \InvalidArgumentException(sprintf('Unsupported table "%s".', $table)),
        };
    }
}
