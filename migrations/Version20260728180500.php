<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728180500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create multi-catalog roots, categories, and projection tables for Cataloging.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS ltree');
        $this->addSql("CREATE TABLE IF NOT EXISTS catalog (id SERIAL PRIMARY KEY, object_code VARCHAR(190) DEFAULT NULL, object_active BOOLEAN NOT NULL DEFAULT TRUE, object_enabled BOOLEAN NOT NULL DEFAULT TRUE, object_status VARCHAR(64) DEFAULT NULL, object_created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, object_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, object_created_by VARCHAR(190) DEFAULT NULL, object_modified_by VARCHAR(190) DEFAULT NULL, name VARCHAR(160) NOT NULL, purpose VARCHAR(48) NOT NULL, tenant VARCHAR(64) NOT NULL DEFAULT 'default')");
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_catalog_tenant_code ON catalog (tenant, object_code)');
        $this->addSql("CREATE TABLE IF NOT EXISTS category (id SERIAL PRIMARY KEY, catalog_id INT NOT NULL, name_entity VARCHAR(160) NOT NULL, slug VARCHAR(36) NOT NULL, parent_id VARCHAR(26) DEFAULT NULL, path LTREE NOT NULL, depth INT NOT NULL, locale VARCHAR(12) DEFAULT NULL, tenant VARCHAR(64) NOT NULL DEFAULT 'default', workflow_state VARCHAR(32) NOT NULL DEFAULT 'draft', published BOOLEAN NOT NULL DEFAULT FALSE, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, icon_url VARCHAR(255) DEFAULT NULL, CONSTRAINT fk_category_catalog FOREIGN KEY (catalog_id) REFERENCES catalog (id) ON DELETE RESTRICT)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_path ON category USING GIST (path)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_catalog_path ON category (catalog_id, path)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_tenant_workflow ON category (tenant, workflow_state)');
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS uniq_category_catalog_parent_slug ON category (catalog_id, COALESCE(parent_id, ''), slug)");
        $this->addSql("CREATE TABLE IF NOT EXISTS category_projection (id VARCHAR(26) PRIMARY KEY, slug VARCHAR(180) NOT NULL, name_entity VARCHAR(160) NOT NULL, parent_id VARCHAR(26) DEFAULT NULL, path VARCHAR(255) NOT NULL, locale VARCHAR(12) DEFAULT NULL, tenant VARCHAR(64) NOT NULL DEFAULT 'default', workflow_state VARCHAR(32) NOT NULL DEFAULT 'draft', published BOOLEAN NOT NULL DEFAULT FALSE, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_projection_path ON category_projection (path)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_projection_name ON category_projection (name_entity)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_projection_tenant_locale ON category_projection (tenant, locale)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_projection_workflow_state ON category_projection (workflow_state)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_category_projection_updated_at ON category_projection (updated_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS category_projection');
        $this->addSql('DROP TABLE IF EXISTS category');
        $this->addSql('DROP TABLE IF EXISTS catalog');
    }
}
