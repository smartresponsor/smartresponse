<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523172000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move repeated code and slug identifiers to Objecting identity/code embeddables.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->migrateCodeSlugTable('module', true, true);
        $this->migrateCodeSlugTable('category', false, true);
        $this->migrateCodeSlugTable('featured', true, true);
        $this->migrateCodeSlugTable('menu', false, true);
        $this->migrateCodeSlugTable('product_type', true, true);
        $this->migrateCodeSlugTable('project_type', true, true);
        $this->migrateSlugOnlyTable('review', true);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL120Platform,
            "Migration can only be executed safely on '\\Doctrine\\DBAL\\Platforms\\PostgreSQL120Platform'."
        );

        $this->restoreCodeSlugTable('module', true, true);
        $this->restoreCodeSlugTable('category', false, true);
        $this->restoreCodeSlugTable('featured', true, true);
        $this->restoreCodeSlugTable('menu', false, true);
        $this->restoreCodeSlugTable('product_type', true, true);
        $this->restoreCodeSlugTable('project_type', true, true);
        $this->restoreSlugOnlyTable('review', true);
    }

    private function migrateCodeSlugTable(string $table, bool $hasCodeConstraint, bool $hasSlugConstraint): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_uuid VARCHAR(36) DEFAULT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_code VARCHAR(190) DEFAULT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_slug VARCHAR(190) DEFAULT NULL', $table));
        $this->addSql(sprintf("UPDATE %s SET object_uuid = %s, object_code = code, object_slug = slug", $table, $this->uuidExpression($table)));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN object_uuid SET NOT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN object_code SET NOT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN object_slug SET NOT NULL', $table));

        if ($hasCodeConstraint) {
            $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->oldCodeConstraintName($table)));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (tenant_id, object_code)', $table, $this->oldCodeConstraintName($table)));
        }

        if ($hasSlugConstraint) {
            $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->oldSlugConstraintName($table)));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (tenant_id, %s)', $table, $this->oldSlugConstraintName($table), $this->slugUniqueColumns($table)));
        }

        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (object_uuid)', $table, $this->uuidConstraintName($table)));

        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN code', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN slug', $table));
    }

    private function restoreCodeSlugTable(string $table, bool $hasCodeConstraint, bool $hasSlugConstraint): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN code VARCHAR(190) DEFAULT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN slug VARCHAR(190) DEFAULT NULL', $table));
        $this->addSql(sprintf('UPDATE %s SET code = object_code, slug = object_slug', $table));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN code SET NOT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN slug SET NOT NULL', $table));

        $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->uuidConstraintName($table)));

        if ($hasCodeConstraint) {
            $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->oldCodeConstraintName($table)));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (tenant_id, code)', $table, $this->oldCodeConstraintName($table)));
        }

        if ($hasSlugConstraint) {
            $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->oldSlugConstraintName($table)));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (tenant_id, %s)', $table, $this->oldSlugConstraintName($table), $this->slugUniqueColumns($table, false)));
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_uuid', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_code', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_slug', $table));
    }

    private function migrateSlugOnlyTable(string $table, bool $hasSlugConstraint): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_uuid VARCHAR(36) DEFAULT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN object_slug VARCHAR(190) DEFAULT NULL', $table));
        $this->addSql(sprintf("UPDATE %s SET object_uuid = %s, object_slug = slug", $table, $this->uuidExpression($table)));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN object_uuid SET NOT NULL', $table));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN object_slug SET NOT NULL', $table));

        if ($hasSlugConstraint) {
            $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->oldSlugConstraintName($table)));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (tenant_id, object_slug)', $table, $this->oldSlugConstraintName($table)));
        }

        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (object_uuid)', $table, $this->uuidConstraintName($table)));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN slug', $table));
    }

    private function restoreSlugOnlyTable(string $table, bool $hasSlugConstraint): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN slug VARCHAR(190) DEFAULT NULL', $table));
        $this->addSql(sprintf('UPDATE %s SET slug = object_slug', $table));
        $this->addSql(sprintf('ALTER TABLE %s ALTER COLUMN slug SET NOT NULL', $table));

        $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->uuidConstraintName($table)));

        if ($hasSlugConstraint) {
            $this->addSql(sprintf('ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s', $table, $this->oldSlugConstraintName($table)));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s UNIQUE (tenant_id, slug)', $table, $this->oldSlugConstraintName($table)));
        }

        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_uuid', $table));
        $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN object_slug', $table));
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
            'category' => 'uniq_category_tenant_module_slug',
            'featured' => 'uniq_featured_tenant_slug',
            'menu' => 'uniq_menu_tenant_module_slug',
            'product_type' => 'uniq_product_type_tenant_slug',
            'project_type' => 'uniq_project_type_tenant_slug',
            'review' => 'uniq_review_tenant_slug',
            default => throw new \InvalidArgumentException(sprintf('Unsupported table "%s".', $table)),
        };
    }

    private function uuidConstraintName(string $table): string
    {
        return sprintf('uniq_%s_object_uuid', $table);
    }

    private function slugUniqueColumns(string $table, bool $newColumns = true): string
    {
        return match ($table) {
            'menu' => 'tenant_id, module_id, ' . ($newColumns ? 'object_slug' : 'slug'),
            'category' => 'tenant_id, module_id, ' . ($newColumns ? 'object_slug' : 'slug'),
            default => 'tenant_id, ' . ($newColumns ? 'object_slug' : 'slug'),
        };
    }

    private function uuidExpression(string $table): string
    {
        $hash = sprintf("md5('%s:' || id::text)", $table);

        return sprintf(
            "substr(%s, 1, 8) || '-' || substr(%s, 9, 4) || '-4' || substr(%s, 13, 3) || '-8' || substr(%s, 17, 3) || '-' || substr(%s, 20, 12)",
            $hash,
            $hash,
            $hash,
            $hash,
            $hash
        );
    }
}
